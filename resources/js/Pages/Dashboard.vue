<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Kpi from '@/Components/Kpi.vue';
import BarChart from '@/Components/BarChart.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { fmt } from '@/money';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { t, fmtDate, fmtTime } from '@/i18n';

const props = defineProps({
    stats: Object, apptSeries: Array, collectedSeries: Array, patientsSeries: Array,
    chart: Array, todayAppointments: Array, unpaid: Array, todayLabel: String, clinic: Object,
    setupGuide: Object,
});
const dismissForm = useForm({});
const dismissGuide = () => dismissForm.post('/setup-guide/dismiss');
const guideVisible = computed(() => props.setupGuide && !props.setupGuide.dismissed && !props.setupGuide.complete);
const money = (v) => fmt(v, props.clinic?.currency);
const time = fmtTime;
const todayText = fmtDate(props.todayLabel, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">{{ t('dash.title') }}</h1>
                <p class="text-sm text-slate-500">{{ clinic?.name }} · {{ todayText }}</p>
            </div>
            <Link href="/appointments" class="btn-secondary btn-sm">{{ t('dash.allToday') }}</Link>
        </div>

        <div v-if="guideVisible" class="mb-6 rounded-2xl border border-brand-200 bg-white p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-ink-900">{{ t('guide.title') }}</h2>
                    <p class="mt-0.5 text-sm text-slate-500">{{ t('guide.subtitle', { done: setupGuide.done, total: setupGuide.total }) }}</p>
                </div>
                <button class="text-xs text-slate-400 hover:text-slate-600 hover:underline" @click="dismissGuide">{{ t('guide.dismiss') }}</button>
            </div>
            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-brand-600 transition-all" :style="{ width: (setupGuide.done / setupGuide.total * 100) + '%' }" />
            </div>
            <ol class="mt-4 space-y-1">
                <li v-for="(step, i) in setupGuide.steps" :key="step.key">
                    <Link :href="step.href" class="flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-50">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold" :class="step.done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                            <svg v-if="step.done" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <template v-else>{{ i + 1 }}</template>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold" :class="step.done ? 'text-slate-400 line-through' : 'text-ink-900'">{{ t('guide.step.' + step.key) }}</span>
                            <span class="block text-xs text-slate-500">{{ t('guide.hint.' + step.key) }}</span>
                        </span>
                    </Link>
                </li>
            </ol>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <Kpi :label="t('dash.kpi.today')" :value="stats.today_appointments" icon="appointments"
                :hint="t('dash.kpi.todayHint', { active: stats.today_active, done: stats.today_done, week: stats.week_appointments })" :series="apptSeries" />
            <Kpi :label="t('dash.kpi.collected')" :value="money(stats.collected_month_minor)" icon="cash" tone="good" :series="collectedSeries" />
            <Kpi :label="t('dash.kpi.outstanding')" :value="money(stats.outstanding_minor)" icon="invoices"
                :tone="stats.outstanding_minor > 0 ? 'bad' : 'slate'" :hint="t('dash.kpi.outstandingHint')" />
            <Kpi :label="t('dash.kpi.newPatients')" :value="stats.new_patients_month" icon="patients"
                :hint="stats.patients_total + ' ' + t('dash.kpi.totalPatients')" :series="patientsSeries" />
        </div>

        <div class="grid gap-4 lg:grid-cols-5">
            <div class="card lg:col-span-3">
                <h2 class="mb-4 font-bold text-ink-900">{{ t('dash.chart.title') }}</h2>
                <BarChart :rows="chart" :label-a="t('dash.chart.a')" :label-b="t('dash.chart.b')" :format="money" />
            </div>

            <div class="card lg:col-span-2">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-bold text-ink-900">{{ t('dash.unpaid.title') }}</h2>
                    <Link href="/invoices" class="text-xs font-semibold text-brand-700 hover:underline">{{ t('dash.unpaid.all') }}</Link>
                </div>
                <EmptyState v-if="!unpaid.length" icon="invoices" :title="t('dash.unpaid.empty')" :hint="t('dash.unpaid.emptyHint')" />
                <div v-else class="divide-y divide-slate-100">
                    <Link v-for="inv in unpaid" :key="inv.id" :href="`/invoices/${inv.id}`"
                        class="flex items-center justify-between gap-2 py-2.5 text-sm transition hover:bg-slate-50">
                        <span class="min-w-0">
                            <span class="block truncate font-medium text-ink-900">{{ inv.patient?.full_name }}</span>
                            <span class="text-xs text-slate-400" dir="ltr">{{ inv.number ?? '—' }}</span>
                        </span>
                        <span class="shrink-0 text-left">
                            <span class="block font-bold text-rose-700">{{ money(inv.total_minor - inv.paid_minor) }}</span>
                            <StatusBadge :value="inv.status" />
                        </span>
                    </Link>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-bold text-ink-900">{{ t('dash.schedule.title') }}</h2>
                <span class="text-xs text-slate-400">{{ t('dash.schedule.count', { active: stats.today_active, total: stats.today_appointments }) }}</span>
            </div>
            <EmptyState v-if="!todayAppointments.length" icon="appointments" :title="t('dash.schedule.empty')"
                :hint="t('dash.schedule.emptyHint')" />
            <div v-else class="divide-y divide-slate-100">
                <div v-for="a in todayAppointments" :key="a.id" class="flex flex-wrap items-center gap-3 py-3 text-sm">
                    <span class="grid w-16 shrink-0 place-items-center rounded-lg bg-brand-50 py-1.5 font-bold tabular-nums text-brand-800" dir="ltr">{{ time(a.starts_at) }}</span>
                    <span class="min-w-0 flex-1">
                        <Link :href="`/patients/${a.patient_id}`" class="block truncate font-semibold text-ink-900 hover:text-brand-700">{{ a.patient?.full_name }}</Link>
                        <span class="text-xs text-slate-500">{{ a.dentist?.name }}<span v-if="a.branch"> · {{ a.branch?.name }}</span><span v-if="a.chair"> · {{ a.chair }}</span></span>
                    </span>
                    <StatusBadge :value="a.status" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
