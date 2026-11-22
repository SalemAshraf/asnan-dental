<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientImageController extends Controller
{
    private function authorizeClinical(): void
    {
        abort_unless(request()->user()->isClinical(), 403);
    }

    public function store(Request $request, Patient $patient)
    {
        $this->authorizeClinical();

        $data = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'kind' => ['required', 'in:' . implode(',', PatientImage::KINDS)],
            'title' => ['nullable', 'string', 'max:190'],
            'taken_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $file = $request->file('image');
        $path = $file->storeAs(
            'patient-images/' . $patient->clinic_id . '/' . $patient->id,
            (string) Str::uuid() . '.' . strtolower($file->getClientOriginalExtension()),
            'local'
        );

        $patient->images()->create([
            'user_id' => $request->user()->id,
            'kind' => $data['kind'],
            'title' => $data['title'] ?? null,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'taken_on' => $data['taken_on'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back();
    }

    public function file(PatientImage $image)
    {
        $this->authorizeClinical();

        abort_unless(Storage::disk('local')->exists($image->path), 404);

        return response()->file(Storage::disk('local')->path($image->path), [
            'Content-Type' => $image->mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($image->original_name) . '"',
        ]);
    }

    public function destroy(PatientImage $image)
    {
        $this->authorizeClinical();

        Storage::disk('local')->delete($image->path);
        $image->delete();

        return back();
    }
}
