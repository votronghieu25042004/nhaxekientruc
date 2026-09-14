<template>
  <div class="w-full max-w-5xl mx-auto">
    <!-- Main Search Card (Clean BusHub & redBus Authentic Box) -->
    <div class="bg-white rounded-3xl p-5 sm:p-7 shadow-xl border border-slate-200 text-slate-800 transition-all duration-300">
      
      <!-- Top Row: Trip Type Radio & Quick Guide -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-5 border-b border-slate-100">
        <div class="flex items-center space-x-6 text-sm font-bold">
          <label class="inline-flex items-center space-x-2 cursor-pointer select-none">
            <input
              type="radio"
              :value="false"
              v-model="isRoundtrip"
              class="w-4 h-4 text-[#2563EB] focus:ring-[#2563EB] border-slate-300"
            />
            <span :class="!isRoundtrip ? 'text-[#2563EB] font-black' : 'text-slate-600'">Một chiều</span>
          </label>

          <label class="inline-flex items-center space-x-2 cursor-pointer select-none">
            <input
              type="radio"
              :value="true"
              v-model="isRoundtrip"
              class="w-4 h-4 text-[#2563EB] focus:ring-[#2563EB] border-slate-300"
            />
            <span :class="isRoundtrip ? 'text-[#2563EB] font-black' : 'text-slate-600'">Khứ hồi</span>
          </label>
        </div>

        <div class="text-xs text-slate-500 flex items-center gap-1.5 font-medium">
          <svg class="w-4 h-4 text-[#2563EB]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <a href="/schedules" class="hover:text-[#2563EB] transition underline">Hướng dẫn mua vé & Quy định hành lý</a>
        </div>
      </div>

      <!-- Main Input Controls Grid -->
      <form @submit.prevent="handleSearch" class="pt-5 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
          
          <!-- Origin (Điểm Đi - 4 Cols) -->
          <div class="md:col-span-3 relative">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Điểm Đi</label>
            <div class="flex items-center px-3.5 py-3 rounded-2xl border border-slate-200 focus-within:border-[#2563EB] focus-within:ring-2 focus-within:ring-[#2563EB]/15 bg-slate-50/50 hover:bg-white transition">
              <svg class="w-5 h-5 text-[#2563EB] shrink-0 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <select v-model="origin" required class="w-full bg-transparent font-bold text-slate-900 text-sm focus:outline-none cursor-pointer">
                <option value="">Chọn điểm đi...</option>
                <option v-for="loc in popularLocations" :key="'from-' + loc" :value="loc">{{ loc }}</option>
              </select>
            </div>
          </div>

          <!-- Swap Button (1 Col) -->
          <div class="md:col-span-1 flex items-center justify-center pt-5">
            <button
              @click="swapLocations"
              type="button"
              class="w-10 h-10 rounded-full border border-slate-200 bg-white hover:bg-blue-50 text-slate-600 hover:text-[#2563EB] flex items-center justify-center transition shadow-xs cursor-pointer active:rotate-180 duration-200"
              title="Đổi điểm đi và điểm đến"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </button>
          </div>

          <!-- Destination (Điểm Đến - 3 Cols) -->
          <div class="md:col-span-3 relative">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Điểm Đến</label>
            <div class="flex items-center px-3.5 py-3 rounded-2xl border border-slate-200 focus-within:border-[#2563EB] focus-within:ring-2 focus-within:ring-[#2563EB]/15 bg-slate-50/50 hover:bg-white transition">
              <svg class="w-5 h-5 text-emerald-600 shrink-0 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
              <select v-model="destination" required class="w-full bg-transparent font-bold text-slate-900 text-sm focus:outline-none cursor-pointer">
                <option value="">Chọn điểm đến...</option>
                <option v-for="loc in popularLocations" :key="'to-' + loc" :value="loc">{{ loc }}</option>
              </select>
            </div>
          </div>

          <!-- Departure Date (2 Cols) -->
          <div class="md:col-span-3 relative">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Ngày Đi</label>
            <div class="flex items-center px-3.5 py-3 rounded-2xl border border-slate-200 focus-within:border-[#2563EB] focus-within:ring-2 focus-within:ring-[#2563EB]/15 bg-slate-50/50 hover:bg-white transition">
              <svg class="w-5 h-5 text-slate-400 shrink-0 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              <input
                v-model="departureDate"
                type="date"
                required
                class="w-full bg-transparent font-bold text-slate-900 text-sm focus:outline-none cursor-pointer"
              />
            </div>
          </div>

          <!-- Return Date or Ticket Count (2 Cols) -->
          <div class="md:col-span-2 relative">
            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
              {{ isRoundtrip ? 'Ngày Về' : 'Số Lượng Vé' }}
            </label>
            <div v-if="isRoundtrip" class="flex items-center px-3 py-3 rounded-2xl border border-slate-200 focus-within:border-[#2563EB] bg-slate-50/50 hover:bg-white transition">
              <input v-model="returnDate" type="date" class="w-full bg-transparent font-bold text-slate-900 text-sm focus:outline-none cursor-pointer" />
            </div>
            <div v-else class="flex items-center px-3 py-3 rounded-2xl border border-slate-200 focus-within:border-[#2563EB] bg-slate-50/50 hover:bg-white transition">
              <select v-model="ticketCount" class="w-full bg-transparent font-bold text-slate-900 text-sm focus:outline-none cursor-pointer">
                <option :value="1">1 vé</option>
                <option :value="2">2 vé</option>
                <option :value="3">3 vé</option>
                <option :value="4">4 vé</option>
                <option :value="5">5 vé</option>
              </select>
            </div>
          </div>

        </div>

        <!-- Submit CTA Button (Authentic BusHub Orange) -->
        <div class="flex justify-center pt-2">
          <button
            type="submit"
            class="px-10 py-3.5 rounded-2xl bg-[#2563EB] hover:bg-[#D84315] text-white font-black text-sm uppercase tracking-wider transition-all duration-200 shadow-lg shadow-blue-600/25 flex items-center justify-center gap-2 cursor-pointer hover:scale-[1.02] active:scale-[0.98]"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span>TÌM CHUYẾN XE</span>
          </button>
        </div>
      </form>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const popularLocations = [
  'Đà Nẵng',
  'Quảng Trị (Đông Hà)',
  'Hội An',
  'TP. Hồ Chí Minh',
  'Cần Thơ',
  'Sóc Trăng',
  'Huế',
  'Nha Trang',
  'Hà Nội',
  'Hải Phòng'
];

const isRoundtrip = ref(false);
const origin = ref('Đà Nẵng');
const destination = ref('Quảng Trị (Đông Hà)');
const departureDate = ref(new Date().toISOString().split('T')[0]);
const returnDate = ref('');
const ticketCount = ref(1);

const swapLocations = () => {
  const temp = origin.value;
  origin.value = destination.value;
  destination.value = temp;
};

const handleSearch = () => {
  router.get('/trips', {
    origin: origin.value,
    destination: destination.value,
    date: departureDate.value,
    is_roundtrip: isRoundtrip.value,
    return_date: returnDate.value,
    ticket_count: ticketCount.value,
  });
};
</script>
