<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login');
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-b from-brand-900 to-ink-900 px-4">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-2xl">
            <div class="mb-6 text-center">
                <div class="flex justify-center text-brand-700">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-brand-700 text-white shadow-lg">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C9.5 2 8.8 3.4 7.2 3.4 5 3.4 3.5 5.6 3.5 8c0 3.6 1.7 4.7 2.1 7.4.3 2 .7 6.6 2.6 6.6 1.7 0 1.2-3.4 1.7-5.5.3-1.3.9-2 2.1-2s1.8.7 2.1 2c.5 2.1 0 5.5 1.7 5.5 1.9 0 2.3-4.6 2.6-6.6.4-2.7 2.1-3.8 2.1-7.4 0-2.4-1.5-4.6-3.7-4.6-1.6 0-2.3-1.4-4.8-1.4z"/></svg>
                    </span>
                </div>
                <h1 class="mt-3 text-2xl font-bold text-ink-900">بسمة</h1>
                <p class="text-sm text-slate-500">نظام إدارة عيادات الأسنان</p>
            </div>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="label">البريد الإلكتروني</label>
                    <input v-model="form.email" type="email" required dir="ltr" class="input">
                    <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="label">كلمة المرور</label>
                    <input v-model="form.password" type="password" required dir="ltr" class="input">
                </div>
                <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-2.5">
                    تسجيل الدخول
                </button>
            </form>
        </div>
    </div>
</template>
