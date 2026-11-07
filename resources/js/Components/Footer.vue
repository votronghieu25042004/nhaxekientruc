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
            <li><Link href="/schedules" class="hover:text-red-500 transition block">Quy định đổi trả vé</Link></li>
            <li><Link href="/schedules" class="hover:text-red-500 transition block">Chính sách bảo mật thông tin</Link></li>
            <li><Link href="/schedules" class="hover:text-red-500 transition block">Quy định về hành lý xách tay</Link></li>
            <li><Link href="/schedules" class="hover:text-red-500 transition block">Tra cứu mã đặt vé & QR Code</Link></li>
            <li><Link href="/scanner" class="hover:text-red-500 transition block font-bold text-red-500">Cổng kiểm soát vé Camera</Link></li>
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
        <div class="flex space-x-4">
          <a href="#" class="hover:text-red-500 transition">Điều khoản sử dụng</a>
          <a href="#" class="hover:text-red-500 transition">Chính sách bảo mật</a>
          <a href="#" class="hover:text-red-500 transition">Sitemap</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  dark: { type: Boolean, default: false }
})
</script>
