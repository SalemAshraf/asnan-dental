<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import Icon from '@/Components/Icon.vue';
import { fmt } from '@/money';
import { useForm } from '@inertiajs/vue3';
import { t } from '@/i18n';

const props = defineProps({ services: Array });
const cats = ['exam','preventive','restorative','endo','perio','surgery','prostho','ortho','cosmetic','other'];

const form = useForm({ code: '', name_ar: '', name_en: '', category: 'other', price: '', duration_min: 30 });
const save = () => form.post('/services', { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <AppLayout>
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-ink-900">{{ t('srv.title') }}</h1>
            <p class="text-sm text-slate-500">{{ t('srv.subtitle') }}</p>
        </div>

        <form @submit.prevent="save" class="card mb-4 grid items-end gap-2 text-sm md:grid-cols-6">
            <div><label class="label">{{ t('srv.code') }}</label><input v-model="form.code" class="input" dir="ltr"></div>
            <div class="md:col-span-2"><label class="label">{{ t('srv.nameAr') }}</label><input v-model="form.name_ar" class="input"></div>
            <div><label class="label">{{ t('srv.category') }}</label>
                <select v-model="form.category" class="input">
                    <option v-for="k in cats" :key="k" :value="k">{{ t('srv.cat.' + k) }}</option>
                </select></div>
            <div><label class="label">{{ t('srv.price') }}</label><input v-model="form.price" type="number" step="0.01" min="0" class="input" dir="ltr"></div>
            <div><label class="label">{{ t('srv.duration') }}</label><input v-model="form.duration_min" type="number" min="5" class="input" dir="ltr"></div>
            <div class="md:col-span-6"><button class="btn-primary"><Icon name="plus" :size="16" /> {{ t('srv.add') }}</button></div>
        </form>
        <p v-if="form.errors.code" class="mb-2 text-xs text-rose-600">{{ form.errors.code }}</p>

        <div class="card !p-0 overflow-hidden">
            <table class="stack w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="th">{{ t('srv.code') }}</th><th class="th">{{ t('srv.col.service') }}</th>
                        <th class="th">{{ t('srv.category') }}</th><th class="th">{{ t('srv.price') }}</th><th class="th">{{ t('srv.duration') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="s in services" :key="s.id" class="transition hover:bg-brand-50/50">
                        <td class="td tabular-nums text-slate-500" dir="ltr" :data-label="t('srv.code')">{{ s.code ?? '—' }}</td>
                        <td class="td font-semibold text-ink-900" :data-label="t('srv.col.service')">{{ s.name_ar }}</td>
                        <td class="td" :data-label="t('srv.category')"><Badge tone="teal">{{ t('srv.cat.' + s.category) }}</Badge></td>
                        <td class="td tabular-nums" :data-label="t('srv.price')">{{ fmt(s.default_price_minor) }}</td>
                        <td class="td tabular-nums text-slate-500" :data-label="t('srv.duration')"><span dir="ltr">{{ s.duration_min }}</span> {{ t('srv.minutes') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
