<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { t } from '@/i18n';

const props = defineProps({ patient: Object });
const form = useForm({
    full_name: props.patient?.full_name ?? '',
    phone: props.patient?.phone ?? '',
    whatsapp: props.patient?.whatsapp ?? '',
    birth_date: props.patient?.birth_date ?? '',
    gender: props.patient?.gender ?? '',
    national_id: props.patient?.national_id ?? '',
    medical_alerts: (props.patient?.medical_alerts ?? []).join('، '),
    notes: props.patient?.notes ?? '',
});
const submit = () => {
    form.transform((d) => ({
        ...d,
        medical_alerts: d.medical_alerts ? d.medical_alerts.split(/[،,]/).map((s) => s.trim()).filter(Boolean) : [],
    }))[props.patient ? 'put' : 'post'](props.patient ? `/patients/${props.patient.id}` : '/patients');
};
</script>

<template>
    <AppLayout>
        <h1 class="mb-5 text-2xl font-bold text-ink-900">{{ t(patient ? 'pat.form.edit' : 'pat.form.new') }}</h1>
        <form @submit.prevent="submit" class="card grid max-w-3xl gap-4 md:grid-cols-2">
            <div>
                <label class="label">{{ t('pat.form.fullName') }}</label>
                <input v-model="form.full_name" class="input">
                <p v-if="form.errors.full_name" class="mt-1 text-xs text-rose-600">{{ form.errors.full_name }}</p>
            </div>
            <div>
                <label class="label">{{ t('pat.form.phone') }}</label>
                <input v-model="form.phone" dir="ltr" class="input">
                <p v-if="form.errors.phone" class="mt-1 text-xs text-rose-600">{{ form.errors.phone }}</p>
            </div>
            <div>
                <label class="label">{{ t('pat.form.whatsapp') }}</label>
                <input v-model="form.whatsapp" dir="ltr" class="input">
            </div>
            <div>
                <label class="label">{{ t('pat.form.birth') }}</label>
                <input v-model="form.birth_date" type="date" class="input">
            </div>
            <div>
                <label class="label">{{ t('pat.form.gender') }}</label>
                <select v-model="form.gender" class="input">
                    <option value="">—</option><option value="male">{{ t('pat.form.male') }}</option><option value="female">{{ t('pat.form.female') }}</option>
                </select>
            </div>
            <div>
                <label class="label">{{ t('pat.form.nationalId') }}</label>
                <input v-model="form.national_id" dir="ltr" class="input">
            </div>
            <div class="md:col-span-2">
                <label class="label">{{ t('pat.form.alerts') }}</label>
                <input v-model="form.medical_alerts" class="input">
            </div>
            <div class="md:col-span-2">
                <label class="label">{{ t('pat.form.notes') }}</label>
                <textarea v-model="form.notes" rows="2" class="input"></textarea>
            </div>
            <div class="md:col-span-2">
                <button :disabled="form.processing" class="btn-primary">{{ t('pat.form.save') }}</button>
            </div>
        </form>
    </AppLayout>
</template>
