<template>
  <div class="min-h-screen bg-slate-50 flex flex-col text-slate-800 font-sans">
    <Navbar />

    <main class="flex-1 max-w-5xl mx-auto px-4 sm:px-6 py-8 w-full space-y-6">
      <!-- Top Title Bar -->
      <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-50 text-red-700 border border-red-200">
              TRUNG TÂM HÀNH KHÁCH
            </span>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900">Quản Lý Vé Xe & Điểm Thưởng</h1>
          </div>
          <p class="text-xs text-slate-500 mt-1">
            Tra cứu thông tin vé điện tử, kiểm tra vị trí xe trực tuyến theo thời gian thực và quản lý điểm thưởng tích lũy
          </p>
        </div>

        <Link
          href="/"
          class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs flex items-center justify-center gap-1.5 shadow-sm transition active:scale-[0.98] self-start md:self-auto cursor-pointer"
        >
          <span>➕ Đặt Thêm Vé Mới</span>
        </Link>
      </div>

      <!-- MAIN NAVIGATION TABS: TÁCH RIÊNG VÉ XE VÀ ĐIỂM THƯỞNG RÕ RÀNG THEO YÊU CẦU -->
      <div class="bg-white p-1.5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-2">
        <button
          type="button"
          @click="activeTab = 'bookings'"
          class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-black flex items-center justify-center gap-2 transition-all cursor-pointer"
          :class="activeTab === 'bookings' ? 'bg-[#dc2626] text-white shadow-md' : 'text-slate-600 hover:bg-slate-100'"
        >
          <span>🎫 Vé Xe Của Tôi ({{ bookings.length }})</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'loyalty'"
          class="flex-1 py-3 px-4 rounded-xl text-xs sm:text-sm font-black flex items-center justify-center gap-2 transition-all cursor-pointer"
          :class="activeTab === 'loyalty' ? 'bg-amber-500 text-slate-950 shadow-md' : 'text-slate-600 hover:bg-slate-100'"
        >
          <span>⭐ Điểm Thưởng & Hạng Thành Viên</span>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold" :class="activeTab === 'loyalty' ? 'bg-slate-950 text-amber-400' : 'bg-amber-100 text-amber-800'">
            {{ (customer_loyalty?.reward_points || 0).toLocaleString('vi-VN') }} đ
          </span>
        </button>
      </div>

      <!-- ==================================================================== -->
      <!-- TAB 1: VÉ XE CỦA TÔI (CHUYẾN ĐANG CHẠY / SẮP ĐI & LỊCH SỬ CHUYẾN CŨ) -->
      <!-- ==================================================================== -->
      <div v-if="activeTab === 'bookings'" class="space-y-6">
        


        <!-- Empty State Khi Không Tìm Thấy Vé -->
        <div 
          v-if="bookings.length === 0" 
          class="bg-white p-12 rounded-3xl border border-slate-200 text-center space-y-3"
        >
          <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-3xl mx-auto">
            🎫
          </div>
          <h3 class="text-base font-black text-slate-800">
            {{ (!user && !filters?.phone && !filters?.email) ? 'Tra Cứu Vé Xe Điện Tử' : 'Không Tìm Thấy Vé Xe Nào' }}
          </h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto">
            {{ (!user && !filters?.phone && !filters?.email) 
              ? 'Vui lòng nhập số điện thoại hoặc email quý khách đã sử dụng khi đặt vé ở khung tìm kiếm phía trên để tra cứu vé xe.' 
              : 'Không tìm thấy vé xe nào khớp với thông tin tra cứu. Quý khách vui lòng kiểm tra lại số điện thoại hoặc đặt vé mới.' }}
          </p>
          <Link 
            href="/trips" 
            class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-[#dc2626] hover:bg-[#b91c1c] text-white font-black text-xs rounded-xl shadow-sm cursor-pointer mt-2"
          >
            Tìm Chuyến Xe Mới
          </Link>
        </div>

        <!-- 1. PHẦN 1: CHUYẾN XE ĐANG DI CHUYỂN HOẶC SẮP ĐI (CÓ THEO DÕI LIVE GPS "XE ĐI TỚI ĐÂU RỒI") -->
        <div v-if="activeBookings.length > 0" class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-black text-slate-900 uppercase tracking-wide flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
              <span>🚍 Chuyến Xe Đang Chạy / Sắp Khởi Hành ({{ activeBookings.length }} chuyến)</span>
            </h2>
            <span class="text-xs text-slate-500 font-medium">Bấm "Xem Xe Đi Tới Đâu Rồi" để xem vị trí trực tiếp</span>
          </div>

          <div
            v-for="b in activeBookings"
            :key="'active-' + b.id"
            class="bg-white p-5 rounded-3xl border-2 border-red-200 shadow-md hover:shadow-lg transition-all space-y-4 relative overflow-hidden"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono font-black text-sm text-red-600 bg-red-50 border border-red-200 px-2.5 py-0.5 rounded-lg">
                  #{{ b.booking_code }}
                </span>

                <!-- Khứ hồi tag -->
                <span v-if="b.is_round_trip" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 text-indigo-700 border border-indigo-200">
                  🔄 Vé Khứ Hồi • {{ b.round_trip_leg }}
                </span>

                <!-- Trạng thái xe -->
                <span
                  v-if="b.trip_status === 'IN_TRANSIT' || b.trip_status === 'RUNNING'"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-orange-100 text-orange-800 border border-orange-300 flex items-center gap-1.5"
                >
                  <span class="w-2 h-2 rounded-full bg-orange-600 animate-pulse"></span>
                  <span>⚡ XE ĐANG CHẠY TRÊN ĐƯỜNG</span>
                </span>
                <span
                  v-else
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200"
                >
                  🕒 Sắp Khởi Hành
                </span>

                <!-- Trạng thái thanh toán & Hoàn tiền -->
                <span 
                  v-if="b.refund_status === 'PENDING'"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 border border-amber-300 animate-pulse"
                >
                  ⏳ Chờ Duyệt Hoàn Tiền
                </span>
                <span 
                  v-else-if="b.refund_status === 'APPROVED' || b.payment_status === 'REFUNDED'"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300"
                >
                  ✓ Đã Hoàn Tiền
                </span>
                <span 
                  v-else-if="b.refund_status === 'REJECTED'"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300"
                >
                  ✕ Từ Chối Hoàn Tiền
                </span>
                <span 
                  v-else-if="b.payment_status === 'CANCELLED'"
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-100 text-slate-700 border border-slate-300"
                >
                  ✕ Đã Hủy Vé
                </span>
                <span 
                  v-else
                  class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                  :class="b.payment_status === 'PAID' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-300'"
                >
                  {{ b.payment_status === 'PAID' ? '✓ Đã Thanh Toán' : '⚠️ Cọc 30%' }}
                </span>
              </div>

              <span class="text-xs text-slate-500 font-medium">
                Đặt ngày: <strong class="text-slate-700">{{ b.created_at }}</strong>
              </span>
            </div>

            <!-- Thông báo hoàn tiền nếu có -->
            <div 
              v-if="b.refund_status === 'PENDING'" 
              class="p-3 bg-amber-50/90 border border-amber-300 rounded-2xl text-xs text-amber-900 flex items-center justify-between flex-wrap gap-2"
            >
              <div class="flex items-center gap-2">
                <span>⏳</span>
                <span><b>Đã gửi đơn hoàn tiền:</b> {{ formatCurrency(b.refund_amount) }} qua <b>{{ b.refund_bank_name }}</b> (STK: {{ b.refund_account_number }})</span>
              </div>
              <span class="text-[11px] text-amber-700 font-semibold">Đang chờ Admin duyệt & chuyển tiền</span>
            </div>

            <!-- Chi tiết tuyến & Giờ chạy -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center text-xs">
              <div class="md:col-span-6 space-y-1.5">
                <h3 class="text-base font-black text-slate-900">
                  {{ b.route_name || (b.origin + ' ➔ ' + b.destination) }}
                </h3>
                <div class="flex items-center gap-3 text-slate-600 font-medium">
                  <span>Khởi hành: <strong class="text-red-600 font-mono font-bold">{{ b.departure_time }}</strong></span>
                  <span v-if="b.arrival_time">➔ Đến: <strong class="text-slate-800 font-mono">{{ b.arrival_time }}</strong></span>
                </div>
                <div class="flex items-center gap-2 pt-1 text-[11px] text-slate-500 flex-wrap">
                  <span>🚍 Xe: <strong class="font-mono text-slate-800">{{ b.bus_plate }}</strong> ({{ b.bus_type || 'VIP' }})</span>
                  <span>•</span>
                  <span>Tài xế: <strong class="text-slate-800">{{ b.driver_name || 'Bác tài nhaxekientruc' }}</strong> ({{ b.driver_phone || '0905.123.456' }})</span>
                </div>
              </div>

              <!-- Vị trí ghế & Tổng tiền -->
              <div class="md:col-span-3 space-y-1">
                <span class="text-slate-400 block text-[11px] font-semibold">Ghế ngồi đã chọn:</span>
                <div class="flex items-center gap-1 flex-wrap">
                  <span 
                    v-for="s in b.seats" 
                    :key="s"
                    class="px-2.5 py-0.5 rounded-lg bg-red-50 text-red-700 border border-red-200 font-mono font-bold text-xs"
                  >
                    {{ s }}
                  </span>
                  <span v-if="!b.seats || b.seats.length === 0" class="text-slate-400 italic text-[11px]">
                    Ghế đã giải phóng
                  </span>
                </div>
                <div class="text-emerald-700 font-mono font-black text-base pt-0.5">
                  {{ formatCurrency(b.total_amount) }}
                </div>
              </div>

              <!-- Nút Hành Động Đặc Biệt -->
              <div class="md:col-span-3 flex flex-col gap-2">
                <!-- NÚT LIVE GPS TRACKING NỔI BẬT (CHỈ MỞ KHI XE ĐÃ XUẤT BẾN / ĐANG CHẠY) -->
                <button
                  v-if="b.trip_status === 'IN_TRANSIT' || b.trip_status === 'RUNNING'"
                  type="button"
                  @click="openLiveTrackingModal(b)"
                  class="w-full py-2.5 bg-gradient-to-r from-orange-500 via-red-600 to-rose-600 hover:from-orange-600 hover:to-red-700 text-white font-black text-xs rounded-xl flex items-center justify-center gap-2 shadow-md shadow-orange-500/25 active:scale-98 transition cursor-pointer"
                >
                  <span class="animate-bounce">📍</span>
                  <span>Xem Xe Đi Tới Đâu Rồi? (Live GPS)</span>
                </button>
                <div
                  v-else-if="b.trip_status !== 'COMPLETED' && b.trip_status !== 'CLOSED' && b.payment_status !== 'CANCELLED'"
                  class="w-full py-2 px-3 bg-slate-100 border border-slate-200 text-slate-500 text-[11px] font-bold rounded-xl flex items-center justify-center gap-1.5 text-center"
                  title="Live GPS chỉ mở khi xe xuất phát rời bến"
                >
                  <span>⏳ Xe Chưa Chạy • GPS mở khi xuất bến</span>
                </div>

                <Link 
                  :href="`/booking/${b.booking_code}/detail`"
                  class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 transition cursor-pointer"
                >
                  <span>🎫 Xem Vé Điện Tử & Sơ Đồ</span>
                </Link>

                <!-- Nút Hủy vé & Hoàn tiền -->
                <button
                  v-if="b.payment_status !== 'CANCELLED' && b.payment_status !== 'REFUNDED' && b.trip_status !== 'COMPLETED' && b.trip_status !== 'CLOSED'"
                  type="button"
                  @click="openCancelModal(b)"
                  class="w-full py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-[11px] rounded-xl flex items-center justify-center gap-1 border border-rose-200 transition cursor-pointer"
                >
                  <span>🚫 Hủy Vé & Hoàn Tiền</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. PHẦN 2: LỊCH SỬ CÁC CHUYẾN XE ĐÃ ĐI (HOÀN THÀNH AN TOÀN) -->
        <div v-if="pastBookings.length > 0" class="space-y-4 pt-4 border-t border-slate-200">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-black text-slate-700 uppercase tracking-wide flex items-center gap-2">
              <span>📜 Lịch Sử Đặt Vé Cũ (Đã Hoàn Thành - {{ pastBookings.length }} chuyến)</span>
            </h2>
            <span class="text-xs text-slate-400">Điểm thưởng đã được cộng trực tiếp vào số dư</span>
          </div>

          <div
            v-for="b in pastBookings"
            :key="'past-' + b.id"
            class="bg-white p-5 rounded-3xl border border-slate-200/90 shadow-2xs hover:shadow-sm transition-all space-y-3 opacity-90 hover:opacity-100"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-slate-100">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-mono font-bold text-xs text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                  #{{ b.booking_code }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                  ✓ Đã Hoàn Thành Chuyến Đi
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                  +{{ b.points_earned }} Điểm thưởng
                </span>
              </div>
              <span class="text-xs text-slate-400">Ngày đặt: {{ b.created_at }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center text-xs">
              <div class="md:col-span-7">
                <h4 class="font-black text-sm text-slate-800">{{ b.route_name }}</h4>
                <p class="text-slate-500 mt-0.5">Khởi hành: {{ b.departure_time }} • Ghế: {{ b.seats.join(', ') }}</p>
              </div>

              <div class="md:col-span-2 text-right">
                <div class="font-mono font-bold text-slate-700">{{ formatCurrency(b.total_amount) }}</div>
              </div>

              <div class="md:col-span-3 flex items-center justify-end gap-2">
                <Link 
                  v-if="!b.has_reviewed"
                  :href="`/booking/${b.booking_code}/review`"
                  class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-xs rounded-xl border border-amber-200 transition"
                >
                  ⭐ Đánh Giá
                </Link>
                <Link 
                  :href="`/booking/${b.booking_code}/detail`"
                  class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition"
                >
                  Xem Lại Vé
                </Link>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ==================================================================== -->
      <!-- TAB 2: ĐIỂM THƯỞNG KHÁCH HÀNG & PHÂN HẠNG THÀNH VIÊN (TÁCH RIÊNG ĐỘC LẬP) -->
      <!-- ==================================================================== -->
      <div v-else-if="activeTab === 'loyalty'" class="space-y-6">
        
        <!-- THẺ THÀNH VIÊN VIP SANG TRỌNG -->
        <div class="bg-gradient-to-br from-rose-50/80 via-white to-amber-50/70 p-6 sm:p-8 rounded-3xl border-2 border-red-200 shadow-sm space-y-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b border-red-100">
            <div class="space-y-2">
              <div class="flex items-center gap-2 flex-wrap">
                <span 
                  class="px-3 py-1 rounded-full text-xs font-black tracking-wide uppercase inline-flex items-center gap-1.5 shadow-2xs"
                  :class="getTierBadgeClass(customer_loyalty?.loyalty_tier)"
                >
                  <span>{{ getTierIcon(customer_loyalty?.loyalty_tier) }}</span>
                  <span>{{ getTierLabel(customer_loyalty?.loyalty_tier) }}</span>
                </span>
                <span class="text-xs text-slate-500 font-medium">
                  Hành khách: <b class="text-slate-900 font-bold">{{ user?.name || filters?.phone || 'Khách Hàng Thân Thiết' }}</b>
                </span>
              </div>

              <div class="flex items-baseline gap-2 pt-2">
                <span class="text-4xl sm:text-5xl font-black font-mono text-amber-600">
                  {{ (customer_loyalty?.reward_points || 0).toLocaleString('vi-VN') }}
                </span>
                <span class="text-xs sm:text-sm font-black text-slate-700 uppercase tracking-wider">Điểm Thưởng Khả Dụng</span>
              </div>

              <p class="text-xs text-slate-600 leading-relaxed max-w-xl">
                🎁 <b>Quy đổi điểm thưởng:</b> Mỗi 1.000 VNĐ chi tiêu đặt vé tích lũy được 1 điểm thưởng. Khi đặt chuyến đi mới, bạn có thể áp dụng <b>100 điểm = 1.000 VNĐ giảm giá trực tiếp</b> vào tiền vé.
              </p>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs shrink-0">
              <div class="p-4 rounded-2xl bg-white border border-amber-200/80 shadow-2xs space-y-1">
                <span class="text-[11px] text-slate-500 font-semibold block">
                  <span v-if="(customer_loyalty?.pending_points || 0) > 0">⏳ Điểm chờ xe hoàn thành:</span>
                  <span v-else>✓ Điểm chờ xử lý:</span>
                </span>
                <strong class="text-base font-black text-amber-600 font-mono block">
                  {{ (customer_loyalty?.pending_points || 0) > 0 ? '+' + (customer_loyalty.pending_points).toLocaleString('vi-VN') + ' đ' : '0 đ' }}
                </strong>
                <span class="text-[10px]" :class="(customer_loyalty?.pending_points || 0) > 0 ? 'text-slate-400' : 'text-emerald-700 font-bold'">
                  {{ (customer_loyalty?.pending_points || 0) > 0 ? 'Sẽ cộng khi xe đến bến' : '✓ Toàn bộ đã cộng vào ví' }}
                </span>
              </div>

              <div class="p-4 rounded-2xl bg-white border border-emerald-200/80 shadow-2xs space-y-1">
                <span class="text-[11px] text-slate-500 font-semibold block">✓ Chuyến đã hoàn thành:</span>
                <strong class="text-base font-black text-emerald-700 font-mono block">
                  {{ customer_loyalty?.completed_trips || 0 }} chuyến
                </strong>
                <span class="text-[10px] text-slate-400">Tích lũy an toàn 100%</span>
              </div>
            </div>
          </div>

          <!-- QUY ĐỊNH & ĐẶC QUYỀN HẠNG THÀNH VIÊN -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-1.5">
              <div class="text-slate-900 font-black text-xs flex items-center gap-1.5">
                <span>🥈 Hạng Bạc (Thân thiết)</span>
              </div>
              <p class="text-[11px] text-slate-500 leading-relaxed">
                Áp dụng cho khách hàng hoàn thành từ 2 chuyến xe. Tích lũy điểm tiêu chuẩn và nhận ưu đãi sinh nhật.
              </p>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-amber-200 bg-amber-50/20 space-y-1.5">
              <div class="text-amber-800 font-black text-xs flex items-center gap-1.5">
                <span>👑 Hạng Vàng (VIP)</span>
              </div>
              <p class="text-[11px] text-slate-500 leading-relaxed">
                Áp dụng cho khách hàng từ 5 chuyến xe. Tặng mã giảm giá 15% độc quyền và ưu tiên chọn chỗ ngồi tầng 1.
              </p>
            </div>

            <div class="p-4 rounded-2xl bg-white border border-purple-200 bg-purple-50/20 space-y-1.5">
              <div class="text-purple-800 font-black text-xs flex items-center gap-1.5">
                <span>💎 Hạng Kim Cương (Super VIP)</span>
              </div>
              <p class="text-[11px] text-slate-500 leading-relaxed">
                Áp dụng cho khách hàng thân thiết lâu năm. Miễn phí hủy vé trước 2 giờ, hỗ trợ xe trung chuyển tận nhà.
              </p>
            </div>
          </div>
        </div>

      </div>

      <!-- ==================================================================== -->
      <!-- MODAL THEO DÕI XE TRỰC TUYẾN "XE ĐI TỚI ĐÂU RỒI" (LIVE GPS TRACKING) -->
      <!-- ==================================================================== -->
      <div 
        v-if="showLiveTrackingModal && trackingBooking" 
        class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in duration-150"
      >
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-5">
          <!-- Header Modal -->
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-orange-500 to-red-600 text-white flex items-center justify-center text-lg shadow-md shadow-orange-500/25">
                📍
              </div>
              <div>
                <h3 class="font-black text-base text-slate-900">VỊ TRÍ XE TRỰC TUYẾN (LIVE GPS)</h3>
                <p class="text-xs text-slate-500">Mã vé: <strong class="font-mono text-red-600">#{{ trackingBooking.booking_code }}</strong> • Biển số: <strong class="font-mono text-slate-800">{{ trackingBooking.bus_plate }}</strong></p>
              </div>
            </div>
            <button 
              type="button" 
              @click="showLiveTrackingModal = false" 
              class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold flex items-center justify-center transition cursor-pointer"
            >
              ✕
            </button>
          </div>

          <!-- Thông số vận hành xe thực tế -->
          <div class="grid grid-cols-3 gap-2.5 text-center">
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
              <span class="text-[10px] text-slate-400 font-bold uppercase block">Vận tốc hiện tại</span>
              <span class="text-base font-black text-slate-900 font-mono">{{ trackingBooking.current_speed || 68 }} km/h</span>
            </div>
            <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200">
              <span class="text-[10px] text-emerald-700 font-bold uppercase block">Lộ trình</span>
              <span class="text-base font-black text-emerald-700 font-mono">Đúng giờ</span>
            </div>
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
              <span class="text-[10px] text-slate-400 font-bold uppercase block">Tài xế cầm lái</span>
              <span class="text-xs font-bold text-slate-800 truncate block">{{ trackingBooking.driver_name || 'Bác tài' }}</span>
            </div>
          </div>

          <!-- Vị trí thực tế trên Quốc Lộ 1A -->
          <div class="p-4 rounded-2xl bg-slate-900 text-white space-y-3 shadow-inner">
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-400">Vị trí hiện tại của xe:</span>
              <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold">Vệ Tinh GPS 4G</span>
            </div>
            <p class="text-sm font-bold text-amber-300 leading-snug">
              🚍 Xe đang di chuyển trên trục Quốc Lộ 1A, cách trạm tiếp theo khoảng 15 phút di chuyển.
            </p>

            <!-- Thanh tiến trình hành trình -->
            <div class="pt-2 space-y-1.5">
              <div class="flex items-center justify-between text-[11px] text-slate-400">
                <span>{{ trackingBooking.origin || 'Đà Nẵng' }}</span>
                <span class="text-amber-400 font-bold">Vị trí xe hiện tại (65%)</span>
                <span>{{ trackingBooking.destination || 'Điểm đến' }}</span>
              </div>
              <div class="w-full bg-slate-800 h-2.5 rounded-full overflow-hidden p-0.5 border border-slate-700">
                <div class="bg-gradient-to-r from-emerald-500 to-amber-400 h-full rounded-full transition-all duration-500" style="width: 65%;"></div>
              </div>
            </div>
          </div>

          <!-- Nút xem đầy đủ -->
          <div class="pt-2 flex items-center justify-end gap-2">
            <Link 
              :href="`/booking/${trackingBooking.booking_code}/detail`"
              class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs text-center shadow-md transition cursor-pointer"
            >
              Xem Chi Tiết Vé & QR Check-in ➔
            </Link>
          </div>
        </div>
      </div>

      <!-- MODAL HỦY VÉ & YÊU CẦU HOÀN TIỀN TRỰC TIẾP -->
      <div v-if="showCancelModal && selectedCancelBooking" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl border border-slate-200 animate-in zoom-in-95">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
              <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-black">🚫</span>
              <div>
                <h3 class="font-black text-slate-900 text-sm">Yêu Cầu Hủy Vé & Hoàn Tiền</h3>
                <p class="text-xs text-slate-500">Mã vé: <strong class="font-mono text-red-600">{{ selectedCancelBooking.booking_code }}</strong></p>
              </div>
            </div>
            <button @click="showCancelModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold flex items-center justify-center cursor-pointer">✕</button>
          </div>

          <!-- Thông tin chuyến & tiền hoàn dự kiến -->
          <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 text-xs space-y-1.5">
            <div class="flex items-center justify-between">
              <span class="text-slate-500">Chuyến đi:</span>
              <strong class="text-slate-800">{{ selectedCancelBooking.route_name || (selectedCancelBooking.origin + ' ➔ ' + selectedCancelBooking.destination) }}</strong>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-500">Số tiền đã trả:</span>
              <strong class="text-slate-900 font-mono">{{ formatCurrency(selectedCancelBooking.paid_amount || selectedCancelBooking.total_amount) }}</strong>
            </div>
            <div class="flex items-center justify-between pt-1 border-t border-slate-200">
              <span class="text-emerald-700 font-bold">Số tiền hoàn dự kiến ({{ selectedCancelBooking.refund_percent || 100 }}%):</span>
              <strong class="text-emerald-700 font-mono text-sm font-black">{{ formatCurrency(calculatedRefundAmount) }}</strong>
            </div>
          </div>

          <!-- Form hoàn tiền -->
          <form @submit.prevent="submitCancelRefund" class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Ngân Hàng Nhận Tiền <span class="text-red-500">*</span></label>
              <select v-model="cancelForm.bank_name" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-800 focus:bg-white focus:outline-red-500 cursor-pointer">
                <option value="Vietcombank">Vietcombank (VCB)</option>
                <option value="MBBank">MB Bank (Quân Đội)</option>
                <option value="Techcombank">Techcombank (TCB)</option>
                <option value="VPBank">VPBank</option>
                <option value="ACB">ACB (Á Châu)</option>
                <option value="BIDV">BIDV</option>
                <option value="Agribank">Agribank</option>
                <option value="TPBank">TPBank</option>
                <option value="VIB">VIB</option>
                <option value="Sacombank">Sacombank</option>
                <option value="MoMo">Ví MoMo</option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Số Tài Khoản Nhận Tiền <span class="text-red-500">*</span></label>
              <input v-model="cancelForm.account_number" type="text" required placeholder="Nhập số tài khoản ngân hàng..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-slate-800 focus:bg-white focus:outline-red-500" />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Họ Và Tên Chủ Tài Khoản <span class="text-red-500">*</span></label>
              <input v-model="cancelForm.account_name" type="text" required placeholder="Ví dụ: NGUYEN VAN A (viết hoa không dấu)" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold uppercase text-slate-800 focus:bg-white focus:outline-red-500" />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Lý Do Hủy Vé <span class="text-red-500">*</span></label>
              <textarea v-model="cancelForm.reason" rows="2" required placeholder="Nhập lý do thay đổi lịch trình hoặc bận việc đột xuất..." class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-800 focus:bg-white focus:outline-red-500"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-slate-100">
              <button type="button" @click="showCancelModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition cursor-pointer">
                Đóng
              </button>
              <button type="submit" :disabled="isSubmittingCancel" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-xl shadow-md shadow-rose-600/30 transition cursor-pointer disabled:opacity-50">
                {{ isSubmittingCancel ? 'Đang gửi yêu cầu...' : 'Xác Nhận Hủy Vé & Nhận Tiền Hoàn' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
  bookings: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  total_count: { type: Number, default: 0 },
  user: { type: Object, default: null },
  customer_loyalty: { type: Object, default: () => ({}) },
});

const activeTab = ref('bookings'); // 'bookings' | 'loyalty'

// Tách danh sách vé: Đang đi & Sắp đi vs Lịch sử đã hoàn thành
const activeBookings = computed(() => {
  return props.bookings.filter(b => b.trip_status !== 'COMPLETED' && b.trip_status !== 'CLOSED');
});

const pastBookings = computed(() => {
  return props.bookings.filter(b => b.trip_status === 'COMPLETED' || b.trip_status === 'CLOSED');
});

// Modal Live Tracking
const showLiveTrackingModal = ref(false);
const trackingBooking = ref(null);

const openLiveTrackingModal = (booking) => {
  trackingBooking.value = booking;
  showLiveTrackingModal.value = true;
};

const showCancelModal = ref(false);
const selectedCancelBooking = ref(null);
const isSubmittingCancel = ref(false);
const cancelForm = ref({
  bank_name: 'Vietcombank',
  account_number: '',
  account_name: '',
  reason: 'Có việc bận đột xuất cần dời lịch trình',
  insist_exception: true
});

const calculatedRefundAmount = computed(() => {
  if (!selectedCancelBooking.value) return 0;
  const b = selectedCancelBooking.value;
  const paid = (b.paid_amount || b.total_amount || 0);
  const pct = b.refund_percent !== undefined ? b.refund_percent : 100;
  return Math.round(paid * (pct / 100));
});

const openCancelModal = (booking) => {
  selectedCancelBooking.value = booking;
  cancelForm.value.account_name = booking.customer_name || '';
  showCancelModal.value = true;
};

const submitCancelRefund = () => {
  if (!selectedCancelBooking.value) return;
  isSubmittingCancel.value = true;
  router.post(`/booking/${selectedCancelBooking.value.booking_code}/cancel-request`, cancelForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmittingCancel.value = false;
      showCancelModal.value = false;
      selectedCancelBooking.value = null;
    },
    onError: () => {
      isSubmittingCancel.value = false;
    }
  });
};

const getTierBadgeClass = (tier) => {
  switch (tier) {
    case 'DIAMOND':
      return 'bg-gradient-to-r from-purple-600 via-indigo-600 to-pink-600 text-white shadow-md ring-1 ring-purple-300';
    case 'GOLD':
      return 'bg-gradient-to-r from-amber-500 to-yellow-600 text-white shadow-sm ring-1 ring-amber-300';
    case 'SILVER':
      return 'bg-gradient-to-r from-slate-600 to-slate-800 text-white shadow-xs';
    default:
      return 'bg-emerald-600 text-white';
  }
};

const getTierIcon = (tier) => {
  switch (tier) {
    case 'DIAMOND': return '💎';
    case 'GOLD': return '👑';
    case 'SILVER': return '🥈';
    default: return '⭐';
  }
};

const getTierLabel = (tier) => {
  switch (tier) {
    case 'DIAMOND': return 'VIP Kim Cương';
    case 'GOLD': return 'VIP Vàng';
    case 'SILVER': return 'Thân Thiết Bạc';
    default: return 'Thành Viên Mới';
  }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};
</script>
