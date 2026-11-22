<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/platform/login');
</script>

<template>
    <Head title="لوحة المنصة — دخول" />
    <div class="flex min-h-screen items-center justify-center bg-ink-900 px-4" dir="rtl">
        <div class="w-full max-w-sm rounded-2xl bg-white p-8 shadow-2xl">
            <h1 class="text-center text-xl font-bold text-ink-900">لوحة المنصة</h1>
            <p class="mt-1 text-center text-sm text-slate-500">دخول فريق المبيعات والإدارة فقط</p>
            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <div>
                    <label class="label">البريد الإلكتروني</label>
                    <input v-model="form.email" type="email" required dir="ltr" class="input">
                    <p v-if="form.errors.email" class="mt-1 text-xs text-rose-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="label">كلمة المرور</label>
                    <input v-model="form.password" type="password" required dir="ltr" class="input">
                </div>
                <button type="submit" :disabled="form.processing" class="btn-primary w-full !py-2.5">دخول</button>
            </form>
        </div>
    </div>
</template>
