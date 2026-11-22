<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);

        return Inertia::render('Settings/Index', [
            'clinic' => $request->user()->clinic,
            'branches' => Branch::orderBy('id')->get(),
            'users' => User::orderBy('id')->get(['id', 'name', 'email', 'phone', 'role', 'branch_id', 'is_active']),
            'roles' => User::ROLES,
            'setupDismissed' => $request->user()->clinic?->setup_dismissed_at !== null,
            'bookingEnabled' => (bool) $request->user()->clinic?->booking_enabled,
            'bookingUrl' => $request->user()->clinic?->booking_token ? url('/b/'.$request->user()->clinic->booking_token) : null,
            'ai' => ['enabled' => (bool) $request->user()->clinic?->ai_enabled, 'url' => url('/mcp'),
                'connections' => \App\Models\OauthToken::where('clinic_id', $request->user()->clinic_id)->whereNull('revoked_at')->whereNull('rotated_at')->where('refresh_expires_at', '>', now())->with('user:id,name')->orderByDesc('id')
                    ->get(['id', 'family', 'client_id', 'user_id', 'last_used_at', 'created_at'])->map(fn ($t) => ['family' => $t->family, 'app' => \App\Models\OauthClient::where('client_id', $t->client_id)->value('name') ?: 'AI app', 'user' => $t->user?->name, 'last_used_at' => $t->last_used_at, 'created_at' => $t->created_at])],
        ]);
    }

    public function aiToggle(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);
        $d = $request->validate(['enabled' => ['required', 'boolean']]);
        $clinic = $request->user()->clinic;
        $clinic->forceFill(['ai_enabled' => $d['enabled']])->save();
        \App\Models\AuditLog::create(['clinic_id' => $clinic->id, 'user_id' => $request->user()->id, 'action' => $d['enabled'] ? 'settings.ai_on' : 'settings.ai_off', 'changes' => null, 'ip' => $request->ip(), 'user_agent' => substr((string) $request->userAgent(), 0, 255)]);

        return back();
    }

    /** Disconnect one AI connection (whole token family). Allowed even for a read-only clinic: revoking must never be blocked. */
    public function aiConnectionRevoke(Request $request, string $family)
    {
        abort_unless($request->user()->isOwner(), 403);
        \App\Models\OauthToken::where('clinic_id', $request->user()->clinic_id)->where('family', $family)->update(['revoked_at' => now()]);

        return back();
    }

    public function updateClinic(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_rate_bp' => ['required', 'integer', 'min:0', 'max:10000'],
            'invoice_prefix' => ['required', 'string', 'max:10', 'alpha_dash'],
            'timezone' => ['required', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'booking_enabled' => ['sometimes', 'boolean'],
        ]);

        $data['currency'] = strtoupper($data['currency']);

        $request->user()->clinic->update($data);

        return back();
    }

    public function storeBranch(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('branches', 'name')->where('clinic_id', $request->user()->clinic_id)],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        Branch::create($data);

        return back();
    }

    public function storeUser(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email',
                Rule::unique('users', 'email')->where('clinic_id', $request->user()->clinic_id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', Rule::in(User::ROLES)],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'password' => ['required', Password::min(8)],
        ]);

        User::create($data);

        return back();
    }

    public function toggleUser(Request $request, User $user)
    {
        abort_unless($request->user()->isOwner(), 403);
        abort_if($user->id === $request->user()->id, 422);

        $user->update(['is_active' => ! $user->is_active]);

        return back();
    }
}
