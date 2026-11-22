<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pager from '@/Components/Pager.vue';
import { fmt } from '@/money';
import { Link } from '@inertiajs/vue3';
import { t } from '@/i18n';

defineProps({ invoices: Object });
</script>

<template>
    <AppLayout>
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-ink-900">{{ t('inv.title') }}</h1>
            <p class="text-sm text-slate-500">{{ t('inv.subtitle') }}</p>
        </div>
        <div class="card !p-0 overflow-hidden">
            <EmptyState v-if="!invoices.data.length" icon="invoices" :title="t('inv.empty')" :hint="t('inv.emptyHint')" />
            <table v-else class="stack w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="th">{{ t('inv.col.number') }}</th><th class="th">{{ t('inv.col.patient') }}</th>
                        <th class="th">{{ t('inv.col.total') }}</th><th class="th">{{ t('inv.col.paid') }}</th>
                        <th class="th">{{ t('inv.col.balance') }}</th><th class="th">{{ t('inv.col.status') }}</th><th class="th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="inv in invoices.data" :key="inv.id" class="transition hover:bg-brand-50/50">
                        <td class="td tabular-nums text-slate-500" dir="ltr" :data-label="t('inv.col.number')">{{ inv.number ?? t('inv.draft') }}</td>
                        <td class="td font-semibold text-ink-900" :data-label="t('inv.col.patient')">{{ inv.patient?.full_name }}</td>
                        <td class="td tabular-nums" :data-label="t('inv.col.total')">{{ fmt(inv.total_minor, inv.currency) }}</td>
                        <td class="td tabular-nums" :data-label="t('inv.col.paid')">{{ fmt(inv.paid_minor, inv.currency) }}</td>
                        <td class="td tabular-nums font-bold" :class="inv.total_minor - inv.paid_minor > 0 ? 'text-rose-700' : 'text-emerald-700'" :data-label="t('inv.col.balance')">{{ fmt(inv.total_minor - inv.paid_minor, inv.currency) }}</td>
                        <td class="td" :data-label="t('inv.col.status')"><StatusBadge :value="inv.status" /></td>
                        <td class="td" data-label=""><Link :href="`/invoices/${inv.id}`" class="font-semibold text-brand-700 hover:underline">{{ t('inv.open') }}</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><Pager :links="invoices.links" /></div>
    </AppLayout>
</template>
