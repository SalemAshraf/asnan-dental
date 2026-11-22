<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { fmt, statusAr } from '@/money';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({ invoice: Object, paymentMethods: Array });
const methodAr = { cash: 'نقدي', card: 'بطاقة', instapay: 'إنستاباي', bank_transfer: 'تحويل بنكي', wallet: 'محفظة إلكترونية' };

const pay = useForm({ amount: '', method: 'cash', reference: '', notes: '' });
const addPayment = () => pay.post(`/invoices/${props.invoice.id}/payments`, { preserveScroll: true, onSuccess: () => pay.reset() });
const issue = () => router.post(`/invoices/${props.invoice.id}/issue`);
const voidIt = () => confirm('إلغاء الفاتورة؟') && router.post(`/invoices/${props.invoice.id}/void`);
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold">فاتورة <span dir="ltr">{{ invoice.number ?? 'مسودة' }}</span></h1>
                <span class="text-sm bg-teal-100 text-teal-800 rounded px-3 py-1">{{ statusAr[invoice.status] }}</span>
            </div>
            <p class="text-gray-500 text-sm mb-4">{{ invoice.patient?.full_name }} · ملف #{{ invoice.patient?.file_no }}</p>

            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <table class="w-full text-sm">
                    <thead class="text-gray-500 border-b"><tr>
                        <th class="text-right py-2">البند</th><th class="text-right">سن</th><th class="text-right">كمية</th><th class="text-right">الإجمالي</th>
                    </tr></thead>
                    <tbody>
                        <tr v-for="i in invoice.items" :key="i.id" class="border-b last:border-0">
                            <td class="py-2">{{ i.description }}</td>
                            <td dir="ltr">{{ i.tooth_number ?? '—' }}</td>
                            <td>{{ i.qty }}</td>
                            <td>{{ fmt(i.total_minor, invoice.currency) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="text-sm mt-3 space-y-1 text-left" dir="ltr">
                    <div class="flex justify-between"><span>الإجمالي الفرعي</span><span>{{ fmt(invoice.subtotal_minor, invoice.currency) }}</span></div>
                    <div class="flex justify-between"><span>الخصم</span><span>-{{ fmt(invoice.discount_minor, invoice.currency) }}</span></div>
                    <div class="flex justify-between"><span>ضريبة القيمة المضافة {{ invoice.tax_rate_bp / 100 }}%</span><span>{{ fmt(invoice.tax_minor, invoice.currency) }}</span></div>
                    <div class="flex justify-between font-bold text-lg"><span>الإجمالي</span><span>{{ fmt(invoice.total_minor, invoice.currency) }}</span></div>
                    <div class="flex justify-between text-teal-700"><span>المدفوع</span><span>{{ fmt(invoice.paid_minor, invoice.currency) }}</span></div>
                    <div class="flex justify-between font-bold text-red-700"><span>المتبقي</span><span>{{ fmt(invoice.total_minor - invoice.paid_minor, invoice.currency) }}</span></div>
                </div>
            </div>

            <div class="flex gap-2 mb-4">
                <button v-if="invoice.status === 'draft'" @click="issue" class="bg-teal-700 text-white rounded-lg px-4 py-2 text-sm">إصدار الفاتورة</button>
                <button v-if="['draft','issued'].includes(invoice.status) && !invoice.payments.length" @click="voidIt"
                    class="border border-red-300 text-red-700 rounded-lg px-4 py-2 text-sm">إلغاء</button>
            </div>

            <div v-if="['issued','partially_paid'].includes(invoice.status)" class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <h3 class="font-bold text-sm mb-2">تسجيل دفعة (قسط)</h3>
                <form @submit.prevent="addPayment" class="flex flex-wrap gap-2 text-sm">
                    <input v-model="pay.amount" type="number" step="0.01" min="0.01" required placeholder="المبلغ"
                        class="border rounded-lg px-3 py-2 w-32" dir="ltr">
                    <select v-model="pay.method" class="border rounded-lg px-3 py-2">
                        <option v-for="m in paymentMethods" :key="m" :value="m">{{ methodAr[m] }}</option>
                    </select>
                    <input v-model="pay.reference" placeholder="مرجع (اختياري)" class="border rounded-lg px-3 py-2 flex-1">
                    <button class="bg-teal-700 text-white rounded-lg px-4">تسجيل</button>
                    <p v-if="pay.errors.amount_minor" class="text-red-600 text-xs w-full">{{ pay.errors.amount_minor }}</p>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4">
                <h3 class="font-bold text-sm mb-2">الدفعات</h3>
                <p v-if="!invoice.payments.length" class="text-gray-400 text-sm">لا توجد دفعات.</p>
                <div v-for="p in invoice.payments" :key="p.id" class="flex justify-between border-b last:border-0 py-2 text-sm">
                    <span>{{ fmt(p.amount_minor, invoice.currency) }} · {{ methodAr[p.method] }}<span v-if="p.reference" class="text-gray-400"> · {{ p.reference }}</span></span>
                    <span class="text-gray-500" dir="ltr">{{ new Date(p.paid_at).toLocaleString('ar-EG') }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
