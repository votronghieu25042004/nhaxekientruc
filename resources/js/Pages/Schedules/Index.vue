<template>
  <div class="min-h-screen flex flex-col bg-slate-50 text-slate-800 font-sans">
    <Navbar />

    <!-- ==================== TOP HERO BANNER (MATCHES IMAGE 2 EXACTLY) ==================== -->
    <section class="relative bg-white border-b border-slate-200/80 pt-6 pb-10 px-4 sm:px-6 lg:px-8 overflow-hidden">
      <!-- Ánh hồng / vàng cực nhẹ tạo chiều sâu cho nền trắng -->
      <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-red-50/80 blur-3xl pointer-events-none"></div>
      <div class="absolute top-12 left-1/3 w-72 h-72 rounded-full bg-amber-50/60 blur-3xl pointer-events-none"></div>

      <div class="relative z-10 max-w-7xl mx-auto space-y-5">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-xs font-semibold text-slate-400">
          <Link href="/" class="hover:text-red-600 transition flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Trang chủ
          </Link>
          <span class="text-slate-300">/</span>
          <span class="text-slate-600 font-bold">Lịch trình các tuyến xe cố định</span>
        </nav>

        <!-- 2-Column Hero: Left Intro & Badges | Right Mini Transit Card -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          <!-- Cột Trái: Tiêu đề & Giới thiệu -->
          <div class="lg:col-span-7 space-y-3.5">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-bold border border-rose-200 shadow-2xs">
              <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
              <span>10 Tuyến Cố Định • 100% Giờ Chạy Chuẩn Xác Hàng Ngày</span>
            </div>

            <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-slate-900 leading-tight">
              Lịch Trình Các Tuyến Xe Cố Định <span class="text-red-600">Xuất Bến Hàng Ngày</span>
            </h1>

            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal">
              Mạng lưới xe chất lượng cao xuất phát từ <strong>Bến xe Trung Tâm Đà Nẵng</strong> kết nối <strong>Thừa Thiên Huế, Quảng Trị, Quảng Bình, Quy Nhơn</strong> và <strong>Buôn Ma Thuột</strong>. Lịch trình công khai, tần suất đều đặn, hỗ trợ đặt vé online & giữ chỗ linh hoạt.
            </p>

            <!-- 3 Cam kết nổi bật dạng Pill nền trắng sạch sẽ -->
            <div class="pt-1 flex flex-wrap items-center gap-2.5 text-xs font-bold text-slate-700">
              <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-slate-50 border border-slate-200/90">
                <svg class="w-4 h-4 text-emerald-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 1.8 3-.2 1 2.8 2.6 1.6-.8 2.9.8 2.9-2.6 1.6-1 2.8-3-.2L12 22l-2.4-1.8-3 .2-1-2.8L3 16l.8-2.9L3 10.2l2.6-1.6 1-2.8 3 .2L12 2zm-1.2 13.6l5.6-5.6-1.4-1.4-4.2 4.2-2-2-1.4 1.4 3.4 3.4z"/></svg>
                <span>Giữ chỗ thật 100%</span>
              </div>
              <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-slate-50 border border-slate-200/90">
                <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="currentColor"><path d="M9 2h6a1 1 0 011 1v3h2a2 2 0 012 2v11a2 2 0 01-2 2h-1v1h-2v-1H9v1H7v-1H6a2 2 0 01-2-2V8a2 2 0 012-2h2V3a1 1 0 011-1zm1 2v2h4V4h-4zM8 10v7h2v-7H8zm6 0v7h2v-7h-2z"/></svg>
                <span>Miễn phí 20kg hành lý</span>
              </div>
              <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-slate-50 border border-slate-200/90">
                <svg class="w-4 h-4 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M15 3a4 4 0 110 8 4 4 0 010-8zM2 14h3l4 1.5h4a1.5 1.5 0 010 3H9.5l.3.8H14l5.2-2.6a1.6 1.6 0 011.5 2.8L14.5 22H8l-3-1.5H2V14z"/></svg>
                <span>Đặt cọc linh hoạt chỉ 30%</span>
              </div>
            </div>
          </div>

          <!-- Cột Phải (5/12): Thẻ Minh Họa Xe Khách Chạy Qua Cầu Rồng Ban Ngày (Sáng & Sang Trọng) -->
          <div class="lg:col-span-5 flex justify-center lg:justify-end">
            <div class="bg-gradient-to-b from-sky-50/80 via-white to-white rounded-3xl border border-slate-200/90 shadow-lg shadow-slate-900/5 p-5 relative overflow-hidden w-full max-w-md">
              
              <!-- Header nhỏ trên thẻ minh họa -->
              <div class="flex items-center justify-between gap-2 mb-3 relative z-10">
                <div class="flex items-center gap-2">
                  <span class="w-8 h-8 rounded-xl bg-red-50 text-[#DC2626] flex items-center justify-center font-bold shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 2L4.5 20.29l.71.71L12 18l6.79 3 .71-.71z"/>
                    </svg>
                  </span>
                  <div>
                    <span class="text-xs font-black text-slate-800 block">Trạm Xuất Phát Trung Tâm</span>
                    <span class="text-[11px] font-medium text-slate-500">BX Trung Tâm Đà Nẵng ⇄ Liên Tỉnh</span>
                  </div>
                </div>
                <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-extrabold whitespace-nowrap">
                  50 Chuyến / Ngày
                </span>
              </div>

              <!-- Khung cảnh Ban Ngày Cầu Rồng Đà Nẵng & Xe Khách 2 Tầng chạy êm ái -->
              <div class="relative h-32 rounded-2xl bg-gradient-to-b from-sky-100/70 via-sky-50/40 to-slate-100 border border-slate-200/70 overflow-hidden">
                <!-- Mặt trời & Mây trắng ban ngày -->
                <div class="absolute top-3 right-6 w-7 h-7 rounded-full bg-amber-300/80 shadow-md shadow-amber-200"></div>
                <div class="animate-cloud absolute top-3 left-8 px-3 py-1 rounded-full bg-white/90 text-[10px] font-bold text-sky-700 shadow-2xs">
                  ☁️ Đà Nẵng • Thời tiết đẹp
                </div>

                <!-- Cầu Rồng Đà Nẵng (Vector màu vàng ban ngày tinh tế) -->
                <svg class="absolute bottom-7 inset-x-0 w-full h-14 text-amber-400/75 pointer-events-none" viewBox="0 0 500 70" fill="none" preserveAspectRatio="none">
                  <path d="M 0 65 Q 60 20, 130 65 T 270 65 T 410 65 T 500 45" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                  <line x1="0" y1="66" x2="500" y2="66" stroke="#CBD5E1" stroke-width="3"/>
                </svg>

                <!-- Mặt đường sáng sạch -->
                <div class="absolute bottom-0 inset-x-0 h-8 bg-slate-200/90 border-t border-slate-300 flex items-center">
                  <div class="animate-road-light w-full h-0.5"></div>
                </div>

                <!-- Xe Khách Giường Nằm 2 Tầng NHAXEKIENTRUC lướt êm trên đường -->
                <div class="animate-bus-daylight absolute bottom-2 left-0 flex items-center z-20 pointer-events-none">
                  <svg class="w-40 h-14 drop-shadow-md" viewBox="0 0 220 74" fill="none">
                    <!-- Thân xe trắng-đỏ sang trọng -->
                    <rect x="8" y="10" width="196" height="48" rx="11" fill="#FFFFFF" stroke="#DC2626" stroke-width="2.5"/>
                    <!-- Dải sóng đỏ thương hiệu -->
                    <path d="M 8 42 Q 80 34, 204 44 L 204 50 Q 204 58, 195 58 L 18 58 Q 8 58, 8 49 Z" fill="#DC2626"/>
                    <!-- Cửa kính 2 tầng sáng thoáng -->
                    <rect x="20" y="15" width="34" height="10" rx="2.5" fill="#E0F2FE" stroke="#0284C7" stroke-width="1"/>
                    <rect x="58" y="15" width="34" height="10" rx="2.5" fill="#E0F2FE" stroke="#0284C7" stroke-width="1"/>
                    <rect x="96" y="15" width="34" height="10" rx="2.5" fill="#E0F2FE" stroke="#0284C7" stroke-width="1"/>
                    <rect x="134" y="15" width="34" height="10" rx="2.5" fill="#E0F2FE" stroke="#0284C7" stroke-width="1"/>
                    <!-- Kính lái phía trước -->
                    <path d="M 173 15 L 197 15 Q 202 15, 202 25 L 202 38 L 173 38 Z" fill="#BAE6FD" stroke="#0284C7" stroke-width="1"/>
                    <!-- Tên thương hiệu trên thân xe -->
                    <text x="22" y="36" fill="#DC2626" font-size="8.5" font-weight="900" letter-spacing="0.6" font-family="sans-serif">NHAXEKIENTRUC VIP</text>
                    <!-- Biển số vàng -->
                    <rect x="132" y="45" width="44" height="10" rx="2" fill="#FACC15"/>
                    <text x="136" y="52.5" fill="#0F172A" font-size="6.8" font-weight="900" font-family="monospace">43B-067.89</text>
                    <!-- 2 Bánh xe quay tròn -->
                    <g class="spin-wheel" style="transform-box: fill-box;">
                      <circle cx="48" cy="58" r="10" fill="#1E293B" stroke="#F8FAFC" stroke-width="2"/>
                      <circle cx="48" cy="58" r="4" fill="#94A3B8"/>
                    </g>
                    <g class="spin-wheel" style="transform-box: fill-box;">
                      <circle cx="164" cy="58" r="10" fill="#1E293B" stroke="#F8FAFC" stroke-width="2"/>
                      <circle cx="164" cy="58" r="4" fill="#94A3B8"/>
                    </g>
                  </svg>
                </div>
              </div>

              <!-- Hàng thông số nhanh dưới thẻ -->
              <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-slate-100 text-center">
                <div>
                  <span class="text-xs font-black text-slate-900 block">10 Tuyến</span>
                  <span class="text-[10px] font-semibold text-slate-400">2 Chiều Cố Định</span>
                </div>
                <div class="border-x border-slate-100">
                  <span class="text-xs font-black text-[#DC2626] block">140k – 320k</span>
                  <span class="text-[10px] font-semibold text-slate-400">Giá Niêm Yết</span>
                </div>
                <div>
                  <span class="text-xs font-black text-emerald-600 block">0đ Trung Chuyển</span>
                  <span class="text-[10px] font-semibold text-slate-400">Đón Trả Tận Nơi</span>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ==================== MAIN CONTENT ==================== -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1 w-full space-y-5">
      <!-- ==================== BỘ LỌC HỢP NHẤT: TỈNH/THÀNH + NGÀY + CHIỀU + TÌM KIẾM + CHẾ ĐỘ XEM ==================== -->
      <div class="bg-white rounded-3xl border border-slate-200/90 shadow-[0_8px_30px_rgb(0,0,0,0.04)] p-4 sm:p-6 space-y-4">
        <!-- Hàng 1: Chọn Tỉnh/Thành Phố (Trái) + Chọn Ngày Khởi Hành (Phải) -->
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 pb-4 border-b border-slate-100">
          
          <!-- Các nút chọn Tỉnh/Thành phố (Pills sáng sạch 1 hàng ngang duy nhất) -->
          <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 sm:pb-0 flex-1 min-w-0">
            <button
              type="button"
              @click="destFilter = 'ALL'"
              :class="destFilter === 'ALL'
                ? 'bg-[#DC2626] text-white font-extrabold shadow-sm shadow-red-600/20'
                : 'bg-slate-50 hover:bg-red-50 hover:text-[#DC2626] text-slate-700 font-bold border border-slate-200/80'"
              class="shrink-0 px-4 py-2.5 rounded-full text-xs sm:text-sm transition cursor-pointer whitespace-nowrap"
            >
              Tất cả tuyến ({{ rawList.length }})
            </button>
            <button
              v-for="dest in destinationTabs"
              :key="dest.name"
              type="button"
              @click="destFilter = dest.name"
              :class="destFilter === dest.name
                ? 'bg-[#DC2626] text-white font-extrabold shadow-sm shadow-red-600/20'
                : 'bg-slate-50 hover:bg-red-50 hover:text-[#DC2626] text-slate-700 font-bold border border-slate-200/80'"
              class="shrink-0 px-4 py-2.5 rounded-full text-xs sm:text-sm transition cursor-pointer whitespace-nowrap"
            >
              {{ dest.label }} ({{ dest.count }})
            </button>
          </div>

          <!-- Cụm chọn Ngày khởi hành -->
          <div class="flex flex-wrap items-center gap-2 bg-slate-50 p-1.5 rounded-2xl border border-slate-200/80 shrink-0">
            <button
              type="button"
              @click="setDate(today_date)"
              :class="currentDate === today_date
                ? 'bg-[#DC2626] text-white font-extrabold shadow-xs'
                : 'text-slate-600 hover:text-slate-900 hover:bg-white font-bold'"
              class="px-4 py-2 rounded-xl text-xs sm:text-sm transition cursor-pointer whitespace-nowrap"
            >
              Hôm nay ({{ formatDisplayDate(today_date) }})
            </button>
            <button
              type="button"
              @click="setDate(tomorrowDate)"
              :class="currentDate === tomorrowDate
                ? 'bg-[#DC2626] text-white font-extrabold shadow-xs'
                : 'text-slate-600 hover:text-slate-900 hover:bg-white font-bold'"
              class="px-3.5 py-2 rounded-xl text-xs sm:text-sm transition cursor-pointer whitespace-nowrap"
            >
              Ngày mai ({{ formatDisplayDate(tomorrowDate) }})
            </button>
            <div class="relative flex items-center bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-bold text-slate-700">
              <span class="text-[#DC2626] mr-2 text-sm">📅</span>
              <input
                type="date"
                v-model="currentDate"
                @change="onDateChange"
                class="bg-transparent text-slate-800 text-xs sm:text-sm font-bold font-mono outline-none cursor-pointer"
              />
            </div>
          </div>
        </div>

        <!-- Hàng 2: Thông tin ngày (trên) và Thanh điều khiển Lọc chiều + Tìm kiếm + Switch View (dưới) -->
        <div class="space-y-3 pt-1">
          <!-- Dòng 2A: Thông tin ngày khởi hành -->
          <div class="flex items-center gap-2 text-xs sm:text-sm">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="font-bold text-slate-600">Lịch khởi hành ngày:</span>
            <strong class="font-mono text-[#DC2626] font-black">{{ formatDisplayDate(currentDate) }}</strong>
            <span class="text-slate-400 font-semibold">({{ filteredDisplayList.length }} tuyến khả dụng)</span>
            <span class="text-slate-200 ml-1">|</span>
          </div>

          <!-- Dòng 2B: Lọc chiều (trái) và Tìm kiếm + View Mode (phải) -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Trái: Bộ lọc Chiều đi từ Đà Nẵng / Chiều về Đà Nẵng -->
            <div class="inline-flex items-center p-1 rounded-xl bg-slate-100 border border-slate-200/70 text-xs font-bold shrink-0">
              <button
                type="button"
                @click="directionFilter = 'ALL'"
                :class="directionFilter === 'ALL' ? 'bg-white text-slate-900 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900'"
                class="px-3 py-1.5 rounded-lg transition cursor-pointer whitespace-nowrap"
              >
                Tất cả chiều
              </button>
              <button
                type="button"
                @click="directionFilter = 'FROM_DN'"
                :class="directionFilter === 'FROM_DN' ? 'bg-white text-slate-900 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900'"
                class="px-3 py-1.5 rounded-lg transition cursor-pointer whitespace-nowrap"
              >
                Đi từ Đà Nẵng
              </button>
              <button
                type="button"
                @click="directionFilter = 'TO_DN'"
                :class="directionFilter === 'TO_DN' ? 'bg-white text-slate-900 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900'"
                class="px-3 py-1.5 rounded-lg transition cursor-pointer whitespace-nowrap"
              >
                Về Đà Nẵng
              </button>
            </div>

            <!-- Phải: Tìm kiếm nhanh & Chuyển đổi Bảng Lịch Trình / Dạng Thẻ -->
            <div class="flex items-center gap-2.5 shrink-0">
              <div class="relative w-56 sm:w-64">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
                <input
                  v-model="destSearch"
                  type="text"
                  placeholder="Tìm nhanh theo điểm đến"
                  class="w-full pl-9 pr-7 py-2 rounded-xl bg-slate-50 focus:bg-white border border-slate-200 focus:border-[#DC2626] text-xs sm:text-sm font-semibold text-slate-800 focus:outline-none transition"
                />
                <button
                  v-if="destSearch"
                  @click="destSearch = ''"
                  type="button"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400 hover:text-slate-600"
                >
                  ✕
                </button>
              </div>

              <div class="inline-flex items-center p-1 rounded-xl bg-slate-100 border border-slate-200 text-xs shrink-0">
                <button
                  type="button"
                  @click="viewMode = 'table'"
                  :class="viewMode === 'table' ? 'bg-white text-slate-900 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
                >
                  <span class="text-[#DC2626] font-black text-xs leading-none">〓</span>
                  <span>Bảng Lịch Trình</span>
                </button>
                <button
                  type="button"
                  @click="viewMode = 'cards'"
                  :class="viewMode === 'cards' ? 'bg-white text-slate-900 shadow-2xs font-extrabold' : 'text-slate-600 hover:text-slate-900 font-bold'"
                  class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap"
                >
                  <span class="text-amber-500 font-black text-xs leading-none">⸬</span>
                  <span>Dạng Thẻ</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== VIEW 1: BẢNG LỊCH TRÌNH 4 CỘT CHUẨN XÁC NHƯ ẢNH (IMAGE 2) ==================== -->
      <div v-if="viewMode === 'table'" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Table Title Header Bar -->
        <div class="px-6 py-3.5 bg-gradient-to-r from-slate-50 via-white to-rose-50/20 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>
            <h2 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-tight">
              MẠNG LƯỚI TUYẾN ĐƯỜNG VÀ KHUNG GIỜ XUẤT BẾN
            </h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
              ✓ Cam kết xe thật 100%
            </span>
          </div>

          <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <span class="w-2 h-2 rounded-full bg-red-500"></span>
            <span>Khung giờ đang chọn: Xuất bến đúng giờ • Trả khách tận nơi theo trạm</span>
          </div>
        </div>

        <!-- Table 4 Cột: Tuyến đường & Trạm dừng | Loại xe & Hành trình | Chọn khung giờ | Giá vé & Đặt chỗ -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/90 text-slate-600 text-[11px] font-black uppercase tracking-wider border-b border-slate-200">
                <th class="py-3 px-6 w-[28%]">Tuyến Đường & Lộ Trình Trạm Dừng</th>
                <th class="py-3 px-4 w-[20%]">Loại Xe & Hành Trình</th>
                <th class="py-3 px-4 w-[34%]">Chọn Khung Giờ Xuất Bến Trong Ngày</th>
                <th class="py-3 px-6 text-right w-[18%]">Giá Vé & Đặt Chỗ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              <tr
                v-for="item in filteredDisplayList"
                :key="item.id"
                class="hover:bg-rose-50/20 transition-colors group"
              >
                <!-- Cột 1: Tuyến Đường & Lộ Trình Trạm Dừng -->
                <td class="py-4 px-6 align-top">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-black text-sm text-slate-900 group-hover:text-red-600 transition">
                      {{ cleanLocationName(item.origin) }}
                    </span>
                    <span class="text-rose-500 font-black">➔</span>
                    <span class="font-black text-sm text-rose-600">
                      {{ cleanLocationName(item.destination) }}
                    </span>
                  </div>

                  <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5">
                    <span>📍</span>
                    <span class="truncate">{{ getStationSubtext(item) }}</span>
                  </div>

                  <div class="mt-2">
                    <button
                      type="button"
                      @click="openStopsModal(item)"
                      class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-bold border border-rose-200/80 transition cursor-pointer inline-flex items-center gap-1"
                    >
                      <span>📍</span>
                      <span>Xem trạm dừng ({{ item.stops?.length || 6 }} trạm)</span>
                    </button>
                  </div>
                </td>

                <!-- Cột 2: Loại Xe & Hành Trình -->
                <td class="py-4 px-4 align-top">
                  <span class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-block border bg-rose-50 text-rose-700 border-rose-200">
                    {{ item.bus_type || 'Ghế ngồi cao cấp VIP' }}
                  </span>
                  <div class="text-xs font-bold text-slate-800 font-mono mt-1.5">
                    {{ item.distance_km }} km <span class="text-slate-300">•</span> {{ item.estimated_hours }}h
                  </div>
                  <div class="text-[10px] text-slate-400 mt-0.5">
                    (Đã gồm nghỉ trạm)
                  </div>
                </td>

                <!-- Cột 3: Khung Giờ Xuất Bến Trong Ngày (Giờ Thật Từ DB - Bấm để chuyển sang Tìm Chuyến) -->
                <td class="py-4 px-4 align-top">
                  <div class="space-y-2">
                    <!-- Pills Khung Giờ Thật Từ Database -->
                    <div v-if="getRouteTrips(item).length > 0" class="flex flex-wrap items-center gap-1.5">
                      <button
                        v-for="trip in getRouteTrips(item)"
                        :key="trip.id || trip.departure_time"
                        type="button"
                        @click="goToTripSearch(item, trip)"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-mono font-bold transition cursor-pointer select-none border flex items-center gap-1.5 group/pill shadow-2xs hover:shadow-xs active:scale-95"
                        :class="trip.can_book
                          ? 'bg-white hover:bg-red-600 hover:text-white hover:border-red-600 text-slate-800 border-slate-200'
                          : 'bg-slate-50 text-slate-400 border-slate-200 hover:bg-slate-100'"
                        :title="trip.can_book ? `Bấm để tìm và đặt vé chuyến ${trip.departure_time}` : `Chuyến ${trip.departure_time} đã qua giờ hoặc hết vé`"
                      >
                        <span class="w-1.5 h-1.5 rounded-full" :class="trip.can_book ? 'bg-emerald-500 group-hover/pill:bg-white' : 'bg-slate-400'"></span>
                        <span>{{ trip.departure_time }}</span>
                        <span class="text-[10px] text-slate-400 group-hover/pill:text-white transition">➔</span>
                      </button>
                    </div>

                    <!-- Trường hợp hôm nay chưa có chuyến mở bán -->
                    <div v-else class="flex items-center gap-2 py-1">
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 border border-amber-200/80 text-[11px] font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        Chưa có chuyến mở bán hôm nay
                      </span>
                      <button
                        type="button"
                        @click="goToTripSearch(item)"
                        class="text-[11px] text-red-600 hover:text-red-700 font-bold hover:underline cursor-pointer"
                      >
                        Tìm ngày khác ➔
                      </button>
                    </div>

                    <!-- Ghi chú khung giờ -->
                    <div v-if="getRouteTrips(item).length > 0" class="text-[11px] flex flex-wrap items-center gap-2 pt-0.5 text-slate-500">
                      <span class="text-emerald-700 font-bold flex items-center gap-1">
                        <span>✓</span>
                        <span>{{ getRouteTrips(item).length }} chuyến trong ngày</span>
                      </span>
                      <span class="text-slate-400">
                        • Bấm vào giờ để chuyển sang tìm chuyến & đặt chỗ
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Cột 4: Giá Vé & Đặt Chỗ -->
                <td class="py-4 px-6 text-right align-top whitespace-nowrap">
                  <div>
                    <span class="text-xl sm:text-2xl font-black text-red-600 font-mono tracking-tight block">
                      {{ formatPrice(getSelectedTrip(item.id)?.base_price || item.min_price) }}
                    </span>
                    <span class="text-[11px] text-slate-400 block mt-0.5">
                      Cọc 30% linh hoạt
                    </span>
                  </div>

                  <div class="mt-2.5">
                    <button
                      type="button"
                      @click="goToTripSearch(item)"
                      class="px-5 py-2.5 rounded-full bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs transition shadow-sm hover:shadow-md inline-flex items-center gap-1.5 cursor-pointer active:scale-98"
                    >
                      <span>Tìm & Đặt Chuyến</span>
                      <span>➔</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ==================== VIEW 2: DẠNG THẺ (CARD VIEW) ==================== -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div
          v-for="item in filteredDisplayList"
          :key="item.id"
          class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 space-y-5 hover:shadow-md transition"
        >
          <!-- Hàng 1: Loại xe (trái) & Quãng đường • Thời gian (phải) -->
          <div class="flex items-center justify-between">
            <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200/80">
              {{ item.bus_type || 'Ghế ngồi cao cấp VIP' }}
            </span>
            <span class="text-xs sm:text-sm font-semibold text-slate-500">
              {{ item.distance_km || 100 }} km • {{ item.estimated_hours || 2.8 }}h
            </span>
          </div>

          <!-- Hàng 2: Điểm đi - Biểu tượng xe - Điểm đến -->
          <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 pt-1">
            <!-- Điểm đi (Trái) -->
            <div>
              <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                {{ cleanLocationName(item.origin) }}
              </h3>
              <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1">
                {{ getOriginStationName(item) }}
              </p>
            </div>

            <!-- Biểu tượng xe & Đường gạch ở giữa -->
            <div class="flex flex-col items-center justify-center px-2 sm:px-4">
              <svg class="w-5 h-5 text-red-600 mb-1" viewBox="0 0 24 24" fill="currentColor">
                <path d="M4 16c0 .88.39 1.67 1 2.22V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h8v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm1.5-6H6V6h12v5z"/>
              </svg>
              <div class="w-12 sm:w-16 h-0.5 bg-rose-200 rounded-full"></div>
            </div>

            <!-- Điểm đến (Phải) -->
            <div class="text-right">
              <h3 class="text-xl sm:text-2xl font-black text-red-600 tracking-tight">
                {{ cleanLocationName(item.destination) }}
              </h3>
              <p class="text-xs sm:text-sm text-slate-400 font-medium mt-1">
                {{ getDestStationName(item) }}
              </p>
            </div>
          </div>

          <!-- Hàng 3: Khung giờ xuất bến -->
          <div class="pt-1">
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2.5">
              KHUNG GIỜ XUẤT BẾN ({{ getRouteTrips(item).length }} CHUYẾN/NGÀY):
            </div>

            <div v-if="getRouteTrips(item).length > 0" class="flex flex-wrap items-center gap-2.5">
              <button
                v-for="(trip, tIdx) in getRouteTrips(item)"
                :key="trip.id || trip.departure_time"
                type="button"
                @click="selectTripForRoute(item.id, trip)"
                :class="[
                  getSelectedTrip(item.id)?.id === trip.id
                    ? 'bg-red-600 text-white border-red-600 shadow-xs'
                    : tIdx === 1
                    ? 'border-amber-300 bg-amber-50/40 text-slate-800 hover:border-amber-400'
                    : 'border-slate-200 bg-white text-slate-800 hover:border-slate-300'
                ]"
                class="px-4 py-2 rounded-xl text-xs sm:text-sm font-mono font-bold transition cursor-pointer border active:scale-95"
              >
                {{ trip.departure_time }}
              </button>
            </div>

            <div v-else class="flex items-center gap-2 py-1">
              <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/80 text-xs font-semibold">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                Chưa có chuyến mở bán hôm nay
              </span>
              <button
                type="button"
                @click="goToTripSearch(item)"
                class="text-xs text-red-600 hover:text-red-700 font-bold hover:underline cursor-pointer"
              >
                Tìm ngày khác ➔
              </button>
            </div>
          </div>

          <!-- Hàng 4: Giá vé, Xem trạm dừng (trái) & Nút Chọn Chuyến (phải) -->
          <div class="flex items-end justify-between pt-2">
            <div>
              <div class="text-2xl sm:text-3xl font-black text-red-600 font-mono tracking-tight leading-none">
                {{ formatPrice(getSelectedTrip(item.id)?.base_price || item.min_price) }}
              </div>
              <button
                type="button"
                @click="openStopsModal(item)"
                class="mt-2.5 text-xs sm:text-sm font-semibold text-slate-600 hover:text-red-600 flex items-center gap-1 underline underline-offset-4 cursor-pointer transition"
              >
                <span>📍</span>
                <span>Xem trạm dừng ({{ getRouteStopsCount(item) }} trạm)</span>
              </button>
            </div>

            <button
              type="button"
              @click="goToTripSearch(item, getSelectedTrip(item.id))"
              class="px-6 py-3 rounded-full bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs sm:text-sm transition cursor-pointer shadow-sm hover:shadow-md active:scale-95 flex items-center gap-1.5 whitespace-nowrap"
            >
              <span>Chọn Chuyến</span>
              <span>→</span>
            </button>
          </div>
        </div>
      </div>
    </main>

    <!-- ==================== MODAL XEM CHI TIẾT TRẠM DỪNG (6 TRẠM) ==================== -->
    <div
      v-if="selectedRouteForStops"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
      @click.self="selectedRouteForStops = null"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 border border-slate-100 relative">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="font-black text-base text-slate-900 flex items-center gap-1.5">
              <span>📍 Lộ Trình & Trạm Dừng Xe Khách</span>
            </h3>
            <p class="text-xs text-slate-500 font-bold mt-0.5">
              {{ selectedRouteForStops.origin }} ➔ {{ selectedRouteForStops.destination }}
            </p>
          </div>
          <button
            @click="selectedRouteForStops = null"
            class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Danh sách trạm dừng chuẩn 6 trạm -->
        <div class="space-y-3 py-1 max-h-[60vh] overflow-y-auto custom-scrollbar pr-1">
          <div
            v-for="(stop, sIdx) in getRouteStopsList(selectedRouteForStops)"
            :key="sIdx"
            class="flex items-start gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-100"
          >
            <div class="w-7 h-7 rounded-full bg-red-100 text-red-600 font-black text-xs flex items-center justify-center shrink-0">
              {{ sIdx + 1 }}
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between">
                <h4 class="font-black text-xs text-slate-900">{{ stop.stop_name }}</h4>
                <span class="text-[10px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200">
                  {{ stop.time_offset || `+${sIdx * 35} phút` }}
                </span>
              </div>
              <p class="text-[11px] text-slate-500 mt-0.5">{{ stop.address || 'Điểm đón/trả khách cố định' }}</p>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
          <span class="text-xs text-slate-400 font-medium">Cam kết đón trả đúng trạm quy định</span>
          <button
            @click="selectedRouteForStops = null"
            class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition cursor-pointer"
          >
            Đã Hiểu & Đóng Lại
          </button>
        </div>
      </div>
    </div>

    <Footer />
    <AiChatModal />
    <AuthModal v-model="showAuthModal" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import AiChatModal from '@/Components/AiChatModal.vue';
import AuthModal from '@/Components/AuthModal.vue';

const page = usePage();
const showAuthModal = ref(false);

const props = defineProps({
  routes: { type: Array, default: () => [] },
  routes_with_trips: { type: Array, default: () => [] },
  trips: { type: Array, default: () => [] },
  selected_date: { type: String, default: '' },
  today_date: { type: String, default: '' },
});

const viewMode = ref('table'); // 'table' | 'cards'
const currentDate = ref(props.selected_date || props.today_date || '2026-11-05');
const destSearch = ref('');
const destFilter = ref('ALL');
const directionFilter = ref('ALL'); // 'ALL' | 'FROM_DN' | 'TO_DN'
const selectedRouteForStops = ref(null);
const isHubNight = ref(false);

const toggleHubNightMode = () => {
  isHubNight.value = !isHubNight.value;
};

if (typeof window !== 'undefined') {
  window.toggleHubNightMode = () => {
    isHubNight.value = !isHubNight.value;
  };
}

// Lưu trữ trip đang được chọn theo từng routeId
const selectedTripMap = ref({});

const tomorrowDate = computed(() => {
  const d = new Date(props.today_date || '2026-11-05');
  d.setDate(d.getDate() + 1);
  return d.toISOString().split('T')[0];
});

const setDate = (dateStr) => {
  currentDate.value = dateStr;
  router.get('/schedules', { date: dateStr }, { preserveState: true });
};

const onDateChange = () => {
  router.get('/schedules', { date: currentDate.value }, { preserveState: true });
};

// Hàm chuẩn hóa tên địa danh: đảm bảo luôn là 'Thừa Thiên Huế' (tuyệt đối không có dấu gạch nối)
const cleanLocationName = (text) => {
  if (!text) return '';
  return String(text).replace(/Thừa\s*Thiên\s*[-–—]\s*Huế/gi, 'Thừa Thiên Huế');
};

// Danh sách tuyến thô
const rawList = computed(() => {
  const source = props.routes_with_trips && props.routes_with_trips.length > 0
    ? props.routes_with_trips
    : (props.routes || []).map(r => ({
        ...r,
        min_price: 140000,
        trips: (props.trips || []).filter(t => t.route_id === r.id)
      }));

  return source.map(r => ({
    ...r,
    name: cleanLocationName(r.name),
    origin: cleanLocationName(r.origin),
    destination: cleanLocationName(r.destination),
    stops: (r.stops || []).map(s => ({
      ...s,
      stop_name: cleanLocationName(s.stop_name),
      address: cleanLocationName(s.address),
    }))
  }));
});

// Các tab điểm đến kèm số lượng tuyến
const destinationTabs = computed(() => {
  const targets = [
    { name: 'Huế', label: 'Thừa Thiên Huế' },
    { name: 'Quảng Trị', label: 'Quảng Trị' },
    { name: 'Quảng Bình', label: 'Quảng Bình' },
    { name: 'Quy Nhơn', label: 'Quy Nhơn' },
    { name: 'Buôn Ma Thuột', label: 'Buôn Ma Thuột' },
  ];

  return targets.map(t => {
    const count = rawList.value.filter(r => 
      (r.destination || '').toLowerCase().includes(t.name.toLowerCase()) ||
      (r.origin || '').toLowerCase().includes(t.name.toLowerCase())
    ).length;
    return {
      name: t.name,
      label: cleanLocationName(t.label),
      count: count || 2,
    };
  });
});

// Lọc dữ liệu theo Tỉnh, Chiều xe và Từ khóa tìm kiếm
const filteredDisplayList = computed(() => {
  let list = rawList.value;

  // Lọc theo tỉnh
  if (destFilter.value !== 'ALL') {
    const q = destFilter.value.toLowerCase();
    list = list.filter(item =>
      (item.destination || '').toLowerCase().includes(q) ||
      (item.origin || '').toLowerCase().includes(q) ||
      (item.name || '').toLowerCase().includes(q)
    );
  }

  // Lọc theo chiều đi
  if (directionFilter.value === 'FROM_DN') {
    list = list.filter(item => (item.origin || '').includes('Đà Nẵng'));
  } else if (directionFilter.value === 'TO_DN') {
    list = list.filter(item => (item.destination || '').includes('Đà Nẵng'));
  }

  // Lọc theo từ khóa tìm kiếm
  if (destSearch.value.trim()) {
    const s = destSearch.value.toLowerCase().trim();
    list = list.filter(item =>
      (item.origin || '').toLowerCase().includes(s) ||
      (item.destination || '').toLowerCase().includes(s) ||
      (item.name || '').toLowerCase().includes(s)
    );
  }

  return list;
});

// Lấy danh sách khung giờ thực tế cho từng tuyến (bên mình có giờ nào hiện ra giờ đó, không sinh giờ ảo)
const getRouteTrips = (item) => {
  return item.trips || [];
};

const selectTripForRoute = (routeId, trip) => {
  selectedTripMap.value[routeId] = trip;
};

const getSelectedTrip = (routeId) => {
  if (selectedTripMap.value[routeId]) {
    return selectedTripMap.value[routeId];
  }
  const item = rawList.value.find(r => r.id === routeId);
  const trips = item ? getRouteTrips(item) : [];
  return trips.length > 0 ? trips[0] : null;
};

const getOriginStationName = (item) => {
  const origin = cleanLocationName(item?.origin || '');
  return origin.includes('Đà Nẵng') ? 'BX Trung Tâm Đà Nẵng' : `Bến xe ${origin}`;
};

const getDestStationName = (item) => {
  const destination = cleanLocationName(item?.destination || '');
  return destination.includes('Đà Nẵng') ? 'BX Trung Tâm Đà Nẵng' : `Bến xe ${destination}`;
};

const getRouteStopsCount = (item) => {
  return getRouteStopsList(item).length;
};

// Chuyển sang trang Tìm Chuyến (/trips) để khách xem và đặt chỗ
const goToTripSearch = (item, trip = null) => {
  const origin = item.origin || '';
  const destination = item.destination || '';
  const date = currentDate.value || props.today_date || '';
  
  router.visit(`/trips?origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}&date=${date}`);
};

// Bấm nút Chọn Chuyến / Đặt vé
const handleBookingClick = (item) => {
  goToTripSearch(item);
};

const getStationSubtext = (item) => {
  const origin = cleanLocationName(item?.origin || '');
  const destination = cleanLocationName(item?.destination || '');
  const originStation = origin.includes('Đà Nẵng') ? 'BX Trung Tâm Đà Nẵng' : `Bến xe ${origin}`;
  const destStation = destination.includes('Đà Nẵng') ? 'BX Trung Tâm Đà Nẵng' : `Bến xe ${destination}`;
  return `${originStation} ➔ ${destStation}`;
};

const openStopsModal = (item) => {
  selectedRouteForStops.value = item;
};

// 5 trạm dừng chuẩn xác
const getRouteStopsList = (item) => {
  if (item?.stops && item.stops.length >= 5) {
    return item.stops.map(s => ({
      ...s,
      stop_name: cleanLocationName(s.stop_name),
      address: cleanLocationName(s.address),
    }));
  }
  const origin = cleanLocationName(item?.origin || '');
  const destination = cleanLocationName(item?.destination || '');
  return [
    { stop_name: `Bến xe xuất phát (${origin})`, address: 'Số 185 Tôn Đức Thắng, Cổng trung tâm', time_offset: 'Xuất bến (00p)' },
    { stop_name: 'Trạm trung chuyển nội thành / Cửa ngõ', address: 'Vòng xoay Nguyễn Sinh Sắc - Hoàng Thị Loan', time_offset: '+20 phút' },
    { stop_name: 'Trạm dừng chân / Hầm đường bộ cao tốc', address: 'Trạm dừng chân chất lượng cao Miền Trung', time_offset: '+60 phút' },
    { stop_name: 'Trạm đón trả liên tỉnh', address: 'Quốc lộ 1A - Điểm trung chuyển cố định', time_offset: '+95 phút' },
    { stop_name: `Bến xe đích đến (${destination})`, address: `Bến xe liên tỉnh ${destination}`, time_offset: 'Đến nơi' },
  ];
};

const formatDisplayDate = (dateStr) => {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  if (parts.length === 3) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
  }
  return dateStr;
};

const formatPrice = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 140000);
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 9999px;
}

/* =====================================================================
   HIỆU ỨNG XE KHÁCH LƯỚT ÊM TRÊN CẦU RỒNG BAN NGÀY (WHITE DAYLIGHT THEME)
   ===================================================================== */
@keyframes roadDashLight {
  0% { background-position: 0 0; }
  100% { background-position: -80px 0; }
}
.animate-road-light {
  background-image: repeating-linear-gradient(
    90deg,
    #CBD5E1 0px,
    #CBD5E1 20px,
    transparent 20px,
    transparent 40px
  );
  background-size: 80px 100%;
  animation: roadDashLight 1.4s linear infinite;
}

@keyframes busGlideDaylight {
  0% { transform: translateX(-220px); }
  100% { transform: translateX(520px); }
}
.animate-bus-daylight {
  animation: busGlideDaylight 11s linear infinite;
}

@keyframes cloudFloat {
  0%, 100% { transform: translateX(0px); }
  50% { transform: translateX(14px); }
}
.animate-cloud {
  animation: cloudFloat 6s ease-in-out infinite;
}

@keyframes wheelSpin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.spin-wheel {
  transform-box: fill-box;
  transform-origin: center;
  animation: wheelSpin 0.6s linear infinite;
}

/* Ẩn thanh cuộn cho dải nút lọc tỉnh thành */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
