<template>
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-2xs transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="flex items-center justify-between h-16 gap-4">
        
        <!-- Logo Brand nhaxekientruc -->
        <Link href="/" class="flex items-center gap-2.5 group flex-shrink-0">
          <div class="w-10 h-10 rounded-xl bg-white border border-red-200/90 flex items-center justify-center p-1 shadow-xs group-hover:scale-105 transition-all">
            <img src="/images/logo.png" alt="Logo Trường ĐH Kiến Trúc Đà Nẵng" class="w-full h-full object-contain" />
          </div>
          <div>
            <span class="text-xl font-black tracking-tight text-red-600 block leading-none">
              nhaxekientruc
            </span>
            <span class="text-[9.5px] text-slate-400 font-bold block tracking-wider uppercase mt-1">ĐH KIẾN TRÚC ĐÀ NẴNG</span>
          </div>
        </Link>

        <!-- Menu Điều Hướng Chuẩn (Trang Chủ, Tìm Chuyến, Lịch Trình, Quản Lý Vé) -->
        <nav class="hidden md:flex items-center gap-8 text-[15px] font-semibold text-slate-700">
          <Link
            href="/"
            class="transition-all py-1"
            :class="$page.url === '/' ? 'text-red-600 font-bold border-b-2 border-red-600 pb-1' : 'hover:text-red-600 text-slate-600'"
          >
            Trang Chủ
          </Link>
          <Link
            href="/trips"
            class="transition-all py-1"
            :class="$page.url.startsWith('/trips') ? 'text-red-600 font-bold border-b-2 border-red-600 pb-1' : 'hover:text-red-600 text-slate-600'"
          >
            Tìm Chuyến
          </Link>
          <Link
            href="/schedules"
            class="transition-all py-1"
            :class="$page.url.startsWith('/schedules') ? 'text-red-600 font-bold border-b-2 border-red-600 pb-1' : 'hover:text-red-600 text-slate-600'"
          >
            Lịch Trình
          </Link>
          <Link
            href="/my-bookings"
            class="transition-all py-1"
            :class="$page.url.startsWith('/my-bookings') ? 'text-red-600 font-bold border-b-2 border-red-600 pb-1' : 'hover:text-red-600 text-slate-600'"
          >
            Quản Lý Vé
          </Link>
        </nav>

        <!-- Cổng Đăng Nhập & Ngôn Ngữ Theo Mẫu: VN | [👤] Đăng nhập -->
        <div class="flex items-center gap-2 sm:gap-3">
          
          <!-- Nút Ngôn Ngữ: VN / EN (Desktop/Tablet) -->
          <button
            type="button"
            @click="showLanguageModal = true"
            class="hidden sm:inline-block text-xs font-bold text-slate-600 hover:text-red-600 transition cursor-pointer px-1.5 py-1 tracking-wide"
            title="Ngôn ngữ"
          >
            {{ currentLang === 'en' ? 'EN' : 'VN' }}
          </button>

          <!-- Dấu gạch phân cách -->
          <span class="hidden sm:inline-block text-slate-300 select-none">|</span>

          <!-- Dropdown Phân Quyền & Đăng Nhập OTP (Desktop/Tablet) -->
          <div class="hidden sm:block relative" @mouseenter="openRoleDropdown" @mouseleave="closeRoleDropdown">
            <button
              @click="isRoleOpen = !isRoleOpen"
              class="flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-700 hover:text-red-600 transition cursor-pointer py-1.5 px-2 rounded-lg hover:bg-slate-50"
            >
              <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-300 flex items-center justify-center text-slate-600 text-xs">
                👤
              </div>
              <span v-if="$page.props.auth?.user" class="flex items-center gap-1.5">
                <span class="font-bold text-slate-900 truncate max-w-[110px]">{{ $page.props.auth.user.name }}</span>
                <span class="hidden sm:inline-block px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-mono font-bold">
                  {{ ($page.props.auth.user.reward_points || 0).toLocaleString('vi-VN') }}đ
                </span>
              </span>
              <span v-else class="font-medium text-slate-700">Đăng nhập</span>
              <svg class="w-3.5 h-3.5 text-slate-400 transition-transform" :class="isRoleOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Menu thả xuống -->
            <div
              v-if="isRoleOpen"
              class="absolute right-0 top-full mt-1.5 w-72 bg-white rounded-3xl shadow-2xl border border-slate-200/80 py-2.5 z-50 animate-in fade-in slide-in-from-top-2 duration-150 before:content-[''] before:absolute before:-top-3 before:inset-x-0 before:h-3"
            >
              <!-- 1. KHI ĐÃ ĐĂNG NHẬP -->
              <div v-if="$page.props.auth?.user">
                <!-- Thông tin người dùng đã đăng nhập -->
                <div class="p-3 mx-2.5 mb-2 bg-gradient-to-br from-amber-50/70 via-orange-50/40 to-slate-50 rounded-2xl border border-amber-200/70 space-y-1.5 shadow-xs">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-slate-900 truncate max-w-[150px]">{{ $page.props.auth.user.name }}</span>
                    <span 
                      class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase text-white shadow-2xs"
                      :class="$page.props.auth.user.loyalty_tier === 'DIAMOND' ? 'bg-gradient-to-r from-purple-600 to-indigo-600' : ($page.props.auth.user.loyalty_tier === 'GOLD' ? 'bg-amber-500' : ($page.props.auth.user.loyalty_tier === 'SILVER' ? 'bg-slate-700' : 'bg-emerald-600'))"
                    >
                      {{ $page.props.auth.user.loyalty_tier === 'DIAMOND' ? '💎 Kim Cương' : ($page.props.auth.user.loyalty_tier === 'GOLD' ? '👑 VIP Vàng' : ($page.props.auth.user.loyalty_tier === 'SILVER' ? '🥈 Bạc' : '⭐ Thành Viên')) }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between text-xs pt-1 border-t border-amber-200/50">
                    <span class="text-slate-500 text-[11px]">Điểm tích lũy:</span>
                    <strong class="text-amber-700 font-mono font-black text-sm">
                      🪙 {{ ($page.props.auth.user.reward_points || 0).toLocaleString('vi-VN') }} Điểm
                    </strong>
                  </div>
                  <Link
                    href="/my-bookings"
                    @click="isRoleOpen = false"
                    class="mt-1 block text-center py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition shadow-xs"
                  >
                    🎫 Xem Vé Của Tôi & Điểm Thưởng
                  </Link>
                </div>

                <!-- TÙY CHỌN TÀI KHOẢN KHÁCH HÀNG -->
                <div class="px-4 py-1 text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                  Hồ Sơ & Bảo Mật
                </div>

                <div class="py-1">
                  <!-- Mở Modal Hồ Sơ Khách Hàng -->
                  <button
                    type="button"
                    @click.prevent.stop="openProfile('info')"
                    class="w-full text-left flex items-center gap-3 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors cursor-pointer"
                  >
                    <span class="text-base">👤</span>
                    <div>
                      <p class="leading-none">Hồ Sơ Khách Hàng</p>
                      <p class="text-[10px] text-slate-400 font-normal mt-0.5">Xem & chỉnh sửa họ tên</p>
                    </div>
                  </button>

                  <!-- Mở Modal Cập Nhật Gmail / SĐT -->
                  <button
                    type="button"
                    @click.prevent.stop="openProfile('contact')"
                    class="w-full text-left flex items-center gap-3 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors cursor-pointer"
                  >
                    <span class="text-base">✉️</span>
                    <div>
                      <p class="leading-none flex items-center gap-1.5">
                        <span v-if="!$page.props.auth.user.email && $page.props.auth.user.phone">Bổ Sung Gmail Nhận Vé</span>
                        <span v-else-if="$page.props.auth.user.email && !$page.props.auth.user.phone">Bổ Sung Số Điện Thoại</span>
                        <span v-else-if="!$page.props.auth.user.email && !$page.props.auth.user.phone">Bổ Sung Gmail / SĐT</span>
                        <span v-else>Cập Nhật Gmail & SĐT</span>
                        <span v-if="!$page.props.auth.user.email || !$page.props.auth.user.phone" class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        <span v-else class="text-[10px] text-emerald-600 font-bold">✓</span>
                      </p>
                      <p class="text-[10px] text-slate-400 font-normal mt-0.5">
                        <span v-if="!$page.props.auth.user.email && $page.props.auth.user.phone">Thêm Gmail để nhận vé điện tử & hóa đơn</span>
                        <span v-else-if="$page.props.auth.user.email && !$page.props.auth.user.phone">Thêm SĐT nhận tin nhắn Zalo khi xe đón</span>
                        <span v-else-if="!$page.props.auth.user.email && !$page.props.auth.user.phone">Cập nhật thông tin nhận vé</span>
                        <span v-else class="text-emerald-700 font-medium">✓ Đã xác thực đầy đủ Gmail & SĐT</span>
                      </p>
                    </div>
                  </button>

                  <!-- Mở Modal Đổi Mật Khẩu -->
                  <button
                    type="button"
                    @click.prevent.stop="openProfile('password')"
                    class="w-full text-left flex items-center gap-3 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors cursor-pointer"
                  >
                    <span class="text-base">🔒</span>
                    <div>
                      <p class="leading-none">Đổi Mật Khẩu</p>
                      <p class="text-[10px] text-slate-400 font-normal mt-0.5">Mật khẩu mới tối thiểu 8 ký tự</p>
                    </div>
                  </button>

                  <!-- Đăng Xuất -->
                  <button
                    type="button"
                    @click="handleLogout"
                    class="w-full text-left flex items-center gap-3 px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 transition-colors cursor-pointer border-t border-slate-100 mt-1 pt-2"
                  >
                    <span class="text-base">🚪</span>
                    <span>Đăng Xuất Tài Khoản</span>
                  </button>
                </div>

                <!-- CHỈ DÀNH CHO ADMIN: LIÊN KẾT NHANH -->
                <div v-if="$page.props.auth.user.role === 'admin'" class="border-t border-slate-100 mt-1 pt-1">
                  <div class="px-4 py-1 text-[10px] font-black text-amber-600 uppercase tracking-wider bg-amber-50/60 flex items-center gap-1">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Khu Vực Quản Trị</span>
                  </div>
                  <Link
                    href="/admin"
                    class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors"
                  >
                    <span>👔</span>
                    <span class="text-xs">Trang Quản Trị (Admin)</span>
                  </Link>
                  <Link
                    href="/scanner"
                    class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-indigo-600 hover:bg-indigo-50 transition-colors"
                  >
                    <span>📷</span>
                    <span class="text-xs">Quét Mã QR Vé</span>
                  </Link>
                </div>
              </div>

              <!-- 2. KHI CHƯA ĐĂNG NHẬP (KHÁCH VÃNG LAI) -->
              <div v-else class="py-1">
                <div class="px-4 py-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                  Tài Khoản
                </div>
                
                <!-- Đăng nhập -->
                <button
                  type="button"
                  @click="isRoleOpen = false; showAuthModal = true"
                  class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-red-600 hover:bg-red-50 transition-colors cursor-pointer"
                >
                  <span class="text-base">🔐</span>
                  <div>
                    <p class="leading-none">Đăng Nhập</p>
                    <p class="text-[10px] text-slate-400 font-normal mt-0.5">Đăng nhập bằng Email / SĐT & Mật khẩu</p>
                  </div>
                </button>

                <!-- Đăng ký tài khoản mới -->
                <Link
                  href="/register"
                  @click="isRoleOpen = false"
                  class="flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors"
                >
                  <span class="text-base">📝</span>
                  <div>
                    <p class="leading-none">Đăng Ký Tài Khoản</p>
                    <p class="text-[10px] text-slate-400 font-normal mt-0.5">Xác thực OTP qua Gmail hoặc Zalo</p>
                  </div>
                </Link>
              </div>
            </div>
          </div>

          <Link
            href="/trips"
            class="px-3 sm:px-5 py-2 sm:py-2.5 bg-[#dc2626] hover:bg-[#b91c1c] text-white text-xs sm:text-sm font-black rounded-xl shadow-md shadow-red-500/25 transition-all cursor-pointer flex items-center gap-1.5 active:scale-95 shrink-0"
          >
            <span>🚌</span>
            <span class="hidden xs:inline">Đặt Vé Ngay</span>
            <span class="xs:hidden">Đặt Vé</span>
          </Link>

          <!-- Nút Hamburger Menu Mobile (< md) -->
          <button
            type="button"
            @click="isMobileMenuOpen = true"
            class="md:hidden w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-lg transition active:scale-95 cursor-pointer shrink-0"
            aria-label="Mở Menu Mobile"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- MOBILE NAVIGATION DRAWER (Slide-over) -->
    <Teleport to="body">
      <div v-if="isMobileMenuOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div 
          class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-200"
          @click="isMobileMenuOpen = false"
        ></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
          <div class="w-screen max-w-xs bg-white shadow-2xl flex flex-col justify-between overflow-y-auto animate-in slide-in-from-right duration-250">
            <!-- Header -->
            <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-red-600 to-rose-600 text-white">
              <div class="flex items-center gap-2">
                <span class="text-2xl">🚌</span>
                <div>
                  <h3 class="font-black text-base leading-none">nhaxekientruc</h3>
                  <span class="text-[9px] text-amber-200 font-bold uppercase tracking-wider block mt-0.5">ĐẠI HỌC KIẾN TRÚC ĐÀ NẴNG</span>
                </div>
              </div>
              <button 
                @click="isMobileMenuOpen = false" 
                class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center font-bold text-base transition cursor-pointer"
              >
                ✕
              </button>
            </div>

            <!-- User Status Card if Logged In -->
            <div v-if="$page.props.auth?.user" class="p-3.5 mx-3 mt-3 bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 rounded-2xl space-y-1.5 shadow-xs">
              <div class="flex items-center justify-between">
                <span class="font-black text-xs text-slate-900 truncate max-w-[140px]">{{ $page.props.auth.user.name }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-red-600 text-white">
                  {{ $page.props.auth.user.role }}
                </span>
              </div>
              <div class="flex justify-between text-xs text-slate-600">
                <span>Điểm tích lũy:</span>
                <strong class="text-amber-700 font-mono font-black">{{ ($page.props.auth.user.reward_points || 0).toLocaleString('vi-VN') }}đ</strong>
              </div>
            </div>

            <!-- Navigation Links -->
            <div class="p-3 space-y-1 flex-1">
              <div class="text-[10px] font-black uppercase text-slate-400 px-3 py-1 tracking-wider">
                Menu Điều Hướng
              </div>
              <Link 
                href="/" 
                @click="isMobileMenuOpen = false" 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition"
                :class="$page.url === '/' ? 'bg-red-50 text-red-600' : 'text-slate-700 hover:bg-slate-50'"
              >
                <span class="text-base">🏠</span>
                <span>Trang Chủ</span>
              </Link>
              <Link 
                href="/trips" 
                @click="isMobileMenuOpen = false" 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition"
                :class="$page.url.startsWith('/trips') ? 'bg-red-50 text-red-600' : 'text-slate-700 hover:bg-slate-50'"
              >
                <span class="text-base">🔍</span>
                <span>Tìm Chuyến Xe</span>
              </Link>
              <Link 
                href="/schedules" 
                @click="isMobileMenuOpen = false" 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition"
                :class="$page.url.startsWith('/schedules') ? 'bg-red-50 text-red-600' : 'text-slate-700 hover:bg-slate-50'"
              >
                <span class="text-base">🗓️</span>
                <span>Lịch Trình Tuyến</span>
              </Link>
              <Link 
                href="/my-bookings" 
                @click="isMobileMenuOpen = false" 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition"
                :class="$page.url.startsWith('/my-bookings') ? 'bg-red-50 text-red-600' : 'text-slate-700 hover:bg-slate-50'"
              >
                <span class="text-base">🎫</span>
                <span>Quản Lý Vé & Điểm</span>
              </Link>
              <Link 
                href="/scanner" 
                @click="isMobileMenuOpen = false" 
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50"
              >
                <span class="text-base">📷</span>
                <span>Soát Vé Mã QR</span>
              </Link>

              <!-- Khu vực Nghiệp vụ -->
              <div class="pt-2.5 border-t border-slate-100">
                <div class="text-[10px] font-black uppercase text-slate-400 px-3 py-1 tracking-wider">
                  Cổng Nghiệp Vụ
                </div>
                <Link 
                  href="/driver" 
                  @click="isMobileMenuOpen = false" 
                  class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-amber-50"
                >
                  <span class="text-base">👨‍✈️</span>
                  <span>Cổng Bác Tài (Lái Xe)</span>
                </Link>
                <Link 
                  href="/conductor" 
                  @click="isMobileMenuOpen = false" 
                  class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-blue-50"
                >
                  <span class="text-base">🎫</span>
                  <span>Cổng Lơ Xe (Tiếp Viên)</span>
                </Link>
                <Link 
                  v-if="$page.props.auth?.user?.role === 'admin'"
                  href="/admin" 
                  @click="isMobileMenuOpen = false" 
                  class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-purple-700 hover:bg-purple-50"
                >
                  <span class="text-base">👑</span>
                  <span>Trang Quản Trị (Admin)</span>
                </Link>
              </div>

              <!-- Auth Buttons on Mobile -->
              <div class="pt-2.5 border-t border-slate-100">
                <div v-if="!$page.props.auth?.user" class="space-y-2 pt-1">
                  <Link
                    href="/login"
                    @click="isMobileMenuOpen = false"
                    class="w-full py-2.5 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs flex items-center justify-center gap-2 shadow-sm transition"
                  >
                    <span>🔐 Đăng Nhập</span>
                  </Link>
                  <Link
                    href="/register"
                    @click="isMobileMenuOpen = false"
                    class="w-full py-2.5 px-4 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs flex items-center justify-center gap-2 hover:bg-slate-50 transition"
                  >
                    <span>📝 Đăng Ký Tài Khoản</span>
                  </Link>
                </div>
                <div v-else class="space-y-1">
                  <button
                    type="button"
                    @click="isMobileMenuOpen = false; openProfile('info')"
                    class="w-full text-left flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50"
                  >
                    <span>👤</span>
                    <span>Hồ Sơ Cá Nhân</span>
                  </button>
                  <button
                    type="button"
                    @click="isMobileMenuOpen = false; openProfile('password')"
                    class="w-full text-left flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50"
                  >
                    <span>🔒</span>
                    <span>Đổi Mật Khẩu</span>
                  </button>
                  <button
                    type="button"
                    @click="isMobileMenuOpen = false; handleLogout()"
                    class="w-full text-left flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold text-red-600 hover:bg-red-50"
                  >
                    <span>🚪</span>
                    <span>Đăng Xuất</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Footer Drawer -->
            <div class="p-3.5 bg-slate-50 border-t border-slate-200 text-center space-y-1">
              <div class="flex justify-center gap-2.5 text-xs font-bold text-slate-700">
                <a href="tel:19006067" class="text-red-600 hover:underline">📞 1900 6067</a>
                <span>•</span>
                <a href="tel:0905123456" class="text-slate-600 hover:underline">0905 123 456</a>
              </div>
              <p class="text-[10px] text-slate-400">© 2026 nhaxekientruc Đà Nẵng</p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modals -->
    <LanguageModal v-model="showLanguageModal" @change="onLanguageChange" />
    <AuthModal v-model="showAuthModal" />
    <ProfileModal v-model="showProfileModal" :user="$page.props.auth?.user" :initial-tab="profileActiveTab" />
  </header>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import LanguageModal from '@/Components/LanguageModal.vue';
import AuthModal from '@/Components/AuthModal.vue';
import ProfileModal from '@/Components/ProfileModal.vue';

const page = usePage();

const isRoleOpen = ref(false);
const isMobileMenuOpen = ref(false);
let roleCloseTimeout = null;

const openRoleDropdown = () => {
  if (roleCloseTimeout) {
    clearTimeout(roleCloseTimeout);
    roleCloseTimeout = null;
  }
  isRoleOpen.value = true;
};

const closeRoleDropdown = () => {
  if (roleCloseTimeout) clearTimeout(roleCloseTimeout);
  roleCloseTimeout = setTimeout(() => {
    isRoleOpen.value = false;
  }, 350);
};

const quickSearchQuery = ref('');
const showLanguageModal = ref(false);
const showAuthModal = ref(false);
const showProfileModal = ref(false);
const profileActiveTab = ref('info');
const currentLang = ref('vi');

onMounted(() => {
  const s = localStorage.getItem('bushub_locale');
  if (s) currentLang.value = s;
});

const onLanguageChange = (l) => {
  currentLang.value = l;
};

const openProfile = (tab = 'info') => {
  if (roleCloseTimeout) {
    clearTimeout(roleCloseTimeout);
    roleCloseTimeout = null;
  }
  isRoleOpen.value = false;
  profileActiveTab.value = tab;
  showProfileModal.value = true;
};

const handleLogout = () => {
  isRoleOpen.value = false;
  router.post('/logout');
};

const handleQuickSearch = () => {
  if (quickSearchQuery.value.trim()) {
    router.get('/trips', {
      destination: quickSearchQuery.value.trim()
    });
  } else {
    router.get('/trips');
  }
};
</script>
