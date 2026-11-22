<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\OauthClient;
use App\Models\OauthCode;
use App\Models\OauthToken;
use App\Models\Patient;
use App\Models\Recall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiConnectorTest extends TestCase
{
    use RefreshDatabase;

    private function rpc(?string $token, string $method, array $params = [], $id = 1)
    {
        Auth::guard('web')->forgetUser();

        return $this->withHeaders($token ? ['Authorization' => 'Bearer '.$token] : [])->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params]);
    }

    /** Run the full OAuth flow in-process and return the raw access token. */
    private function connect(User $owner): string
    {
        $owner->clinic->forceFill(['ai_enabled' => true])->save();
        $client = OauthClient::firstOrCreate(['client_id' => 'oc_test'], ['name' => 'Claude', 'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback']]);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', 'test-verifier-'.str_repeat('a', 40), true)), '+/', '-_'), '=');
        $this->actingAs($owner, 'web')->get('/oauth/authorize?'.http_build_query([
            'response_type' => 'code', 'client_id' => 'oc_test', 'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'code_challenge' => $challenge, 'code_challenge_method' => 'S256', 'state' => 's1',
        ]))->assertInertia(fn ($p) => $p->where('blocked', null)->where('clinic', $owner->clinic->name));
        $rid = session('oauth_req')['rid'];
        $this->actingAs($owner, 'web')->post('/oauth/authorize', ['rid' => $rid, 'decision' => 'allow']);
        $code = OauthCode::first();
        $this->assertNotNull($code);
        // Exchange the code with the matching PKCE verifier.
        $verifier = 'test-verifier-'.str_repeat('a', 40);
        $res = $this->postJson('/oauth/token', ['grant_type' => 'authorization_code', 'client_id' => 'oc_test', 'code' => '__raw__', 'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback', 'code_verifier' => $verifier]);
        $res->assertStatus(400); // placeholder code must fail

        return $this->exchangeForReal($owner, $client);
    }

    private function exchangeForReal(User $owner, OauthClient $client): string
    {
        // Re-run consent to capture the raw code from the redirect target.
        $challenge = rtrim(strtr(base64_encode(hash('sha256', 'v2-'.str_repeat('b', 44), true)), '+/', '-_'), '=');
        $this->actingAs($owner, 'web')->get('/oauth/authorize?'.http_build_query([
            'response_type' => 'code', 'client_id' => $client->client_id, 'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'code_challenge' => $challenge, 'code_challenge_method' => 'S256',
        ]));
        $rid = session('oauth_req')['rid'];
        $resp = $this->actingAs($owner, 'web')->post('/oauth/authorize', ['rid' => $rid, 'decision' => 'allow']);
        $loc = $resp->headers->get('X-Inertia-Location') ?? $resp->headers->get('Location');
        parse_str(parse_url($loc, PHP_URL_QUERY), $q);
        $this->assertArrayHasKey('code', $q);
        $res = $this->postJson('/oauth/token', ['grant_type' => 'authorization_code', 'client_id' => $client->client_id, 'code' => $q['code'], 'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback', 'code_verifier' => 'v2-'.str_repeat('b', 44)]);
        $res->assertOk()->assertJsonPath('token_type', 'Bearer');

        return $res->json('access_token');
    }

    public function test_mcp_requires_a_valid_oauth_token_and_enabled_clinic(): void
    {
        $owner = User::factory()->create();
        $this->rpc(null, 'tools/list')->assertStatus(401)->assertHeader('WWW-Authenticate');
        $this->rpc('tao_nope', 'tools/list')->assertStatus(401);

        $token = $this->connect($owner);
        $names = collect($this->rpc($token, 'tools/list')->assertOk()->json('result.tools'))->pluck('name')->all();
        $this->assertEquals(['today_schedule', 'search_patients', 'get_patient', 'recalls_due', 'revenue_summary'], $names);

        // Switching the clinic switch off kills the token immediately.
        $owner->clinic->forceFill(['ai_enabled' => false])->save();
        $this->rpc($token, 'tools/list')->assertStatus(401);
    }

    public function test_tools_are_clinic_scoped_read_only_and_hide_clinical_data(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create(); // different clinic
        $branch = \App\Models\Branch::factory()->create(['clinic_id' => $owner->clinic_id]);
        $mine = Patient::factory()->create(['clinic_id' => $owner->clinic_id, 'branch_id' => $branch->id, 'full_name' => 'Mine Patient', 'medical_alerts' => ['penicillin'], 'notes' => 'secret clinical note']);
        $theirs = Patient::factory()->create(['clinic_id' => $other->clinic_id, 'full_name' => 'OtherClinic Patient']);
        Appointment::create(['clinic_id' => $owner->clinic_id, 'branch_id' => $branch->id, 'patient_id' => $mine->id, 'dentist_id' => $owner->id, 'chair' => '1', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'status' => 'scheduled', 'reason' => 'كشف', 'notes' => null, 'booked_via' => 'staff']);
        Recall::create(['clinic_id' => $owner->clinic_id, 'patient_id' => $mine->id, 'service_id' => null, 'due_date' => now()->subDay()->toDateString(), 'status' => 'pending', 'reason' => 'تنظيف دوري', 'notes' => null, 'created_by' => $owner->id]);
        Invoice::create(['clinic_id' => $owner->clinic_id, 'branch_id' => $branch->id, 'patient_id' => $mine->id, 'number' => 'INV-1', 'status' => 'issued', 'currency' => 'EGP', 'subtotal_minor' => 100000, 'discount_minor' => 0, 'tax_rate_bp' => 0, 'tax_minor' => 0, 'total_minor' => 100000, 'paid_minor' => 40000, 'issued_at' => now(), 'due_at' => null, 'notes' => null]);

        $token = $this->connect($owner);

        $this->rpc($token, 'tools/call', ['name' => 'search_patients', 'arguments' => ['query' => 'Patient']])->assertOk();
        $one = $this->rpc($token, 'tools/call', ['name' => 'get_patient', 'arguments' => ['id' => $mine->id]])->assertOk()->json('result.content.0.text');
        $this->assertStringContainsString('Mine Patient', $one);
        $this->assertStringNotContainsString('penicillin', $one);
        $this->assertStringNotContainsString('secret clinical note', $one);
        $this->assertStringContainsString('"outstanding":600', $one);

        // Cross-clinic reads are impossible.
        $this->rpc($token, 'tools/call', ['name' => 'get_patient', 'arguments' => ['id' => $theirs->id]])->assertOk()->assertJsonPath('error.code', -32602);
        $search2 = $this->rpc($token, 'tools/call', ['name' => 'search_patients', 'arguments' => ['query' => 'OtherClinic']])->json('result.content.0.text');
        $this->assertStringNotContainsString('OtherClinic Patient', $search2);

        // Schedule, recalls and revenue answer.
        $this->rpc($token, 'tools/call', ['name' => 'today_schedule', 'arguments' => []])->assertOk();
        $rec = $this->rpc($token, 'tools/call', ['name' => 'recalls_due', 'arguments' => []])->json('result.content.0.text');
        $this->assertStringContainsString('تنظيف دوري', $rec);
        $rev = $this->rpc($token, 'tools/call', ['name' => 'revenue_summary', 'arguments' => []])->json('result.content.0.text');
        $this->assertStringContainsString('"invoiced":1000', $rev);

        // No tool writes anything.
        $before = Patient::withoutGlobalScopes()->count();
        $this->rpc($token, 'tools/call', ['name' => 'delete_patient', 'arguments' => ['id' => $mine->id]])->assertJsonPath('error.code', -32602);
        $this->assertSame($before, Patient::withoutGlobalScopes()->count());
    }

    public function test_non_owner_cannot_authorize_and_token_dies_when_owner_demoted(): void
    {
        $owner = User::factory()->create();
        $owner->clinic->forceFill(['ai_enabled' => true])->save();
        $dentist = User::factory()->dentist()->create(['clinic_id' => $owner->clinic_id]);
        OauthClient::create(['client_id' => 'oc_x', 'name' => 'Claude', 'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback']]);
        $challenge = str_repeat('c', 43);
        $this->actingAs($dentist, 'web')->get('/oauth/authorize?'.http_build_query([
            'response_type' => 'code', 'client_id' => 'oc_x', 'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'code_challenge' => $challenge, 'code_challenge_method' => 'S256',
        ]))->assertInertia(fn ($p) => $p->where('blocked', 'not_owner'));
        $this->assertTrue(true);

        $token = $this->connect($owner);
        $this->rpc($token, 'tools/list')->assertOk();
        $owner->forceFill(['role' => User::ROLE_DENTIST])->save();
        $this->rpc($token, 'tools/list')->assertStatus(401);
    }

    public function test_refresh_rotation_and_revoke(): void
    {
        $owner = User::factory()->create();
        $access = $this->connect($owner);
        $row = OauthToken::first();
        // A rotated access token dies immediately (refresh rotation marks the old pair).
        $row->forceFill(['rotated_at' => now()])->save();
        $this->rpc($access, 'tools/list')->assertStatus(401); // rotated access token dies

        // Revoke endpoint always answers 200 and ends the family.
        $owner2 = User::factory()->create();
        $access2 = $this->connect($owner2);
        $this->postJson('/oauth/revoke', ['token' => $access2])->assertOk();
        $this->rpc($access2, 'tools/list')->assertStatus(401);
        $this->assertNotNull(OauthToken::latest('id')->first()->revoked_at);
    }

    public function test_registration_is_limited_to_claude_callbacks_and_owner_settings_flow(): void
    {
        $this->postJson('/oauth/register', ['client_name' => 'Evil', 'redirect_uris' => ['https://evil.example.com/cb']])->assertStatus(400);
        $this->postJson('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback']])->assertStatus(201)->assertJsonPath('token_endpoint_auth_method', 'none');

        $owner = User::factory()->create();
        $this->actingAs($owner, 'web')->get('/settings')->assertInertia(fn ($p) => $p->where('ai.enabled', false));
        $this->actingAs($owner, 'web')->patch('/settings/ai', ['enabled' => true])->assertRedirect();
        $this->assertTrue($owner->clinic->fresh()->ai_enabled);
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.ai_on', 'clinic_id' => $owner->clinic_id]);
    }
}
