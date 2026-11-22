<script setup>
import Badge from './Badge.vue';
import { statusLabel } from '@/i18n';

const props = defineProps({ value: { type: String, required: true } });

const tones = {
    scheduled: 'blue', confirmed: 'violet', checked_in: 'amber', completed: 'green',
    cancelled: 'slate', no_show: 'rose',
    draft: 'slate', issued: 'blue', partially_paid: 'amber', paid: 'green', void: 'rose',
    proposed: 'blue', accepted: 'violet', in_progress: 'amber', rejected: 'rose',
};
</script>

<template>
    <Badge :tone="tones[value] ?? 'slate'">{{ statusLabel(value) }}</Badge>
</template>
