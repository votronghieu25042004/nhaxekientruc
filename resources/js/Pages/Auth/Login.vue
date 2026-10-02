<template>
  <div class="min-h-screen bg-[#F8FAFC] text-slate-800 flex flex-col font-sans relative selection:bg-red-600 selection:text-white" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">
    <!-- 1. TOP NAVBAR -->
    <Navbar />

    <!-- 2. MAIN AUTH CONTAINER (SPLIT-CARD 12 CỘT SÁNG ĐẸP HIỆN ĐẠI) -->
    <main class="flex-1 py-8 sm:py-12 px-4 sm:px-6 flex items-center justify-center bg-gradient-to-br from-slate-50 via-rose-50/25 to-slate-100 relative overflow-hidden">
      
      <!-- Ambient Background Glows -->
      <div class="absolute top-10 left-1/4 w-96 h-96 bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-6xl w-full relative z-10">
        
        <!-- MAIN CARD CONTAINER -->
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/80 border border-slate-200 overflow-hidden flex flex-col">
          
          <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[680px]">
            
            <!-- ========================================================================= -->
            <!-- A. CỘT TRÁI (5/12): SA BÀN XA LỘ ĐÀ NẴNG & HIỆU ỨNG XE CHẠY 2 CHIỀU       -->
            <!-- ========================================================================= -->
            <div 
              class="lg:col-span-5 relative text-white p-6 sm:p-8 flex flex-col justify-between overflow-hidden transition-colors duration-700 select-none"
              :class="skyMode === 'night' ? 'bg-gradient-to-br from-[#1a0508] via-[#380811] to-[#540b17]' : 'bg-gradient-to-br from-[#b91c1c] via-[#dc2626] to-[#ea580c]'"
            >
              <!-- Stars in Night Mode (Ở NGOÀI) -->
              <div v-if="skyMode === 'night'" class="absolute inset-0 pointer-events-none overflow-hidden opacity-80">
                <div class="star star-1"></div>
                <div class="star star-2"></div>
                <div class="star star-3"></div>
                <div class="star star-4"></div>
                <div class="star star-5"></div>
                <div class="star star-6"></div>
                <div class="star star-7"></div>
                <div class="star star-8"></div>
                <!-- Glowing Crescent Moon -->
                <div class="absolute top-6 right-8 w-10 h-10 rounded-full bg-amber-100 shadow-[0_0_25px_rgba(254,243,199,0.7)] opacity-90">
                  <div class="w-8 h-8 rounded-full bg-[#1a0508] absolute -top-1 -right-1"></div>
                </div>
              </div>

              <!-- Sunset Glow in Sunset Mode (Ở NGOÀI) -->
              <div v-else class="absolute inset-0 pointer-events-none overflow-hidden">
                <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-72 h-72 rounded-full bg-amber-400/30 blur-3xl"></div>
                <div class="absolute top-8 right-10 w-12 h-12 rounded-full bg-amber-300 shadow-[0_0_40px_rgba(251,191,36,0.9)] opacity-95"></div>
              </div>

              <!-- Dãy núi Đèo Hải Vân & Silhouette Cầu Rồng Đà Nẵng phát sáng (Vector Backdrop) -->
              <div class="absolute bottom-40 inset-x-0 h-44 pointer-events-none opacity-40 overflow-hidden">
                <!-- Hai Van Pass Mountain Silhouettes -->
                <svg class="absolute bottom-0 w-full h-full text-slate-900/80" viewBox="0 0 500 150" preserveAspectRatio="none">
                  <path d="M0,150 L0,75 Q80,20 160,85 T320,50 T450,95 L500,60 L500,150 Z" fill="currentColor" opacity="0.6"/>
                  <path d="M0,150 L0,95 Q90,50 200,110 T380,80 T500,105 L500,150 Z" fill="currentColor" opacity="0.9"/>
                </svg>
                <!-- Glowing Dragon Bridge Silhouette (Cầu Rồng Đà Nẵng) -->
                <svg class="absolute bottom-1 right-2 w-64 h-24 text-amber-400 drop-shadow-[0_0_8px_rgba(245,158,11,0.8)]" viewBox="0 0 240 80" fill="none">
                  <path d="M10,75 C40,75 50,45 75,45 C100,45 110,65 135,65 C160,65 170,35 195,35 C210,35 225,50 235,75" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                  <circle cx="198" cy="35" r="4" fill="#EF4444" class="animate-ping"/>
                  <circle cx="198" cy="35" r="3" fill="#FBBF24"/>
                </svg>
              </div>

              <!-- HEADER & INTERACTIVE SA BÀN CONTROLS -->
              <div class="relative z-20 space-y-3">
                <div class="flex items-center justify-between gap-2">
                  <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-amber-300 text-[10.5px] font-black uppercase tracking-wider shadow-xs">
                    <span>⭐</span>
                    <span>VẬN TẢI HÀNH KHÁCH CÔNG NGHỆ CAO</span>
                  </div>
                </div>

                <div>
                  <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight flex items-center gap-2">
                    <span>nhaxekientruc</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-600/80 border border-red-400 text-white font-bold font-mono">ĐÀ NẴNG</span>
                  </h2>
                  <p class="text-xs sm:text-sm font-bold text-amber-300 mt-0.5 flex items-center gap-1.5">
                    <span>Hành trình êm ái</span>
                    <span>•</span>
                    <span>Vạn dặm bình an</span>
                  </p>
                </div>
              </div>

              <!-- SA BÀN XA LỘ XE CHẠY 2 CHIỀU -->
              <div class="relative z-20 my-4 p-3 rounded-2xl bg-black/40 border border-white/20 backdrop-blur-md shadow-2xl overflow-hidden transition-all duration-700">
                <!-- Header bên trong sa bàn (Ảnh Image 1: Xa lộ Đà Nẵng ⇄ Liên Tỉnh + Đá pha & Còi + Hoàng Hôn / Đêm) -->
                <div class="flex items-center justify-between text-[11px] font-bold text-slate-300 mb-2.5 px-0.5">
                  <!-- Tiêu đề bên trái -->
                  <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-950 border border-emerald-500/80 flex items-center justify-center">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    </span>
                    <span class="font-bold text-xs sm:text-sm text-slate-100 tracking-tight">Xa lộ Đà Nẵng ⇄ Liên Tỉnh</span>
                  </div>

                  <!-- 2 nút điều khiển đưa vào trong như ảnh Image 1 & Image 2 -->
                  <div class="flex items-center gap-2">
                    <!-- Nút 1: Đá pha & Còi (Ảnh Image 1) -->
                    <button
                      type="button"
                      @click="triggerHeadlightsAndHorn"
                      class="px-2.5 sm:px-3 py-1 rounded-lg sm:rounded-full text-[11px] font-bold transition flex items-center gap-1.5 cursor-pointer border border-[#854d0e]/80 bg-[#361a0f]/90 hover:bg-[#452214] text-amber-300 shadow-sm active:scale-95"
                      :class="isHornActive ? 'ring-2 ring-amber-400 bg-amber-400 text-slate-950 font-black' : ''"
                      title="Bấm để bật chùm đèn pha LED và còi hơi"
                    >
                      <span class="text-xs">📢</span>
                      <span>Còi</span>
                    </button>

                    <!-- Nút 2: Hoàng Hôn / Ban Đêm (Ảnh Image 1: Bấm để đổi cả ở trong và ở ngoài) -->
                    <button
                      type="button"
                      @click="toggleSkyMode"
                      class="px-2.5 sm:px-3 py-1 rounded-lg sm:rounded-full text-[11px] font-bold transition flex items-center gap-1.5 cursor-pointer shadow-sm active:scale-95 border"
                      :class="skyMode === 'night' 
                        ? 'border-slate-700 bg-[#1e293b]/90 hover:bg-slate-700 text-white' 
                        : 'border-[#5c2317] bg-[#3a1d17]/90 hover:bg-[#4d261e] text-white shadow-[0_0_12px_rgba(245,158,11,0.25)]'"
                      :title="skyMode === 'night' ? 'Chuyển sang Bầu trời Hoàng Hôn / Ban Ngày (Ở trong & ở ngoài cùng đổi)' : 'Chuyển sang Bầu trời Ban Đêm (Ở trong & ở ngoài cùng đổi)'"
                    >
                      <span class="w-4 h-4 rounded flex items-center justify-center text-xs" :class="skyMode === 'night' ? 'bg-indigo-950' : 'bg-gradient-to-tr from-amber-500 to-rose-500'">
                        {{ skyMode === 'night' ? '🌙' : '🌅' }}
                      </span>
                      <span class="font-bold">{{ skyMode === 'night' ? 'Ban Đêm' : 'Hoàng Hôn' }}</span>
                    </button>
                  </div>
                </div>

                <!-- KHUNG ĐƯỜNG 2 LÀN XE CHẠY NGƯỢC CHIỀU (Ở TRONG: ĐỔI MÀU & HIỆU ỨNG THEO BẦU TRỜI) -->
                <div 
                  class="relative h-28 rounded-xl overflow-hidden border border-slate-700/80 flex flex-col justify-between p-1.5 transition-all duration-700"
                  :class="skyMode === 'night' 
                    ? 'bg-gradient-to-b from-[#080d1a] via-[#0f172a] to-[#1e293b]' 
                    : 'bg-gradient-to-b from-[#2a0c06] via-[#541607] to-[#7c2d12]'"
                >
                  <!-- 1. Bầu trời đêm sa bàn (Khi skyMode === 'night') -->
                  <div v-if="skyMode === 'night'" class="absolute inset-0 pointer-events-none overflow-hidden z-0 animate-in fade-in duration-500">
                    <!-- Mặt trăng khuyết góc trên phải -->
                    <div class="absolute top-2 right-6 w-7 h-7 rounded-full bg-amber-100 shadow-[0_0_20px_#FDE047]">
                      <div class="w-5 h-5 rounded-full bg-[#080d1a] absolute -top-1 -right-1"></div>
                    </div>
                    
                    <!-- Ngôi sao nhỏ lấp lánh -->
                    <div class="absolute top-2.5 left-10 w-1 h-1 bg-white rounded-full opacity-80 animate-ping"></div>
                    <div class="absolute top-5 left-36 w-1 h-1 bg-white rounded-full opacity-70"></div>
                    <div class="absolute top-3.5 right-32 w-1.5 h-1.5 bg-white rounded-full opacity-80"></div>

                    <!-- Dãy đồi núi đêm -->
                    <svg class="absolute inset-x-0 bottom-8 w-full h-10 opacity-60" viewBox="0 0 320 40" fill="none" preserveAspectRatio="none">
                      <path d="M0,40 L0,22 Q60,5 140,25 T280,15 L320,20 L320,40 Z" fill="#090d16"/>
                    </svg>
                  </div>

                  <!-- 2. Bầu trời hoàng hôn sa bàn (Khi skyMode === 'sunset', CHUẨN XÁC THEO ẢNH IMAGE 1) -->
                  <div v-else class="absolute inset-0 pointer-events-none overflow-hidden z-0 animate-in fade-in duration-500">
                    <!-- Mặt trời hoàng hôn to tròn màu vàng rực rỡ góc trên bên phải (Ảnh Image 1) -->
                    <div class="absolute top-2 right-6 w-8 h-8 rounded-full bg-amber-300 shadow-[0_0_30px_#F59E0B] opacity-95"></div>
                    
                    <!-- Vầng sáng hoàng hôn ấm áp -->
                    <div class="absolute -top-4 right-2 w-28 h-28 rounded-full bg-amber-500/25 blur-xl"></div>

                    <!-- Đốm sáng phản chiếu hoàng hôn lơ lửng -->
                    <div class="absolute top-3 left-8 w-1.5 h-1.5 rounded-full bg-amber-200/60"></div>
                    <div class="absolute top-5 left-28 w-1 h-1 rounded-full bg-amber-100/40"></div>

                    <!-- Dãy đồi núi uốn lượn màu hoàng hôn sẫm (Ảnh Image 1) -->
                    <svg class="absolute inset-x-0 bottom-8 w-full h-12 opacity-80" viewBox="0 0 320 45" fill="none" preserveAspectRatio="none">
                      <path d="M0,45 L0,25 Q50,8 130,28 T260,18 L320,22 L320,45 Z" fill="#3d1409"/>
                      <path d="M0,45 L0,32 Q70,18 160,32 T300,24 L320,28 L320,45 Z" fill="#5c1d0c" opacity="0.6"/>
                    </svg>
                  </div>

                  <!-- Đường lượn sóng cam neon đặc trưng (Ảnh Image 1 & Image 2) -->
                  <svg class="absolute inset-x-0 top-1 w-full h-14 pointer-events-none z-10" viewBox="0 0 320 40" fill="none" preserveAspectRatio="none">
                    <path d="M 0 25 Q 80 5, 160 25 T 320 18" stroke="#f59e0b" stroke-width="2.5" fill="none" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(245,158,11,0.9)]"/>
                    <line x1="0" y1="28" x2="320" y2="28" stroke="#eab308" stroke-opacity="0.4" stroke-width="1.5" stroke-dasharray="6 4" />
                  </svg>

                  <!-- Dải vạch tim đường phản quang di chuyển -->
                  <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-1 border-t-2 border-dashed border-amber-400/80 opacity-80 z-0"></div>

                  <!-- ========================================== -->
                  <!-- LÀN 1: XE GIƯỜNG NẰM NHAXEKIENTRUC (Trái -> Phải) -->
                  <!-- ========================================== -->
                  <div class="relative h-12 w-full z-10 flex items-center overflow-visible">
                    <div 
                      class="bus-lane-1 absolute flex items-center select-none"
                      :class="[
                        isTurboActive ? 'bus-speed-fast' : 'bus-speed-normal',
                        isHornActive ? 'scale-105' : ''
                      ]"
                    >
                      <!-- Bong bóng còi hơi khi bấm Đá pha & Còi -->
                      <div 
                        v-if="isHornActive" 
                        class="absolute -top-9 -left-6 px-3.5 py-1 rounded-full bg-amber-400 text-slate-950 font-black text-xs whitespace-nowrap shadow-xl border-2 border-white animate-bounce z-30 flex items-center gap-1.5"
                      >
                        <span>📢</span>
                        <span>Nhà xe Kiến Trúc xin chào!</span>
                        <span>🚌✨</span>
                      </div>

                      <!-- Nhóm Thân xe và Bánh xe định vị chuẩn xác 100% -->
                      <div class="relative flex items-center">
                        <!-- Xe Khách Giường Nằm 2 Tầng Đỏ - Trắng Chuẩn Image 1 -->
                        <div class="relative w-44 h-11 bg-[#991b1b] rounded-lg shadow-xl border-2 border-amber-400 flex flex-col justify-between p-1 overflow-hidden">
                          
                          <!-- Dãy 4 khoang kính giường nằm phía trên (Ảnh Image 1) -->
                          <div class="flex items-center gap-1.5 h-3.5 pr-6">
                            <div class="flex-1 h-3 rounded-[2px] bg-amber-200/90 border border-amber-500/70 flex items-center justify-center">
                              <div class="w-3.5 h-0.5 bg-amber-700/60 rounded-full"></div>
                            </div>
                            <div class="flex-1 h-3 rounded-[2px] bg-amber-200/90 border border-amber-500/70 flex items-center justify-center">
                              <div class="w-3.5 h-0.5 bg-amber-700/60 rounded-full"></div>
                            </div>
                            <div class="flex-1 h-3 rounded-[2px] bg-amber-200/90 border border-amber-500/70 flex items-center justify-center">
                              <div class="w-3.5 h-0.5 bg-amber-700/60 rounded-full"></div>
                            </div>
                            <div class="flex-1 h-3 rounded-[2px] bg-amber-200/90 border border-amber-500/70 flex items-center justify-center">
                              <div class="w-3.5 h-0.5 bg-amber-700/60 rounded-full"></div>
                            </div>
                          </div>

                          <!-- Kính buồng lái màu xanh dương cyan góc trước (Ảnh Image 1) -->
                          <div class="absolute right-1 top-1 w-6 h-8 rounded-r-md bg-sky-300 border border-sky-400 shadow-[0_0_8px_#38bdf8] flex items-center justify-center">
                            <div class="w-4 h-1 bg-white/70 rounded-full blur-[0.5px]"></div>
                          </div>

                          <!-- Dải chữ Limousine & Biển số vàng phía dưới (Ảnh Image 1) -->
                          <div class="flex items-center justify-between pr-7 z-10">
                            <span class="text-[7.5px] font-black text-amber-300 tracking-tight flex items-center gap-0.5">
                              <span class="text-[6.5px]">★</span> NHAXEKIENTRUC LIMOUSINE
                            </span>
                            <span class="px-1 py-0.2 rounded-xs bg-amber-400 text-slate-950 font-mono font-black text-[7px] tracking-tight">
                              43B-067.89
                            </span>
                          </div>

                          <!-- Đèn hậu đỏ -->
                          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-red-400 shadow-[0_0_8px_#EF4444]"></div>

                          <!-- Đèn pha LED trước -->
                          <div class="absolute right-0 top-1/2 -translate-y-1/2 w-1.5 h-6 bg-amber-100 shadow-[0_0_12px_#FDE047]"></div>
                        </div>

                        <!-- 2 Bánh xe quay tròn định vị chính xác khớp với thân xe đỏ -->
                        <div class="absolute -bottom-2 left-6 w-4 h-4 rounded-full bg-slate-950 border-2 border-amber-400 wheel-spin flex items-center justify-center z-20">
                          <div class="w-1.5 h-1.5 rounded-full bg-slate-800 border border-amber-300"></div>
                        </div>
                        <div class="absolute -bottom-2 right-6 w-4 h-4 rounded-full bg-slate-950 border-2 border-amber-400 wheel-spin flex items-center justify-center z-20">
                          <div class="w-1.5 h-1.5 rounded-full bg-slate-800 border border-amber-300"></div>
                        </div>
                      </div>

                      <!-- Chùm sáng đèn pha quét mặt đường phía trước -->
                      <div 
                        class="h-8 transition-all duration-300 pointer-events-none origin-left"
                        :class="isHornActive ? 'w-36 bg-gradient-to-r from-amber-200/90 via-amber-300/40 to-transparent blur-[1px]' : 'w-16 bg-gradient-to-r from-amber-200/40 via-amber-300/10 to-transparent'"
                        style="clip-path: polygon(0 25%, 100% 0, 100% 100%, 0 75%);"
                      ></div>
                    </div>
                  </div>

                  <!-- ========================================== -->
                  <!-- LÀN 2: XE TRUNG CHUYỂN 0Đ (Phải -> Trái)   -->
                  <!-- ========================================== -->
                  <div class="relative h-11 w-full z-10 flex items-center overflow-visible">
                    <div 
                      class="bus-lane-2 absolute flex items-center flex-row-reverse select-none"
                      :class="isTurboActive ? 'bus-speed-fast' : 'bus-speed-normal'"
                    >
                      <!-- Nhóm Thân xe và Bánh xe trung chuyển định vị chuẩn xác 100% -->
                      <div class="relative flex items-center">
                        <!-- Xe buýt trung chuyển màu trắng đỏ chuẩn Ảnh 1 -->
                        <div class="relative w-36 h-9 bg-white rounded-md shadow-lg border-2 border-slate-300 flex flex-col justify-between p-1 overflow-hidden">
                          <!-- Tầng trên: Banner vàng TRUNG CHUYỂN 0Đ -->
                          <div class="flex items-center justify-between pl-7">
                            <span class="text-[7.5px] font-black text-amber-900 bg-amber-300/80 px-1 py-0.2 rounded-xs">TRUNG CHUYỂN 0Đ</span>
                          </div>

                          <!-- Kính buồng lái màu xanh đậm bên trái (Ảnh Image 1) -->
                          <div class="absolute left-1 top-1 w-5 h-7 rounded-l-md bg-slate-800 border border-slate-600 flex items-center justify-center">
                            <div class="w-3 h-1 bg-sky-400/50 rounded-full"></div>
                          </div>

                          <!-- Dải đỏ ĐÓN TẬN NƠI & Biển số (Ảnh Image 1) -->
                          <div class="flex items-center justify-between pl-7 bg-red-600 -mx-1 -mb-1 px-1.5 py-0.5 text-white">
                            <span class="text-[7px] font-black tracking-tight uppercase">ĐÓN TẬN NƠI</span>
                            <span class="font-mono text-[6.5px] font-bold">43B-199.28</span>
                          </div>
                        </div>

                        <!-- 2 Bánh xe trung chuyển định vị chính xác khớp với thân xe trắng đỏ -->
                        <div class="absolute -bottom-1.5 left-5 w-3.5 h-3.5 rounded-full bg-slate-950 border-2 border-amber-400 wheel-spin flex items-center justify-center z-20">
                          <div class="w-1 h-1 rounded-full bg-slate-800"></div>
                        </div>
                        <div class="absolute -bottom-1.5 right-5 w-3.5 h-3.5 rounded-full bg-slate-950 border-2 border-amber-400 wheel-spin flex items-center justify-center z-20">
                          <div class="w-1 h-1 rounded-full bg-slate-800"></div>
                        </div>
                      </div>

                      <!-- Chùm sáng đèn pha của xe làn 2 (chiếu sang trái) -->
                      <div 
                        class="w-12 h-6 bg-gradient-to-l from-amber-200/30 to-transparent pointer-events-none"
                        style="clip-path: polygon(100% 25%, 0 0, 0 100%, 100% 75%);"
                      ></div>
                    </div>
                  </div>

                </div>
              </div>

              <!-- LƯỚI 4 THẺ QUYỀN LỢI (2x2 GLASSMORPHISM TRÊN NỀN TỐI) -->
              <div class="relative z-20 my-2 grid grid-cols-2 gap-2 text-xs">
                
                <!-- 1. HÀNH LÝ MIỄN PHÍ 100% (0đ) -->
                <div class="p-2.5 rounded-2xl bg-slate-900/60 backdrop-blur-md border border-white/10 hover:border-amber-400/50 transition-all shadow-md group">
                  <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-base group-hover:scale-110 transition-transform">🧳</span>
                    <span class="font-black text-white text-[11px] truncate">Hành Lý FREE 100%</span>
                  </div>
                  <p class="text-[10px] text-slate-300 leading-tight">Miễn cước 20kg khoang gầm + xách tay, không phụ phí.</p>
                </div>

                <!-- 2. SƠ ĐỒ 30 CHỖ CHUẨN XÁC -->
                <div class="p-2.5 rounded-2xl bg-slate-900/60 backdrop-blur-md border border-white/10 hover:border-amber-400/50 transition-all shadow-md group">
                  <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-base group-hover:scale-110 transition-transform">💺</span>
                    <span class="font-black text-white text-[11px] truncate">Sơ Đồ 30 Chỗ Chuẩn</span>
                  </div>
                  <p class="text-[10px] text-slate-300 leading-tight">Tầng 1 (18 chỗ) • Tầng 2 (12 chỗ) cập nhật tức thì.</p>
                </div>

                <!-- 3. GÓI SINH VIÊN LẦN ĐẦU (-100%) -->
                <div class="p-2.5 rounded-2xl bg-slate-900/60 backdrop-blur-md border border-white/10 hover:border-amber-400/50 transition-all shadow-md group">
                  <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-base group-hover:scale-110 transition-transform">🎓</span>
                    <span class="font-black text-amber-300 text-[11px] truncate">Đặc Quyền Sinh Viên</span>
                  </div>
                  <p class="text-[10px] text-slate-300 leading-tight">Email .edu.vn áp mã SINHVIEN: Vé đầu 0đ & Giảm 30%.</p>
                </div>

                <!-- 4. VÉ QR CODE TỨC THÌ -->
                <div class="p-2.5 rounded-2xl bg-slate-900/60 backdrop-blur-md border border-white/10 hover:border-amber-400/50 transition-all shadow-md group">
                  <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-base group-hover:scale-110 transition-transform">🎫</span>
                    <span class="font-black text-white text-[11px] truncate">Vé QR & Giữ Chỗ Thật</span>
                  </div>
                  <p class="text-[10px] text-slate-300 leading-tight">Quét mã lên xe ngay, cam kết 100% giữ đúng chỗ ngồi.</p>
                </div>

              </div>

              <!-- THỐNG KÊ CHÂN CỘT TRÁI -->
              <div class="relative z-20 border-t border-white/15 pt-3 flex items-center justify-between text-center mt-2">
                <div>
                  <p class="font-mono font-black text-base text-white">500.000+</p>
                  <p class="text-[9.5px] text-slate-300 uppercase font-bold tracking-wide">Lượt khách</p>
                </div>
                <div class="w-px h-6 bg-white/15"></div>
                <div>
                  <p class="font-mono font-black text-base text-amber-300">120+</p>
                  <p class="text-[9.5px] text-slate-300 uppercase font-bold tracking-wide">Tuyến xe</p>
                </div>
                <div class="w-px h-6 bg-white/15"></div>
                <div>
                  <p class="font-mono font-black text-base text-emerald-400">100%</p>
                  <p class="text-[9.5px] text-slate-300 uppercase font-bold tracking-wide">Giữ chỗ thật</p>
                </div>
              </div>

            </div>

            <!-- ========================================================================= -->
            <!-- B. CỘT PHẢI (7/12): FORM ĐĂNG NHẬP & ĐĂNG KÝ 3 BƯỚC HOẠT ĐỘNG THỰC TẾ    -->
            <!-- ========================================================================= -->
            <div class="lg:col-span-7 p-6 sm:p-9 flex flex-col justify-between bg-white text-slate-900">
              <div>
                
                <!-- THANH CHUYỂN ĐỔI 2 TAB (ĐĂNG NHẬP vs ĐĂNG KÝ) -->
                <div class="flex border-b border-slate-200 mb-6">
                  <button
                    type="button"
                    @click="switchTab('login')"
                    class="flex-1 pb-3 text-center text-sm font-black transition relative cursor-pointer"
                    :class="activeTab === 'login' ? 'text-red-600' : 'text-slate-400 hover:text-slate-700'"
                  >
                    <span class="flex items-center justify-center gap-1.5">
                      <span>🔐</span>
                      <span>ĐĂNG NHẬP</span>
                    </span>
                    <div
                      v-if="activeTab === 'login'"
                      class="absolute bottom-0 left-0 right-0 h-0.5 bg-red-600 rounded-t-full shadow-sm"
                    ></div>
                  </button>

                  <button
                    type="button"
                    @click="switchTab('register')"
                    class="flex-1 pb-3 text-center text-sm font-black transition relative cursor-pointer"
                    :class="activeTab === 'register' ? 'text-red-600' : 'text-slate-400 hover:text-slate-700'"
                  >
                    <span class="flex items-center justify-center gap-1.5">
                      <span>📝</span>
                      <span>ĐĂNG KÝ TÀI KHOẢN</span>
                    </span>
                    <div
                      v-if="activeTab === 'register'"
                      class="absolute bottom-0 left-0 right-0 h-0.5 bg-red-600 rounded-t-full shadow-sm"
                    ></div>
                  </button>
                </div>

                <!-- FLASH MESSAGE NẾU CÓ -->
                <div v-if="$page.props.flash?.success" class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center gap-2">
                  <span>✓</span>
                  <span>{{ $page.props.flash.success }}</span>
                </div>

                <!-- ==================== TAB 1: FORM ĐĂNG NHẬP ==================== -->
                <div v-if="activeTab === 'login'" class="space-y-4">
                  
                  <!-- Báo Lỗi Đăng Nhập -->
                  <div 
                    v-if="loginErrorText" 
                    class="p-4 rounded-2xl border text-xs transition animate-in fade-in"
                    :class="isAccountLocked ? 'bg-red-50 border-2 border-red-500 text-red-900 shadow-sm' : 'bg-red-50 border border-red-200 text-red-700 font-bold'"
                  >
                    <div class="flex items-start gap-3">
                      <span class="text-2xl shrink-0 mt-0.5">{{ isAccountLocked ? '⛔' : '⚠️' }}</span>
                      <div class="space-y-2 flex-1">
                        <p :class="isAccountLocked ? 'font-black text-sm text-red-900 leading-snug' : 'font-bold'">
                          {{ loginErrorText }}
                        </p>
                        <!-- Hotline hỗ trợ khi tài khoản bị khóa -->
                        <div v-if="isAccountLocked" class="pt-1 flex flex-wrap items-center gap-2">
                          <a 
                            href="tel:19006067" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-sm transition cursor-pointer"
                          >
                            <span>📞</span>
                            <span>Gọi Tổng Đài: 1900 6067</span>
                          </a>
                          <a 
                            href="tel:0905123456" 
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition cursor-pointer"
                          >
                            <span>📱</span>
                            <span>Hotline: 0905 123 456</span>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Form Đăng Nhập Chính -->
                  <form @submit.prevent="submitLogin" class="space-y-4">
                    
                    <!-- Ô Nhập Số Điện Thoại hoặc Email -->
                    <div>
                      <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                          Số điện thoại hoặc Email / Gmail <span class="text-red-500">*</span>
                        </label>
                      </div>

                      <div class="relative flex items-center">
                        <span class="absolute left-3.5 text-slate-400 text-sm pointer-events-none">👤</span>
                        <input
                          v-model="loginForm.email"
                          type="text"
                          required
                          autofocus
                          @focus="triggerTurboSpeed"
                          @blur="stopTurboSpeed"
                          @input="triggerTurboSpeed"
                          placeholder="Nhập SĐT (10 số) hoặc Email (VD: sinhvien@dau.edu.vn)"
                          class="w-full bg-slate-50 border border-slate-300 focus:border-red-500 focus:bg-white rounded-2xl pl-10 pr-4 py-3 text-xs sm:text-sm font-semibold text-slate-900 placeholder:text-slate-400 placeholder:font-normal outline-none transition focus:ring-2 focus:ring-red-500/20"
                        />
                      </div>

                      <!-- TỰ ĐỘNG NHẬN DIỆN EMAIL SINH VIÊN (BANNER XANH LÁ) -->
                      <div 
                        v-if="isStudentEmailLogin" 
                        class="mt-3 p-3 bg-emerald-50 border-2 border-emerald-400 rounded-2xl text-emerald-950 text-xs flex items-start gap-2.5 shadow-sm animate-in fade-in"
                      >
                        <span class="text-xl shrink-0 mt-0.5">🎓</span>
                        <div>
                          <p class="font-black text-emerald-800 text-[12.5px] flex items-center gap-1.5">
                            <span>ĐÃ NHẬN DIỆN EMAIL SINH VIÊN KIẾN TRÚC!</span>
                            <span class="px-1.5 py-0.2 rounded bg-emerald-600 text-white font-mono text-[9px] font-black uppercase">SINHVIEN</span>
                          </p>
                          <p class="text-[11px] text-emerald-700 mt-0.5 leading-snug">
                            Tài khoản trường <strong>.edu.vn</strong> được kích hoạt <strong>Gói Giảm 30% (Mã: SINHVIEN)</strong> và đặc quyền vé đầu tiên miễn phí 100%!
                          </p>
                        </div>
                      </div>
                    </div>

                    <!-- Ô Nhập Mật Khẩu -->
                    <div>
                      <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                          Mật khẩu <span class="text-red-500">*</span>
                        </label>
                        <button
                          type="button"
                          @click="showForgotModal = true"
                          class="text-[11px] font-bold text-red-600 hover:underline cursor-pointer"
                        >
                          Quên mật khẩu?
                        </button>
                      </div>

                      <div class="relative flex items-center">
                        <span class="absolute left-3.5 text-slate-400 text-sm pointer-events-none">🔒</span>
                        <input
                          ref="passwordInputRef"
                          v-model="loginForm.password"
                          :type="showPassword ? 'text' : 'password'"
                          required
                          @focus="triggerTurboSpeed"
                          @blur="stopTurboSpeed"
                          placeholder="Nhập mật khẩu tài khoản"
                          class="w-full bg-slate-50 border border-slate-300 focus:border-red-500 focus:bg-white rounded-2xl pl-10 pr-10 py-3 text-xs sm:text-sm font-semibold text-slate-900 placeholder:text-slate-400 placeholder:font-normal outline-none transition focus:ring-2 focus:ring-red-500/20"
                        />
                        <button
                          type="button"
                          @click="showPassword = !showPassword"
                          class="absolute right-3.5 text-slate-400 hover:text-slate-600 text-sm cursor-pointer p-1"
                          title="Ẩn / Hiện mật khẩu"
                        >
                          {{ showPassword ? '🙈' : '👁️' }}
                        </button>
                      </div>
                    </div>

                    <!-- Ghi Nhớ Đăng Nhập -->
                    <div class="flex items-center justify-between text-xs text-slate-600 pt-0.5">
                      <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input
                          type="checkbox"
                          v-model="loginForm.remember"
                          class="w-4 h-4 rounded text-red-600 border-slate-300 focus:ring-red-500 cursor-pointer"
                        />
                        <span class="font-medium text-slate-700">Ghi nhớ đăng nhập</span>
                      </label>
                    </div>

                    <!-- NÚT ĐĂNG NHẬP CHÍNH (CỠ LỚN CÓ HIỆU ỨNG ÁNH SÁNG LƯỚT QUA) -->
                    <button
                      type="submit"
                      :disabled="loginForm.processing"
                      class="relative overflow-hidden w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-red-600 via-red-500 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs sm:text-sm shadow-lg shadow-red-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99] disabled:bg-slate-300 btn-shimmer"
                    >
                      <span>{{ loginForm.processing ? 'ĐANG ĐĂNG NHẬP...' : 'ĐĂNG NHẬP VÀO HỆ THỐNG' }}</span>
                      <span class="text-base">➔</span>
                    </button>
                  </form>

                </div>

                <!-- ==================== TAB 2: FORM ĐĂNG KÝ (3 BƯỚC ĐẦY ĐỦ) ==================== -->
                <div v-if="activeTab === 'register'" class="space-y-4">
                  
                  <!-- 1. BỘ CHUYỂN ĐỔI PHƯƠNG THỨC: SĐT vs EMAIL -->
                  <div class="grid grid-cols-2 gap-2 p-1.5 bg-slate-100 rounded-2xl border border-slate-200 text-xs">
                    <button
                      type="button"
                      @click="setRegisterMethod('phone')"
                      class="py-2.5 px-3 rounded-xl font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
                      :class="registerMethod === 'phone' ? 'bg-white text-red-600 shadow-sm font-black' : 'text-slate-600 hover:text-slate-900'"
                    >
                      <span>📱</span>
                      <span>Số Điện Thoại (Zalo / SMS)</span>
                    </button>

                    <button
                      type="button"
                      @click="setRegisterMethod('gmail')"
                      class="py-2.5 px-3 rounded-xl font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
                      :class="registerMethod === 'gmail' ? 'bg-white text-red-600 shadow-sm font-black' : 'text-slate-600 hover:text-slate-900'"
                    >
                      <span>✉️</span>
                      <span>Email / Gmail (.edu.vn)</span>
                    </button>
                  </div>

                  <!-- 2. THANH TIẾN TRÌNH 3 BƯỚC (STEPPER) -->
                  <div class="flex items-center justify-between px-1 py-1.5 border-b border-slate-100">
                    <div class="flex items-center gap-1.5">
                      <div
                        class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black"
                        :class="registerStep === 1 ? 'bg-red-600 text-white shadow-xs' : (registerStep > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500')"
                      >
                        {{ registerStep > 1 ? '✓' : '1' }}
                      </div>
                      <span class="text-xs font-bold" :class="registerStep === 1 ? 'text-red-600' : 'text-slate-500'">
                        {{ registerMethod === 'phone' ? 'Nhập SĐT' : 'Nhập Email' }}
                      </span>
                    </div>

                    <div class="flex-1 h-0.5 mx-2 bg-slate-200">
                      <div class="h-full bg-red-600 transition-all duration-300" :style="{ width: registerStep === 1 ? '0%' : (registerStep === 2 ? '50%' : '100%') }"></div>
                    </div>

                    <div class="flex items-center gap-1.5">
                      <div
                        class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black"
                        :class="registerStep === 2 ? 'bg-red-600 text-white shadow-xs' : (registerStep > 2 ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500')"
                      >
                        {{ registerStep > 2 ? '✓' : '2' }}
                      </div>
                      <span class="text-xs font-bold" :class="registerStep === 2 ? 'text-red-600' : 'text-slate-500'">
                        Mã OTP
                      </span>
                    </div>

                    <div class="flex-1 h-0.5 mx-2 bg-slate-200">
                      <div class="h-full bg-red-600 transition-all duration-300" :style="{ width: registerStep === 3 ? '100%' : '0%' }"></div>
                    </div>

                    <div class="flex items-center gap-1.5">
                      <div
                        class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black"
                        :class="registerStep === 3 ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-200 text-slate-500'"
                      >
                        3
                      </div>
                      <span class="text-xs font-bold" :class="registerStep === 3 ? 'text-red-600' : 'text-slate-500'">
                        Mật khẩu
                      </span>
                    </div>
                  </div>

                  <!-- BÁO LỖI NẾU CÓ HOẶC TÀI KHOẢN ĐÃ TỒN TẠI -->
                  <div 
                    v-if="registerErrorText" 
                    class="p-3.5 rounded-2xl border text-xs transition animate-in fade-in"
                    :class="isRegisterLocked ? 'bg-red-50 border-2 border-red-500 text-red-900 shadow-sm' : 'bg-red-50 border border-red-200 text-red-700 font-bold'"
                  >
                    <div class="flex items-start gap-2.5">
                      <span class="text-xl shrink-0 mt-0.5">{{ isRegisterLocked ? '⛔' : '⚠️' }}</span>
                      <div class="space-y-1.5 flex-1">
                        <p :class="isRegisterLocked ? 'font-black text-sm text-red-900' : 'font-bold'">
                          {{ registerErrorText }}
                        </p>
                        <!-- Nút chuyển sang Đăng nhập nếu đã có tài khoản -->
                        <div v-if="isAlreadyRegistered" class="pt-1">
                          <button
                            type="button"
                            @click="switchTab('login'); loginForm.email = activeIdentifier"
                            class="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center gap-1.5"
                          >
                            <span>👉</span>
                            <span>Chuyển sang Đăng Nhập ngay (Đã điền sẵn)</span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- ==================== BƯỚC 1: NHẬP SĐT HOẶC EMAIL ==================== -->
                  <div v-if="registerStep === 1" class="space-y-4">
                    
                    <!-- 1A. SĐT -->
                    <div v-if="registerMethod === 'phone'" class="space-y-3">
                      <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                          Số điện thoại nhận OTP (Zalo/SMS) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                          <span class="absolute left-3.5 text-slate-400 text-sm">📱</span>
                          <input
                            v-model="regPhone"
                            type="tel"
                            required
                            @focus="triggerTurboSpeed"
                            @blur="stopTurboSpeed"
                            placeholder="Nhập 10 chữ số (VD: 0905123456)"
                            class="w-full bg-slate-50 border border-slate-300 focus:border-red-500 focus:bg-white rounded-2xl pl-10 pr-4 py-3 text-xs sm:text-sm font-semibold text-slate-900 outline-none transition focus:ring-2 focus:ring-red-500/20"
                          />
                        </div>
                        <!-- Hộp Quà Tặng Mới -->
                        <div class="mt-2 p-2.5 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-950 flex items-center gap-2 font-medium">
                          <span class="text-lg">🎁</span>
                          <div>
                            <span class="font-bold text-amber-800">Quà Tặng Thành Viên Mới:</span>
                            <span class="text-slate-700 ml-1">Tặng ngay mã <strong>CHAOBAOMOI (Giảm 10%)</strong> cho lần đặt vé đầu tiên!</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- 1B. Email (Nhận diện Sinh Viên vs Email thường) -->
                    <div v-else class="space-y-3">
                      <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                          Địa chỉ Email / Gmail <span class="text-red-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                          <span class="absolute left-3.5 text-slate-400 text-sm">✉️</span>
                          <input
                            v-model="regGmail"
                            type="email"
                            required
                            @focus="triggerTurboSpeed"
                            @blur="stopTurboSpeed"
                            placeholder="Nhập email (VD: sinhvien@dau.edu.vn hoặc yourname@gmail.com)"
                            class="w-full bg-slate-50 border border-slate-300 focus:border-red-500 focus:bg-white rounded-2xl pl-10 pr-4 py-3 text-xs sm:text-sm font-semibold text-slate-900 outline-none transition focus:ring-2 focus:ring-red-500/20"
                          />
                        </div>
                        
                        <!-- Tự động phát hiện email sinh viên vs email thường -->
                        <div v-if="regGmail && regGmail.toLowerCase().includes('.edu.vn')" class="mt-2 p-3 bg-emerald-50 border border-emerald-300 rounded-2xl text-xs text-emerald-950 flex items-center gap-2 font-bold animate-in fade-in">
                          <span class="text-xl">🎓</span>
                          <div>
                            <span class="text-emerald-800">Email Sinh Viên Trường Hợp Lệ:</span>
                            <span class="font-normal text-slate-700 ml-1">Kích hoạt đặc quyền <strong>Mã: SINHVIEN (Giảm 30% vĩnh viễn & vé đầu 0đ)</strong>!</span>
                          </div>
                        </div>
                        <div v-else-if="regGmail" class="mt-2 p-2.5 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-950 flex items-center gap-2 font-medium animate-in fade-in">
                          <span class="text-lg">🎁</span>
                          <div>
                            <span class="font-bold text-amber-800">Quà Tặng Thành Viên Mới:</span>
                            <span class="text-slate-700 ml-1">Áp dụng mã <strong>CHAOBAOMOI (Giảm 10%)</strong> cho chuyến đi đầu tiên!</span>
                          </div>
                        </div>
                        <p v-else class="text-[11px] text-slate-500 mt-1">Mã xác thực 6 số OTP sẽ được gửi về hộp thư này.</p>
                      </div>
                    </div>

                    <!-- Điều khoản dịch vụ -->
                    <div class="flex items-start gap-2 pt-1">
                      <input
                        type="checkbox"
                        id="agree-reg"
                        v-model="agreeTerms"
                        class="w-4 h-4 rounded text-red-600 border-slate-300 focus:ring-red-500 mt-0.5 cursor-pointer"
                      />
                      <label for="agree-reg" class="text-xs text-slate-600 select-none cursor-pointer">
                        Tôi đồng ý với <a href="#" class="text-red-600 underline font-bold">Điều khoản & Quy định vận chuyển</a> của nhaxekientruc (Bao gồm miễn phí 20kg hành lý 0đ).
                      </label>
                    </div>

                    <!-- Nút Gửi OTP Bước 1 -->
                    <button
                      type="button"
                      @click="handleSendOtpStep1"
                      :disabled="!isStep1Valid || !agreeTerms || isSendingOtp"
                      class="relative overflow-hidden w-full py-3.5 px-4 rounded-2xl bg-red-600 hover:bg-red-700 disabled:bg-slate-300 text-white font-black text-xs sm:text-sm shadow-md shadow-red-600/30 transition flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed btn-shimmer"
                    >
                      <span v-if="isSendingOtp" class="animate-spin text-sm">⏳</span>
                      <span>
                        {{ isSendingOtp ? 'ĐANG GỬI MÃ XÁC THỰC OTP...' : (registerMethod === 'phone' ? 'NHẬN MÃ OTP QUA ZALO / SMS →' : 'NHẬN MÃ OTP QUA EMAIL →') }}
                      </span>
                    </button>
                  </div>

                  <!-- ==================== BƯỚC 2: NHẬP MÃ OTP 6 SỐ ==================== -->
                  <div v-if="registerStep === 2" class="space-y-4">
                    <div class="flex items-center justify-between">
                      <button
                        type="button"
                        @click="registerStep = 1"
                        class="text-xs font-bold text-slate-500 hover:text-red-600 flex items-center gap-1 cursor-pointer transition"
                      >
                        <span>← Quay lại</span>
                        <span class="text-[11px]">({{ activeTargetDisplay }})</span>
                      </button>
                      <span class="text-xs font-mono font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-xl border border-amber-200">
                        ⏱️ Còn lại: {{ formattedCountdown }}
                      </span>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 text-center space-y-1.5">
                      <p class="text-xs text-slate-600">
                        Mã xác thực OTP gồm 6 chữ số đã được gửi tới:
                      </p>
                      <p class="font-mono font-black text-slate-900 text-sm sm:text-base">
                        {{ activeTargetDisplay }}
                      </p>
                      <p class="text-[11px] text-slate-500">
                        Vui lòng kiểm tra tin nhắn/hộp thư và nhập 6 chữ số bên dưới:
                      </p>
                    </div>

                    <!-- 6 Ô Input OTP Tự Động Chuyển Ô Khi Gõ -->
                    <div class="flex items-center justify-center gap-2 sm:gap-2.5 py-1">
                      <input
                        v-for="(digit, idx) in otpDigits"
                        :key="'otp-' + idx"
                        :id="'otp-box-' + idx"
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        :value="digit"
                        @input="onOtpInput($event, idx)"
                        @keydown="onOtpKeydown($event, idx)"
                        @paste="onOtpPaste"
                        class="w-11 h-12 sm:w-12 sm:h-14 text-center font-mono font-black text-lg sm:text-xl text-slate-900 bg-slate-50 border-2 border-slate-300 focus:border-red-600 focus:bg-white rounded-2xl outline-none transition focus:ring-2 focus:ring-red-500/20 shadow-xs"
                      />
                    </div>

                    <!-- Nút Điền Nhanh OTP Mẫu (1-Click Demo OTP Fill Button) -->
                    <div v-if="receivedOtp" class="text-center">
                      <button
                        type="button"
                        @click="quickFillOtp"
                        class="text-[11px] font-bold text-amber-700 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-3 py-1 rounded-xl transition cursor-pointer"
                      >
                        ⚡ Điền nhanh mã OTP: <span class="font-mono font-black text-red-600">{{ receivedOtp }}</span>
                      </button>
                    </div>

                    <!-- Nút Xác Thực OTP Bước 2 -->
                    <div class="space-y-2 pt-1">
                      <button
                        type="button"
                        @click="handleVerifyOtpStep2"
                        :disabled="!isOtpFilled || isVerifyingOtp"
                        class="w-full py-3.5 px-4 rounded-2xl bg-red-600 hover:bg-red-700 disabled:bg-slate-300 text-white font-black text-xs sm:text-sm shadow-md shadow-red-600/30 transition flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed btn-shimmer"
                      >
                        <span v-if="isVerifyingOtp" class="animate-spin text-sm">⏳</span>
                        <span>{{ isVerifyingOtp ? 'ĐANG XÁC THỰC...' : 'XÁC THỰC MÃ OTP & TIẾP TỤC →' }}</span>
                      </button>

                      <div class="text-center pt-1">
                        <button
                          type="button"
                          @click="handleResendOtp"
                          :disabled="countdownSeconds > 0 || isSendingOtp"
                          class="text-xs font-bold transition cursor-pointer"
                          :class="countdownSeconds > 0 ? 'text-slate-400 cursor-not-allowed' : 'text-red-600 hover:underline'"
                        >
                          {{ countdownSeconds > 0 ? `Gửi lại mã sau (${countdownSeconds}s)` : 'Chưa nhận được mã? Bấm gửi lại OTP' }}
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- ==================== BƯỚC 3: TẠO MẬT KHẨU & HOÀN TẤT ==================== -->
                  <div v-if="registerStep === 3" class="space-y-4">
                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2">
                      <span>✓</span>
                      <span>Xác thực OTP thành công cho {{ activeTargetDisplay }}! Thiết lập mật khẩu để hoàn tất.</span>
                    </div>

                    <form @submit.prevent="handleCompleteRegisterStep3" class="space-y-3.5">
                      <!-- Họ và tên -->
                      <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                          Họ và tên của bạn <span class="text-red-500">*</span>
                        </label>
                        <input
                          v-model="regName"
                          type="text"
                          required
                          @focus="triggerTurboSpeed"
                          @blur="stopTurboSpeed"
                          placeholder="Ví dụ: Nguyễn Văn An"
                          class="w-full bg-slate-50 border border-slate-300 focus:border-red-500 focus:bg-white rounded-2xl px-4 py-3 text-xs sm:text-sm font-semibold text-slate-900 outline-none transition"
                        />
                      </div>

                      <!-- Mật khẩu mới -->
                      <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                          Mật khẩu mới <span class="text-red-500">*</span>
                        </label>
                        <input
                          v-model="regPassword"
                          type="password"
                          required
                          minlength="6"
                          @focus="triggerTurboSpeed"
                          @blur="stopTurboSpeed"
                          placeholder="Tối thiểu 6 ký tự"
                          class="w-full bg-slate-50 border border-slate-300 focus:border-red-500 focus:bg-white rounded-2xl px-4 py-3 text-xs sm:text-sm font-semibold text-slate-900 outline-none transition"
                        />
                      </div>

                      <!-- Xác nhận mật khẩu -->
                      <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                          Xác nhận lại mật khẩu <span class="text-red-500">*</span>
                        </label>
                        <input
                          v-model="regPasswordConfirm"
                          type="password"
                          required
                          minlength="6"
                          @focus="triggerTurboSpeed"
                          @blur="stopTurboSpeed"
                          placeholder="Nhập lại mật khẩu vừa đặt"
                          class="w-full bg-slate-50 border border-slate-300 focus:border-red-500 focus:bg-white rounded-2xl px-4 py-3 text-xs sm:text-sm font-semibold text-slate-900 outline-none transition"
                        />
                      </div>

                      <!-- Nút hoàn tất đăng ký -->
                      <button
                        type="submit"
                        :disabled="registerForm.processing"
                        class="relative overflow-hidden w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-red-600 via-rose-600 to-red-700 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs sm:text-sm shadow-lg shadow-red-600/30 transition flex items-center justify-center gap-2 cursor-pointer disabled:bg-slate-300 btn-shimmer"
                      >
                        <span>{{ registerForm.processing ? 'ĐANG TẠO TÀI KHOẢN...' : 'HOÀN TẤT ĐĂNG KÝ TÀI KHOẢN 🎉' }}</span>
                      </button>
                    </form>
                  </div>

                </div>

              </div>

              <!-- Footer hotline & an toàn thông tin -->
              <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span class="flex items-center gap-1">
                  <span>🛡️</span>
                  <span>Bảo mật dữ liệu 100% chuẩn SSL</span>
                </span>
                <span>Tổng đài CSKH: <a href="tel:19006067" class="font-bold text-red-600 hover:underline">1900 6067</a></span>
              </div>

            </div>

          </div>

          <!-- ========================================================================= -->
          <!-- C. DẢI XA LỘ TOÀN CẢNH CHẠY XUYÊN SUỐT DƯỚI ĐÁY CARD                       -->
          <!-- ========================================================================= -->
          <div class="w-full h-9 bg-slate-950 border-t-2 border-slate-800 relative overflow-hidden flex items-center select-none">
            <!-- Mặt đường bê tông & Vạch sơn tim đường di chuyển liên tục -->
            <div 
              class="absolute inset-0 bg-repeat-x road-strip"
              :class="isTurboActive ? 'road-strip-fast' : 'road-strip-normal'"
            ></div>

            <!-- Mini Bus Chạy Toàn Cảnh (Trái -> Phải) -->
            <div 
              class="panoramic-bus-1 absolute flex items-center gap-1 pointer-events-none"
              :class="isTurboActive ? 'bus-speed-fast' : 'bus-speed-normal'"
            >
              <div class="w-14 h-4 bg-red-600 rounded-sm border border-white/40 shadow-md flex items-center justify-center text-[7px] text-white font-black">
                NHAXEKIENTRUC
              </div>
              <div class="w-5 h-2 bg-amber-300/60 blur-[1px]"></div>
            </div>

            <!-- Mini Transit Chạy Toàn Cảnh (Phải -> Trái) -->
            <div 
              class="panoramic-bus-2 absolute flex items-center flex-row-reverse gap-1 pointer-events-none"
              :class="isTurboActive ? 'bus-speed-fast' : 'bus-speed-normal'"
            >
              <div class="w-11 h-3.5 bg-amber-400 rounded-sm border border-slate-900 shadow-sm flex items-center justify-center text-[6.5px] text-slate-950 font-bold">
                0Đ
              </div>
              <div class="w-4 h-2 bg-amber-200/50 blur-[1px]"></div>
            </div>

            <div class="absolute right-4 text-[9px] font-mono text-slate-400 font-bold tracking-wider uppercase z-10 bg-slate-900/80 px-2 py-0.5 rounded">
              HÀNH TRÌNH ĐÀ NẴNG ↔ LIÊN TỈNH TOÀN QUỐC
            </div>
          </div>

        </div>

      </div>
    </main>

    <!-- 3. FOOTER 4 CỘT HIỆN ĐẠI -->
    <Footer />

    <!-- MODAL QUÊN MẬT KHẨU -->
    <div
      v-if="showForgotModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in"
    >
      <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-sm w-full space-y-4 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
            <span>🔑</span>
            <span>Hỗ Trợ Lấy Lại Mật Khẩu</span>
          </h3>
          <button @click="showForgotModal = false" class="text-slate-400 hover:text-slate-600 text-lg cursor-pointer">✕</button>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">
          Vui lòng liên hệ tổng đài CSKH nhaxekientruc để được cấp lại mật khẩu hoặc xác thực tài khoản nhanh chóng:
        </p>
        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs text-slate-800 space-y-2 font-bold">
          <div class="flex items-center justify-between">
            <span>📞 Tổng đài 24/7:</span>
            <a href="tel:19006067" class="text-red-600 underline font-mono text-sm">1900 6067</a>
          </div>
          <div class="flex items-center justify-between">
            <span>📱 Hotline hỗ trợ:</span>
            <a href="tel:0905123456" class="text-red-600 underline font-mono">0905 123 456</a>
          </div>
          <div class="flex items-center justify-between">
            <span>✉️ Hòm thư CSKH:</span>
            <span class="text-red-600 font-mono">hotro@nhaxekientruc.vn</span>
          </div>
        </div>
        <button
          @click="showForgotModal = false"
          class="w-full py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs transition cursor-pointer"
        >
          Đã Hiểu
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
  errors: Object,
  initialTab: { type: String, default: 'login' },
});

const page = usePage();

// Tab state: 'login' | 'register'
const activeTab = ref(props.initialTab || 'login');
const showPassword = ref(false);
const showForgotModal = ref(false);
const agreeTerms = ref(true);

// Sa bàn state
const skyMode = ref('night'); // 'night' | 'sunset'
const isHornActive = ref(false);
const isTurboActive = ref(false);
let hornTimeout = null;

const toggleSkyMode = () => {
  skyMode.value = skyMode.value === 'night' ? 'sunset' : 'night';
};

// Đá pha và Còi xe phát tiếng "Nhà xe Kiến Trúc xin chào!"
const triggerHeadlightsAndHorn = () => {
  isHornActive.value = true;
  if (hornTimeout) clearTimeout(hornTimeout);
  
  // 1. Web Speech API: Giọng nói "Nhà xe Kiến Trúc xin chào!"
  try {
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
      const utterance = new SpeechSynthesisUtterance("Nhà xe Kiến Trúc xin chào!");
      utterance.lang = 'vi-VN';
      utterance.rate = 1.0;
      utterance.pitch = 1.05;
      window.speechSynthesis.speak(utterance);
    }
  } catch (e) {}

  // 2. Phát âm thanh còi xe khách mô phỏng qua Web Audio API
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (AudioContext) {
      const audioCtx = new AudioContext();
      const osc1 = audioCtx.createOscillator();
      const osc2 = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();

      osc1.type = 'sawtooth';
      osc2.type = 'triangle';
      osc1.frequency.setValueAtTime(440, audioCtx.currentTime); // Note A4
      osc2.frequency.setValueAtTime(554.37, audioCtx.currentTime); // Note C#5 (Major 3rd)

      gainNode.gain.setValueAtTime(0.12, audioCtx.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);

      osc1.connect(gainNode);
      osc2.connect(gainNode);
      gainNode.connect(audioCtx.destination);

      osc1.start();
      osc2.start();
      osc1.stop(audioCtx.currentTime + 0.5);
      osc2.stop(audioCtx.currentTime + 0.5);
    }
  } catch (e) {}

  hornTimeout = setTimeout(() => {
    isHornActive.value = false;
  }, 2600);
};

const triggerTurboSpeed = () => {
  isTurboActive.value = true;
};

const stopTurboSpeed = () => {
  isTurboActive.value = false;
};

const switchTab = (tab) => {
  activeTab.value = tab;
  otpError.value = '';
  isAlreadyRegistered.value = false;
  if (page.props.errors) {
    page.props.errors = {};
  }
  if (typeof window !== 'undefined') {
    const newPath = tab === 'register' ? '/register' : '/login';
    window.history.replaceState({}, '', newPath);
  }
};

// ==================== 1. FORM ĐĂNG NHẬP ====================
const passwordInputRef = ref(null);

const loginForm = useForm({
  email: '',
  password: '',
  remember: true,
});

const isStudentEmailLogin = computed(() => {
  const val = (loginForm.email || '').toLowerCase().trim();
  return val.endsWith('.edu.vn') || val.includes('@dau.edu.vn');
});

const fillLoginCredential = (identifier) => {
  loginForm.email = identifier;
  loginForm.password = '';
  nextTick(() => {
    passwordInputRef.value?.focus();
  });
};

const loginErrorText = computed(() => {
  return loginForm.errors.email || loginForm.errors.status || loginForm.errors.phone || page.props.errors?.email || page.props.errors?.status || page.props.errors?.phone || null;
});

const isAccountLocked = computed(() => {
  const text = String(loginErrorText.value || '');
  return text.includes('KHÓA') || text.includes('XÓA') || text.includes('vô hiệu hóa');
});

const submitLogin = () => {
  triggerTurboSpeed();
  loginForm.post('/login', {
    preserveScroll: true,
    onSuccess: () => {
      try {
        localStorage.setItem('nhaxekientruc_show_welcome_modal', '1');
      } catch (e) {}
    },
    onFinish: () => {
      stopTurboSpeed();
    }
  });
};

// ==================== 2. QUY TRÌNH ĐĂNG KÝ 3 BƯỚC ====================
const registerMethod = ref('phone'); // 'phone' | 'gmail'
const registerStep = ref(1); // 1: Nhập SĐT/Email -> 2: Nhập OTP -> 3: Đặt Mật Khẩu
const regPhone = ref('');
const regGmail = ref('');
const regName = ref('');
const regPassword = ref('');
const regPasswordConfirm = ref('');
const otpDigits = ref(['', '', '', '', '', '']);
const receivedOtp = ref('');
const otpChannel = ref('');
const isAlreadyRegistered = ref(false);
const isSendingOtp = ref(false);
const isVerifyingOtp = ref(false);
const otpError = ref('');
const countdownSeconds = ref(119);
let countdownTimer = null;

const setRegisterMethod = (method) => {
  registerMethod.value = method;
  registerStep.value = 1;
  otpDigits.value = ['', '', '', '', '', ''];
  receivedOtp.value = '';
  otpChannel.value = '';
  isAlreadyRegistered.value = false;
  otpError.value = '';
  if (page.props.errors) {
    page.props.errors = {};
  }
};

const regPhoneClean = computed(() => (regPhone.value || '').replace(/[^0-9]/g, ''));
const regPhoneValid = computed(() => {
  const raw = (regPhone.value || '').trim();
  if (!raw) return false;
  return /^(0|\+84)(3|5|7|8|9)[0-9]{8}$/.test(raw.replace(/\s+/g, '')) || /^0[35789][0-9]{8}$/.test(regPhoneClean.value);
});

const regGmailValid = computed(() => {
  const raw = (regGmail.value || '').trim();
  if (!raw) return false;
  return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(raw);
});

const isStep1Valid = computed(() => {
  return registerMethod.value === 'phone' ? regPhoneValid.value : regGmailValid.value;
});

const activeIdentifier = computed(() => {
  if (registerMethod.value === 'phone') {
    let clean = regPhoneClean.value;
    if (clean.startsWith('84') && clean.length === 11) clean = '0' + clean.slice(2);
    return clean;
  }
  return (regGmail.value || '').trim().toLowerCase();
});

const activeTargetDisplay = computed(() => {
  if (registerMethod.value === 'phone') {
    const p = activeIdentifier.value;
    if (p.length === 10) {
      return `+84 ${p.slice(1, 4)} ${p.slice(4, 7)} ${p.slice(7)}`;
    }
    return `+84 ${p}`;
  }
  return activeIdentifier.value || 'email@gmail.com';
});

const isOtpFilled = computed(() => {
  return otpDigits.value.every(d => d.trim().length === 1);
});

const formattedCountdown = computed(() => {
  const m = Math.floor(countdownSeconds.value / 60);
  const s = countdownSeconds.value % 60;
  return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
});

const startCountdown = (sec = 119) => {
  if (countdownTimer) clearInterval(countdownTimer);
  countdownSeconds.value = sec;
  countdownTimer = setInterval(() => {
    if (countdownSeconds.value > 0) {
      countdownSeconds.value--;
    } else {
      clearInterval(countdownTimer);
      countdownTimer = null;
    }
  }, 1000);
};

const onOtpInput = (event, idx) => {
  const val = event.target.value.replace(/[^0-9]/g, '');
  otpDigits.value[idx] = val ? val.slice(-1) : '';

  if (val && idx < 5) {
    nextTick(() => {
      document.getElementById(`otp-box-${idx + 1}`)?.focus();
    });
  }

  if (isOtpFilled.value) {
    handleVerifyOtpStep2();
  }
};

const onOtpKeydown = (event, idx) => {
  if (event.key === 'Backspace' && !otpDigits.value[idx] && idx > 0) {
    nextTick(() => {
      const prevBox = document.getElementById(`otp-box-${idx - 1}`);
      if (prevBox) {
        prevBox.focus();
        otpDigits.value[idx - 1] = '';
      }
    });
  }
};

const onOtpPaste = (event) => {
  event.preventDefault();
  const text = (event.clipboardData || window.clipboardData).getData('text') || '';
  const digits = text.replace(/[^0-9]/g, '').slice(0, 6).split('');
  if (digits.length > 0) {
    digits.forEach((d, i) => {
      if (i < 6) otpDigits.value[i] = d;
    });
    const nextIdx = Math.min(digits.length, 5);
    document.getElementById(`otp-box-${nextIdx}`)?.focus();

    if (digits.length === 6) {
      handleVerifyOtpStep2();
    }
  }
};

const quickFillOtp = () => {
  if (!receivedOtp.value) return;
  const digits = String(receivedOtp.value).split('').slice(0, 6);
  digits.forEach((d, i) => {
    otpDigits.value[i] = d;
  });
  handleVerifyOtpStep2();
};

const handleSendOtpStep1 = async () => {
  if (!isStep1Valid.value || !agreeTerms.value || isSendingOtp.value) return;

  isSendingOtp.value = true;
  otpError.value = '';
  isAlreadyRegistered.value = false;

  const reqIdentifier = activeIdentifier.value;
  const reqType = registerMethod.value === 'phone' ? 'phone' : 'email';

  try {
    const res = await axios.post('/register/send-otp', {
      identifier: reqIdentifier,
      type: reqType,
    }, {
      headers: { 'Accept': 'application/json' }
    });

    if (res.data?.success) {
      registerStep.value = 2;
      receivedOtp.value = res.data?.otp || '';
      otpDigits.value = ['', '', '', '', '', ''];
      otpChannel.value = res.data?.channel || (registerMethod.value === 'phone' ? 'zalo' : 'email');
      startCountdown(119);
      nextTick(() => {
        document.getElementById('otp-box-0')?.focus();
      });
    } else {
      otpError.value = res.data?.message || 'Có lỗi khi gửi mã OTP. Vui lòng thử lại!';
    }
  } catch (err) {
    const res = err.response?.data;
    if (res?.code === 'ALREADY_EXISTS') {
      isAlreadyRegistered.value = true;
    }
    otpError.value = res?.message || res?.errors?.identifier?.[0] || 'Không thể gửi mã xác thực. Vui lòng kiểm tra lại thông tin!';
  } finally {
    isSendingOtp.value = false;
  }
};

const handleResendOtp = async () => {
  if (countdownSeconds.value > 0 || isSendingOtp.value) return;

  isSendingOtp.value = true;
  otpError.value = '';

  const reqIdentifier = activeIdentifier.value;
  const reqType = registerMethod.value === 'phone' ? 'phone' : 'email';

  try {
    const res = await axios.post('/register/send-otp', {
      identifier: reqIdentifier,
      type: reqType,
    }, {
      headers: { 'Accept': 'application/json' }
    });

    if (res.data?.success) {
      receivedOtp.value = res.data?.otp || '';
      otpDigits.value = ['', '', '', '', '', ''];
      otpChannel.value = res.data?.channel || (registerMethod.value === 'phone' ? 'zalo' : 'email');
      startCountdown(119);
      nextTick(() => {
        document.getElementById('otp-box-0')?.focus();
      });
    }
  } catch (err) {
    const res = err.response?.data;
    otpError.value = res?.message || 'Lỗi khi gửi lại OTP.';
  } finally {
    isSendingOtp.value = false;
  }
};

const handleVerifyOtpStep2 = async () => {
  const fullOtp = otpDigits.value.join('').trim();
  if (fullOtp.length !== 6 || isVerifyingOtp.value) return;

  isVerifyingOtp.value = true;
  otpError.value = '';

  try {
    const res = await axios.post('/register/verify-otp', {
      otp: fullOtp,
      identifier: activeIdentifier.value,
    }, {
      headers: { 'Accept': 'application/json' }
    });

    if (res.data?.success) {
      registerStep.value = 3;
      if (countdownTimer) clearInterval(countdownTimer);
    } else {
      otpError.value = res.data?.message || 'Mã OTP không đúng hoặc đã hết hạn!';
    }
  } catch (err) {
    const res = err.response?.data;
    otpError.value = res?.errors?.otp?.[0] || res?.message || 'Mã OTP không đúng hoặc đã hết hạn. Vui lòng thử lại!';
  } finally {
    isVerifyingOtp.value = false;
  }
};

const registerForm = useForm({
  name: '',
  identifier: '',
  password: '',
  password_confirmation: '',
});

watch([regPhone, regGmail], () => {
  otpError.value = '';
  isAlreadyRegistered.value = false;
  if (page.props.errors) {
    delete page.props.errors.identifier;
    delete page.props.errors.email;
    delete page.props.errors.phone;
  }
});

const registerErrorText = computed(() => {
  if (activeTab.value !== 'register') return null;
  
  let err = otpError.value;
  if (!err && registerStep.value === 3) {
    err = registerForm.errors.name || registerForm.errors.password || registerForm.errors.password_confirmation || registerForm.errors.identifier || null;
  }
  if (!err) {
    err = page.props.errors?.identifier || null;
  }
  return err;
});

const isRegisterLocked = computed(() => {
  const text = String(registerErrorText.value || '');
  return text.includes('KHÓA') || text.includes('XÓA') || text.includes('vô hiệu hóa');
});

const handleCompleteRegisterStep3 = () => {
  if (!regName.value.trim()) {
    otpError.value = 'Vui lòng nhập họ và tên của bạn!';
    return;
  }
  if (!regPassword.value || regPassword.value.length < 6) {
    otpError.value = 'Mật khẩu phải từ 6 ký tự trở lên!';
    return;
  }
  if (regPassword.value !== regPasswordConfirm.value) {
    otpError.value = 'Mật khẩu xác nhận không khớp!';
    return;
  }

  registerForm.name = regName.value.trim();
  registerForm.identifier = activeIdentifier.value;
  registerForm.password = regPassword.value;
  registerForm.password_confirmation = regPasswordConfirm.value;

  triggerTurboSpeed();
  registerForm.post('/register', {
    onSuccess: () => {
      try {
        localStorage.setItem('nhaxekientruc_show_welcome_modal', '1');
      } catch (e) {}
    },
    onError: (errs) => {
      otpError.value = Object.values(errs)[0] || 'Lỗi đăng ký tài khoản.';
    },
    onFinish: () => {
      stopTurboSpeed();
    }
  });
};
</script>

<style scoped>
/* ========================================================================= */
/* ANIMATIONS CHO XA LỘ, XE CHẠY 2 CHIỀU, BÁNH XE VÀ ÁNH SÁNG                */
/* ========================================================================= */

/* Ngôi sao lấp lánh bầu trời đêm */
.star {
  position: absolute;
  width: 2px;
  height: 2px;
  background: white;
  border-radius: 9999px;
  box-shadow: 0 0 6px rgba(255, 255, 255, 0.9);
  animation: starTwinkle 2.5s infinite ease-in-out alternate;
}
.star-1 { top: 15%; left: 20%; animation-delay: 0.2s; }
.star-2 { top: 25%; left: 60%; animation-delay: 1.1s; }
.star-3 { top: 40%; left: 85%; animation-delay: 0.6s; }
.star-4 { top: 10%; left: 75%; animation-delay: 1.8s; }
.star-5 { top: 35%; left: 35%; animation-delay: 0.9s; }
.star-6 { top: 50%; left: 15%; animation-delay: 1.4s; }
.star-7 { top: 18%; left: 45%; animation-delay: 2.1s; }
.star-8 { top: 45%; left: 65%; animation-delay: 0.4s; }

@keyframes starTwinkle {
  0% { opacity: 0.2; transform: scale(0.8); }
  50% { opacity: 1; transform: scale(1.3); }
  100% { opacity: 0.3; transform: scale(0.9); }
}

/* Làn 1: Xe chạy Trái sang Phải */
.bus-lane-1 {
  animation: driveLeftToRight 7s linear infinite;
}
@keyframes driveLeftToRight {
  0% {
    transform: translateX(-160px);
  }
  100% {
    transform: translateX(450px);
  }
}

/* Làn 2: Xe chạy Phải sang Trái */
.bus-lane-2 {
  animation: driveRightToLeft 8.5s linear infinite;
}
@keyframes driveRightToLeft {
  0% {
    transform: translateX(450px);
  }
  100% {
    transform: translateX(-160px);
  }
}

/* Turbo Speed: Tăng gấp đôi tốc độ khi gõ phím hoặc submit */
.bus-speed-fast {
  animation-duration: 3.2s !important;
}
.bus-speed-normal {
  animation-duration: 7.5s;
}

/* Bánh xe xoay */
.wheel-spin {
  animation: wheelRotate 0.6s linear infinite;
}
@keyframes wheelRotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Hiệu ứng ánh sáng kim loại lướt qua nút Submit */
.btn-shimmer::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 50%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.35),
    transparent
  );
  transform: skewX(-20deg);
  animation: shimmerMove 3.5s infinite;
}
@keyframes shimmerMove {
  0% { left: -100%; }
  25% { left: 200%; }
  100% { left: 200%; }
}

/* Dải Xa Lộ Toàn Cảnh Chạy Xuyên Suốt Đáy Card */
.road-strip {
  background-image: repeating-linear-gradient(
    90deg,
    transparent,
    transparent 18px,
    rgba(245, 158, 11, 0.8) 18px,
    rgba(245, 158, 11, 0.8) 36px
  );
  background-size: 72px 3px;
  background-position: center;
  background-repeat: repeat-x;
}
.road-strip-normal {
  animation: roadStripMove 1.2s linear infinite;
}
.road-strip-fast {
  animation: roadStripMove 0.5s linear infinite;
}
@keyframes roadStripMove {
  0% { background-position-x: 0; }
  100% { background-position-x: -72px; }
}

/* Xe Panoramic chạy toàn cảnh đáy Card */
.panoramic-bus-1 {
  animation: drivePanoramicRight 11s linear infinite;
}
@keyframes drivePanoramicRight {
  0% { left: -100px; }
  100% { left: 110%; }
}

.panoramic-bus-2 {
  animation: drivePanoramicLeft 14s linear infinite;
}
@keyframes drivePanoramicLeft {
  0% { left: 110%; }
  100% { left: -100px; }
}
</style>
