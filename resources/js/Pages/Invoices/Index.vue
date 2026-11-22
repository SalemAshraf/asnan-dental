<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pager from '@/Components/Pager.vue';
import { fmt } from '@/money';
import { Link } from '@inertiajs/vue3';

defineProps({ invoices: Object });
</script>

<template>
    <AppLayout>
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-ink-900">الفواتير</h1>
            <p class="text-sm text-slate-500">تُنشأ الفواتير من خطط العلاج المقبولة في ملف المريض</p>
        </div>
        <div class="card !p-0 overflow-hidden">
            <EmptyState v-if="!invoices.data.length" icon="invoices" title="لا توجد فواتير بعد" hint="أنشئها من خطة علاج مقبولة في ملف المريض." />
            <table v-else class="stack w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="th">رقم</th><th class="th">المريض</th>
                        <th class="th">الإجمالي</th><th class="th">المدفوع</th>
                        <th class="th">المتبقي</th><th class="th">الحالة</th><th class="th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="inv in invoices.data" :key="inv.id" class="transition hover:bg-brand-50/50">
                        <td class="td tabular-nums text-slate-500" dir="ltr" data-label="رقم">{{ inv.number ?? 'مسودة' }}</td>
                        <td class="td font-semibold text-ink-900" data-label="المريض">{{ inv.patient?.full_name }}</td>
                        <td class="td tabular-nums" data-label="الإجمالي">{{ fmt(inv.total_minor, inv.currency) }}</td>
                        <td class="td tabular-nums" data-label="المدفوع">{{ fmt(inv.paid_minor, inv.currency) }}</td>
                        <td class="td tabular-nums font-bold" :class="inv.total_minor - inv.paid_minor > 0 ? 'text-rose-700' : 'text-emerald-700'" data-label="المتبقي">{{ fmt(inv.total_minor - inv.paid_minor, inv.currency) }}</td>
                        <td class="td" data-label="الحالة"><StatusBadge :value="inv.status" /></td>
                        <td class="td" data-label=""><Link :href="`/invoices/${inv.id}`" class="font-semibold text-brand-700 hover:underline">فتح</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><Pager :links="invoices.links" /></div>
    </AppLayout>
</template>
