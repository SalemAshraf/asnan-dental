<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { i18n, toggleLocale } from '@/i18n';

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login');

const C = {
    ar: { tagline: 'نظام إدارة عيادات الأسنان', email: 'البريد الإلكتروني', password: 'كلمة المرور', login: 'تسجيل الدخول' },
    en: { tagline: 'Dental clinic management', email: 'Email', password: 'Password', login: 'Sign in' },
};
const c = computed(() => C[i18n.locale] || C.ar);
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-b from-brand-900 to-ink-900 px-4">
        <div class="w-full max-w-sm">
            <div class="mb-3 flex justify-end">
                <button class="rounded-lg bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20" @click="toggleLocale">{{ i18n.locale === 'ar' ? 'EN' : 'ع' }}</button>
            </div>
            <div class="rounded-2xl bg-white p-8 shadow-2xl">
                <div class="mb-6 text-center">
                    <div class="flex justify-center text-brand-700">
                        <img src="/brand/taqwim-icon.svg" alt="تقويم" class="h-14 w-14 rounded-2xl shadow-lg">
                    </div>
                    <h1 class="mt-3 text-2xl font-bold text-ink-900">تقويم</h1>
                    <p class="text-sm text-slate-500">{{ c.tagline }}</p>
                </div>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="label">{{ c.email }}</label>
                        <input v-model="form.email" type="email" required dir="ltr" class="input">
                        <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="label">{{ c.password }}</label>
                        <input v-model="form.password" type="password" required dir="ltr" class="input">
                    </div>
                    <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-2.5">
                        {{ c.login }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
