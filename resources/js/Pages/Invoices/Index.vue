<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { fmt, statusAr } from '@/money';
import { Link } from '@inertiajs/vue3';

defineProps({ invoices: Object });
</script>

<template>
    <AppLayout>
        <h1 class="text-2xl font-bold mb-4">الفواتير</h1>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500"><tr>
                    <th class="p-3 text-right">رقم</th><th class="text-right">المريض</th>
                    <th class="text-right">الإجمالي</th><th class="text-right">المدفوع</th>
                    <th class="text-right">المتبقي</th><th class="text-right">الحالة</th><th></th>
                </tr></thead>
                <tbody>
                    <tr v-for="inv in invoices.data" :key="inv.id" class="border-t hover:bg-gray-50">
                        <td class="p-3" dir="ltr">{{ inv.number ?? 'مسودة' }}</td>
                        <td>{{ inv.patient?.full_name }}</td>
                        <td>{{ fmt(inv.total_minor, inv.currency) }}</td>
                        <td>{{ fmt(inv.paid_minor, inv.currency) }}</td>
                        <td class="font-bold">{{ fmt(inv.total_minor - inv.paid_minor, inv.currency) }}</td>
                        <td><span class="text-xs bg-gray-100 rounded px-2 py-0.5">{{ statusAr[inv.status] }}</span></td>
                        <td><Link :href="`/invoices/${inv.id}`" class="text-teal-700 hover:underline">فتح</Link></td>
                    </tr>
                </tbody>
            </table>
            <p v-if="!invoices.data.length" class="p-6 text-center text-gray-400">لا توجد فواتير بعد. أنشئها من خطة علاج مقبولة.</p>
        </div>
    </AppLayout>
</template>
