<template>
  <div class="min-h-screen bg-slate-50 flex flex-col text-slate-800">
    <AdminHeader title="HỆ THỐNG POS BÁN VÉ TẠI QUẦY & TỔNG ĐÀI" subtitle="Giao diện chia đôi màn hình: Chọn tuyến chuyến bên trái, sơ đồ ghế & xuất vé bên phải" />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-5">
      <!-- Top Control Bar: Bộ Lọc Ngày, Tuyến & Thống kê nhanh quầy vé -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 uppercase">
              CORE POS SYSTEM
            </span>
            <h1 class="text-lg font-black text-slate-900">Màn Hình Bán Vé Tại Quầy & Đặt Chỗ Trực Tiếp</h1>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">Nhân viên tổng đài và thu ngân quầy bấm chọn chuyến, sơ đồ ghế và xuất vé tức thì cho khách</p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 text-xs">
          <!-- Chọn ngày chạy -->
          <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
            <label class="font-bold text-slate-600">📅 Ngày:</label>
            <input 
              type="date" 
              v-model="filterDate" 
              @change="handleDateChange"
              class="bg-white border border-slate-200 rounded-lg px-2 py-1 font-bold text-slate-900 cursor-pointer focus:outline-red-500"
            />
          </div>

          <!-- Lọc tuyến đường -->
          <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
            <label class="font-bold text-slate-600">🛣️ Tuyến:</label>
            <select 
              v-model="filterRouteId" 
              @change="handleRouteChange"
              class="bg-white border border-slate-200 rounded-lg px-2 py-1 font-bold text-slate-900 cursor-pointer focus:outline-red-500 max-w-[180px]"
            >
              <option value="ALL">-- Tất cả tuyến đường --</option>
              <option v-for="r in routes" :key="r.id" :value="String(r.id)">
                {{ r.name }}
              </option>
            </select>
          </div>

          <!-- Lọc theo xe (Biển số) -->
          <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
            <label class="font-bold text-slate-600">🚍 Xe:</label>
            <select 
              v-model="filterBusPlate"
              class="bg-white border border-slate-200 rounded-lg px-2 py-1 font-bold text-slate-900 cursor-pointer focus:outline-red-500 max-w-[150px]"
            >
              <option value="ALL">-- Tất cả xe (10 xe) --</option>
              <option v-for="plate in availableBuses" :key="plate" :value="plate">
                {{ plate }}
              </option>
            </select>
          </div>

          <!-- Lọc theo trạng thái xe (Yêu cầu 2: xe chưa chạy, xe đang chạy, hoàn thành, tất cả) -->
          <div class="flex items-center gap-1.5 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
            <label class="font-bold text-slate-600">🚦 Trạng thái:</label>
            <select 
              v-model="filterTripStatus"
              class="bg-white border border-slate-200 rounded-lg px-2 py-1 font-bold text-slate-900 cursor-pointer focus:outline-red-500"
            >
              <option value="ALL">-- Tất cả trạng thái --</option>
              <option value="NOT_STARTED">⏳ Xe chưa chạy (Chờ xuất bến)</option>
              <option value="IN_TRANSIT">🚍 Xe đang chạy (Trên đường)</option>
              <option value="COMPLETED">🏁 Đã hoàn thành (Về bến)</option>
            </select>
          </div>

          <!-- Tìm nhanh chuyến / tài xế (Làm to và dài ra theo yêu cầu) -->
          <div class="flex items-center gap-2 bg-slate-50 px-3.5 py-2 rounded-2xl border border-slate-200 shadow-2xs flex-1 sm:min-w-[280px] lg:min-w-[360px]">
            <span class="text-slate-400 text-sm">🔍</span>
            <input 
              type="text" 
              v-model="searchTripKeyword" 
              placeholder="Tìm theo mã chuyến (#BH-...), biển số xe, tuyến đường, tài xế..."
              class="bg-white border border-slate-200 rounded-xl px-3 py-1.5 font-bold text-slate-900 w-full focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 text-xs sm:text-sm transition shadow-inner"
            />
            <button 
              v-if="searchTripKeyword" 
              @click="searchTripKeyword = ''" 
              class="text-xs text-slate-400 hover:text-slate-600 px-1 cursor-pointer"
            >
              ✕
            </button>
          </div>
        </div>
      </div>

      <!-- Flash Message -->
      <div v-if="$page.props.flash?.success" class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-bold flex items-center gap-2">
        <span>✅</span>
        <span>{{ $page.props.flash.success }}</span>
      </div>

      <!-- ==================== SPLIT SCREEN POS LAYOUT ==================== -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        <!-- BÊN TRÁI: DANH SÁCH TUYẾN & CHUYẾN CHẠY (5 CỘT - GIỚI HẠN CHIỀU CAO GỌN GÀNG, CUỘN MƯỢT) -->
        <div class="lg:col-span-5 flex flex-col max-h-[calc(100vh-160px)] bg-slate-50/60 p-3 rounded-3xl border border-slate-200 shadow-xs">
          <div class="flex items-center justify-between pb-2.5 border-b border-slate-200/80 shrink-0">
            <h2 class="text-xs font-black uppercase text-slate-700 tracking-wider flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
              1. CHỌN CHUYẾN XE ({{ filteredTrips.length }} / {{ trips.length }} chuyến)
            </h2>
            <span class="text-[11px] text-slate-400 font-medium">Bấm thẻ để xem ghế</span>
          </div>

          <div class="flex-1 overflow-y-auto space-y-2.5 pr-1.5 pt-2.5 custom-scrollbar">
            <div v-if="filteredTrips.length === 0" class="p-8 text-center bg-white rounded-2xl border border-slate-200 text-slate-400 text-xs">
              Không tìm thấy chuyến nào phù hợp. Vui lòng kiểm tra lại ngày, tuyến hoặc biển số xe.
            </div>

            <div 
              v-for="trip in filteredTrips" 
              :key="trip.id"
              @click="selectTrip(trip.id)"
              class="p-4 rounded-2xl border transition-all cursor-pointer select-none"
              :class="[
                selectedTrip?.id === trip.id 
                  ? 'bg-red-50/80 border-red-400 ring-2 ring-red-500/25 shadow-xs' 
                  : 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50/80 shadow-2xs'
              ]"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-base font-black text-slate-900 font-mono">{{ trip.dep_time_only }}</span>
                    <span class="text-xs text-slate-400 font-medium">➔ {{ trip.arrival_time }}</span>
                    <span class="text-[10px] font-mono font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">#{{ trip.trip_code }}</span>
                  </div>
                  <h3 class="text-xs font-bold text-slate-800 mt-0.5">{{ trip.route_name }}</h3>
                  <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-2">
                    <span>👨‍✈️ {{ trip.driver_name }}</span>
                    <span>•</span>
                    <span>🎫 {{ trip.conductor_name }}</span>
                  </div>
                </div>
              </div>

              <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs flex-wrap gap-1.5">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="px-2 py-0.5 rounded-md font-mono text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                    🚍 {{ trip.bus_plate }}
                  </span>
                  <!-- Badge trạng thái chuyến xe thời gian thực -->
                  <span v-if="trip.status === 'COMPLETED'" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-700 border border-slate-300">
                    🏁 Đã về bến
                  </span>
                  <span v-else-if="trip.status === 'IN_TRANSIT'" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200">
                    🚍 Đang chạy
                  </span>
                  <span v-else-if="trip.status === 'BOARDING'" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                    ⏳ Đang đón khách
                  </span>
                  <span v-else class="text-[11px] text-slate-500 truncate max-w-[120px]">{{ trip.bus_type }}</span>
                </div>

                <!-- Trạng thái ghế trống -->
                <span 
                  class="px-2 py-0.5 rounded-full text-[11px] font-bold"
                  :class="trip.status === 'COMPLETED' ? 'bg-slate-100 text-slate-600 border border-slate-200' : (trip.available_seats > 5 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : 'bg-amber-50 text-amber-700 border border-amber-200/60')"
                >
                  {{ trip.status === 'COMPLETED' ? 'Đã kết thúc' : `Còn ${trip.available_seats} / ${trip.total_seats} ghế` }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- BÊN PHẢI: SƠ ĐỒ GHẾ & FORM XUẤT VÉ NHANH (7 CỘT - STICKY ĐI THEO MÀN HÌNH) -->
        <div class="lg:col-span-7 space-y-4 lg:sticky lg:top-4 lg:self-start lg:max-h-[calc(100vh-160px)] overflow-y-auto pr-1 custom-scrollbar">
          <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-5">
            <!-- Banner Chuyến Xe Đã Hoàn Thành (Theo Giờ Thực Tế) -->
            <div v-if="selectedTrip?.is_completed || selectedTrip?.status === 'COMPLETED'" class="p-4 bg-slate-100/90 rounded-2xl border border-slate-300 text-xs text-slate-800 space-y-1 shadow-2xs">
              <div class="flex items-center gap-2 text-slate-900 font-black">
                <span class="text-base">🏁</span>
                <span>CHUYẾN XE ĐÃ HOÀN THÀNH XUẤT SẮC HÀNH TRÌNH VÀ VỀ BẾN AN TOÀN (LÚC {{ selectedTrip?.arrival_time }})</span>
              </div>
              <p class="text-slate-600 leading-relaxed font-normal">
                100% hành khách trên xe đã hoàn tất thủ tục và thanh toán đủ tiền vé trước khi xuống xe. Hệ thống đã đóng sổ quầy POS cho chuyến này.
              </p>
            </div>

            <!-- BẢNG ĐIỀU KHIỂN & CHÚ THÍCH GHẾ (GIAO DIỆN SÁNG TRẮNG HIỆN ĐẠI, KHÔNG MÀU ĐEN) -->
            <div class="bg-white border-2 border-slate-200 text-slate-800 p-4 rounded-3xl shadow-xs space-y-3">
              <!-- Hàng 1: Thông tin chuyến xe đang chọn -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-2xl bg-red-50 text-red-600 border border-red-200 flex items-center justify-center text-lg shrink-0">
                    🚍
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-black text-red-600 font-mono tracking-wider">#{{ selectedTrip?.trip_code || 'CHƯA CHỌN' }}</span>
                      <span class="text-xs text-slate-300">•</span>
                      <h2 class="text-sm font-black text-slate-900">{{ selectedTrip?.route_name }}</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                      Biển số xe: <strong class="text-slate-900 font-mono">{{ selectedTrip?.bus_plate }}</strong> • Xuất bến: <strong class="text-red-600 font-bold">{{ selectedTrip?.departure_time }}</strong>
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <span class="text-[11px] px-3 py-1 rounded-full font-bold bg-slate-100 border border-slate-200 text-slate-700">
                    Tổng 36 Ghế (Tầng 1: 18 • Tầng 2: 18)
                  </span>
                </div>
              </div>

              <!-- Hàng 2: Thẻ trạng thái & Chú thích trực quan đồng bộ (Ghép thống kê + Màu ghế) -->
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                <!-- 1. Ghế trống -->
                <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-md border-2 border-slate-400 bg-white shrink-0"></span>
                    <span class="text-slate-700 font-bold">Ghế Trống</span>
                  </div>
                  <strong class="text-base font-black text-slate-900 font-mono">{{ availableSeatCount }}</strong>
                </div>

                <!-- 2. Đang chọn -->
                <div class="p-2.5 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-md bg-red-600 border border-red-400 shrink-0"></span>
                    <span class="text-red-800 font-bold">Đang Chọn</span>
                  </div>
                  <strong class="text-base font-black text-red-600 font-mono">{{ selectedSeats.length }}</strong>
                </div>

                <!-- 3. Đã thanh toán 100% -->
                <div class="p-2.5 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-md bg-blue-600 border border-blue-400 shrink-0"></span>
                    <span class="text-blue-800 font-bold">Đã TT 100%</span>
                  </div>
                  <strong class="text-base font-black text-blue-700 font-mono">{{ paidSeatCount }}</strong>
                </div>

                <!-- 4. Đặt cọc 30% (Chờ lơ xe thu 70%) -->
                <div class="p-2.5 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-md bg-amber-500 border border-amber-400 shrink-0"></span>
                    <div>
                      <span class="text-amber-900 font-bold block leading-none">Cọc 30%</span>
                      <span class="text-[9px] text-amber-700 leading-none">Thu 70% tại xe</span>
                    </div>
                  </div>
                  <strong class="text-base font-black text-amber-800 font-mono">{{ depositSeatCount }}</strong>
                </div>
              </div>
            </div>

            <!-- SƠ ĐỒ GHẾ 2 TẦNG TRỰC QUAN -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase text-slate-600 tracking-wider">
                  2. SƠ ĐỒ GHẾ (BẤM VÀO GHẾ ĐỂ CHỌN HOẶC XEM HÀNH KHÁCH / GỌI ĐIỆN)
                </h3>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- TẦNG 1: DÃY A -->
                <div class="bg-slate-50/60 p-3.5 rounded-xl border border-slate-200">
                  <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                    <span class="text-xs font-black text-slate-800">TẦNG 1 (Dãy A)</span>
                    <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                      {{ formatCurrency(selectedTrip?.floor1_price || 280000) }}
                    </span>
                  </div>

                  <div class="grid grid-cols-3 gap-1.5 text-[9px] font-bold text-slate-500 pb-1 mb-1 border-b border-slate-200 select-none text-center">
                    <span>🪟 Cửa sổ</span>
                    <span>🚶 Dãy giữa</span>
                    <span>🪟 Cửa sổ</span>
                  </div>

                  <div class="grid grid-cols-3 gap-2">
                    <button
                      v-for="code in floor1Codes"
                      :key="code"
                      type="button"
                      @click="handleSeatClick(code)"
                      :disabled="isTripCompleted && seatMap[code]?.status !== 'BOOKED'"
                      class="p-2.5 rounded-xl border text-center transition-all relative min-h-[58px] flex flex-col items-center justify-center"
                      :class="[
                        getSeatStyle(code),
                        (isTripCompleted && seatMap[code]?.status !== 'BOOKED') ? 'cursor-not-allowed opacity-40 bg-slate-100 border-slate-200 text-slate-400' : 'cursor-pointer'
                      ]"
                      :title="isTripCompleted && seatMap[code]?.status !== 'BOOKED' ? 'Chuyến xe đã kết thúc hành trình - Quầy POS đã đóng sổ' : (seatMap[code]?.customer_name ? `Khách: ${seatMap[code]?.customer_name} (${seatMap[code]?.customer_phone}) - Bấm để xem chi tiết / gọi điện` : 'Ghế trống - Bấm để chọn')"
                    >
                      <div class="font-mono font-black text-xs">{{ code }}</div>
                      <div class="text-[9px] mt-0.5 leading-none">
                        <span v-if="selectedSeats.includes(code)" class="font-black text-red-600">✓ Chọn</span>
                        <span v-else-if="seatMap[code]?.status === 'BOOKED' && seatMap[code]?.payment_status === 'PAID'" class="truncate max-w-[65px] inline-block font-black text-blue-700">✓ Đã TT</span>
                        <span v-else-if="seatMap[code]?.status === 'BOOKED' && (seatMap[code]?.payment_status === 'DEPOSIT_PAID' || seatMap[code]?.payment_status === 'PARTIALLY_PAID')" class="truncate max-w-[65px] inline-block font-black text-amber-700">⚡ Cọc 30%</span>
                        <span v-else-if="seatMap[code]?.status === 'BOOKED'" class="truncate max-w-[65px] inline-block font-bold text-orange-700">⚠️ Đã Đặt</span>
                        <span v-else-if="seatMap[code]?.status === 'LOCKED_TEMP'" class="text-slate-400">Khóa 5p</span>
                        <span v-else class="text-slate-400">Trống</span>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- TẦNG 2: DÃY B - 2 BÊN CỬA SỔ -->
                <div class="bg-slate-50/60 p-3.5 rounded-xl border border-slate-200">
                  <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-200/60">
                    <span class="text-xs font-black text-slate-800">TẦNG 2 (Dãy B - 2 Bên Cửa Sổ)</span>
                    <span class="text-[10px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                      {{ formatCurrency(selectedTrip?.floor2_price || 270000) }}
                    </span>
                  </div>

                  <div class="grid grid-cols-3 gap-2 text-[9px] font-bold text-slate-500 pb-1 mb-1 border-b border-slate-200 select-none text-center">
                    <span>🪟 Cửa sổ (Trái)</span>
                    <span>🚶 Lối đi thông tầng</span>
                    <span>🪟 Cửa sổ (Phải)</span>
                  </div>

                  <div class="grid grid-cols-3 gap-2">
                    <template v-for="r in 6" :key="'pos-f2-row-' + r">
                      <!-- Ghế Cửa Sổ Trái: B01, B03, B05, B07, B09, B11 -->
                      <button
                        type="button"
                        @click="handleSeatClick('B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1)))"
                        :disabled="isTripCompleted && seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status !== 'BOOKED'"
                        class="p-2.5 rounded-xl border text-center transition-all relative min-h-[58px] flex flex-col items-center justify-center"
                        :class="[
                          getSeatStyle('B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))),
                          (isTripCompleted && seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status !== 'BOOKED') ? 'cursor-not-allowed opacity-40 bg-slate-100 border-slate-200 text-slate-400' : 'cursor-pointer'
                        ]"
                        :title="isTripCompleted && seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status !== 'BOOKED' ? 'Chuyến xe đã kết thúc hành trình' : (seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.customer_name ? `Khách: ${seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.customer_name} (${seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.customer_phone})` : 'Ghế trống - Bấm để chọn')"
                      >
                        <div class="font-mono font-black text-xs">B{{ (r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1) }}</div>
                        <div class="text-[9px] mt-0.5 leading-none">
                          <span v-if="selectedSeats.includes('B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1)))" class="font-black text-red-600">✓ Chọn</span>
                          <span v-else-if="seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'BOOKED' && seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.payment_status === 'PAID'" class="truncate max-w-[65px] inline-block font-black text-blue-700">✓ Đã TT</span>
                          <span v-else-if="seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'BOOKED' && (seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.payment_status === 'DEPOSIT_PAID' || seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.payment_status === 'PARTIALLY_PAID')" class="truncate max-w-[65px] inline-block font-black text-amber-700">⚡ Cọc 30%</span>
                          <span v-else-if="seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'BOOKED'" class="truncate max-w-[65px] inline-block font-bold text-orange-700">⚠️ Đã Đặt</span>
                          <span v-else-if="seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'LOCKED_TEMP'" class="text-slate-400">Khóa 5p</span>
                          <span v-else class="text-slate-400">Trống</span>
                        </div>
                      </button>

                      <!-- Cột Giữa: Lối Đi Thông Tầng VIP -->
                      <div class="p-2.5 rounded-xl border border-dashed border-slate-200 bg-slate-50/70 text-center flex flex-col items-center justify-center select-none text-slate-400 min-h-[58px]">
                        <span class="text-xs">🚶</span>
                        <span class="text-[8px] font-bold uppercase tracking-tighter">Lối đi</span>
                      </div>

                      <!-- Ghế Cửa Sổ Phải: B02, B04, B06, B08, B10, B12 -->
                      <button
                        type="button"
                        @click="handleSeatClick('B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2)))"
                        :disabled="isTripCompleted && seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status !== 'BOOKED'"
                        class="p-2.5 rounded-xl border text-center transition-all relative min-h-[58px] flex flex-col items-center justify-center"
                        :class="[
                          getSeatStyle('B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))),
                          (isTripCompleted && seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status !== 'BOOKED') ? 'cursor-not-allowed opacity-40 bg-slate-100 border-slate-200 text-slate-400' : 'cursor-pointer'
                        ]"
                        :title="isTripCompleted && seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status !== 'BOOKED' ? 'Chuyến xe đã kết thúc hành trình' : (seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.customer_name ? `Khách: ${seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.customer_name} (${seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.customer_phone})` : 'Ghế trống - Bấm để chọn')"
                      >
                        <div class="font-mono font-black text-xs">B{{ (r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2) }}</div>
                        <div class="text-[9px] mt-0.5 leading-none">
                          <span v-if="selectedSeats.includes('B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2)))" class="font-black text-red-600">✓ Chọn</span>
                          <span v-else-if="seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'BOOKED' && seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.payment_status === 'PAID'" class="truncate max-w-[65px] inline-block font-black text-blue-700">✓ Đã TT</span>
                          <span v-else-if="seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'BOOKED' && (seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.payment_status === 'DEPOSIT_PAID' || seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.payment_status === 'PARTIALLY_PAID')" class="truncate max-w-[65px] inline-block font-black text-amber-700">⚡ Cọc 30%</span>
                          <span v-else-if="seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'BOOKED'" class="truncate max-w-[65px] inline-block font-bold text-orange-700">⚠️ Đã Đặt</span>
                          <span v-else-if="seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'LOCKED_TEMP'" class="text-slate-400">Khóa 5p</span>
                          <span v-else class="text-slate-400">Trống</span>
                        </div>
                      </button>
                    </template>
                  </div>
                </div>
              </div>

              <!-- THÔNG BÁO KHI CHUYẾN XE ĐÃ HOÀN THÀNH - ĐÓNG SỔ POS -->
              <div v-if="isTripCompleted" class="p-3.5 bg-slate-100 rounded-2xl border border-slate-300 text-center space-y-1">
                <div class="font-black text-slate-800 text-xs flex items-center justify-center gap-1.5">
                  <span>🔒</span>
                  <span>CHUYẾN XE ĐÃ HOÀN THÀNH - QUẦY POS ĐÃ ĐÓNG SỔ</span>
                </div>
                <p class="text-[11px] text-slate-500">
                  Không thể chọn ghế hoặc xuất vé mới cho chuyến xe đã kết thúc hành trình. Bạn có thể bấm vào các ghế đã đặt màu xanh để tra cứu thông tin hành khách.
                </p>
              </div>
            </div>

            <!-- FORM NHẬP THÔNG TIN KHÁCH & XUẤT VÉ -->
            <form @submit.prevent="submitBooking" class="pt-3 border-t border-slate-100 space-y-4 text-xs">
              <div class="flex items-center justify-between">
                <h3 class="text-xs font-black uppercase text-slate-700 tracking-wider">
                  3. THÔNG TIN KHÁCH & THANH TOÁN
                </h3>
                <div v-if="selectedSeats.length > 0" class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                  <span>Ghế chọn: <strong class="text-red-600 font-mono">{{ selectedSeats.join(', ') }}</strong> ({{ selectedSeats.length }} vé)</span>
                  <span class="text-slate-300">•</span>
                  <span>Tổng tiền: <strong class="text-emerald-700 font-mono text-sm">{{ formatCurrency(totalAmount) }}</strong></span>
                </div>
              </div>

              <!-- Tên & Số điện thoại (Bắt buộc) -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">
                    Họ Và Tên Khách Hàng <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="text" 
                    v-model="bookingForm.customer_name" 
                    required 
                    placeholder="Ví dụ: Nguyễn Văn An"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-red-500"
                  />
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">
                    Số Điện Thoại Nhận Mã Vé <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="tel" 
                    v-model="bookingForm.customer_phone" 
                    required 
                    placeholder="Ví dụ: 0905123456"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono font-bold text-slate-900 focus:bg-white focus:outline-red-500"
                  />
                </div>
              </div>

              <!-- Điểm đón & Điểm trả -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Điểm Đón Khách (Tại bến xe xuất phát)</label>
                  <select 
                    v-model="bookingForm.pickup_stop_id"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-red-500"
                  >
                    <option v-for="s in availablePickupStops" :key="s.id" :value="s.id">
                      {{ s.stop_name }} (+{{ s.distance_from_start_km }} km)
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block font-bold text-slate-700 mb-1">Điểm Trả Khách (Dọc tuyến & Bến đến)</label>
                  <select 
                    v-model="bookingForm.dropoff_stop_id"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-red-500"
                  >
                    <option v-for="s in availableDropoffStops" :key="s.id" :value="s.id">
                      {{ s.stop_name }} (+{{ s.distance_from_start_km }} km)
                    </option>
                  </select>
                </div>
              </div>

              <!-- Phương thức thanh toán (Chỉ 2 hình thức: Cọc 30% hoặc Thanh toán đủ 100%) -->
              <div>
                <label class="block font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                  <span>Hình Thức Thanh Toán <span class="text-red-500">*</span></span>
                  <span class="text-[11px] text-slate-500 font-normal">70% còn lại lơ xe sẽ thu khi khách check-in lên xe</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <!-- Lựa chọn 1: Đặt cọc 30% -->
                  <button
                    type="button"
                    @click="bookingForm.payment_method = 'DEPOSIT_30'"
                    class="p-3 rounded-2xl border text-left transition-all cursor-pointer relative"
                    :class="bookingForm.payment_method === 'DEPOSIT_30' ? 'bg-amber-50 text-amber-950 border-amber-400 ring-2 ring-amber-400/30 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-2">
                        <span class="text-base">⚡</span>
                        <span class="font-black text-xs">Đặt Cọc Trước 30%</span>
                      </div>
                      <span class="font-mono font-black text-xs text-amber-800">
                        {{ formatCurrency(Math.round(totalAmount * 0.3)) }}
                      </span>
                    </div>
                    <div class="text-[10.5px] text-slate-500 mt-1 leading-tight">
                      Khách cọc trước 30%. Còn lại <b>70% ({{ formatCurrency(Math.round(totalAmount * 0.7)) }})</b> lơ xe thu trực tiếp khi lên xe.
                    </div>
                  </button>

                  <!-- Lựa chọn 2: Thanh toán đủ 100% -->
                  <button
                    type="button"
                    @click="bookingForm.payment_method = 'PAID_FULL'"
                    class="p-3 rounded-2xl border text-left transition-all cursor-pointer relative"
                    :class="bookingForm.payment_method === 'PAID_FULL' ? 'bg-emerald-50 text-emerald-950 border-emerald-400 ring-2 ring-emerald-400/30 shadow-xs' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-2">
                        <span class="text-base">✓</span>
                        <span class="font-black text-xs">Thanh Toán Đủ 100%</span>
                      </div>
                      <span class="font-mono font-black text-xs text-emerald-800">
                        {{ formatCurrency(totalAmount) }}
                      </span>
                    </div>
                    <div class="text-[10.5px] text-slate-500 mt-1 leading-tight">
                      Khách trả full tiền vé ngay. Khi lên xe chỉ cần quét mã QR check-in, không cần nộp thêm tiền.
                    </div>
                  </button>
                </div>
              </div>

              <!-- Ghi chú & Email -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Email Nhận Vé Điện Tử</label>
                  <input 
                    type="email" 
                    v-model="bookingForm.customer_email" 
                    placeholder="khachhang@gmail.com"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:bg-white focus:outline-red-500"
                  />
                </div>
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Ghi Chú Đón Khách</label>
                  <input 
                    type="text" 
                    v-model="bookingForm.notes" 
                    placeholder="Ví dụ: Đón tại cây xăng Hòa Cầm, có 1 vali to..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-900 focus:bg-white focus:outline-red-500"
                  />
                </div>
              </div>

              <!-- HÀNH ĐỘNG: XUẤT VÉ / GIỮ CHỖ -->
              <div class="pt-2">
                <div v-if="selectedTrip?.is_completed || selectedTrip?.status === 'COMPLETED'" class="w-full py-3.5 bg-slate-100 border border-slate-300 text-slate-500 font-black text-xs rounded-xl text-center flex items-center justify-center gap-2 cursor-not-allowed select-none">
                  <span>🏁</span>
                  <span>CHUYẾN XE ĐÃ VỀ BẾN • ĐÃ ĐÓNG SỔ POS KHÔNG THỂ XUẤT VÉ MỚI</span>
                </div>
                <div v-else class="flex flex-col sm:flex-row items-center gap-3">
                  <!-- Nút Xuất Vé & In Vé Nhiệt -->
                  <button
                    type="submit"
                    :disabled="selectedSeats.length === 0 || isSubmitting"
                    class="w-full sm:flex-1 py-3 bg-[#dc2626] hover:bg-red-700 text-white font-black text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99]"
                  >
                    <span v-if="isSubmitting">Đang xử lý xuất vé...</span>
                    <span v-else class="flex items-center gap-2">
                      <span>🖨️</span>
                      <span>XUẤT VÉ & IN VÉ QUẦY ({{ selectedSeats.length }} Ghế - {{ formatCurrency(totalAmount) }})</span>
                    </span>
                  </button>

                  <!-- Nút Đặt Giữ Chỗ (Khóa Ghế 5 Phút) -->
                  <button
                    type="button"
                    @click="lockSelectedSeats"
                    :disabled="selectedSeats.length === 0 || isSubmitting"
                    class="w-full sm:w-auto px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl border border-slate-200 transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                    title="Khóa ghế 5 phút cho khách tổng đài đang chuyển tiền"
                  >
                    <span>⏳</span>
                    <span>Giữ Chỗ (Khóa 5p)</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>

    <!-- POPUP IN VÉ NHIỆT POS K80 (THERMAL RECEIPT PREVIEW) -->
    <div v-if="showPrintTicketModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 max-w-sm w-full p-5 space-y-4 shadow-xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <div class="flex items-center gap-1.5">
            <span class="text-emerald-600 font-bold">✓</span>
            <span class="font-black text-slate-900 text-sm">XUẤT VÉ QUẦY THÀNH CÔNG</span>
          </div>
          <button @click="showPrintTicketModal = false" class="text-slate-400 hover:text-slate-700 text-sm cursor-pointer">✕</button>
        </div>

        <!-- MÔ PHỎNG VÉ IN NHIỆT K80 THỰC TẾ -->
        <div class="p-4 bg-slate-50 border border-dashed border-slate-300 rounded-xl font-mono text-xs space-y-2 text-slate-800">
          <div class="text-center pb-2 border-b border-slate-300">
            <div class="font-black text-sm">nhaxekientruc CENTRAL TERMINAL</div>
            <div class="text-[10px] text-slate-500">Vé Xe Khách Điện Tử & Giữ Chỗ</div>
          </div>

          <div class="flex justify-between py-0.5">
            <span class="text-slate-500">Mã vé:</span>
            <strong class="font-black text-red-600">{{ printedTicket?.booking_code || 'BH-POS-9988' }}</strong>
          </div>
          <div class="flex justify-between py-0.5">
            <span class="text-slate-500">Hành khách:</span>
            <strong>{{ printedTicket?.customer_name }}</strong>
          </div>
          <div class="flex justify-between py-0.5">
            <span class="text-slate-500">Số ĐT:</span>
            <strong>{{ printedTicket?.customer_phone }}</strong>
          </div>
          <div class="flex justify-between py-0.5">
            <span class="text-slate-500">Tuyến xe:</span>
            <strong class="text-right truncate max-w-[170px]">{{ selectedTrip?.route_name }}</strong>
          </div>
          <div class="flex justify-between py-0.5">
            <span class="text-slate-500">Biển số:</span>
            <strong>{{ selectedTrip?.bus_plate }}</strong>
          </div>
          <div class="flex justify-between py-0.5">
            <span class="text-slate-500">Giờ chạy:</span>
            <strong class="text-red-700">{{ selectedTrip?.departure_time }}</strong>
          </div>
          <div class="flex justify-between py-0.5">
            <span class="text-slate-500">Số ghế:</span>
            <strong class="text-base text-red-600 font-black">{{ printedTicket?.seats }}</strong>
          </div>
          <div class="flex justify-between py-0.5 pt-1.5 border-t border-slate-300">
            <span class="font-bold">Tổng tiền vé:</span>
            <strong class="text-sm font-black text-slate-900">{{ formatCurrency(printedTicket?.total_amount) }}</strong>
          </div>
          <div v-if="printedTicket?.payment_method === 'DEPOSIT_30' || printedTicket?.payment_status === 'DEPOSIT_PAID'" class="space-y-0.5 text-amber-900 bg-amber-50 p-1.5 rounded text-[11px]">
            <div class="flex justify-between font-bold">
              <span>Đã cọc 30% tại quầy:</span>
              <strong class="text-emerald-700 font-mono">{{ formatCurrency(printedTicket?.paid_amount || Math.round(printedTicket?.total_amount * 0.3)) }}</strong>
            </div>
            <div class="flex justify-between font-bold text-red-600">
              <span>Còn lại 70% (Lơ xe thu):</span>
              <strong class="font-mono">{{ formatCurrency(printedTicket?.total_amount - (printedTicket?.paid_amount || Math.round(printedTicket?.total_amount * 0.3))) }}</strong>
            </div>
          </div>
          <div v-else class="flex justify-between py-0.5 text-[11px] font-bold text-emerald-700">
            <span>Hình thức:</span>
            <span>✓ ĐÃ THANH TOÁN ĐỦ 100%</span>
          </div>

          <div class="text-center pt-2 border-t border-slate-300 text-[10px] text-slate-400">
            Chúc quý khách thượng lộ bình an!
          </div>
        </div>

        <div class="flex items-center gap-2 pt-1">
          <button
            @click="printReceipt"
            class="flex-1 py-2.5 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl flex items-center justify-center gap-1.5 cursor-pointer"
          >
            <span>🖨️ In Vé Ngay</span>
          </button>
          <button
            @click="showPrintTicketModal = false"
            class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL CHI TIẾT HÀNH KHÁCH & GỌI ĐIỆN CHO KHÁCH (THEO YÊU CẦU CỦA ADMIN) -->
    <div v-if="inspectingSeat" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 space-y-5 shadow-2xl animate-in fade-in zoom-in-95">
        <!-- Header -->
        <div class="flex items-start justify-between pb-3 border-b border-slate-100">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-mono font-black px-2 py-0.5 rounded-md bg-slate-100 text-slate-800 border border-slate-200">
                GHẾ {{ inspectingSeat.seat_number }} ({{ inspectingSeat.floor === 1 ? 'Tầng 1 - Dãy A' : 'Tầng 2 - Dãy B' }})
              </span>
              <!-- Tình trạng thanh toán & lên xe phân chia đầy đủ các trường hợp -->
              <span 
                v-if="inspectingSeat.payment_status === 'PAID' && (inspectingSeat.boarding_status === 'BOARDED' || inspectingSeat.checkin_status === 'boarded')"
                class="text-[11px] font-black px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300"
              >
                ✓ ĐÃ TT 100% • ĐÃ LÊN XE
              </span>
              <span 
                v-else-if="inspectingSeat.payment_status === 'PAID'"
                class="text-[11px] font-black px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 border border-blue-300"
              >
                ✓ ĐÃ TT 100% • CHỜ LÊN XE
              </span>
              <span 
                v-else-if="inspectingSeat.payment_status === 'DEPOSIT_PAID' && (inspectingSeat.boarding_status === 'BOARDED' || inspectingSeat.checkin_status === 'boarded')"
                class="text-[11px] font-black px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800 border border-purple-300"
              >
                ⚡ CỌC 30% • ĐÃ LÊN (THU 70%)
              </span>
              <span 
                v-else-if="inspectingSeat.payment_status === 'DEPOSIT_PAID'"
                class="text-[11px] font-black px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-300"
              >
                ⚡ CỌC 30% • CHỜ THU 70%
              </span>
              <span 
                v-else-if="inspectingSeat.payment_status === 'REFUNDED' || inspectingSeat.payment_status === 'CANCELLED'"
                class="text-[11px] font-black px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800 border border-rose-300"
              >
                ⛔ ĐÃ HỦY VÉ / HOÀN TIỀN
              </span>
              <span 
                v-else
                class="text-[11px] font-black px-2.5 py-0.5 rounded-full bg-orange-100 text-orange-800 border border-orange-300"
              >
                ⚠️ CHƯA THANH TOÁN (THU TẠI XE)
              </span>
            </div>
            <h3 class="text-base font-black text-slate-900 mt-1">Thông Tin Hành Khách Đặt Chỗ</h3>
          </div>
          <button @click="inspectingSeat = null" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center font-bold text-sm cursor-pointer">✕</button>
        </div>

        <!-- Thông tin khách -->
        <div class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-xs">
          <!-- Tình trạng lên xe -->
          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-500 font-medium">Trạng thái lên xe:</span>
            <span 
              class="font-black px-2 py-0.5 rounded-md text-[11px]"
              :class="(inspectingSeat.boarding_status === 'BOARDED' || inspectingSeat.checkin_status === 'boarded') ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'"
            >
              {{ (inspectingSeat.boarding_status === 'BOARDED' || inspectingSeat.checkin_status === 'boarded') ? '✓ Đã lên xe xuất bến' : '⏳ Chưa lên xe (Đang chờ đón)' }}
            </span>
          </div>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-500 font-medium">Họ và tên khách:</span>
            <strong class="text-slate-900 font-black text-sm">{{ inspectingSeat.customer_name || 'Khách đặt qua tổng đài/quầy' }}</strong>
          </div>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-500 font-medium">Số điện thoại:</span>
            <div class="flex items-center gap-2">
              <strong class="text-slate-900 font-mono font-black text-sm">{{ inspectingSeat.customer_phone || 'Chưa cập nhật' }}</strong>
            </div>
          </div>

          <!-- NÚT GỌI ĐIỆN CHO KHÁCH (prominent direct phone call button) -->
          <a 
            v-if="inspectingSeat.customer_phone"
            :href="'tel:' + inspectingSeat.customer_phone"
            class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs rounded-xl flex items-center justify-center gap-2 shadow-sm transition active:scale-[0.98] cursor-pointer"
          >
            <span>📞</span>
            <span>GỌI ĐIỆN CHO KHÁCH ({{ inspectingSeat.customer_phone }})</span>
          </a>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-500 font-medium">Mã vé / Mã đặt:</span>
            <span class="font-mono font-black text-red-600">{{ inspectingSeat.booking_code || 'BH-POS-' + inspectingSeat.seat_number }}</span>
          </div>

          <!-- Phân tách số tiền thanh toán / cọc / còn lại -->
          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-500 font-medium">Tổng tiền vé:</span>
            <strong class="font-mono text-slate-900 text-sm">{{ formatCurrency(inspectingSeat.total_amount || inspectingSeat.price || 280000) }}</strong>
          </div>

          <div v-if="inspectingSeat.payment_status === 'DEPOSIT_PAID'" class="bg-amber-50 p-2.5 rounded-xl border border-amber-200 space-y-1">
            <div class="flex justify-between items-center text-[11px]">
              <span class="text-amber-800 font-bold">✓ Đã cọc trước 30%:</span>
              <strong class="font-mono text-emerald-700 font-black">{{ formatCurrency(inspectingSeat.deposit_amount || (inspectingSeat.total_amount * 0.3)) }}</strong>
            </div>
            <div class="flex justify-between items-center text-[11px]">
              <span class="text-amber-900 font-bold">⚠️ Cần thu còn lại 70%:</span>
              <strong class="font-mono text-red-600 font-black">{{ formatCurrency(inspectingSeat.remaining_amount || (inspectingSeat.total_amount * 0.7)) }}</strong>
            </div>
          </div>

          <div v-if="inspectingSeat.voucher_code" class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-500 font-medium">Mã giảm giá áp dụng:</span>
            <span class="font-mono font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">{{ inspectingSeat.voucher_code }}</span>
          </div>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-500 font-medium">Kênh đặt vé:</span>
            <span class="font-bold text-slate-700">
              {{ inspectingSeat.booking_source === 'POS_COUNTER' ? '🏢 Tại Quầy Vé Bến Xe' : (inspectingSeat.booking_source === 'ROADSIDE' ? '🚍 Bắt Xe Dọc Đường' : '🌐 Website nhaxekientruc Online') }}
            </span>
          </div>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-500 font-medium">Hình thức thanh toán:</span>
            <span class="font-bold text-slate-700">{{ inspectingSeat.payment_method === 'CASH' ? '💵 Tiền mặt' : (inspectingSeat.payment_method === 'TRANSFER' ? '📱 Chuyển khoản QR' : '💳 Online VNPay/Momo') }}</span>
          </div>

          <div class="flex justify-between items-center py-1 border-b border-slate-200/60">
            <span class="text-slate-500 font-medium">Điểm đón khách:</span>
            <span class="font-bold text-slate-800 text-right truncate max-w-[200px]">{{ inspectingSeat.pickup_stop || 'Bến xe trung tâm' }}</span>
          </div>

          <div class="flex justify-between items-center py-1">
            <span class="text-slate-500 font-medium">Điểm trả khách:</span>
            <span class="font-bold text-slate-800 text-right truncate max-w-[200px]">{{ inspectingSeat.dropoff_stop || 'Bến xe trả khách' }}</span>
          </div>
        </div>

        <!-- Các nút hành động: Thu tiền & Hủy vé -->
        <div class="space-y-2 pt-1">
          <button
            v-if="inspectingSeat.payment_status !== 'PAID' && inspectingSeat.booking_id"
            @click="markSeatPaid(inspectingSeat.booking_id)"
            class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-black text-xs rounded-xl flex items-center justify-center gap-2 shadow-sm transition active:scale-[0.98] cursor-pointer"
          >
            <span>💳</span>
            <span>XÁC NHẬN ĐÃ THU TIỀN TẠI QUẦY (ĐỔI SANG PAID)</span>
          </button>

          <button
            v-if="inspectingSeat.booking_id"
            @click="cancelSeat(inspectingSeat.booking_id)"
            class="w-full py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs rounded-xl border border-rose-200 flex items-center justify-center gap-2 transition cursor-pointer"
          >
            <span>❌</span>
            <span>HỦY GIỮ CHỖ / GIẢI PHÓNG GHẾ NÀY</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';

const props = defineProps({
  routes: Array,
  trips: Array,
  selectedDate: String,
  selectedRouteId: String,
  selectedTrip: Object,
  seatMap: Object,
});

const filterDate = ref(props.selectedDate || new Date().toISOString().split('T')[0]);
const filterRouteId = ref(props.selectedRouteId || 'ALL');
const filterBusPlate = ref('ALL');
const filterTripStatus = ref('ALL');
const searchTripKeyword = ref('');

const availableBuses = computed(() => {
  const plates = new Set();
  (props.trips || []).forEach(t => {
    if (t.bus_plate) plates.add(t.bus_plate);
  });
  return Array.from(plates);
});

const filteredTrips = computed(() => {
  let list = props.trips || [];
  if (filterBusPlate.value !== 'ALL') {
    list = list.filter(t => t.bus_plate === filterBusPlate.value);
  }
  if (filterTripStatus.value === 'NOT_STARTED') {
    list = list.filter(t => !['IN_TRANSIT', 'RUNNING', 'COMPLETED', 'CLOSED', 'ARRIVED'].includes(t.status));
  } else if (filterTripStatus.value === 'IN_TRANSIT') {
    list = list.filter(t => ['IN_TRANSIT', 'RUNNING', 'BOARDING'].includes(t.status));
  } else if (filterTripStatus.value === 'COMPLETED') {
    list = list.filter(t => ['COMPLETED', 'CLOSED', 'ARRIVED'].includes(t.status));
  }
  if (searchTripKeyword.value.trim()) {
    const kw = searchTripKeyword.value.toLowerCase();
    list = list.filter(t => 
      (t.bus_plate && t.bus_plate.toLowerCase().includes(kw)) ||
      (t.trip_code && t.trip_code.toLowerCase().includes(kw)) ||
      (t.route_name && t.route_name.toLowerCase().includes(kw)) ||
      (t.driver_name && t.driver_name.toLowerCase().includes(kw)) ||
      (t.conductor_name && t.conductor_name.toLowerCase().includes(kw))
    );
  }
  return list;
});

const selectedSeats = ref([]);
const isSubmitting = ref(false);
const showPrintTicketModal = ref(false);
const printedTicket = ref(null);
const inspectingSeat = ref(null);

// Thống kê nhanh ghế
const availableSeatCount = computed(() => {
  if (!props.seatMap) return 0;
  return Object.values(props.seatMap).filter(s => s.status === 'AVAILABLE').length;
});

const isTripDone = computed(() => {
  return props.selectedTrip?.is_completed || props.selectedTrip?.status === 'COMPLETED' || props.selectedTrip?.status === 'IN_TRANSIT' || props.selectedTrip?.status === 'RUNNING';
});

const isTripCompleted = computed(() => {
  return props.selectedTrip?.is_completed || props.selectedTrip?.status === 'COMPLETED' || props.selectedTrip?.status === 'CLOSED';
});

const boardedSeatCount = computed(() => {
  if (!props.seatMap) return 0;
  if (isTripDone.value) {
    return Object.values(props.seatMap).filter(s => s.status === 'BOOKED').length;
  }
  return Object.values(props.seatMap).filter(s => s.status === 'BOOKED' && (s.boarding_status === 'BOARDED' || s.checkin_status === 'boarded')).length;
});

const paidSeatCount = computed(() => {
  if (!props.seatMap) return 0;
  if (isTripDone.value) {
    return Object.values(props.seatMap).filter(s => s.status === 'BOOKED').length;
  }
  return Object.values(props.seatMap).filter(s => s.status === 'BOOKED' && s.payment_status === 'PAID').length;
});

const depositSeatCount = computed(() => {
  if (!props.seatMap || isTripDone.value) return 0;
  return Object.values(props.seatMap).filter(s => s.status === 'BOOKED' && (s.payment_status === 'DEPOSIT_PAID' || s.payment_status === 'PARTIALLY_PAID')).length;
});

const unpaidSeatCount = computed(() => {
  if (!props.seatMap || isTripDone.value) return 0;
  return Object.values(props.seatMap).filter(s => s.status === 'BOOKED' && s.payment_status !== 'PAID' && s.payment_status !== 'DEPOSIT_PAID' && s.payment_status !== 'PARTIALLY_PAID').length;
});

const floor1Codes = computed(() => {
  const codes = [];
  for (let i = 1; i <= 18; i++) {
    codes.push('A' + (i < 10 ? '0' + i : i));
  }
  return codes;
});

const floor2Codes = computed(() => {
  const codes = [];
  for (let i = 1; i <= 12; i++) {
    codes.push('B' + (i < 10 ? '0' + i : i));
  }
  return codes;
});

const availablePickupStops = computed(() => {
  const stops = props.selectedTrip?.stops || [];
  if (stops.length <= 2) return stops.slice(0, 1);
  return stops.filter((s, idx) => {
    const sName = (s.stop_name || '').toLowerCase();
    const isExplicitDrop = sName.includes('trả khách') || sName.includes('bến xe đến') || sName.includes('điểm trả');
    const isNearStart = (s.distance_from_start_km !== undefined && s.distance_from_start_km !== null) 
      ? Number(s.distance_from_start_km) <= 35 
      : (idx <= 1);
    return (idx === 0 || isNearStart) && !isExplicitDrop && idx < stops.length - 1;
  });
});

const availableDropoffStops = computed(() => {
  const stops = props.selectedTrip?.stops || [];
  if (stops.length <= 2) return stops.slice(1);
  const pickupIdx = stops.findIndex(s => s.id === bookingForm.pickup_stop_id);
  const minIdx = pickupIdx >= 0 ? pickupIdx + 1 : 1;
  return stops.filter((s, idx) => idx >= minIdx);
});

const bookingForm = reactive({
  trip_id: props.selectedTrip?.id || '',
  customer_name: '',
  customer_phone: '',
  customer_email: '',
  pickup_stop_id: '',
  dropoff_stop_id: '',
  payment_method: 'DEPOSIT_30',
  notes: '',
});

// Update bookingForm trip_id when selectedTrip changes
watch(() => props.selectedTrip, (newTrip) => {
  if (newTrip) {
    bookingForm.trip_id = newTrip.id;
    if (availablePickupStops.value.length > 0) {
      bookingForm.pickup_stop_id = availablePickupStops.value[0].id;
    } else if (newTrip.stops && newTrip.stops.length > 0) {
      bookingForm.pickup_stop_id = newTrip.stops[0].id;
    }
    if (availableDropoffStops.value.length > 0) {
      bookingForm.dropoff_stop_id = availableDropoffStops.value[availableDropoffStops.value.length - 1].id;
    } else if (newTrip.stops && newTrip.stops.length > 0) {
      bookingForm.dropoff_stop_id = newTrip.stops[newTrip.stops.length - 1].id;
    }
  }
  selectedSeats.value = [];
  inspectingSeat.value = null;
}, { immediate: true });

const totalAmount = computed(() => {
  let sum = 0;
  for (const s of selectedSeats.value) {
    if (s.startsWith('A')) {
      sum += (props.selectedTrip?.floor1_price || props.selectedTrip?.base_price || 280000);
    } else {
      sum += (props.selectedTrip?.floor2_price || (props.selectedTrip?.base_price - 10000) || 270000);
    }
  }
  return sum;
});

const handleDateChange = () => {
  router.get('/admin/pos', {
    date: filterDate.value,
    route_id: filterRouteId.value,
  }, { preserveState: true });
};

const handleRouteChange = () => {
  router.get('/admin/pos', {
    date: filterDate.value,
    route_id: filterRouteId.value,
  }, { preserveState: true });
};

const selectTrip = (tripId) => {
  router.get('/admin/pos', {
    date: filterDate.value,
    route_id: filterRouteId.value,
    pos_trip_id: tripId,
  }, { preserveState: true });
};

const handleSeatClick = (seatCode) => {
  const info = props.seatMap?.[seatCode];
  if (info?.status === 'BOOKED') {
    inspectingSeat.value = info;
    return;
  }
  // Chuyến xe đã hoàn thành -> Không cho phép click vào ghế trống để đặt vé
  if (isTripCompleted.value) {
    return;
  }
  toggleSeat(seatCode);
};

const toggleSeat = (seatCode) => {
  // Chuyến xe đã hoàn thành -> Tuyệt đối không cho phép chọn ghế
  if (isTripCompleted.value) return;

  const info = props.seatMap?.[seatCode];
  if (info?.status === 'BOOKED') return;

  const idx = selectedSeats.value.indexOf(seatCode);
  if (idx > -1) {
    selectedSeats.value.splice(idx, 1);
  } else {
    selectedSeats.value.push(seatCode);
  }
};

const getSeatStyle = (seatCode) => {
  if (selectedSeats.value.includes(seatCode)) {
    return 'bg-red-600 text-white border-red-700 shadow-md font-black scale-105 ring-2 ring-red-400';
  }

  const info = props.seatMap?.[seatCode];
  if (!info) return 'bg-white border-slate-200 text-slate-700';

  if (info.status === 'BOOKED') {
    if (isTripDone.value) {
      return 'bg-emerald-100 text-emerald-950 border-emerald-400 font-black ring-1 ring-emerald-500 hover:bg-emerald-200 hover:shadow-xs';
    }
    const isBoarded = info.boarding_status === 'BOARDED' || info.checkin_status === 'boarded';
    if (info.payment_status === 'PAID') {
      if (isBoarded) {
        return 'bg-emerald-100 text-emerald-950 border-emerald-400 font-black ring-1 ring-emerald-500 hover:bg-emerald-200 hover:shadow-xs';
      }
      return 'bg-blue-100 text-blue-950 border-blue-400 font-bold hover:bg-blue-200 hover:border-blue-500 hover:shadow-xs';
    } else if (info.payment_status === 'DEPOSIT_PAID' || info.payment_status === 'PARTIALLY_PAID') {
      if (isBoarded) {
        return 'bg-purple-100 text-purple-950 border-purple-400 font-black ring-1 ring-purple-500 hover:bg-purple-200 hover:shadow-xs';
      }
      return 'bg-amber-100 text-amber-950 border-amber-400 font-bold hover:bg-amber-200 ring-1 ring-amber-400 hover:shadow-xs';
    } else {
      return 'bg-orange-100 text-orange-950 border-orange-400 font-bold hover:bg-orange-200 ring-1 ring-orange-400 hover:shadow-xs';
    }
  }
  if (info.status === 'LOCKED_TEMP') {
    return 'bg-slate-200 text-slate-600 border-slate-400';
  }
  return 'bg-white text-slate-800 border-slate-200 hover:border-red-400 hover:bg-red-50/20';
};

const markSeatPaid = (bookingId) => {
  router.post(`/admin/pos/booking/${bookingId}/mark-paid`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      inspectingSeat.value = null;
    }
  });
};

const cancelSeat = (bookingId) => {
  if (confirm('Bạn có chắc chắn muốn HỦY GIỮ CHỖ này và giải phóng ghế cho khách khác?')) {
    router.post(`/admin/pos/booking/${bookingId}/cancel`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        inspectingSeat.value = null;
      }
    });
  }
};

const submitBooking = () => {
  if (selectedSeats.value.length === 0) {
    alert('Vui lòng click chọn ít nhất 1 vị trí ghế trên sơ đồ!');
    return;
  }

  isSubmitting.value = true;
  router.post('/admin/pos/book', {
    trip_id: props.selectedTrip.id,
    customer_name: bookingForm.customer_name,
    customer_phone: bookingForm.customer_phone,
    customer_email: bookingForm.customer_email,
    seats: selectedSeats.value.join(', '),
    total_amount: totalAmount.value,
    payment_method: bookingForm.payment_method,
    pickup_stop_id: bookingForm.pickup_stop_id,
    dropoff_stop_id: bookingForm.dropoff_stop_id,
    notes: bookingForm.notes,
  }, {
    onSuccess: () => {
      isSubmitting.value = false;
      printedTicket.value = {
        booking_code: 'BH-POS-' + Math.floor(100000 + Math.random() * 900000),
        customer_name: bookingForm.customer_name,
        customer_phone: bookingForm.customer_phone,
        seats: selectedSeats.value.join(', '),
        total_amount: totalAmount.value,
        payment_method: bookingForm.payment_method,
      };
      showPrintTicketModal.value = true;
      selectedSeats.value = [];
      bookingForm.customer_name = '';
      bookingForm.customer_phone = '';
      bookingForm.notes = '';
    },
    onError: () => {
      isSubmitting.value = false;
    }
  });
};

const lockSelectedSeats = () => {
  if (selectedSeats.value.length === 0) return;
  for (const s of selectedSeats.value) {
    router.post('/admin/pos/lock-seat', {
      trip_id: props.selectedTrip.id,
      seat_number: s,
    });
  }
  alert(`Đã giữ chỗ khóa các ghế [${selectedSeats.value.join(', ')}] trong 5 phút thành công!`);
};

const printReceipt = () => {
  window.print();
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
  height: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(241, 245, 249, 0.6);
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
