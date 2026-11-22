<script setup>
import { computed } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    label: String, value: [String, Number], hint: String,
    tone: { type: String, default: 'slate' },
    icon: String,
    series: { type: Array, default: () => [] },
});
const pts = computed(() => {
    const v = props.series;
    if (v.length < 2) return '';
    const mx = Math.max(1, ...v);
    return v.map((x, i) => `${(i * 100) / (v.length - 1)},${30 - 3 - (x / mx) * 24}`).join(' ');
});
</script>

<template>
    <div class="card min-w-0 !p-4 sm:!p-5">
        <div class="flex items-start justify-between gap-2">
            <p class="text-sm leading-snug text-slate-500">{{ label }}</p>
            <span v-if="icon" class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700"><Icon :name="icon" :size="17" /></span>
        </div>
        <p class="mt-1 whitespace-nowrap text-xl font-bold tracking-tight text-ink-900 sm:text-2xl"
            :class="{ '!text-rose-600': tone === 'bad', '!text-brand-700': tone === 'good' }">{{ value }}</p>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <p v-if="hint" class="min-w-24 flex-1 text-xs text-slate-400">{{ hint }}</p>
            <svg v-if="pts" viewBox="0 0 100 30" preserveAspectRatio="none" class="h-7 w-20 shrink-0" dir="ltr" aria-hidden="true">
                <polyline :points="pts" fill="none" stroke="#177A75" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            </svg>
        </div>
    </div>
</template>
