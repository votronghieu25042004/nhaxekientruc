<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Navbar />

    <main class="flex-1 max-w-4xl mx-auto px-4 py-8 w-full">
      <!-- Success Banner -->
      <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-6 text-center mb-6 shadow-sm">
        <div class="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center mx-auto mb-3 shadow-md shadow-emerald-600/20">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900">Đặt Vé Xe Thành Công!</h1>
        <p class="text-xs sm:text-sm text-slate-600 mt-1">
          Mã vé điện tử & QR Code đã được gửi đến email: <strong class="text-slate-900">{{ booking.customer_email }}</strong>
        </p>
      </div>

      <!-- Boarding Pass E-Ticket Card -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <!-- Ticket Header -->
        <div class="bg-gradient-to-r from-blue-900 via-blue-800 to-slate-900 text-white p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="flex items-center space-x-3">
            <div class="w-11 h-11 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white border border-white/20">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M8 7h8m-8 4h8m-8 4h4m5 4H7a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v13a2 2 0 01-2 2z" />
                <circle cx="8" cy="18" r="1.5" fill="currentColor" />
                <circle cx="16" cy="18" r="1.5" fill="currentColor" />
              </svg>
            </div>
            <div>
              <span class="text-xl font-black tracking-tight">nhaxekientruc Terminal</span>
              <p class="text-xs text-blue-200">Thẻ Lên Xe Điện Tử (E-Boarding Pass)</p>
            </div>
          </div>

          <div class="text-left sm:text-right bg-white/10 px-4 py-2 rounded-2xl border border-white/10">
            <span class="text-[11px] text-blue-200 block uppercase font-bold">Mã Vé / Booking Code</span>
            <span class="text-lg font-black text-amber-300 tracking-wider">{{ booking.booking_code }}</span>
          </div>
        </div>

        <!-- Ticket Body -->
        <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-12 gap-6">
          <!-- Journey Details (8 cols) -->
          <div class="md:col-span-8 space-y-6">
            <!-- Route -->
            <div>
              <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Hành Trình</span>
              <div class="text-lg font-extrabold text-slate-900">
                {{ booking.from_location || booking.trip?.route?.origin || 'Bến xuất phát' }} ➔ {{ booking.to_location || booking.trip?.route?.destination || 'Bến đến' }}
              </div>
            </div>

            <!-- Departure Time & Date -->
            <div class="grid grid-cols-2 gap-4">
              <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <span class="text-[11px] text-slate-500 font-bold uppercase block">Giờ Xuất Bến</span>
                <span class="text-base font-black text-blue-600">{{ booking.departure_time || booking.trip?.departure_time?.substring(0, 5) }}</span>
              </div>
              <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <span class="text-[11px] text-slate-500 font-bold uppercase block">Ngày Khởi Hành</span>
                <span class="text-base font-black text-slate-900">{{ booking.departure_date || booking.trip?.departure_date }}</span>
              </div>
            </div>

            <!-- Passenger & Seats -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <span class="text-[11px] text-slate-400 font-bold uppercase block">Hành Khách</span>
                <span class="text-sm font-bold text-slate-800">{{ booking.customer_name }}</span>
                <span class="text-xs text-slate-500 block">{{ booking.customer_phone }}</span>
              </div>
              <div>
                <span class="text-[11px] text-slate-400 font-bold uppercase block">Số Ghế Đã Chọn</span>
                <span class="text-base font-black text-blue-600">
                  {{ Array.isArray(booking.seats) ? booking.seats.map(s => typeof s === 'object' ? s.seat_number : s).join(', ') : (booking.seats || 'A01') }}
                </span>
              </div>
            </div>

            <!-- HỘP LƯU Ý ĐẶC BIỆT CỦA HÀNH KHÁCH (CHỈ HIỆN KHI KHÁCH CÓ GHI CHÚ) -->
            <div 
              v-if="booking.notes && booking.notes.trim() !== '' && booking.notes !== 'Đặt vé trực tuyến qua nhaxekientruc'"
              class="my-2 px-4 py-3 rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] text-[#92400E] flex items-start gap-2.5 text-xs shadow-2xs"
            >
              <span class="text-amber-500 font-bold text-sm shrink-0 leading-none">⚠️</span>
              <div class="leading-relaxed font-medium">
                <strong class="font-bold text-amber-900">Lưu ý:</strong>
                <span> {{ booking.notes }}</span>
              </div>
            </div>

            <!-- Driver & Conductor Info (Tài xế & Lơ xe) -->
            <div class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div v-if="booking.driver" class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1.5">
                <div class="flex items-center justify-between">
                  <span class="text-[10px] font-bold text-red-600 uppercase">👨‍✈️ Bác Tài Chính</span>
                  <span class="text-amber-500 font-bold text-[10px]">★ {{ booking.driver.avg_rating || '4.9' }}</span>
                </div>
                <strong class="text-sm font-black text-slate-900 block">{{ booking.driver.name }}</strong>
                <a 
                  v-if="booking.driver.phone"
                  :href="'tel:' + booking.driver.phone"
                  class="w-full py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg flex items-center justify-center gap-1 shadow-2xs"
                >
                  <span>📞 Gọi Tài Xế ({{ booking.driver.phone }})</span>
                </a>
              </div>

              <div v-if="booking.conductor" class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 space-y-1.5">
                <div class="flex items-center justify-between">
                  <span class="text-[10px] font-bold text-blue-600 uppercase">🎫 Lơ Xe Phụ Trách</span>
                  <span class="text-[10px] text-slate-400">Hỗ trợ hành lý</span>
                </div>
                <strong class="text-sm font-black text-slate-900 block">{{ booking.conductor.name }}</strong>
                <a 
                  v-if="booking.conductor.phone"
                  :href="'tel:' + booking.conductor.phone"
                  class="w-full py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] rounded-lg flex items-center justify-center gap-1 shadow-2xs"
                >
                  <span>📞 Gọi Lơ Xe ({{ booking.conductor.phone }})</span>
                </a>
              </div>
            </div>

            <!-- Financials -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
              <div>
                <span class="text-slate-500">Tổng tiền:</span>
                <strong class="text-slate-900 ml-1">{{ formatPrice(booking.total_amount) }}</strong>
              </div>
              <div>
                <span class="text-slate-500">Đã thanh toán / cọc:</span>
                <strong class="text-emerald-600 ml-1">{{ formatPrice(booking.paid_amount) }}</strong>
              </div>
            </div>

            <!-- Luggage Status (FREE 0d) -->
            <div class="mt-2.5 p-2 bg-emerald-50 rounded-xl border border-emerald-200 flex items-center justify-between text-[11px] text-emerald-900">
              <span class="flex items-center gap-1 font-bold">
                <span>🧳</span>
                <span>Hành lý xách tay & khoang gầm:</span>
              </span>
              <span class="font-black text-emerald-700 bg-white px-2 py-0.5 rounded border border-emerald-200">
                MIỄN PHÍ 0đ
              </span>
            </div>
          </div>

          <!-- QR Code Section (4 cols) -->
          <div class="md:col-span-4 flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border border-slate-200/80 text-center">
            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 mb-3 flex items-center justify-center">
              <qrcode-vue
                :value="qrCodeValue"
                :size="160"
                level="H"
                render-as="svg"
                class="rounded-lg"
              />
            </div>
            <span class="text-xs font-bold text-slate-800">Quét QR Khi Lên Xe</span>
            <span class="text-[11px] text-slate-500 mt-0.5">Tiếp viên / Lơ xe quét mã</span>
            <div class="mt-2 text-[10px] font-mono bg-slate-200 px-2 py-0.5 rounded text-slate-700 font-bold">
              {{ booking.booking_code }} • {{ booking.qr_token ? booking.qr_token.substring(0, 16) + '...' : 'PASS' }}
            </div>

            <!-- Nút Xem Bản Đồ Live GPS Tracking -->
            <div class="mt-4 w-full">
              <Link 
                :href="`/booking/${booking.booking_code}/detail`"
                class="w-full py-2.5 px-3 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-black flex items-center justify-center gap-1.5 shadow-sm transition active:scale-[0.98]"
              >
                <span>🗺️ Xem Bản Đồ Xe Đang Chạy</span>
              </Link>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="p-6 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
          <Link href="/" class="text-xs font-bold text-slate-600 hover:text-blue-600 transition flex items-center space-x-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            <span>Quay lại trang chủ</span>
          </Link>
          <div class="flex items-center flex-wrap gap-2">
            <Link 
              v-if="booking.payment_status !== 'PAID' || booking.remaining_due > 0"
              :href="`/booking/${booking.booking_code}/payment`" 
              class="px-4 py-2 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white text-xs font-black shadow-xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <span>💳 Thanh Toán VietQR</span>
            </Link>
            <div class="px-3.5 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold flex items-center gap-2 border border-slate-200 shadow-2xs">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>Tổng đài AI tự động gọi điện & gửi Gmail nhắc trước 45P</span>
            </div>
            <Link 
              href="/my-bookings" 
              class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-black text-white text-xs font-black shadow-xs flex items-center gap-1.5 transition"
            >
              <span>📜 Vé Của Tôi</span>
            </Link>
            <Link 
              :href="`/booking/${booking.booking_code}/detail`" 
              class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black shadow-xs flex items-center gap-1.5 transition"
            >
              <span>🗺️ Chi Tiết & Live GPS</span>
            </Link>
            <button @click="window.print()" class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-300 transition cursor-pointer">
              In Vé
            </button>
          </div>
        </div>
      </div>

    </main>

    <Footer />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import QrcodeVue from 'qrcode.vue'
import Navbar from '@/Components/Navbar.vue'
import Footer from '@/Components/Footer.vue'

const props = defineProps({
  booking: { type: Object, required: true },
})

const qrCodeValue = computed(() => {
  if (typeof window !== 'undefined' && window.location.origin) {
    return `${window.location.origin}/ve/${props.booking?.booking_code}`;
  }
  return props.booking?.booking_code || props.booking?.qr_token || 'KT-TICKET';
})

const formatPrice = (p) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(p || 0)
}
</script>
