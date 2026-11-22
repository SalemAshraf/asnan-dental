<script setup>
import Badge from './Badge.vue';

const props = defineProps({ value: { type: String, required: true } });

const labels = {
    scheduled: 'مجدول', confirmed: 'مؤكد', checked_in: 'وصل', completed: 'تم',
    cancelled: 'ملغي', no_show: 'لم يحضر',
    draft: 'مسودة', issued: 'صادرة', partially_paid: 'مدفوعة جزئيًا', paid: 'مدفوعة', void: 'ملغاة',
    proposed: 'مقترحة', accepted: 'مقبولة', in_progress: 'قيد التنفيذ', rejected: 'مرفوضة',
};
const tones = {
    scheduled: 'blue', confirmed: 'violet', checked_in: 'amber', completed: 'green',
    cancelled: 'slate', no_show: 'rose',
    draft: 'slate', issued: 'blue', partially_paid: 'amber', paid: 'green', void: 'rose',
    proposed: 'blue', accepted: 'violet', in_progress: 'amber', rejected: 'rose',
};
</script>

<template>
    <Badge :tone="tones[value] ?? 'slate'">{{ labels[value] ?? value }}</Badge>
</template>
