<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { i18n, toggleLocale } from '@/i18n';

const props = defineProps({ rid: String, blocked: String, app: String, redirectHost: String, user: Object, clinic: String });
const busy = ref(false);
const send = (decision) => { busy.value = true; router.post('/oauth/authorize', { rid: props.rid, decision }, { onFinish: () => (busy.value = false) }); };

const C = {
    ar: {
        title: 'ربط Claude بعيادتك',
        sub: 'تطبيق خارجي يطلب إذن قراءة بيانات عيادتك',
        appName: 'التطبيق', from: 'جهة الرد', as: 'بحساب', clinicL: 'العيادة',
        reads: 'اللي هيقدر يشوفه: مواعيد العيادة وحالتها، المتابعات المستحقة، أسماء وهواتف المرضى، وإجمالي الفواتير والتحصيل. مش هيقدر يشوف خطط علاج أو تاريخ طبي أو أي بيانات سريرية، ومش هيقدر يضيف أو يعدّل أو يمسح أي حاجة.',
        egress: 'البيانات دي هتخرج من تقويم لحسابك في Claude. وصّل بس لو أنت اللي طلبت الربط من Claude نفسه.',
        manage: 'تقدر تفصل الربط في أي وقت من إعدادات العيادة.',
        allow: 'سماح بالربط', deny: 'رفض',
        notOwner: 'الربط متاح لمالك العيادة فقط. سجّل الدخول بحساب المالك وأعد المحاولة من Claude.',
        aiOff: 'ربط Claude مقفول من إعدادات العيادة. فعّله أولًا من الإعدادات ثم أعد المحاولة.',
        settings: 'فتح الإعدادات',
    },
    en: {
        title: 'Connect Claude to your clinic',
        sub: 'An external app is asking to read your clinic data',
        appName: 'App', from: 'Redirect host', as: 'Signed in as', clinicL: 'Clinic',
        reads: 'What it can see: the clinic schedule and statuses, due recalls, patient names and phones, and invoice and collection totals. It cannot see treatment plans, medical history or any clinical data, and it cannot add, change or delete anything.',
        egress: 'This data leaves Taqwim to your Claude account. Only connect if you started this from Claude yourself.',
        manage: 'You can disconnect any time from clinic settings.',
        allow: 'Allow connection', deny: 'Deny',
        notOwner: 'Only the clinic owner can connect. Log in with the owner account and retry from Claude.',
        aiOff: 'The Claude connection is off in clinic settings. Turn it on there, then retry.',
        settings: 'Open settings',
    },
};
const c = computed(() => C[i18n.locale] || C.ar);
</script>

<template>
    <Head :title="c.title" />
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-b from-brand-900 to-ink-900 px-4 py-10">
        <div class="w-full max-w-md">
            <div class="mb-3 flex justify-end">
                <button class="rounded-lg bg-white/10 px-3 py-1.5 text-sm font-semibold text-white hover:bg-white/20" @click="toggleLocale">{{ i18n.locale === 'ar' ? 'EN' : 'ع' }}</button>
            </div>
            <div class="rounded-2xl bg-white p-8 shadow-2xl">
                <div class="mb-5 text-center">
                    <div class="flex justify-center">
                        <img src="/brand/taqwim-lockup-slogan-light.svg" alt="تقويم" class="h-16 w-auto">
                    </div>
                    <h1 class="mt-4 text-xl font-bold text-ink-900">{{ c.title }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ c.sub }}</p>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ c.appName }}</dt><dd class="font-semibold">{{ app }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ c.from }}</dt><dd dir="ltr" class="font-mono text-xs">{{ redirectHost }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ c.as }}</dt><dd class="text-end">{{ user.name }}<br><span dir="ltr" class="text-xs text-slate-500">{{ user.email }}</span></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ c.clinicL }}</dt><dd class="font-semibold">{{ clinic }}</dd></div>
                </dl>
                <template v-if="blocked">
                    <p class="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">{{ blocked === 'not_owner' ? c.notOwner : c.aiOff }}</p>
                    <a v-if="blocked === 'ai_off'" href="/settings" class="btn-secondary mt-3 flex w-full justify-center">{{ c.settings }}</a>
                </template>
                <template v-else>
                    <p class="mt-5 text-sm leading-6 text-slate-700">{{ c.reads }}</p>
                    <p class="mt-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">{{ c.egress }}</p>
                    <p class="mt-2 text-xs text-slate-500">{{ c.manage }}</p>
                    <div class="mt-5 flex gap-2">
                        <button type="button" class="btn-primary flex-1" :disabled="busy" @click="send('allow')">{{ c.allow }}</button>
                        <button type="button" class="btn-secondary flex-1" :disabled="busy" @click="send('deny')">{{ c.deny }}</button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
