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
            <div class="w-10 h-10 rounded-2xl bg-white border border-red-200 flex items-center justify-center p-1 shadow-sm">
              <img src="/images/logo.png" alt="Logo Trường ĐH Kiến Trúc Đà Nẵng" class="w-full h-full object-contain" />
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

        <!-- NÚT ĐĂNG NHẬP GOOGLE CHUẨN MẪU -->
        <div class="mt-4">
          <button
            type="button"
            @click="handleGoogleLogin"
            :disabled="isSubmitting"
            class="w-full py-3 px-4 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm rounded-2xl border border-slate-300 shadow-xs hover:shadow-md transition-all flex items-center justify-center gap-3 cursor-pointer active:scale-[0.99] disabled:opacity-50"
          >
            <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24">
              <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
              <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
              <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span class="text-slate-800 font-bold">Google</span>
          </button>

          <div class="relative flex items-center justify-center my-3.5">
            <div class="border-t border-slate-200 w-full"></div>
            <span class="bg-white px-3 text-[10.5px] font-bold text-slate-400 uppercase tracking-wider shrink-0">
              HOẶC ĐĂNG NHẬP BẰNG TÀI KHOẢN
            </span>
            <div class="border-t border-slate-200 w-full"></div>
          </div>
        </div>

        <!-- FORM ĐĂNG NHẬP MẬT KHẨU -->
        <form @submit.prevent="handlePasswordLogin" class="space-y-4">
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
                class="w-full pl-10 pr-3.5 py-3 min-h-[46px] bg-slate-50 border border-slate-200 rounded-2xl text-base sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition"
              />
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
                class="w-full pl-10 pr-10 py-3 min-h-[46px] bg-slate-50 border border-slate-200 rounded-2xl text-base sm:text-sm font-semibold text-slate-900 focus:ring-2 focus:ring-red-500 focus:bg-white outline-none transition"
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
            Bạn chưa có tài khoản?
          </p>
          <button
            type="button"
            @click="goToRegister"
            class="w-full py-2.5 px-4 rounded-xl border border-slate-200 hover:border-red-500 hover:bg-red-50/50 text-slate-700 hover:text-red-700 font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
          >
            <span>📝</span>
            <span>Đăng ký ngay (Xác thực OTP) ➔</span>
          </button>
        </div>

        <!-- HỘP HỖ TRỢ KHI GẶP KHÓ KHĂN (CHUẨN ẢNH 1) -->
        <div class="mt-3.5 p-3 bg-slate-50 border border-slate-200/80 rounded-2xl text-center text-xs text-slate-600">
          <span>Bạn có gặp khó khăn khi tạo tài khoản? Vui lòng gọi tới số </span>
          <a href="tel:0906123931" class="font-bold text-red-600 hover:underline">📞 0906 123 931</a>
          <span class="text-[11px] text-slate-400"> (Giờ hành chính)</span>
        </div>

        <!-- CỤM HỖ TRỢ ZALO & SỐ ĐIỆN THOẠI (CHUẨN ẢNH 2) -->
        <div class="mt-2.5 space-y-1.5">
          <a
            href="https://zalo.me/0906123931"
            target="_blank"
            class="p-2.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl flex items-center justify-between text-xs font-bold text-emerald-900 transition"
          >
            <div class="flex items-center gap-2">
              <span class="text-base">💬</span>
              <span>Hỗ trợ qua Zalo</span>
            </div>
            <span class="text-emerald-700 text-sm">›</span>
          </a>
          <a
            href="tel:0906123931"
            class="p-2.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl flex items-center justify-between text-xs font-bold text-slate-800 transition"
          >
            <div class="flex items-center gap-2">
              <span class="text-base">📞</span>
              <span>Liên hệ Nhà xe: 0906 123 931</span>
            </div>
            <span class="text-slate-400 text-sm">›</span>
          </a>
        </div>

      </div>
    </div>

    <!-- Popup Google Account Chooser chuẩn phong cách Google Chrome -->
    <GoogleAccountChooserModal
      :visible="showGoogleChooser"
      @close="showGoogleChooser = false"
      @switchToRegister="closeModal"
    />
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import GoogleAccountChooserModal from '@/Components/GoogleAccountChooserModal.vue';

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
const showGoogleChooser = ref(false);

const loginForm = ref({
  email: '',
  password: '',
  remember: true,
});

const closeModal = () => {
  emit('update:modelValue', false);
  emit('close');
  errorMessage.value = '';
  showForgot.value = false;
  showGoogleChooser.value = false;
};

const goToRegister = () => {
  closeModal();
  router.visit('/register');
};

const handleGoogleLogin = () => {
  showGoogleChooser.value = true;
};

const handlePasswordLogin = async () => {
  if (!loginForm.value.email.trim() || !loginForm.value.password) {
    errorMessage.value = 'Vui lòng nhập email/số điện thoại và mật khẩu!';
    return;
  }

  isSubmitting.value = true;
  errorMessage.value = '';

  try {
    const res = await axios.post('/login', {
      email: loginForm.value.email.trim(),
      password: loginForm.value.password,
      remember: loginForm.value.remember,
    }, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (res.data?.success) {
      isSubmitting.value = false;
      closeModal();
      const redirectUrl = res.data.redirect || '/';
      if (redirectUrl && redirectUrl !== '/') {
        window.location.href = redirectUrl;
      } else {
        router.reload();
      }
    } else {
      isSubmitting.value = false;
      errorMessage.value = res.data?.message || 'Email/Số điện thoại hoặc mật khẩu không chính xác!';
    }
  } catch (err) {
    isSubmitting.value = false;
    const resData = err.response?.data;
    errorMessage.value = resData?.message || resData?.errors?.email?.[0] || 'Email/Số điện thoại hoặc mật khẩu không chính xác!';
  }
};
</script>
