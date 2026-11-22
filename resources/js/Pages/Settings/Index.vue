<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ clinic: Object, branches: Array, users: Array, roles: Array });
const roleAr = { owner: 'مالك', dentist: 'طبيب', receptionist: 'استقبال', assistant: 'مساعد' };

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
        <h1 class="mb-5 text-2xl font-bold text-ink-900">إعدادات العيادة</h1>

        <form @submit.prevent="clinicForm.put('/settings/clinic')" class="card mb-4 grid gap-3 text-sm md:grid-cols-3">
            <div><label class="label">اسم العيادة</label>
                <input v-model="clinicForm.name" class="input"></div>
            <div><label class="label">الهاتف</label>
                <input v-model="clinicForm.phone" class="input" dir="ltr"></div>
            <div><label class="label">العنوان</label>
                <input v-model="clinicForm.address" class="input"></div>
            <div><label class="label">نسبة الضريبة (نقاط أساس، مثال: 500 = 5%). اضبطها بعد التحقق من القواعد المطبقة على عيادتك — لا توجد نسبة افتراضية ملزمة</label>
                <input v-model="clinicForm.tax_rate_bp" type="number" min="0" max="10000" class="input" dir="ltr"></div>
            <div><label class="label">بادئة الفواتير</label>
                <input v-model="clinicForm.invoice_prefix" class="input" dir="ltr"></div>
            <div><label class="label">المنطقة الزمنية (تؤثر على المواعيد والتقارير)</label>
                <select v-model="clinicForm.timezone" class="input" dir="ltr">
                    <option v-for="tz in ['Africa/Cairo','Africa/Khartoum','Asia/Riyadh','Asia/Jeddah','Asia/Dubai','Asia/Kuwait','Asia/Qatar','Asia/Bahrain','Asia/Muscat','Asia/Amman','Asia/Beirut','Africa/Casablanca','Africa/Tunis','Europe/Istanbul','UTC']" :key="tz" :value="tz">{{ tz }}</option>
                </select></div>
            <div><label class="label">العملة (رمز ISO من 3 أحرف)</label>
                <input v-model="clinicForm.currency" class="input" dir="ltr" maxlength="3"></div>
            <div class="self-end"><button class="btn-primary">حفظ</button></div>
        </form>

        <div class="card mb-4">
            <h2 class="mb-3 font-bold text-ink-900">الفروع</h2>
            <ul class="mb-3 divide-y divide-slate-100 text-sm">
                <li v-for="b in branches" :key="b.id" class="py-2"><span class="font-medium text-ink-900">{{ b.name }}</span> <span class="text-slate-400">{{ b.address }}</span></li>
            </ul>
            <form @submit.prevent="branchForm.post('/settings/branches', { onSuccess: () => branchForm.reset() })" class="flex flex-wrap gap-2 text-sm">
                <input v-model="branchForm.name" placeholder="اسم الفرع *" class="input !w-48">
                <input v-model="branchForm.address" placeholder="العنوان" class="input flex-1">
                <button class="btn-secondary">+ فرع</button>
            </form>
            <p v-if="branchForm.errors.name" class="mt-1 text-xs text-rose-600">{{ branchForm.errors.name }}</p>
        </div>

        <div class="card">
            <h2 class="mb-3 font-bold text-ink-900">المستخدمون</h2>
            <div class="mb-4 overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead class="border-b border-slate-200">
                        <tr><th class="th !px-0">الاسم</th><th class="th">البريد</th><th class="th">الدور</th><th class="th">الحالة</th><th class="th !px-0"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in users" :key="u.id">
                            <td class="td !px-0 font-medium text-ink-900">{{ u.name }}</td>
                            <td class="td tabular-nums text-slate-500" dir="ltr">{{ u.email }}</td>
                            <td class="td">{{ roleAr[u.role] ?? u.role }}</td>
                            <td class="td"><Badge :tone="u.is_active ? 'green' : 'slate'">{{ u.is_active ? 'نشط' : 'موقوف' }}</Badge></td>
                            <td class="td !px-0 text-end"><button @click="toggle(u.id)" class="btn-secondary btn-sm">{{ u.is_active ? 'إيقاف' : 'تفعيل' }}</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <form @submit.prevent="userForm.post('/settings/users', { onSuccess: () => userForm.reset() })" class="grid items-end gap-2 text-sm md:grid-cols-6">
                <div><label class="label">الاسم *</label><input v-model="userForm.name" class="input"></div>
                <div><label class="label">البريد *</label><input v-model="userForm.email" type="email" class="input" dir="ltr"></div>
                <div><label class="label">الدور</label>
                    <select v-model="userForm.role" class="input">
                        <option v-for="r in roles" :key="r" :value="r">{{ roleAr[r] ?? r }}</option>
                    </select></div>
                <div><label class="label">الفرع</label>
                    <select v-model="userForm.branch_id" class="input">
                        <option value="">بدون فرع</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select></div>
                <div><label class="label">كلمة مرور *</label><input v-model="userForm.password" type="password" class="input" dir="ltr"></div>
                <div><button class="btn-primary">+ مستخدم</button></div>
            </form>
            <p v-for="(e, k) in userForm.errors" :key="k" class="mt-1 text-xs text-rose-600">{{ e }}</p>
        </div>
    </AppLayout>
</template>
