<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/Badge.vue';
import Pager from '@/Components/Pager.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({ logs: Object });
const actionAr = { created: 'إنشاء', updated: 'تعديل', deleted: 'حذف', login: 'دخول', login_failed: 'محاولة دخول فاشلة', invoice_issued: 'إصدار فاتورة', payment_received: 'استلام دفعة', consent_recorded: 'تسجيل موافقة' };
const tone = (a) => a === 'deleted' || a === 'login_failed' ? 'rose' : a === 'created' ? 'green' : a === 'payment_received' ? 'teal' : 'slate';
</script>

<template>
    <AppLayout>
        <div class="mb-5">
            <h1 class="text-2xl font-bold text-ink-900">سجل التدقيق</h1>
            <p class="text-sm text-slate-500">سجل غير قابل للتعديل بكل الإجراءات الحساسة — الحقول الحساسة مستبعدة</p>
        </div>
        <div class="card !p-0 overflow-hidden">
            <EmptyState v-if="!logs.data.length" icon="audit" title="لا توجد سجلات بعد" />
            <table v-else class="stack w-full">
                <thead class="border-b border-slate-200 bg-slate-50">
                    <tr>
                        <th class="th">الوقت</th><th class="th">المستخدم</th>
                        <th class="th">الإجراء</th><th class="th">الكيان</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="l in logs.data" :key="l.id">
                        <td class="td tabular-nums text-slate-500" dir="ltr" data-label="الوقت">{{ l.created_at }}</td>
                        <td class="td" data-label="المستخدم">#{{ l.user_id ?? '—' }}</td>
                        <td class="td" data-label="الإجراء"><Badge :tone="tone(l.action)">{{ actionAr[l.action] ?? l.action }}</Badge></td>
                        <td class="td text-slate-500" dir="ltr" data-label="الكيان">{{ l.auditable_type?.split('\\').pop() }} #{{ l.auditable_id }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4" v-if="logs.links"><Pager :links="logs.links" /></div>
    </AppLayout>
</template>
