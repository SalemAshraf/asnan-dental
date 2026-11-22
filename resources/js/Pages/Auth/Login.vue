<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post('/login');
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-teal-50">
        <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-sm">
            <div class="text-center mb-6">
                <div class="text-teal-700 flex justify-center"><svg class="inline-block w-12 h-12 -mt-1" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C9.5 2 8.8 3.4 7.2 3.4 5 3.4 3.5 5.6 3.5 8c0 3.6 1.7 4.7 2.1 7.4.3 2 .7 6.6 2.6 6.6 1.7 0 1.2-3.4 1.7-5.5.3-1.3.9-2 2.1-2s1.8.7 2.1 2c.5 2.1 0 5.5 1.7 5.5 1.9 0 2.3-4.6 2.6-6.6.4-2.7 2.1-3.8 2.1-7.4 0-2.4-1.5-4.6-3.7-4.6-1.6 0-2.3-1.4-4.8-1.4z"/></svg></div>
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
