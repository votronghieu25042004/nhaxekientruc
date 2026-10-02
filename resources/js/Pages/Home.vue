<template>
  <div class="min-h-screen flex flex-col bg-white text-slate-800 font-sans">
    <Navbar />

    <!-- ==================== BANNER ĐỎ SANG TRỌNG CÓ CẦU RỒNG & THÁP CỔ & XE KHÁCH 3D ==================== -->
    <section class="relative bg-gradient-to-r from-[#420609] via-[#630b0f] to-[#7a0f13] text-white pt-8 sm:pt-12 pb-20 sm:pb-24 px-4 sm:px-6 lg:px-8 overflow-visible shadow-md">
      
      <!-- HỌA TIẾT CẦU RỒNG ĐÀ NẴNG & THÁP RÙA 3D CHUẨN ĐẸP 100% THEO HÌNH MẪU -->
      <div class="absolute left-0 top-0 bottom-0 w-full sm:w-8/12 lg:w-7/12 pointer-events-none overflow-hidden select-none">
        <img
          src="/images/dragon_bridge_landmark.jpg"
          alt="Cầu Rồng & Tháp Cổ Landmark"
          class="w-full h-full object-cover object-left opacity-90"
          style="mask-image: linear-gradient(to right, black 65%, transparent 100%); -webkit-mask-image: linear-gradient(to right, black 65%, transparent 100%);"
        />
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[#420609]/20 to-[#630b0f]"></div>
      </div>

      <!-- Bức Tranh Xe Khách 3D Phía Bên Phải -->
      <div class="absolute right-0 top-0 bottom-0 w-full sm:w-1/2 lg:w-5/12 pointer-events-none overflow-hidden hidden sm:block">
        <img
          src="/images/luxury_bus_banner.jpg"
          alt="Xe khách cao cấp"
          class="w-full h-full object-cover object-center opacity-90"
          style="mask-image: linear-gradient(to right, transparent 0%, rgba(0,0,0,0.85) 35%, black 100%); -webkit-mask-image: linear-gradient(to right, transparent 0%, rgba(0,0,0,0.85) 35%, black 100%);"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-[#420609]/70 via-transparent to-transparent"></div>
      </div>

      <!-- Nội Dung Banner: Cụm Tiêu Đề & Card Voucher 30% XÍCH VÀO GIỮA -->
      <div class="max-w-7xl mx-auto relative z-10 flex items-center justify-center pt-2">
        <div class="flex flex-col sm:flex-row items-center justify-center gap-5 sm:gap-8">
          
          <!-- Chữ "Đặt vé xe online" + Cam kết -->
          <div class="text-center sm:text-left space-y-1">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white drop-shadow-md whitespace-nowrap">
              Đặt vé xe online
            </h1>
            <p class="text-white/95 text-xs sm:text-sm font-bold flex items-center justify-center sm:justify-start gap-1.5 pt-0.5">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block shadow-sm shadow-emerald-400"></span>
              <span>Cam kết giữ chỗ 100% • Hủy/Đổi linh hoạt</span>
            </p>
          </div>

          <!-- Cards Voucher Shopee Style: Khách Mới 10% (CHAOBAOMOI) & Sinh Viên 30% (SINHVIEN) -->
          <div class="flex flex-wrap items-center justify-center gap-2.5 sm:gap-3">
            <!-- Card 1: Khách mới 10% -->
            <div class="flex-shrink-0 rounded-2xl overflow-hidden shadow-xl border-2 border-white/40 transform hover:scale-105 transition-all duration-300">
              <div class="bg-[#00767a] px-4 py-1.5 text-center flex items-center justify-between gap-2">
                <span class="text-white font-black text-xs sm:text-sm tracking-wide whitespace-nowrap">
                  Khách Mới Giảm 10%
                </span>
                <button
                  type="button"
                  @click="saveWelcomeVoucher('CHAOBAOMOI')"
                  class="px-2 py-0.5 rounded-lg text-[10px] font-black cursor-pointer transition shadow-xs"
                  :class="isVoucherSaved('CHAOBAOMOI') ? 'bg-white/20 text-white' : 'bg-amber-300 hover:bg-amber-400 text-slate-900'"
                >
                  {{ isVoucherSaved('CHAOBAOMOI') ? '✓ Đã Lưu' : 'Lưu Mã' }}
                </button>
              </div>
              <div class="bg-[#fce8a6] px-3.5 py-1 text-center flex items-center justify-center gap-2">
                <span class="text-slate-900 text-[11px] font-bold whitespace-nowrap">Nhập mã</span>
                <span class="text-[#00767a] font-black text-xs sm:text-sm tracking-wider bg-white px-2 py-0.5 rounded shadow-xs border border-[#00767a]/40 font-mono whitespace-nowrap">
                  CHAOBAOMOI
                </span>
              </div>
            </div>

            <!-- Card 2: Sinh viên 30% -->
            <div class="flex-shrink-0 rounded-2xl overflow-hidden shadow-xl border-2 border-amber-300/60 transform hover:scale-105 transition-all duration-300">
              <div class="bg-gradient-to-r from-red-600 to-rose-600 px-4 py-1.5 text-center flex items-center justify-between gap-2">
                <span class="text-white font-black text-xs sm:text-sm tracking-wide whitespace-nowrap flex items-center gap-1">
                  <span>🎓</span>
                  <span>Sinh Viên Giảm 30%</span>
                </span>
                <button
                  type="button"
                  @click="saveWelcomeVoucher('SINHVIEN')"
                  class="px-2 py-0.5 rounded-lg text-[10px] font-black cursor-pointer transition shadow-xs"
                  :class="isVoucherSaved('SINHVIEN') ? 'bg-white/20 text-white' : 'bg-amber-300 hover:bg-amber-400 text-slate-900'"
                >
                  {{ isVoucherSaved('SINHVIEN') ? '✓ Đã Lưu' : 'Lưu Mã' }}
                </button>
              </div>
              <div class="bg-red-50 px-3.5 py-1 text-center flex items-center justify-center gap-2 border-t border-red-200">
                <span class="text-slate-900 text-[11px] font-bold whitespace-nowrap">Nhập mã</span>
                <span class="text-red-600 font-black text-xs sm:text-sm tracking-wider bg-white px-2 py-0.5 rounded shadow-xs border border-red-300 font-mono whitespace-nowrap">
                  SINHVIEN
                </span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- KHUNG TÌM KIẾM CĂN CHÍNH XÁC: 50% NỬA TRÊN ĐỎ / 50% NỬA DƯỚI TRẮNG -->
      <div class="max-w-7xl mx-auto relative z-30 mt-6 sm:mt-8 -mb-28 sm:-mb-36">
        <SearchWidget :destinations="destinations_today" />
      </div>
    </section>

    <!-- Spacer Cho Nửa Dưới Của Search Widget -->
    <div class="h-24 sm:h-32"></div>

    <!-- ==================== KHỐI 1: TUYẾN ĐƯỜNG PHỔ BIẾN & ƯU ĐÃI (NỀN TRẮNG) ==================== -->
    <section class="w-full bg-white py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Tuyến Phổ Biến -->
        <div class="flex items-center justify-between gap-4 mb-5">
          <div>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-red-600"></span>
              <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                Tuyến Đường Phổ Biến Xuất Bến Từ Đà Nẵng
              </h2>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-0.5">
              Được khách hàng tin chọn nhiều nhất với giá vé ưu đãi và dàn xe VIP hiện đại
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="scrollRoutes('left')"
              class="w-9 h-9 rounded-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 flex items-center justify-center shadow-xs transition-all active:scale-95 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button
              @click="scrollRoutes('right')"
              class="w-9 h-9 rounded-full bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 flex items-center justify-center shadow-xs transition-all active:scale-95 cursor-pointer"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Carousel Cards Tuyến Đường -->
        <div
          ref="routesContainer"
          class="flex gap-4 sm:gap-6 overflow-x-auto no-scrollbar pb-4 pt-1 snap-x"
        >
          <div
            v-for="(item, idx) in popularRoutesList"
            :key="idx"
            @click="selectRoute(item)"
            class="flex-shrink-0 w-72 sm:w-80 rounded-3xl overflow-hidden shadow-xs hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer group flex flex-col justify-between border border-slate-100"
          >
            <div class="h-44 relative overflow-hidden bg-slate-200">
              <img
                :src="item.image"
                :alt="item.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                loading="lazy"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
              
              <div class="absolute top-3 left-3 bg-black/40 backdrop-blur-md text-white text-[11px] font-bold px-2.5 py-1 rounded-full border border-white/20">
                {{ item.tripsCount }} chuyến/ngày
              </div>
              <div class="absolute top-3 right-3 bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-xs">
                {{ item.distance }}
              </div>

              <div class="absolute bottom-3 left-4 right-4 text-white">
                <p class="text-xs font-medium text-slate-200">{{ item.duration }} di chuyển</p>
                <h3 class="text-base font-bold truncate">{{ item.title }}</h3>
              </div>
            </div>

            <div class="p-4 bg-white border border-t-0 border-slate-100 flex items-center justify-between">
              <div>
                <span class="text-[11px] text-slate-400 block font-medium">Giá vé từ</span>
                <span class="text-lg font-black text-red-600">{{ formatPrice(item.price) }}</span>
              </div>
              <span class="text-xs font-bold text-red-600 group-hover:text-red-700 flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                Tìm chuyến ➔
              </span>
            </div>
          </div>
        </div>

        <!-- Khối ƯU ĐÃI (3 Thẻ Voucher Ngay Dưới Tuyến Đường Phổ Biến) -->
        <div class="mt-8 pt-6 border-t border-slate-100">
          <div class="mb-4">
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
              ƯU ĐÃI NỔI BẬT
            </h2>
            <p class="text-xs text-slate-500 font-medium mt-0.5">
              Nhập mã voucher độc quyền khi thanh toán để nhận ngay chiết khấu trực tiếp vào tiền vé
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Voucher Card 1: Dành riêng sinh viên giảm 30% -->
            <div 
              @click="saveVoucherWithToast('SINHVIEN')"
              class="relative bg-gradient-to-br from-red-50 via-rose-50 to-amber-50/40 border border-red-200 rounded-3xl p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group cursor-pointer"
            >
              <div class="space-y-1.5 z-10">
                <span class="text-[10px] font-black tracking-widest text-red-700 uppercase flex items-center gap-1">
                  <span>🎓</span>
                  <span>ĐẶC QUYỀN SINH VIÊN</span>
                </span>
                <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-snug">
                  Giảm 30% khi sinh viên đặt vé xe lần đầu
                </h3>
                <p class="text-[11px] text-slate-500 font-medium">Áp dụng tài khoản có email trường (.edu.vn)</p>
              </div>

              <div class="mt-5 flex items-center justify-between z-10">
                <button
                  type="button"
                  @click.stop="saveVoucherWithToast('SINHVIEN')"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border shadow-xs text-xs font-black transition cursor-pointer"
                  :class="isVoucherSaved('SINHVIEN')
                    ? 'bg-slate-100 text-slate-700 border-slate-300'
                    : 'bg-red-600 hover:bg-red-700 text-white border-red-600'"
                >
                  <span>{{ isVoucherSaved('SINHVIEN') ? '✓ Đã Lưu Vào Ví' : '🎟️ Nhấn để lưu mã' }}</span>
                </button>
                <span class="text-[10px] font-black text-red-700 bg-red-100 px-2.5 py-0.5 rounded-full">
                  -30%
                </span>
              </div>

              <div class="absolute -bottom-1 -right-1 w-24 h-24 opacity-80 pointer-events-none group-hover:scale-110 transition-transform">
                <svg viewBox="0 0 100 100" class="w-full h-full drop-shadow-xs">
                  <circle cx="55" cy="55" r="30" fill="#fca5a5" />
                  <ellipse cx="50" cy="52" rx="26" ry="22" fill="#f87171" />
                  <circle cx="45" cy="42" r="3" fill="#1e293b" />
                  <circle cx="70" cy="24" r="7" fill="#fbbf24" stroke="#f59e0b" stroke-width="1.5" />
                  <text x="69" y="27" font-size="8" font-weight="bold" fill="#78350f" text-anchor="middle">🎓</text>
                </svg>
              </div>
            </div>

            <!-- Voucher Card 2: Dành cho thành viên mới giảm 10% -->
            <div 
              @click="saveVoucherWithToast('CHAOBAOMOI')"
              class="relative bg-gradient-to-br from-teal-50 via-emerald-50 to-cyan-50/40 border border-teal-200 rounded-3xl p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group cursor-pointer"
            >
              <div class="space-y-1.5 z-10">
                <span class="text-[10px] font-black tracking-widest text-teal-800 uppercase flex items-center gap-1">
                  <span>🎉</span>
                  <span>DÀNH RIÊNG KHÁCH MỚI</span>
                </span>
                <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-snug">
                  Giảm 10% khi đặt vé xe lần đầu tiên
                </h3>
                <p class="text-[11px] text-slate-500 font-medium">Áp dụng cho mọi khách hàng mới tạo tài khoản</p>
              </div>

              <div class="mt-5 flex items-center justify-between z-10">
                <button
                  type="button"
                  @click.stop="saveVoucherWithToast('CHAOBAOMOI')"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border shadow-xs text-xs font-black transition cursor-pointer"
                  :class="isVoucherSaved('CHAOBAOMOI')
                    ? 'bg-slate-100 text-slate-700 border-slate-300'
                    : 'bg-teal-600 hover:bg-teal-700 text-white border-teal-600'"
                >
                  <span>{{ isVoucherSaved('CHAOBAOMOI') ? '✓ Đã Lưu Vào Ví' : '🎟️ Nhấn để lưu mã' }}</span>
                </button>
                <span class="text-[10px] font-black text-teal-800 bg-teal-100 px-2.5 py-0.5 rounded-full">
                  -10%
                </span>
              </div>

              <div class="absolute -bottom-1 -right-1 w-24 h-24 opacity-80 pointer-events-none group-hover:scale-110 transition-transform">
                <svg viewBox="0 0 100 100" class="w-full h-full drop-shadow-xs">
                  <rect x="25" y="30" width="45" height="58" rx="8" transform="rotate(-15 47 59)" fill="#0d9488" />
                  <rect x="29" y="34" width="37" height="50" rx="5" transform="rotate(-15 47 59)" fill="#14b8a6" />
                  <circle cx="70" cy="35" r="8" fill="#fbbf24" />
                </svg>
              </div>
            </div>

            <!-- Voucher Card 3: Tiết kiệm 20.000đ cho đơn từ 200k -->
            <div 
              @click="saveVoucherWithToast('KIENTRUC20')"
              class="relative bg-gradient-to-br from-amber-50 via-orange-50 to-yellow-50/40 border border-amber-200 rounded-3xl p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group cursor-pointer"
            >
              <div class="space-y-1.5 z-10">
                <span class="text-[10px] font-black tracking-widest text-amber-800 uppercase flex items-center gap-1">
                  <span>⭐</span>
                  <span>TRI ÂN KHÁCH HÀNG</span>
                </span>
                <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-snug">
                  Tiết kiệm 20.000 VNĐ cho đơn từ 200.000đ
                </h3>
                <p class="text-[11px] text-slate-500 font-medium">Áp dụng cho mọi chuyến xe tại nhaxekientruc</p>
              </div>

              <div class="mt-5 flex items-center justify-between z-10">
                <button
                  type="button"
                  @click.stop="saveVoucherWithToast('KIENTRUC20')"
                  class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border shadow-xs text-xs font-black transition cursor-pointer"
                  :class="isVoucherSaved('KIENTRUC20')
                    ? 'bg-slate-100 text-slate-700 border-slate-300'
                    : 'bg-amber-600 hover:bg-amber-700 text-white border-amber-600'"
                >
                  <span>{{ isVoucherSaved('KIENTRUC20') ? '✓ Đã Lưu Vào Ví' : '🎟️ Nhấn để lưu mã' }}</span>
                </button>
                <span class="text-[10px] font-black text-amber-800 bg-amber-100 px-2.5 py-0.5 rounded-full">
                  -20K
                </span>
              </div>

              <div class="absolute -bottom-1 -right-1 w-24 h-24 opacity-85 pointer-events-none group-hover:scale-110 transition-transform">
                <svg viewBox="0 0 100 100" class="w-full h-full drop-shadow-xs">
                  <rect x="25" y="45" width="55" height="26" rx="8" fill="#d97706" />
                  <path d="M35 45 L45 30 L70 30 L77 45 Z" fill="#f59e0b" />
                  <circle cx="38" cy="71" r="7" fill="#0f172a" />
                  <circle cx="68" cy="71" r="7" fill="#0f172a" />
                </svg>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ==================== KHỐI 2: CÓ GÌ MỚI / CHÍNH SÁCH (NỀN HỒNG PASTEL) ==================== -->
    <section class="w-full bg-[#fff5f4] border-y border-[#ffe4e1] py-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6">
          <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight uppercase">
            CÓ GÌ MỚI / CHÍNH SÁCH
          </h2>
          <p class="text-xs text-slate-500 font-medium mt-0.5">
            Các chính sách bảo vệ quyền lợi tối đa và hỗ trợ hành khách chu đáo từ nhaxekientruc
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Card 1: Chính sách Đảm bảo vé -->
          <div class="bg-white rounded-3xl p-6 border border-red-100 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="space-y-4">
              <div class="w-12 h-12 rounded-2xl bg-[#e0f2fe] text-[#0284c7] flex items-center justify-center text-xl shadow-xs">
                🛡️
              </div>
              <div>
                <h3 class="font-black text-base text-slate-900 mb-1.5">
                  Chính sách Đảm bảo vé
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                  Hoàn tiền 100% tiền cọc và tiền vé khi quý khách thực hiện yêu cầu hủy vé đúng quy định.
                </p>
              </div>
            </div>
            <div class="pt-4 mt-2">
              <a href="#faq-accordion-section" class="text-xs font-bold text-[#0284c7] hover:text-[#0369a1] inline-flex items-center gap-1 group">
                <span>Tìm hiểu thêm</span>
                <span class="group-hover:translate-x-1 transition-transform">➔</span>
              </a>
            </div>
          </div>

          <!-- Card 2: Đổi chuyến xe linh hoạt -->
          <div class="bg-white rounded-3xl p-6 border border-red-100 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="space-y-4">
              <div class="w-12 h-12 rounded-2xl bg-[#e0e7ff] text-[#4f46e5] flex items-center justify-center text-xl shadow-xs">
                🔄
              </div>
              <div>
                <h3 class="font-black text-base text-slate-900 mb-1.5">
                  Đổi chuyến xe linh hoạt
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                  Hỗ trợ đổi sang chuyến xe khác cùng tuyến miễn phí 01 lần trước giờ xuất bến 04 tiếng.
                </p>
              </div>
            </div>
            <div class="pt-4 mt-2">
              <a href="#faq-accordion-section" class="text-xs font-bold text-[#0284c7] hover:text-[#0369a1] inline-flex items-center gap-1 group">
                <span>Tìm hiểu thêm</span>
                <span class="group-hover:translate-x-1 transition-transform">➔</span>
              </a>
            </div>
          </div>

          <!-- Card 3: Cam kết giữ đúng chỗ -->
          <div class="bg-white rounded-3xl p-6 border border-red-100 shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between">
            <div class="space-y-4">
              <div class="w-12 h-12 rounded-2xl bg-[#fef3c7] text-[#d97706] flex items-center justify-center text-xl shadow-xs">
                💺
              </div>
              <div>
                <h3 class="font-black text-base text-slate-900 mb-1.5">
                  Cam kết giữ đúng chỗ
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
                  Cam kết giữ đúng 100% vị trí giường/ghế đã chọn, không nhồi nhét, không đón khách dọc đường.
                </p>
              </div>
            </div>
            <div class="pt-4 mt-2">
              <a href="#faq-accordion-section" class="text-xs font-bold text-[#0284c7] hover:text-[#0369a1] inline-flex items-center gap-1 group">
                <span>Tìm hiểu thêm</span>
                <span class="group-hover:translate-x-1 transition-transform">➔</span>
              </a>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ==================== KHỐI 3: NHAXEKIENTRUC LINES - CHẤT LƯỢNG LÀ DANH DỰ (NỀN TRẮNG) ==================== -->
    <section class="w-full bg-white py-14">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center max-w-3xl mx-auto mb-10">
          <h2 class="text-2xl sm:text-3xl font-black text-[#006039] tracking-tight uppercase">
            nhaxekientruc LINES - CHẤT LƯỢNG LÀ DANH DỰ
          </h2>
          <p class="text-xs sm:text-sm text-slate-600 font-bold mt-1">
            25 Năm Vững Tin & Phát Triển • Hệ Thống Đặt Vé Xe Hàng Đầu Miền Trung
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          <div class="lg:col-span-7 space-y-5">
            <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:border-red-200 transition">
              <div class="w-13 h-13 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-2xl flex-shrink-0 shadow-xs">
                👥
              </div>
              <div>
                <h3 class="text-lg sm:text-xl font-black text-slate-900 flex items-baseline gap-1.5">
                  <span>Hơn 40 Triệu</span>
                  <span class="text-xs font-bold text-slate-500 uppercase">Lượt khách</span>
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 font-medium mt-0.5 leading-relaxed">
                  nhaxekientruc phục vụ hơn 40 triệu lượt khách bình quân 1 năm trên toàn quốc, kết nối trọn vẹn hành trình an toàn.
                </p>
              </div>
            </div>

            <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:border-amber-200 transition">
              <div class="w-13 h-13 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl flex-shrink-0 shadow-xs">
                📍
              </div>
              <div>
                <h3 class="text-lg sm:text-xl font-black text-slate-900 flex items-baseline gap-1.5">
                  <span>Hơn 350</span>
                  <span class="text-xs font-bold text-slate-500 uppercase">Phòng vé - Bưu cục</span>
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 font-medium mt-0.5 leading-relaxed">
                  Hệ thống hơn 350 phòng vé, trạm trung chuyển, bến xe hiện đại trải dài khắp các tỉnh thành cả nước.
                </p>
              </div>
            </div>

            <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:border-emerald-200 transition">
              <div class="w-13 h-13 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl flex-shrink-0 shadow-xs">
                🚌
              </div>
              <div>
                <h3 class="text-lg sm:text-xl font-black text-slate-900 flex items-baseline gap-1.5">
                  <span>Hơn 6,500</span>
                  <span class="text-xs font-bold text-slate-500 uppercase">Chuyến xe</span>
                </h3>
                <p class="text-xs sm:text-sm text-slate-600 font-medium mt-0.5 leading-relaxed">
                  Vận hành hơn 6,500 chuyến xe đường dài và liên tỉnh mỗi ngày, đảm bảo đúng giờ và giữ 100% chỗ ngồi.
                </p>
              </div>
            </div>
          </div>

          <div class="lg:col-span-5 flex items-center justify-center">
            <div class="relative w-full max-w-md p-4 bg-slate-50 rounded-3xl border border-slate-100 shadow-xs">
              <div class="h-64 rounded-2xl overflow-hidden relative">
                <img
                  src="https://images.unsplash.com/photo-1517824806704-9040b037703b?w=700&auto=format&fit=crop&q=80"
                  alt="Hành trình du lịch"
                  class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent flex items-end p-5">
                  <div class="text-white">
                    <span class="text-[11px] font-bold bg-red-600 px-2.5 py-0.5 rounded-full uppercase">Đồng hành tin cậy</span>
                    <p class="text-sm font-black mt-1">Hành trình êm ái • Dịch vụ tận tâm</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ==================== KHỐI 4: TẠI SAO NÊN CHỌN NHAXEKIENTRUC? (5 THẺ CAO CẤP CHUẨN 100% THEO HÌNH MẪU) ==================== -->
    <section class="w-full bg-[#fff5f4] border-y border-[#ffe4e1] py-14">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
          <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            Tại sao nên chọn nhaxekientruc?
          </h2>
          <p class="text-xs text-slate-500 font-medium mt-0.5">
            Trải nghiệm nền tảng đặt vé xe công nghệ thông minh, tiện lợi và minh bạch nhất hiện nay
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-5">
          
          <!-- Card 1: Giá và ưu đãi độc quyền tại nhaxekientruc -->
          <div class="rounded-[28px] overflow-hidden bg-gradient-to-b from-[#cbf4f8] via-[#ddf9fc] to-[#eefcfe] border border-[#aee8ef]/80 shadow-sm flex flex-col justify-between hover:-translate-y-1.5 transition-all duration-300 group h-[380px] sm:h-[400px]">
            <div class="p-5 space-y-1.5 z-10">
              <h3 class="font-black text-base text-slate-900 leading-tight">
                Giá và ưu đãi độc quyền tại nhaxekientruc
              </h3>
              <p class="text-xs text-slate-600 leading-relaxed font-medium">
                Nhận ưu đãi chỉ có tại nhaxekientruc
              </p>
            </div>
            
            <div class="relative w-full h-52 sm:h-56 overflow-hidden flex items-end justify-center">
              <img
                src="/images/why_choose/why_1.jpg"
                alt="Giá và ưu đãi độc quyền"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500"
                style="mask-image: linear-gradient(to top, black 80%, transparent 100%); -webkit-mask-image: linear-gradient(to top, black 80%, transparent 100%);"
              />
            </div>
          </div>

          <!-- Card 2: Mạng lưới xe buýt phủ rộng -->
          <div class="rounded-[28px] overflow-hidden bg-gradient-to-b from-[#d5edfe] via-[#e5f4fe] to-[#f4faff] border border-[#bcdffb]/80 shadow-sm flex flex-col justify-between hover:-translate-y-1.5 transition-all duration-300 group h-[380px] sm:h-[400px]">
            <div class="p-5 space-y-1.5 z-10">
              <h3 class="font-black text-base text-slate-900 leading-tight">
                Mạng lưới xe buýt phủ rộng
              </h3>
              <p class="text-xs text-slate-600 leading-relaxed font-medium">
                Di chuyển êm ái, thuận lợi từ Bắc vào Nam
              </p>
            </div>
            
            <div class="relative w-full h-52 sm:h-56 overflow-hidden flex items-end justify-center">
              <img
                src="/images/why_choose/why_2.jpg"
                alt="Mạng lưới xe buýt phủ rộng"
                class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
                style="mask-image: linear-gradient(to top, black 80%, transparent 100%); -webkit-mask-image: linear-gradient(to top, black 80%, transparent 100%);"
              />
            </div>
          </div>

          <!-- Card 3: Mạng lưới nhà xe lớn nhất -->
          <div class="rounded-[28px] overflow-hidden bg-gradient-to-b from-[#cef6fa] via-[#e2fafc] to-[#f2fcfd] border border-[#b2eef4]/80 shadow-sm flex flex-col justify-between hover:-translate-y-1.5 transition-all duration-300 group h-[380px] sm:h-[400px]">
            <div class="p-5 space-y-1.5 z-10">
              <h3 class="font-black text-base text-slate-900 leading-tight">
                Mạng lưới nhà xe lớn nhất
              </h3>
              <p class="text-xs text-slate-600 leading-relaxed font-medium">
                Các nhà xe nổi bật như Futa, Long Vân, Hoàng Long cùng nhiều nhà xe danh tiếng
              </p>
            </div>
            
            <div class="relative w-full h-52 sm:h-56 overflow-hidden flex items-end justify-center">
              <img
                src="/images/why_choose/why_3.jpg"
                alt="Mạng lưới nhà xe lớn nhất"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500"
                style="mask-image: linear-gradient(to top, black 80%, transparent 100%); -webkit-mask-image: linear-gradient(to top, black 80%, transparent 100%);"
              />
            </div>
          </div>

          <!-- Card 4: Chăm sóc khách hàng nhanh và đáng tin cậy -->
          <div class="rounded-[28px] overflow-hidden bg-gradient-to-b from-[#ffe0e4] via-[#ffedf0] to-[#fff7f8] border border-[#fccad1]/80 shadow-sm flex flex-col justify-between hover:-translate-y-1.5 transition-all duration-300 group h-[380px] sm:h-[400px]">
            <div class="p-5 space-y-1.5 z-10">
              <h3 class="font-black text-base text-slate-900 leading-tight">
                Chăm sóc khách hàng nhanh và đáng tin cậy
              </h3>
              <p class="text-xs text-slate-600 leading-relaxed font-medium">
                Liên hệ qua điện thoại, email, zalo
              </p>
            </div>
            
            <div class="relative w-full h-52 sm:h-56 overflow-hidden flex items-end justify-center">
              <img
                src="/images/why_choose/why_4.jpg"
                alt="Chăm sóc khách hàng tận tâm"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500"
                style="mask-image: linear-gradient(to top, black 80%, transparent 100%); -webkit-mask-image: linear-gradient(to top, black 80%, transparent 100%);"
              />
            </div>
          </div>

          <!-- Card 5: Đa dạng phương thức thanh toán -->
          <div class="rounded-[28px] overflow-hidden bg-gradient-to-b from-[#eee5fa] via-[#f5effd] to-[#faf7fe] border border-[#ded0f6]/80 shadow-sm flex flex-col justify-between hover:-translate-y-1.5 transition-all duration-300 group h-[380px] sm:h-[400px]">
            <div class="p-5 space-y-1.5 z-10">
              <h3 class="font-black text-base text-slate-900 leading-tight">
                Đa dạng phương thức thanh toán
              </h3>
              <p class="text-xs text-slate-600 leading-relaxed font-medium">
                Thanh toán an toàn qua VietQR, VNPay, MomoPay và nhiều phương thức
              </p>
            </div>
            
            <div class="relative w-full h-52 sm:h-56 overflow-hidden flex items-end justify-center">
              <img
                src="/images/why_choose/why_5.jpg"
                alt="Đa dạng thanh toán"
                class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500"
                style="mask-image: linear-gradient(to top, black 80%, transparent 100%); -webkit-mask-image: linear-gradient(to top, black 80%, transparent 100%);"
              />
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- ==================== KHỐI 5: TIN TỨC MỚI (NỀN TRẮNG) ==================== -->
    <section class="w-full bg-white py-14">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-8">
          <h2 class="text-2xl sm:text-3xl font-black text-[#006039] uppercase tracking-tight">
            TIN TỨC MỚI
          </h2>
          <div class="flex items-center justify-between mt-1">
            <p class="text-xs text-slate-500 font-medium">Cập nhật những thông tin mới từ nhaxekientruc</p>
            <a href="#" class="text-xs font-bold text-red-600 hover:underline">Xem tất cả &gt;</a>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-xs hover:shadow-md transition group cursor-pointer flex flex-col justify-between">
            <div class="h-44 overflow-hidden bg-slate-100">
              <img
                src="https://images.unsplash.com/photo-1574375927938-d5a98e8ffe85?w=600&auto=format&fit=crop&q=80"
                alt="Chủ động hành trình"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
            </div>
            <div class="p-4 space-y-2">
              <h3 class="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-red-600 transition uppercase line-clamp-2">
                CHỦ ĐỘNG HÀNH TRÌNH, AN TÂM GIỮ CHỖ CÙNG APP nhaxekientruc
              </h3>
              <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-100">
                <span>🕒 18/09/2026</span>
                <span class="text-red-600 font-bold">Chi tiết &gt;</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-xs hover:shadow-md transition group cursor-pointer flex flex-col justify-between">
            <div class="h-44 overflow-hidden bg-slate-100">
              <img
                src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=600&auto=format&fit=crop&q=80"
                alt="Mở tuyến mới"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
            </div>
            <div class="p-4 space-y-2">
              <h3 class="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-red-600 transition uppercase line-clamp-2">
                ĐÀ NẴNG - QUY NHƠN - TÂY NGUYÊN: MỞ TUYẾN MỚI THÊM TIỆN ÍCH
              </h3>
              <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-100">
                <span>🕒 15/09/2026</span>
                <span class="text-red-600 font-bold">Chi tiết &gt;</span>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-xs hover:shadow-md transition group cursor-pointer flex flex-col justify-between">
            <div class="h-44 overflow-hidden bg-slate-100">
              <img
                src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=600&auto=format&fit=crop&q=80"
                alt="Thông báo lịch xe"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              />
            </div>
            <div class="p-4 space-y-2">
              <h3 class="font-bold text-xs sm:text-sm text-slate-900 group-hover:text-red-600 transition uppercase line-clamp-2">
                TĂNG CƯỜNG 100 CHUYẾN XE PHỤC VỤ HÀNH KHÁCH CAO ĐIỂM DỊP LỄ
              </h3>
              <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-100">
                <span>🕒 25/08/2026</span>
                <span class="text-red-600 font-bold">Chi tiết &gt;</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ==================== KHỐI 6: KHÁCH HÀNG NÓI GÌ VỀ CHÚNG TÔI (NỀN HỒNG PASTEL - TÁCH RIÊNG ĐỘC LẬP) ==================== -->
    <section class="w-full bg-[#fff5f4] border-y border-[#ffe4e1] py-14">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center sm:text-left mb-8">
          <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            Khách hàng nói gì về chúng tôi
          </h2>
          <p class="text-xs text-slate-500 font-medium mt-0.5">
            Những trải nghiệm thực tế từ hành khách đã đặt vé và đồng hành cùng nhaxekientruc
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="bg-white rounded-3xl p-6 border border-red-100 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
            <div class="space-y-3">
              <div class="text-3xl font-black text-emerald-400 font-serif leading-none select-none">““</div>
              <p class="text-xs sm:text-sm text-slate-700 leading-relaxed italic">
                "Ứng dụng rất hữu ích. Mình hay đi du lịch lên đây có đủ các nhà xe mà giá còn rẻ hơn giá của nhà xe vì có nhiều mã giảm giá. Thông tin và hỗ trợ khách hàng rất tốt."
              </p>
            </div>
            <div class="pt-5 mt-4 border-t border-slate-100">
              <div class="font-bold text-sm text-slate-900">Eric</div>
              <div class="text-[11px] text-slate-500 font-medium">Đã đặt 3 chuyến đi với nhaxekientruc</div>
            </div>
          </div>

          <div class="bg-white rounded-3xl p-6 border border-red-100 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
            <div class="space-y-3">
              <div class="text-3xl font-black text-emerald-400 font-serif leading-none select-none">““</div>
              <p class="text-xs sm:text-sm text-slate-700 leading-relaxed italic">
                "Thì việc đặt vé ở trên nhaxekientruc khá là tiện lợi và dễ dàng. Nó cũng rất là minh bạch về giá cả lẫn sơ đồ chỗ ngồi, mọi thứ có rất là nhiều lựa chọn từ khung giờ cho đến hãng xe."
              </p>
            </div>
            <div class="pt-5 mt-4 border-t border-slate-100">
              <div class="font-bold text-slate-900 text-sm">Nguyen Thy</div>
              <div class="text-[11px] text-slate-500 font-medium">Đã đặt 2 chuyến đi với nhaxekientruc</div>
            </div>
          </div>

          <div class="bg-white rounded-3xl p-6 border border-red-100 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
            <div class="space-y-3">
              <div class="text-3xl font-black text-emerald-400 font-serif leading-none select-none">““</div>
              <p class="text-xs sm:text-sm text-slate-700 leading-relaxed italic">
                "Dạ em thấy dễ dùng, đặt với nhận vé nhanh chóng. Lúc ra nhà xe, không cần gọi ra để lấy vé cứng nữa. Em hay quên nên xài vé điện tử quét mã thẳng qua điện thoại rất tiện ạ."
              </p>
            </div>
            <div class="pt-5 mt-4 border-t border-slate-100">
              <div class="font-bold text-slate-900 text-sm">Lưu Ly</div>
              <div class="text-[11px] text-slate-500 font-medium">Đã đặt 5 chuyến đi với nhaxekientruc</div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ==================== KHỐI 7: HƯỚNG DẪN ĐẶT VÉ & FAQ & CỔNG THANH TOÁN (NỀN TRẮNG - TÁCH RIÊNG ĐỘC LẬP) ==================== -->
    <section id="faq-accordion-section" class="w-full bg-white py-14">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xs">
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Cột Trái: Hướng Dẫn Đặt Vé 6 Bước & Phương Thức Thanh Toán -->
            <div class="lg:col-span-6 space-y-6">
              <div>
                <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                  Hướng dẫn đặt vé xe online trên nhaxekientruc
                </h3>
                <p class="text-xs text-slate-500 mt-0.5 font-medium">Quy trình đặt vé tự động hóa chỉ với 6 bước đơn giản</p>
              </div>

              <div class="space-y-3 text-xs sm:text-sm text-slate-700">
                <div class="flex items-start gap-3">
                  <span class="font-black text-slate-900 whitespace-nowrap text-xs bg-slate-100 px-2 py-0.5 rounded">Bước 1:</span>
                  <p class="leading-relaxed">Tải xuống ứng dụng nhaxekientruc hoặc truy cập trang web đặt vé xe khách.</p>
                </div>
                <div class="flex items-start gap-3">
                  <span class="font-black text-slate-900 whitespace-nowrap text-xs bg-slate-100 px-2 py-0.5 rounded">Bước 2:</span>
                  <p class="leading-relaxed">Nhập điểm khởi hành (Đà Nẵng), điểm đến và ngày đi, sau đó nhấp vào <strong>'Tìm kiếm xe khách'</strong>.</p>
                </div>
                <div class="flex items-start gap-3">
                  <span class="font-black text-slate-900 whitespace-nowrap text-xs bg-slate-100 px-2 py-0.5 rounded">Bước 3:</span>
                  <p class="leading-relaxed">Chọn nhà xe có giờ khởi hành và lịch trình phù hợp. Bấm chọn vào khung giờ muốn đi để tiến hành đặt vé.</p>
                </div>
                <div class="flex items-start gap-3">
                  <span class="font-black text-slate-900 whitespace-nowrap text-xs bg-slate-100 px-2 py-0.5 rounded">Bước 4:</span>
                  <p class="leading-relaxed">Chọn chỗ ngồi, điểm đón, điểm trả khách và điền thông tin hành khách.</p>
                </div>
                <div class="flex items-start gap-3">
                  <span class="font-black text-slate-900 whitespace-nowrap text-xs bg-slate-100 px-2 py-0.5 rounded">Bước 5:</span>
                  <p class="leading-relaxed">Chọn phương thức thanh toán phù hợp và tiến hành thanh toán vé. Nhập mã khuyến mãi (nếu có) để tiết kiệm.</p>
                </div>
                <div class="flex items-start gap-3">
                  <span class="font-black text-slate-900 whitespace-nowrap text-xs bg-slate-100 px-2 py-0.5 rounded">Bước 6:</span>
                  <p class="leading-relaxed">Vé xe điện tử (Mã QR) sẽ được gửi đi email hoặc SMS tới số điện thoại đã đăng ký của bạn.</p>
                </div>
              </div>

              <!-- Phương Thức Thanh Toán -->
              <div class="pt-4 border-t border-slate-100">
                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-3">
                  Phương Thức Thanh Toán
                </h4>
                <div class="flex flex-wrap items-center gap-2.5">
                  <span class="px-3 py-1.5 rounded-xl bg-[#d82d8b]/10 text-[#d82d8b] font-black text-xs border border-[#d82d8b]/20">
                    MoMo
                  </span>
                  <span class="px-3 py-1.5 rounded-xl bg-[#0068ff]/10 text-[#0068ff] font-black text-xs border border-[#0068ff]/20">
                    ZaloPay
                  </span>
                  <span class="px-3 py-1.5 rounded-xl bg-[#ed1c24]/10 text-[#ed1c24] font-black text-xs border border-[#ed1c24]/20">
                    VNPAY
                  </span>
                  <span class="px-3 py-1.5 rounded-xl bg-[#004a99]/10 text-[#004a99] font-black text-xs border border-[#004a99]/20">
                    MB Bank
                  </span>
                  <span class="px-3 py-1.5 rounded-xl bg-[#005f37]/10 text-[#005f37] font-black text-xs border border-[#005f37]/20">
                    Vietcombank
                  </span>
                </div>
              </div>
            </div>

            <!-- Cột Phải: Câu Hỏi Thường Gặp (FAQ Accordion) -->
            <div class="lg:col-span-6 space-y-4">
              <div>
                <h3 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                  Câu hỏi thường gặp
                </h3>
                <p class="text-xs text-slate-500 mt-0.5 font-medium">Giải đáp thắc mắc phổ biến của hành khách</p>
              </div>

              <div class="space-y-3">
                <div class="border border-slate-200 rounded-2xl overflow-hidden transition-all">
                  <button
                    @click="toggleFaq(1)"
                    class="w-full text-left px-4 py-3.5 bg-slate-50 hover:bg-slate-100 flex items-center justify-between font-bold text-xs sm:text-sm text-slate-900 cursor-pointer"
                  >
                    <span>Mất bao lâu để đi từ TP. Đà Nẵng đi các tỉnh miền Trung bằng xe khách?</span>
                    <svg class="w-4 h-4 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': openFaq === 1 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                  <div v-if="openFaq === 1" class="p-4 bg-white text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                    Một chuyến xe khách từ Đà Nẵng đi Huế mất khoảng 2 giờ, đi Sóc Trăng / TP.HCM khoảng 14 - 18 giờ tùy theo lộ trình và điều kiện giao thông.
                  </div>
                </div>

                <div class="border border-slate-200 rounded-2xl overflow-hidden transition-all">
                  <button
                    @click="toggleFaq(2)"
                    class="w-full text-left px-4 py-3.5 bg-slate-50 hover:bg-slate-100 flex items-center justify-between font-bold text-xs sm:text-sm text-slate-900 cursor-pointer"
                  >
                    <span>Những loại xe khách nào có sẵn cho hành trình?</span>
                    <svg class="w-4 h-4 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': openFaq === 2 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                  <div v-if="openFaq === 2" class="p-4 bg-white text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                    Hệ thống có sẵn các dòng xe cao cấp: Limousine VIP 22 phòng cung điện, Xe giường nằm 34 phòng và Xe ghế ngồi chất lượng cao.
                  </div>
                </div>

                <div class="border border-slate-200 rounded-2xl overflow-hidden transition-all">
                  <button
                    @click="toggleFaq(3)"
                    class="w-full text-left px-4 py-3.5 bg-slate-50 hover:bg-slate-100 flex items-center justify-between font-bold text-xs sm:text-sm text-slate-900 cursor-pointer"
                  >
                    <span>Có xe khách phục vụ hành trình qua đêm không?</span>
                    <svg class="w-4 h-4 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': openFaq === 3 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                  <div v-if="openFaq === 3" class="p-4 bg-white text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                    Có, các tuyến xe đường dài luôn có chuyến xuất bến buổi tối từ 18:30 - 21:00 để khách hàng ngủ một giấc và đến nơi vào sáng sớm.
                  </div>
                </div>

                <div class="border border-slate-200 rounded-2xl overflow-hidden transition-all">
                  <button
                    @click="toggleFaq(4)"
                    class="w-full text-left px-4 py-3.5 bg-slate-50 hover:bg-slate-100 flex items-center justify-between font-bold text-xs sm:text-sm text-slate-900 cursor-pointer"
                  >
                    <span>Tôi có thể đặt vé xe trực tuyến trước không?</span>
                    <svg class="w-4 h-4 text-slate-500 transition-transform duration-200" :class="{ 'rotate-180': openFaq === 4 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                  <div v-if="openFaq === 4" class="p-4 bg-white text-xs text-slate-600 leading-relaxed border-t border-slate-100">
                    Có, bạn có thể đặt vé trực tuyến trước từ 1 giờ đến 30 ngày để giữ được vị trí ghế đẹp và hưởng các mã ưu đãi giảm giá tốt nhất.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>

    <!-- ==================== POPUP CHÀO MỪNG TẶNG MÃ VOUCHER (SHOPEE STYLE) ==================== -->
    <Teleport to="body">
      <div
        v-if="showWelcomeVoucherModal"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in"
      >
        <div class="fixed inset-0" @click="closeWelcomeModal"></div>
        <div
          class="relative bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 z-10 animate-in zoom-in-95 duration-200 p-6 sm:p-7 text-center space-y-4"
        >
          <!-- Nút Đóng -->
          <button
            type="button"
            @click="closeWelcomeModal"
            class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm cursor-pointer transition"
          >
            ✕
          </button>

          <!-- Icon & Header -->
          <div class="space-y-1.5 pt-1">
            <div class="w-14 h-14 mx-auto rounded-3xl flex items-center justify-center text-3xl shadow-md"
                 :class="welcomeVoucherType === 'STUDENT' ? 'bg-gradient-to-tr from-red-600 to-amber-500 text-white shadow-red-500/20' : 'bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-emerald-500/20'">
              {{ welcomeVoucherType === 'STUDENT' ? '🎓' : '🎉' }}
            </div>

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10.5px] font-black uppercase tracking-wider"
                 :class="welcomeVoucherType === 'STUDENT' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
              <span>{{ welcomeVoucherType === 'STUDENT' ? '⭐ ĐẶC QUYỀN SINH VIÊN' : '⭐ THÀNH VIÊN MỚI' }}</span>
            </div>

            <h3 class="text-xl font-black text-slate-900 leading-tight">
              {{ welcomeVoucherType === 'STUDENT' ? 'CHÀO MỪNG BẠN SINH VIÊN!' : 'CHÀO MỪNG BẠN MỚI GIA NHẬP!' }}
            </h3>
            <p class="text-xs text-slate-600 leading-relaxed">
              {{ welcomeVoucherType === 'STUDENT' 
                ? 'Chúc mừng bạn đã kích hoạt tài khoản Sinh Viên! Tặng bạn Bộ Đôi Voucher Độc Quyền: 1 Vé Giảm 100% (0đ) cho chuyến đầu tiên & Voucher Giảm 30% xài suốt 1 năm!' 
                : 'Chúc mừng bạn đã tạo tài khoản thành công! Tặng bạn Gói Ưu Đãi Thành Viên Mới giảm 10% cho lần đầu đặt vé.' }}
            </p>
          </div>

          <!-- Shopee-style Ticket Cards Container -->
          <div v-if="welcomeVoucherType === 'STUDENT'" class="space-y-3">
            <!-- VOUCHER 1: GIẢM 100% LẦN ĐẦU -->
            <div class="rounded-2xl border-2 border-red-500 bg-red-50/50 overflow-hidden flex items-stretch shadow-md text-left relative">
              <!-- Left Ticket Stub -->
              <div class="p-3 text-white flex flex-col items-center justify-center text-center shrink-0 w-24 sm:w-28 relative overflow-hidden bg-gradient-to-br from-red-600 to-rose-600">
                <span class="text-[11px] font-bold uppercase tracking-wider opacity-90">GIẢM</span>
                <span class="text-2xl sm:text-3xl font-black leading-none my-0.5">100%</span>
                <span class="text-[9px] font-bold bg-white/20 px-1.5 py-0.5 rounded-full mt-0.5">LẦN ĐẦU TIÊN</span>
              </div>

              <!-- Perforated vertical line divider -->
              <div class="w-0 border-r-2 border-dashed border-red-300 relative flex flex-col justify-between">
                <div class="w-3 h-3 rounded-full bg-white -ml-1.5 -mt-1.5 border border-red-300"></div>
                <div class="w-3 h-3 rounded-full bg-white -ml-1.5 -mb-1.5 border border-red-300"></div>
              </div>

              <!-- Right Ticket Details -->
              <div class="p-3 sm:p-3.5 flex-1 bg-white flex flex-col justify-between">
                <div>
                  <div class="flex items-center justify-between gap-1">
                    <span class="font-mono font-black text-sm tracking-wider text-red-600">SINHVIEN100</span>
                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-red-100 text-red-700">
                      Vé 0-Đồng
                    </span>
                  </div>
                  <p class="text-[11px] font-bold text-slate-700 mt-1 leading-snug">
                    Miễn phí 100% chuyến xe đầu tiên dành cho sinh viên
                  </p>
                  <p class="text-[10px] text-slate-400 mt-0.5">
                    🧳 Miễn phí 100% hành lý ký gửi (20kg) • Áp dụng 1 lần duy nhất
                  </p>
                </div>

                <div class="pt-2 flex justify-end">
                  <button
                    type="button"
                    @click="saveVoucherWithToast('SINHVIEN100')"
                    class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-xs"
                    :class="isVoucherSaved('SINHVIEN100')
                      ? 'bg-slate-100 text-slate-600 border border-slate-300 cursor-default'
                      : 'bg-red-600 hover:bg-red-700 text-white'"
                  >
                    <span>{{ isVoucherSaved('SINHVIEN100') ? '✓ Đã Lưu Vào Ví' : 'Lưu Mã Vào Ví' }}</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- VOUCHER 2: GIẢM 30% HẠN 1 NĂM -->
            <div class="rounded-2xl border-2 border-amber-500 bg-amber-50/50 overflow-hidden flex items-stretch shadow-md text-left relative">
              <!-- Left Ticket Stub -->
              <div class="p-3 text-white flex flex-col items-center justify-center text-center shrink-0 w-24 sm:w-28 relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600">
                <span class="text-[11px] font-bold uppercase tracking-wider opacity-90">GIẢM</span>
                <span class="text-2xl sm:text-3xl font-black leading-none my-0.5">30%</span>
                <span class="text-[9px] font-bold bg-white/20 px-1.5 py-0.5 rounded-full mt-0.5">HẠN 1 NĂM</span>
              </div>

              <!-- Perforated vertical line divider -->
              <div class="w-0 border-r-2 border-dashed border-amber-300 relative flex flex-col justify-between">
                <div class="w-3 h-3 rounded-full bg-white -ml-1.5 -mt-1.5 border border-amber-300"></div>
                <div class="w-3 h-3 rounded-full bg-white -ml-1.5 -mb-1.5 border border-amber-300"></div>
              </div>

              <!-- Right Ticket Details -->
              <div class="p-3 sm:p-3.5 flex-1 bg-white flex flex-col justify-between">
                <div>
                  <div class="flex items-center justify-between gap-1">
                    <span class="font-mono font-black text-sm tracking-wider text-amber-600">SINHVIEN30</span>
                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-700">
                      Xài Cả Năm
                    </span>
                  </div>
                  <p class="text-[11px] font-bold text-slate-700 mt-1 leading-snug">
                    Giảm 30% giá vé xe xuyên suốt 1 năm cho mọi chuyến đi
                  </p>
                  <p class="text-[10px] text-slate-400 mt-0.5">
                    🎓 Áp dụng không giới hạn lượt đi trong suốt 365 ngày
                  </p>
                </div>

                <div class="pt-2 flex justify-end">
                  <button
                    type="button"
                    @click="saveVoucherWithToast('SINHVIEN30')"
                    class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-xs"
                    :class="isVoucherSaved('SINHVIEN30')
                      ? 'bg-slate-100 text-slate-600 border border-slate-300 cursor-default'
                      : 'bg-amber-600 hover:bg-amber-700 text-white'"
                  >
                    <span>{{ isVoucherSaved('SINHVIEN30') ? '✓ Đã Lưu Vào Ví' : 'Lưu Mã Vào Ví' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Shopee-style Ticket Card (Cho Khách Mới Thông Thường) -->
          <div v-else class="rounded-2xl border-2 border-emerald-500 bg-emerald-50/50 overflow-hidden flex items-stretch shadow-md text-left relative">
            <!-- Left Ticket Stub -->
            <div class="p-3.5 sm:p-4 text-white flex flex-col items-center justify-center text-center shrink-0 w-24 sm:w-28 relative overflow-hidden bg-gradient-to-br from-emerald-600 to-teal-600">
              <span class="text-xs font-bold uppercase tracking-wider opacity-90">GIẢM</span>
              <span class="text-2xl sm:text-3xl font-black leading-none my-1">10%</span>
              <span class="text-[9px] font-medium opacity-90">LẦN ĐẦU ĐI</span>
            </div>

            <!-- Perforated vertical line divider -->
            <div class="w-0 border-r-2 border-dashed border-slate-300 relative flex flex-col justify-between">
              <div class="w-3 h-3 rounded-full bg-white -ml-1.5 -mt-1.5 border border-slate-300"></div>
              <div class="w-3 h-3 rounded-full bg-white -ml-1.5 -mb-1.5 border border-slate-300"></div>
            </div>

            <!-- Right Ticket Details -->
            <div class="p-3 sm:p-4 flex-1 bg-white flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between gap-1">
                  <span class="font-mono font-black text-sm tracking-wider text-emerald-700">CHAOBAOMOI</span>
                  <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700">
                    Khách Mới
                  </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-1 leading-snug">
                  Dành cho khách hàng mới đặt vé lần đầu tại nhaxekientruc
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5">
                  🧳 Miễn phí 100% hành lý ký gửi & xách tay (20kg)
                </p>
              </div>

              <!-- Button Lưu Mã Vào Ví (Shopee style) -->
              <div class="pt-2 flex justify-end">
                <button
                  type="button"
                  @click="saveVoucherWithToast('CHAOBAOMOI')"
                  class="px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1 shadow-xs"
                  :class="isVoucherSaved('CHAOBAOMOI')
                    ? 'bg-slate-100 text-slate-600 border border-slate-300 cursor-default'
                    : 'bg-emerald-600 hover:bg-emerald-700 text-white'"
                >
                  <span>{{ isVoucherSaved('CHAOBAOMOI') ? '✓ Đã Lưu Vào Ví' : 'Lưu Mã Vào Ví' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="space-y-2 pt-2">
            <button
              type="button"
              @click="useVoucherNow"
              class="w-full py-3.5 px-4 rounded-2xl text-white font-black text-xs sm:text-sm shadow-md transition flex items-center justify-center gap-2 cursor-pointer active:scale-98"
              :class="welcomeVoucherType === 'STUDENT' ? 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-red-600/30' : 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-emerald-600/30'"
            >
              <span>🎟️ ĐẶT VÉ DÙNG MÃ NGAY</span>
              <span>➔</span>
            </button>
            <button
              type="button"
              @click="closeWelcomeModal"
              class="text-xs font-bold text-slate-400 hover:text-slate-600 transition cursor-pointer block mx-auto pt-1"
            >
              Để sau
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <Footer />
    <AiChatModal />

    <!-- Toast Thông Báo Khi Lưu Mã Thành Công -->
    <Teleport to="body">
      <div 
        v-if="showVoucherToast"
        class="fixed bottom-6 right-6 z-[99999] bg-slate-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl border border-slate-700 flex items-center gap-3 animate-in fade-in slide-in-from-bottom-4 duration-300 max-w-md"
      >
        <span class="text-xl">🎟️</span>
        <div class="text-xs font-bold text-slate-100 leading-snug">
          {{ voucherToastMsg }}
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import SearchWidget from '@/Components/SearchWidget.vue';
import AiChatModal from '@/Components/AiChatModal.vue';

const page = usePage();

const props = defineProps({
  routes: Array,
  destinations_today: Array,
  featured_trips: Array,
});

const showWelcomeVoucherModal = ref(false);
const welcomeVoucherType = ref('NORMAL'); // 'STUDENT' | 'NORMAL'
const savedVouchers = ref([]);

const showVoucherToast = ref(false);
const voucherToastMsg = ref('');

const initSavedVouchers = () => {
  try {
    savedVouchers.value = JSON.parse(localStorage.getItem('nhaxekientruc_saved_vouchers') || '[]');
  } catch (e) {
    savedVouchers.value = [];
  }
};

const saveWelcomeVoucher = (code) => {
  if (!savedVouchers.value.includes(code)) {
    savedVouchers.value.push(code);
    try {
      localStorage.setItem('nhaxekientruc_saved_vouchers', JSON.stringify(savedVouchers.value));
    } catch (e) {}
  }
};

const saveVoucherWithToast = (code) => {
  saveWelcomeVoucher(code);
  voucherToastMsg.value = `✓ Đã lưu thành công mã [${code}] vào Ví Voucher của bạn! Khi đặt vé, mã sẽ tự động xuất hiện trong kho ưu đãi.`;
  showVoucherToast.value = true;
  setTimeout(() => {
    showVoucherToast.value = false;
  }, 4000);
};

const isVoucherSaved = (code) => {
  return savedVouchers.value.includes(code);
};

const closeWelcomeModal = () => {
  showWelcomeVoucherModal.value = false;
};

const useVoucherNow = () => {
  if (welcomeVoucherType.value === 'STUDENT') {
    saveWelcomeVoucher('SINHVIEN100');
    saveWelcomeVoucher('SINHVIEN30');
  } else {
    saveWelcomeVoucher('CHAOBAOMOI');
  }
  showWelcomeVoucherModal.value = false;
  window.scrollTo({ top: 300, behavior: 'smooth' });
};

onMounted(() => {
  initSavedVouchers();

  const user = page.props.auth?.user;
  const shouldShow = localStorage.getItem('nhaxekientruc_show_welcome_modal') === '1';

  if (user) {
    const email = (user.email || '').toLowerCase();
    const isStudent = email.includes('.edu.vn') || email.includes('sinhvien') || !!user.is_student;
    welcomeVoucherType.value = isStudent ? 'STUDENT' : 'NORMAL';

    const sawKey = 'nhaxekientruc_saw_welcome_' + user.id;
    const hasSeen = localStorage.getItem(sawKey) === '1';

    if (shouldShow || !hasSeen) {
      showWelcomeVoucherModal.value = true;
      localStorage.removeItem('nhaxekientruc_show_welcome_modal');
      localStorage.setItem(sawKey, '1');
    }
  }
});

const routesContainer = ref(null);
const openFaq = ref(1);

const toggleFaq = (idx) => {
  openFaq.value = openFaq.value === idx ? 0 : idx;
};

const copyPromoCode = (code) => {
  navigator.clipboard?.writeText(code);
  alert(`Đã sao chép mã ưu đãi: ${code}! Hãy nhập mã này khi thanh toán vé.`);
};

const popularRoutesList = ref([
  {
    title: 'Đà Nẵng ⇄ Thừa Thiên Huế',
    origin: 'Đà Nẵng',
    destination: 'Thừa Thiên Huế',
    distance: '100km',
    duration: '2.0 giờ',
    tripsCount: 12,
    price: 120000,
    image: 'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=600&auto=format&fit=crop&q=80',
  },
  {
    title: 'Đà Nẵng ⇄ Quảng Trị (Đông Hà)',
    origin: 'Đà Nẵng',
    destination: 'Quảng Trị',
    distance: '175km',
    duration: '3.5 giờ',
    tripsCount: 8,
    price: 140000,
    image: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=600&auto=format&fit=crop&q=80',
  },
  {
    title: 'Đà Nẵng ⇄ Quy Nhơn (Bình Định)',
    origin: 'Đà Nẵng',
    destination: 'Quy Nhơn',
    distance: '315km',
    duration: '6.0 giờ',
    tripsCount: 6,
    price: 240000,
    image: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=600&auto=format&fit=crop&q=80',
  },
  {
    title: 'Đà Nẵng ⇄ Buôn Ma Thuột (Đắk Lắk)',
    origin: 'Đà Nẵng',
    destination: 'Buôn Ma Thuột',
    distance: '540km',
    duration: '10.5 giờ',
    tripsCount: 4,
    price: 380000,
    image: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&auto=format&fit=crop&q=80',
  },
]);

const scrollRoutes = (dir) => {
  if (routesContainer.value) {
    const scrollAmount = dir === 'left' ? -340 : 340;
    routesContainer.value.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  }
};

const selectRoute = (item) => {
  const now = new Date();
  // Nếu đã qua 17:00 chiều và các chuyến trong ngày đã xuất bến, chọn ngày mai để khách bấm vào là luôn có xe thật sẵn sàng đặt!
  const isLate = now.getHours() >= 17;
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  const tomorrowStr = tomorrow.toISOString().split('T')[0];
  const todayStr = now.toISOString().split('T')[0];

  router.get('/trips', {
    origin: item.origin,
    destination: item.destination,
    date: isLate ? tomorrowStr : todayStr,
  });
};

const formatPrice = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>