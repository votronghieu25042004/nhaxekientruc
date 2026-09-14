<template>
  <div class="min-h-screen flex flex-col bg-slate-950 text-slate-100 selection:bg-blue-600 selection:text-white">
    <Navbar />

    <div class="max-w-2xl mx-auto px-4 py-8 flex-1 w-full space-y-6">
      <div class="text-center space-y-2">
        <span class="px-3 py-1 rounded-full text-xs font-black bg-blue-600/20 text-blue-500 border border-blue-600/30 uppercase tracking-wider">
          Ý Kiến Hành Khách
        </span>
        <h1 class="text-2xl sm:text-3xl font-black text-white">ĐÁNH GIÁ TÀI XẾ & CHUYẾN ĐI</h1>
        <p class="text-xs text-slate-400">Đánh giá của bạn được gửi trực tiếp đến Bác tài và Ban điều hành BusHub Terminal</p>
      </div>

      <div class="bg-slate-900 border border-slate-800 p-6 sm:p-8 rounded-3xl space-y-6 shadow-2xl">
        <!-- Trip Info -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 space-y-2 text-xs">
          <div class="flex justify-between items-center">
            <span class="text-slate-400">Mã vé chuyến xe:</span>
            <span class="font-mono font-black text-blue-500 text-sm">#{{ booking.booking_code }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-400">Hành trình:</span>
            <span class="font-bold text-white">{{ booking.from_location }} ➔ {{ booking.to_location }}</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-400">Xe / Biển số:</span>
            <span class="text-amber-400 font-mono font-bold">{{ booking.license_plate }} ({{ booking.bus_type }})</span>
          </div>
        </div>

        <!-- Driver Profile Card -->
        <div v-if="booking.driver" class="flex items-center gap-4 p-4 bg-gradient-to-r from-blue-600/10 via-slate-800/80 to-slate-900 border border-blue-600/30 rounded-2xl">
          <div class="w-14 h-14 rounded-2xl bg-blue-600/20 flex items-center justify-center text-3xl shrink-0 ring-2 ring-blue-600/40">
            👨‍✈️
          </div>
          <div>
            <span class="text-[11px] text-blue-500 font-extrabold uppercase block tracking-wider">Tài xế lái chính:</span>
            <h3 class="text-base font-black text-white">{{ booking.driver.name }}</h3>
            <span class="text-xs text-slate-400">{{ booking.driver.experience_years }} năm kinh nghiệm lái đường dài • Đánh giá: ★ {{ booking.driver.avg_rating }}</span>
          </div>
        </div>

        <!-- Rating Form -->
        <form @submit.prevent="submitReview" class="space-y-6">
          <!-- 1. Overall Rating -->
          <div class="space-y-2 text-center bg-slate-950/60 p-4 rounded-2xl border border-slate-800">
            <label class="text-sm font-bold text-white block">1. Mức độ hài lòng chung về chuyến đi</label>
            <div class="flex justify-center gap-3">
              <button
                v-for="star in 5"
                :key="star"
                type="button"
                @click="form.rating = star"
                class="text-3xl transition-transform hover:scale-125 cursor-pointer"
                :class="star <= form.rating ? 'text-amber-400' : 'text-slate-700'"
              >
                ★
              </button>
            </div>
            <div class="text-xs font-bold text-blue-500">
              {{ form.rating === 5 ? 'Tuyệt vời (5/5 sao)' : (form.rating === 4 ? 'Hài lòng (4/5 sao)' : 'Bình thường (3/5 sao)') }}
            </div>
          </div>

          <!-- 2. Safety Driving Rating -->
          <div class="space-y-2 text-center bg-slate-950/60 p-4 rounded-2xl border border-slate-800">
            <label class="text-sm font-bold text-white block">2. Kỹ năng lái xe an toàn, êm ái của Bác tài</label>
            <div class="flex justify-center gap-3">
              <button
                v-for="star in 5"
                :key="star"
                type="button"
                @click="form.safety_rating = star"
                class="text-2xl transition-transform hover:scale-125 cursor-pointer"
                :class="star <= form.safety_rating ? 'text-amber-400' : 'text-slate-700'"
              >
                ★
              </button>
            </div>
          </div>

          <!-- 3. Service Attitude -->
          <div class="space-y-2 text-center bg-slate-950/60 p-4 rounded-2xl border border-slate-800">
            <label class="text-sm font-bold text-white block">3. Thái độ phục vụ & hướng dẫn của Lơ xe / Tài xế</label>
            <div class="flex justify-center gap-3">
              <button
                v-for="star in 5"
                :key="star"
                type="button"
                @click="form.service_rating = star"
                class="text-2xl transition-transform hover:scale-125 cursor-pointer"
                :class="star <= form.service_rating ? 'text-amber-400' : 'text-slate-700'"
              >
                ★
              </button>
            </div>
          </div>

          <!-- Quick feedback tags -->
          <div class="space-y-2">
            <label class="text-xs font-bold text-slate-300 uppercase block">Điểm bạn thấy hài lòng nhất:</label>
            <div class="flex flex-wrap gap-2">
              <button 
                type="button" 
                v-for="tag in quickTags" 
                :key="tag"
                @click="addTagToComment(tag)"
                class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-300 border border-slate-700 hover:text-white transition">
                + {{ tag }}
              </button>
            </div>
          </div>

          <!-- Comment -->
          <div>
            <label class="text-xs font-bold text-slate-300 uppercase block mb-1">Nhận xét chi tiết (Lời khen hoặc góp ý chân thành)</label>
            <textarea
              v-model="form.comment"
              rows="3"
              placeholder="Ví dụ: Bác tài lái rất êm ái, xe chạy đúng giờ, lơ xe nhiệt tình xách hành lý giúp..."
              class="w-full bg-slate-950 border border-slate-700 rounded-2xl p-4 text-white text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
            ></textarea>
          </div>

          <!-- ANONYMOUS OPTION (ẨN DANH HOẶC CÔNG KHAI) -->
          <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-2">
            <label class="flex items-center space-x-3 cursor-pointer select-none">
              <input 
                type="checkbox" 
                v-model="form.is_anonymous" 
                class="w-5 h-5 rounded text-blue-600 bg-slate-900 border-slate-700 focus:ring-blue-600" />
              <div>
                <span class="text-sm font-bold text-white flex items-center space-x-1.5">
                  <span>🕶️ Đánh giá ẩn danh</span>
                  <span class="text-[10px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded-full border border-slate-700">Bảo mật</span>
                </span>
                <p class="text-xs text-slate-400">Khi chọn ẩn danh, tên và số điện thoại của bạn sẽ được giấu kín (hiển thị: "Hành khách ẩn danh").</p>
              </div>
            </label>
          </div>

          <button
            type="submit"
            class="w-full py-4 rounded-2xl bg-gradient-to-r from-blue-600 to-amber-500 hover:from-blue-600 hover:to-amber-600 text-white font-black text-base shadow-xl shadow-blue-600/30 transition-all cursor-pointer"
          >
            GỬI ĐÁNH GIÁ CHO BÁC TÀI
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';

const props = defineProps({
  booking: Object,
});

const quickTags = [
  'Lái xe êm ái, an toàn',
  'Xe chạy đúng giờ',
  'Tài xế thân thiện, chu đáo',
  'Xe sạch sẽ, thơm tho',
  'Máy lạnh mát mẻ',
  'Lơ xe nhiệt tình hỗ trợ'
];

const form = reactive({
  rating: props.booking.review?.rating || 5,
  safety_rating: props.booking.review?.safety_rating || 5,
  service_rating: props.booking.review?.service_rating || 5,
  comment: props.booking.review?.comment || '',
  is_anonymous: props.booking.review?.is_anonymous ? true : false,
});

const addTagToComment = (tag) => {
  if (form.comment) {
    if (!form.comment.includes(tag)) {
      form.comment += `, ${tag}`;
    }
  } else {
    form.comment = tag;
  }
};

const submitReview = () => {
  router.post(`/booking/${props.booking.booking_code}/review`, form);
};
</script>
