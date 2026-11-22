<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Synthetic development data only. No real patient information.
 * Local login (dev only): owner@asnan.test / password
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $clinic = Clinic::create([
            'name' => 'عيادة الابتسامة لطب الأسنان',
            'country' => 'EG',
            'currency' => 'EGP',
            'timezone' => 'Africa/Cairo',
            'tax_rate_bp' => 1400,
            'invoice_prefix' => 'INV',
        ]);

        $branch = Branch::create([
            'clinic_id' => $clinic->id,
            'name' => 'الفرع الرئيسي',
            'address' => '١٢ شارع التحرير، القاهرة',
        ]);

        User::create([
            'clinic_id' => $clinic->id, 'branch_id' => $branch->id,
            'name' => 'د. مالك العيادة', 'email' => 'owner@asnan.test',
            'password' => 'password', 'role' => User::ROLE_OWNER,
        ]);
        User::create([
            'clinic_id' => $clinic->id, 'branch_id' => $branch->id,
            'name' => 'د. طبيب أسنان', 'email' => 'dentist@asnan.test',
            'password' => 'password', 'role' => User::ROLE_DENTIST,
        ]);
        User::create([
            'clinic_id' => $clinic->id, 'branch_id' => $branch->id,
            'name' => 'موظفة الاستقبال', 'email' => 'reception@asnan.test',
            'password' => 'password', 'role' => User::ROLE_RECEPTIONIST,
        ]);

        $services = [
            ['code' => 'EXAM', 'name_ar' => 'كشف وتشخيص', 'category' => 'exam', 'default_price_minor' => 30000, 'duration_min' => 20],
            ['code' => 'CLEAN', 'name_ar' => 'تنظيف وتلميع الأسنان', 'category' => 'preventive', 'default_price_minor' => 60000, 'duration_min' => 40],
            ['code' => 'FILL', 'name_ar' => 'حشو ضوئي (كمبوزيت)', 'category' => 'restorative', 'default_price_minor' => 90000, 'duration_min' => 45],
            ['code' => 'RCT', 'name_ar' => 'علاج عصب', 'category' => 'endo', 'default_price_minor' => 250000, 'duration_min' => 60],
            ['code' => 'XTR', 'name_ar' => 'خلع ضرس', 'category' => 'surgery', 'default_price_minor' => 80000, 'duration_min' => 30],
            ['code' => 'CRWN', 'name_ar' => 'تركيب تاج خزف', 'category' => 'prostho', 'default_price_minor' => 350000, 'duration_min' => 60],
            ['code' => 'SCALE', 'name_ar' => 'إزالة جير', 'category' => 'perio', 'default_price_minor' => 50000, 'duration_min' => 30],
        ];
        foreach ($services as $s) {
            Service::create([...$s, 'clinic_id' => $clinic->id]);
        }

        // Synthetic patients — fictional names/numbers for development only.
        $patients = [
            ['full_name' => 'مريض تجريبي أول', 'phone' => '01000000001', 'gender' => 'male'],
            ['full_name' => 'مريضة تجريبية ثانية', 'phone' => '01000000002', 'gender' => 'female'],
        ];
        foreach ($patients as $i => $p) {
            Patient::create([...$p, 'clinic_id' => $clinic->id, 'branch_id' => $branch->id, 'file_no' => $i + 1, 'medical_alerts' => []]);
        }
        $clinic->update(['next_patient_seq' => count($patients) + 1]);
    }
}
