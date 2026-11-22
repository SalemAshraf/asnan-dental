<script setup>
import { computed } from 'vue';

// Two-series monthly bar chart. rows: [{ label: '06/26', a: number, b: number }]
const props = defineProps({
    rows: { type: Array, required: true },
    labelA: String, labelB: String,
    format: { type: Function, default: (v) => v },
});
const max = computed(() => Math.max(1, ...props.rows.flatMap((r) => [r.a, r.b])));
const h = (v) => Math.max(v ? 4 : 0, (v / max.value) * 120);
</script>

<template>
    <div>
        <div class="flex items-end justify-between gap-2" style="height: 150px" dir="ltr">
            <div v-for="r in rows" :key="r.label" class="flex flex-1 flex-col items-center justify-end gap-1">
                <div class="flex w-full items-end justify-center gap-1" style="height: 130px">
                    <div class="w-1/3 rounded-t bg-brand-200" :style="{ height: h(r.b) + 'px' }" :title="labelB + ': ' + format(r.b)"><span class="sr-only">{{ r.b }}</span></div>
                    <div class="w-1/3 rounded-t bg-brand-700" :style="{ height: h(r.a) + 'px' }" :title="labelA + ': ' + format(r.a)"><span class="sr-only">{{ r.a }}</span></div>
                </div>
                <span class="text-[11px] text-slate-500">{{ r.label }}</span>
            </div>
        </div>
        <div class="mt-3 flex gap-4 text-xs text-slate-500">
            <span class="flex items-center gap-1"><i class="inline-block h-2.5 w-2.5 rounded bg-brand-700" />{{ labelA }}</span>
            <span class="flex items-center gap-1"><i class="inline-block h-2.5 w-2.5 rounded bg-brand-200" />{{ labelB }}</span>
        </div>
    </div>
</template>
