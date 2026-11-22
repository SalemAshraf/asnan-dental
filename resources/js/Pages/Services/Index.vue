<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import { fmt } from '@/money';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ services: Array });
const catAr = { exam: 'كشف', preventive: 'وقائي', restorative: 'ترميمي', endo: 'عصب', perio: 'لثة', surgery: 'جراحة', prostho: 'تركيبات', ortho: 'تقويم', cosmetic: 'تجميل', other: 'أخرى' };

const form = useForm({ code: '', name_ar: '', name_en: '', category: 'other', price: '', duration_min: 30 });
const save = () => form.post('/services', { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <AppLayout>
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-ink-900">قائمة الخدمات والأسعار</h1>
            <p class="text-sm text-slate-500">تُستخدم الخدمات في بنود خطط العلاج والفواتير</p>
        </div>

        <form @submit.prevent="save" class="card mb-4 grid items-end gap-2 text-sm md:grid-cols-6">
            <div><label class="label">كود</label><input v-model="form.code" class="input" dir="ltr"></div>
            <div class="md:col-span-2"><label class="label">الاسم بالعربية *</label><input v-model="form.name_ar" class="input"></div>
            <div><label class="label">الفئة</label>
                <select v-model="form.category" class="input">
                    <option v-for="(l, k) in catAr" :key="k" :value="k">{{ l }}</option>
                </select></div>
            <div><label class="label">السعر *</label><input v-model="form.price" type="number" step="0.01" min="0" class="input" dir="ltr"></div>
            <div><label class="label">المدة (دقيقة)</label><input v-model="form.duration_min" type="number" min="5" class="input" dir="ltr"></div>
            <div class="md:col-span-6"><button class="btn-primary"><Icon name="plus" :size="16" /> إضافة خدمة</button></div>
        </form>
        <p v-if="form.errors.code" class="mb-2 text-xs text-rose-600">{{ form.errors.code }}</p>

        <div class="card !p-0 overflow-hidden">
            <table class="stack w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="th">كود</th><th class="th">الخدمة</th>
                        <th class="th">الفئة</th><th class="th">السعر</th><th class="th">المدة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="s in services" :key="s.id" class="transition hover:bg-brand-50/50">
                        <td class="td tabular-nums text-slate-500" dir="ltr" data-label="كود">{{ s.code ?? '—' }}</td>
                        <td class="td font-semibold text-ink-900" data-label="الخدمة">{{ s.name_ar }}</td>
                        <td class="td" data-label="الفئة"><Badge tone="teal">{{ catAr[s.category] }}</Badge></td>
                        <td class="td tabular-nums" data-label="السعر">{{ fmt(s.default_price_minor) }}</td>
                        <td class="td tabular-nums text-slate-500" data-label="المدة"><span dir="ltr">{{ s.duration_min }}</span> دقيقة</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
