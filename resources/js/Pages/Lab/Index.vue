<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { t } from '@/i18n';
import { fmt } from '@/money';

const props = defineProps({ open: Array, closed: Array, dentists: Array });
const page = usePage();
const isOwner = computed(() => page.props.auth?.user?.role === 'owner');
const money = (v) => fmt(v, page.props.auth?.clinic?.currency ?? 'EGP');

const showForm = ref(false);
const editing = ref(null);
const patientQ = ref('');
const patientResults = ref([]);
const form = useForm({ patient_id: '', dentist_id: '', lab_name: '', work_type: 'crown', shade: '', sent_on: new Date().toISOString().slice(0, 10), due_on: '', cost: '', notes: '' });

const searchPatients = async () => {
    if (patientQ.value.length < 2) { patientResults.value = []; return; }
    const res = await fetch(`/patients?q=${encodeURIComponent(patientQ.value)}`, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) { patientResults.value = []; return; }
    const data = await res.json();
    patientResults.value = data.patients?.slice(0, 5) ?? [];
};

const startEdit = (c) => {
    editing.value = c.id;
    showForm.value = true;
    form.patient_id = c.patient_id || '';
    patientQ.value = c.patient ? `${c.patient.full_name} #${c.patient.file_no}` : '';
    form.dentist_id = c.dentist_id || '';
    form.lab_name = c.lab_name;
    form.work_type = c.work_type;
    form.shade = c.shade || '';
    form.sent_on = dstr(c.sent_on);
    form.due_on = dstr(c.due_on);
    form.cost = c.cost_minor != null ? (c.cost_minor / 100).toString() : '';
    form.notes = c.notes || '';
};
const cancelEdit = () => { editing.value = null; showForm.value = false; form.reset(); patientQ.value = ''; };
const save = () => {
    const opts = { preserveScroll: true, onSuccess: () => cancelEdit() };
    if (editing.value) form.put(`/lab-cases/${editing.value}`, opts);
    else form.post('/lab-cases', opts);
};
const setStatus = (id, status) => router.post(`/lab-cases/${id}/transition`, { status }, { preserveScroll: true });
const remove = (id) => {
    if (!window.confirm(t('lab.confirmDelete'))) return;
    router.delete(`/lab-cases/${id}`, { preserveScroll: true });
};
const dstr = (d) => (d ? String(d).slice(0, 10) : '');
const today = new Date().toISOString().slice(0, 10);
const isOverdue = (c) => c.due_on && dstr(c.due_on) < today && c.status !== 'received';
const tone = (s) => ({ sent: 'amber', in_progress: 'blue', received: 'green', fitted: 'green', cancelled: 'slate' }[s] ?? 'slate');
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">{{ t('lab.title') }}</h1>
                <p class="text-sm text-slate-500">{{ t('lab.subtitle') }}</p>
            </div>
            <button @click="showForm = !showForm" class="btn-primary"><Icon name="plus" :size="16" /> {{ t('lab.new') }}</button>
        </div>

        <form v-if="showForm" @submit.prevent="save" class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">{{ editing ? t('lab.edit') : t('lab.new') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="relative">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.patient') }}</span>
                    <input v-model="patientQ" @input="searchPatients" :placeholder="t('lab.form.patientSearch')" class="input" />
                    <div v-if="patientResults.length" class="absolute z-10 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg">
                        <div v-for="p in patientResults" :key="p.id" @click="form.patient_id = p.id; patientQ = p.full_name + ' #' + p.file_no; patientResults = []"
                            class="cursor-pointer border-b border-slate-100 px-3 py-2 text-sm last:border-0 hover:bg-brand-50">{{ p.full_name }} <span class="text-slate-400">#{{ p.file_no }}</span></div>
                    </div>
                </div>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.dentist') }}</span>
                    <select v-model="form.dentist_id" class="input">
                        <option value="">—</option>
                        <option v-for="d in dentists" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.labName') }}</span>
                    <input v-model="form.lab_name" type="text" required maxlength="190" class="input" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.workType') }}</span>
                    <select v-model="form.work_type" class="input">
                        <option v-for="w in ['crown','bridge','denture','veneer','implant','orthodontic','other']" :key="w" :value="w">{{ t('lab.wt.' + w) }}</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.shade') }}</span>
                    <input v-model="form.shade" type="text" maxlength="60" class="input" dir="ltr" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.sentOn') }}</span>
                    <input v-model="form.sent_on" type="date" required class="input" dir="ltr" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.dueOn') }}</span>
                    <input v-model="form.due_on" type="date" class="input" dir="ltr" />
                    <p v-if="form.errors.due_on" class="mt-1 text-xs text-rose-600">{{ form.errors.due_on }}</p>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.cost') }}</span>
                    <input v-model="form.cost" type="number" step="0.01" min="0" class="input" dir="ltr" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.notes') }}</span>
                    <input v-model="form.notes" type="text" maxlength="2000" class="input" />
                </label>
            </div>
            <div class="mt-3 flex gap-2">
                <button type="submit" class="btn-primary" :disabled="form.processing">{{ t('lab.form.save') }}</button>
                <button type="button" class="btn-secondary" @click="cancelEdit">{{ t('lab.cancel') }}</button>
            </div>
        </form>

        <div class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('lab.open', { n: (open ?? []).length }) }}</h2>
            <EmptyState v-if="!(open ?? []).length" icon="services" :title="t('lab.empty')" />
            <div v-else class="divide-y divide-slate-100">
                <div v-for="c in open" :key="c.id" class="flex flex-wrap items-center gap-3 py-3 text-sm">
                    <span class="min-w-52 flex-1">
                        <span class="block truncate font-semibold text-ink-900">
                            {{ c.patient?.full_name || '—' }} <span v-if="c.patient" class="font-normal text-slate-400">#{{ c.patient.file_no }}</span>
                            <span class="ms-1 font-normal text-slate-500">· {{ t('lab.wt.' + c.work_type) }}<template v-if="c.shade"> · <span dir="ltr">{{ c.shade }}</span></template></span>
                        </span>
                        <span class="text-xs text-slate-500">
                            {{ c.lab_name }}<template v-if="c.dentist"> · {{ c.dentist.name }}</template>
                            <template v-if="c.due_on"> · {{ t('lab.due') }} <span class="tabular-nums" dir="ltr">{{ dstr(c.due_on) }}</span><span v-if="isOverdue(c)" class="ms-1 font-semibold text-rose-600">{{ t('lab.overdue') }}</span></template>
                            <template v-if="c.cost_minor != null"> · {{ money(c.cost_minor) }}</template>
                        </span>
                    </span>
                    <Badge :tone="tone(c.status)">{{ t('lab.st.' + c.status) }}</Badge>
                    <button v-if="c.status === 'sent'" @click="setStatus(c.id, 'in_progress')" class="btn-secondary btn-sm">{{ t('lab.act.progress') }}</button>
                    <button v-if="c.status === 'sent' || c.status === 'in_progress'" @click="setStatus(c.id, 'received')" class="btn-primary btn-sm">{{ t('lab.act.receive') }}</button>
                    <button v-if="c.status === 'received'" @click="setStatus(c.id, 'fitted')" class="btn-primary btn-sm">{{ t('lab.act.fit') }}</button>
                    <button @click="startEdit(c)" class="btn-secondary btn-sm">{{ t('lab.edit') }}</button>
                    <button @click="setStatus(c.id, 'cancelled')" class="btn-secondary btn-sm">{{ t('lab.act.cancel') }}</button>
                    <button v-if="isOwner" @click="remove(c.id)" class="btn-secondary btn-sm !text-rose-600">{{ t('lab.delete') }}</button>
                </div>
            </div>
        </div>

        <div v-if="(closed ?? []).length" class="card">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('lab.closed') }}</h2>
            <div class="divide-y divide-slate-100">
                <div v-for="c in closed" :key="c.id" class="flex items-center gap-3 py-2 text-sm">
                    <span class="min-w-0 flex-1 truncate">{{ c.patient?.full_name || '—' }} <span class="text-xs text-slate-400">{{ t('lab.wt.' + c.work_type) }} · {{ c.lab_name }}</span></span>
                    <Badge :tone="tone(c.status)">{{ t('lab.st.' + c.status) }}</Badge>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
