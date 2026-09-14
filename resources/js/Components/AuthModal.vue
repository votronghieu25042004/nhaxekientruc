<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
      @click.self="closeModal"
    >
      <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 sm:p-8 my-auto transform transition-all animate-in fade-in zoom-in-95 duration-200">
        <!-- Close Button -->
        <button
          type="button"
          @click="closeModal"
          class="absolute top-5 right-5 p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition cursor-pointer"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Brand Header -->
        <div class="text-center mb-5">
          <div class="w-12 h-12 rounded-2xl bg-[#2563EB] text-white flex items-center justify-center mx-auto mb-2 shadow-md shadow-blue-600/25 font-black text-xl">
            F
          </div>
          <h2 class="text-xl font-black text-slate-900 tracking-tight">BusHub Terminal & redBus</h2>
          <p class="text-xs text-slate-500 mt-0.5">Đăng nhập tài khoản & Nhận mã vé QR</p>
        </div>

        <!-- Fast Dev 1-Click Role Login Bar (Easy Testing) -->
        <div class="mb-5 p-3.5 bg-blue-50/80 rounded-2xl border border-blue-200/80">
          <div class="flex items-center space-x-1.5 text-xs font-black text-orange-900 mb-2">
            <span>⚡</span>
            <span>ĐĂNG NHẬP NHANH 1 CHẠM (TIỆN TEST):</span>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <button
              type="button"
              @click="fastLogin('admin')"
              class="py-2 px-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold text-center transition cursor-pointer shadow-xs"
            >
              👑 Admin
            </button>
            <button
              type="button"
              @click="fastLogin('driver')"
              class="py-2 px-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold text-center transition cursor-pointer shadow-xs"
            >
              🚌 Tài Xế
            </button>
            <button
              type="button"
              @click="fastLogin('staff')"
              class="py-2 px-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold text-center transition cursor-pointer shadow-xs"
            >
              🎫 Lơ Xe
            </button>
          </div>
        </div>

        <!-- Mode Switcher Tabs -->
        <div class="flex p-1 bg-slate-100 rounded-2xl mb-5">
          <button
            type="button"
            @click="activeTab = 'otp'"
            :class="[
              'flex-1 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer',
              activeTab === 'otp' ? 'bg-white text-[#2563EB] shadow-sm' : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            Đăng Nhập OTP Gmail
          </button>
          <button
            type="button"
            @click="activeTab = 'password'"
            :class="[
              'flex-1 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer',
              activeTab === 'password' ? 'bg-white text-[#2563EB] shadow-sm' : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            Mật Khẩu
          </button>
        </div>

        <!-- OTP Flow -->
        <div v-if="activeTab === 'otp'" class="space-y-4">
          <!-- Step 1: Input Email -->
          <div v-if="!otpSent">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Địa chỉ Gmail của bạn</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <input
                type="email"
                v-model="otpEmail"
                placeholder="tenban@gmail.com"
                class="w-full pl-10 pr-3 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold focus:ring-2 focus:ring-[#2563EB] focus:bg-white"
              />
            </div>
            <button
              type="button"
              :disabled="!otpEmail || isSubmitting"
              @click="handleSendOtp"
              class="w-full mt-4 py-3 bg-[#2563EB] hover:bg-[#D84315] text-white font-extrabold text-xs rounded-2xl shadow-md shadow-blue-600/20 active:scale-95 disabled:opacity-50 transition-all cursor-pointer"
            >
              <span v-if="isSubmitting">Đang gửi mã...</span>
              <span v-else>Gửi Mã Xác Thực OTP</span>
            </button>
          </div>

          <!-- Step 2: Input OTP Code -->
          <div v-else>
            <div class="p-3 bg-blue-50 rounded-2xl border border-blue-100 text-xs text-orange-900 mb-4">
              Mã OTP 6 số đã được gửi đến: <strong>{{ otpEmail }}</strong>
            </div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nhập mã OTP 6 chữ số</label>
            <input
              type="text"
              v-model="otpCode"
              placeholder="123456"
              maxlength="6"
              class="w-full p-3 bg-slate-50 border border-slate-200 rounded-2xl text-center text-lg font-black tracking-widest focus:ring-2 focus:ring-[#2563EB] focus:bg-white"
            />
            <button
              type="button"
              :disabled="otpCode.length < 6 || isSubmitting"
              @click="handleVerifyOtp"
              class="w-full mt-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-2xl shadow-md shadow-emerald-500/20 active:scale-95 disabled:opacity-50 transition-all cursor-pointer"
            >
              <span v-if="isSubmitting">Đang xác thực...</span>
              <span v-else>Xác Nhận & Đăng Nhập</span>
            </button>
            <button
              type="button"
              @click="otpSent = false"
              class="w-full mt-2 text-xs font-bold text-slate-500 hover:text-slate-800 text-center cursor-pointer"
            >
              Đổi email khác
            </button>
          </div>
        </div>

        <!-- Password Flow -->
        <div v-else class="space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Email</label>
            <input
              type="email"
              v-model="loginForm.email"
              placeholder="admin@bushub.vn"
              class="w-full p-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold focus:ring-2 focus:ring-[#2563EB] focus:bg-white"
            />
          </div>
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Mật khẩu</label>
            <input
              type="password"
              v-model="loginForm.password"
              placeholder="••••••••"
              class="w-full p-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold focus:ring-2 focus:ring-[#2563EB] focus:bg-white"
            />
          </div>
          <button
            type="button"
            :disabled="isSubmitting"
            @click="handlePasswordLogin"
            class="w-full py-3 bg-[#2563EB] hover:bg-[#D84315] text-white font-extrabold text-xs rounded-2xl shadow-md shadow-blue-600/20 active:scale-95 disabled:opacity-50 transition-all cursor-pointer"
          >
            <span v-if="isSubmitting">Đang đăng nhập...</span>
            <span v-else>Đăng Nhập</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  isOpen: { type: Boolean, default: false },
  initialTab: { type: String, default: 'otp' },
})

const emit = defineEmits(['update:modelValue', 'close'])

const visible = computed(() => props.modelValue || props.isOpen)

const activeTab = ref(props.initialTab || 'otp')
const isSubmitting = ref(false)
const otpEmail = ref('')
const otpCode = ref('')
const otpSent = ref(false)

const loginForm = ref({
  email: '',
  password: '',
})

const closeModal = () => {
  emit('update:modelValue', false)
  emit('close')
}

const fastLogin = (role) => {
  closeModal()
  router.post('/auth/fast-login', { role })
}

const handleSendOtp = () => {
  if (!otpEmail.value) return
  isSubmitting.value = true

  router.post('/auth/send-otp', { target: otpEmail.value, type: 'email' }, {
    onSuccess: () => {
      otpSent.value = true
      isSubmitting.value = false
    },
    onError: () => {
      isSubmitting.value = false
      alert('Không thể gửi mã OTP. Vui lòng thử lại.')
    }
  })
}

const handleVerifyOtp = () => {
  if (!otpCode.value) return
  isSubmitting.value = true

  router.post('/auth/verify-otp', {
    target: otpEmail.value,
    otp: otpCode.value,
  }, {
    onSuccess: () => {
      isSubmitting.value = false
      closeModal()
      window.location.reload()
    },
    onError: () => {
      isSubmitting.value = false
      alert('Mã OTP không chính xác hoặc đã hết hạn.')
    }
  })
}

const handlePasswordLogin = () => {
  isSubmitting.value = true
  router.post('/login', loginForm.value, {
    onSuccess: () => {
      isSubmitting.value = false
      closeModal()
    },
    onError: () => {
      isSubmitting.value = false
      alert('Email hoặc mật khẩu không đúng.')
    }
  })
}
</script>
