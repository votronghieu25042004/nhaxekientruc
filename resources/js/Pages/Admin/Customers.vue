<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans">
    <AdminHeader title="Quản Lý Khách Hàng & CRM Hành Khách" subtitle="Dữ liệu khách hàng, tổng chi tiêu tích lũy, phân hạng VIP và lịch sử số ghế" />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">
      <!-- Thống kê tổng quan CRM Phân hạng & Lịch sử đi xe -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-3xl border border-slate-200/90 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tổng Khách Hàng</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ allCustomersCount }} <span class="text-sm text-slate-400 font-normal">hành khách</span></p>
            <p class="text-xs mt-0.5 flex items-center gap-1.5 font-bold">
              <span class="text-emerald-600">🟢 {{ activeCustomersCount }} hoạt động</span>
              <span v-if="lockedCustomersCount > 0" class="text-rose-600">• 🔒 {{ lockedCustomersCount }} đã khóa</span>
            </p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-2xs">👥</div>
        </div>

        <div class="bg-gradient-to-br from-indigo-50 via-purple-50 to-white p-5 rounded-3xl border-2 border-indigo-200 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider">💎 VIP Kim Cương & Vàng 👑</p>
            <p class="text-2xl font-black text-indigo-950 mt-1">{{ diamondCount + goldCount }} <span class="text-sm text-indigo-400 font-normal">thành viên</span></p>
            <p class="text-xs text-purple-700 font-semibold mt-0.5">{{ diamondCount }} Kim Cương • {{ goldCount }} VIP Vàng</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 text-white flex items-center justify-center text-xl shadow-md">💎</div>
        </div>

        <div class="bg-gradient-to-br from-slate-50 via-amber-50/40 to-white p-5 rounded-3xl border-2 border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">🥈 Bạc & Khách Đã Đi 1 Lần</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ silverCount + oneTripCount }} <span class="text-sm text-slate-400 font-normal">khách</span></p>
            <p class="text-xs text-slate-600 font-medium mt-0.5">{{ silverCount }} Thân Thiết • {{ oneTripCount }} khách đi 1 lần</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-800 flex items-center justify-center text-xl shadow-2xs">🥈</div>
        </div>

        <div class="bg-white p-5 rounded-3xl border border-slate-200/90 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tổng Doanh Thu</p>
            <p class="text-2xl font-black text-emerald-600 font-mono mt-1">{{ formatCurrency(totalSystemSpent) }}</p>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Doanh số tích lũy toàn hệ thống</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-2xs">💰</div>
        </div>
      </div>

      <!-- THANH TAB LỌC THEO PHÂN HẠNG THÀNH VIÊN THỰC TẾ (1 LẦN, VÀI LẦN, VIP) -->
      <div class="bg-white p-4 rounded-3xl border border-slate-200/90 shadow-xs space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <!-- Tier Filter Tabs -->
          <div class="flex flex-wrap items-center gap-2">
            <button
              @click="setTierFilter('ALL')"
              :class="activeTier === 'ALL' ? 'bg-slate-900 text-white font-black shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold'"
              class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <span>Tất Cả</span>
              <span class="px-2 py-0.5 rounded-md bg-white/20 text-xs font-mono font-bold">{{ allCustomersCount }}</span>
            </button>

            <button
              @click="setTierFilter('DIAMOND')"
              :class="activeTier === 'DIAMOND' ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-black shadow-md' : 'bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 font-bold'"
              class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <span>💎 VIP Kim Cương</span>
              <span class="px-2 py-0.5 rounded-md bg-white/30 text-xs font-mono font-bold">{{ diamondCount }}</span>
            </button>

            <button
              @click="setTierFilter('GOLD')"
              :class="activeTier === 'GOLD' ? 'bg-gradient-to-r from-amber-500 to-yellow-600 text-white font-black shadow-md' : 'bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-200 font-bold'"
              class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <span>👑 VIP Vàng</span>
              <span class="px-2 py-0.5 rounded-md bg-white/30 text-xs font-mono font-bold">{{ goldCount }}</span>
            </button>

            <button
              @click="setTierFilter('SILVER')"
              :class="activeTier === 'SILVER' ? 'bg-slate-800 text-white font-black shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 font-bold'"
              class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <span>🥈 Thân Thiết Bạc</span>
              <span class="px-2 py-0.5 rounded-md bg-white/30 text-xs font-mono font-bold">{{ silverCount }}</span>
            </button>

            <button
              @click="setTierFilter('ONE_TRIP')"
              :class="activeTier === 'ONE_TRIP' ? 'bg-rose-600 text-white font-black shadow-sm' : 'bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-bold'"
              class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <span>⭐ Đã Đi 1 Lần</span>
              <span class="px-2 py-0.5 rounded-md bg-white/30 text-xs font-mono font-bold">{{ oneTripCount }}</span>
            </button>

            <button
              @click="setTierFilter('NEW')"
              :class="activeTier === 'NEW' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-bold'"
              class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <span>🆕 Mới Đăng Ký</span>
              <span class="px-2 py-0.5 rounded-md bg-white/30 text-xs font-mono font-bold">{{ newCount }}</span>
            </button>

            <button
              @click="setTierFilter('LOCKED')"
              :class="activeTier === 'LOCKED' ? 'bg-rose-600 text-white font-black shadow-sm' : 'bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-bold'"
              class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <span>🔒 Đã Bị Khóa</span>
              <span class="px-2 py-0.5 rounded-md bg-white/30 text-xs font-mono font-bold">{{ lockedCustomersCount }}</span>
            </button>
          </div>

          <!-- Sắp xếp danh sách -->
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 whitespace-nowrap">Sắp xếp:</span>
            <select
              v-model="selectedSort"
              @change="handleSortChange"
              class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-red-500 cursor-pointer"
            >
              <option value="mixed">🕒 Hoạt Động Gần Đây (Thực Tế Tự Nhiên)</option>
              <option value="spent">💰 Chi Tiêu Nhiều Nhất</option>
              <option value="trips">🚌 Số Lần Đi Nhiều Nhất</option>
              <option value="name">🔤 Tên Khách Hàng (A-Z)</option>
            </select>
          </div>
        </div>

        <!-- Bộ Điều Khiển: Tìm Kiếm, Chọn PageSize & Chuyển Chế Độ Xem (Card / Table) -->
        <div class="pt-3 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h2 class="font-black text-slate-900 text-base">Danh Sách Hành Khách ({{ filteredCustomers.length }} khách)</h2>
            <p class="text-xs text-slate-500 mt-0.5">Hiển thị đầy đủ khách mới, khách đi 1 lần, khách thân thiết và VIP</p>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <!-- Toggle View Mode: Grid Cards vs Table -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl text-xs font-bold text-slate-600">
              <button
                @click="viewMode = 'grid'"
                :class="viewMode === 'grid' ? 'bg-[#dc2626] text-white shadow-xs font-black' : 'hover:bg-white text-slate-700'"
                class="px-3.5 py-2 rounded-lg transition cursor-pointer flex items-center gap-1.5"
              >
                <span>🗂️</span>
                <span>Dạng Thẻ</span>
              </button>
              <button
                @click="viewMode = 'table'"
                :class="viewMode === 'table' ? 'bg-[#dc2626] text-white shadow-xs font-black' : 'hover:bg-white text-slate-700'"
                class="px-3.5 py-2 rounded-lg transition cursor-pointer flex items-center gap-1.5"
              >
                <span>📋</span>
                <span>Dạng Bảng</span>
              </button>
            </div>

            <!-- Page size selector -->
            <div class="flex items-center gap-1.5 text-xs text-slate-500">
              <select
                v-model="pageSize"
                @change="currentPage = 1"
                class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-red-500 cursor-pointer"
              >
                <option :value="6">6 khách/trang</option>
                <option :value="12">12 khách/trang</option>
                <option :value="24">24 khách/trang</option>
              </select>
            </div>

            <!-- Search input -->
            <div class="flex items-center gap-2">
              <div class="relative">
                <input
                  type="text"
                  v-model="searchQuery"
                  @keyup.enter="handleSearch"
                  placeholder="Tìm tên, SĐT, Email..."
                  class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 w-52 sm:w-60 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 font-medium"
                />
                <button 
                  v-if="searchQuery"
                  @click="searchQuery = ''; handleSearch()"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs cursor-pointer"
                >✕</button>
              </div>
              <button
                @click="handleSearch"
                class="px-4 py-2 bg-[#dc2626] hover:bg-[#b91c1c] text-white font-black text-xs rounded-xl shadow-xs transition cursor-pointer"
              >
                Tìm
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== 1. HIỂN THỊ DẠNG THẺ (CARD GRID ĐẶC SẮC THEO TỪNG HẠNG) ==================== -->
      <div v-if="viewMode === 'grid'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div
            v-for="(cust, idx) in paginatedCustomers"
            :key="cust.phone"
            class="rounded-3xl p-5 transition flex flex-col justify-between space-y-4"
            :class="getCardContainerClass(cust.tier_code)"
          >
            <!-- Card Header: Avatar + Tên & Khách ID + Badge Rank Nổi Bật -->
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div 
                  class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-base shrink-0 transition"
                  :class="getAvatarClass(cust.tier_code)"
                >
                  {{ cust.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h3 class="font-black text-slate-900 text-base leading-snug">{{ cust.name }}</h3>
                  <p class="text-xs text-slate-500 font-medium">Khách ID #{{ 1000 + (currentPage - 1) * pageSize + idx }} • Tham gia {{ cust.joined_at }}</p>
                </div>
              </div>

              <div class="flex flex-col items-end gap-1.5 shrink-0">
                <!-- Badge Phân Hạng VIP Sang Trọng -->
                <span 
                  class="px-3.5 py-1.5 rounded-full text-xs font-black whitespace-nowrap inline-flex items-center gap-1.5 border shadow-2xs"
                  :class="getRankBadgeClass(cust.tier_code)"
                >
                  <span>{{ getRankIcon(cust.tier_code) }}</span>
                  <span>{{ getRankTitle(cust.tier_code) }}</span>
                </span>

                <!-- Badge Trạng Thái Tài Khoản -->
                <span 
                  class="px-2.5 py-0.5 rounded-full text-[10px] font-black inline-flex items-center gap-1 border shadow-2xs"
                  :class="cust.status === 'LOCKED' ? 'bg-rose-100 text-rose-800 border-rose-300 animate-pulse' : cust.status === 'DELETED' ? 'bg-slate-200 text-slate-700 border-slate-300' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                >
                  <span>{{ cust.status === 'LOCKED' ? '🔒 Đã Khóa' : cust.status === 'DELETED' ? '🗑️ Đã Xóa' : '🟢 Hoạt Động' }}</span>
                </span>
              </div>
            </div>

            <!-- Banner Đặc Quyền Riêng Từng Hạng -->
            <div 
              class="px-3.5 py-2 rounded-xl text-xs font-bold border flex items-center gap-2"
              :class="getPrivilegeBannerClass(cust.tier_code)"
            >
              <span>✨</span>
              <span>{{ getPrivilegeText(cust.tier_code) }}</span>
            </div>

            <!-- Card Body: Thông tin liên hệ & Điểm Tích Lũy Thật -->
            <div class="space-y-2.5 text-xs pt-2 border-t border-slate-100">
              <div class="flex items-center justify-between py-0.5">
                <span class="text-slate-500 font-medium">Số điện thoại:</span>
                <span class="font-mono font-bold text-slate-900 text-sm">{{ cust.phone }}</span>
              </div>
              <div class="flex items-center justify-between py-0.5">
                <span class="text-slate-500 font-medium">Email:</span>
                <span class="text-slate-800 font-semibold truncate max-w-[200px]">{{ cust.email }}</span>
              </div>
              <div class="flex items-center justify-between py-0.5">
                <span class="text-slate-500 font-medium">Số lần đi xe:</span>
                <span class="font-black text-red-600 bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-100 text-xs">
                  {{ cust.total_trips }} chuyến đã đi
                </span>
              </div>

              <!-- Hàng Điểm Tích Lũy (Chỉ cộng khi chuyến hoàn thành) -->
              <div class="flex items-center justify-between py-2 bg-amber-50/70 px-3 rounded-xl border border-amber-200/60">
                <span class="text-amber-900 font-bold flex items-center gap-1.5 text-xs">
                  <span>🪙</span>
                  <span>Điểm Thưởng Tích Lũy:</span>
                </span>
                <div class="text-right">
                  <span class="font-mono font-black text-amber-700 text-sm">
                    {{ (cust.reward_points || 0).toLocaleString('vi-VN') }} điểm
                  </span>
                </div>
              </div>
            </div>

            <!-- Card Footer: Tổng Chi Tiêu + Nút Quản Lý Tài Khoản (Khóa / Mở Khóa / Xóa) + Lịch Sử Vé -->
            <div class="pt-2 border-t border-slate-100 space-y-2.5">
              <div class="flex items-center justify-between">
                <span class="text-xs uppercase font-bold text-slate-400 tracking-wider">TỔNG CHI TIÊU</span>
                <span class="text-emerald-600 font-mono font-black text-lg">{{ formatCurrency(cust.total_spent) }}</span>
              </div>

              <!-- Nút Thao Tác Quản Trị: Khóa / Mở Khóa / Xóa Nick -->
              <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100">
                <button
                  @click="toggleAccountStatus(cust)"
                  class="py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs active:scale-95"
                  :class="cust.status === 'LOCKED' ? 'bg-emerald-600 hover:bg-emerald-700 text-white font-black' : cust.status === 'DELETED' ? 'bg-blue-600 hover:bg-blue-700 text-white font-bold' : 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'"
                  :title="cust.status === 'LOCKED' ? 'Mở khóa để khách đăng nhập lại bình thường' : 'Khóa tài khoản (chặn khách đăng nhập)'"
                >
                  <span>{{ cust.status === 'LOCKED' ? '🔓' : cust.status === 'DELETED' ? '♻️' : '🔒' }}</span>
                  <span>{{ cust.status === 'LOCKED' ? 'Mở Khóa' : cust.status === 'DELETED' ? 'Khôi Phục' : 'Khóa Nick' }}</span>
                </button>

                <button
                  v-if="cust.status !== 'DELETED'"
                  @click="confirmDeleteAccount(cust)"
                  class="py-2 px-3 rounded-xl text-xs font-bold bg-slate-100 hover:bg-red-50 text-slate-600 hover:text-red-700 border border-slate-200 hover:border-red-200 transition flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs active:scale-95"
                  title="Xóa tài khoản khách hàng khỏi hệ thống"
                >
                  <span>🗑️</span>
                  <span>Xóa Tài Khoản</span>
                </button>
                <div v-else class="py-2 px-3 rounded-xl text-xs font-bold text-slate-400 bg-slate-100 border border-slate-200 text-center flex items-center justify-center">
                  Đã Xóa
                </div>
              </div>

              <button
                @click="openHistoryModal(cust)"
                class="w-full py-2.5 text-white font-black text-xs sm:text-sm rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]"
                :class="getHistoryBtnClass(cust.tier_code)"
              >
                <span>🎟️</span>
                <span>Xem Lịch Sử Đi & Ghế ({{ cust.total_trips }} chuyến)</span>
              </button>
            </div>
          </div>
        </div>

        <div v-if="customers.length === 0" class="text-center py-12 bg-white rounded-3xl border border-slate-200 text-slate-400">
          Không tìm thấy khách hàng nào phù hợp với từ khóa tìm kiếm
        </div>
      </div>

      <!-- ==================== 2. HIỂN THỊ DẠNG BẢNG (TABLE VIEW) ==================== -->
      <div v-else class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-black tracking-wider border-b border-slate-200">
              <tr>
                <th class="px-4 py-3.5">Họ Tên Khách Hàng</th>
                <th class="px-4 py-3.5">Số Điện Thoại</th>
                <th class="px-4 py-3.5 text-center">Số Lần Đi Xe</th>
                <th class="px-4 py-3.5 text-right">Tổng Chi Tiêu</th>
                <th class="px-4 py-3.5 text-center">Điểm Tích Lũy</th>
                <th class="px-4 py-3.5 text-center">Hạng Khách Hàng</th>
                <th class="px-4 py-3.5 text-center">Trạng Thái</th>
                <th class="px-4 py-3.5 text-center">Thao Tác Quản Trị</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="(cust, idx) in paginatedCustomers"
                :key="cust.phone"
                class="hover:bg-slate-50/80 transition"
                :class="cust.status === 'LOCKED' ? 'bg-rose-50/40' : cust.tier_code === 'DIAMOND' ? 'bg-purple-50/20' : cust.tier_code === 'GOLD' ? 'bg-amber-50/20' : ''"
              >
                <td class="px-4 py-3.5">
                  <div class="flex items-center gap-2.5">
                    <div 
                      class="w-9 h-9 rounded-xl text-white font-black flex items-center justify-center text-xs shadow-2xs shrink-0"
                      :class="getAvatarClass(cust.tier_code)"
                    >
                      {{ cust.name.charAt(0) }}
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 block">{{ cust.name }}</span>
                      <span class="text-[10px] text-slate-400">ID #{{ 1000 + (currentPage - 1) * pageSize + idx }} • {{ cust.email }}</span>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3.5 font-mono font-bold text-slate-700 whitespace-nowrap">
                  {{ cust.phone }}
                </td>
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                  <span class="px-2.5 py-1 rounded-full font-black text-xs bg-rose-50 text-red-700 border border-rose-100">
                    {{ cust.total_trips }} lần
                  </span>
                </td>
                <td class="px-4 py-3.5 text-right font-black text-emerald-600 font-mono text-sm whitespace-nowrap">
                  {{ formatCurrency(cust.total_spent) }}
                </td>
                <td class="px-4 py-3.5 text-center whitespace-nowrap font-mono font-bold text-amber-700">
                  🪙 {{ (cust.reward_points || 0).toLocaleString('vi-VN') }} pts
                </td>
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                  <span 
                    class="px-3 py-1 rounded-full text-xs font-black whitespace-nowrap inline-flex items-center justify-center gap-1.5 border shadow-2xs"
                    :class="getRankBadgeClass(cust.tier_code)"
                  >
                    <span>{{ getRankIcon(cust.tier_code) }}</span>
                    <span>{{ getRankTitle(cust.tier_code) }}</span>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                  <span 
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-black inline-flex items-center gap-1 border shadow-2xs"
                    :class="cust.status === 'LOCKED' ? 'bg-rose-100 text-rose-800 border-rose-300 animate-pulse' : cust.status === 'DELETED' ? 'bg-slate-200 text-slate-700 border-slate-300' : 'bg-emerald-50 text-emerald-700 border-emerald-200'"
                  >
                    <span>{{ cust.status === 'LOCKED' ? '🔒 Đã Khóa' : cust.status === 'DELETED' ? '🗑️ Đã Xóa' : '🟢 Hoạt Động' }}</span>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5">
                    <button
                      @click="toggleAccountStatus(cust)"
                      class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer shadow-2xs active:scale-95"
                      :class="cust.status === 'LOCKED' ? 'bg-emerald-600 hover:bg-emerald-700 text-white font-black' : cust.status === 'DELETED' ? 'bg-blue-600 hover:bg-blue-700 text-white font-bold' : 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'"
                      :title="cust.status === 'LOCKED' ? 'Mở khóa tài khoản' : 'Khóa tài khoản'"
                    >
                      {{ cust.status === 'LOCKED' ? '🔓 Mở' : cust.status === 'DELETED' ? '♻️ Phục Hồi' : '🔒 Khóa' }}
                    </button>

                    <button
                      v-if="cust.status !== 'DELETED'"
                      @click="confirmDeleteAccount(cust)"
                      class="px-2 py-1.5 rounded-lg text-xs font-bold text-slate-500 hover:text-red-700 hover:bg-red-50 border border-slate-200 transition cursor-pointer shadow-2xs active:scale-95"
                      title="Xóa tài khoản khách hàng"
                    >
                      🗑️ Xóa
                    </button>

                    <button
                      @click="openHistoryModal(cust)"
                      class="px-2.5 py-1.5 text-white font-bold text-xs rounded-lg shadow-xs transition cursor-pointer flex items-center gap-1 active:scale-[0.99]"
                      :class="getHistoryBtnClass(cust.tier_code)"
                      title="Xem lịch sử chuyến xe & số ghế"
                    >
                      <span>🎟️</span>
                      <span>Vé ({{ cust.total_trips }})</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredCustomers.length === 0">
                <td colspan="8" class="text-center py-8 text-slate-400 font-bold">
                  Không tìm thấy khách hàng nào phù hợp với từ khóa hoặc bộ lọc đã chọn
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- THANH PHÂN TRANG (PAGINATION) CHUNG CHO CẢ CARD VÀ BẢNG -->
      <div v-if="filteredCustomers && filteredCustomers.length > 0" class="px-6 py-4 rounded-3xl border border-slate-200/80 bg-white flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
        <div class="text-xs text-slate-500 font-medium">
          Hiển thị <b class="text-slate-900">{{ (currentPage - 1) * pageSize + 1 }} - {{ Math.min(currentPage * pageSize, filteredCustomers.length) }}</b> trên tổng số <b class="text-slate-900">{{ filteredCustomers.length }}</b> khách hàng
        </div>

        <div class="flex items-center gap-2">
          <button
            :disabled="currentPage === 1"
            @click="currentPage--"
            class="px-3.5 py-1.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
          >
            ← Trang Trước
          </button>

          <div class="flex items-center gap-1">
            <template v-for="(p, idx) in visiblePages" :key="idx">
              <span v-if="p === '...'" class="px-1.5 text-xs text-slate-400 font-bold select-none">...</span>
              <button
                v-else
                @click="currentPage = p"
                class="w-7 h-7 rounded-lg text-xs font-black transition cursor-pointer"
                :class="currentPage === p ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
              >
                {{ p }}
              </button>
            </template>
          </div>

          <button
            :disabled="currentPage >= totalPages"
            @click="currentPage++"
            class="px-3.5 py-1.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
          >
            Kế Tiếp →
          </button>
        </div>
      </div>
    </main>

    <!-- ==================== MODAL LỊCH SỬ CHUYẾN ĐI & SỐ GHẾ ==================== -->
    <div v-if="selectedCustomerModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 max-w-2xl w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-black text-slate-900 text-lg">{{ selectedCustomerModal.name }}</span>
              <span 
                class="px-2.5 py-0.5 rounded-full text-[11px] font-black border"
                :class="getRankBadgeClass(selectedCustomerModal.tier_code)"
              >
                {{ getRankIcon(selectedCustomerModal.tier_code) }} {{ getRankTitle(selectedCustomerModal.tier_code) }}
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">SĐT: {{ selectedCustomerModal.phone }} • Email: {{ selectedCustomerModal.email }}</p>
          </div>
          <button 
            @click="selectedCustomerModal = null" 
            class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer p-1 rounded-lg"
          >
            ✕
          </button>
        </div>

        <!-- Thống kê điểm thưởng khách hàng -->
        <div class="grid grid-cols-2 gap-3 p-3 bg-amber-50/70 border border-amber-200/60 rounded-2xl text-xs">
          <div>
            <span class="text-slate-500 font-medium block">Điểm Tích Lũy Đã Cộng (Chuyến Hoàn Thành):</span>
            <span class="font-black text-amber-800 text-base font-mono">🪙 {{ (selectedCustomerModal.reward_points || 0).toLocaleString('vi-VN') }} Điểm</span>
          </div>
          <div>
            <span class="text-slate-500 font-medium block">Tổng Giá Trị Chi Tiêu:</span>
            <span class="font-black text-emerald-700 text-base font-mono">{{ formatCurrency(selectedCustomerModal.total_spent) }}</span>
          </div>
        </div>

        <!-- Danh sách từng chuyến xe đã đi -->
        <div class="space-y-3">
          <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">
            Lịch Sử Đặt Vé & Tích Điểm ({{ selectedCustomerModal.history?.length || 0 }} chuyến)
          </h4>

          <div v-if="selectedCustomerModal.history && selectedCustomerModal.history.length > 0" class="space-y-2.5">
            <div
              v-for="h in selectedCustomerModal.history"
              :key="h.booking_code"
              class="p-4 rounded-2xl border border-slate-100 bg-slate-50/80 hover:bg-slate-100 transition space-y-2 text-xs"
            >
              <div class="flex items-center justify-between">
                <span class="font-black text-red-600 font-mono">{{ h.booking_code }}</span>
                <span 
                  v-if="h.points_awarded"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                >
                  ✓ Đã cộng +{{ h.points_earned }} điểm (Chuyến hoàn thành)
                </span>
                <span 
                  v-else
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200"
                >
                  ⏳ Chờ chuyến xe chạy xong (+{{ h.points_earned }} điểm)
                </span>
              </div>

              <div class="font-bold text-slate-800 text-sm">
                {{ h.route_name }}
              </div>

              <div class="grid grid-cols-2 gap-2 text-slate-600 text-[11px]">
                <div>🚌 Xe: <strong class="text-slate-800 font-mono">{{ h.bus_plate }}</strong> ({{ h.driver_name }})</div>
                <div>💺 Ghế đã đặt: <strong class="text-red-700 font-mono bg-white px-1.5 py-0.2 rounded border border-slate-200">{{ h.seats }}</strong></div>
                <div>🕒 Khởi hành: <strong>{{ h.departure_time }}</strong></div>
                <div>💰 Tiền vé: <strong class="text-emerald-700">{{ formatCurrency(h.amount) }}</strong></div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-6 text-slate-400 text-xs">
            Khách hàng này chưa có vé xe nào được lưu trong hệ thống.
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
          <button
            @click="selectedCustomerModal = null"
            class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition cursor-pointer"
          >
            Đóng Lại
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL XÁC NHẬN XÓA TÀI KHOẢN KHÁCH HÀNG ==================== -->
    <div v-if="showDeleteModal && customerToDelete" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-2xl animate-in zoom-in-95">
        <div class="w-14 h-14 rounded-2xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center text-2xl mx-auto shadow-2xs">
          🗑️
        </div>

        <div class="text-center space-y-2">
          <h3 class="text-base font-black text-slate-900">
            Xác Nhận Xóa Vĩnh Viễn Tài Khoản Khách Hàng?
          </h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Bạn có chắc chắn muốn xóa tài khoản của khách hàng <strong class="text-slate-900 font-black">{{ customerToDelete.name }}</strong> (SĐT: <span class="font-mono font-bold text-red-600">{{ customerToDelete.phone }}</span>)?
          </p>
          <div class="p-3.5 bg-red-50 border border-red-200 rounded-2xl text-[11.5px] text-red-950 text-left space-y-1.5 leading-relaxed">
            <p class="font-black text-red-900 flex items-center gap-1.5">
              <span>⚠️</span>
              <span>Lưu ý quan trọng:</span>
            </p>
            <p>
              • Tài khoản sẽ bị <strong>XÓA VĨNH VIỄN VÀ MẤT HOÀN TOÀN TRONG DATABASE</strong>.
            </p>
            <p>
              • Người này có thể dùng lại email / số điện thoại để đăng ký tài khoản mới bình thường.
            </p>
            <p class="text-slate-600 border-t border-red-200 pt-1">
              * Nếu bạn chỉ muốn chặn tạm thời không cho khách đăng nhập, hãy dùng chức năng <strong>"Khóa Nick"</strong> (Tài khoản vẫn còn nhưng khi khách đăng nhập sẽ bị thông báo tài khoản bị khóa).
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 pt-2">
          <button
            @click="showDeleteModal = false; customerToDelete = null"
            class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-100 transition cursor-pointer"
          >
            Hủy Bỏ
          </button>
          <button
            @click="executeDeleteAccount"
            class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md transition cursor-pointer active:scale-95"
          >
            Đồng Ý Xóa Tài Khoản
          </button>
        </div>
      </div>
    </div>

    <AiChatModal />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';
import AiChatModal from '@/Components/AiChatModal.vue';

const props = defineProps({
  customers: Array,
  stats: Object,
  tier_filter: { type: String, default: 'ALL' },
  sort_by: { type: String, default: 'mixed' },
  search_query: String,
});

const viewMode = ref('grid'); // 'grid' hoặc 'table'
const searchQuery = ref(props.search_query || '');
const activeTier = ref(props.tier_filter || 'ALL');
const selectedSort = ref(props.sort_by || 'mixed');
const selectedCustomerModal = ref(null);
const customerToDelete = ref(null);
const showDeleteModal = ref(false);
const currentPage = ref(1);
const pageSize = ref(6);

// Tổng số và phân hạng tính chuẩn xác trên toàn bộ khách hàng
const allCustomersCount = computed(() => {
  return (props.customers || []).length;
});

const activeCustomersCount = computed(() => {
  return (props.customers || []).filter(c => (c.status || 'ACTIVE') === 'ACTIVE').length;
});

const lockedCustomersCount = computed(() => {
  return (props.customers || []).filter(c => c.status === 'LOCKED').length;
});

const deletedCustomersCount = computed(() => {
  return (props.customers || []).filter(c => c.status === 'DELETED').length;
});

const diamondCount = computed(() => {
  return (props.customers || []).filter(c => c.tier_code === 'DIAMOND').length;
});

const goldCount = computed(() => {
  return (props.customers || []).filter(c => c.tier_code === 'GOLD').length;
});

const silverCount = computed(() => {
  return (props.customers || []).filter(c => c.tier_code === 'SILVER').length;
});

const oneTripCount = computed(() => {
  return (props.customers || []).filter(c => c.total_trips === 1).length;
});

const newCount = computed(() => {
  return (props.customers || []).filter(c => c.total_trips === 0).length;
});

const totalSystemSpent = computed(() => {
  return (props.customers || []).reduce((sum, c) => sum + (c.total_spent || 0), 0);
});

// Lọc và sắp xếp hoàn toàn Reactive trên bộ nhớ Vue (Phản hồi tức thì 0ms, không reload, không loạn số)
const filteredCustomers = computed(() => {
  if (!props.customers) return [];
  let list = [...props.customers];

  // 1. Lọc theo Phân Hạng Thành Viên hoặc Trạng Thái Khóa
  if (activeTier.value === 'LOCKED') {
    list = list.filter(c => c.status === 'LOCKED');
  } else if (activeTier.value === 'DELETED') {
    list = list.filter(c => c.status === 'DELETED');
  } else if (activeTier.value === 'DIAMOND') {
    list = list.filter(c => c.tier_code === 'DIAMOND');
  } else if (activeTier.value === 'GOLD') {
    list = list.filter(c => c.tier_code === 'GOLD');
  } else if (activeTier.value === 'SILVER') {
    list = list.filter(c => c.tier_code === 'SILVER');
  } else if (activeTier.value === 'ONE_TRIP') {
    list = list.filter(c => c.total_trips === 1);
  } else if (activeTier.value === 'NEW') {
    list = list.filter(c => c.total_trips === 0);
  }

  // 2. Lọc theo Từ Khóa Tìm Kiếm (Tên, SĐT, Email)
  if (searchQuery.value && searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(c => 
      (c.name && c.name.toLowerCase().includes(q)) ||
      (c.phone && c.phone.includes(q)) ||
      (c.email && c.email.toLowerCase().includes(q))
    );
  }

  // 3. Sắp Xếp
  if (selectedSort.value === 'spent') {
    list.sort((a, b) => (b.total_spent || 0) - (a.total_spent || 0));
  } else if (selectedSort.value === 'trips') {
    list.sort((a, b) => (b.total_trips || 0) - (a.total_trips || 0));
  } else if (selectedSort.value === 'name') {
    list.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
  } else {
    // mixed: Hoạt động gần đây nhất
    list.sort((a, b) => new Date(b.latest_booking_at || 0) - new Date(a.latest_booking_at || 0));
  }

  return list;
});

const totalPages = computed(() => {
  if (filteredCustomers.value.length === 0) return 1;
  return Math.ceil(filteredCustomers.value.length / pageSize.value);
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
  for (let i = start; i <= end; i++) pages.push(i);
  if (end < total - 1) pages.push('...');
  pages.push(total);
  return pages;
});

const paginatedCustomers = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return filteredCustomers.value.slice(start, start + pageSize.value);
});

// Chuyển Tab VIP / Thân Thiết / 1 Lần / Bị Khóa tức thì (0ms)
const setTierFilter = (tier) => {
  activeTier.value = tier;
  currentPage.value = 1;
};

// Đổi cách sắp xếp tức thì (0ms)
const handleSortChange = () => {
  currentPage.value = 1;
};

// Tìm kiếm tức thì (0ms)
const handleSearch = () => {
  currentPage.value = 1;
};

const openHistoryModal = (cust) => {
  selectedCustomerModal.value = cust;
};

// Khóa / Mở khóa tài khoản khách hàng
const toggleAccountStatus = (cust) => {
  const isLocked = cust.status === 'LOCKED';
  const isDeleted = cust.status === 'DELETED';
  const action = isLocked ? 'Mở Khóa' : isDeleted ? 'Khôi Phục' : 'Khóa';
  const note = (!isLocked && !isDeleted)
    ? 'Khi bị khóa, khách hàng sẽ KHÔNG THỂ đăng nhập và nhận được thông báo:\n"⛔ Tài khoản của bạn đã bị KHÓA. Xin vui lòng liên hệ nhà xe nhaxekientruc qua Hotline: 1900 6067 hoặc SĐT: 0905 123 456 để được hỗ trợ mở khóa tài khoản!".'
    : 'Khách hàng sẽ có thể đăng nhập lại hệ thống bình thường.';

  if (!confirm(`Xác nhận ${action} tài khoản của khách hàng "${cust.name}" (${cust.phone})?\n\n${note}`)) {
    return;
  }

  router.post(`/admin/users/${cust.id}/toggle-status`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      cust.status = (isLocked || isDeleted) ? 'ACTIVE' : 'LOCKED';
    }
  });
};

// Xác nhận mở modal xóa tài khoản
const confirmDeleteAccount = (cust) => {
  customerToDelete.value = cust;
  showDeleteModal.value = true;
};

// Thực thi xóa tài khoản
const executeDeleteAccount = () => {
  if (!customerToDelete.value) return;
  const cust = customerToDelete.value;
  router.delete(`/admin/users/${cust.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      customerToDelete.value = null;
      router.reload();
    }
  });
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

// ==================== TẠO PHONG CÁCH ĐẶC SẮC RIÊNG BIỆT CHO TỪNG HẠNG (MỤC 1) ====================
const getCardContainerClass = (tier) => {
  switch (tier) {
    case 'DIAMOND':
      return 'bg-gradient-to-br from-indigo-50/70 via-purple-50/40 to-white border-2 border-indigo-300 shadow-md ring-1 ring-purple-300/40 hover:shadow-xl hover:border-indigo-500';
    case 'GOLD':
      return 'bg-gradient-to-br from-amber-50/70 via-yellow-50/40 to-white border-2 border-amber-300 shadow-sm hover:shadow-lg hover:border-amber-400';
    case 'SILVER':
      return 'bg-gradient-to-br from-slate-50/90 to-white border-2 border-slate-300 shadow-2xs hover:shadow-md hover:border-slate-400';
    default:
      return 'bg-white border border-slate-200 shadow-2xs hover:shadow-sm hover:border-slate-300';
  }
};

const getAvatarClass = (tier) => {
  switch (tier) {
    case 'DIAMOND':
      return 'bg-gradient-to-tr from-indigo-600 via-purple-600 to-pink-500 text-white shadow-md ring-2 ring-indigo-300';
    case 'GOLD':
      return 'bg-gradient-to-tr from-amber-500 to-yellow-600 text-white shadow-sm ring-2 ring-amber-300';
    case 'SILVER':
      return 'bg-slate-700 text-white shadow-2xs';
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200';
  }
};

const getRankBadgeClass = (tier) => {
  switch (tier) {
    case 'DIAMOND':
      return 'bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 text-white border-purple-300';
    case 'GOLD':
      return 'bg-gradient-to-r from-amber-500 via-amber-600 to-yellow-500 text-white border-amber-300';
    case 'SILVER':
      return 'bg-slate-700 text-white border-slate-400';
    default:
      return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  }
};

const getRankIcon = (tier) => {
  switch (tier) {
    case 'DIAMOND': return '💎';
    case 'GOLD': return '👑';
    case 'SILVER': return '🥈';
    default: return '⭐';
  }
};

const getRankTitle = (tier) => {
  switch (tier) {
    case 'DIAMOND': return 'VIP KIM CƯƠNG';
    case 'GOLD': return 'VIP VÀNG';
    case 'SILVER': return 'THÂN THIẾT';
    default: return 'THÀNH VIÊN MỚI';
  }
};

const getPrivilegeBannerClass = (tier) => {
  switch (tier) {
    case 'DIAMOND':
      return 'bg-indigo-100/70 border-indigo-200 text-indigo-900';
    case 'GOLD':
      return 'bg-amber-100/70 border-amber-200 text-amber-900';
    case 'SILVER':
      return 'bg-slate-100 border-slate-200 text-slate-700';
    default:
      return 'bg-slate-50 border-slate-200 text-slate-600';
  }
};

const getPrivilegeText = (tier) => {
  switch (tier) {
    case 'DIAMOND':
      return 'Đặc quyền: Giảm 15% trọn đời • Ưu tiên chọn ghế đầu • Cọc 0đ';
    case 'GOLD':
      return 'Đặc quyền: Giảm 10% vé khứ hồi • Tích 1.2x điểm thưởng';
    case 'SILVER':
      return 'Đặc quyền: Giảm 5% vé đêm • Đổi voucher quà tặng';
    default:
      return 'Tích điểm chuyến đầu tiên để nâng lên hạng Thân Thiết Bạc';
  }
};

const getHistoryBtnClass = (tier) => {
  switch (tier) {
    case 'DIAMOND':
      return 'bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-700 hover:to-pink-700';
    case 'GOLD':
      return 'bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700';
    case 'SILVER':
      return 'bg-slate-700 hover:bg-slate-800';
    default:
      return 'bg-slate-800 hover:bg-slate-900';
  }
};
</script>
