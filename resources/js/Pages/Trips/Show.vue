<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-800 font-sans">
    <Navbar />

    <!-- Top 3-Step Breadcrumb Progress Bar -->
    <div class="bg-white border-b border-slate-200 sticky top-16 z-30 shadow-xs">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5">
        <div class="flex items-center justify-center space-x-6 sm:space-x-12 text-xs sm:text-sm font-bold">
          
          <!-- Step 1 -->
          <button
            @click="step = 1"
            class="flex items-center space-x-2 transition cursor-pointer"
            :class="step === 1 ? 'text-[#2563EB] border-b-2 border-[#2563EB] pb-1' : (step > 1 ? 'text-emerald-600' : 'text-slate-400')"
          >
            <span v-if="step > 1">✓</span>
            <span v-else>1.</span>
            <span>Chọn Chỗ Ngồi</span>
          </button>

          <!-- Step 2 -->
          <button
            @click="selectedSeats.length > 0 ? step = 2 : alert('Vui lòng chọn ghế trước!')"
            class="flex items-center space-x-2 transition cursor-pointer"
            :class="step === 2 ? 'text-[#2563EB] border-b-2 border-[#2563EB] pb-1' : (step > 2 ? 'text-emerald-600' : 'text-slate-400')"
          >
            <span v-if="step > 2">✓</span>
            <span v-else>2.</span>
            <span>Điểm Đón & Điểm Trả</span>
          </button>

          <!-- Step 3 -->
          <button
            @click="selectedSeats.length > 0 && selectedPickup && selectedDropoff ? step = 3 : null"
            class="flex items-center space-x-2 transition cursor-pointer"
            :class="step === 3 ? 'text-[#2563EB] border-b-2 border-[#2563EB] pb-1' : 'text-slate-400'"
          >
            <span>3. Thông Tin & Thanh Toán</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content Body -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">

      <!-- ==================== BƯỚC 1: CHỌN CHỖ NGỒI 2 TẦNG ==================== -->
      <div v-if="step === 1" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left: Seat Map Card (5 Cols) -->
        <div class="lg:col-span-5 bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-5">
          
          <!-- Seat Hold Timer (3 minutes) -->
          <div v-if="selectedSeats.length > 0" class="p-2.5 rounded-xl bg-blue-50 border border-blue-200 text-[#2563EB] text-xs font-bold flex items-center justify-between animate-pulse">
            <span>⏱️ Ghế đang giữ trong:</span>
            <span class="font-mono text-sm font-black">{{ timerMinutes }}:{{ timerSeconds < 10 ? '0' + timerSeconds : timerSeconds }}</span>
          </div>

          <!-- Bus Frame Container -->
          <div class="border-2 border-slate-200 rounded-3xl p-4 bg-slate-50/60 space-y-4">
            
            <!-- Floor Headers -->
            <div class="grid grid-cols-2 text-center text-xs font-black text-slate-700 pb-2 border-b border-slate-200">
              <div class="flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <span>Tầng Dưới</span>
              </div>
              <div class="flex items-center justify-center gap-1.5">
                <span>Tầng Trên</span>
              </div>
            </div>

            <!-- Seat Grids Side by Side -->
            <div class="grid grid-cols-2 gap-4">
              <!-- Floor 1 -->
              <div class="space-y-2.5">
                <div v-for="(row, rIdx) in floor1Rows" :key="'f1-' + rIdx" class="grid grid-cols-3 gap-1.5">
                  <button
                    v-for="seat in [row.left, row.middle, row.right]"
                    :key="seat.seat_number"
                    @click="toggleSeat(seat)"
                    :disabled="seat.status === 'booked'"
                    class="h-11 rounded-lg border-2 flex items-center justify-center font-black text-[11px] transition-all cursor-pointer select-none"
                    :class="{
                      'bg-slate-200 text-slate-400 border-slate-300 cursor-not-allowed': seat.status === 'booked',
                      'bg-[#2563EB] text-white border-[#2563EB] shadow-md scale-105': isSeatSelected(seat.seat_number),
                      'bg-white text-slate-700 border-emerald-500 hover:bg-emerald-50': seat.status === 'available' && !isSeatSelected(seat.seat_number)
                    }"
                  >
                    <span v-if="seat.status === 'booked'">✕</span>
                    <span v-else>{{ seat.seat_number }}</span>
                  </button>
                </div>
              </div>

              <!-- Floor 2 -->
              <div class="space-y-2.5">
                <div v-for="(row, rIdx) in floor2Rows" :key="'f2-' + rIdx" class="grid grid-cols-3 gap-1.5">
                  <button
                    v-for="seat in [row.left, row.middle, row.right]"
                    :key="seat.seat_number"
                    @click="toggleSeat(seat)"
                    :disabled="seat.status === 'booked'"
                    class="h-11 rounded-lg border-2 flex items-center justify-center font-black text-[11px] transition-all cursor-pointer select-none"
                    :class="{
                      'bg-slate-200 text-slate-400 border-slate-300 cursor-not-allowed': seat.status === 'booked',
                      'bg-[#2563EB] text-white border-[#2563EB] shadow-md scale-105': isSeatSelected(seat.seat_number),
                      'bg-white text-slate-700 border-emerald-500 hover:bg-emerald-50': seat.status === 'available' && !isSeatSelected(seat.seat_number)
                    }"
                  >
                    <span v-if="seat.status === 'booked'">✕</span>
                    <span v-else>{{ seat.seat_number }}</span>
                  </button>
                </div>
              </div>
            </div>

          </div>

          <!-- Seat Legend -->
          <div class="border-t border-slate-100 pt-3">
            <div class="grid grid-cols-3 gap-2 text-[11px] text-slate-600 font-medium text-center">
              <div class="flex items-center justify-center space-x-1.5">
                <div class="w-3.5 h-4 rounded border-2 border-emerald-500 bg-white"></div>
                <span>Còn trống</span>
              </div>
              <div class="flex items-center justify-center space-x-1.5">
                <div class="w-3.5 h-4 rounded bg-slate-200 border border-slate-300 text-[9px] text-slate-400 font-bold leading-4">✕</div>
                <span>Đã bán</span>
              </div>
              <div class="flex items-center justify-center space-x-1.5">
                <div class="w-3.5 h-4 rounded bg-[#2563EB] border border-[#2563EB]"></div>
                <span>Đang chọn</span>
              </div>
            </div>
          </div>

        </div>

        <!-- Right: Trip Info & Summary (7 Cols) -->
        <div class="lg:col-span-7 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-6">
          
          <!-- Trip Operator Header -->
          <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
              <div class="text-xs font-bold text-[#2563EB] uppercase tracking-wider">Chuyến Xe BusHub Terminal</div>
              <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">
                {{ trip.route?.name || 'Tuyến Đà Nẵng ➔ Đông Hà (Quảng Trị)' }}
              </h2>
              <div class="text-xs text-slate-500 mt-1">
                Xuất bến: <strong class="text-slate-800">{{ trip.departure_time }}</strong> • Biển số: <strong class="text-slate-800">{{ trip.bus?.license_plate }}</strong> • Bác tài: <strong class="text-slate-800">{{ trip.driver?.name }}</strong>
              </div>
            </div>
          </div>

          <!-- Selected Seats Summary Box -->
          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
            <div class="flex justify-between items-center text-xs">
              <span class="text-slate-500">Ghế đã chọn:</span>
              <span class="font-black text-sm text-[#2563EB]">
                {{ selectedSeats.length > 0 ? selectedSeats.map(s => s.seat_number).join(', ') : 'Chưa chọn ghế' }}
              </span>
            </div>
            <div class="flex justify-between items-center text-xs pt-2 border-t border-slate-200">
              <span class="text-slate-500">Tạm tính:</span>
              <span class="font-black text-base text-slate-900">{{ formatPrice(totalSeatPrice) }}đ</span>
            </div>
          </div>

          <!-- Step 1 Next Button -->
          <div class="flex justify-end pt-2">
            <button
              @click="goToStep2"
              :disabled="selectedSeats.length === 0"
              class="px-8 py-3.5 rounded-2xl bg-[#2563EB] hover:bg-[#D84315] disabled:bg-slate-200 text-white disabled:text-slate-400 font-bold text-sm shadow-lg shadow-blue-600/20 transition cursor-pointer disabled:cursor-not-allowed"
            >
              Tiếp Tục: Chọn Điểm Đón & Trả →
            </button>
          </div>

        </div>

      </div>

      <!-- ==================== BƯỚC 2: CHỌN ĐIỂM ĐÓN & ĐIỂM TRẢ ==================== -->
      <div v-else-if="step === 2" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-8 max-w-4xl mx-auto">
        <div>
          <h2 class="text-xl font-black text-slate-900">Chọn Điểm Đón & Điểm Trả Trên Tuyến</h2>
          <p class="text-xs text-slate-500 mt-1">Danh sách trạm dừng chính xác theo lộ trình thực tế của chuyến xe</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Pickup Station Selection -->
          <div class="space-y-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-full bg-blue-100 text-[#2563EB] flex items-center justify-center text-xs font-bold">1</span>
              Điểm Đón Khách (Điểm đi)
            </h3>
            <div class="space-y-2">
              <label 
                v-for="st in pickupStops" 
                :key="'pickup-' + st.id"
                class="p-3.5 rounded-2xl border flex items-start space-x-3 cursor-pointer transition select-none"
                :class="selectedPickup?.id === st.id ? 'bg-blue-50 border-[#2563EB]' : 'border-slate-200 hover:bg-slate-50'">
                <input type="radio" name="pickup" :value="st" v-model="selectedPickup" class="w-4 h-4 text-[#2563EB] mt-0.5" />
                <div class="text-xs">
                  <div class="font-bold text-slate-800">{{ st.time }} - {{ st.name }}</div>
                  <div class="text-[11px] text-slate-500 mt-0.5">{{ st.address }}</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Dropoff Station Selection -->
          <div class="space-y-3">
            <h3 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">2</span>
              Điểm Trả Khách (Điểm đến)
            </h3>
            <div class="space-y-2">
              <label 
                v-for="st in dropoffStops" 
                :key="'dropoff-' + st.id"
                class="p-3.5 rounded-2xl border flex items-start space-x-3 cursor-pointer transition select-none"
                :class="selectedDropoff?.id === st.id ? 'bg-emerald-50 border-emerald-500' : 'border-slate-200 hover:bg-slate-50'">
                <input type="radio" name="dropoff" :value="st" v-model="selectedDropoff" class="w-4 h-4 text-emerald-600 mt-0.5" />
                <div class="text-xs">
                  <div class="font-bold text-slate-800">{{ st.time }} - {{ st.name }}</div>
                  <div class="text-[11px] text-slate-500 mt-0.5">{{ st.address }}</div>
                </div>
              </label>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
          <button @click="step = 1" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition cursor-pointer">
            ← Quay Lại Chọn Ghế
          </button>
          <button
            @click="goToStep3"
            :disabled="!selectedPickup || !selectedDropoff"
            class="px-8 py-3.5 rounded-2xl bg-[#2563EB] hover:bg-[#D84315] disabled:bg-slate-200 text-white disabled:text-slate-400 font-bold text-sm shadow-lg shadow-blue-600/20 transition cursor-pointer"
          >
            Tiếp Tục: Điền Thông Tin & Thanh Toán →
          </button>
        </div>
      </div>

      <!-- ==================== BƯỚC 3: ĐIỀN THÔNG TIN & THANH TOÁN CỌC ==================== -->
      <div v-else class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-8 max-w-4xl mx-auto">
        <div>
          <h2 class="text-xl font-black text-slate-900">Thông Tin Hành Khách & Thanh Toán Cọc</h2>
          <p class="text-xs text-slate-500 mt-1">Mã vé điện tử & Mã QR Check-in sẽ được gửi trực tiếp về Gmail của bạn</p>
        </div>

        <form @submit.prevent="submitBooking" class="space-y-6">
          <!-- Passenger Info Inputs -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Họ và tên hành khách</label>
              <input
                v-model="customerForm.name"
                required
                type="text"
                placeholder="Nguyễn Văn A"
                class="w-full px-3.5 py-3 rounded-2xl border border-slate-300 focus:border-[#2563EB] text-xs font-semibold text-slate-900 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Số điện thoại di động</label>
              <input
                v-model="customerForm.phone"
                required
                type="tel"
                placeholder="0912345678"
                class="w-full px-3.5 py-3 rounded-2xl border border-slate-300 focus:border-[#2563EB] text-xs font-semibold text-slate-900 focus:outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1">Địa chỉ Gmail nhận vé</label>
              <input
                v-model="customerForm.email"
                required
                type="email"
                placeholder="tronghieuvo9@gmail.com"
                class="w-full px-3.5 py-3 rounded-2xl border border-slate-300 focus:border-[#2563EB] text-xs font-semibold text-slate-900 focus:outline-none"
              />
            </div>
          </div>

          <!-- Payment Choice: Cọc 30% vs Thanh Toán 100% -->
          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Hình Thức Thanh Toán</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <label class="p-4 rounded-2xl border-2 flex items-start space-x-3 cursor-pointer transition select-none"
                :class="paymentChoice === 'DEPOSIT' ? 'bg-blue-50/70 border-[#2563EB]' : 'bg-white border-slate-200'">
                <input type="radio" value="DEPOSIT" v-model="paymentChoice" class="w-4 h-4 text-[#2563EB] mt-0.5" />
                <div>
                  <div class="font-black text-slate-900 text-sm">Đặt cọc Online 30% (Khuyên dùng)</div>
                  <div class="text-[#2563EB] font-black text-base mt-1">{{ formatPrice(depositAmount) }}đ</div>
                  <div class="text-[11px] text-slate-500 mt-1">Số tiền còn lại ({{ formatPrice(remainingAmount) }}đ) sẽ thanh toán cho Bác tài / Lơ xe khi lên xe.</div>
                </div>
              </label>

              <label class="p-4 rounded-2xl border-2 flex items-start space-x-3 cursor-pointer transition select-none"
                :class="paymentChoice === 'FULL' ? 'bg-blue-50/70 border-[#2563EB]' : 'bg-white border-slate-200'">
                <input type="radio" value="FULL" v-model="paymentChoice" class="w-4 h-4 text-[#2563EB] mt-0.5" />
                <div>
                  <div class="font-black text-slate-900 text-sm">Thanh toán đủ 100%</div>
                  <div class="text-[#2563EB] font-black text-base mt-1">{{ formatPrice(totalSeatPrice) }}đ</div>
                  <div class="text-[11px] text-slate-500 mt-1">Lên xe chỉ cần xuất trình mã QR Check-in mà không cần trả thêm tiền mặt.</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Submit Buttons -->
          <div class="flex items-center justify-between pt-4 border-t border-slate-100">
            <button type="button" @click="step = 2" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition cursor-pointer">
              ← Quay Lại
            </button>
            <button
              type="submit"
              class="px-10 py-3.5 rounded-2xl bg-[#2563EB] hover:bg-[#D84315] text-white font-black text-sm uppercase tracking-wider shadow-lg shadow-blue-600/20 transition cursor-pointer"
            >
              Xác Nhận Đặt Vé & Nhận Mã QR →
            </button>
          </div>
        </form>
      </div>

    </main>

    <Footer />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
  trip: Object,
  floor1Rows: Array,
  floor2Rows: Array,
  pickupStops: Array,
  dropoffStops: Array,
  user: Object,
});

const step = ref(1);
const selectedSeats = ref([]);
const selectedPickup = ref(props.pickupStops?.[0] || null);
const selectedDropoff = ref(props.dropoffStops?.[props.dropoffStops.length - 1] || null);
const paymentChoice = ref('DEPOSIT');

const customerForm = ref({
  name: props.user?.name || 'Võ Trọng Hiếu',
  phone: props.user?.phone || '0912345678',
  email: props.user?.email || 'tronghieuvo9@gmail.com',
});

// 3-Minute Hold Timer
const timerMinutes = ref(3);
const timerSeconds = ref(0);

onMounted(() => {
  const interval = setInterval(() => {
    if (timerSeconds.value > 0) {
      timerSeconds.value--;
    } else if (timerMinutes.value > 0) {
      timerMinutes.value--;
      timerSeconds.value = 59;
    }
  }, 1000);
});

const isSeatSelected = (seatNum) => {
  return selectedSeats.value.some(s => s.seat_number === seatNum);
};

const toggleSeat = (seat) => {
  const idx = selectedSeats.value.findIndex(s => s.seat_number === seat.seat_number);
  if (idx > -1) {
    selectedSeats.value.splice(idx, 1);
  } else {
    selectedSeats.value.push(seat);
  }
};

const totalSeatPrice = computed(() => {
  return selectedSeats.value.reduce((sum, s) => sum + (parseFloat(s.price) || 150000), 0);
});

const depositAmount = computed(() => Math.round(totalSeatPrice.value * 0.3));
const remainingAmount = computed(() => totalSeatPrice.value - depositAmount.value);

const goToStep2 = () => {
  if (selectedSeats.value.length === 0) {
    alert('Vui lòng chọn ít nhất 1 chỗ ngồi!');
    return;
  }
  step.value = 2;
};

const goToStep3 = () => {
  if (!selectedPickup.value || !selectedDropoff.value) {
    alert('Vui lòng chọn điểm đón và điểm trả!');
    return;
  }
  step.value = 3;
};

const submitBooking = () => {
  router.post(`/trips/${props.trip.id}/book`, {
    customer_name: customerForm.value.name,
    customer_email: customerForm.value.email,
    customer_phone: customerForm.value.phone,
    pickup_stop_id: selectedPickup.value?.id,
    dropoff_stop_id: selectedDropoff.value?.id,
    pickup_stop_name: selectedPickup.value.name,
    dropoff_stop_name: selectedDropoff.value.name,
    seat_numbers: selectedSeats.value.map(s => s.seat_number),
    payment_choice: paymentChoice.value,
  });
};

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(val || 0);
</script>
