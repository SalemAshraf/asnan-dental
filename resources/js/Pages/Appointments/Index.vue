<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { fmt, statusAr } from '@/money';
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
    // Lightweight: reuse the patients page endpoint via fetch for suggestions.
    const res = await fetch(`/patients?q=${encodeURIComponent(patientQ.value)}`, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) { patientResults.value = []; return; }
    const data = await res.json();
    patientResults.value = data.patients?.slice(0, 5) ?? [];
};
</script>

<template>
    <AppLayout>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h1 class="text-xl md:text-2xl font-bold">المواعيد</h1>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <button @click="shift(-1)" class="border rounded px-3 py-1">← اليوم السابق</button>
                <input type="date" :value="day" @change="goDay($event.target.value)" class="border rounded px-2 py-1">
                <button @click="shift(1)" class="border rounded px-3 py-1">اليوم التالي →</button>
                <button @click="showForm = !showForm" class="bg-teal-700 text-white rounded px-4 py-1">+ حجز</button>
            </div>
        </div>

        <form v-if="showForm" @submit.prevent="save" class="bg-white rounded-xl shadow-sm p-4 mb-4 grid md:grid-cols-3 gap-3 text-sm">
            <div>
                <label class="text-xs text-gray-500">المريض</label>
                <input v-model="patientQ" @input="searchPatients" placeholder="ابحث بالاسم أو الهاتف" class="w-full border rounded px-2 py-1">
                <div v-for="p in patientResults" :key="p.id" @click="form.patient_id = p.id; patientQ = p.full_name + ' #' + p.file_no; patientResults = []"
                    class="cursor-pointer hover:bg-teal-50 px-2 py-1 border-b">{{ p.full_name }} <span class="text-gray-400">#{{ p.file_no }}</span></div>
                <p v-if="form.errors.patient_id" class="text-red-600 text-xs">{{ form.errors.patient_id }}</p>
            </div>
            <div><label class="text-xs text-gray-500">الفرع</label>
                <select v-model="form.branch_id" class="w-full border rounded px-2 py-1">
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select></div>
            <div><label class="text-xs text-gray-500">الطبيب</label>
                <select v-model="form.dentist_id" class="w-full border rounded px-2 py-1">
                    <option v-for="d in dentists" :key="d.id" :value="d.id">{{ d.name }}</option>
                </select></div>
            <div><label class="text-xs text-gray-500">من</label>
                <input v-model="form.starts_at" type="datetime-local" class="w-full border rounded px-2 py-1" dir="ltr">
                <p v-if="form.errors.starts_at" class="text-red-600 text-xs">{{ form.errors.starts_at }}</p></div>
            <div><label class="text-xs text-gray-500">إلى</label>
                <input v-model="form.ends_at" type="datetime-local" class="w-full border rounded px-2 py-1" dir="ltr"></div>
            <div><label class="text-xs text-gray-500">الكرسي</label>
                <input v-model="form.chair" class="w-full border rounded px-2 py-1">
                <p v-if="form.errors.chair" class="text-red-600 text-xs">{{ form.errors.chair }}</p></div>
            <div class="md:col-span-3"><button class="bg-teal-700 text-white rounded px-4 py-2">حفظ الحجز</button></div>
        </form>

        <div class="bg-white rounded-xl shadow-sm">
            <p v-if="!appointments.length" class="p-6 text-center text-gray-400">لا توجد مواعيد في هذا اليوم.</p>
            <div v-for="a in appointments" :key="a.id" class="flex items-center justify-between border-b last:border-0 p-3 text-sm">
                <div>
                    <div class="font-bold" dir="ltr">{{ new Date(a.starts_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) }} – {{ new Date(a.ends_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) }}</div>
                    <div>{{ a.patient?.full_name }} · {{ a.dentist?.name }}<span v-if="a.chair" class="text-gray-400"> · {{ a.chair }}</span></div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs bg-teal-100 text-teal-800 rounded px-2 py-0.5">{{ statusAr[a.status] ?? a.status }}</span>
                    <select v-if="['scheduled','confirmed','checked_in'].includes(a.status)" :value="a.status"
                        @change="setStatus(a.id, $event.target.value)" class="border rounded px-1 py-0.5 text-xs">
                        <option v-for="s in ['scheduled','confirmed','checked_in','completed','cancelled','no_show']" :key="s" :value="s">{{ statusAr[s] }}</option>
                    </select>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
