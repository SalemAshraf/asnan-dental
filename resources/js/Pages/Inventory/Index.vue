<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { intlLocale, t } from '@/i18n';

const props = defineProps({ items: Array, movements: Array, rules: Array, services: Array });
const page = usePage();
const loc = computed(() => intlLocale());
const num = (v) => Number(v ?? 0).toLocaleString(loc.value, { maximumFractionDigits: 3 });
const lowCount = computed(() => (props.items ?? []).filter((i) => i.low).length);

const itemForm = useForm({ name: '', unit: 'piece', quantity: '', low_stock_threshold: '', notes: '' });
const saveItem = () => itemForm.post('/inventory/items', { preserveScroll: true, onSuccess: () => itemForm.reset() });

const moveForm = useForm({ inventory_item_id: '', reason: 'purchase', qty: '', note: '' });
const saveMovement = () => moveForm.post('/inventory/movements', { preserveScroll: true, onSuccess: () => moveForm.reset('qty', 'note') });

const ruleForm = useForm({ service_id: '', inventory_item_id: '', qty: '' });
const saveRule = () => ruleForm.post('/inventory/rules', { preserveScroll: true, onSuccess: () => ruleForm.reset('qty') });
const removeRule = (id) => {
    if (!window.confirm(t('inv.confirmDeleteRule'))) return;
    router.delete(`/inventory/rules/${id}`, { preserveScroll: true });
};
const svcName = (s) => (loc.value === 'ar' ? (s?.name_ar || s?.name_en) : (s?.name_en || s?.name_ar));
const dt = (d) => (d ? String(d).slice(0, 16).replace('T', ' ') : '');
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">{{ t('inv.title') }}</h1>
                <p class="text-sm text-slate-500">{{ t('inv.subtitle') }}</p>
            </div>
            <Badge v-if="lowCount" tone="rose">{{ t('inv.low') }}: {{ lowCount }}</Badge>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <form @submit.prevent="saveItem" class="card">
                <h2 class="mb-3 font-bold text-ink-900">{{ t('inv.addItem') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block sm:col-span-2">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('inv.form.name') }}</span>
                        <input v-model="itemForm.name" type="text" required maxlength="190" class="input" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('inv.form.unit') }}</span>
                        <select v-model="itemForm.unit" class="input">
                            <option v-for="u in ['piece','box','pack','bottle','kit']" :key="u" :value="u">{{ t('inv.unit.' + u) }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('inv.form.opening') }}</span>
                        <input v-model="itemForm.quantity" type="number" step="0.001" min="0" class="input" dir="ltr" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('inv.form.threshold') }}</span>
                        <input v-model="itemForm.low_stock_threshold" type="number" step="0.001" min="0" class="input" dir="ltr" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('inv.form.notes') }}</span>
                        <input v-model="itemForm.notes" type="text" maxlength="2000" class="input" />
                    </label>
                </div>
                <button type="submit" class="btn-primary mt-3" :disabled="itemForm.processing"><Icon name="plus" :size="16" /> {{ t('inv.form.save') }}</button>
            </form>

            <form @submit.prevent="saveMovement" class="card">
                <h2 class="mb-3 font-bold text-ink-900">{{ t('inv.movement') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('inv.form.item') }}</span>
                        <select v-model="moveForm.inventory_item_id" required class="input">
                            <option value="" disabled>—</option>
                            <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option>
                        </select>
                        <p v-if="moveForm.errors.inventory_item_id" class="mt-1 text-xs text-rose-600">{{ moveForm.errors.inventory_item_id }}</p>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('inv.form.reason') }}</span>
                        <select v-model="moveForm.reason" class="input">
                            <option value="purchase">{{ t('inv.reason.purchase') }}</option>
                            <option value="usage">{{ t('inv.reason.usage') }}</option>
                            <option value="adjustment">{{ t('inv.reason.adjustment') }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('inv.form.qty') }}</span>
                        <input v-model="moveForm.qty" type="number" step="0.001" min="0.001" required class="input" dir="ltr" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('inv.form.note') }}</span>
                        <input v-model="moveForm.note" type="text" maxlength="500" class="input" />
                    </label>
                </div>
                <button type="submit" class="btn-primary mt-3" :disabled="moveForm.processing">{{ t('inv.form.record') }}</button>
            </form>
        </div>

        <div class="card mt-4 !p-0 overflow-hidden">
            <EmptyState v-if="!(items ?? []).length" icon="services" :title="t('inv.itemsEmpty')" />
            <div v-else class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="th">{{ t('inv.form.name') }}</th>
                            <th class="th">{{ t('inv.form.unit') }}</th>
                            <th class="th text-end">{{ t('inv.onHand') }}</th>
                            <th class="th text-end">{{ t('inv.form.threshold') }}</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="i in items" :key="i.id">
                            <td class="td font-semibold" :data-label="t('inv.form.name')">{{ i.name }}</td>
                            <td class="td" :data-label="t('inv.form.unit')">{{ t('inv.unit.' + i.unit) }}</td>
                            <td class="td text-end tabular-nums font-bold" :data-label="t('inv.onHand')">{{ num(i.quantity) }}</td>
                            <td class="td text-end tabular-nums text-slate-500" :data-label="t('inv.form.threshold')">{{ i.low_stock_threshold != null ? num(i.low_stock_threshold) : '—' }}</td>
                            <td class="td" data-label=""><Badge v-if="i.low" tone="rose">{{ t('inv.low') }}</Badge></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <h2 class="font-bold text-ink-900">{{ t('inv.rules') }}</h2>
            <p class="mb-3 text-xs text-slate-500">{{ t('inv.rulesHint') }}</p>
            <form @submit.prevent="saveRule" class="mb-3 grid gap-3 sm:grid-cols-4">
                <select v-model="ruleForm.service_id" required class="input">
                    <option value="" disabled>{{ t('inv.form.service') }}</option>
                    <option v-for="s in services" :key="s.id" :value="s.id">{{ svcName(s) }}</option>
                </select>
                <select v-model="ruleForm.inventory_item_id" required class="input">
                    <option value="" disabled>{{ t('inv.form.item') }}</option>
                    <option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option>
                </select>
                <input v-model="ruleForm.qty" type="number" step="0.001" min="0.001" required :placeholder="t('inv.form.ruleQty')" class="input" dir="ltr" />
                <button type="submit" class="btn-primary" :disabled="ruleForm.processing">{{ t('inv.form.addRule') }}</button>
            </form>
            <p v-if="!(rules ?? []).length" class="text-sm text-slate-400">{{ t('inv.rulesEmpty') }}</p>
            <div v-else class="divide-y divide-slate-100">
                <div v-for="r in rules" :key="r.id" class="flex items-center gap-3 py-2 text-sm">
                    <span class="min-w-0 flex-1 truncate">{{ svcName(r.service) }} ← {{ r.item?.name }} <span class="tabular-nums font-semibold" dir="ltr">× {{ num(r.qty) }}</span></span>
                    <button @click="removeRule(r.id)" class="btn-secondary btn-sm !text-rose-600">{{ t('inv.delete') }}</button>
                </div>
            </div>
        </div>

        <div class="card mt-4 !p-0 overflow-hidden">
            <h2 class="px-4 pt-4 font-bold text-ink-900">{{ t('inv.recent') }}</h2>
            <EmptyState v-if="!(movements ?? []).length" icon="clock" :title="t('inv.movementsEmpty')" />
            <div v-else class="table-wrap mt-2">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="th">{{ t('inv.form.item') }}</th>
                            <th class="th">{{ t('inv.form.reason') }}</th>
                            <th class="th text-end">{{ t('inv.form.qty') }}</th>
                            <th class="th">{{ t('inv.form.note') }}</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in movements" :key="m.id">
                            <td class="td" :data-label="t('inv.form.item')">{{ m.item?.name }}</td>
                            <td class="td text-xs" :data-label="t('inv.form.reason')">{{ t('inv.reason.' + m.reason) }}<span class="block text-slate-400">{{ m.user?.name }} · <span class="tabular-nums" dir="ltr">{{ dt(m.created_at) }}</span></span></td>
                            <td class="td text-end tabular-nums font-semibold" :class="Number(m.change) < 0 ? 'text-rose-700' : 'text-emerald-700'" :data-label="t('inv.form.qty')" dir="ltr">{{ Number(m.change) > 0 ? '+' : '' }}{{ num(m.change) }}</td>
                            <td class="td max-w-44 truncate text-slate-500" colspan="2" :data-label="t('inv.form.note')">{{ m.note || '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
