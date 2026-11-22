<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const nav = [
    { href: '/', label: 'الرئيسية' },
    { href: '/patients', label: 'المرضى' },
    { href: '/appointments', label: 'المواعيد' },
    { href: '/invoices', label: 'الفواتير' },
    { href: '/services', label: 'الخدمات' },
];
const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen">
        <header class="bg-teal-800 text-white shadow">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-6">
                    <Link href="/" class="text-xl font-bold">🦷 أسنان</Link>
                    <nav class="hidden md:flex gap-4 text-sm">
                        <Link v-for="item in nav" :key="item.href" :href="item.href"
                            class="hover:text-teal-200 px-2 py-1 rounded"
                            :class="{ 'bg-teal-700': $page.url === item.href }">{{ item.label }}</Link>
                        <Link v-if="user?.role === 'owner'" href="/settings" class="hover:text-teal-200 px-2 py-1">الإعدادات</Link>
                        <Link v-if="user?.role === 'owner'" href="/audit-log" class="hover:text-teal-200 px-2 py-1">سجل التدقيق</Link>
                    </nav>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span>{{ user?.name }}</span>
                    <button @click="logout" class="bg-teal-700 hover:bg-teal-600 rounded px-3 py-1">خروج</button>
                </div>
            </div>
        </header>
        <main class="max-w-7xl mx-auto px-4 py-6">
            <slot />
        </main>
    </div>
</template>
