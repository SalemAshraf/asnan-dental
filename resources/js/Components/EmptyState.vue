<script setup>
import Icon from './Icon.vue';
defineProps({ icon: { type: String, default: 'search' }, title: String, hint: String });
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center">
        <span class="grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-400"><Icon :name="icon" :size="24" /></span>
        <p class="text-sm font-semibold text-slate-600">{{ title }}</p>
        <p v-if="hint" class="max-w-sm text-xs text-slate-400">{{ hint }}</p>
        <slot />
    </div>
</template>
