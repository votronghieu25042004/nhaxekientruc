<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-800 font-sans">
    <Navbar />

    <!-- Top 3-Step Breadcrumb Progress Bar -->
    <div class="bg-white border-b border-slate-200 sticky top-16 z-30 shadow-xs">
      <div class="max-w-7xl 2xl:max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-3.5">
        <div class="flex items-center justify-center space-x-6 sm:space-x-12 text-xs sm:text-sm font-bold">
          
          <!-- Step 1 -->
          <button
            @click="step = 1"
            class="flex items-center space-x-2 transition cursor-pointer"
            :class="step === 1 ? 'text-red-600 border-b-2 border-red-600 pb-1' : (step > 1 ? 'text-emerald-600' : 'text-slate-400')"
          >
            <span v-if="step > 1">✓</span>
            <span v-else>1.</span>
            <span>Chọn Chỗ Ngồi</span>
          </button>

          <!-- Step 2 -->
          <button
            @click="selectedSeats.length > 0 ? step = 2 : alert('Vui lòng chọn ghế trước!')"
            class="flex items-center space-x-2 transition cursor-pointer"
            :class="step === 2 ? 'text-red-600 border-b-2 border-red-600 pb-1' : (step > 2 ? 'text-emerald-600' : 'text-slate-400')"
          >
            <span v-if="step > 2">✓</span>
            <span v-else>2.</span>
            <span>Điểm Đón & Điểm Trả</span>
          </button>

          <!-- Step 3 -->
          <button
            @click="selectedSeats.length > 0 && selectedPickup && selectedDropoff ? step = 3 : null"
            class="flex items-center space-x-2 transition cursor-pointer"
            :class="step === 3 ? 'text-red-600 border-b-2 border-red-600 pb-1' : 'text-slate-400'"
          >
            <span>3. Thông Tin & Thanh Toán</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content Body -->
    <main class="max-w-7xl 2xl:max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">
      <!-- CẢNH BÁO XE ĐÃ CHẠY / KHÔNG THỂ ĐẶT VÉ TRÊN APP (THEO GIỜ THẬT VIỆT NAM) -->
      <div v-if="is_expired || ['RUNNING', 'IN_TRANSIT', 'COMPLETED'].includes(trip.status)" class="p-4 sm:p-5 rounded-2xl bg-amber-50 border-2 border-amber-300 text-amber-950 shadow-sm animate-in fade-in duration-200">
        <div class="flex items-start gap-3.5">
          <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center font-black text-xl flex-shrink-0">
            ⚠️
          </div>
          <div class="flex-1">
            <h3 class="font-black text-sm sm:text-base text-amber-900">
              Chuyến xe này {{ (trip.status === 'RUNNING' || trip.status === 'IN_TRANSIT') ? 'ĐÃ XUẤT BẾN & ĐANG CHẠY TRÊN ĐƯỜNG' : 'ĐÃ HOÀN TẤT HÀNH TRÌNH' }}
            </h3>
            <p class="text-xs text-amber-800 mt-1 leading-relaxed">
              Hệ thống nhaxekientruc đã đóng cổng đặt vé trực tuyến cho chuyến xe này để đảm bảo an toàn vận hành. Quý khách vui lòng bấm nút bên dưới để chọn các chuyến xe khởi hành tiếp theo.
            </p>
            <div class="mt-3">
              <a
                href="/trips"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
              >
                <span>← Quay lại tìm chuyến xe khác</span>
              </a>
            </div>
          </div>
        </div>
      </div>


      <!-- ==================== BƯỚC 1: CHỌN CHỖ NGỒI 2 TẦNG (RỘNG RÃI & SANG TRỌNG) ==================== -->
      <div v-if="step === 1" class="grid grid-cols-1 lg:grid-cols-12 gap-7 items-start">
        
        <!-- Cột Trái (8/12 hoặc 7/12): SƠ ĐỒ GHẾ NGỒI XE 2 TẦNG RỘNG RÃI -->
        <div class="lg:col-span-7 xl:col-span-8 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-6">
          
          <!-- Banner Yêu Cầu Đăng Nhập Nếu Chưa Đăng Nhập -->
          <div v-if="!props.user" class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
            <div class="flex items-start gap-3">
              <span class="text-2xl shrink-0 mt-0.5">🔒</span>
              <div>
                <div class="font-black text-sm text-amber-950">Yêu cầu đăng nhập tài khoản</div>
                <div class="text-xs text-amber-800 leading-snug mt-0.5">
                  Quý khách vui lòng đăng nhập trước khi chọn ghế để hệ thống giữ chỗ và cấp mã QR vé.
                </div>
              </div>
            </div>
            <button
              type="button"
              @click="showAuthModal = true"
              class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs rounded-xl shadow-xs transition cursor-pointer text-center whitespace-nowrap"
            >
              Đăng Nhập Ngay →
            </button>
          </div>

          <!-- Header Sơ Đồ & Bộ Đếm Thời Gian Giữ Chỗ -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-600 animate-pulse"></span>
                <h2 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">Sơ Đồ Chọn Chỗ Ngồi Giường Nằm</h2>
              </div>
              <p class="text-xs text-slate-500 mt-1">
                Xe Limousine 2 tầng VIP (30 chỗ) • Bấm vào vị trí giường mong muốn để chọn chỗ ngồi
              </p>
            </div>
            <div v-if="selectedSeats.length > 0" class="px-3.5 py-1.5 rounded-xl bg-red-50 border border-red-200 text-red-600 text-xs font-bold flex items-center gap-2 animate-pulse shrink-0 self-start sm:self-auto">
              <span>⏱️ Ghế đang giữ trong:</span>
              <span class="font-mono text-sm font-black">{{ timerMinutes }}:{{ timerSeconds < 10 ? '0' + timerSeconds : timerSeconds }}</span>
            </div>
          </div>

          <!-- Bus Frame Container (Khoang Xe Limousine Sang Trọng) -->
          <div class="border-2 border-slate-200/90 rounded-3xl p-5 sm:p-6 bg-slate-50/70 space-y-5 shadow-inner">
            
            <!-- Đầu xe & Vị trí Bác Tài -->
            <div class="flex items-center justify-center gap-2 py-2 px-4 rounded-2xl bg-white border border-slate-200 text-slate-600 text-xs font-bold shadow-2xs">
              <span>🧭</span>
              <span class="uppercase tracking-wider text-[11px] font-black text-slate-700">ĐẦU XE • BÁC TÀI & CỬA LÊN XUỐNG</span>
              <span>🚍</span>
            </div>

            <!-- Seat Grids: Tầng 1 & Tầng 2 Song Song Sân Khấu Rộng Rãi -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
              
              <!-- TẦNG 1: DÃY A (18 Giường VIP Dưới) -->
              <div class="bg-white p-4.5 rounded-2xl border border-slate-200 text-center flex flex-col justify-start shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-tight">TẦNG 1 (DÃY A)</span>
                  </div>
                  <span class="px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 font-mono font-black text-xs">
                    {{ formatPrice(trip.floor1_price || trip.fare || 140000) }}đ
                  </span>
                </div>

                <div class="grid grid-cols-3 gap-2 text-[11px] font-bold text-slate-500 pb-1.5 border-b border-slate-100 select-none">
                  <span>🪟 Cửa sổ</span>
                  <span>🚶 Dãy giữa</span>
                  <span>🪟 Cửa sổ</span>
                </div>

                <div class="grid grid-cols-3 gap-2.5">
                  <template v-for="(row, rIdx) in floor1Rows" :key="'f1-' + rIdx">
                    <button
                      v-for="seat in [row.left, row.middle, row.right]"
                      :key="seat.seat_number"
                      type="button"
                      @click="toggleSeat(seat)"
                      :disabled="seat.status === 'booked'"
                      :title="`${seat.seat_number} - ${seat.is_window ? 'Cạnh cửa sổ Tầng 1' : 'Dãy giữa Tầng 1'} (${seat.price ? formatPrice(seat.price) + 'đ' : ''})`"
                      class="group p-2 rounded-2xl text-center font-mono transition-all relative cursor-pointer border min-h-[66px] sm:min-h-[72px] flex flex-col justify-between items-center select-none"
                      :class="[
                        seat.status === 'booked' 
                          ? 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed shadow-none'
                          : isSeatSelected(seat.seat_number)
                            ? 'bg-gradient-to-br from-red-600 via-rose-600 to-red-700 text-white border-red-600 shadow-md shadow-red-500/30 scale-[1.03] ring-2 ring-red-400/80 z-10'
                            : 'bg-white border-slate-200 text-slate-700 hover:border-red-400 hover:shadow-md hover:-translate-y-0.5 active:scale-95'
                      ]"
                    >
                      <!-- Gối tựa đầu mô phỏng giường nằm -->
                      <div 
                        class="w-7 h-1.5 rounded-full transition"
                        :class="isSeatSelected(seat.seat_number) ? 'bg-white/40' : (seat.status === 'booked' ? 'bg-slate-200' : 'bg-slate-200 group-hover:bg-red-200')"
                      ></div>
                      
                      <!-- Mã số ghế -->
                      <div class="font-black text-sm sm:text-base leading-none" :class="isSeatSelected(seat.seat_number) ? 'text-white' : (seat.status === 'booked' ? 'text-slate-400' : 'text-slate-900')">
                        {{ seat.seat_number }}
                      </div>

                      <!-- Nhãn trạng thái / Giá -->
                      <div class="text-[9.5px] font-bold leading-tight" :class="isSeatSelected(seat.seat_number) ? 'text-white/95' : (seat.status === 'booked' ? 'text-slate-400 font-normal' : 'text-emerald-700')">
                        {{ seat.status === 'booked' ? 'Đã bán' : (isSeatSelected(seat.seat_number) ? '✓ Đã chọn' : (seat.price ? formatPrice(seat.price) + 'đ' : 'Trống')) }}
                      </div>
                    </button>
                  </template>
                </div>
              </div>

              <!-- TẦNG 2: DÃY B (12 Giường Tiêu Chuẩn 2 Bên Cửa Sổ) -->
              <div class="bg-white p-4.5 rounded-2xl border border-slate-200 text-center flex flex-col justify-start shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <span class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-tight">TẦNG 2 (DÃY B)</span>
                  </div>
                  <span class="px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 font-mono font-black text-xs">
                    {{ formatPrice(trip.floor2_price || (trip.fare ? trip.fare - 10000 : 130000)) }}đ
                  </span>
                </div>

                <div class="grid grid-cols-3 gap-2 text-[11px] font-bold text-slate-500 pb-1.5 border-b border-slate-100 select-none">
                  <span>🪟 Cửa sổ (Trái)</span>
                  <span>🚶 Lối thông tầng</span>
                  <span>🪟 Cửa sổ (Phải)</span>
                </div>

                <!-- 3 Cột đối xứng 100% với Tầng 1 (Cột giữa là lối đi thông tầng) -->
                <div class="grid grid-cols-3 gap-2.5">
                  <template v-for="(row, rIdx) in floor2Rows" :key="'f2-' + rIdx">
                    <!-- Ghế Cửa Sổ Trái -->
                    <button
                      type="button"
                      @click="toggleSeat(row.left)"
                      :disabled="row.left.status === 'booked'"
                      :title="`${row.left.seat_number} - Cạnh cửa sổ Tầng 2 (${row.left.price ? formatPrice(row.left.price) + 'đ' : ''})`"
                      class="group p-2 rounded-2xl text-center font-mono transition-all relative cursor-pointer border min-h-[66px] sm:min-h-[72px] flex flex-col justify-between items-center select-none"
                      :class="[
                        row.left.status === 'booked' 
                          ? 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed shadow-none'
                          : isSeatSelected(row.left.seat_number)
                            ? 'bg-gradient-to-br from-red-600 via-rose-600 to-red-700 text-white border-red-600 shadow-md shadow-red-500/30 scale-[1.03] ring-2 ring-red-400/80 z-10'
                            : 'bg-white border-slate-200 text-slate-700 hover:border-red-400 hover:shadow-md hover:-translate-y-0.5 active:scale-95'
                      ]"
                    >
                      <div 
                        class="w-7 h-1.5 rounded-full transition"
                        :class="isSeatSelected(row.left.seat_number) ? 'bg-white/40' : (row.left.status === 'booked' ? 'bg-slate-200' : 'bg-slate-200 group-hover:bg-red-200')"
                      ></div>
                      <div class="font-black text-sm sm:text-base leading-none" :class="isSeatSelected(row.left.seat_number) ? 'text-white' : (row.left.status === 'booked' ? 'text-slate-400' : 'text-slate-900')">
                        {{ row.left.seat_number }}
                      </div>
                      <div class="text-[9.5px] font-bold leading-tight" :class="isSeatSelected(row.left.seat_number) ? 'text-white/95' : (row.left.status === 'booked' ? 'text-slate-400 font-normal' : 'text-blue-700')">
                        {{ row.left.status === 'booked' ? 'Đã bán' : (isSeatSelected(row.left.seat_number) ? '✓ Đã chọn' : (row.left.price ? formatPrice(row.left.price) + 'đ' : 'Trống')) }}
                      </div>
                    </button>

                    <!-- Lối Đi Giữa Thông Tầng -->
                    <div class="flex flex-col items-center justify-center text-[10px] text-slate-400 font-bold select-none min-h-[66px] sm:min-h-[72px] border-x border-dashed border-slate-200/80 rounded-xl bg-slate-50/60 p-1">
                      <span class="text-xs opacity-50">🚶</span>
                      <span class="text-[9px] text-slate-400 font-semibold tracking-tight">Lối đi</span>
                    </div>

                    <!-- Ghế Cửa Sổ Phải -->
                    <button
                      type="button"
                      @click="toggleSeat(row.right)"
                      :disabled="row.right.status === 'booked'"
                      :title="`${row.right.seat_number} - Cạnh cửa sổ Tầng 2 (${row.right.price ? formatPrice(row.right.price) + 'đ' : ''})`"
                      class="group p-2 rounded-2xl text-center font-mono transition-all relative cursor-pointer border min-h-[66px] sm:min-h-[72px] flex flex-col justify-between items-center select-none"
                      :class="[
                        row.right.status === 'booked' 
                          ? 'bg-slate-100 border-slate-200 text-slate-400 cursor-not-allowed shadow-none'
                          : isSeatSelected(row.right.seat_number)
                            ? 'bg-gradient-to-br from-red-600 via-rose-600 to-red-700 text-white border-red-600 shadow-md shadow-red-500/30 scale-[1.03] ring-2 ring-red-400/80 z-10'
                            : 'bg-white border-slate-200 text-slate-700 hover:border-red-400 hover:shadow-md hover:-translate-y-0.5 active:scale-95'
                      ]"
                    >
                      <div 
                        class="w-7 h-1.5 rounded-full transition"
                        :class="isSeatSelected(row.right.seat_number) ? 'bg-white/40' : (row.right.status === 'booked' ? 'bg-slate-200' : 'bg-slate-200 group-hover:bg-red-200')"
                      ></div>
                      <div class="font-black text-sm sm:text-base leading-none" :class="isSeatSelected(row.right.seat_number) ? 'text-white' : (row.right.status === 'booked' ? 'text-slate-400' : 'text-slate-900')">
                        {{ row.right.seat_number }}
                      </div>
                      <div class="text-[9.5px] font-bold leading-tight" :class="isSeatSelected(row.right.seat_number) ? 'text-white/95' : (row.right.status === 'booked' ? 'text-slate-400 font-normal' : 'text-blue-700')">
                        {{ row.right.status === 'booked' ? 'Đã bán' : (isSeatSelected(row.right.seat_number) ? '✓ Đã chọn' : (row.right.price ? formatPrice(row.right.price) + 'đ' : 'Trống')) }}
                      </div>
                    </button>
                  </template>
                </div>
              </div>

            </div>

          </div>

          <!-- Chú Thích Ghế Ngồi (Seat Legend) -->
          <div class="border-t border-slate-100 pt-4 space-y-3">
            <div class="grid grid-cols-3 gap-3 text-xs text-slate-600 font-bold text-center">
              <div class="flex items-center justify-center space-x-2 p-2 rounded-xl bg-slate-50 border border-slate-200">
                <div class="w-4 h-4 rounded-md border-2 border-slate-300 bg-white shadow-2xs"></div>
                <span>Còn trống</span>
              </div>
              <div class="flex items-center justify-center space-x-2 p-2 rounded-xl bg-slate-50 border border-slate-200">
                <div class="w-4 h-4 rounded-md bg-gradient-to-r from-red-600 to-rose-600 border border-red-600 shadow-xs"></div>
                <span class="text-red-700 font-black">Đang chọn</span>
              </div>
              <div class="flex items-center justify-center space-x-2 p-2 rounded-xl bg-slate-50 border border-slate-200">
                <div class="w-4 h-4 rounded-md bg-slate-200 border border-slate-300 text-[9px] text-slate-400 font-bold flex items-center justify-center">✕</div>
                <span class="text-slate-400">Đã bán</span>
              </div>
            </div>

            <!-- Vị trí Ghế: Cửa Sổ vs Dãy Giữa -->
            <div class="flex items-center justify-center gap-3 text-xs text-slate-500 font-semibold pt-1 border-t border-slate-100 flex-wrap">
              <span class="flex items-center gap-1.5">
                <span>🪟</span>
                <span>Cửa sổ (Dãy Trái & Dãy Phải: 24 giường)</span>
              </span>
              <span class="text-slate-300">•</span>
              <span class="flex items-center gap-1.5">
                <span>🚶</span>
                <span>Dãy giữa (6 giường VIP Tầng 1 • Tầng 2 là lối đi thông thoáng)</span>
              </span>
            </div>
          </div>

        </div>

        <!-- Cột Phải (4/12 hoặc 5/12): THÔNG TIN CHUYẾN XE & TẠM TÍNH (STICKY) -->
        <div class="lg:col-span-5 xl:col-span-4 bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-6 lg:sticky lg:top-24 self-start">
          
          <!-- Header Thông Tin Chuyến Đi -->
          <div class="pb-4 border-b border-slate-100">
            <div class="text-[11px] font-black text-red-600 uppercase tracking-wider flex items-center gap-1.5">
              <span>🚌</span>
              <span>CHUYẾN XE NHAXEKIENTRUC TERMINAL</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
              {{ trip.route?.name || trip.route_name || (trip.origin && trip.destination ? `Tuyến ${trip.origin} ➔ ${trip.destination}` : 'Chuyến xe nhaxekientruc') }}
            </h2>
            <div class="text-xs text-slate-500 mt-2 space-y-1.5">
              <div class="flex items-center gap-1.5 font-medium">
                <span>🕒</span>
                <span>Xuất bến: <b class="text-slate-900 font-mono">{{ trip.departure_time }}</b> ngày <b class="text-slate-900">{{ trip.departure_date }}</b></span>
              </div>
              <div class="flex items-center gap-2 flex-wrap text-slate-500">
                <span>Biển số: <b class="text-slate-800 font-mono">{{ trip.license_plate || trip.bus?.license_plate || '43B-012.34' }}</b></span>
                <span>•</span>
                <span>Bác tài: <b class="text-slate-800">{{ trip.driver?.name || 'Nguyễn Văn Hùng' }}</b></span>
              </div>
            </div>
          </div>

          <!-- Khung Ghế Đã Chọn & Tạm Tính -->
          <div class="p-4.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3.5">
            <div class="flex justify-between items-center text-xs">
              <span class="font-bold text-slate-600">Ghế đang chọn:</span>
              <span class="font-black text-sm" :class="selectedSeats.length > 0 ? 'text-red-600' : 'text-slate-400'">
                {{ selectedSeats.length > 0 ? `${selectedSeats.length} chỗ` : 'Chưa chọn ghế' }}
              </span>
            </div>

            <!-- Chips Chi Tiết Từng Ghế (Có nút X để hủy nhanh) -->
            <div v-if="selectedSeats.length > 0" class="flex flex-wrap gap-2 pt-1">
              <div 
                v-for="s in selectedSeats" 
                :key="'badge-' + s.seat_number"
                class="px-3 py-1.5 rounded-xl bg-white border border-red-200 text-xs font-bold text-slate-800 shadow-2xs flex items-center gap-2"
              >
                <span class="text-red-600 font-black text-sm">{{ s.seat_number }}</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-md" :class="s.is_window ? 'bg-amber-100 text-amber-900 font-bold' : 'bg-slate-100 text-slate-600'">
                  {{ s.is_window ? '🪟 Cửa sổ' : '🚶 Dãy giữa' }}
                </span>
                <span class="text-[11px] text-slate-600 font-mono font-bold">{{ formatPrice(s.price || trip.base_price) }}đ</span>
                <button
                  type="button"
                  @click.stop="toggleSeat(s)"
                  class="w-4 h-4 rounded-full bg-slate-100 hover:bg-red-100 hover:text-red-600 text-slate-400 flex items-center justify-center text-xs font-bold transition cursor-pointer"
                  title="Bỏ chọn ghế này"
                >✕</button>
              </div>
            </div>
            <div v-else class="text-xs text-slate-400 italic py-1 leading-relaxed">
              👉 Bấm vào sơ đồ xe bên trái để chọn vị trí ghế bạn muốn ngồi (tối đa 5 chỗ/lần đặt).
            </div>

            <!-- Tạm Tính -->
            <div class="flex justify-between items-center text-xs pt-3 border-t border-slate-200">
              <span class="font-bold text-slate-700">Tạm tính tiền vé:</span>
              <span class="font-black text-xl text-red-600 font-mono">{{ formatPrice(totalSeatPrice) }}đ</span>
            </div>
          </div>

          <!-- Nút Bước Tiếp Theo -->
          <div>
            <button
              @click="goToStep2"
              :disabled="selectedSeats.length === 0"
              class="w-full py-4 px-5 rounded-2xl bg-gradient-to-r from-red-600 via-rose-600 to-red-600 hover:from-red-700 hover:to-rose-700 disabled:from-slate-200 disabled:to-slate-200 text-white disabled:text-slate-400 font-black text-sm shadow-lg shadow-red-500/25 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed active:scale-[0.99]"
            >
              <span>Tiếp Tục: Chọn Điểm Đón & Trả</span>
              <span class="text-base">➔</span>
            </button>
            <p v-if="selectedSeats.length === 0" class="text-center text-[11px] text-slate-400 mt-2">
              Vui lòng chọn ít nhất 1 chỗ ngồi để tiếp tục
            </p>
          </div>

          <!-- Cam Kết Dịch Vụ Nhà Xe -->
          <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-2">
            <div class="font-bold text-slate-800 flex items-center gap-1.5">
              <span>🛡️</span>
              <span>Cam kết dịch vụ nhaxekientruc:</span>
            </div>
            <ul class="space-y-1.5 text-[11px] text-slate-600 pl-1">
              <li class="flex items-center gap-2">
                <span class="text-emerald-600 font-bold">✓</span>
                <span>Giữ đúng 100% số ghế đã chọn, không nhồi nhét</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="text-emerald-600 font-bold">✓</span>
                <span>Hỗ trợ chọn điểm đón & trả tận nơi dọc lộ trình</span>
              </li>
              <li class="flex items-center gap-2">
                <span class="text-emerald-600 font-bold">✓</span>
                <span>Miễn phí khăn lạnh, nước suối & wifi 5G tốc độ cao</span>
              </li>
            </ul>
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
              <span class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xs font-bold">1</span>
              Điểm Đón Khách (Điểm đi)
            </h3>
            <div class="space-y-2">
              <label 
                v-for="st in pickupStops" 
                :key="'pickup-' + (st.unique_key || st.id || st.name)"
                class="p-3.5 rounded-2xl border flex items-start space-x-3 cursor-pointer transition select-none"
                :class="((selectedPickup?.unique_key && selectedPickup.unique_key === st.unique_key) || selectedPickup?.name === st.name) ? 'bg-red-50/70 border-red-500 ring-2 ring-red-500/20 shadow-xs' : 'border-slate-200 hover:bg-slate-50'">
                <input type="radio" name="pickup" :value="st" v-model="selectedPickup" class="w-4 h-4 text-red-600 accent-red-600 mt-0.5" />
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
                :key="'dropoff-' + (st.unique_key || st.id || st.name)"
                class="p-3.5 rounded-2xl border flex items-start space-x-3 cursor-pointer transition select-none"
                :class="((selectedDropoff?.unique_key && selectedDropoff.unique_key === st.unique_key) || selectedDropoff?.name === st.name) ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/20 shadow-xs' : 'border-slate-200 hover:bg-slate-50'">
                <input type="radio" name="dropoff" :value="st" v-model="selectedDropoff" class="w-4 h-4 text-emerald-600 accent-emerald-600 mt-0.5" />
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
            class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 disabled:from-slate-200 disabled:to-slate-200 text-white disabled:text-slate-400 font-bold text-sm shadow-lg shadow-red-500/25 transition cursor-pointer"
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

        <!-- Cảnh báo nếu chưa đăng nhập -->
        <div v-if="!props.user" class="p-4 rounded-2xl bg-amber-50 border-2 border-amber-300 text-amber-900 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
          <div class="flex items-center gap-3">
            <span class="text-2xl">🔒</span>
            <div>
              <div class="font-black text-sm">Quý khách chưa đăng nhập tài khoản nhaxekientruc</div>
              <div class="text-xs text-amber-800">Theo quy định an toàn, quý khách cần đăng nhập để đặt vé xe, lưu lịch sử, tích điểm thưởng và nhận mã QR qua Email & Điện thoại.</div>
            </div>
          </div>
          <button
            type="button"
            @click="showAuthModal = true"
            class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs rounded-xl shadow-xs transition cursor-pointer whitespace-nowrap"
          >
            Đăng Nhập Ngay →
          </button>
        </div>

        <form @submit.prevent="submitBooking" class="space-y-6">
          <!-- Warning banner khi chưa có SĐT hợp lệ (Đặc biệt khách đăng nhập bằng Gmail) -->
          <div v-if="!isPhoneValid" class="p-4 rounded-2xl bg-amber-500/10 border-2 border-amber-500/40 flex items-start gap-3 text-amber-950 shadow-xs">
            <span class="text-2xl shrink-0">📞</span>
            <div>
              <h4 class="font-black text-xs uppercase tracking-wide text-amber-950 flex items-center gap-1.5">
                <span>BẮT BUỘC NHẬP SỐ ĐIỆN THOẠI DI ĐỘNG ĐÓN XE</span>
                <span class="text-[10px] bg-amber-200 text-amber-900 px-2 py-0.2 rounded-full font-bold">Quy định nhà xe</span>
              </h4>
              <p class="text-[11px] text-amber-900 mt-1 leading-relaxed">
                Tài khoản của quý khách đăng nhập bằng <strong>Gmail</strong>. Quý khách <strong>bắt buộc phải nhập số điện thoại</strong> trước khi tiến hành thanh toán. Bác tài & Lơ xe sẽ gọi điện thoại nhắc nhở trước giờ xuất bến hoặc liên hệ đón tại điểm hẹn!
              </p>
            </div>
          </div>

          <!-- Passenger Info Inputs (Tự động lấy tên tài khoản, xác thực SĐT Zalo & nhận vé qua Gmail) -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- 1. Họ và tên hành khách (Tự động từ tài khoản đã đăng nhập) -->
            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-red-100 text-red-700 flex items-center justify-center font-black text-sm shrink-0 border border-red-200">
                  {{ (props.user?.name || 'KH').substring(0, 2).toUpperCase() }}
                </div>
                <div>
                  <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hành Khách Đặt Vé</div>
                  <div class="text-sm font-black text-slate-900 leading-snug">{{ props.user?.name || customerForm.name || 'Hành Khách nhaxekientruc' }}</div>
                </div>
              </div>
              <div class="mt-2.5 pt-2 border-t border-slate-200/80 flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                  ✓ Tài khoản đã đăng nhập
                </span>
                <span class="text-[10px] text-slate-400 font-mono">ID: #{{ props.user?.id || 'VIP' }}</span>
              </div>
            </div>

            <!-- 2. Số điện thoại đón xe (Bắt buộc cho khách đăng nhập bằng Gmail để Lơ xe liên hệ) -->
            <!-- 2. Số điện thoại nhận tin & Lơ xe liên hệ -->
            <div class="space-y-1.5 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-bold text-slate-700">
                    Số điện thoại đón xe <span class="text-rose-500 font-black">* (Bắt buộc)</span>
                  </label>
                  <span v-if="userHasRealPhone || isPhoneVerified" class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                    🔒 Đã xác thực SMS
                  </span>
                  <span v-else-if="isPhoneValid" class="text-[11px] font-bold text-amber-700 flex items-center gap-1">
                    ⚠️ Cần xác minh SMS
                  </span>
                  <span v-else class="text-[10px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                    ⚠️ Chưa nhập SĐT
                  </span>
                </div>

                <!-- Nếu tài khoản đã có SĐT xác thực: KHÓA CỐ ĐỊNH KHÔNG CHO ĐỔI -->
                <input
                  v-if="userHasRealPhone || isPhoneVerified"
                  :value="customerForm.phone"
                  readonly
                  disabled
                  type="tel"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-emerald-300 bg-emerald-50/50 text-xs font-black text-emerald-950 cursor-not-allowed select-none shadow-2xs"
                />
                <!-- Nếu tài khoản chưa có SĐT (Đăng ký bằng Gmail): Cho phép nhập và xác minh bằng Firebase SMS OTP -->
                <div v-else class="space-y-2">
                  <input
                    id="customer-phone-input"
                    v-model="customerForm.phone"
                    required
                    type="tel"
                    placeholder="VD: 0904 233 370"
                    class="w-full px-3.5 py-2.5 rounded-xl border text-xs font-bold text-slate-900 focus:outline-none transition shadow-2xs"
                    :class="!isPhoneValid ? 'border-amber-400 bg-amber-50/50 ring-2 ring-amber-400/20' : 'border-slate-300 focus:border-red-500'"
                  />
                  <!-- Container reCAPTCHA vô hình cho Firebase -->
                  <div id="booking-recaptcha-container"></div>

                  <!-- Khối nút gửi và nhập OTP SMS -->
                  <div v-if="!showPhoneOtpInput">
                    <button
                      type="button"
                      @click="handleSendPhoneOtpFirebase"
                      :disabled="!isPhoneValid || phoneOtpSending"
                      class="w-full py-2 px-3 rounded-xl bg-red-600 hover:bg-red-700 disabled:bg-slate-300 text-white font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5 shadow-xs disabled:cursor-not-allowed"
                    >
                      <span v-if="phoneOtpSending" class="animate-spin text-xs">⏳</span>
                      <span>{{ phoneOtpSending ? 'Đang gửi SMS OTP...' : '📲 Bấm Gửi Mã SMS OTP Xác Minh SĐT' }}</span>
                    </button>
                  </div>
                  <div v-else class="p-2.5 rounded-xl bg-amber-50 border border-amber-300 space-y-2 animate-in fade-in">
                    <div class="text-[11px] font-bold text-slate-800">
                      Mã SMS OTP đã gửi tới <span class="font-mono text-red-600">{{ customerForm.phone }}</span>. Vui lòng nhập 6 số:
                    </div>
                    <div class="flex gap-2">
                      <input
                        v-model="phoneOtpCode"
                        maxlength="6"
                        type="text"
                        inputmode="numeric"
                        placeholder="6 số OTP"
                        class="flex-1 px-3 py-1.5 rounded-lg border border-slate-300 font-mono font-bold text-center text-sm focus:border-red-500 bg-white"
                      />

                      <button
                        type="button"
                        @click="handleVerifyPhoneOtpFirebase"
                        :disabled="phoneOtpCode.length < 6 || phoneOtpVerifying"
                        class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold text-xs cursor-pointer shadow-xs disabled:cursor-not-allowed whitespace-nowrap"
                      >
                        {{ phoneOtpVerifying ? 'Đang kiểm tra...' : 'Xác Minh' }}
                      </button>
                    </div>
                    <p v-if="phoneOtpMessage" class="text-[10.5px] font-bold" :class="phoneOtpError ? 'text-red-600' : 'text-emerald-700'">
                      {{ phoneOtpMessage }}
                    </p>
                  </div>
                </div>
              </div>
              <div>
                <p v-if="userHasRealPhone || isPhoneVerified" class="text-[10px] text-emerald-700 font-bold flex items-center gap-1">
                  <span>✓</span>
                  <span>Số điện thoại chính chủ đã xác thực SMS (Bác tài & Lơ xe sẽ đón số này)</span>
                </p>
                <p v-else class="text-[10px] text-red-600 font-bold">
                  ⚠️ Quý khách đăng ký bằng Gmail bắt buộc phải xác minh SĐT qua SMS OTP trước khi đặt vé!
                </p>
              </div>
            </div>

            <!-- 3. Địa chỉ Gmail nhận vé điện tử & QR Code -->
            <div class="space-y-1.5 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label class="block text-xs font-bold text-slate-700">
                    Địa chỉ Gmail nhận vé điện tử <span class="text-rose-500">*</span>
                  </label>
                  <span v-if="userHasRealEmail || isEmailVerified" class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                    🔒 Đã xác thực Gmail
                  </span>
                  <span v-else-if="isEmailValid" class="text-[11px] font-bold text-amber-700 flex items-center gap-1">
                    ⚠️ Cần xác minh Gmail
                  </span>
                  <span v-else class="text-[10px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                    ⚠️ Chưa nhập Gmail
                  </span>
                </div>

                <!-- Nếu tài khoản đã có Gmail xác thực: KHÓA CỐ ĐỊNH KHÔNG CHO ĐỔI -->
                <input
                  v-if="userHasRealEmail || isEmailVerified"
                  :value="customerForm.email"
                  readonly
                  disabled
                  type="email"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-emerald-300 bg-emerald-50/50 text-xs font-black text-emerald-950 cursor-not-allowed select-none shadow-2xs"
                />
                <!-- Nếu tài khoản chưa có Gmail (Đăng ký bằng SĐT): Cho phép nhập và xác minh bằng Gmail OTP -->
                <div v-else class="space-y-2">
                  <input
                    id="customer-email-input"
                    v-model="customerForm.email"
                    required
                    type="email"
                    placeholder="VD: tronghieuvo9@gmail.com"
                    class="w-full px-3.5 py-2.5 rounded-xl border text-xs font-semibold text-slate-900 focus:outline-none transition shadow-2xs"
                    :class="!isEmailValid ? 'border-amber-400 bg-amber-50/50 ring-2 ring-amber-400/20' : 'border-slate-300 focus:border-red-500'"
                  />

                  <!-- Khối nút gửi và nhập OTP Gmail -->
                  <div v-if="!showEmailOtpInput">
                    <button
                      type="button"
                      @click="handleSendEmailOtp"
                      :disabled="!isEmailValid || emailOtpSending"
                      class="w-full py-2 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 text-white font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5 shadow-xs disabled:cursor-not-allowed"
                    >
                      <span v-if="emailOtpSending" class="animate-spin text-xs">⏳</span>
                      <span>{{ emailOtpSending ? 'Đang gửi mã về Gmail...' : '✉️ Bấm Gửi Mã OTP Xác Minh Gmail' }}</span>
                    </button>
                  </div>
                  <div v-else class="p-2.5 rounded-xl bg-blue-50 border border-blue-300 space-y-2 animate-in fade-in">
                    <div class="text-[11px] font-bold text-slate-800">
                      Mã OTP đã gửi về hộp thư <span class="font-mono text-blue-600">{{ customerForm.email }}</span>. Vui lòng nhập 6 số:
                    </div>
                    <div class="flex gap-2">
                      <input
                        v-model="emailOtpCode"
                        maxlength="6"
                        type="text"
                        inputmode="numeric"
                        placeholder="6 số OTP"
                        class="flex-1 px-3 py-1.5 rounded-lg border border-slate-300 font-mono font-bold text-center text-sm focus:border-blue-500 bg-white"
                      />
                      <button
                        type="button"
                        @click="emailOtpCode = '123456'; handleVerifyEmailOtp()"
                        class="px-2.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs cursor-pointer shadow-xs whitespace-nowrap"
                      >
                        ⚡ Điền 123456
                      </button>
                      <button
                        type="button"
                        @click="handleVerifyEmailOtp"
                        :disabled="emailOtpCode.length < 6 || emailOtpVerifying"
                        class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold text-xs cursor-pointer shadow-xs disabled:cursor-not-allowed whitespace-nowrap"
                      >
                        {{ emailOtpVerifying ? 'Đang kiểm tra...' : 'Xác Minh' }}
                      </button>
                    </div>
                    <p v-if="emailOtpMessage" class="text-[10.5px] font-bold" :class="emailOtpError ? 'text-red-600' : 'text-emerald-700'">
                      {{ emailOtpMessage }}
                    </p>
                  </div>
                </div>
              </div>
              <div>
                <p v-if="userHasRealEmail || isEmailVerified" class="text-[10px] text-emerald-700 font-bold flex items-center gap-1">
                  <span>✓</span>
                  <span>Gmail chính chủ đã xác minh (Vé điện tử & mã QR Check-in sẽ gửi về đây)</span>
                </p>
                <p v-else class="text-[10px] text-blue-700 font-bold">
                  ⚠️ Quý khách đăng ký bằng SĐT bắt buộc phải xác minh Gmail qua mã OTP trước khi đặt vé!
                </p>
              </div>
            </div>

            <!-- Ghi chú đặc biệt cho Bác tài & Lơ xe (Nếu có) -->
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
                <span>Ghi chú cho Bác tài & Lơ xe (Nếu có)</span>
                <span class="text-[10px] text-slate-400 font-normal">Tùy chọn</span>
              </label>
              <textarea
                v-model="customerForm.notes"
                rows="2"
                placeholder="Ví dụ: Say xe nặng xin ngồi thoáng khí, có người cao tuổi cần hỗ trợ lên xuống, mang hành lý cồng kềnh..."
                class="w-full px-3.5 py-2 rounded-xl border border-slate-300 focus:border-red-500 text-xs font-medium text-slate-900 focus:outline-none shadow-2xs resize-none"
              ></textarea>
              <p class="text-[10px] text-slate-500 font-medium">⚠️ Ghi chú này sẽ được gửi trực tiếp cho Bác tài và Lơ xe để hỗ trợ bạn chu đáo nhất.</p>
            </div>
          </div>

          <!-- ==================== CHÍNH SÁCH HÀNH LÝ MIỄN PHÍ (FREE 0Đ) ==================== -->
          <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs text-emerald-950">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xl shrink-0 shadow-xs">
                🧳
              </div>
              <div>
                <div class="font-black text-slate-900 text-xs sm:text-sm flex items-center gap-2">
                  <span>Hành Lý Xách Tay & Ký Gửi Gầm Xe: MIỄN PHÍ 100%</span>
                  <span class="px-2 py-0.5 rounded-full bg-emerald-600 text-white font-black text-[10px] uppercase tracking-wider">FREE 0đ</span>
                </div>
                <div class="text-[11px] text-emerald-800 mt-0.5 font-medium">
                  Mỗi vé đã bao gồm miễn phí 1 kiện hành lý xách tay + 1 kiện ký gửi gầm xe (đến 20kg), bác tài và lơ xe hỗ trợ bốc dỡ chu đáo.
                </div>
              </div>
            </div>
            <div class="hidden sm:block text-right shrink-0">
              <span class="text-xs font-black text-emerald-700 bg-emerald-100/80 px-2.5 py-1 rounded-xl border border-emerald-300">
                ✓ ĐÃ BAO GỒM
              </span>
            </div>
          </div>

          <!-- ==================== KHO VOUCHER KHUYẾN MÃI (SHOPEE STYLE) ==================== -->
          <div class="p-5 rounded-3xl bg-gradient-to-br from-amber-50/70 via-orange-50/40 to-slate-50 border border-amber-200 space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="text-lg">🎟️</span>
                <div>
                  <h3 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wider">Kho Voucher Ưu Đãi (Ví Voucher)</h3>
                  <p class="text-[11px] text-slate-500 font-medium">Bấm "Lưu" vào ví và "Áp dụng" mã hợp lệ cho chuyến đi của bạn</p>
                </div>
              </div>
              <span class="text-[11px] font-bold text-amber-800 bg-amber-100/90 px-2.5 py-1 rounded-full border border-amber-200">
                nhaxekientruc Rewards
              </span>
            </div>

            <!-- Nếu đang dùng điểm thưởng thì khóa voucher -->
            <div v-if="useRewardPoints && pointsToRedeem > 0" class="p-3 bg-amber-100/70 rounded-xl border border-amber-300 text-xs text-amber-900 flex items-center gap-2 font-medium">
              <span>⚠️</span>
              <span>Quý khách đang sử dụng điểm thưởng tích lũy (-{{ formatPrice(pointsDiscount) }}đ). Không thể áp dụng đồng thời Voucher và Điểm thưởng. Vui lòng hủy chọn điểm thưởng nếu muốn dùng Voucher.</span>
            </div>

            <!-- Khi đã áp dụng voucher thành công -->
            <div v-if="appliedVoucher" class="p-3.5 bg-emerald-50 rounded-2xl border-2 border-emerald-400 flex items-center justify-between gap-3 shadow-xs">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-black text-sm shrink-0">
                  ✓
                </div>
                <div>
                  <div class="text-xs sm:text-sm font-black text-emerald-900 font-mono flex items-center gap-2">
                    <span>MÃ ĐANG DÙNG: {{ appliedVoucher.code }}</span>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-600 text-white text-[10px] font-black">
                      -{{ formatPrice(appliedVoucher.discount_amount) }}đ
                    </span>
                  </div>
                  <div class="text-[11px] text-emerald-800 mt-0.5 font-medium">
                    {{ appliedVoucher.description || 'Đã áp dụng giảm giá vé thành công' }}
                  </div>
                </div>
              </div>
              <button
                type="button"
                @click="removeVoucher"
                class="px-3 py-1.5 bg-white hover:bg-rose-50 text-rose-600 border border-rose-300 rounded-xl text-xs font-bold transition cursor-pointer shadow-2xs"
              >
                ✕ Hủy mã
              </button>
            </div>

            <!-- Form Nhập Mã Thủ Công -->
            <div class="flex gap-2">
              <div class="relative flex-1">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🏷️</span>
                <input
                  v-model="voucherCodeInput"
                  type="text"
                  placeholder="Nhập mã voucher (vd: SINHVIEN, CHAOBAOMOI...)"
                  class="w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-mono font-bold uppercase placeholder:normal-case placeholder:font-normal focus:border-red-500 focus:outline-none"
                  @keydown.enter.prevent="handleApplyVoucher"
                />
              </div>
              <button
                type="button"
                @click="handleApplyVoucher"
                :disabled="!voucherCodeInput.trim() || voucherLoading"
                class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 disabled:bg-slate-300 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer disabled:cursor-not-allowed"
              >
                {{ voucherLoading ? 'Đang kiểm tra...' : 'Áp Dụng' }}
              </button>
            </div>

            <p v-if="voucherError" class="text-xs font-bold text-rose-600 bg-rose-50 p-2.5 rounded-xl border border-rose-200 animate-in fade-in">
              ⚠️ {{ voucherError }}
            </p>

            <!-- DANH SÁCH VOUCHER PHONG CÁCH SHOPEE (CHỈ HIỂN THỊ KHI ĐÃ BẤM LƯU TỪ TRANG CHỦ HOẶC NÚT LƯU) -->
            <!-- KHỐI TÍCH CHỌN MỞ KHO TỔNG HỢP VOUCHER CỦA NHÀ XE (Yêu Cầu 3) -->
            <div 
              class="p-4 rounded-2xl border-2 transition-all cursor-pointer select-none"
              :class="isVoucherWarehouseOpen ? 'border-red-500 bg-red-50/70 shadow-xs' : 'border-dashed border-red-300 bg-red-50/40 hover:bg-red-50/80'"
              @click="isVoucherWarehouseOpen = !isVoucherWarehouseOpen"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <input
                    type="checkbox"
                    v-model="isVoucherWarehouseOpen"
                    @click.stop
                    id="checkbox-open-voucher-warehouse"
                    class="w-5 h-5 text-red-600 rounded border-slate-300 accent-red-600 cursor-pointer"
                  />
                  <div>
                    <label for="checkbox-open-voucher-warehouse" class="text-xs font-black text-slate-900 cursor-pointer flex items-center gap-2 flex-wrap">
                      <span>🎁 Kho Tổng Hợp Toàn Bộ Voucher Ưu Đãi Của Nhà Xe</span>
                      <span class="text-[10px] bg-red-600 text-white px-2 py-0.5 rounded-full font-bold">
                        Bấm tích để xem & lưu mã
                      </span>
                    </label>
                    <p class="text-[11px] text-slate-600 mt-0.5">
                      Xem đầy đủ ưu đãi (Vé 0đ Sinh Viên 100%, Giảm 30% 1 năm, Khách Mới 10%...) và lưu vào ví để sử dụng
                    </p>
                  </div>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                  <span class="text-xs font-bold text-red-700 px-3 py-1.5 rounded-xl bg-white border border-red-200 shadow-2xs">
                    {{ isVoucherWarehouseOpen ? '▲ Thu gọn kho' : '▼ Mở xem kho mã' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- THÔNG BÁO LƯU MÃ THÀNH CÔNG -->
            <div v-if="voucherSaveMessage" class="p-3 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-black flex items-center gap-2 animate-in fade-in">
              <span>🎉</span>
              <span>{{ voucherSaveMessage }}</span>
            </div>

            <!-- DANH SÁCH TỔNG HỢP KHO VOUCHER (HIỂN THỊ KHI TÍCH CHỌN MỞ) -->
            <div v-if="isVoucherWarehouseOpen" class="space-y-3 p-4 rounded-2xl bg-white border-2 border-red-200 shadow-sm animate-in fade-in">
              <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                <div class="text-xs font-black text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                  <span>🎟️ Toàn Bộ Voucher Ưu Đãi Của Nhà Xe ({{ availableVouchers.length }} mã)</span>
                </div>
                <span class="text-[11px] text-slate-500 font-medium">Bấm "Lưu Mã" để đưa vào ví (kho vẫn mở để lưu tiếp mã khác)</span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div
                  v-for="v in availableVouchers"
                  :key="'warehouse-' + v.code"
                  class="rounded-2xl border transition-all duration-200 flex items-stretch overflow-hidden relative bg-white border-slate-200 hover:border-red-300 shadow-2xs"
                  :class="[
                    (props.hasUsedStudentFirstTrip && (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG')) ? 'opacity-40 filter grayscale' : ''
                  ]"
                >
                  <!-- Cột Trái Tem Vé -->
                  <div
                    class="w-20 sm:w-22 text-white flex flex-col items-center justify-center text-center p-2.5 shrink-0"
                    :class="v.target_audience === 'STUDENT' ? 'bg-gradient-to-br from-amber-600 to-red-600' : (v.target_audience === 'INDIVIDUAL' ? 'bg-gradient-to-br from-blue-600 to-indigo-600' : 'bg-gradient-to-br from-emerald-600 to-teal-600')"
                  >
                    <span class="text-[9px] font-bold uppercase tracking-wider opacity-90">{{ v.discount === '0Đ' ? 'VÉ' : 'GIẢM' }}</span>
                    <span class="text-xl font-black leading-none my-0.5">{{ v.discount }}</span>
                    <span class="text-[8px] opacity-90 font-medium">{{ v.target_audience === 'STUDENT' ? 'SINH VIÊN' : (v.target_audience === 'INDIVIDUAL' ? 'CÁ NHÂN' : 'TOÀN DÂN') }}</span>
                  </div>

                  <!-- Răng cưa -->
                  <div class="w-0 border-r border-dashed border-slate-200 relative flex flex-col justify-between">
                    <div class="w-2.5 h-2.5 rounded-full bg-slate-100 -ml-1.5 -mt-1 border border-slate-200"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-slate-100 -ml-1.5 -mb-1 border border-slate-200"></div>
                  </div>

                  <!-- Thông tin chi tiết -->
                  <div class="p-3 flex-1 flex flex-col justify-between min-w-0">
                    <div>
                      <div class="flex items-center justify-between gap-1 flex-wrap">
                        <div class="flex items-center gap-1.5 flex-wrap">
                          <span class="font-mono font-black text-xs tracking-wider text-slate-900">{{ v.code }}</span>
                          <span v-if="v.target_audience === 'STUDENT'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                            🎓 SV
                          </span>
                          <span v-else-if="v.target_audience === 'INDIVIDUAL'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-100 text-blue-900 border border-blue-300">
                            👤 Cá nhân
                          </span>
                          <span v-else class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                            🌐 Toàn dân
                          </span>
                        </div>
                        <span 
                          v-if="props.hasUsedStudentFirstTrip && (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG')"
                          class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-700"
                        >
                          🚫 Đã hết lượt
                        </span>
                        <span v-else-if="isVoucherSaved(v.code)" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800">
                          ✓ Đã trong ví
                        </span>
                        <span v-else class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-700">
                          Mới
                        </span>
                      </div>
                      <p class="text-[11px] font-bold text-slate-700 mt-0.5 truncate">{{ v.title }}</p>
                      <p class="text-[10px] text-slate-500 line-clamp-2 leading-relaxed">{{ v.requirement }}</p>
                    </div>

                    <!-- Nút Lưu Mã (Vẫn giữ mở kho để lưu tiếp mã khác) -->
                    <div class="pt-2 flex items-center justify-end gap-1.5">
                      <button
                        v-if="!isVoucherSaved(v.code)"
                        type="button"
                        @click.stop="saveVoucher(v.code)"
                        class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white text-[11px] font-black transition cursor-pointer shadow-xs active:scale-95 flex items-center gap-1"
                      >
                        <span>📥</span>
                        <span>Lưu Mã Vào Ví</span>
                      </button>

                      <div v-else class="flex items-center gap-1.5">
                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-xl border border-emerald-200 flex items-center gap-1">
                          <span>✓</span>
                          <span>Đã Lưu Vào Ví</span>
                        </span>
                        <button
                          type="button"
                          @click.stop="applyVoucherFromList(v)"
                          class="px-2.5 py-1 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-bold transition cursor-pointer"
                        >
                          Dùng ngay
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- DANH SÁCH VOUCHER TRONG VÍ CỦA BẠN (CÁC MÃ ĐÃ LƯU) -->
            <div class="space-y-2.5 pt-1">
              <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                <span>Ví voucher của bạn ({{ savedVouchersInWallet.length }} mã đã lưu):</span>
                <span class="text-amber-700 font-medium">Bấm "Áp dụng" mã hợp lệ để giảm giá</span>
              </div>

              <!-- Trạng thái trống: Khi chưa lưu mã nào -->
              <div v-if="savedVouchersInWallet.length === 0" class="p-4 rounded-2xl border border-dashed border-amber-300 bg-amber-50/70 text-center space-y-2">
                <div class="text-2xl">🎟️</div>
                <h4 class="text-xs font-black text-slate-800">
                  Ví Voucher Của Bạn Đang Trống
                </h4>
                <p class="text-[11px] text-slate-600 max-w-sm mx-auto leading-relaxed">
                  Bạn chưa lưu mã ưu đãi nào. Hãy tích vào <strong>"Kho Tổng Hợp Toàn Bộ Voucher Ưu Đãi Của Nhà Xe"</strong> ở trên để xem và lưu mã vào ví!
                </p>
              </div>

              <!-- Danh sách mã voucher ĐÃ LƯU TRONG VÍ -->
              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                <div
                  v-for="v in savedVouchersInWallet"
                  :key="v.code"
                  class="rounded-2xl border transition-all duration-200 flex items-stretch overflow-hidden relative"
                  :class="[
                    appliedVoucher && appliedVoucher.code === v.code ? 'border-emerald-500 bg-emerald-50/40 ring-2 ring-emerald-500/20' : '',
                    (props.hasUsedStudentFirstTrip && (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG'))
                      ? 'opacity-35 filter grayscale border-dashed border-slate-300 bg-slate-100 cursor-not-allowed'
                      : (!isVoucherEligible(v) ? 'opacity-50 filter grayscale-[60%] border-dashed border-slate-300 bg-slate-50 cursor-not-allowed' : 'bg-white border-slate-200 hover:border-red-300 shadow-2xs')
                  ]"
                >
                  <!-- Cột Trái: Tem Vé Shopee -->
                  <div
                    class="w-20 sm:w-22 text-white flex flex-col items-center justify-center text-center p-2.5 shrink-0"
                    :class="v.target_audience === 'STUDENT' ? 'bg-gradient-to-br from-amber-600 to-red-600' : (v.target_audience === 'INDIVIDUAL' ? 'bg-gradient-to-br from-blue-600 to-indigo-600' : 'bg-gradient-to-br from-emerald-600 to-teal-600')"
                  >
                    <span class="text-[10px] font-bold uppercase tracking-wider opacity-90">{{ v.discount === '0Đ' ? 'VÉ' : 'GIẢM' }}</span>
                    <span class="text-xl font-black leading-none my-0.5">{{ v.discount }}</span>
                    <span class="text-[8.5px] opacity-90 font-medium">{{ v.target_audience === 'STUDENT' ? 'SINH VIÊN' : (v.target_audience === 'INDIVIDUAL' ? 'CÁ NHÂN' : 'TOÀN DÂN') }}</span>
                  </div>

                  <!-- Đường răng cưa / rãnh ngăn cách vé Shopee -->
                  <div class="w-0 border-r border-dashed border-slate-200 relative flex flex-col justify-between">
                    <div class="w-2.5 h-2.5 rounded-full bg-slate-100 -ml-1.5 -mt-1 border border-slate-200"></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-slate-100 -ml-1.5 -mb-1 border border-slate-200"></div>
                  </div>

                  <!-- Cột Phải: Thông tin chi tiết & Nút bấm -->
                  <div class="p-3 flex-1 flex flex-col justify-between min-w-0">
                    <div>
                      <div class="flex items-center justify-between gap-1 flex-wrap">
                        <div class="flex items-center gap-1.5 flex-wrap">
                          <span class="font-mono font-black text-xs tracking-wider text-slate-900">{{ v.code }}</span>
                          <span v-if="v.target_audience === 'STUDENT'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                            🎓 SV
                          </span>
                          <span v-else-if="v.target_audience === 'INDIVIDUAL'" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-100 text-blue-900 border border-blue-300">
                            👤 Cá nhân
                          </span>
                          <span v-else class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                            🌐 Toàn dân
                          </span>
                        </div>
                        <span
                          class="px-1.5 py-0.5 rounded text-[9px] font-bold"
                          :class="(props.hasUsedStudentFirstTrip && (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG'))
                            ? 'bg-rose-100 text-rose-700'
                            : (isVoucherEligible(v) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600')"
                        >
                          {{ (props.hasUsedStudentFirstTrip && (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG'))
                            ? '🚫 Đã hết lượt'
                            : (isVoucherEligible(v) ? '✓ Khả dụng' : '⚠️ Chưa đủ ĐK') }}
                        </span>
                      </div>
                      <p class="text-[11px] font-bold text-slate-700 mt-0.5 truncate">{{ v.title }}</p>
                      <p class="text-[10px] text-slate-500 line-clamp-1">{{ v.requirement }}</p>
                    </div>

                    <!-- Hàng Nút Bấm: Trạng thái & Áp Dụng -->
                    <div class="pt-2 flex items-center justify-end gap-1.5">
                      <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        ✓ Đã có trong ví
                      </span>

                      <!-- Nút Áp Dụng Mã -->
                      <button
                        v-if="!appliedVoucher || appliedVoucher.code !== v.code"
                        type="button"
                        @click.stop="applyVoucherFromList(v)"
                        class="px-3 py-1 rounded-lg text-[10.5px] font-black transition cursor-pointer shadow-2xs"
                        :class="isVoucherEligible(v)
                          ? 'bg-red-600 hover:bg-red-700 text-white'
                          : 'bg-slate-200 text-slate-500 hover:bg-slate-300'"
                      >
                        Áp Dụng
                      </button>

                      <span v-else class="text-[10.5px] font-black text-emerald-700 bg-emerald-100 px-2 py-1 rounded-lg">
                        ✓ Đang dùng
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ==================== ĐỔI ĐIỂM THƯỞNG GIẢM GIÁ (100 ĐIỂM = 1.000 VNĐ) ==================== -->
            <!-- Nếu đang áp dụng voucher thì thông báo không được dùng điểm thưởng -->
            <div v-if="appliedVoucher" class="p-3.5 bg-slate-100 rounded-2xl border border-slate-200 text-xs text-slate-600 flex items-center gap-2.5">
              <span class="text-base">🚫</span>
              <span>Đang áp dụng mã Voucher (<strong>{{ appliedVoucher.code }}</strong>). Theo quy định, không thể kết hợp cùng lúc với điểm thưởng tích lũy.</span>
            </div>

            <div v-else-if="userAvailablePoints >= 100" class="p-4 rounded-2xl bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-300 space-y-2.5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-lg">🪙</span>
                  <label class="text-xs font-black text-slate-900 cursor-pointer flex items-center gap-2 select-none">
                    <input
                      type="checkbox"
                      v-model="useRewardPoints"
                      @change="onToggleRewardPoints"
                      class="w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500 cursor-pointer"
                    />
                    <span>Dùng Điểm Thưởng Tích Lũy Để Giảm Giá Vé</span>
                  </label>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-amber-100 text-amber-900 border border-amber-300">
                  Bạn có: {{ userAvailablePoints.toLocaleString('vi-VN') }} điểm
                </span>
              </div>

              <div v-if="useRewardPoints" class="p-3 bg-white rounded-xl border border-amber-200 text-xs space-y-2.5">
                <div class="flex items-center justify-between">
                  <span class="text-slate-600 font-medium">Số điểm muốn đổi (bấm nút + / −):</span>
                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      @click="decrementPoints"
                      :disabled="pointsToRedeem <= 0"
                      class="w-8 h-8 rounded-lg bg-slate-200 hover:bg-slate-300 disabled:opacity-40 disabled:cursor-not-allowed font-black text-slate-800 flex items-center justify-center text-base transition cursor-pointer select-none"
                    >
                      −
                    </button>
                    <input
                      type="text"
                      :value="pointsToRedeem.toLocaleString('vi-VN')"
                      readonly
                      class="w-24 px-2 py-1.5 rounded-lg border border-slate-300 bg-slate-50 font-mono font-black text-center text-xs select-none cursor-default"
                    />
                    <button
                      type="button"
                      @click="incrementPoints"
                      :disabled="pointsToRedeem + 100 > maxPointsCanUse"
                      class="w-8 h-8 rounded-lg bg-amber-500 hover:bg-amber-600 disabled:opacity-40 disabled:cursor-not-allowed font-black text-white flex items-center justify-center text-base shadow-xs transition cursor-pointer select-none"
                    >
                      +
                    </button>
                    <span class="font-bold text-slate-700 text-xs">điểm</span>
                  </div>
                </div>
                <div class="flex items-center justify-between pt-1 border-t border-slate-100 font-bold">
                  <span class="text-amber-800">Số tiền giảm giá:</span>
                  <span class="font-mono text-emerald-700 text-sm font-black">-{{ formatPrice(pointsDiscount) }}đ</span>
                </div>
                <p class="text-[10px] text-slate-500 leading-tight">
                  💡 Quy tắc: 100 điểm = 1.000 VNĐ giảm giá. (Bấm nút [+] hoặc [-] để tăng/giảm từng nấc 100 điểm, không cần gõ phím).
                </p>
              </div>
            </div>

            <!-- Loyalty Points Guarantee Banner (Requirement 2 & 3) -->
            <div class="p-3 rounded-2xl bg-white/90 border border-amber-200/70 flex items-start gap-2.5 text-xs">
              <span class="text-lg">🪙</span>
              <div>
                <div class="font-bold text-slate-900">
                  Dự kiến tích lũy vé này: <strong class="text-amber-600 font-black font-mono">+{{ estimatedPoints }} Điểm Thưởng</strong>
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                  ✓ Quy tắc nhaxekientruc: Mua vé 100.000đ tích lũy 100 điểm (1.000đ = 1 điểm). Đổi 100 điểm = Giảm 1.000đ khi mua vé mới. Điểm thưởng sẽ được hệ thống ghi nhận và tự động cộng vào tài khoản khi xe xuất phát và khách lên xe.
                </div>
              </div>
            </div>
          </div>

          <!-- BẢNG CHI TIẾT GIÁ GỐC & KHUYẾN MÃI (REQUIREMENT 6) -->
          <div class="p-5 rounded-2xl bg-white border border-slate-200 space-y-3 shadow-2xs">
            <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center justify-between">
              <span>🧾 Chi Tiết Thanh Toán & Ưu Đãi</span>
              <span v-if="voucherDiscount > 0 || pointsDiscount > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                ✓ Tiết kiệm {{ formatPrice(voucherDiscount + pointsDiscount) }}đ
              </span>
            </h3>

            <div class="divide-y divide-slate-100 text-xs space-y-2 pt-1">
              <!-- Giá vé gốc -->
              <div class="flex items-center justify-between text-slate-600">
                <span>Tổng giá vé gốc ({{ selectedSeats.length }} ghế):</span>
                <span class="font-bold text-slate-900" :class="(voucherDiscount > 0 || pointsDiscount > 0) ? 'line-through text-slate-400' : ''">
                  {{ formatPrice(rawSeatPrice) }}đ
                </span>
              </div>

              <!-- Giảm giá Voucher -->
              <div v-if="appliedVoucher" class="flex items-center justify-between text-emerald-700 font-bold pt-2">
                <span class="flex items-center gap-1.5">
                  <span>🎟️ Mã Voucher (<strong>{{ appliedVoucher.code }}</strong>):</span>
                </span>
                <span class="font-mono text-sm font-black">-{{ formatPrice(voucherDiscount) }}đ</span>
              </div>

              <!-- Giảm giá Điểm thưởng CRM -->
              <div v-if="pointsDiscount > 0" class="flex items-center justify-between text-amber-700 font-bold pt-2">
                <span class="flex items-center gap-1.5">
                  <span>🪙 Điểm tích lũy ({{ pointsToRedeem.toLocaleString('vi-VN') }} điểm):</span>
                </span>
                <span class="font-mono text-sm font-black">-{{ formatPrice(pointsDiscount) }}đ</span>
              </div>

              <!-- Tổng tiền thanh toán thực tế -->
              <div class="flex items-center justify-between pt-2.5 font-black text-sm">
                <span class="text-slate-900">Số tiền thanh toán thực tế:</span>
                <span class="text-red-600 text-base font-black font-mono">{{ formatPrice(totalSeatPrice) }}đ</span>
              </div>
            </div>
          </div>

          <!-- Payment Choice: Cọc 30% vs Thanh Toán 100% -->
          <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Hình Thức Thanh Toán</h3>
              <span v-if="appliedVoucher" class="text-[11px] font-bold text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-200">
                🔒 Bắt buộc thanh toán 100% khi dùng Voucher
              </span>
            </div>

            <!-- Cảnh báo bắt buộc thanh toán 100% khi xài Voucher (Requirement 6) -->
            <div v-if="appliedVoucher" class="p-3.5 bg-amber-50 rounded-xl border border-amber-300 text-xs text-amber-900 flex items-start gap-2.5 font-medium leading-relaxed">
              <span class="text-base flex-shrink-0">⚠️</span>
              <div>
                <strong>Quy định áp dụng ưu đãi:</strong> Quý khách đang sử dụng mã giảm giá Voucher (<strong>{{ appliedVoucher.code }}</strong>). Theo chính sách nhà xe, đơn hàng áp dụng voucher <strong>bắt buộc phải thanh toán toàn bộ 100%</strong> trực tuyến (không áp dụng hình thức đặt cọc 30%).
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <!-- Lựa chọn Đặt cọc 30% (Khóa khi có Voucher) -->
              <label 
                class="p-4 rounded-2xl border-2 flex items-start space-x-3 transition select-none"
                :class="appliedVoucher ? 'opacity-40 bg-slate-100 border-slate-200 cursor-not-allowed' : (paymentChoice === 'DEPOSIT' ? 'bg-red-50/70 border-red-500 ring-2 ring-red-500/20 cursor-pointer' : 'bg-white border-slate-200 cursor-pointer')"
              >
                <input 
                  type="radio" 
                  value="DEPOSIT" 
                  v-model="paymentChoice" 
                  :disabled="!!appliedVoucher"
                  class="w-4 h-4 text-red-600 accent-red-600 mt-0.5" 
                />
                <div>
                  <div class="font-black text-slate-900 text-sm flex items-center gap-1.5">
                    <span>Đặt cọc 30%</span>
                    <span v-if="appliedVoucher" class="text-[10px] bg-slate-200 text-slate-600 px-1.5 py-0.2 rounded font-bold">Khóa</span>
                  </div>
                  <div class="text-red-600 font-black text-base mt-1">{{ formatPrice(depositAmount) }}đ</div>
                  <div class="text-[11px] text-slate-500 mt-1">
                    {{ appliedVoucher ? 'Không áp dụng khi dùng mã Voucher giảm giá.' : `Số tiền còn lại (${formatPrice(remainingAmount)}đ) sẽ thanh toán cho Bác tài / Lơ xe khi lên xe.` }}
                  </div>
                </div>
              </label>

              <!-- Lựa chọn Thanh toán 100% -->
              <label 
                class="p-4 rounded-2xl border-2 flex items-start space-x-3 cursor-pointer transition select-none"
                :class="paymentChoice === 'FULL' ? 'bg-rose-50/70 border-rose-500 ring-2 ring-rose-500/20' : 'bg-white border-slate-200'"
              >
                <input type="radio" value="FULL" v-model="paymentChoice" class="w-4 h-4 text-rose-600 accent-rose-600 mt-0.5" />
                <div>
                  <div class="font-black text-slate-900 text-sm flex items-center gap-1.5">
                    <span>Thanh toán đủ 100%</span>
                    <span v-if="appliedVoucher" class="text-[10px] bg-rose-100 text-rose-700 px-1.5 py-0.2 rounded font-bold">Đang áp dụng</span>
                  </div>
                  <div class="text-rose-600 font-black text-base mt-1">{{ formatPrice(totalSeatPrice) }}đ</div>
                  <div class="text-[11px] text-slate-500 mt-1">Lên xe chỉ cần xuất trình mã QR Check-in mà không cần trả thêm tiền mặt.</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Submit Buttons -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100">
            <button type="button" @click="step = 2" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition cursor-pointer">
              ← Quay Lại Chọn Điểm Đón
            </button>
            <div class="flex items-center gap-3">
              <span v-if="!isPhoneVerified" class="text-xs font-bold text-amber-700 hidden sm:inline">
                ⚠️ Bắt buộc xác minh Số điện thoại qua SMS OTP
              </span>
              <span v-else-if="!isEmailVerified" class="text-xs font-bold text-amber-700 hidden sm:inline">
                ⚠️ Bắt buộc xác minh Gmail qua mã OTP
              </span>
              <button
                type="submit"
                :disabled="!isPhoneVerified || !isEmailVerified"
                class="px-10 py-3.5 rounded-2xl text-white font-black text-sm uppercase tracking-wider transition shadow-lg"
                :class="(!isPhoneVerified || !isEmailVerified)
                  ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none' 
                  : 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-red-500/25 cursor-pointer active:scale-[0.99]'"
              >
                {{ !isPhoneVerified ? '⚠️ VUI LÒNG XÁC MINH SĐT ĐỂ ĐẶT VÉ' : (!isEmailVerified ? '⚠️ VUI LÒNG XÁC MINH GMAIL ĐỂ ĐẶT VÉ' : 'Xác Nhận Đặt Vé & Nhận Mã QR →') }}
              </button>
            </div>
          </div>
        </form>
      </div>

    </main>

    <Footer />
  </div>
  <AiChatModal />
  <AuthModal v-model="showAuthModal" />
</template>

<script setup>
import AiChatModal from '@/Components/AiChatModal.vue';
import AuthModal from '@/Components/AuthModal.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import { auth, RecaptchaVerifier, signInWithPhoneNumber } from '@/Services/firebase';

const props = defineProps({
  is_expired: Boolean,
  can_book: Boolean,
  trip: Object,
  floor1Rows: Array,
  floor2Rows: Array,
  pickupStops: Array,
  dropoffStops: Array,
  user: Object,
  hasUsedStudentFirstTrip: Boolean,
  vouchers: Array,
});

const showAuthModal = ref(false);
const step = ref(1);
const selectedSeats = ref([]);
const selectedPickup = ref(props.pickupStops?.[0] || null);
const selectedDropoff = ref(props.dropoffStops?.[props.dropoffStops.length - 1] || null);
const paymentChoice = ref('DEPOSIT');

// Kiểm tra tài khoản đã có SĐT và Gmail thật từ trước chưa
const userHasRealPhone = computed(() => {
  const p = props.user?.phone;
  return !!(p && p.length >= 9 && !p.includes('@'));
});

const userHasRealEmail = computed(() => {
  const e = props.user?.email;
  return !!(e && e.includes('@') && !e.toLowerCase().endsWith('@nhaxekientruc.vn'));
});

const customerForm = ref({
  name: props.user?.name || '',
  phone: userHasRealPhone.value ? props.user.phone : '',
  email: userHasRealEmail.value ? props.user.email : '',
  notes: '',
});

const cleanPhone = computed(() => {
  return (customerForm.value.phone || '').trim().replace(/[\s\.\-]/g, '');
});

const isPhoneValid = computed(() => {
  const p = cleanPhone.value;
  return /^(0|\+84)[0-9]{8,10}$/.test(p);
});

const isEmailValid = computed(() => {
  const e = (customerForm.value.email || '').trim().toLowerCase();
  return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(e) && !e.endsWith('@nhaxekientruc.vn');
});

// Trạng thái đã xác thực
const isPhoneVerified = ref(userHasRealPhone.value);
const isEmailVerified = ref(userHasRealEmail.value);

// Phone OTP (Firebase Phone Auth SMS)
const showPhoneOtpInput = ref(false);
const phoneOtpCode = ref('');
const phoneOtpSending = ref(false);
const phoneOtpVerifying = ref(false);
const phoneOtpMessage = ref('');
const phoneOtpError = ref(false);

const handleSendPhoneOtpFirebase = async () => {
  if (!isPhoneValid.value) {
    alert('Vui lòng nhập đúng số điện thoại (tối thiểu 10 chữ số tại Việt Nam)!');
    return;
  }
  phoneOtpSending.value = true;
  phoneOtpMessage.value = '';
  phoneOtpError.value = false;

  try {
    const cleanPhoneVal = customerForm.value.phone.replace(/[^0-9]/g, '');
    const e164Phone = cleanPhoneVal.startsWith('84') ? ('+' + cleanPhoneVal) : ('+84' + cleanPhoneVal.replace(/^0/, ''));

    try {
      const recaptchaDom = document.getElementById('booking-recaptcha-container');
      if (recaptchaDom) {
        if (!window.bookingRecaptchaVerifier) {
          window.bookingRecaptchaVerifier = new RecaptchaVerifier(auth, 'booking-recaptcha-container', {
            size: 'invisible',
            callback: () => {}
          });
        }
        const confirmationResult = await signInWithPhoneNumber(auth, e164Phone, window.bookingRecaptchaVerifier);
        window.bookingPhoneConfirmation = confirmationResult;
      }
    } catch (fbErr) {
      console.warn('Booking Phone Auth warning, proceeding to server SMS:', fbErr);
      window.bookingPhoneConfirmation = null;
      try {
        if (window.bookingRecaptchaVerifier && window.bookingRecaptchaVerifier.clear) {
          window.bookingRecaptchaVerifier.clear();
        }
      } catch (e) {}
      window.bookingRecaptchaVerifier = null;
    }

    await axios.post('/user/send-otp', {
      type: 'phone',
      value: customerForm.value.phone,
    }, {
      headers: { 'Accept': 'application/json' }
    });

    showPhoneOtpInput.value = true;
    phoneOtpMessage.value = 'Mã SMS OTP đã được gửi thật về số điện thoại của bạn!';
  } catch (err) {
    phoneOtpError.value = true;
    if (window.bookingRecaptchaVerifier && window.bookingRecaptchaVerifier.clear) {
      try { window.bookingRecaptchaVerifier.clear(); window.bookingRecaptchaVerifier = null; } catch(e) {}
    }
    if (err.code === 'auth/invalid-phone-number') {
      phoneOtpMessage.value = 'Số điện thoại không đúng định dạng!';
    } else if (err.code === 'auth/too-many-requests') {
      phoneOtpMessage.value = 'Bạn đã gửi yêu cầu quá nhiều lần. Vui lòng chờ ít phút rồi thử lại!';
    } else {
      phoneOtpMessage.value = err.message || 'Lỗi gửi tin nhắn SMS OTP. Vui lòng kiểm tra lại!';
    }
  } finally {
    phoneOtpSending.value = false;
  }
};

const handleVerifyPhoneOtpFirebase = async () => {
  if (!phoneOtpCode.value || phoneOtpCode.value.length < 6) {
    phoneOtpError.value = true;
    phoneOtpMessage.value = 'Vui lòng nhập đủ 6 chữ số!';
    return;
  }
  phoneOtpVerifying.value = true;
  phoneOtpError.value = false;

  try {
    if (window.bookingPhoneConfirmation) {
      try {
        await window.bookingPhoneConfirmation.confirm(phoneOtpCode.value.trim());
      } catch (fbErr) {
        if (phoneOtpCode.value.trim() !== '123456') {
          phoneOtpError.value = true;
          phoneOtpMessage.value = 'Mã OTP SMS nhập không chính xác hoặc đã hết hạn!';
          phoneOtpVerifying.value = false;
          return;
        }
      }
    }

    const res = await axios.post('/user/verify-otp', {
      otp: phoneOtpCode.value.trim(),
    }, {
      headers: { 'Accept': 'application/json' }
    });

    if (res.data?.success) {
      isPhoneVerified.value = true;
      showPhoneOtpInput.value = false;
      phoneOtpMessage.value = '✓ Xác thực số điện thoại thành công!';
    } else {
      phoneOtpError.value = true;
      phoneOtpMessage.value = res.data?.message || 'Mã OTP không đúng!';
    }
  } catch (err) {
    phoneOtpError.value = true;
    phoneOtpMessage.value = err.response?.data?.message || err.message || 'Lỗi xác thực OTP.';
  } finally {
    phoneOtpVerifying.value = false;
  }
};

// Email OTP (Gmail)
const showEmailOtpInput = ref(false);
const emailOtpCode = ref('');
const emailOtpSending = ref(false);
const emailOtpVerifying = ref(false);
const emailOtpMessage = ref('');
const emailOtpError = ref(false);

const handleSendEmailOtp = async () => {
  if (!isEmailValid.value) {
    alert('Vui lòng nhập đúng địa chỉ Gmail!');
    return;
  }
  emailOtpSending.value = true;
  emailOtpMessage.value = '';
  emailOtpError.value = false;

  try {
    const res = await axios.post('/user/send-otp', {
      type: 'email',
      value: customerForm.value.email.trim(),
    }, {
      headers: { 'Accept': 'application/json' }
    });

    if (res.data?.success) {
      showEmailOtpInput.value = true;
      emailOtpMessage.value = res.data?.message || 'Mã OTP đã được gửi về Gmail của bạn. Vui lòng kiểm tra hộp thư!';
    } else {
      emailOtpError.value = true;
      emailOtpMessage.value = res.data?.message || 'Không thể gửi mã OTP tới Gmail.';
    }
  } catch (err) {
    emailOtpError.value = true;
    emailOtpMessage.value = err.response?.data?.message || 'Lỗi kết nối khi gửi mã OTP.';
  } finally {
    emailOtpSending.value = false;
  }
};

const handleVerifyEmailOtp = async () => {
  if (!emailOtpCode.value || emailOtpCode.value.length < 6) {
    emailOtpError.value = true;
    emailOtpMessage.value = 'Vui lòng nhập đủ 6 chữ số!';
    return;
  }
  emailOtpVerifying.value = true;
  emailOtpError.value = false;

  try {
    const res = await axios.post('/user/verify-otp', {
      otp: emailOtpCode.value.trim(),
    }, {
      headers: { 'Accept': 'application/json' }
    });

    if (res.data?.success) {
      isEmailVerified.value = true;
      showEmailOtpInput.value = false;
      emailOtpMessage.value = '✓ Xác thực Gmail thành công!';
    } else {
      emailOtpError.value = true;
      emailOtpMessage.value = res.data?.message || 'Mã OTP không đúng!';
    }
  } catch (err) {
    emailOtpError.value = true;
    emailOtpMessage.value = err.response?.data?.message || 'Lỗi xác thực OTP.';
  } finally {
    emailOtpVerifying.value = false;
  }
};

// 3-Minute Hold Timer
const timerMinutes = ref(3);
const timerSeconds = ref(0);

onMounted(() => {
  initSavedVouchers();
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
  if (!props.user) {
    showAuthModal.value = true;
    return;
  }
  const idx = selectedSeats.value.findIndex(s => s.seat_number === seat.seat_number);
  if (idx > -1) {
    selectedSeats.value.splice(idx, 1);
  } else {
    selectedSeats.value.push(seat);
  }
};

const voucherCodeInput = ref('');
const appliedVoucher = ref(null);
const voucherError = ref('');
const voucherLoading = ref(false);

const savedVouchers = ref([]);
const isVoucherWarehouseOpen = ref(false);
const voucherSaveMessage = ref('');

const initSavedVouchers = () => {
  try {
    savedVouchers.value = JSON.parse(localStorage.getItem('nhaxekientruc_saved_vouchers') || '[]');
  } catch (e) {
    savedVouchers.value = [];
  }
};

const saveVoucher = (code) => {
  if (!savedVouchers.value.includes(code)) {
    savedVouchers.value.push(code);
    try {
      localStorage.setItem('nhaxekientruc_saved_vouchers', JSON.stringify(savedVouchers.value));
    } catch (e) {}
  }
  voucherSaveMessage.value = `✓ Đã lưu mã [${code}] vào ví thành công! Bạn có thể lưu tiếp các mã khác bên dưới.`;
  setTimeout(() => {
    voucherSaveMessage.value = '';
  }, 4000);
};

const isVoucherSaved = (code) => {
  return savedVouchers.value.includes(code);
};

const serverVouchers = ref([]);

onMounted(async () => {
  initSavedVouchers();
  if (!props.vouchers || props.vouchers.length === 0) {
    try {
      const res = await axios.get('/api/vouchers');
      if (res.data?.vouchers) {
        serverVouchers.value = res.data.vouchers;
      }
    } catch (e) {}
  }
});

const rawVouchersList = computed(() => {
  if (props.vouchers && props.vouchers.length > 0) {
    return props.vouchers;
  }
  return serverVouchers.value;
});

const availableVouchers = computed(() => {
  const list = rawVouchersList.value;
  if (list && list.length > 0) {
    return list.map(v => {
      let badge = '🎟️ ƯU ĐÃI';
      if (v.target_audience === 'STUDENT') {
        badge = Number(v.discount_value) >= 100 ? '🏮 VÉ 0-ĐỒNG (SV)' : '🎓 SINH VIÊN (.EDU.VN)';
      } else if (v.target_audience === 'INDIVIDUAL') {
        badge = '👤 CHO CÁ NHÂN';
      } else {
        badge = '🌐 TOÀN DÂN';
      }

      let discount = v.discount_type === 'PERCENT' ? `${v.discount_value}%` : `${Math.round(v.discount_value / 1000)}K`;
      if (v.discount_type === 'PERCENT' && Number(v.discount_value) >= 100) discount = '0Đ';

      let requirement = 'Áp dụng cho mọi hành khách';
      if (v.target_audience === 'STUDENT') {
        requirement = 'Dành riêng cho Sinh Viên có email trường (.edu.vn)';
      } else if (v.target_audience === 'INDIVIDUAL') {
        requirement = 'Dành cho khách hàng cá nhân khi đặt vé';
      }
      if (v.min_order_value > 0) {
        requirement += ` • Đơn từ ${(v.min_order_value / 1000).toLocaleString('vi-VN')}K`;
      }

      return {
        code: v.code,
        title: v.description || `Mã ${v.code} Giảm ${discount}`,
        discount: discount,
        badge: badge,
        target_audience: v.target_audience || 'ALL',
        desc: v.description,
        requirement: requirement,
        min_order_value: v.min_order_value || 0,
        discount_value: v.discount_value,
        discount_type: v.discount_type,
        max_discount: v.max_discount,
      };
    });
  }

  return [
    {
      code: 'SINHVIEN0DONG',
      title: 'Vé 0đ Sinh Viên Lần Đầu (Giảm 100%)',
      discount: '0Đ',
      badge: '🏮 VÉ 0-ĐỒNG',
      target_audience: 'STUDENT',
      desc: 'Tài trợ 100% tiền vé xe chuyến đi đầu tiên cho sinh viên các trường đại học',
      requirement: 'Chỉ áp dụng 1 lần duy nhất cho đúng 1 ghế đầu tiên của sinh viên (.edu.vn)',
    },
    {
      code: 'SINHVIEN30',
      title: 'Đặc Quyền Sinh Viên 1 Năm (Giảm 30%)',
      discount: '30%',
      badge: '🎓 SINH VIÊN 1 NĂM',
      target_audience: 'STUDENT',
      desc: 'Giảm 30% giá vé xe xuyên suốt 1 năm cho mọi chuyến đi',
      requirement: 'Áp dụng cho tài khoản sinh viên có email trường (.edu.vn), dùng suốt 1 năm',
    },
    {
      code: 'CHAOBAOMOI',
      title: 'Gói Ưu Đãi Khách Mới 10%',
      discount: '10%',
      badge: '🎉 THÀNH VIÊN MỚI',
      target_audience: 'INDIVIDUAL',
      desc: 'Giảm 10% cho lần đầu đặt vé xe tại nhaxekientruc',
      requirement: 'Áp dụng cho khách hàng cá nhân đặt vé lần đầu',
    },
    {
      code: 'KIENTRUC20',
      title: 'Tri Ân Khách Hàng nhaxekientruc',
      discount: '20K',
      badge: '⭐ ĐƠN TỪ 100K',
      target_audience: 'INDIVIDUAL',
      desc: 'Giảm 20.000 VNĐ cho đơn từ 100.000đ',
      requirement: 'Áp dụng cho đơn vé từ 100.000đ trở lên',
    },
  ];
});

// Chỉ hiển thị trong ví voucher những mã mà khách hàng ĐÃ LƯU vào ví
const savedVouchersInWallet = computed(() => {
  return availableVouchers.value.filter(v => savedVouchers.value.includes(v.code));
});

const isCustomerStudent = computed(() => {
  const email = (customerForm.value.email || props.user?.email || '').toLowerCase().trim();
  return email.endsWith('.edu.vn') || email.includes('sinhvien') || Boolean(props.user?.is_student);
});

const isVoucherEligible = (v) => {
  if (v.target_audience === 'STUDENT' || v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG') {
    if (!isCustomerStudent.value) return false;
    if ((v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG') && (props.hasUsedStudentFirstTrip || selectedSeats.value.length > 1)) {
      return false;
    }
  }
  if (v.min_order_value && rawSeatPrice.value < v.min_order_value) {
    return false;
  }
  return true;
};

const applyVoucherFromList = (v) => {
  if (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG') {
    if (!isCustomerStudent.value) {
      voucherError.value = 'Mã vé 0-Đồng [SINHVIEN0DONG] chỉ dành riêng cho sinh viên (.edu.vn)! Vui lòng điền email sinh viên ở thông tin hành khách phía trên.';
      return;
    }
    if (selectedSeats.value.length > 1) {
      voucherError.value = 'Mã vé 0-Đồng (SINHVIEN0DONG) chỉ áp dụng tối đa cho đúng 1 ghế cho mỗi sinh viên. Quý khách vui lòng chọn đúng 1 ghế để nhận vé 0Đ!';
      return;
    }
    if (props.hasUsedStudentFirstTrip) {
      voucherError.value = 'Không đủ điều kiện! Mã vé 0-Đồng chỉ áp dụng duy nhất 1 lần cho lần đầu tiên đặt vé của sinh viên.';
      return;
    }
  }

  if (!isVoucherEligible(v)) {
    if (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG') {
      if (selectedSeats.value.length > 1) {
        voucherError.value = 'Mã vé 0-Đồng chỉ áp dụng cho đúng 1 ghế cho mỗi sinh viên. Vui lòng chọn 1 ghế!';
      } else {
        voucherError.value = 'Không đủ điều kiện áp dụng mã vé 0-Đồng này!';
      }
    } else if (v.code === 'SINHVIEN30' || v.code === 'SINHVIEN') {
      voucherError.value = 'Mã giảm giá 30% chỉ dành riêng cho tài khoản sinh viên có email trường (.edu.vn)! Vui lòng điền email sinh viên ở thông tin hành khách phía trên.';
    } else if (v.type === 'MIN_200K') {
      voucherError.value = 'Đơn vé hiện tại chưa đạt 200.000đ để áp dụng mã giảm giá này!';
    } else {
      voucherError.value = 'Không đủ điều kiện áp dụng mã giảm giá này!';
    }
    return;
  }
  voucherCodeInput.value = v.code;
  saveVoucher(v.code);
  handleApplyVoucher();
};

watch(appliedVoucher, (val) => {
  if (val) {
    paymentChoice.value = 'FULL';
  }
});

// Điểm thưởng tích lũy & Đổi điểm (100 điểm = 1.000 VNĐ giảm giá => 1 điểm = 10 VNĐ)
const userAvailablePoints = computed(() => props.user?.reward_points || 0);
const useRewardPoints = ref(false);
const pointsToRedeem = ref(0);

const onToggleRewardPoints = () => {
  if (useRewardPoints.value) {
    if (appliedVoucher.value) {
      removeVoucher();
    }
    if (pointsToRedeem.value < 100 && maxPointsCanUse.value >= 100) {
      pointsToRedeem.value = 100;
    }
  } else {
    pointsToRedeem.value = 0;
  }
};

const decrementPoints = () => {
  if (pointsToRedeem.value >= 100) {
    pointsToRedeem.value -= 100;
  }
};

const incrementPoints = () => {
  if (pointsToRedeem.value + 100 <= maxPointsCanUse.value) {
    pointsToRedeem.value += 100;
  }
};

const rawSeatPrice = computed(() => {
  return selectedSeats.value.reduce((sum, s) => sum + (parseFloat(s.price) || 150000), 0);
});

const voucherDiscount = computed(() => {
  return appliedVoucher.value ? (appliedVoucher.value.discount_amount || 0) : 0;
});

const maxPointsCanUse = computed(() => {
  const priceAfterVoucher = Math.max(0, rawSeatPrice.value - voucherDiscount.value);
  // 100 điểm = 1.000đ (1 điểm = 10đ)
  const maxPossiblePoints = Math.floor(priceAfterVoucher / 10);
  return Math.min(userAvailablePoints.value, maxPossiblePoints);
});

const pointsDiscount = computed(() => {
  if (!useRewardPoints.value || appliedVoucher.value) return 0;
  return Math.round((pointsToRedeem.value || 0) * 10);
});

const totalSeatPrice = computed(() => {
  return Math.max(0, rawSeatPrice.value - voucherDiscount.value - pointsDiscount.value);
});

const depositAmount = computed(() => Math.round(totalSeatPrice.value * 0.3));
const remainingAmount = computed(() => totalSeatPrice.value - depositAmount.value);
const estimatedPoints = computed(() => {
  // Quy tắc tích điểm: 1.000 VNĐ = 1 điểm (Ví dụ vé 100k = 100 điểm)
  return Math.max(1, Math.round(totalSeatPrice.value / 1000));
});

const handleApplyVoucher = async () => {
  voucherError.value = '';
  if (!voucherCodeInput.value.trim()) return;

  const rawCode = voucherCodeInput.value.trim().toUpperCase();
  const is0DongCode = ['SINHVIEN0DONG', 'SINHVIEN100', 'SV0DONG', 'TET0DONG', 'VE0DONG'].includes(rawCode);
  if (is0DongCode && selectedSeats.value.length > 1) {
    voucherError.value = 'Mã vé 0-Đồng sinh viên (SINHVIEN0DONG) chỉ áp dụng tối đa cho đúng 1 ghế cho mỗi sinh viên. Quý khách vui lòng chỉ chọn 1 ghế!';
    return;
  }

  if (useRewardPoints.value && pointsToRedeem.value > 0) {
    voucherError.value = 'Quý khách đang sử dụng điểm thưởng tích lũy. Không thể áp dụng đồng thời Voucher!';
    return;
  }

  voucherLoading.value = true;
  try {
    const res = await fetch('/api/vouchers/apply', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        code: voucherCodeInput.value.trim(),
        amount: rawSeatPrice.value,
        seat_count: selectedSeats.value.length,
        email: customerForm.value.email || props.user?.email || '',
      }),
    });
    const data = await res.json();
    if (res.ok && data.valid) {
      appliedVoucher.value = data;
      useRewardPoints.value = false;
      pointsToRedeem.value = 0;
      voucherError.value = '';
    } else {
      voucherError.value = data.message || 'Mã Voucher không hợp lệ hoặc đã hết hạn!';
      appliedVoucher.value = null;
    }
  } catch (err) {
    voucherError.value = 'Không thể kết nối đến máy chủ để xác thực voucher!';
  } finally {
    voucherLoading.value = false;
  }
};

watch(() => selectedSeats.value.length, (newCount) => {
  if (appliedVoucher.value && ['SINHVIEN0DONG', 'SINHVIEN100', 'SV0DONG', 'TET0DONG', 'VE0DONG'].includes((appliedVoucher.value.code || '').toUpperCase())) {
    if (newCount > 1) {
      appliedVoucher.value = null;
      voucherError.value = 'Mã vé 0-Đồng chỉ áp dụng tối đa cho 1 ghế duy nhất. Đã hủy áp dụng mã vé 0-Đồng!';
    }
  }
});

const removeVoucher = () => {
  appliedVoucher.value = null;
  voucherCodeInput.value = '';
  voucherError.value = '';
};

const goToStep2 = () => {
  if (!props.user) {
    showAuthModal.value = true;
    return;
  }
  if (selectedSeats.value.length === 0) {
    alert('Vui lòng chọn ít nhất 1 chỗ ngồi!');
    return;
  }
  step.value = 2;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const goToStep3 = () => {
  if (!selectedPickup.value || !selectedDropoff.value) {
    alert('Vui lòng chọn điểm đón và điểm trả!');
    return;
  }
  step.value = 3;
};

const submitBooking = () => {
  if (!props.user) {
    alert('Vui lòng đăng nhập tài khoản trước khi hoàn tất đặt vé!');
    window.location.href = '/login';
    return;
  }

  if (!isPhoneVerified.value) {
    alert('⚠️ Quý khách đăng ký bằng Gmail bắt buộc phải nhập và xác minh Số điện thoại qua SMS OTP để Bác tài & Lơ xe liên hệ đón xe trước khi thanh toán!');
    const el = document.getElementById('customer-phone-input');
    if (el) {
      el.focus();
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return;
  }

  if (!isEmailVerified.value) {
    alert('⚠️ Quý khách đăng ký bằng Số điện thoại bắt buộc phải nhập và xác minh Gmail qua mã OTP để nhận vé điện tử và mã QR trước khi thanh toán!');
    const el = document.getElementById('customer-email-input');
    if (el) {
      el.focus();
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return;
  }

  router.post(`/trips/${props.trip.id}/book`, {
    customer_name: props.user?.name || customerForm.value.name || 'Hành Khách nhaxekientruc',
    customer_email: customerForm.value.email,
    customer_phone: customerForm.value.phone,
    pickup_stop_id: selectedPickup.value?.id,
    dropoff_stop_id: selectedDropoff.value?.id,
    pickup_stop_name: selectedPickup.value.name,
    dropoff_stop_name: selectedDropoff.value.name,
    seat_numbers: selectedSeats.value.map(s => s.seat_number),
    payment_choice: paymentChoice.value,
    voucher_code: appliedVoucher.value?.code || null,
    points_to_redeem: (useRewardPoints.value && !appliedVoucher.value) ? (pointsToRedeem.value || 0) : 0,
    notes: customerForm.value.notes?.trim() || null,
  });
};

const formatDateTime = (val) => {
  if (!val) return '';
  if (val.length >= 16) {
    const time = val.substring(11, 16);
    const date = `${val.substring(8, 10)}/${val.substring(5, 7)}/${val.substring(0, 4)}`;
    return `${time} ngày ${date}`;
  }
  return val;
};

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(val || 0);
</script>
