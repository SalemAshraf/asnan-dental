<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Odontogram from '@/Components/Odontogram.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { fmt, statusAr } from '@/money';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ patient: Object, appointments: Array, chart: Object, can: Object });
const tab = ref('info');
const tabs = [['info', 'الملف'], ['chart', 'خريطة الأسنان'], ['plans', 'خطط العلاج'], ['billing', 'الفواتير'], ['consents', 'الموافقات']];

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
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div class="min-w-0">
                <h1 class="break-words text-xl font-bold text-ink-900 md:text-2xl">{{ patient.full_name }} <span class="text-sm font-normal text-slate-400">ملف #{{ patient.file_no }}</span></h1>
                <p class="text-sm text-slate-500 tabular-nums" dir="ltr">{{ patient.phone }}<span v-if="patient.whatsapp"> · واتساب {{ patient.whatsapp }}</span></p>
            </div>
            <Link :href="`/patients/${patient.id}/edit`" class="btn-secondary">تعديل البيانات</Link>
        </div>

        <div v-if="patient.medical_alerts?.length" class="mb-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-medium text-rose-800">
            <Icon name="alert" :size="18" class="mt-0.5 shrink-0" />
            <span>تنبيهات طبية هامة: {{ patient.medical_alerts.join('، ') }}</span>
        </div>

        <div class="mb-4 flex gap-1 overflow-x-auto whitespace-nowrap border-b border-slate-200 text-sm">
            <button v-for="t in tabs" :key="t[0]" @click="tab = t[0]"
                class="rounded-t-lg px-4 py-2 transition"
                :class="tab === t[0] ? 'bg-white border border-b-0 border-slate-200 font-bold text-brand-800' : 'text-slate-500 hover:text-ink-900'">{{ t[1] }}</button>
        </div>

        <div v-if="tab === 'info'" class="grid gap-4 lg:grid-cols-2">
            <div class="card text-sm">
                <h3 class="mb-3 font-bold text-ink-900">البيانات</h3>
                <dl class="space-y-2">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">تاريخ الميلاد</dt><dd>{{ patient.birth_date ? patient.birth_date.slice(0, 10) : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">النوع</dt><dd>{{ patient.gender === 'male' ? 'ذكر' : patient.gender === 'female' ? 'أنثى' : '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">ملاحظات</dt><dd class="text-end">{{ patient.notes ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="card text-sm">
                <h3 class="mb-3 font-bold text-ink-900">آخر المواعيد</h3>
                <EmptyState v-if="!appointments.length" icon="appointments" title="لا توجد مواعيد" />
                <ul v-else class="divide-y divide-slate-100">
                    <li v-for="a in appointments" :key="a.id" class="flex items-center justify-between gap-2 py-2">
                        <span class="tabular-nums text-slate-600" dir="ltr">{{ new Date(a.starts_at).toLocaleString('ar-EG') }}</span>
                        <span class="text-slate-500">{{ a.dentist?.name }}</span>
                        <StatusBadge :value="a.status" />
                    </li>
                </ul>
            </div>
        </div>

        <div v-if="tab === 'chart'" class="card">
            <Odontogram :patient-id="patient.id" :chart="chart" :readonly="!can.clinical" />
        </div>

        <div v-if="tab === 'plans'">
            <form v-if="can.clinical" @submit.prevent="savePlan" class="card mb-4 flex flex-wrap items-end gap-2">
                <div class="min-w-52 flex-1"><label class="label">خطة جديدة</label>
                    <input v-model="plan.title" placeholder="مثال: خطة علاج الفك العلوي" class="input"></div>
                <button class="btn-primary">إنشاء</button>
            </form>
            <EmptyState v-if="!patient.treatment_plans.length" icon="services" title="لا توجد خطط علاج" hint="أنشئ خطة من النموذج بالأعلى." />
            <div v-for="p in patient.treatment_plans" :key="p.id" class="card mb-3">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-bold text-ink-900">{{ p.title }} <StatusBadge :value="p.status" /></h3>
                    <div class="text-sm font-bold tabular-nums text-ink-900">{{ fmt(p.total_minor) }}</div>
                </div>
                <table class="mb-2 w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="i in p.items" :key="i.id">
                            <td class="py-2">{{ i.description }} <span v-if="i.tooth_number" class="text-slate-400">(سن <span dir="ltr">{{ i.tooth_number }}</span>)</span></td>
                            <td class="text-end tabular-nums">{{ fmt((i.price_minor - i.discount_minor) * i.qty) }}</td>
                            <td class="w-44 text-end">
                                <select v-if="can.clinical" :value="i.status" @change="itemStatus(i.id, $event.target.value)" class="input !inline-block !w-auto !px-2 !py-1 text-xs">
                                    <option v-for="s in ['proposed','accepted','in_progress','completed','rejected']" :key="s" :value="s">{{ statusAr[s] }}</option>
                                </select>
                                <StatusBadge v-else :value="i.status" />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <form v-if="can.clinical && ['draft','proposed'].includes(p.status)" @submit.prevent="addItem(p.id)" class="mb-2 flex flex-wrap gap-2 text-sm">
                    <input v-model="item.description" placeholder="وصف البند" class="input flex-1" required>
                    <input v-model="item.tooth_number" placeholder="سن" class="input !w-16" dir="ltr">
                    <input v-model="item.price" placeholder="السعر" type="number" step="0.01" class="input !w-28" dir="ltr" required>
                    <button class="btn-secondary btn-sm !py-2">+ بند</button>
                </form>
                <div class="flex flex-wrap gap-2 text-xs" v-if="can.clinical">
                    <button v-for="s in nextTransitions[p.status] ?? []" :key="s" @click="transition(p.id, s)"
                        class="btn-secondary btn-sm">→ {{ statusAr[s] }}</button>
                    <button v-if="can.billing && p.items.some(i => i.status === 'accepted')" @click="invoiceFromPlan(p)"
                        class="btn-primary btn-sm">إنشاء فاتورة من البنود المقبولة</button>
                </div>
            </div>
        </div>

        <div v-if="tab === 'billing'" class="card !p-0 overflow-hidden">
            <EmptyState v-if="!patient.invoices.length" icon="invoices" title="لا توجد فواتير" />
            <table v-else class="stack w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr><th class="th">رقم</th><th class="th">الإجمالي</th><th class="th">المدفوع</th><th class="th">الحالة</th><th class="th"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="inv in patient.invoices" :key="inv.id">
                        <td class="td tabular-nums text-slate-500" dir="ltr" data-label="رقم">{{ inv.number ?? 'مسودة' }}</td>
                        <td class="td tabular-nums" data-label="الإجمالي">{{ fmt(inv.total_minor) }}</td>
                        <td class="td tabular-nums" data-label="المدفوع">{{ fmt(inv.paid_minor) }}</td>
                        <td class="td" data-label="الحالة"><StatusBadge :value="inv.status" /></td>
                        <td class="td" data-label=""><Link :href="`/invoices/${inv.id}`" class="font-semibold text-brand-700 hover:underline">فتح</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="tab === 'consents'" class="max-w-xl">
            <div class="card mb-3">
                <h3 class="mb-3 font-bold text-ink-900">تسجيل موافقة</h3>
                <form @submit.prevent="saveConsent" class="space-y-2 text-sm">
                    <select v-model="consent.type" class="input">
                        <option value="data_processing">موافقة معالجة البيانات الصحية</option>
                        <option value="treatment">موافقة علاجية</option>
                        <option value="marketing">موافقة تواصل تسويقي</option>
                    </select>
                    <textarea v-model="consent.text_snapshot" rows="2" required placeholder="نص الموافقة كما وافق عليه المريض" class="input"></textarea>
                    <div class="flex flex-wrap gap-2">
                        <select v-model="consent.method" class="input !w-40">
                            <option value="written">مكتوبة</option><option value="verbal_logged">شفهية (موثقة)</option><option value="digital">رقمية</option>
                        </select>
                        <input v-model="consent.given_at" type="datetime-local" class="input !w-52">
                        <button class="btn-primary">تسجيل</button>
                    </div>
                </form>
            </div>
            <EmptyState v-if="!patient.consents.length" icon="audit" title="لا توجد موافقات مسجلة" />
            <div v-for="c in patient.consents" :key="c.id" class="card mb-2 !p-3 text-sm">
                <div class="mb-1 text-xs text-slate-500">{{ { data_processing: 'معالجة بيانات', treatment: 'علاجية', marketing: 'تسويقية' }[c.type] }} · <span dir="ltr">{{ c.given_at }}</span></div>
                <p>{{ c.text_snapshot }}</p>
            </div>
        </div>
    </AppLayout>
</template>
