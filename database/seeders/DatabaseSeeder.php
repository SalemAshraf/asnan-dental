<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Clinic;
use App\Models\ConsentRecord;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OdontogramEntry;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Recall;
use App\Models\Service;
use App\Models\TreatmentPlan;
use App\Models\TreatmentPlanItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Synthetic development data only. No real patient information: every name,
 * phone number and record below is fictional and generated for demos.
 * Local login (dev only): owner@asnan.test / password (or DEMO_PASSWORD when set)
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
            // No statutory rate is shipped. 0 = no tax until the clinic sets its own
            // rate in Settings after verifying the rules that apply to it.
            'tax_rate_bp' => 0,
            'invoice_prefix' => 'INV',
        ]);

        $branch = Branch::create([
            'clinic_id' => $clinic->id,
            'name' => 'الفرع الرئيسي',
            'address' => '١٢ شارع التحرير، القاهرة',
        ]);

        // Demo logins share one password. Outside local env it MUST come from
        // DEMO_PASSWORD so a public repo never ships a reusable credential;
        // choose it per deployment and store it in a password manager.
        $demoPassword = env('DEMO_PASSWORD') ?: (app()->environment('local') ? 'password' : null);
        if (! $demoPassword) {
            throw new RuntimeException('Set DEMO_PASSWORD in the environment before seeding demo users outside local.');
        }

        $owner = User::create([
            'clinic_id' => $clinic->id, 'branch_id' => $branch->id,
            'name' => 'د. مالك العيادة', 'email' => 'owner@asnan.test',
            'password' => $demoPassword, 'role' => User::ROLE_OWNER,
        ]);
        $dentist = User::create([
            'clinic_id' => $clinic->id, 'branch_id' => $branch->id,
            'name' => 'د. طبيب أسنان', 'email' => 'dentist@asnan.test',
            'password' => $demoPassword, 'role' => User::ROLE_DENTIST,
        ]);
        User::create([
            'clinic_id' => $clinic->id, 'branch_id' => $branch->id,
            'name' => 'موظفة الاستقبال', 'email' => 'reception@asnan.test',
            'password' => $demoPassword, 'role' => User::ROLE_RECEPTIONIST,
        ]);

        $services = [];
        foreach ([
            ['EXAM', 'كشف وتشخيص', 'exam', 30000, 20],
            ['CLEAN', 'تنظيف وتلميع الأسنان', 'preventive', 60000, 40],
            ['FILL', 'حشو ضوئي (كمبوزيت)', 'restorative', 90000, 45],
            ['RCT', 'علاج عصب', 'endo', 250000, 60],
            ['XTR', 'خلع ضرس', 'surgery', 80000, 30],
            ['CRWN', 'تركيب تاج خزف', 'prostho', 350000, 60],
            ['SCALE', 'إزالة جير', 'perio', 50000, 30],
            ['WHT', 'تبييض الأسنان', 'cosmetic', 400000, 60],
        ] as [$code, $name, $cat, $price, $dur]) {
            $services[$code] = Service::create([
                'clinic_id' => $clinic->id, 'code' => $code, 'name_ar' => $name,
                'category' => $cat, 'default_price_minor' => $price, 'duration_min' => $dur,
            ]);
        }

        // Synthetic patients — fictional names/numbers for development only.
        $names = [
            ['أحمد سمير عبد العال', 'male', -1], ['منى حسن خليل', 'female', -2], ['كريم فتحي السيد', 'male', -3],
            ['سارة محمود عوض', 'female', -8], ['يوسف إبراهيم ناجي', 'male', -12], ['هبة عادل رزق', 'female', -20],
            ['محمود سيد قطب', 'male', -25], ['نورهان أشرف فؤاد', 'female', -40], ['عمرو جمال حسب الله', 'male', -55],
            ['دينا طارق مصطفى', 'female', -70], ['حسن علي مراد', 'male', -90], ['رنا وائل شعبان', 'female', -120],
        ];
        $patients = [];
        foreach ($names as $i => [$name, $gender, $daysAgo]) {
            $patients[] = Patient::create([
                'clinic_id' => $clinic->id, 'branch_id' => $branch->id, 'file_no' => $i + 1,
                'full_name' => $name, 'phone' => '010100000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'whatsapp' => '010100000'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'gender' => $gender, 'medical_alerts' => $i === 3 ? ['حساسية من البنسلين'] : [],
                'created_at' => now()->addDays($daysAgo), 'updated_at' => now()->addDays($daysAgo),
            ]);
        }
        $clinic->update(['next_patient_seq' => count($patients) + 1]);

        $tz = 'Africa/Cairo';
        $at = fn (int $daysAgo, int $h, int $m = 0) => Carbon::now($tz)->subDays($daysAgo)->setTime($h, $m)->utc();

        // Today's schedule (times in clinic-local hours).
        $todayAppts = [
            [0, 9, 0, 0, 'completed'], [1, 10, 0, 1, 'checked_in'], [2, 11, 0, 0, 'confirmed'],
            [3, 12, 30, 1, 'confirmed'], [4, 14, 0, 0, 'scheduled'],
        ];
        foreach ($todayAppts as [$pi, $h, $m, $di, $status]) {
            Appointment::create([
                'clinic_id' => $clinic->id, 'branch_id' => $branch->id,
                'patient_id' => $patients[$pi]->id, 'dentist_id' => $di ? $dentist->id : $owner->id,
                'chair' => 'كرسي '.($di + 1),
                'starts_at' => $at(0, $h, $m), 'ends_at' => $at(0, $h, $m)->addMinutes(40),
                'status' => $status, 'reason' => 'كشف',
            ]);
        }
        // Upcoming week.
        foreach ([[5, -2, 10, 1], [6, -3, 12, 0], [7, -4, 11, 1], [8, -5, 13, 0], [9, -6, 9, 30, 1]] as [$pi, $d, $h, $di]) {
            Appointment::create([
                'clinic_id' => $clinic->id, 'branch_id' => $branch->id,
                'patient_id' => $patients[$pi]->id, 'dentist_id' => $di ? $dentist->id : $owner->id,
                'chair' => 'كرسي '.($di + 1),
                'starts_at' => $at($d, $h), 'ends_at' => $at($d, $h)->addMinutes(45),
                'status' => $d >= -2 ? 'confirmed' : 'scheduled', 'reason' => 'متابعة',
            ]);
        }
        // History: completed visits + a no-show, spread over two months.
        foreach ([[0, 3, 10, 0, 'completed'], [1, 7, 11, 1, 'completed'], [2, 10, 12, 0, 'completed'],
                  [3, 14, 9, 1, 'no_show'], [4, 18, 13, 0, 'completed'], [5, 25, 10, 1, 'completed'],
                  [6, 32, 11, 0, 'completed'], [7, 45, 12, 1, 'completed'], [8, 60, 10, 0, 'completed']] as [$pi, $d, $h, $di, $st]) {
            Appointment::create([
                'clinic_id' => $clinic->id, 'branch_id' => $branch->id,
                'patient_id' => $patients[$pi]->id, 'dentist_id' => $di ? $dentist->id : $owner->id,
                'chair' => 'كرسي '.($di + 1),
                'starts_at' => $at($d, $h), 'ends_at' => $at($d, $h)->addMinutes(45),
                'status' => $st, 'reason' => 'كشف',
            ]);
        }

        // Odontogram sample for the first patient (synthetic conditions).
        foreach ([[36, 'filling', null], [16, 'caries', 'O'], [11, 'veneer', null], [46, 'root_canal', null], [28, 'missing', null]] as [$tooth, $cond, $surface]) {
            OdontogramEntry::create([
                'clinic_id' => $clinic->id, 'patient_id' => $patients[0]->id,
                'tooth_number' => $tooth, 'surface' => $surface, 'condition' => $cond,
                'recorded_by' => $owner->id,
            ]);
        }

        // Treatment plans with items.
        $plan1 = TreatmentPlan::create([
            'clinic_id' => $clinic->id, 'patient_id' => $patients[0]->id, 'dentist_id' => $owner->id,
            'title' => 'خطة علاج شاملة', 'status' => 'in_progress',
        ]);
        $p1Items = [];
        foreach ([
            [$services['RCT'], 46, 'علاج عصب ضرس سفلي', 'completed'],
            [$services['CRWN'], 46, 'تركيب تاج خزف بعد العصب', 'accepted'],
            [$services['FILL'], 16, 'حشو ضوئي ضرس علوي', 'completed'],
        ] as [$svc, $tooth, $desc, $st]) {
            $p1Items[] = TreatmentPlanItem::create([
                'plan_id' => $plan1->id, 'service_id' => $svc->id, 'tooth_number' => $tooth,
                'description' => $desc, 'price_minor' => $svc->default_price_minor, 'qty' => 1, 'status' => $st,
            ]);
        }
        $plan1->update(['total_minor' => collect($p1Items)->sum(fn ($i) => $i->lineTotal())]);

        $plan2 = TreatmentPlan::create([
            'clinic_id' => $clinic->id, 'patient_id' => $patients[1]->id, 'dentist_id' => $dentist->id,
            'title' => 'تجميل وتنظيف', 'status' => 'accepted',
        ]);
        $p2Items = [];
        foreach ([[$services['WHT'], null, 'تبييض الأسنان', 'accepted'], [$services['CLEAN'], null, 'تنظيف وتلميع', 'completed']] as [$svc, $tooth, $desc, $st]) {
            $p2Items[] = TreatmentPlanItem::create([
                'plan_id' => $plan2->id, 'service_id' => $svc->id, 'tooth_number' => $tooth,
                'description' => $desc, 'price_minor' => $svc->default_price_minor, 'qty' => 1, 'status' => $st,
            ]);
        }
        $plan2->update(['total_minor' => collect($p2Items)->sum(fn ($i) => $i->lineTotal())]);

        // Invoices + payments, spread across months so charts show real activity.
        $makeInvoice = function (Patient $patient, array $items, int $seq, Carbon $issuedAt, array $paymentsSpec, string $status) use ($clinic, $branch, $tz) {
            $subtotal = collect($items)->sum(fn ($i) => ($i['price'] - ($i['discount'] ?? 0)) * ($i['qty'] ?? 1));
            $invoice = Invoice::create([
                'clinic_id' => $clinic->id, 'branch_id' => $branch->id, 'patient_id' => $patient->id,
                'number' => 'INV-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                'status' => $status, 'currency' => 'EGP',
                'subtotal_minor' => $subtotal, 'discount_minor' => 0, 'tax_rate_bp' => 0, 'tax_minor' => 0,
                'total_minor' => $subtotal,
                'paid_minor' => collect($paymentsSpec)->sum('amount'),
                'issued_at' => $issuedAt->copy()->utc(),
                'created_at' => $issuedAt->copy()->utc(), 'updated_at' => $issuedAt->copy()->utc(),
            ]);
            foreach ($items as $i) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id, 'description' => $i['desc'], 'tooth_number' => $i['tooth'] ?? null,
                    'qty' => $i['qty'] ?? 1, 'unit_price_minor' => $i['price'],
                    'discount_minor' => $i['discount'] ?? 0,
                    'total_minor' => ($i['price'] - ($i['discount'] ?? 0)) * ($i['qty'] ?? 1),
                ]);
            }
            foreach ($paymentsSpec as $j => $p) {
                Payment::create([
                    'clinic_id' => $clinic->id, 'invoice_id' => $invoice->id, 'patient_id' => $patient->id,
                    'amount_minor' => $p['amount'], 'method' => $p['method'],
                    'paid_at' => $issuedAt->copy()->addDays($p['plusDays'] ?? 0)->utc(),
                ]);
            }

            return $invoice;
        };

        $makeInvoice($patients[4], [['desc' => 'علاج عصب', 'price' => 250000]], 1, $at(150, 12), [['amount' => 250000, 'method' => 'cash']], 'paid');
        $makeInvoice($patients[5], [['desc' => 'حشو ضوئي', 'price' => 90000], ['desc' => 'إزالة جير', 'price' => 50000]], 2, $at(120, 13), [['amount' => 140000, 'method' => 'instapay']], 'paid');
        $makeInvoice($patients[6], [['desc' => 'تركيب تاج خزف', 'price' => 350000]], 3, $at(90, 11), [['amount' => 350000, 'method' => 'bank_transfer']], 'paid');
        $makeInvoice($patients[7], [['desc' => 'تنظيف وتلميع الأسنان', 'price' => 60000]], 4, $at(60, 10), [['amount' => 60000, 'method' => 'cash']], 'paid');
        $makeInvoice($patients[8], [['desc' => 'تبييض الأسنان', 'price' => 400000]], 5, $at(35, 12), [['amount' => 400000, 'method' => 'card']], 'paid');
        $makeInvoice($patients[9], [['desc' => 'علاج عصب', 'price' => 250000], ['desc' => 'كشف وتشخيص', 'price' => 30000]], 6, $at(12, 13), [['amount' => 150000, 'method' => 'instapay'], ['amount' => 50000, 'method' => 'cash', 'plusDays' => 5]], 'partially_paid');
        $makeInvoice($patients[10], [['desc' => 'خلع ضرس', 'price' => 80000], ['desc' => 'حشو ضوئي', 'price' => 90000]], 7, $at(2, 10), [], 'issued');

        // Recalls: one overdue, upcoming ones, closed history.
        Recall::create(['clinic_id' => $clinic->id, 'patient_id' => $patients[3]->id, 'service_id' => $services['CLEAN']->id, 'due_date' => now($tz)->subDays(3)->toDateString(), 'status' => 'pending', 'reason' => 'مراجعة تنظيف دورية', 'created_by' => $owner->id]);
        Recall::create(['clinic_id' => $clinic->id, 'patient_id' => $patients[9]->id, 'due_date' => now($tz)->addDays(3)->toDateString(), 'status' => 'pending', 'reason' => 'استكمال علاج العصب', 'created_by' => $owner->id]);
        Recall::create(['clinic_id' => $clinic->id, 'patient_id' => $patients[0]->id, 'service_id' => $services['CRWN']->id, 'due_date' => now($tz)->addDays(7)->toDateString(), 'status' => 'pending', 'reason' => 'تجربة التاج النهائي', 'created_by' => $owner->id]);
        Recall::create(['clinic_id' => $clinic->id, 'patient_id' => $patients[5]->id, 'service_id' => $services['CLEAN']->id, 'due_date' => now($tz)->addDays(14)->toDateString(), 'status' => 'pending', 'reason' => 'مراجعة بعد الحشو', 'created_by' => $owner->id]);
        Recall::create(['clinic_id' => $clinic->id, 'patient_id' => $patients[7]->id, 'due_date' => now($tz)->subDays(10)->toDateString(), 'status' => 'done', 'reason' => 'مراجعة تنظيف دورية', 'created_by' => $owner->id, 'done_at' => now($tz)->subDays(10)->utc()]);

        // Consent records (synthetic).
        ConsentRecord::create(['clinic_id' => $clinic->id, 'patient_id' => $patients[0]->id, 'type' => 'data_processing', 'text_snapshot' => 'أوافق على معالجة بياناتي الصحية لغرض العلاج داخل العيادة.', 'method' => 'written', 'given_at' => now($tz)->subDays(20)->utc(), 'recorded_by' => $owner->id]);
        ConsentRecord::create(['clinic_id' => $clinic->id, 'patient_id' => $patients[0]->id, 'type' => 'treatment', 'text_snapshot' => 'أوافق على خطة علاج العصب والتركيب كما شرحها الطبيب.', 'method' => 'written', 'given_at' => now($tz)->subDays(15)->utc(), 'recorded_by' => $owner->id]);
    }
}
