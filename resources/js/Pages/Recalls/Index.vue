<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ pending: Array, recentDone: Array, today: String });
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

const statusLabel = { pending: 'قادم', done: 'تم', cancelled: 'ملغي' };
const overdue = computed(() => props.pending.filter((r) => r.overdue));
const upcoming = computed(() => props.pending.filter((r) => !r.overdue));
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">تذكيرات المتابعة</h1>
                <p class="text-sm text-slate-500">مرضى محتاجين رجوع للعيادة. زر واتساب يفتح محادثة على جهازك برسالة تذكير جاهزة ترسلها بنفسك — لا يُرسل أي شيء تلقائيًا، والرسالة لا تحتوي تفاصيل علاجية.</p>
            </div>
            <button @click="showForm = !showForm" class="btn-primary"><Icon name="plus" :size="16" /> تذكير جديد</button>
        </div>

        <form v-if="showForm" @submit.prevent="save" class="card mb-4 grid gap-3 text-sm md:grid-cols-4">
            <div class="relative md:col-span-2">
                <label class="label">المريض</label>
                <input v-model="patientQ" @input="searchPatients" placeholder="ابحث بالاسم أو الهاتف" class="input">
                <div v-if="patientResults.length" class="absolute z-10 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg">
                    <div v-for="p in patientResults" :key="p.id" @click="form.patient_id = p.id; patientQ = p.full_name + ' #' + p.file_no; patientResults = []"
                        class="cursor-pointer border-b border-slate-100 px-3 py-2 last:border-0 hover:bg-brand-50">{{ p.full_name }} <span class="text-slate-400">#{{ p.file_no }}</span></div>
                </div>
                <p v-if="form.errors.patient_id" class="mt-1 text-xs text-rose-600">{{ form.errors.patient_id }}</p>
            </div>
            <div><label class="label">تاريخ الاستحقاق *</label>
                <input v-model="form.due_date" type="date" required class="input">
                <p v-if="form.errors.due_date" class="mt-1 text-xs text-rose-600">{{ form.errors.due_date }}</p></div>
            <div><label class="label">السبب</label>
                <input v-model="form.reason" placeholder="مثال: مراجعة تنظيف دورية" class="input"></div>
            <div class="md:col-span-4"><button class="btn-primary">حفظ التذكير</button></div>
        </form>

        <div v-if="overdue.length" class="card mb-4 border-rose-200">
            <h2 class="mb-3 font-bold text-rose-700">متأخرة ({{ overdue.length }})</h2>
            <div class="divide-y divide-slate-100">
                <div v-for="r in overdue" :key="r.id" class="flex flex-wrap items-center gap-3 py-3 text-sm">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-ink-900">{{ r.patient?.full_name }} <span class="font-normal text-slate-400">#{{ r.patient?.file_no }}</span></span>
                        <span class="text-xs text-slate-500">{{ r.reason || r.service?.name_ar || 'متابعة' }} · كان مستحقًا <span class="tabular-nums" dir="ltr">{{ dstr(r.due_date) }}</span></span>
                    </span>
                    <a v-if="waLink(r)" :href="waLink(r)" target="_blank" rel="noopener" class="btn-secondary btn-sm" title="يفتح محادثة واتساب برسالة جاهزة — الإرسال يدوي من جهازك"><Icon name="whatsapp" :size="15" /> واتساب</a>
                    <button @click="setStatus(r.id, 'done')" class="btn-primary btn-sm">تم التواصل/الحجز</button>
                    <button @click="setStatus(r.id, 'cancelled')" class="btn-secondary btn-sm">إلغاء</button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">قادمة ({{ upcoming.length }})</h2>
            <EmptyState v-if="!upcoming.length" icon="clock" title="لا توجد تذكيرات قادمة" hint="أضف تذكير متابعة من الزر بالأعلى." />
            <div v-else class="divide-y divide-slate-100">
                <div v-for="r in upcoming" :key="r.id" class="flex flex-wrap items-center gap-3 py-3 text-sm">
                    <span class="grid w-24 shrink-0 place-items-center rounded-lg bg-brand-50 py-1.5 text-xs font-bold tabular-nums text-brand-800" dir="ltr">{{ dstr(r.due_date) }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-ink-900">{{ r.patient?.full_name }}</span>
                        <span class="text-xs text-slate-500">{{ r.reason || r.service?.name_ar || 'متابعة' }}</span>
                    </span>
                    <a v-if="waLink(r)" :href="waLink(r)" target="_blank" rel="noopener" class="btn-secondary btn-sm" title="يفتح محادثة واتساب برسالة جاهزة — الإرسال يدوي من جهازك"><Icon name="whatsapp" :size="15" /> واتساب</a>
                    <button @click="setStatus(r.id, 'done')" class="btn-primary btn-sm">تم</button>
                    <button @click="setStatus(r.id, 'cancelled')" class="btn-secondary btn-sm">إلغاء</button>
                </div>
            </div>
        </div>

        <div v-if="recentDone.length" class="card">
            <h2 class="mb-3 font-bold text-ink-900">آخر المغلقة</h2>
            <div class="divide-y divide-slate-100">
                <div v-for="r in recentDone" :key="r.id" class="flex items-center gap-3 py-2 text-sm">
                    <span class="min-w-0 flex-1 truncate">{{ r.patient?.full_name }} <span class="text-xs text-slate-400">{{ r.reason || r.service?.name_ar }}</span></span>
                    <Badge :tone="r.status === 'done' ? 'green' : 'slate'">{{ statusLabel[r.status] }}</Badge>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
