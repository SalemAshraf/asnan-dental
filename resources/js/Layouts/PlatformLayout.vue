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
                <span class="flex shrink-0 items-center gap-2 font-bold">
                    <img src="/brand/taqwim-lockup-light.svg" alt="تقويم" class="h-8 w-auto">
                    <span class="hidden text-slate-500 sm:inline">لوحة المنصة</span>
                </span>
                <nav class="flex items-center gap-1 overflow-x-auto text-sm [&>*]:shrink-0">
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
