<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-800 font-sans pb-24 md:pb-0">
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

          <!-- ==================================================================== -->
          <!-- 🎯 AI SMART SEAT MATCHER: GỢI Ý CHỖ NGỒI THEO THỂ TRẠNG & THÓI QUEN -->
          <!-- ==================================================================== -->
          <div class="rounded-3xl border-2 border-amber-400/80 bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-amber-500/15 p-4 sm:p-5 shadow-lg shadow-amber-500/10 space-y-4">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-600 text-white flex items-center justify-center text-xl shadow-md shadow-amber-500/30 shrink-0">
                  🎯
                </div>
                <div>
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2 py-0.5 rounded-full bg-amber-500 text-slate-950 font-black text-[10px] uppercase tracking-wider">AI RECOMMENDATION</span>
                    <h3 class="font-black text-sm sm:text-base text-slate-900 tracking-tight">AI Chọn Giường Theo Thể Trạng & Thói Quen</h3>
                  </div>
                  <p class="text-xs text-slate-500 mt-0.5">Nói hoặc tick chọn tình trạng của bạn để AI tự động phát sáng vị trí giường êm nhất</p>
                </div>
              </div>

              <!-- Nút đóng/mở nhanh bộ lọc AI -->
              <button
                type="button"
                @click="isAiSeatMatcherOpen = !isAiSeatMatcherOpen"
                class="self-start sm:self-auto px-3 py-1.5 rounded-xl border border-amber-300 bg-white/80 hover:bg-white text-amber-900 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs"
              >
                <span>{{ isAiSeatMatcherOpen ? 'Thu gọn' : 'Mở trợ lý AI' }}</span>
                <i class="fa-solid text-[10px]" :class="isAiSeatMatcherOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
              </button>
            </div>

            <!-- Khung tương tác AI khi mở -->
            <div v-show="isAiSeatMatcherOpen" class="space-y-3.5 pt-1 border-t border-amber-200/60">
              
              <!-- 1. Thanh Nhập Câu Lệnh Tự Nhiên & Nút Micro Thu Âm -->
              <div class="flex items-center gap-2">
                <div class="relative flex-1">
                  <input
                    type="text"
                    v-model="aiCustomPrompt"
                    @keyup.enter="handleAiPromptSubmit"
                    placeholder="Nhập nhu cầu (vd: Say xe nặng, Đi 2 người, Người già, Cao 1m8...)"
                    class="w-full pl-3.5 pr-10 py-2.5 rounded-2xl bg-white border border-amber-300 text-xs font-bold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 shadow-inner"
                  />
                  <button
                    type="button"
                    @click="startVoiceRecognition"
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-7 h-7 rounded-xl flex items-center justify-center transition cursor-pointer"
                    :class="isListeningVoice ? 'bg-red-500 text-white animate-pulse shadow-md' : 'bg-amber-100 hover:bg-amber-200 text-amber-800'"
                    :title="isListeningVoice ? 'Đang lắng nghe bạn nói...' : 'Bấm để nói bằng giọng nói'"
                  >
                    <i class="fa-solid fa-microphone text-xs"></i>
                  </button>
                </div>

                <button
                  type="button"
                  @click="handleAiPromptSubmit"
                  class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-black text-xs transition shadow-md shadow-amber-500/25 active:scale-95 cursor-pointer shrink-0 flex items-center gap-1"
                >
                  <i class="fa-solid fa-wand-magic-sparkles text-[11px]"></i>
                  <span class="hidden sm:inline">AI Chọn Giúp</span>
                </button>
              </div>

              <!-- 2. Danh Sách 5 Thẻ Nhanh (Preset Tags) -->
              <div class="flex items-center gap-2 flex-wrap">
                <button
                  v-for="p in AI_PRESETS"
                  :key="p.id"
                  type="button"
                  @click="selectAiPreset(p.id)"
                  class="px-3 py-2 rounded-2xl text-xs font-bold transition flex items-center gap-1.5 border cursor-pointer active:scale-95"
                  :class="activeAiSeatMode === p.id 
                    ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white border-amber-500 shadow-md shadow-amber-500/30 scale-105' 
                    : 'bg-white hover:bg-amber-50 text-slate-700 border-amber-200/90 shadow-2xs'"
                >
                  <span class="text-base">{{ p.icon }}</span>
                  <div class="text-left">
                    <span class="block font-black leading-tight">{{ p.label }}</span>
                    <span class="text-[9.5px] opacity-80 block leading-tight font-normal">{{ p.sublabel }}</span>
                  </div>
                </button>
              </div>

              <!-- 3. Hộp Giải Thích Lý Do AI Khuyên Chọn & Nút 1-Click Áp Dụng -->
              <div v-if="aiRecommendation" class="p-3.5 rounded-2xl bg-white/95 border-2 border-amber-400 shadow-md space-y-2.5 animate-in fade-in slide-in-from-top-1">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-start gap-2.5">
                    <span class="text-2xl mt-0.5">🤖</span>
                    <div>
                      <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-black text-xs text-amber-900 uppercase tracking-wide">AI KHUYÊN BẠN NÊN CHỌN:</span>
                        <div class="flex items-center gap-1">
                          <span 
                            v-for="sNum in aiRecommendation.seats" 
                            :key="sNum"
                            class="px-2 py-0.5 rounded-lg bg-red-600 text-white font-mono font-black text-xs shadow-xs"
                          >
                            {{ sNum }}
                          </span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">{{ aiRecommendation.score }}</span>
                      </div>
                      <p class="text-xs text-slate-700 mt-1 leading-relaxed">
                        {{ aiRecommendation.reason }}
                      </p>
                    </div>
                  </div>

                  <button 
                    type="button" 
                    @click="clearAiRecommendation" 
                    class="w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 text-xs font-bold flex items-center justify-center cursor-pointer shrink-0"
                    title="Hủy bộ lọc AI"
                  >
                    ✕
                  </button>
                </div>

                <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 flex-wrap">
                  <button
                    type="button"
                    @click="openVirtualSeatPreview(aiRecommendation)"
                    class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer flex items-center gap-1.5 active:scale-95 border border-slate-200"
                  >
                    <span>🛏️</span>
                    <span>Xem Kích Thước & Tiện Ích Giường</span>
                  </button>

                  <button
                    type="button"
                    @click="applyAiRecommendedSeats"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-xs shadow-md shadow-emerald-500/25 transition active:scale-95 cursor-pointer flex items-center gap-1.5"
                  >
                    <i class="fa-solid fa-circle-check text-xs"></i>
                    <span>Tự Động Chọn Ghế Này Ngay ➔</span>
                  </button>
                </div>
              </div>

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

            <!-- Mobile Floor Segmented Switcher (md:hidden) -->
            <div class="md:hidden flex items-center p-1 bg-slate-200/90 rounded-2xl mb-1 text-xs font-black shadow-inner">
              <button
                type="button"
                @click="activeMobileFloor = 'f1'"
                :class="activeMobileFloor === 'f1' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                class="flex-1 py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>🛏️ Tầng 1 (Dưới)</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold font-mono" :class="activeMobileFloor === 'f1' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-300 text-slate-700'">18 Chỗ</span>
              </button>
              <button
                type="button"
                @click="activeMobileFloor = 'f2'"
                :class="activeMobileFloor === 'f2' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                class="flex-1 py-2.5 rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer"
              >
                <span>🛏️ Tầng 2 (Trên)</span>
                <span class="text-[10px] px-1.5 py-0.2 rounded-full font-bold font-mono" :class="activeMobileFloor === 'f2' ? 'bg-blue-50 text-blue-700' : 'bg-slate-300 text-slate-700'">12 Chỗ</span>
              </button>
            </div>

            <!-- Seat Grids: Tầng 1 & Tầng 2 Song Song Sân Khấu Rộng Rãi -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
              
              <!-- TẦNG 1: DÃY A (18 Giường VIP Dưới) -->
              <div 
                class="bg-white p-4.5 rounded-2xl border border-slate-200 text-center flex flex-col justify-start shadow-xs space-y-3"
                :class="{ 'hidden md:flex': activeMobileFloor !== 'f1' }"
              >
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-tight">TẦNG 1 (DÃY A)</span>
                  </div>
                  <span class="px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-800 font-mono font-black text-xs">
                    {{ formatPrice(trip.floor1_price || trip.fare || 140000) }}đ
                  </span>
                </div>

                <!-- Tiện nghi Tầng 1 -->
                <div class="p-2 rounded-xl bg-emerald-50/70 border border-emerald-200/60 text-left space-y-1">
                  <div class="text-[10px] font-black text-emerald-900 uppercase flex items-center justify-between">
                    <span>✨ Tiện Nghi Giường Tầng 1 (Dưới)</span>
                    <span class="text-[9px] font-bold text-emerald-700 bg-white px-1.5 py-0.2 rounded border border-emerald-200">Giảm xóc 98%</span>
                  </div>
                  <div class="flex flex-wrap gap-1 text-[10px] text-slate-700 font-medium">
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-emerald-100 flex items-center gap-1">🎁 Tặng 1 chai nước</span>
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-emerald-100 flex items-center gap-1">❄️ Khăn lạnh</span>
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-emerald-100 flex items-center gap-1">🔌 Sạc Type-C & USB</span>
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-emerald-100 flex items-center gap-1">📺 TV LED</span>
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-emerald-100 flex items-center gap-1 text-emerald-800 font-bold">🌀 Giảm xóc êm nhất</span>
                  </div>
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
                            : isAiRecommended(seat.seat_number)
                              ? 'bg-amber-50/95 border-amber-500 ring-4 ring-amber-400 text-slate-900 shadow-lg shadow-amber-400/40 scale-[1.03] animate-pulse z-10'
                              : 'bg-white border-slate-200 text-slate-700 hover:border-red-400 hover:shadow-md hover:-translate-y-0.5 active:scale-95'
                      ]"
                    >
                      <!-- Badge AI Khuyên Chọn -->
                      <span 
                        v-if="isAiRecommended(seat.seat_number) && !isSeatSelected(seat.seat_number)" 
                        class="absolute -top-2.5 px-2 py-0.2 rounded-full bg-gradient-to-r from-amber-500 to-orange-500 text-white font-black text-[8px] uppercase tracking-wider shadow-sm z-20 animate-bounce"
                      >
                        ⭐ AI Khuyên
                      </span>

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
                      <div class="text-[9.5px] font-bold leading-tight" :class="isSeatSelected(seat.seat_number) ? 'text-white/95' : (seat.status === 'booked' ? 'text-slate-400 font-normal' : (isAiRecommended(seat.seat_number) ? 'text-amber-800 font-black' : 'text-emerald-700'))">
                        {{ seat.status === 'booked' ? 'Đã bán' : (isSeatSelected(seat.seat_number) ? '✓ Đã chọn' : (isAiRecommended(seat.seat_number) ? 'Khuyên chọn' : (seat.price ? formatPrice(seat.price) + 'đ' : 'Trống'))) }}
                      </div>
                    </button>
                  </template>
                </div>
              </div>

              <!-- TẦNG 2: DÃY B (12 Giường Tiêu Chuẩn 2 Bên Cửa Sổ) -->
              <div 
                class="bg-white p-4.5 rounded-2xl border border-slate-200 text-center flex flex-col justify-start shadow-xs space-y-3"
                :class="{ 'hidden md:flex': activeMobileFloor !== 'f2' }"
              >
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <span class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-tight">TẦNG 2 (DÃY B)</span>
                  </div>
                  <span class="px-2.5 py-0.5 rounded-lg bg-blue-100 text-blue-800 font-mono font-black text-xs">
                    {{ formatPrice(trip.floor2_price || (trip.fare ? trip.fare - 10000 : 130000)) }}đ
                  </span>
                </div>

                <!-- Tiện nghi Tầng 2 -->
                <div class="p-2 rounded-xl bg-blue-50/70 border border-blue-200/60 text-left space-y-1">
                  <div class="text-[10px] font-black text-blue-900 uppercase flex items-center justify-between">
                    <span>✨ Tiện Nghi Giường Tầng 2 (Trên)</span>
                    <span class="text-[9px] font-bold text-blue-700 bg-white px-1.5 py-0.2 rounded border border-blue-200">Riêng tư 100%</span>
                  </div>
                  <div class="flex flex-wrap gap-1 text-[10px] text-slate-700 font-medium">
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-blue-100 flex items-center gap-1">🎁 Tặng 1 chai nước</span>
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-blue-100 flex items-center gap-1">❄️ Khăn lạnh</span>
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-blue-100 flex items-center gap-1">🔌 Sạc USB</span>
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-blue-100 flex items-center gap-1">📺 TV LED</span>
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-blue-100 flex items-center gap-1 text-blue-800 font-black">🎪 Rèm che riêng tư 100%</span>
                    <span class="bg-white/90 px-1.5 py-0.5 rounded border border-blue-100 flex items-center gap-1">🪟 Cửa sổ ngắm cảnh</span>
                  </div>
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
                            : isAiRecommended(row.left.seat_number)
                              ? 'bg-amber-50/95 border-amber-500 ring-4 ring-amber-400 text-slate-900 shadow-lg shadow-amber-400/40 scale-[1.03] animate-pulse z-10'
                              : 'bg-white border-slate-200 text-slate-700 hover:border-red-400 hover:shadow-md hover:-translate-y-0.5 active:scale-95'
                      ]"
                    >
                      <!-- Badge AI Khuyên Chọn -->
                      <span 
                        v-if="isAiRecommended(row.left.seat_number) && !isSeatSelected(row.left.seat_number)" 
                        class="absolute -top-2.5 px-2 py-0.2 rounded-full bg-gradient-to-r from-amber-500 to-orange-500 text-white font-black text-[8px] uppercase tracking-wider shadow-sm z-20 animate-bounce"
                      >
                        ⭐ AI Khuyên
                      </span>

                      <div 
                        class="w-7 h-1.5 rounded-full transition"
                        :class="isSeatSelected(row.left.seat_number) ? 'bg-white/40' : (row.left.status === 'booked' ? 'bg-slate-200' : 'bg-slate-200 group-hover:bg-red-200')"
                      ></div>
                      <div class="font-black text-sm sm:text-base leading-none" :class="isSeatSelected(row.left.seat_number) ? 'text-white' : (row.left.status === 'booked' ? 'text-slate-400' : 'text-slate-900')">
                        {{ row.left.seat_number }}
                      </div>
                      <div class="text-[9.5px] font-bold leading-tight" :class="isSeatSelected(row.left.seat_number) ? 'text-white/95' : (row.left.status === 'booked' ? 'text-slate-400 font-normal' : (isAiRecommended(row.left.seat_number) ? 'text-amber-800 font-black' : 'text-blue-700'))">
                        {{ row.left.status === 'booked' ? 'Đã bán' : (isSeatSelected(row.left.seat_number) ? '✓ Đã chọn' : (isAiRecommended(row.left.seat_number) ? 'Khuyên chọn' : (row.left.price ? formatPrice(row.left.price) + 'đ' : 'Trống'))) }}
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
                            : isAiRecommended(row.right.seat_number)
                              ? 'bg-amber-50/95 border-amber-500 ring-4 ring-amber-400 text-slate-900 shadow-lg shadow-amber-400/40 scale-[1.03] animate-pulse z-10'
                              : 'bg-white border-slate-200 text-slate-700 hover:border-red-400 hover:shadow-md hover:-translate-y-0.5 active:scale-95'
                      ]"
                    >
                      <!-- Badge AI Khuyên Chọn -->
                      <span 
                        v-if="isAiRecommended(row.right.seat_number) && !isSeatSelected(row.right.seat_number)" 
                        class="absolute -top-2.5 px-2 py-0.2 rounded-full bg-gradient-to-r from-amber-500 to-orange-500 text-white font-black text-[8px] uppercase tracking-wider shadow-sm z-20 animate-bounce"
                      >
                        ⭐ AI Khuyên
                      </span>

                      <div 
                        class="w-7 h-1.5 rounded-full transition"
                        :class="isSeatSelected(row.right.seat_number) ? 'bg-white/40' : (row.right.status === 'booked' ? 'bg-slate-200' : 'bg-slate-200 group-hover:bg-red-200')"
                      ></div>
                      <div class="font-black text-sm sm:text-base leading-none" :class="isSeatSelected(row.right.seat_number) ? 'text-white' : (row.right.status === 'booked' ? 'text-slate-400' : 'text-slate-900')">
                        {{ row.right.seat_number }}
                      </div>
                      <div class="text-[9.5px] font-bold leading-tight" :class="isSeatSelected(row.right.seat_number) ? 'text-white/95' : (row.right.status === 'booked' ? 'text-slate-400 font-normal' : (isAiRecommended(row.right.seat_number) ? 'Khuyên chọn' : 'text-blue-700'))">
                        {{ row.right.status === 'booked' ? 'Đã bán' : (isSeatSelected(row.right.seat_number) ? '✓ Đã chọn' : (isAiRecommended(row.right.seat_number) ? 'Khuyên chọn' : (row.right.price ? formatPrice(row.right.price) + 'đ' : 'Trống'))) }}
                      </div>
                    </button>
                  </template>
                </div>
              </div>

            </div>

          </div>

          <!-- Chú Thích Ghế Ngồi (Seat Legend) -->
          <div class="border-t border-slate-100 pt-4 space-y-3">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-slate-600 font-bold text-center">
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
              <div class="flex items-center justify-center space-x-2 p-2 rounded-xl bg-amber-50 border border-amber-300">
                <div class="w-4 h-4 rounded-md bg-amber-100 border-2 border-amber-500 shadow-xs ring-2 ring-amber-300"></div>
                <span class="text-amber-800 font-black">⭐ AI Khuyên</span>
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
                  {{ (cleanCustomerName || 'KH').substring(0, 2).toUpperCase() }}
                </div>
                <div>
                  <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Hành Khách Đặt Vé</div>
                  <div class="text-sm font-black text-slate-900 leading-snug">{{ cleanCustomerName }}</div>
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
                <!-- Nếu tài khoản chưa có SĐT (Đăng ký bằng Gmail): Cho phép nhập và xác minh bằng eSMS OTP -->
                <div v-else class="space-y-2">
                  <input
                    id="customer-phone-input"
                    v-model="customerForm.phone"
                    required
                    type="tel"
                    placeholder="VD: 0904 233 370"
                    class="w-full px-3.5 py-2.5 rounded-xl border text-base sm:text-xs font-bold text-slate-900 focus:outline-none transition shadow-2xs"
                    :class="!isPhoneValid ? 'border-amber-400 bg-amber-50/50 ring-2 ring-amber-400/20' : 'border-slate-300 focus:border-red-500'"
                  />

                  <!-- Khối nút gửi và nhập OTP SMS -->
                  <div v-if="!showPhoneOtpInput" class="space-y-2">
                    <button
                      type="button"
                      @click="handleSendPhoneOtp('sms')"
                      :disabled="!isPhoneValid || phoneOtpSending"
                      class="w-full py-2.5 px-3 rounded-xl bg-red-600 hover:bg-red-700 disabled:bg-slate-300 text-white font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5 shadow-xs disabled:cursor-not-allowed"
                    >
                      <span v-if="phoneOtpSending" class="animate-spin text-xs">⏳</span>
                      <span>💬 Nhận mã xác minh qua tin nhắn SMS</span>
                    </button>
                  </div>
                  <div v-else class="p-2.5 rounded-xl bg-amber-50 border border-amber-300 space-y-2 animate-in fade-in">
                    <div class="text-[11px] font-bold text-slate-800">
                      Mã OTP đã gửi tới <span class="font-mono text-red-600">{{ customerForm.phone }}</span> qua tin nhắn SMS. Vui lòng nhập 6 số:
                    </div>
                    <div class="flex gap-2">
                      <input
                        v-model="phoneOtpCode"
                        maxlength="6"
                        type="text"
                        inputmode="numeric"
                        placeholder="6 số OTP"
                        class="flex-1 px-3 py-2 rounded-xl border border-slate-300 font-mono font-black text-center text-base sm:text-sm focus:border-red-500 bg-white"
                      />

                      <button
                        type="button"
                        @click="handleVerifyPhoneOtp"
                        :disabled="phoneOtpCode.length < 6 || phoneOtpVerifying"
                        class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold text-xs cursor-pointer shadow-xs disabled:cursor-not-allowed whitespace-nowrap"
                      >
                        {{ phoneOtpVerifying ? 'Đang kiểm tra...' : 'Xác Minh' }}
                      </button>
                    </div>
                    <div class="flex items-center justify-between text-[11px] pt-1">
                      <button
                        type="button"
                        @click="handleSendPhoneOtp(phoneMethod)"
                        :disabled="phoneCountdown > 0 || phoneOtpSending"
                        class="text-red-600 hover:underline font-bold disabled:text-slate-400 cursor-pointer disabled:cursor-not-allowed"
                      >
                        {{ phoneCountdown > 0 ? `Gửi lại sau (${phoneCountdown}s)` : 'Chưa nhận được? Bấm gửi lại mã' }}
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
                        @click="handleVerifyEmailOtp"
                        :disabled="emailOtpCode.length < 6 || emailOtpVerifying"
                        class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 text-white font-bold text-xs cursor-pointer shadow-xs disabled:cursor-not-allowed whitespace-nowrap"
                      >
                        {{ emailOtpVerifying ? 'Đang kiểm tra...' : 'Xác Minh' }}
                      </button>
                    </div>
                    <div class="flex items-center justify-between text-[11px] pt-1">
                      <button
                        type="button"
                        @click="handleSendEmailOtp"
                        :disabled="emailCountdown > 0 || emailOtpSending"
                        class="text-blue-600 hover:underline font-bold disabled:text-slate-400 cursor-pointer disabled:cursor-not-allowed"
                      >
                        {{ emailCountdown > 0 ? `Gửi lại sau (${emailCountdown}s)` : 'Chưa nhận được mã? Gửi lại OTP' }}
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
                  class="w-full pl-9 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl text-base sm:text-xs font-mono font-bold uppercase placeholder:normal-case placeholder:font-normal focus:border-red-500 focus:outline-none"
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
                          v-if="v.is_active === false"
                          class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-200 text-slate-600"
                        >
                          🚫 Tạm ngưng
                        </span>
                        <span 
                          v-else-if="String(props.user?.membership_plan || '').toUpperCase().includes('ULTRA') && (v.code.includes('30') || v.code === 'SINHVIEN')"
                          class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800"
                        >
                          🚫 Không khả dụng (Ultra 40%)
                        </span>
                        <span 
                          v-else-if="props.hasUsedStudentFirstTrip && (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG')"
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
                      <span
                        v-if="v.is_active === false"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-400 text-[10px] font-bold border border-slate-200"
                      >
                        🚫 Tạm ngưng
                      </span>
                      <span
                        v-else-if="String(props.user?.membership_plan || '').toUpperCase().includes('ULTRA') && (v.code.includes('30') || v.code === 'SINHVIEN')"
                        class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-400 text-[10px] font-bold border border-slate-200"
                      >
                        Không khả dụng
                      </span>
                      <button
                        v-else-if="!isVoucherSaved(v.code)"
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
                          :class="v.is_active === false
                            ? 'bg-slate-200 text-slate-600'
                            : (String(props.user?.membership_plan || '').toUpperCase().includes('ULTRA') && (v.code.includes('30') || v.code === 'SINHVIEN')
                              ? 'bg-amber-100 text-amber-800'
                              : ((props.hasUsedStudentFirstTrip && (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG'))
                                ? 'bg-rose-100 text-rose-700'
                                : (isVoucherEligible(v) ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-200 text-slate-600')))"
                        >
                          {{ v.is_active === false
                            ? '🚫 Tạm ngưng'
                            : (String(props.user?.membership_plan || '').toUpperCase().includes('ULTRA') && (v.code.includes('30') || v.code === 'SINHVIEN')
                              ? '🚫 Không khả dụng (Ultra 40%)'
                              : ((props.hasUsedStudentFirstTrip && (v.code === 'SINHVIEN100' || v.code === 'SINHVIEN0DONG'))
                                ? '🚫 Đã hết lượt'
                                : (isVoucherEligible(v) ? '✓ Khả dụng' : '⚠️ Chưa đủ ĐK'))) }}
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
                        :disabled="!isVoucherEligible(v)"
                        class="px-3 py-1 rounded-lg text-[10.5px] font-black transition shadow-2xs"
                        :class="isVoucherEligible(v)
                          ? 'bg-red-600 hover:bg-red-700 text-white cursor-pointer'
                          : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                      >
                        {{ v.is_active === false ? 'Tạm ngưng' : (String(props.user?.membership_plan || '').toUpperCase().includes('ULTRA') && (v.code.includes('30') || v.code === 'SINHVIEN') ? 'Không khả dụng' : 'Áp Dụng') }}
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

    <!-- ============================================================== -->
    <!-- THANH TÁC VỤ ĐẶT VÉ NỔI CỐ ĐỊNH Ở ĐÁY MÀN HÌNH ĐIỆN THOẠI (MOBILE CHECKOUT BAR) -->
    <!-- ============================================================== -->
    <div 
      v-if="!is_expired && !['RUNNING', 'IN_TRANSIT', 'COMPLETED'].includes(trip.status)"
      class="lg:hidden fixed bottom-0 inset-x-0 z-50 bg-white/95 backdrop-blur-md border-t border-slate-200 px-4 py-3 shadow-[0_-4px_25px_rgba(0,0,0,0.15)] flex items-center justify-between gap-3 animate-in slide-in-from-bottom-2 duration-200"
    >
      <!-- Khi ở Bước 1: Chọn Chỗ Ngồi -->
      <template v-if="step === 1">
        <div class="min-w-0 flex-1">
          <div class="text-[11px] font-bold text-slate-500 flex items-center gap-1.5">
            <span>💺 Đã chọn:</span>
            <span v-if="selectedSeats.length > 0" class="text-red-600 font-black font-mono">
              {{ selectedSeats.map(s => s.seat_number).join(', ') }} ({{ selectedSeats.length }})
            </span>
            <span v-else class="text-slate-400 italic">Chưa chọn ghế</span>
          </div>
          <div class="text-base font-black text-slate-900 font-mono leading-tight mt-0.5">
            {{ formatPrice(totalSeatPrice) }}đ
          </div>
        </div>

        <button
          type="button"
          @click="goToStep2"
          :disabled="selectedSeats.length === 0"
          class="px-5 py-3 rounded-2xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 disabled:from-slate-200 disabled:to-slate-200 text-white disabled:text-slate-400 font-black text-xs shadow-lg shadow-red-500/25 transition active:scale-95 cursor-pointer disabled:cursor-not-allowed flex items-center gap-1.5 shrink-0"
        >
          <span>Tiếp Tục</span>
          <span class="text-sm">➔</span>
        </button>
      </template>

      <!-- Khi ở Bước 2: Chọn Điểm Đón & Trả -->
      <template v-else-if="step === 2">
        <button
          type="button"
          @click="step = 1"
          class="px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-slate-700 font-bold text-xs active:scale-95 transition cursor-pointer shrink-0"
        >
          ← Ghế
        </button>

        <div class="min-w-0 flex-1 text-center">
          <div class="text-[10.5px] font-bold text-slate-600 truncate">
            Đón: {{ selectedPickup?.name || 'Chưa chọn' }}
          </div>
          <div class="text-[10.5px] font-bold text-emerald-600 truncate">
            Trả: {{ selectedDropoff?.name || 'Chưa chọn' }}
          </div>
        </div>

        <button
          type="button"
          @click="goToStep3"
          :disabled="!selectedPickup || !selectedDropoff"
          class="px-5 py-3 rounded-2xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 disabled:from-slate-200 disabled:to-slate-200 text-white disabled:text-slate-400 font-black text-xs shadow-lg shadow-red-500/25 transition active:scale-95 cursor-pointer disabled:cursor-not-allowed flex items-center gap-1.5 shrink-0"
        >
          <span>Tiếp Tục</span>
          <span class="text-sm">➔</span>
        </button>
      </template>

      <!-- Khi ở Bước 3: Thông tin & Thanh toán -->
      <template v-else>
        <button
          type="button"
          @click="step = 2"
          class="px-3.5 py-3 rounded-2xl border border-slate-200 bg-slate-50 text-slate-700 font-bold text-xs active:scale-95 transition cursor-pointer shrink-0"
        >
          ← Trạm
        </button>

        <div class="min-w-0 flex-1">
          <div class="text-[10px] font-bold text-slate-500">
            {{ paymentChoice === 'DEPOSIT' ? 'Thanh toán Cọc 30%' : 'Thanh toán 100%' }}
          </div>
          <div class="text-base font-black text-red-600 font-mono leading-tight mt-0.5">
            {{ formatPrice(paymentChoice === 'DEPOSIT' ? depositAmount : totalSeatPrice) }}đ
          </div>
        </div>

        <button
          type="button"
          @click="submitBooking"
          :disabled="bookingSubmitting"
          class="px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 disabled:from-slate-300 disabled:to-slate-300 text-white font-black text-xs shadow-lg shadow-emerald-500/25 transition active:scale-95 cursor-pointer disabled:cursor-not-allowed flex items-center gap-1.5 shrink-0"
        >
          <span v-if="bookingSubmitting" class="animate-spin text-xs">⏳</span>
          <span>{{ bookingSubmitting ? 'Đang Xử Lý...' : 'Đặt Vé Ngay ➔' }}</span>
        </button>
      </template>
    </div>

    <Footer />
  </div>

  <!-- MODAL VIRTUAL SEAT PREVIEW (MÔ PHỎNG CHI TIẾT GIƯỜNG NẰM & THÔNG SỐ AI) -->
  <div 
    v-if="showVirtualSeatModal"
    class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-200"
    @click.self="showVirtualSeatModal = false"
  >
    <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-red-950 text-white p-5 flex items-start justify-between">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-2xl shadow-inner">
            🛏️
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Xe Limousine VIP 34 Phòng</span>
              <span class="px-2 py-0.2 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 text-[10px] font-mono font-bold">Chuẩn Châu Âu</span>
            </div>
            <h3 class="text-lg font-black tracking-tight mt-0.5">
              Chi Tiết Khoang Giường {{ previewingSeat?.seat_number ? previewingSeat.seat_number : (previewingSeat?.seats ? previewingSeat.seats.join(', ') : 'VIP') }}
            </h3>
          </div>
        </div>
        <button 
          type="button" 
          @click="showVirtualSeatModal = false"
          class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center cursor-pointer transition"
        >
          ✕
        </button>
      </div>

      <!-- Body with scroll -->
      <div class="p-5 space-y-4 overflow-y-auto">
        <!-- Mô phỏng không gian khoang giường -->
        <div class="rounded-2xl p-4 bg-gradient-to-br from-amber-50/80 via-orange-50/40 to-slate-50 border border-amber-200/80 space-y-2.5">
          <div class="flex items-center justify-between text-xs">
            <span class="font-black text-amber-900 uppercase tracking-wide flex items-center gap-1.5">
              <span>⭐ Phân Tích Thể Trạng Thích Hợp</span>
            </span>
            <span class="font-mono font-black text-xs text-red-600 bg-red-100 px-2.5 py-0.5 rounded-lg">
              {{ previewingSeat?.score || '98% Điểm Hài Lòng' }}
            </span>
          </div>
          <p class="text-xs text-slate-700 leading-relaxed font-medium">
            {{ previewingSeat?.reason || 'Khoang giường thiết kế công thái học (Ergonomic), giảm tối đa áp lực cột sống cho chuyến đi dài Đà Nẵng ↔ Huế / Đông Hà / Quy Nhơn.' }}
          </p>
        </div>

        <!-- Thông số kích thước & công nghệ -->
        <div class="space-y-2">
          <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">📐 Thông Số Kỹ Thuật Khoang Giường</h4>
          <div class="grid grid-cols-2 gap-2.5 text-xs">
            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
              <div class="text-[11px] text-slate-500 font-bold flex items-center gap-1">
                <span>📏 Kích thước lọt lòng:</span>
              </div>
              <div class="font-black text-slate-900 text-sm font-mono">185cm × 68cm</div>
              <div class="text-[10px] text-slate-500">Người cao đến 1m85 nằm duỗi thẳng chân thoải mái</div>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
              <div class="text-[11px] text-slate-500 font-bold flex items-center gap-1">
                <span>🌀 Giảm chấn khí nén:</span>
              </div>
              <div class="font-black text-emerald-600 text-sm font-mono">98% Êm Ái</div>
              <div class="text-[10px] text-slate-500">Hệ thống bầu hơi đa điểm triệt tiêu dao động cua đèo</div>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
              <div class="text-[11px] text-slate-500 font-bold flex items-center gap-1">
                <span>🔌 Nguồn điện & Cổng sạc:</span>
              </div>
              <div class="font-black text-slate-900 text-sm font-mono">Type-C PD & USB 20W</div>
              <div class="text-[10px] text-slate-500">Sạc nhanh điện thoại, iPad, laptop không lo hết pin</div>
            </div>

            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
              <div class="text-[11px] text-slate-500 font-bold flex items-center gap-1">
                <span>🎪 Độ riêng tư & Cách âm:</span>
              </div>
              <div class="font-black text-slate-900 text-sm font-mono">Rèm Che 100% UV</div>
              <div class="text-[10px] text-slate-500">Rèm kéo kín đáo ngăn ánh sáng đèn đường ban đêm</div>
            </div>
          </div>
        </div>

        <!-- Các tiện ích khác đi kèm -->
        <div class="p-3 rounded-2xl bg-blue-50/60 border border-blue-200/70 space-y-2">
          <h4 class="text-xs font-black text-blue-900 uppercase tracking-wider flex items-center gap-1.5">
            <span>🎁 Tiện Ích Miễn Phí Tại Chỗ</span>
          </h4>
          <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-700 font-medium">
            <span class="flex items-center gap-1.5"><span>💧</span> Nước suối & khăn lạnh</span>
            <span class="flex items-center gap-1.5"><span>📶</span> Wifi 5G tốc độ cao</span>
            <span class="flex items-center gap-1.5"><span>❄️</span> Cửa gió máy lạnh độc lập</span>
            <span class="flex items-center gap-1.5"><span>💡</span> Đèn đọc sách ấm 3000K</span>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3">
        <button
          type="button"
          @click="showVirtualSeatModal = false"
          class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-100 transition cursor-pointer"
        >
          Đóng
        </button>
        <button
          type="button"
          @click="applyAiRecommendedSeats(); showVirtualSeatModal = false;"
          class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs shadow-md shadow-red-500/25 transition active:scale-95 cursor-pointer flex items-center gap-1.5"
        >
          <span>✓ Chọn Giường Này & Tiếp Tục</span>
        </button>
      </div>
    </div>
  </div>

  <AiChatModal />
  <AuthModal v-model="showAuthModal" />
</template>

<script setup>
import AiChatModal from '@/Components/AiChatModal.vue';
import AuthModal from '@/Components/AuthModal.vue';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';

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
const activeMobileFloor = ref('f1');
const selectedSeats = ref([]);
const selectedPickup = ref(props.pickupStops?.[0] || null);
const selectedDropoff = ref(props.dropoffStops?.[props.dropoffStops.length - 1] || null);
const paymentChoice = ref('DEPOSIT');

// ==================== 🎯 AI SMART SEAT MATCHER & VOICE ASSISTANT ====================
const isAiSeatMatcherOpen = ref(true);
const activeAiSeatMode = ref(''); // 'say_xe', 'ngu_say', 'cap_doi', 'nguoi_gia', 'chan_dai'
const aiCustomPrompt = ref('');
const isListeningVoice = ref(false);
const aiRecommendation = ref(null);
const showVirtualSeatModal = ref(false);
const previewingSeat = ref(null);

const AI_PRESETS = [
  {
    id: 'say_xe',
    icon: '🤢',
    label: 'Say Xe / Chóng Mặt',
    sublabel: 'Trọng tâm xe, ít lắc',
    seats: ['A02', 'A05'],
    floor: 'f1',
    reason: 'Vị trí trọng tâm xe, cách xa bánh xe, ít rung lắc nhất khi xe ôm cua đèo dốc hoặc phanh gấp. Gần cửa gió điều hòa dịu nhẹ, giảm 80% cảm giác say xe.',
    score: '98% Giảm Say Xe',
    specs: { size: '185cm x 68cm', shockproof: '99% Êm ái', privacy: 'Rèm che riêng tư', charger: 'Type-C sạc nhanh' }
  },
  {
    id: 'ngu_say',
    icon: '😴',
    label: 'Ngủ Say / Yên Tĩnh',
    sublabel: 'Tầng 2, tránh người qua lại',
    seats: ['B04', 'B07'],
    floor: 'f2',
    reason: 'Khoang tầng 2 sát cửa sổ cách biệt hoàn toàn luồng di chuyển lên xuống của khách. Rèm che riêng tư 100%, đèn đọc sách dịu mắt giúp ngủ sâu suốt đêm.',
    score: '99% Yên Tĩnh',
    specs: { size: '185cm x 68cm', shockproof: '95% Êm ái', privacy: 'Riêng tư 100%', charger: 'USB + Type-C' }
  },
  {
    id: 'cap_doi',
    icon: '👩‍❤️‍👨',
    label: 'Cặp Đôi / Đi 2 Người',
    sublabel: '2 giường liền kề cùng tầng',
    seats: [],
    floor: 'f1',
    reason: 'Hai giường liền kề cạnh nhau còn trống, cùng một tầng, thuận tiện trò chuyện, chia sẻ hành lý và chăm sóc nhau suốt hành trình.',
    score: '100% Liền Kề',
    specs: { size: '185cm x 68cm x 2', shockproof: '96% Êm ái', privacy: 'Cặp đôi liền vách', charger: '2 Cổng sạc riêng' }
  },
  {
    id: 'nguoi_gia',
    icon: '👴',
    label: 'Người Lớn Tuổi / Trẻ Em',
    sublabel: 'Tầng 1, sát cửa buồng lái',
    seats: ['A01', 'A03'],
    floor: 'f1',
    reason: 'Tầng 1 hoàn toàn không phải leo cầu thang. Sát cửa lên xuống và buồng lái bác tài, thuận tiện lơ xe hỗ trợ mang vác hành lý và di chuyển khi dừng nghỉ.',
    score: '96% An Toàn & Tiện Lợi',
    specs: { size: '185cm x 68cm', shockproof: '98% Êm ái', privacy: 'Gần cửa ra vào', charger: 'Type-C tiện với' }
  },
  {
    id: 'chan_dai',
    icon: '🦵',
    label: 'Cao > 1m75 (Chân Dài)',
    sublabel: 'Khoang đầu/cuối vách rộng',
    seats: ['A01', 'A03', 'A16'],
    floor: 'f1',
    reason: 'Chiều dài lọt lòng giường 1m85, không có vách chặn chân gò bó, người cao từ 1m75 đến 1m85 duỗi thẳng chân thoải mái suốt đêm.',
    score: '95% Thoáng Chân',
    specs: { size: '190cm x 68cm', shockproof: '96% Êm ái', privacy: 'Khoang thoáng', charger: 'Type-C' }
  }
];

const findBestCoupleSeats = () => {
  const allF1 = [];
  (props.floor1Rows || []).forEach(r => {
    if (r.left && r.left.status !== 'booked') allF1.push(r.left.seat_number);
    if (r.middle && r.middle.status !== 'booked') allF1.push(r.middle.seat_number);
    if (r.right && r.right.status !== 'booked') allF1.push(r.right.seat_number);
  });
  
  for (let i = 0; i < allF1.length - 1; i++) {
    const s1 = allF1[i];
    const s2 = allF1[i + 1];
    const n1 = parseInt(s1.replace(/\D/g, ''));
    const n2 = parseInt(s2.replace(/\D/g, ''));
    if (Math.abs(n1 - n2) === 1) {
      return [s1, s2];
    }
  }

  const allF2 = [];
  (props.floor2Rows || []).forEach(r => {
    if (r.left && r.left.status !== 'booked') allF2.push(r.left.seat_number);
    if (r.right && r.right.status !== 'booked') allF2.push(r.right.seat_number);
  });
  for (let i = 0; i < allF2.length - 1; i++) {
    const s1 = allF2[i];
    const s2 = allF2[i + 1];
    const n1 = parseInt(s1.replace(/\D/g, ''));
    const n2 = parseInt(s2.replace(/\D/g, ''));
    if (Math.abs(n1 - n2) === 1) {
      return [s1, s2];
    }
  }
  return ['A01', 'A02'];
};

const selectAiPreset = (presetId) => {
  if (activeAiSeatMode.value === presetId) {
    clearAiRecommendation();
    return;
  }
  activeAiSeatMode.value = presetId;
  const p = AI_PRESETS.find(item => item.id === presetId);
  if (!p) return;

  let recommendedSeats = [...p.seats];
  if (presetId === 'cap_doi') {
    recommendedSeats = findBestCoupleSeats();
  }

  aiRecommendation.value = {
    ...p,
    seats: recommendedSeats,
  };

  if (p.floor) {
    activeMobileFloor.value = p.floor;
  }
};

const clearAiRecommendation = () => {
  activeAiSeatMode.value = '';
  aiRecommendation.value = null;
  aiCustomPrompt.value = '';
};

const isAiRecommended = (seatNum) => {
  return aiRecommendation.value?.seats?.includes(seatNum) || false;
};

const applyAiRecommendedSeats = () => {
  if (!aiRecommendation.value?.seats) return;
  const targetNums = aiRecommendation.value.seats;
  const allSeats = [];
  (props.floor1Rows || []).forEach(r => {
    if (r.left) allSeats.push(r.left);
    if (r.middle) allSeats.push(r.middle);
    if (r.right) allSeats.push(r.right);
  });
  (props.floor2Rows || []).forEach(r => {
    if (r.left) allSeats.push(r.left);
    if (r.right) allSeats.push(r.right);
  });

  selectedSeats.value = [];
  targetNums.forEach(num => {
    const seatObj = allSeats.find(s => s.seat_number === num && s.status !== 'booked');
    if (seatObj) {
      selectedSeats.value.push(seatObj);
    }
  });
};

const handleAiPromptSubmit = () => {
  const query = (aiCustomPrompt.value || '').toLowerCase().trim();
  if (!query) return;

  if (query.includes('say') || query.includes('chóng mặt') || query.includes('nôn') || query.includes('đỡ lắc') || query.includes('êm')) {
    selectAiPreset('say_xe');
  } else if (query.includes('ngủ') || query.includes('yên tĩnh') || query.includes('riêng tư') || query.includes('kín')) {
    selectAiPreset('ngu_say');
  } else if (query.includes('đôi') || query.includes('người yêu') || query.includes('bạn gái') || query.includes('2 người') || query.includes('cặp')) {
    selectAiPreset('cap_doi');
  } else if (query.includes('già') || query.includes('bà') || query.includes('ông') || query.includes('mẹ') || query.includes('bé') || query.includes('trẻ em') || query.includes('đau lưng')) {
    selectAiPreset('nguoi_gia');
  } else if (query.includes('cao') || query.includes('dài') || query.includes('m7') || query.includes('m8') || query.includes('chân')) {
    selectAiPreset('chan_dai');
  } else {
    selectAiPreset('say_xe');
  }
};

const startVoiceRecognition = () => {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition) {
    alert('Trình duyệt của bạn chưa hỗ trợ nhận diện giọng nói Web Speech. Bạn hãy gõ câu hỏi vào ô nhé!');
    return;
  }

  try {
    const recognition = new SpeechRecognition();
    recognition.lang = 'vi-VN';
    recognition.continuous = false;
    recognition.interimResults = false;

    isListeningVoice.value = true;

    recognition.onresult = (event) => {
      isListeningVoice.value = false;
      const transcript = event.results[0][0].transcript;
      aiCustomPrompt.value = transcript;
      handleAiPromptSubmit();
    };

    recognition.onerror = () => {
      isListeningVoice.value = false;
    };

    recognition.onend = () => {
      isListeningVoice.value = false;
    };

    recognition.start();
  } catch (err) {
    isListeningVoice.value = false;
  }
};

const openVirtualSeatPreview = (seat) => {
  previewingSeat.value = seat;
  showVirtualSeatModal.value = true;
};

// Kiểm tra tài khoản đã có SĐT và Gmail thật từ trước chưa
const userHasRealPhone = computed(() => {
  const p = props.user?.phone;
  return !!(p && p.length >= 9 && !p.includes('@'));
});

const userHasRealEmail = computed(() => {
  const e = props.user?.email;
  return !!(e && e.includes('@') && !e.toLowerCase().endsWith('@nhaxekientruc.vn'));
});

const cleanCustomerName = computed(() => {
  let name = (props.user?.name || customerForm.value?.name || 'Hành Khách').trim();
  // Loại bỏ tiền tố Sinh viên / Sinh vien
  name = name.replace(/^Sinh\s*viên\s+/i, '').replace(/^Sinh\s*vien\s+/i, '');
  // Nếu là username email như hieu_2251220108, lấy phần tên đầu viết hoa
  if (name.includes('_')) {
    const part = name.split('_')[0];
    if (part) {
      name = part.charAt(0).toUpperCase() + part.slice(1);
    }
  }
  // Loại bỏ phần trong ngoặc như (ĐH Kiến Trúc DAU)
  name = name.replace(/\s*\([^)]*\)/g, '').trim();
  if (/^[0-9]+$/.test(name) || !name) {
    name = 'Hành Khách';
  }
  return name;
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

// Phone OTP (eSMS Brandname Baotrixemay & Voice OTP)
const showPhoneOtpInput = ref(false);
const phoneOtpCode = ref('');
const phoneOtpSending = ref(false);
const phoneOtpVerifying = ref(false);
const phoneOtpMessage = ref('');
const phoneOtpError = ref(false);

const phoneMethod = ref('sms'); // 'sms' | 'voice'
const phoneCountdown = ref(0);
let phoneTimer = null;
const emailCountdown = ref(0);
let emailTimer = null;

const startPhoneCountdown = (sec = 60) => {
  if (phoneTimer) clearInterval(phoneTimer);
  phoneCountdown.value = sec;
  phoneTimer = setInterval(() => {
    if (phoneCountdown.value > 0) {
      phoneCountdown.value--;
    } else {
      clearInterval(phoneTimer);
      phoneTimer = null;
    }
  }, 1000);
};

const startEmailCountdown = (sec = 60) => {
  if (emailTimer) clearInterval(emailTimer);
  emailCountdown.value = sec;
  emailTimer = setInterval(() => {
    if (emailCountdown.value > 0) {
      emailCountdown.value--;
    } else {
      clearInterval(emailTimer);
      emailTimer = null;
    }
  }, 1000);
};

const handleSendPhoneOtp = async (method = 'sms') => {
  if (!isPhoneValid.value) {
    alert('Vui lòng nhập số điện thoại hợp lệ!');
    return;
  }
  phoneMethod.value = method;
  phoneOtpSending.value = true;
  phoneOtpMessage.value = '';
  phoneOtpError.value = false;

  try {
    const res = await axios.post('/user/send-otp', {
      type: 'phone',
      value: customerForm.value.phone.trim(),
      method: method,
    }, {
      headers: { 'Accept': 'application/json' }
    });

    showPhoneOtpInput.value = true;
    startPhoneCountdown(60);
    phoneOtpMessage.value = res.data?.message || 'Mã SMS OTP đã được gửi về số điện thoại của bạn!';
  } catch (err) {
    phoneOtpError.value = true;
    phoneOtpMessage.value = err.response?.data?.message || err.message || 'Lỗi gửi mã OTP. Vui lòng thử lại!';
  } finally {
    phoneOtpSending.value = false;
  }
};

const handleVerifyPhoneOtp = async () => {
  if (!phoneOtpCode.value || phoneOtpCode.value.length < 6) {
    phoneOtpError.value = true;
    phoneOtpMessage.value = 'Vui lòng nhập đủ 6 chữ số!';
    return;
  }
  phoneOtpVerifying.value = true;
  phoneOtpError.value = false;

  try {
    const res = await axios.post('/user/verify-otp', {
      otp: phoneOtpCode.value.trim(),
    }, {
      headers: { 'Accept': 'application/json' }
    });

    if (res.data?.success) {
      isPhoneVerified.value = true;
      showPhoneOtpInput.value = false;
      if (phoneTimer) clearInterval(phoneTimer);
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
      startEmailCountdown(60);
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
      if (emailTimer) clearInterval(emailTimer);
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

  // Đọc tham số ghế từ AI Chatbox truyền sang (?seats=A02,A05)
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search);
    const seatsParam = urlParams.get('seats') || urlParams.get('auto_seats');
    const reasonParam = urlParams.get('reason') || '';

    if (seatsParam) {
      const seatNums = seatsParam.split(',').map(s => s.trim().toUpperCase());
      const allSeats = [];
      (props.floor1Rows || []).forEach(r => {
        if (r.left) allSeats.push(r.left);
        if (r.middle) allSeats.push(r.middle);
        if (r.right) allSeats.push(r.right);
      });
      (props.floor2Rows || []).forEach(r => {
        if (r.left) allSeats.push(r.left);
        if (r.right) allSeats.push(r.right);
      });

      const matched = allSeats.filter(s => seatNums.includes(s.seat_number) && s.status !== 'booked');
      if (matched.length > 0) {
        selectedSeats.value = matched;
        
        let reasonTitle = 'AI Chatbox Đã Tự Động Chọn Ghế Giúp Bạn';
        let reasonDesc = 'Vị trí giường tối ưu theo thể trạng và nhu cầu bạn vừa trao đổi với Trợ Lý AI trong Chatbox.';
        if (reasonParam === 'say_xe') {
          reasonTitle = 'AI Chọn Ghế Chống Say Xe Tối Đa';
          reasonDesc = 'Vị trí trọng tâm xe Tầng 1 (ít dao động đèo dốc, gần gió điều hòa dịu nhẹ, giảm 80% cảm giác say xe).';
        } else if (reasonParam === 'ngu_say') {
          reasonTitle = 'AI Chọn Ghế Riêng Tư & Yên Tĩnh';
          reasonDesc = 'Khoang Tầng 2 cạnh cửa sổ có rèm che riêng tư 100%, cách biệt luồng người qua lại để ngủ suốt chuyến.';
        } else if (reasonParam === 'cap_doi') {
          reasonTitle = 'AI Chọn Cặp Giường Liền Kề 2 Người';
          reasonDesc = '2 giường nằm cạnh nhau còn trống đẹp nhất trên cùng một tầng để thuận tiện chăm sóc nhau.';
        }

        aiRecommendation.value = {
          id: 'chatbox_ai',
          icon: '🤖',
          label: reasonTitle,
          seats: matched.map(s => s.seat_number),
          reason: reasonDesc,
          score: '98% Phù Hợp Thể Trạng'
        };

        if (matched.some(s => s.floor === 2)) {
          activeMobileFloor.value = 'f2';
        }
      }
    }
  }

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

const voucherCodeInput = ref('');
const appliedVoucher = ref(null);
const voucherError = ref('');
const voucherLoading = ref(false);

const savedVouchers = ref([]);
const isVoucherWarehouseOpen = ref(false);
const voucherSaveMessage = ref('');

const initSavedVouchers = () => {
  try {
    const list = JSON.parse(localStorage.getItem('nhaxekientruc_saved_vouchers') || localStorage.getItem('bushub_saved_vouchers') || localStorage.getItem('saved_vouchers') || '[]');
    savedVouchers.value = Array.isArray(list) ? list : [];
  } catch (e) {
    savedVouchers.value = [];
  }
};

const saveVoucher = (code) => {
  if (!code) return;
  if (!savedVouchers.value.includes(code)) {
    savedVouchers.value.push(code);
    try {
      const keys = ['nhaxekientruc_saved_vouchers', 'bushub_saved_vouchers', 'saved_vouchers'];
      keys.forEach(k => {
        let list = [];
        try { list = JSON.parse(localStorage.getItem(k) || '[]'); } catch (e) {}
        if (!list.includes(code)) {
          list.push(code);
          localStorage.setItem(k, JSON.stringify(list));
        }
      });
      window.dispatchEvent(new CustomEvent('voucher-saved-to-wallet', { detail: { code } }));
    } catch (e) {}
  }
  voucherSaveMessage.value = `✓ Đã lưu mã [${code}] vào ví thành công! Bạn có thể chọn áp dụng ngay.`;
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
  window.addEventListener('voucher-saved-to-wallet', initSavedVouchers);
  if (!props.vouchers || props.vouchers.length === 0) {
    try {
      const res = await axios.get('/api/vouchers');
      if (res.data?.vouchers) {
        serverVouchers.value = res.data.vouchers;
      }
    } catch (e) {}
  }
});

onUnmounted(() => {
  window.removeEventListener('voucher-saved-to-wallet', initSavedVouchers);
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
        is_active: v.is_active !== false && v.is_active !== 0 && v.is_active !== '0',
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
      code: 'SINHVIEN10',
      title: 'Đặc Quyền Sinh Viên (Giảm 10%)',
      discount: '10%',
      badge: '🎓 SINH VIÊN 10%',
      target_audience: 'STUDENT',
      desc: 'Giảm 10% giá vé xe cho sinh viên các trường đại học',
      requirement: 'Áp dụng cho tài khoản sinh viên có email trường (.edu.vn)',
      is_active: true,
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
  if (v.is_active === false) return false;

  const userPlan = String(props.user?.membership_plan || '').toUpperCase();
  const code = String(v.code || '').toUpperCase();
  if (userPlan === 'CANCELLED' && ['ULTRA40', 'PRO30', 'ECO20', 'SINHVIEN30', 'SINHVIEN'].includes(code)) {
    return false;
  }
  if (userPlan.includes('ULTRA') && (code.includes('30') || code === 'SINHVIEN' || code === 'SINHVIEN30' || code === 'PRO30')) {
    return false;
  }

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
  if (v.is_active === false) {
    voucherError.value = `Mã ưu đãi "${v.code}" hiện đang tạm ngưng sử dụng!`;
    return;
  }

  const userPlan = String(props.user?.membership_plan || '').toUpperCase();
  const code = String(v.code || '').toUpperCase();
  if (userPlan === 'CANCELLED' && ['ULTRA40', 'PRO30', 'ECO20', 'SINHVIEN30', 'SINHVIEN'].includes(code)) {
    voucherError.value = 'Gói hội viên của bạn đã bị hủy, mã ưu đãi độc quyền này không còn khả dụng!';
    return;
  }
  if (userPlan.includes('ULTRA') && (code.includes('30') || code === 'SINHVIEN' || code === 'SINHVIEN30' || code === 'PRO30')) {
    voucherError.value = 'Bạn đang sử dụng Gói ULTRA VIP (Giảm 40%), không cần dùng mã 30%!';
    return;
  }
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
    } else if (v.code === 'SINHVIEN10' || v.code === 'SV10') {
      voucherError.value = 'Mã giảm giá 10% chỉ dành riêng cho sinh viên có email trường (.edu.vn)! Vui lòng điền email sinh viên ở thông tin hành khách phía trên.';
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
    customer_name: cleanCustomerName.value || props.user?.name || customerForm.value.name || 'Hành Khách',
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
