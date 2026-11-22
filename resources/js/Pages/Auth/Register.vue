<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { i18n, toggleLocale } from '@/i18n';

const props = defineProps({ plan: Object, trialDays: Number });

const form = useForm({ name: '', clinic_name: '', email: '', password: '', password_confirmation: '', plan: props.plan?.slug || '' });
const submit = () => form.post('/register');

const C = {
    ar: {
        title: 'إنشاء حساب عيادة', sub: 'إنشاء الحساب مجاني. لا نطلب أي وسيلة دفع.',
        name: 'اسمك (مالك العيادة) *', clinic: 'اسم العيادة *', email: 'البريد الإلكتروني *',
        password: 'كلمة المرور *', confirm: 'تأكيد كلمة المرور *', submit: 'إنشاء الحساب',
        have: 'عندك حساب؟', login: 'تسجيل الدخول', home: 'الرئيسية',
        planPicked: 'الباقة المختارة', trialNote: 'التفعيل بيتم يدويًا من فريقنا بعد إنشاء الحساب. حتى يتم، الحساب للاطلاع فقط وبياناتك محفوظة.',
    },
    en: {
        title: 'Create a clinic account', sub: 'Creating an account is free. No payment method asked.',
        name: 'Your name (clinic owner) *', clinic: 'Clinic name *', email: 'Email *',
        password: 'Password *', confirm: 'Confirm password *', submit: 'Create account',
        have: 'Have an account?', login: 'Log in', home: 'Home',
        planPicked: 'Selected plan', trialNote: 'Activation is done manually by our team after sign-up. Until then the account is read-only and your data stays saved.',
    },
};
const c = computed(() => C[i18n.locale] || C.ar);
const tt = (s) => s.replace('{days}', props.trialDays);
</script>

<template>
    <Head :title="c.title" />
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-b from-brand-900 to-ink-900 px-4 py-10">
        <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-6 text-center">
                <div class="flex justify-center text-brand-700">
                    <img src="/brand/taqwim-icon.svg" alt="تقويم" class="h-14 w-14 rounded-2xl shadow-lg">
                </div>
                <h1 class="mt-3 text-2xl font-bold text-ink-900">{{ c.title }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ c.sub }}</p>
                <p v-if="plan" class="mt-1 text-xs font-semibold text-brand-700">{{ c.planPicked }}: {{ i18n.locale === 'ar' ? plan.name_ar : plan.name_en }}</p>
            </div>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="label">{{ c.name }}</label>
                    <input v-model="form.name" type="text" required class="input">
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="label">{{ c.clinic }}</label>
                    <input v-model="form.clinic_name" type="text" required class="input">
                    <p v-if="form.errors.clinic_name" class="mt-1 text-xs text-rose-600">{{ form.errors.clinic_name }}</p>
                </div>
                <div>
                    <label class="label">{{ c.email }}</label>
                    <input v-model="form.email" type="email" required dir="ltr" class="input">
                    <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">{{ c.password }}</label>
                        <input v-model="form.password" type="password" required dir="ltr" class="input">
                    </div>
                    <div>
                        <label class="label">{{ c.confirm }}</label>
                        <input v-model="form.password_confirmation" type="password" required dir="ltr" class="input">
                    </div>
                </div>
                <p v-if="form.errors.password" class="text-xs text-rose-600">{{ form.errors.password }}</p>
                <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-2.5">{{ c.submit }}</button>
                <p class="text-center text-xs leading-5 text-slate-500">{{ c.trialNote }}</p>
            </form>
            <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
                <span class="text-slate-500">{{ c.have }} <Link href="/login" class="font-semibold text-brand-700 hover:underline">{{ c.login }}</Link></span>
                <span class="flex items-center gap-3">
                    <Link href="/" class="text-slate-500 hover:underline">{{ c.home }}</Link>
                    <button class="text-slate-500 hover:underline" @click="toggleLocale">{{ i18n.locale === 'ar' ? 'EN' : 'ع' }}</button>
                </span>
            </div>
        </div>
    </div>
</template>
