<template>
  <Teleport to="body">
    <div>
      <!-- Backdrop -->
      <div
        v-if="modelValue"
        @click="$emit('update:modelValue', false)"
        class="fixed inset-0 z-[99998] bg-black/50 backdrop-blur-sm transition-opacity"
      ></div>

      <!-- Slide-over Drawer -->
      <div
        class="fixed top-0 right-0 bottom-0 z-[99999] w-full max-w-sm bg-white shadow-2xl transition-transform duration-300 transform text-slate-800 flex flex-col justify-between"
        :class="modelValue ? 'translate-x-0' : 'translate-x-full'"
      >
        <div class="p-6 overflow-y-auto space-y-6">
          <!-- Header -->
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h2 class="text-base font-black text-slate-900">Tài khoản cá nhân</h2>
            <button @click="$emit('update:modelValue', false)" class="text-slate-400 hover:text-slate-700 text-xl">&times;</button>
          </div>

          <!-- User Profile Section -->
          <div v-if="user" class="space-y-4">
            <div class="flex items-center space-x-3 p-3 bg-slate-50 rounded-2xl border border-slate-100">
              <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-blue-600 to-rose-600 text-white flex items-center justify-center font-black text-lg shadow-sm">
                {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-bold text-slate-900 truncate">{{ user.name }}</div>
                <div class="text-xs text-slate-400 font-mono truncate">{{ user.email || user.phone }}</div>
                <div class="flex items-center gap-1.5 mt-1">
                  <span 
                    class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase"
                    :class="getTierBadgeClass(user.loyalty_tier)"
                  >
                    {{ getTierIcon(user.loyalty_tier) }} {{ getTierLabel(user.loyalty_tier) }}
                  </span>
                  <span class="px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold uppercase">
                    {{ user.role || 'Khách Hàng' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Loyalty Points Card (Requirement 2 & 3) -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-500/10 via-yellow-500/10 to-amber-500/5 border border-amber-300/80 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase text-amber-900 tracking-wider flex items-center gap-1.5">
                  <span>🪙</span>
                  <span>Điểm Thưởng Tích Lũy</span>
                </span>
                <span class="text-[10px] font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full font-mono">
                  100đ = 10.000đ
                </span>
              </div>
              <div class="flex items-baseline gap-1.5">
                <span class="text-2xl font-black font-mono text-amber-700">
                  {{ (user.reward_points || 0).toLocaleString('vi-VN') }}
                </span>
                <span class="text-xs font-bold text-slate-600">Điểm khả dụng</span>
              </div>
              <p class="text-xs text-slate-500 leading-snug">
                Điểm thưởng thành viên tự động cộng vào tài khoản sau mỗi chuyến xe hoàn thành an toàn.
              </p>
            </div>

            <!-- Drawer Links -->
            <div class="space-y-1 text-xs font-semibold text-slate-700">
              <a href="/my-bookings" class="flex items-center space-x-2.5 p-3 rounded-xl bg-red-50 text-red-700 font-bold hover:bg-red-100 transition">
                <span>🎫</span>
                <span>Vé của tôi & Lịch sử tích điểm</span>
              </a>
              <a href="/schedules" class="flex items-center space-x-2.5 p-3 rounded-xl hover:bg-slate-100 transition">
                <span>📅</span>
                <span>Lịch trình toàn quốc</span>
              </a>
              <a href="/trips" class="flex items-center space-x-2.5 p-3 rounded-xl hover:bg-slate-100 transition">
                <span>🚍</span>
                <span>Tìm kiếm chuyến xe</span>
              </a>
              <a v-if="user.role === 'admin'" href="/admin" class="flex items-center space-x-2.5 p-3 rounded-xl hover:bg-amber-50 text-amber-800 transition font-bold">
                <span>⚡</span>
                <span>Trung tâm Quản trị Admin</span>
              </a>
              <a v-if="user.role === 'driver'" href="/driver" class="flex items-center space-x-2.5 p-3 rounded-xl hover:bg-emerald-50 text-emerald-800 transition font-bold">
                <span>🧑‍✈️</span>
                <span>Ứng dụng Bác tài lái xe</span>
              </a>
              <a v-if="user.role === 'staff' || user.role === 'conductor'" href="/conductor" class="flex items-center space-x-2.5 p-3 rounded-xl hover:bg-blue-50 text-blue-800 transition font-bold">
                <span>🎫</span>
                <span>Ứng dụng Lơ xe phụ trách</span>
              </a>
            </div>
          </div>

          <!-- If Guest -->
          <div v-else class="space-y-4 text-center py-6">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto text-2xl">
              👤
            </div>
            <h3 class="font-black text-slate-900">Bạn chưa đăng nhập</h3>
            <p class="text-xs text-slate-500">Đăng nhập để nhận mã vé qua Gmail, theo dõi điểm thưởng nhaxekientruc và lịch sử chuyến đi.</p>
            <button
              @click="$emit('openLogin')"
              class="w-full py-3 rounded-xl bg-[#D84E55] hover:bg-[#C1121F] text-white font-bold text-xs shadow-md shadow-red-500/20 transition cursor-pointer"
            >
              Đăng Nhập / Đăng Ký Ngay
            </button>
          </div>
        </div>

        <!-- Footer / Logout -->
        <div v-if="user" class="p-6 border-t border-slate-100">
          <button
            @click="logout"
            class="w-full py-2.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
          >
            <span>🚪</span> Đăng xuất tài khoản
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

defineProps({
  modelValue: { type: Boolean, default: false },
  user: { type: Object, default: null }
});

defineEmits(['update:modelValue', 'openLogin']);

const logout = () => {
  router.post('/logout', {}, {
    onSuccess: () => window.location.reload()
  });
};

const getTierBadgeClass = (tier) => {
  switch (tier) {
    case 'DIAMOND': return 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white';
    case 'GOLD': return 'bg-gradient-to-r from-amber-500 to-yellow-600 text-white';
    case 'SILVER': return 'bg-slate-700 text-white';
    default: return 'bg-emerald-600 text-white';
  }
};

const getTierIcon = (tier) => {
  switch (tier) {
    case 'DIAMOND': return '💎';
    case 'GOLD': return '👑';
    case 'SILVER': return '🥈';
    default: return '⭐';
  }
};

const getTierLabel = (tier) => {
  switch (tier) {
    case 'DIAMOND': return 'VIP Kim Cương';
    case 'GOLD': return 'VIP Vàng';
    case 'SILVER': return 'Thân Thiết Bạc';
    default: return 'Thành Viên Mới';
  }
};
</script>
