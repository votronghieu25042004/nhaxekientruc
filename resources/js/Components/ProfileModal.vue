<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs animate-in fade-in duration-200">
      <!-- Backdrop click to close -->
      <div class="fixed inset-0" @click="closeModal"></div>

      <div 
        class="relative z-10 bg-white w-full max-w-lg rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh] animate-in zoom-in-95 duration-200"
        @click.stop
      >
        <!-- Modal Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-red-600 to-rose-600 text-white flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl shadow-inner">
              👤
            </div>
            <div>
              <h3 class="font-black text-base leading-tight">Hồ Sơ Khách Hàng</h3>
              <p class="text-xs text-red-100 font-medium">nhaxekientruc Loyalty & Bảo Mật Tài Khoản</p>
            </div>
          </div>
          <button
            type="button"
            @click="closeModal"
            class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- User Tier & Points Banner -->
        <div class="px-6 py-3 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-amber-200/70 flex items-center justify-between text-xs">
          <div class="flex items-center gap-2">
            <span 
              class="px-2.5 py-0.5 rounded-full font-black uppercase text-[10px] text-white shadow-xs"
              :class="user?.loyalty_tier === 'DIAMOND' ? 'bg-gradient-to-r from-purple-600 to-indigo-600' : (user?.loyalty_tier === 'GOLD' ? 'bg-amber-500' : 'bg-emerald-600')"
            >
              {{ user?.loyalty_tier === 'DIAMOND' ? '💎 Kim Cương' : (user?.loyalty_tier === 'GOLD' ? '👑 VIP Vàng' : '⭐ Thành Viên') }}
            </span>
            <span class="font-bold text-slate-800">{{ user?.name }}</span>
          </div>
          <div class="flex items-center gap-1 font-mono">
            <span class="text-slate-500">Điểm:</span>
            <strong class="text-amber-700 font-black">{{ (user?.reward_points || 0).toLocaleString('vi-VN') }}đ</strong>
          </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex border-b border-slate-200 bg-slate-50 px-6 pt-2 gap-2 text-xs font-bold text-slate-600">
          <button
            type="button"
            @click="activeTab = 'info'"
            class="pb-2.5 px-3 border-b-2 transition cursor-pointer"
            :class="activeTab === 'info' ? 'border-red-600 text-red-600 font-black' : 'border-transparent hover:text-slate-900'"
          >
            Thông Tin Chung
          </button>
          <button
            type="button"
            @click="activeTab = 'contact'"
            class="pb-2.5 px-3 border-b-2 transition cursor-pointer flex items-center gap-1"
            :class="activeTab === 'contact' ? 'border-red-600 text-red-600 font-black' : 'border-transparent hover:text-slate-900'"
          >
            <span v-if="!user?.email && user?.phone">Bổ Sung Gmail</span>
            <span v-else-if="user?.email && !user?.phone">Bổ Sung SĐT</span>
            <span v-else-if="!user?.email && !user?.phone">Bổ Sung Gmail / SĐT</span>
            <span v-else>Cập Nhật Gmail & SĐT</span>
            <span v-if="!user?.email || !user?.phone" class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
          </button>
        <button
          type="button"
          @click="activeTab = 'password'"
          class="pb-2.5 px-3 border-b-2 transition cursor-pointer"
          :class="activeTab === 'password' ? 'border-red-600 text-red-600 font-black' : 'border-transparent hover:text-slate-900'"
        >
          Đổi Mật Khẩu
        </button>
      </div>

      <!-- Alert Banners -->
      <div v-if="feedbackSuccess" class="m-4 mb-0 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-2xl flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
        <span>{{ feedbackSuccess }}</span>
      </div>
      <div v-if="feedbackError" class="m-4 mb-0 p-3 bg-red-50 border border-red-200 text-red-800 text-xs rounded-2xl flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation text-red-500 text-base"></i>
        <span>{{ feedbackError }}</span>
      </div>

      <!-- Tab Content Area -->
      <div class="p-6 overflow-y-auto space-y-4 flex-1">
        
        <!-- TAB 1: THÔNG TIN CHUNG -->
        <div v-if="activeTab === 'info'" class="space-y-4">
          <form @submit.prevent="submitUpdateName" class="space-y-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Họ và Tên Khách Hàng</label>
              <input
                type="text"
                v-model="nameForm.name"
                class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-300 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500"
                required
              />
            </div>

            <div class="grid grid-cols-2 gap-3 pt-1">
              <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Địa Chỉ Gmail</span>
                <span class="text-xs font-black text-slate-800 truncate block mt-0.5">
                  {{ user?.email || '(Chưa liên kết)' }}
                </span>
                <span v-if="user?.email" class="text-[10px] text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                  <span>🔒</span>
                  <span>Đã xác minh (Cố định)</span>
                </span>
                <button 
                  v-else 
                  type="button" 
                  @click="activeTab = 'contact'; contactType = 'email'" 
                  class="text-[10px] text-red-600 font-bold underline mt-1 cursor-pointer"
                >
                  + Thêm Gmail ngay
                </button>
              </div>

              <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Số Điện Thoại</span>
                <span class="text-xs font-black text-slate-800 truncate block mt-0.5 font-mono">
                  {{ user?.phone || '(Chưa liên kết)' }}
                </span>
                <span v-if="user?.phone" class="text-[10px] text-emerald-700 font-bold flex items-center gap-1 mt-0.5">
                  <span>🔒</span>
                  <span>Đã xác minh (Cố định)</span>
                </span>
                <button 
                  v-else 
                  type="button" 
                  @click="activeTab = 'contact'; contactType = 'phone'" 
                  class="text-[10px] text-red-600 font-bold underline mt-1 cursor-pointer"
                >
                  + Thêm SĐT ngay
                </button>
              </div>
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="isSubmitting"
                class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white font-black text-xs rounded-2xl transition shadow-md cursor-pointer disabled:opacity-50"
              >
                {{ isSubmitting ? 'Đang cập nhật...' : 'Lưu Thay Đổi Họ Tên' }}
              </button>
            </div>
          </form>
        </div>

        <!-- TAB 2: CẬP NHẬT / BỔ SUNG GMAIL HOẶC SĐT -->
        <div v-if="activeTab === 'contact'" class="space-y-4">
          <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
            <p class="font-bold flex items-center gap-1.5">
              <span>💡</span>
              <span>Quy định bảo mật thông tin tài khoản:</span>
            </p>
            <p class="text-[11px] mt-1 text-amber-800">
              - Địa chỉ <strong>Gmail</strong> và <strong>Số điện thoại</strong> sau khi đã xác minh sẽ được khóa cố định theo tài khoản để bảo vệ vé điện tử và quyền lợi khách hàng.<br/>
              - Nếu bạn chưa bổ sung Gmail hoặc SĐT, bạn có thể thực hiện liên kết lần đầu dưới đây.
            </p>
          </div>

          <!-- Type Selector (Gmail vs Số Điện Thoại SMS) -->
          <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-2xl border border-slate-200 text-xs">
            <button
              type="button"
              @click="contactType = 'email'; otpSent = false; otpCode = ''"
              class="py-2 rounded-xl font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
              :class="contactType === 'email' ? 'bg-white text-red-600 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            >
              <span>✉️</span>
              <span>Gmail (@gmail.com)</span>
            </button>
            <button
              type="button"
              @click="contactType = 'phone'; otpSent = false; otpCode = ''"
              class="py-2 rounded-xl font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
              :class="contactType === 'phone' ? 'bg-white text-blue-600 font-black shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            >
              <span>📱</span>
              <span>Số Điện Thoại (SMS)</span>
            </button>
          </div>

          <!-- Trạng thái nếu đã có Gmail hoặc SĐT cố định -->
          <div v-if="contactType === 'email' && user?.email" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-center space-y-2">
            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg mx-auto font-black">
              🔒
            </div>
            <h4 class="text-xs font-black text-slate-800">Gmail Đã Được Khóa Cố Định</h4>
            <p class="text-[11px] text-slate-600">
              Địa chỉ Gmail <strong class="text-slate-900">{{ user.email }}</strong> đã được xác minh chính chủ theo tài khoản và không thể thay đổi để đảm bảo an toàn cho các giao dịch đặt vé.
            </p>
          </div>

          <div v-else-if="contactType === 'phone' && user?.phone" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-center space-y-2">
            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg mx-auto font-black">
              🔒
            </div>
            <h4 class="text-xs font-black text-slate-800">Số Điện Thoại Đã Được Khóa Cố Định</h4>
            <p class="text-[11px] text-slate-600">
              Số điện thoại <strong class="text-slate-900 font-mono">{{ user.phone }}</strong> đã được xác minh chính chủ theo tài khoản và không thể thay đổi để đảm bảo an toàn cho các giao dịch đặt vé.
            </p>
          </div>

          <!-- Step 1: Input target value & Send OTP (chỉ hiện khi chưa có thông tin) -->
          <div v-else class="space-y-3">
            <div v-if="contactType === 'email'">
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Địa Chỉ Gmail Mới <span class="text-red-500 font-black">*</span>
              </label>
              <input
                type="email"
                v-model="contactValue"
                placeholder="vidu: nguyenvana@gmail.com"
                class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-300 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500"
              />
              <p class="text-[10px] text-slate-400 mt-1">Bắt buộc phải có đuôi <strong>@gmail.com</strong> hoặc email trường học</p>
            </div>

            <div v-else>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Số Điện Thoại (Nhận OTP qua SMS) <span class="text-red-500 font-black">*</span>
              </label>
              <input
                type="tel"
                v-model="contactValue"
                placeholder="vidu: 0912345678"
                class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-300 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
              />
              <p class="text-[10px] text-slate-400 mt-1">Mã OTP xác thực sẽ được gửi trực tiếp qua tin nhắn SMS tới điện thoại của bạn.</p>
            </div>

            <button
              type="button"
              @click="sendContactOtp"
              :disabled="isSubmitting || !contactValue"
              class="w-full py-2.5 rounded-2xl font-black text-xs transition shadow-md cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
              :class="contactType === 'email' ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white'"
            >
              <span>{{ isSubmitting ? 'Đang gửi mã...' : (otpSent ? 'Gửi Lại Mã Xác Thực' : 'Gửi Mã Xác Thực OTP') }}</span>
            </button>
          </div>

          <!-- Step 2: Input OTP & Confirm -->
          <div v-if="otpSent" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-3 animate-in fade-in duration-200">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Nhập Mã OTP 6 Số Đã Nhận:
              </label>
              <input
                type="text"
                v-model="otpCode"
                maxlength="6"
                placeholder="Nhập 6 số (ví dụ: 123456)"
                class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-300 text-center font-mono font-black text-base text-slate-900 tracking-widest focus:outline-none focus:ring-2 focus:ring-emerald-500"
              />
            </div>

            <button
              type="button"
              @click="verifyContactOtp"
              :disabled="isSubmitting || otpCode.length < 6"
              class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-2xl transition shadow-md cursor-pointer disabled:opacity-50"
            >
              {{ isSubmitting ? 'Đang xác thực...' : 'Xác Nhận & Cập Nhật Tài Khoản' }}
            </button>
          </div>
        </div>

        <!-- TAB 3: ĐỔI MẬT KHẨU -->
        <div v-if="activeTab === 'password'" class="space-y-4">
          <form @submit.prevent="submitChangePassword" class="space-y-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Mật Khẩu Mới <span class="text-red-500 font-black">*</span>
              </label>
              <input
                type="password"
                v-model="passwordForm.new_password"
                placeholder="Tối thiểu 8 ký tự (trên 7 chữ số)"
                class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-300 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500"
                required
                minlength="8"
              />
              <p class="text-[10px] text-slate-400 mt-1">Yêu cầu bảo mật: Mật khẩu phải dài trên 7 ký tự (tối thiểu 8 ký tự).</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">
                Xác Nhận Mật Khẩu Mới <span class="text-red-500 font-black">*</span>
              </label>
              <input
                type="password"
                v-model="passwordForm.new_password_confirmation"
                placeholder="Nhập lại mật khẩu mới"
                class="w-full px-3.5 py-2.5 rounded-2xl border border-slate-300 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500"
                required
                minlength="8"
              />
            </div>

            <div class="pt-2">
              <button
                type="submit"
                :disabled="isSubmitting"
                class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white font-black text-xs rounded-2xl transition shadow-md cursor-pointer disabled:opacity-50"
              >
                {{ isSubmitting ? 'Đang cập nhật...' : 'Xác Nhận Đổi Mật Khẩu' }}
              </button>
            </div>
          </form>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs">
        <button
          type="button"
          @click="logout"
          class="text-red-600 font-bold hover:underline flex items-center gap-1 cursor-pointer"
        >
          <span>🚪</span>
          <span>Đăng Xuất Khỏi Thiết Bị</span>
        </button>
        <button
          type="button"
          @click="closeModal"
          class="px-4 py-1.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold transition cursor-pointer"
        >
          Đóng
        </button>
      </div>

    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  user: { type: Object, default: () => ({}) },
  initialTab: { type: String, default: 'info' }
})

const emit = defineEmits(['update:modelValue'])

const activeTab = ref(props.initialTab || 'info')

watch(() => props.initialTab, (t) => {
  if (t) activeTab.value = t
}, { immediate: true })

watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.initialTab) {
    activeTab.value = props.initialTab
  }
})
const isSubmitting = ref(false)
const feedbackSuccess = ref('')
const feedbackError = ref('')

const nameForm = ref({ name: '' })
const passwordForm = ref({ new_password: '', new_password_confirmation: '' })

const contactType = ref('email')
const contactValue = ref('')
const otpSent = ref(false)
const otpCode = ref('')

watch(() => props.user, (u) => {
  if (u) {
    nameForm.value.name = u.name || ''
    // Tự động chuyển tab bổ sung thông tin còn thiếu
    if (!u.email && u.phone) {
      contactType.value = 'email'
    } else if (u.email && !u.phone) {
      contactType.value = 'phone'
    }
    contactValue.value = contactType.value === 'email' ? (u.email || '') : (u.phone || '')
  }
}, { immediate: true })

watch(contactType, (type) => {
  if (props.user) {
    contactValue.value = type === 'email' ? (props.user.email || '') : (props.user.phone || '')
  }
})

const closeModal = () => {
  emit('update:modelValue', false)
  feedbackSuccess.value = ''
  feedbackError.value = ''
}

const submitUpdateName = () => {
  feedbackSuccess.value = ''
  feedbackError.value = ''
  isSubmitting.value = true

  router.post('/user/profile', {
    name: nameForm.value.name
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false
      feedbackSuccess.value = 'Đã cập nhật họ và tên thành công!'
    },
    onError: (err) => {
      isSubmitting.value = false
      feedbackError.value = err.name || 'Không thể cập nhật họ tên. Vui lòng thử lại.'
    }
  })
}

const sendContactOtp = async () => {
  feedbackSuccess.value = ''
  feedbackError.value = ''

  if (contactType.value === 'email' && !contactValue.value.endsWith('@gmail.com')) {
    feedbackError.value = 'Hệ thống yêu cầu địa chỉ Email bắt buộc phải có đuôi @gmail.com!'
    return
  }

  isSubmitting.value = true
  try {
    const res = await axios.post('/user/send-otp', {
      type: contactType.value,
      value: contactValue.value
    }, {
      headers: { 'Accept': 'application/json' }
    })
    const data = res.data
    if (data?.success) {
      otpSent.value = true
      feedbackSuccess.value = data.message || 'Mã xác thực OTP đã được gửi thành công!'
    } else {
      feedbackError.value = data?.message || 'Không thể gửi mã xác thực. Vui lòng thử lại!'
    }
  } catch (err) {
    feedbackError.value = err.response?.data?.message || err.message || 'Gửi mã xác thực thất bại. Vui lòng kiểm tra lại thông tin.'
  } finally {
    isSubmitting.value = false
  }
}

const verifyContactOtp = async () => {
  feedbackSuccess.value = ''
  feedbackError.value = ''
  isSubmitting.value = true

  try {
    const res = await axios.post('/user/verify-otp', {
      otp: otpCode.value
    }, {
      headers: { 'Accept': 'application/json' }
    })
    const data = res.data
    if (data?.success) {
      otpSent.value = false
      otpCode.value = ''
      feedbackSuccess.value = data.message || 'Cập nhật và xác thực thông tin thành công!'
      router.reload({ only: ['auth'] })
    } else {
      feedbackError.value = data?.message || 'Mã OTP không chính xác hoặc đã hết hạn.'
    }
  } catch (err) {
    feedbackError.value = err.response?.data?.message || err.message || 'Mã OTP không chính xác hoặc đã hết hạn.'
  } finally {
    isSubmitting.value = false
  }
}

const submitChangePassword = () => {
  feedbackSuccess.value = ''
  feedbackError.value = ''

  if (passwordForm.value.new_password.length < 8) {
    feedbackError.value = 'Mật khẩu mới phải dài trên 7 chữ số (tối thiểu 8 ký tự)!'
    return
  }

  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    feedbackError.value = 'Xác nhận mật khẩu mới không trùng khớp!'
    return
  }

  isSubmitting.value = true
  router.post('/user/change-password', {
    new_password: passwordForm.value.new_password,
    new_password_confirmation: passwordForm.value.new_password_confirmation
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false
      passwordForm.value.new_password = ''
      passwordForm.value.new_password_confirmation = ''
      feedbackSuccess.value = 'Đổi mật khẩu tài khoản thành công!'
    },
    onError: (err) => {
      isSubmitting.value = false
      feedbackError.value = err.new_password || 'Đổi mật khẩu thất bại. Vui lòng thử lại.'
    }
  })
}

const logout = () => {
  closeModal()
  router.post('/logout')
}
</script>
