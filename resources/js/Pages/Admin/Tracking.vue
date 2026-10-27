<template>
  <div 
    class="min-h-screen bg-slate-50 flex flex-col transition-all duration-300"
    :class="{ 'ring-8 ring-red-500 ring-inset animate-pulse': isSosActive }"
  >
    <AdminHeader title="BẢN ĐỒ LIVE TRACKING GPS & ĐIỀU HÀNH ĐỘI XE" subtitle="Giám sát vị trí thực của đội xe theo ngày & biển số xe, nhận báo cáo tức thời từ bác tài" />

    <!-- CẢNH BÁO SOS TOÀN TRANG KHI TÀI XẾ BẤM KHẨN CẤP -->
    <div 
      v-if="isSosActive" 
      class="bg-red-600 text-white px-4 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-lg z-40 sticky top-[108px] animate-bounce"
    >
      <div class="flex items-center gap-3">
        <span class="text-2xl animate-spin">🚨</span>
        <div>
          <h2 class="font-black text-sm tracking-wide">
            BÁO ĐỘNG ĐỎ: TÀI XẾ BÁO SOS KHẨN CẤP DỌC ĐƯỜNG!
          </h2>
          <p class="text-xs text-red-100">
            Xe <strong>{{ sosVehicle?.license_plate || '43B-012.34' }}</strong> (Bác tài: {{ sosVehicle?.driver_name || 'Nguyễn Văn Hùng' }} - SĐT: {{ sosVehicle?.driver_phone || '0905.123.456' }}) đang gặp sự cố tại: {{ sosVehicle?.location_text || 'Đèo Ngang - Quảng Bình' }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button 
          @click="toggleAudioMute" 
          class="px-3 py-1.5 bg-red-800 hover:bg-red-900 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer"
        >
          <span>{{ isMuted ? '🔇 Bật Còi' : '🔊 Tắt Còi' }}</span>
        </button>
        <button 
          @click="resolveSos" 
          class="px-4 py-1.5 bg-white text-red-700 hover:bg-slate-100 font-black rounded-lg text-xs transition shadow-sm cursor-pointer"
        >
          ✓ ĐÃ ĐIỀU CỨU HỘ & TẮT SOS
        </button>
      </div>
    </div>

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 w-full space-y-4">
      <!-- TOP CONTROL BAR: CHỌN XE & CHỌN NGÀY & TRẠNG THÁI -->
      <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm space-y-3">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center font-bold text-lg">
              📡
            </div>
            <div>
              <h1 class="text-base font-black text-slate-900 flex items-center gap-2">
                <span>Giám Sát Vị Trí Xe Trực Tuyến (GPS Box IoT)</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800">
                  {{ filteredBuses.length }} xe hiển thị
                </span>
              </h1>
              <p class="text-xs text-slate-500">
                Theo dõi vị trí GPS, vận tốc, trạng thái dừng nghỉ ngơi & báo cáo thời gian thực từ bác tài
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Nút kích hoạt Demo SOS cho người dùng test -->
            <button
              v-if="!isSosActive"
              @click="triggerSos"
              class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 font-bold rounded-xl text-xs border border-red-200 transition flex items-center gap-1.5 cursor-pointer"
              title="Mô phỏng tài xế ấn còi khẩn cấp trên xe"
            >
              <span>🚨</span>
              <span>Test Còi SOS</span>
            </button>

            <!-- Real-time live pulse badge -->
            <button
              @click="autoRefresh = !autoRefresh"
              class="px-3 py-1.5 rounded-xl text-xs font-bold border transition flex items-center gap-1.5 cursor-pointer"
              :class="autoRefresh ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200'"
            >
              <span class="w-2 h-2 rounded-full" :class="autoRefresh ? 'bg-emerald-500 animate-ping' : 'bg-slate-400'"></span>
              <span>{{ autoRefresh ? 'Live GPS: Tự đồng bộ (8s)' : 'Tự đồng bộ: Đang tắt' }}</span>
            </button>

            <!-- Nút Refresh ngay -->
            <button
              @click="refreshDataNow"
              class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer"
              title="Làm mới dữ liệu ngay"
            >
              🔄
            </button>
          </div>
        </div>

        <!-- BỘ LỌC CHỌN XE & CHỌN NGÀY & TRẠNG THÁI -->
        <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
          <!-- 1. CHỌN NGÀY (4 Cột) -->
          <div class="lg:col-span-4 flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 shrink-0 flex items-center gap-1">
              <span>📅</span>
              <span>Chọn ngày:</span>
            </span>
            <input
              type="date"
              v-model="currentDate"
              @change="applyFilters"
              class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-blue-500 cursor-pointer"
            />
            <div class="flex items-center gap-1 shrink-0">
              <button
                @click="setDate('TODAY')"
                :class="isToday ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold'"
                class="px-2 py-1 rounded-lg text-[11px] transition cursor-pointer"
              >
                Hôm nay
              </button>
              <button
                @click="setDate('TOMORROW')"
                class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-semibold transition cursor-pointer"
              >
                Ngày mai
              </button>
            </div>
          </div>

          <!-- 2. CHỌN XE (4 Cột) -->
          <div class="lg:col-span-4 flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 shrink-0 flex items-center gap-1">
              <span>🚍</span>
              <span>Chọn xe:</span>
            </span>
            <select
              v-model="currentBusId"
              @change="applyFilters"
              class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-blue-500 cursor-pointer truncate"
            >
              <option value="ALL">-- Tất Cả Các Xe Trong Đội --</option>
              <option v-for="b in (allBuses || [])" :key="b.id" :value="b.id">
                {{ b.license_plate }} ({{ b.bus_type || 'Giường Nằm' }})
              </option>
            </select>
          </div>

          <!-- 3. CHỌN TRẠNG THÁI CHUYẾN (4 Cột) -->
          <div class="lg:col-span-4 flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 shrink-0 flex items-center gap-1">
              <span>🚦</span>
              <span>Trạng thái:</span>
            </span>
            <select
              v-model="currentStatus"
              @change="applyFilters"
              class="flex-1 bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-blue-500 cursor-pointer"
            >
              <option value="ALL">Tất cả trạng thái</option>
              <option value="IN_TRANSIT">🚍 Đang lăn bánh trên đường</option>
              <option value="REST_STOP">☕ Đang dừng nghỉ chân</option>
              <option value="BOARDING">🎫 Đang đón khách tại bến</option>
              <option value="COMPLETED">🏁 Đã hoàn thành chuyến</option>
            </select>
          </div>
        </div>
      </div>

      <!-- GRID CHÍNH: CỘT TRÁI (DANH SÁCH XE) & CỘT PHẢI (BẢN ĐỒ + NHẬT KÝ SỰ KIỆN) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
        <!-- Danh sách xe bên trái (4 Cột) -->
        <div class="lg:col-span-4 space-y-2.5 max-h-[750px] overflow-y-auto pr-1">
          <div class="flex items-center justify-between pb-1">
            <span class="text-xs font-black uppercase text-slate-500 tracking-wider">
              DANH SÁCH PHƯƠNG TIỆN ({{ filteredBuses.length }} XE)
            </span>
            <span class="text-[11px] text-slate-400 font-mono">
              Ngày: {{ currentDate }}
            </span>
          </div>

          <div
            v-for="bus in filteredBuses"
            :key="bus.trip_id"
            @click="focusBus(bus)"
            class="p-3.5 bg-white rounded-2xl border transition-all cursor-pointer select-none relative overflow-hidden"
            :class="[
              bus.is_sos 
                ? 'bg-red-50 border-red-400 ring-2 ring-red-500/30 shadow-xs' 
                : (selectedBus?.trip_id === bus.trip_id ? 'border-blue-500 ring-2 ring-blue-500/20 bg-blue-50/30 shadow-sm' : 'border-slate-200 hover:border-slate-300')
            ]"
          >
            <div class="flex items-start justify-between">
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-mono font-black text-slate-900 text-sm">{{ bus.license_plate }}</span>
                  
                  <!-- Trạng thái Dừng Nghỉ Chân (Theo yêu cầu) -->
                  <span 
                    v-if="bus.status === 'REST_STOP'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1 animate-pulse"
                  >
                    <span>☕</span>
                    <span>ĐANG DỪNG NGHỈ</span>
                  </span>

                  <!-- Trạng thái Đang Chạy -->
                  <span 
                    v-else-if="bus.status === 'IN_TRANSIT' || bus.status === 'RUNNING'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <span>Đang chạy {{ bus.speed }} km/h</span>
                  </span>

                  <!-- Trạng thái Đón Khách -->
                  <span 
                    v-else-if="['BOARDING', 'SCHEDULED', 'READY'].includes(bus.status)"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200"
                  >
                    🎫 Đón khách
                  </span>

                  <!-- Trạng thái Hoàn Thành -->
                  <span 
                    v-else-if="bus.status === 'COMPLETED'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200"
                  >
                    🏁 Hoàn thành
                  </span>

                  <!-- Báo động SOS -->
                  <span 
                    v-if="bus.is_sos"
                    class="px-2 py-0.5 rounded-full text-[10px] font-black bg-red-600 text-white animate-pulse"
                  >
                    🚨 SOS KHẨN
                  </span>
                </div>

                <div class="text-xs font-bold text-slate-800 mt-1">{{ bus.route_name }}</div>
              </div>

              <span class="text-xs text-slate-400 font-mono font-bold">#{{ bus.trip_code }}</span>
            </div>

            <div class="mt-2.5 pt-2 border-t border-slate-100 text-xs space-y-1 text-slate-600">
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Vị trí:</span>
                <span class="font-medium text-slate-800 text-right truncate max-w-[210px]" :title="bus.location_text">
                  {{ bus.location_text }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Bác tài:</span>
                <span class="font-bold text-slate-900">{{ bus.driver_name }} ({{ bus.driver_phone }})</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400">Khách trên xe:</span>
                <span class="font-bold text-slate-800">{{ bus.passenger_count }} khách</span>
              </div>
              <div class="flex items-center justify-between text-[11px] text-slate-400 pt-0.5">
                <span>Giờ xuất bến:</span>
                <span class="font-mono font-bold text-slate-600">{{ bus.departure_time }}</span>
              </div>
            </div>
          </div>

          <div v-if="filteredBuses.length === 0" class="p-8 text-center bg-white rounded-2xl border border-slate-200 text-slate-400 space-y-2">
            <span class="text-3xl block">🚍</span>
            <p class="font-bold text-xs text-slate-600">Không tìm thấy xe nào phù hợp với bộ lọc</p>
            <p class="text-[11px] text-slate-400">Hãy thử đổi ngày hoặc chọn "Tất cả các xe" để kiểm tra.</p>
            <button
              @click="resetFilters"
              class="px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200 cursor-pointer"
            >
              Đặt lại bộ lọc
            </button>
          </div>
        </div>

        <!-- Bên phải (8 Cột): Bản đồ Leaflet & Nhật ký sự kiện thời gian thực -->
        <div class="lg:col-span-8 space-y-4">
          <!-- Leaflet Map Container -->
          <div class="bg-white p-3 rounded-3xl border border-slate-200 shadow-sm space-y-3">
            <div 
              id="tracking-map" 
              class="w-full h-[540px] rounded-2xl border border-slate-200 z-10 relative overflow-hidden bg-slate-100"
            >
              <!-- Overlay Info khi chọn xe -->
              <div 
                v-if="selectedBus" 
                class="absolute bottom-4 left-4 right-4 bg-white/95 backdrop-blur-md p-3.5 rounded-2xl border border-slate-200 shadow-lg z-[1000] flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
              >
                <div>
                  <div class="flex items-center gap-2">
                    <span class="font-mono font-black text-slate-900 text-sm">🚍 {{ selectedBus.license_plate }}</span>
                    <span class="text-slate-400">•</span>
                    <span class="font-bold text-slate-800">{{ selectedBus.route_name }}</span>
                    <span 
                      v-if="selectedBus.status === 'REST_STOP'" 
                      class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300"
                    >
                      ☕ ĐANG DỪNG NGHỈ
                    </span>
                  </div>
                  <div class="text-[11px] text-slate-500 mt-1">
                    Vị trí: <strong class="text-slate-800">{{ selectedBus.location_text }}</strong> • Tốc độ: <strong class="text-emerald-700 font-mono">{{ selectedBus.speed }} km/h</strong> • Tài xế: <strong>{{ selectedBus.driver_name }}</strong> ({{ selectedBus.driver_phone }})
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <a 
                    :href="`tel:${selectedBus.driver_phone}`" 
                    class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold rounded-xl transition flex items-center gap-1 cursor-pointer"
                  >
                    <span>📞</span>
                    <span>Gọi Bác Tài</span>
                  </a>
                  <button 
                    @click="triggerSosForBus(selectedBus)" 
                    class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 font-bold rounded-xl border border-red-200 transition flex items-center gap-1 cursor-pointer"
                  >
                    <span>🚨</span>
                    <span>Báo SOS</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- LỘ TRÌNH TUYẾN ĐƯỜNG & TIẾN ĐỘ CHUYẾN ĐI CỦA XE (YÊU CẦU 3) -->
          <div v-if="selectedBus" class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-100">
              <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">🛣️</span>
                <div>
                  <div class="flex items-center gap-2">
                    <h3 class="font-black text-slate-900 text-xs sm:text-sm">
                      Tiến Độ Tuyến Đường: {{ selectedBus.route_name }}
                    </h3>
                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                      {{ selectedBus.license_plate }}
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-400">
                    Chuyến #{{ selectedBus.trip_code }} • Xuất bến: <strong class="text-slate-700">{{ selectedBus.departure_time }}</strong> • Đến bến: <strong class="text-slate-700">{{ selectedBus.arrival_time }}</strong>
                  </p>
                </div>
              </div>

              <!-- Trạng thái hiện tại -->
              <div>
                <span 
                  v-if="selectedBus.status === 'REST_STOP'"
                  class="px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1.5 animate-pulse"
                >
                  <span>☕</span>
                  <span>ĐANG DỪNG NGHỈ CHÂN</span>
                </span>
                <span 
                  v-else-if="selectedBus.status === 'IN_TRANSIT' || selectedBus.status === 'RUNNING'"
                  class="px-2.5 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1.5"
                >
                  <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                  <span>ĐANG LĂN BÁNH ({{ selectedBus.speed }} km/h)</span>
                </span>
                <span 
                  v-else-if="['BOARDING', 'SCHEDULED', 'READY'].includes(selectedBus.status)"
                  class="px-2.5 py-1 rounded-full text-xs font-black bg-blue-100 text-blue-800 border border-blue-300 flex items-center gap-1.5"
                >
                  <span>🎫</span>
                  <span>ĐANG ĐÓN KHÁCH TẠI BẾN</span>
                </span>
                <span 
                  v-else-if="selectedBus.status === 'COMPLETED'"
                  class="px-2.5 py-1 rounded-full text-xs font-black bg-purple-100 text-purple-800 border border-purple-300 flex items-center gap-1.5"
                >
                  <span>🏁</span>
                  <span>ĐÃ VỀ BẾN AN TOÀN</span>
                </span>
              </div>
            </div>

            <!-- THANH TIẾN ĐỘ TRỰC QUAN CÁC TRẠM DỌC TUYẾN -->
            <div class="relative py-2">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div 
                  v-for="(stop, idx) in currentRouteStops" 
                  :key="stop.id || idx"
                  class="p-3 rounded-2xl border transition-all relative overflow-hidden"
                  :class="[
                    getStopProgress(stop, idx, currentRouteStops.length) === 'PASSED'
                      ? 'bg-emerald-50/60 border-emerald-200 text-slate-800'
                      : (getStopProgress(stop, idx, currentRouteStops.length) === 'CURRENT'
                          ? (selectedBus.status === 'REST_STOP' ? 'bg-amber-50 border-amber-300 ring-2 ring-amber-400/30' : 'bg-blue-50 border-blue-300 ring-2 ring-blue-400/30')
                          : 'bg-slate-50/60 border-slate-200 text-slate-500')
                  ]"
                >
                  <div class="flex items-center justify-between pb-1.5 border-b border-slate-200/50">
                    <div class="flex items-center gap-1.5">
                      <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black"
                        :class="[
                          getStopProgress(stop, idx, currentRouteStops.length) === 'PASSED'
                            ? 'bg-emerald-600 text-white'
                            : (getStopProgress(stop, idx, currentRouteStops.length) === 'CURRENT'
                                ? (selectedBus.status === 'REST_STOP' ? 'bg-amber-600 text-white animate-pulse' : 'bg-blue-600 text-white animate-pulse')
                                : 'bg-slate-300 text-slate-700')
                        ]"
                      >
                        {{ idx + 1 }}
                      </span>
                      <span class="text-[11px] font-black uppercase tracking-wider">
                        {{ idx === 0 ? 'Bến xuất phát' : (idx === currentRouteStops.length - 1 ? 'Bến điểm đến' : 'Trạm dừng / Nghỉ') }}
                      </span>
                    </div>

                    <span 
                      v-if="getStopProgress(stop, idx, currentRouteStops.length) === 'PASSED'"
                      class="text-[10px] font-black text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md"
                    >
                      ✓ ĐÃ QUA
                    </span>
                    <span 
                      v-else-if="getStopProgress(stop, idx, currentRouteStops.length) === 'CURRENT'"
                      class="text-[10px] font-black px-2 py-0.5 rounded-md"
                      :class="selectedBus.status === 'REST_STOP' ? 'text-amber-900 bg-amber-200 animate-pulse' : 'text-blue-900 bg-blue-200 animate-pulse'"
                    >
                      {{ selectedBus.status === 'REST_STOP' ? '☕ ĐANG NGHỈ TẠI ĐÂY' : '🚍 VỊ TRÍ HIỆN TẠI' }}
                    </span>
                    <span 
                      v-else
                      class="text-[10px] font-bold text-slate-400 bg-slate-200/60 px-2 py-0.5 rounded-md"
                    >
                      ⏳ SẮP ĐẾN
                    </span>
                  </div>

                  <div class="mt-2 space-y-1">
                    <h4 class="font-black text-xs text-slate-900 leading-snug">
                      {{ stop.stop_name }}
                    </h4>
                    <p class="text-[11px] text-slate-500 truncate" :title="stop.address || stop.stop_name">
                      {{ stop.address || (idx === 0 ? 'Khu vực xuất phát' : (idx === currentRouteStops.length - 1 ? 'Khu vực bến cuối' : 'Điểm dừng nghỉ ngơi & trả khách')) }}
                    </p>
                    <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-200/40">
                      <span>Cự ly: <strong class="text-slate-700 font-mono">{{ stop.distance_km || (idx * 60) }} km</strong></span>
                      <span v-if="stop.est_minutes">Dự kiến: <strong class="text-slate-700 font-mono">{{ stop.est_minutes }} phút</strong></span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- NHẬT KÝ THỜI GIAN THỰC TỪ BÁC TÀI GỬI VỀ ADMIN (YÊU CẦU 3) -->
          <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-100">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                <h3 class="font-black text-slate-900 text-xs sm:text-sm uppercase tracking-wide">
                  Nhật Ký Tác Vụ Thời Gian Thực Của Đội Xe (Live Audit Stream)
                </h3>
              </div>

              <!-- BỘ LỌC THEO TỪNG XE RIÊNG HOẶC TẤT CẢ CÁC XE -->
              <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-bold shrink-0">
                <button
                  @click="auditStreamFilter = 'SELECTED_BUS'"
                  :disabled="!selectedBus"
                  :class="[
                    auditStreamFilter === 'SELECTED_BUS' 
                      ? 'bg-blue-600 text-white shadow-xs' 
                      : 'text-slate-700 hover:bg-white',
                    !selectedBus ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                  ]"
                  class="px-2.5 py-1 rounded-lg transition flex items-center gap-1.5"
                  :title="selectedBus ? `Xem nhật ký riêng xe ${selectedBus.license_plate}` : 'Vui lòng chọn 1 xe'"
                >
                  <span>🚍</span>
                  <span>Xe {{ selectedBus?.license_plate || 'Đang Chọn' }} ({{ selectedBusEvents.length }})</span>
                </button>
                <button
                  @click="auditStreamFilter = 'ALL'"
                  :class="auditStreamFilter === 'ALL' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-700 hover:bg-white'"
                  class="px-2.5 py-1 rounded-lg transition flex items-center gap-1.5 cursor-pointer"
                  title="Xem toàn bộ nhật ký của tất cả các xe trong đội"
                >
                  <span>🌐</span>
                  <span>Tất Cả Các Xe ({{ (recentEvents || []).length }})</span>
                </button>
              </div>
            </div>

            <!-- THÔNG BÁO XE ĐANG XEM NHẬT KÝ -->
            <div 
              v-if="auditStreamFilter === 'SELECTED_BUS' && selectedBus" 
              class="px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs flex items-center justify-between"
            >
              <span>
                Đang hiển thị tác vụ riêng của xe: <strong class="font-mono font-bold">{{ selectedBus.license_plate }}</strong> (Chuyến #{{ selectedBus.trip_code }} - Bác tài: {{ selectedBus.driver_name }})
              </span>
              <button 
                @click="auditStreamFilter = 'ALL'" 
                class="text-[11px] font-bold text-blue-700 hover:underline cursor-pointer ml-2"
              >
                Xem tất cả các xe
              </button>
            </div>

            <div class="divide-y divide-slate-100 max-h-56 overflow-y-auto">
              <div 
                v-for="ev in displayedEvents" 
                :key="ev.id" 
                class="py-2.5 flex items-start justify-between gap-3 text-xs hover:bg-slate-50/70 transition px-2 rounded-xl"
              >
                <div class="flex items-start gap-2.5">
                  <span class="text-base mt-0.5 shrink-0">
                    <span v-if="ev.action === 'TRIP_REST_STOP'">☕</span>
                    <span v-else-if="ev.action === 'TRIP_RESUMED'">▶️</span>
                    <span v-else-if="ev.action === 'TRIP_DEPARTED'">🚀</span>
                    <span v-else-if="ev.action === 'TRIP_COMPLETED'">🏁</span>
                    <span v-else-if="ev.action === 'INCIDENT_REPORTED'">🚨</span>
                    <span v-else>📋</span>
                  </span>
                  <div>
                    <div class="flex items-center gap-2">
                      <span 
                        v-if="ev.bus_plate" 
                        class="px-1.5 py-0.5 rounded font-mono text-[10px] font-black bg-slate-100 text-slate-800 border border-slate-200"
                      >
                        {{ ev.bus_plate }}
                      </span>
                      <p class="font-bold text-slate-800 leading-snug">{{ ev.description }}</p>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                      Thực hiện bởi: <strong class="text-slate-600">{{ ev.performed_by }}</strong>
                    </p>
                  </div>
                </div>

                <span class="font-mono text-[11px] text-slate-400 whitespace-nowrap shrink-0">
                  {{ ev.time }}
                </span>
              </div>

              <div v-if="displayedEvents.length === 0" class="py-6 text-center text-slate-400 text-xs space-y-1">
                <div class="text-xl">📭</div>
                <div v-if="auditStreamFilter === 'SELECTED_BUS' && selectedBus">
                  Chưa có thao tác mới phát sinh từ xe <strong class="text-slate-700">{{ selectedBus.license_plate }}</strong> trên chuyến này.
                </div>
                <div v-else>
                  Chưa có thao tác mới nào từ các bác tài.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  liveBuses: Array,
  allBuses: Array,
  selectedDate: String,
  selectedBusId: [String, Number],
  selectedStatus: String,
  activeSosCount: Number,
  recentEvents: Array,
});

const currentDate = ref(props.selectedDate || new Date().toISOString().split('T')[0]);
const currentBusId = ref(props.selectedBusId || 'ALL');
const currentStatus = ref(props.selectedStatus || 'ALL');
const autoRefresh = ref(true);

const isMuted = ref(false);
const selectedBus = ref(props.liveBuses?.[0] || null);
const auditStreamFilter = ref('SELECTED_BUS');
let leafletMap = null;
let markersGroup = null;
let audioInterval = null;
let audioContext = null;
let reloadTimer = null;

const selectedBusEvents = computed(() => {
  if (!selectedBus.value || !props.recentEvents) return [];
  const plate = selectedBus.value.license_plate;
  const tripId = selectedBus.value.trip_id;
  const busId = selectedBus.value.bus_id;

  return props.recentEvents.filter(ev => {
    if (ev.trip_id && ev.trip_id === tripId) return true;
    if (ev.bus_plate && ev.bus_plate === plate) return true;
    if (ev.bus_id && ev.bus_id === busId) return true;
    if (ev.description && (ev.description.includes(plate) || ev.description.includes(`Xe ${plate}`))) return true;
    return false;
  });
});

const displayedEvents = computed(() => {
  if (auditStreamFilter.value === 'SELECTED_BUS' && selectedBus.value) {
    return selectedBusEvents.value;
  }
  return props.recentEvents || [];
});

const currentRouteStops = computed(() => {
  if (!selectedBus.value) return [];
  const rawStops = selectedBus.value.stops || [];
  if (rawStops.length > 0) return rawStops;
  
  return [
    { id: 1, stop_name: selectedBus.value.origin || 'Bến xe xuất phát', distance_km: 0, est_minutes: 0, stop_order: 0 },
    { id: 2, stop_name: 'Trạm Dừng Chân Nghỉ Ngơi (Hành khách thư giãn)', distance_km: 65, est_minutes: 90, stop_order: 1 },
    { id: 3, stop_name: selectedBus.value.destination || 'Bến xe điểm đến', distance_km: 125, est_minutes: 180, stop_order: 2 }
  ];
});

const getStopProgress = (stop, index, totalStops) => {
  if (!selectedBus.value) return 'UPCOMING';
  const status = selectedBus.value.status;

  if (status === 'COMPLETED') {
    return 'PASSED';
  }
  if (status === 'BOARDING' || status === 'SCHEDULED' || status === 'READY') {
    return index === 0 ? 'CURRENT' : 'UPCOMING';
  }
  if (status === 'REST_STOP') {
    const isRest = (stop.stop_name || '').toLowerCase().includes('nghỉ') || (stop.stop_name || '').toLowerCase().includes('dừng') || (index === 1 && totalStops >= 3);
    if (isRest) return 'CURRENT';
    return index < 1 ? 'PASSED' : 'UPCOMING';
  }
  if (status === 'IN_TRANSIT' || status === 'RUNNING') {
    if (index === 0) return 'PASSED';
    if (index === 1 && totalStops > 2) return 'CURRENT';
    return 'UPCOMING';
  }
  return 'UPCOMING';
};

const isToday = computed(() => {
  const todayStr = new Date().toISOString().split('T')[0];
  return currentDate.value === todayStr;
});

const isSosActive = computed(() => {
  return props.activeSosCount > 0 || (props.liveBuses && props.liveBuses.some(b => b.is_sos));
});

const sosVehicle = computed(() => {
  return (props.liveBuses && props.liveBuses.find(b => b.is_sos)) || props.liveBuses?.[0];
});

const filteredBuses = computed(() => {
  if (!props.liveBuses) return [];
  return props.liveBuses;
});

const applyFilters = () => {
  router.get('/admin/tracking', {
    date: currentDate.value,
    bus_id: currentBusId.value,
    status: currentStatus.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['liveBuses', 'selectedDate', 'selectedBusId', 'selectedStatus', 'activeSosCount', 'recentEvents'],
  });
};

const setDate = (type) => {
  const d = new Date();
  if (type === 'TOMORROW') {
    d.setDate(d.getDate() + 1);
  }
  currentDate.value = d.toISOString().split('T')[0];
  applyFilters();
};

const resetFilters = () => {
  currentDate.value = new Date().toISOString().split('T')[0];
  currentBusId.value = 'ALL';
  currentStatus.value = 'ALL';
  applyFilters();
};

const refreshDataNow = () => {
  router.reload({
    only: ['liveBuses', 'activeSosCount', 'recentEvents'],
  });
};

// Sound synthesizer for emergency SOS Siren
const playSosBeep = () => {
  if (isMuted.value) return;
  try {
    if (!audioContext) {
      audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
    const osc = audioContext.createOscillator();
    const gain = audioContext.createGain();
    osc.connect(gain);
    gain.connect(audioContext.destination);

    osc.type = 'sawtooth';
    osc.frequency.setValueAtTime(880, audioContext.currentTime);
    osc.frequency.exponentialRampToValueAtTime(440, audioContext.currentTime + 0.35);
    gain.gain.setValueAtTime(0.15, audioContext.currentTime);
    gain.gain.linearRampToValueAtTime(0.01, audioContext.currentTime + 0.4);

    osc.start();
    osc.stop(audioContext.currentTime + 0.4);
  } catch (e) {}
};

const toggleAudioMute = () => {
  isMuted.value = !isMuted.value;
};

const triggerSos = () => {
  router.post('/admin/tracking/trigger-sos-demo', {
    trip_id: selectedBus.value?.trip_id || 39,
  });
};

const triggerSosForBus = (bus) => {
  router.post('/admin/tracking/trigger-sos-demo', {
    trip_id: bus.trip_id,
  });
};

const resolveSos = () => {
  router.post('/admin/tracking/resolve-sos-demo', {
    trip_id: sosVehicle.value?.trip_id || 39,
  });
};

const focusBus = (bus) => {
  selectedBus.value = bus;
  auditStreamFilter.value = 'SELECTED_BUS';
  if (leafletMap && bus.lat && bus.lng) {
    leafletMap.flyTo([bus.lat, bus.lng], 13, { duration: 1.2 });
  }
};

const updateMapMarkers = () => {
  if (!leafletMap) return;
  if (!markersGroup) {
    markersGroup = L.featureGroup().addTo(leafletMap);
  } else {
    markersGroup.clearLayers();
  }

  const boundsList = [];

  if (filteredBuses.value && filteredBuses.value.length > 0) {
    filteredBuses.value.forEach((bus) => {
      if (!bus.lat || !bus.lng) return;

      const isSos = bus.is_sos;
      const isRest = bus.status === 'REST_STOP';
      const bgColor = isSos ? '#dc2626' : (isRest ? '#f59e0b' : '#2563eb');
      const iconChar = isSos ? '🚨' : (isRest ? '☕' : '🚍');

      const iconHtml = `
        <div style="position:relative; display:flex; align-items:center; justify-content:center;">
          <div style="width:38px; height:38px; border-radius:50%; background:${bgColor}; color:white; display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:bold; box-shadow:0 4px 10px rgba(0,0,0,0.35); border:2.5px solid white;">
            ${iconChar}
          </div>
          <div style="position:absolute; top:-24px; white-space:nowrap; background:#0f172a; color:white; font-family:monospace; font-weight:bold; font-size:10px; padding:2px 6px; border-radius:5px; box-shadow:0 2px 4px rgba(0,0,0,0.25);">
            ${bus.license_plate}
          </div>
        </div>
      `;

      const customIcon = L.divIcon({
        html: iconHtml,
        className: 'bus-gps-marker',
        iconSize: [38, 38],
        iconAnchor: [19, 19],
      });

      const marker = L.marker([bus.lat, bus.lng], { icon: customIcon }).addTo(markersGroup);
      boundsList.push([bus.lat, bus.lng]);

      marker.bindPopup(`
        <div style="font-size:12px; padding:4px; font-family:sans-serif; min-width:180px;">
          <div style="font-family:monospace; font-weight:900; color:#0f172a; font-size:14px; display:flex; align-items:center; justify-content:space-between;">
            <span>🚍 ${bus.license_plate}</span>
            <span style="font-size:10px; color:#64748b;">#${bus.trip_code}</span>
          </div>
          <div style="color:#1e293b; font-weight:bold; margin-top:3px;">${bus.route_name}</div>
          <div style="font-size:11px; color:#475569; margin-top:4px;">
            Trạng thái: <strong style="color:${isRest ? '#b45309' : '#15803d'};">${isRest ? '☕ Đang dừng nghỉ ngơi' : (bus.speed > 0 ? `Đang chạy ${bus.speed} km/h` : 'Đang đỗ')}</strong>
          </div>
          <div style="font-size:11px; color:#64748b; margin-top:2px;">Vị trí: <strong>${bus.location_text}</strong></div>
          <div style="font-size:11px; color:#64748b;">Tài xế: <strong>${bus.driver_name} (${bus.driver_phone})</strong></div>
          <div style="font-size:11px; color:#64748b;">Hành khách: <strong>${bus.passenger_count} người</strong></div>
        </div>
      `);

      marker.on('click', () => {
        selectedBus.value = bus;
      });
    });

    if (boundsList.length > 0) {
      leafletMap.fitBounds(boundsList, { padding: [60, 60], maxZoom: 12 });
    }
  }
};

const initTrackingMap = () => {
  try {
    const container = document.getElementById('tracking-map');
    if (!container || leafletMap) return;

    leafletMap = L.map('tracking-map', {
      center: [16.0544, 108.2022],
      zoom: 9,
      scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 18,
    }).addTo(leafletMap);

    markersGroup = L.featureGroup().addTo(leafletMap);
    updateMapMarkers();

    setTimeout(() => {
      if (leafletMap) leafletMap.invalidateSize();
    }, 350);
  } catch (err) {
    console.error('Leaflet load error:', err);
  }
};

watch(() => props.liveBuses, () => {
  updateMapMarkers();
}, { deep: true });

onMounted(() => {
  nextTick(() => {
    initTrackingMap();
  });

  // SOS Audio Siren loop
  audioInterval = setInterval(() => {
    if (isSosActive.value) {
      playSosBeep();
    }
  }, 900);

  // Background Live Sync (8s)
  reloadTimer = setInterval(() => {
    if (autoRefresh.value && !document.hidden) {
      router.reload({
        only: ['liveBuses', 'activeSosCount', 'recentEvents'],
      });
    }
  }, 8000);
});

onUnmounted(() => {
  if (audioInterval) clearInterval(audioInterval);
  if (reloadTimer) clearInterval(reloadTimer);
  if (audioContext) {
    try { audioContext.close(); } catch (e) {}
  }
  if (leafletMap) {
    try { leafletMap.remove(); } catch (e) {}
  }
});
</script>
