<script setup>
import { Link, useForm } from '@inertiajs/vue3';

defineProps({ title: String });
const logout = useForm({});
const links = [
    { href: '/platform', label: 'الرئيسية', exact: true },
    { href: '/platform/clinics', label: 'العيادات' },
    { href: '/platform/plans', label: 'الباقات' },
    { href: '/platform/inquiries', label: 'طلبات التواصل' },
];
const isCurrent = (l) => (l.exact ? window.location.pathname === l.href : window.location.pathname.startsWith(l.href));
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink-900" dir="rtl">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-3 px-5 py-3">
                <span class="flex items-center gap-2 font-bold">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-ink-900 text-white">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C9.5 2 8.8 3.4 7.2 3.4 5 3.4 3.5 5.6 3.5 8c0 3.6 1.7 4.7 2.1 7.4.3 2 .7 6.6 2.6 6.6 1.7 0 1.2-3.4 1.7-5.5.3-1.3.9-2 2.1-2s1.8.7 2.1 2c.5 2.1 0 5.5 1.7 5.5 1.9 0 2.3-4.6 2.6-6.6.4-2.7 2.1-3.8 2.1-7.4 0-2.4-1.5-4.6-3.7-4.6-1.6 0-2.3-1.4-4.8-1.4z"/></svg>
                    </span>
                    تقويم — لوحة المنصة
                </span>
                <nav class="flex items-center gap-1 text-sm">
                    <Link v-for="l in links" :key="l.href" :href="l.href" class="rounded-lg px-3 py-1.5 font-medium" :class="isCurrent(l) ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-100'">{{ l.label }}</Link>
                    <button class="rounded-lg px-3 py-1.5 text-slate-500 hover:bg-slate-100" @click="logout.post('/platform/logout')">خروج</button>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-5xl px-5 py-8">
            <h1 v-if="title" class="mb-6 text-2xl font-bold">{{ title }}</h1>
            <slot />
        </main>
    </div>
</template>
