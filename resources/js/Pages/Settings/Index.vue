<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import { useForm } from '@inertiajs/vue3';
import { t } from '@/i18n';

const props = defineProps({ clinic: Object, branches: Array, users: Array, roles: Array });


const clinicForm = useForm({
    name: props.clinic.name, phone: props.clinic.phone ?? '', address: props.clinic.address ?? '',
    tax_rate_bp: props.clinic.tax_rate_bp, invoice_prefix: props.clinic.invoice_prefix,
    timezone: props.clinic.timezone ?? 'Africa/Cairo', currency: props.clinic.currency ?? 'EGP',
});
const branchForm = useForm({ name: '', address: '', phone: '' });
const userForm = useForm({ name: '', email: '', phone: '', role: 'dentist', branch_id: '', password: '' });
const toggle = (id) => useForm({}).post(`/settings/users/${id}/toggle`, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <h1 class="mb-5 text-2xl font-bold text-ink-900">{{ t('set.title') }}</h1>

        <form @submit.prevent="clinicForm.put('/settings/clinic')" class="card mb-4 grid gap-3 text-sm md:grid-cols-3">
            <div><label class="label">{{ t('set.clinicName') }}</label>
                <input v-model="clinicForm.name" class="input"></div>
            <div><label class="label">{{ t('set.phone') }}</label>
                <input v-model="clinicForm.phone" class="input" dir="ltr"></div>
            <div><label class="label">{{ t('set.address') }}</label>
                <input v-model="clinicForm.address" class="input"></div>
            <div><label class="label">{{ t('set.tax') }}</label>
                <input v-model="clinicForm.tax_rate_bp" type="number" min="0" max="10000" class="input" dir="ltr"></div>
            <div><label class="label">{{ t('set.prefix') }}</label>
                <input v-model="clinicForm.invoice_prefix" class="input" dir="ltr"></div>
            <div><label class="label">{{ t('set.tz') }}</label>
                <select v-model="clinicForm.timezone" class="input" dir="ltr">
                    <option v-for="tz in ['Africa/Cairo','Africa/Khartoum','Asia/Riyadh','Asia/Jeddah','Asia/Dubai','Asia/Kuwait','Asia/Qatar','Asia/Bahrain','Asia/Muscat','Asia/Amman','Asia/Beirut','Africa/Casablanca','Africa/Tunis','Europe/Istanbul','UTC']" :key="tz" :value="tz">{{ tz }}</option>
                </select></div>
            <div><label class="label">{{ t('set.currency') }}</label>
                <input v-model="clinicForm.currency" class="input" dir="ltr" maxlength="3"></div>
            <div class="self-end"><button class="btn-primary">{{ t('set.save') }}</button></div>
        </form>

        <div class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('set.branches') }}</h2>
            <ul class="mb-3 divide-y divide-slate-100 text-sm">
                <li v-for="b in branches" :key="b.id" class="py-2"><span class="font-medium text-ink-900">{{ b.name }}</span> <span class="text-slate-400">{{ b.address }}</span></li>
            </ul>
            <form @submit.prevent="branchForm.post('/settings/branches', { onSuccess: () => branchForm.reset() })" class="flex flex-wrap gap-2 text-sm">
                <input v-model="branchForm.name" :placeholder="t('set.branchName')" class="input !w-48">
                <input v-model="branchForm.address" :placeholder="t('set.address')" class="input flex-1">
                <button class="btn-secondary">{{ t('set.addBranch') }}</button>
            </form>
            <p v-if="branchForm.errors.name" class="mt-1 text-xs text-rose-600">{{ branchForm.errors.name }}</p>
        </div>

        <div class="card">
            <h2 class="mb-3 font-bold text-ink-900">{{ t('set.users') }}</h2>
            <div class="mb-4 overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead class="border-b border-slate-200">
                        <tr><th class="th !px-0">{{ t('set.col.name') }}</th><th class="th">{{ t('set.col.email') }}</th><th class="th">{{ t('set.col.role') }}</th><th class="th">{{ t('set.col.status') }}</th><th class="th !px-0"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in users" :key="u.id">
                            <td class="td !px-0 font-medium text-ink-900">{{ u.name }}</td>
                            <td class="td tabular-nums text-slate-500" dir="ltr">{{ u.email }}</td>
                            <td class="td">{{ t('set.role.' + u.role) }}</td>
                            <td class="td"><Badge :tone="u.is_active ? 'green' : 'slate'">{{ t(u.is_active ? 'set.active' : 'set.inactive') }}</Badge></td>
                            <td class="td !px-0 text-end"><button @click="toggle(u.id)" class="btn-secondary btn-sm">{{ t(u.is_active ? 'set.deactivate' : 'set.activate') }}</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <form @submit.prevent="userForm.post('/settings/users', { onSuccess: () => userForm.reset() })" class="grid items-end gap-2 text-sm md:grid-cols-6">
                <div><label class="label">{{ t('set.form.name') }}</label><input v-model="userForm.name" class="input"></div>
                <div><label class="label">{{ t('set.form.email') }}</label><input v-model="userForm.email" type="email" class="input" dir="ltr"></div>
                <div><label class="label">{{ t('set.form.role') }}</label>
                    <select v-model="userForm.role" class="input">
                        <option v-for="r in roles" :key="r" :value="r">{{ t('set.role.' + r) }}</option>
                    </select></div>
                <div><label class="label">{{ t('set.form.branch') }}</label>
                    <select v-model="userForm.branch_id" class="input">
                        <option value="">{{ t('set.form.noBranch') }}</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select></div>
                <div><label class="label">{{ t('set.form.password') }}</label><input v-model="userForm.password" type="password" class="input" dir="ltr"></div>
                <div><button class="btn-primary">{{ t('set.addUser') }}</button></div>
            </form>
            <p v-for="(e, k) in userForm.errors" :key="k" class="mt-1 text-xs text-rose-600">{{ e }}</p>
        </div>
    </AppLayout>
</template>
