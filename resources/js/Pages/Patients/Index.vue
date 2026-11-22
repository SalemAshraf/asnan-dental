<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Pager from '@/Components/Pager.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Icon from '@/Components/Icon.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ patients: Object, filters: Object });
const q = ref(props.filters?.q ?? '');
const search = () => router.get('/patients', { q: q.value || undefined }, { preserveState: true });
</script>

<template>
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-ink-900">المرضى</h1>
                <p class="text-sm text-slate-500">{{ patients.total }} ملف مسجل</p>
            </div>
            <Link href="/patients/create" class="btn-primary"><Icon name="plus" :size="16" /> مريض جديد</Link>
        </div>

        <form @submit.prevent="search" class="mb-4">
            <div class="relative max-w-md">
                <Icon name="search" :size="16" class="pointer-events-none absolute inset-y-0 start-3 my-auto text-slate-400" />
                <input v-model="q" class="input !ps-9" placeholder="بحث بالاسم أو الهاتف أو رقم الملف…" aria-label="بحث في المرضى" />
            </div>
        </form>

        <div class="card !p-0 overflow-hidden">
            <EmptyState v-if="!patients.data.length" icon="patients" title="لا يوجد مرضى مطابقون" hint="جرّب بحثًا مختلفًا أو أضف مريضًا جديدًا." />
            <table v-else class="stack w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="th">ملف</th><th class="th">الاسم</th>
                        <th class="th">الهاتف</th><th class="th">تاريخ الميلاد</th><th class="th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="p in patients.data" :key="p.id" class="transition hover:bg-brand-50/50">
                        <td class="td text-slate-500" data-label="ملف">#{{ p.file_no }}</td>
                        <td class="td font-semibold text-ink-900" data-label="الاسم">{{ p.full_name }}</td>
                        <td class="td tabular-nums" dir="ltr" data-label="الهاتف">{{ p.phone }}</td>
                        <td class="td text-slate-500" data-label="تاريخ الميلاد">{{ p.birth_date ?? '—' }}</td>
                        <td class="td" data-label=""><Link :href="`/patients/${p.id}`" class="font-semibold text-brand-700 hover:underline">فتح الملف</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><Pager :links="patients.links" /></div>
    </AppLayout>
</template>
