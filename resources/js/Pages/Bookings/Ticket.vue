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
              <span class="text-xl font-black tracking-tight">BusHub Terminal</span>
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
                {{ booking.trip?.route?.origin_location?.name || booking.trip?.route?.origin }} ➔ {{ booking.trip?.route?.destination_location?.name || booking.trip?.route?.destination }}
              </div>
            </div>

            <!-- Departure Time & Date -->
            <div class="grid grid-cols-2 gap-4">
              <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <span class="text-[11px] text-slate-500 font-bold uppercase block">Giờ Xuất Bến</span>
                <span class="text-base font-black text-blue-600">{{ booking.trip?.departure_time?.substring(0, 5) }}</span>
              </div>
              <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <span class="text-[11px] text-slate-500 font-bold uppercase block">Ngày Khởi Hành</span>
                <span class="text-base font-black text-slate-900">{{ booking.trip?.departure_date }}</span>
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
                  {{ (booking.seats || []).map(s => s.seat_number).join(', ') || 'A01' }}
                </span>
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
          </div>

          <!-- QR Code Section (4 cols) -->
          <div class="md:col-span-4 flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border border-slate-200/80 text-center">
            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 mb-3">
              <!-- Inline QR Code SVG representation -->
              <svg class="w-36 h-36" viewBox="0 0 100 100" fill="none">
                <rect width="100" height="100" fill="white" />
                <rect x="10" y="10" width="25" height="25" fill="#0F172A" />
                <rect x="15" y="15" width="15" height="15" fill="white" />
                <rect x="18" y="18" width="9" height="9" fill="#0F172A" />
                
                <rect x="65" y="10" width="25" height="25" fill="#0F172A" />
                <rect x="70" y="15" width="15" height="15" fill="white" />
                <rect x="73" y="18" width="9" height="9" fill="#0F172A" />
                
                <rect x="10" y="65" width="25" height="25" fill="#0F172A" />
                <rect x="15" y="70" width="15" height="15" fill="white" />
                <rect x="18" y="73" width="9" height="9" fill="#0F172A" />
                
                <circle cx="50" cy="50" r="10" fill="#2563EB" />
                <circle cx="50" cy="50" r="5" fill="white" />
                <rect x="42" y="20" width="8" height="8" fill="#0F172A" />
                <rect x="70" y="50" width="12" height="6" fill="#0F172A" />
                <rect x="50" y="75" width="8" height="12" fill="#0F172A" />
                <rect x="75" y="75" width="10" height="10" fill="#0F172A" />
              </svg>
            </div>
            <span class="text-xs font-bold text-slate-800">Quét QR Khi Lên Xe</span>
            <span class="text-[11px] text-slate-500 mt-0.5">Tiếp viên / Lơ xe quét mã</span>
            <div class="mt-2 text-[10px] font-mono bg-slate-200 px-2 py-0.5 rounded text-slate-700">
              {{ booking.qr_token ? booking.qr_token.substring(0, 16) + '...' : 'QR-BUSHUB-PASS' }}
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="p-6 bg-slate-50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
          <Link href="/" class="text-xs font-bold text-slate-600 hover:text-blue-600 transition flex items-center space-x-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            <span>Quay lại trang chủ</span>
          </Link>
          <div class="flex items-center space-x-2">
            <button @click="window.print()" class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-300 transition">
              In Vé Điện Tử
            </button>
            <Link href="/schedules" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition">
              Xem Lịch Chạy
            </Link>
          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Navbar from '@/Components/Navbar.vue'
import Footer from '@/Components/Footer.vue'

const props = defineProps({
  booking: { type: Object, required: true },
})

const formatPrice = (p) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(p || 0)
}
</script>
