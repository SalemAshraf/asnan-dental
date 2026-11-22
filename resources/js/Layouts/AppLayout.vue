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
                    <Link href="/" class="text-xl font-bold"><svg class="inline-block w-6 h-6 -mt-1" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C9.5 2 8.8 3.4 7.2 3.4 5 3.4 3.5 5.6 3.5 8c0 3.6 1.7 4.7 2.1 7.4.3 2 .7 6.6 2.6 6.6 1.7 0 1.2-3.4 1.7-5.5.3-1.3.9-2 2.1-2s1.8.7 2.1 2c.5 2.1 0 5.5 1.7 5.5 1.9 0 2.3-4.6 2.6-6.6.4-2.7 2.1-3.8 2.1-7.4 0-2.4-1.5-4.6-3.7-4.6-1.6 0-2.3-1.4-4.8-1.4z"/></svg> أسنان</Link>
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
