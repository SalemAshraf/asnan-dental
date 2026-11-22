<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { fmt } from '@/money';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ services: Array });
const catAr = { exam: 'كشف', preventive: 'وقائي', restorative: 'ترميمي', endo: 'عصب', perio: 'لثة', surgery: 'جراحة', prostho: 'تركيبات', ortho: 'تقويم', cosmetic: 'تجميل', other: 'أخرى' };

const form = useForm({ code: '', name_ar: '', name_en: '', category: 'other', price: '', duration_min: 30 });
const save = () => form.post('/services', { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <AppLayout>
        <h1 class="text-2xl font-bold mb-4">قائمة الخدمات والأسعار</h1>
        <form @submit.prevent="save" class="bg-white rounded-xl shadow-sm p-4 mb-4 grid md:grid-cols-6 gap-2 text-sm items-end">
            <input v-model="form.code" placeholder="كود" class="border rounded px-2 py-1" dir="ltr">
            <input v-model="form.name_ar" placeholder="الاسم بالعربية *" class="border rounded px-2 py-1">
            <select v-model="form.category" class="border rounded px-2 py-1">
                <option v-for="(l, k) in catAr" :key="k" :value="k">{{ l }}</option>
            </select>
            <input v-model="form.price" type="number" step="0.01" min="0" placeholder="السعر *" class="border rounded px-2 py-1" dir="ltr">
            <input v-model="form.duration_min" type="number" min="5" placeholder="المدة (د)" class="border rounded px-2 py-1" dir="ltr">
            <button class="bg-teal-700 text-white rounded px-3 py-1.5">+ إضافة</button>
        </form>
        <p v-if="form.errors.code" class="text-red-600 text-xs mb-2">{{ form.errors.code }}</p>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500"><tr>
                    <th class="p-3 text-right">كود</th><th class="text-right">الخدمة</th>
                    <th class="text-right">الفئة</th><th class="text-right">السعر</th><th class="text-right">المدة</th>
                </tr></thead>
                <tbody>
                    <tr v-for="s in services" :key="s.id" class="border-t">
                        <td class="p-3" dir="ltr">{{ s.code ?? '—' }}</td>
                        <td class="font-medium">{{ s.name_ar }}</td>
                        <td>{{ catAr[s.category] }}</td>
                        <td>{{ fmt(s.default_price_minor) }}</td>
                        <td dir="ltr">{{ s.duration_min }} min</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
