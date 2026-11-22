<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ appointments: Array, day: String, branches: Array, dentists: Array });
const goDay = (d) => router.get('/appointments', { day: d });
const shift = (days) => {
    const d = new Date(props.day); d.setDate(d.getDate() + days);
    goDay(d.toISOString().slice(0, 10));
};

const form = useForm({ branch_id: props.branches[0]?.id ?? '', dentist_id: props.dentists[0]?.id ?? '', patient_id: '', chair: '', starts_at: '', ends_at: '', reason: '' });
const showForm = ref(false);
const save = () => form.post('/appointments', { onSuccess: () => { showForm.value = false; form.reset(); } });
const setStatus = (id, status) => router.post(`/appointments/${id}/transition`, { status }, { preserveScroll: true });
const patientQ = ref('');
const patientResults = ref([]);
const searchPatients = async () => {
    if (patientQ.value.length < 2) { patientResults.value = []; return; }
    const res = await fetch(`/patients?q=${encodeURIComponent(patientQ.value)}`, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) { patientResults.value = []; return; }
    const data = await res.json();
    patientResults.value = data.patients?.slice(0, 5) ?? [];
};
const time = (d) => new Date(d).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
const dayLabel = new Date(props.day + 'T00:00:00').toLocaleDateString('ar-EG', { weekday: 'long', day: 'numeric', month: 'long' });
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">المواعيد</h1>
                <p class="text-sm text-slate-500">{{ dayLabel }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <button @click="shift(-1)" class="btn-secondary btn-sm"><Icon name="chevron" :size="14" class="rtl:-scale-x-100" /> السابق</button>
                <input type="date" :value="day" @change="goDay($event.target.value)" class="input !w-auto !py-1.5" aria-label="اختر اليوم">
                <button @click="shift(1)" class="btn-secondary btn-sm">التالي <Icon name="chevron" :size="14" class="rotate-180 rtl:-scale-x-100" /></button>
                <button @click="showForm = !showForm" class="btn-primary btn-sm !py-2"><Icon name="plus" :size="15" /> حجز موعد</button>
            </div>
        </div>

        <form v-if="showForm" @submit.prevent="save" class="card mb-4 grid gap-3 text-sm md:grid-cols-3">
            <div class="relative">
                <label class="label">المريض</label>
                <input v-model="patientQ" @input="searchPatients" placeholder="ابحث بالاسم أو الهاتف" class="input">
                <div v-if="patientResults.length" class="absolute z-10 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg">
                    <div v-for="p in patientResults" :key="p.id" @click="form.patient_id = p.id; patientQ = p.full_name + ' #' + p.file_no; patientResults = []"
                        class="cursor-pointer border-b border-slate-100 px-3 py-2 last:border-0 hover:bg-brand-50">{{ p.full_name }} <span class="text-slate-400">#{{ p.file_no }}</span></div>
                </div>
                <p v-if="form.errors.patient_id" class="mt-1 text-xs text-rose-600">{{ form.errors.patient_id }}</p>
            </div>
            <div><label class="label">الفرع</label>
                <select v-model="form.branch_id" class="input">
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select></div>
            <div><label class="label">الطبيب</label>
                <select v-model="form.dentist_id" class="input">
                    <option v-for="d in dentists" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select></div>
            <div><label class="label">من</label>
                <input v-model="form.starts_at" type="datetime-local" class="input" dir="ltr">
                <p v-if="form.errors.starts_at" class="mt-1 text-xs text-rose-600">{{ form.errors.starts_at }}</p></div>
            <div><label class="label">إلى</label>
                <input v-model="form.ends_at" type="datetime-local" class="input" dir="ltr"></div>
            <div><label class="label">الكرسي</label>
                <input v-model="form.chair" class="input">
                <p v-if="form.errors.chair" class="mt-1 text-xs text-rose-600">{{ form.errors.chair }}</p></div>
            <div class="md:col-span-3"><button class="btn-primary">حفظ الحجز</button></div>
        </form>

        <div class="card">
            <EmptyState v-if="!appointments.length" icon="appointments" title="لا توجد مواعيد في هذا اليوم" hint="استخدم زر حجز موعد لإضافة أول موعد." />
            <div v-else class="divide-y divide-slate-100">
                <div v-for="a in appointments" :key="a.id" class="flex flex-wrap items-center gap-3 py-3 text-sm">
                    <span class="grid w-24 shrink-0 place-items-center rounded-lg bg-brand-50 py-1.5 text-xs font-bold tabular-nums text-brand-800" dir="ltr">{{ time(a.starts_at) }} – {{ time(a.ends_at) }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-ink-900">{{ a.patient?.full_name }}</span>
                        <span class="text-xs text-slate-500">{{ a.dentist?.name }}<span v-if="a.chair"> · {{ a.chair }}</span></span>
                    </span>
                    <StatusBadge :value="a.status" />
                    <select v-if="['scheduled','confirmed','checked_in'].includes(a.status)" :value="a.status"
                        @change="setStatus(a.id, $event.target.value)" class="input !w-auto !px-2 !py-1 text-xs">
                        <option v-for="s in ['scheduled','confirmed','checked_in','completed','cancelled','no_show']" :key="s" :value="s">{{ { scheduled: 'مجدول', confirmed: 'مؤكد', checked_in: 'وصل', completed: 'تم', cancelled: 'ملغي', no_show: 'لم يحضر' }[s] }}</option>
                    </select>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
