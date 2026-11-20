<template>
  <footer 
    :class="dark 
      ? 'bg-[#0a0203] text-slate-400 text-xs border-t border-red-950/80' 
      : 'bg-gradient-to-b from-[#fff5f4] via-[#fff0ef] to-[#ffe8e7] text-slate-700 text-xs border-t border-[#ffd8d6]'"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
        
        <!-- Col 1 & 2: Company Info -->
        <div class="lg:col-span-2 space-y-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-white flex items-center justify-center p-1 shadow-md shadow-red-500/20 border border-red-200">
              <img src="/images/logo.png" alt="Logo Trường ĐH Kiến Trúc Đà Nẵng" class="w-full h-full object-contain" />
            </div>
            <div>
              <div 
                class="font-black text-base tracking-tight"
                :class="dark ? 'text-white' : 'text-slate-900'"
              >
                TRƯỜNG ĐẠI HỌC KIẾN TRÚC ĐÀ NẴNG
              </div>
              <div class="text-[10px] text-red-500 font-bold tracking-wider uppercase">NHAXEKIENTRUC TERMINAL - HỆ THỐNG VÉ XE THÔNG MINH</div>
            </div>
          </div>

          <p :class="dark ? 'text-slate-400' : 'text-slate-600'" class="text-xs leading-relaxed">
            nhaxekientruc là nền tảng đặt vé xe khách công nghệ hàng đầu Việt Nam. Kết nối các tuyến đường huyết mạch toàn quốc xuất bến từ Đà Nẵng, chọn vị trí giường nằm 2 tầng trực quan, tích hợp vé điện tử QR Code và thanh toán cọc linh hoạt 30%.
          </p>

          <div class="space-y-1.5" :class="dark ? 'text-slate-400' : 'text-slate-600'">
            <div>🏢 <strong :class="dark ? 'text-slate-200' : 'text-slate-800'">Trụ sở chính:</strong> 292 Đinh Bộ Lĩnh, Phường 26, Bình Thạnh, TP. Hồ Chí Minh</div>
            <div>🏢 <strong :class="dark ? 'text-slate-200' : 'text-slate-800'">Chi nhánh Đà Nẵng:</strong> 159 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, Đà Nẵng</div>
            <div>📞 <strong :class="dark ? 'text-slate-200' : 'text-slate-800'">Tổng đài hỗ trợ 24/7:</strong> <span class="text-red-500 font-black text-sm">1900 6067</span> • <strong :class="dark ? 'text-slate-200' : 'text-slate-800'">Email:</strong> hotro@nhaxekientruc.vn</div>
          </div>
        </div>

        <!-- Col 3: Quick Links -->
        <div class="space-y-3">
          <h4 
            class="font-black uppercase tracking-wider text-xs border-b-2 pb-1.5 inline-block"
            :class="dark ? 'text-white border-red-600' : 'text-slate-900 border-red-500/80'"
          >
            Tuyến Xe Nổi Bật
          </h4>
          <ul class="space-y-2" :class="dark ? 'text-slate-400' : 'text-slate-600'">
            <li><Link href="/trips?origin=Đà Nẵng&destination=Thừa Thiên Huế" class="hover:text-red-500 hover:translate-x-0.5 transition-all flex items-center gap-1.5"><span class="text-red-500">➔</span><span>Đà Nẵng ⇄ Thừa Thiên Huế</span></Link></li>
            <li><Link href="/trips?origin=Đà Nẵng&destination=Quảng Trị" class="hover:text-red-500 hover:translate-x-0.5 transition-all flex items-center gap-1.5"><span class="text-red-500">➔</span><span>Đà Nẵng ⇄ Đông Hà (Quảng Trị)</span></Link></li>
            <li><Link href="/trips?origin=Đà Nẵng&destination=Quy Nhơn" class="hover:text-red-500 hover:translate-x-0.5 transition-all flex items-center gap-1.5"><span class="text-red-500">➔</span><span>Đà Nẵng ⇄ Quy Nhơn (Bình Định)</span></Link></li>
            <li><Link href="/trips?origin=Đà Nẵng&destination=Buôn Ma Thuột" class="hover:text-red-500 hover:translate-x-0.5 transition-all flex items-center gap-1.5"><span class="text-red-500">➔</span><span>Đà Nẵng ⇄ Buôn Ma Thuột</span></Link></li>
            <li><Link href="/trips?origin=Đà Nẵng&destination=Hội An" class="hover:text-red-500 hover:translate-x-0.5 transition-all flex items-center gap-1.5"><span class="text-red-500">➔</span><span>Đà Nẵng ⇄ Hội An</span></Link></li>
          </ul>
        </div>

        <!-- Col 4: Support & Policy -->
        <div class="space-y-3">
          <h4 
            class="font-black uppercase tracking-wider text-xs border-b-2 pb-1.5 inline-block"
            :class="dark ? 'text-white border-red-600' : 'text-slate-900 border-red-500/80'"
          >
            Hỗ Trợ Khách Hàng
          </h4>
          <ul class="space-y-2" :class="dark ? 'text-slate-400' : 'text-slate-600'">
            <li>
              <button 
                type="button" 
                @click="openPolicy('refund')" 
                class="hover:text-red-500 transition text-left cursor-pointer flex items-center gap-1.5"
              >
                <span>Quy định đổi trả vé</span>
              </button>
            </li>
            <li>
              <button 
                type="button" 
                @click="openPolicy('privacy')" 
                class="hover:text-red-500 transition text-left cursor-pointer flex items-center gap-1.5"
              >
                <span>Chính sách bảo mật thông tin</span>
              </button>
            </li>
            <li>
              <button 
                type="button" 
                @click="openPolicy('luggage')" 
                class="hover:text-red-500 transition text-left cursor-pointer flex items-center gap-1.5"
              >
                <span>Quy định về hành lý xách tay</span>
              </button>
            </li>
            <li>
              <Link 
                href="/my-bookings" 
                class="hover:text-red-500 transition block font-bold text-red-600"
              >
                Tra cứu mã đặt vé & QR Code
              </Link>
            </li>
            <li>
              <Link 
                href="/scanner" 
                class="hover:text-red-500 transition block font-bold text-emerald-600 flex items-center gap-1"
              >
                <span>📷</span>
                <span>Cổng kiểm soát vé Camera</span>
              </Link>
            </li>
          </ul>
        </div>

        <!-- Col 5: Certifications & Payments -->
        <div class="space-y-3">
          <h4 
            class="font-black uppercase tracking-wider text-xs border-b-2 pb-1.5 inline-block"
            :class="dark ? 'text-white border-red-600' : 'text-slate-900 border-red-500/80'"
          >
            Thanh Toán An Toàn
          </h4>
          <div class="grid grid-cols-3 gap-2">
            <div 
              class="p-2 rounded-xl text-center font-black text-[10px] shadow-2xs"
              :class="dark ? 'bg-[#160507] border border-red-950 text-slate-200' : 'bg-white border border-rose-200/90 text-slate-800'"
            >VISA</div>
            <div 
              class="p-2 rounded-xl text-center font-black text-[10px] shadow-2xs"
              :class="dark ? 'bg-[#160507] border border-red-950 text-slate-200' : 'bg-white border border-rose-200/90 text-slate-800'"
            >Master</div>
            <div 
              class="p-2 rounded-xl text-center font-black text-[10px] shadow-2xs"
              :class="dark ? 'bg-[#160507] border border-red-950 text-pink-400' : 'bg-white border border-rose-200/90 text-pink-600'"
            >MoMo</div>
            <div 
              class="p-2 rounded-xl text-center font-black text-[10px] shadow-2xs"
              :class="dark ? 'bg-[#160507] border border-red-950 text-blue-400' : 'bg-white border border-rose-200/90 text-blue-600'"
            >VNPay</div>
            <div 
              class="p-2 rounded-xl text-center font-black text-[10px] shadow-2xs"
              :class="dark ? 'bg-[#160507] border border-red-950 text-emerald-400' : 'bg-white border border-rose-200/90 text-emerald-600'"
            >ZaloPay</div>
            <div 
              class="p-2 rounded-xl text-center font-black text-[10px] shadow-2xs"
              :class="dark ? 'bg-[#160507] border border-red-950 text-amber-400' : 'bg-white border border-rose-200/90 text-amber-600'"
            >Napas</div>
          </div>
          <div class="pt-2 text-[10px] leading-relaxed" :class="dark ? 'text-slate-500' : 'text-slate-500'">
            Hệ thống thanh toán bảo mật tiêu chuẩn quốc tế PCI-DSS & mã hóa SSL 256-bit.
          </div>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div 
        class="mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between text-[11px] gap-3"
        :class="dark ? 'border-t border-red-950/80 text-slate-500' : 'border-t border-rose-200/80 text-slate-500'"
      >
        <div>© 2026 nhaxekientruc Terminal Vietnam. Bản quyền thuộc Hệ thống Đặt vé xe khách nhaxekientruc.</div>
        <div class="flex items-center space-x-4">
          <button 
            type="button" 
            @click="openPolicy('terms')" 
            class="hover:text-red-500 transition cursor-pointer"
          >
            Điều khoản sử dụng
          </button>
          <span class="text-slate-300">|</span>
          <button 
            type="button" 
            @click="openPolicy('privacy')" 
            class="hover:text-red-500 transition cursor-pointer"
          >
            Chính sách bảo mật
          </button>
          <span class="text-slate-300">|</span>
          <button 
            type="button" 
            @click="openPolicy('sitemap')" 
            class="hover:text-red-500 transition cursor-pointer"
          >
            Sitemap
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL CHÍNH SÁCH & SITEMAP ==================== -->
    <div 
      v-if="showPolicyModal" 
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-sm animate-in fade-in duration-200"
      @click.self="closePolicy"
    >
      <div 
        class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden text-slate-800 animate-in zoom-in-95 duration-150"
      >
        <!-- Modal Header -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 text-white flex-shrink-0">
          <div class="flex items-center gap-2.5">
            <span class="text-xl">
              {{ activePolicyTab === 'refund' ? '🔄' : (activePolicyTab === 'privacy' ? '🔒' : (activePolicyTab === 'luggage' ? '🧳' : (activePolicyTab === 'terms' ? '📜' : '🗺️'))) }}
            </span>
            <div>
              <h3 class="font-black text-sm sm:text-base leading-tight">
                {{ 
                  activePolicyTab === 'refund' ? 'Quy Định Đổi Trả & Hủy Vé' : 
                  (activePolicyTab === 'privacy' ? 'Chính Sách Bảo Mật Thông Tin' : 
                  (activePolicyTab === 'luggage' ? 'Quy Định Về Hành Lý & Ký Gửi' : 
                  (activePolicyTab === 'terms' ? 'Điều Khoản Dịch Vụ Vận Chuyển' : 'Sơ Đồ Hệ Thống (Sitemap)'))) 
                }}
              </h3>
              <p class="text-[11px] text-white/80">Áp dụng chính thức trên toàn mạng lưới nhaxekientruc</p>
            </div>
          </div>
          <button 
            type="button" 
            @click="closePolicy"
            class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition cursor-pointer font-bold text-sm"
          >
            ✕
          </button>
        </div>

        <!-- Tab Bar -->
        <div class="flex items-center gap-1 p-2 bg-slate-50 border-b border-slate-200/80 overflow-x-auto flex-shrink-0 text-xs">
          <button 
            type="button"
            @click="activePolicyTab = 'refund'"
            :class="activePolicyTab === 'refund' ? 'bg-white text-red-600 font-bold shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition cursor-pointer flex items-center gap-1.5"
          >
            <span>🔄</span>
            <span>Đổi trả vé</span>
          </button>
          <button 
            type="button"
            @click="activePolicyTab = 'privacy'"
            :class="activePolicyTab === 'privacy' ? 'bg-white text-red-600 font-bold shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition cursor-pointer flex items-center gap-1.5"
          >
            <span>🔒</span>
            <span>Bảo mật</span>
          </button>
          <button 
            type="button"
            @click="activePolicyTab = 'luggage'"
            :class="activePolicyTab === 'luggage' ? 'bg-white text-red-600 font-bold shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition cursor-pointer flex items-center gap-1.5"
          >
            <span>🧳</span>
            <span>Hành lý</span>
          </button>
          <button 
            type="button"
            @click="activePolicyTab = 'terms'"
            :class="activePolicyTab === 'terms' ? 'bg-white text-red-600 font-bold shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition cursor-pointer flex items-center gap-1.5"
          >
            <span>📜</span>
            <span>Điều khoản</span>
          </button>
          <button 
            type="button"
            @click="activePolicyTab = 'sitemap'"
            :class="activePolicyTab === 'sitemap' ? 'bg-white text-red-600 font-bold shadow-xs border border-slate-200' : 'text-slate-600 hover:text-slate-900'"
            class="px-3 py-1.5 rounded-xl whitespace-nowrap transition cursor-pointer flex items-center gap-1.5"
          >
            <span>🗺️</span>
            <span>Sitemap</span>
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-5 sm:p-6 overflow-y-auto space-y-4 text-xs sm:text-[13px] leading-relaxed text-slate-700">
          
          <!-- TAB 1: QUY ĐỊNH ĐỔI TRẢ VÉ -->
          <div v-if="activePolicyTab === 'refund'" class="space-y-4">
            <div class="p-3 bg-red-50 border border-red-200 rounded-2xl flex items-center gap-3 text-red-900">
              <span class="text-2xl">⚡</span>
              <div>
                <p class="font-bold text-xs uppercase tracking-wide">Chính sách hủy đổi linh hoạt 24/7</p>
                <p class="text-[11px] text-red-700">Tự động hoàn tiền vào số tài khoản hoặc ví thanh toán ban đầu trong vòng 24h - 48h.</p>
              </div>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">1. Biểu Phí Hoàn Tiền Đặt Cọc & Vé:</h4>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-center space-y-1">
                  <span class="text-xs font-bold text-emerald-800 uppercase block">Trước 24 giờ</span>
                  <span class="text-xl font-black text-emerald-600 block">Hoàn 100%</span>
                  <span class="text-[10px] text-emerald-700 block">Miễn phí hoàn cọc chuyến đi</span>
                </div>
                <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-center space-y-1">
                  <span class="text-xs font-bold text-amber-800 uppercase block">Từ 12h - 24 giờ</span>
                  <span class="text-xl font-black text-amber-600 block">Hoàn 50%</span>
                  <span class="text-[10px] text-amber-700 block">Khấu trừ 50% phí giữ chỗ</span>
                </div>
                <div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-center space-y-1">
                  <span class="text-xs font-bold text-rose-800 uppercase block">Dưới 12 giờ</span>
                  <span class="text-xl font-black text-rose-600 block">Không hoàn</span>
                  <span class="text-[10px] text-rose-700 block">Không hỗ trợ hủy giờ chót</span>
                </div>
              </div>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">2. Quy Trình Yêu Cầu Hủy & Đổi Chuyến:</h4>
              <ul class="list-disc pl-5 space-y-1.5 text-slate-600">
                <li>Hành khách có thể hủy vé trực tuyến tại mục <strong>Vé Của Tôi</strong> bằng cách nhập mã đặt vé và số điện thoại.</li>
                <li>Hỗ trợ chuyển đổi sang chuyến xe khác trước giờ xuất bến 12 tiếng nếu tuyến đó còn giường trống.</li>
                <li>Đối với vé mua vào các đợt cao điểm Lễ, Tết Nguyên Đán, thời hạn hủy vé tối thiểu là trước 48 giờ.</li>
              </ul>
            </div>

            <div class="p-3 bg-slate-100 rounded-2xl text-[11px] text-slate-600 flex items-center justify-between">
              <span>Cần hỗ trợ đổi chuyến gấp?</span>
              <a href="tel:19006067" class="text-red-600 font-bold hover:underline">📞 Tổng đài: 1900 6067</a>
            </div>
          </div>

          <!-- TAB 2: CHÍNH SÁCH BẢO MẬT -->
          <div v-else-if="activePolicyTab === 'privacy'" class="space-y-4">
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-emerald-900">
              <span class="text-2xl">🛡️</span>
              <div>
                <p class="font-bold text-xs uppercase tracking-wide">Bảo mật thông tin chuẩn Nghị định 13/2023/NĐ-CP</p>
                <p class="text-[11px] text-emerald-700">Mã hóa đa tầng SSL 256-bit và không chia sẻ cho bên thứ ba dưới bất kỳ hình thức nào.</p>
              </div>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">1. Dữ Liệu Thu Thập:</h4>
              <p>Hệ thống chỉ thu thập thông tin cần thiết phục vụ cho việc vận chuyển và liên lạc đón khách: Họ và tên, số điện thoại, email nhận vé điện tử, điểm đón và trả khách.</p>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">2. Bảo Mật Thanh Toán Trực Tuyến:</h4>
              <ul class="list-disc pl-5 space-y-1.5 text-slate-600">
                <li>Toàn bộ giao dịch qua cổng MoMo, VNPay, ZaloPay, VietQR đều được thực hiện qua cổng thanh toán bảo mật chuẩn PCI-DSS Level 1.</li>
                <li>Hệ thống nhaxekientruc tuyệt đối <strong>không lưu trữ</strong> số thẻ tín dụng hoặc mật khẩu thanh toán của khách hàng.</li>
              </ul>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">3. Quyền Lợi Của Hành Khách:</h4>
              <p>Khách hàng có quyền tra cứu, cập nhật thông tin cá nhân hoặc yêu cầu xóa dữ liệu bằng cách liên hệ phòng vé hoặc gửi yêu cầu tới <a href="mailto:hotro@nhaxekientruc.vn" class="text-red-600 font-bold hover:underline">hotro@nhaxekientruc.vn</a>.</p>
            </div>
          </div>

          <!-- TAB 3: QUY ĐỊNH HÀNH LÝ -->
          <div v-else-if="activePolicyTab === 'luggage'" class="space-y-4">
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-2xl flex items-center gap-3 text-amber-900">
              <span class="text-2xl">🧳</span>
              <div>
                <p class="font-bold text-xs uppercase tracking-wide">Miễn phí 20kg hành lý ký gửi 0đ</p>
                <p class="text-[11px] text-amber-800">Mỗi hành khách được miễn cước 20kg hành lý gầm xe + 1 túi xách tay 7kg lên khoang giường nằm.</p>
              </div>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">1. Tiêu Chuẩn Hành Lý Miễn Phí:</h4>
              <ul class="list-disc pl-5 space-y-1.5 text-slate-600">
                <li><strong>Hành lý xách tay:</strong> Tối đa 7kg, bao gồm ba lô cá nhân, túi xách nhỏ, máy tính xách tay. Cần để gọn tại hộc để đồ trên giường.</li>
                <li><strong>Hành lý ký gửi:</strong> Tối đa 20kg (1 vali hoặc thùng đồ kích thước không vượt quá 80x50x30cm). Được nhân viên phụ xe cấp thẻ tag hành lý có mã số trùng khớp vé.</li>
              </ul>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">2. Hàng Hóa Cấm & Hạn Chế Vận Chuyển:</h4>
              <div class="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-1">
                <p class="font-bold">🚫 Danh mục nghiêm cấm tuyệt đối:</p>
                <p class="text-[11px] leading-relaxed">Vũ khí, chất nổ, bình khí ga mini, xăng dầu, hóa chất dễ bốc cháy, hàng cấm theo quy định pháp luật.</p>
              </div>
              <p class="text-[11px] text-slate-600">Đồ tươi sống, hải sản, sầu riêng bắt buộc đóng trong thùng xốp dán kín băng keo không bay mùi để xếp dưới hầm hàng.</p>
            </div>
          </div>

          <!-- TAB 4: ĐIỀU KHOẢN DỊCH VỤ -->
          <div v-else-if="activePolicyTab === 'terms'" class="space-y-4">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl flex items-center gap-3 text-slate-900">
              <span class="text-2xl">📋</span>
              <div>
                <p class="font-bold text-xs uppercase tracking-wide">Điều khoản dịch vụ hành khách nhaxekientruc</p>
                <p class="text-[11px] text-slate-600">Vui lòng đọc kỹ các quyền và trách nhiệm khi sử dụng dịch vụ vận chuyển.</p>
              </div>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">1. Quy Định Đặt Chỗ & Thanh Toán Cọc:</h4>
              <p>Hệ thống cho phép hành khách đặt chỗ trước chỉ với <strong>30% tiền cọc</strong> để giữ giường nằm mong muốn. Số tiền còn lại sẽ thanh toán trực tiếp cho tiếp viên khi lên xe.</p>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">2. Thời Gian Có Mặt Tại Điểm Đón:</h4>
              <p>Hành khách cần có mặt tại bến xe hoặc điểm đón hẹn trước ít nhất <strong>15 - 30 phút</strong> để nhân viên soát vé QR Code và xếp hành lý vào hầm xe an toàn.</p>
            </div>

            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs">3. Ưu Đãi Học Sinh - Sinh Viên:</h4>
              <p>Sinh viên đăng ký bằng email nhà trường (.edu.vn) được hưởng chính sách vé 0đ cho lần đầu (Mã: SINHVIEN0DONG) và giảm 30% suốt năm học (Mã: SINHVIEN30).</p>
            </div>
          </div>

          <!-- TAB 5: SITEMAP (SƠ ĐỒ TRANG TRỰC QUAN) -->
          <div v-else-if="activePolicyTab === 'sitemap'" class="space-y-5">
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-2xl flex items-center gap-3 text-blue-900">
              <span class="text-2xl">🗺️</span>
              <div>
                <p class="font-bold text-xs uppercase tracking-wide">Sơ Đồ Hệ Thống nhaxekientruc</p>
                <p class="text-[11px] text-blue-700">Click vào bất kỳ liên kết nào dưới đây để chuyển ngay đến trang bạn cần.</p>
              </div>
            </div>

            <!-- Khối 1: Dành cho hành khách -->
            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs flex items-center gap-1.5">
                <span>🚌</span>
                <span>Dành Cho Khách Hàng Đặt Vé:</span>
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <Link 
                  href="/" 
                  @click="closePolicy"
                  class="p-2.5 rounded-xl border border-slate-200 hover:border-red-500 hover:bg-red-50/50 transition flex items-center justify-between group"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-base">🏠</span>
                    <span class="font-bold text-slate-800 group-hover:text-red-600 text-xs">Trang Chủ Hệ Thống</span>
                  </div>
                  <span class="text-red-500 text-xs group-hover:translate-x-0.5 transition-transform">➔</span>
                </Link>

                <Link 
                  href="/trips" 
                  @click="closePolicy"
                  class="p-2.5 rounded-xl border border-slate-200 hover:border-red-500 hover:bg-red-50/50 transition flex items-center justify-between group"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-base">🔍</span>
                    <span class="font-bold text-slate-800 group-hover:text-red-600 text-xs">Tìm Chuyến & Lộ Trình Xe</span>
                  </div>
                  <span class="text-red-500 text-xs group-hover:translate-x-0.5 transition-transform">➔</span>
                </Link>

                <Link 
                  href="/schedules" 
                  @click="closePolicy"
                  class="p-2.5 rounded-xl border border-slate-200 hover:border-red-500 hover:bg-red-50/50 transition flex items-center justify-between group"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-base">📅</span>
                    <span class="font-bold text-slate-800 group-hover:text-red-600 text-xs">Lịch Trình Toàn Tuyến</span>
                  </div>
                  <span class="text-red-500 text-xs group-hover:translate-x-0.5 transition-transform">➔</span>
                </Link>

                <Link 
                  href="/my-bookings" 
                  @click="closePolicy"
                  class="p-2.5 rounded-xl border border-slate-200 hover:border-red-500 hover:bg-red-50/50 transition flex items-center justify-between group"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-base">🎫</span>
                    <span class="font-bold text-slate-800 group-hover:text-red-600 text-xs">Tra Cứu Vé & Mã QR Code</span>
                  </div>
                  <span class="text-red-500 text-xs group-hover:translate-x-0.5 transition-transform">➔</span>
                </Link>

                <Link 
                  href="/scanner" 
                  @click="closePolicy"
                  class="p-2.5 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition flex items-center justify-between group"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-base">📷</span>
                    <span class="font-bold text-slate-800 group-hover:text-emerald-600 text-xs">Cổng Kiểm Soát Vé Camera QR</span>
                  </div>
                  <span class="text-emerald-500 text-xs group-hover:translate-x-0.5 transition-transform">➔</span>
                </Link>

                <Link 
                  href="/login" 
                  @click="closePolicy"
                  class="p-2.5 rounded-xl border border-slate-200 hover:border-red-500 hover:bg-red-50/50 transition flex items-center justify-between group"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-base">🔐</span>
                    <span class="font-bold text-slate-800 group-hover:text-red-600 text-xs">Đăng Nhập / Đăng Ký OTP</span>
                  </div>
                  <span class="text-red-500 text-xs group-hover:translate-x-0.5 transition-transform">➔</span>
                </Link>
              </div>
            </div>

            <!-- Khối 2: Tuyến đường nổi bật -->
            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs flex items-center gap-1.5">
                <span>🛣️</span>
                <span>Tuyến Đường Huyết Mạch:</span>
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                <Link 
                  href="/trips?origin=Đà Nẵng&destination=Thừa Thiên Huế" 
                  @click="closePolicy"
                  class="p-2 rounded-xl bg-slate-50 hover:bg-red-50 text-slate-700 hover:text-red-600 transition flex items-center gap-2"
                >
                  <span class="text-red-500 font-bold">➔</span>
                  <span>Đà Nẵng ⇄ Thừa Thiên Huế</span>
                </Link>
                <Link 
                  href="/trips?origin=Đà Nẵng&destination=Quảng Trị" 
                  @click="closePolicy"
                  class="p-2 rounded-xl bg-slate-50 hover:bg-red-50 text-slate-700 hover:text-red-600 transition flex items-center gap-2"
                >
                  <span class="text-red-500 font-bold">➔</span>
                  <span>Đà Nẵng ⇄ Đông Hà (Quảng Trị)</span>
                </Link>
                <Link 
                  href="/trips?origin=Đà Nẵng&destination=Quy Nhơn" 
                  @click="closePolicy"
                  class="p-2 rounded-xl bg-slate-50 hover:bg-red-50 text-slate-700 hover:text-red-600 transition flex items-center gap-2"
                >
                  <span class="text-red-500 font-bold">➔</span>
                  <span>Đà Nẵng ⇄ Quy Nhơn (Bình Định)</span>
                </Link>
                <Link 
                  href="/trips?origin=Đà Nẵng&destination=Buôn Ma Thuột" 
                  @click="closePolicy"
                  class="p-2 rounded-xl bg-slate-50 hover:bg-red-50 text-slate-700 hover:text-red-600 transition flex items-center gap-2"
                >
                  <span class="text-red-500 font-bold">➔</span>
                  <span>Đà Nẵng ⇄ Buôn Ma Thuột (Đắk Lắk)</span>
                </Link>
              </div>
            </div>

            <!-- Khối 3: Cổng nội bộ nhân viên & quản trị -->
            <div class="space-y-2">
              <h4 class="font-black text-slate-900 uppercase text-xs flex items-center gap-1.5">
                <span>⚙️</span>
                <span>Cổng Vận Hành Nội Bộ:</span>
              </h4>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
                <Link 
                  href="/admin" 
                  @click="closePolicy"
                  class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold transition flex items-center gap-2"
                >
                  <span>👑</span>
                  <span>Cổng Admin</span>
                </Link>
                <Link 
                  href="/driver" 
                  @click="closePolicy"
                  class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold transition flex items-center gap-2"
                >
                  <span>🚖</span>
                  <span>Cổng Tài Xế</span>
                </Link>
                <Link 
                  href="/staff" 
                  @click="closePolicy"
                  class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold transition flex items-center gap-2"
                >
                  <span>👔</span>
                  <span>Cổng Phụ Xe / Staff</span>
                </Link>
              </div>
            </div>

          </div>

        </div>

        <!-- Modal Footer -->
        <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between flex-shrink-0">
          <div class="text-[11px] text-slate-500 hidden sm:block">
            Tổng đài chăm sóc khách hàng: <strong class="text-red-600 font-mono">1900 6067</strong>
          </div>
          <button 
            type="button" 
            @click="closePolicy"
            class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs transition cursor-pointer shadow-xs ml-auto"
          >
            Đã Hiểu & Đóng
          </button>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'

defineProps({
  dark: { type: Boolean, default: false }
})

const showPolicyModal = ref(false)
const activePolicyTab = ref('refund')

const openPolicy = (tab = 'refund') => {
  activePolicyTab.value = tab
  showPolicyModal.value = true
}

const closePolicy = () => {
  showPolicyModal.value = false
}
</script>
