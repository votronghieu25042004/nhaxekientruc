<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-800 font-sans">
    <Navbar />

    <!-- ==================== HERO SECTION & SEARCH ==================== -->
    <section class="relative bg-gradient-to-b from-blue-600 via-blue-600 to-[#2563EB] text-white pt-10 pb-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
      <!-- Decorative Pattern Background -->
      <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>

      <div class="max-w-6xl mx-auto relative z-10 text-center space-y-4 mb-8">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/20 backdrop-blur-md text-xs font-bold uppercase tracking-wider text-white border border-white/20">
          <span>🚍</span> MẠNG LƯỚI XE KHÁCH CÔNG NGHỆ • ĐẶT VÉ NHANH CHÓNG
        </div>
        <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-white leading-tight">
          BUSHUB - CỔNG THÔNG TIN & QUẢN LÝ BẾN XE LIÊN TỈNH
        </h1>
        <p class="text-white/90 text-sm sm:text-base max-w-2xl mx-auto font-medium">
          Hệ thống đặt vé xe trực tuyến thông minh. Chọn chỗ ngồi 2 tầng, chọn trạm đón tận nơi, cọc linh hoạt 30% và nhận mã QR soát vé camera tức thì.
        </p>
      </div>

      <!-- Search Widget Embedded -->
      <div class="relative z-20 -mb-16">
        <SearchWidget />
      </div>
    </section>

    <!-- Spacer for Search Widget overlap -->
    <div class="h-20 sm:h-24"></div>

    <!-- Main Body Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-16 flex-1 w-full">
      
      <!-- ==================== SECTION: PROMOTIONS & VOUCHERS ==================== -->
      <section id="promos" class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Ưu Đãi & Khuyến Mãi Nổi Bật</h2>
            <p class="text-xs sm:text-sm text-slate-500">Mã giảm giá áp dụng trực tiếp khi thanh toán online</p>
          </div>
          <Link href="/trips" class="text-xs font-bold text-[#2563EB] hover:underline flex items-center gap-1">
            Xem tất cả chuyến xe →
          </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <!-- Promo 1 -->
          <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
            <div class="space-y-2">
              <div class="inline-block px-3 py-1 bg-red-100 text-[#2563EB] rounded-full text-xs font-black">
                GIẢM 30.000đ
              </div>
              <h3 class="font-bold text-slate-900 text-base">Khách hàng mới đặt vé lần đầu</h3>
              <p class="text-xs text-slate-500 leading-relaxed">Giảm ngay 30.000đ cho đơn đặt vé đầu tiên trên hệ thống BusHub Terminal.</p>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
              <div class="font-mono font-black text-slate-800 text-xs bg-slate-100 px-2.5 py-1 rounded-lg">BUSHUB30</div>
              <button @click="copyCode('BUSHUB30')" class="text-xs font-bold text-[#2563EB] hover:underline cursor-pointer">Sao chép mã</button>
            </div>
          </div>

          <!-- Promo 2 -->
          <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
            <div class="space-y-2">
              <div class="inline-block px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-black">
                GIẢM 20%
              </div>
              <h3 class="font-bold text-slate-900 text-base">Ưu đãi vé khứ hồi toàn tuyến</h3>
              <p class="text-xs text-slate-500 leading-relaxed">Áp dụng khi chọn đặt cả chiều đi và chiều về trên các tuyến miền Trung & miền Tây.</p>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
              <div class="font-mono font-black text-slate-800 text-xs bg-slate-100 px-2.5 py-1 rounded-lg">KHUHOI20</div>
              <button @click="copyCode('KHUHOI20')" class="text-xs font-bold text-[#2563EB] hover:underline cursor-pointer">Sao chép mã</button>
            </div>
          </div>

          <!-- Promo 3 -->
          <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-sm hover:shadow-md transition flex flex-col justify-between space-y-4">
            <div class="space-y-2">
              <div class="inline-block px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-black">
                VNPAY GIẢM 50K
              </div>
              <h3 class="font-bold text-slate-900 text-base">Thanh toán qua Ví điện tử & QR</h3>
              <p class="text-xs text-slate-500 leading-relaxed">Giảm tới 50.000đ khi thanh toán cọc hoặc thanh toán toàn bộ qua cổng VNPay/MoMo.</p>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
              <div class="font-mono font-black text-slate-800 text-xs bg-slate-100 px-2.5 py-1 rounded-lg">VNPAYSMART</div>
              <button @click="copyCode('VNPAYSMART')" class="text-xs font-bold text-[#2563EB] hover:underline cursor-pointer">Sao chép mã</button>
            </div>
          </div>
        </div>
      </section>

      <!-- ==================== SECTION: POPULAR ROUTES ==================== -->
      <section class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Tuyến Đường Phổ Biến</h2>
            <p class="text-xs sm:text-sm text-slate-500">Các tuyến xe chất lượng cao có tần suất chạy liên tục mỗi ngày</p>
          </div>
          <Link href="/schedules" class="text-xs font-bold text-[#2563EB] hover:underline flex items-center gap-1">
            Xem tất cả lịch trình →
          </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Route 1: Đà Nẵng -> Đông Hà -->
          <div class="bg-white rounded-3xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-md transition group">
            <div class="h-40 bg-gradient-to-tr from-slate-800 to-slate-700 relative flex items-end p-5 text-white overflow-hidden">
              <div class="absolute inset-0 bg-cover bg-center opacity-70 group-hover:scale-105 transition duration-500" style="background-image: url('https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?auto=format&fit=crop&w=600&q=80');"></div>
              <div class="relative z-10">
                <span class="px-2.5 py-0.5 bg-[#2563EB] rounded-md text-[10px] font-black uppercase tracking-wider">Xe Giường Nằm VIP</span>
                <h3 class="text-lg font-black mt-1">Đà Nẵng ➔ Đông Hà (Quảng Trị)</h3>
              </div>
            </div>
            <div class="p-5 space-y-4">
              <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>Cự ly: <strong>175 km</strong></span>
                <span>Thời gian: <strong>3.5 giờ</strong></span>
                <span>Tần suất: <strong>6 chuyến/ngày</strong></span>
              </div>
              <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <div>
                  <span class="text-[10px] text-slate-400 block font-semibold">Giá vé từ:</span>
                  <span class="text-base font-black text-[#2563EB]">150.000đ</span>
                </div>
                <Link href="/trips?origin=Đà Nẵng&destination=Quảng Trị (Đông Hà)" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-[#2563EB] text-white text-xs font-bold transition">
                  Đặt vé ngay
                </Link>
              </div>
            </div>
          </div>

          <!-- Route 2: TP.HCM -> Cần Thơ -->
          <div class="bg-white rounded-3xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-md transition group">
            <div class="h-40 bg-gradient-to-tr from-slate-800 to-slate-700 relative flex items-end p-5 text-white overflow-hidden">
              <div class="absolute inset-0 bg-cover bg-center opacity-70 group-hover:scale-105 transition duration-500" style="background-image: url('https://images.unsplash.com/photo-1583417319070-4a69db38a482?auto=format&fit=crop&w=600&q=80');"></div>
              <div class="relative z-10">
                <span class="px-2.5 py-0.5 bg-emerald-600 rounded-md text-[10px] font-black uppercase tracking-wider">Limousine VIP</span>
                <h3 class="text-lg font-black mt-1">TP. Hồ Chí Minh ➔ Cần Thơ</h3>
              </div>
            </div>
            <div class="p-5 space-y-4">
              <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>Cự ly: <strong>165 km</strong></span>
                <span>Thời gian: <strong>3.2 giờ</strong></span>
                <span>Tần suất: <strong>15 phút/chuyến</strong></span>
              </div>
              <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <div>
                  <span class="text-[10px] text-slate-400 block font-semibold">Giá vé từ:</span>
                  <span class="text-base font-black text-[#2563EB]">165.000đ</span>
                </div>
                <Link href="/trips?origin=TP. Hồ Chí Minh&destination=Cần Thơ" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-[#2563EB] text-white text-xs font-bold transition">
                  Đặt vé ngay
                </Link>
              </div>
            </div>
          </div>

          <!-- Route 3: Đà Nẵng -> Hội An -->
          <div class="bg-white rounded-3xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-md transition group">
            <div class="h-40 bg-gradient-to-tr from-slate-800 to-slate-700 relative flex items-end p-5 text-white overflow-hidden">
              <div class="absolute inset-0 bg-cover bg-center opacity-70 group-hover:scale-105 transition duration-500" style="background-image: url('https://images.unsplash.com/photo-1528127269322-539801943592?auto=format&fit=crop&w=600&q=80');"></div>
              <div class="relative z-10">
                <span class="px-2.5 py-0.5 bg-blue-600 rounded-md text-[10px] font-black uppercase tracking-wider">Ghế Ngồi Cao Cấp</span>
                <h3 class="text-lg font-black mt-1">Đà Nẵng ➔ Hội An</h3>
              </div>
            </div>
            <div class="p-5 space-y-4">
              <div class="flex items-center justify-between text-xs text-slate-500 font-medium">
                <span>Cự ly: <strong>35 km</strong></span>
                <span>Thời gian: <strong>1.25 giờ</strong></span>
                <span>Tần suất: <strong>30 phút/chuyến</strong></span>
              </div>
              <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <div>
                  <span class="text-[10px] text-slate-400 block font-semibold">Giá vé từ:</span>
                  <span class="text-base font-black text-[#2563EB]">60.000đ</span>
                </div>
                <Link href="/trips?origin=Đà Nẵng&destination=Hội An" class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-[#2563EB] text-white text-xs font-bold transition">
                  Đặt vé ngay
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ==================== SECTION: QUALITY COMMITMENTS ==================== -->
      <section class="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm">
        <div class="text-center max-w-2xl mx-auto space-y-2 mb-10">
          <h2 class="text-2xl font-black text-slate-900">Cam Kết Chất Lượng BusHub Terminal</h2>
          <p class="text-xs sm:text-sm text-slate-500">Chúng tôi không ngừng nâng cao chất lượng dịch vụ vì sự an tâm và thoải mái của hành khách</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="text-center space-y-3 p-4 rounded-2xl bg-slate-50 border border-slate-100">
            <div class="w-12 h-12 bg-blue-100 text-[#2563EB] rounded-2xl flex items-center justify-center mx-auto text-xl font-bold">
              🚌
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Hệ Thống Xe Đời Mới</h3>
            <p class="text-xs text-slate-500 leading-relaxed">100% dòng xe giường nằm và limousine thế hệ mới trang bị cổng sạc, Wifi, rèm che riêng tư.</p>
          </div>

          <div class="text-center space-y-3 p-4 rounded-2xl bg-slate-50 border border-slate-100">
            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto text-xl font-bold">
              🛡️
            </div>
            <h3 class="font-bold text-slate-900 text-sm">An Toàn Tuyệt Đối</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Tài xế chuyên nghiệp, kiểm tra nồng độ cồn và kiểm tra kỹ thuật an toàn trước mỗi chuyến đi.</p>
          </div>

          <div class="text-center space-y-3 p-4 rounded-2xl bg-slate-50 border border-slate-100">
            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mx-auto text-xl font-bold">
              ⏱️
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Đúng Giờ Xuất Bến</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Cam kết xuất bến đúng lịch trình niêm yết, không đón khách dọc đường trái quy định.</p>
          </div>

          <div class="text-center space-y-3 p-4 rounded-2xl bg-slate-50 border border-slate-100">
            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center mx-auto text-xl font-bold">
              📱
            </div>
            <h3 class="font-bold text-slate-900 text-sm">Vé Điện Tử QR Code</h3>
            <p class="text-xs text-slate-500 leading-relaxed">Nhận vé và mã QR qua Gmail/SMS, check-in lên xe bằng camera không cần in vé giấy.</p>
          </div>
        </div>
      </section>

    </main>

    <Footer />
  </div>
</template>

<script setup>
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import SearchWidget from '@/Components/SearchWidget.vue';
import { Link } from '@inertiajs/vue3';

const copyCode = (code) => {
  navigator.clipboard.writeText(code);
  alert(`Đã sao chép mã ưu đãi: ${code}`);
};
</script>
