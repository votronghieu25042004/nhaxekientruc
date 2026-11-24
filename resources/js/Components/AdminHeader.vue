<template>
  <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
    <!-- Top Bar: Logo, System info & Action buttons + Profile Switcher -->
    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2.5 sm:py-3 flex items-center justify-between gap-2 sm:gap-4">
      <!-- Left: Logo & Brand -->
      <div class="flex items-center space-x-2 sm:space-x-3.5 min-w-0">
        <Link href="/admin" class="w-8 h-8 sm:w-11 sm:h-11 rounded-xl bg-white border border-red-200 flex items-center justify-center p-1 sm:p-1.5 shadow-xs hover:border-red-500 transition duration-200 shrink-0">
          <img src="/images/logo.png" alt="Logo Trường ĐH Kiến Trúc Đà Nẵng" class="w-full h-full object-contain" />
        </Link>
        <div class="min-w-0">
          <div class="flex items-center space-x-1.5 sm:space-x-2">
            <span class="text-sm sm:text-xl font-black text-slate-900 tracking-tight truncate">nhaxekientruc</span>
            <span class="px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase tracking-wider shrink-0">
              ADMIN
            </span>
          </div>
          <p class="hidden sm:block text-[11px] text-slate-500 font-medium">Hệ thống điều hành và quản lý vận tải liên tỉnh</p>
        </div>
      </div>

      <!-- Right: Nút [+ Bán Vé Nhanh] + Profile & Role Switcher -->
      <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
        <!-- NÚT [ + BÁN VÉ NHANH ] NỔI BẬT Ở HEADER THEO YÊU CẦU -->
        <!-- CHUÔNG THÔNG BÁO ADMIN -->
        <NotificationDropdown role="admin" customButtonClass="bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200" />

        <Link 
          href="/admin/pos"  
          class="px-2.5 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-[#dc2626] hover:bg-red-700 text-white font-bold text-[11px] sm:text-xs flex items-center gap-1 sm:gap-1.5 transition-all active:scale-95 cursor-pointer whitespace-nowrap shadow-xs"
          title="Mở màn hình POS bán vé nhanh tại quầy và tổng đài"
        >
          <span class="text-xs sm:text-sm">⚡</span>
          <span class="hidden xs:inline">+ Bán Vé Nhanh</span>
          <span class="xs:hidden">+ Bán Vé</span>
        </Link>

        <!-- Nút Menu Mobile Cho Admin (< lg) -->
        <button
          type="button"
          @click="isMobileAdminOpen = true"
          class="lg:hidden w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition active:scale-95 cursor-pointer shrink-0 border border-slate-200"
          aria-label="Mở menu quản trị"
        >
          <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <!-- Right: Admin Profile Avatar & Role with Clickable Dropdown Menu -->
        <div class="relative" ref="dropdownRef">
          <button 
            @click="isDropdownOpen = !isDropdownOpen"
            class="flex items-center space-x-2.5 p-1 pr-2 rounded-xl hover:bg-slate-50 border border-slate-200 transition-all cursor-pointer group"
            title="Bấm để chuyển đổi cổng thông tin & xem phân quyền"
          >
            <div class="relative">
              <div class="w-9 h-9 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 font-bold text-sm">
                🛡️
              </div>
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 border border-white absolute -top-0.5 -right-0.5"></span>
            </div>
            <div class="text-left hidden sm:block">
              <div class="text-xs font-bold text-slate-900 leading-tight flex items-center gap-1">
                <span>Quản Trị Viên</span>
                <svg class="w-3 h-3 text-slate-400 group-hover:text-slate-700 transition-transform duration-200" :class="{ 'rotate-180': isDropdownOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
              <div class="text-[10px] text-slate-400 font-medium leading-tight">Super Admin</div>
            </div>
          </button>

          <!-- Dropdown Menu: Chuyển đổi giữa các vai trò & cổng thông tin -->
          <div 
            v-if="isDropdownOpen" 
            class="absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-slate-200 p-2 z-50 animate-in fade-in zoom-in-95 duration-150"
          >
            <div class="px-3 py-2 border-b border-slate-100">
              <p class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Chuyển Đổi Cổng Thông Tin</p>
              <p class="text-xs font-bold text-slate-800 mt-0.5">Chọn vai trò & cổng truy cập</p>
            </div>

            <div class="p-1 space-y-1 text-xs">
              <!-- 1. Quản Trị Viên Admin -->
              <Link 
                href="/admin" 
                @click="isDropdownOpen = false"
                class="flex items-center gap-2.5 p-2 rounded-xl bg-slate-100 text-slate-900 font-bold transition border border-slate-200"
              >
                <div class="w-7 h-7 rounded-lg bg-slate-900 text-white flex items-center justify-center text-xs">
                  🛡️
                </div>
                <div class="flex-1">
                  <div class="font-bold text-slate-900 flex items-center justify-between">
                    <span>Quản Trị Viên</span>
                    <span class="text-[9px] bg-red-600 text-white px-1.5 py-0.2 rounded font-bold">Admin</span>
                  </div>
                  <div class="text-[10px] text-slate-500 font-medium">Bảng điều khiển Super Admin</div>
                </div>
              </Link>

              <!-- 2. Quản Lý Phân Quyền (RBAC) -->
              <Link 
                href="/admin/roles" 
                @click="isDropdownOpen = false"
                class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-50 text-slate-700 font-medium transition"
              >
                <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-700 flex items-center justify-center text-xs border border-purple-200">
                  🔐
                </div>
                <div>
                  <div class="font-bold text-slate-900">Phân Quyền RBAC</div>
                  <div class="text-[10px] text-slate-400">Giám đốc, Kế toán, Điều hành, Bán vé</div>
                </div>
              </Link>

              <!-- 3. Trang Khách Hàng -->
              <Link 
                href="/" 
                @click="isDropdownOpen = false"
                class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-50 text-slate-700 font-medium transition"
              >
                <div class="w-7 h-7 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                  🌐
                </div>
                <div>
                  <div class="font-bold text-slate-900">Trang Khách Đặt Vé</div>
                  <div class="text-[10px] text-slate-400">Giao diện người dùng công khai</div>
                </div>
              </Link>

              <!-- 4. Cổng Bác Tài -->
              <Link 
                href="/driver" 
                @click="isDropdownOpen = false"
                class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-50 text-slate-700 font-medium transition"
              >
                <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center text-xs border border-blue-200">
                  👨‍✈️
                </div>
                <div>
                  <div class="font-bold text-slate-900">Cổng Bác Tài (Tài Xế)</div>
                  <div class="text-[10px] text-slate-400">Giao diện di động nhận chuyến & SOS</div>
                </div>
              </Link>

              <!-- 5. Cổng Lơ Xe -->
              <Link 
                href="/conductor" 
                @click="isDropdownOpen = false"
                class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-slate-50 text-slate-700 font-medium transition"
              >
                <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs border border-emerald-200">
                  🎫
                </div>
                <div>
                  <div class="font-bold text-slate-900">Cổng Lơ Xe (Phụ Xe)</div>
                  <div class="text-[10px] text-slate-400">Soát vé QR, thu tiền mặt ca trực</div>
                </div>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Nav Tabs Bar: Nhóm danh mục thông minh, KHÔNG CUỘN NGANG, hiển thị gọn gàng cao cấp -->
    <div class="border-t border-slate-200 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
        <!-- DESKTOP NAV (>= lg) -->
        <nav class="hidden lg:flex w-full items-center justify-between gap-2 text-xs font-bold">
          <div class="flex items-center gap-1 sm:gap-2 flex-wrap">
            <!-- 1. Tổng quan -->
            <Link 
              href="/admin" 
              :class="[
                'px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer shadow-2xs',
                $page.url === '/admin'
                  ? 'bg-slate-900 text-white font-black'
                  : 'text-slate-700 hover:text-slate-950 hover:bg-slate-100'
              ]"
            >
              <span>📊</span>
              <span>Tổng quan</span>
            </Link>

            <!-- 2. Vận Hành & Lịch Trình (Dropdown) -->
            <div class="relative group">
              <button
                type="button"
                :class="[
                  'px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer shadow-2xs',
                  ($page.url.startsWith('/admin/pos') || $page.url.startsWith('/admin/tracking') || $page.url.startsWith('/admin/trips') || $page.url.startsWith('/admin/routes'))
                    ? 'bg-red-50 text-red-700 border border-red-300 font-black'
                    : 'text-slate-700 hover:text-slate-950 hover:bg-slate-100'
                ]"
              >
                <span>⚡</span>
                <span>Vận Hành & Lịch Trình</span>
                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <div class="hidden group-hover:block hover:block absolute left-0 top-full pt-1.5 z-50 w-56 animate-in fade-in duration-100">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-1.5 space-y-1">
                  <Link
                    href="/admin/pos"
                    class="flex items-center justify-between p-2 rounded-xl hover:bg-red-50 text-slate-700 hover:text-red-700 transition"
                    :class="$page.url.startsWith('/admin/pos') ? 'bg-red-50 text-red-700 font-black' : ''"
                  >
                    <div class="flex items-center gap-2">
                      <span>⚡</span>
                      <span>POS Bán Vé</span>
                    </div>
                    <span class="px-1.5 py-0.2 rounded text-[9px] bg-red-600 text-white font-black">Mới</span>
                  </Link>

                  <Link
                    href="/admin/tracking"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-blue-50 text-slate-700 hover:text-blue-700 transition"
                    :class="$page.url.startsWith('/admin/tracking') ? 'bg-blue-50 text-blue-700 font-black' : ''"
                  >
                    <span>📍</span>
                    <span>Live Tracking GPS</span>
                  </Link>

                  <Link
                    href="/admin/trips"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-950 transition"
                    :class="$page.url.startsWith('/admin/trips') ? 'bg-slate-100 text-slate-950 font-black' : ''"
                  >
                    <span>🗓️</span>
                    <span>Lịch Trình Chuyến</span>
                  </Link>

                  <Link
                    href="/admin/routes"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-950 transition"
                    :class="$page.url.startsWith('/admin/routes') ? 'bg-slate-100 text-slate-950 font-black' : ''"
                  >
                    <span>🛣️</span>
                    <span>Tuyến Đường Cố Định</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- 3. Phương Tiện & Nhân Sự (Dropdown) -->
            <div class="relative group">
              <button
                type="button"
                :class="[
                  'px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer shadow-2xs',
                  ($page.url.startsWith('/admin/buses') || $page.url.startsWith('/admin/fleet-inventory') || $page.url.startsWith('/admin/drivers'))
                    ? 'bg-amber-50 text-amber-900 border border-amber-300 font-black'
                    : 'text-slate-700 hover:text-slate-950 hover:bg-slate-100'
                ]"
              >
                <span>🚌</span>
                <span>Phương Tiện & Nhân Sự</span>
                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <div class="hidden group-hover:block hover:block absolute left-0 top-full pt-1.5 z-50 w-56 animate-in fade-in duration-100">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-1.5 space-y-1">
                  <Link
                    href="/admin/buses"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-950 transition"
                    :class="$page.url.startsWith('/admin/buses') ? 'bg-slate-100 text-slate-950 font-black' : ''"
                  >
                    <span>🚌</span>
                    <span>Quản Lý Đội Xe</span>
                  </Link>

                  <Link
                    href="/admin/fleet-inventory"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-amber-50 text-slate-700 hover:text-amber-800 transition"
                    :class="$page.url.startsWith('/admin/fleet-inventory') ? 'bg-amber-50 text-amber-800 font-black' : ''"
                  >
                    <span>🔧</span>
                    <span>Vật Tư & Chi Phí Xe</span>
                  </Link>

                  <Link
                    href="/admin/drivers"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-blue-50 text-slate-700 hover:text-blue-700 transition"
                    :class="$page.url.startsWith('/admin/drivers') ? 'bg-blue-50 text-blue-700 font-black' : ''"
                  >
                    <span>👥</span>
                    <span>Tài Xế & Lơ Xe</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- 4. Khách Hàng & Giá Vé (Dropdown) -->
            <div class="relative group">
              <button
                type="button"
                :class="[
                  'px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer shadow-2xs',
                  ($page.url.startsWith('/admin/customers') || $page.url.startsWith('/admin/pricing') || $page.url.startsWith('/admin/vouchers') || $page.url.startsWith('/admin/refunds'))
                    ? 'bg-purple-50 text-purple-900 border border-purple-300 font-black'
                    : 'text-slate-700 hover:text-slate-950 hover:bg-slate-100'
                ]"
              >
                <span>👤</span>
                <span>Khách Hàng & Giá Vé</span>
                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <div class="hidden group-hover:block hover:block absolute left-0 top-full pt-1.5 z-50 w-60 animate-in fade-in duration-100">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-1.5 space-y-1">
                  <Link
                    href="/admin/customers"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-purple-50 text-slate-700 hover:text-purple-700 transition"
                    :class="$page.url.startsWith('/admin/customers') ? 'bg-purple-50 text-purple-700 font-black' : ''"
                  >
                    <span>👤</span>
                    <span>Hành Khách CRM</span>
                  </Link>

                  <Link
                    href="/admin/vouchers"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-red-50 text-slate-700 hover:text-red-700 transition"
                    :class="$page.url.startsWith('/admin/vouchers') ? 'bg-red-50 text-red-700 font-black' : ''"
                  >
                    <span>🎟️</span>
                    <span class="font-bold">Quản Lý Voucher (SV / Cá Nhân)</span>
                  </Link>

                  <Link
                    href="/admin/pricing"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 transition"
                    :class="$page.url.startsWith('/admin/pricing') && !$page.url.startsWith('/admin/vouchers') ? 'bg-emerald-50 text-emerald-700 font-black' : ''"
                  >
                    <span>🏷️</span>
                    <span>Quy Tắc Giá & Linh Hoạt</span>
                  </Link>

                  <Link
                    href="/admin/refunds"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-rose-50 text-slate-700 hover:text-rose-700 transition"
                    :class="$page.url.startsWith('/admin/refunds') ? 'bg-rose-50 text-rose-700 font-black' : ''"
                  >
                    <span>💸</span>
                    <span>Hoàn Tiền & Hủy Vé</span>
                  </Link>
                </div>
              </div>
            </div>

            <!-- 5. Quản lý Doanh thu & Tiền Xe -->
            <Link 
              href="/admin/finance" 
              :class="[
                'px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer shadow-2xs',
                $page.url.startsWith('/admin/finance')
                  ? 'bg-emerald-600 text-white font-black'
                  : 'text-slate-700 hover:text-emerald-700 hover:bg-emerald-50'
              ]"
            >
              <span>💰</span>
              <span>Quản Lý Doanh Thu & Tiền Xe</span>
            </Link>

            <!-- 6. Phân Quyền & Hệ Thống (Dropdown) -->
            <div class="relative group">
              <button
                type="button"
                :class="[
                  'px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer shadow-2xs',
                  ($page.url.startsWith('/admin/roles') || $page.url === '/admin/users' || $page.url.startsWith('/admin/logs'))
                    ? 'bg-indigo-50 text-indigo-900 border border-indigo-300 font-black'
                    : 'text-slate-700 hover:text-slate-950 hover:bg-slate-100'
                ]"
              >
                <span>🔐</span>
                <span>Hệ Thống</span>
                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              <div class="hidden group-hover:block hover:block absolute right-0 top-full pt-1.5 z-50 w-52 animate-in fade-in duration-100">
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-1.5 space-y-1">
                  <Link
                    href="/admin/roles"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 transition"
                    :class="$page.url.startsWith('/admin/roles') ? 'bg-indigo-50 text-indigo-700 font-black' : ''"
                  >
                    <span>🔐</span>
                    <span>Phân Quyền RBAC</span>
                  </Link>

                  <Link
                    href="/admin/logs"
                    class="flex items-center gap-2 p-2 rounded-xl hover:bg-slate-100 text-slate-700 hover:text-slate-950 transition"
                    :class="$page.url.startsWith('/admin/logs') ? 'bg-slate-100 text-slate-950 font-black' : ''"
                  >
                    <span>📜</span>
                    <span>Nhật Ký Audit Logs</span>
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </nav>

        <!-- MOBILE HORIZONTAL FAST NAV (< lg) -->
        <nav class="lg:hidden flex items-center gap-1.5 overflow-x-auto scrollbar-none py-1 px-1 -mx-1 text-xs font-bold select-none touch-pan-x">
          <Link
            href="/admin"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition flex items-center gap-1 shrink-0"
            :class="$page.url === '/admin' ? 'bg-slate-900 text-white font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
          >
            <span>📊</span>
            <span>Tổng quan</span>
          </Link>
          <Link
            href="/admin/pos"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition flex items-center gap-1 shrink-0"
            :class="$page.url.startsWith('/admin/pos') ? 'bg-red-600 text-white font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
          >
            <span>⚡</span>
            <span>POS Bán Vé</span>
          </Link>
          <Link
            href="/admin/trips"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition flex items-center gap-1 shrink-0"
            :class="$page.url.startsWith('/admin/trips') ? 'bg-slate-900 text-white font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
          >
            <span>🗓️</span>
            <span>Chuyến Xe</span>
          </Link>
          <Link
            href="/admin/tracking"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition flex items-center gap-1 shrink-0"
            :class="$page.url.startsWith('/admin/tracking') ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
          >
            <span>📍</span>
            <span>GPS</span>
          </Link>
          <Link
            href="/admin/buses"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition flex items-center gap-1 shrink-0"
            :class="$page.url.startsWith('/admin/buses') ? 'bg-slate-900 text-white font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
          >
            <span>🚌</span>
            <span>Đội Xe</span>
          </Link>
          <Link
            href="/admin/finance"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition flex items-center gap-1 shrink-0"
            :class="$page.url.startsWith('/admin/finance') ? 'bg-emerald-600 text-white font-black shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
          >
            <span>💰</span>
            <span>Doanh Thu</span>
          </Link>
          <button
            type="button"
            @click="isMobileAdminOpen = true"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap bg-red-50 text-red-700 border border-red-200 font-black transition flex items-center gap-1 shrink-0 cursor-pointer active:scale-95"
          >
            <span>☰</span>
            <span>Tất Cả (15)</span>
          </button>
        </nav>
      </div>
    </div>

    <!-- MOBILE ADMIN DRAWER (Slide-over cho điện thoại) -->
    <Teleport to="body">
      <div v-if="isMobileAdminOpen" class="fixed inset-0 z-[9999] overflow-hidden">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-200" @click="isMobileAdminOpen = false"></div>
        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
          <div class="w-screen max-w-xs sm:max-w-sm bg-white shadow-2xl flex flex-col justify-between overflow-y-auto animate-in slide-in-from-right duration-250">
            <!-- Header Drawer -->
            <div class="p-4 bg-gradient-to-r from-slate-900 via-slate-800 to-red-900 text-white flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-red-600 flex items-center justify-center text-lg shadow-sm">🛡️</div>
                <div>
                  <h3 class="font-black text-sm uppercase leading-none">ADMIN CONTROL</h3>
                  <p class="text-[10px] text-slate-300 mt-0.5">nhaxekientruc Đà Nẵng</p>
                </div>
              </div>
              <button @click="isMobileAdminOpen = false" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center font-bold text-base cursor-pointer">✕</button>
            </div>

            <!-- Body Links -->
            <div class="p-3.5 space-y-3.5 flex-1 text-xs">
              <!-- 1. Vận Hành -->
              <div>
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider px-2 mb-1.5">⚡ Vận Hành & Lịch Trình</p>
                <div class="space-y-1">
                  <Link href="/admin" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url === '/admin' ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">📊</span><span>Tổng Quan Dashboard</span>
                  </Link>
                  <Link href="/admin/pos" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/pos') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">⚡</span><span>POS Bán Vé Quầy</span>
                  </Link>
                  <Link href="/admin/tracking" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/tracking') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">📍</span><span>Live GPS Tracking</span>
                  </Link>
                  <Link href="/admin/trips" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/trips') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">🗓️</span><span>Lịch Trình Chuyến Xe</span>
                  </Link>
                  <Link href="/admin/routes" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/routes') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">🛣️</span><span>Tuyến Đường Cố Định</span>
                  </Link>
                </div>
              </div>

              <!-- 2. Phương tiện & Nhân sự -->
              <div class="pt-2 border-t border-slate-100">
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider px-2 mb-1.5">🚌 Phương Tiện & Nhân Sự</p>
                <div class="space-y-1">
                  <Link href="/admin/buses" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/buses') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">🚌</span><span>Quản Lý Đội Xe</span>
                  </Link>
                  <Link href="/admin/fleet-inventory" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/fleet-inventory') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">🔧</span><span>Vật Tư & Chi Phí Xe</span>
                  </Link>
                  <Link href="/admin/drivers" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/drivers') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">👥</span><span>Tài Xế & Lơ Xe</span>
                  </Link>
                </div>
              </div>

              <!-- 3. Khách hàng & Giá vé -->
              <div class="pt-2 border-t border-slate-100">
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider px-2 mb-1.5">👤 Khách Hàng & Doanh Thu</p>
                <div class="space-y-1">
                  <Link href="/admin/customers" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/customers') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">👤</span><span>Hành Khách CRM</span>
                  </Link>
                  <Link href="/admin/vouchers" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/vouchers') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">🎟️</span><span>Quản Lý Voucher (SV / Ưu Đãi)</span>
                  </Link>
                  <Link href="/admin/pricing" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/pricing') && !$page.url.startsWith('/admin/vouchers') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">🏷️</span><span>Quy Tắc Giá Vé</span>
                  </Link>
                  <Link href="/admin/refunds" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/refunds') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">💸</span><span>Hoàn Tiền & Hủy Vé</span>
                  </Link>
                  <Link href="/admin/invoices"
            class="px-3 sm:px-3.5 py-1.5 sm:py-2 rounded-xl text-xs sm:text-sm font-bold transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
            :class="$page.url.startsWith('/admin/invoices') ? 'bg-[#dc2626] text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
          >
            <span>📑</span>
            <span>Kho Hóa Đơn & PDF</span>
          </Link>

          <Link href="/admin/finance"  @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/finance') ? 'bg-emerald-50 text-emerald-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">💰</span><span>Quản Lý Doanh Thu & Tiền Xe</span>
                  </Link>
                  <Link href="/admin/roles" @click="isMobileAdminOpen = false" class="flex items-center gap-3 px-3 py-2 rounded-xl transition" :class="$page.url.startsWith('/admin/roles') ? 'bg-red-50 text-red-700 font-black' : 'text-slate-700 hover:bg-slate-50 font-bold'">
                    <span class="text-base">🔐</span><span>Phân Quyền RBAC</span>
                  </Link>
                </div>
              </div>

              <!-- 4. Chuyển Cổng Vai Trò Nhanh -->
              <div class="pt-2 border-t border-slate-100">
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider px-2 mb-1.5">🌐 Cổng Trải Nghiệm Khác</p>
                <div class="grid grid-cols-2 gap-2 px-1">
                  <Link href="/" @click="isMobileAdminOpen = false" class="p-2 rounded-xl bg-slate-100 text-slate-800 font-bold text-center hover:bg-slate-200">Trang Khách</Link>
                  <Link href="/driver" @click="isMobileAdminOpen = false" class="p-2 rounded-xl bg-amber-50 text-amber-900 font-bold text-center hover:bg-amber-100">Cổng Bác Tài</Link>
                  <Link href="/conductor" @click="isMobileAdminOpen = false" class="p-2 rounded-xl bg-blue-50 text-blue-900 font-bold text-center hover:bg-blue-100">Cổng Lơ Xe</Link>
                  <Link href="/scanner" @click="isMobileAdminOpen = false" class="p-2 rounded-xl bg-emerald-50 text-emerald-900 font-bold text-center hover:bg-emerald-100">Soát Vé QR</Link>
                </div>
              </div>
            </div>

            <!-- Footer Drawer -->
            <div class="p-3 bg-slate-50 border-t border-slate-200 text-center text-[10px] text-slate-400">
              Admin Mobile Dashboard • nhaxekientruc Đà Nẵng
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
  </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AiChatModal from './AiChatModal.vue';

defineProps({
  title: {
    type: String,
    default: 'nhaxekientruc Terminal Management'
  },
  subtitle: {
    type: String,
    default: 'Quản trị điều hành, lịch trình, đội xe và doanh thu'
  }
});

const isDropdownOpen = ref(false);
const isMobileAdminOpen = ref(false);
const dropdownRef = ref(null);

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isDropdownOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
