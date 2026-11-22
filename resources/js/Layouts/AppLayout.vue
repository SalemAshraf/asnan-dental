<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Icon from '@/Components/Icon.vue';
import { i18n, t, toggleLocale } from '@/i18n';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const clinic = computed(() => page.props.auth?.clinic);
const flash = computed(() => page.props.flash?.success);
const flashErrorRaw = computed(() => page.props.flash?.error);
const flashError = computed(() => {
    const v = flashErrorRaw.value;
    if (v === 'read_only') return t('sub.readOnlyFlash');
    if (v === 'clinic_suspended') return t('sub.suspendedFlash');
    return v;
});
const sub = computed(() => page.props.auth?.subscription);
const salesWa = computed(() => page.props.platform?.sales_whatsapp);

const open = ref(false);
const collapsed = ref(typeof localStorage !== 'undefined' && localStorage.getItem('nav_collapsed') === '1');
const toggleNav = () => { collapsed.value = !collapsed.value; localStorage.setItem('nav_collapsed', collapsed.value ? '1' : '0'); };

const groups = computed(() => {
    const g = [
        { label: t('nav.groups.clinic'), items: [
            { href: '/dashboard', label: t('nav.dashboard'), icon: 'dashboard', exact: true },
            { href: '/patients', label: t('nav.patients'), icon: 'patients' },
            { href: '/appointments', label: t('nav.appointments'), icon: 'appointments' },
            { href: '/recalls', label: t('nav.recalls'), icon: 'clock' },
            { href: '/services', label: t('nav.services'), icon: 'services' },
        ] },
        { label: t('nav.groups.finance'), items: [
            { href: '/invoices', label: t('nav.invoices'), icon: 'invoices' },
        ] },
    ];
    if (user.value?.role === 'owner') {
        g.push({ label: t('nav.groups.admin'), items: [
            { href: '/reports', label: t('nav.reports'), icon: 'reports' },
            { href: '/settings', label: t('nav.settings'), icon: 'settings' },
            { href: '/audit-log', label: t('nav.audit'), icon: 'audit' },
        ] });
    }
    return g;
});

const url = computed(() => page.url.split('?')[0]);
const active = (item) => item.exact ? url.value === item.href : (url.value === item.href || url.value.startsWith(item.href + '/'));

const q = ref('');
const goSearch = () => router.get('/patients', { q: q.value || undefined });
const logout = () => router.post('/logout');
const initials = computed(() => (user.value?.name || '?').trim().split(/\s+/).slice(0, 2).map((x) => x[0]).join(''));
const roleAr = { owner: 'مالك العيادة', dentist: 'طبيب', receptionist: 'استقبال', assistant: 'مساعد' };
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink-800 lg:flex">
        <aside class="fixed inset-y-0 z-30 flex w-72 flex-col border-e border-slate-200 bg-white shadow-xl transition-[transform,width] duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shadow-none"
            :class="[open ? 'translate-x-0' : 'rtl:translate-x-full rtl:lg:translate-x-0', collapsed ? 'lg:w-[76px]' : 'lg:w-64']" style="inset-inline-start: 0">
            <div class="flex h-16 shrink-0 items-center justify-between gap-2 px-4" :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
                <Link href="/dashboard" class="flex min-w-0 items-center gap-3 text-lg font-bold text-ink-900" title="بسمة">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-700 text-white shadow">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C9.5 2 8.8 3.4 7.2 3.4 5 3.4 3.5 5.6 3.5 8c0 3.6 1.7 4.7 2.1 7.4.3 2 .7 6.6 2.6 6.6 1.7 0 1.2-3.4 1.7-5.5.3-1.3.9-2 2.1-2s1.8.7 2.1 2c.5 2.1 0 5.5 1.7 5.5 1.9 0 2.3-4.6 2.6-6.6.4-2.7 2.1-3.8 2.1-7.4 0-2.4-1.5-4.6-3.7-4.6-1.6 0-2.3-1.4-4.8-1.4z"/></svg>
                    </span>
                    <span class="truncate" :class="collapsed ? 'lg:hidden' : ''">بسمة</span>
                </Link>
                <button class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden" :aria-label="t('nav.close')" @click="open = false"><Icon name="close" :size="20" /></button>
            </div>
            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 pb-4" :class="collapsed ? 'lg:px-2' : ''">
                <div v-for="(g, gi) in groups" :key="g.label" :class="gi && collapsed ? 'lg:mt-2 lg:border-t lg:border-slate-100 lg:pt-2' : ''">
                    <p class="mb-1 mt-4 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400" :class="collapsed ? 'lg:hidden' : ''">{{ g.label }}</p>
                    <Link v-for="n in g.items" :key="n.href" :href="n.href" @click="open = false" :title="n.label"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                        :class="[collapsed ? 'lg:justify-center lg:px-0' : '', active(n) ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-ink-900']">
                        <Icon :name="n.icon" :size="20" :class="active(n) ? 'text-white' : 'text-slate-400 group-hover:text-ink-800'" /><span class="truncate" :class="collapsed ? 'lg:hidden' : ''">{{ n.label }}</span>
                    </Link>
                </div>
            </nav>
            <button class="mx-3 mb-1 hidden items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-ink-900 lg:flex" :class="collapsed ? 'lg:justify-center lg:px-0' : ''" :aria-expanded="!collapsed" :aria-label="t('nav.collapse')" @click="toggleNav">
                <Icon name="menu" :size="20" /><span :class="collapsed ? 'lg:hidden' : ''">{{ t('nav.collapse') }}</span>
            </button>
            <div class="m-3 flex items-center gap-3 rounded-xl bg-slate-50 p-3" :class="collapsed ? 'lg:m-2 lg:justify-center lg:p-2' : ''">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-ink-900 text-sm font-bold text-brand-300">{{ initials }}</span>
                <span class="min-w-0 flex-1" :class="collapsed ? 'lg:hidden' : ''">
                    <span class="block truncate text-sm font-semibold text-ink-900">{{ user?.name }}</span>
                    <span class="block text-xs text-slate-500">{{ roleAr[user?.role] ?? user?.role }}</span>
                </span>
                <button @click="logout" class="rounded-lg p-1.5 text-slate-400 hover:bg-white hover:text-rose-600" :class="collapsed ? 'lg:hidden' : ''" :title="t('nav.logout')" :aria-label="t('nav.logout')"><Icon name="logout" :size="18" class="rtl:-scale-x-100" /></button>
            </div>
        </aside>
        <div v-if="open" class="fixed inset-0 z-20 bg-ink-900/40 backdrop-blur-[1px] lg:hidden" @click="open = false" />
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-10 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-canvas/90 px-4 backdrop-blur lg:px-8">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <button class="rounded-lg border border-slate-200 bg-white p-2 text-slate-600 hover:bg-slate-100 lg:hidden" :aria-label="t('nav.menu')" @click="open = !open"><Icon name="menu" :size="20" /></button>
                    <p class="hidden truncate text-sm font-semibold text-ink-800 md:block">{{ clinic?.name }}</p>
                    <form class="hidden max-w-md flex-1 md:block" @submit.prevent="goSearch">
                        <div class="relative">
                            <Icon name="search" :size="16" class="pointer-events-none absolute inset-y-0 start-3 my-auto text-slate-400" />
                            <input v-model="q" type="search" class="input !rounded-xl !ps-9" :placeholder="t('nav.search')" :aria-label="t('nav.search')" />
                        </div>
                    </form>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/appointments" class="btn-secondary btn-sm !py-2"><Icon name="appointments" :size="15" /><span class="hidden sm:inline">{{ t('nav.appointments') }}</span></Link>
                    <button @click="toggleLocale" class="btn-secondary btn-sm !py-2 !px-2.5 font-bold" :title="i18n.locale === 'ar' ? 'Switch to English' : 'التبديل إلى العربية'" :aria-label="i18n.locale === 'ar' ? 'Switch to English' : 'التبديل إلى العربية'">{{ i18n.locale === 'ar' ? 'EN' : 'ع' }}</button>
                    <Link href="/patients/create" class="btn-primary btn-sm !py-2"><Icon name="plus" :size="15" /><span class="hidden sm:inline">{{ t('nav.newPatient') }}</span></Link>
                </div>
            </header>
            <div v-if="sub?.read_only" class="mx-4 mt-4 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-rose-300 bg-rose-50 px-4 py-2 text-sm text-rose-900 lg:mx-8">
                <span>{{ t('sub.readOnlyBanner') }}</span>
                <a v-if="salesWa" :href="`https://wa.me/${salesWa}`" target="_blank" rel="noopener" class="font-bold underline">{{ t('sub.contactSales') }}</a>
            </div>
            <div v-else-if="sub?.status === 'trialing' && sub?.days_left !== null" class="mx-4 mt-4 rounded-lg border border-sky-200 bg-sky-50 px-4 py-2 text-sm text-sky-900 lg:mx-8">
                {{ t('sub.trialBanner', { days: sub.days_left }) }}
            </div>
            <p v-if="flashError" class="mx-4 mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-900 lg:mx-8">{{ flashError }}</p>
            <p v-if="flash" class="mx-4 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ flash }}</p>
            <main class="mx-auto w-full max-w-[1400px] flex-1 p-4 lg:p-8"><slot /></main>
        </div>
    </div>
</template>
