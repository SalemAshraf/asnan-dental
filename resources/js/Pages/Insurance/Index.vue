<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { t } from '@/i18n';
import { fmt } from '@/money';

const props = defineProps({ companies: Array, openClaims: Array, closedClaims: Array });
const page = usePage();
const money = (v) => fmt(v, page.props.auth?.clinic?.currency ?? 'EGP');

const showClaimForm = ref(false);
const showCompanyForm = ref(false);
const patientQ = ref('');
const patientResults = ref([]);

const claimForm = useForm({
    patient_id: '', insurance_company_id: '', invoice_id: '', claim_no: '',
    amount: '', submitted_on: new Date().toISOString().slice(0, 10), notes: '',
});
const companyForm = useForm({ name: '', phone: '', email: '', notes: '' });

const searchPatients = async () => {
    if (patientQ.value.length < 2) { patientResults.value = []; return; }
    const res = await fetch(`/patients?q=${encodeURIComponent(patientQ.value)}`, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) { patientResults.value = []; return; }
    const data = await res.json();
    patientResults.value = data.patients?.slice(0, 5) ?? [];
};

const saveClaim = () => claimForm.post('/insurance/claims', {
    preserveScroll: true,
    onSuccess: () => { showClaimForm.value = false; claimForm.reset(); patientQ.value = ''; },
});
const saveCompany = () => companyForm.post('/insurance/companies', {
    preserveScroll: true,
    onSuccess: () => { showCompanyForm.value = false; companyForm.reset(); },
});
const setStatus = (id, status) =>
    router.patch(`/insurance/claims/${id}`, { status }, { preserveScroll: true });

const dstr = (d) => (d ? String(d).slice(0, 10) : '');
const tone = (s) => ({ submitted: 'amber', approved: 'blue', paid: 'green', rejected: 'rose' }[s] ?? 'slate');
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">{{ t('ins.title') }}</h1>
                <p class="text-sm text-slate-500">{{ t('ins.subtitle') }}</p>
            </div>
            <div class="flex gap-2">
                <button @click="showCompanyForm = !showCompanyForm" class="btn-secondary"><Icon name="plus" :size="16" /> {{ t('ins.newCompany') }}</button>
                <button @click="showClaimForm = !showClaimForm" class="btn-primary"><Icon name="plus" :size="16" /> {{ t('ins.newClaim') }}</button>
            </div>
        </div>

        <form v-if="showCompanyForm" @submit.prevent="saveCompany" class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('ins.newCompany') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('ins.company.name') }}</span>
                    <input v-model="companyForm.name" type="text" required maxlength="190" class="input" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('ins.company.phone') }}</span>
                    <input v-model="companyForm.phone" type="text" maxlength="40" class="input" dir="ltr" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('ins.company.email') }}</span>
                    <input v-model="companyForm.email" type="email" maxlength="190" class="input" dir="ltr" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('ins.company.notes') }}</span>
                    <input v-model="companyForm.notes" type="text" maxlength="2000" class="input" />
                </label>
            </div>
            <div class="mt-3 flex gap-2">
                <button type="submit" class="btn-primary" :disabled="companyForm.processing">{{ t('ins.company.save') }}</button>
                <button type="button" class="btn-secondary" @click="showCompanyForm = false">{{ t('lab.cancel') }}</button>
            </div>
        </form>

        <form v-if="showClaimForm" @submit.prevent="saveClaim" class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('ins.newClaim') }}</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div class="relative">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('lab.form.patient') }}</span>
                    <input v-model="patientQ" @input="searchPatients" :placeholder="t('lab.form.patientSearch')" class="input" />
                    <p v-if="claimForm.errors.patient_id" class="mt-1 text-xs text-rose-600">{{ claimForm.errors.patient_id }}</p>
                    <div v-if="patientResults.length" class="absolute z-10 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg">
                        <div v-for="p in patientResults" :key="p.id" @click="claimForm.patient_id = p.id; patientQ = p.full_name + ' #' + p.file_no; patientResults = []"
                            class="cursor-pointer border-b border-slate-100 px-3 py-2 text-sm last:border-0 hover:bg-brand-50">{{ p.full_name }} <span class="text-slate-400">#{{ p.file_no }}</span></div>
                    </div>
                </div>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('ins.claim.company') }}</span>
                    <select v-model="claimForm.insurance_company_id" required class="input">
                        <option value="" disabled>—</option>
                        <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <p v-if="claimForm.errors.insurance_company_id" class="mt-1 text-xs text-rose-600">{{ claimForm.errors.insurance_company_id }}</p>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('ins.claim.claimNo') }}</span>
                    <input v-model="claimForm.claim_no" type="text" maxlength="60" class="input" dir="ltr" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('ins.claim.amount') }}</span>
                    <input v-model="claimForm.amount" type="number" step="0.01" min="0" required class="input" dir="ltr" />
                    <p v-if="claimForm.errors.amount" class="mt-1 text-xs text-rose-600">{{ claimForm.errors.amount }}</p>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('ins.claim.submittedOn') }}</span>
                    <input v-model="claimForm.submitted_on" type="date" required class="input" dir="ltr" />
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs text-slate-500">{{ t('ins.company.notes') }}</span>
                    <input v-model="claimForm.notes" type="text" maxlength="2000" class="input" />
                </label>
            </div>
            <div class="mt-3 flex gap-2">
                <button type="submit" class="btn-primary" :disabled="claimForm.processing">{{ t('ins.claim.save') }}</button>
                <button type="button" class="btn-secondary" @click="showClaimForm = false">{{ t('lab.cancel') }}</button>
            </div>
        </form>

        <div class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('ins.open', { n: (openClaims ?? []).length }) }}</h2>
            <EmptyState v-if="!(openClaims ?? []).length" icon="invoices" :title="t('ins.empty')" />
            <div v-else class="divide-y divide-slate-100">
                <div v-for="c in openClaims" :key="c.id" class="flex flex-wrap items-center gap-3 py-3 text-sm">
                    <span class="min-w-52 flex-1">
                        <span class="block truncate font-semibold text-ink-900">
                            {{ c.patient?.full_name || '—' }} <span v-if="c.patient" class="font-normal text-slate-400">#{{ c.patient.file_no }}</span>
                            <span class="ms-1 font-normal text-slate-500">· {{ c.company?.name }}</span>
                        </span>
                        <span class="text-xs text-slate-500">
                            <span dir="ltr" v-if="c.claim_no">{{ c.claim_no }} · </span>
                            {{ money(c.amount) }} · {{ t('ins.claim.submittedOn') }} <span class="tabular-nums" dir="ltr">{{ dstr(c.submitted_on) }}</span>
                        </span>
                    </span>
                    <Badge :tone="tone(c.status)">{{ t('ins.st.' + c.status) }}</Badge>
                    <button v-if="c.status === 'submitted'" @click="setStatus(c.id, 'approved')" class="btn-primary btn-sm">{{ t('ins.act.approve') }}</button>
                    <button v-if="c.status === 'submitted'" @click="setStatus(c.id, 'rejected')" class="btn-secondary btn-sm">{{ t('ins.act.reject') }}</button>
                    <button v-if="c.status === 'approved'" @click="setStatus(c.id, 'paid')" class="btn-primary btn-sm">{{ t('ins.act.pay') }}</button>
                    <button v-if="c.status === 'approved'" @click="setStatus(c.id, 'rejected')" class="btn-secondary btn-sm">{{ t('ins.act.reject') }}</button>
                </div>
            </div>
        </div>

        <div v-if="(closedClaims ?? []).length" class="card">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('ins.closed') }}</h2>
            <div class="divide-y divide-slate-100">
                <div v-for="c in closedClaims" :key="c.id" class="flex items-center gap-3 py-2 text-sm">
                    <span class="min-w-0 flex-1 truncate">{{ c.patient?.full_name || '—' }} <span class="text-xs text-slate-400">{{ c.company?.name }} · {{ money(c.amount) }}</span></span>
                    <Badge :tone="tone(c.status)">{{ t('ins.st.' + c.status) }}</Badge>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
