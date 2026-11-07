<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-800 font-sans selection:bg-red-500 selection:text-white">
    <Navbar />

    <!-- Main Container -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-10 flex-1 w-full space-y-6">
      
      <!-- Back Link & Title Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <Link
          :href="'/booking/' + booking.booking_code + '/detail'"
          class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-red-600 transition"
        >
          <span>← Quay lại chi tiết vé</span>
          <span class="font-mono text-slate-400">#{{ booking.booking_code }}</span>
        </Link>

        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-600 text-[11px] font-black uppercase tracking-wider self-start sm:self-auto">
          <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
          <span>Khảo Sát Chất Lượng Chuyến Xe</span>
        </div>
      </div>

      <div class="text-center sm:text-left space-y-1">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
          ĐÁNH GIÁ BÁC TÀI & CHUYẾN ĐI
        </h1>
        <p class="text-xs sm:text-sm text-slate-500">
          Ý kiến quý báu của quý khách giúp nhaxekientruc không ngừng nâng cao chất lượng phục vụ an toàn và êm ái.
        </p>
      </div>

      <!-- ========================================================================= -->
      <!-- HIỆU ỨNG SA BÀN XA LỘ XE KHÁCH CHẠY QUA CHẠY LẠI (GIỐNG ĐĂNG KÝ)          -->
      <!-- ========================================================================= -->
      <div class="relative rounded-3xl bg-gradient-to-br from-[#1a0508] via-[#380811] to-[#540b17] border-2 border-red-900/40 text-white p-4 sm:p-5 shadow-xl overflow-hidden select-none">
        
        <!-- Stars & Glow Backdrop -->
        <div class="absolute inset-0 pointer-events-none opacity-60 overflow-hidden">
          <div class="star star-1"></div>
          <div class="star star-2"></div>
          <div class="star star-3"></div>
          <div class="star star-4"></div>
          <div class="star star-5"></div>
          <div class="absolute -top-10 -right-10 w-44 h-44 rounded-full bg-amber-500/20 blur-3xl"></div>
        </div>

        <!-- Header Sa Bàn: Xa lộ Đà Nẵng + Nút Còi xe -->
        <div class="relative z-10 flex items-center justify-between gap-2 text-xs font-bold mb-3 pb-2.5 border-b border-white/10">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-slate-100 font-black text-xs sm:text-sm tracking-wide">
              Xa lộ Đà Nẵng ⇄ Liên Tỉnh Toàn Quốc
            </span>
          </div>

          <button
            type="button"
            @click="honkHorn"
            class="px-3 py-1 rounded-full text-[11px] font-black transition flex items-center gap-1.5 cursor-pointer border border-amber-500/60 bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 active:scale-95 shadow-sm"
            :class="isHornHonking ? 'ring-2 ring-amber-400 bg-amber-400 text-slate-950 font-black' : ''"
            title="Bấm để còi xe chào hành khách"
          >
            <span>📢</span>
            <span>{{ isHornHonking ? 'BÍP BÍP!' : 'Còi Xe' }}</span>
          </button>
        </div>

        <!-- Lòng Đường & 2 Làn Xe Chạy 2 Chiều -->
        <div class="relative h-24 w-full rounded-2xl bg-slate-950 border border-slate-800/80 overflow-hidden flex flex-col justify-between py-1.5 px-2 shadow-inner">
          
          <!-- Làn 1: Xe chạy Trái sang Phải (Đà Nẵng đi các tỉnh) -->
          <div class="relative h-9 w-full flex items-center overflow-hidden">
            <div class="bus-lane-1 absolute flex items-center gap-2 px-3 py-1 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 border border-amber-300 text-white shadow-lg text-[11px] font-black whitespace-nowrap">
              <span class="text-sm">🚌</span>
              <span class="tracking-wider">NHAXEKIENTRUC VIP</span>
              <span class="text-[9.5px] px-1.5 py-0.2 rounded bg-white/20 text-amber-200 font-mono">{{ booking.license_plate || '43B-012.34' }}</span>
              <span class="w-1.5 h-1.5 rounded-full bg-amber-300 animate-ping"></span>
            </div>
          </div>

          <!-- Vạch kẻ đường đứt đoạn chuyển động -->
          <div class="road-strip h-1 w-full opacity-80 my-0.5"></div>

          <!-- Làn 2: Xe chạy Phải sang Trái (Xe trung chuyển đón tận nơi 0đ) -->
          <div class="relative h-9 w-full flex items-center overflow-hidden">
            <div class="bus-lane-2 absolute flex items-center gap-2 px-3 py-1 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-400 border border-white text-slate-950 shadow-lg text-[10.5px] font-black whitespace-nowrap">
              <span class="text-sm">🚐</span>
              <span>TRUNG CHUYỂN 0Đ</span>
              <span class="text-[9px] px-1.5 py-0.2 rounded bg-black/15 text-slate-900 font-mono">ĐÓN TRẢ TẬN NƠI</span>
            </div>
          </div>

        </div>

        <!-- Caption dưới sa bàn -->
        <div class="relative z-10 flex items-center justify-between text-[11px] text-slate-300 mt-2 px-1">
          <span class="flex items-center gap-1 text-amber-300 font-bold">
            <span>✓</span> Bác tài và nhà xe luôn đồng hành cùng bạn
          </span>
          <span class="text-slate-400 hidden sm:inline">100% tài xế được đào tạo chuẩn 5 sao</span>
        </div>

      </div>

      <!-- ========================================================================= -->
      <!-- CARD THÔNG TIN VÉ & PROFILE BÁC TÀI PHỤ TRÁCH                             -->
      <!-- ========================================================================= -->
      <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-5 sm:p-6 space-y-4">
        
        <!-- Hàng 1: Mã vé & Hành trình -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-100">
          <div>
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">THÔNG TIN HÀNH TRÌNH</span>
            <h3 class="text-base sm:text-lg font-black text-slate-900 mt-0.5">
              {{ booking.from_location }} <span class="text-red-600">⇄</span> {{ booking.to_location }}
            </h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5">
              Khởi hành: <strong class="text-slate-800">{{ booking.departure_time || 'Đúng giờ xuất bến' }}</strong>
            </p>
          </div>

          <div class="text-left sm:text-right">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">MÃ VÉ ĐIỆN TỬ</span>
            <span class="inline-block px-3 py-1 rounded-xl bg-red-50 text-red-700 font-mono font-black text-sm border border-red-200 mt-0.5">
              #{{ booking.booking_code }}
            </span>
            <div class="text-[11px] text-slate-500 mt-0.5 font-bold font-mono">
              Biển số: {{ booking.license_plate }} ({{ booking.bus_type }})
            </div>
          </div>
        </div>

        <!-- Hàng 2: Profile Bác Tài -->
        <div class="flex items-center gap-4 p-4 rounded-2xl bg-gradient-to-r from-red-50 via-rose-50/60 to-amber-50 border border-red-100">
          <!-- Avatar Bác tài -->
          <div class="w-14 h-14 rounded-2xl bg-white border-2 border-red-200 flex items-center justify-center text-3xl shadow-sm shrink-0 overflow-hidden">
            <img 
              v-if="booking.driver?.avatar" 
              :src="booking.driver.avatar" 
              :alt="booking.driver?.name"
              class="w-full h-full object-cover"
            />
            <span v-else>👨‍✈️</span>
          </div>

          <!-- Thông tin Bác tài -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-[10.5px] font-black uppercase tracking-wider text-red-700 bg-red-100 px-2 py-0.5 rounded-full">
                Tài Xế Phụ Trách
              </span>
              <span class="text-[11px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                <span>★</span>
                <span>{{ booking.driver?.avg_rating ? Number(booking.driver.avg_rating).toFixed(1) : '5.0' }} / 5.0</span>
              </span>
            </div>

            <h4 class="text-base sm:text-lg font-black text-slate-900 truncate mt-1">
              {{ booking.driver?.name || 'Bác tài nhaxekientruc' }}
            </h4>

            <p class="text-xs text-slate-600 font-medium">
              Kinh nghiệm: <strong class="text-slate-800">{{ booking.driver?.experience_years || 8 }} năm</strong> lái đường dài an toàn • Tuyến liên tỉnh
            </p>
          </div>
        </div>

      </div>

      <!-- ========================================================================= -->
      <!-- FORM ĐÁNH GIÁ CHI TIẾT (3 TIÊU CHÍ 5 SAO + CHIPS + GÓP Ý + NÚT GỬI)       -->
      <!-- ========================================================================= -->
      <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 space-y-6">
        
        <form @submit.prevent="submitReview" class="space-y-6">

          <!-- 1. TIÊU CHÍ 1: MỨC ĐỘ HÀI LÒNG CHUNG -->
          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-center space-y-2.5">
            <label class="text-sm font-black text-slate-900 block">
              1. Mức độ hài lòng chung về chuyến đi của bạn
            </label>
            <p class="text-xs text-slate-500">Chạm vào số sao để xếp hạng trải nghiệm</p>

            <div class="flex justify-center items-center gap-2 sm:gap-3 py-1">
              <button
                v-for="star in 5"
                :key="'rating-' + star"
                type="button"
                @click="form.rating = star"
                @mouseenter="hoverRating = star"
                @mouseleave="hoverRating = 0"
                class="text-3xl sm:text-4xl transition-all duration-150 transform hover:scale-125 cursor-pointer focus:outline-none"
                :class="star <= (hoverRating || form.rating) ? 'text-amber-400 drop-shadow-[0_2px_8px_rgba(251,191,36,0.5)]' : 'text-slate-300 hover:text-amber-200'"
              >
                ★
              </button>
            </div>

            <div class="inline-block px-3 py-1 rounded-full bg-white border border-slate-200 text-xs font-black text-slate-800 shadow-2xs">
              {{ getRatingText(form.rating) }}
            </div>
          </div>

          <!-- 2. TIÊU CHÍ 2 & 3: GRID 2 CỘT -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <!-- Tiêu chí 2: Lái xe an toàn -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-center space-y-2">
              <label class="text-xs font-black text-slate-900 block">
                2. Kỹ năng lái xe an toàn, êm ái
              </label>
              <div class="flex justify-center gap-2">
                <button
                  v-for="star in 5"
                  :key="'safety-' + star"
                  type="button"
                  @click="form.safety_rating = star"
                  class="text-2xl transition-transform hover:scale-125 cursor-pointer focus:outline-none"
                  :class="star <= form.safety_rating ? 'text-amber-400' : 'text-slate-300'"
                >
                  ★
                </button>
              </div>
              <span class="text-[11px] font-bold text-slate-600 block">
                {{ form.safety_rating }}/5 sao - {{ form.safety_rating >= 4 ? 'Rất êm ái' : 'Tạm ổn' }}
              </span>
            </div>

            <!-- Tiêu chí 3: Thái độ phục vụ -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-center space-y-2">
              <label class="text-xs font-black text-slate-900 block">
                3. Thái độ phục vụ của Tài xế & Lơ xe
              </label>
              <div class="flex justify-center gap-2">
                <button
                  v-for="star in 5"
                  :key="'service-' + star"
                  type="button"
                  @click="form.service_rating = star"
                  class="text-2xl transition-transform hover:scale-125 cursor-pointer focus:outline-none"
                  :class="star <= form.service_rating ? 'text-amber-400' : 'text-slate-300'"
                >
                  ★
                </button>
              </div>
              <span class="text-[11px] font-bold text-slate-600 block">
                {{ form.service_rating }}/5 sao - {{ form.service_rating >= 4 ? 'Nhiệt tình, chu đáo' : 'Bình thường' }}
              </span>
            </div>

          </div>

          <!-- CHIPS GỢI Ý NHANH (QUICK TAGS) -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="text-xs font-black uppercase tracking-wider text-slate-700">
                Điểm bạn hài lòng nhất (Bấm chọn nhanh):
              </label>
              <span class="text-[11px] text-slate-400">Chọn nhiều mục</span>
            </div>

            <div class="flex flex-wrap gap-2">
              <button
                v-for="tag in quickTags"
                :key="tag"
                type="button"
                @click="toggleTag(tag)"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer border flex items-center gap-1.5"
                :class="isTagSelected(tag) 
                  ? 'bg-red-600 text-white border-red-600 shadow-sm shadow-red-500/20' 
                  : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200 hover:border-slate-300'"
              >
                <span>{{ isTagSelected(tag) ? '✓' : '+' }}</span>
                <span>{{ tag }}</span>
              </button>
            </div>
          </div>

          <!-- Ô NHẬP NHẬN XÉT CHI TIẾT -->
          <div class="space-y-1.5">
            <label class="text-xs font-black uppercase tracking-wider text-slate-700 block">
              Nhận xét chi tiết (Lời khen ngợi hoặc góp ý chân thành)
            </label>
            <textarea
              v-model="form.comment"
              rows="3"
              placeholder="Ví dụ: Bác tài lái rất êm, qua cua nhẹ nhàng, xe sạch sẽ thơm tho, lơ xe nhiệt tình xách hành lý giúp..."
              class="w-full bg-slate-50 border border-slate-300 focus:border-red-500 focus:bg-white rounded-2xl p-4 text-xs sm:text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-red-500/20 transition leading-relaxed shadow-2xs"
            ></textarea>
            <p class="text-[11px] text-slate-400 flex items-center justify-between">
              <span>Đánh giá của bạn sẽ được gửi trực tiếp đến Bác tài và Ban giám đốc nhà xe.</span>
              <span class="font-mono">{{ (form.comment || '').length }}/1000</span>
            </p>
          </div>

          <!-- TÙY CHỌN ĐÁNH GIÁ ẨN DANH -->
          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
            <label class="flex items-start space-x-3 cursor-pointer select-none">
              <input
                type="checkbox"
                v-model="form.is_anonymous"
                class="w-5 h-5 rounded text-red-600 border-slate-300 focus:ring-red-500 mt-0.5 cursor-pointer"
              />
              <div>
                <div class="text-xs sm:text-sm font-black text-slate-900 flex items-center gap-2">
                  <span>Đánh giá ẩn danh</span>
                  <span class="text-[10px] bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full font-bold">Bảo mật</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                  Khi chọn ẩn danh, hệ thống sẽ ẩn tên thật và số điện thoại của bạn (hiển thị trên bảng tin là: "Hành khách ẩn danh").
                </p>
              </div>
            </label>
          </div>

          <!-- NÚT GỬI ĐÁNH GIÁ -->
          <div class="pt-2">
            <button
              type="submit"
              :disabled="isSubmitting"
              class="btn-shimmer relative overflow-hidden w-full py-4 rounded-2xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-500 hover:to-amber-500 text-white font-black text-sm sm:text-base uppercase tracking-wider shadow-lg shadow-red-500/30 active:scale-98 transition-all cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <span>{{ isSubmitting ? 'ĐANG GỬI ĐÁNH GIÁ...' : 'GỬI ĐÁNH GIÁ CHUYẾN ĐI CHO BÁC TÀI →' }}</span>
            </button>
          </div>

        </form>

      </div>

    </main>

    <Footer />
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
  booking: Object,
});

const hoverRating = ref(0);
const isSubmitting = ref(false);
const isHornHonking = ref(false);

const quickTags = [
  'Lái xe êm ái, an toàn',
  'Xe chạy đúng giờ',
  'Tài xế thân thiện, chu đáo',
  'Xe sạch sẽ, thơm tho',
  'Máy lạnh mát mẻ',
  'Lơ xe nhiệt tình hỗ trợ',
  'Không nhồi nhét khách',
  'Đón đúng điểm hẹn',
];

const form = reactive({
  rating: props.booking?.review?.rating || 5,
  safety_rating: props.booking?.review?.safety_rating || 5,
  service_rating: props.booking?.review?.service_rating || 5,
  comment: props.booking?.review?.comment || '',
  is_anonymous: props.booking?.review?.is_anonymous ? true : false,
});

const getRatingText = (val) => {
  switch (val) {
    case 5: return '⭐ 5/5 sao - Tuyệt vời xuất sắc';
    case 4: return '⭐ 4/5 sao - Rất hài lòng';
    case 3: return '⭐ 3/5 sao - Bình thường, tạm ổn';
    case 2: return '⭐ 2/5 sao - Cần cải thiện';
    case 1: return '⭐ 1/5 sao - Không hài lòng';
    default: return '⭐ 5/5 sao - Rất hài lòng';
  }
};

const isTagSelected = (tag) => {
  return (form.comment || '').includes(tag);
};

const toggleTag = (tag) => {
  if (!form.comment) {
    form.comment = tag;
    return;
  }
  if (form.comment.includes(tag)) {
    // Remove tag
    const parts = form.comment.split(', ').filter(p => p.trim() !== tag);
    form.comment = parts.join(', ');
  } else {
    // Add tag
    form.comment = `${form.comment}, ${tag}`;
  }
};

const honkHorn = () => {
  isHornHonking.value = true;
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(420, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(360, ctx.currentTime + 0.25);
    gain.gain.setValueAtTime(0.2, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.start();
    osc.stop(ctx.currentTime + 0.3);
  } catch (e) {
    // AudioContext blocked
  }
  setTimeout(() => {
    isHornHonking.value = false;
  }, 600);
};

const submitReview = () => {
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  router.post(`/booking/${props.booking.booking_code}/review`, form, {
    onFinish: () => {
      isSubmitting.value = false;
    }
  });
};
</script>

<style scoped>
/* ========================================================================= */
/* ANIMATION XE CHẠY 2 CHIỀU & DẢI ĐƯỜNG CAO TỐC CHO TRANG ĐÁNH GIÁ          */
/* ========================================================================= */

/* Ngôi sao lấp lánh */
.star {
  position: absolute;
  width: 2px;
  height: 2px;
  background: white;
  border-radius: 9999px;
  box-shadow: 0 0 6px rgba(255, 255, 255, 0.9);
  animation: starTwinkle 2.5s infinite ease-in-out alternate;
}
.star-1 { top: 20%; left: 15%; animation-delay: 0.2s; }
.star-2 { top: 35%; left: 70%; animation-delay: 1.1s; }
.star-3 { top: 60%; left: 40%; animation-delay: 0.6s; }
.star-4 { top: 15%; left: 85%; animation-delay: 1.8s; }
.star-5 { top: 75%; left: 25%; animation-delay: 0.9s; }

@keyframes starTwinkle {
  0% { opacity: 0.2; transform: scale(0.8); }
  50% { opacity: 1; transform: scale(1.3); }
  100% { opacity: 0.3; transform: scale(0.9); }
}

/* Làn 1: Xe khách đỏ chạy từ Trái sang Phải */
.bus-lane-1 {
  animation: driveLeftToRight 8s linear infinite;
}
@keyframes driveLeftToRight {
  0% {
    transform: translateX(-240px);
  }
  100% {
    transform: translateX(780px);
  }
}

/* Làn 2: Xe trung chuyển vàng chạy từ Phải sang Trái */
.bus-lane-2 {
  animation: driveRightToLeft 9.5s linear infinite;
}
@keyframes driveRightToLeft {
  0% {
    transform: translateX(780px);
  }
  100% {
    transform: translateX(-240px);
  }
}

/* Dải kẻ đường cao tốc đứt đoạn chuyển động liên tục */
.road-strip {
  background-image: repeating-linear-gradient(
    90deg,
    transparent,
    transparent 16px,
    rgba(245, 158, 11, 0.85) 16px,
    rgba(245, 158, 11, 0.85) 32px
  );
  background-size: 64px 3px;
  background-position: center;
  background-repeat: repeat-x;
  animation: roadStripMove 1s linear infinite;
}
@keyframes roadStripMove {
  0% { background-position-x: 0; }
  100% { background-position-x: -64px; }
}

/* Ánh kim lướt qua nút Submit */
.btn-shimmer::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 50%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.35),
    transparent
  );
  transform: skewX(-20deg);
  animation: shimmerMove 3.5s infinite;
}
@keyframes shimmerMove {
  0% { left: -100%; }
  25% { left: 200%; }
  100% { left: 200%; }
}
</style>
