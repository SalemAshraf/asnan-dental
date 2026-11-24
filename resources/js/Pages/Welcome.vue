<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { i18n, toggleLocale } from '@/i18n';

defineProps({ canLogin: Boolean, canRegister: Boolean });

const C = {
    ar: {
        nav: [['#product', 'المنتج'], ['#ai', 'Claude'], ['#flow', 'طريقة الشغل'], ['#team', 'الفريق'], ['#faq', 'أسئلة شائعة']],
        aiH: 'اسأل Claude عن عيادتك',
        aiS: 'اربط تقويم بحسابك في Claude واسأل بالعربي أو الإنجليزي عن مواعيدك ومتابعاتك وأرقامك، من غير ما تفتح لوحة التحكم.',
        aiBadge: 'جديد',
        aiQ: ['مواعيد النهاردة وحالة كل واحدة إيه؟', 'أنهي متابعات متأخرة أو مستحقة النهاردة؟', 'دور على ملف المريض فلان برقمه', 'المحصل والمستحق الشهر ده كام؟'],
        aiHow: [['فعّل الربط', 'مالك العيادة يفعّل ربط Claude من إعدادات العيادة.'], ['أضف الرابط في Claude', 'في Claude أضف Custom Connector برابط عيادتك ووافق من صفحة الموافقة.'], ['اسأل', 'اسأل عن الجدول والمتابعات والأرقام. تقدر تفصل الربط في أي وقت من الإعدادات.']],
        aiTrust: ['قراءة فقط: لا يضيف ولا يعدّل ولا يرسل رسائل للمرضى', 'بدون بيانات سريرية: لا خطط علاج ولا تاريخ طبي ولا مخطط أسنان', 'الموافقة لمالك العيادة فقط، والوصول لبيانات عيادته فقط', 'يتوقف فورًا لو الربط اتقفل من الإعدادات أو اتفصل', 'البيانات تنتقل لحسابك في Claude تحت شروط استخدامك أنت معهم'],
        aiNote: 'ميزة جديدة في مرحلة الإطلاق الأولى، وتتطلب حساب Claude منفصل بخططه الخاصة. الربط اختياري وتقدر تفصله في أي وقت من الإعدادات.',
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
        qH: 'أسئلة شائعة',
        faq: [['هل بيانات عيادتي بتظهر لعيادات تانية؟', 'لا. كل حساب عيادة له بياناته المنفصلة. التسجيل بينشئ عيادة جديدة فقط، ولا يضيفك لعيادة موجودة. الانضمام يتم بإضافة من مالك العيادة.'], ['هل النظام بيبعت رسائل واتساب للمرضى؟', 'لا حاليًا. زر واتساب بيفتح محادثة في واتساب على جهازك برسالة جاهزة تبعتها أنت، من غير أي تفاصيل سريرية. الإرسال التلقائي مش متاح لسه.'], ['هل في تطبيق موبايل؟', 'النظام بيشتغل كامل من متصفح الموبايل. التطبيق الأصلي لأندرويد وiOS مش موجود لسه.'], ['كام السعر؟', 'السعر والباقات لسه ما اتحددوش، فمفيش أرقام هنا.'], ['هل إنشاء الحساب مجاني؟', 'أيوه. أنشئ حسابك من غير أي وسيلة دفع، وفريقنا بيفعّل الحساب يدويًا بعد التواصل. حتى التفعيل، الحساب للاطلاع فقط: بياناتك محفوظة وتقدر تشوفها، لكن لا يمكن إضافة أو تعديل أي شيء.'], ['أقدر أجرّب قبل ما أدخل بياناتي؟', 'تقدر تنشئ حساب عيادة جديد وتجرّب. بياناتك التجريبية بتفضل منفصلة عن أي عيادة تانية.']],
        fin: 'جاهز تبدأ؟', finP: 'أنشئ حساب عيادتك وابدأ بإضافة الفريق والمرضى.', foot: 'تقويم', fProduct: 'المنتج', fAccount: 'الحساب', fContact: 'تواصل', fRights: 'جميع الحقوق محفوظة.', fNav: [['#product', 'المنتج'], ['#flow', 'طريقة الشغل'], ['#team', 'الفريق'], ['#faq', 'أسئلة شائعة']], creditA: 'تم تطوير هذا النظام بواسطة', creditB: 'Reviv Solutions', login: 'تسجيل الدخول', register: 'إنشاء حساب', plans: 'الباقات',
    },
    en: {
        nav: [['#product', 'Product'], ['#ai', 'Claude'], ['#flow', 'How it works'], ['#team', 'Team'], ['#faq', 'FAQ']],
        aiH: 'Ask Claude about your clinic',
        aiS: 'Connect Taqwim to your Claude account and ask, in Arabic or English, about your schedule, recalls and numbers, without opening the dashboard.',
        aiBadge: 'New',
        aiQ: ["What are today's appointments and their status?", 'Which recalls are due or overdue today?', "Find a patient's file by phone", 'How much was collected and is outstanding this month?'],
        aiHow: [['Turn it on', 'The clinic owner enables the Claude connection in clinic settings.'], ['Add the connector in Claude', 'In Claude, add a Custom Connector with your clinic URL and approve on the consent page.'], ['Ask', 'Ask about the schedule, recalls and numbers. Disconnect any time from settings.']],
        aiTrust: ['Read-only: it cannot add, change or message patients', 'No clinical data: no treatment plans, medical history or odontogram', "Only the clinic owner approves, and only their clinic's data is exposed", 'It stops the moment the switch is off or the connection is removed', 'Data goes to your own Claude account under your terms with them'],
        aiNote: 'A new feature in early rollout; it needs a separate Claude account under its own plans. The connection is optional and you can disconnect it any time from settings.',
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
        qH: 'Frequently asked questions',
        faq: [['Can other clinics see my data?', 'No. Each clinic account has its own separate data. Signing up only creates a new clinic; it never adds you to an existing one. Joining happens when the clinic owner adds you.'], ['Does it send WhatsApp messages to patients?', 'Not today. The WhatsApp button opens a chat on your device with a ready message you send yourself, with no clinical details. Automatic sending is not available yet.'], ['Is there a mobile app?', 'The system works fully from a phone browser. A native Android or iOS app does not exist yet.'], ['How much does it cost?', 'Pricing and plans are not set yet, so there are no numbers here.'], ['Is creating an account free?', 'Yes. Create your account with no payment method, and our team activates it manually after getting in touch. Until activation the account is read-only: your data stays saved and visible, but nothing can be added or changed.'], ['Can I try it before entering my data?', 'You can create a new clinic account and try it. Your trial data stays separate from every other clinic.']],
        fin: 'Ready to start?', finP: 'Create your clinic account and start by adding your team and patients.', foot: 'Taqwim', fProduct: 'Product', fAccount: 'Account', fContact: 'Contact', fRights: 'All rights reserved.', fNav: [['#product', 'Product'], ['#flow', 'How it works'], ['#team', 'Team'], ['#faq', 'FAQ']], creditA: 'Built by', creditB: 'Reviv Solutions', login: 'Log in', register: 'Create account', plans: 'Plans',
    },
};
const c = computed(() => C[i18n.locale] || C.ar);
const L = computed(() => (i18n.locale === 'en' ? 'en' : 'ar'));
const tab = ref(0);
const img = (n) => `/landing/${L.value}-${n}.jpg`;
</script>

<template>
    <Head title="تقويم — إدارة عيادات الأسنان" />
    <div class="min-h-screen bg-canvas text-ink-900">
        <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-canvas/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-5 py-3">
                <span class="flex items-center gap-3 text-lg font-bold">
                    <img src="/brand/taqwim-lockup-light.svg" alt="تقويم" class="h-9 w-auto">
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

            <section id="ai" class="relative overflow-hidden border-y border-slate-200 bg-slate-900 py-16 text-white">
                <div class="pointer-events-none absolute inset-0" style="background: radial-gradient(40rem 20rem at 80% -10%, rgba(23, 122, 117, 0.35), transparent 60%), radial-gradient(30rem 18rem at 0% 110%, rgba(16, 78, 76, 0.45), transparent 60%);" />
                <div class="relative mx-auto max-w-6xl px-5">
                    <div class="text-center">
                        <span class="inline-block rounded-full bg-brand-500/20 px-3 py-1 text-xs font-bold text-brand-200 ring-1 ring-brand-400/40">{{ c.aiBadge }}</span>
                        <h2 class="mt-3 text-3xl font-bold">{{ c.aiH }}</h2>
                        <p class="mx-auto mt-2 max-w-2xl text-slate-300">{{ c.aiS }}</p>
                    </div>
                    <div class="mt-10 grid items-start gap-8 lg:grid-cols-2">
                        <div class="space-y-3">
                            <div v-for="q in c.aiQ" :key="q" class="rounded-2xl rounded-ss-md border border-white/10 bg-white/5 px-4 py-3 text-sm leading-6 text-slate-100 shadow-sm">
                                <span class="me-2 inline-block h-2 w-2 rounded-full bg-brand-400 align-middle" />{{ q }}
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div v-for="(h, i) in c.aiHow" :key="h[0]" class="flex items-start gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand-500/20 text-sm font-bold text-brand-200 ring-1 ring-brand-400/40">{{ i + 1 }}</span>
                                <div>
                                    <h3 class="font-bold">{{ h[0] }}</h3>
                                    <p class="mt-1 text-sm leading-6 text-slate-300">{{ h[1] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <ul class="mt-10 grid gap-3 rounded-2xl border border-white/10 bg-white/5 p-5 text-sm leading-6 text-slate-200 sm:grid-cols-2">
                        <li v-for="tr in c.aiTrust" :key="tr" class="flex items-start gap-2"><svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-300" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-8 8a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.4L8 12.6l7.3-7.3a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>{{ tr }}</li>
                    </ul>
                    <p class="mt-5 text-center text-xs leading-5 text-slate-400">{{ c.aiNote }}</p>
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
                <img src="/brand/taqwim-lockup-slogan-dark.svg" alt="تقويم" class="mx-auto mb-6 h-16 w-auto">
                <h2 class="text-3xl font-bold">{{ c.fin }}</h2>
                <p class="mt-2 text-teal-100">{{ c.finP }}</p>
                <Link v-if="canRegister" href="/register" class="mt-6 inline-block rounded-xl bg-white px-6 py-3 font-bold text-brand-800 shadow hover:bg-teal-50">{{ c.cta }}</Link>
            </section>
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
                <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="space-y-4">
                        <img src="/brand/taqwim-lockup-slogan-light.svg" alt="تقويم" class="h-12 w-auto">
                        <p class="text-sm leading-relaxed text-slate-500">{{ c.eyebrow }}</p>
                    </div>
                    <nav :aria-label="c.fProduct">
                        <p class="mb-4 text-sm font-bold text-slate-800">{{ c.fProduct }}</p>
                        <ul class="space-y-3 text-sm text-slate-500">
                            <li v-for="item in c.fNav" :key="item[0]"><a :href="item[0]" class="transition hover:text-brand-700">{{ item[1] }}</a></li>
                        </ul>
                    </nav>
                    <nav :aria-label="c.fAccount">
                        <p class="mb-4 text-sm font-bold text-slate-800">{{ c.fAccount }}</p>
                        <ul class="space-y-3 text-sm text-slate-500">
                            <li><Link v-if="canLogin" href="/login" class="transition hover:text-brand-700">{{ c.login }}</Link></li>
                            <li><Link v-if="canRegister" href="/register" class="transition hover:text-brand-700">{{ c.register }}</Link></li>
                        </ul>
                    </nav>
                    <div>
                        <p class="mb-4 text-sm font-bold text-slate-800">{{ c.fContact }}</p>
                        <ul class="space-y-3 text-sm text-slate-500">
                            <li><a href="https://wa.me/201098971853" target="_blank" rel="noopener noreferrer" class="transition hover:text-brand-700"><span dir="ltr">+20 109 897 1853</span></a></li>
                            <li><a href="https://www.facebook.com/RevivSolutions" target="_blank" rel="noopener noreferrer" class="transition hover:text-brand-700">Facebook</a></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-10 flex flex-col gap-2 border-t border-slate-200 pt-6 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {{ new Date().getFullYear() }} {{ c.foot }}. {{ c.fRights }}</p>
                    <p>{{ c.creditA }} <a href="https://wa.me/201098971853" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-700 hover:text-brand-800 hover:underline">{{ c.creditB }}</a></p>
                </div>
            </div>
        </footer>
    </div>
</template>
