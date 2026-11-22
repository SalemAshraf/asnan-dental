<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ logs: Object });
const actionAr = { created: 'إنشاء', updated: 'تعديل', deleted: 'حذف', login: 'دخول', login_failed: 'محاولة دخول فاشلة', invoice_issued: 'إصدار فاتورة', payment_received: 'استلام دفعة', consent_recorded: 'تسجيل موافقة' };
</script>

<template>
    <AppLayout>
        <h1 class="text-2xl font-bold mb-4">سجل التدقيق</h1>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500"><tr>
                    <th class="p-3 text-right">الوقت</th><th class="text-right">المستخدم</th>
                    <th class="text-right">الإجراء</th><th class="text-right">الكيان</th>
                </tr></thead>
                <tbody>
                    <tr v-for="l in logs.data" :key="l.id" class="border-t">
                        <td class="p-3" dir="ltr">{{ l.created_at }}</td>
                        <td>#{{ l.user_id ?? '—' }}</td>
                        <td>{{ actionAr[l.action] ?? l.action }}</td>
                        <td class="text-gray-500" dir="ltr">{{ l.auditable_type?.split('\\').pop() }} #{{ l.auditable_id }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
