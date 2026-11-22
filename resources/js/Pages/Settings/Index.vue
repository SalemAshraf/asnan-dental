<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ clinic: Object, branches: Array, users: Array, roles: Array });
const roleAr = { owner: 'مالك', dentist: 'طبيب', receptionist: 'استقبال', assistant: 'مساعد' };

const clinicForm = useForm({
    name: props.clinic.name, phone: props.clinic.phone ?? '', address: props.clinic.address ?? '',
    tax_rate_bp: props.clinic.tax_rate_bp, invoice_prefix: props.clinic.invoice_prefix,
});
const branchForm = useForm({ name: '', address: '', phone: '' });
const userForm = useForm({ name: '', email: '', phone: '', role: 'dentist', branch_id: '', password: '' });
const toggle = (id) => useForm({}).post(`/settings/users/${id}/toggle`, { preserveScroll: true });
</script>

<template>
    <AppLayout>
        <h1 class="text-2xl font-bold mb-4">إعدادات العيادة</h1>

        <form @submit.prevent="clinicForm.put('/settings/clinic')" class="bg-white rounded-xl shadow-sm p-4 mb-4 grid md:grid-cols-3 gap-3 text-sm">
            <div><label class="text-xs text-gray-500">اسم العيادة</label>
                <input v-model="clinicForm.name" class="w-full border rounded px-2 py-1"></div>
            <div><label class="text-xs text-gray-500">الهاتف</label>
                <input v-model="clinicForm.phone" class="w-full border rounded px-2 py-1" dir="ltr"></div>
            <div><label class="text-xs text-gray-500">العنوان</label>
                <input v-model="clinicForm.address" class="w-full border rounded px-2 py-1"></div>
            <div><label class="text-xs text-gray-500">نسبة الضريبة (نقاط أساس، 1400 = 14%)</label>
                <input v-model="clinicForm.tax_rate_bp" type="number" min="0" max="10000" class="w-full border rounded px-2 py-1" dir="ltr"></div>
            <div><label class="text-xs text-gray-500">بادئة الفواتير</label>
                <input v-model="clinicForm.invoice_prefix" class="w-full border rounded px-2 py-1" dir="ltr"></div>
            <div class="self-end"><button class="bg-teal-700 text-white rounded px-4 py-1.5">حفظ</button></div>
        </form>

        <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
            <h2 class="font-bold mb-2">الفروع</h2>
            <ul class="text-sm mb-3">
                <li v-for="b in branches" :key="b.id" class="border-b py-1">{{ b.name }} <span class="text-gray-400">{{ b.address }}</span></li>
            </ul>
            <form @submit.prevent="branchForm.post('/settings/branches', { onSuccess: () => branchForm.reset() })" class="flex gap-2 text-sm">
                <input v-model="branchForm.name" placeholder="اسم الفرع *" class="border rounded px-2 py-1">
                <input v-model="branchForm.address" placeholder="العنوان" class="border rounded px-2 py-1 flex-1">
                <button class="bg-gray-800 text-white rounded px-3 py-1">+ فرع</button>
            </form>
            <p v-if="branchForm.errors.name" class="text-red-600 text-xs mt-1">{{ branchForm.errors.name }}</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-4">
            <h2 class="font-bold mb-2">المستخدمون</h2>
            <table class="w-full text-sm mb-3">
                <thead class="text-gray-500"><tr><th class="text-right py-1">الاسم</th><th class="text-right">البريد</th><th class="text-right">الدور</th><th class="text-right">الحالة</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="u in users" :key="u.id" class="border-t">
                        <td class="py-1">{{ u.name }}</td>
                        <td dir="ltr">{{ u.email }}</td>
                        <td>{{ roleAr[u.role] }}</td>
                        <td>{{ u.is_active ? 'نشط' : 'موقوف' }}</td>
                        <td><button @click="toggle(u.id)" class="text-xs border rounded px-2 py-0.5">{{ u.is_active ? 'إيقاف' : 'تفعيل' }}</button></td>
                    </tr>
                </tbody>
            </table>
            <form @submit.prevent="userForm.post('/settings/users', { onSuccess: () => userForm.reset() })" class="grid md:grid-cols-6 gap-2 text-sm items-end">
                <input v-model="userForm.name" placeholder="الاسم *" class="border rounded px-2 py-1">
                <input v-model="userForm.email" type="email" placeholder="البريد *" class="border rounded px-2 py-1" dir="ltr">
                <select v-model="userForm.role" class="border rounded px-2 py-1">
                    <option v-for="r in roles" :key="r" :value="r">{{ roleAr[r] }}</option>
                </select>
                <select v-model="userForm.branch_id" class="border rounded px-2 py-1">
                    <option value="">بدون فرع</option>
                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                </select>
                <input v-model="userForm.password" type="password" placeholder="كلمة مرور *" class="border rounded px-2 py-1" dir="ltr">
                <button class="bg-gray-800 text-white rounded px-3 py-1.5">+ مستخدم</button>
            </form>
            <p v-for="(e, k) in userForm.errors" :key="k" class="text-red-600 text-xs mt-1">{{ e }}</p>
        </div>
    </AppLayout>
</template>
