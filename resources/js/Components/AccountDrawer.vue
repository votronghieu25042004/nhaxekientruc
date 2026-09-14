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
              <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-blue-600 to-rose-600 text-white flex items-center justify-center font-black text-lg">
                {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
              </div>
              <div>
                <div class="font-bold text-slate-900">{{ user.name }}</div>
                <div class="text-xs text-slate-400">{{ user.email || user.phone }}</div>
                <span class="inline-block mt-1 px-2 py-0.5 rounded-md bg-blue-100 text-blue-700 text-[10px] font-bold uppercase">
                  {{ user.role || 'Thành viên' }}
                </span>
              </div>
            </div>

            <!-- Drawer Links -->
            <div class="space-y-1 text-xs font-semibold text-slate-700">
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
              <a v-if="user.role === 'staff'" href="/conductor" class="flex items-center space-x-2.5 p-3 rounded-xl hover:bg-blue-50 text-blue-800 transition font-bold">
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
            <p class="text-xs text-slate-500">Đăng nhập để nhận mã vé qua Gmail, theo dõi điểm thưởng BusHub và lịch sử chuyến đi.</p>
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
</script>
