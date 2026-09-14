<template>
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-xs">
    <!-- Top Micro Bar -->
    <div class="bg-slate-900 text-slate-300 text-[11px] font-medium py-1.5 px-4 sm:px-8">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-6">
          <span class="flex items-center gap-1.5 text-slate-300">
            <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            Tổng đài CSKH 24/7: <strong class="text-amber-400 font-bold">1900 8899</strong>
          </span>
          <span class="text-slate-700 hidden sm:inline">|</span>
          <span class="text-slate-400 hidden sm:inline">BusHub Terminal Vietnam • Hành Trình Thông Minh & Đẳng Cấp</span>
        </div>

        <!-- Fast Login Buttons for Testing -->
        <div class="flex items-center space-x-1.5 sm:space-x-2">
          <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider mr-1 hidden md:inline">⚡ Vào nhanh:</span>
          
          <button
            type="button"
            @click="fastLogin('admin')"
            class="px-2.5 py-1 rounded-lg bg-blue-600/20 hover:bg-blue-600/30 border border-amber-400/30 text-amber-300 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
          >
            <span>👑</span>
            <span>Admin</span>
          </button>

          <button
            type="button"
            @click="fastLogin('driver')"
            class="px-2.5 py-1 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/30 text-blue-300 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
          >
            <span>🚌</span>
            <span>Tài Xế</span>
          </button>

          <button
            type="button"
            @click="fastLogin('staff')"
            class="px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-400/30 text-emerald-300 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
          >
            <span>🎫</span>
            <span>Lơ Xe</span>
          </button>

          <span class="text-slate-700 hidden lg:inline">|</span>
          <Link href="/schedules" class="hover:text-white transition hidden lg:inline font-semibold">Lịch trình tuyến</Link>
          <button type="button" @click="openAiChat" class="px-2.5 py-1 rounded-lg bg-indigo-500/30 hover:bg-indigo-500/40 border border-indigo-400/40 text-indigo-200 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"><span>✨</span><span>Trợ lý AI</span></button>
        </div>
      </div>
    </div>

    <!-- Main Navigation Bar -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16 sm:h-20">
        
        <!-- Brand Logo: BusHub Terminal -->
        <div class="flex items-center space-x-8">
          <Link href="/" class="flex items-center space-x-3 group">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-800 via-blue-600 to-cyan-500 text-white flex items-center justify-center font-black text-xl shadow-md shadow-blue-600/25 group-hover:scale-105 transition-transform duration-300">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 7h8m-8 4h8m-8 4h4m5 4H7a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v13a2 2 0 01-2 2z"/><circle cx="8" cy="18" r="1.5" fill="currentColor"/><circle cx="16" cy="18" r="1.5" fill="currentColor"/></svg>
            </div>
            <div class="flex flex-col">
              <div class="flex items-center gap-1.5">
                <span class="font-black text-xl tracking-tight bg-gradient-to-r from-blue-900 via-blue-700 to-indigo-800 bg-clip-text text-transparent leading-none">BusHub</span>
                <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md">EXPRESS</span>
              </div>
              <span class="text-[10px] font-bold tracking-wider text-slate-500 uppercase mt-0.5">HÀNH TRÌNH THÔNG MINH & ĐẲNG CẤP</span>
            </div>
          </Link>

          <!-- Main Nav Links -->
          <nav class="hidden lg:flex items-center space-x-1 text-sm font-bold text-slate-700">
            <Link href="/" class="px-3.5 py-2 rounded-xl hover:text-blue-600 hover:bg-blue-50/70 transition" :class="$page.url === '/' ? 'text-blue-600 bg-blue-50/80 font-extrabold' : ''">Trang chủ</Link>
            <Link href="/schedules" class="px-3.5 py-2 rounded-xl hover:text-blue-600 hover:bg-blue-50/70 transition" :class="$page.url.startsWith('/schedules') ? 'text-blue-600 bg-blue-50/80 font-extrabold' : ''">Lịch trình</Link>
            <Link href="/trips" class="px-3.5 py-2 rounded-xl hover:text-blue-600 hover:bg-blue-50/70 transition" :class="$page.url.startsWith('/trips') ? 'text-blue-600 bg-blue-50/80 font-extrabold' : ''">Tìm chuyến xe</Link>
            <Link href="/scanner" class="px-3.5 py-2 rounded-xl hover:text-blue-600 hover:bg-blue-50/70 transition">Soát vé QR</Link>
          </nav>
        </div>

        <!-- Right: Portals & Auth Button -->
        <div class="flex items-center space-x-2 sm:space-x-3">
          <!-- Portals Quick Links -->
          <div class="hidden xl:flex items-center space-x-1.5 text-xs font-semibold">
            <Link href="/driver" class="px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-blue-50 hover:text-blue-700 text-slate-600 transition flex items-center gap-1.5 border border-slate-200/80">
              <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              Bác tài
            </Link>
            <Link href="/conductor" class="px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-indigo-50 hover:text-indigo-700 text-slate-600 transition flex items-center gap-1.5 border border-slate-200/80">
              <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
              Lơ xe
            </Link>
            <Link href="/admin" class="px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-amber-50 hover:text-amber-700 text-slate-600 transition flex items-center gap-1.5 border border-slate-200/80">
              <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              Admin
            </Link>
          </div>

          <!-- Auth Account Button -->
          <button
            @click="handleAuthClick"
            type="button"
            class="px-4 py-2.5 rounded-2xl border border-slate-200 hover:border-blue-600 bg-white hover:bg-blue-50/40 text-slate-800 text-xs sm:text-sm font-bold transition flex items-center gap-2 cursor-pointer shadow-xs"
          >
            <span class="w-2 h-2 rounded-full" :class="$page.props.auth?.user ? 'bg-emerald-500' : 'bg-blue-600'"></span>
            <span>{{ $page.props.auth?.user?.name || 'Đăng nhập / Đăng ký' }}</span>
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </button>
        </div>

      </div>
    </div>

    <!-- Modals & Drawers -->
    <AuthModal :isOpen="showAuthModal" v-model="showAuthModal" @close="showAuthModal = false" />
    <AccountDrawer
      :isOpen="showAccountDrawer"
      v-model="showAccountDrawer"
      :user="$page.props.auth?.user"
      @openLogin="showAccountDrawer = false; showAuthModal = true"
    />

    <!-- Floating AI Assistant & Quick Role Switcher -->
    <AiChatModal />
    <QuickRoleSwitcher />
  </header>
</template>

<script setup>
import { ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import AuthModal from '@/Components/AuthModal.vue'
import AccountDrawer from '@/Components/AccountDrawer.vue'
import QuickRoleSwitcher from '@/Components/QuickRoleSwitcher.vue'
import AiChatModal from '@/Components/AiChatModal.vue'

const page = usePage()
const showAuthModal = ref(false)
const showAccountDrawer = ref(false)

const handleAuthClick = () => {
  if (page.props.auth?.user) {
    showAccountDrawer.value = true
  } else {
    showAuthModal.value = true
  }
}

const openAiChat = () => {
  window.dispatchEvent(new CustomEvent('open-bushub-ai-chat'))
}

const fastLogin = (role) => {
  router.post('/auth/fast-login', { role })
}
</script>
