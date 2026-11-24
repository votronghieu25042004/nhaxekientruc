<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-[99999] overflow-y-auto bg-black/65 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 animate-in fade-in duration-200">
      <!-- Backdrop click to close -->
      <div class="fixed inset-0" @click="close"></div>

      <div class="relative z-10 bg-white rounded-3xl max-w-3xl w-full overflow-hidden shadow-2xl border border-slate-200 flex flex-col max-h-[92vh]" @click.stop>
        
        <!-- HEADER -->
        <div class="p-5 sm:p-6 bg-gradient-to-r from-[#991b1b] via-[#dc2626] to-[#ea580c] text-white relative overflow-hidden flex-shrink-0">
          <div class="relative z-10 flex items-center justify-between">
            <div>
              <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/20 text-amber-200 text-[10.5px] font-black uppercase tracking-wider mb-1.5 border border-white/20">
                <span>⭐</span>
                <span>ĐẶC QUYỀN HỘI VIÊN NHAXEKIENTRUC</span>
              </div>
              <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                <span v-if="modalStep === 'select_plan'">Gói Hội Viên Ưu Đãi (Pro & Ultra)</span>
                <span v-else-if="modalStep === 'payment'">Thanh Toán Gói Hội Viên (VietQR MBBank)</span>
                <span v-else>🎉 Kích Hoạt Gói Hội Viên Thành Công!</span>
              </h2>
              <p class="text-xs sm:text-sm text-red-100 font-medium mt-0.5">
                <span v-if="modalStep === 'select_plan'">Tiết kiệm từ 20% đến 40% cho mỗi chuyến đi suốt năm</span>
                <span v-else-if="modalStep === 'payment'">Quét mã VietQR MBBank bằng bất kỳ App ngân hàng để kích hoạt tự động</span>
                <span v-else>Mã giảm giá độc quyền đã sẵn sàng sử dụng khi đặt vé</span>
              </p>
            </div>

            <button
              @click="close"
              type="button"
              class="w-9 h-9 rounded-full bg-black/20 hover:bg-black/40 text-white flex items-center justify-center text-lg transition cursor-pointer"
            >
              ✕
            </button>
          </div>

          <!-- Trạng thái hiện tại của người dùng (Chỉ hiện khi đang ở bước chọn gói và đã có gói) -->
          <div v-if="currentPlanInfo && modalStep === 'select_plan'" class="mt-4 p-3.5 bg-black/35 backdrop-blur-md rounded-2xl border border-white/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2.5">
              <span class="text-2xl">{{ String(currentPlanInfo.name).includes('ULTRA') ? '👑' : '🎓' }}</span>
              <div>
                <div class="font-bold text-white flex items-center gap-1.5">
                  <span>Gói đang dùng:</span>
                  <span class="text-amber-300 font-black uppercase">{{ currentPlanInfo.name }}</span>
                  <span class="px-2 py-0.2 rounded-full bg-emerald-500 text-white text-[10px] font-black">HOẠT ĐỘNG</span>
                </div>
                <div class="text-[11px] text-red-200 mt-0.5">
                  Bắt đầu: {{ currentPlanInfo.registered_at }} • Hết hạn: {{ currentPlanInfo.expires_at }}
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-center">
              <div class="text-xs font-mono font-black text-amber-300 bg-amber-400/20 px-2.5 py-1.5 rounded-xl border border-amber-300/40 inline-flex items-center gap-1">
                <span>⏱️ Còn lại:</span>
                <span>{{ currentPlanInfo.days_left }} ngày</span>
              </div>
              <button
                type="button"
                @click="handleCancelCurrentPlan"
                :disabled="isCancelling"
                class="px-3 py-1.5 rounded-xl bg-red-600/95 hover:bg-red-700 text-white font-black text-[11px] shadow-xs flex items-center gap-1 transition active:scale-95 cursor-pointer disabled:opacity-50"
                title="Hủy gói này để có thể mua gói khác"
              >
                <span>🗑️</span>
                <span>{{ isCancelling ? 'Đang hủy...' : 'Hủy Gói Đang Dùng' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- BƯỚC 1: CHỌN GÓI DỊCH VỤ -->
        <div v-if="modalStep === 'select_plan'" class="p-4 sm:p-6 overflow-y-auto space-y-4 flex-1">
          
          <!-- Toggle: Sinh Viên vs Khách Thường -->
          <div class="flex items-center justify-center">
            <div class="p-1 bg-slate-100 rounded-2xl border border-slate-200 flex items-center gap-1 max-w-md w-full">
              <button
                type="button"
                @click="targetRole = 'student'"
                class="flex-1 py-2 px-3 rounded-xl text-center text-xs font-black transition cursor-pointer flex items-center justify-center gap-1.5"
                :class="targetRole === 'student' ? 'bg-[#dc2626] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
              >
                <span>🎓</span>
                <span>Gói Sinh Viên (Trợ Giá)</span>
              </button>

              <button
                type="button"
                @click="targetRole = 'standard'"
                class="flex-1 py-2 px-3 rounded-xl text-center text-xs font-black transition cursor-pointer flex items-center justify-center gap-1.5"
                :class="targetRole === 'standard' ? 'bg-[#dc2626] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 font-bold'"
              >
                <span>👤</span>
                <span>Khách Hàng Thường</span>
              </button>
            </div>
          </div>

          <!-- Cảnh báo bắt buộc hủy gói cũ nếu muốn mua gói khác -->
          <div v-if="currentPlanInfo" class="p-3.5 bg-amber-50 border-2 border-amber-300 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs animate-in fade-in">
            <div class="flex items-center gap-2.5 text-xs text-amber-950">
              <span class="text-2xl shrink-0">⚠️</span>
              <div>
                <p class="font-black text-xs text-amber-900 uppercase">Bạn đang sử dụng: {{ currentPlanInfo.name }}</p>
                <p class="text-slate-700 mt-0.5">Theo quy định, bạn cần <strong>hủy gói đang sử dụng</strong> trước khi được chọn mua hoặc đăng ký gói dịch vụ khác.</p>
              </div>
            </div>
            <button
              type="button"
              @click="handleCancelCurrentPlan"
              :disabled="isCancelling"
              class="px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white font-black text-xs rounded-xl shadow-xs transition cursor-pointer flex items-center justify-center gap-1.5 shrink-0 disabled:opacity-50 active:scale-95"
            >
              <span>🗑️</span>
              <span>{{ isCancelling ? 'Đang hủy...' : 'Hủy Gói Để Mua Gói Mới' }}</span>
            </button>
          </div>

          <!-- 3 CỘT GÓI DỊCH VỤ -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            
            <!-- GÓI 1: GÓI ECO (GIẢM 20%) -->
            <div 
              @click="selectPlan('ECO_20')"
              class="rounded-2xl border-2 p-4 flex flex-col justify-between transition cursor-pointer relative"
              :class="selectedPlan === 'ECO_20' ? 'border-red-600 bg-red-50/20 shadow-md ring-2 ring-red-600/20' : 'border-slate-200 hover:border-slate-300 bg-white'"
            >
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">6 Tháng</span>
                  <span v-if="selectedPlan === 'ECO_20'" class="w-4 h-4 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center font-bold">✓</span>
                </div>

                <div>
                  <h3 class="font-black text-slate-900 text-base">Gói ECO</h3>
                  <p class="text-[11px] text-slate-500">Tiết kiệm vé thường nhật</p>
                </div>

                <div class="py-2 border-y border-slate-100">
                  <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-red-600">
                      {{ targetRole === 'student' ? '49.000đ' : '199.000đ' }}
                    </span>
                    <span v-if="targetRole === 'student'" class="text-xs font-bold text-slate-400 line-through">199.000đ</span>
                    <span v-if="targetRole === 'student'" class="text-[9px] font-black text-red-700 bg-red-100 px-1 py-0.2 rounded uppercase">Tiết kiệm 75%</span>
                  </div>
                  <div class="text-[10px] text-slate-400">
                    Thời hạn: 6 tháng (180 ngày)
                  </div>
                </div>

                <ul class="text-xs text-slate-600 space-y-2">
                  <li class="flex items-center gap-1.5 font-bold text-slate-800">
                    <span class="text-emerald-600">✓</span>
                    <span>Giảm 20% mỗi chuyến xe</span>
                  </li>
                  <li class="flex items-center gap-1.5">
                    <span class="text-emerald-600">✓</span>
                    <span>Miễn phí 20kg hành lý</span>
                  </li>
                  <li class="flex items-center gap-1.5">
                    <span class="text-emerald-600">✓</span>
                    <span>Tích 1.2x điểm thưởng</span>
                  </li>
                </ul>
              </div>

              <button
                type="button"
                class="mt-4 w-full py-2.5 rounded-xl font-black text-xs transition cursor-pointer"
                :class="selectedPlan === 'ECO_20' ? 'bg-red-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
              >
                {{ selectedPlan === 'ECO_20' ? 'Đã Chọn Gói Này' : 'Chọn Gói ECO' }}
              </button>
            </div>

            <!-- GÓI 2: GÓI PRO (GIẢM 30% - SINH VIÊN PRO) -->
            <div 
              @click="selectPlan('PRO_30')"
              class="rounded-2xl border-2 p-4 flex flex-col justify-between transition cursor-pointer relative"
              :class="selectedPlan === 'PRO_30' ? 'border-emerald-600 bg-emerald-50/20 shadow-md ring-2 ring-emerald-600/30' : 'border-slate-200 hover:border-slate-300 bg-white'"
            >
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                        :class="targetRole === 'student' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-blue-50 text-blue-700 border border-blue-200'">
                    {{ targetRole === 'student' 
                        ? (isActivePlan('PRO_30') ? '✓ Đang Sử Dụng' : (isStudentRebuyPro ? '🎓 Sinh Viên Mua Lại' : (isGuest ? '🎓 Nhận 1 Năm 0đ' : '🎓 Có Sẵn 1 Năm'))) 
                        : '1 Năm Trọn Gói' }}
                  </span>
                  <span v-if="selectedPlan === 'PRO_30'" class="w-4 h-4 rounded-full bg-emerald-600 text-white text-[10px] flex items-center justify-center font-bold">✓</span>
                </div>

                <div>
                  <h3 class="font-black text-slate-900 text-base flex items-center gap-1">
                    <span>Gói {{ targetRole === 'student' ? 'Sinh Viên PRO' : 'PRO' }}</span>
                    <span v-if="targetRole === 'student'" class="text-xs">🎓</span>
                  </h3>
                  <p class="text-[11px] text-emerald-800 font-semibold">
                    {{ targetRole === 'student' 
                        ? (isStudentRebuyPro ? 'Mua lại sau khi hủy (Trợ giá sinh viên)' : 'Có sẵn 1 năm (365 ngày) khi đăng ký thành công') 
                        : 'Giảm 30% suốt 365 ngày' }}
                  </p>
                </div>

                <div class="py-2 border-y border-slate-100">
                  <div v-if="targetRole === 'student'" class="space-y-0.5">
                    <!-- Sinh viên mua lại sau khi đã hủy gói trước đó -->
                    <div v-if="isStudentRebuyPro" class="space-y-0.5">
                      <div class="flex items-baseline gap-1.5">
                        <span class="text-2xl font-black text-emerald-700">149.000đ</span>
                        <span class="text-xs font-bold text-slate-400 line-through">349.000đ</span>
                        <span class="text-[9px] font-black text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded uppercase">TIẾT KIỆM 200K</span>
                      </div>
                      <div class="text-[10px] text-slate-500 font-medium">
                        Ưu đãi sinh viên mua lại gói sau khi đã hủy trước đó
                      </div>
                    </div>
                    <!-- Khách chưa đăng nhập: Tặng 1 năm 0đ khi tạo tài khoản -->
                    <div v-else-if="isGuest" class="space-y-0.5">
                      <div class="flex items-baseline gap-1.5">
                        <span class="text-3xl font-black text-emerald-600">0đ</span>
                        <span class="text-xs font-bold text-slate-400 line-through">349.000đ</span>
                        <span class="text-[9.5px] font-black text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded-md uppercase">MIỄN PHÍ 100%</span>
                      </div>
                      <div class="text-[10px] text-slate-500 font-medium">
                        Tặng sẵn 1 năm (365 ngày) khi đăng ký tài khoản sinh viên mới
                      </div>
                    </div>
                    <!-- Sinh viên mới tạo tài khoản hợp lệ -->
                    <div v-else class="space-y-0.5">
                      <div class="flex items-baseline gap-1.5">
                        <span class="text-3xl font-black text-emerald-600">0đ</span>
                        <span class="text-xs font-bold text-slate-400 line-through">349.000đ</span>
                        <span class="text-[9.5px] font-black text-emerald-800 bg-emerald-100 px-1.5 py-0.5 rounded-md uppercase">MIỄN PHÍ 100%</span>
                      </div>
                      <div class="text-[10px] text-slate-500 font-medium">
                        Đặc quyền cấp sẵn 365 ngày khi đăng ký tài khoản sinh viên
                      </div>
                    </div>
                  </div>
                  <!-- Khách hàng thường -->
                  <div v-else>
                    <div class="text-2xl font-black text-blue-600">
                      349.000đ
                    </div>
                    <div class="text-[10px] text-slate-400">
                      Thời hạn: 1 năm (365 ngày)
                    </div>
                  </div>
                </div>

                <!-- Quyền lợi cho Sinh Viên -->
                <ul v-if="targetRole === 'student'" class="text-xs text-slate-600 space-y-2">
                  <li class="flex items-center gap-1.5 font-bold text-emerald-900">
                    <span class="text-emerald-600 font-black">✓</span>
                    <span>Giảm 30% toàn bộ vé xe suốt 365 ngày</span>
                  </li>
                  <li class="flex items-center gap-1.5 font-bold text-slate-800">
                    <span class="text-emerald-600 font-black">✓</span>
                    <span>Tặng sẵn 0đ khi đăng ký email trường (.edu.vn)</span>
                  </li>
                  <li class="flex items-center gap-1.5 font-bold text-blue-900">
                    <span class="text-blue-600 font-black">✓</span>
                    <span>Mã ưu đãi độc quyền: SINHVIEN30</span>
                  </li>
                  <li class="flex items-center gap-1.5">
                    <span class="text-emerald-600">✓</span>
                    <span>Miễn phí 20kg hành lý & tích điểm 1.5x</span>
                  </li>
                </ul>

                <!-- Quyền lợi cho Khách Hàng Thường -->
                <ul v-else class="text-xs text-slate-600 space-y-2">
                  <li class="flex items-center gap-1.5 font-bold text-slate-900">
                    <span class="text-blue-600 font-black">✓</span>
                    <span>Giảm 30% toàn bộ vé xe suốt 365 ngày</span>
                  </li>
                  <li class="flex items-center gap-1.5 font-bold text-blue-900">
                    <span class="text-blue-600 font-black">✓</span>
                    <span>Mã ưu đãi độc quyền: PRO30</span>
                  </li>
                  <li class="flex items-center gap-1.5 text-slate-700">
                    <span class="text-emerald-600">✓</span>
                    <span>Ưu tiên hỗ trợ giữ chỗ</span>
                  </li>
                  <li class="flex items-center gap-1.5">
                    <span class="text-emerald-600">✓</span>
                    <span>Miễn phí 20kg hành lý & tích điểm 1.5x</span>
                  </li>
                </ul>
              </div>

              <button
                type="button"
                @click.stop="isGuest ? handleMainActionClick() : selectPlan('PRO_30')"
                class="mt-4 w-full py-2.5 rounded-xl font-black text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
                :class="selectedPlan === 'PRO_30' ? 'bg-emerald-600 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
              >
                <span>
                  {{ isActivePlan('PRO_30') 
                      ? '✓ Gói Bạn Đang Dùng' 
                      : (isGuest && targetRole === 'student' 
                          ? 'Đăng Ký Nhận 0đ ➔' 
                          : (isStudentRebuyPro 
                              ? (selectedPlan === 'PRO_30' ? 'Đã Chọn Gói Này' : 'Mua Lại Gói PRO (149.000đ)') 
                              : (selectedPlan === 'PRO_30' ? 'Đã Chọn Gói Này' : (targetRole === 'student' ? 'Kích Hoạt Gói PRO 0đ' : 'Chọn Gói PRO')))) }}
                </span>
              </button>
            </div>

            <!-- GÓI 3: GÓI ULTRA (GIẢM 40% - KHUYÊN DÙNG NHẤT) -->
            <div 
              @click="selectPlan('ULTRA_40')"
              class="rounded-2xl border-2 p-4 flex flex-col justify-between transition cursor-pointer relative bg-gradient-to-b from-amber-50/40 via-white to-red-50/30"
              :class="selectedPlan === 'ULTRA_40' ? 'border-[#dc2626] shadow-lg ring-3 ring-red-600/30' : 'border-amber-300 hover:border-red-500'"
            >
              <!-- Tag Best Value -->
              <div class="absolute -top-3 right-4 px-3 py-0.5 rounded-full bg-gradient-to-r from-red-600 to-amber-500 text-white text-[9.5px] font-black uppercase tracking-wider shadow-sm">
                👑 KHUYÊN DÙNG NHẤT
              </div>

              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-xs font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300">1 Năm VIP</span>
                  <span v-if="selectedPlan === 'ULTRA_40'" class="w-4 h-4 rounded-full bg-red-600 text-white text-[10px] flex items-center justify-center font-bold">✓</span>
                </div>

                <div>
                  <h3 class="font-black text-slate-900 text-base flex items-center gap-1.5">
                    <span>Gói ULTRA</span>
                    <span class="text-amber-500">✨</span>
                  </h3>
                  <p class="text-[11px] text-amber-900 font-bold">Giảm 40% trọn vẹn 1 năm</p>
                </div>

                <div class="py-2 border-y border-amber-200/60">
                  <div class="flex items-baseline gap-1.5">
                    <span class="text-2xl font-black text-red-600">
                      {{ targetRole === 'student' ? '239.000đ' : '599.000đ' }}
                    </span>
                    <span v-if="targetRole === 'student'" class="text-xs font-bold text-slate-400 line-through">599.000đ</span>
                    <span v-if="targetRole === 'student'" class="text-[9px] font-black text-amber-800 bg-amber-100 px-1 py-0.2 rounded uppercase">Tiết kiệm 360K</span>
                  </div>
                  <div class="text-[10px] text-slate-500">
                    Thời hạn: 1 năm (365 ngày)
                  </div>
                </div>

                <ul class="text-xs text-slate-700 space-y-2">
                  <li class="flex items-center gap-1.5 font-black text-red-600">
                    <span>🔥</span>
                    <span>GIẢM 40% MỌI CHUYẾN XE</span>
                  </li>
                  <li class="flex items-center gap-1.5 font-bold text-amber-900">
                    <span class="text-emerald-600">✓</span>
                    <span>Mã độc quyền ULTRA40</span>
                  </li>
                  <li class="flex items-center gap-1.5 font-medium">
                    <span class="text-emerald-600">✓</span>
                    <span>Ưu tiên chọn chỗ VIP tầng 1</span>
                  </li>
                  <li class="flex items-center gap-1.5 font-medium">
                    <span class="text-emerald-600">✓</span>
                    <span>Đổi/hoàn vé miễn phí 100%</span>
                  </li>
                </ul>
              </div>


              <button
                type="button"
                class="mt-4 w-full py-2.5 rounded-xl font-black text-xs transition cursor-pointer shadow-sm"
                :class="selectedPlan === 'ULTRA_40' ? 'bg-[#dc2626] text-white hover:bg-red-700' : 'bg-amber-400 hover:bg-amber-500 text-slate-950'"
              >
                <span>{{ isActivePlan('ULTRA_40') ? '✓ Gói Bạn Đang Dùng' : (selectedPlan === 'ULTRA_40' ? 'Đã Chọn Gói Này' : 'Nâng Cấp Gói ULTRA 40%') }}</span>
              </button>
            </div>

          </div>

        </div>

        <!-- BƯỚC 2: MÀN HÌNH THANH TOÁN THẬT VIETQR MBBANK (TỰ ĐỘNG NHẬN DIỆN CHUYỂN TIỀN) -->
        <div v-else-if="modalStep === 'payment'" class="p-4 sm:p-6 overflow-y-auto space-y-4 flex-1">
          <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-center">
            
            <!-- Cột QR Code (5/12) -->
            <div class="md:col-span-5 flex flex-col items-center text-center p-4 bg-slate-50 border border-slate-200 rounded-3xl shadow-xs">
              <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 font-black text-[11px] mb-2 border border-blue-200">
                <span>🏦 MBBank Quân Đội</span>
                <span>•</span>
                <span>VietQR 2.0</span>
              </div>

              <div class="relative p-2 bg-white rounded-2xl border-2 border-red-500 shadow-md">
                <img 
                  :src="vietQrUrl" 
                  alt="Mã VietQR Thanh Toán MBBank"
                  class="w-48 h-48 sm:w-56 sm:h-56 object-contain"
                />
                <div class="absolute inset-x-0 bottom-1 text-center">
                  <span class="text-[9px] bg-red-600 text-white px-2 py-0.5 rounded-full font-bold uppercase shadow-2xs">
                    Quét bằng mọi App Ngân Hàng
                  </span>
                </div>
              </div>

              <!-- Thời gian hiệu lực mã QR -->
              <div class="mt-3 flex items-center justify-center gap-2 text-xs font-mono font-bold text-slate-700 bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-xl">
                <span>⏱️ Mã QR hết hạn sau:</span>
                <span class="text-red-600 font-black">{{ formattedPaymentTime }}</span>
              </div>
            </div>

            <!-- Cột Thông Tin Chuyển Khoản (7/12) -->
            <div class="md:col-span-7 space-y-3">
              <div class="p-3 bg-red-50 border border-red-200 rounded-2xl text-xs text-red-900 flex items-center justify-between">
                <div>
                  <span class="text-slate-500 block text-[11px]">Gói đăng ký:</span>
                  <span class="font-black text-red-700 text-sm uppercase">{{ currentPlanDetails.name }}</span>
                </div>
                <div class="text-right">
                  <span class="text-slate-500 block text-[11px]">Số tiền cần chuyển:</span>
                  <span class="font-black text-red-600 text-lg">{{ currentPriceFormatted }}</span>
                </div>
              </div>

              <!-- Chi tiết tài khoản ngân hàng -->
              <div class="space-y-2 text-xs">
                <!-- Ngân hàng -->
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                  <span class="text-slate-500">Ngân hàng:</span>
                  <span class="font-bold text-slate-800">MBBank (Ngân Hàng Quân Đội)</span>
                </div>

                <!-- Số tài khoản -->
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-2">
                  <div>
                    <span class="text-slate-500 block text-[10.5px]">Số tài khoản (STK):</span>
                    <span class="font-mono font-black text-slate-900 text-base">09025042004</span>
                  </div>
                  <button
                    type="button"
                    @click="copyText('09025042004', 'Số tài khoản')"
                    class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg font-bold text-[11px] cursor-pointer transition shadow-2xs"
                  >
                    Sao chép
                  </button>
                </div>

                <!-- Chủ tài khoản -->
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
                  <span class="text-slate-500">Chủ tài khoản:</span>
                  <span class="font-black text-slate-900">VO TRONG HIEU</span>
                </div>

                <!-- Số tiền -->
                <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between gap-2">
                  <div>
                    <span class="text-slate-500 block text-[10.5px]">Số tiền chính xác:</span>
                    <span class="font-mono font-black text-red-600 text-base">{{ currentPriceFormatted }}</span>
                  </div>
                  <button
                    type="button"
                    @click="copyText(String(currentPrice), 'Số tiền')"
                    class="px-2.5 py-1 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 rounded-lg font-bold text-[11px] cursor-pointer transition shadow-2xs"
                  >
                    Sao chép
                  </button>
                </div>

                <!-- Nội dung chuyển khoản -->
                <div class="p-2.5 bg-amber-50/70 rounded-xl border border-amber-300 flex items-center justify-between gap-2">
                  <div>
                    <span class="text-amber-900 block text-[10.5px] font-bold">Nội dung chuyển khoản (bắt buộc):</span>
                    <span class="font-mono font-black text-amber-950 text-sm tracking-wide">{{ transferMemo }}</span>
                  </div>
                  <button
                    type="button"
                    @click="copyText(transferMemo, 'Nội dung chuyển khoản')"
                    class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-black text-[11px] cursor-pointer transition shadow-2xs"
                  >
                    Sao chép
                  </button>
                </div>
              </div>

              <!-- Hộp thông báo tự động kích hoạt -->
              <div class="p-3 bg-emerald-50 border border-emerald-300 rounded-2xl text-xs text-emerald-950 flex items-center gap-2.5 shadow-2xs">
                <span class="text-xl shrink-0">⚡</span>
                <span class="font-medium leading-relaxed">
                  Hệ thống <strong>tự động nhận diện thanh toán</strong> qua MBBank 24/7. Ngay khi bạn quét mã và chuyển tiền thành công, màn hình sẽ tự động chuyển sang trang kích hoạt và cấp mã ưu đãi cho bạn!
                </span>
              </div>

              <!-- Toast copy notification -->
              <div v-if="copyToast" class="p-2 bg-emerald-600 text-white rounded-xl text-xs font-bold text-center animate-in fade-in">
                ✓ {{ copyToast }}
              </div>
            </div>

          </div>
        </div>

        <!-- BƯỚC 3: MÀN HÌNH CHÚC MỪNG & HIỆN MÃ ƯU ĐÃI TRƯỚC MẶT LIỀN -->
        <div v-else-if="modalStep === 'success'" class="p-6 sm:p-8 overflow-y-auto space-y-6 flex-1 text-center animate-in zoom-in-95 duration-200">
          <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-md ring-8 ring-emerald-50">
            ✓
          </div>

          <div class="space-y-1.5">
            <h3 class="text-2xl font-black text-slate-900">
              KÍCH HOẠT THÀNH CÔNG!
            </h3>
            <p class="text-xs sm:text-sm text-slate-600 max-w-md mx-auto">
              Chúc mừng <strong>{{ authUser?.name }}</strong>! Tài khoản của bạn đã được nâng cấp lên 
              <strong class="text-red-600">{{ activatedPlanData?.plan_name }}</strong>.
            </p>
          </div>

          <!-- KHUNG HỘI VIÊN CHUẨN HIỆN DIỆN -->
          <div class="max-w-md mx-auto p-4 rounded-3xl border-2 shadow-md space-y-3"
               :class="activatedPlanData?.plan === 'ULTRA' ? 'bg-gradient-to-br from-amber-500/10 via-white to-orange-50 border-amber-400' : 'bg-gradient-to-br from-emerald-500/10 via-white to-teal-50 border-emerald-400'">
            <div class="flex items-center justify-between">
              <span class="text-xs font-black uppercase px-3 py-1 rounded-full text-white"
                    :class="activatedPlanData?.plan === 'ULTRA' ? 'bg-gradient-to-r from-amber-500 to-red-600' : 'bg-emerald-600'">
                {{ activatedPlanData?.plan === 'ULTRA' ? '👑 KHUNG HỘI VIÊN ULTRA' : '⭐ KHUNG HỘI VIÊN PRO' }}
              </span>
              <span class="text-xs font-mono font-black text-slate-700 bg-white px-2.5 py-0.5 rounded-full border border-slate-200">
                Thời hạn: 365 ngày
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-left text-xs pt-1 border-t border-slate-200/80">
              <div>
                <span class="text-[10.5px] text-slate-400 block font-bold">Ngày Bắt Đầu:</span>
                <span class="font-mono font-bold text-slate-800">{{ activatedPlanData?.registered_at }}</span>
              </div>
              <div class="text-right">
                <span class="text-[10.5px] text-slate-400 block font-bold">Ngày Hết Hạn:</span>
                <span class="font-mono font-bold text-slate-800">{{ activatedPlanData?.expires_at }}</span>
              </div>
            </div>

            <!-- HỘP MÃ ƯU ĐÃI HIỆN LÊN TRƯỚC MẶT -->
            <div class="p-4 bg-white rounded-2xl border-2 border-dashed border-red-500 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-inner">
              <div class="text-left">
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">MÃ ƯU ĐÃI ĐỘC QUYỀN CỦA BẠN:</span>
                <span class="font-mono font-black text-2xl text-red-600 tracking-wider block">
                  {{ activatedPlanData?.voucher_code }}
                </span>
                <span class="text-[11px] text-emerald-700 font-bold block mt-0.5">
                  Giảm {{ activatedPlanData?.discount_percent }}% tất cả chuyến xe suốt 1 năm!
                </span>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button
                  type="button"
                  @click="saveVoucherToWallet(activatedPlanData?.voucher_code)"
                  class="px-4 py-2.5 rounded-xl text-xs font-black shadow-xs transition active:scale-95 cursor-pointer flex items-center gap-1.5"
                  :class="isVoucherSaved(activatedPlanData?.voucher_code) ? 'bg-emerald-600 text-white' : 'bg-red-600 hover:bg-red-700 text-white'"
                >
                  <span>{{ isVoucherSaved(activatedPlanData?.voucher_code) ? '✓ Đã Lưu Vào Ví' : '🎟️ Lưu Mã Vào Ví' }}</span>
                </button>
                <button
                  type="button"
                  @click="copyText(activatedPlanData?.voucher_code, 'Mã ưu đãi')"
                  class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition active:scale-95 cursor-pointer"
                  title="Sao chép mã"
                >
                  📋
                </button>
              </div>
            </div>
          </div>

          <!-- Nút đặt vé ngay -->
          <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <button
              type="button"
              @click="goToBooking"
              class="w-full sm:w-auto px-8 py-3 bg-[#dc2626] hover:bg-red-700 text-white font-black text-sm rounded-2xl shadow-lg shadow-red-500/25 transition cursor-pointer"
            >
              🚌 ĐẶT VÉ VỚI ƯU ĐÃI NGAY ➔
            </button>
            <button
              type="button"
              @click="close"
              class="w-full sm:w-auto px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-2xl transition cursor-pointer"
            >
              Đóng Lại
            </button>
          </div>
        </div>

        <!-- FOOTER ACTIONS (CHO BƯỚC 1 & 2) -->
        <div v-if="modalStep !== 'success'" class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 flex-shrink-0">
          <div class="text-xs text-slate-500">
            <span>Thanh toán một lần qua <strong>VietQR MBBank</strong>. Kích hoạt tự động sau khi xác nhận.</span>
          </div>

          <div class="flex items-center gap-2">
            <!-- Nút Quay lại / Để sau -->
            <button
              v-if="modalStep === 'payment'"
              type="button"
              @click="modalStep = 'select_plan'"
              class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold cursor-pointer"
            >
              ← Chọn Gói Khác
            </button>
            <button
              v-else
              type="button"
              @click="close"
              class="px-4 py-2.5 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold cursor-pointer"
            >
              Để Sau
            </button>

            <!-- Nút Hành động chính -->
            <button
              v-if="modalStep === 'select_plan'"
              type="button"
              @click="handleMainActionClick"
              class="px-6 py-2.5 rounded-xl text-white font-black text-xs shadow-md transition cursor-pointer flex items-center gap-2 active:scale-95"
              :class="isGuest ? 'bg-gradient-to-r from-red-600 to-rose-600 hover:opacity-95' : (currentPlanInfo ? 'bg-red-600 hover:bg-red-700' : (isFreeStudentPro ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-[#dc2626] hover:bg-red-700'))"
            >
              <span v-if="isGuest">🔑 ĐĂNG KÝ / ĐĂNG NHẬP ĐỂ NHẬN ƯU ĐÃI ➔</span>
              <span v-else-if="currentPlanInfo">⚠️ HỦY GÓI ĐANG DÙNG ĐỂ MUA GÓI MỚI</span>
              <span v-else-if="isFreeStudentPro">🎉 KÍCH HOẠT GÓI SINH VIÊN PRO (0Đ) ➔</span>
              <span v-else-if="selectedPlan === 'PRO_30' && isStudentRebuyPro">THANH TOÁN MUA LẠI GÓI PRO (149.000đ) ➔</span>
              <span v-else>{{ selectedPlan.includes('ULTRA') ? `NÂNG CẤP GÓI ULTRA (${currentPriceFormatted}) ➔` : `THANH TOÁN VIETQR (${currentPriceFormatted}) ➔` }}</span>
            </button>

            <!-- Ở bước thanh toán: Đang tự động nhận diện chuyển khoản -->
            <div
              v-else-if="modalStep === 'payment'"
              class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 border border-slate-200 text-xs font-bold flex items-center gap-2"
            >
              <span class="animate-spin text-sm">⏳</span>
              <span>Chờ nhận diện chuyển khoản MBBank...</span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'open-auth']);
const page = usePage();
const authUser = computed(() => page.props.auth?.user || null);
const isGuest = computed(() => !authUser.value);

const isStudentUser = computed(() => {
  if (!authUser.value) return false;
  return Boolean(
    authUser.value.is_student ||
    String(authUser.value.email || '').includes('.edu.vn') ||
    String(authUser.value.email || '').includes('sinhvien')
  );
});

const isCancelledUser = computed(() => {
  if (!authUser.value) return false;
  const plan = String(authUser.value.membership_plan || '').toUpperCase();
  return plan === 'CANCELLED';
});

// Sinh viên mua lại sau khi đã hủy gói trước đó
const isStudentRebuyPro = computed(() => {
  return isStudentUser.value && isCancelledUser.value;
});

const targetRole = ref('student');
const selectedPlan = ref('ULTRA_40');
const modalStep = ref('select_plan'); // 'select_plan' | 'payment' | 'success'
const isCheckingPayment = ref(false);
const isCancelling = ref(false);
const copyToast = ref('');
const savedVoucherCodes = ref([]);
const paymentSecondsLeft = ref(600); // 10 phút
let paymentTimer = null;
let pollingInterval = null;
let waitingSeconds = 0;

const activatedPlanData = ref(null);

const onKeyDown = (e) => {
  if (e.key === 'Escape' && props.isOpen) {
    close();
  }
};

const selectPlan = (planCode) => {
  if (currentPlanInfo.value) {
    alert(`Bạn đang sử dụng "${currentPlanInfo.value.name}".\nTheo quy định, bạn cần bấm "Hủy Gói Đang Dùng" trước khi được mua gói khác!`);
    return;
  }
  selectedPlan.value = planCode;
};

const isActivePlan = (code) => {
  if (!currentPlanInfo.value) return false;
  const plan = String(authUser.value?.membership_plan || '').toUpperCase();
  if (plan === 'CANCELLED' || plan === 'STANDARD' || plan === 'THUONG' || !plan) return false;
  if (code.includes('ULTRA') && plan.includes('ULTRA')) return true;
  if (code.includes('PRO') && plan.includes('PRO')) return true;
  if (code.includes('ECO') && plan.includes('ECO')) return true;
  return false;
};

watch(() => props.isOpen, (open) => {
  if (open) {
    document.addEventListener('keydown', onKeyDown);
    modalStep.value = 'select_plan';
    targetRole.value = 'student';
    // Mặc định chọn Gói ULTRA
    selectedPlan.value = 'ULTRA_40';
  } else {
    document.removeEventListener('keydown', onKeyDown);
    stopAllTimers();
  }
});

onMounted(() => {
  initSavedVouchers();
});

onUnmounted(() => {
  document.removeEventListener('keydown', onKeyDown);
  stopAllTimers();
});

const stopAllTimers = () => {
  if (paymentTimer) clearInterval(paymentTimer);
  paymentTimer = null;
  if (pollingInterval) clearInterval(pollingInterval);
  pollingInterval = null;
  waitingSeconds = 0;
};

const currentPlanDetails = computed(() => {
  const isStudent = targetRole.value === 'student';
  const configs = {
    'ECO_20': {
      name: 'Gói ECO (Giảm 20%)',
      code: 'ECO_20',
      voucher_code: 'ECO20',
      discount_percent: 20,
      student_price: 49000,
      standard_price: 199000,
    },
    'PRO_30': {
      name: isStudent ? 'Gói Sinh Viên PRO (Giảm 30%)' : 'Gói PRO (Giảm 30%)',
      code: 'PRO',
      voucher_code: isStudent ? 'SINHVIEN30' : 'PRO30',
      discount_percent: 30,
      student_price: isStudentRebuyPro.value ? 149000 : 0,
      standard_price: 349000,
    },
    'PRO_25': {
      name: isStudent ? 'Gói Sinh Viên PRO (Giảm 30%)' : 'Gói PRO (Giảm 30%)',
      code: 'PRO',
      voucher_code: isStudent ? 'SINHVIEN30' : 'PRO30',
      discount_percent: 30,
      student_price: isStudentRebuyPro.value ? 149000 : 0,
      standard_price: 349000,
    },
    'ULTRA_40': {
      name: isStudent ? 'Gói Sinh Viên ULTRA (Giảm 40%)' : 'Gói ULTRA (Giảm 40%)',
      code: 'ULTRA',
      voucher_code: 'ULTRA40',
      discount_percent: 40,
      student_price: 239000,
      standard_price: 599000,
    },
  };
  return configs[selectedPlan.value] || configs['ULTRA_40'];
});

const isFreeStudentPro = computed(() => {
  if (isGuest.value) return false;
  if (isCancelledUser.value) return false;
  const plan = String(authUser.value?.membership_plan || '').toUpperCase();
  if (plan === 'CANCELLED') return false;
  return targetRole.value === 'student' && 
    (selectedPlan.value === 'PRO_30' || selectedPlan.value === 'PRO' || selectedPlan.value === 'PRO_25') &&
    !isActivePlan('PRO_30');
});

const currentPrice = computed(() => {
  return targetRole.value === 'student' 
    ? currentPlanDetails.value.student_price 
    : currentPlanDetails.value.standard_price;
});

const currentPriceFormatted = computed(() => {
  return new Intl.NumberFormat('vi-VN').format(currentPrice.value) + 'đ';
});

const transferMemo = computed(() => {
  const uid = authUser.value?.id || 'SV';
  const planTag = selectedPlan.value.includes('ULTRA') ? 'ULTRA' : (selectedPlan.value.includes('ECO') ? 'ECO' : 'PRO');
  return `HOIVIEN ${uid} ${planTag}`;
});

const vietQrUrl = computed(() => {
  const memoEncoded = encodeURIComponent(transferMemo.value);
  return `https://img.vietqr.io/image/MB-09025042004-compact2.png?amount=${currentPrice.value}&addInfo=${memoEncoded}&accountName=VO%20TRONG%20HIEU`;
});

const formattedPaymentTime = computed(() => {
  const m = Math.floor(paymentSecondsLeft.value / 60);
  const s = paymentSecondsLeft.value % 60;
  return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
});

const startPaymentPolling = () => {
  waitingSeconds = 0;
  if (pollingInterval) clearInterval(pollingInterval);

  pollingInterval = setInterval(async () => {
    waitingSeconds += 2;
    try {
      const res = await axios.get('/api/membership/check-payment', {
        params: {
          plan: selectedPlan.value,
          waiting_seconds: waitingSeconds
        }
      });
      if (res.data && res.data.paid) {
        handlePaymentSuccess(res.data);
      }
    } catch (err) {
      // Polling quietly in background
    }
  }, 2000);
};

const startPaymentTimer = () => {
  stopAllTimers();
  paymentSecondsLeft.value = 600;
  paymentTimer = setInterval(() => {
    if (paymentSecondsLeft.value > 0) {
      paymentSecondsLeft.value--;
    } else {
      stopAllTimers();
    }
  }, 1000);

  startPaymentPolling();
};

const copyText = (text, label) => {
  navigator.clipboard.writeText(text);
  copyToast.value = `Đã sao chép ${label}!`;
  setTimeout(() => {
    copyToast.value = '';
  }, 2500);
};

const handleCancelCurrentPlan = async () => {
  const planName = currentPlanInfo.value?.name || 'gói đang sử dụng';
  if (!confirm(`Bạn đang sử dụng "${planName}".\nBạn có chắc chắn muốn hủy gói này để có thể chọn mua gói khác không?`)) {
    return;
  }
  isCancelling.value = true;
  try {
    const res = await axios.post('/api/membership/cancel');
    if (res.data && res.data.success) {
      alert(res.data.message || 'Đã hủy gói thành công! Bây giờ bạn có thể chọn mua gói mới.');
      router.reload({ only: ['auth'] });
    } else {
      alert(res.data?.message || 'Không thể hủy gói. Vui lòng thử lại.');
    }
  } catch (err) {
    alert('Lỗi hệ thống khi hủy gói. Vui lòng thử lại.');
  } finally {
    isCancelling.value = false;
  }
};

const handleMainActionClick = () => {
  if (isGuest.value) {
    emit('open-auth');
    close();
    return;
  }
  if (currentPlanInfo.value) {
    handleCancelCurrentPlan();
    return;
  }
  if (isFreeStudentPro.value) {
    // Sinh viên mới kích hoạt gói 0đ
    handlePaymentSuccess({
      paid: true,
      plan: 'PRO',
      plan_name: 'Gói Sinh Viên PRO (Giảm 30%)',
      voucher_code: 'SINHVIEN30',
      discount_percent: 30,
      registered_at: authUser.value?.membership_registered_at || authUser.value?.student_registered_at || 'Từ ngày đăng ký',
      expires_at: authUser.value?.membership_expires_at || authUser.value?.student_discount_expires_at || '365 ngày',
      days_remaining: 365,
    });
    return;
  }
  goToPaymentStep();
};

const goToPaymentStep = () => {
  if (!authUser.value) {
    emit('open-auth');
    close();
    return;
  }
  modalStep.value = 'payment';
  startPaymentTimer();
};

const confirmPaymentNow = async () => {
  isCheckingPayment.value = true;
  try {
    const res = await axios.get('/api/membership/check-payment', {
      params: { plan: selectedPlan.value, confirm: 1 }
    });
    if (res.data && res.data.paid) {
      handlePaymentSuccess(res.data);
    } else {
      alert(res.data?.message || 'Hệ thống chưa nhận được khoản chuyển từ MBBank. Vui lòng quét mã trên App ngân hàng và thử lại sau.');
    }
  } catch (err) {
    alert('Không thể kết nối đến máy chủ xác nhận. Vui lòng thử lại.');
  } finally {
    isCheckingPayment.value = false;
  }
};

const initSavedVouchers = () => {
  try {
    const list = JSON.parse(localStorage.getItem('nhaxekientruc_saved_vouchers') || localStorage.getItem('bushub_saved_vouchers') || localStorage.getItem('saved_vouchers') || '[]');
    savedVoucherCodes.value = Array.isArray(list) ? list : [];
  } catch (e) {
    savedVoucherCodes.value = [];
  }
};

const isVoucherSaved = (code) => {
  if (!code) return false;
  return savedVoucherCodes.value.includes(code);
};

const saveVoucherToWallet = (code) => {
  if (!code) return;
  try {
    const keys = ['nhaxekientruc_saved_vouchers', 'bushub_saved_vouchers', 'saved_vouchers'];
    keys.forEach(k => {
      let list = [];
      try { list = JSON.parse(localStorage.getItem(k) || '[]'); } catch (e) {}
      if (!list.includes(code)) {
        list.push(code);
        localStorage.setItem(k, JSON.stringify(list));
      }
    });
    savedVoucherCodes.value = [...new Set([...savedVoucherCodes.value, code])];
    window.dispatchEvent(new CustomEvent('voucher-saved-to-wallet', { detail: { code } }));
    copyToast.value = `✓ Đã lưu mã [${code}] vào ví thành công!`;
    setTimeout(() => { copyToast.value = ''; }, 2500);
  } catch (e) {}
};

const handlePaymentSuccess = (data) => {
  stopAllTimers();
  activatedPlanData.value = data;
  modalStep.value = 'success';

  if (data?.voucher_code) {
    saveVoucherToWallet(data.voucher_code);
  }

  // Tự động tải lại thông tin auth trên Inertia để Navbar và Dropdown đổi khung ngay tức thì
  router.reload({ only: ['auth'] });
};

const goToBooking = () => {
  close();
  router.visit('/trips');
};

const currentPlanInfo = computed(() => {
  if (!authUser.value) return null;
  const user = authUser.value;
  const plan = String(user.membership_plan || '').toUpperCase();
  const daysLeft = user.student_days_remaining ?? 0;

  if (!plan || plan === 'STANDARD' || plan === 'THUONG' || plan === 'CANCELLED') {
    return null;
  }

  const planName = plan.includes('ULTRA')
    ? 'Gói ULTRA (Giảm 40%)' 
    : (plan.includes('PRO') 
        ? (user.is_student ? 'Gói Sinh Viên PRO (Giảm 30%)' : 'Gói PRO (Giảm 30%)') 
        : (plan.includes('ECO') 
            ? 'Gói ECO (Giảm 20%)' 
            : 'Gói Hội Viên'));

  const regDate = user.student_registered_at || user.membership_registered_at || 'Từ ngày đăng ký';
  const expDate = user.membership_expires_at || user.student_discount_expires_at || '1 năm';

  return {
    name: planName,
    registered_at: regDate,
    expires_at: expDate,
    days_left: Math.max(0, daysLeft),
  };
});

const close = () => {
  modalStep.value = 'select_plan';
  stopAllTimers();
  emit('close');
};
</script>
