<template>
    <header class="bg-white border-b border-slate-200 sticky top-0 z-50 px-4 sm:px-6 py-3 shadow-xs">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <!-- Brand & Info -->
            <div class="flex items-center space-x-3">
                <Link href="/admin" class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-black text-white text-base shadow-sm shadow-blue-500/20 shrink-0 hover:opacity-95 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                </Link>
                <div>
                    <div class="flex items-center space-x-2">
                        <h1 class="text-base font-black text-slate-900 leading-tight tracking-tight">{{ title }}</h1>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wider">
                            ADMIN PORTAL
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 hidden sm:block">{{ subtitle }}</p>
                </div>
            </div>

            <!-- Unified Persistent Navigation Tabs -->
            <nav class="flex items-center flex-wrap gap-1.5 overflow-x-auto pb-1 lg:pb-0">
                <Link 
                    v-for="item in navItems" 
                    :key="item.path"
                    :href="item.path"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 whitespace-nowrap',
                        $page.url.startsWith(item.path) && (item.path !== '/admin' || $page.url === '/admin')
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 bg-slate-50/80 border border-slate-200/80'
                    ]"
                >
                    <span>{{ item.icon }}</span>
                    <span>{{ item.label }}</span>
                </Link>

                <div class="h-4 w-px bg-slate-200 mx-1 hidden lg:block"></div>

                <Link 
                    href="/" 
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition flex items-center space-x-1"
                >
                    <span>🏠</span>
                    <span>Khách</span>
                </Link>

                <Link 
                    href="/driver" 
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition flex items-center space-x-1"
                >
                    <span>🚍</span>
                    <span>Tài xế</span>
                </Link>

                <Link 
                    href="/conductor" 
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition flex items-center space-x-1"
                >
                    <span>📋</span>
                    <span>Lơ xe</span>
                </Link>

                <Link 
                    href="/logout" 
                    method="post" 
                    as="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-600 hover:bg-rose-50 border border-rose-200 transition flex items-center space-x-1"
                >
                    <span>🚪</span>
                    <span>Đăng xuất</span>
                </Link>
            </nav>
        </div>
        <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</header>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AiChatModal from './AiChatModal.vue';

defineProps({
    title: {
        type: String,
        default: 'BusHub Terminal Management'
    },
    subtitle: {
        type: String,
        default: 'Quản trị điều hành, lịch trình, xe và doanh thu'
    }
});

const navItems = [
    { label: 'Tổng quan', path: '/admin', icon: '📊' },
    { label: 'Chuyến xe', path: '/admin/trips', icon: '🚍' },
    { label: 'Tuyến đường', path: '/admin/routes', icon: '🗺️' },
    { label: 'Đội xe', path: '/admin/buses', icon: '🚌' },
    { label: 'Tài xế', path: '/admin/drivers', icon: '👨‍✈️' },
    { label: 'Tài chính', path: '/admin/finance', icon: '💰' },
    { label: 'Khách hàng', path: '/admin/users', icon: '👥' },
];
</script>
