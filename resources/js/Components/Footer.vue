<template>
  <footer class="bg-slate-900 text-slate-300 text-xs border-t border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
        
        <!-- Col 1 & 2: Company Info -->
        <div class="lg:col-span-2 space-y-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-blue-700 to-indigo-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-blue-500/30">
              S
            </div>
            <div>
              <div class="font-black text-white text-base tracking-tight">HỆ THỐNG VẬN TẢI CÔNG NGHỆ BUSHUB TERMINAL</div>
              <div class="text-[10px] text-cyan-400 font-bold tracking-wider uppercase">BUSHUB - HÀNH TRÌNH THÔNG MINH & TIỆN NGHI</div>
            </div>
          </div>

          <p class="text-slate-400 text-xs leading-relaxed">
            BusHub Terminal là nền tảng đặt vé xe khách công nghệ hàng đầu Việt Nam. Kết nối các tuyến đường huyết mạch toàn quốc, chọn vị trí giường nằm 2 tầng trực quan, tích hợp vé điện tử QR Code và thanh toán cọc linh hoạt 30%.
          </p>

          <div class="space-y-1 text-slate-400">
            <div>🏢 <strong>Trụ sở chính:</strong> 292 Đinh Bộ Lĩnh, Phường 26, Bình Thạnh, TP. Hồ Chí Minh</div>
            <div>🏢 <strong>Chi nhánh Đà Nẵng:</strong> 159 Tôn Đức Thắng, Hòa Minh, Liên Chiểu, Đà Nẵng</div>
            <div>📞 <strong>Tổng đài hỗ trợ 24/7:</strong> <span class="text-amber-400 font-bold">1900 8899</span> • <strong>Email:</strong> hotro@bushub.vn</div>
          </div>
        </div>

        <!-- Col 3: Quick Links -->
        <div class="space-y-3">
          <h4 class="font-black text-white uppercase tracking-wider text-xs">Tuyến Xe Nổi Bật</h4>
          <ul class="space-y-2 text-slate-400">
            <li><Link href="/trips?origin=Đà Nẵng&destination=Quảng Trị (Đông Hà)" class="hover:text-blue-400 transition">Đà Nẵng ➔ Đông Hà (Quảng Trị)</Link></li>
            <li><Link href="/trips?origin=TP. Hồ Chí Minh&destination=Cần Thơ" class="hover:text-blue-400 transition">TP. Hồ Chí Minh ➔ Cần Thơ</Link></li>
            <li><Link href="/trips?origin=Đà Nẵng&destination=Hội An" class="hover:text-blue-400 transition">Đà Nẵng ➔ Hội An</Link></li>
            <li><Link href="/trips?origin=TP. Hồ Chí Minh&destination=Sóc Trăng" class="hover:text-blue-400 transition">TP. Hồ Chí Minh ➔ Sóc Trăng</Link></li>
            <li><Link href="/trips?origin=Đà Nẵng&destination=Huế" class="hover:text-blue-400 transition">Đà Nẵng ➔ Thừa Thiên Huế</Link></li>
          </ul>
        </div>

        <!-- Col 4: Support & Policy -->
        <div class="space-y-3">
          <h4 class="font-black text-white uppercase tracking-wider text-xs">Hỗ Trợ Khách Hàng</h4>
          <ul class="space-y-2 text-slate-400">
            <li><Link href="/schedules" class="hover:text-blue-400 transition">Quy định đổi trả vé</Link></li>
            <li><Link href="/schedules" class="hover:text-blue-400 transition">Chính sách bảo mật thông tin</Link></li>
            <li><Link href="/schedules" class="hover:text-blue-400 transition">Quy định về hành lý xách tay</Link></li>
            <li><Link href="/schedules" class="hover:text-blue-400 transition">Tra cứu mã đặt vé & QR Code</Link></li>
            <li><Link href="/scanner" class="hover:text-blue-400 transition">Cổng kiểm soát vé Camera</Link></li>
          </ul>
        </div>

        <!-- Col 5: Certifications & Payments -->
        <div class="space-y-3">
          <h4 class="font-black text-white uppercase tracking-wider text-xs">Thanh Toán An Toàn</h4>
          <div class="grid grid-cols-3 gap-2">
            <div class="bg-slate-800 p-2 rounded-lg text-center font-bold text-[10px] text-white">VISA</div>
            <div class="bg-slate-800 p-2 rounded-lg text-center font-bold text-[10px] text-white">Master</div>
            <div class="bg-slate-800 p-2 rounded-lg text-center font-bold text-[10px] text-pink-400">MoMo</div>
            <div class="bg-slate-800 p-2 rounded-lg text-center font-bold text-[10px] text-blue-400">VNPay</div>
            <div class="bg-slate-800 p-2 rounded-lg text-center font-bold text-[10px] text-emerald-400">ZaloPay</div>
            <div class="bg-slate-800 p-2 rounded-lg text-center font-bold text-[10px] text-amber-400">Napas</div>
          </div>
          <div class="pt-2 text-[10px] text-slate-500">
            Hệ thống thanh toán bảo mật tiêu chuẩn quốc tế PCI-DSS & mã hóa SSL 256-bit.
          </div>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="mt-10 pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between text-slate-500 text-[11px] gap-3">
        <div>© 2026 BusHub Terminal Vietnam. Bản quyền thuộc Hệ thống Đặt vé xe khách BusHub.</div>
        <div class="flex space-x-4">
          <a href="#" class="hover:text-slate-300">Điều khoản sử dụng</a>
          <a href="#" class="hover:text-slate-300">Chính sách bảo mật</a>
          <a href="#" class="hover:text-slate-300">Sitemap</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>
