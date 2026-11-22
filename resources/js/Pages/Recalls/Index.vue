<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { t } from '@/i18n';

const props = defineProps({ pending: Array, recentDone: Array, reminders: Array, today: String });
const page = usePage();
const clinicName = computed(() => page.props.auth?.clinic?.name ?? 'العيادة');

const ccMap = { EG: '20', SA: '966', AE: '971', KW: '965', BH: '973', QA: '974', OM: '968' };
const intlPhone = (raw) => {
    let d = (raw ?? '').replace(/\D/g, '');
    if (!d) return null;
    if (d.startsWith('00')) d = d.slice(2);
    const cc = ccMap[page.props.auth?.clinic?.country] ?? '20';
    if (d.startsWith('0') && d.length <= 11) d = cc + d.slice(1);
    return d;
};
const waLink = (r) => {
    const phone = intlPhone(r.patient?.whatsapp || r.patient?.phone);
    if (!phone) return null;
    const msg = `مرحبًا ${r.patient?.full_name}، معاك ${clinicName.value}. بنذكّر حضرتك بموعد المتابعة يوم ${dstr(r.due_date)}. لو حابب تثبت موعدك أو تغيّره رد علينا.`;
    return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
};

const dstr = (d) => (d ? String(d).slice(0, 10) : '');
const form = useForm({ patient_id: '', service_id: '', due_date: '', reason: '', notes: '' });
const showForm = ref(false);
const patientQ = ref('');
const patientResults = ref([]);
const searchPatients = async () => {
    if (patientQ.value.length < 2) { patientResults.value = []; return; }
    const res = await fetch(`/patients?q=${encodeURIComponent(patientQ.value)}`, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) { patientResults.value = []; return; }
    const data = await res.json();
    patientResults.value = data.patients?.slice(0, 5) ?? [];
};
const save = () => form.post('/recalls', { onSuccess: () => { showForm.value = false; form.reset(); patientQ.value = ''; } });
const setStatus = (id, status) => router.post(`/recalls/${id}/transition`, { status }, { preserveScroll: true });

const setReminder = (id, status) => router.patch(`/reminders/${id}`, { status }, { preserveScroll: true });
const remWaLink = (m) => {
    const phone = intlPhone(m.patient?.whatsapp || m.patient?.phone);
    if (!phone) return null;
    const when = dstr(m.scheduled_for);
    const time = m.appointment?.starts_at ? String(m.appointment.starts_at).slice(11, 16) : null;
    const msg = m.kind === 'appointment' && time
        ? `مرحبًا ${m.patient?.full_name}، معاك ${clinicName.value}. بنذكّر حضرتك بموعدك يوم ${when} الساعة ${time}. لو محتاج تأكيد أو تعديل رد علينا.`
        : `مرحبًا ${m.patient?.full_name}، معاك ${clinicName.value}. بنذكّر حضرتك بموعد المتابعة يوم ${when}. لو حابب تثبت موعدك أو تغيّره رد علينا.`;
    return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
};


const overdue = computed(() => props.pending.filter((r) => r.overdue));
const upcoming = computed(() => props.pending.filter((r) => !r.overdue));
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">{{ t('rec.title') }}</h1>
                <p class="text-sm text-slate-500">{{ t('rec.subtitle') }}</p>
            </div>
            <button @click="showForm = !showForm" class="btn-primary"><Icon name="plus" :size="16" /> {{ t('rec.new') }}</button>
        </div>

        <form v-if="showForm" @submit.prevent="save" class="card mb-4 grid gap-3 text-sm md:grid-cols-4">
            <div class="relative md:col-span-2">
                <label class="label">{{ t('rec.form.patient') }}</label>
                <input v-model="patientQ" @input="searchPatients" :placeholder="t('rec.form.patientSearch')" class="input">
                <div v-if="patientResults.length" class="absolute z-10 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg">
                    <div v-for="p in patientResults" :key="p.id" @click="form.patient_id = p.id; patientQ = p.full_name + ' #' + p.file_no; patientResults = []"
                        class="cursor-pointer border-b border-slate-100 px-3 py-2 last:border-0 hover:bg-brand-50">{{ p.full_name }} <span class="text-slate-400">#{{ p.file_no }}</span></div>
                </div>
                <p v-if="form.errors.patient_id" class="mt-1 text-xs text-rose-600">{{ form.errors.patient_id }}</p>
            </div>
            <div><label class="label">{{ t('rec.form.dueDate') }}</label>
                <input v-model="form.due_date" type="date" required class="input">
                <p v-if="form.errors.due_date" class="mt-1 text-xs text-rose-600">{{ form.errors.due_date }}</p></div>
            <div><label class="label">{{ t('rec.form.reason') }}</label>
                <input v-model="form.reason" :placeholder="t('rec.form.reasonPh')" class="input"></div>
            <div class="md:col-span-4"><button class="btn-primary">{{ t('rec.form.save') }}</button></div>
        </form>

        <div class="card mb-4">
            <h2 class="font-bold text-ink-900">{{ t('rem.title') }} <span class="text-sm font-normal text-slate-400">({{ (reminders ?? []).length }})</span></h2>
            <p class="mb-3 text-xs text-slate-500">{{ t('rem.subtitle') }}</p>
            <EmptyState v-if="!(reminders ?? []).length" icon="whatsapp" :title="t('rem.empty')" />
            <div v-else class="divide-y divide-slate-100">
                <div v-for="m in reminders" :key="m.id" class="flex flex-wrap items-center gap-3 py-3 text-sm">
                    <span class="grid w-24 shrink-0 place-items-center rounded-lg py-1.5 text-xs font-bold tabular-nums" :class="m.overdue ? 'bg-rose-50 text-rose-700' : 'bg-brand-50 text-brand-800'" dir="ltr">
                        {{ dstr(m.scheduled_for) }}<span v-if="m.appointment?.starts_at" class="mt-0.5 block text-[10px] font-semibold">{{ String(m.appointment.starts_at).slice(11, 16) }}</span>
                    </span>
                    <span class="min-w-40 flex-1">
                        <span class="block truncate font-semibold text-ink-900">{{ m.patient?.full_name }} <span class="font-normal text-slate-400">#{{ m.patient?.file_no }}</span></span>
                        <span class="text-xs text-slate-500">{{ t('rem.kind.' + m.kind) }}<span v-if="m.overdue" class="ms-1 font-semibold text-rose-600">· {{ t('rem.overdue') }}</span></span>
                    </span>
                    <a v-if="remWaLink(m)" :href="remWaLink(m)" target="_blank" rel="noopener" class="btn-secondary btn-sm" :title="t('rec.whatsappTitle')"><Icon name="whatsapp" :size="15" /> {{ t('rem.whatsapp') }}</a>
                    <button @click="setReminder(m.id, 'sent')" class="btn-primary btn-sm">{{ t('rem.markSent') }}</button>
                    <button @click="setReminder(m.id, 'skipped')" class="btn-secondary btn-sm">{{ t('rem.skip') }}</button>
                </div>
            </div>
        </div>

        <div v-if="overdue.length" class="card mb-4 border-rose-200">
            <h2 class="mb-3 font-bold text-rose-700">{{ t('rec.overdue', { n: overdue.length }) }}</h2>
            <div class="divide-y divide-slate-100">
                <div v-for="r in overdue" :key="r.id" class="flex flex-wrap items-center gap-3 py-3 text-sm">
                    <span class="min-w-40 flex-1">
                        <span class="block truncate font-semibold text-ink-900">{{ r.patient?.full_name }} <span class="font-normal text-slate-400">#{{ r.patient?.file_no }}</span></span>
                        <span class="text-xs text-slate-500">{{ r.reason || r.service?.name_ar || t('rec.defaultReason') }} · {{ t('rec.wasDue') }} <span class="whitespace-nowrap tabular-nums" dir="ltr">{{ dstr(r.due_date) }}</span></span>
                    </span>
                    <a v-if="waLink(r)" :href="waLink(r)" target="_blank" rel="noopener" class="btn-secondary btn-sm" :title="t('rec.whatsappTitle')"><Icon name="whatsapp" :size="15" /> {{ t('rec.whatsapp') }}</a>
                    <button @click="setStatus(r.id, 'done')" class="btn-primary btn-sm">{{ t('rec.contacted') }}</button>
                    <button @click="setStatus(r.id, 'cancelled')" class="btn-secondary btn-sm">{{ t('rec.cancel') }}</button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('rec.upcoming', { n: upcoming.length }) }}</h2>
            <EmptyState v-if="!upcoming.length" icon="clock" :title="t('rec.emptyUpcoming')" :hint="t('rec.emptyUpcomingHint')" />
            <div v-else class="divide-y divide-slate-100">
                <div v-for="r in upcoming" :key="r.id" class="flex flex-wrap items-center gap-3 py-3 text-sm">
                    <span class="grid w-24 shrink-0 place-items-center rounded-lg bg-brand-50 py-1.5 text-xs font-bold tabular-nums text-brand-800" dir="ltr">{{ dstr(r.due_date) }}</span>
                    <span class="min-w-40 flex-1">
                        <span class="block truncate font-semibold text-ink-900">{{ r.patient?.full_name }}</span>
                        <span class="text-xs text-slate-500">{{ r.reason || r.service?.name_ar || t('rec.defaultReason') }}</span>
                    </span>
                    <a v-if="waLink(r)" :href="waLink(r)" target="_blank" rel="noopener" class="btn-secondary btn-sm" :title="t('rec.whatsappTitle')"><Icon name="whatsapp" :size="15" /> {{ t('rec.whatsapp') }}</a>
                    <button @click="setStatus(r.id, 'done')" class="btn-primary btn-sm">{{ t('rec.done') }}</button>
                    <button @click="setStatus(r.id, 'cancelled')" class="btn-secondary btn-sm">{{ t('rec.cancel') }}</button>
                </div>
            </div>
        </div>

        <div v-if="recentDone.length" class="card">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('rec.closed') }}</h2>
            <div class="divide-y divide-slate-100">
                <div v-for="r in recentDone" :key="r.id" class="flex items-center gap-3 py-2 text-sm">
                    <span class="min-w-0 flex-1 truncate">{{ r.patient?.full_name }} <span class="text-xs text-slate-400">{{ r.reason || r.service?.name_ar }}</span></span>
                    <Badge :tone="r.status === 'done' ? 'green' : 'slate'">{{ t('rec.st.' + r.status) }}</Badge>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
