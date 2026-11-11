<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-sans selection:bg-red-500 selection:text-white">
    <Navbar />

    <!-- TOP BREADCRUMB & COUNTDOWN BAR (LIGHT & PREMIUM) -->
    <div class="border-b border-slate-200 bg-white/95 backdrop-blur-md sticky top-0 z-20 shadow-2xs">
      <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between text-xs">
        <div class="flex items-center gap-2 text-slate-500">
          <Link href="/" class="hover:text-red-600 transition font-medium">Trang chủ</Link>
          <span>/</span>
          <span class="text-slate-700 font-bold">Vé #{{ booking.booking_code }}</span>
          <span>/</span>
          <span class="text-red-600 font-bold">Thanh Toán Trực Tuyến</span>
        </div>

        <!-- ĐỒNG HỒ ĐẾM NGƯỢC GIỮ CHỖ 5 PHÚT -->
        <div 
          class="flex items-center gap-2 px-3 py-1.5 rounded-full font-mono font-black text-xs transition-all shadow-2xs border"
          :class="timeLeft <= 60 
            ? 'bg-rose-50 text-rose-700 border-rose-300 animate-pulse' 
            : 'bg-amber-50 text-amber-800 border-amber-300'"
        >
          <span class="text-sm">{{ timeLeft <= 60 ? '🔥' : '⏳' }}</span>
          <span>Thời gian giữ chỗ:</span>
          <strong class="text-sm tracking-wider">{{ formattedCountdown }}</strong>
        </div>
      </div>
    </div>

    <!-- MAIN BODY -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-6 sm:py-8 space-y-6">

      <!-- SUCCESS HERO (KHI ĐÃ THANH TOÁN THÀNH CÔNG) -->
      <div v-if="isPaid" class="bg-white border-2 border-emerald-500 rounded-3xl p-8 sm:p-12 text-center space-y-6 shadow-xl animate-in fade-in zoom-in duration-200">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-100 text-emerald-600 border border-emerald-200 flex items-center justify-center text-4xl shadow-md">
          ✓
        </div>
        <div class="space-y-2">
          <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
            XÁC NHẬN GIAO DỊCH THÀNH CÔNG
          </span>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
            Thanh Toán Vé Xe Thành Công!
          </h1>
          <p class="text-sm text-slate-600 max-w-lg mx-auto leading-relaxed">
            Hệ thống đã nhận được tiền chuyển khoản của Quý khách. Đang tự động chuyển sang trang Quản Lý Vé & Thẻ Lên Xe Điện Tử...
          </p>
        </div>

        <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
          <Link 
            :href="`/booking/${booking.booking_code}/detail`"
            class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs uppercase tracking-wider transition shadow-lg shadow-red-500/25 cursor-pointer active:scale-98"
          >
            Mở Trang Quản Lý Vé & Mã QR Ngay →
          </Link>
        </div>
      </div>

      <!-- MAIN PAYMENT CONTENT (KHI ĐANG CHỜ THANH TOÁN) -->
      <div v-else class="space-y-6">

        <!-- TIÊU ĐỀ TRANG & CHỌN PHƯƠNG THỨC THANH TOÁN -->
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span 
                class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase border"
                :class="booking.is_deposit ? 'bg-amber-50 text-amber-800 border-amber-300' : 'bg-red-50 text-red-700 border-red-200'"
              >
                {{ booking.is_deposit ? '⚡ ĐẶT CỌC 30% GIỮ CHỖ' : 'CỔNG THANH TOÁN TỰ ĐỘNG 24/7' }}
              </span>
              <span class="text-xs font-mono font-bold text-slate-400">#{{ booking.booking_code }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
              {{ booking.is_deposit ? 'Thanh Toán Tiền Cọc 30% Giữ Chỗ' : 'Thanh Toán Vé Xe Trực Tuyến' }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
              <span v-if="booking.is_deposit">
                Quý khách chọn <strong>đặt cọc trước 30%</strong> để giữ chỗ. Số tiền còn lại <strong>70% ({{ formatPrice(booking.remaining_amount) }}đ)</strong> sẽ thanh toán cho Bác tài / Lơ xe khi lên xe.
              </span>
              <span v-else>
                Quét mã QR bằng App Ngân Hàng hoặc Ví MoMo. Mã QR đã nhúng sẵn <strong>số tiền</strong> và <strong>nội dung chuyển tiền</strong>.
              </span>
            </p>
          </div>

          <!-- BỘ CHUYỂN TAB MBBANK & MOMO (GIAO DIỆN SÁNG CAO CẤP) -->
          <div class="flex items-center gap-2 p-1.5 bg-slate-100 rounded-2xl border border-slate-200 self-start md:self-auto w-full md:w-auto">
            <!-- Tab MBBank -->
            <button
              @click="activeMethod = 'MBBANK'"
              type="button"
              class="flex-1 md:flex-initial px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 cursor-pointer"
              :class="activeMethod === 'MBBANK' 
                ? 'bg-white text-blue-900 shadow-md border border-blue-200 ring-2 ring-blue-500/10' 
                : 'text-slate-600 hover:text-slate-900'"
            >
              <span class="text-base">🏦</span>
              <div class="text-left">
                <span class="block leading-none">MBBank (VietQR)</span>
                <span class="text-[9.5px] font-medium text-slate-400">Mọi ngân hàng VN</span>
              </div>
            </button>

            <!-- Tab MoMo -->
            <button
              @click="activeMethod = 'MOMO'"
              type="button"
              class="flex-1 md:flex-initial px-4 py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 cursor-pointer"
              :class="activeMethod === 'MOMO' 
                ? 'bg-white text-pink-700 shadow-md border border-pink-200 ring-2 ring-pink-500/10' 
                : 'text-slate-600 hover:text-slate-900'"
            >
              <span class="text-base">📱</span>
              <div class="text-left">
                <span class="block leading-none">Ví MoMo</span>
                <span class="text-[9.5px] font-medium text-slate-400">Quét qua App MoMo</span>
              </div>
            </button>
          </div>
        </div>

        <!-- GRID 2 CỘT: CỘT TRÁI (QR ĐỘNG TO BẢN) - CỘT PHẢI (THÔNG TIN CHUYỂN KHOẢN BẤM CHAY) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
          
          <!-- ========================================================= -->
          <!-- CỘT TRÁI: MÃ QR ĐỘNG CÓ SẴN TIỀN VÀ NỘI DUNG (5 COLS) -->
          <!-- ========================================================= -->
          <div class="lg:col-span-5 bg-white border border-slate-200 rounded-3xl p-6 sm:p-7 space-y-5 shadow-sm text-center">
            
            <!-- Header mã QR -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
              <div class="flex items-center gap-2.5 text-left">
                <div 
                  class="w-10 h-10 rounded-2xl flex items-center justify-center text-white font-black text-sm shadow-xs"
                  :class="activeMethod === 'MBBANK' ? 'bg-blue-600' : 'bg-pink-600'"
                >
                  {{ activeMethod === 'MBBANK' ? 'MB' : 'MO' }}
                </div>
                <div>
                  <h3 class="text-sm font-black text-slate-900">
                    {{ activeMethod === 'MBBANK' ? 'Mã QR Ngân Hàng MBBank' : 'Mã QR Ví Điện Tử MoMo' }}
                  </h3>
                  <p class="text-[11px] text-slate-500 font-medium">
                    {{ activeMethod === 'MBBANK' ? 'Chủ TK: VO TRONG HIEU • STK: 09025042004' : 'Chủ Ví: VO TRONG HIEU • 09025042004' }}
                  </p>
                </div>
              </div>

              <span 
                class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase border"
                :class="activeMethod === 'MBBANK' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-pink-50 text-pink-700 border-pink-200'"
              >
                {{ activeMethod === 'MBBANK' ? 'NAPAS 247' : 'MOMO QR' }}
              </span>
            </div>

            <!-- THÔNG BÁO TỰ ĐỘNG ĐIỀN TIỀN & LỜI NHẮN -->
            <div class="p-2.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-left flex items-center gap-2 text-xs text-emerald-800">
              <span class="text-base shrink-0">✨</span>
              <span class="text-[11px] leading-tight font-medium">
                Quét mã này sẽ <strong>tự động điền số tiền {{ vietQr.amount_formatted }}</strong> và nội dung <strong>{{ vietQr.content }}</strong>!
              </span>
            </div>

            <!-- KHUNG HÌNH ẢNH MÃ QR TO BẢN & SẮC NÉT (CÓ SẴN TIỀN VÀ NỘI DUNG) -->
            <div class="p-3 bg-slate-50/80 rounded-3xl border-2 border-dashed border-slate-300 flex flex-col items-center justify-center">
              
              <div class="w-full max-w-[340px] bg-white p-2.5 rounded-2xl border border-slate-200 shadow-md">
                
                <!-- ẢNH QR MBBANK (VIETQR NAPAS 247 CÓ SẴN TIỀN & ND) -->
                <img 
                  v-if="activeMethod === 'MBBANK'"
                  :src="mbbankCurrentQr" 
                  @error="onMbbankQrError"
                  alt="Mã VietQR MBBank có sẵn tiền và nội dung"
                  class="w-full h-auto object-contain rounded-xl block mx-auto transition-all"
                />

                <!-- ẢNH QR MOMO (VIETQR MOMO CÓ SẴN TIỀN & ND) -->
                <img 
                  v-else
                  :src="momoCurrentQr" 
                  @error="onMomoQrError"
                  alt="Mã QR MoMo có sẵn tiền và nội dung"
                  class="w-full h-auto object-contain rounded-xl block mx-auto transition-all"
                />
              </div>

              <!-- Thông tin đính kèm dưới QR -->
              <div class="mt-3 text-center space-y-0.5">
                <div class="text-xs font-black text-slate-900">
                  VO TRONG HIEU &bull; <span class="font-mono text-red-600">09025042004</span>
                </div>
                <div class="text-[11px] font-mono text-slate-600">
                  Nội dung chuyển: <strong class="text-amber-900 bg-amber-100 px-1.5 py-0.5 rounded">{{ vietQr.content }}</strong>
                </div>
              </div>
            </div>

            <!-- Hướng dẫn quét 3 bước -->
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 text-left text-xs space-y-2">
              <span class="text-[10.5px] font-black uppercase text-slate-400 block tracking-wider">
                Hướng Dẫn Quét Mã Trong 15 Giây:
              </span>
              <div class="flex items-start gap-2 text-slate-700">
                <span class="w-4 h-4 rounded-full bg-red-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">1</span>
                <span>Mở App Ngân Hàng bất kỳ (hoặc Ví MoMo) trên điện thoại</span>
              </div>
              <div class="flex items-start gap-2 text-slate-700">
                <span class="w-4 h-4 rounded-full bg-red-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">2</span>
                <span>Chọn <strong>"Quét Mã QR"</strong> và hướng camera vào mã ở trên</span>
              </div>
              <div class="flex items-start gap-2 text-slate-700">
                <span class="w-4 h-4 rounded-full bg-red-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0 mt-0.5">3</span>
                <span>App đã tự điền sẵn tiền & nội dung &rarr; bấm <strong>"Xác nhận chuyển khoản"</strong></span>
              </div>
            </div>

            <p class="text-[11px] text-slate-500 italic">
              {{ activeMethod === 'MBBANK' 
                ? 'Hỗ trợ quét bằng MBBank, Vietcombank, BIDV, Techcombank, VPBank, ACB và hơn 40 ngân hàng khác' 
                : 'Mở ứng dụng Ví MoMo trên điện thoại để quét mã nhanh chóng' }}
            </p>

          </div>

          <!-- ========================================================= -->
          <!-- CỘT PHẢI: DÀNH CHO KHÁCH BẤM STK CHAY (NỘI DUNG VIẾT SÁT NHAU) -->
          <!-- ========================================================= -->
          <div class="lg:col-span-7 space-y-5">
            
            <!-- HỘP THÔNG TIN CHUYỂN TIỀN THỦ CÔNG (BẤM STK CHAY) -->
            <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-7 space-y-4 shadow-sm">
              <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                  <span class="text-xs font-black uppercase tracking-wider text-slate-400 block">
                    Thông Tin Chuyển Khoản
                  </span>
                  <span class="text-[11px] text-slate-500">
                    Nếu bạn không quét được mã QR, hãy dùng thông tin dưới đây để bấm STK chay:
                  </span>
                </div>
                <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 shrink-0">
                  ✓ Tài khoản chính chủ
                </span>
              </div>

              <!-- SỐ TIỀN CẦN THANH TOÁN (LỚN & RÕ RÀNG) -->
              <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-red-50/80 via-white to-red-50/50 border border-red-200 flex items-center justify-between gap-3">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                      {{ booking.is_deposit ? 'Số Tiền Đặt Cọc 30% Cần Chuyển:' : 'Số Tiền Cần Thanh Toán:' }}
                    </span>
                    <span v-if="booking.is_deposit" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-300">
                      ⚡ Cọc 30%
                    </span>
                  </div>
                  <span class="text-2xl sm:text-3xl font-black text-red-600 font-mono tracking-tight block mt-0.5">
                    {{ vietQr.amount_formatted }}
                  </span>
                  <span class="text-[11.5px] text-slate-600 font-medium leading-relaxed block mt-0.5">
                    <template v-if="booking.is_deposit">
                      (Đã tính đúng 30% cọc giữ chỗ. Còn lại 70% tương ứng <strong class="text-slate-900">{{ formatPrice(booking.remaining_amount) }}đ</strong> Quý khách sẽ trả cho Bác tài / Lơ xe khi lên xe)
                    </template>
                    <template v-else>
                      (Thanh toán đủ 100% để hệ thống tự động xuất vé điện tử)
                    </template>
                  </span>
                </div>
                <button 
                  @click="copyText(vietQr.amount, 'amount')"
                  type="button"
                  class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm shadow-red-500/20 cursor-pointer active:scale-95 shrink-0"
                >
                  <span>{{ copiedField === 'amount' ? '✓ Đã sao chép' : '📋 Sao Chép Số Tiền' }}</span>
                </button>
              </div>

              <!-- NỘI DUNG CHUYỂN KHOẢN (VIẾT LIỀN SÁT NHAU - KHÔNG CÓ DẤU CÁCH) -->
              <div class="p-4 sm:p-5 rounded-2xl bg-amber-50/90 border-2 border-amber-400 space-y-3">
                <div class="flex items-center justify-between gap-3">
                  <div class="flex-1">
                    <div class="flex items-center gap-1.5">
                      <span class="text-base">⚠️</span>
                      <span class="text-[11px] font-black uppercase text-amber-900 tracking-wider">
                        NỘI DUNG CHUYỂN TIỀN (VIẾT SÁT NHAU - KHÔNG CÁCH):
                      </span>
                    </div>
                    
                    <!-- CHỮ SÁT NHAU TO, RÕ RÀNG, DỄ NHÌN NHẤT -->
                    <div class="mt-1 flex items-center gap-2">
                      <span class="text-2xl sm:text-3xl font-black text-amber-950 font-mono tracking-wider bg-white px-3 py-1 rounded-xl border border-amber-300 shadow-2xs inline-block">
                        {{ vietQr.content }}
                      </span>
                    </div>
                  </div>

                  <!-- NÚT SAO CHÉP NỘI DUNG SÁT NHAU -->
                  <button 
                    @click="copyText(vietQr.content, 'content')"
                    type="button"
                    class="px-5 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs transition flex items-center gap-1.5 shadow-md shadow-amber-500/20 cursor-pointer active:scale-95 shrink-0"
                  >
                    <span>{{ copiedField === 'content' ? '✓ Đã sao chép!' : '📋 Sao Chép Nội Dung' }}</span>
                  </button>
                </div>

                <div class="pt-2 border-t border-amber-200/80 text-[11.5px] text-amber-900 flex items-start gap-1.5 leading-relaxed">
                  <span class="font-bold shrink-0">💡 Lưu ý quan trọng:</span>
                  <span>
                    Nội dung chuyển khoản được viết liền sát nhau: <strong class="font-mono bg-amber-200/70 px-1 py-0.5 rounded">{{ vietQr.content }}</strong> (không có khoảng cách, không có dấu cách). Khi bạn bấm STK chay chỉ cần gõ đúng chuỗi này là hệ thống nhận diện tự động 100%!
                  </span>
                </div>
              </div>

              <!-- CHI TIẾT TÀI KHOẢN THỤ HƯỞNG THEO TAB -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-1">
                <!-- Ngân hàng / Kênh -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                  <span class="text-[10.5px] text-slate-400 block font-bold uppercase">Kênh Thanh Toán:</span>
                  <strong class="text-slate-900 text-sm block mt-0.5">
                    {{ activeMethod === 'MBBANK' ? 'MBBank (Ngân Hàng Quân Đội)' : 'Ví Điện Tử MoMo' }}
                  </strong>
                </div>

                <!-- Số tài khoản / Số ví -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                  <div>
                    <span class="text-[10.5px] text-slate-400 block font-bold uppercase">
                      {{ activeMethod === 'MBBANK' ? 'Số Tài Khoản (STK):' : 'Số Ví MoMo:' }}
                    </span>
                    <strong class="text-slate-900 text-sm font-mono font-black block mt-0.5 tracking-wider">
                      09025042004
                    </strong>
                  </div>
                  <button 
                    @click="copyText('09025042004', 'acc_no')"
                    type="button"
                    class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 text-[11px] font-bold border border-slate-200 shadow-2xs cursor-pointer active:scale-95"
                  >
                    {{ copiedField === 'acc_no' ? '✓ Đã chép' : 'Sao chép STK' }}
                  </button>
                </div>

                <!-- Chủ tài khoản -->
                <div class="sm:col-span-2 p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                  <div>
                    <span class="text-[10.5px] text-slate-400 block font-bold uppercase">Chủ Tài Khoản / Chủ Ví Thụ Hưởng:</span>
                    <strong class="text-slate-900 text-sm uppercase font-black block mt-0.5">VO TRONG HIEU</strong>
                  </div>
                  <button 
                    @click="copyText('VO TRONG HIEU', 'acc_name')"
                    type="button"
                    class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-700 text-[11px] font-bold border border-slate-200 shadow-2xs cursor-pointer active:scale-95"
                  >
                    {{ copiedField === 'acc_name' ? '✓ Đã chép' : 'Sao chép Tên' }}
                  </button>
                </div>
              </div>
            </div>

            <!-- TÓM TẮT CHUYẾN XE ĐÃ ĐẶT -->
            <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-3 text-xs">
              <span class="text-xs font-black uppercase text-slate-400 tracking-wider block">
                Thông Tin Chuyến Xe Đã Đặt
              </span>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1 border-t border-slate-100">
                <div>
                  <span class="text-slate-400 block text-[10.5px]">Tuyến xe:</span>
                  <strong class="text-slate-900 font-bold block truncate">{{ booking.route_name }}</strong>
                </div>
                <div>
                  <span class="text-slate-400 block text-[10.5px]">Giờ khởi hành:</span>
                  <strong class="text-red-600 font-bold block">{{ booking.departure_time }}</strong>
                </div>
                <div>
                  <span class="text-slate-400 block text-[10.5px]">Chỗ ngồi:</span>
                  <strong class="text-indigo-600 font-mono font-black block">Ghế #{{ booking.seats_label }}</strong>
                </div>
                <div>
                  <span class="text-slate-400 block text-[10.5px]">Hành khách:</span>
                  <strong class="text-slate-900 font-bold block truncate">{{ booking.customer_name }}</strong>
                </div>
              </div>
            </div>

            <!-- KHỐI TỰ ĐỘNG NHẬN DIỆN CHUYỂN KHOẢN (REAL-TIME 24/7) -->
            <div class="p-5 rounded-3xl bg-gradient-to-br from-emerald-50 via-white to-teal-50 border-2 border-emerald-300 shadow-sm space-y-3">
              <div class="flex items-start gap-3.5">
                <div class="relative flex items-center justify-center shrink-0 mt-0.5">
                  <span class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-lg font-black shadow-md shadow-emerald-500/20">
                    ⚡
                  </span>
                  <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                  <span class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-emerald-600"></span>
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-black text-emerald-800 uppercase tracking-wider">
                      HỆ THỐNG ĐANG TỰ ĐỘNG LẮNG NGHE GIAO DỊCH CHUYỂN KHOẢN
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 animate-pulse">
                      Real-time 24/7
                    </span>
                  </div>
                  <p class="text-[11.5px] text-slate-600 mt-1 leading-relaxed">
                    Sau khi bạn chuyển khoản thành công với nội dung <strong class="text-amber-800 font-mono bg-amber-100/80 px-1 py-0.5 rounded">{{ vietQr.content }}</strong>, hệ thống sẽ <strong>TỰ ĐỘNG CHUYỂN SANG TRANG VÉ</strong> trong giây lát mà bạn <strong>không cần bấm thêm bất kỳ nút nào</strong>!
                  </p>
                </div>
              </div>

              <!-- Thanh sóng radar quét tự động -->
              <div class="w-full bg-white rounded-xl p-2.5 border border-emerald-200 flex items-center justify-between text-xs text-slate-500">
                <div class="flex items-center gap-2.5">
                  <div class="flex space-x-1">
                    <span class="w-1.5 h-4 bg-emerald-500 rounded-full animate-bounce [animation-delay:0ms]"></span>
                    <span class="w-1.5 h-4 bg-emerald-400 rounded-full animate-bounce [animation-delay:150ms]"></span>
                    <span class="w-1.5 h-4 bg-emerald-300 rounded-full animate-bounce [animation-delay:300ms]"></span>
                  </div>
                  <span class="text-[11px] text-emerald-700 font-bold">Đang kết nối tài khoản ngân hàng & MoMo theo thời gian thực...</span>
                </div>
                <span class="text-[10px] text-slate-400 font-mono">Tự động kích hoạt vé</span>
              </div>
            </div>

            <!-- KHỐI DỰ PHÒNG XÁC NHẬN KHI ĐÃ CHUYỂN TIỀN THẬT TỪ APP NGÂN HÀNG -->
            <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-xs text-center space-y-2.5">
              <div class="text-left space-y-0.5">
                <span class="text-xs font-bold text-slate-800 block">Đã thực hiện chuyển khoản từ App MBBank / MoMo?</span>
                <span class="text-[11px] text-slate-500 block">Sau khi chuyển tiền xong, nếu cổng ngân hàng chưa kịp đẩy tín hiệu tự động, bạn có thể bấm nút dưới đây để kích hoạt vé ngay:</span>
              </div>
              <button
                @click="manualConfirm"
                :disabled="isConfirming"
                type="button"
                class="w-full py-3 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs uppercase tracking-wider transition shadow-md shadow-red-500/20 cursor-pointer disabled:opacity-50 active:scale-95"
              >
                <span v-if="isConfirming">⏳ Đang kích hoạt vé & gửi email...</span>
                <span v-else>✓ Tôi Đã Chuyển Khoản Rồi - Kích Hoạt Vé Ngay</span>
              </button>
            </div>

          </div>

        </div>

      </div>
    </main>

    <!-- ============================================================== -->
    <!-- MODAL THANH TOÁN THÀNH CÔNG (TỰ ĐỘNG CHUYỂN TRANG) -->
    <!-- ============================================================== -->
    <div 
      v-if="isPaid"
      class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 text-center space-y-4 shadow-2xl border-2 border-emerald-400">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-inner animate-bounce">
          ✓
        </div>
        <div class="space-y-1.5">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
            {{ booking.is_deposit ? 'XÁC NHẬN ĐẶT CỌC 30% THÀNH CÔNG' : 'THANH TOÁN THÀNH CÔNG' }}
          </span>
          <h3 class="text-lg sm:text-xl font-black text-slate-900">
            Hệ Thống Đã Nhận Được Tiền!
          </h3>
          <p class="text-xs text-slate-600 leading-relaxed max-w-sm mx-auto">
            Giao dịch chuyển khoản qua tài khoản <strong class="text-slate-900">09025042004 - VO TRONG HIEU</strong> đã được ghi nhận. Thẻ lên xe điện tử đã được gửi về Gmail của Quý khách.
          </p>
        </div>

        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 flex items-center justify-center gap-2 text-xs font-bold text-emerald-800">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
          <span>Đang tự động chuyển sang trang Quản Lý Vé...</span>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL HẾT GIỜ GIỮ CHỖ (KHI QUÁ 5 PHÚT MÀ CHƯA CHUYỂN TIỀN) -->
    <!-- ============================================================== -->
    <div 
      v-if="isExpired && !isPaid"
      class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in duration-200"
    >
      <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 text-center space-y-5 shadow-2xl border border-slate-200">
        <div class="w-16 h-16 rounded-3xl bg-rose-100 text-rose-600 flex items-center justify-center text-3xl mx-auto shadow-inner">
          ⏱️
        </div>
        <div class="space-y-1.5">
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-50 text-rose-700 border border-rose-200">
            HẾT THỜI GIAN GIỮ CHỖ
          </span>
          <h3 class="text-lg sm:text-xl font-black text-slate-900">
            Đã Hết Thời Gian 5 Phút!
          </h3>
          <p class="text-xs text-slate-600 leading-relaxed max-w-sm mx-auto">
            Thời gian giữ chỗ ngồi cho mã vé <strong class="font-mono text-slate-900">#{{ booking.booking_code }}</strong> đã kết thúc. Vị trí ghế đã được tự động giải phóng để nhường quyền ưu tiên cho hành khách khác.
          </p>
        </div>

        <div class="pt-2 flex flex-col gap-2">
          <Link
            :href="booking.trip_id ? `/trips/${booking.trip_id}` : '/trips'"
            class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-black text-xs rounded-xl shadow-md transition active:scale-98 cursor-pointer uppercase tracking-wider"
          >
            🔄 Chọn Lại Chuyến Xe & Đặt Lại
          </Link>
          <Link
            href="/"
            class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer"
          >
            Về Trang Chủ
          </Link>
        </div>
      </div>
    </div>

    <Footer />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
  booking: Object,
  vietQr: Object,
  is_admin: Boolean,
});

const activeMethod = ref('MBBANK'); // 'MBBANK' hoặc 'MOMO'
const isPaid = ref(
  props.booking.payment_status === 'PAID' || 
  props.booking.payment_status === 'DEPOSIT_PAID' || 
  props.booking.payment_status === 'PARTIALLY_PAID'
);
const isExpired = ref(false);
const copiedField = ref(null);
const isConfirming = ref(false);

// Quản lý URL ảnh QR động có fallback
const mbbankCurrentQr = ref(props.vietQr.mbbank_qr_url || props.vietQr.mbbank_local_qr || '/images/qr/mbbank_qr.png');
const momoCurrentQr = ref(props.vietQr.momo_qr_url || props.vietQr.momo_local_qr || '/images/qr/momo_qr.png');

const onMbbankQrError = () => {
  if (mbbankCurrentQr.value !== props.vietQr.mbbank_local_qr && props.vietQr.mbbank_local_qr) {
    mbbankCurrentQr.value = props.vietQr.mbbank_local_qr;
  } else if (mbbankCurrentQr.value !== '/images/qr/mbbank_qr.png') {
    mbbankCurrentQr.value = '/images/qr/mbbank_qr.png';
  }
};

const onMomoQrError = () => {
  if (momoCurrentQr.value !== props.vietQr.momo_local_qr && props.vietQr.momo_local_qr) {
    momoCurrentQr.value = props.vietQr.momo_local_qr;
  } else if (momoCurrentQr.value !== '/images/qr/momo_qr.png') {
    momoCurrentQr.value = '/images/qr/momo_qr.png';
  }
};

// THỜI GIAN GIỮ CHỖ ĐÚNG 5 PHÚT = 300 GIÂY
const timeLeft = ref(300);
let timerInterval = null;
let pollInterval = null;

const formattedCountdown = computed(() => {
  const m = Math.floor(timeLeft.value / 60);
  const s = timeLeft.value % 60;
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
});

// Phát âm thanh chuông chúc mừng khi nhận tiền thành công
const playSuccessSound = () => {
  try {
    const AudioContext = window.AudioContext || window.webkitAudioContext;
    if (!AudioContext) return;
    const ctx = new AudioContext();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.type = 'sine';
    osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
    osc.frequency.setValueAtTime(880, ctx.currentTime + 0.15); // A5
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.6);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.6);
  } catch (e) {
    // Ignore audio error
  }
};

// Sao chép thông tin vào Clipboard
const copyText = async (text, fieldName) => {
  try {
    await navigator.clipboard.writeText(String(text));
    copiedField.value = fieldName;
    setTimeout(() => {
      if (copiedField.value === fieldName) copiedField.value = null;
    }, 2000);
  } catch (e) {
    alert('Đã sao chép: ' + text);
  }
};

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(val || 0);

// Tự động chuyển sang trang Quản Lý Vé
const redirectToTicket = () => {
  playSuccessSound();
  setTimeout(() => {
    router.visit(`/booking/${props.booking.booking_code}/detail`);
  }, 1200);
};

// Xác nhận thủ công nếu khách đã chuyển tiền từ App Ngân Hàng
const manualConfirm = async () => {
  if (isConfirming.value || isPaid.value) return;
  isConfirming.value = true;
  try {
    const res = await axios.post(`/api/booking/${props.booking.booking_code}/confirm-payment`);
    if (res.data?.success) {
      isPaid.value = true;
      if (pollInterval) clearInterval(pollInterval);
      if (timerInterval) clearInterval(timerInterval);
      redirectToTicket();
    } else {
      alert(res.data?.message || 'Chưa nhận được giao dịch.');
    }
  } catch (err) {
    alert('Lỗi xác nhận: ' + (err.response?.data?.message || err.message));
  } finally {
    isConfirming.value = false;
  }
};

// Polling tự động lắng nghe giao dịch từ máy chủ (chỉ chuyển khi có tiền thật từ SePay hoặc Xác nhận)
const checkStatus = async () => {
  if (isPaid.value || isExpired.value) return;
  try {
    const res = await axios.get(`/api/booking/${props.booking.booking_code}/payment-status`);
    if (res.data?.is_paid) {
      isPaid.value = true;
      if (pollInterval) clearInterval(pollInterval);
      if (timerInterval) clearInterval(timerInterval);
      redirectToTicket();
    }
  } catch (err) {
    // Ignore polling network hiccup
  }
};

onMounted(() => {
  // Nếu vé đã ở trạng thái đã thanh toán -> tự động chuyển ngay sang trang Quản lý vé
  if (isPaid.value) {
    redirectToTicket();
    return;
  }

  // Timer đếm ngược đúng 5 phút (300 giây)
  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--;
    } else {
      clearInterval(timerInterval);
      if (pollInterval) clearInterval(pollInterval);
      isExpired.value = true;
    }
  }, 1000);

  // Polling tự động kiểm tra mỗi 1.5 giây (Lắng nghe webhook/SePay thật từ máy chủ)
  pollInterval = setInterval(checkStatus, 1500);
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
  if (pollInterval) clearInterval(pollInterval);
});
</script>
