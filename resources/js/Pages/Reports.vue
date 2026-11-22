<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Kpi from '@/Components/Kpi.vue';
import Badge from '@/Components/Badge.vue';
import BarChart from '@/Components/BarChart.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { fmt } from '@/money';
import { statusLabel, t } from '@/i18n';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ range: Object, currency: String, cards: Object, byMethod: Object, byStatus: Object, perDentist: Array, topServices: Array, daily: Array });
const money = (v) => fmt(v, props.currency);

const from = ref(props.range.from);
const to = ref(props.range.to);
const apply = () => router.get('/reports', { from: from.value, to: to.value }, { preserveState: true });
const preset = (days) => {
    const t = new Date(); const f = new Date(); f.setDate(f.getDate() - (days - 1));
    from.value = f.toISOString().slice(0, 10); to.value = t.toISOString().slice(0, 10); apply();
};
const methodMax = Math.max(1, ...Object.values(props.byMethod));
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">{{ t('rep.title') }}</h1>
                <p class="text-sm text-slate-500 tabular-nums" dir="ltr">{{ range.from }} → {{ range.to }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <button @click="preset(30)" class="btn-secondary btn-sm">{{ t('rep.30d') }}</button>
                <button @click="preset(90)" class="btn-secondary btn-sm">{{ t('rep.90d') }}</button>
                <input v-model="from" type="date" class="input !w-auto !py-1.5" :aria-label="t('rep.from')">
                <input v-model="to" type="date" class="input !w-auto !py-1.5" :aria-label="t('rep.to')">
                <button @click="apply" class="btn-primary btn-sm !py-2">{{ t('rep.apply') }}</button>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <Kpi :label="t('rep.kpi.collected')" :value="money(cards.collected_minor)" icon="cash" tone="good" />
            <Kpi :label="t('rep.kpi.invoiced')" :value="money(cards.invoiced_minor)" icon="invoices" />
            <Kpi :label="t('rep.kpi.outstanding')" :value="money(cards.outstanding_minor)" icon="invoices" :tone="cards.outstanding_minor > 0 ? 'bad' : 'slate'" />
            <Kpi :label="t('rep.kpi.newPatients')" :value="cards.new_patients" icon="patients" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="card lg:col-span-2">
                <h2 class="mb-4 font-bold text-ink-900">{{ t('rep.chart.title') }}</h2>
                <EmptyState v-if="!cards.collected_minor" icon="reports" :title="t('rep.chart.empty')" />
                <BarChart v-else :rows="daily" :label-a="t('dash.chart.a')" :label-b="t('dash.chart.b')" :format="money" />
            </div>

            <div class="card">
                <h2 class="mb-4 font-bold text-ink-900">{{ t('rep.byMethod') }}</h2>
                <EmptyState v-if="!cards.collected_minor" icon="cash" :title="t('rep.byMethod.empty')" />
                <div v-else class="space-y-2.5 text-sm">
                    <div v-for="(v, m) in byMethod" :key="m" class="flex items-center gap-3">
                        <span class="w-28 shrink-0 text-slate-600">{{ t('pay.' + m) }}</span>
                        <div class="h-4 flex-1 overflow-hidden rounded bg-slate-100">
                            <div class="h-full rounded bg-brand-600" :style="{ width: (v / methodMax * 100) + '%' }" />
                        </div>
                        <span class="w-24 shrink-0 text-end tabular-nums font-semibold">{{ money(v) }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="mb-4 font-bold text-ink-900">{{ t('rep.byStatus') }}</h2>
                <div class="flex flex-wrap gap-2">
                    <Badge v-for="(v, s) in byStatus" :key="s" :tone="{ scheduled: 'blue', confirmed: 'violet', checked_in: 'amber', completed: 'green', cancelled: 'slate', no_show: 'rose' }[s]">
                        {{ statusLabel(s) }} · {{ v }}
                    </Badge>
                </div>
                <p class="mt-4 text-sm text-slate-500">{{ t('rep.rates', { done: cards.completion_pct, noShow: cards.no_show_pct }) }}</p>
            </div>

            <div class="card">
                <h2 class="mb-3 font-bold text-ink-900">{{ t('rep.dentists') }}</h2>
                <EmptyState v-if="!perDentist.length" icon="patients" :title="t('rep.dentists.empty')" />
                <table v-else class="w-full text-sm">
                    <thead class="border-b border-slate-200">
                        <tr><th class="th !px-0">{{ t('rep.col.dentist') }}</th><th class="th">{{ t('rep.col.appts') }}</th><th class="th">{{ t('rep.col.completed') }}</th><th class="th">{{ t('rep.col.noShow') }}</th><th class="th !px-0">{{ t('rep.col.cancelled') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="d in perDentist" :key="d.dentist">
                            <td class="td !px-0 font-medium">{{ d.dentist }}</td>
                            <td class="td tabular-nums">{{ d.total }}</td>
                            <td class="td tabular-nums text-emerald-700">{{ d.completed }}</td>
                            <td class="td tabular-nums text-rose-700">{{ d.no_show }}</td>
                            <td class="td !px-0 tabular-nums text-slate-500">{{ d.cancelled }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2 class="mb-3 font-bold text-ink-900">{{ t('rep.topServices') }}</h2>
                <EmptyState v-if="!topServices.length" icon="services" :title="t('rep.topServices.empty')" />
                <table v-else class="w-full text-sm">
                    <thead class="border-b border-slate-200">
                        <tr><th class="th !px-0">{{ t('rep.col.service') }}</th><th class="th">{{ t('rep.col.count') }}</th><th class="th !px-0 text-end">{{ t('rep.col.value') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="s in topServices" :key="s.name">
                            <td class="td !px-0 font-medium">{{ s.name }}</td>
                            <td class="td tabular-nums">{{ s.count }}</td>
                            <td class="td !px-0 text-end tabular-nums font-semibold">{{ money(s.total_minor) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
