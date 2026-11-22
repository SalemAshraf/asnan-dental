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
                    <img src="/brand/taqwim-icon.svg" alt="تقويم" class="h-14 w-14 rounded-2xl shadow-lg">
                </div>
                <h1 class="mt-3 text-2xl font-bold text-ink-900">تقويم</h1>
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
