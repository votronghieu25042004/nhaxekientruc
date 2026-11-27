<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed inset-0 z-[100000] flex items-center justify-center p-2 sm:p-4 bg-black/65 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <!-- Backdrop click to close -->
      <div class="fixed inset-0" @click="closeModal"></div>

      <!-- Google Chrome Popup Window Container -->
      <div
        class="relative bg-white rounded-2xl sm:rounded-3xl shadow-2xl w-full max-w-[460px] overflow-hidden z-10 border border-slate-300 animate-in zoom-in-95 duration-150 flex flex-col max-h-[92vh]"
        style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;"
      >
        <!-- ========================================================================= -->
        <!-- 1. CHROME BROWSER WINDOW TITLEBAR                                         -->
        <!-- ========================================================================= -->
        <div class="bg-[#dfe1e5] px-3 py-2 flex items-center justify-between border-b border-[#c4c7cc] select-none">
          <!-- Window Title -->
          <div class="flex items-center gap-2 truncate pr-2">
            <!-- Google Chrome Icon -->
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
              <path fill="#EA4335" d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 2.98.97 4.29L7.5 9.5H12V2z"/>
              <path fill="#4285F4" d="M12 2v7.5h7.5c-.68-2.14-2.14-3.92-4.1-4.99C14.33 2.87 13.19 2 12 2z"/>
              <path fill="#FBBC05" d="M2.97 16.29C4.41 19.68 7.9 22 12 22c1.7 0 3.28-.43 4.67-1.18L12 12.5 7.5 9.5 2.97 16.29z"/>
              <path fill="#34A853" d="M16.67 20.82C19.86 19.12 22 15.82 22 12c0-1.57-.37-3.05-1.02-4.38L12 12.5l4.67 8.32z"/>
              <circle cx="12" cy="12" r="4.2" fill="#FFFFFF"/>
              <circle cx="12" cy="12" r="3.2" fill="#1a73e8"/>
            </svg>
            <span class="text-xs text-slate-800 font-medium truncate">Đăng nhập - Tài khoản Google - Google Chrome</span>
          </div>

          <!-- Window Controls (Minimize, Maximize, Close) -->
          <div class="flex items-center gap-1.5 shrink-0">
            <button
              type="button"
              @click="closeModal"
              class="w-6 h-6 rounded flex items-center justify-center text-slate-500 hover:bg-[#cfd1d6] text-xs transition cursor-pointer"
              title="Thu nhỏ"
            >
              —
            </button>
            <button
              type="button"
              @click="closeModal"
              class="w-6 h-6 rounded flex items-center justify-center text-slate-500 hover:bg-[#cfd1d6] text-xs transition cursor-pointer"
              title="Phóng to"
            >
              ▢
            </button>
            <button
              type="button"
              @click="closeModal"
              class="w-6 h-6 rounded flex items-center justify-center text-slate-600 hover:bg-[#e81123] hover:text-white text-xs font-bold transition cursor-pointer"
              title="Đóng cửa sổ"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Chrome Address / Omnibox Bar -->
        <div class="bg-[#f1f3f4] px-3 py-1.5 border-b border-slate-200 flex items-center gap-2">
          <div class="flex items-center gap-1.5 bg-white border border-slate-300 rounded-full px-3 py-1 flex-1 shadow-inner text-[11px] text-slate-600 truncate font-mono">
            <span class="text-slate-400">🔒</span>
            <span class="text-slate-900 font-medium truncate">accounts.google.com</span>
            <span class="text-slate-400 truncate">/v3/signin/accountchooser?client_id=105709211...</span>
          </div>
        </div>

        <!-- Google Loading Progress Bar -->
        <div v-if="loading" class="h-1 w-full bg-blue-100 overflow-hidden">
          <div class="h-full bg-[#1a73e8] animate-pulse w-full"></div>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. MAIN GOOGLE ACCOUNT CHOOSER BODY                                       -->
        <!-- ========================================================================= -->
        <div class="p-6 sm:p-7 overflow-y-auto flex-1 flex flex-col justify-between">
          <div>
            <!-- Google Logo & Subtitle -->
            <div class="flex items-center gap-2 mb-3">
              <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
              </svg>
              <span class="text-xs font-semibold text-slate-700">Đăng nhập bằng Google</span>
            </div>

            <!-- Header Titles -->
            <h2 class="text-2xl font-normal text-slate-900 leading-tight">Chọn tài khoản</h2>
            <p class="text-sm text-slate-600 mt-1 mb-5">
              Tiếp tục tới <span class="font-semibold text-slate-800">nhaxekientruc.vn</span>
            </p>

            <!-- ================= THÔNG BÁO LỖI NẾU CHƯA ĐĂNG KÝ ================= -->
            <div
              v-if="unregisteredNotice"
              class="mb-4 p-3.5 bg-red-50 border border-red-300 rounded-2xl text-red-800 text-xs animate-in fade-in"
            >
              <div class="flex items-start gap-2.5">
                <span class="text-base shrink-0">⚠️</span>
                <div class="space-y-1">
                  <p class="font-bold text-red-900 leading-snug">
                    Tài khoản Google <span class="underline">{{ selectedEmail }}</span> chưa được đăng ký!
                  </p>
                  <p class="text-[11.5px] text-red-700">
                    Vui lòng đăng ký tài khoản trước khi đăng nhập để được lưu lịch sử vé và bảo lưu điểm thưởng.
                  </p>
                  <div class="pt-2">
                    <button
                      type="button"
                      @click="redirectToRegister(selectedEmail, selectedName)"
                      class="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5"
                    >
                      <span>📝 Đăng ký tài khoản ngay với Google</span>
                      <span>➔</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- THÔNG BÁO LỖI THÔNG THƯỜNG -->
            <div
              v-if="generalError && !unregisteredNotice"
              class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs font-semibold animate-in fade-in flex items-center gap-2"
            >
              <span>⚠️</span>
              <span>{{ generalError }}</span>
            </div>

            <!-- ================= DANH SÁCH TÀI KHOẢN GOOGLE ================= -->
            <div class="border-t border-slate-200 divide-y divide-slate-100">
              <!-- Tài khoản 1: Trọng Hiếu Võ -->
              <button
                type="button"
                @click="chooseAccount('tronghieuvo9@gmail.com', 'Trọng Hiếu Võ')"
                :disabled="loading"
                class="w-full py-3 px-2 flex items-center gap-3.5 hover:bg-slate-50 transition text-left cursor-pointer group disabled:opacity-60"
              >
                <!-- Avatar T (Brown) -->
                <div class="w-10 h-10 rounded-full bg-[#5d4037] text-white font-bold text-base flex items-center justify-center shrink-0 shadow-xs">
                  T
                </div>
                <div class="truncate flex-1">
                  <div class="text-sm font-semibold text-slate-800 group-hover:text-blue-600 transition truncate">
                    Trọng Hiếu Võ
                  </div>
                  <div class="text-xs text-slate-500 truncate">
                    tronghieuvo9@gmail.com
                  </div>
                </div>
              </button>

              <!-- Tài khoản 2: VÕ TRỌNG HIẾU (Sinh viên ĐH Kiến Trúc) -->
              <button
                type="button"
                @click="chooseAccount('hieu_2251220108@dau.edu.vn', 'VÕ TRỌNG HIẾU')"
                :disabled="loading"
                class="w-full py-3 px-2 flex items-center gap-3.5 hover:bg-slate-50 transition text-left cursor-pointer group disabled:opacity-60"
              >
                <!-- Avatar V (Purple) -->
                <div class="w-10 h-10 rounded-full bg-[#7b1fa2] text-white font-bold text-base flex items-center justify-center shrink-0 shadow-xs">
                  V
                </div>
                <div class="truncate flex-1">
                  <div class="text-sm font-semibold text-slate-800 group-hover:text-blue-600 transition truncate flex items-center gap-1.5">
                    <span>VÕ TRỌNG HIẾU</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-amber-100 text-amber-800 font-bold border border-amber-300">Sinh Viên</span>
                  </div>
                  <div class="text-xs text-slate-500 truncate">
                    hieu_2251220108@dau.edu.vn
                  </div>
                </div>
              </button>

              <!-- Tài khoản 3: Anh Võ Lan -->
              <button
                type="button"
                @click="chooseAccount('volananh2k4@gmail.com', 'Anh Võ Lan')"
                :disabled="loading"
                class="w-full py-3 px-2 flex items-center gap-3.5 hover:bg-slate-50 transition text-left cursor-pointer group disabled:opacity-60"
              >
                <!-- Avatar Picture -->
                <div class="w-10 h-10 rounded-full overflow-hidden bg-rose-200 border border-rose-300 shrink-0 flex items-center justify-center shadow-xs">
                  <img
                    src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80"
                    alt="Anh Võ Lan"
                    class="w-full h-full object-cover"
                    @error="$event.target.style.display='none'"
                  />
                  <span class="font-bold text-rose-800 text-sm">A</span>
                </div>
                <div class="truncate flex-1">
                  <div class="text-sm font-semibold text-slate-800 group-hover:text-blue-600 transition truncate">
                    Anh Võ Lan
                  </div>
                  <div class="text-xs text-slate-500 truncate">
                    volananh2k4@gmail.com
                  </div>
                </div>
              </button>

              <!-- Tài khoản 4: Sử dụng một tài khoản khác -->
              <div>
                <button
                  type="button"
                  @click="showCustomInput = !showCustomInput"
                  :disabled="loading"
                  class="w-full py-3 px-2 flex items-center gap-3.5 hover:bg-slate-50 transition text-left cursor-pointer group disabled:opacity-60"
                >
                  <div class="w-10 h-10 rounded-full border border-slate-300 text-slate-600 flex items-center justify-center shrink-0 group-hover:border-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                  </div>
                  <div class="text-sm font-semibold text-slate-700 group-hover:text-blue-600 transition truncate">
                    Sử dụng một tài khoản khác
                  </div>
                </button>

                <!-- Khung nhập tài khoản khác -->
                <div v-if="showCustomInput" class="p-3 bg-slate-50 rounded-2xl border border-slate-200 my-2 space-y-2 animate-in fade-in">
                  <label class="block text-xs font-bold text-slate-600">
                    Nhập Email Google của bạn:
                  </label>
                  <input
                    type="email"
                    v-model="customEmail"
                    placeholder="vidu@gmail.com"
                    class="w-full px-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs sm:text-sm font-medium text-slate-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                    @keyup.enter="handleCustomSubmit"
                  />
                  <div class="flex justify-end gap-2 pt-1">
                    <button
                      type="button"
                      @click="showCustomInput = false"
                      class="px-3 py-1.5 rounded-lg text-slate-500 hover:bg-slate-200 text-xs font-semibold cursor-pointer"
                    >
                      Hủy
                    </button>
                    <button
                      type="button"
                      @click="handleCustomSubmit"
                      :disabled="!customEmail.trim() || loading"
                      class="px-4 py-1.5 bg-[#1a73e8] hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-xs transition cursor-pointer disabled:opacity-50"
                    >
                      Tiếp tục ➔
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ================= CHÍNH SÁCH & FOOTER CHUẨN GOOGLE ================= -->
          <div class="mt-6 pt-4 border-t border-slate-100 text-[11px] text-slate-500 leading-relaxed">
            <p>
              Trước khi sử dụng <span class="font-medium text-slate-700">nhaxekientruc.vn</span>, bạn có thể xem
              <a href="#" class="text-blue-600 hover:underline">Chính sách quyền riêng tư</a> và
              <a href="#" class="text-blue-600 hover:underline">Điều khoản dịch vụ</a> của ứng dụng này.
            </p>

            <div class="mt-4 flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-100 pt-2.5">
              <div class="flex items-center gap-1 cursor-pointer hover:text-slate-700">
                <span>Tiếng Việt</span>
                <span class="text-[9px]">▼</span>
              </div>
              <div class="flex items-center gap-3">
                <a href="#" class="hover:text-slate-700">Trợ giúp</a>
                <a href="#" class="hover:text-slate-700">Quyền riêng tư</a>
                <a href="#" class="hover:text-slate-700">Điều khoản</a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  visible: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'loginSuccess', 'switchToRegister']);

const loading = ref(false);
const unregisteredNotice = ref(false);
const generalError = ref('');
const selectedEmail = ref('');
const selectedName = ref('');
const showCustomInput = ref(false);
const customEmail = ref('');

const closeModal = () => {
  if (loading.value) return;
  unregisteredNotice.value = false;
  generalError.value = '';
  showCustomInput.value = false;
  customEmail.value = '';
  emit('close');
};

const chooseAccount = async (email, name) => {
  selectedEmail.value = email;
  selectedName.value = name || 'Khách Hàng Google';
  unregisteredNotice.value = false;
  generalError.value = '';
  loading.value = true;

  try {
    const res = await axios.post('/auth/google-login', {
      email: email,
      name: name,
    }, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    if (res.data?.success) {
      loading.value = false;
      emit('loginSuccess', res.data.user);
      closeModal();
      window.location.reload();
    } else {
      loading.value = false;
      if (res.data?.requires_registration) {
        unregisteredNotice.value = true;
      } else {
        generalError.value = res.data?.message || 'Đăng nhập Google thất bại!';
      }
    }
  } catch (err) {
    loading.value = false;
    const resData = err.response?.data;
    if (resData?.requires_registration) {
      unregisteredNotice.value = true;
    } else {
      generalError.value = resData?.message || 'Tài khoản Google chưa được đăng ký trên hệ thống! Vui lòng đăng ký trước khi đăng nhập.';
      unregisteredNotice.value = true;
    }
  }
};

const handleCustomSubmit = () => {
  const email = customEmail.value.trim();
  if (!email) return;
  const username = email.split('@')[0];
  chooseAccount(email, username);
};

const redirectToRegister = (email, name) => {
  closeModal();
  emit('switchToRegister', { email, name });
  router.visit('/register', {
    data: {
      email: email,
      name: name,
    }
  });
};
</script>
