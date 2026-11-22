<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PlatformLayout from '@/Layouts/PlatformLayout.vue';

const props = defineProps({ plans: Array });

const empty = {
    slug: '', name_ar: '', name_en: '', blurb_ar: '', blurb_en: '',
    price: '', price_yearly: '', currency: 'EGP', period: 'monthly', trial_days: '',
    max_users: '', max_patients: '', features_ar_text: '', features_en_text: '',
    is_public: false, is_active: true, signup_default: false, sort: 0,
};
const editing = ref(null);
const form = useForm({ ...empty });

const edit = (p) => {
    editing.value = p.id;
    form.defaults({
        ...empty,
        ...p,
        price: p.price ?? '', price_yearly: p.price_yearly ?? '', trial_days: p.trial_days ?? '',
        max_users: p.max_users ?? '', max_patients: p.max_patients ?? '',
        features_ar_text: (p.features_ar || []).join('\n'),
        features_en_text: (p.features_en || []).join('\n'),
    });
    form.reset();
};
const fresh = () => { editing.value = null; form.defaults({ ...empty }); form.reset(); };

const submit = () => {
    form.transform((d) => ({
        ...d,
        price: d.price === '' ? null : d.price,
        price_yearly: d.price_yearly === '' ? null : d.price_yearly,
        trial_days: d.trial_days === '' ? null : d.trial_days,
        max_users: d.max_users === '' ? null : d.max_users,
        max_patients: d.max_patients === '' ? null : d.max_patients,
        features_ar: d.features_ar_text.split('\n').map((x) => x.trim()).filter(Boolean),
        features_en: d.features_en_text.split('\n').map((x) => x.trim()).filter(Boolean),
    }));
    if (editing.value) {
        form.put(`/platform/plans/${editing.value}`);
    } else {
        form.post('/platform/plans', { onSuccess: () => fresh() });
    }
};
</script>

<template>
    <Head title="الباقات — لوحة المنصة" />
    <PlatformLayout title="الباقات">
        <div class="grid items-start gap-6 lg:grid-cols-2">
            <div class="space-y-3">
                <p v-if="!plans.length" class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500 shadow-sm">لا توجد باقات بعد. الباقة لا تظهر في صفحة الأسعار إلا عند تفعيل «عامة». السعر الفارغ يظهر «السعر عند التواصل».</p>
                <div v-for="p in plans" :key="p.id" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-bold">{{ p.name_ar }} <span class="text-sm font-normal text-slate-400">{{ p.name_en }}</span></p>
                            <p class="text-xs text-slate-400" dir="ltr">{{ p.slug }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="p.is_public" class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">عامة</span>
                            <span v-if="!p.is_active" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">موقوفة</span>
                            <span v-if="p.signup_default" class="rounded-full bg-sky-100 px-2 py-0.5 text-xs text-sky-800">افتراضية</span>
                            <button class="btn-secondary btn-sm" @click="edit(p)">تعديل</button>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">{{ p.price !== null ? `${p.price} ${p.currency || ''} / ${p.period === 'yearly' ? 'سنة' : 'شهر'}` : 'السعر عند التواصل' }}</p>
                </div>
            </div>

            <form class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                <h2 class="font-bold">{{ editing ? 'تعديل باقة' : 'باقة جديدة' }}</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div><label class="label">Slug (إنجليزي) *</label><input v-model="form.slug" dir="ltr" class="input" required></div>
                    <div><label class="label">الترتيب</label><input v-model="form.sort" type="number" min="0" dir="ltr" class="input"></div>
                    <div><label class="label">الاسم بالعربي *</label><input v-model="form.name_ar" class="input" required></div>
                    <div><label class="label">الاسم بالإنجليزي *</label><input v-model="form.name_en" dir="ltr" class="input" required></div>
                    <div class="col-span-2"><label class="label">وصف عربي</label><input v-model="form.blurb_ar" class="input"></div>
                    <div class="col-span-2"><label class="label">وصف إنجليزي</label><input v-model="form.blurb_en" dir="ltr" class="input"></div>
                    <div><label class="label">السعر الشهري (فارغ = عند التواصل)</label><input v-model="form.price" type="number" min="0" step="0.01" dir="ltr" class="input"></div>
                    <div><label class="label">السعر السنوي</label><input v-model="form.price_yearly" type="number" min="0" step="0.01" dir="ltr" class="input"></div>
                    <div><label class="label">العملة</label><input v-model="form.currency" maxlength="3" dir="ltr" class="input"></div>
                    <div><label class="label">الفترة</label><select v-model="form.period" class="input"><option value="monthly">شهري</option><option value="yearly">سنوي</option></select></div>
                    <div><label class="label">أيام التجربة</label><input v-model="form.trial_days" type="number" min="0" dir="ltr" class="input"></div>
                    <div><label class="label">حد المستخدمين</label><input v-model="form.max_users" type="number" min="0" dir="ltr" class="input"></div>
                    <div class="col-span-2"><label class="label">حد المرضى</label><input v-model="form.max_patients" type="number" min="0" dir="ltr" class="input"></div>
                    <div><label class="label">المزايا بالعربي (سطر لكل ميزة)</label><textarea v-model="form.features_ar_text" rows="4" class="input"></textarea></div>
                    <div><label class="label">المزايا بالإنجليزي</label><textarea v-model="form.features_en_text" rows="4" dir="ltr" class="input"></textarea></div>
                </div>
                <div class="mt-3 flex flex-wrap gap-4 text-sm">
                    <label class="flex items-center gap-2"><input v-model="form.is_public" type="checkbox"> عامة (تظهر في صفحة الأسعار)</label>
                    <label class="flex items-center gap-2"><input v-model="form.is_active" type="checkbox"> نشطة</label>
                    <label class="flex items-center gap-2"><input v-model="form.signup_default" type="checkbox"> افتراضية عند التسجيل</label>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" :disabled="form.processing" class="btn-primary flex-1">{{ editing ? 'حفظ التعديل' : 'إنشاء الباقة' }}</button>
                    <button v-if="editing" type="button" class="btn-secondary" @click="fresh">إلغاء</button>
                </div>
                <p v-if="Object.keys(form.errors).length" class="mt-2 text-xs text-rose-600" dir="ltr">{{ Object.values(form.errors)[0] }}</p>
            </form>
        </div>
    </PlatformLayout>
</template>
