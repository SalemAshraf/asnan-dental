<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import { useForm } from '@inertiajs/vue3';
import { i18n, t } from '@/i18n';

const props = defineProps({ clinic: Object, branches: Array, users: Array, roles: Array, setupDismissed: Boolean, bookingEnabled: Boolean, bookingUrl: String, ai: Object });
const aiForm = useForm({ enabled: props.ai?.enabled ?? false });
const revoke = (family) => useForm({}).delete(`/settings/ai-connections/${family}`, { preserveScroll: true });
const fmt = (d) => d ? new Date(d).toLocaleDateString(i18n.locale === 'ar' ? 'ar-EG' : 'en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : t('set.aiNever');
const guideForm = useForm({});
const showGuide = () => guideForm.post('/setup-guide/show', { preserveScroll: true });


const clinicForm = useForm({
    name: props.clinic.name, phone: props.clinic.phone ?? '', address: props.clinic.address ?? '',
    tax_rate_bp: props.clinic.tax_rate_bp, invoice_prefix: props.clinic.invoice_prefix,
    timezone: props.clinic.timezone ?? 'Africa/Cairo', currency: props.clinic.currency ?? 'EGP',
    booking_enabled: props.bookingEnabled ?? false,
});
const branchForm = useForm({ name: '', address: '', phone: '' });
const userForm = useForm({ name: '', email: '', phone: '', role: 'dentist', branch_id: '', password: '' });
const toggle = (id) => useForm({}).post(`/settings/users/${id}/toggle`, { preserveScroll: true });
</script>

<template>
    <AppLayout>
                <div v-if="setupDismissed" class="mb-4 flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm">
            <span class="text-slate-500">{{ t('guide.title') }}</span>
            <button class="btn-secondary btn-sm" @click="showGuide">{{ t('guide.show') }}</button>
        </div>
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
            <h2 class="mb-1 font-bold text-ink-900">{{ t('set.booking') }}</h2>
            <p class="mb-3 text-xs text-slate-500">{{ t('set.bookingHint') }}</p>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="clinicForm.booking_enabled" type="checkbox" @change="clinicForm.put('/settings/clinic', { preserveScroll: true })">
                {{ t('set.bookingToggle') }}
            </label>
            <div v-if="clinicForm.booking_enabled && bookingUrl" class="mt-3 flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 p-3 text-sm">
                <a :href="bookingUrl" target="_blank" rel="noopener" class="break-all font-medium text-brand-700 hover:underline" dir="ltr">{{ bookingUrl }}</a>
            </div>
        </div>

        <div class="card mb-4">
            <h2 class="mb-1 font-bold text-ink-900">{{ t('set.ai') }}</h2>
            <p class="mb-3 text-xs leading-5 text-slate-500">{{ t('set.aiHint') }}</p>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="aiForm.enabled" type="checkbox" @change="aiForm.patch('/settings/ai', { preserveScroll: true })">
                {{ t('set.aiToggle') }}
            </label>
            <div v-if="aiForm.enabled" class="mt-3 rounded-lg bg-slate-50 p-3 text-sm">
                <p class="mb-1 text-xs text-slate-500">{{ t('set.aiHow') }}</p>
                <code class="break-all font-medium text-brand-700" dir="ltr">{{ ai.url }}</code>
            </div>
            <div v-if="ai.connections?.length" class="mt-3 overflow-x-auto">
                <p class="mb-1 text-xs font-semibold text-slate-500">{{ t('set.aiConnections') }}</p>
                <table class="w-full min-w-[420px] text-sm">
                    <thead class="border-b border-slate-200">
                        <tr><th class="th !px-0">{{ t('set.aiApp') }}</th><th class="th">{{ t('set.aiUser') }}</th><th class="th">{{ t('set.aiSince') }}</th><th class="th">{{ t('set.aiLast') }}</th><th class="th !px-0"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="x in ai.connections" :key="x.family">
                            <td class="td !px-0 font-medium text-ink-900">{{ x.app }}</td>
                            <td class="td text-slate-600">{{ x.user }}</td>
                            <td class="td tabular-nums text-slate-500">{{ fmt(x.created_at) }}</td>
                            <td class="td tabular-nums text-slate-500">{{ fmt(x.last_used_at) }}</td>
                            <td class="td !px-0 text-end"><button class="btn-secondary btn-sm" @click="revoke(x.family)">{{ t('set.aiRevoke') }}</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="mt-3 text-xs text-slate-400">{{ t('set.aiNone') }}</p>
        </div>

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
