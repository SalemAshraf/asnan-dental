<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { fmt, statusAr } from '@/money';
import { Link } from '@inertiajs/vue3';

const props = defineProps({ stats: Object, todayAppointments: Array, clinic: Object });
const cards = (s) => [
    { label: 'مواعيد اليوم', value: s.today_appointments },
    { label: 'إجمالي المرضى', value: s.patients_total },
    { label: 'خطط علاج مفتوحة', value: s.open_plans },
    { label: 'مستحقات متبقية', value: fmt(s.outstanding_minor, props.clinic?.currency) },
    { label: 'محصل هذا الشهر', value: fmt(s.collected_month_minor, props.clinic?.currency) },
];
</script>

<template>
    <AppLayout>
        <h1 class="text-2xl font-bold mb-1">لوحة المتابعة</h1>
        <p class="text-gray-500 mb-6">{{ clinic?.name }}</p>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div v-for="c in cards(stats)" :key="c.label" class="bg-white rounded-xl shadow-sm p-4">
                <div class="text-sm text-gray-500">{{ c.label }}</div>
                <div class="text-2xl font-bold text-teal-800 mt-1">{{ c.value }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4">
            <h2 class="font-bold mb-3">مواعيد اليوم</h2>
            <p v-if="!todayAppointments.length" class="text-gray-400 text-sm">لا توجد مواعيد اليوم.</p>
            <table v-else class="w-full text-sm">
                <thead><tr class="text-gray-500 border-b">
                    <th class="py-2 text-right">الوقت</th><th class="text-right">المريض</th>
                    <th class="text-right">الطبيب</th><th class="text-right">الفرع</th><th class="text-right">الحالة</th>
                </tr></thead>
                <tbody>
                    <tr v-for="a in todayAppointments" :key="a.id" class="border-b last:border-0">
                        <td class="py-2" dir="ltr">{{ new Date(a.starts_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) }}</td>
                        <td>{{ a.patient?.full_name }}</td>
                        <td>{{ a.dentist?.name }}</td>
                        <td>{{ a.branch?.name }}</td>
                        <td><span class="bg-teal-100 text-teal-800 rounded px-2 py-0.5 text-xs">{{ statusAr[a.status] ?? a.status }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
