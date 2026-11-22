<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login');
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-teal-50">
        <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-sm">
            <div class="text-center mb-6">
                <div class="text-4xl">🦷</div>
                <h1 class="text-2xl font-bold text-teal-800 mt-2">أسنان</h1>
                <p class="text-gray-500 text-sm">نظام إدارة عيادات الأسنان</p>
            </div>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm mb-1">البريد الإلكتروني</label>
                    <input v-model="form.email" type="email" required dir="ltr"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                    <p v-if="form.errors.email" class="text-red-600 text-xs mt-1">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-sm mb-1">كلمة المرور</label>
                    <input v-model="form.password" type="password" required dir="ltr"
                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <button type="submit" :disabled="form.processing"
                    class="w-full bg-teal-700 hover:bg-teal-600 text-white rounded-lg py-2 font-semibold disabled:opacity-50">
                    تسجيل الدخول
                </button>
            </form>
        </div>
    </div>
</template>
