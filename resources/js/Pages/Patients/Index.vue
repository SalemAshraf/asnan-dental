<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ patients: Object, filters: Object });
const q = ref(props.filters?.q ?? '');
const search = () => router.get('/patients', { q: q.value }, { preserveState: true });
</script>

<template>
    <AppLayout>
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold">المرضى</h1>
            <Link href="/patients/create" class="bg-teal-700 text-white rounded-lg px-4 py-2 text-sm hover:bg-teal-600">+ مريض جديد</Link>
        </div>
        <form @submit.prevent="search" class="mb-4">
            <input v-model="q" placeholder="بحث بالاسم أو الهاتف أو رقم الملف…"
                class="border rounded-lg px-3 py-2 w-full md:w-96 focus:ring-2 focus:ring-teal-500 outline-none">
        </form>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500"><tr>
                    <th class="p-3 text-right">ملف</th><th class="text-right">الاسم</th>
                    <th class="text-right">الهاتف</th><th class="text-right">تاريخ الميلاد</th><th></th>
                </tr></thead>
                <tbody>
                    <tr v-for="p in patients.data" :key="p.id" class="border-t hover:bg-gray-50">
                        <td class="p-3">#{{ p.file_no }}</td>
                        <td class="font-medium">{{ p.full_name }}</td>
                        <td dir="ltr">{{ p.phone }}</td>
                        <td>{{ p.birth_date ?? '—' }}</td>
                        <td><Link :href="`/patients/${p.id}`" class="text-teal-700 hover:underline">فتح</Link></td>
                    </tr>
                </tbody>
            </table>
            <p v-if="!patients.data.length" class="p-6 text-center text-gray-400">لا يوجد مرضى مطابقون.</p>
        </div>
        <div class="mt-4 flex gap-2 text-sm">
            <Link v-for="l in patients.links" :key="l.label" :href="l.url ?? '#'"
                class="px-3 py-1 rounded border" :class="{ 'bg-teal-700 text-white': l.active, 'opacity-40': !l.url }"
                v-html="l.label" />
        </div>
    </AppLayout>
</template>
