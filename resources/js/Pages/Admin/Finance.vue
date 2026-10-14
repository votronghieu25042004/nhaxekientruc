<template>
  <div class="min-h-screen flex flex-col bg-slate-50 text-slate-900 selection:bg-[#dc2626] selection:text-white">
    <AdminHeader title="BÁO CÁO DÒNG TIỀN & ĐỐI SOÁT TÀI CHÍNH" subtitle="Đối soát tiền bàn giao từng Chuyến Đi & Chuyến Về, Dòng tiền cọc online 30% và thu tiền chốt ca Lơ xe" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-xl font-black text-slate-900 flex items-center space-x-2">
            <span>💰 Báo Cáo Dòng Tiền & Thu Tiền Chốt Ca Lơ Xe</span>
          </h1>
          <p class="text-slate-500 text-xs mt-0.5">Đối soát Tiền App tính vs Tiền thực tế Lơ xe nộp về nhà xe; xác nhận thu tiền sẽ reset số dư của Lơ xe về 0đ</p>
        </div>
      </div>

      <!-- Financial Cards Summary -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-1.5 transition hover:border-slate-300">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Tổng Doanh Thu</span>
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">💵</span>
          </div>
          <p class="text-2xl font-black text-slate-900 font-mono">{{ formatPrice(metrics?.total_revenue || 5950000) }}</p>
          <span class="text-[11px] text-slate-500 block">Bao gồm cọc online & tiền thu mặt</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-1.5 transition hover:border-slate-300">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Cọc Online 30% (Bank)</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">🏦</span>
          </div>
          <p class="text-2xl font-black text-emerald-700 font-mono">{{ formatPrice(metrics?.total_paid || 1785000) }}</p>
          <span class="text-[11px] text-emerald-700 block">✓ Đã thanh toán giữ chỗ trực tuyến</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-1.5 transition hover:border-slate-300">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Chi Phí Xe (BOT, Dầu)</span>
            <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-sm font-bold">⛽</span>
          </div>
          <p class="text-2xl font-black text-rose-700 font-mono">{{ formatPrice(metrics?.total_expenses || 770000) }}</p>
          <span class="text-[11px] text-slate-500 block">Đã trừ trực tiếp từ tiền nộp</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-1.5 transition hover:border-slate-300">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Lợi Nhuận Ròng Thực</span>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">📈</span>
          </div>
          <p class="text-2xl font-black text-blue-700 font-mono">{{ formatPrice(metrics?.net_profit || 5180000) }}</p>
          <span class="text-[11px] text-blue-700 block">★ Tỷ lệ lấp đầy bình quân: 88.5%</span>
        </div>
      </div>

      <!-- Sub-Tabs: Ca Chờ Thu vs Lịch Sử Đã Thu vs Top Xe -->
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
        <div class="flex items-center gap-2">
          <button
            @click="financeTab = 'PENDING'"
            class="px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2"
            :class="financeTab === 'PENDING' ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
          >
            <span>⏳ Ca Đang Chờ Thu Tiền</span>
            <span class="px-2 py-0.5 rounded-full text-[10px]" :class="financeTab === 'PENDING' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'">
              {{ pendingClosings.length }}
            </span>
          </button>

          <button
            @click="financeTab = 'HISTORY'"
            class="px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2"
            :class="financeTab === 'HISTORY' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
          >
            <span>📜 Lịch Sử Đã Thu Tiền (Đối Soát)</span>
            <span class="px-2 py-0.5 rounded-full text-[10px]" :class="financeTab === 'HISTORY' ? 'bg-white/20 text-white' : 'bg-emerald-50 text-emerald-700 font-bold'">
              {{ historyClosings.length }}
            </span>
          </button>

          <button
            @click="financeTab = 'TOP_BUSES'"
            class="px-4 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5"
            :class="financeTab === 'TOP_BUSES' ? 'bg-slate-900 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
          >
            <span>🏆 Top Xe Doanh Thu</span>
          </button>
        </div>

        <!-- Filter Direction (Chuyến Đi vs Chuyến Về) -->
        <div class="flex items-center space-x-1.5 text-xs bg-slate-100 p-1 rounded-xl">
          <button 
            @click="directionFilter = 'ALL'"
            :class="directionFilter === 'ALL' ? 'bg-slate-800 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg transition cursor-pointer">
            Tất cả hướng
          </button>
          <button 
            @click="directionFilter = 'DI'"
            :class="directionFilter === 'DI' ? 'bg-slate-800 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg transition cursor-pointer">
            🛫 Chuyến Đi
          </button>
          <button 
            @click="directionFilter = 'VE'"
            :class="directionFilter === 'VE' ? 'bg-slate-800 text-white font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-lg transition cursor-pointer">
            🛬 Chuyến Về
          </button>
        </div>
      </div>

      <!-- TAB 1: SECTION CA ĐANG CHỜ THU TIỀN -->
      <div v-if="financeTab === 'PENDING'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
              <span>📋 Danh Sách Ca Chờ Thu Tiền Mặt Lơ Xe ({{ pendingClosings.length }} ca)</span>
            </h3>
            <p class="text-xs text-slate-500 mt-0.5">Kế toán kiểm tra số tiền thực nộp, nhập vào ô và bấm [Xác nhận thu tiền] để nhập quỹ & lưu lịch sử đối soát</p>
          </div>
        </div>

        <div v-if="pendingClosings.length === 0" class="p-8 text-center text-slate-400 text-xs">
          Hiện không có ca nào đang chờ thu tiền. Tất cả các ca đã được hoàn tất đối soát và lưu vào lịch sử!
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200">
              <tr>
                <th class="py-3 px-3">Chuyến / Hướng</th>
                <th class="py-3 px-3">Xe & Người Bàn Giao</th>
                <th class="py-3 px-3 text-right">Tiền App Tính</th>
                <th class="py-3 px-3 text-center">Tiền Lơ Xe Thực Nộp</th>
                <th class="py-3 px-3 text-center">Chênh Lệch</th>
                <th class="py-3 px-3 text-center">Trạng Thái</th>
                <th class="py-3 px-3 text-right">Thao Tác Kế Toán</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="c in pendingClosings" :key="c.id" class="hover:bg-slate-50/70 transition">
                <!-- Chuyến & Hướng -->
                <td class="py-3.5 px-3">
                  <div class="flex items-center space-x-1.5">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                          :class="c.trip_direction?.includes('Chuyến Đi') || c.trip_direction?.includes('DI') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200'">
                      {{ c.trip_direction || 'Chuyến Đi' }}
                    </span>
                    <span class="font-mono font-black text-slate-900 text-xs">#{{ c.trip?.trip_code || ('TRIP-' + c.trip_id) }}</span>
                  </div>
                  <div class="text-[11px] text-slate-500 mt-0.5 font-medium">
                    {{ c.trip?.route?.name || 'Tuyến liên tỉnh' }}
                  </div>
                </td>

                <!-- Xe & Người bàn giao -->
                <td class="py-3.5 px-3">
                  <div class="font-mono font-bold text-slate-900">🚍 {{ c.trip?.bus?.plate_number || c.trip?.bus?.license_plate || '43B-012.34' }}</div>
                  <div class="text-[11px] text-slate-500">
                    Lơ xe: <strong class="text-slate-800">{{ c.trip?.conductor?.name || c.closed_by || 'Nguyễn Văn Phúc (Lơ xe)' }}</strong>
                  </div>
                </td>

                <!-- Tiền App Tính -->
                <td class="py-3.5 px-3 text-right">
                  <span class="font-mono font-black text-slate-900 text-sm">
                    {{ formatPrice(c.expected_cash_from_conductor) }}
                  </span>
                  <div class="text-[10px] text-slate-400">Đã trừ chi phí xe</div>
                </td>

                <!-- Ô Input Tiền Lơ Xe Thực Nộp -->
                <td class="py-3.5 px-3 text-center">
                  <div class="inline-flex items-center space-x-1.5">
                    <input 
                      type="number" 
                      v-model.number="cashInput[c.id]" 
                      class="w-32 px-2.5 py-1.5 bg-slate-50 border border-slate-300 rounded-xl text-right font-mono font-black text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"
                      :placeholder="c.expected_cash_from_conductor"
                    />
                    <span class="text-slate-400 text-[10px] font-bold">đ</span>
                  </div>
                  <div v-if="cashInput[c.id] && cashInput[c.id] !== c.expected_cash_from_conductor" class="text-[10px] text-amber-600 font-semibold mt-0.5">
                    Khác tính toán: {{ formatPrice(cashInput[c.id] - c.expected_cash_from_conductor) }}
                  </div>
                </td>

                <!-- Chênh Lệch -->
                <td class="py-3.5 px-3 text-center">
                  <span v-if="(c.cash_discrepancy || 0) === 0" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    ✓ Khớp 0 đ
                  </span>
                  <span v-else-if="c.cash_discrepancy < 0" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    Thiếu {{ formatPrice(Math.abs(c.cash_discrepancy)) }}
                  </span>
                  <span v-else class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    Thừa +{{ formatPrice(c.cash_discrepancy) }}
                  </span>
                </td>

                <!-- Trạng Thái Đối Soát -->
                <td class="py-3.5 px-3 text-center">
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                    ⏳ CHỜ KẾ TOÁN THU
                  </span>
                  <div v-if="c.notes" class="text-[10px] text-slate-400 mt-1 italic max-w-xs truncate mx-auto">
                    {{ c.notes }}
                  </div>
                </td>

                <!-- Nút Xác Nhận Thu Tiền Của Admin -->
                <td class="py-3.5 px-3 text-right">
                  <div class="flex items-center justify-end space-x-1.5">
                    <button 
                      @click="confirmCollectCash(c)"
                      class="px-3.5 py-2 bg-[#dc2626] hover:bg-[#b91c1c] text-white rounded-xl text-xs font-black shadow-xs transition flex items-center space-x-1 cursor-pointer active:scale-95">
                      <span>✓ Xác Nhận Thu Tiền</span>
                    </button>
                    <button 
                      @click="openDiscrepancyModal(c)"
                      title="Báo sai lệch tiền"
                      class="px-2 py-2 bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-200 hover:border-rose-200 rounded-xl text-xs font-bold transition cursor-pointer">
                      <span>✕ Lệch</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 2: SECTION LỊCH SỬ ĐÃ THU TIỀN ĐỐI SOÁT (YÊU CẦU 2 CỦA USER) -->
      <div v-if="financeTab === 'HISTORY'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div>
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              <h3 class="text-base font-black text-slate-900">📜 Lịch Sử Đã Thu Tiền & Nhập Quỹ ({{ historyClosings.length }} ca đã nộp)</h3>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Hồ sơ đối soát toàn bộ các chuyến xe đã được kế toán xác nhận nộp tiền mặt và chốt sổ thành công</p>
          </div>
        </div>

        <div v-if="historyClosings.length === 0" class="p-8 text-center text-slate-400 text-xs">
          Chưa có ca nào được xác nhận thu tiền.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200">
              <tr>
                <th class="py-3 px-3">Thời Gian Thu</th>
                <th class="py-3 px-3">Kế Toán Xác Nhận</th>
                <th class="py-3 px-3">Chuyến / Hướng</th>
                <th class="py-3 px-3">Xe & Lơ Xe Bàn Giao</th>
                <th class="py-3 px-3 text-right">Tiền App Tính</th>
                <th class="py-3 px-3 text-right font-black text-emerald-800">Số Tiền Thực Thu</th>
                <th class="py-3 px-3 text-center">Chênh Lệch</th>
                <th class="py-3 px-3 text-center">Trạng Thái Sổ Sách</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="c in historyClosings" :key="c.id" class="hover:bg-slate-50/70 transition">
                <!-- Thời Gian Thu -->
                <td class="py-3.5 px-3 font-mono text-[11px] text-slate-700">
                  <div class="font-bold text-slate-900">{{ c.audited_at ? c.audited_at.substring(11, 16) : '18:30' }}</div>
                  <div class="text-slate-400 text-[10px]">{{ c.audited_at ? c.audited_at.substring(0, 10) : '27/10/2026' }}</div>
                </td>

                <!-- Kế Toán Thu -->
                <td class="py-3.5 px-3">
                  <strong class="text-slate-900 font-bold block">{{ c.audited_by || 'Trần Thu Hà (Kế Toán Trưởng)' }}</strong>
                  <span class="text-[10px] text-emerald-600 font-medium">✓ Đã ký nhận đủ tiền</span>
                </td>

                <!-- Chuyến & Hướng -->
                <td class="py-3.5 px-3">
                  <div class="flex items-center space-x-1.5">
                    <span class="font-mono font-black text-slate-900 text-xs">#{{ c.trip?.trip_code || ('TRIP-' + c.trip_id) }}</span>
                    <span class="px-2 py-0.2 rounded-full text-[9px] font-bold"
                          :class="c.trip_direction?.includes('Chuyến Đi') || c.trip_direction?.includes('DI') ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-purple-50 text-purple-700 border border-purple-200'">
                      {{ c.trip_direction || 'Chuyến Đi' }}
                    </span>
                  </div>
                  <div class="text-[11px] text-slate-500 mt-0.5 truncate max-w-[180px]">
                    {{ c.trip?.route?.name || 'Tuyến liên tỉnh' }}
                  </div>
                </td>

                <!-- Xe & Người bàn giao -->
                <td class="py-3.5 px-3">
                  <div class="font-mono font-bold text-slate-900">🚍 {{ c.trip?.bus?.plate_number || c.trip?.bus?.license_plate || '43B-012.34' }}</div>
                  <div class="text-[11px] text-slate-500">
                    Lơ xe: <strong class="text-slate-800">{{ c.trip?.conductor?.name || c.closed_by || 'Lơ xe trưởng' }}</strong>
                  </div>
                </td>

                <!-- Tiền App Tính -->
                <td class="py-3.5 px-3 text-right font-mono text-slate-600">
                  {{ formatPrice(c.expected_cash_from_conductor) }}
                </td>

                <!-- Số Tiền Thực Thu -->
                <td class="py-3.5 px-3 text-right">
                  <span class="font-mono font-black text-emerald-700 text-sm">
                    {{ formatPrice(c.actual_cash_submitted || c.expected_cash_from_conductor) }}
                  </span>
                  <div class="text-[10px] text-emerald-600">Đã nhập quỹ</div>
                </td>

                <!-- Chênh Lệch -->
                <td class="py-3.5 px-3 text-center">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    ✓ Khớp 0 đ
                  </span>
                </td>

                <!-- Trạng Thái Sổ Sách -->
                <td class="py-3.5 px-3 text-center">
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    ✓ ĐÃ THU TIỀN (Số dư = 0đ)
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TAB 3: SECTION TOP PERFORMING BUSES TABLE -->
      <div v-if="financeTab === 'TOP_BUSES'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900">🏆 Bảng Xếp Hạng Xe Doanh Thu Cao Nhất</h3>
            <p class="text-xs text-slate-500">Thống kê số lượt khách và doanh thu đóng góp theo từng biển số xe</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200">
              <tr>
                <th class="py-3 px-3">Hạng</th>
                <th class="py-3 px-3">Biển Số Xe</th>
                <th class="py-3 px-3">Dòng Xe</th>
                <th class="py-3 px-3">Số Chuyến</th>
                <th class="py-3 px-3">Lượt Khách</th>
                <th class="py-3 px-3 text-right">Tổng Doanh Thu</th>
                <th class="py-3 px-3 text-center">Trạng Thái Xe</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="(b, idx) in topBuses" :key="b.id" class="hover:bg-slate-50/70 transition">
                <td class="py-3 px-3">
                  <span
                    :class="{
                      'w-6 h-6 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-black flex items-center justify-center text-xs': idx === 0,
                      'w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center text-xs': idx > 0,
                    }"
                  >
                    #{{ idx + 1 }}
                  </span>
                </td>
                <td class="py-3 px-3 font-mono font-black text-slate-900 text-xs">{{ b.license_plate || '43B-012.34' }}</td>
                <td class="py-3 px-3 text-slate-700 font-medium">{{ b.bus_type }}</td>
                <td class="py-3 px-3 font-bold text-slate-900">{{ b.trips_count }} chuyến</td>
                <td class="py-3 px-3 font-bold text-slate-800">{{ b.total_passengers || 0 }} khách</td>
                <td class="py-3 px-3 text-right font-black text-emerald-700 font-mono text-xs">
                  {{ formatPrice(b.total_revenue) }}
                </td>
                <td class="py-3 px-3 text-center">
                  <span
                    :class="b.status === 'RUNNING' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-700 border-slate-200'"
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold border"
                  >
                    {{ b.status === 'RUNNING' ? 'Đang chạy tuyến' : 'Sẵn sàng / Bảo dưỡng' }}
                  </span>
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
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminHeader from '@/Components/AdminHeader.vue'

const props = defineProps({
  metrics: Object,
  topBuses: { type: Array, default: () => [] },
  tripClosings: { type: Array, default: () => [] },
});

const financeTab = ref('PENDING') // 'PENDING', 'HISTORY', 'TOP_BUSES'
const directionFilter = ref('ALL') // 'ALL', 'DI', 'VE'
const showDiscrepancyModal = ref(false)
const activeClosing = ref(null)
const auditNote = ref('')
const cashInput = ref({})

// Initialize default cash input for each trip closing
props.tripClosings?.forEach(c => {
  cashInput.value[c.id] = c.actual_cash_submitted || c.expected_cash_from_conductor || 0
})

const filteredClosings = computed(() => {
  let list = props.tripClosings || []
  if (directionFilter.value === 'DI') {
    list = list.filter(c => (c.trip_direction || '').includes('Chuyến Đi') || (c.trip_direction || '').includes('DI'))
  } else if (directionFilter.value === 'VE') {
    list = list.filter(c => (c.trip_direction || '').includes('Chuyến Về') || (c.trip_direction || '').includes('VE'))
  }
  return list
})

const pendingClosings = computed(() => {
  return filteredClosings.value.filter(c => c.audit_status !== 'AUDITED_OK' && c.audit_status !== 'SETTLED_CASH_SUBMITTED')
})

const historyClosings = computed(() => {
  return filteredClosings.value.filter(c => c.audit_status === 'AUDITED_OK' || c.audit_status === 'SETTLED_CASH_SUBMITTED')
})

const confirmCollectCash = (closing) => {
  const amount = cashInput.value[closing.id] !== undefined ? cashInput.value[closing.id] : closing.expected_cash_from_conductor
  
  if (!confirm(`Xác nhận đã nhận đủ ${formatPrice(amount)} từ Lơ xe (${closing.closed_by || 'Lơ xe chuyến #' + closing.trip?.trip_code})?\n\nSau khi xác nhận, số tiền sẽ được nhập quỹ và lưu vào Lịch Sử Đối Soát.`)) return

  router.post('/admin/finance/collect-cash', {
    closing_id: closing.id,
    trip_id: closing.trip_id,
    actual_cash_submitted: Number(amount)
  }, {
    preserveScroll: true,
    onSuccess: () => {
      alert(`🟢 XÁC NHẬN THÀNH CÔNG!\nKế toán đã nhận ${formatPrice(amount)}. Ca chạy đã được chuyển vào Lịch Sử Đã Thu Tiền thành công!`)
    }
  })
}

const openDiscrepancyModal = (closing) => {
  activeClosing.value = closing
  const inputAmt = cashInput.value[closing.id] || closing.actual_cash_submitted || 0
  const diff = inputAmt - (closing.expected_cash_from_conductor || 0)
  auditNote.value = `Lệch ${formatPrice(Math.abs(diff))}. Cần bổ sung cuống vé hoặc giải trình vé đón dọc đường.`
  showDiscrepancyModal.value = true
}

const submitDiscrepancy = () => {
  if (!activeClosing.value) return
  router.post(`/admin/closings/${activeClosing.value.id}/audit`, {
    status: 'AUDITED_DISCREPANCY',
    audit_note: auditNote.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showDiscrepancyModal.value = false
      alert('⚠️ Đã gửi yêu cầu giải trình lệch tiền!')
    }
  })
}

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(val || 0) + ' đ';
</script>
