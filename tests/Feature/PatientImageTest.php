<?php

namespace Tests\Feature;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PatientImageTest extends TestCase
{
    use RefreshDatabase;

    private function fakeImage(string $name = 'xray.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 800, 600);
    }

    public function test_clinical_user_can_upload_list_and_view_patient_image(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);

        $this->actingAs($user)->post("/patients/{$patient->id}/images", [
            'image' => $this->fakeImage(),
            'kind' => 'xray',
            'title' => 'Panorama',
            'taken_on' => '2026-11-01',
        ])->assertRedirect();

        $image = PatientImage::first();
        $this->assertNotNull($image);
        $this->assertSame('xray', $image->kind);
        $this->assertSame($patient->id, $image->patient_id);
        $this->assertSame($user->clinic_id, $image->clinic_id);
        Storage::disk('local')->assertExists($image->path);

        $this->actingAs($user)->get("/patient-images/{$image->id}/file")->assertOk();
    }

    public function test_non_clinical_user_cannot_upload_or_view_images(): void
    {
        Storage::fake('local');
        $user = User::factory()->receptionist()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);
        $image = $patient->images()->create([
            'clinic_id' => $user->clinic_id, 'user_id' => null, 'kind' => 'xray', 'path' => 'patient-images/x/y.jpg',
            'original_name' => 'y.jpg', 'mime' => 'image/jpeg', 'size' => 10,
        ]);

        $this->actingAs($user)->post("/patients/{$patient->id}/images", [
            'image' => $this->fakeImage(), 'kind' => 'xray',
        ])->assertForbidden();

        $this->actingAs($user)->get("/patient-images/{$image->id}/file")->assertForbidden();
        $this->actingAs($user)->delete("/patient-images/{$image->id}")->assertForbidden();
    }

    public function test_images_are_scoped_to_clinic(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $otherClinic = Clinic::factory()->create();
        $otherPatient = Patient::factory()->create(['clinic_id' => $otherClinic->id]);
        $image = $otherPatient->images()->create([
            'clinic_id' => $otherClinic->id, 'user_id' => null, 'kind' => 'xray', 'path' => 'patient-images/x/z.jpg',
            'original_name' => 'z.jpg', 'mime' => 'image/jpeg', 'size' => 10,
        ]);

        // Cross-tenant route-model access must not resolve the record at all.
        $this->actingAs($user)->get("/patient-images/{$image->id}/file")->assertNotFound();
        $this->actingAs($user)->delete("/patient-images/{$image->id}")->assertNotFound();
    }

    public function test_upload_validation_rejects_non_image_and_missing_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);

        $this->actingAs($user)->post("/patients/{$patient->id}/images", [
            'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            'kind' => 'xray',
        ])->assertSessionHasErrors('image');

        $this->actingAs($user)->post("/patients/{$patient->id}/images", [
            'kind' => 'xray',
        ])->assertSessionHasErrors('image');
    }

    public function test_delete_removes_record_and_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $patient = Patient::factory()->create(['clinic_id' => $user->clinic_id]);
        Storage::disk('local')->put('patient-images/a/b.jpg', 'x');
        $image = $patient->images()->create([
            'clinic_id' => $user->clinic_id, 'user_id' => $user->id, 'kind' => 'photo', 'path' => 'patient-images/a/b.jpg',
            'original_name' => 'b.jpg', 'mime' => 'image/jpeg', 'size' => 1,
        ]);

        $this->actingAs($user)->delete("/patient-images/{$image->id}")->assertRedirect();
        $this->assertDatabaseMissing('patient_images', ['id' => $image->id]);
        Storage::disk('local')->assertMissing('patient-images/a/b.jpg');
    }
}
