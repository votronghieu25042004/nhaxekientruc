<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-800 font-sans">
    <Navbar />

    <!-- Top Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-[#2563EB] text-white py-10 px-4 sm:px-8">
      <div class="max-w-7xl mx-auto space-y-3">
        <div class="flex items-center gap-2 text-xs text-blue-200">
          <Link href="/" class="hover:underline">Trang chủ</Link>
          <span>/</span>
          <span>Lịch trình các tuyến xe</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-black tracking-tight">LỊCH TRÌNH CÁC TUYẾN XE BUSHUB TERMINAL</h1>
        <p class="text-xs sm:text-sm text-blue-100 max-w-2xl">
          Tra cứu thông tin chi tiết các tuyến xe khách BusHub Terminal toàn quốc. Thời gian di chuyển, cự ly km và mức giá vé niêm yết chính thức.
        </p>

        <!-- Quick Filter Input -->
        <div class="pt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 max-w-3xl">
          <input
            v-model="originSearch"
            type="text"
            placeholder="Tìm theo nơi đi (VD: Đà Nẵng, Sài Gòn)..."
            class="px-3.5 py-2.5 rounded-xl bg-white/20 text-white placeholder-blue-200 text-xs font-bold border border-white/30 focus:outline-none focus:bg-white focus:text-slate-800 focus:placeholder-slate-400"
          />
          <input
            v-model="destSearch"
            type="text"
            placeholder="Tìm theo nơi đến (VD: Đông Hà, Cần Thơ)..."
            class="px-3.5 py-2.5 rounded-xl bg-white/20 text-white placeholder-blue-200 text-xs font-bold border border-white/30 focus:outline-none focus:bg-white focus:text-slate-800 focus:placeholder-slate-400"
          />
          <button
            @click="originSearch = ''; destSearch = ''"
            type="button"
            class="px-4 py-2.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xs font-bold transition border border-white/30"
          >
            Xóa bộ lọc
          </button>
        </div>
      </div>
    </div>

    <!-- Main Schedule Table Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
          <div class="font-black text-slate-900 text-base">
            Mạng Lưới Tuyến Đường ({{ filteredList.length }} tuyến)
          </div>
          <span class="text-xs font-bold text-[#00613D] bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
            Cập nhật liên tục 24/7
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-900 text-white uppercase text-[11px] font-black tracking-wider">
                <th class="py-4 px-6">Tuyến Đường</th>
                <th class="py-4 px-4">Loại Xe</th>
                <th class="py-4 px-4">Quãng Đường</th>
                <th class="py-4 px-4">Thời Gian Chạy</th>
                <th class="py-4 px-6 text-right">Hành Động</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="(item, idx) in filteredList" :key="idx" class="hover:bg-blue-50/40 transition">
                <td class="py-4 px-6">
                  <div class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                    <span>{{ item.origin }}</span>
                    <span class="text-[#2563EB]">➔</span>
                    <span>{{ item.destination }}</span>
                  </div>
                  <div class="text-[11px] text-slate-400 mt-0.5">{{ item.route || (item.origin + ' - ' + item.destination) }}</div>
                </td>
                <td class="py-4 px-4">
                  <span class="font-bold text-[#00613D] bg-emerald-50 px-2.5 py-1 rounded-md">{{ item.bus_type || 'Xe Giường Nằm VIP' }}</span>
                </td>
                <td class="py-4 px-4 font-medium text-slate-500">
                  {{ item.distance || '175km' }}
                </td>
                <td class="py-4 px-4 font-medium text-slate-600">
                  {{ item.duration || '3.5 giờ' }}
                </td>
                <td class="py-4 px-6 text-right">
                  <Link
                    :href="`/trips?origin=${encodeURIComponent(item.origin)}&destination=${encodeURIComponent(item.destination)}`"
                    class="px-4 py-2 rounded-xl bg-[#2563EB] hover:bg-[#D84315] text-white font-bold text-xs transition inline-flex items-center gap-1 shadow-xs"
                  >
                    <span>Tìm chuyến</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';

const props = defineProps({
  schedules: { type: Array, default: () => [] }
});

const originSearch = ref('');
const destSearch = ref('');

const fallbackSchedules = [
  { route: 'Đà Nẵng - Đông Hà (Quảng Trị)', origin: 'Đà Nẵng', destination: 'Quảng Trị (Đông Hà)', bus_type: 'Giường nằm VIP', distance: '175km', duration: '3.5 giờ' },
  { route: 'TP. Hồ Chí Minh - Cần Thơ', origin: 'TP. Hồ Chí Minh', destination: 'Cần Thơ', bus_type: 'Limousine VIP', distance: '165km', duration: '3.2 giờ' },
  { route: 'Đà Nẵng - Hội An', origin: 'Đà Nẵng', destination: 'Hội An', bus_type: 'Ghế ngồi cao cấp', distance: '35km', duration: '1.25 giờ' },
  { route: 'TP. Hồ Chí Minh - Sóc Trăng', origin: 'TP. Hồ Chí Minh', destination: 'Sóc Trăng', bus_type: 'Giường nằm VIP', distance: '230km', duration: '5.5 giờ' },
  { route: 'Đà Nẵng - Huế', origin: 'Đà Nẵng', destination: 'Huế', bus_type: 'Ghế ngồi cao cấp', distance: '100km', duration: '2.0 giờ' },
  { route: 'TP. Hồ Chí Minh - Đà Lạt', origin: 'TP. Hồ Chí Minh', destination: 'Đà Lạt', bus_type: 'Limousine VIP 34 Phòng', distance: '305km', duration: '6.5 giờ' },
  { route: 'TP. Hồ Chí Minh - Nha Trang', origin: 'TP. Hồ Chí Minh', destination: 'Nha Trang', bus_type: 'Cung Điện Di Động', distance: '430km', duration: '8.5 giờ' },
];

const list = computed(() => {
  if (props.schedules && props.schedules.length > 0) return props.schedules;
  return fallbackSchedules;
});

const filteredList = computed(() => {
  return list.value.filter(item => {
    const oMatch = !originSearch.value || (item.origin || item.route || '').toLowerCase().includes(originSearch.value.toLowerCase());
    const dMatch = !destSearch.value || (item.destination || item.route || '').toLowerCase().includes(destSearch.value.toLowerCase());
    return oMatch && dMatch;
  });
});
</script>
