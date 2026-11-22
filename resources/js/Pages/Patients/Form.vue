<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

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
        <h1 class="text-2xl font-bold mb-4">{{ patient ? 'تعديل بيانات المريض' : 'مريض جديد' }}</h1>
        <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm p-6 grid md:grid-cols-2 gap-4 max-w-3xl">
            <div>
                <label class="block text-sm mb-1">الاسم الكامل *</label>
                <input v-model="form.full_name" class="w-full border rounded-lg px-3 py-2">
                <p v-if="form.errors.full_name" class="text-red-600 text-xs mt-1">{{ form.errors.full_name }}</p>
            </div>
            <div>
                <label class="block text-sm mb-1">الهاتف *</label>
                <input v-model="form.phone" dir="ltr" class="w-full border rounded-lg px-3 py-2">
                <p v-if="form.errors.phone" class="text-red-600 text-xs mt-1">{{ form.errors.phone }}</p>
            </div>
            <div>
                <label class="block text-sm mb-1">واتساب</label>
                <input v-model="form.whatsapp" dir="ltr" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm mb-1">تاريخ الميلاد</label>
                <input v-model="form.birth_date" type="date" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm mb-1">النوع</label>
                <select v-model="form.gender" class="w-full border rounded-lg px-3 py-2">
                    <option value="">—</option><option value="male">ذكر</option><option value="female">أنثى</option>
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">الرقم القومي</label>
                <input v-model="form.national_id" dir="ltr" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm mb-1">تنبيهات طبية (حساسيات، أمراض مزمنة) — افصل بفاصلة</label>
                <input v-model="form.medical_alerts" class="w-full border rounded-lg px-3 py-2">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm mb-1">ملاحظات</label>
                <textarea v-model="form.notes" rows="2" class="w-full border rounded-lg px-3 py-2"></textarea>
            </div>
            <div class="md:col-span-2">
                <button :disabled="form.processing" class="bg-teal-700 text-white rounded-lg px-6 py-2 hover:bg-teal-600 disabled:opacity-50">
                    حفظ
                </button>
            </div>
        </form>
    </AppLayout>
</template>
