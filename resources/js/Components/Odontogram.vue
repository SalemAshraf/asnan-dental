<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ patientId: Number, chart: Object, readonly: Boolean });

const conditions = {
    healthy: { label: 'سليم', color: 'bg-emerald-100 border-emerald-400' },
    caries: { label: 'تسوس', color: 'bg-red-200 border-red-500' },
    filling: { label: 'حشو', color: 'bg-sky-200 border-sky-500' },
    root_canal: { label: 'علاج عصب', color: 'bg-violet-200 border-violet-500' },
    crown: { label: 'تاج', color: 'bg-amber-200 border-amber-500' },
    missing: { label: 'مفقود', color: 'bg-gray-200 border-gray-400' },
    implant: { label: 'زراعة', color: 'bg-teal-200 border-teal-500' },
    to_extract: { label: 'للخلع', color: 'bg-orange-200 border-orange-500' },
    bridge: { label: 'جسر', color: 'bg-yellow-100 border-yellow-500' },
    veneer: { label: 'قشرة', color: 'bg-pink-100 border-pink-400' },
    sealant: { label: 'حشوة وقائية', color: 'bg-lime-100 border-lime-400' },
};
const quadrants = [
    [18, 17, 16, 15, 14, 13, 12, 11],
    [21, 22, 23, 24, 25, 26, 27, 28],
    [48, 47, 46, 45, 44, 43, 42, 41],
    [31, 32, 33, 34, 35, 36, 37, 38],
];

const selected = ref(null);
const form = ref({ condition: 'caries', surface: '' });

const pick = (tooth) => { if (!props.readonly) selected.value = tooth; };
const toothState = (tooth) => props.chart?.[tooth]?.whole ?? null;
const toothSurfaces = (tooth) => props.chart?.[tooth] ?? {};
const save = () => {
    router.post(`/patients/${props.patientId}/odontogram`, {
        tooth_number: selected.value,
        condition: form.value.condition,
        surface: form.value.surface || null,
    }, { preserveScroll: true, onSuccess: () => (selected.value = null) });
};
</script>

<template>
    <div>
        <div class="grid grid-cols-8 gap-1 max-w-2xl mx-auto" dir="ltr">
            <template v-for="(row, i) in quadrants" :key="i">
                <button v-for="tooth in row" :key="tooth" type="button" @click="pick(tooth)"
                    class="border-2 rounded-lg py-2 text-xs font-bold transition"
                    :class="[toothState(tooth) ? conditions[toothState(tooth)]?.color : 'bg-white border-gray-300',
                        readonly ? 'cursor-default' : 'hover:border-teal-500 cursor-pointer',
                        selected === tooth ? 'ring-2 ring-teal-600' : '']">
                    {{ tooth }}
                    <div v-if="toothSurfaces(tooth) && Object.keys(toothSurfaces(tooth)).some(k => k !== 'whole')"
                        class="text-[9px] font-normal text-gray-600 mt-0.5">
                        {{ Object.entries(toothSurfaces(tooth)).filter(([k]) => k !== 'whole').map(([s, c]) => s + ':' + (conditions[c]?.label ?? c)).join(' ') }}
                    </div>
                </button>
                <div v-if="i === 1" class="col-span-8 border-t my-1"></div>
            </template>
        </div>

        <div v-if="selected && !readonly" class="mt-4 bg-gray-50 rounded-lg p-4 max-w-2xl mx-auto">
            <h3 class="font-bold mb-2">السن {{ selected }}</h3>
            <div class="flex flex-wrap gap-2 mb-3">
                <button v-for="(c, key) in conditions" :key="key" type="button"
                    @click="form.condition = key"
                    class="px-3 py-1 rounded border text-sm"
                    :class="form.condition === key ? 'bg-teal-700 text-white' : 'bg-white'">
                    {{ c.label }}
                </button>
            </div>
            <div class="flex gap-2 mb-3 text-sm">
                <span class="self-center">السطح:</span>
                <button v-for="s in ['', 'M', 'O', 'D', 'B', 'L']" :key="s" type="button"
                    @click="form.surface = s"
                    class="px-2 py-1 rounded border"
                    :class="form.surface === s ? 'bg-teal-700 text-white' : 'bg-white'">
                    {{ s === '' ? 'السن كامل' : s }}
                </button>
            </div>
            <button @click="save" class="bg-teal-700 text-white rounded px-4 py-1 text-sm">حفظ</button>
        </div>

        <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-500 justify-center">
            <span v-for="(c, key) in conditions" :key="key" class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-sm border inline-block" :class="c.color"></span>{{ c.label }}
            </span>
        </div>
    </div>
</template>
