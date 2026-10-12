<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <AdminHeader title="Quản Lý Đội Xe & Lượt Chạy Trong Ngày" subtitle="Theo dõi chi tiết số lượt chạy, lượng hành khách thực tế và tổng tiền thu theo từng phương tiện" />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">
      <!-- Header & Thanh Thao Tác Thống Nhất (Tích hợp Tìm kiếm, Lọc ngày, Chọn xe, Nút hành động - Không bị khoảng trống) -->
      <div class="bg-white p-4.5 rounded-3xl border border-slate-200 shadow-xs space-y-3.5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
          <!-- Cột trái: Tiêu đề & Search Bar -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-1">
            <div class="shrink-0">
              <h1 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span class="text-red-600">●</span>
                <span>Quản Lý Đội Xe</span>
              </h1>
              <p class="text-[11px] text-slate-500">
                {{ rawBuses.length }} phương tiện trong hệ thống
              </p>
            </div>

            <!-- Thanh Tìm Kiếm Đội Xe (Biển số, loại xe, tài xế, tuyến đường) -->
            <div class="relative flex-1 max-w-md">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs pointer-events-none">🔍</span>
              <input
                v-model="busSearchQuery"
                type="text"
                placeholder="Tìm biển số (VD: 43B-...), loại xe, tài xế, tuyến đường..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-7 py-2 text-xs font-bold text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:bg-white focus:outline-red-500 transition shadow-2xs"
              />
              <button
                v-if="busSearchQuery"
                @click="busSearchQuery = ''"
                type="button"
                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600 font-bold text-xs cursor-pointer"
              >✕</button>
            </div>
          </div>

          <!-- Cột phải: Bộ lọc Ngày, Dropdown xe & Các Nút Hành Động -->
          <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <!-- Chọn ngày -->
            <div class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1.5 rounded-xl border border-slate-200">
              <span class="text-xs">📅</span>
              <input
                type="date"
                v-model="currentDate"
                @change="applyFilters"
                class="text-xs font-bold text-slate-900 bg-transparent border-none focus:outline-none cursor-pointer"
              />
            </div>

            <!-- Dropdown Chọn xe nhanh -->
            <div class="flex items-center gap-1.5 bg-slate-50 px-2.5 py-1.5 rounded-xl border border-slate-200">
              <span class="text-xs">🚍</span>
              <select
                v-model="currentBusId"
                @change="handleBusSelect"
                class="text-xs font-bold text-slate-900 bg-transparent border-none focus:outline-none cursor-pointer max-w-[170px] truncate"
              >
                <option value="ALL">Tất cả ({{ rawBuses.length }} xe)</option>
                <option v-for="b in rawBuses" :key="b.id" :value="String(b.id)">
                  {{ b.license_plate }}
                </option>
              </select>
            </div>

            <!-- Nút Vật Tư & Chi Phí -->
            <Link
              href="/admin/fleet-inventory"
              class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-900 font-bold rounded-xl text-xs border border-amber-300 transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap shadow-2xs"
            >
              <span>🔧</span>
              <span>Vật Tư & Chi Phí</span>
            </Link>

            <!-- Nút Thêm xe mới -->
            <button
              @click="showCreateBusModal = true"
              class="px-4 py-2 bg-[#dc2626] hover:bg-[#b91c1c] text-white font-black rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap shadow-sm active:scale-[0.98]"
            >
              <span>➕</span>
              <span>THÊM XE MỚI</span>
            </button>
          </div>
        </div>
      </div>

      <!-- 5 THẺ THỐNG KÊ CHI TIẾT ĐỘI XE (PASTEL FLAT SAAS DESIGN) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Tổng Lượt Chạy -->
        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tổng Lượt Chạy</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ totalTripsCount }} <span class="text-xs text-slate-400 font-normal">lượt</span></p>
            <p class="text-[11px] text-slate-500 mt-0.5">Chạy 2 chiều khứ hồi</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg font-black">
            🚍
          </div>
        </div>

        <!-- Thẻ 2: Xe Hoạt Động (CLICKABLE: Lọc xe đang hoạt động - Pastel Theme) -->
        <div 
          @click="statusFilter = statusFilter === 'ACTIVE' ? 'ALL' : 'ACTIVE'"
          class="p-4.5 rounded-2xl border flex items-center justify-between cursor-pointer transition-all duration-200 select-none"
          :class="statusFilter === 'ACTIVE' ? 'bg-emerald-50/70 border-emerald-300 ring-1 ring-emerald-400/30' : 'bg-white border-slate-200 hover:border-emerald-300'"
        >
          <div>
            <div class="flex items-center gap-1.5">
              <p class="text-[11px] font-bold uppercase tracking-wider" :class="statusFilter === 'ACTIVE' ? 'text-emerald-800' : 'text-slate-500'">
                Xe Hoạt Động
              </p>
              <span v-if="statusFilter === 'ACTIVE'" class="text-[10px] bg-emerald-600 text-white font-bold px-1.5 py-0.2 rounded-md">Đang lọc</span>
            </div>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ activeBusesCount }} / {{ rawBuses.length }} <span class="text-xs text-slate-400 font-normal">xe</span></p>
            <p class="text-[11px] text-emerald-700 font-medium mt-0.5 flex items-center gap-1">
              <span>✓ Bấm để lọc xe hoạt động</span>
            </p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/60 flex items-center justify-center text-base font-black">
            ✓
          </div>
        </div>

        <!-- Thẻ 3: Khách Đã Đón -->
        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Khách Đã Đón</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ totalPassengersCount }} <span class="text-xs text-slate-400 font-normal">khách</span></p>
            <p class="text-[11px] text-slate-500 mt-0.5">Lưu trữ từ DB đặt vé</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 border border-blue-200/60 flex items-center justify-center text-base font-black">
            👥
          </div>
        </div>

        <!-- Thẻ 4: Xe Bảo Dưỡng (CLICKABLE: Lọc xe đang bảo dưỡng - Pastel Theme) -->
        <div 
          @click="statusFilter = statusFilter === 'MAINTENANCE' ? 'ALL' : 'MAINTENANCE'"
          class="p-4.5 rounded-2xl border flex items-center justify-between cursor-pointer transition-all duration-200 select-none"
          :class="statusFilter === 'MAINTENANCE' ? 'bg-amber-50/70 border-amber-300 ring-1 ring-amber-400/30' : 'bg-white border-slate-200 hover:border-amber-300'"
        >
          <div>
            <div class="flex items-center gap-1.5">
              <p class="text-[11px] font-bold uppercase tracking-wider" :class="statusFilter === 'MAINTENANCE' ? 'text-amber-800' : 'text-slate-500'">
                Bảo Dưỡng Kỹ Thuật
              </p>
              <span v-if="statusFilter === 'MAINTENANCE'" class="text-[10px] bg-amber-600 text-white font-bold px-1.5 py-0.2 rounded-md">Đang lọc</span>
            </div>
            <p class="text-2xl font-black text-amber-800 mt-1">{{ maintenanceCount }} <span class="text-xs text-amber-700/60 font-normal">xe</span></p>
            <p class="text-[11px] text-amber-800 font-medium mt-0.5 flex items-center gap-1">
              <span>🔧 Bấm để lọc xe bảo dưỡng</span>
            </p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 border border-amber-200/60 flex items-center justify-center text-base font-black">
            🔧
          </div>
        </div>

        <!-- Thẻ 5: Doanh Thu Ngày -->
        <div class="bg-white p-4.5 rounded-2xl border border-slate-200 flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Doanh Thu Ngày</p>
            <p class="text-2xl font-black text-emerald-700 font-mono mt-1">{{ formatCurrency(totalRevenueSum) }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5">Bao gồm cọc 30% & vé quầy</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/60 flex items-center justify-center text-base font-black">
            💰
          </div>
        </div>
      </div>

      <!-- THANH ĐIỀU KHIỂN & BỘ LỌC TRẠNG THÁI (TẤT CẢ / ĐANG HOẠT ĐỘNG / BẢO DƯỠNG) -->
      <div class="bg-white p-4.5 rounded-3xl border border-slate-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Chế độ Switcher: Tất cả / Lọc Trạng Thái -->
        <div class="flex flex-wrap items-center gap-2">
          <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl text-xs font-bold text-slate-600">
            <!-- Nút Tất cả -->
            <button
              @click="statusFilter = 'ALL'; if (currentBusId !== 'ALL') resetToAll();"
              class="px-3.5 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5"
              :class="statusFilter === 'ALL' && currentBusId === 'ALL' ? 'bg-[#dc2626] text-white shadow-xs' : 'hover:bg-white text-slate-700'"
            >
              <span>📊</span>
              <span>Tất cả ({{ rawBuses.length }} xe)</span>
            </button>

            <!-- Nút Đang hoạt động -->
            <button
              @click="statusFilter = 'ACTIVE'; if (currentBusId !== 'ALL') currentBusId = 'ALL';"
              class="px-3.5 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5"
              :class="statusFilter === 'ACTIVE' ? 'bg-[#065f46] text-white shadow-xs font-black' : 'hover:bg-white text-emerald-800'"
            >
              <span>✓</span>
              <span>Đang hoạt động ({{ activeBusesCount }})</span>
            </button>

            <!-- Nút Bảo dưỡng kỹ thuật -->
            <button
              @click="statusFilter = 'MAINTENANCE'; if (currentBusId !== 'ALL') currentBusId = 'ALL';"
              class="px-3.5 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5"
              :class="statusFilter === 'MAINTENANCE' ? 'bg-[#92400e] text-white shadow-xs font-black' : 'hover:bg-white text-[#92400e]'"
            >
              <span>🔧</span>
              <span>Bảo dưỡng kỹ thuật ({{ maintenanceCount }})</span>
            </button>

            <!-- Nút Đã Thanh Lý -->
            <button
              @click="statusFilter = 'LIQUIDATED'; if (currentBusId !== 'ALL') currentBusId = 'ALL';"
              class="px-3.5 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1.5"
              :class="statusFilter === 'LIQUIDATED' ? 'bg-purple-900 text-white shadow-xs font-black' : 'hover:bg-white text-purple-900 font-bold'"
            >
              <span>🏷️</span>
              <span>Đã thanh lý ({{ liquidatedCount }})</span>
            </button>
          </div>

          <button
            v-if="currentBusId !== 'ALL'"
            @click="resetToAll"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-rose-50 text-red-600 border border-rose-200 hover:bg-rose-100 transition flex items-center gap-1 cursor-pointer"
          >
            <span>✕ Đang xem riêng xe: {{ selectedBusData?.license_plate }} (Bấm để xem tất cả)</span>
          </button>

          <button
            v-if="busSearchQuery"
            @click="busSearchQuery = ''"
            class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-900 border border-amber-300 hover:bg-amber-100 transition flex items-center gap-1 cursor-pointer"
          >
            <span>✕ Xóa tìm kiếm: "{{ busSearchQuery }}" ({{ filteredBuses.length }} xe)</span>
          </button>
        </div>

        <!-- Chế độ Hiển Thị: Dạng Thẻ (Cards) vs Dạng Bảng (Table) -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl text-xs font-bold text-slate-600">
            <button
              @click="displayView = 'cards'"
              class="px-3 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1"
              :class="displayView === 'cards' ? 'bg-[#dc2626] text-white shadow-xs' : 'hover:bg-white text-slate-700'"
            >
              <span>🗂️</span>
              <span>Dạng Thẻ</span>
            </button>
            <button
              @click="displayView = 'table'"
              class="px-3 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1"
              :class="displayView === 'table' ? 'bg-[#dc2626] text-white shadow-xs' : 'hover:bg-white text-slate-700'"
            >
              <span>📋</span>
              <span>Dạng Bảng</span>
            </button>
          </div>

          <button
            @click="showCreateBusModal = true"
            class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs transition flex items-center gap-1 cursor-pointer shadow-2xs"
          >
            <span>➕</span>
            <span>Thêm Xe</span>
          </button>
        </div>
      </div>

      <!-- ==================== 1. CHẾ ĐỘ: DANH SÁCH TẤT CẢ XE (CÓ ÁP DỤNG BỘ LỌC TRẠNG THÁI) ==================== -->
      <div v-if="currentBusId === 'ALL'" class="space-y-6">

        <!-- A. HIỂN THỊ DẠNG THẺ (CARDS GIỐNG HÌNH MẪU CỦA BẠN 100%) -->
        <div v-if="displayView === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="bus in filteredBuses"
            :key="bus.id"
            class="bg-white rounded-2xl border border-slate-200 p-5 hover:border-slate-300 transition-all flex flex-col justify-between space-y-4"
          >
            <!-- Card Header: Icon Tròn + Biển Số & Trạng Thái + Badge Số Chỗ -->
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <!-- Icon Tròn xe / cờ lê bảo dưỡng -->
                <div 
                  class="w-11 h-11 rounded-xl flex items-center justify-center text-lg font-bold shrink-0"
                  :class="bus.status === 'ACTIVE' ? 'bg-slate-100 text-slate-800' : 'bg-amber-50 text-amber-800 border border-amber-200/60'"
                >
                  <span v-if="bus.status === 'ACTIVE'">🚌</span>
                  <span v-else>🔧</span>
                </div>

                <div>
                  <div class="flex items-center gap-2">
                    <span class="font-mono font-black text-slate-900 text-base leading-tight">{{ bus.license_plate }}</span>
                  </div>

                  <!-- Thẻ trạng thái Pastel nhẹ nhàng, chữ đậm thanh lịch -->
                  <div class="mt-1 flex items-center gap-1.5">
                    <button
                      @click="toggleBusStatus(bus)"
                      class="transition-transform active:scale-95 cursor-pointer inline-block"
                      title="Bấm để chuyển đổi trạng thái xe"
                    >
                      <span
                        v-if="bus.status === 'ACTIVE'"
                        class="bg-emerald-50 text-emerald-700 border border-emerald-200/70 rounded-full px-2.5 py-0.5 text-[11px] font-bold inline-flex items-center gap-1.5"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Sẵn sàng hoạt động</span>
                      </span>

                      <span
                        v-else-if="bus.status === 'LIQUIDATED'"
                        class="bg-purple-50 text-purple-700 border border-purple-200/70 rounded-full px-2.5 py-0.5 text-[11px] font-bold inline-flex items-center gap-1.5"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span>
                        <span>Đã thanh lý (Chỗ để 0đ)</span>
                      </span>

                      <span
                        v-else
                        class="bg-amber-50 text-amber-800 border border-amber-200/70 rounded-full px-2.5 py-0.5 text-[11px] font-bold inline-flex items-center gap-1.5"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>Bảo dưỡng kỹ thuật</span>
                      </span>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Badge Loại Xe / Số chỗ bên phải -->
              <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 shrink-0 border border-slate-200">
                {{ bus.total_seats }} Chỗ
              </span>
            </div>

            <!-- Card Body: Thông tin dòng xe, tài xế, lượt chạy hôm nay, khách đã đón -->
            <div class="space-y-2 text-xs pt-2 border-t border-slate-100">
              <div class="flex items-center justify-between py-0.5">
                <span class="text-slate-400 font-medium">Dòng xe:</span>
                <span class="font-bold text-slate-800 text-right max-w-[200px] truncate">{{ bus.bus_type }}</span>
              </div>
              <div class="flex items-center justify-between py-0.5">
                <span class="text-slate-400 font-medium">Tài xế phụ trách:</span>
                <span class="font-bold text-slate-900">{{ bus.driver_name || 'Bác tài nhaxekientruc' }}</span>
              </div>
              <div class="flex items-center justify-between py-0.5">
                <span class="text-slate-400 font-medium">Lượt chạy hôm nay:</span>
                <span class="font-bold text-red-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-100">
                  {{ bus.trips_count || 0 }} lượt chạy
                </span>
              </div>
              <div class="flex items-center justify-between py-0.5">
                <span class="text-slate-400 font-medium">Khách đã đón:</span>
                <span class="font-bold text-slate-800">{{ bus.total_passengers || 0 }} khách</span>
              </div>
            </div>

            <!-- Card Footer: Doanh thu xe & Nút "Xem Khách & Sơ Đồ Ghế" -->
            <div class="pt-2 border-t border-slate-100 space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs uppercase font-bold text-slate-400 tracking-wider">DOANH THU XE</span>
                <span class="text-emerald-600 font-mono font-black text-base">{{ formatCurrency(bus.total_revenue) }}</span>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <button
                  @click="viewBusDetail(bus.id)"
                  class="py-2 bg-rose-50 hover:bg-[#dc2626] hover:text-white text-red-600 font-bold text-xs rounded-xl border border-rose-200 transition flex items-center justify-center gap-1 cursor-pointer"
                >
                  <span>🎯 Xem Khách</span>
                </button>

                <button
                  v-if="bus.status !== 'LIQUIDATED'"
                  @click="handleLiquidateBus(bus)"
                  class="py-2 bg-purple-50 hover:bg-purple-800 hover:text-white text-purple-700 font-bold text-xs rounded-xl border border-purple-200 transition flex items-center justify-center gap-1 cursor-pointer"
                  title="Thanh lý xe (chỗ để bãi xe là miễn phí 0đ)"
                >
                  <span>🏷️ Thanh Lý</span>
                </button>
                <button
                  v-else
                  @click="handleRestoreBus(bus)"
                  class="py-2 bg-emerald-50 hover:bg-emerald-700 hover:text-white text-emerald-700 font-bold text-xs rounded-xl border border-emerald-200 transition flex items-center justify-center gap-1 cursor-pointer"
                  title="Khôi phục xe về hoạt động"
                >
                  <span>↩ Phục Hồi</span>
                </button>
              </div>

              <!-- Ghi chú Chỗ để xe & Nút Xóa vĩnh viễn -->
              <div class="flex items-center justify-between text-[11px] pt-1">
                <span class="text-slate-400">Chỗ để bãi xe: <b class="text-emerald-600 font-bold">FREE 0đ</b></span>
                <button
                  @click="handleDeleteBus(bus)"
                  class="text-slate-400 hover:text-rose-600 font-bold transition cursor-pointer"
                  title="Xóa phương tiện khỏi đội xe"
                >
                  🗑️ Xóa xe
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- B. HIỂN THỊ DẠNG BẢNG (TABLE) -->
        <div v-else class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
              <thead class="bg-[#0f172a] text-white uppercase text-[11px] font-black tracking-wider">
                <tr>
                  <th class="px-5 py-3.5">Biển Số Xe</th>
                  <th class="px-5 py-3.5">Loại Xe / Sức Chứa</th>
                  <th class="px-5 py-3.5">Tài Xế Phụ Trách</th>
                  <th class="px-5 py-3.5 text-center">Lượt Chạy / Ngày</th>
                  <th class="px-5 py-3.5 text-center">Khách Đã Đón</th>
                  <th class="px-5 py-3.5 text-right">Doanh Thu Từng Xe</th>
                  <th class="px-5 py-3.5 text-center">Trạng Thái</th>
                  <th class="px-5 py-3.5 text-center">Hành Động</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="bus in filteredBuses"
                  :key="bus.id"
                  class="hover:bg-red-50/20 transition-all text-xs"
                  :class="bus.status === 'MAINTENANCE' ? 'bg-amber-50/30' : ''"
                >
                  <td class="px-5 py-3.5 font-mono font-bold text-slate-900">
                    <div class="flex items-center gap-2">
                      <span class="w-2.5 h-2.5 rounded-full" :class="bus.status === 'ACTIVE' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                      <span>{{ bus.license_plate }}</span>
                    </div>
                  </td>
                  <td class="px-5 py-3.5">
                    <span class="font-bold text-slate-800">{{ bus.bus_type }}</span>
                    <span class="text-xs text-slate-400 block">{{ bus.total_seats }} chỗ</span>
                  </td>
                  <td class="px-5 py-3.5 font-medium text-slate-800">
                    {{ bus.driver_name }}
                  </td>
                  <td class="px-5 py-3.5 text-center">
                    <span class="font-bold text-red-700 bg-red-50 px-2.5 py-0.5 rounded-full text-xs border border-red-200">
                      {{ bus.trips_count || 0 }} lượt
                    </span>
                  </td>
                  <td class="px-5 py-3.5 text-center font-bold text-slate-700">
                    {{ bus.total_passengers || 0 }} khách
                  </td>
                  <td class="px-5 py-3.5 text-right font-bold text-emerald-700 font-mono">
                    {{ formatCurrency(bus.total_revenue) }}
                  </td>
                  <td class="px-5 py-3.5 text-center">
                    <button
                      @click="toggleBusStatus(bus)"
                      class="transition-transform active:scale-95 cursor-pointer inline-block"
                      title="Bấm để chuyển đổi trạng thái"
                    >
                      <span
                        v-if="bus.status === 'ACTIVE'"
                        class="bg-emerald-50 text-emerald-700 border border-emerald-200/70 rounded-full px-3 py-0.5 text-xs font-bold inline-flex items-center gap-1.5"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Đang hoạt động</span>
                      </span>

                      <span
                        v-else-if="bus.status === 'LIQUIDATED'"
                        class="bg-purple-50 text-purple-700 border border-purple-200/70 rounded-full px-3 py-0.5 text-xs font-bold inline-flex items-center gap-1.5"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span>
                        <span>Đã thanh lý</span>
                      </span>

                      <span
                        v-else
                        class="bg-amber-50 text-amber-800 border border-amber-200/70 rounded-full px-3 py-0.5 text-xs font-bold inline-flex items-center gap-1.5"
                      >
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span>Bảo dưỡng kỹ thuật</span>
                      </span>
                    </button>
                  </td>
                  <td class="px-5 py-3.5 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button
                        @click="viewBusDetail(bus.id)"
                        class="px-2.5 py-1 bg-rose-50 hover:bg-[#dc2626] hover:text-white text-red-700 font-bold text-xs rounded-lg border border-rose-200 transition cursor-pointer"
                        title="Xem khách & ghế"
                      >
                        🔍 Xem
                      </button>
                      <button
                        v-if="bus.status !== 'LIQUIDATED'"
                        @click="handleLiquidateBus(bus)"
                        class="px-2.5 py-1 bg-purple-50 hover:bg-purple-800 hover:text-white text-purple-700 font-bold text-xs rounded-lg border border-purple-200 transition cursor-pointer"
                        title="Thanh lý xe (chỗ để bãi xe là miễn phí 0đ)"
                      >
                        🏷️ Thanh lý
                      </button>
                      <button
                        v-else
                        @click="handleRestoreBus(bus)"
                        class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-700 hover:text-white text-emerald-700 font-bold text-xs rounded-lg border border-emerald-200 transition cursor-pointer"
                        title="Khôi phục hoạt động"
                      >
                        ↩ Phục hồi
                      </button>
                      <button
                        @click="handleDeleteBus(bus)"
                        class="px-2 py-1 bg-slate-100 hover:bg-rose-600 hover:text-white text-slate-500 font-bold text-xs rounded-lg transition cursor-pointer"
                        title="Xóa phương tiện"
                      >
                        🗑️
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Trạng thái trống khi lọc không có kết quả -->
        <div v-if="filteredBuses.length === 0" class="text-center py-12 bg-white rounded-3xl border border-slate-200 text-slate-400">
          <p class="text-sm font-bold text-slate-600">Không tìm thấy phương tiện nào thỏa mãn điều kiện lọc.</p>
          <button 
            @click="statusFilter = 'ALL'"
            class="mt-3 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition cursor-pointer"
          >
            Quay lại xem tất cả xe
          </button>
        </div>
      </div>

      <!-- ==================== 2. CHẾ ĐỘ: XEM RIÊNG TỪNG XE (SƠ ĐỒ GHẾ & HÀNH KHÁCH) ==================== -->
      <div v-else class="space-y-6">
        <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-rose-50 text-red-600 flex items-center justify-center text-2xl font-black shadow-2xs">
                🚌
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h2 class="text-xl font-black text-slate-900 font-mono">{{ selectedBusData?.license_plate }}</h2>
                  <button
                    @click="toggleBusStatus(selectedBusData)"
                    class="cursor-pointer transition-transform active:scale-95 inline-block"
                    title="Bấm để chuyển đổi trạng thái"
                  >
                    <span
                      v-if="selectedBusData?.status === 'ACTIVE'"
                      class="bg-[#dcfce7] text-[#065f46] hover:bg-[#bbf7d0] transition-colors rounded-full px-3.5 py-1 text-xs font-bold inline-flex items-center gap-1.5 shadow-2xs"
                    >
                      <span class="font-black">✓</span>
                      <span>Đang hoạt động</span>
                    </span>
                    <span
                      v-else
                      class="bg-[#fef3c7] text-[#92400e] hover:bg-[#fde68a] transition-colors rounded-full px-3.5 py-1 text-xs font-bold inline-flex items-center gap-1.5 shadow-2xs"
                    >
                      <span>🔧</span>
                      <span>Bảo dưỡng kỹ thuật</span>
                    </span>
                  </button>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                  {{ selectedBusData?.bus_type }} • Sức chứa: <b>{{ selectedBusData?.total_seats }} chỗ</b> • Bác tài: <b>{{ selectedBusData?.driver_name }}</b>
                </p>
              </div>
            </div>

            <!-- Thống kê của xe trong ngày -->
            <div class="flex items-center gap-3 text-xs">
              <div class="text-center px-3.5 py-2 bg-slate-50 rounded-2xl border border-slate-200">
                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Lượt chạy</span>
                <span class="text-base font-black text-red-600">{{ selectedBusData?.trips_count || 0 }} lượt</span>
              </div>
              <div class="text-center px-3.5 py-2 bg-slate-50 rounded-2xl border border-slate-200">
                <span class="text-slate-400 font-semibold block text-[10px] uppercase">Tổng khách</span>
                <span class="text-base font-black text-purple-600">{{ selectedBusData?.total_passengers || 0 }} khách</span>
              </div>
              <div class="text-center px-3.5 py-2 bg-emerald-50 rounded-2xl border border-emerald-200">
                <span class="text-emerald-700 font-semibold block text-[10px] uppercase">Doanh thu xe</span>
                <span class="text-base font-black text-emerald-700 font-mono">{{ formatCurrency(selectedBusData?.total_revenue) }}</span>
              </div>
            </div>
          </div>

          <!-- Danh sách từng lượt chạy của xe đó -->
          <div class="mt-6 space-y-6">
            <div class="flex items-center justify-between">
              <h3 class="font-black text-slate-900 text-base flex items-center gap-2">
                <span>📋</span>
                <span>Các Chuyến Chạy Trong Ngày ({{ currentDate }}) & Danh Sách Ghế Ngồi Hành Khách</span>
              </h3>

              <button
                @click="resetToAll"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-2xl transition cursor-pointer"
              >
                ← Quay lại danh sách xe
              </button>
            </div>

            <div v-if="!selectedBusData?.trips || selectedBusData?.trips.length === 0" class="text-center py-10 bg-slate-50 rounded-2xl border border-slate-200">
              <p class="text-sm font-bold text-slate-600">Xe {{ selectedBusData?.license_plate }} không có lịch chạy vào ngày {{ currentDate }}.</p>
              <p class="text-xs text-slate-400 mt-1">Vui lòng chọn ngày khác trên bộ lọc để xem danh sách khách và ghế.</p>
            </div>

            <!-- Từng Chuyến Của Xe -->
            <div
              v-for="trip in selectedBusData?.trips"
              :key="trip.id"
              class="border border-slate-200 rounded-3xl p-5 bg-slate-50/50 space-y-4"
            >
              <!-- Chuyến Info Header -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="font-mono font-bold bg-[#dc2626] text-white px-2.5 py-0.5 rounded-lg text-xs">{{ trip.trip_code }}</span>
                    <span class="font-black text-slate-900 text-sm">{{ trip.route_name }}</span>
                  </div>
                  <p class="text-xs text-slate-500 mt-1">
                    Xuất bến: <b class="text-slate-800 font-mono">{{ trip.departure_time }}</b> → Đến nơi: <b class="text-slate-800 font-mono">{{ trip.arrival_time }}</b> • Tài xế: <b>{{ trip.driver_name }}</b> ({{ trip.driver_phone }})
                  </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap text-xs">
                  <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 font-bold">
                    Đã đặt: {{ trip.booked_seats }} / {{ trip.total_seats }} ghế
                  </span>
                  <span class="px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-mono font-bold">
                    Thu: {{ formatCurrency(trip.revenue) }}
                  </span>
                </div>
              </div>

              <!-- CẢNH BÁO: HÀNH KHÁCH CHƯA LÊN XE KHI GẦN ĐẾN GIỜ (<= 10 PHÚT) HOẶC XE ĐANG CHẠY -->
              <div 
                v-if="isUrgentDepartureWarning(trip)"
                class="bg-amber-500/10 border-2 border-amber-500/40 rounded-2xl p-3.5 flex flex-wrap items-center justify-between gap-3 text-amber-950 shadow-xs"
              >
                <div class="flex items-center gap-2.5">
                  <span class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm font-bold shadow-xs animate-bounce">
                    ⚠️
                  </span>
                  <div>
                    <h5 class="font-black text-xs text-amber-950 flex items-center gap-1.5">
                      <span>CẢNH BÁO: Còn {{ trip.bookings.filter(b => !b.is_boarded).length }} hành khách chưa lên xe!</span>
                      <span class="text-[10px] bg-amber-200 text-amber-900 px-2 py-0.5 rounded-full font-bold">Khẩn cấp</span>
                    </h5>
                    <p class="text-[11px] text-amber-800">
                      Chuyến xe sắp xuất bến trong 10 phút hoặc đang chạy trên đường. Lơ xe/Điều phối vui lòng gọi điện nhắc khách khẩn cấp!
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap shrink-0">
                  <a 
                    v-for="unboarded in trip.bookings.filter(b => !b.is_boarded).slice(0, 3)" 
                    :key="unboarded.booking_code"
                    :href="'tel:' + unboarded.customer_phone"
                    class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-[11px] font-bold flex items-center gap-1 transition shadow-xs cursor-pointer"
                  >
                    <span>📞 Gọi</span>
                    <span>{{ unboarded.customer_name?.split(' ').pop() }}: {{ unboarded.customer_phone }}</span>
                  </a>
                </div>
              </div>

              <!-- Sơ đồ ghế và Danh sách hành khách -->
              <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Sơ Đồ Ghế Ngồi Tầng 1 & Tầng 2 -->
                <div class="lg:col-span-6 bg-white p-4.5 rounded-2xl border border-slate-200 flex flex-col justify-start">
                  <h4 class="font-bold text-slate-800 text-xs uppercase mb-3 flex items-center justify-between flex-wrap gap-2">
                    <span>💺 Trực Quan Vị Trí Ghế (Tầng 1 & Tầng 2)</span>
                    <div class="flex items-center gap-2 text-[10px] normal-case flex-wrap">
                      <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-emerald-600"></span> ✓ Đã Lên Xe</span>
                      <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-amber-500"></span> ⏳ Chờ Lên</span>
                      <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-white border border-slate-300"></span> Trống</span>
                    </div>
                  </h4>

                  <div class="grid grid-cols-2 gap-4">
                    <!-- Tầng 1 -->
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200 text-center flex flex-col justify-start">
                      <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-slate-200 h-8">
                        <span class="text-[11px] font-black text-slate-700 whitespace-nowrap">TẦNG 1 (DÃY A)</span>
                        <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-mono font-black text-[10px]">
                          {{ formatCurrency(trip.price_floor_1 || trip.fare || 140000) }}
                        </span>
                      </div>
                      <div class="grid grid-cols-3 gap-1.5 text-[9px] font-bold text-slate-500 pb-1.5 mb-2 border-b border-slate-100 select-none h-6 items-center">
                        <span>🪟 Cửa sổ</span>
                        <span>🚶 Dãy giữa</span>
                        <span>🪟 Cửa sổ</span>
                      </div>
                      <div class="grid grid-cols-3 gap-1.5">
                        <div
                          v-for="i in 18"
                          :key="'A' + i"
                          class="p-1.5 sm:p-2 rounded-xl text-center font-mono font-bold text-xs transition relative group cursor-pointer border min-h-[54px] flex flex-col justify-center items-center"
                          :class="[
                            trip.booked_seats_details && trip.booked_seats_details['A' + (i < 10 ? '0' + i : i)]
                              ? (trip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].is_boarded
                                  ? 'bg-emerald-600 border-emerald-500 text-white shadow-xs ring-1 ring-emerald-300'
                                  : 'bg-amber-500 border-amber-400 text-white shadow-xs')
                              : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'
                          ]"
                        >
                          <div class="font-black">A{{ i < 10 ? '0' + i : i }}</div>
                          <div v-if="trip.booked_seats_details && trip.booked_seats_details['A' + (i < 10 ? '0' + i : i)]" class="text-[9px] font-bold">
                            {{ trip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].is_boarded ? '✓ Đã lên' : '⏳ Chờ lên' }}
                          </div>
                          <div v-else class="text-[9px] text-slate-400 font-normal">
                            Trống
                          </div>
                          <!-- Tooltip -->
                          <div
                            v-if="trip.booked_seats_details && trip.booked_seats_details['A' + (i < 10 ? '0' + i : i)]"
                            class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-1 w-48 p-2.5 bg-slate-900 text-white rounded-xl text-[10px] shadow-2xl z-30 pointer-events-none text-left"
                          >
                            <p class="font-black text-amber-300">{{ trip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].customer_name }}</p>
                            <p class="text-slate-300 font-mono">{{ trip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].customer_phone }}</p>
                            <p class="text-emerald-400 font-mono font-bold">Giá: {{ formatCurrency(trip.price_floor_1 || trip.fare || 140000) }}</p>
                            <p :class="trip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].is_boarded ? 'text-emerald-300 font-bold' : 'text-amber-300 font-bold'">
                              Trạng thái: {{ trip.booked_seats_details['A' + (i < 10 ? '0' + i : i)].is_boarded ? '✓ Đã lên xe' : '⏳ Chưa lên xe' }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Tầng 2 (Dãy B - 2 Dãy Cửa Sổ) -->
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200 text-center flex flex-col justify-start">
                      <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-slate-200 h-8">
                        <span class="text-[11px] font-black text-slate-700 whitespace-nowrap">TẦNG 2 (DÃY B – 2 BÊN CỬA SỔ)</span>
                        <span class="px-2 py-0.5 rounded-md bg-blue-100 text-blue-800 font-mono font-black text-[10px]">
                          {{ formatCurrency(trip.price_floor_2 || (trip.fare ? trip.fare - 10000 : 130000)) }}
                        </span>
                      </div>
                      <div class="grid grid-cols-2 gap-2 text-[9px] font-bold text-slate-500 pb-1.5 mb-2 border-b border-slate-100 select-none h-6 items-center">
                        <span>🪟 Cửa sổ (Trái)</span>
                        <span>🪟 Cửa sổ (Phải)</span>
                      </div>
                      <div class="grid grid-cols-2 gap-2">
                        <div
                          v-for="i in 12"
                          :key="'B' + i"
                          class="p-1.5 sm:p-2 rounded-xl text-center font-mono font-bold text-xs transition relative group cursor-pointer border min-h-[54px] flex flex-col justify-center items-center"
                          :class="[
                            trip.booked_seats_details && trip.booked_seats_details['B' + (i < 10 ? '0' + i : i)]
                              ? (trip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].is_boarded
                                  ? 'bg-emerald-600 border-emerald-500 text-white shadow-xs ring-1 ring-emerald-300'
                                  : 'bg-amber-500 border-amber-400 text-white shadow-xs')
                              : 'bg-white border-slate-200 text-slate-600 hover:border-slate-300'
                          ]"
                        >
                          <div class="font-black">B{{ i < 10 ? '0' + i : i }}</div>
                          <div v-if="trip.booked_seats_details && trip.booked_seats_details['B' + (i < 10 ? '0' + i : i)]" class="text-[9px] font-bold">
                            {{ trip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].is_boarded ? '✓ Đã lên' : '⏳ Chờ lên' }}
                          </div>
                          <div v-else class="text-[9px] text-slate-400 font-normal">
                            Trống
                          </div>
                          <!-- Tooltip -->
                          <div
                            v-if="trip.booked_seats_details && trip.booked_seats_details['B' + (i < 10 ? '0' + i : i)]"
                            class="hidden group-hover:block absolute bottom-full left-1/2 -translate-x-1/2 mb-1 w-48 p-2.5 bg-slate-900 text-white rounded-xl text-[10px] shadow-2xl z-30 pointer-events-none text-left"
                          >
                            <p class="font-black text-amber-300">{{ trip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].customer_name }}</p>
                            <p class="text-slate-300 font-mono">{{ trip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].customer_phone }}</p>
                            <p class="text-emerald-400 font-mono font-bold">Giá: {{ formatCurrency(trip.price_floor_2 || (trip.fare ? trip.fare - 10000 : 130000)) }}</p>
                            <p :class="trip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].is_boarded ? 'text-emerald-300 font-bold' : 'text-amber-300 font-bold'">
                              Trạng thái: {{ trip.booked_seats_details['B' + (i < 10 ? '0' + i : i)].is_boarded ? '✓ Đã lên xe' : '⏳ Chưa lên xe' }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Danh sách hành khách của chuyến đó -->
                <div class="lg:col-span-6 bg-white p-4.5 rounded-2xl border border-slate-200 flex flex-col">
                  <h4 class="font-bold text-slate-800 text-xs uppercase mb-3 flex items-center justify-between">
                    <span>👥 Danh Sách Đặt Vé ({{ trip.bookings?.length || 0 }} Mã Đặt)</span>
                    <span class="text-slate-400 text-[11px]">Tổng {{ trip.booked_seats }} vé</span>
                  </h4>

                  <div class="overflow-y-auto flex-1 max-h-[580px] lg:max-h-[640px] space-y-2.5 pr-1">
                    <div
                      v-for="b in trip.bookings"
                      :key="b.booking_code"
                      class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-slate-50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                    >
                      <div class="space-y-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                          <span class="font-mono font-bold text-red-600">{{ b.booking_code }}</span>
                          <span class="font-bold text-slate-900">{{ b.customer_name }}</span>
                          <!-- Boarding status tag -->
                          <span 
                            class="text-[9px] px-2 py-0.5 rounded-md font-black uppercase tracking-wider"
                            :class="b.is_boarded ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
                          >
                            {{ b.is_boarded ? '✓ Đã lên xe' : '⏳ Chưa lên xe' }}
                          </span>
                        </div>
                        <div class="text-[11px] text-slate-500 font-mono flex items-center gap-2 flex-wrap">
                          <span>Ghế: <b class="text-red-700">{{ b.seat_code }}</b> ({{ b.total_seats }} chỗ)</span>
                          <span class="text-slate-300">•</span>
                          <span class="text-emerald-700 font-bold">Giá: {{ formatCurrency(b.seat_price || (b.total_amount / max(1, b.total_seats))) }}/ghế</span>
                          <span class="text-slate-300">•</span>
                          <!-- Nút bấm gọi số điện thoại khách hàng trực tiếp -->
                          <a 
                            v-if="b.customer_phone" 
                            :href="'tel:' + b.customer_phone"
                            class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-700 hover:text-blue-900 border border-blue-200 rounded-lg font-bold text-[10px] transition cursor-pointer shadow-2xs"
                            title="Bấm để gọi điện thoại trực tiếp cho hành khách này"
                          >
                            <span>📞</span>
                            <span>{{ b.customer_phone }}</span>
                            <span class="text-blue-500 font-normal">(Gọi ngay)</span>
                          </a>
                        </div>
                      </div>

                      <div class="text-right sm:self-center shrink-0">
                        <!-- Hiển thị đúng logic tiền cọc và thanh toán 100% -->
                        <div v-if="b.payment_status === 'PAID'">
                          <span class="font-mono font-bold text-emerald-700 block text-xs">{{ formatCurrency(b.total_amount) }}</span>
                          <span class="text-[10px] px-2 py-0.5 rounded-md font-bold inline-block mt-0.5 bg-emerald-100 text-emerald-800 border border-emerald-200">
                            ✓ Đã TT 100%
                          </span>
                        </div>
                        <div v-else-if="b.payment_status === 'DEPOSIT_PAID' || b.payment_status === 'PARTIAL_PAID'">
                          <span class="font-mono font-bold text-amber-700 block text-xs">
                            Đã cọc: {{ formatCurrency(b.paid_amount || (b.total_amount * 0.3)) }}
                          </span>
                          <span class="text-[10px] text-slate-500 font-mono block">
                            (Tổng vé: {{ formatCurrency(b.total_amount) }})
                          </span>
                          <span class="text-[10px] px-2 py-0.5 rounded-md font-bold inline-block mt-0.5 bg-amber-100 text-amber-900 border border-amber-300">
                            ⚠️ Cọc 30% • Còn thu: {{ formatCurrency(b.total_amount - (b.paid_amount || (b.total_amount * 0.3))) }}
                          </span>
                        </div>
                        <div v-else-if="b.payment_status === 'REFUNDED'">
                          <span class="font-mono font-bold text-slate-400 line-through block text-xs">{{ formatCurrency(b.total_amount) }}</span>
                          <span class="text-[10px] px-2 py-0.5 rounded-md font-bold inline-block mt-0.5 bg-rose-100 text-rose-800 border border-rose-200">
                            ✕ Đã hủy / Hoàn tiền
                          </span>
                        </div>
                        <div v-else>
                          <span class="font-mono font-bold text-slate-800 block text-xs">Cần thu: {{ formatCurrency(b.total_amount) }}</span>
                          <span class="text-[10px] px-2 py-0.5 rounded-md font-bold inline-block mt-0.5 bg-slate-100 text-slate-700 border border-slate-200">
                            💵 Thu tại quầy / xe
                          </span>
                        </div>
                      </div>
                    </div>

                    <div v-if="!trip.bookings || trip.bookings.length === 0" class="text-center py-6 text-slate-400 text-xs">
                      Chưa có hành khách đặt vé chuyến này
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- ==================== MODAL THÊM XE MỚI VÀO ĐỘI ==================== -->
    <div v-if="showCreateBusModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto animate-in zoom-in-95">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-rose-50 text-red-600 flex items-center justify-center text-xl font-black">
              🚍
            </div>
            <div>
              <h3 class="text-base font-black text-slate-900">Thêm Phương Tiện Mới Vào Đội Xe</h3>
              <p class="text-[11px] text-slate-500">Khai báo thông số kỹ thuật, biển số xe và dòng phương tiện</p>
            </div>
          </div>
          <button @click="showCreateBusModal = false" class="text-slate-400 hover:text-slate-800 text-xl font-bold cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitCreateBus" class="space-y-4 text-xs">
          <!-- Biển số xe -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Biển Số Xe <span class="text-red-500">*</span>
            </label>
            <input
              v-model="busForm.license_plate"
              type="text"
              placeholder="VD: 43B-035.88 hoặc 43B-028.99"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-500 focus:outline-none uppercase"
              required
            />
            <span v-if="busForm.errors.license_plate" class="text-red-600 text-[11px] font-bold mt-1 block">
              {{ busForm.errors.license_plate }}
            </span>
          </div>

          <!-- Dòng xe / Loại xe -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Dòng Xe / Loại Phương Tiện <span class="text-red-500">*</span>
            </label>
            <select
              v-model="busForm.bus_type"
              @change="onBusTypeChange"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500 focus:outline-none cursor-pointer"
            >
              <option value="Thaco Mobihome VIP 34 Phòng">Thaco Mobihome VIP 34 Phòng (2 tầng - Tiêu chuẩn cao cấp)</option>
              <option value="Hyundai Universe Luxury 36 Chỗ">Hyundai Universe Luxury 36 Chỗ (2 tầng - Sang trọng)</option>
              <option value="Thaco Mobihome Deluxe 36 Giường">Thaco Mobihome Deluxe 36 Giường (2 tầng - Tiêu chuẩn)</option>
              <option value="Giường Nằm Luxury 22 Cabin">Giường Nằm Luxury 22 Cabin Cung Điện (2 tầng - Siêu VIP)</option>
              <option value="Limousine Cao Cấp 16 Chỗ">Limousine Ghế Massage 16 Chỗ (1 tầng - VIP Express)</option>
              <option value="Giường Nằm Tiêu Chuẩn 40 Chỗ">Giường Nằm Tiêu Chuẩn 40 Chỗ (2 tầng - Đoàn đông)</option>
            </select>
          </div>

          <!-- Số chỗ & Số tầng -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">
                Tổng Số Ghế / Giường <span class="text-red-500">*</span>
              </label>
              <input
                v-model="busForm.total_seats"
                type="number"
                min="1"
                max="60"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-500 focus:outline-none"
                required
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">
                Số Tầng
              </label>
              <select
                v-model="busForm.floors"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-red-500 focus:outline-none cursor-pointer"
              >
                <option :value="2">2 Tầng (Tầng 1 & Tầng 2)</option>
                <option :value="1">1 Tầng (Ghế đơn tiêu chuẩn)</option>
              </select>
            </div>
          </div>

          <!-- Trạng thái ban đầu -->
          <div>
            <label class="block font-bold text-slate-700 mb-1.5">
              Trạng Thái Ban Đầu
            </label>
            <div class="grid grid-cols-2 gap-3">
              <label
                class="flex items-center gap-2 p-3 rounded-2xl border cursor-pointer transition"
                :class="busForm.status === 'ACTIVE' ? 'bg-[#dcfce7] border-emerald-400 text-[#065f46] font-bold' : 'bg-slate-50 border-slate-200 text-slate-600'"
              >
                <input type="radio" v-model="busForm.status" value="ACTIVE" class="text-emerald-600" />
                <span class="inline-flex items-center gap-1">
                  <span class="font-black">✓</span>
                  <span>Đang hoạt động</span>
                </span>
              </label>

              <label
                class="flex items-center gap-2 p-3 rounded-2xl border cursor-pointer transition"
                :class="busForm.status === 'MAINTENANCE' ? 'bg-[#fef3c7] border-amber-400 text-[#92400e] font-bold' : 'bg-slate-50 border-slate-200 text-slate-600'"
              >
                <input type="radio" v-model="busForm.status" value="MAINTENANCE" class="text-amber-600" />
                <span class="inline-flex items-center gap-1">
                  <span>🔧</span>
                  <span>Bảo dưỡng kỹ thuật</span>
                </span>
              </label>
            </div>
          </div>

          <!-- Ghi chú bảo trì / kỹ thuật -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">
              Ghi Chú Kỹ Thuật (Tùy chọn)
            </label>
            <textarea
              v-model="busForm.maintenance_notes"
              rows="2"
              placeholder="VD: Xe mới nhập khẩu năm 2026, đã kiểm định an toàn kỹ thuật..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-900 focus:bg-white focus:ring-2 focus:ring-red-500 focus:outline-none"
            ></textarea>
          </div>

          <!-- Nút hành động -->
          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showCreateBusModal = false"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition cursor-pointer"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="busForm.processing"
              class="px-5 py-2.5 bg-[#dc2626] hover:bg-[#b91c1c] text-white font-black rounded-xl shadow-md shadow-red-600/30 transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
            >
              <span>➕</span>
              <span>XÁC NHẬN THÊM XE</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <AiChatModal />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';
import AiChatModal from '@/Components/AiChatModal.vue';

const props = defineProps({
  buses_list: { type: Array, default: () => [] },
  buses: { type: Array, default: () => [] },
  fleetData: { type: Array, default: () => [] },
  selected_date: String,
  selectedDate: String,
  selected_bus_id: [String, Number],
  selectedBusId: [String, Number],
  all_buses: { type: Array, default: () => [] },
  allBuses: { type: Array, default: () => [] },
});

// Danh sách xe an toàn, tương thích mọi tên prop
const rawBuses = computed(() => {
  if (Array.isArray(props.buses_list) && props.buses_list.length > 0) return props.buses_list;
  if (Array.isArray(props.buses) && props.buses.length > 0) return props.buses;
  if (Array.isArray(props.fleetData) && props.fleetData.length > 0) return props.fleetData;
  return [];
});

const currentDate = ref(props.selected_date || props.selectedDate || new Date().toISOString().split('T')[0]);
const currentBusId = ref(props.selected_bus_id !== undefined ? String(props.selected_bus_id) : (props.selectedBusId !== undefined ? String(props.selectedBusId) : 'ALL'));
const statusFilter = ref('ALL'); // 'ALL', 'ACTIVE', 'MAINTENANCE'
const displayView = ref('cards'); // 'cards' hoặc 'table'
const busSearchQuery = ref('');

watch(() => props.selected_date || props.selectedDate, (val) => {
  if (val) currentDate.value = val;
});

watch(() => props.selected_bus_id !== undefined ? props.selected_bus_id : props.selectedBusId, (val) => {
  if (val !== undefined && val !== null) currentBusId.value = String(val);
});

// Lọc danh sách xe theo trạng thái Đang hoạt động / Bảo dưỡng + Tìm kiếm biển số, dòng xe, tài xế, tuyến đường
const filteredBuses = computed(() => {
  let list = rawBuses.value;
  if (!list || !list.length) return [];
  if (statusFilter.value === 'ACTIVE') {
    list = list.filter(b => b.status === 'ACTIVE');
  } else if (statusFilter.value === 'MAINTENANCE') {
    list = list.filter(b => b.status === 'MAINTENANCE');
  } else if (statusFilter.value === 'LIQUIDATED') {
    list = list.filter(b => b.status === 'LIQUIDATED');
  }
  if (busSearchQuery.value && busSearchQuery.value.trim() !== '') {
    const q = busSearchQuery.value.trim().toLowerCase();
    list = list.filter(b => {
      const plate = (b.license_plate || '').toLowerCase();
      const type = (b.bus_type || '').toLowerCase();
      const driver = (b.driver_name || '').toLowerCase();
      const hasRoute = Array.isArray(b.trips) && b.trips.some(t => 
        (t.route_name || '').toLowerCase().includes(q) || 
        (t.trip_code || '').toLowerCase().includes(q)
      );
      return plate.includes(q) || type.includes(q) || driver.includes(q) || hasRoute;
    });
  }
  return list;
});

const selectedBusData = computed(() => {
  if (currentBusId.value === 'ALL') return null;
  return rawBuses.value.find(b => String(b.id) === String(currentBusId.value)) || rawBuses.value[0];
});

const activeBusesCount = computed(() => {
  return rawBuses.value.filter(b => b.status === 'ACTIVE').length || 0;
});

const maintenanceCount = computed(() => {
  return rawBuses.value.filter(b => b.status === 'MAINTENANCE').length || 0;
});

const liquidatedCount = computed(() => {
  return rawBuses.value.filter(b => b.status === 'LIQUIDATED').length || 0;
});

const totalTripsCount = computed(() => {
  return rawBuses.value.reduce((sum, b) => sum + (b.trips_count || 0), 0) || 0;
});

const totalPassengersCount = computed(() => {
  return rawBuses.value.reduce((sum, b) => sum + (b.total_passengers || 0), 0) || 0;
});

const totalRevenueSum = computed(() => {
  return rawBuses.value.reduce((sum, b) => sum + (b.total_revenue || 0), 0) || 0;
});

const applyFilters = () => {
  router.get('/admin/buses', {
    date: currentDate.value,
    bus_id: currentBusId.value,
  }, {
    preserveState: false,
    preserveScroll: true,
  });
};

const handleBusSelect = () => {
  applyFilters();
};

const viewBusDetail = (busId) => {
  currentBusId.value = String(busId);
  applyFilters();
};

const resetToAll = () => {
  currentBusId.value = 'ALL';
  applyFilters();
};

const showCreateBusModal = ref(false);

const busForm = useForm({
  license_plate: '',
  bus_type: 'Thaco Mobihome VIP 34 Phòng',
  total_seats: 34,
  floors: 2,
  status: 'ACTIVE',
  current_km: 120000,
  maintenance_notes: '',
});

const onBusTypeChange = () => {
  if (busForm.bus_type.includes('22')) {
    busForm.total_seats = 22;
    busForm.floors = 2;
  } else if (busForm.bus_type.includes('16')) {
    busForm.total_seats = 16;
    busForm.floors = 1;
  } else if (busForm.bus_type.includes('36')) {
    busForm.total_seats = 36;
    busForm.floors = 2;
  } else if (busForm.bus_type.includes('40')) {
    busForm.total_seats = 40;
    busForm.floors = 2;
  } else {
    busForm.total_seats = 34;
    busForm.floors = 2;
  }
};

const submitCreateBus = () => {
  busForm.post('/admin/buses', {
    onSuccess: () => {
      showCreateBusModal.value = false;
      busForm.reset();
    }
  });
};

const toggleBusStatus = (bus) => {
  if (!bus) return;
  const targetLabel = bus.status === 'ACTIVE' ? 'Bảo dưỡng kỹ thuật 🔧' : 'Đang hoạt động ✓';
  if (confirm(`Bạn có chắc muốn chuyển trạng thái xe ${bus.license_plate} sang "${targetLabel}"?`)) {
    router.post(`/admin/buses/${bus.id}/toggle-status`, {}, {
      preserveScroll: true
    });
  }
};

const handleLiquidateBus = (bus) => {
  if (!bus) return;
  if (confirm(`Bạn có chắc chắn muốn THANH LÝ xe ${bus.license_plate}?\n\n• Xe sẽ được chuyển sang diện ĐÃ THANH LÝ.\n• Các chuyến xe tương lai trống sẽ được thu hồi hoặc chuyển giao sang xe hoạt động khác.\n• Chỗ để xe tại bãi là hoàn toàn MIỄN PHÍ (0đ).`)) {
    router.post(`/admin/buses/${bus.id}/liquidate`, {}, {
      preserveScroll: true,
      onSuccess: () => applyFilters(),
    });
  }
};

const handleRestoreBus = (bus) => {
  if (!bus) return;
  if (confirm(`Khôi phục xe ${bus.license_plate} về trạng thái SẴN SÀNG HOẠT ĐỘNG?`)) {
    router.post(`/admin/buses/${bus.id}/toggle-status`, { status: 'ACTIVE' }, {
      preserveScroll: true,
      onSuccess: () => applyFilters(),
    });
  }
};

const handleDeleteBus = (bus) => {
  if (!bus) return;
  if (confirm(`CẢNH BÁO NGUY HIỂM: Bạn có chắc chắn muốn XÓA VĨNH VIỄN xe ${bus.license_plate} khỏi đội xe?\n\nThao tác này sẽ xóa hoàn toàn phương tiện và không thể hoàn tác!`)) {
    router.delete(`/admin/buses/${bus.id}`, {
      preserveScroll: true,
      onSuccess: () => applyFilters(),
      onError: (err) => {
        alert(err.bus || err.error || 'Không thể xóa xe vì đang có hành khách đặt vé!');
      }
    });
  }
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

// Cảnh báo chỉ xuất hiện khi còn đúng <= 10 phút hoặc xe đang chạy trên đường
const isUrgentDepartureWarning = (trip) => {
  if (!trip || !trip.bookings) return false;
  const unboarded = trip.bookings.filter(b => !b.is_boarded);
  if (unboarded.length === 0) return false;

  // Nếu xe đã hoàn thành hoặc hủy -> không hiển thị cảnh báo
  if (['COMPLETED', 'CANCELLED', 'CLOSED', 'ARRIVED'].includes(trip.status)) return false;

  // Nếu xe đang chạy trên đường mà vẫn còn khách chưa check-in -> cảnh báo khẩn cấp
  if (trip.status === 'IN_TRANSIT' || trip.status === 'RUNNING') return true;

  // Kiểm tra thời gian xuất bến thực tế
  const depStr = trip.departure_datetime || (trip.departure_time ? `${currentDate.value}T${trip.departure_time}:00` : null);
  if (depStr) {
    const depTime = new Date(depStr).getTime();
    if (!isNaN(depTime)) {
      const diffMinutes = Math.floor((depTime - Date.now()) / 60000);
      // CHỈ CẢNH BÁO KHI CÒN <= 10 PHÚT VÀ KHÔNG QUÁ 60 PHÚT TRỄ
      return diffMinutes <= 10 && diffMinutes >= -60;
    }
  }

  return false;
};
</script>
