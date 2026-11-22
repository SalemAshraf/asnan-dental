<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineProps({ clinics: Array });

const statusLabel = { trialing: 'تجربة', active: 'مفعّلة', past_due: 'متأخرة', cancelled: 'ملغاة', pending: 'معلّقة' };
const statusClass = { trialing: 'bg-sky-100 text-sky-800', active: 'bg-emerald-100 text-emerald-800', past_due: 'bg-amber-100 text-amber-800', cancelled: 'bg-slate-100 text-slate-600', pending: 'bg-slate-100 text-slate-600' };
</script>

<template>
    <Head title="العيادات — لوحة المنصة" />
    <PlatformLayout title="العيادات">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-start text-xs text-slate-500">
                        <th class="px-4 py-3 text-start font-medium">العيادة</th>
                        <th class="px-4 py-3 text-start font-medium">الحالة</th>
                        <th class="px-4 py-3 text-start font-medium">الباقة</th>
                        <th class="px-4 py-3 text-start font-medium">المستخدمون</th>
                        <th class="px-4 py-3 text-start font-medium">المرضى</th>
                        <th class="px-4 py-3 text-start font-medium">أنشئت</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="cl in clinics" :key="cl.id" class="border-b border-slate-50 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <Link :href="`/platform/clinics/${cl.id}`" class="font-semibold text-brand-700 hover:underline">{{ cl.name }}</Link>
                            <span v-if="cl.suspended" class="ms-2 rounded-full bg-rose-100 px-2 py-0.5 text-xs text-rose-700">موقوفة</span>
                        </td>
                        <td class="px-4 py-3"><span v-if="cl.subscription" class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusClass[cl.subscription.status]">{{ statusLabel[cl.subscription.status] }}</span><span v-else class="text-xs text-slate-400">—</span></td>
                        <td class="px-4 py-3 text-slate-600">{{ cl.subscription?.plan ? cl.subscription.plan.ar : '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ cl.users }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ cl.patients }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ cl.created_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PlatformLayout>
</template>
