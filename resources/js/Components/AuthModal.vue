<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <!-- Backdrop click to close -->
      <div class="fixed inset-0" @click="closeModal"></div>

      <!-- Modal Card -->
      <div
        class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden z-10 animate-in zoom-in-95 duration-150 border border-slate-100 p-6 sm:p-7"
      >
        <!-- Header -->
        <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-red-600 to-rose-600 flex items-center justify-center text-white font-black text-xl shadow-md shadow-red-500/20">
              🚌
            </div>
            <div>
              <h3 class="text-base sm:text-lg font-black text-slate-900 leading-tight">Đăng Nhập Tài Khoản</h3>
              <p class="text-[11px] text-slate-500 font-medium">Đăng nhập bằng Email hoặc Số điện thoại & Mật khẩu</p>
            </div>
          </div>
          <button
            type="button"
            @click="closeModal"
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center text-base font-bold transition cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- THÔNG BÁO LỖI NẾU ĐĂNG NHẬP THẤT BẠI -->
        <div
          v-if="errorMessage"
          class="mt-4 p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-2xl flex items-start gap-2.5 animate-in fade-in duration-150"
        >
          <span class="text-base shrink-0 leading-none">⚠️</span>
          <span class="leading-relaxed">{{ errorMessage }}</span>
        </div>

        <!-- FORM ĐĂNG NHẬP MẬT KHẨU -->
        <form @submit.prevent="handlePasswordLogin" class="mt-4 space-y-4">
          <!-- Tài khoản (Email hoặc SĐT) -->
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
              Email hoặc Số điện thoại <span class="text-red-500">*</span>
            </label>
            <div class="relative flex items-center">
              <span class="absolute left-3.5 text-slate-400 text-sm pointer-events-none">👤</span>
              <input
                type="text"
                v-model="loginForm.email"
                required
                autofocus
                placeholder="Nhập email hoặc 10 số điện thoại của bạn"
                class="w-full pl-10 pr-3.5 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition"
              />
            </div>

            <!-- Student Auto-detection Banner -->
            <div v-if="isArchitectureStudent" class="mt-2 p-3 bg-gradient-to-r from-red-50 to-amber-50 border border-amber-300 rounded-2xl text-xs text-amber-950 space-y-1 animate-in fade-in">
              <div class="flex items-center gap-1.5 font-black text-red-700">
                <span>🏮</span>
                <span>SINH VIÊN ĐẠI HỌC KIẾN TRÚC (DAU)</span>
                <span class="ml-auto text-[9.5px] px-2 py-0.5 rounded-full bg-red-600 text-white font-mono">Xác thực: Hợp lệ</span>
              </div>
              <p class="text-[11px] text-slate-700 leading-snug">
                🎉 Bạn được hưởng đặc quyền <strong>VÉ 0-ĐỒNG VỀ QUÊ ĐÓN TẾT (Mã: SINHVIEN0DONG)</strong>, miễn phí 100% đồ án & có <strong>AI gọi điện nhắc lịch xuất bến</strong>!
              </p>
            </div>
            <div v-else-if="isGeneralStudent" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-2xl text-xs text-blue-950 space-y-1 animate-in fade-in">
              <div class="flex items-center gap-1.5 font-black text-blue-700">
                <span>🎓</span>
                <span>SINH VIÊN CÁC TRƯỜNG ĐH & CĐ TOÀN QUỐC</span>
                <span class="ml-auto text-[9.5px] px-2 py-0.5 rounded-full bg-blue-600 text-white font-mono">Đã xác nhận SV</span>
              </div>
              <p class="text-[11px] text-slate-700 leading-snug">
                Áp dụng chính sách <strong>GIẢM 30% LẦN ĐẦU (Mã SINHVIEN)</strong> hoặc <strong>VÉ 0-ĐỒNG TẾT</strong> và AI nhắc lịch trình tự động!
              </p>
            </div>
          </div>

          <!-- Mật khẩu -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Mật khẩu <span class="text-red-500">*</span>
              </label>
              <button
                type="button"
                @click="showForgot = !showForgot"
                class="text-[11px] font-bold text-red-600 hover:underline cursor-pointer"
              >
                Quên mật khẩu?
              </button>
            </div>
            <div class="relative flex items-center">
              <span class="absolute left-3.5 text-slate-400 text-sm pointer-events-none">🔒</span>
              <input
                :type="showPassword ? 'text' : 'password'"
                v-model="loginForm.password"
                required
                placeholder="Nhập mật khẩu tài khoản"
                class="w-full pl-10 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 text-slate-400 hover:text-slate-600 text-base cursor-pointer"
              >
                {{ showPassword ? '🙈' : '👁️' }}
              </button>
            </div>
          </div>

          <!-- Hộp hướng dẫn quên mật khẩu -->
          <div v-if="showForgot" class="p-3 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-900 space-y-1 animate-in fade-in">
            <p class="font-bold">📞 Hỗ trợ lấy lại mật khẩu nhaxekientruc:</p>
            <p class="text-[11px] text-amber-800">
              Vui lòng gọi Tổng đài 24/7: <a href="tel:19006067" class="font-bold underline text-red-700">1900 6067</a> hoặc Hotline: <a href="tel:0905123456" class="font-bold underline text-red-700">0905 123 456</a> để được hỗ trợ cấp lại mật khẩu.
            </p>
          </div>

          <!-- Ghi nhớ đăng nhập -->
          <div class="flex items-center justify-between text-xs text-slate-600 pt-0.5">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input
                type="checkbox"
                v-model="loginForm.remember"
                class="rounded border-slate-300 text-red-600 focus:ring-red-500 cursor-pointer"
              />
              <span class="text-xs font-medium">Ghi nhớ đăng nhập</span>
            </label>
          </div>

          <!-- Nút Đăng Nhập -->
          <button
            type="submit"
            :disabled="isSubmitting"
            class="w-full py-3.5 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs sm:text-sm rounded-2xl shadow-lg shadow-red-600/25 active:scale-95 disabled:opacity-50 transition cursor-pointer flex items-center justify-center gap-2"
          >
            <span>{{ isSubmitting ? 'ĐANG ĐĂNG NHẬP...' : 'ĐĂNG NHẬP NGAY' }}</span>
            <span>➔</span>
          </button>
        </form>

        <!-- CHUYỂN SANG ĐĂNG KÝ (XÁC THỰC OTP) -->
        <div class="mt-4 pt-3.5 border-t border-slate-100 text-center space-y-2">
          <p class="text-xs text-slate-500 font-medium">
            Chưa có tài khoản nhaxekientruc?
          </p>
          <button
            type="button"
            @click="goToRegister"
            class="w-full py-2.5 px-4 rounded-xl border border-slate-200 hover:border-red-500 hover:bg-red-50/50 text-slate-700 hover:text-red-700 font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
          >
            <span>📝</span>
            <span>Đăng ký tài khoản mới (Xác thực OTP qua Gmail / Zalo)</span>
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  isOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'close']);

const visible = computed(() => props.modelValue || props.isOpen);

const isSubmitting = ref(false);
const showPassword = ref(false);
const showForgot = ref(false);
const errorMessage = ref('');

const loginForm = ref({
  email: '',
  password: '',
  remember: true,
});

const isArchitectureStudent = computed(() => {
  const e = (loginForm.value.email || '').toLowerCase().trim();
  return e.endsWith('@dau.edu.vn') || e.includes('kientruc');
});

const isGeneralStudent = computed(() => {
  const e = (loginForm.value.email || '').toLowerCase().trim();
  return !isArchitectureStudent.value && (e.includes('.edu.vn') || e.includes('sinhvien'));
});

const isStudentEmail = computed(() => isArchitectureStudent.value || isGeneralStudent.value);

const closeModal = () => {
  emit('update:modelValue', false);
  emit('close');
  errorMessage.value = '';
  showForgot.value = false;
};

const goToRegister = () => {
  closeModal();
  router.visit('/register');
};

const handlePasswordLogin = () => {
  if (!loginForm.value.email.trim() || !loginForm.value.password) {
    errorMessage.value = 'Vui lòng nhập email/số điện thoại và mật khẩu!';
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = '';

  router.post('/login', {
    email: loginForm.value.email.trim(),
    password: loginForm.value.password,
    remember: loginForm.value.remember,
  }, {
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false;
      closeModal();
    },
    onError: (errors) => {
      isSubmitting.value = false;
      errorMessage.value = errors.email || errors.password || errors.status || 'Email/Số điện thoại hoặc mật khẩu không chính xác!';
    }
  });
};
</script>
