<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Odontogram from '@/Components/Odontogram.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { fmt } from '@/money';
import { statusLabel, intlLocale, t } from '@/i18n';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ patient: Object, appointments: Array, chart: Object, can: Object });
const tab = ref('info');
const tabs = [
    ['info', 'pat.show.tab.info'], ['chart', 'pat.show.tab.chart'], ['plans', 'pat.show.tab.plans'],
    ...(props.can.clinical ? [['images', 'pat.show.tab.images']] : []),
    ['billing', 'pat.show.tab.billing'], ['consents', 'pat.show.tab.consents'],
];

const consent = useForm({ type: 'data_processing', text_snapshot: '', method: 'written', given_at: new Date().toISOString().slice(0, 16) });
const saveConsent = () => consent.post(`/patients/${props.patient.id}/consents`, { preserveScroll: true, onSuccess: () => consent.reset('text_snapshot') });

const img = useForm({ image: null, kind: 'xray', title: '', taken_on: '', notes: '' });
const pickImage = (e) => { img.image = e.target.files?.[0] ?? null; };
const saveImage = () => {
    if (!img.image) return;
    img.post(`/patients/${props.patient.id}/images`, {
        preserveScroll: true, forceFormData: true,
        onSuccess: () => { img.reset(); img.image = null; if (imgInput.value) imgInput.value.value = ''; },
    });
};
const imgInput = ref(null);
const removeImage = (id) => {
    if (!window.confirm(t('pat.img.confirmDelete'))) return;
    router.delete(`/patient-images/${id}`, { preserveScroll: true });
};
const imgUrl = (id) => `/patient-images/${id}/file`;
const dstr = (d) => (d ? String(d).slice(0, 10) : '');

const plan = useForm({ title: '', notes: '' });
const savePlan = () => plan.post(`/patients/${props.patient.id}/plans`, { preserveScroll: true, onSuccess: () => plan.reset() });

const item = useForm({ plan_id: null, description: '', price: '', discount: 0, qty: 1, tooth_number: '' });
const addItem = (planId) => {
    router.post(`/plans/${planId}/items`, {
        description: item.description, price: item.price, discount: item.discount,
        qty: item.qty, tooth_number: item.tooth_number || null,
    }, { preserveScroll: true, onSuccess: () => item.reset() });
};
const transition = (planId, status) => router.post(`/plans/${planId}/transition`, { status }, { preserveScroll: true });
const itemStatus = (itemId, status) => router.patch(`/plan-items/${itemId}`, { status }, { preserveScroll: true });
const invoiceFromPlan = (plan) => {
    const ids = plan.items.filter((i) => i.status === 'accepted').map((i) => i.id);
    if (!ids.length) return alert(t('pat.show.noAccepted'));
    const branchId = props.appointments[0]?.branch_id ?? props.patient.branch_id;
    router.post(`/plans/${plan.id}/invoice`, { branch_id: branchId, item_ids: ids });
};
const nextTransitions = { draft: ['proposed', 'cancelled'], proposed: ['accepted', 'cancelled'], accepted: ['in_progress', 'cancelled'], in_progress: ['completed', 'cancelled'] };
</script>

<template>
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <h1 class="break-words text-xl font-bold text-ink-900 md:text-2xl">{{ patient.full_name }} <span class="text-sm font-normal text-slate-400">{{ t('pat.show.fileNo') }}{{ patient.file_no }}</span></h1>
                <p class="text-sm text-slate-500 tabular-nums" dir="ltr">{{ patient.phone }}<span v-if="patient.whatsapp"> · {{ t('pat.show.whatsapp') }} {{ patient.whatsapp }}</span></p>
            </div>
            <Link :href="`/patients/${patient.id}/edit`" class="btn-secondary">{{ t('pat.show.edit') }}</Link>
        </div>

        <div v-if="patient.medical_alerts?.length" class="mb-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-medium text-rose-800">
            <Icon name="alert" :size="18" class="mt-0.5 shrink-0" />
            <span>{{ t('pat.show.alerts') }} {{ patient.medical_alerts.join('، ') }}</span>
        </div>

        <div class="mb-4 flex gap-1 overflow-x-auto whitespace-nowrap border-b border-slate-200 text-sm">
            <button v-for="tb in tabs" :key="tb[0]" @click="tab = tb[0]"
                class="rounded-t-lg px-4 py-2 transition"
                :class="tab === tb[0] ? 'bg-white border border-b-0 border-slate-200 font-bold text-brand-800' : 'text-slate-500 hover:text-ink-900'">{{ t(tb[1]) }}</button>
        </div>

        <div v-if="tab === 'info'" class="grid gap-4 lg:grid-cols-2">
            <div class="card text-sm">
                <h3 class="mb-3 font-bold text-ink-900">{{ t('pat.show.data') }}</h3>
                <dl class="space-y-2">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ t('pat.show.birthDate') }}</dt><dd>{{ patient.birth_date ? patient.birth_date.slice(0, 10) : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ t('pat.show.gender') }}</dt><dd>{{ patient.gender === 'male' ? t('pat.show.male') : patient.gender === 'female' ? t('pat.show.female') : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">{{ t('pat.show.notes') }}</dt><dd class="text-end">{{ patient.notes ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="card text-sm">
                <h3 class="mb-3 font-bold text-ink-900">{{ t('pat.show.recentAppts') }}</h3>
                <EmptyState v-if="!appointments.length" icon="appointments" :title="t('pat.show.noAppts')" />
                <ul v-else class="divide-y divide-slate-100">
                    <li v-for="a in appointments" :key="a.id" class="flex items-center justify-between gap-2 py-2">
                        <span class="tabular-nums text-slate-600" dir="ltr">{{ new Date(a.starts_at).toLocaleString(intlLocale()) }}</span>
                        <span class="text-slate-500">{{ a.dentist?.name }}</span>
                        <StatusBadge :value="a.status" />
                    </li>
                </ul>
            </div>
        </div>

        <div v-if="tab === 'chart'" class="card">
            <Odontogram :patient-id="patient.id" :chart="chart" :readonly="!can.clinical" />
        </div>

        <div v-if="tab === 'plans'">
            <form v-if="can.clinical" @submit.prevent="savePlan" class="card mb-4 flex flex-wrap items-end gap-2">
                <div class="min-w-52 flex-1"><label class="label">{{ t('pat.show.newPlan') }}</label>
                    <input v-model="plan.title" :placeholder="t('pat.show.newPlanPh')" class="input"></div>
                <button class="btn-primary">{{ t('pat.show.create') }}</button>
            </form>
            <EmptyState v-if="!patient.treatment_plans.length" icon="services" :title="t('pat.show.noPlans')" :hint="t('pat.show.noPlansHint')" />
            <div v-for="p in patient.treatment_plans" :key="p.id" class="card mb-3">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-bold text-ink-900">{{ p.title }} <StatusBadge :value="p.status" /></h3>
                    <div class="text-sm font-bold tabular-nums text-ink-900">{{ fmt(p.total_minor) }}</div>
                </div>
                <table class="mb-2 w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="i in p.items" :key="i.id">
                            <td class="py-2">{{ i.description }} <span v-if="i.tooth_number" class="text-slate-400">({{ t('pat.show.tooth') }} <span dir="ltr">{{ i.tooth_number }}</span>)</span></td>
                            <td class="text-end tabular-nums">{{ fmt((i.price_minor - i.discount_minor) * i.qty) }}</td>
                            <td class="w-44 text-end">
                                <select v-if="can.clinical" :value="i.status" @change="itemStatus(i.id, $event.target.value)" class="input !inline-block !w-auto !px-2 !py-1 text-xs">
                                    <option v-for="s in ['proposed','accepted','in_progress','completed','rejected']" :key="s" :value="s">{{ statusLabel(s) }}</option>
                                </select>
                                <StatusBadge v-else :value="i.status" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <form v-if="can.clinical && ['draft','proposed'].includes(p.status)" @submit.prevent="addItem(p.id)" class="mb-2 flex flex-wrap gap-2 text-sm">
                    <input v-model="item.description" :placeholder="t('pat.show.itemDesc')" class="input flex-1" required>
                    <input v-model="item.tooth_number" :placeholder="t('pat.show.tooth')" class="input !w-16" dir="ltr">
                    <input v-model="item.price" :placeholder="t('pat.show.price')" type="number" step="0.01" class="input !w-28" dir="ltr" required>
                    <button class="btn-secondary btn-sm !py-2">{{ t('pat.show.addItem') }}</button>
                </form>
                <div class="flex flex-wrap gap-2 text-xs" v-if="can.clinical">
                    <button v-for="s in nextTransitions[p.status] ?? []" :key="s" @click="transition(p.id, s)"
                        class="btn-secondary btn-sm">→ {{ statusLabel(s) }}</button>
                    <button v-if="can.billing && p.items.some(i => i.status === 'accepted')" @click="invoiceFromPlan(p)"
                        class="btn-primary btn-sm">{{ t('pat.show.invoiceFromPlan') }}</button>
                </div>
            </div>
        </div>

        <div v-if="tab === 'billing'" class="card !p-0 overflow-hidden">
            <EmptyState v-if="!patient.invoices.length" icon="invoices" :title="t('pat.show.noInvoices')" />
            <table v-else class="stack w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr><th class="th">{{ t('pat.show.col.number') }}</th><th class="th">{{ t('pat.show.col.total') }}</th><th class="th">{{ t('pat.show.col.paid') }}</th><th class="th">{{ t('pat.show.col.status') }}</th><th class="th"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="inv in patient.invoices" :key="inv.id">
                        <td class="td tabular-nums text-slate-500" dir="ltr" data-label="رقم">{{ inv.number ?? t('pat.show.draft') }}</td>
                        <td class="td tabular-nums" data-label="الإجمالي">{{ fmt(inv.total_minor) }}</td>
                        <td class="td tabular-nums" data-label="المدفوع">{{ fmt(inv.paid_minor) }}</td>
                        <td class="td" data-label="الحالة"><StatusBadge :value="inv.status" /></td>
                        <td class="td" data-label=""><Link :href="`/invoices/${inv.id}`" class="font-semibold text-brand-700 hover:underline">{{ t('pat.show.open') }}</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="tab === 'images' && can.clinical">
            <form @submit.prevent="saveImage" class="card mb-4">
                <h2 class="mb-3 font-bold text-ink-900">{{ t('pat.img.add') }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('pat.img.kind') }}</span>
                        <select v-model="img.kind" class="input">
                            <option value="xray">{{ t('pat.img.kind.xray') }}</option>
                            <option value="photo">{{ t('pat.img.kind.photo') }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('pat.img.date') }}</span>
                        <input v-model="img.taken_on" type="date" class="input" dir="ltr" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('pat.img.title') }}</span>
                        <input v-model="img.title" type="text" class="input" maxlength="190" />
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs text-slate-500">{{ t('pat.img.file') }}</span>
                        <input ref="imgInput" type="file" accept="image/jpeg,image/png,image/webp" required @change="pickImage" class="input !px-0 !py-0 text-xs text-slate-600 file:me-3 file:rounded-s-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2.5 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100 ps-3" />
                    </label>
                </div>
                <label class="mt-3 block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('pat.img.notes') }}</span>
                    <textarea v-model="img.notes" rows="2" class="input" maxlength="2000"></textarea>
                </label>
                <div v-if="img.errors.image" class="mt-2 text-sm text-rose-600">{{ img.errors.image }}</div>
                <button type="submit" class="btn-primary mt-3" :disabled="img.processing || !img.image">{{ t('pat.img.upload') }}</button>
            </form>
            <EmptyState v-if="!(patient.images ?? []).length" icon="photo" :title="t('pat.img.empty')" />
            <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="im in patient.images" :key="im.id" class="card !p-3">
                    <a :href="imgUrl(im.id)" target="_blank" rel="noopener" class="block overflow-hidden rounded-lg bg-slate-100">
                        <img :src="imgUrl(im.id)" :alt="im.title || im.original_name" class="h-40 w-full object-cover" loading="lazy" />
                    </a>
                    <div class="mt-2 flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-ink-900">{{ im.title || im.original_name }}</p>
                            <p class="text-xs text-slate-500">
                                {{ t('pat.img.kind.' + im.kind) }}<span v-if="im.taken_on"> · <span class="tabular-nums" dir="ltr">{{ dstr(im.taken_on) }}</span></span>
                                <span v-if="im.uploader"> · {{ t('pat.img.by') }} {{ im.uploader.name }}</span>
                            </p>
                        </div>
                        <button type="button" class="btn-secondary btn-sm shrink-0 !text-rose-600" @click="removeImage(im.id)">{{ t('pat.img.delete') }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="tab === 'consents'" class="max-w-xl">
            <div class="card mb-3">
                <h3 class="mb-3 font-bold text-ink-900">{{ t('pat.show.recordConsent') }}</h3>
                <form @submit.prevent="saveConsent" class="space-y-2 text-sm">
                    <select v-model="consent.type" class="input">
                        <option value="data_processing">{{ t('pat.show.consent.data') }}</option>
                        <option value="treatment">{{ t('pat.show.consent.treatment') }}</option>
                        <option value="marketing">{{ t('pat.show.consent.marketing') }}</option>
                    </select>
                    <textarea v-model="consent.text_snapshot" rows="2" required :placeholder="t('pat.show.consentTextPh')" class="input"></textarea>
                    <div class="flex flex-wrap gap-2">
                        <select v-model="consent.method" class="input !w-40">
                            <option value="written">{{ t('pat.show.method.written') }}</option><option value="verbal_logged">{{ t('pat.show.method.verbal') }}</option><option value="digital">{{ t('pat.show.method.digital') }}</option>
                        </select>
                        <input v-model="consent.given_at" type="datetime-local" class="input !w-52">
                        <button class="btn-primary">{{ t('pat.show.record') }}</button>
                    </div>
                </form>
            </div>
            <EmptyState v-if="!patient.consents.length" icon="audit" :title="t('pat.show.noConsents')" />
            <div v-for="c in patient.consents" :key="c.id" class="card mb-2 !p-3 text-sm">
                <div class="mb-1 text-xs text-slate-500">{{ t('pat.show.typeShort.' + c.type) }} · <span dir="ltr">{{ c.given_at }}</span></div>
                <p>{{ c.text_snapshot }}</p>
            </div>
        </div>
    </AppLayout>
</template>
