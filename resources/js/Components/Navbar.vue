<template>
  <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-200/80 shadow-2xs transition-all">
    <div class="w-full max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16 gap-2 lg:gap-4">
        
        <!-- Logo Brand nhaxekientruc -->
        <Link href="/" class="flex items-center gap-2.5 group shrink-0">
          <div class="w-10 h-10 rounded-xl bg-white border border-red-200/90 flex items-center justify-center p-1 shadow-xs group-hover:scale-105 transition-all">
            <img src="/images/logo.png" alt="Logo Trường ĐH Kiến Trúc Đà Nẵng" class="w-full h-full object-contain" />
          </div>
          <div class="shrink-0">
            <span class="text-xl font-black tracking-tight text-red-600 block leading-none">
              nhaxekientruc
            </span>
            <span class="text-[9.5px] text-slate-400 font-bold block tracking-wider uppercase mt-1 whitespace-nowrap">ĐH KIẾN TRÚC ĐÀ NẴNG</span>
          </div>
        </Link>

        <!-- Menu Điều Hướng Chuẩn (Trang Chủ, Tìm Chuyến, Lịch Trình, Quản Lý Vé) -->
        <!-- Xích qua bên phải, nằm ngay sát bên cạnh nút Nâng cấp VIP -->
        <nav class="hidden md:flex items-center gap-5 lg:gap-7 ml-auto mr-3 lg:mr-5 text-[15px] font-semibold text-slate-700 shrink-0 whitespace-nowrap">
          <Link
            href="/"
            class="transition-all py-1 whitespace-nowrap shrink-0"
            :class="$page.url === '/' ? 'text-red-600 font-bold border-b-2 border-red-600 pb-1' : 'hover:text-red-600 text-slate-600'"
          >
            Trang Chủ
          </Link>
          <Link
            href="/trips"
            class="transition-all py-1 whitespace-nowrap shrink-0"
            :class="$page.url.startsWith('/trips') ? 'text-red-600 font-bold border-b-2 border-red-600 pb-1' : 'hover:text-red-600 text-slate-600'"
          >
            Tìm Chuyến
          </Link>
          <Link
            href="/schedules"
            class="transition-all py-1 whitespace-nowrap shrink-0"
            :class="$page.url.startsWith('/schedules') ? 'text-red-600 font-bold border-b-2 border-red-600 pb-1' : 'hover:text-red-600 text-slate-600'"
          >
            Lịch Trình
          </Link>
          <Link
            href="/my-bookings"
            class="transition-all py-1 whitespace-nowrap shrink-0"
            :class="$page.url.startsWith('/my-bookings') ? 'text-red-600 font-bold border-b-2 border-red-600 pb-1' : 'hover:text-red-600 text-slate-600'"
          >
            Quản Lý Vé
          </Link>
        </nav>

        <!-- Cổng Đăng Nhập & Ngôn Ngữ Theo Mẫu: VN | [👤] Đăng nhập -->
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
          
          <!-- Nút Countdown Gói Sinh Viên / Hội Viên Pro & Ultra Chuẩn Đẹp Như Cũ -->
          <button
            type="button"
            @click="showMembershipModal = true"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold transition shadow-2xs border cursor-pointer active:scale-95 bg-amber-50 hover:bg-amber-100 text-amber-950 border-amber-300"
            title="Bấm để xem quyền lợi & nâng cấp gói hội viên"
          >
            <span class="text-sm">{{ userMembershipPlan === 'ULTRA' ? '👑' : (userMembershipPlan === 'PRO' ? '🎓' : '👑') }}</span>
            <span class="font-black whitespace-nowrap">{{ userMembershipPlan === 'THUONG' ? 'Gói Ưu Đãi Hội Viên' : studentCountdownText }}</span>
            <span class="text-[9.5px] bg-[#dc2626] text-white px-1.5 py-0.5 rounded-full font-black uppercase tracking-tight shadow-xs">{{ userMembershipPlan === 'THUONG' ? 'Khám phá' : 'Nâng cấp' }}</span>
          </button>

          <!-- Nút Ngôn Ngữ (Desktop/Tablet) -->
          <button
            type="button"
            @click="showLanguageModal = true"
            class="hidden sm:inline-flex items-center justify-center text-xs font-bold text-slate-700 hover:text-red-600 transition cursor-pointer px-3 py-1.5 rounded-full hover:bg-slate-50 border border-slate-200 shadow-2xs tracking-wide font-mono"
            title="Đổi ngôn ngữ hiển thị"
          >
            {{ currentLangCode }}
          </button>

          <!-- Dấu gạch phân cách -->
          <span class="hidden sm:inline-block text-slate-300 select-none">|</span>

          <!-- CHUÔNG THÔNG BÁO KHÁCH HÀNG (CHỈ HIỂN THỊ KHI ĐÃ ĐĂNG NHẬP TÀI KHOẢN) -->
          <NotificationDropdown 
            v-if="$page.props.auth?.user" 
            customButtonClass="bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs" 
          />

          <!-- Dropdown Phân Quyền & Đăng Nhập OTP (Desktop/Tablet) -->
          <div class="hidden sm:block relative" @mouseenter="openRoleDropdown" @mouseleave="closeRoleDropdown">
            <!-- Nút tài khoản với KHUNG ĐỔI THEO TỪNG GÓI (ULTRA / PRO / THƯỜNG) -->
            <button
              @click="isRoleOpen = !isRoleOpen"
              class="flex items-center gap-2 text-xs sm:text-sm font-semibold transition cursor-pointer py-1.5 px-2.5 rounded-2xl active:scale-98 shadow-xs"
              :class="userMembershipPlan === 'ULTRA' 
                ? 'bg-gradient-to-r from-amber-50 via-orange-50/80 to-amber-100/70 border-2 border-amber-400 text-amber-950 ring-2 ring-amber-300/30 hover:ring-amber-400/50 hover:border-amber-500' 
                : (userMembershipPlan === 'PRO' 
                  ? 'bg-gradient-to-r from-emerald-50 via-teal-50/80 to-emerald-100/70 border-2 border-emerald-400 text-emerald-950 ring-2 ring-emerald-300/30 hover:ring-emerald-400/50 hover:border-emerald-500' 
                  : 'bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 hover:border-slate-300')"
            >
              <!-- Avatar icon đổi theo gói -->
              <div 
                class="w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0 shadow-2xs font-bold"
                :class="userMembershipPlan === 'ULTRA'
                  ? 'bg-gradient-to-tr from-amber-500 to-yellow-400 text-white border border-amber-200 shadow-amber-500/20'
                  : (userMembershipPlan === 'PRO'
                    ? 'bg-gradient-to-tr from-emerald-600 to-teal-500 text-white border border-emerald-200 shadow-emerald-500/20'
                    : 'bg-slate-100 text-slate-600 border border-slate-300')"
              >
                {{ userMembershipPlan === 'ULTRA' ? '👑' : (userMembershipPlan === 'PRO' ? ($page.props.auth?.user?.is_student ? '🎓' : '⭐') : '👤') }}
              </div>

              <!-- Tên người dùng ngắn gọn & Badge gói -->
              <span v-if="$page.props.auth?.user" class="flex items-center gap-1.5">
                <span 
                  class="font-black whitespace-nowrap"
                  :class="userMembershipPlan === 'ULTRA' ? 'text-amber-950' : (userMembershipPlan === 'PRO' ? 'text-emerald-950' : 'text-slate-900')"
                >
                  {{ cleanUserDisplayName }}
                </span>
                
                <!-- Badge Gói Hội Viên Gọn Gàng Trên Thanh Header -->
                <span 
                  class="px-1.5 py-0.5 rounded-md text-[9.5px] font-black uppercase tracking-tight shadow-2xs"
                  :class="userMembershipPlan === 'ULTRA'
                    ? 'bg-gradient-to-r from-red-600 to-amber-600 text-white'
                    : (userMembershipPlan === 'PRO'
                      ? 'bg-emerald-600 text-white'
                      : 'bg-slate-200 text-slate-700')"
                >
                  {{ userMembershipPlan === 'ULTRA' ? 'ULTRA' : (userMembershipPlan === 'PRO' ? ($page.props.auth.user.is_student ? 'SV PRO' : 'PRO') : 'THƯỜNG') }}
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
              class="absolute right-0 top-full mt-1.5 w-80 bg-white rounded-3xl shadow-2xl border border-slate-200/80 py-2.5 z-50 animate-in fade-in slide-in-from-top-2 duration-150 before:content-[''] before:absolute before:-top-3 before:inset-x-0 before:h-3"
            >
              <!-- 1. KHI ĐÃ ĐĂNG NHẬP -->
              <div v-if="$page.props.auth?.user">
                <!-- KHUNG HỘI VIÊN CHUẨN: ULTRA / PRO / THƯỜNG -->
                <div 
                  class="p-3 mx-2.5 mb-2 rounded-2xl border-2 space-y-1.5 shadow-sm transition"
                  :class="userMembershipPlan === 'ULTRA' ? 'bg-gradient-to-br from-amber-100/90 via-orange-50/60 to-amber-50/80 border-amber-400 ring-2 ring-amber-300/40' : (userMembershipPlan === 'PRO' ? 'bg-gradient-to-br from-emerald-100/90 via-teal-50/60 to-emerald-50/80 border-emerald-400 ring-2 ring-emerald-300/40' : 'bg-gradient-to-br from-slate-100 via-slate-50 to-white border-slate-200')"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-black text-slate-900 truncate max-w-[140px]">{{ cleanUserDisplayName }}</span>
                    <span 
                      class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase text-white shadow-2xs"
                      :class="userMembershipPlan === 'ULTRA' ? 'bg-gradient-to-r from-red-600 to-amber-600' : (userMembershipPlan === 'PRO' ? 'bg-emerald-600' : 'bg-slate-600')"
                    >
                      {{ userMembershipPlan === 'ULTRA' ? '👑 HỘI VIÊN ULTRA' : (userMembershipPlan === 'PRO' ? ($page.props.auth.user.is_student ? '🎓 SINH VIÊN PRO' : '⭐ HỘI VIÊN PRO') : '🥉 KHÁCH THƯỜNG') }}
                    </span>
                  </div>

                  <!-- Khung trạng thái gói hội viên & Số ngày còn lại -->
                  <div class="p-2 rounded-xl bg-white/90 border border-slate-200/80 space-y-1 text-xs">
                    <div class="flex items-center justify-between font-bold">
                      <span class="text-[11px] text-slate-600">
                        {{ userMembershipPlan === 'ULTRA' ? 'Gói ULTRA (-40%)' : (userMembershipPlan === 'PRO' ? 'Gói PRO (-30%)' : 'Chưa kích hoạt gói ưu đãi') }}
                      </span>
                      <span v-if="userMembershipPlan !== 'THUONG'" class="text-[10.5px] font-mono font-black text-red-600">
                        Còn {{ $page.props.auth.user.student_days_remaining ?? 365 }} ngày
                      </span>
                      <span v-else class="text-[10px] text-slate-400 font-normal">Tiêu chuẩn</span>
                    </div>

                    <div v-if="userMembershipPlan !== 'THUONG'" class="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-100">
                      <span>Bắt đầu: <b>{{ $page.props.auth.user.membership_registered_at || $page.props.auth.user.student_registered_at || 'Hôm nay' }}</b></span>
                      <span>Hết hạn: <b>{{ $page.props.auth.user.membership_expires_at || '365 ngày' }}</b></span>
                    </div>
                  </div>

                  <div class="flex items-center justify-between text-xs pt-0.5">
                    <span class="text-slate-500 text-[11px]">Điểm tích lũy:</span>
                    <strong class="text-amber-700 font-mono font-black text-sm">
                      🪙 {{ ($page.props.auth.user.reward_points || 0).toLocaleString('vi-VN') }} Điểm
                    </strong>
                  </div>

                  <!-- Các nút xem gói & nâng cấp -->
                  <div class="grid grid-cols-2 gap-1.5 pt-1">
                    <button
                      type="button"
                      @click="showMembershipModal = true; isRoleOpen = false"
                      class="text-center py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl transition shadow-xs cursor-pointer"
                    >
                      {{ userMembershipPlan === 'THUONG' ? '⚡ Mua Gói' : '⚡ Nâng Cấp Gói' }}
                    </button>
                    <button
                      type="button"
                      @click="openProfile('membership')"
                      class="text-center py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition shadow-xs cursor-pointer"
                    >
                      👑 Gói Của Tôi
                    </button>
                  </div>
                </div>

                <!-- TÙY CHỌN TÀI KHOẢN KHÁCH HÀNG -->
                <div class="px-4 py-1 text-[10px] font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                  Hội Viên & Quản Lý Gói
                </div>

                <div class="py-1">
                  <!-- Mua / Nâng Cấp Gói Hội Viên (Pro, Ultra) -->
                  <button
                    type="button"
                    @click.prevent.stop="showMembershipModal = true; isRoleOpen = false"
                    class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-xs font-bold text-amber-950 bg-amber-50/70 hover:bg-amber-100/80 transition-colors cursor-pointer border-b border-amber-200/50"
                  >
                    <span class="text-base">👑</span>
                    <div class="flex-1 min-w-0">
                      <p class="leading-none flex items-center justify-between">
                        <span class="font-black text-slate-900">Mua / Nâng Cấp Gói Hội Viên</span>
                        <span class="text-[9px] bg-red-600 text-white px-1.5 py-0.2 rounded font-black">ƯU ĐÃI 40%</span>
                      </p>
                      <p class="text-[10px] text-amber-800 font-medium mt-0.5">Khám phá các gói Eco, Pro, Ultra & mua ngay</p>
                    </div>
                  </button>

                  <!-- Xem Chi Tiết Gói Đang Dùng -->
                  <button
                    type="button"
                    @click.prevent.stop="openProfile('membership')"
                    class="w-full text-left flex items-center gap-3 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:text-red-600 transition-colors cursor-pointer"
                  >
                    <span class="text-base">📊</span>
                    <div class="flex-1 min-w-0">
                      <p class="leading-none">Chi Tiết Gói & Thời Gian Dùng</p>
                      <p class="text-[10px] text-slate-400 font-normal mt-0.5 truncate">{{ navbarMembershipBadgeText }}</p>
                    </div>
                  </button>

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
                        <span v-else-if="$page.props.auth.user.email && !$page.props.auth.user.phone">Thêm SĐT nhận tin nhắn SMS khi xe đón</span>
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
                    <p class="text-[10px] text-slate-400 font-normal mt-0.5">Xác thực OTP qua SMS hoặc Gmail</p>
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
  </header>

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

              <!-- Mở Modal Gói Hội Viên trên Mobile -->
              <div v-if="$page.props.auth?.user" class="my-1.5 space-y-1.5">
                <button
                  type="button"
                  @click="openProfile('membership'); isMobileMenuOpen = false"
                  class="w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-2xl text-xs font-bold transition cursor-pointer border shadow-xs"
                  :class="userMembershipPlan === 'ULTRA' ? 'bg-amber-100/90 text-amber-950 border-amber-400' : (userMembershipPlan === 'PRO' ? 'bg-emerald-50 text-emerald-950 border-emerald-400' : 'bg-slate-50 text-slate-800 border-slate-200')"
                >
                  <span class="text-xl">{{ userMembershipPlan === 'ULTRA' ? '👑' : (userMembershipPlan === 'PRO' ? '⭐' : '🥉') }}</span>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <p class="font-black text-xs leading-tight">
                        {{ userMembershipPlan === 'ULTRA' ? 'Khung Hội Viên ULTRA (-40%)' : (userMembershipPlan === 'PRO' ? 'Khung Hội Viên PRO (-30%)' : 'Khung Khách Thường') }}
                      </p>
                      <span class="text-[9.5px] bg-slate-800 text-white px-2 py-0.5 rounded-full font-black uppercase">
                        CHI TIẾT
                      </span>
                    </div>
                    <p class="text-[10px] text-slate-600 mt-0.5 font-semibold truncate">{{ navbarMembershipBadgeText }}</p>
                  </div>
                </button>

                <button
                  type="button"
                  @click="showMembershipModal = true; isMobileMenuOpen = false"
                  class="w-full py-2 px-3 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs shadow-xs flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  <span>⚡</span>
                  <span>{{ userMembershipPlan === 'THUONG' ? 'Mua Gói Ưu Đãi Hội Viên (Giảm 20% - 40%)' : 'Nâng Cấp Gói Hội Viên' }}</span>
                </button>
              </div>

              <!-- Dành cho Khách chưa đăng nhập trên Mobile -->
              <div v-else class="my-1.5">
                <button
                  type="button"
                  @click="showMembershipModal = true; isMobileMenuOpen = false"
                  class="w-full py-2.5 px-3 rounded-2xl bg-gradient-to-r from-amber-50 via-orange-50 to-amber-100/90 text-amber-950 border border-amber-300 font-black text-xs shadow-xs flex items-center justify-between cursor-pointer"
                >
                  <span class="flex items-center gap-2">
                    <span class="text-base">👑</span>
                    <span>Gói Hội Viên & Ưu Đãi Sinh Viên</span>
                  </span>
                  <span class="text-[9.5px] bg-red-600 text-white px-2 py-0.5 rounded-full font-black uppercase tracking-tight">Khám phá</span>
                </button>
              </div>

              <!-- Khu vực Nghiệp vụ -->
              <div class="pt-2.5 border-t border-slate-100">
                <div class="text-[10px] font-black uppercase text-slate-400 px-3 py-1 tracking-wider">
                  Cổng Nghiệp Vụ & Điều Hành
                </div>
                <div class="grid grid-cols-2 gap-2 px-1 pt-1">
                  <Link 
                    href="/driver" 
                    @click="isMobileMenuOpen = false" 
                    class="flex items-center gap-2 p-2.5 rounded-2xl bg-amber-50 hover:bg-amber-100 text-amber-950 border border-amber-200 transition"
                  >
                    <span class="text-xl">👨‍✈️</span>
                    <div class="min-w-0">
                      <p class="font-black text-xs leading-tight">Bác Tài</p>
                      <p class="text-[9px] text-amber-700 truncate">Lái xe • Map</p>
                    </div>
                  </Link>

                  <Link 
                    href="/conductor" 
                    @click="isMobileMenuOpen = false" 
                    class="flex items-center gap-2 p-2.5 rounded-2xl bg-blue-50 hover:bg-blue-100 text-blue-950 border border-blue-200 transition"
                  >
                    <span class="text-xl">🎫</span>
                    <div class="min-w-0">
                      <p class="font-black text-xs leading-tight">Lơ Xe</p>
                      <p class="text-[9px] text-blue-700 truncate">Soát vé • Sơ đồ</p>
                    </div>
                  </Link>

                  <Link 
                    href="/scanner" 
                    @click="isMobileMenuOpen = false" 
                    class="flex items-center gap-2 p-2.5 rounded-2xl bg-emerald-50 hover:bg-emerald-100 text-emerald-950 border border-emerald-200 transition"
                  >
                    <span class="text-xl">📷</span>
                    <div class="min-w-0">
                      <p class="font-black text-xs leading-tight">Quét QR</p>
                      <p class="text-[9px] text-emerald-700 truncate">Camera vé</p>
                    </div>
                  </Link>

                  <Link 
                    href="/admin" 
                    @click="isMobileMenuOpen = false" 
                    class="flex items-center gap-2 p-2.5 rounded-2xl bg-purple-50 hover:bg-purple-100 text-purple-950 border border-purple-200 transition"
                  >
                    <span class="text-xl">👑</span>
                    <div class="min-w-0">
                      <p class="font-black text-xs leading-tight">Admin</p>
                      <p class="text-[9px] text-purple-700 truncate">Quản trị xe</p>
                    </div>
                  </Link>
                </div>
              </div>

              <!-- Auth Buttons on Mobile -->
              <div class="pt-2.5 border-t border-slate-100">
                <div v-if="!$page.props.auth?.user" class="space-y-2 pt-1">
                  <Link
                    href="/login"
                    @click="isMobileMenuOpen = false"
                    class="w-full py-3 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs sm:text-sm flex items-center justify-center gap-2 shadow-sm transition active:scale-98"
                  >
                    <span>🔐 Đăng Nhập Hệ Thống</span>
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
                    class="w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                  >
                    <span class="text-base">👤</span>
                    <span>Hồ Sơ Cá Nhân</span>
                  </button>
                  <button
                    type="button"
                    @click="isMobileMenuOpen = false; openProfile('password')"
                    class="w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 transition"
                  >
                    <span class="text-base">🔒</span>
                    <span>Đổi Mật Khẩu</span>
                  </button>

                  <!-- Đổi ngôn ngữ trong Mobile Drawer -->
                  <button
                    type="button"
                    @click="showLanguageModal = true"
                    class="w-full text-left flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition border border-slate-200"
                  >
                    <span class="flex items-center gap-2.5">
                      <span class="text-base">🌐</span>
                      <span>Ngôn Ngữ / Language</span>
                    </span>
                    <span class="font-mono text-red-600 font-black text-xs">
                      {{ currentLangCode }}
                    </span>
                  </button>

                  <button
                    type="button"
                    @click="isMobileMenuOpen = false; handleLogout()"
                    class="w-full text-left flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-red-600 hover:bg-red-50 transition"
                  >
                    <span class="text-base">🚪</span>
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

    <!-- THANH BOTTOM NAVIGATION BAR CHUẨN NATIVE MOBILE APP (KHÁCH HÀNG) -->
    <Teleport to="body">
      <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_25px_rgba(0,0,0,0.08)] pt-1.5 pb-3 px-1 grid grid-cols-5 items-end justify-items-center select-none">
        <!-- 1. Trang Chủ -->
        <Link 
          href="/" 
          class="w-full flex flex-col items-center justify-center py-1 transition-all"
          :class="$page.url === '/' ? 'text-red-600 font-black' : 'text-slate-500 hover:text-slate-800 font-bold'"
        >
          <span class="text-lg leading-none">🏠</span>
          <span class="text-[10px] tracking-tight mt-1 truncate text-center w-full">Trang Chủ</span>
        </Link>

        <!-- 2. Tìm Chuyến -->
        <Link 
          href="/trips" 
          class="w-full flex flex-col items-center justify-center py-1 transition-all"
          :class="$page.url.startsWith('/trips') ? 'text-red-600 font-black' : 'text-slate-500 hover:text-slate-800 font-bold'"
        >
          <span class="text-lg leading-none">🔍</span>
          <span class="text-[10px] tracking-tight mt-1 truncate text-center w-full">Tìm Chuyến</span>
        </Link>

        <!-- 3. Nút Đặt Vé Nổi Bật Trung Tâm (CTA Style Grab/Shopee) -->
        <Link
          href="/trips"
          class="w-full flex flex-col items-center justify-center -mt-6 transition-transform active:scale-95"
        >
          <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-red-600 via-rose-600 to-red-500 text-white flex items-center justify-center text-xl shadow-lg shadow-red-500/40 border-2 border-white">
            🚌
          </div>
          <span class="text-[9.5px] font-black text-red-600 tracking-tight mt-0.5 truncate text-center w-full">Đặt Vé</span>
        </Link>

        <!-- 4. Vé Của Tôi -->
        <Link 
          href="/my-bookings" 
          class="w-full flex flex-col items-center justify-center py-1 transition-all"
          :class="$page.url.startsWith('/my-bookings') ? 'text-red-600 font-black' : 'text-slate-500 hover:text-slate-800 font-bold'"
        >
          <span class="text-lg leading-none">🎫</span>
          <span class="text-[10px] tracking-tight mt-1 truncate text-center w-full">Vé Của Tôi</span>
        </Link>

        <!-- 5. Menu / Tài Khoản -->
        <button 
          type="button"
          @click="isMobileMenuOpen = true" 
          class="w-full flex flex-col items-center justify-center py-1 transition-all cursor-pointer"
          :class="isMobileMenuOpen ? 'text-red-600 font-black' : 'text-slate-500 hover:text-slate-800 font-bold'"
        >
          <span class="text-lg leading-none">{{ $page.props.auth?.user ? '👤' : '☰' }}</span>
          <span class="text-[10px] tracking-tight mt-1 truncate text-center w-full">{{ $page.props.auth?.user ? 'Tài Khoản' : 'Menu' }}</span>
        </button>
      </nav>
    </Teleport>

    <!-- Modals -->
    <LanguageModal v-model="showLanguageModal" @change="onLanguageChange" />
    <AuthModal v-model="showAuthModal" />
    <ProfileModal v-model="showProfileModal" :user="$page.props.auth?.user" :initial-tab="profileActiveTab" @open-membership="showMembershipModal = true" />
    <MembershipModal :is-open="showMembershipModal" @close="showMembershipModal = false" @open-auth="showMembershipModal = false; showAuthModal = true" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';
import LanguageModal from '@/Components/LanguageModal.vue';
import AuthModal from '@/Components/AuthModal.vue';
import ProfileModal from '@/Components/ProfileModal.vue';
import MembershipModal from '@/Components/MembershipModal.vue';
import { getCurrentLanguage, getLanguageInfo } from '@/utils/translator';

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
const showMembershipModal = ref(false);
const profileActiveTab = ref('info');
const currentLang = ref(getCurrentLanguage());
const currentLangInfo = computed(() => getLanguageInfo(currentLang.value));
const currentLangFlag = computed(() => currentLangInfo.value?.flag || '🇻🇳');
const currentLangCode = computed(() => currentLangInfo.value?.short || 'VN');

const userMembershipPlan = computed(() => {
  const u = page.props.auth?.user;
  if (!u) return 'THUONG';
  const plan = String(u.membership_plan || '').toUpperCase();
  if (plan === 'CANCELLED' || plan === 'STANDARD' || plan === 'THUONG' || !plan) return 'THUONG';
  if (plan.includes('ULTRA')) return 'ULTRA';
  if (plan.includes('PRO')) return 'PRO';
  if (plan.includes('ECO')) return 'ECO';
  return 'THUONG';
});

const cleanUserDisplayName = computed(() => {
  const raw = page.props.auth?.user?.name || 'Khách hàng';
  const cleaned = raw.replace(/^Sinh\s*vi[êe]n\s+/i, '').trim();
  return cleaned || raw;
});

const navbarMembershipBadgeText = computed(() => {
  const u = page.props.auth?.user;
  if (!u) return 'Gói Ưu Đãi Hội Viên';
  const days = u.student_days_remaining ?? 0;
  if (userMembershipPlan.value === 'ULTRA') {
    return `Ultra 40%: Còn ${days} ngày`;
  }
  if (userMembershipPlan.value === 'PRO') {
    return `SV 30%: Còn ${days} ngày`;
  }
  return 'Gói Ưu Đãi Hội Viên';
});

const studentCountdownText = computed(() => {
  return navbarMembershipBadgeText.value;
});

onMounted(() => {
  currentLang.value = getCurrentLanguage();
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
  try {
    localStorage.removeItem('bushub_saved_vouchers');
    localStorage.removeItem('saved_vouchers');
    localStorage.removeItem('nhaxekientruc_saved_vouchers');
    localStorage.removeItem('nhaxekientruc_read_notifs');
  } catch (e) {}
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
