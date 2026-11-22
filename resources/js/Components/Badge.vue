<script setup>
defineProps({ tone: { type: String, default: 'slate' } });
const tones = {
    slate: 'bg-slate-100 text-slate-700',
    blue: 'bg-sky-100 text-sky-800',
    green: 'bg-emerald-100 text-emerald-800',
    amber: 'bg-amber-100 text-amber-800',
    rose: 'bg-rose-100 text-rose-800',
    violet: 'bg-violet-100 text-violet-800',
    teal: 'bg-brand-100 text-brand-800',
};
</script>

<template>
    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="tones[tone] ?? tones.slate"><slot /></span>
</template>
