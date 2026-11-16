<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <AdminHeader 
      title="BẢNG ĐIỀU KHIỂN TỔNG QUAN HỆ THỐNG" 
      subtitle="Theo dõi doanh thu, lịch trình đội xe, trạng thái vận hành và quản lý vé đặt đa ngày" 
    />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 w-full space-y-5">
      <!-- 1. BẢNG CHỈ SỐ NHANH EXECUTIVE KPIS (GỌN GÀNG, ĐẸP MẮT) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <!-- Doanh thu -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs flex items-center justify-between">
          <div>
            <div class="flex items-center gap-1.5">
              <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Doanh Thu</p>
              <button 
                @click="scrollToFleetRevenue"
                class="text-[9px] font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-1.5 py-0.5 rounded-md cursor-pointer transition flex items-center gap-1"
                title="Bấm để xem chi tiết doanh thu từng xe"
              >
                <span>📊</span>
                <span>Từng xe</span>
              </button>
            </div>
            <p class="text-xl sm:text-2xl font-black text-emerald-600 font-mono mt-0.5">{{ formatCurrency(stats?.revenue_today) }}</p>
            <p class="text-[10px] text-slate-400 mt-0.5">Tổng thu cọc 30% & thanh toán 100%</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
            💰
          </div>
        </div>

        <!-- Số vé hôm nay & Đặt trước -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Vé Hôm Nay & Đặt Trước</p>
            <p class="text-xl sm:text-2xl font-black text-slate-900 mt-0.5">
              {{ stats?.bookings_today || 0 }} <span class="text-xs font-bold text-slate-400">hôm nay</span>
            </p>
            <p class="text-[10px] text-amber-700 font-bold mt-0.5">
              ⚡ Đặt trước: <strong class="text-red-600 font-black">{{ stats?.advance_bookings_count || 0 }} vé</strong> (Tổng {{ stats?.total_bookings_count || 0 }})
            </p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
            🎟️
          </div>
        </div>

        <!-- Chuyến chạy hôm nay -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Chuyến Chạy Hôm Nay</p>
            <p class="text-xl sm:text-2xl font-black text-red-600 mt-0.5">{{ stats?.trips_today_count || 22 }} <span class="text-xs font-bold text-slate-400">chuyến</span></p>
            <p class="text-[10px] text-slate-400 mt-0.5">Đà Nẵng, Huế, Hà Nội, Sài Gòn</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-xl shrink-0">
            🚍
          </div>
        </div>

        <!-- Đội xe sẵn sàng -->
        <div class="bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs flex items-center justify-between">
          <div>
            <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Đội Xe Sẵn Sàng</p>
            <p class="text-xl sm:text-2xl font-black text-purple-600 mt-0.5">{{ stats?.active_buses || 9 }} / {{ stats?.total_buses || 10 }} <span class="text-xs font-bold text-slate-400">xe</span></p>
            <p class="text-[10px] text-emerald-600 font-bold mt-0.5">✓ 9 xe hoạt động tốt</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shrink-0">
            🚌
          </div>
        </div>
      </div>

      <!-- 2. THANH TIỆN ÍCH ĐIỀU HÀNH NHANH (COMPACT QUICK BAR) -->
      <div class="bg-white px-4 py-2.5 rounded-2xl border border-slate-200 shadow-2xs flex items-center justify-between gap-3 overflow-x-auto text-xs font-bold">
        <span class="text-slate-400 uppercase tracking-wider text-[10px] shrink-0 font-black">LỐI TẮT ĐIỀU HÀNH:</span>
        <div class="flex items-center gap-2 shrink-0">
          <a
            href="/admin/pos"
            class="px-3 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
          >
            <span>🖥️</span>
            <span>POS Bán Vé Quầy</span>
          </a>
          <a
            href="/admin/tracking"
            class="px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
          >
            <span>📡</span>
            <span>Live GPS Tracking</span>
          </a>
          <a
            href="/admin/pricing"
            class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
          >
            <span>🏷️</span>
            <span>Giá & Khuyến Mãi</span>
          </a>
          <a
            href="/admin/fleet-inventory"
            class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
          >
            <span>🛠️</span>
            <span>Vật Tư & Xe</span>
          </a>
          <a
            href="/admin/drivers"
            class="px-3 py-1.5 rounded-xl bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
          >
            <span>👨‍✈️</span>
            <span>Tài Xế & Lơ Xe</span>
          </a>
          <a
            href="/admin/trips"
            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 flex items-center gap-1.5 transition active:scale-95 cursor-pointer"
          >
            <span>📋</span>
            <span>Lịch Trình Chuyến</span>
          </a>
        </div>
      </div>

      <!-- ==================== 3. BẢNG TỔNG QUAN VÉ ĐẶT TOÀN HỆ THỐNG (ĐƯỢC ĐẨY LÊN TRÊN THEO YÊU CẦU 8) ==================== -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- HEADER VÀ BỘ LỌC ĐA NĂNG -->
        <div class="p-4 border-b border-slate-100 space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <h2 class="font-black text-slate-900 text-base">Tổng Quan Vé Đặt Hệ Thống</h2>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-700 border border-slate-200">
                  {{ filteredBookings.length }} vé hiển thị
                </span>
              </div>
              <p class="text-xs text-slate-400 mt-0.5">
                Xem toàn bộ vé đã đặt của khách trên web hoặc tại quầy (Bao gồm các ngày trước & các ngày tới)
              </p>
            </div>
          </div>

          <!-- BỘ LỌC THỜI GIAN NHANH DẠNG VIÊN THUỐC & TÌM KIẾM (ĐÃ BỎ CHẾ ĐỘ LỌC THEO NGÀY KHỞI HÀNH/ĐẶT VÉ THEO YÊU CẦU 1) -->
          <div class="flex flex-wrap items-center justify-between gap-2.5 pt-1">
            <div class="flex flex-wrap items-center gap-1.5">
              <!-- Nút Tất cả -->
              <button
                @click="setBookingFilter('ALL')"
                :class="currentFilter === 'ALL' ? 'bg-slate-900 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer"
              >
                🌟 Tất cả các ngày
              </button>

              <!-- Hôm nay (Tự động cập nhật ngày thực tế) -->
              <button
                @click="setBookingFilter('TODAY')"
                :class="currentFilter === 'TODAY' ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
              >
                🚍 Hôm nay ({{ todayDayMonth }})
              </button>

              <!-- Ngày mai (Tự động cập nhật) -->
              <button
                @click="setBookingFilter('TOMORROW')"
                :class="currentFilter === 'TOMORROW' ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
              >
                ⚡ Ngày mai ({{ tomorrowDayMonth }})
              </button>

              <!-- Các ngày trước (Lịch sử đã qua) -->
              <button
                @click="setBookingFilter('PAST')"
                :class="currentFilter === 'PAST' ? 'bg-slate-700 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
              >
                ⏪ Các ngày trước
              </button>

              <!-- Date Picker chọn ngày cụ thể -->
              <div class="flex items-center gap-1 pl-1">
                <span class="text-xs text-slate-400">📅</span>
                <input
                  type="date"
                  v-model="customDate"
                  @change="setBookingFilter('CUSTOM')"
                  class="bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1 text-xs font-bold text-slate-800 focus:outline-red-500 cursor-pointer"
                />
              </div>
            </div>

            <!-- Ô tìm kiếm mã vé, tên, điện thoại, biển số xe -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
              <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 pl-2.5 flex items-center text-slate-400 text-xs">🔍</span>
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Tìm mã vé, tên, sđt, xe..."
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-7 py-1 text-xs font-bold text-slate-800 placeholder-slate-400 focus:outline-red-500"
                />
                <button
                  v-if="searchQuery"
                  @click="searchQuery = ''"
                  type="button"
                  class="absolute inset-y-0 right-0 pr-2 flex items-center text-slate-400 hover:text-slate-600 font-bold text-xs cursor-pointer"
                >✕</button>
              </div>

              <!-- Page Size -->
              <select
                v-model="pageSize"
                @change="currentPage = 1"
                class="bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1 text-xs font-bold text-slate-700 cursor-pointer"
              >
                <option :value="10">10 dòng</option>
                <option :value="20">20 dòng</option>
                <option :value="50">50 dòng</option>
                <option :value="100">100 dòng</option>
              </select>
            </div>
          </div>
        </div>

        <!-- BẢNG DỮ LIỆU VÉ ĐẶT GỌN GÀNG, KHÔNG BỊ TRÀN KÉO NGANG -->
        <div class="overflow-x-auto rounded-2xl border border-slate-100">
          <table class="w-full text-left text-xs table-fixed">
            <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-black text-[10px] tracking-wider">
              <tr>
                <th class="px-3 py-2.5 w-[14%]">Mã Vé & Ngày Đặt</th>
                <th class="px-3 py-2.5 w-[16%]">Khách Hàng</th>
                <th class="px-3 py-2.5 w-[18%]">Tuyến Đường & Xe</th>
                <th class="px-3 py-2.5 w-[14%]">Khởi Hành</th>
                <th class="px-3 py-2.5 w-[8%] text-center">Ghế</th>
                <th class="px-3 py-2.5 w-[13%] text-right">Tổng Tiền & TT</th>
                <th class="px-3 py-2.5 w-[17%] text-center">Nhắc 45P (Gmail & AI)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr 
                v-for="b in paginatedBookings" 
                :key="b.id"
                class="hover:bg-slate-50/80 transition"
              >
                <!-- 1. Mã Vé & Ngày Tạo Đơn -->
                <td class="px-3 py-2.5 align-middle">
                  <div class="font-mono font-black text-red-600 text-xs">
                    #{{ b.booking_code }}
                  </div>
                  <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                    {{ b.created_at }}
                  </div>
                </td>

                <!-- 2. Khách Hàng (Tên & SĐT) -->
                <td class="px-3 py-2.5 align-middle">
                  <div class="font-bold text-slate-900 leading-snug truncate" :title="b.customer_name">
                    {{ b.customer_name }}
                  </div>
                  <div class="text-[11px] text-slate-500 font-mono">
                    {{ b.customer_phone }}
                  </div>
                  <div v-if="b.notes && b.notes !== 'Đặt vé trực tuyến qua nhaxekientruc'" class="mt-1 flex items-start gap-1 text-[10px] text-amber-900 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                    <span class="text-amber-500 font-bold shrink-0">⚠️</span>
                    <span class="truncate" :title="b.notes">{{ b.notes }}</span>
                  </div>
                </td>

                <!-- 3. Tuyến Đường & Biển Số Xe -->
                <td class="px-3 py-2.5 align-middle">
                  <div class="font-bold text-slate-800 leading-snug truncate" :title="b.route_name">
                    {{ b.route_name }}
                  </div>
                  <div class="mt-0.5">
                    <span class="font-mono font-bold text-slate-700 bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded text-[10px]">
                      🚍 {{ b.bus_plate }}
                    </span>
                  </div>
                </td>

                <!-- 4. Khởi Hành & Badge Hôm Nay/Đặt Trước -->
                <td class="px-3 py-2.5 align-middle">
                  <div class="font-bold text-slate-900 font-mono leading-snug">
                    {{ b.trip_departure }}
                  </div>
                  <div class="mt-0.5">
                    <span 
                      v-if="b.is_today" 
                      class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-red-100 text-red-800 border border-red-200 inline-block"
                    >
                      🚍 Hôm nay
                    </span>
                    <span 
                      v-else-if="b.is_advance" 
                      class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-200 inline-block"
                    >
                      ⚡ Đặt trước
                    </span>
                  </div>
                </td>

                <!-- 5. Vị Trí Ghế -->
                <td class="px-3 py-2.5 align-middle text-center">
                  <span class="font-mono font-black text-red-700 bg-red-50 border border-red-100 px-2 py-0.5 rounded-lg text-xs inline-block">
                    {{ b.seat_code }}
                  </span>
                </td>

                <!-- 6. Tổng Tiền & Thanh Toán -->
                <td class="px-3 py-2.5 align-middle text-right">
                  <div class="font-black text-emerald-700 font-mono text-xs">
                    {{ formatCurrency(b.total_amount) }}
                  </div>
                  <div class="mt-0.5">
                    <span
                      v-if="b.payment_status === 'CANCELLED' || b.payment_status === 'REFUNDED'"
                      class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-rose-50 text-rose-700 border border-rose-200 inline-block"
                    >
                      ✕ Hoàn Tiền
                    </span>
                    <span
                      v-else-if="b.payment_status === 'PAID'"
                      class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-block"
                    >
                      ✓ Đã Thanh Toán
                    </span>
                    <span
                      v-else
                      class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200 inline-block"
                    >
                      ⏳ Cọc 30%
                    </span>
                  </div>
                </td>

                <!-- 7. Thao Tác Nhắc 45P (Gửi Gmail & Kích Hoạt AI Gọi Điện) -->
                <td class="px-3 py-2.5 align-middle text-center">
                  <div v-if="b.reminder_sent_at || b.ai_call_at" class="space-y-1">
                    <span 
                      v-if="b.reminder_sent_by === 'auto' || b.ai_call_by === 'auto'"
                      class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-blue-100 text-blue-800 border border-blue-200 inline-flex items-center gap-1"
                    >
                      <span>🤖 AI ĐÃ GỌI & GỬI</span>
                      <span class="font-mono">({{ b.reminder_sent_at || b.ai_call_at }})</span>
                    </span>
                    <span 
                      v-else
                      class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-purple-100 text-purple-800 border border-purple-200 inline-flex items-center gap-1"
                    >
                      <span>✍️ ADMIN ĐÃ GỌI & GỬI</span>
                      <span class="font-mono">({{ b.reminder_sent_at || b.ai_call_at }})</span>
                    </span>
                    <div class="flex items-center justify-center gap-1 flex-wrap">
                      <button 
                        v-if="b.ai_call_audio_url || b.ai_call_script"
                        @click.stop="playAiCallAudio(b)"
                        type="button"
                        class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[9px] font-bold transition flex items-center gap-0.5 cursor-pointer shadow-2xs"
                        title="Nghe lại cuộc gọi AI đã thông báo cho khách"
                      >
                        <span>🎧 Nghe AI</span>
                      </button>

                      <button
                        @click.stop="notifyAndCallSingleBooking(b)"
                        :disabled="notifyingBookingId === b.id"
                        type="button"
                        class="px-2 py-0.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-[9.5px] transition flex items-center gap-1 shadow-2xs cursor-pointer active:scale-95 disabled:opacity-50"
                        title="Gửi lại Gmail nhắc 45P và kích hoạt gọi điện thoại AI lần nữa"
                      >
                        <span v-if="notifyingBookingId === b.id" class="w-2.5 h-2.5 border-2 border-slate-900 border-t-transparent rounded-full animate-spin"></span>
                        <span v-else>🔄 Gửi & Gọi Lại</span>
                      </button>
                    </div>
                  </div>
                  <div v-else>
                    <button
                      @click.stop="notifyAndCallSingleBooking(b)"
                      :disabled="notifyingBookingId === b.id"
                      type="button"
                      class="px-2.5 py-1 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-[10px] tracking-tight transition inline-flex items-center gap-1 shadow-xs cursor-pointer active:scale-95 disabled:opacity-50"
                      title="Gửi Gmail trước 45P & Kích hoạt AI tự động gọi điện tới SĐT của khách hàng này"
                    >
                      <span v-if="notifyingBookingId === b.id" class="w-2.5 h-2.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                      <span v-else>📞 Gửi & Gọi AI</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredBookings.length === 0">
                <td colspan="7" class="text-center py-8 text-slate-400 font-bold">
                  Không tìm thấy vé đặt nào trong khoảng thời gian đã chọn. Hãy bấm <button @click="setBookingFilter('ALL')" class="text-red-600 underline font-black cursor-pointer">"Tất cả các ngày"</button> để xem toàn bộ danh sách.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- THANH PHÂN TRANG (PAGINATION) GỌN GÀNG -->
        <div v-if="filteredBookings && filteredBookings.length > 0" class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
          <div class="text-xs text-slate-500 font-medium">
            Hiển thị <b class="text-slate-900">{{ (currentPage - 1) * pageSize + 1 }} - {{ Math.min(currentPage * pageSize, filteredBookings.length) }}</b> / tổng <b class="text-slate-900">{{ filteredBookings.length }}</b> vé đặt (Lọc: <strong class="text-red-700">{{ currentFilter }}</strong>)
          </div>

          <div class="flex items-center gap-1.5">
            <button
              :disabled="currentPage === 1"
              @click="currentPage--"
              class="px-3 py-1 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            >
              ← Trước
            </button>

            <div class="flex items-center gap-1">
              <template v-for="(p, idx) in visiblePages" :key="idx">
                <span v-if="p === '...'" class="px-1 text-xs text-slate-400 font-bold select-none">...</span>
                <button
                  v-else
                  @click="currentPage = p"
                  class="w-6 h-6 rounded-lg text-xs font-bold transition cursor-pointer"
                  :class="currentPage === p ? 'bg-[#dc2626] text-white shadow-2xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
                >
                  {{ p }}
                </button>
              </template>
            </div>

            <button
              :disabled="currentPage >= totalPages"
              @click="currentPage++"
              class="px-3 py-1 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            >
              Sau →
            </button>
          </div>
        </div>
      </div>

      <!-- ==================== 4. BẢNG BÁO CÁO DOANH THU & HIỆU SUẤT TỪNG XE (ĐƯỢC ĐẶT DƯỚI TỔNG QUAN VÉ ĐẶT THEO YÊU CẦU 8) ==================== -->
      <div id="fleet-revenue-section" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-5">
        <!-- Tiêu đề & Bộ lọc theo Ngày / Tất cả ngày -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div>
            <div class="flex items-center gap-2">
              <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base font-bold">🚍</span>
              <div>
                <div class="flex items-center gap-2">
                  <h2 class="font-black text-slate-900 text-base">Báo Cáo Doanh Thu Từng Xe & Bảng Xếp Hạng Đội Xe</h2>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                    {{ processedFleet.length }} xe vận hành
                  </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">
                  Xem doanh thu chi tiết từng xe theo từng ngày hoặc tất cả ngày, xếp hạng xe chạy nhiều nhất, chở khách đông nhất và hiệu suất khai thác
                </p>
              </div>
            </div>
          </div>

          <!-- Bộ lọc thời gian: Tất cả các ngày, Hôm nay, Hôm qua, 7 ngày qua, Tháng này, Chọn ngày -->
          <div class="flex flex-wrap items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl text-xs font-bold shrink-0">
            <button
              @click="fleetDateFilter = 'ALL'"
              :class="fleetDateFilter === 'ALL' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:bg-white'"
              class="px-2.5 py-1.5 rounded-xl transition cursor-pointer"
            >
              Tất Cả Các Ngày
            </button>
            <button
              @click="fleetDateFilter = 'TODAY'"
              :class="fleetDateFilter === 'TODAY' ? 'bg-[#dc2626] text-white shadow-xs' : 'text-slate-700 hover:bg-white'"
              class="px-2.5 py-1.5 rounded-xl transition cursor-pointer"
            >
              Hôm Nay
            </button>
            <button
              @click="fleetDateFilter = 'YESTERDAY'"
              :class="fleetDateFilter === 'YESTERDAY' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:bg-white'"
              class="px-2.5 py-1.5 rounded-xl transition cursor-pointer"
            >
              Hôm Qua
            </button>
            <button
              @click="fleetDateFilter = '7DAYS'"
              :class="fleetDateFilter === '7DAYS' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:bg-white'"
              class="px-2.5 py-1.5 rounded-xl transition cursor-pointer"
            >
              7 Ngày Qua
            </button>
            <button
              @click="fleetDateFilter = 'THIS_MONTH'"
              :class="fleetDateFilter === 'THIS_MONTH' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:bg-white'"
              class="px-2.5 py-1.5 rounded-xl transition cursor-pointer"
            >
              Tháng Này
            </button>

            <div class="flex items-center gap-1 pl-1 border-l border-slate-300">
              <span class="text-[10px] text-slate-400">📅 Ngày:</span>
              <input
                type="date"
                v-model="fleetCustomDate"
                @change="fleetDateFilter = 'CUSTOM'"
                class="bg-white border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-slate-800 focus:outline-blue-500 cursor-pointer"
              />
            </div>
          </div>
        </div>

        <!-- 3 THẺ TỔNG KẾT EXECUTIVE HIGHLIGHTS (XE NÀO CHẠY NHIỀU NHẤT, DOANH THU CAO NHẤT, ĐÔNG KHÁCH NHẤT) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <!-- 1. Xe chạy nhiều nhất -->
          <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50/40 border border-amber-200/80 shadow-2xs relative overflow-hidden">
            <div class="flex items-start justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 bg-amber-100/80 px-2 py-0.5 rounded-md border border-amber-300/60 inline-flex items-center gap-1">
                  <span>🔥</span>
                  <span>XE CHẠY NHIỀU NHẤT</span>
                </span>
                <h3 class="font-mono font-black text-xl text-slate-900 mt-2">
                  {{ topRunningBus?.license_plate || 'Đang cập nhật' }}
                </h3>
                <p class="text-xs text-slate-600 mt-0.5 font-medium truncate max-w-[200px]" :title="topRunningBus?.bus_type">
                  {{ topRunningBus?.bus_type }}
                </p>
              </div>
              <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-2xl shadow-sm shrink-0">
                🏆
              </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-amber-200/60 flex items-center justify-between text-xs">
              <span class="text-slate-600 font-bold">
                Số chuyến: <strong class="text-amber-900 font-mono text-sm">{{ topRunningBus?.trips_count || 0 }} chuyến</strong>
              </span>
              <span class="text-emerald-700 font-mono font-black">
                {{ formatCurrency(topRunningBus?.revenue) }}
              </span>
            </div>
          </div>

          <!-- 2. Xe doanh thu cao nhất -->
          <div class="p-4 rounded-2xl bg-gradient-to-br from-emerald-50 to-teal-50/40 border border-emerald-200/80 shadow-2xs relative overflow-hidden">
            <div class="flex items-start justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded-md border border-emerald-300/60 inline-flex items-center gap-1">
                  <span>👑</span>
                  <span>DOANH THU CAO NHẤT</span>
                </span>
                <h3 class="font-mono font-black text-xl text-slate-900 mt-2">
                  {{ topRevenueBus?.license_plate || 'Đang cập nhật' }}
                </h3>
                <p class="text-xs text-slate-600 mt-0.5 font-medium truncate max-w-[200px]" :title="topRevenueBus?.bus_type">
                  {{ topRevenueBus?.bus_type }}
                </p>
              </div>
              <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-2xl shadow-sm shrink-0">
                💰
              </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-emerald-200/60 flex items-center justify-between text-xs">
              <span class="text-slate-600 font-bold">
                Tổng thu: <strong class="text-emerald-700 font-mono text-sm font-black">{{ formatCurrency(topRevenueBus?.revenue) }}</strong>
              </span>
              <span class="text-slate-500 font-mono">
                {{ topRevenueBus?.tickets_count || 0 }} vé bán
              </span>
            </div>
          </div>

          <!-- 3. Xe chở đông khách nhất -->
          <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50/40 border border-blue-200/80 shadow-2xs relative overflow-hidden">
            <div class="flex items-start justify-between">
              <div>
                <span class="text-[10px] font-black uppercase tracking-wider text-blue-800 bg-blue-100/80 px-2 py-0.5 rounded-md border border-blue-300/60 inline-flex items-center gap-1">
                  <span>👥</span>
                  <span>ĐÔNG KHÁCH NHẤT</span>
                </span>
                <h3 class="font-mono font-black text-xl text-slate-900 mt-2">
                  {{ topPassengerBus?.license_plate || 'Đang cập nhật' }}
                </h3>
                <p class="text-xs text-slate-600 mt-0.5 font-medium truncate max-w-[200px]" :title="topPassengerBus?.bus_type">
                  {{ topPassengerBus?.bus_type }}
                </p>
              </div>
              <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-2xl shadow-sm shrink-0">
                🎟️
              </div>
            </div>
            <div class="mt-3 pt-2.5 border-t border-blue-200/60 flex items-center justify-between text-xs">
              <span class="text-slate-600 font-bold">
                Lượng khách: <strong class="text-blue-900 font-mono text-sm">{{ topPassengerBus?.tickets_count || 0 }} vé</strong>
              </span>
              <span class="text-slate-500 font-mono text-xs">
                Tỷ lệ lấp đầy: <b>{{ topPassengerBus?.occupancy_rate || 0 }}%</b>
              </span>
            </div>
          </div>
        </div>

        <!-- BẢNG BÁO CÁO DOANH THU & CHỈ SỐ KHAI THÁC CHI TIẾT TỪNG XE -->
        <div class="border border-slate-200 rounded-2xl overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-black text-[10px] tracking-wider">
                <tr>
                  <th class="py-2.5 px-3.5 text-center">Hạng</th>
                  <th class="py-2.5 px-3.5">Biển Số Xe</th>
                  <th class="py-2.5 px-3.5">Dòng Phương Tiện</th>
                  <th class="py-2.5 px-3.5">Tuyến Hoạt Động</th>
                  <th class="py-2.5 px-3.5 text-center">Số Chuyến Chạy</th>
                  <th class="py-2.5 px-3.5 text-center">Vé Đã Bán</th>
                  <th class="py-2.5 px-3.5 text-center">Tỷ Lệ Lấp Đầy</th>
                  <th class="py-2.5 px-3.5 text-right">Tổng Doanh Thu</th>
                  <th class="py-2.5 px-3.5 text-right">TB / Chuyến</th>
                  <th class="py-2.5 px-3.5 text-center">Thao Tác</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr 
                  v-for="(bus, index) in processedFleet" 
                  :key="bus.bus_id"
                  class="hover:bg-slate-50/80 transition"
                  :class="index === 0 ? 'bg-amber-50/20' : ''"
                >
                  <!-- Thứ hạng -->
                  <td class="py-3 px-3.5 text-center">
                    <span 
                      class="w-6 h-6 rounded-full inline-flex items-center justify-center font-black text-xs"
                      :class="index === 0 ? 'bg-amber-400 text-slate-900 shadow-2xs font-bold' : (index === 1 ? 'bg-slate-300 text-slate-800' : (index === 2 ? 'bg-amber-200 text-amber-900' : 'text-slate-400'))"
                    >
                      {{ index + 1 }}
                    </span>
                  </td>

                  <!-- Biển số -->
                  <td class="py-3 px-3.5">
                    <span class="font-mono font-black text-slate-900 text-sm bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-lg">
                      {{ bus.license_plate }}
                    </span>
                  </td>

                  <!-- Dòng xe -->
                  <td class="py-3 px-3.5 text-slate-700">
                    <div class="font-bold">{{ bus.bus_type }}</div>
                    <div class="text-[10px] text-slate-400 font-mono">{{ bus.total_seats }} chỗ • ODO {{ (bus.current_km || 12000).toLocaleString('vi-VN') }} km</div>
                  </td>

                  <!-- Tuyến chính -->
                  <td class="py-3 px-3.5 font-bold text-slate-800">
                    {{ bus.primary_route }}
                  </td>

                  <!-- Số chuyến chạy -->
                  <td class="py-3 px-3.5 text-center">
                    <span class="font-mono font-black text-slate-900 bg-blue-50 border border-blue-200 text-blue-700 px-2 py-0.5 rounded-md">
                      {{ bus.trips_count }} lượt
                    </span>
                  </td>

                  <!-- Vé đã bán -->
                  <td class="py-3 px-3.5 text-center font-mono font-bold text-slate-800">
                    {{ bus.tickets_count }} vé
                  </td>

                  <!-- Tỷ lệ lấp đầy -->
                  <td class="py-3 px-3.5 text-center">
                    <div class="flex items-center justify-center gap-1.5 font-mono font-black">
                      <span :class="bus.occupancy_rate >= 70 ? 'text-emerald-700' : (bus.occupancy_rate >= 40 ? 'text-amber-700' : 'text-slate-500')">
                        {{ bus.occupancy_rate }}%
                      </span>
                    </div>
                  </td>

                  <!-- Doanh thu -->
                  <td class="py-3 px-3.5 text-right">
                    <div class="font-mono font-black text-sm text-emerald-600">
                      {{ formatCurrency(bus.revenue) }}
                    </div>
                    <div class="w-24 h-1 bg-slate-100 rounded-full ml-auto mt-1 overflow-hidden">
                      <div 
                        class="h-full bg-emerald-500 rounded-full"
                        :style="`width: ${maxRevenue > 0 ? Math.round((bus.revenue / maxRevenue) * 100) : 0}%`"
                      ></div>
                    </div>
                  </td>

                  <!-- TB / Chuyến -->
                  <td class="py-3 px-3.5 text-right font-mono font-bold text-slate-700">
                    {{ formatCurrency(bus.avg_per_trip) }}
                  </td>

                  <!-- Nút xem chi tiết sơ đồ ghế -->
                  <td class="py-3 px-3 text-center">
                    <button 
                      @click="selectBusForInspection(bus)"
                      class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-700 text-xs font-bold border border-slate-200 transition cursor-pointer flex items-center gap-1.5 mx-auto"
                      title="Bấm để xem sơ đồ ghế 2 tầng và danh sách vé thật của xe"
                    >
                      <span>💺</span>
                      <span>Sơ đồ ghế ({{ bus.trips_count }})</span>
                    </button>
                  </td>
                </tr>

                <tr v-if="processedFleet.length === 0">
                  <td colspan="10" class="py-8 text-center text-slate-400 text-xs">
                    Không có dữ liệu chuyến xe nào trong khoảng thời gian được chọn.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ==================== MODAL CHI TIẾT CÁC CHUYẾN XE & SƠ ĐỒ GHẾ THỰC TẾ (YÊU CẦU 8) ==================== -->
      <div v-if="inspectingBus" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-3 sm:p-4 animate-in fade-in">
        <div class="bg-white rounded-3xl border border-slate-200 max-w-5xl w-full p-5 space-y-4 shadow-2xl max-h-[92vh] flex flex-col">
          <!-- Header Modal -->
          <div class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-rose-50 text-red-600 flex items-center justify-center text-xl font-black shadow-xs">
                🚍
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="font-black text-slate-900 text-base font-mono">
                    {{ inspectingBus.license_plate }}
                  </h3>
                  <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-slate-100 text-slate-700">
                    {{ inspectingBus.bus_type }}
                  </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  Tổng thu: <strong class="text-emerald-700 font-mono font-black">{{ formatCurrency(inspectingBus.revenue) }}</strong> • {{ inspectingBus.trips_count }} chuyến trong thời gian lọc
                </p>
              </div>
            </div>
            <button @click="inspectingBus = null" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center font-bold text-sm cursor-pointer">
              ✕
            </button>
          </div>

          <!-- Trip Selector Pills (Nếu có nhiều chuyến) -->
          <div v-if="inspectingBus.matched_trips && inspectingBus.matched_trips.length > 0" class="flex items-center gap-2 overflow-x-auto pb-1 shrink-0">
            <span class="text-xs font-bold text-slate-500 shrink-0">Chọn chuyến:</span>
            <button
              v-for="tr in inspectingBus.matched_trips"
              :key="tr.trip_id"
              @click="selectedInspectTrip = tr"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap border"
              :class="selectedInspectTrip?.trip_id === tr.trip_id ? 'bg-[#dc2626] text-white border-red-600 shadow-xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200'"
            >
              <span class="font-mono font-black">#{{ tr.trip_code }}</span>
              <span>{{ tr.route_name }}</span>
              <span class="font-mono text-[11px] opacity-90">({{ tr.departure_time }})</span>
            </button>
          </div>

          <!-- Chi Tiết Chuyến Được Chọn: Sơ Đồ Ghế & Danh Sách Vé Thật (Yêu Cầu 8) -->
          <div v-if="selectedInspectTrip" class="overflow-y-auto flex-1 pr-1 space-y-4">
            <!-- Thông tin chuyến bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs">
              <div>
                <span class="font-bold text-slate-900">{{ selectedInspectTrip.route_name }}</span>
                <span class="text-slate-400 mx-1.5">•</span>
                <span class="text-slate-600">Xuất bến: <b class="font-mono text-slate-800">{{ selectedInspectTrip.departure_time }}</b></span>
                <span class="text-slate-400 mx-1.5">•</span>
                <span class="text-slate-600">Tài xế: <b>{{ selectedInspectTrip.driver_name }}</b> ({{ selectedInspectTrip.driver_phone }})</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 font-bold">
                  Đã đặt: {{ selectedInspectTrip.booked_seats || selectedInspectTrip.tickets_count || 0 }} / {{ selectedInspectTrip.total_seats || 36 }} ghế
                </span>
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-mono font-black">
                  Doanh thu: {{ formatCurrency(selectedInspectTrip.revenue) }}
                </span>
              </div>
            </div>

            <!-- Sơ Đồ Ghế & Danh Sách Vé (Grid 2 cột) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
              <!-- Cột Trái (6/12): Sơ đồ ghế Tầng 1 & Tầng 2 -->
              <div class="lg:col-span-6 bg-slate-50/70 p-4 rounded-2xl border border-slate-200 space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                  <h4 class="font-black text-slate-800 text-xs uppercase flex items-center gap-1.5">
                    <span>💺</span>
                    <span>Sơ Đồ Ghế (Tầng 1 & Tầng 2)</span>
                  </h4>
                  <div class="flex items-center gap-2 text-[10px]">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-emerald-600"></span> ✓ Đã Lên</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-amber-500"></span> ⏳ Chờ Lên</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-white border border-slate-300"></span> Trống</span>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                  <!-- Tầng 1 (Dãy A - 18 ghế) -->
                  <div class="bg-white p-2.5 rounded-xl border border-slate-200 text-center shadow-2xs">
                    <div class="flex items-center justify-between mb-1.5 pb-1 border-b border-slate-200">
                      <span class="text-[10px] font-black text-slate-700">TẦNG 1 (DÃY A)</span>
                      <span class="px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 font-mono font-black text-[9px]">
                        {{ formatCurrency(selectedInspectTrip.price_floor_1 || 140000) }}
                      </span>
                    </div>
                    <div class="grid grid-cols-3 gap-1 text-[8px] font-bold text-slate-400 pb-1 mb-1 border-b border-slate-100 select-none">
                      <span>Cửa sổ</span>
                      <span>Giữa</span>
                      <span>Cửa sổ</span>
                    </div>
                    <div class="grid grid-cols-3 gap-1">
                      <div
                        v-for="i in 18"
                        :key="'A' + i"
                        class="p-1 rounded-lg text-center font-mono font-bold text-[11px] transition relative group cursor-pointer border"
                        :class="[
                          selectedInspectTrip.booked_seats_details && selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)]
                            ? (selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].is_boarded
                                ? 'bg-emerald-600 border-emerald-500 text-white shadow-2xs'
                                : 'bg-amber-500 border-amber-400 text-white shadow-2xs')
                            : 'bg-slate-50 border-slate-200 text-slate-500 hover:border-slate-300'
                        ]"
                      >
                        <div class="font-black text-[10px]">A{{ i < 10 ? '0' + i : i }}</div>
                        <div v-if="selectedInspectTrip.booked_seats_details && selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)]" class="text-[8px] font-bold leading-none">
                          {{ selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].is_boarded ? '✓ Lên' : '⏳ Chờ' }}
                        </div>
                        <div v-else class="text-[8px] text-slate-400 font-normal leading-none">
                          Trống
                        </div>

                        <!-- Tooltip -->
                        <div
                          v-if="selectedInspectTrip.booked_seats_details && selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)]"
                          class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-1 w-44 p-2 bg-slate-900 text-white rounded-xl text-[10px] shadow-2xl z-30 pointer-events-none text-left"
                        >
                          <p class="font-black text-amber-300">{{ selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].customer_name }}</p>
                          <p class="text-slate-300 font-mono">{{ selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].customer_phone }}</p>
                          <p class="text-emerald-400 font-mono font-bold">Giá: {{ formatCurrency(selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].seat_price) }}</p>
                          <p :class="selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].is_boarded ? 'text-emerald-300 font-bold' : 'text-amber-300 font-bold'">
                            {{ selectedInspectTrip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].is_boarded ? '✓ Đã lên xe' : '⏳ Chưa lên xe' }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Tầng 2 (Dãy B - 12 ghế) -->
                  <div class="bg-white p-2.5 rounded-xl border border-slate-200 text-center shadow-2xs">
                    <div class="flex items-center justify-between mb-1.5 pb-1 border-b border-slate-200">
                      <span class="text-[10px] font-black text-slate-700">TẦNG 2 (DÃY B)</span>
                      <span class="px-1.5 py-0.2 rounded bg-blue-100 text-blue-800 font-mono font-black text-[9px]">
                        {{ formatCurrency(selectedInspectTrip.price_floor_2 || 130000) }}
                      </span>
                    </div>
                    <div class="grid grid-cols-3 gap-1 text-[8px] font-bold text-slate-400 pb-1 mb-1 border-b border-slate-100 select-none">
                      <span>Cửa sổ</span>
                      <span>Dãy giữa</span>
                      <span>Cửa sổ</span>
                    </div>
                    <div class="grid grid-cols-3 gap-1">
                      <div
                        v-for="i in 18"
                        :key="'B' + i"
                        class="p-1 rounded-lg text-center font-mono font-bold text-[11px] transition relative group cursor-pointer border"
                        :class="[
                          selectedInspectTrip.booked_seats_details && selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)]
                            ? (selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].is_boarded
                                ? 'bg-emerald-600 border-emerald-500 text-white shadow-2xs'
                                : 'bg-amber-500 border-amber-400 text-white shadow-2xs')
                            : 'bg-slate-50 border-slate-200 text-slate-500 hover:border-slate-300'
                        ]"
                      >
                        <div class="font-black text-[10px]">B{{ i < 10 ? '0' + i : i }}</div>
                        <div v-if="selectedInspectTrip.booked_seats_details && selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)]" class="text-[8px] font-bold leading-none">
                          {{ selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].is_boarded ? '✓ Lên' : '⏳ Chờ' }}
                        </div>
                        <div v-else class="text-[8px] text-slate-400 font-normal leading-none">
                          Trống
                        </div>

                        <!-- Tooltip -->
                        <div
                          v-if="selectedInspectTrip.booked_seats_details && selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)]"
                          class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-1 w-44 p-2 bg-slate-900 text-white rounded-xl text-[10px] shadow-2xl z-30 pointer-events-none text-left"
                        >
                          <p class="font-black text-amber-300">{{ selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].customer_name }}</p>
                          <p class="text-slate-300 font-mono">{{ selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].customer_phone }}</p>
                          <p class="text-emerald-400 font-mono font-bold">Giá: {{ formatCurrency(selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].seat_price) }}</p>
                          <p :class="selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].is_boarded ? 'text-emerald-300 font-bold' : 'text-amber-300 font-bold'">
                            {{ selectedInspectTrip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].is_boarded ? '✓ Đã lên xe' : '⏳ Chưa lên xe' }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Cột Phải (6/12): Danh sách hành khách đặt vé -->
              <div class="lg:col-span-6 bg-white p-4 rounded-2xl border border-slate-200 flex flex-col h-full">
                <h4 class="font-bold text-slate-800 text-xs uppercase mb-3 flex items-center justify-between">
                  <span>👥 Danh Sách Đặt Vé ({{ selectedInspectTrip.bookings?.length || 0 }} Mã Đặt)</span>
                  <span class="text-slate-400 text-[11px]">Tổng {{ selectedInspectTrip.booked_seats || 0 }} vé</span>
                </h4>

                <div class="overflow-y-auto flex-1 max-h-[380px] space-y-2.5 pr-1">
                  <div
                    v-for="b in selectedInspectTrip.bookings"
                    :key="b.booking_code"
                    class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-slate-50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                  >
                    <div class="space-y-1">
                      <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="font-mono font-bold text-red-600">{{ b.booking_code }}</span>
                        <span class="font-bold text-slate-900">{{ b.customer_name }}</span>
                        <span
                          class="text-[9px] px-2 py-0.5 rounded-md font-black uppercase tracking-wider"
                          :class="b.is_boarded ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
                        >
                          {{ b.is_boarded ? '✓ Đã lên xe' : '⏳ Chưa lên xe' }}
                        </span>
                      </div>
                      <div class="text-[11px] text-slate-500 font-mono flex items-center gap-1.5 flex-wrap">
                        <span>Ghế: <b class="text-red-700">{{ b.seat_code }}</b> ({{ b.total_seats }} chỗ)</span>
                        <span class="text-slate-300">•</span>
                        <a
                          v-if="b.customer_phone"
                          :href="'tel:' + b.customer_phone"
                          class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg font-bold text-[10px] transition cursor-pointer"
                        >
                          <span>📞 {{ b.customer_phone }}</span>
                        </a>
                      </div>
                      <div v-if="b.notes && b.notes !== 'Đặt vé trực tuyến qua nhaxekientruc'" class="mt-1 flex items-start gap-1 text-[10px] text-amber-900 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200">
                        <span class="text-amber-500 font-bold shrink-0">⚠️</span>
                        <span><b>Lưu ý:</b> {{ b.notes }}</span>
                      </div>
                    </div>

                    <div class="text-right sm:self-center shrink-0">
                      <div v-if="b.payment_status === 'PAID'">
                        <span class="font-mono font-bold text-emerald-700 block text-xs">{{ formatCurrency(b.total_amount) }}</span>
                        <span class="text-[9px] px-2 py-0.5 rounded-md font-bold inline-block bg-emerald-100 text-emerald-800 border border-emerald-200">
                          ✓ Đã TT 100%
                        </span>
                      </div>
                      <div v-else>
                        <span class="font-mono font-bold text-amber-700 block text-xs">
                          Đã cọc: {{ formatCurrency(b.paid_amount || (b.total_amount * 0.3)) }}
                        </span>
                        <span class="text-[9px] px-2 py-0.5 rounded-md font-bold inline-block bg-amber-100 text-amber-900 border border-amber-300">
                          ⚠️ Cọc 30% • Còn thu: {{ formatCurrency(b.total_amount - (b.paid_amount || (b.total_amount * 0.3))) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div v-if="loadingInspectDetails && !selectedInspectTrip.bookings" class="text-center py-8 text-slate-400 text-xs animate-pulse">
                    ⏳ Đang tải danh sách vé...
                  </div>
                  <div v-else-if="!selectedInspectTrip.bookings || selectedInspectTrip.bookings.length === 0" class="text-center py-8 text-slate-400 text-xs">
                    Chưa có hành khách nào đặt vé trên chuyến này.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-10 text-slate-400 text-xs">
            Không có chuyến nào trong thời gian lọc.
          </div>

          <!-- Footer Modal -->
          <div class="pt-2 flex justify-end shrink-0 border-t border-slate-100">
            <button 
              @click="inspectingBus = null"
              class="px-5 py-2 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl transition cursor-pointer"
            >
              Đóng cửa sổ
            </button>
          </div>
        </div>
      </div>
    </main>

    <AiChatModal />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AdminHeader from '@/Components/AdminHeader.vue';
import AiChatModal from '@/Components/AiChatModal.vue';

const props = defineProps({
  stats: Object,
  recent_bookings: Array,
  fleetRevenue: Array,
  booking_filter: String,
  booking_date: String,
  filter_type: String,
  search_query: String,
  today_iso: String,
  today_display: String,
  tomorrow_iso: String,
  tomorrow_display: String,
});

const currentFilter = ref(props.booking_filter || 'ALL');
const currentFilterType = ref(props.filter_type || 'TRIP_DATE');
const customDate = ref(props.booking_date || '');
const searchQuery = ref(props.search_query || '');
const currentPage = ref(1);
const pageSize = ref(10);

// Ngày hôm nay & ngày mai động chuẩn múi giờ Việt Nam (Asia/Ho_Chi_Minh)
const getVnDate = (offsetDays = 0) => {
  const d = new Date(Date.now() + offsetDays * 24 * 60 * 60 * 1000);
  const parts = new Intl.DateTimeFormat('vi-VN', {
    timeZone: 'Asia/Ho_Chi_Minh',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).formatToParts(d);
  const day = parts.find(p => p.type === 'day')?.value || '31';
  const month = parts.find(p => p.type === 'month')?.value || '10';
  const year = parts.find(p => p.type === 'year')?.value || '2026';
  return { iso: `${year}-${month}-${day}`, display: `${day}/${month}` };
};

const vnToday = getVnDate(0);
const vnTomorrow = getVnDate(1);

const todayIso = props.today_iso || vnToday.iso;
const todayDayMonth = props.today_display || vnToday.display;
const tomorrowIso = props.tomorrow_iso || vnTomorrow.iso;
const tomorrowDayMonth = props.tomorrow_display || vnTomorrow.display;

// ==================== FLEET REVENUE & PERFORMANCE LOGIC (YÊU CẦU 1 & 8) ====================
const fleetDateFilter = ref('ALL');
const fleetCustomDate = ref('');
const inspectingBus = ref(null);
const selectedInspectTrip = ref(null);
const loadingInspectDetails = ref(false);
const inspectDetailsCache = {};

// Tải chi tiết ghế & vé của chuyến theo yêu cầu (không nhồi toàn bộ vào trang => trang Admin nhẹ, không đơ)
watch(() => selectedInspectTrip.value?.trip_id, async (tripId) => {
  if (!tripId) return;
  const applyDetails = (data) => {
    if (selectedInspectTrip.value?.trip_id === tripId) {
      selectedInspectTrip.value = { ...selectedInspectTrip.value, ...data };
    }
  };
  if (inspectDetailsCache[tripId]) {
    applyDetails(inspectDetailsCache[tripId]);
    return;
  }
  loadingInspectDetails.value = true;
  try {
    const res = await fetch(`/admin/fleet-trip/${tripId}/details`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });
    if (res.ok) {
      const data = await res.json();
      inspectDetailsCache[tripId] = data;
      applyDetails(data);
    }
  } catch (e) {
    console.error('Không tải được chi tiết chuyến', e);
  } finally {
    loadingInspectDetails.value = false;
  }
});

const selectBusForInspection = (bus) => {
  inspectingBus.value = bus;
  if (bus.matched_trips && bus.matched_trips.length > 0) {
    selectedInspectTrip.value = bus.matched_trips[0];
  } else {
    selectedInspectTrip.value = null;
  }
};

const scrollToFleetRevenue = () => {
  const el = document.getElementById('fleet-revenue-section');
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
};

const todayStr = todayIso;
const yesterdayDate = new Date();
yesterdayDate.setDate(yesterdayDate.getDate() - 1);
const yesterdayStr = yesterdayDate.toISOString().split('T')[0];

const sevenDaysAgoDate = new Date();
sevenDaysAgoDate.setDate(sevenDaysAgoDate.getDate() - 7);
const sevenDaysAgoStr = sevenDaysAgoDate.toISOString().split('T')[0];

const currentMonthStr = todayStr.substring(0, 7);

const processedFleet = computed(() => {
  if (!props.fleetRevenue) return [];

  return props.fleetRevenue.map(bus => {
    const rawTrips = bus.trips_breakdown || [];
    
    let matchedTrips = rawTrips;
    if (fleetDateFilter.value === 'TODAY') {
      matchedTrips = rawTrips.filter(t => t.departure_date === todayStr);
    } else if (fleetDateFilter.value === 'YESTERDAY') {
      matchedTrips = rawTrips.filter(t => t.departure_date === yesterdayStr);
    } else if (fleetDateFilter.value === '7DAYS') {
      matchedTrips = rawTrips.filter(t => t.departure_date >= sevenDaysAgoStr);
    } else if (fleetDateFilter.value === 'THIS_MONTH') {
      matchedTrips = rawTrips.filter(t => (t.departure_date || '').startsWith(currentMonthStr));
    } else if (fleetDateFilter.value === 'CUSTOM' && fleetCustomDate.value) {
      matchedTrips = rawTrips.filter(t => t.departure_date === fleetCustomDate.value);
    }

    const tripsCount = matchedTrips.length;
    const ticketsCount = matchedTrips.reduce((acc, t) => acc + (t.tickets_count || 0), 0);
    const revenue = matchedTrips.reduce((acc, t) => acc + (t.revenue || 0), 0);
    const totalCapacity = tripsCount * (bus.total_seats || 36);
    const occupancyRate = totalCapacity > 0 ? Math.min(100, Math.round((ticketsCount / totalCapacity) * 100)) : 0;
    const avgPerTrip = tripsCount > 0 ? Math.round(revenue / tripsCount) : 0;

    return {
      bus_id: bus.bus_id,
      license_plate: bus.license_plate,
      bus_type: bus.bus_type,
      total_seats: bus.total_seats,
      status: bus.status,
      current_km: bus.current_km,
      primary_route: bus.primary_route,
      trips_count: tripsCount,
      tickets_count: ticketsCount,
      revenue: revenue,
      occupancy_rate: occupancyRate,
      avg_per_trip: avgPerTrip,
      matched_trips: matchedTrips,
    };
  }).sort((a, b) => b.revenue - a.revenue);
});

const topRunningBus = computed(() => {
  if (!processedFleet.value || processedFleet.value.length === 0) return null;
  return [...processedFleet.value].sort((a, b) => b.trips_count - a.trips_count)[0];
});

const topRevenueBus = computed(() => {
  if (!processedFleet.value || processedFleet.value.length === 0) return null;
  return processedFleet.value[0];
});

const topPassengerBus = computed(() => {
  if (!processedFleet.value || processedFleet.value.length === 0) return null;
  return [...processedFleet.value].sort((a, b) => b.tickets_count - a.tickets_count)[0];
});

const maxRevenue = computed(() => {
  if (!processedFleet.value || processedFleet.value.length === 0) return 1;
  return Math.max(1, ...processedFleet.value.map(b => b.revenue));
});

const switchFilterType = (type) => {
  currentFilterType.value = type;
  applyServerFilter();
};

const setBookingFilter = (filter) => {
  currentFilter.value = filter;
  currentPage.value = 1;
  applyServerFilter();
};

const applyServerFilter = () => {
  router.get('/admin', {
    booking_filter: currentFilter.value,
    filter_type: currentFilterType.value,
    booking_date: customDate.value,
    search: searchQuery.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['recent_bookings', 'booking_filter', 'booking_date', 'filter_type', 'search_query', 'stats'],
  });
};

const filteredBookings = computed(() => {
  if (!props.recent_bookings) return [];
  let list = props.recent_bookings;

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase();
    list = list.filter(b => 
      (b.customer_name && b.customer_name.toLowerCase().includes(q)) ||
      (b.customer_phone && b.customer_phone.includes(q)) ||
      (b.booking_code && b.booking_code.toLowerCase().includes(q)) ||
      (b.route_name && b.route_name.toLowerCase().includes(q)) ||
      (b.bus_plate && b.bus_plate.toLowerCase().includes(q)) ||
      (b.seat_code && b.seat_code.toLowerCase().includes(q))
    );
  }

  return list;
});

const totalPages = computed(() => {
  if (!filteredBookings.value || filteredBookings.value.length === 0) return 1;
  return Math.ceil(filteredBookings.value.length / pageSize.value);
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const cur = currentPage.value;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [1];
  let start = Math.max(2, cur - 1);
  let end = Math.min(total - 1, cur + 1);
  if (start > 2) pages.push('...');
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  if (end < total - 1) pages.push('...');
  pages.push(total);
  return pages;
});

const paginatedBookings = computed(() => {
  if (!filteredBookings.value) return [];
  const start = (currentPage.value - 1) * pageSize.value;
  return filteredBookings.value.slice(start, start + pageSize.value);
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const notifyingBookingId = ref(null);

const notifyAndCallSingleBooking = async (b) => {
  if (notifyingBookingId.value) return;
  notifyingBookingId.value = b.id;
  try {
    const res = await axios.post(`/admin/bookings/${b.id}/notify-and-call`);
    if (res.data?.success) {
      const updated = res.data.booking;
      b.reminder_sent_at = updated.reminder_sent_at;
      b.reminder_sent_by = 'manual';
      b.ai_call_at = updated.ai_call_at;
      b.ai_call_by = 'manual';
      b.ai_call_status = updated.ai_call_status;
      b.ai_call_audio_url = updated.ai_call_audio_url;
      b.ai_call_script = updated.ai_call_script;
      alert(`🎉 ${res.data.message}`);
    } else {
      alert(res.data?.message || 'Có lỗi xảy ra khi gọi AI & gửi thông báo.');
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Không thể kết nối đến máy chủ.');
  } finally {
    notifyingBookingId.value = null;
  }
};

const playAiCallAudio = (b) => {
  if (b.ai_call_audio_url) {
    const audio = new Audio(b.ai_call_audio_url);
    audio.play().catch(() => {
      if ('speechSynthesis' in window && b.ai_call_script) {
        const utterance = new SpeechSynthesisUtterance(b.ai_call_script);
        utterance.lang = 'vi-VN';
        window.speechSynthesis.speak(utterance);
      } else {
        alert(`Lời thoại AI: "${b.ai_call_script}"`);
      }
    });
  } else if (b.ai_call_script) {
    if ('speechSynthesis' in window) {
      const utterance = new SpeechSynthesisUtterance(b.ai_call_script);
      utterance.lang = 'vi-VN';
      window.speechSynthesis.speak(utterance);
    } else {
      alert(`Lời thoại AI: "${b.ai_call_script}"`);
    }
  }
};
</script>
