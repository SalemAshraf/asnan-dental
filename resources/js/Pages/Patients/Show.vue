<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Odontogram from '@/Components/Odontogram.vue';
import { fmt, statusAr } from '@/money';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ patient: Object, appointments: Array, chart: Object, can: Object });
const tab = ref('info');

const consent = useForm({ type: 'data_processing', text_snapshot: '', method: 'written', given_at: new Date().toISOString().slice(0, 16) });
const saveConsent = () => consent.post(`/patients/${props.patient.id}/consents`, { preserveScroll: true, onSuccess: () => consent.reset('text_snapshot') });

const plan = useForm({ title: '', notes: '' });
const savePlan = () => plan.post(`/patients/${props.patient.id}/plans`, { preserveScroll: true, onSuccess: () => plan.reset() });

const item = useForm({ plan_id: null, description: '', price: '', discount: 0, qty: 1, tooth_number: '' });
const addItem = (planId) => {
    router.post(`/plans/${planId}/items`, {
        description: item.description, price: item.price, discount: item.discount,
        qty: item.qty, tooth_number: item.tooth_number || null,
    }, { preserveScroll: true, onSuccess: () => item.reset() });
};
const transition = (planId, status) => router.post(`/plans/${planId}/transition`, { status }, { preserveScroll: true });
const itemStatus = (itemId, status) => router.patch(`/plan-items/${itemId}`, { status }, { preserveScroll: true });
const invoiceFromPlan = (plan) => {
    const ids = plan.items.filter((i) => i.status === 'accepted').map((i) => i.id);
    if (!ids.length) return alert('لا توجد بنود مقبولة.');
    const branchId = props.appointments[0]?.branch_id ?? props.patient.branch_id;
    router.post(`/plans/${plan.id}/invoice`, { branch_id: branchId, item_ids: ids });
};
const nextTransitions = { draft: ['proposed', 'cancelled'], proposed: ['accepted', 'cancelled'], accepted: ['in_progress', 'cancelled'], in_progress: ['completed', 'cancelled'] };
</script>

<template>
    <AppLayout>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold">{{ patient.full_name }} <span class="text-gray-400 text-sm">ملف #{{ patient.file_no }}</span></h1>
                <p class="text-gray-500 text-sm" dir="ltr">{{ patient.phone }}<span v-if="patient.whatsapp"> · واتساب {{ patient.whatsapp }}</span></p>
            </div>
            <Link :href="`/patients/${patient.id}/edit`" class="border rounded-lg px-4 py-2 text-sm hover:bg-gray-50">تعديل</Link>
        </div>

        <div v-if="patient.medical_alerts?.length" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 mb-4 text-sm">
            ⚠️ تنبيهات طبية: {{ patient.medical_alerts.join('، ') }}
        </div>

        <div class="flex gap-1 border-b mb-4 text-sm">
            <button v-for="t in [['info','الملف'],['chart','خريطة الأسنان'],['plans','خطط العلاج'],['billing','الفواتير'],['consents','الموافقات']]" :key="t[0]"
                @click="tab = t[0]" class="px-4 py-2 rounded-t"
                :class="tab === t[0] ? 'bg-white border border-b-0 font-bold text-teal-800' : 'text-gray-500'">{{ t[1] }}</button>
        </div>

        <div v-if="tab === 'info'" class="bg-white rounded-xl shadow-sm p-6 text-sm space-y-2 max-w-xl">
            <p><span class="text-gray-500">تاريخ الميلاد:</span> {{ patient.birth_date ?? '—' }}</p>
            <p><span class="text-gray-500">النوع:</span> {{ patient.gender === 'male' ? 'ذكر' : patient.gender === 'female' ? 'أنثى' : '—' }}</p>
            <p><span class="text-gray-500">ملاحظات:</span> {{ patient.notes ?? '—' }}</p>
            <h3 class="font-bold pt-3">آخر المواعيد</h3>
            <p v-if="!appointments.length" class="text-gray-400">لا توجد مواعيد.</p>
            <ul v-else class="space-y-1">
                <li v-for="a in appointments" :key="a.id" class="flex justify-between border-b py-1">
                    <span dir="ltr">{{ new Date(a.starts_at).toLocaleString('ar-EG') }}</span>
                    <span>{{ a.dentist?.name }}</span>
                    <span class="text-xs bg-gray-100 rounded px-2">{{ statusAr[a.status] ?? a.status }}</span>
                </li>
            </ul>
        </div>

        <div v-if="tab === 'chart'" class="bg-white rounded-xl shadow-sm p-6">
            <Odontogram :patient-id="patient.id" :chart="chart" :readonly="!can.clinical" />
        </div>

        <div v-if="tab === 'plans'">
            <form v-if="can.clinical" @submit.prevent="savePlan" class="bg-white rounded-xl shadow-sm p-4 mb-4 flex gap-2 items-end">
                <div class="flex-1"><label class="text-xs text-gray-500">خطة جديدة</label>
                    <input v-model="plan.title" placeholder="مثال: خطة علاج الفك العلوي" class="w-full border rounded-lg px-3 py-2"></div>
                <button class="bg-teal-700 text-white rounded-lg px-4 py-2 text-sm">إنشاء</button>
            </form>
            <div v-for="p in patient.treatment_plans" :key="p.id" class="bg-white rounded-xl shadow-sm p-4 mb-3">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-bold">{{ p.title }} <span class="text-xs bg-teal-100 text-teal-800 rounded px-2 py-0.5">{{ statusAr[p.status] ?? p.status }}</span></h3>
                    <div class="text-sm font-bold">{{ fmt(p.total_minor) }}</div>
                </div>
                <table class="w-full text-sm mb-2">
                    <tbody>
                        <tr v-for="i in p.items" :key="i.id" class="border-t">
                            <td class="py-1">{{ i.description }} <span v-if="i.tooth_number" class="text-gray-400">(سن {{ i.tooth_number }})</span></td>
                            <td dir="ltr" class="text-left">{{ fmt((i.price_minor - i.discount_minor) * i.qty) }}</td>
                            <td class="w-40">
                                <select v-if="can.clinical" :value="i.status" @change="itemStatus(i.id, $event.target.value)" class="border rounded px-1 py-0.5 text-xs">
                                    <option v-for="s in ['proposed','accepted','in_progress','completed','rejected']" :key="s" :value="s">{{ statusAr[s] }}</option>
                                </select>
                                <span v-else class="text-xs">{{ statusAr[i.status] }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <form v-if="can.clinical && ['draft','proposed'].includes(p.status)" @submit.prevent="addItem(p.id)" class="flex gap-2 text-sm mb-2">
                    <input v-model="item.description" placeholder="وصف البند" class="border rounded px-2 py-1 flex-1" required>
                    <input v-model="item.tooth_number" placeholder="سن" class="border rounded px-2 py-1 w-16" dir="ltr">
                    <input v-model="item.price" placeholder="السعر" type="number" step="0.01" class="border rounded px-2 py-1 w-24" dir="ltr" required>
                    <button class="bg-gray-800 text-white rounded px-3 py-1">+ بند</button>
                </form>
                <div class="flex gap-2 text-xs" v-if="can.clinical">
                    <button v-for="s in nextTransitions[p.status] ?? []" :key="s" @click="transition(p.id, s)"
                        class="border rounded px-3 py-1 hover:bg-gray-50">→ {{ statusAr[s] }}</button>
                    <button v-if="can.billing && p.items.some(i => i.status === 'accepted')" @click="invoiceFromPlan(p)"
                        class="bg-teal-700 text-white rounded px-3 py-1">إنشاء فاتورة من البنود المقبولة</button>
                </div>
            </div>
        </div>

        <div v-if="tab === 'billing'" class="bg-white rounded-xl shadow-sm p-4">
            <p v-if="!patient.invoices.length" class="text-gray-400 text-sm">لا توجد فواتير.</p>
            <table v-else class="w-full text-sm">
                <thead class="text-gray-500"><tr><th class="text-right py-2">رقم</th><th class="text-right">الإجمالي</th><th class="text-right">المدفوع</th><th class="text-right">الحالة</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="inv in patient.invoices" :key="inv.id" class="border-t">
                        <td class="py-2" dir="ltr">{{ inv.number ?? 'مسودة' }}</td>
                        <td>{{ fmt(inv.total_minor) }}</td><td>{{ fmt(inv.paid_minor) }}</td>
                        <td><span class="text-xs bg-gray-100 rounded px-2 py-0.5">{{ statusAr[inv.status] }}</span></td>
                        <td><Link :href="`/invoices/${inv.id}`" class="text-teal-700 hover:underline">فتح</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="tab === 'consents'" class="max-w-xl">
            <div class="bg-white rounded-xl shadow-sm p-4 mb-3">
                <h3 class="font-bold mb-2 text-sm">تسجيل موافقة</h3>
                <form @submit.prevent="saveConsent" class="space-y-2 text-sm">
                    <select v-model="consent.type" class="w-full border rounded-lg px-3 py-2">
                        <option value="data_processing">موافقة معالجة البيانات الصحية</option>
                        <option value="treatment">موافقة علاجية</option>
                        <option value="marketing">موافقة تواصل تسويقي</option>
                    </select>
                    <textarea v-model="consent.text_snapshot" rows="2" required placeholder="نص الموافقة كما وافق عليه المريض"
                        class="w-full border rounded-lg px-3 py-2"></textarea>
                    <div class="flex gap-2">
                        <select v-model="consent.method" class="border rounded-lg px-3 py-2">
                            <option value="written">مكتوبة</option><option value="verbal_logged">شفهية (موثقة)</option><option value="digital">رقمية</option>
                        </select>
                        <input v-model="consent.given_at" type="datetime-local" class="border rounded-lg px-3 py-2">
                        <button class="bg-teal-700 text-white rounded-lg px-4">تسجيل</button>
                    </div>
                </form>
            </div>
            <p v-if="!patient.consents.length" class="text-gray-400 text-sm">لا توجد موافقات مسجلة.</p>
            <div v-for="c in patient.consents" :key="c.id" class="bg-white rounded-xl shadow-sm p-3 mb-2 text-sm">
                <div class="text-xs text-gray-500 mb-1">{{ { data_processing: 'معالجة بيانات', treatment: 'علاجية', marketing: 'تسويقية' }[c.type] }} · {{ c.given_at }}</div>
                <p>{{ c.text_snapshot }}</p>
            </div>
        </div>
    </AppLayout>
</template>
