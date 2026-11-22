<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { t } from '@/i18n';
import { fmt } from '@/money';

const props = defineProps({ expenses: Array, byCategory: Object, month: String, monthTotal: Number, currency: String });

const money = (v) => fmt(v, props.currency);
const month = ref(props.month);
const applyMonth = () => router.get('/expenses', { month: month.value }, { preserveState: true });

const form = useForm({ category: 'supplies', amount: '', spent_on: new Date().toISOString().slice(0, 10), vendor: '', notes: '' });
const save = () => form.post('/expenses', { preserveScroll: true, onSuccess: () => { form.reset('amount', 'vendor', 'notes'); } });
const remove = (id) => {
    if (!window.confirm(t('exp.confirmDelete'))) return;
    router.delete(`/expenses/${id}`, { preserveScroll: true });
};
const dstr = (d) => (d ? String(d).slice(0, 10) : '');
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">{{ t('exp.title') }}</h1>
                <p class="text-sm text-slate-500">{{ t('exp.subtitle') }}</p>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="month" type="month" class="input !w-auto" dir="ltr" @change="applyMonth" />
            </label>
        </div>

        <form @submit.prevent="save" class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('exp.new') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('exp.form.category') }}</span>
                    <select v-model="form.category" class="input">
                        <option v-for="c in ['rent','salaries','lab','supplies','utilities','marketing','other']" :key="c" :value="c">{{ t('exp.cat.' + c) }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('exp.form.amount') }}</span>
                    <input v-model="form.amount" type="number" step="0.01" min="0.01" required class="input" dir="ltr" />
                    <p v-if="form.errors.amount" class="mt-1 text-xs text-rose-600">{{ form.errors.amount }}</p>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('exp.form.date') }}</span>
                    <input v-model="form.spent_on" type="date" required class="input" dir="ltr" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('exp.form.vendor') }}</span>
                    <input v-model="form.vendor" type="text" maxlength="190" class="input" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('exp.form.notes') }}</span>
                    <input v-model="form.notes" type="text" maxlength="2000" class="input" />
                </label>
            </div>
            <button type="submit" class="btn-primary mt-3" :disabled="form.processing"><Icon name="plus" :size="16" /> {{ t('exp.form.save') }}</button>
        </form>

        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div class="card">
                <p class="text-xs text-slate-500">{{ t('exp.monthTotal') }}</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-ink-900">{{ money(monthTotal) }}</p>
            </div>
            <div class="card">
                <p class="mb-2 text-xs text-slate-500">{{ t('exp.byCategory') }}</p>
                <div v-if="Object.keys(byCategory ?? {}).length" class="space-y-1 text-sm">
                    <div v-for="(v, c) in byCategory" :key="c" class="flex items-center justify-between gap-2">
                        <span>{{ t('exp.cat.' + c) }}</span>
                        <span class="tabular-nums font-semibold">{{ money(v) }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">—</p>
            </div>
        </div>

        <div class="card !p-0 overflow-hidden">
            <EmptyState v-if="!(expenses ?? []).length" icon="cash" :title="t('exp.empty')" />
            <div v-else class="table-wrap">
                <table class="table stack">
                    <thead>
                        <tr>
                            <th class="th">{{ t('exp.form.date') }}</th>
                            <th class="th">{{ t('exp.form.category') }}</th>
                            <th class="th">{{ t('exp.vendor') }}</th>
                            <th class="th">{{ t('exp.form.notes') }}</th>
                            <th class="th">{{ t('exp.recordedBy') }}</th>
                            <th class="th text-end">{{ t('exp.form.amount') }}</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in expenses" :key="e.id">
                            <td class="td whitespace-nowrap tabular-nums" dir="ltr" :data-label="t('exp.form.date')">{{ dstr(e.spent_on) }}</td>
                            <td class="td" :data-label="t('exp.form.category')">{{ t('exp.cat.' + e.category) }}</td>
                            <td class="td" :data-label="t('exp.vendor')">{{ e.vendor || '—' }}</td>
                            <td class="td max-w-48 truncate" :data-label="t('exp.form.notes')">{{ e.notes || '—' }}</td>
                            <td class="td text-xs text-slate-500" :data-label="t('exp.recordedBy')">{{ e.creator?.name || '—' }}</td>
                            <td class="td text-end tabular-nums font-semibold" :data-label="t('exp.form.amount')">{{ money(e.amount_minor) }}</td>
                            <td class="td" data-label="">
                                <button type="button" class="btn-secondary btn-sm !text-rose-600" @click="remove(e.id)">{{ t('exp.delete') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
