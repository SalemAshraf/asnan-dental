<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { fmt } from '@/money';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({ invoice: Object, paymentMethods: Array });
const methodAr = { cash: 'نقدي', card: 'بطاقة', instapay: 'إنستاباي', bank_transfer: 'تحويل بنكي', wallet: 'محفظة إلكترونية' };

const pay = useForm({ amount: '', method: 'cash', reference: '', notes: '' });
const addPayment = () => pay.post(`/invoices/${props.invoice.id}/payments`, { preserveScroll: true, onSuccess: () => pay.reset() });
const issue = () => router.post(`/invoices/${props.invoice.id}/issue`);
const voidIt = () => confirm('إلغاء الفاتورة؟') && router.post(`/invoices/${props.invoice.id}/void`);
const money = (v) => fmt(v, props.invoice.currency);
const paidPct = Math.min(100, props.invoice.total_minor ? Math.round(props.invoice.paid_minor / props.invoice.total_minor * 100) : 0);
const fillRemaining = () => { pay.amount = ((props.invoice.total_minor - props.invoice.paid_minor) / 100).toFixed(2); };
</script>

<template>
    <AppLayout>
        <div class="max-w-3xl">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h1 class="text-2xl font-bold text-ink-900">فاتورة <span dir="ltr" class="tabular-nums">{{ invoice.number ?? 'مسودة' }}</span></h1>
                    <p class="text-sm text-slate-500">{{ invoice.patient?.full_name }} · ملف #{{ invoice.patient?.file_no }}</p>
                </div>
                <StatusBadge :value="invoice.status" />
            </div>

            <div class="card mb-4">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200">
                        <tr><th class="th !px-0">البند</th><th class="th">سن</th><th class="th">كمية</th><th class="th !px-0 text-end">الإجمالي</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="i in invoice.items" :key="i.id">
                            <td class="td !px-0">{{ i.description }}</td>
                            <td class="td tabular-nums" dir="ltr">{{ i.tooth_number ?? '—' }}</td>
                            <td class="td">{{ i.qty }}</td>
                            <td class="td !px-0 text-end tabular-nums">{{ money(i.total_minor) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 space-y-1.5 border-t border-slate-100 pt-3 text-sm">
                    <div class="flex justify-between text-slate-600"><span>الإجمالي الفرعي</span><span class="tabular-nums">{{ money(invoice.subtotal_minor) }}</span></div>
                    <div class="flex justify-between text-slate-600"><span>الخصم</span><span class="tabular-nums">-{{ money(invoice.discount_minor) }}</span></div>
                    <div class="flex justify-between text-slate-600"><span>ضريبة ({{ invoice.tax_rate_bp / 100 }}%)</span><span class="tabular-nums">{{ money(invoice.tax_minor) }}</span></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold text-ink-900"><span>الإجمالي</span><span class="tabular-nums">{{ money(invoice.total_minor) }}</span></div>
                    <div class="flex justify-between text-emerald-700"><span>المدفوع</span><span class="tabular-nums">{{ money(invoice.paid_minor) }}</span></div>
                    <div class="flex justify-between font-bold text-rose-700"><span>المتبقي</span><span class="tabular-nums">{{ money(invoice.total_minor - invoice.paid_minor) }}</span></div>
                    <div class="pt-1" :title="'نسبة السداد ' + paidPct + '%'">
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full transition-all" :class="paidPct >= 100 ? 'bg-emerald-500' : 'bg-brand-600'" :style="{ width: paidPct + '%' }" />
                        </div>
                        <p class="mt-1 text-xs text-slate-400">سُدد {{ paidPct }}% من قيمة الفاتورة</p>
                    </div>
                </div>
            </div>

            <div class="mb-4 flex gap-2">
                <button v-if="invoice.status === 'draft'" @click="issue" class="btn-primary">إصدار الفاتورة</button>
                <button v-if="['draft','issued'].includes(invoice.status) && !invoice.payments.length" @click="voidIt" class="btn-danger">إلغاء</button>
            </div>

            <div v-if="['issued','partially_paid'].includes(invoice.status)" class="card mb-4">
                <h3 class="mb-3 font-bold text-ink-900">تسجيل دفعة (قسط)</h3>
                <form @submit.prevent="addPayment" class="flex flex-wrap gap-2 text-sm">
                    <input v-model="pay.amount" type="number" step="0.01" min="0.01" required placeholder="المبلغ" class="input !w-32" dir="ltr">
                    <select v-model="pay.method" class="input !w-40">
                        <option v-for="m in paymentMethods" :key="m" :value="m">{{ methodAr[m] }}</option>
                    </select>
                    <input v-model="pay.reference" placeholder="مرجع (اختياري)" class="input flex-1">
                    <button type="button" @click="fillRemaining" class="btn-secondary">سداد كامل المتبقي ({{ money(invoice.total_minor - invoice.paid_minor) }})</button>
                    <button class="btn-primary">تسجيل</button>
                    <p v-if="pay.errors.amount_minor" class="w-full text-xs text-rose-600">{{ pay.errors.amount_minor }}</p>
                </form>
            </div>

            <div class="card">
                <h3 class="mb-3 font-bold text-ink-900">الدفعات</h3>
                <EmptyState v-if="!invoice.payments.length" icon="cash" title="لا توجد دفعات مسجلة" />
                <div v-else class="divide-y divide-slate-100">
                    <div v-for="p in invoice.payments" :key="p.id" class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                        <span class="font-semibold text-ink-900 tabular-nums">{{ money(p.amount_minor) }} <span class="font-normal text-slate-500">· {{ methodAr[p.method] }}</span><span v-if="p.reference" class="text-slate-400"> · {{ p.reference }}</span></span>
                        <span class="text-xs text-slate-400 tabular-nums" dir="ltr">{{ new Date(p.paid_at).toLocaleString('ar-EG') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
