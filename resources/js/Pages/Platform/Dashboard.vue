<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

defineProps({ counts: Object, trialEnding: Array });

const cards = [
    ['clinics', 'كل العيادات'],
    ['trialing', 'في التجربة'],
    ['active', 'مفعّلة'],
    ['past_due', 'متأخرة السداد'],
    ['cancelled', 'ملغاة'],
    ['new_inquiries', 'طلبات تواصل جديدة'],
];
</script>

<template>
    <Head title="لوحة المنصة" />
    <PlatformLayout title="نظرة عامة">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div v-for="c in cards" :key="c[0]" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ c[1] }}</p>
                <p class="mt-1 text-3xl font-bold">{{ counts[c[0]] }}</p>
            </div>
        </div>
        <div v-if="trialEnding.length" class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <h2 class="font-bold text-amber-900">تجارب تنتهي خلال ٣ أيام أو انتهت</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li v-for="tRow in trialEnding" :key="tRow.id" class="flex items-center justify-between rounded-lg bg-white px-4 py-2">
                    <Link :href="`/platform/clinics/${tRow.id}`" class="font-semibold text-brand-700 hover:underline">{{ tRow.name }}</Link>
                    <span class="text-slate-500">{{ tRow.trial_ends_at }}</span>
                </li>
            </ul>
        </div>
    </PlatformLayout>
</template>
