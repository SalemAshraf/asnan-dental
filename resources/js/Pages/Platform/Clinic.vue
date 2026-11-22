<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({ clinic: Object, subscription: Object, plans: Array });

const sub = useForm({
    plan_id: props.subscription?.plan_id || '',
    status: props.subscription?.status || 'active',
    current_period_end: props.subscription?.current_period_end || '',
    note: props.subscription?.note || '',
});
const suspend = useForm({});
const activate = useForm({});

const statusLabel = { trialing: 'تجربة', active: 'مفعّلة', past_due: 'متأخرة السداد', cancelled: 'ملغاة' };
</script>

<template>
    <Head :title="`${clinic.name} — لوحة المنصة`" />
    <PlatformLayout :title="clinic.name">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-bold">بيانات تجميعية</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">أنشئت</dt><dd>{{ clinic.created_at }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">المستخدمون</dt><dd>{{ clinic.users }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">المرضى (عدد فقط)</dt><dd>{{ clinic.patients }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">المواعيد (عدد فقط)</dt><dd>{{ clinic.appointments }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">الحالة</dt><dd>{{ clinic.suspended ? 'موقوفة' : 'تعمل' }}</dd></div>
                </dl>
                <div class="mt-5 border-t border-slate-100 pt-4">
                    <button v-if="!clinic.suspended" class="btn-secondary w-full !border-rose-300 !text-rose-700 hover:!bg-rose-50" @click="suspend.patch(`/platform/clinics/${clinic.id}/suspend`)">إيقاف العيادة (تسجيل خروج كل مستخدميها)</button>
                    <button v-else class="btn-primary w-full" @click="activate.patch(`/platform/clinics/${clinic.id}/activate`)">إعادة تفعيل العيادة</button>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="font-bold">الاشتراك</h2>
                <p v-if="subscription?.trial_ends_at" class="mt-2 text-sm text-slate-500">تنتهي التجربة: {{ subscription.trial_ends_at }}</p>
                <form class="mt-4 space-y-3" @submit.prevent="sub.put(`/platform/clinics/${clinic.id}/subscription`)">
                    <div>
                        <label class="label">الباقة</label>
                        <select v-model="sub.plan_id" class="input">
                            <option value="">بدون باقة</option>
                            <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name_ar }} ({{ p.slug }})</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">الحالة</label>
                        <select v-model="sub.status" class="input">
                            <option v-for="(l, k) in statusLabel" :key="k" :value="k">{{ l }}</option>
                        </select>
                        <p class="mt-1 text-xs text-slate-400">«مفعّلة» تلغي قاعدة التجربة نهائيًا لهذه العيادة.</p>
                    </div>
                    <div>
                        <label class="label">نهاية الفترة الحالية</label>
                        <input v-model="sub.current_period_end" type="date" dir="ltr" class="input">
                    </div>
                    <div>
                        <label class="label">ملاحظة داخلية</label>
                        <textarea v-model="sub.note" rows="2" class="input"></textarea>
                    </div>
                    <button type="submit" :disabled="sub.processing" class="btn-primary w-full">حفظ الاشتراك</button>
                </form>
            </div>
        </div>
    </PlatformLayout>
</template>
