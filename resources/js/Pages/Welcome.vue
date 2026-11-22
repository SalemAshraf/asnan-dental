<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { i18n, toggleLocale } from '@/i18n';

defineProps({ canLogin: Boolean, canRegister: Boolean });

const C = {
    ar: {
        nav: [['#product', 'المنتج'], ['#flow', 'طريقة الشغل'], ['#team', 'الفريق'], ['#faq', 'أسئلة شائعة']],
        eyebrow: 'نظام إدارة عيادات الأسنان',
        h1: 'من أول حجز لحد تحصيل الفاتورة، كل شغل العيادة في مكان واحد',
        lead: 'ملفات مرضى وخطط علاج وفواتير ومدفوعات ومتابعات، مع تقارير توريك أرقام عيادتك. بالعربي والإنجليزي، ومن الموبايل كمان.',
        cta: 'ابدأ بحساب عيادتك', cta2: 'عندي حساب', demo: 'الصور من عيادة تجريبية ببيانات وهمية',
        chips: ['ملفات المرضى', 'خطة العمل اليومية', 'المواعيد', 'خطط العلاج', 'الفواتير والمدفوعات', 'المتابعات', 'التقارير', 'الصلاحيات وسجل المراجعة'],
        pH: 'شوف النظام من جوّه', pS: 'لقطات حقيقية من النسخة الحالية، بدون تعديل.',
        tabs: [
            ['dashboard', 'لوحة التحكم', 'يومك في شاشة واحدة', ['مواعيد النهاردة وحالة كل واحدة', 'إيرادات ومصروف الفترة اللي تختارها', 'المتابعات المتأخرة والقريبة', 'أرقام المرضى الجداد']],
            ['patient', 'ملف المريض', 'كل تاريخ المريض في مكان واحد', ['التاريخ الطبي والموافقات المسجلة', 'مخطط الأسنان (أودونتوجرام) لكل سن', 'خطط العلاج وحالة كل بند', 'الفواتير والمدفوعات على المريض']],
            ['appointments', 'المواعيد', 'يوم وأسبوع العيادة بوضوح', ['عرض يومي وأسبوعي لكل الأطباء', 'حالات الموعد: محجوز، مؤكد، حضر، تم، لم يحضر', 'حجز سريع من ملف المريض أو من الشاشة']],
            ['invoice', 'الفواتير', 'من خطة العلاج للتحصيل', ['فاتورة من خطة العلاج المقبولة بضغطة', 'دفعات وأقساط مع شريط تقدّم السداد', 'ضريبة تحددها العيادة بنفسها، بدون افتراض']],
            ['reports', 'التقارير', 'أرقام العيادة الحقيقية', ['المحصَّل والمُصدَر والمستحق في أي فترة', 'التحصيل حسب طريقة الدفع', 'نشاط كل طبيب وأكثر الخدمات قيمة']],
        ],
        fH: 'طريقة الشغل', fS: 'أربع خطوات، وكل خطوة بتغذّي اللي بعدها.',
        flow: [['سجّل المريض', 'اسم وهاتف وتاريخ طبي، ورقم ملف تلقائي. بحث سريع بالاسم أو الهاتف من أي شاشة.'], ['احجز وتتبّع', 'مواعيد يومية وأسبوعية بحالات واضحة، ومتابعات دورية بتوصلك قبل ما المريض يتأخر.'], ['اعمل خطة علاج', 'خطط علاج بالأسنان والأسعار، والمريض يوافق عليها، ومنها تتولد الفاتورة.'], ['حصّل وقِس', 'دفعات وأقساط على كل فاتورة، وتقارير بالمحصل فعلًا، مش بالمتوقع.']],
        tH: 'الفريق والصلاحيات', tS: 'كل شخص يشوف اللي يخصه، وكل تعديل مهم متسجل.',
        team: [['مالك العيادة', 'يشوف كل شيء، يضيف الفريق والفروع، ويضبط الأسعار والإعدادات.'], ['طبيب الأسنان', 'ملفات المرضى وخطط العلاج ومخطط الأسنان والمواعيد.'], ['موظف الاستقبال', 'الحجز والفواتير والمدفوعات والمتابعات، بدون تعديل البيانات السريرية.']],
        teamNote: 'سجل مراجعة بالتعديلات المهمة، وبيانات كل عيادة منفصلة تمامًا عن أي عيادة تانية.',
        mH: 'بالعربي والإنجليزي، وعلى الموبايل', mP: 'الواجهة بتتبدل بين العربية (من اليمين لليسار) والإنجليزية بضغطة واحدة لكل مستخدم، وبتشتغل من متصفح الموبايل.',
        nH: 'مش موجود لسه', nP: 'علشان الصورة تبقى واضحة: الإرسال التلقائي لرسائل واتساب، الرسائل النصية والإيميلات، الدفع أونلاين، مطالبات التأمين، والتطبيق الأصلي للموبايل لسه ما اتبنوش. زر واتساب بيفتح محادثة جاهزة على جهازك ولا يرسل بنفسه.',
        qH: 'أسئلة شائعة',
        faq: [['هل بيانات عيادتي بتظهر لعيادات تانية؟', 'لا. كل حساب عيادة له بياناته المنفصلة. التسجيل بينشئ عيادة جديدة فقط، ولا يضيفك لعيادة موجودة. الانضمام يتم بإضافة من مالك العيادة.'], ['هل النظام بيبعت رسائل واتساب للمرضى؟', 'لا حاليًا. زر واتساب بيفتح محادثة في واتساب على جهازك برسالة جاهزة تبعتها أنت، من غير أي تفاصيل سريرية. الإرسال التلقائي مش متاح لسه.'], ['هل في تطبيق موبايل؟', 'النظام بيشتغل كامل من متصفح الموبايل. التطبيق الأصلي لأندرويد وiOS مش موجود لسه.'], ['كام السعر؟', 'السعر والباقات لسه ما اتحددوش، فمفيش أرقام هنا.'], ['هل إنشاء الحساب مجاني؟', 'أيوه. أنشئ حسابك من غير أي وسيلة دفع، وفريقنا بيفعّل الحساب يدويًا بعد التواصل. حتى التفعيل، الحساب للاطلاع فقط: بياناتك محفوظة وتقدر تشوفها، لكن لا يمكن إضافة أو تعديل أي شيء.'], ['أقدر أجرّب قبل ما أدخل بياناتي؟', 'تقدر تنشئ حساب عيادة جديد وتجرّب. بياناتك التجريبية بتفضل منفصلة عن أي عيادة تانية.']],
        fin: 'جاهز تبدأ؟', finP: 'أنشئ حساب عيادتك وابدأ بإضافة الفريق والمرضى.', foot: 'بسمة', login: 'تسجيل الدخول', register: 'إنشاء حساب', plans: 'الباقات',
    },
    en: {
        nav: [['#product', 'Product'], ['#flow', 'How it works'], ['#team', 'Team'], ['#faq', 'FAQ']],
        eyebrow: 'Dental clinic management',
        h1: 'From the first booking to the paid invoice, your whole clinic in one place',
        lead: 'Patient files, treatment plans, invoices and payments, recalls, and reports that show your clinic\u2019s real numbers. In Arabic and English, and on your phone too.',
        cta: 'Start with your clinic account', cta2: 'I have an account', demo: 'Screenshots are from a demo clinic with fictional data',
        chips: ['Patient files', 'Daily schedule', 'Appointments', 'Treatment plans', 'Invoices and payments', 'Recalls', 'Reports', 'Permissions and audit log'],
        pH: 'See inside the product', pS: 'Real screenshots of the current version, unedited.',
        tabs: [
            ['dashboard', 'Dashboard', 'Your day on one screen', ['Today\u2019s appointments and their status', 'Revenue for the period you pick', 'Overdue and upcoming recalls', 'New-patient numbers']],
            ['patient', 'Patient file', 'The patient\u2019s whole history in one place', ['Medical history and recorded consents', 'A per-tooth odontogram', 'Treatment plans and each item\u2019s status', 'Invoices and payments for the patient']],
            ['appointments', 'Appointments', 'The clinic\u2019s day and week, clearly', ['Daily and weekly views for all dentists', 'Statuses: scheduled, confirmed, checked in, completed, no-show', 'Quick booking from the patient file or the screen']],
            ['invoice', 'Invoices', 'From treatment plan to collection', ['An invoice from the accepted plan in one click', 'Payments and installments with a settlement progress bar', 'A tax rate the clinic sets itself, with no default']],
            ['reports', 'Reports', 'The clinic\u2019s real numbers', ['Collected, issued and outstanding for any period', 'Collections by payment method', 'Per-dentist activity and the most valuable services']],
        ],
        fH: 'How it works', fS: 'Four steps, each one feeding the next.',
        flow: [['Register the patient', 'Name, phone and medical history, with an automatic file number. Fast search by name or phone from any screen.'], ['Book and follow up', 'Daily and weekly appointments with clear statuses, and recalls that reach you before the patient is overdue.'], ['Plan the treatment', 'Treatment plans with teeth and prices, patient approval, and an invoice generated from the plan.'], ['Collect and measure', 'Payments and installments per invoice, and reports built from what was actually collected, not what was expected.']],
        tH: 'Team and permissions', tS: 'Everyone sees what concerns them, and important changes are logged.',
        team: [['Clinic owner', 'Sees everything, adds the team and branches, and sets prices and settings.'], ['Dentist', 'Patient files, treatment plans, the odontogram and appointments.'], ['Receptionist', 'Booking, invoices, payments and recalls, without editing clinical data.']],
        teamNote: 'An audit log of important changes, and each clinic\u2019s data fully separate from every other clinic.',
        mH: 'Arabic, English and mobile', mP: 'Each user switches between Arabic (right to left) and English in one tap, and everything works from a phone browser.',
        nH: 'Not available yet', nP: 'To be clear: automatic WhatsApp sending, SMS and email, online payment, insurance claims and a native mobile app are not built yet. The WhatsApp button opens a ready chat on your device; it does not send by itself.',
        qH: 'Frequently asked questions',
        faq: [['Can other clinics see my data?', 'No. Each clinic account has its own separate data. Signing up only creates a new clinic; it never adds you to an existing one. Joining happens when the clinic owner adds you.'], ['Does it send WhatsApp messages to patients?', 'Not today. The WhatsApp button opens a chat on your device with a ready message you send yourself, with no clinical details. Automatic sending is not available yet.'], ['Is there a mobile app?', 'The system works fully from a phone browser. A native Android or iOS app does not exist yet.'], ['How much does it cost?', 'Pricing and plans are not set yet, so there are no numbers here.'], ['Is creating an account free?', 'Yes. Create your account with no payment method, and our team activates it manually after getting in touch. Until activation the account is read-only: your data stays saved and visible, but nothing can be added or changed.'], ['Can I try it before entering my data?', 'You can create a new clinic account and try it. Your trial data stays separate from every other clinic.']],
        fin: 'Ready to start?', finP: 'Create your clinic account and start by adding your team and patients.', foot: 'Basma', login: 'Log in', register: 'Create account', plans: 'Plans',
    },
};
const c = computed(() => C[i18n.locale] || C.ar);
const L = computed(() => (i18n.locale === 'en' ? 'en' : 'ar'));
const tab = ref(0);
const img = (n) => `/landing/${L.value}-${n}.jpg`;
</script>

<template>
    <Head title="بسمة — إدارة عيادات الأسنان" />
    <div class="min-h-screen bg-canvas text-ink-900">
        <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-canvas/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-5 py-3">
                <span class="flex items-center gap-3 text-lg font-bold">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-700 text-white shadow">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C9.5 2 8.8 3.4 7.2 3.4 5 3.4 3.5 5.6 3.5 8c0 3.6 1.7 4.7 2.1 7.4.3 2 .7 6.6 2.6 6.6 1.7 0 1.2-3.4 1.7-5.5.3-1.3.9-2 2.1-2s1.8.7 2.1 2c.5 2.1 0 5.5 1.7 5.5 1.9 0 2.3-4.6 2.6-6.6.4-2.7 2.1-3.8 2.1-7.4 0-2.4-1.5-4.6-3.7-4.6-1.6 0-2.3-1.4-4.8-1.4z"/></svg>
                    </span>{{ c.foot }}
                </span>
                <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex"><a v-for="n in c.nav" :key="n[0]" :href="n[0]" class="hover:text-ink-900">{{ n[1] }}</a></nav>
                <div class="flex items-center gap-2">
                    <button class="btn-secondary btn-sm" @click="toggleLocale">{{ i18n.locale === 'ar' ? 'EN' : 'ع' }}</button>
                    <Link href="/pricing" class="btn-secondary btn-sm hidden sm:inline-flex">{{ c.plans }}</Link>
                    <Link v-if="canLogin" href="/login" class="btn-secondary btn-sm hidden sm:inline-flex">{{ c.login }}</Link>
                    <Link v-if="canRegister" href="/register" class="btn-primary btn-sm">{{ c.register }}</Link>
                </div>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden">
                <div class="pointer-events-none absolute -top-32 h-96 w-96 rounded-full bg-teal-300/30 blur-3xl" style="inset-inline-start: -6rem" />
                <div class="relative mx-auto max-w-6xl px-5 pb-10 pt-12 text-center lg:pt-20">
                    <p class="mx-auto inline-block rounded-full border border-slate-200 bg-white px-4 py-1 text-xs font-semibold text-teal-800">{{ c.eyebrow }}</p>
                    <h1 class="mx-auto mt-5 max-w-3xl text-3xl font-bold leading-tight sm:text-5xl sm:leading-[1.25]">{{ c.h1 }}</h1>
                    <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600">{{ c.lead }}</p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <Link v-if="canRegister" href="/register" class="btn-primary !px-6 !py-3 text-base">{{ c.cta }}</Link>
                        <Link v-if="canLogin" href="/login" class="btn-secondary !px-6 !py-3 text-base">{{ c.cta2 }}</Link>
                    </div>
                    <div class="mx-auto mt-10 max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                        <div class="flex items-center gap-1.5 border-b border-slate-200 bg-slate-50 px-4 py-2.5"><span class="h-2.5 w-2.5 rounded-full bg-slate-300" /><span class="h-2.5 w-2.5 rounded-full bg-slate-300" /><span class="h-2.5 w-2.5 rounded-full bg-slate-300" /></div>
                        <img :src="img('dashboard')" width="1280" height="800" alt="" class="block w-full" fetchpriority="high" />
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ c.demo }}</p>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-5 pb-6">
                <ul class="flex flex-wrap justify-center gap-2"><li v-for="x in c.chips" :key="x" class="rounded-full bg-white px-4 py-1.5 text-sm font-medium text-slate-700 ring-1 ring-slate-200">{{ x }}</li></ul>
            </section>

            <section id="product" class="mx-auto max-w-6xl px-5 py-14">
                <h2 class="text-center text-3xl font-bold">{{ c.pH }}</h2>
                <p class="mt-2 text-center text-slate-600">{{ c.pS }}</p>
                <div class="mt-8 flex flex-wrap justify-center gap-2">
                    <button v-for="(t, i) in c.tabs" :key="t[0]" class="rounded-full px-4 py-2 text-sm font-semibold ring-1 transition" :class="tab === i ? 'bg-brand-700 text-white ring-brand-700' : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50'" @click="tab = i">{{ t[1] }}</button>
                </div>
                <div class="mt-8 grid items-start gap-8 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <h3 class="text-2xl font-bold">{{ c.tabs[tab][2] }}</h3>
                        <ul class="mt-4 space-y-3">
                            <li v-for="p in c.tabs[tab][3]" :key="p" class="flex items-start gap-2 text-slate-700"><span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-600" />{{ p }}</li>
                        </ul>
                    </div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl lg:col-span-3">
                        <img :src="img(c.tabs[tab][0])" width="1280" height="800" alt="" class="block w-full" loading="lazy" />
                    </div>
                </div>
            </section>

            <section id="flow" class="border-y border-slate-200 bg-white/60 py-14">
                <div class="mx-auto max-w-6xl px-5">
                    <h2 class="text-center text-3xl font-bold">{{ c.fH }}</h2>
                    <p class="mt-2 text-center text-slate-600">{{ c.fS }}</p>
                    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div v-for="(f, i) in c.flow" :key="f[0]" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-50 text-sm font-bold text-brand-700">{{ i + 1 }}</span>
                            <h3 class="mt-3 font-bold">{{ f[0] }}</h3>
                            <p class="mt-1.5 text-sm leading-6 text-slate-600">{{ f[1] }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="team" class="mx-auto max-w-6xl px-5 py-14">
                <h2 class="text-center text-3xl font-bold">{{ c.tH }}</h2>
                <p class="mt-2 text-center text-slate-600">{{ c.tS }}</p>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <div v-for="m in c.team" :key="m[0]" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h3 class="font-bold">{{ m[0] }}</h3>
                        <p class="mt-1.5 text-sm leading-6 text-slate-600">{{ m[1] }}</p>
                    </div>
                </div>
                <p class="mx-auto mt-6 max-w-2xl text-center text-sm text-slate-500">{{ c.teamNote }}</p>
            </section>

            <section class="border-y border-slate-200 bg-white/60 py-14">
                <div class="mx-auto max-w-3xl px-5 text-center">
                    <h2 class="text-3xl font-bold">{{ c.mH }}</h2>
                    <p class="mt-3 text-slate-600">{{ c.mP }}</p>
                </div>
            </section>

            <section class="mx-auto max-w-3xl px-5 py-14">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <h2 class="text-xl font-bold text-amber-900">{{ c.nH }}</h2>
                    <p class="mt-2 text-sm leading-6 text-amber-900">{{ c.nP }}</p>
                </div>
            </section>

            <section id="faq" class="mx-auto max-w-3xl px-5 pb-14">
                <h2 class="text-center text-3xl font-bold">{{ c.qH }}</h2>
                <div class="mt-8 space-y-3">
                    <details v-for="q in c.faq" :key="q[0]" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <summary class="cursor-pointer font-semibold">{{ q[0] }}</summary>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ q[1] }}</p>
                    </details>
                </div>
            </section>

            <section class="border-t border-slate-200 bg-brand-700 py-14 text-center text-white">
                <h2 class="text-3xl font-bold">{{ c.fin }}</h2>
                <p class="mt-2 text-teal-100">{{ c.finP }}</p>
                <Link v-if="canRegister" href="/register" class="mt-6 inline-block rounded-xl bg-white px-6 py-3 font-bold text-brand-800 shadow hover:bg-teal-50">{{ c.cta }}</Link>
            </section>
        </main>

        <footer class="border-t border-slate-200 py-6 text-center text-sm text-slate-500">{{ c.foot }}</footer>
    </div>
</template>
