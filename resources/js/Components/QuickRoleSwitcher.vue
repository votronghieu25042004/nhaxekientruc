<template>
  <Teleport to="body">
  <div class="fixed bottom-5 right-5 z-[99990] flex flex-col items-end space-y-2 select-none">
    <!-- Collapsible role panel -->
    <div
      v-if="isOpen"
      class="bg-slate-900/95 backdrop-blur-md border border-slate-700/80 rounded-3xl p-4 shadow-2xl text-white w-72 sm:w-80 transition-all animate-in fade-in zoom-in-95 duration-200"
    >
      <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
        <div class="flex items-center space-x-2">
          <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
          <span class="text-xs font-black uppercase tracking-wider text-amber-300">Đăng Nhập Nhanh 1 Chạm</span>
        </div>
        <button @click="isOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg text-xs font-bold">✕</button>
      </div>

      <p class="text-[11px] text-slate-400 mb-3">
        Chuyển quyền nhanh không cần gõ mật khẩu / OTP (Bật sẵn để tiện test):
      </p>

      <div class="space-y-2">
        <!-- Admin -->
        <button
          type="button"
          @click="fastLogin('admin')"
          class="w-full py-2.5 px-3 rounded-2xl bg-blue-600/20 hover:bg-blue-600/30 border border-amber-400/30 text-amber-200 hover:text-amber-100 flex items-center justify-between transition text-xs font-bold"
        >
          <div class="flex items-center space-x-2">
            <span class="w-6 h-6 rounded-lg bg-blue-600/30 flex items-center justify-center text-amber-300">👑</span>
            <span>Admin (Ban Điều Hành)</span>
          </div>
          <span class="text-[10px] bg-blue-600/30 px-1.5 py-0.5 rounded text-amber-300">/admin</span>
        </button>

        <!-- Driver -->
        <button
          type="button"
          @click="fastLogin('driver')"
          class="w-full py-2.5 px-3 rounded-2xl bg-blue-500/20 hover:bg-blue-500/30 border border-blue-400/30 text-blue-200 hover:text-blue-100 flex items-center justify-between transition text-xs font-bold"
        >
          <div class="flex items-center space-x-2">
            <span class="w-6 h-6 rounded-lg bg-blue-500/30 flex items-center justify-center text-blue-300">🚌</span>
            <span>Tài Xế (Bác Tài)</span>
          </div>
          <span class="text-[10px] bg-blue-500/30 px-1.5 py-0.5 rounded text-blue-300">/driver</span>
        </button>

        <!-- Conductor -->
        <button
          type="button"
          @click="fastLogin('staff')"
          class="w-full py-2.5 px-3 rounded-2xl bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-400/30 text-emerald-200 hover:text-emerald-100 flex items-center justify-between transition text-xs font-bold"
        >
          <div class="flex items-center space-x-2">
            <span class="w-6 h-6 rounded-lg bg-emerald-500/30 flex items-center justify-center text-emerald-300">🎫</span>
            <span>Lơ Xe / Tiếp Viên</span>
          </div>
          <span class="text-[10px] bg-emerald-500/30 px-1.5 py-0.5 rounded text-emerald-300">/conductor</span>
        </button>

        <!-- Customer -->
        <button
          type="button"
          @click="fastLogin('customer')"
          class="w-full py-2.5 px-3 rounded-2xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 flex items-center justify-between transition text-xs font-bold"
        >
          <div class="flex items-center space-x-2">
            <span class="w-6 h-6 rounded-lg bg-slate-700 flex items-center justify-center text-slate-300">👤</span>
            <span>Khách Hàng (Hành Khách)</span>
          </div>
          <span class="text-[10px] bg-slate-700 px-1.5 py-0.5 rounded text-slate-300">/</span>
        </button>
      </div>
    </div>

    <!-- Toggle Trigger Button -->
    <button
      type="button"
      @click="isOpen = !isOpen"
      class="flex items-center space-x-2 bg-gradient-to-r from-slate-900 to-blue-900 text-white hover:from-slate-800 hover:to-blue-800 py-2.5 px-4 rounded-full shadow-2xl border border-blue-500/30 text-xs font-black transition-all hover:scale-105 active:scale-95 group"
    >
      <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
      <span>⚡ Đăng Nhập Nhanh 3 Quyền</span>
      <svg class="w-4 h-4 text-slate-400 group-hover:text-white transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path v-if="!isOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
      </svg>
    </button>
  </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const isOpen = ref(false)

const fastLogin = (role) => {
  router.post('/auth/fast-login', { role }, {
    onSuccess: (page) => {
      // Handled by backend redirect
    }
  })
}
</script>
