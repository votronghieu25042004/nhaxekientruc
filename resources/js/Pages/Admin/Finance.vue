<template>
  <div class="min-h-screen flex flex-col bg-slate-50 text-slate-900 selection:bg-[#dc2626] selection:text-white">
    <AdminHeader 
      title="QUẢN LÝ DOANH THU & ĐỐI SOÁT TÀI CHÍNH" 
      subtitle="Báo cáo doanh thu theo ngày (Hôm nay, Hôm qua, Tùy chọn), tổng doanh thu tất cả xe và chi tiết từng xe" 
    />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full space-y-6">
      
      <!-- TOP CONTROL & FILTER BAR: CHỌN NGÀY & CHỌN XE -->
      <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-800 border border-emerald-200">
                REVENUE ANALYTICS & FLEET MANAGEMENT
              </span>
              <span class="text-xs font-bold text-slate-400">•</span>
              <span class="text-xs font-bold text-emerald-700 flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Dữ liệu thời gian thực
              </span>
            </div>
            <h1 class="text-xl font-black text-slate-900 mt-1 flex items-center gap-2">
              <span>💰 Quản Lý Doanh Thu: {{ metrics?.display_date || 'Hôm Nay' }}</span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
              Theo dõi biến động dòng tiền, so sánh với ngày hôm qua, tổng thu của toàn bộ đội xe hoặc lọc chi tiết theo từng xe cụ thể
            </p>
          </div>

          <!-- BỘ LỌC THỜI GIAN NHANH 4 NÚT -->
          <div class="flex items-center gap-1.5 flex-wrap bg-slate-100 p-1.5 rounded-2xl border border-slate-200">
            <button
              type="button"
              @click="changePeriod('today')"
              class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5"
              :class="period === 'today' ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-200'"
            >
              <span>⚡</span>
              <span>Hôm Nay</span>
            </button>

            <button
              type="button"
              @click="changePeriod('yesterday')"
              class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5"
              :class="period === 'yesterday' ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-200'"
            >
              <span>⏪</span>
              <span>Hôm Qua</span>
            </button>

            <button
              type="button"
              @click="changePeriod('7days')"
              class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5"
              :class="period === '7days' ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-200'"
            >
              <span>🗓️</span>
              <span>7 Ngày Qua</span>
            </button>

            <button
              type="button"
              @click="changePeriod('month')"
              class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5"
              :class="period === 'month' ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-white text-slate-700 hover:bg-slate-200'"
            >
              <span>📅</span>
              <span>Tháng Này</span>
            </button>
          </div>
        </div>

        <!-- HÀNG LỌC CHI TIẾT: CHỌN NGÀY TÙY Ý + CHỌN XE CỤ THỂ + LOẠI NGÀY -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
          <!-- 1. Ô Chọn Ngày Cụ Thể (Date Picker) -->
          <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1 flex items-center gap-1">
              <span>📅 Chọn Ngày Xem Báo Cáo</span>
            </label>
            <div class="relative flex items-center">
              <input
                type="date"
                v-model="dateInput"
                @change="onDateInputChange"
                class="w-full bg-slate-50 hover:bg-white focus:bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 outline-none focus:ring-2 focus:ring-red-500 transition cursor-pointer"
              />
            </div>
          </div>

          <!-- 2. Dropdown Lọc Theo Từng Xe -->
          <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1 flex items-center gap-1">
              <span>🚌 Lọc Theo Xe (Tất Cả Hoặc Từng Xe)</span>
            </label>
            <select
              v-model="busFilter"
              @change="onBusFilterChange"
              class="w-full bg-slate-50 hover:bg-white focus:bg-white border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 outline-none focus:ring-2 focus:ring-red-500 transition cursor-pointer"
            >
              <option value="all">★ Tất cả 10 xe trong đội</option>
              <option v-for="b in buses" :key="b.id" :value="b.id">
                {{ b.license_plate }} - {{ b.bus_type }}
              </option>
            </select>
          </div>

          <!-- 3. Chuyển Đổi: Ngày Thu Tiền vs Ngày Xe Chạy -->
          <div>
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1 flex items-center gap-1">
              <span>⚙️ Tiêu Chí Thống Kê Dòng Tiền</span>
            </label>
            <div class="grid grid-cols-2 gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold">
              <button
                type="button"
                @click="onFilterTypeChange('created_at')"
                class="py-1 px-2 rounded-lg transition cursor-pointer truncate"
                :class="filterType === 'created_at' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                title="Tính tiền theo thời điểm khách thanh toán / mua vé"
              >
                💳 Ngày Thu Tiền
              </button>
              <button
                type="button"
                @click="onFilterTypeChange('departure_time')"
                class="py-1 px-2 rounded-lg transition cursor-pointer truncate"
                :class="filterType === 'departure_time' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                title="Tính tiền theo ngày xe xuất bến chạy trên đường"
              >
                🚍 Ngày Xe Chạy
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- 4 THẺ TỔNG QUAN DOANH THU CỦA KỲ ĐÃ CHỌN -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Card 1: TỔNG DOANH THU TẤT CẢ XE (KÈM SO SÁNH VỚI HÔM QUA) -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-xs space-y-2 hover:border-slate-300 transition">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Tổng Doanh Thu Tất Cả Xe</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">💰</span>
          </div>
          <div>
            <p class="text-2xl lg:text-3xl font-black text-slate-900 font-mono tracking-tight">
              {{ formatPrice(metrics?.total_revenue) }}
            </p>
          </div>
          <div class="pt-1 border-t border-slate-100 flex items-center justify-between text-[11px]">
            <span 
              v-if="(metrics?.diff_amount || 0) >= 0"
              class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200"
            >
              <span>▲</span>
              <span>+{{ formatPrice(metrics?.diff_amount) }} (+{{ metrics?.diff_percent }}%)</span>
            </span>
            <span 
              v-else
              class="inline-flex items-center gap-1 font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200"
            >
              <span>▼</span>
              <span>-{{ formatPrice(Math.abs(metrics?.diff_amount)) }} ({{ metrics?.diff_percent }}%)</span>
            </span>
            <span class="text-slate-400 font-medium">so với kỳ trước</span>
          </div>
        </div>

        <!-- Card 2: DOANH THU VÉ ONLINE / VIETQR -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-xs space-y-2 hover:border-slate-300 transition">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Thu Online / VietQR MBBank</span>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">🏦</span>
          </div>
          <div>
            <p class="text-2xl lg:text-3xl font-black text-blue-700 font-mono tracking-tight">
              {{ formatPrice(metrics?.total_paid) }}
            </p>
          </div>
          <div class="pt-1 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
            <span class="font-bold text-blue-800">
              Chiếm {{ onlineSharePercent }}% tổng tiền
            </span>
            <span class="text-slate-400 truncate">STK: 09025042004</span>
          </div>
        </div>

        <!-- Card 3: TIỀN MẶT THU TRÊN XE & TẠI BẾN -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-xs space-y-2 hover:border-slate-300 transition">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Tiền Mặt Thu Trên Xe / Bến</span>
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">💵</span>
          </div>
          <div>
            <p class="text-2xl lg:text-3xl font-black text-amber-700 font-mono tracking-tight">
              {{ formatPrice(metrics?.total_cash) }}
            </p>
          </div>
          <div class="pt-1 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
            <span class="font-bold text-amber-800">
              Chiếm {{ cashSharePercent }}% tổng tiền
            </span>
            <span class="text-slate-400">Lơ xe & Quầy thu</span>
          </div>
        </div>

        <!-- Card 4: TỔNG VÉ BÁN & CÔNG SUẤT LẤP ĐẦY -->
        <div class="bg-white border border-slate-200 p-5 rounded-2xl shadow-xs space-y-2 hover:border-slate-300 transition">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Tổng Số Vé Đã Bán</span>
            <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold">🎫</span>
          </div>
          <div>
            <p class="text-2xl lg:text-3xl font-black text-purple-700 font-mono tracking-tight">
              {{ metrics?.total_seats_sold || 0 }} <span class="text-sm font-bold text-slate-500">vé</span>
            </p>
          </div>
          <div class="pt-1 border-t border-slate-100 text-[11px] text-slate-500 flex items-center justify-between">
            <span class="font-bold text-purple-800">
              {{ metrics?.total_trips || 0 }} chuyến chạy
            </span>
            <span class="font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
              {{ metrics?.active_buses_count || 0 }}/{{ buses.length }} xe chạy
            </span>
          </div>
        </div>

      </div>

      <!-- TABS CHỨC NĂNG CHÍNH CỦA MODULE TÀI CHÍNH & DOANH THU -->
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Tab 1: DOANH THU TỪNG XE (MỤC TIÊU YÊU CẦU CỦA USER) -->
          <button
            type="button"
            @click="financeTab = 'FLEET_REVENUE'"
            class="px-4 py-2.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2"
            :class="financeTab === 'FLEET_REVENUE' ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
          >
            <span>📊</span>
            <span>Doanh Thu Từng Xe ({{ fleetRevenueList.length }} Xe)</span>
          </button>

          <!-- Tab 2: CHI TIẾT CHUYẾN ĐI TRONG NGÀY -->
          <button
            type="button"
            @click="financeTab = 'DAY_TRIPS'"
            class="px-4 py-2.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2"
            :class="financeTab === 'DAY_TRIPS' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
          >
            <span>🚍</span>
            <span>Chi Tiết Chuyến Đi ({{ dayTrips.length }} Chuyến)</span>
          </button>

          <!-- Tab 3: BÁO CÁO 7 NGÀY GẦN NHẤT -->
          <button
            type="button"
            @click="financeTab = 'DAILY_HISTORY'"
            class="px-4 py-2.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2"
            :class="financeTab === 'DAILY_HISTORY' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
          >
            <span>📈</span>
            <span>Biến Động 7 Ngày Qua</span>
          </button>

          <!-- Tab 4: CA CHỜ THU TIỀN LƠ XE -->
          <button
            type="button"
            @click="financeTab = 'PENDING'"
            class="px-4 py-2.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2"
            :class="financeTab === 'PENDING' ? 'bg-amber-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
          >
            <span>⏳</span>
            <span>Ca Chờ Thu Tiền Lơ Xe</span>
            <span class="px-2 py-0.5 rounded-full text-[10px]" :class="financeTab === 'PENDING' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">
              {{ pendingClosings.length }}
            </span>
          </button>

          <!-- Tab 5: LỊCH SỬ ĐÃ THU TIỀN -->
          <button
            type="button"
            @click="financeTab = 'HISTORY'"
            class="px-4 py-2.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2"
            :class="financeTab === 'HISTORY' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
          >
            <span>📜</span>
            <span>Lịch Sử Đã Thu Tiền (Đối Soát)</span>
          </button>
        </div>
      </div>

      <!-- ==================== TAB 1: BẢNG DOANH THU THEO TỪNG XE ==================== -->
      <div v-if="financeTab === 'FLEET_REVENUE'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div>
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
              <span>🏆 Bảng Doanh Thu Chi Tiết Theo Từng Xe - {{ metrics?.display_date }}</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
              Phân loại chi tiết tiền vé thu được theo từng biển số xe, số chuyến xe chạy, tỷ lệ lấp đầy ghế và tỷ trọng đóng góp
            </p>
          </div>

          <!-- Nút Xuất Báo Cáo / In -->
          <button
            type="button"
            @click="printReport"
            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
          >
            <span>🖨️</span>
            <span>In Báo Cáo Doanh Thu</span>
          </button>
        </div>

        <!-- BẢNG TỔNG KẾT TỪNG XE -->
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200">
              <tr>
                <th class="py-3 px-3 text-center w-12">Hạng</th>
                <th class="py-3 px-3">Biển Số Xe</th>
                <th class="py-3 px-3">Loại Dòng Xe</th>
                <th class="py-3 px-3 text-center">Số Chuyến</th>
                <th class="py-3 px-3 text-center">Số Vé / Ghế</th>
                <th class="py-3 px-3 text-center">Công Suất</th>
                <th class="py-3 px-3 text-right">Online (VietQR)</th>
                <th class="py-3 px-3 text-right">Tiền Mặt</th>
                <th class="py-3 px-3 text-right font-black">Tổng Doanh Thu</th>
                <th class="py-3 px-3 text-center w-36">Tỷ Trọng (%)</th>
                <th class="py-3 px-3 text-center">Hành Động</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr 
                v-for="(b, idx) in fleetRevenueList" 
                :key="b.id" 
                class="hover:bg-slate-50/80 transition"
                :class="{ 'bg-emerald-50/30': idx === 0 && b.total_revenue > 0 }"
              >
                <!-- Hạng -->
                <td class="py-3.5 px-3 text-center">
                  <span
                    v-if="idx === 0 && b.total_revenue > 0"
                    class="w-7 h-7 mx-auto rounded-full bg-amber-100 text-amber-800 font-black flex items-center justify-center text-xs shadow-xs border border-amber-300"
                    title="Xe dẫn đầu doanh thu"
                  >
                    🥇
                  </span>
                  <span
                    v-else-if="idx === 1 && b.total_revenue > 0"
                    class="w-7 h-7 mx-auto rounded-full bg-slate-200 text-slate-700 font-black flex items-center justify-center text-xs border border-slate-300"
                  >
                    🥈
                  </span>
                  <span
                    v-else-if="idx === 2 && b.total_revenue > 0"
                    class="w-7 h-7 mx-auto rounded-full bg-amber-50 text-amber-900 font-black flex items-center justify-center text-xs border border-amber-200"
                  >
                    🥉
                  </span>
                  <span
                    v-else
                    class="w-6 h-6 mx-auto rounded-full bg-slate-100 text-slate-600 font-bold flex items-center justify-center text-[11px]"
                  >
                    #{{ idx + 1 }}
                  </span>
                </td>

                <!-- Biển số xe -->
                <td class="py-3.5 px-3">
                  <div class="font-mono font-black text-slate-900 text-sm flex items-center gap-1.5">
                    <span>🚌</span>
                    <span>{{ b.license_plate }}</span>
                  </div>
                  <div class="text-[10px] text-slate-400 font-medium">Sức chứa: {{ b.total_seats }} chỗ</div>
                </td>

                <!-- Dòng xe -->
                <td class="py-3.5 px-3">
                  <span class="font-semibold text-slate-800">{{ b.bus_type }}</span>
                </td>

                <!-- Số chuyến -->
                <td class="py-3.5 px-3 text-center">
                  <span 
                    class="px-2 py-0.5 rounded-md font-bold text-xs"
                    :class="b.trips_count > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'text-slate-400'"
                  >
                    {{ b.trips_count }} chuyến
                  </span>
                </td>

                <!-- Vé / Ghế -->
                <td class="py-3.5 px-3 text-center">
                  <span class="font-black text-slate-900">{{ b.seats_sold }}</span>
                  <span class="text-slate-400 text-[11px]"> vé</span>
                </td>

                <!-- Công suất lấp đầy -->
                <td class="py-3.5 px-3 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <span 
                      class="font-mono font-bold text-xs"
                      :class="{
                        'text-emerald-700': b.occupancy_rate >= 80,
                        'text-amber-700': b.occupancy_rate >= 50 && b.occupancy_rate < 80,
                        'text-slate-500': b.occupancy_rate < 50
                      }"
                    >
                      {{ b.occupancy_rate }}%
                    </span>
                  </div>
                </td>

                <!-- Tiền Online -->
                <td class="py-3.5 px-3 text-right font-mono font-bold text-blue-700">
                  {{ formatPrice(b.online_amount) }}
                </td>

                <!-- Tiền Mặt -->
                <td class="py-3.5 px-3 text-right font-mono font-bold text-amber-700">
                  {{ formatPrice(b.cash_amount) }}
                </td>

                <!-- Tổng Doanh Thu Xe -->
                <td class="py-3.5 px-3 text-right">
                  <span class="font-mono font-black text-sm text-emerald-700">
                    {{ formatPrice(b.total_revenue) }}
                  </span>
                </td>

                <!-- Tỷ Trọng Đóng Góp (%) & Progress Bar -->
                <td class="py-3.5 px-3">
                  <div class="space-y-1">
                    <div class="flex items-center justify-between text-[10px] font-bold">
                      <span class="text-slate-700">{{ b.share_percent }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden border border-slate-200">
                      <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="idx === 0 ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : 'bg-gradient-to-r from-red-500 to-rose-500'"
                        :style="{ width: Math.min(100, Math.max(3, b.share_percent)) + '%' }"
                      ></div>
                    </div>
                  </div>
                </td>

                <!-- Hành động lọc nhanh -->
                <td class="py-3.5 px-3 text-center">
                  <button
                    type="button"
                    @click="filterByBus(b.id)"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-100 hover:bg-slate-900 hover:text-white text-slate-700 transition cursor-pointer"
                    title="Xem toàn bộ các chuyến đi của xe này"
                  >
                    Xem Chuyến →
                  </button>
                </td>
              </tr>
            </tbody>
            <!-- Dòng Tổng Cộng Đội Xe -->
            <tfoot class="bg-slate-100 text-slate-900 font-black border-t-2 border-slate-300 text-xs">
              <tr>
                <td colspan="3" class="py-3 px-3 uppercase">Tổng Cộng Toàn Bộ Đội Xe:</td>
                <td class="py-3 px-3 text-center">{{ metrics?.total_trips || 0 }} chuyến</td>
                <td class="py-3 px-3 text-center">{{ metrics?.total_seats_sold || 0 }} vé</td>
                <td class="py-3 px-3 text-center">-</td>
                <td class="py-3 px-3 text-right font-mono text-blue-800">{{ formatPrice(metrics?.total_paid) }}</td>
                <td class="py-3 px-3 text-right font-mono text-amber-800">{{ formatPrice(metrics?.total_cash) }}</td>
                <td class="py-3 px-3 text-right font-mono text-emerald-800 text-sm">{{ formatPrice(metrics?.total_revenue) }}</td>
                <td class="py-3 px-3 text-center">100%</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- ==================== TAB 2: CHI TIẾT DOANH THU TỪNG CHUYẾN ĐI ==================== -->
      <div v-if="financeTab === 'DAY_TRIPS'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div>
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
              <span>🚍 Chi Tiết Doanh Thu Từng Chuyến Đi - {{ metrics?.display_date }}</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
              Danh sách toàn bộ các chuyến xuất bến trong ngày cùng lái xe, lơ xe và số tiền vé thu được
            </p>
          </div>

          <div v-if="selectedBusId !== 'all'" class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-600">Đang lọc xe: <strong>#{{ selectedBusId }}</strong></span>
            <button
              type="button"
              @click="filterByBus('all')"
              class="px-2.5 py-1 bg-red-50 text-red-700 hover:bg-red-100 rounded-lg text-xs font-bold transition cursor-pointer"
            >
              ✕ Bỏ Lọc Xe
            </button>
          </div>
        </div>

        <div v-if="dayTrips.length === 0" class="text-center py-12 text-slate-400 space-y-2">
          <div class="text-4xl">📭</div>
          <p class="font-bold text-sm">Không có chuyến đi nào trong kỳ này hoặc xe chưa có lịch chạy.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200">
              <tr>
                <th class="py-3 px-3">Mã Chuyến & Giờ Chạy</th>
                <th class="py-3 px-3">Tuyến Đường</th>
                <th class="py-3 px-3">Xe Phụ Trách</th>
                <th class="py-3 px-3">Tài Xế / Lơ Xe</th>
                <th class="py-3 px-3 text-center">Số Vé / Chỗ</th>
                <th class="py-3 px-3 text-center">Lấp Đầy (%)</th>
                <th class="py-3 px-3 text-right">Online (VietQR)</th>
                <th class="py-3 px-3 text-right">Tiền Mặt</th>
                <th class="py-3 px-3 text-right font-black">Tổng Tiền Vé</th>
                <th class="py-3 px-3 text-center">Trạng Thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="t in dayTrips" :key="t.id" class="hover:bg-slate-50/80 transition">
                <td class="py-3.5 px-3">
                  <div class="font-mono font-black text-slate-900 text-xs">{{ t.trip_code }}</div>
                  <div class="text-[11px] text-red-600 font-bold mt-0.5">⏰ {{ t.departure_time }}</div>
                </td>
                <td class="py-3.5 px-3">
                  <div class="font-bold text-slate-900">{{ t.route_name }}</div>
                </td>
                <td class="py-3.5 px-3">
                  <div class="font-mono font-bold text-slate-800">{{ t.bus_license_plate }}</div>
                  <div class="text-[10px] text-slate-500">{{ t.bus_type }}</div>
                </td>
                <td class="py-3.5 px-3 text-xs">
                  <div>👨‍✈️ {{ t.driver_name }}</div>
                  <div class="text-[11px] text-slate-500">🎫 {{ t.conductor_name }}</div>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span class="font-bold text-slate-900">{{ t.seats_sold }}/{{ t.capacity }}</span>
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span 
                    class="px-2 py-0.5 rounded-md font-mono font-bold text-[11px]"
                    :class="t.occupancy_rate >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600'"
                  >
                    {{ t.occupancy_rate }}%
                  </span>
                </td>
                <td class="py-3.5 px-3 text-right font-mono font-bold text-blue-700">
                  {{ formatPrice(t.online_amount) }}
                </td>
                <td class="py-3.5 px-3 text-right font-mono font-bold text-amber-700">
                  {{ formatPrice(t.cash_amount) }}
                </td>
                <td class="py-3.5 px-3 text-right font-mono font-black text-emerald-700 text-sm">
                  {{ formatPrice(t.total_revenue) }}
                </td>
                <td class="py-3.5 px-3 text-center">
                  <span
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold border"
                    :class="{
                      'bg-emerald-50 text-emerald-700 border-emerald-200': t.status === 'COMPLETED',
                      'bg-blue-50 text-blue-700 border-blue-200': t.status === 'RUNNING',
                      'bg-slate-100 text-slate-600 border-slate-200': t.status !== 'COMPLETED' && t.status !== 'RUNNING'
                    }"
                  >
                    {{ t.status === 'COMPLETED' ? '✓ Đã xong' : (t.status === 'RUNNING' ? 'Đang chạy' : 'Lịch trình') }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ==================== TAB 3: BÁO CÁO DOANH THU 7 NGÀY QUA ==================== -->
      <div v-if="financeTab === 'DAILY_HISTORY'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-xs">
        <div class="pb-3 border-b border-slate-100">
          <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
            <span>📈 Xu Hướng Doanh Thu 7 Ngày Gần Nhất (Daily Revenue Trend)</span>
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            So sánh doanh thu từng ngày trong tuần để nắm bắt ngày nào khách đi đông nhất
          </p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200">
              <tr>
                <th class="py-3 px-4">Ngày</th>
                <th class="py-3 px-4 text-center">Số Lượng Vé Bán</th>
                <th class="py-3 px-4 text-center">Số Giao Dịch</th>
                <th class="py-3 px-4 text-right">Tổng Doanh Thu Cả Đội Xe</th>
                <th class="py-3 px-4 text-center">Thao Tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr 
                v-for="d in dailyHistory" 
                :key="d.date"
                class="hover:bg-slate-50 transition"
                :class="{ 'bg-red-50/40 font-bold': d.is_current }"
              >
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900 text-xs flex items-center gap-2">
                    <span>📅</span>
                    <span>{{ d.date_formatted }}</span>
                    <span v-if="d.is_current" class="px-2 py-0.5 bg-red-600 text-white text-[10px] rounded-md font-bold">
                      Đang chọn
                    </span>
                  </div>
                  <div class="text-[10px] text-slate-400 font-mono">{{ d.date }}</div>
                </td>
                <td class="py-3.5 px-4 text-center font-bold text-slate-900">
                  {{ d.total_seats }} vé
                </td>
                <td class="py-3.5 px-4 text-center text-slate-600">
                  {{ d.bookings_count }} lượt đặt
                </td>
                <td class="py-3.5 px-4 text-right font-mono font-black text-emerald-700 text-sm">
                  {{ formatPrice(d.total_revenue) }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <button
                    type="button"
                    @click="selectSpecificDate(d.date)"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer"
                    :class="d.is_current ? 'bg-slate-900 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                  >
                    Xem Chi Tiết Ngày Này →
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ==================== TAB 4: CA CHỜ THU TIỀN LƠ XE ==================== -->
      <div v-if="financeTab === 'PENDING'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
              <span>📋 Danh Sách Ca Chờ Thu Tiền Mặt Lơ Xe ({{ pendingClosings.length }} ca)</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Kế toán kiểm tra số tiền thực nộp, nhập vào ô và bấm [Xác nhận thu tiền] để nhập quỹ & lưu lịch sử đối soát</p>
          </div>
        </div>

        <div v-if="pendingClosings.length === 0" class="text-center py-10 text-slate-400 space-y-2">
          <div class="text-4xl">✅</div>
          <p class="font-bold text-sm">Tất cả các ca chạy đã được thu tiền và đối soát xong!</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
              <tr>
                <th class="py-3 px-3">Mã Chuyến & Ca</th>
                <th class="py-3 px-3">Tuyến & Xe</th>
                <th class="py-3 px-3">Lơ Xe Chốt Ca</th>
                <th class="py-3 px-3 text-right">App Tính Thu</th>
                <th class="py-3 px-3 text-right">Chi Phí Đã Trừ</th>
                <th class="py-3 px-3 text-right">Thực Nộp (VNĐ)</th>
                <th class="py-3 px-3 text-center">Đối Soát Lệch</th>
                <th class="py-3 px-3 text-center">Hành Động</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="c in pendingClosings" :key="c.id" class="hover:bg-slate-50/70 transition">
                <td class="py-3 px-3">
                  <div class="font-mono font-bold text-slate-900 text-xs">#{{ c.trip?.trip_code }}</div>
                  <div class="text-[10px] text-slate-500">{{ c.created_at ? c.created_at.substring(0, 16) : 'Mới chốt ca' }}</div>
                </td>
                <td class="py-3 px-3">
                  <div class="font-bold text-slate-900">{{ c.trip?.route?.name || 'Đà Nẵng - Quy Nhơn' }}</div>
                  <div class="text-[10px] font-mono text-slate-500">{{ c.trip?.bus?.license_plate || '43B-012.34' }}</div>
                </td>
                <td class="py-3 px-3">
                  <span class="font-medium text-slate-900">{{ c.closed_by || c.trip?.conductor?.name || 'Lơ xe trực ca' }}</span>
                </td>
                <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                  {{ formatPrice(c.expected_cash_from_conductor) }}
                </td>
                <td class="py-3 px-3 text-right font-mono text-rose-700">
                  -{{ formatPrice(c.total_expenses) }}
                </td>
                <td class="py-3 px-3 text-right">
                  <input
                    type="number"
                    v-model.number="cashInput[c.id]"
                    class="w-32 bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1 text-right font-mono font-black text-slate-900 text-xs outline-none focus:bg-white focus:ring-2 focus:ring-red-500"
                  />
                </td>
                <td class="py-3 px-3 text-center">
                  <span
                    v-if="(cashInput[c.id] || c.actual_cash_submitted) == c.expected_cash_from_conductor"
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                  >
                    ✓ Khớp 100%
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"
                  >
                    Lệch: {{ formatPrice((cashInput[c.id] || c.actual_cash_submitted || 0) - c.expected_cash_from_conductor) }}
                  </span>
                </td>
                <td class="py-3 px-3 text-center space-x-1 whitespace-nowrap">
                  <button
                    type="button"
                    @click="confirmCollectCash(c)"
                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black shadow-xs cursor-pointer"
                  >
                    ✓ Thu Tiền
                  </button>
                  <button
                    type="button"
                    @click="openDiscrepancyModal(c)"
                    class="px-2 py-1.5 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 rounded-lg text-xs font-bold border border-slate-200 cursor-pointer"
                    title="Báo lệch tiền & yêu cầu giải trình"
                  >
                    ⚠️
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ==================== TAB 5: LỊCH SỬ ĐÃ THU TIỀN ==================== -->
      <div v-if="financeTab === 'HISTORY'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4 shadow-xs">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900">📜 Lịch Sử Thu Tiền & Nhật Ký Đối Soát</h3>
            <p class="text-xs text-slate-500">Toàn bộ các ca đã bàn giao tiền mặt cho kế toán và nhập sổ quỹ</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200">
              <tr>
                <th class="py-3 px-3">Mã Chuyến</th>
                <th class="py-3 px-3">Tuyến & Xe</th>
                <th class="py-3 px-3">Người Nộp Tiền</th>
                <th class="py-3 px-3 text-right">Tiền Thực Nộp</th>
                <th class="py-3 px-3 text-center">Trạng Thái Đối Soát</th>
                <th class="py-3 px-3">Thời Gian Xác Nhận</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="c in historyClosings" :key="c.id" class="hover:bg-slate-50/70 transition">
                <td class="py-3 px-3 font-mono font-bold text-slate-900">#{{ c.trip?.trip_code }}</td>
                <td class="py-3 px-3">
                  <div class="font-bold text-slate-900">{{ c.trip?.route?.name }}</div>
                  <div class="text-[10px] font-mono text-slate-500">{{ c.trip?.bus?.license_plate }}</div>
                </td>
                <td class="py-3 px-3">{{ c.closed_by || c.trip?.conductor?.name || 'Lơ xe' }}</td>
                <td class="py-3 px-3 text-right font-mono font-black text-emerald-700">
                  {{ formatPrice(c.actual_cash_submitted || c.expected_cash_from_conductor) }}
                </td>
                <td class="py-3 px-3 text-center">
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    ✓ Đã Thu & Nhập Quỹ
                  </span>
                </td>
                <td class="py-3 px-3 text-slate-500 text-[11px] font-mono">
                  {{ c.updated_at ? c.updated_at.substring(0, 16) : 'Hôm nay' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </main>

    <!-- Modal Admin Báo Sai Lệch Tiền & Yêu Cầu Giải Trình -->
    <div v-if="showDiscrepancyModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white border border-slate-200 rounded-2xl p-6 max-w-md w-full shadow-lg space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="text-base font-black text-rose-700">⚠️ Báo Lệch Tiền Bàn Giao</h3>
          <button @click="showDiscrepancyModal = false" class="text-slate-400 hover:text-slate-600 text-xl cursor-pointer">&times;</button>
        </div>

        <div class="bg-slate-50 p-3 rounded-xl text-xs space-y-1 border border-slate-200">
          <div>Chuyến: <strong class="text-slate-900">{{ activeClosing?.trip?.route?.name }}</strong></div>
          <div>Tiền App tính: <strong class="text-slate-700 font-mono">{{ formatPrice(activeClosing?.expected_cash_from_conductor) }}</strong></div>
          <div>Tiền thực nộp: <strong class="text-slate-900 font-mono">{{ formatPrice(cashInput[activeClosing?.id] || activeClosing?.actual_cash_submitted) }}</strong></div>
          <div>Lệch: <strong class="text-rose-700 font-mono">{{ formatPrice((cashInput[activeClosing?.id] || activeClosing?.actual_cash_submitted) - (activeClosing?.expected_cash_from_conductor || 0)) }}</strong></div>
        </div>

        <div class="space-y-1.5 text-xs">
          <label class="font-bold text-slate-700 block">Lý do sai lệch / Yêu cầu giải trình gửi Lơ xe & Tài xế:</label>
          <textarea 
            v-model="auditNote" 
            rows="3" 
            placeholder="Ví dụ: Thiếu 30.000đ tiền vé chặng Điện Bàn, yêu cầu đối soát lại bill chuyển khoản..."
            class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-slate-900 outline-none focus:bg-white focus:ring-2 focus:ring-red-500 font-medium"
          ></textarea>
        </div>

        <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
          <button @click="showDiscrepancyModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">Hủy</button>
          <button @click="submitDiscrepancy" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black shadow-xs cursor-pointer">
            Gửi Báo Lệch Tiền
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';

const props = defineProps({
  metrics: { type: Object, default: () => ({}) },
  fleetRevenueList: { type: Array, default: () => [] },
  dayTrips: { type: Array, default: () => [] },
  dailyHistory: { type: Array, default: () => [] },
  buses: { type: Array, default: () => [] },
  selectedDate: { type: String, default: '' },
  selectedBusId: { type: [String, Number], default: 'all' },
  period: { type: String, default: 'today' },
  filterType: { type: String, default: 'created_at' },
  tripClosings: { type: Array, default: () => [] },
});

// Tab mặc định ưu tiên xem doanh thu theo xe và ngày
const financeTab = ref('FLEET_REVENUE'); // 'FLEET_REVENUE', 'DAY_TRIPS', 'DAILY_HISTORY', 'PENDING', 'HISTORY'
const dateInput = ref(props.selectedDate || new Date().toISOString().substring(0, 10));
const busFilter = ref(props.selectedBusId || 'all');
const showDiscrepancyModal = ref(false);
const activeClosing = ref(null);
const auditNote = ref('');
const cashInput = ref({});

// Khởi tạo cash input cho trip closings
props.tripClosings?.forEach(c => {
  cashInput.value[c.id] = c.actual_cash_submitted || c.expected_cash_from_conductor || 0;
});

// Tỷ trọng % online & tiền mặt
const onlineSharePercent = computed(() => {
  const total = Number(props.metrics?.total_revenue) || 1;
  const online = Number(props.metrics?.total_paid) || 0;
  return Math.min(100, Math.round((online / total) * 100));
});

const cashSharePercent = computed(() => {
  return Math.max(0, 100 - onlineSharePercent.value);
});

// Chuyển đổi period nhanh: today, yesterday, 7days, month
const changePeriod = (p) => {
  router.get('/admin/finance', {
    period: p,
    bus_id: busFilter.value,
    filter_type: props.filterType,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

// Khi người dùng chọn ngày bất kỳ trong date picker
const onDateInputChange = () => {
  if (!dateInput.value) return;
  router.get('/admin/finance', {
    period: 'custom',
    date: dateInput.value,
    bus_id: busFilter.value,
    filter_type: props.filterType,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

// Chọn ngày từ bảng 7 ngày
const selectSpecificDate = (d) => {
  dateInput.value = d;
  router.get('/admin/finance', {
    period: 'custom',
    date: d,
    bus_id: busFilter.value,
    filter_type: props.filterType,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

// Lọc theo xe
const onBusFilterChange = () => {
  router.get('/admin/finance', {
    period: props.period,
    date: props.selectedDate,
    bus_id: busFilter.value,
    filter_type: props.filterType,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

const filterByBus = (busId) => {
  busFilter.value = busId;
  financeTab.value = 'DAY_TRIPS';
  router.get('/admin/finance', {
    period: props.period,
    date: props.selectedDate,
    bus_id: busId,
    filter_type: props.filterType,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

// Đổi tiêu chí: ngày thu tiền vs ngày xe chạy
const onFilterTypeChange = (type) => {
  router.get('/admin/finance', {
    period: props.period,
    date: props.selectedDate,
    bus_id: busFilter.value,
    filter_type: type,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

// Lọc danh sách ca chạy
const pendingClosings = computed(() => {
  return (props.tripClosings || []).filter(c => c.audit_status !== 'AUDITED_OK' && c.audit_status !== 'SETTLED_CASH_SUBMITTED');
});

const historyClosings = computed(() => {
  return (props.tripClosings || []).filter(c => c.audit_status === 'AUDITED_OK' || c.audit_status === 'SETTLED_CASH_SUBMITTED');
});

const confirmCollectCash = (closing) => {
  const amount = cashInput.value[closing.id] !== undefined ? cashInput.value[closing.id] : closing.expected_cash_from_conductor;
  
  if (!confirm(`Xác nhận đã nhận đủ ${formatPrice(amount)} từ Lơ xe (${closing.closed_by || 'Lơ xe chuyến #' + closing.trip?.trip_code})?\n\nSau khi xác nhận, số tiền sẽ được nhập quỹ và lưu vào Lịch Sử Đối Soát.`)) return;

  router.post('/admin/finance/collect-cash', {
    closing_id: closing.id,
    trip_id: closing.trip_id,
    actual_cash_submitted: Number(amount)
  }, {
    preserveScroll: true,
    onSuccess: () => {
      alert(`🟢 XÁC NHẬN THÀNH CÔNG!\nKế toán đã nhận ${formatPrice(amount)}. Ca chạy đã được chuyển vào Lịch Sử Đã Thu Tiền thành công!`);
    }
  });
};

const openDiscrepancyModal = (closing) => {
  activeClosing.value = closing;
  const inputAmt = cashInput.value[closing.id] || closing.actual_cash_submitted || 0;
  const diff = inputAmt - (closing.expected_cash_from_conductor || 0);
  auditNote.value = `Lệch ${formatPrice(Math.abs(diff))}. Cần bổ sung cuống vé hoặc giải trình vé đón dọc đường.`;
  showDiscrepancyModal.value = true;
};

const submitDiscrepancy = () => {
  if (!activeClosing.value) return;
  router.post(`/admin/closings/${activeClosing.value.id}/audit`, {
    status: 'AUDITED_DISCREPANCY',
    audit_note: auditNote.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showDiscrepancyModal.value = false;
      alert('⚠️ Đã gửi yêu cầu giải trình lệch tiền!');
    }
  });
};

const printReport = () => {
  window.print();
};

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(Number(val) || 0) + ' đ';
</script>
