<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-[99999] overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 animate-in fade-in duration-200">
      <!-- Backdrop click to close -->
      <div class="fixed inset-0" @click="close"></div>

      <div class="relative z-10 bg-white rounded-3xl max-w-3xl w-full overflow-hidden shadow-2xl border border-slate-200 flex flex-col max-h-[92vh]" @click.stop>
        
        <!-- HEADER -->
      <div class="p-5 sm:p-6 bg-gradient-to-r from-[#991b1b] via-[#dc2626] to-[#ea580c] text-white relative overflow-hidden flex-shrink-0">
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/20 text-amber-200 text-[10.5px] font-black uppercase tracking-wider mb-1.5 border border-white/20">
              <span>⭐</span>
              <span>ĐẶC QUYỀN THÀNH VIÊN BUSHUB</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
              Gói Hội Viên Ưu Đãi (Pro & Ultra)
            </h2>
            <p class="text-xs sm:text-sm text-red-100 font-medium mt-0.5">
              Tiết kiệm từ 20% đến 40% cho mỗi chuyến đi suốt năm
            </p>
          </div>

          <button
            @click="close"
            type="button"
            class="w-9 h-9 rounded-full bg-black/20 hover:bg-black/40 text-white flex items-center justify-center text-lg transition cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Trạng thái hiện tại của người dùng -->
        <div v-if="currentPlanInfo" class="mt-4 p-3 bg-black/30 backdrop-blur-md rounded-2xl border border-white/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
          <div class="flex items-center gap-2">
            <span class="text-lg">🎓</span>
            <div>
              <div class="font-bold text-white flex items-center gap-1.5">
                <span>Gói đang dùng:</span>
                <span class="text-amber-300 font-black uppercase">{{ currentPlanInfo.name }}</span>
                <span class="px-2 py-0.2 rounded-full bg-emerald-500 text-white text-[10px] font-black">HOẠT ĐỘNG</span>
              </div>
              <div class="text-[11px] text-red-200">
                Đăng ký từ: {{ currentPlanInfo.registered_at }} • Hết hạn: {{ currentPlanInfo.expires_at }}
              </div>
            </div>
          </div>

          <div class="text-right sm:text-right">
            <div class="text-xs font-mono font-black text-amber-300 bg-amber-400/20 px-2.5 py-1 rounded-xl border border-amber-300/40 inline-flex items-center gap-1">
              <span>⏱️ Còn lại:</span>
              <span>{{ currentPlanInfo.days_left }} ngày</span>
            </div>
          </div>
        </div>
      </div>

      <!-- BODY SCROLLABLE -->
      <div class="p-4 sm:p-6 overflow-y-auto space-y-5 flex-1">
        
        <!-- Toggle: Sinh Viên vs Khách Thường -->
        <div class="flex items-center justify-center">
          <div class="p-1 bg-slate-100 rounded-2xl border border-slate-200 flex items-center gap-1 max-w-md w-full">
            <button
              type="button"
              @click="targetRole = 'student'"
              class="flex-1 py-2 px-3 rounded-xl text-center text-xs font-black transition cursor-pointer flex items-center justify-center gap-1.5"
              :class="targetRole === 'student' ? 'bg-[#dc2626] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
            >
              <span>🎓</span>
              <span>Gói Sinh Viên (Trợ Giá)</span>
            </button>

            <button
              type="button"
              @click="targetRole = 'standard'"
              class="flex-1 py-2 px-3 rounded-xl text-center text-xs font-black transition cursor-pointer flex items-center justify-center gap-1.5"
              :class="targetRole === 'standard' ? 'bg-[#dc2626] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
            >
              <span>👤</span>
              <span>Hành Khách Thường</span>
            </button>
          </div>
        </div>

        <!-- Thông báo Trợ giá SV -->
        <div v-if="targetRole === 'student'" class="p-3 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-950 flex items-center gap-2.5">
          <span class="text-xl">🎁</span>
          <div>
            <strong class="text-amber-900 font-bold">Chính sách trợ giá Sinh viên:</strong>
            <span class="ml-1 text-slate-700">Sinh viên khi đăng ký được tặng <strong>1 năm giảm 30%</strong> và chuyến đầu tiên <strong>giảm 100% trong 350k</strong>! Khi hết hạn hoặc muốn giảm cao hơn (40%), bạn có thể nâng cấp gói dưới đây.</span>
          </div>
        </div>

        <!-- 3 CỘT GÓI DỊCH VỤ -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          
          <!-- GÓI 1: ECO PASS (GIẢM 20%) -->
          <div 
            @click="selectedPlan = 'ECO_20'"
            class="rounded-2xl border-2 p-4 flex flex-col justify-between transition cursor-pointer relative"
            :class="selectedPlan === 'ECO_20' ? 'border-red-600 bg-red-50/20 shadow-md ring-2 ring-red-600/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
          >
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">6 Tháng</span>
                <span v-if="selectedPlan === 'ECO_20'" class="w-4 h-4 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center font-bold">✓</span>
              </div>

              <div>
                <h3 class="font-black text-slate-900 text-base">Eco Pass</h3>
                <p class="text-[11px] text-slate-500">Tiết kiệm vé thường nhật</p>
              </div>

              <div class="py-2 border-y border-slate-100">
                <div class="text-2xl font-black text-red-600">
                  {{ targetRole === 'student' ? '49.000đ' : '199.000đ' }}
                </div>
                <div class="text-[10px] text-slate-400">
                  Thời hạn: 6 tháng (180 ngày)
                </div>
              </div>

              <ul class="text-xs text-slate-600 space-y-2">
                <li class="flex items-center gap-1.5 font-bold text-slate-800">
                  <span class="text-emerald-600">✓</span>
                  <span>Giảm 20% mỗi chuyến xe</span>
                </li>
                <li class="flex items-center gap-1.5">
                  <span class="text-emerald-600">✓</span>
                  <span>Miễn phí 20kg hành lý</span>
                </li>
                <li class="flex items-center gap-1.5">
                  <span class="text-emerald-600">✓</span>
                  <span>Tích 1.2x điểm thưởng</span>
                </li>
              </ul>
            </div>

            <button
              type="button"
              class="mt-4 w-full py-2.5 rounded-xl font-black text-xs transition cursor-pointer"
              :class="selectedPlan === 'ECO_20' ? 'bg-red-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
            >
              {{ selectedPlan === 'ECO_20' ? 'Đã Chọn Gói Này' : 'Chọn Gói Eco' }}
            </button>
          </div>

          <!-- GÓI 2: PRO PASS (GIẢM 25%) -->
          <div 
            @click="selectedPlan = 'PRO_25'"
            class="rounded-2xl border-2 p-4 flex flex-col justify-between transition cursor-pointer relative"
            :class="selectedPlan === 'PRO_25' ? 'border-red-600 bg-red-50/20 shadow-md ring-2 ring-red-600/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
          >
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">1 Năm Trọn Gói</span>
                <span v-if="selectedPlan === 'PRO_25'" class="w-4 h-4 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center font-bold">✓</span>
              </div>

              <div>
                <h3 class="font-black text-slate-900 text-base">Pro Pass</h3>
                <p class="text-[11px] text-slate-500">Lựa chọn cân bằng & linh hoạt</p>
              </div>

              <div class="py-2 border-y border-slate-100">
                <div class="text-2xl font-black text-blue-600">
                  {{ targetRole === 'student' ? '139.000đ' : '349.000đ' }}
                </div>
                <div class="text-[10px] text-slate-400">
                  Thời hạn: 1 năm (365 ngày)
                </div>
              </div>

              <ul class="text-xs text-slate-600 space-y-2">
                <li class="flex items-center gap-1.5 font-bold text-slate-800">
                  <span class="text-emerald-600">✓</span>
                  <span>Giảm 25% trọn vẹn 365 ngày</span>
                </li>
                <li class="flex items-center gap-1.5">
                  <span class="text-emerald-600">✓</span>
                  <span>Miễn phí hủy vé 1 lần/tháng</span>
                </li>
                <li class="flex items-center gap-1.5">
                  <span class="text-emerald-600">✓</span>
                  <span>Tích 1.5x điểm thưởng</span>
                </li>
              </ul>
            </div>

            <button
              type="button"
              class="mt-4 w-full py-2.5 rounded-xl font-black text-xs transition cursor-pointer"
              :class="selectedPlan === 'PRO_25' ? 'bg-blue-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
            >
              {{ selectedPlan === 'PRO_25' ? 'Đã Chọn Gói Này' : 'Chọn Gói Pro' }}
            </button>
          </div>

          <!-- GÓI 3: ULTRA VIP PASS (GIẢM 40% - KHUYÊN DÙNG) -->
          <div 
            @click="selectedPlan = 'ULTRA_40'"
            class="rounded-2xl border-2 p-4 flex flex-col justify-between transition cursor-pointer relative bg-gradient-to-b from-amber-50/40 via-white to-red-50/30"
            :class="selectedPlan === 'ULTRA_40' ? 'border-[#dc2626] shadow-lg ring-3 ring-red-600/30' : 'border-amber-300 hover:border-red-500'"
          >
            <!-- Tag Best Value -->
            <div class="absolute -top-3 right-4 px-3 py-0.5 rounded-full bg-gradient-to-r from-red-600 to-amber-500 text-white text-[9.5px] font-black uppercase tracking-wider shadow-sm">
              👑 KHUYÊN DÙNG NHẤT
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300">1 Năm VIP</span>
                <span v-if="selectedPlan === 'ULTRA_40'" class="w-4 h-4 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center font-bold">✓</span>
              </div>

              <div>
                <h3 class="font-black text-slate-900 text-base flex items-center gap-1.5">
                  <span>Ultra VIP Pass</span>
                  <span class="text-amber-500">✨</span>
                </h3>
                <p class="text-[11px] text-amber-900 font-bold">Giảm 40% trọn vẹn 1 năm</p>
              </div>

              <div class="py-2 border-y border-amber-200/60">
                <div class="text-2xl font-black text-red-600">
                  {{ targetRole === 'student' ? '239.000đ' : '599.000đ' }}
                </div>
                <div class="text-[10px] text-slate-500">
                  Thời hạn: 1 năm (365 ngày)
                </div>
              </div>

              <ul class="text-xs text-slate-700 space-y-2">
                <li class="flex items-center gap-1.5 font-black text-red-600">
                  <span>🔥</span>
                  <span>GIẢM 40% MỌI CHUYẾN XE</span>
                </li>
                <li class="flex items-center gap-1.5 font-medium">
                  <span class="text-emerald-600">✓</span>
                  <span>Ưu tiên chọn chỗ VIP tầng 1</span>
                </li>
                <li class="flex items-center gap-1.5 font-medium">
                  <span class="text-emerald-600">✓</span>
                  <span>Đổi/hoàn vé miễn phí 100%</span>
                </li>
                <li class="flex items-center gap-1.5 font-medium">
                  <span class="text-emerald-600">✓</span>
                  <span>Hotline VIP trực ban 24/7</span>
                </li>
              </ul>
            </div>

            <button
              type="button"
              class="mt-4 w-full py-2.5 rounded-xl font-black text-xs transition cursor-pointer shadow-sm"
              :class="selectedPlan === 'ULTRA_40' ? 'bg-[#dc2626] text-white hover:bg-red-700' : 'bg-amber-400 hover:bg-amber-500 text-slate-950'"
            >
              {{ selectedPlan === 'ULTRA_40' ? 'Đã Chọn Gói Ultra' : 'Nâng Cấp Ultra 40%' }}
            </button>
          </div>

        </div>

      </div>

      <!-- FOOTER ACTIONS -->
      <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 flex-shrink-0">
        <div class="text-xs text-slate-500">
          <span>Thanh toán một lần qua <strong>VietQR MBBank</strong>. Kích hoạt tự động sau 3 giây.</span>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="close"
            class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold cursor-pointer"
          >
            Để Sau
          </button>

          <button
            type="button"
            @click="submitSubscription"
            :disabled="isSubmitting"
            class="px-6 py-2.5 rounded-xl bg-[#dc2626] hover:bg-red-700 text-white font-black text-xs shadow-md transition cursor-pointer disabled:opacity-50 flex items-center gap-2"
          >
            <span v-if="isSubmitting" class="animate-spin text-sm">⏳</span>
            <span>{{ isSubmitting ? 'ĐANG KÍCH HOẠT...' : 'KÍCH HOẠT GÓI NGAY ➔' }}</span>
          </button>
        </div>
      </div>

    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
});

const onKeyDown = (e) => {
  if (e.key === 'Escape' && props.isOpen) {
    close();
  }
};

watch(() => props.isOpen, (open) => {
  if (open) {
    document.addEventListener('keydown', onKeyDown);
  } else {
    document.removeEventListener('keydown', onKeyDown);
  }
});

onUnmounted(() => {
  document.removeEventListener('keydown', onKeyDown);
});

const emit = defineEmits(['close']);

const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);

const isStudentUser = computed(() => {
  if (!authUser.value) return false;
  return Boolean(
    authUser.value.is_student ||
    String(authUser.value.email || '').includes('.edu.vn') ||
    String(authUser.value.email || '').includes('sinhvien')
  );
});

const targetRole = ref(isStudentUser.value ? 'student' : 'standard');
const selectedPlan = ref('ULTRA_40');
const isSubmitting = ref(false);

const currentPlanInfo = computed(() => {
  if (!authUser.value) return null;
  const user = authUser.value;
  if (!user.student_registered_at && !user.membership_plan) return null;

  const planName = user.membership_plan === 'ULTRA_40' 
    ? 'Ultra VIP Pass (Giảm 40%)' 
    : (user.membership_plan === 'PRO_25' 
        ? 'Pro Pass (Giảm 25%)' 
        : (user.membership_plan === 'ECO_20' 
            ? 'Eco Pass (Giảm 20%)' 
            : 'Gói Sinh Viên Mặc Định (Giảm 30%)'));

  const regDate = user.student_registered_at ? String(user.student_registered_at).substring(0, 10) : 'Gần đây';
  const expDate = user.membership_expires_at || user.student_discount_expires_at;
  const daysLeft = user.student_days_remaining ?? 365;

  return {
    name: planName,
    registered_at: regDate,
    expires_at: expDate ? String(expDate).substring(0, 10) : '1 năm',
    days_left: daysLeft,
  };
});

const close = () => {
  emit('close');
};

const submitSubscription = () => {
  if (!authUser.value) {
    alert('Vui lòng đăng nhập để đăng ký gói ưu đãi!');
    router.visit('/login');
    return;
  }

  isSubmitting.value = true;
  router.post('/membership/subscribe', {
    plan: selectedPlan.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false;
      close();
    },
    onError: (errors) => {
      isSubmitting.value = false;
      const msg = Object.values(errors).flat().join('\n') || 'Không thể đăng ký gói lúc này.';
      alert(msg);
    }
  });
};
</script>
