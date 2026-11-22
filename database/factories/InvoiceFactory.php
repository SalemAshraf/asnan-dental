<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $branch = Branch::factory()->create();

        return [
            'clinic_id' => $branch->clinic_id,
            'branch_id' => $branch->id,
            'patient_id' => Patient::factory()->state(['clinic_id' => $branch->clinic_id]),
            'status' => 'draft',
            'currency' => 'EGP',
            'tax_rate_bp' => 0,
        ];
    }
}
