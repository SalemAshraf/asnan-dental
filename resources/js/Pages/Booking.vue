<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { i18n, toggleLocale } from '@/i18n';
import { fmt } from '@/money';

const props = defineProps({ clinicName: String, token: String, services: Array, currency: String });

const page = usePage();
const sent = computed(() => page.props.flash?.success);

const form = useForm({ name: '', phone: '', service_id: '', preferred_date: '', preferred_time: '', notes: '', website: '' });
const submit = () => form.post(`/b/${props.token}`, { onSuccess: () => form.reset() });

const C = {
    ar: {
        title: 'حجز موعد', sub: 'اطلب موعد في {clinic}. طلبك بيوصل للعيادة وبتأكد معاك قبل ما الموعد يتحجز فعليًا.',
        name: 'الاسم الكامل *', phone: 'رقم الهاتف *', service: 'الخدمة المطلوبة', anyService: 'غير محدد',
        date: 'اليوم المفضّل', time: 'الوقت المفضّل (مثال: 6 مساءً)', notes: 'ملاحظات (اختياري)',
        send: 'إرسال الطلب', sentH: 'طلبك وصل', sentP: 'العيادة هتتواصل معاك لتأكيد الموعد. شكرًا.',
        noAuto: 'الطلب مش حجز نهائي. العيادة بتأكد موعدك بنفسها.',
    },
    en: {
        title: 'Book an appointment', sub: 'Request an appointment at {clinic}. Your request reaches the clinic, which confirms with you before the appointment is actually booked.',
        name: 'Full name *', phone: 'Phone number *', service: 'Requested service', anyService: 'Not specified',
        date: 'Preferred day', time: 'Preferred time (e.g. 6 pm)', notes: 'Notes (optional)',
        send: 'Send request', sentH: 'Request received', sentP: 'The clinic will contact you to confirm the appointment. Thank you.',
        noAuto: 'A request is not a final booking. The clinic confirms your appointment itself.',
    },
};
const c = computed(() => C[i18n.locale] || C.ar);
const svcName = (s) => (i18n.locale === 'ar' ? s.name_ar : (s.name_en || s.name_ar));
</script>

<template>
    <Head :title="`${c.title} — ${clinicName}`" />
    <div class="flex min-h-screen items-center justify-center bg-canvas px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-5 flex items-center justify-between">
                <span class="flex items-center gap-2 font-bold text-ink-900">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-700 text-white shadow">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C9.5 2 8.8 3.4 7.2 3.4 5 3.4 3.5 5.6 3.5 8c0 3.6 1.7 4.7 2.1 7.4.3 2 .7 6.6 2.6 6.6 1.7 0 1.2-3.4 1.7-5.5.3-1.3.9-2 2.1-2s1.8.7 2.1 2c.5 2.1 0 5.5 1.7 5.5 1.9 0 2.3-4.6 2.6-6.6.4-2.7 2.1-3.8 2.1-7.4 0-2.4-1.5-4.6-3.7-4.6-1.6 0-2.3-1.4-4.8-1.4z"/></svg>
                    </span>{{ clinicName }}
                </span>
                <button class="btn-secondary btn-sm" @click="toggleLocale">{{ i18n.locale === 'ar' ? 'EN' : 'ع' }}</button>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h1 class="text-xl font-bold text-ink-900">{{ c.title }}</h1>
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ c.sub.replace('{clinic}', clinicName) }}</p>
                <div v-if="sent" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-center">
                    <p class="font-bold text-emerald-800">{{ c.sentH }}</p>
                    <p class="mt-1 text-sm text-emerald-800">{{ c.sentP }}</p>
                </div>
                <form v-else @submit.prevent="submit" class="mt-4 space-y-3">
                    <input v-model="form.website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                    <div>
                        <label class="label">{{ c.name }}</label>
                        <input v-model="form.name" type="text" required class="input">
                        <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="label">{{ c.phone }}</label>
                        <input v-model="form.phone" type="tel" required dir="ltr" class="input">
                        <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-600">{{ form.errors.phone }}</p>
                    </div>
                    <div v-if="services.length">
                        <label class="label">{{ c.service }}</label>
                        <select v-model="form.service_id" class="input">
                            <option value="">{{ c.anyService }}</option>
                            <option v-for="s in services" :key="s.id" :value="s.id">{{ svcName(s) }} — {{ fmt(s.default_price_minor, currency) }}</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label">{{ c.date }}</label>
                            <input v-model="form.preferred_date" type="date" dir="ltr" class="input">
                            <p v-if="form.errors.preferred_date" class="mt-1 text-xs text-rose-600">{{ form.errors.preferred_date }}</p>
                        </div>
                        <div>
                            <label class="label">{{ c.time }}</label>
                            <input v-model="form.preferred_time" type="text" class="input">
                        </div>
                    </div>
                    <div>
                        <label class="label">{{ c.notes }}</label>
                        <textarea v-model="form.notes" rows="2" class="input"></textarea>
                    </div>
                    <button type="submit" :disabled="form.processing" class="btn-primary w-full">{{ c.send }}</button>
                    <p class="text-center text-xs text-slate-400">{{ c.noAuto }}</p>
                </form>
            </div>
        </div>
    </div>
</template>
