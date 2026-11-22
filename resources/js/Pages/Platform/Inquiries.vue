<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineProps({ inquiries: Array });

const statusLabel = { new: 'جديد', contacted: 'تم التواصل', closed: 'مغلق' };
const statusClass = { new: 'bg-sky-100 text-sky-800', contacted: 'bg-amber-100 text-amber-800', closed: 'bg-slate-100 text-slate-600' };
const setStatus = (id, status) => useForm({ status }).patch(`/platform/inquiries/${id}`);
const fmt = (d) => new Date(d).toLocaleString('ar-EG', { dateStyle: 'medium', timeStyle: 'short' });
</script>

<template>
    <Head title="طلبات التواصل — لوحة المنصة" />
    <PlatformLayout title="طلبات التواصل">
        <p v-if="!inquiries.length" class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm">لا توجد طلبات بعد.</p>
        <div class="space-y-3">
            <div v-for="q in inquiries" :key="q.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="font-bold">{{ q.name }} <span v-if="q.clinic_name" class="font-normal text-slate-500">— {{ q.clinic_name }}</span></p>
                        <p class="mt-0.5 text-sm text-slate-500" dir="ltr">{{ [q.phone, q.email].filter(Boolean).join(' · ') }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span v-if="q.plan_slug" class="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-800" dir="ltr">{{ q.plan_slug }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusClass[q.status]">{{ statusLabel[q.status] }}</span>
                    </div>
                </div>
                <p v-if="q.message" class="mt-2 whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm text-slate-700">{{ q.message }}</p>
                <div class="mt-3 flex items-center justify-between text-xs text-slate-400">
                    <span>{{ fmt(q.created_at) }}</span>
                    <span class="flex gap-2">
                        <button v-if="q.status !== 'contacted'" class="text-brand-700 hover:underline" @click="setStatus(q.id, 'contacted')">تم التواصل</button>
                        <button v-if="q.status !== 'closed'" class="text-slate-500 hover:underline" @click="setStatus(q.id, 'closed')">إغلاق</button>
                    </span>
                </div>
            </div>
        </div>
    </PlatformLayout>
</template>
