<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index()
    {
        return Inertia::render('Services/Index', [
            'services' => Service::orderBy('category')->orderBy('name_ar')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Service::create($this->validateData($request));

        return back();
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validateData($request, $service->id));

        return back();
    }

    public function destroy(Service $service)
    {
        abort_unless(request()->user()->isOwner(), 403);
        $service->delete();

        return back();
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:32',
                Rule::unique('services', 'code')->where('clinic_id', $request->user()->clinic_id)->ignore($ignoreId)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'category' => ['required', Rule::in(Service::CATEGORIES)],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_min' => ['required', 'integer', 'min:5', 'max:480'],
            'is_active' => ['boolean'],
        ]);

        $data['default_price_minor'] = (int) round(((float) $data['price']) * 100);
        unset($data['price']);

        return $data;
    }
}
