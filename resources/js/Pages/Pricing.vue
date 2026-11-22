<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { i18n, toggleLocale } from '@/i18n';

defineProps({ plans: Array, trialDays: Number });

const page = usePage();
const sent = computed(() => page.props.flash?.success);

const form = useForm({ name: '', clinic_name: '', phone: '', email: '', plan_slug: '', message: '', website: '' });
const submit = () => form.post('/pricing/inquiry', { onSuccess: () => form.reset() });

const C = {
    ar: {
        title: 'الباقات والأسعار', sub: 'أنشئ حساب عيادتك مجانًا، بدون أي وسيلة دفع. التفعيل بيتم يدويًا بعد التواصل.',
        noPlansH: 'الأسعار لسه ما اتحددتش', noPlansP: 'الباقات والأسعار النهائية لسه تحت التحديد. سيب بياناتك وهنتواصل معاك أول ما تتاح، أو ابدأ التجربة المجانية دلوقتي.',
        contactUs: 'السعر عند التواصل', perMonth: 'شهريًا', perYear: 'سنويًا', yearly: 'سنوي',
        users: 'حتى {n} مستخدم', patients: 'حتى {n} مريض', trial: 'تجربة {n} يوم',
        start: 'ابدأ بهذه الباقة', startFree: 'ابدأ التجربة المجانية', login: 'عندي حساب', home: 'الرئيسية',
        inqH: 'اطلب التواصل', inqP: 'سيب بياناتك وهنرجعلك.',
        fName: 'الاسم *', fClinic: 'اسم العيادة', fPhone: 'الهاتف', fEmail: 'البريد الإلكتروني', fPlan: 'الباقة محل الاهتمام', fMsg: 'رسالتك (اختياري)',
        fEither: 'الهاتف أو البريد الإلكتروني مطلوب', send: 'إرسال', sentOk: 'وصلتنا رسالتك. هنتواصل معاك.',
        noPayment: 'لا يتم تحصيل أي مبلغ من الموقع. التفعيل يتم يدويًا بعد التواصل.',
    },
    en: {
        title: 'Plans and pricing', sub: 'Create your clinic account for free, with no payment method asked. Activation is manual after we talk.',
        noPlansH: 'Pricing is not set yet', noPlansP: 'Final plans and prices are still being decided. Leave your details and we will reach out once available, or start the free trial now.',
        contactUs: 'Price on request', perMonth: '/month', perYear: '/year', yearly: 'yearly',
        users: 'Up to {n} users', patients: 'Up to {n} patients', trial: '{n}-day trial',
        start: 'Start with this plan', startFree: 'Start the free trial', login: 'I have an account', home: 'Home',
        inqH: 'Request a call back', inqP: 'Leave your details and we will get back to you.',
        fName: 'Name *', fClinic: 'Clinic name', fPhone: 'Phone', fEmail: 'Email', fPlan: 'Plan of interest', fMsg: 'Your message (optional)',
        fEither: 'Phone or email is required', send: 'Send', sentOk: 'We received your message. We will be in touch.',
        noPayment: 'No payment is collected on this site. Activation happens manually after we talk.',
    },
};
const c = computed(() => C[i18n.locale] || C.ar);
const tt = (s, n) => s.replace('{days}', n ?? 0).replaceAll('{n}', n ?? 0);
const name = (p) => (i18n.locale === 'ar' ? p.name_ar : p.name_en);
const blurb = (p) => (i18n.locale === 'ar' ? p.blurb_ar : p.blurb_en);
const features = (p) => (i18n.locale === 'ar' ? p.features_ar : p.features_en) || [];
</script>

<template>
    <Head :title="c.title" />
    <div class="min-h-screen bg-canvas text-ink-900">
        <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-canvas/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-5 py-3">
                <Link href="/" class="flex items-center gap-3 text-lg font-bold">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-700 text-white shadow">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C9.5 2 8.8 3.4 7.2 3.4 5 3.4 3.5 5.6 3.5 8c0 3.6 1.7 4.7 2.1 7.4.3 2 .7 6.6 2.6 6.6 1.7 0 1.2-3.4 1.7-5.5.3-1.3.9-2 2.1-2s1.8.7 2.1 2c.5 2.1 0 5.5 1.7 5.5 1.9 0 2.3-4.6 2.6-6.6.4-2.7 2.1-3.8 2.1-7.4 0-2.4-1.5-4.6-3.7-4.6-1.6 0-2.3-1.4-4.8-1.4z"/></svg>
                    </span>{{ i18n.locale === 'ar' ? 'تقويم' : 'Taqwim' }}
                </Link>
                <div class="flex items-center gap-2">
                    <button class="btn-secondary btn-sm" @click="toggleLocale">{{ i18n.locale === 'ar' ? 'EN' : 'ع' }}</button>
                    <Link href="/login" class="btn-secondary btn-sm hidden sm:inline-flex">{{ c.login }}</Link>
                    <Link href="/register" class="btn-primary btn-sm">{{ c.startFree }}</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-5 py-12">
            <h1 class="text-center text-3xl font-bold sm:text-4xl">{{ c.title }}</h1>
            <p class="mx-auto mt-3 max-w-2xl text-center text-slate-600">{{ c.sub }}</p>

            <div v-if="plans.length" class="mt-10 grid gap-6 md:grid-cols-3">
                <div v-for="p in plans" :key="p.slug" class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-bold">{{ name(p) }}</h2>
                    <p v-if="blurb(p)" class="mt-1 text-sm text-slate-500">{{ blurb(p) }}</p>
                    <p class="mt-4 text-3xl font-bold">
                        <template v-if="p.price !== null">{{ p.price }} <span class="text-base font-semibold">{{ p.currency }}</span> <span class="text-sm font-normal text-slate-500">{{ p.period === 'yearly' ? c.perYear : c.perMonth }}</span></template>
                        <span v-else class="text-lg text-slate-500">{{ c.contactUs }}</span>
                    </p>
                    <ul class="mt-4 flex-1 space-y-2 text-sm text-slate-700">
                        <li v-if="p.max_users" class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600" />{{ tt(c.users, p.max_users) }}</li>
                        <li v-if="p.max_patients" class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600" />{{ tt(c.patients, p.max_patients) }}</li>
                        <li v-for="f in features(p)" :key="f" class="flex gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600" />{{ f }}</li>
                    </ul>
                    <Link :href="`/register?plan=${p.slug}`" class="btn-primary mt-6 w-full">{{ c.start }}</Link>
                </div>
            </div>

            <div v-else class="mx-auto mt-10 max-w-xl rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                <h2 class="text-xl font-bold">{{ c.noPlansH }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ c.noPlansP }}</p>
                <Link href="/register" class="btn-primary mt-5 inline-block">{{ c.startFree }}</Link>
            </div>

            <div class="mx-auto mt-12 max-w-xl">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-bold">{{ c.inqH }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ c.inqP }}</p>
                    <p v-if="sent" class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ c.sentOk }}</p>
                    <form v-if="!sent" @submit.prevent="submit" class="mt-4 space-y-3">
                        <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="label">{{ c.fName }}</label>
                                <input v-model="form.name" type="text" required class="input">
                                <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="label">{{ c.fClinic }}</label>
                                <input v-model="form.clinic_name" type="text" class="input">
                            </div>
                            <div>
                                <label class="label">{{ c.fPhone }}</label>
                                <input v-model="form.phone" type="text" dir="ltr" class="input">
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-600">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="label">{{ c.fEmail }}</label>
                                <input v-model="form.email" type="email" dir="ltr" class="input">
                                <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                            </div>
                        </div>
                        <p v-if="form.errors.phone && form.errors.email" class="text-xs text-rose-600">{{ c.fEither }}</p>
                        <div v-if="plans.length">
                            <label class="label">{{ c.fPlan }}</label>
                            <select v-model="form.plan_slug" class="input">
                                <option value="">—</option>
                                <option v-for="p in plans" :key="p.slug" :value="p.slug">{{ name(p) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">{{ c.fMsg }}</label>
                            <textarea v-model="form.message" rows="3" class="input"></textarea>
                        </div>
                        <button type="submit" :disabled="form.processing" class="btn-primary w-full">{{ c.send }}</button>
                        <p class="text-center text-xs text-slate-400">{{ c.noPayment }}</p>
                    </form>
                </div>
            </div>
        </main>

        <footer class="border-t border-slate-200 py-6 text-center text-sm text-slate-500">{{ i18n.locale === 'ar' ? 'تقويم' : 'Taqwim' }}</footer>
    </div>
</template>
