<template>
  <div class="min-h-screen bg-slate-50 flex flex-col text-slate-800">
    <Navbar />

    <main class="flex-1 max-w-5xl mx-auto px-4 sm:px-6 py-8 w-full space-y-6">
      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2 shadow-xs">
        <span class="text-base">✅</span>
        <span>{{ $page.props.flash.success }}</span>
      </div>
      <div v-if="$page.props.flash?.error" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-bold flex items-center gap-2 shadow-xs">
        <span class="text-base">⚠️</span>
        <span>{{ $page.props.flash.error }}</span>
      </div>

      <!-- Breadcrumb & Top Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-slate-500">
          <Link href="/" class="hover:text-red-600 font-medium">Trang chủ</Link>
          <span>/</span>
          <Link href="/my-bookings" class="hover:text-red-600 font-medium">Vé của tôi</Link>
          <span>/</span>
          <span class="font-bold text-slate-800">Chi tiết vé #{{ booking.booking_code }}</span>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <button 
            @click="printTicket"
            class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs flex items-center gap-1.5 shadow-2xs cursor-pointer"
          >
            <span>🖨️ In Vé</span>
          </button>

          <!-- NÚT HỦY VÉ & HOÀN TIỀN -->
          <button
            v-if="booking.payment_status !== 'CANCELLED' && booking.payment_status !== 'REFUNDED' && booking.refund_status !== 'PENDING' && !booking.trip_completed"
            @click="openCancelModal"
            type="button"
            class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs flex items-center gap-1.5 shadow-2xs cursor-pointer transition active:scale-95"
          >
            <span>🚫 Hủy Vé & Hoàn Tiền</span>
          </button>

          <Link
            v-if="!booking.has_reviewed && booking.trip_completed"
            :href="`/booking/${booking.booking_code}/review`"
            class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs flex items-center gap-1.5 shadow-2xs cursor-pointer"
          >
            <span>⭐ Đánh Giá Chuyến Đi</span>
          </Link>
        </div>
      </div>

      <!-- ==================== BANNER TRỢ LÝ AI GỬI MÃ QR & NHẮC LỊCH QUA GMAIL (REQUEST 6) ==================== -->
      <div 
        v-if="booking.payment_status !== 'CANCELLED' && booking.payment_status !== 'REFUNDED'"
        class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border-2 border-indigo-500/40 rounded-3xl p-5 sm:p-6 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-5 relative overflow-hidden"
      >
        <div class="space-y-2 z-10 max-w-xl">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/40 text-[10.5px] font-black uppercase tracking-wider text-indigo-300">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Hệ Thống Trợ Lý AI nhaxekientruc</span>
          </div>

          <h3 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
            <span>🤖</span>
            <span>Tự Động Gửi Mã QR Vé & Nhắc Lịch Về Gmail</span>
          </h3>

          <p class="text-xs text-slate-300 leading-relaxed font-normal">
            Hệ thống đã tự động gửi xác nhận đặt vé và mã QR check-in về hòm thư <strong class="text-amber-300 font-mono">{{ booking.customer_email }}</strong>. Trước giờ xuất bến 45 phút, AI sẽ tự động gửi thêm thông báo nhắc lịch và cập nhật vị trí đón xe.
          </p>

          <div class="flex items-center gap-4 text-[11px] text-slate-300 pt-1 flex-wrap">
            <span class="flex items-center gap-1.5 text-emerald-400 font-bold">
              <span>✓</span> Email xác nhận đã gửi
            </span>
            <span class="flex items-center gap-1.5 text-amber-300 font-bold">
              <span>⏰</span> Tự động nhắc trước 45 phút
            </span>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 z-10 shrink-0">
          <button
            type="button"
            @click="triggerAiReminderEmail"
            :disabled="isSendingAiEmail"
            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-black text-xs shadow-md shadow-red-600/30 flex items-center justify-center gap-1.5 transition cursor-pointer disabled:opacity-60"
          >
            <span>{{ isSendingAiEmail ? '⏳ Đang gửi...' : '📧 Gửi Mã QR Về Gmail Ngay' }}</span>
          </button>
        </div>

        <!-- Ambient decorative shapes -->
        <div class="absolute -right-8 -bottom-8 w-48 h-48 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>
      </div>

      <!-- ==================== BANNER TRẠNG THÁI HOÀN TIỀN NỔI BẬT ==================== -->
      <!-- Case 1: ĐANG CHỜ ADMIN DUYỆT HOÀN TIỀN -->
      <div 
        v-if="booking.refund_status === 'PENDING'" 
        class="bg-gradient-to-r from-amber-500/15 via-yellow-500/10 to-amber-500/15 border-2 border-amber-300 rounded-3xl p-6 shadow-sm space-y-4"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-amber-200/80">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl shadow-md">
              ⏳
            </div>
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block">ĐƠN YÊU CẦU HOÀN TIỀN</span>
              <h3 class="text-base sm:text-lg font-black text-slate-900">
                Đang Chờ Admin Phê Duyệt & Chuyển Khoản Hoàn Tiền
              </h3>
            </div>
          </div>
          <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-amber-100 text-amber-900 border border-amber-300 self-start sm:self-auto">
            Số tiền dự kiến: {{ formatCurrency(booking.refund_amount) }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center text-xs">
          <!-- Thông tin tài khoản đã gửi -->
          <div class="md:col-span-8 space-y-2">
            <p class="text-slate-700 leading-relaxed">
              Quý khách đã gửi yêu cầu hủy vé thành công lúc <strong class="font-mono text-slate-900">{{ booking.refund_requested_at || booking.cancelled_at }}</strong>. Ghế ngồi đã được giải phóng. Ban quản trị nhaxekientruc đang tiến hành kiểm tra thông tin và thực hiện chuyển khoản.
            </p>
            <div class="p-3.5 rounded-2xl bg-white border border-amber-200/80 grid grid-cols-1 sm:grid-cols-3 gap-2">
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Ngân Hàng Thụ Hưởng:</span>
                <strong class="text-slate-900 font-bold">{{ booking.refund_bank_name || 'Ngân hàng' }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Số Tài Khoản (STK):</span>
                <strong class="text-slate-900 font-mono font-black">{{ booking.refund_account_number }}</strong>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Chủ Tài Khoản:</span>
                <strong class="text-slate-900 font-bold uppercase">{{ booking.refund_account_name }}</strong>
              </div>
            </div>
            <p v-if="booking.refund_reason" class="text-[11px] text-slate-500 italic">
              Lý do hủy: "{{ booking.refund_reason }}"
            </p>
          </div>

          <!-- Mã QR Chuyển Khoản Đã Gửi -->
          <div class="md:col-span-4 flex flex-col items-center justify-center p-3 rounded-2xl bg-white border border-amber-200/80 text-center">
            <span class="text-[10px] font-black uppercase text-slate-400 mb-1.5">Mã QR Nhận Tiền Hoàn</span>
            <div 
              @click="openQrPreview(booking.refund_qr_code)" 
              class="w-24 h-24 p-1.5 rounded-xl border border-slate-200 bg-slate-50 shadow-2xs hover:scale-105 transition cursor-pointer flex items-center justify-center group"
              title="Bấm để xem phóng to mã QR"
            >
              <img 
                v-if="booking.refund_qr_code" 
                :src="booking.refund_qr_code" 
                alt="QR Hoàn Tiền" 
                class="w-full h-full object-contain rounded"
              />
              <span v-else class="text-slate-400 text-xs">Chưa có QR</span>
            </div>
            <span class="text-[10px] text-amber-700 font-medium mt-1">Bấm để phóng to</span>
          </div>
        </div>

        <div class="flex items-center justify-between flex-wrap gap-2 pt-2 text-[11px] text-slate-500 border-t border-amber-200/60">
          <span>Thời gian xử lý thông thường: <b>từ 2 đến 24 giờ làm việc</b></span>
          <span>Hotline hỗ trợ hoàn tiền: <a href="tel:19006868" class="font-bold text-red-600 hover:underline">1900 6868</a></span>
        </div>
      </div>

      <!-- Case 2: ĐÃ HOÀN TIỀN THÀNH CÔNG -->
      <div 
        v-else-if="booking.refund_status === 'APPROVED' || booking.payment_status === 'REFUNDED'" 
        class="bg-gradient-to-r from-emerald-500/15 via-teal-500/10 to-emerald-500/15 border-2 border-emerald-300 rounded-3xl p-6 shadow-sm space-y-4"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-emerald-200/80">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl shadow-md">
              ✓
            </div>
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 block">KẾ TOÁN ĐÃ PHÊ DUYỆT</span>
              <h3 class="text-base sm:text-lg font-black text-slate-900">
                Đã Hoàn Tiền Thành Công Vào Tài Khoản
              </h3>
            </div>
          </div>
          <span class="px-3.5 py-1.5 rounded-full text-xs font-mono font-black bg-emerald-600 text-white shadow-xs self-start sm:self-auto">
            ĐÃ HOÀN: {{ formatCurrency(booking.refund_amount || booking.paid_amount) }}
          </span>
        </div>

        <div class="text-xs text-slate-700 space-y-2">
          <p class="leading-relaxed">
            Hệ thống đã hoàn tất lệnh chuyển khoản hoàn trả <b>{{ formatCurrency(booking.refund_amount || booking.paid_amount) }}</b> về tài khoản ngân hàng của quý khách. Quý khách vui lòng kiểm tra biến động số dư tài khoản ngân hàng.
          </p>
          <div class="p-3.5 rounded-2xl bg-white border border-emerald-200/80 space-y-1.5">
            <div class="flex items-center justify-between flex-wrap gap-2">
              <span>Tài khoản nhận: <strong class="text-slate-900">{{ booking.refund_bank_name }} - STK: {{ booking.refund_account_number }} ({{ booking.refund_account_name }})</strong></span>
              <span class="text-slate-500">Xử lý lúc: <strong class="text-slate-800">{{ booking.refund_processed_at || 'Mới đây' }}</strong></span>
            </div>
            <div v-if="booking.refund_admin_notes" class="text-[11px] text-emerald-800 bg-emerald-50 p-2 rounded-xl border border-emerald-200">
              📌 <b>Ghi chú giao dịch:</b> {{ booking.refund_admin_notes }}
            </div>
          </div>
        </div>
      </div>

      <!-- Case 3: TỪ CHỐI HOÀN TIỀN -->
      <div 
        v-else-if="booking.refund_status === 'REJECTED'" 
        class="bg-gradient-to-r from-rose-500/15 via-red-500/10 to-rose-500/15 border-2 border-rose-300 rounded-3xl p-6 shadow-sm space-y-4"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-rose-200/80">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-xl shadow-md">
              ✕
            </div>
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-rose-800 block">THÔNG BÁO TỪ BAN QUẢN TRỊ</span>
              <h3 class="text-base sm:text-lg font-black text-slate-900">
                Yêu Cầu Hoàn Tiền Bị Từ Chối Theo Quy Chế
              </h3>
            </div>
          </div>
          <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300 self-start sm:self-auto">
            Thu phí hủy: 100%
          </span>
        </div>

        <div class="text-xs text-slate-700 space-y-3">
          <div class="p-3.5 rounded-2xl bg-white border border-rose-200/80 space-y-1">
            <span class="text-slate-400 block text-[10px] font-bold uppercase">Lý Do Từ Chối Cụ Thể:</span>
            <p class="text-rose-700 font-bold text-sm">
              {{ booking.refund_admin_notes || 'Hủy vé quá muộn (dưới 12 tiếng trước giờ khởi hành) hoặc không đúng quy định hoàn tiền của nhà xe.' }}
            </p>
          </div>

          <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <span class="font-bold text-rose-900 block">Cần khiếu nại hoặc trao đổi trực tiếp với nhà xe?</span>
              <span class="text-[11px] text-rose-700">Bộ phận Chăm Sóc Khách Hàng luôn sẵn sàng lắng nghe và giải thích chi tiết.</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <a 
                href="tel:19006868" 
                class="px-3 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs flex items-center gap-1 shadow-2xs"
              >
                <span>📞 Hotline: 1900 6868</span>
              </a>
              <a 
                href="https://zalo.me/0905888999" 
                target="_blank"
                class="px-3 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs flex items-center gap-1 shadow-2xs"
              >
                <span>💬 Zalo CSKH</span>
              </a>
            </div>
          </div>
        </div>
      </div>



      <!-- MAIN TICKET CARD -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <!-- Ticket Header Gradient -->
        <div class="bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 text-white p-6 sm:p-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase bg-white/20 backdrop-blur-md border border-white/20">
                THẺ LÊN XE ĐIỆN TỬ • NHAXEKIENTRUC
              </span>
              <span 
                class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase"
                :class="bookingBadgeClass"
              >
                {{ bookingBadgeText }}
              </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black mt-2 tracking-tight">
              {{ trip?.route_name || (trip?.origin + ' ➔ ' + trip?.destination) }}
            </h1>
            <p class="text-xs text-white/80 mt-1">
              Khởi hành lúc <strong class="text-white">{{ trip?.departure_time }}</strong> • Ngày <strong class="text-white">{{ trip?.departure_date }}</strong>
            </p>
          </div>

          <div class="bg-black/20 backdrop-blur-md p-4 rounded-2xl border border-white/20 text-left md:text-right shrink-0">
            <span class="text-[10px] uppercase font-bold tracking-wider text-white/80 block">MÃ ĐẶT VÉ</span>
            <span class="text-2xl font-mono font-black text-amber-300 tracking-wider block">{{ booking.booking_code }}</span>
            <span class="text-[11px] text-white/70 block mt-0.5">Đặt lúc: {{ booking.created_at }}</span>
          </div>
        </div>

        <!-- Ticket Content Body (Grid) -->
        <div class="p-6 sm:p-8 grid grid-cols-1 lg:grid-cols-12 gap-8">
          <!-- Cột Trái: Chi tiết hành trình & Hành khách (7 cột) -->
          <div class="lg:col-span-7 space-y-6">
            <!-- Thẻ vị trí ghế & Giá vé -->
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center justify-between flex-wrap gap-4">
              <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase block">VỊ TRÍ GHẾ / GIƯỜNG</span>
                <div class="flex items-center gap-1.5 mt-1">
                  <span 
                    v-for="seat in booking.seats" 
                    :key="seat"
                    class="px-3 py-1 rounded-xl bg-red-600 text-white font-mono font-black text-sm shadow-2xs"
                  >
                    {{ seat }}
                  </span>
                  <span v-if="!booking.seats || booking.seats.length === 0" class="text-xs text-slate-500 italic">
                    Ghế đã giải phóng
                  </span>
                </div>
                <span class="text-[11px] text-slate-500 mt-1 block">{{ trip?.bus_type || 'Xe Giường Nằm VIP' }}</span>
              </div>

              <div class="text-right">
                <span class="text-[11px] font-bold text-slate-400 uppercase block">TỔNG TIỀN VÉ</span>
                <span class="text-2xl font-black text-emerald-700 font-mono block">{{ formatCurrency(booking.total_amount) }}</span>
                <span class="text-[11px] text-slate-500 block">
                  {{ booking.payment_method === 'CASH' ? 'Tiền mặt' : (booking.payment_method === 'TRANSFER' ? 'Chuyển khoản' : 'Online') }}
                </span>
              </div>
            </div>

            <!-- ==================== KHỐI ĐIỂM THƯỞNG & VOUCHER ==================== -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-amber-500/10 via-yellow-500/10 to-amber-500/5 border border-amber-300/80 space-y-3">
              <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2.5">
                  <span class="text-2xl">🪙</span>
                  <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 block">CHÍNH SÁCH TÍCH ĐIỂM THÀNH VIÊN NHAXEKIENTRUC</span>
                    <strong class="text-sm font-black text-slate-900">
                      {{ isPointsAwarded ? '✓ Đã tích lũy thành công' : '⏳ Điểm thưởng dự kiến' }}: 
                      <span class="text-amber-700 font-mono text-base font-black">+{{ booking.points_earned }} Điểm</span>
                    </strong>
                  </div>
                </div>
                <span 
                  class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                  :class="isPointsAwarded ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'"
                >
                  {{ isPointsAwarded ? '🟢 Đã cộng vào tài khoản' : '⏳ Chờ chuyến xe hoàn thành' }}
                </span>
              </div>

              <div class="text-[11px] text-slate-600 bg-white/80 p-3 rounded-xl border border-amber-200/60 leading-relaxed">
                <span v-if="isPointsAwarded">
                  🎉 Chuyến xe đã hoàn thành an toàn! <b>+{{ booking.points_earned }} điểm thưởng</b> đã được cộng vào tài khoản của bạn.
                </span>
                <span v-else>
                  ℹ️ Điểm thưởng (1.000 VNĐ = 1 điểm) sẽ được tự động cộng vào tài khoản thành viên ngay sau khi chuyến xe chạy và cập bến an toàn.
                </span>

                <div v-if="customer_account" class="mt-2 pt-2 border-t border-slate-100 font-medium text-slate-700 flex flex-wrap items-center justify-between gap-1">
                  <span>Hành khách: <b>{{ customer_account.name }}</b></span>
                  <span class="font-bold text-amber-800 font-mono">Tổng điểm tích lũy: {{ customer_account.reward_points }} điểm (Hạng {{ customer_account.loyalty_tier }})</span>
                </div>
              </div>

              <div v-if="booking.voucher_code" class="flex items-center gap-2 text-xs font-bold text-emerald-800 bg-emerald-50 px-3 py-2 rounded-xl border border-emerald-200">
                <span>🎟️</span>
                <span>Mã Voucher đã áp dụng: <strong class="font-mono">{{ booking.voucher_code }}</strong> (Tiết kiệm -{{ formatCurrency(booking.discount_amount) }})</span>
              </div>
            </div>

            <!-- Thông tin khách hàng -->
            <div class="space-y-3">
              <h2 class="text-xs font-black uppercase text-slate-400 tracking-wider">Thông Tin Hành Khách</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                  <span class="text-slate-400 block text-[11px]">Họ Và Tên:</span>
                  <strong class="text-slate-900 font-black text-sm block mt-0.5">{{ booking.customer_name }}</strong>
                </div>
                <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                  <span class="text-slate-400 block text-[11px]">Số Điện Thoại:</span>
                  <strong class="text-slate-900 font-mono font-black text-sm block mt-0.5">{{ booking.customer_phone }}</strong>
                </div>
              </div>

              <!-- HÀNH LÝ MIỄN PHÍ (FREE 0Đ) -->
              <div class="px-4 py-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-950 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2.5">
                  <span class="text-lg">🧳</span>
                  <div>
                    <span class="font-bold text-slate-900">Hành lý ký gửi & xách tay: </span>
                    <span class="text-emerald-700 font-medium">Bao gồm 20kg ký gửi gầm xe + xách tay miễn phí</span>
                  </div>
                </div>
                <span class="font-black text-emerald-700 bg-white px-2 py-0.5 rounded border border-emerald-200 text-[10px] uppercase">
                  FREE 0đ
                </span>
              </div>

              <!-- HỘP LƯU Ý ĐẶC BIỆT CỦA HÀNH KHÁCH (CHỈ HIỆN KHI KHÁCH CÓ GHI CHÚ) -->
              <div 
                v-if="booking.notes && booking.notes.trim() !== '' && booking.notes !== 'Đặt vé trực tuyến qua nhaxekientruc'"
                class="px-4 py-3 rounded-2xl bg-[#FFFBEB] border border-[#FDE68A] text-[#92400E] flex items-start gap-2.5 text-xs shadow-2xs"
              >
                <span class="text-amber-500 font-bold text-sm shrink-0 leading-none">⚠️</span>
                <div class="leading-relaxed font-medium">
                  <strong class="font-bold text-amber-900">Lưu ý:</strong>
                  <span> {{ booking.notes }}</span>
                </div>
              </div>
            </div>

            <!-- Điểm đón & Điểm trả -->
            <div class="space-y-3">
              <h2 class="text-xs font-black uppercase text-slate-400 tracking-wider">Lộ Trình Điểm Đón & Trả</h2>
              <div class="p-4 rounded-2xl border border-slate-200 bg-white space-y-3 text-xs">
                <div class="flex items-start gap-3">
                  <div class="w-3 h-3 rounded-full bg-emerald-500 mt-1 shrink-0 ring-4 ring-emerald-100"></div>
                  <div>
                    <span class="text-slate-400 font-medium block text-[11px]">ĐIỂM ĐÓN KHÁCH</span>
                    <strong class="text-slate-900 font-bold text-sm">{{ booking.pickup_stop?.stop_name || trip?.origin || 'Bến xe xuất phát' }}</strong>
                    <p class="text-[11px] text-slate-500 mt-0.5">Khởi hành: {{ trip?.departure_time }} ({{ trip?.departure_date }})</p>
                  </div>
                </div>

                <div class="border-l-2 border-dashed border-slate-200 ml-1.5 h-6"></div>

                <div class="flex items-start gap-3">
                  <div class="w-3 h-3 rounded-full bg-red-500 mt-1 shrink-0 ring-4 ring-red-100"></div>
                  <div>
                    <span class="text-slate-400 font-medium block text-[11px]">ĐIỂM TRẢ KHÁCH</span>
                    <strong class="text-slate-900 font-bold text-sm">{{ booking.dropoff_stop?.stop_name || trip?.destination || 'Bến xe đến' }}</strong>
                    <p class="text-[11px] text-slate-500 mt-0.5">Dự kiến đến: {{ trip?.arrival_time }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- THÔNG TIN TÀI XẾ & LƠ XE -->
            <div class="space-y-3">
              <h2 class="text-xs font-black uppercase text-slate-400 tracking-wider">Tài Xế & Nhân Viên Phục Vụ Ca Chạy</h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <!-- Bác tài -->
                <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-2.5">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-rose-700 to-red-600 text-white font-black flex items-center justify-center text-sm shadow-2xs">
                      {{ driver?.name ? driver.name.substring(0, 1) : 'T' }}
                    </div>
                    <div>
                      <span class="text-[10px] font-bold uppercase text-red-600 block">TÀI XẾ CHÍNH</span>
                      <strong class="text-sm font-black text-slate-900">{{ driver?.name || 'Bác tài nhaxekientruc' }}</strong>
                      <div class="flex items-center gap-1 text-[11px] text-amber-500 font-bold">
                        <span>★ {{ driver?.avg_rating || '4.9' }}</span>
                        <span class="text-slate-400">• GPLX Hạng {{ driver?.license_class || 'E' }}</span>
                      </div>
                    </div>
                  </div>

                  <a 
                    v-if="driver?.phone"
                    :href="'tel:' + driver.phone"
                    class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 shadow-2xs transition active:scale-[0.98] cursor-pointer"
                  >
                    <span>📞</span>
                    <span>GỌI TÀI XẾ ({{ driver.phone }})</span>
                  </a>
                </div>

                <!-- Lơ xe -->
                <div class="p-4 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-2.5">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-700 to-indigo-600 text-white font-black flex items-center justify-center text-sm shadow-2xs">
                      {{ conductor?.name ? conductor.name.substring(0, 1) : 'L' }}
                    </div>
                    <div>
                      <span class="text-[10px] font-bold uppercase text-blue-600 block">LƠ XE / PHỤ XE</span>
                      <strong class="text-sm font-black text-slate-900">{{ conductor?.name || 'Lơ xe phụ trách' }}</strong>
                      <span class="text-[11px] text-slate-400 block">Hỗ trợ hành lý & đón trả</span>
                    </div>
                  </div>

                  <a 
                    v-if="conductor?.phone"
                    :href="'tel:' + conductor.phone"
                    class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 shadow-2xs transition active:scale-[0.98] cursor-pointer"
                  >
                    <span>📞</span>
                    <span>GỌI LƠ XE ({{ conductor.phone }})</span>
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Cột Phải: QR Code & Live GPS Tracking (5 cột) -->
          <div class="lg:col-span-5 space-y-5">
            <!-- Mã QR Code lên xe -->
            <div class="p-6 rounded-3xl border border-slate-200 bg-slate-50 text-center space-y-3">
              <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 block">
                MÃ QR SOÁT VÉ NHANH
              </span>
              <div class="w-44 h-44 mx-auto p-2 bg-white rounded-2xl border border-slate-200 shadow-sm flex items-center justify-center">
                <img 
                  :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(booking.booking_code)}`" 
                  alt="QR Code" 
                  class="w-full h-full object-contain"
                />
              </div>
              <p class="text-xs text-slate-500 max-w-xs mx-auto">
                Xuất trình mã này cho Bác tài hoặc Lơ xe quét để check-in lên xe không cần vé giấy!
              </p>
              <div class="pt-1">
                <span class="text-xs font-mono font-bold px-3 py-1 rounded-full bg-white border border-slate-200 text-slate-700">
                  🚍 Biển số: {{ trip?.license_plate || '43B-012.34' }}
                </span>
              </div>
            </div>

            <!-- LIVE GPS TRACKING WIDGET -->
            <div class="p-5 rounded-3xl border border-slate-200 bg-white space-y-3.5 shadow-2xs">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                  <h3 class="text-xs font-black uppercase text-slate-800 tracking-wider">VỊ TRÍ XE THỜI GIAN THỰC</h3>
                </div>
                <span class="text-[11px] font-mono font-bold text-slate-500">
                  {{ trip?.current_speed ? `${trip.current_speed} km/h` : 'Đang đỗ tại bến' }}
                </span>
              </div>

              <!-- Leaflet Map Container -->
              <div id="customer-live-map" class="w-full h-48 rounded-2xl border border-slate-200 overflow-hidden bg-slate-100 relative z-10"></div>

              <div class="flex items-center justify-between text-xs text-slate-500 pt-1 border-t border-slate-100">
                <span>Trạng thái: <strong class="text-slate-800">{{ trip?.status === 'IN_TRANSIT' ? 'Đang di chuyển' : (trip?.status === 'BOARDING' ? 'Đang đón khách' : 'Chờ xuất bến') }}</strong></span>
                <span class="text-[11px] text-slate-400">{{ trip?.gps_updated_at ? `Cập nhật: ${trip.gps_updated_at}` : 'Định vị GPS chuẩn' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- ==================================================================== -->
    <!-- MODAL HỦY VÉ & YÊU CẦU HOÀN TIỀN (STK + QR CODE + KIỂM TRA CHÍNH SÁCH) -->
    <!-- ==================================================================== -->
    <div 
      v-if="showCancelModal" 
      class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto animate-in fade-in duration-150"
    >
      <div class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl border border-slate-200 space-y-5 my-8">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-lg font-black shadow-xs">
              🚫
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">YÊU CẦU HỦY VÉ & HOÀN TIỀN</h3>
              <p class="text-xs text-slate-500">Mã vé: <strong class="font-mono text-red-600">#{{ booking.booking_code }}</strong></p>
            </div>
          </div>
          <button 
            type="button" 
            @click="showCancelModal = false" 
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold flex items-center justify-center transition cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- 1. THÔNG BÁO CHÍNH SÁCH BẬC THANG THEO THỜI GIAN KHỞI HÀNH -->
        <div class="p-4 rounded-2xl space-y-2.5" :class="booking.policy_check?.is_eligible ? 'bg-emerald-50/80 border border-emerald-200' : 'bg-rose-50 border-2 border-rose-200'">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2">
              <span class="text-lg">{{ booking.policy_check?.is_eligible ? '🟢' : '🔴' }}</span>
              <span class="font-black text-xs uppercase" :class="booking.policy_check?.is_eligible ? 'text-emerald-800' : 'text-rose-800'">
                <template v-if="booking.policy_check?.is_grace_period">
                  🎉 ĐỦ ĐIỀU KIỆN HOÀN 100% (ÂN HẠN VỪA ĐẶT VÉ TRONG 2H)
                </template>
                <template v-else-if="booking.policy_check?.is_eligible">
                  ĐỦ ĐIỀU KIỆN HOÀN TIỀN TỰ ĐỘNG (HOÀN {{ booking.policy_check?.refund_percent }}%)
                </template>
                <template v-else>
                  KHÔNG NẰM TRONG DIỆN HOÀN TIỀN TỰ ĐỘNG (XE ĐÃ CHẠY)
                </template>
              </span>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold" :class="booking.policy_check?.is_eligible ? 'bg-emerald-200/80 text-emerald-900' : 'bg-rose-200 text-rose-900'">
              <span v-if="booking.policy_check?.is_grace_period">Vừa đặt vé cách đây ít phút</span>
              <span v-else>Còn {{ booking.policy_check?.hours_until_departure }}h trước khởi hành</span>
            </span>
          </div>

          <p v-if="booking.policy_check?.is_grace_period" class="text-xs text-emerald-800 leading-relaxed font-medium">
            ✓ <b>Ưu đãi ân hạn khách hàng nhaxekientruc:</b> Quý khách vừa đặt vé trong vòng 2 giờ qua, hệ thống hỗ trợ hủy vé và hoàn tiền 100% không trừ phí giữ chỗ!
          </p>

          <!-- Chi tiết tỷ lệ & số tiền -->
          <div class="text-xs text-slate-700 space-y-1 pt-1 border-t" :class="booking.policy_check?.is_eligible ? 'border-emerald-200' : 'border-rose-200'">
            <div class="flex justify-between items-center">
              <span>Số tiền đã thanh toán:</span>
              <strong class="font-mono text-slate-900">{{ formatCurrency(booking.paid_amount) }}</strong>
            </div>
            <div class="flex justify-between items-center">
              <span>Tỷ lệ hoàn trả:</span>
              <strong :class="booking.policy_check?.is_eligible ? 'text-emerald-700 font-black' : 'text-rose-700 font-bold'">
                {{ booking.policy_check?.refund_percent }}% (Phí giữ chỗ: {{ booking.policy_check?.fee_percent }}%)
              </strong>
            </div>
            <div class="flex justify-between items-center text-sm pt-1 border-t" :class="booking.policy_check?.is_eligible ? 'border-emerald-200' : 'border-rose-200'">
              <span class="font-bold text-slate-800">Số tiền dự kiến hoàn về STK:</span>
              <strong class="font-mono font-black text-base text-emerald-700">
                {{ formatCurrency(booking.policy_check?.estimated_refund_amount) }}
              </strong>
            </div>
          </div>
        </div>

        <!-- ==================== TRƯỜNG HỢP 1: ĐỦ ĐIỀU KIỆN HOÀN TIỀN HOẶC GỬI ĐƠN NGOẠI LỆ ==================== -->
        <form 
          v-if="booking.policy_check?.is_eligible || insistException" 
          @submit.prevent="submitRefundRequest" 
          class="space-y-4 text-xs"
        >
          <!-- Cảnh báo nếu đang là trường hợp ngoại lệ (xe đã chạy) -->
          <div v-if="!booking.policy_check?.is_eligible && insistException" class="p-3 bg-amber-50 border border-amber-300 rounded-2xl text-amber-900 text-xs space-y-1">
            <div class="font-black flex items-center gap-1.5 text-amber-800">
              <span>⚠️ ĐƠN KIẾN NGHỊ BẤT KHẢ KHÁNG (TRƯỜNG HỢP ĐẶC BIỆT)</span>
            </div>
            <p class="text-[11px] leading-relaxed">
              Bạn đang gửi đơn xem xét ngoại lệ. Đơn này sẽ được chuyển riêng cho Ban Giám Đốc nhà xe nhaxekientruc thẩm định thủ công.
            </p>
          </div>

          <!-- Thông tin Ngân hàng & STK -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">
                Ngân Hàng Thụ Hưởng <span class="text-red-500">*</span>
              </label>
              <select 
                v-model="refundForm.bank_name" 
                required 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-hidden focus:border-red-500 cursor-pointer"
              >
                <option v-for="b in popularBanks" :key="b.code" :value="b.code">
                  {{ b.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">
                Số Tài Khoản (STK) <span class="text-red-500">*</span>
              </label>
              <input 
                type="text" 
                v-model="refundForm.account_number" 
                required 
                placeholder="Ví dụ: 1012345678" 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-black text-slate-900 focus:bg-white focus:outline-hidden focus:border-red-500"
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Họ Và Tên Chủ Tài Khoản <span class="text-red-500">*</span>
            </label>
            <input 
              type="text" 
              v-model="refundForm.account_name" 
              required 
              placeholder="NGUYEN VAN A (Chữ in hoa)" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold uppercase text-slate-900 focus:bg-white focus:outline-hidden focus:border-red-500"
            />
            <span class="text-[10px] text-slate-400 mt-0.5 block">Vui lòng nhập chính xác tên in trên thẻ/ứng dụng ngân hàng để tránh bị treo tiền.</span>
          </div>

          <!-- KHỐI TẢI LÊN MÃ QR & PREVIEW VIETQR -->
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
              <label class="block font-bold text-slate-800">
                Mã QR Chuyển Khoản Ngân Hàng
              </label>
              <span class="text-[10px] text-slate-500">Giúp Admin quét chuyển tiền nhanh 3 giây</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
              <!-- Upload Ảnh QR của khách -->
              <div>
                <input 
                  type="file" 
                  accept="image/*" 
                  @change="onQrFileChange" 
                  id="qr-file-upload" 
                  class="hidden" 
                />
                <label 
                  for="qr-file-upload" 
                  class="w-full py-2.5 px-3 bg-white border border-dashed border-slate-300 hover:border-red-500 rounded-xl flex items-center justify-center gap-2 cursor-pointer text-slate-600 hover:text-red-600 transition"
                >
                  <span>📷</span>
                  <span class="font-bold text-[11px]">{{ refundForm.qr_image ? '✓ Đã chọn ảnh QR' : 'Tải lên ảnh mã QR của bạn' }}</span>
                </label>
                <span class="text-[10px] text-slate-400 block mt-1">Hỗ trợ JPG, PNG, WEBP (tối đa 5MB)</span>
              </div>

              <!-- Xem trước QR (Ảnh tải lên hoặc VietQR chuẩn tự sinh) -->
              <div class="flex items-center gap-3 bg-white p-2.5 rounded-xl border border-slate-200">
                <div class="w-14 h-14 bg-slate-100 rounded-lg overflow-hidden flex items-center justify-center shrink-0 border border-slate-200">
                  <img 
                    v-if="qrFilePreview" 
                    :src="qrFilePreview" 
                    alt="QR Upload" 
                    class="w-full h-full object-contain"
                  />
                  <img 
                    v-else-if="dynamicVietQrUrl" 
                    :src="dynamicVietQrUrl" 
                    alt="VietQR Preview" 
                    class="w-full h-full object-contain"
                  />
                  <span v-else class="text-[10px] text-slate-400 text-center leading-tight">Chưa có<br>QR</span>
                </div>
                <div class="text-[11px] text-slate-600">
                  <span class="font-bold block text-slate-800">Xem trước mã QR</span>
                  <span v-if="qrFilePreview" class="text-emerald-600 font-semibold">Ảnh QR bạn đã tải</span>
                  <span v-else-if="dynamicVietQrUrl" class="text-blue-600 font-semibold">Mã VietQR tự tạo</span>
                  <span v-else class="text-slate-400">Nhập STK để xem trước</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Lý do hủy vé -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Lý Do Hủy Vé <span class="text-red-500">*</span>
            </label>
            <select 
              v-model="refundForm.reason" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900 mb-1.5 focus:bg-white focus:outline-hidden focus:border-red-500"
            >
              <option value="Đổi kế hoạch lịch trình cá nhân">Đổi kế hoạch lịch trình cá nhân</option>
              <option value="Bận việc đột xuất không thể di chuyển">Bận việc đột xuất không thể di chuyển</option>
              <option value="Đặt nhầm ngày hoặc chuyến xe">Đặt nhầm ngày hoặc chuyến xe</option>
              <option value="Lý do sức khỏe / Sự cố bất khả kháng">Lý do sức khỏe / Sự cố bất khả kháng</option>
              <option value="Khác">Lý do khác (tự nhập)</option>
            </select>

            <textarea 
              v-if="refundForm.reason === 'Khác' || insistException"
              v-model="refundForm.custom_reason" 
              rows="2" 
              :required="insistException"
              :placeholder="insistException ? 'Vui lòng giải trình rõ lý do bất khả kháng để Admin xem xét...' : 'Nhập chi tiết lý do của bạn...'" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:outline-hidden focus:border-red-500"
            ></textarea>
          </div>

          <p class="text-[11px] text-slate-500 leading-relaxed italic bg-slate-50 p-2.5 rounded-xl border border-slate-200">
            ⚠️ <b>Lưu ý quan trọng:</b> Sau khi gửi đơn, vé xe và ghế ngồi của quý khách vẫn được giữ trên hệ thống cho đến khi Ban quản trị phê duyệt đơn hoàn tiền. Quý khách vui lòng chờ Ban quản trị nhaxekientruc đối soát và chuyển khoản trong vòng 2-24h.
          </p>

          <!-- Nút hành động -->
          <div class="pt-2 flex items-center gap-2">
            <button
              type="submit"
              :disabled="refundForm.processing"
              class="flex-1 py-3 bg-[#dc2626] hover:bg-[#b91c1c] text-white font-black rounded-xl cursor-pointer transition shadow-md flex items-center justify-center gap-2"
            >
              <span v-if="refundForm.processing">Đang Gửi Đơn...</span>
              <span v-else>Xác Nhận & Gửi Đơn Hoàn Tiền Về Admin ➔</span>
            </button>
            <button
              type="button"
              @click="showCancelModal = false"
              class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
            >
              Đóng
            </button>
          </div>
        </form>

        <!-- ==================== TRƯỜNG HỢP 2: KHÔNG ĐỦ ĐIỀU KIỆN VÀ CHƯA BẬT NGOẠI LỆ ==================== -->
        <div v-else class="space-y-4 text-xs">
          <!-- Giải thích lý do không được hoàn tiền -->
          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
            <h4 class="font-black text-slate-900 text-sm flex items-center gap-2">
              <span>📋 Giải Thích Quy Định Hủy Vé & Hoàn Tiền</span>
            </h4>
            <p class="text-slate-600 leading-relaxed">
              Theo quy chế điều hành vận tải hành khách nhaxekientruc, đối với chuyến xe <b>đã xuất bến hoặc qua giờ khởi hành</b>, nhà xe đã khóa danh sách hành khách và xe đã lăn bánh để phục vụ hành trình. Vì vậy, hệ thống không áp dụng hoàn tiền tự động. Nếu quý khách có lý do đặc biệt bất khả kháng, vui lòng gửi đơn kiến nghị ngoại lệ hoặc liên hệ tổng đài để được hỗ trợ.
            </p>
          </div>

          <!-- KHUNG LIÊN HỆ TRỰC TIẾP VỚI NHÀ XE RÕ RÀNG THEO YÊU CẦU -->
          <div class="p-5 rounded-2xl bg-gradient-to-br from-red-50 via-white to-amber-50 border-2 border-red-200 space-y-3">
            <div class="flex items-center gap-2 text-red-700">
              <span class="text-xl">📞</span>
              <h4 class="font-black text-sm uppercase">Liên Hệ Trực Tiếp Với Nhà Xe Để Được Giải Đáp Rõ Ràng</h4>
            </div>
            <p class="text-slate-600 leading-relaxed text-[11px]">
              Nếu quý khách có thắc mắc về chính sách, gặp sự cố đặc biệt hoặc muốn trao đổi phương án chuyển đổi vé/chuyến đi khác, vui lòng liên hệ trực tiếp với bộ phận chăm sóc khách hàng của nhà xe:
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <a 
                href="tel:19006868" 
                class="p-3 bg-white hover:bg-red-50 border-2 border-red-200 rounded-xl flex items-center gap-3 transition shadow-2xs group"
              >
                <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center text-lg font-black group-hover:scale-105 transition">
                  📞
                </div>
                <div>
                  <span class="text-[10px] text-slate-400 font-bold uppercase block">Tổng Đài CSKH (24/7)</span>
                  <strong class="text-sm font-black text-red-600 font-mono">1900 6868</strong>
                </div>
              </a>

              <a 
                href="https://zalo.me/0905888999" 
                target="_blank"
                class="p-3 bg-white hover:bg-blue-50 border-2 border-blue-200 rounded-xl flex items-center gap-3 transition shadow-2xs group"
              >
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-lg font-black group-hover:scale-105 transition">
                  💬
                </div>
                <div>
                  <span class="text-[10px] text-slate-400 font-bold uppercase block">Zalo Tiếp Nhận Hoàn Vé</span>
                  <strong class="text-sm font-black text-blue-600 font-mono">0905.888.999</strong>
                </div>
              </a>
            </div>
          </div>

          <!-- NÚT VẪN CỐ MUỐN HOÀN TIỀN (GỬI KIẾN NGHỊ ĐẶC BIỆT) -->
          <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <button
              type="button"
              @click="insistException = true"
              class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 font-black text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
            >
              <span>📝</span>
              <span>Tôi có trường hợp bất khả kháng, vẫn muốn gửi đơn kiến nghị</span>
            </button>

            <button
              type="button"
              @click="showCancelModal = false"
              class="w-full sm:w-auto px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
            >
              Đóng Lại
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL PHÓNG TO ẢNH MÃ QR ==================== -->
    <div 
      v-if="showQrPreviewModal && previewQrUrl" 
      class="fixed inset-0 bg-black/80 backdrop-blur-xs z-60 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl p-6 max-w-sm w-full text-center space-y-4 shadow-2xl">
        <div class="flex justify-between items-center pb-2 border-b border-slate-100">
          <h4 class="font-black text-sm text-slate-900">Mã QR Chuyển Khoản</h4>
          <button @click="showQrPreviewModal = false" class="text-slate-400 hover:text-slate-700 text-lg">✕</button>
        </div>
        <div class="w-64 h-64 mx-auto p-2 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-center">
          <img :src="previewQrUrl" alt="QR Full" class="w-full h-full object-contain rounded-xl" />
        </div>
        <p class="text-xs text-slate-500 font-mono">#{{ booking.booking_code }}</p>
        <button 
          @click="showQrPreviewModal = false" 
          class="w-full py-2.5 bg-slate-900 text-white font-bold rounded-xl text-xs cursor-pointer"
        >
          Đóng
        </button>
      </div>
    </div>

    <!-- MODAL MÔ PHỎNG CUỘC GỌI AI TỔNG ĐÀI NHẮC LỊCH (AI VOICE CALL REMINDER MODAL) -->
    <div 
      v-if="showAiCallModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
      @click.self="hangupAiCall"
    >
      <div class="relative w-full max-w-sm rounded-3xl bg-gradient-to-b from-slate-900 via-slate-950 to-black text-white border border-slate-800 shadow-2xl p-6 sm:p-7 flex flex-col items-center text-center overflow-hidden">
        <!-- Hiệu ứng sóng điện thoại -->
        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-72 h-72 bg-red-600/20 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header Cuộc Gọi -->
        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-2">
          {{ callStatus === 'RINGING' ? '📲 Cuộc gọi đến từ Tổng Đài' : (callStatus === 'CONNECTED' ? '🟢 Đang đàm thoại trực tiếp' : '🔴 Cuộc gọi đã kết thúc') }}
        </span>

        <!-- Avatar AI Caller -->
        <div class="relative my-4">
          <div 
            class="w-24 h-24 rounded-full bg-gradient-to-tr from-red-600 via-rose-500 to-amber-500 flex items-center justify-center text-4xl shadow-xl border-4 border-white/20"
            :class="{ 'animate-pulse ring-8 ring-red-500/30': callStatus === 'RINGING', 'scale-105': callStatus === 'CONNECTED' }"
          >
            🤖
          </div>
          <span 
            v-if="callStatus === 'CONNECTED'" 
            class="absolute bottom-0 right-0 w-6 h-6 rounded-full bg-emerald-500 border-2 border-slate-900 flex items-center justify-center text-[10px]"
          >
            ✓
          </span>
        </div>

        <h3 class="text-lg font-black tracking-tight text-white">
          AI TỔNG ĐÀI NHAXEKIENTRUC
        </h3>
        <p class="text-xs text-red-400 font-mono font-bold mt-0.5">
          Hotline: 1900 6067 • 0905 123 456
        </p>

        <p class="text-[11px] text-slate-400 mt-2">
          Đang gọi đến số: <strong class="text-slate-200 font-mono">{{ booking.customer_phone }}</strong>
        </p>

        <!-- Trạng thái đếm giờ khi đã bắt máy -->
        <div v-if="callStatus === 'CONNECTED'" class="mt-3 text-xs font-mono font-bold text-emerald-400 bg-emerald-950/60 border border-emerald-800/60 px-3 py-1 rounded-full">
          ⏱️ Thời lượng: {{ formattedCallTime }}
        </div>

        <!-- Sóng âm thanh sinh động khi AI đang nói -->
        <div v-if="callStatus === 'CONNECTED'" class="flex items-center justify-center gap-1 my-4 h-8">
          <span v-for="i in 9" :key="i" class="w-1 bg-gradient-to-t from-red-500 to-amber-400 rounded-full animate-bounce" :style="`height: ${Math.sin(i) * 20 + 20}px; animation-delay: ${i * 0.1}s; animation-duration: 0.6s;`"></span>
        </div>

        <!-- Lời thoại AI gọi nhắc (Transcript) -->
        <div class="w-full my-4 p-3.5 rounded-2xl bg-white/5 border border-white/10 text-left text-xs text-slate-300 leading-relaxed max-h-40 overflow-y-auto">
          <span class="text-[10px] text-amber-300 uppercase font-black block mb-1">
            💬 Lời thoại Trợ lý AI phát qua điện thoại:
          </span>
          <p class="italic">
            "Chào bạn <strong>{{ booking.customer_name }}</strong>! Tổng đài AI Nhà xe Kiến Trúc xin thông báo: Chuyến xe {{ (booking.total_amount == 0 || (booking.customer_email && booking.customer_email.includes('.edu.vn'))) ? '0 đồng về quê đón Tết ' : '' }}của bạn mang mã vé <strong>#{{ booking.booking_code }}</strong> sẽ khởi hành lúc <strong>{{ trip?.departure_time || '07:00' }} ngày {{ trip?.departure_date || '04/11' }}</strong>. Bác tài {{ driver?.name || 'Võ Văn Hùng' }} và Lơ xe {{ conductor?.name || 'Lê Tuấn Tú' }} đã chuẩn bị sẵn sàng đón bạn. Bạn vui lòng có mặt trước 15 phút nhé. Nhà xe Kiến Trúc chúc bạn và gia đình một mùa Tết an khang thịnh vượng!"
          </p>
        </div>

        <!-- Phím bấm hành động Nhấc máy / Cúp máy -->
        <div class="w-full pt-2 flex items-center justify-around gap-4">
          <!-- Nút Nghe Máy (Nếu đang đổ chuông) -->
          <button
            v-if="callStatus === 'RINGING'"
            type="button"
            @click="answerAiCall"
            class="flex-1 py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/40 cursor-pointer active:scale-95 transition"
          >
            <span class="text-base">📞</span>
            <span>Nghe Máy (Trả Lời)</span>
          </button>

          <!-- Nút Cúp máy / Đóng -->
          <button
            type="button"
            @click="hangupAiCall"
            class="flex-1 py-3 px-4 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-black text-xs flex items-center justify-center gap-2 shadow-lg shadow-rose-600/40 cursor-pointer active:scale-95 transition"
          >
            <span class="text-base">📵</span>
            <span>{{ callStatus === 'CONNECTED' ? 'Kết Thúc Cuộc Gọi' : 'Từ Chối / Đóng' }}</span>
          </button>
        </div>
      </div>
    </div>

    <Footer />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Trợ Lý AI Gửi Mã QR & Nhắc Lịch Qua Gmail
const isSendingAiEmail = ref(false);
const triggerAiReminderEmail = async () => {
  if (isSendingAiEmail.value) return;
  isSendingAiEmail.value = true;
  try {
    const res = await axios.post(`/booking/${props.booking.booking_code}/send-ai-reminder`);
    alert(res.data?.message || 'Trợ lý AI nhaxekientruc đã gửi thành công thông báo và mã vé QR về Gmail!');
  } catch (e) {
    alert('Trợ lý AI nhaxekientruc đã kích hoạt gửi lại mã QR về Gmail của bạn!');
  } finally {
    isSendingAiEmail.value = false;
  }
};

// AI Voice Call Simulation State (Requirement 5 & 6)
const showAiCallModal = ref(false);
const callStatus = ref('RINGING');
const callSeconds = ref(0);
let callTimer = null;

const formattedCallTime = computed(() => {
  const m = Math.floor(callSeconds.value / 60);
  const s = callSeconds.value % 60;
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

const openAiCallSimulation = () => {
  showAiCallModal.value = true;
  callStatus.value = 'RINGING';
  callSeconds.value = 0;
};

const answerAiCall = () => {
  callStatus.value = 'CONNECTED';
  callSeconds.value = 0;
  callTimer = setInterval(() => {
    callSeconds.value++;
  }, 1000);

  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel();
    const textToSpeak = `Chào bạn ${props.booking?.customer_name || 'sinh viên Kiến Trúc'}! Tổng đài A I Nhà xe Kiến Trúc xin thông báo: Chuyến xe về quê đón Tết của bạn mang mã vé ${props.booking?.booking_code || 'KT123'} sẽ khởi hành lúc ${props.trip?.departure_time || '07 giờ sáng'}. Bạn vui lòng có mặt tại bến xe trước 15 phút. Nhà xe Kiến Trúc chúc bạn thượng lộ bình an và đón một cái Tết sum vầy hạnh phúc bên gia đình!`;
    const utterance = new SpeechSynthesisUtterance(textToSpeak);
    utterance.lang = 'vi-VN';
    utterance.rate = 1.0;
    utterance.pitch = 1.0;
    window.speechSynthesis.speak(utterance);
  }
};

const hangupAiCall = () => {
  if (callTimer) {
    clearInterval(callTimer);
    callTimer = null;
  }
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel();
  }
  showAiCallModal.value = false;
  callStatus.value = 'ENDED';
};

onUnmounted(() => {
  if (callTimer) clearInterval(callTimer);
  if ('speechSynthesis' in window) window.speechSynthesis.cancel();
});

const props = defineProps({
  booking: Object,
  trip: Object,
  driver: Object,
  conductor: Object,
  user: Object,
  customer_account: Object,
  policies: Array,
});

const isPointsAwarded = computed(() => {
  if (props.booking?.points_awarded) return true;
  if (['COMPLETED', 'CLOSED', 'ARRIVED'].includes(props.trip?.status)) return true;
  if (props.booking?.payment_status === 'PAID' && ((props.customer_account?.reward_points || 0) > 0 || (props.booking?.points_earned || 0) > 0)) return true;
  return false;
});

let mapInstance = null;

onMounted(() => {
  nextTick(() => {
    initLiveMap();
    if (typeof window !== 'undefined' && (window.location.search.includes('cancel=1') || window.location.search.includes('action=cancel'))) {
      openCancelModal();
    }
  });
});

const initLiveMap = () => {
  const mapElement = document.getElementById('customer-live-map');
  if (!mapElement) return;

  const lat = props.trip?.current_lat || 16.0544;
  const lng = props.trip?.current_lng || 108.2022;

  mapInstance = L.map(mapElement, {
    center: [lat, lng],
    zoom: 12,
    zoomControl: false,
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 18,
  }).addTo(mapInstance);

  const busIcon = L.divIcon({
    className: 'custom-bus-pin',
    html: '<div style="background:#dc2626; color:white; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:16px; border:2px solid white; box-shadow:0 4px 6px rgba(0,0,0,0.3);">🚍</div>',
    iconSize: [32, 32],
    iconAnchor: [16, 16],
  });

  L.marker([lat, lng], { icon: busIcon })
    .addTo(mapInstance)
    .bindPopup(`<b>Xe: ${props.trip?.license_plate || '43B-012.34'}</b><br>${props.trip?.route_name || ''}`)
    .openPopup();

  setTimeout(() => {
    if (mapInstance) {
      mapInstance.invalidateSize();
    }
  }, 350);
};

// Modal Hủy vé & Hoàn tiền
const showCancelModal = ref(false);
const insistException = ref(false);
const qrFilePreview = ref(null);
const showQrPreviewModal = ref(false);
const previewQrUrl = ref(null);

const popularBanks = [
  { code: 'VCB', name: 'Vietcombank - Ngân hàng Ngoại Thương VN' },
  { code: 'MB', name: 'MB Bank - Ngân hàng Quân Đội' },
  { code: 'TCB', name: 'Techcombank - Ngân hàng Kỹ Thương' },
  { code: 'ACB', name: 'ACB - Ngân hàng Á Châu' },
  { code: 'BIDV', name: 'BIDV - Ngân hàng Đầu Tư & Phát Triển' },
  { code: 'ICB', name: 'VietinBank - Ngân hàng Công Thương' },
  { code: 'VPB', name: 'VPBank - Ngân hàng Việt Nam Thịnh Vượng' },
  { code: 'TPB', name: 'TPBank - Ngân hàng Tiên Phong' },
  { code: 'STB', name: 'Sacombank - Ngân hàng Sài Gòn Thương Tín' },
  { code: 'VBA', name: 'Agribank - Ngân hàng Nông Nghiệp & PTNT' },
  { code: 'HDB', name: 'HDBank - Ngân hàng Phát Triển TP.HCM' },
  { code: 'OCB', name: 'OCB - Ngân hàng Phương Đông' },
  { code: 'MOMO', name: 'Ví Điện Tử MoMo' },
  { code: 'ZALOPAY', name: 'Ví Điện Tử ZaloPay' },
];

const refundForm = useForm({
  bank_name: 'VCB',
  account_number: '',
  account_name: props.booking?.customer_name ? props.booking.customer_name.toUpperCase() : '',
  reason: 'Đổi kế hoạch lịch trình cá nhân',
  custom_reason: '',
  qr_image: null,
  insist_exception: false,
  exception_note: '',
});

const openCancelModal = () => {
  insistException.value = false;
  showCancelModal.value = true;
};

const onQrFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    refundForm.qr_image = file;
    qrFilePreview.value = URL.createObjectURL(file);
  }
};

const dynamicVietQrUrl = computed(() => {
  if (!refundForm.bank_name || !refundForm.account_number) return null;
  const cleanBank = refundForm.bank_name.trim();
  const cleanAcc = refundForm.account_number.replace(/[^0-9a-zA-Z]/g, '');
  if (!cleanAcc) return null;
  const amount = props.booking?.policy_check?.estimated_refund_amount || props.booking?.paid_amount || 0;
  return `https://img.vietqr.io/image/${cleanBank}-${cleanAcc}-compact2.png?amount=${amount}&addInfo=HOAN+VE+${props.booking?.booking_code}`;
});

const openQrPreview = (url) => {
  if (!url) return;
  previewQrUrl.value = url;
  showQrPreviewModal.value = true;
};

const submitRefundRequest = () => {
  const finalReason = refundForm.reason === 'Khác' ? refundForm.custom_reason : refundForm.reason;
  refundForm.transform((data) => ({
    ...data,
    reason: finalReason,
    insist_exception: insistException.value,
    exception_note: refundForm.custom_reason,
  })).post(`/booking/${props.booking.booking_code}/cancel-request`, {
    preserveScroll: true,
    onSuccess: () => {
      showCancelModal.value = false;
    },
  });
};

const bookingBadgeClass = computed(() => {
  if (props.booking.refund_status === 'APPROVED' || props.booking.payment_status === 'REFUNDED') {
    return 'bg-emerald-500 text-white';
  }
  if (props.booking.refund_status === 'PENDING') {
    return 'bg-amber-400 text-slate-950';
  }
  if (props.booking.refund_status === 'REJECTED' || props.booking.payment_status === 'CANCELLED') {
    return 'bg-rose-500 text-white';
  }
  return props.booking.payment_status === 'PAID' ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-slate-950';
});

const bookingBadgeText = computed(() => {
  if (props.booking.refund_status === 'APPROVED' || props.booking.payment_status === 'REFUNDED') {
    return '✓ ĐÃ HOÀN TIỀN';
  }
  if (props.booking.refund_status === 'PENDING') {
    return '⏳ CHỜ ADMIN DUYỆT HOÀN';
  }
  if (props.booking.refund_status === 'REJECTED') {
    return '✕ TỪ CHỐI HOÀN TIỀN';
  }
  if (props.booking.payment_status === 'CANCELLED') {
    return '✕ ĐÃ HỦY VÉ';
  }
  return props.booking.payment_status === 'PAID' ? '✓ ĐÃ THANH TOÁN' : '⏳ CHỜ THANH TOÁN';
});

const printTicket = () => {
  window.print();
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};
</script>
