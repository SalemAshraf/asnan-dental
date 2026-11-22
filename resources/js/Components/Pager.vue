<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ links: { type: Array, required: true } });
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap gap-1.5 text-sm" aria-label="التنقل بين الصفحات">
        <Link v-for="l in links" :key="l.label" :href="l.url ?? '#'"
            class="rounded-lg border px-3 py-1.5 transition"
            :class="[l.active ? 'border-brand-700 bg-brand-700 text-white' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100', !l.url ? 'pointer-events-none opacity-40' : '']"
            v-html="l.label" />
    </nav>
</template>
