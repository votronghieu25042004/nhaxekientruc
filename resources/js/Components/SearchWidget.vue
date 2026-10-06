<template>
  <div class="relative max-w-4xl mx-auto w-full px-3 sm:px-4">
    <!-- Khung Tìm Kiếm Trắng -->
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-200/80 p-3.5 sm:p-4 relative z-20">
      
      <!-- HÀNG TRÊN: RADIO BUTTONS & HƯỚNG DẪN MUA VÉ -->
      <div class="flex items-center justify-between gap-4 pb-2 text-xs font-bold text-slate-800">
        <div class="flex items-center gap-5 sm:gap-6">
          <label class="flex items-center gap-2 cursor-pointer group select-none">
            <div class="relative flex items-center justify-center">
              <input type="radio" value="one_way" v-model="tripType" class="sr-only" />
              <div
                class="w-4 h-4 rounded-full border-2 transition-all flex items-center justify-center"
                :class="tripType === 'one_way' ? 'border-[#b91c1c] bg-white' : 'border-slate-300 group-hover:border-slate-400'"
              >
                <div v-if="tripType === 'one_way'" class="w-2 h-2 rounded-full bg-[#b91c1c]"></div>
              </div>
            </div>
            <span :class="tripType === 'one_way' ? 'text-slate-900 font-bold' : 'text-slate-600'">Một chiều</span>
          </label>

          <label class="flex items-center gap-2 cursor-pointer group select-none">
            <div class="relative flex items-center justify-center">
              <input type="radio" value="round_trip" v-model="tripType" class="sr-only" />
              <div
                class="w-4 h-4 rounded-full border-2 transition-all flex items-center justify-center"
                :class="tripType === 'round_trip' ? 'border-[#b91c1c] bg-white' : 'border-slate-300 group-hover:border-slate-400'"
              >
                <div v-if="tripType === 'round_trip'" class="w-2 h-2 rounded-full bg-[#b91c1c]"></div>
              </div>
            </div>
            <span :class="tripType === 'round_trip' ? 'text-slate-900 font-bold' : 'text-slate-600'">Khứ hồi</span>
          </label>
        </div>

        <button
          type="button"
          @click="showGuide = !showGuide"
          class="text-xs font-normal text-slate-600 hover:text-red-600 flex items-center gap-1.5 transition-colors cursor-pointer"
        >
          <span class="w-4 h-4 rounded-full border border-slate-400 text-[10px] flex items-center justify-center font-bold">?</span>
          <span>Hướng dẫn mua vé</span>
        </button>
      </div>

      <!-- THANH TÌM KIẾM BO CONG BÊN TRONG (CHIA HÀNG RỘNG RÃI KHI KHỨ HỒI ĐỂ KHÔNG BỊ CHE CHỮ) -->
      <div 
        class="bg-[#f0f2f5] border border-slate-200/80 rounded-2xl p-1.5 transition-all"
        :class="tripType === 'round_trip' ? 'flex flex-col gap-2' : 'flex flex-col md:flex-row items-stretch md:items-center'"
      >
        
        <!-- NHÓM 1: ĐIỂM ĐI (TỪ) & ĐIỂM ĐẾN (ĐẾN) -->
        <div 
          class="flex items-center flex-1 transition-all"
          :class="tripType === 'round_trip' ? 'bg-white rounded-xl border border-slate-200/80 p-1 shadow-2xs' : ''"
        >
          <!-- 1. Ô: TỪ -->
          <div class="flex-1 flex items-center gap-2 px-3 py-1.5 hover:bg-white/80 rounded-xl transition-colors relative">
            <div class="text-slate-700 flex-shrink-0">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-[10px] font-normal text-slate-500 leading-none">Từ</div>
              <input
                type="text"
                v-model="origin"
                @focus="showOriginSuggest = true"
                @blur="hideOriginSuggestLater"
                placeholder="Hà Nội (hoặc Điểm đi)"
                class="w-full bg-transparent text-slate-900 font-bold text-xs sm:text-[13px] outline-none placeholder-slate-400 p-0 border-none focus:ring-0 truncate mt-0.5"
              />
            </div>
            <button v-if="origin" @click="origin = ''" type="button" class="text-slate-400 hover:text-slate-600 text-xs p-1">✕</button>

            <!-- Dropdown Gợi Ý Điểm Đi -->
            <div
              v-if="showOriginSuggest"
              class="absolute top-full left-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 z-50 max-h-60 overflow-y-auto"
            >
              <div class="text-[10px] font-bold text-slate-400 px-3 py-1 uppercase tracking-wider">Điểm đón phổ biến</div>
              <button
                v-for="loc in filteredOriginSuggestions"
                :key="loc.name"
                type="button"
                @mousedown="selectOrigin(loc.name)"
                class="w-full text-left px-3 py-2 rounded-xl hover:bg-red-50 text-xs font-semibold text-slate-700 hover:text-red-700 flex items-center justify-between transition cursor-pointer"
              >
                <span>{{ loc.name }}</span>
                <span class="text-[10px] text-slate-400 font-normal truncate max-w-[120px]">{{ loc.desc }}</span>
              </button>
            </div>
          </div>

          <!-- NÚT ĐẢO CHIỀU ⇄ -->
          <div class="flex items-center justify-center my-0.5 md:my-0 px-1">
            <button
              type="button"
              @click="swapLocations"
              class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-[#1e293b] hover:bg-red-600 text-white flex items-center justify-center shadow-sm transition-all active:scale-90 cursor-pointer"
              title="Đảo chiều điểm đi - điểm đến"
            >
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
              </svg>
            </button>
          </div>

          <!-- 2. Ô: ĐẾN -->
          <div class="flex-1 flex items-center gap-2 px-3 py-1.5 hover:bg-white/80 rounded-xl transition-colors relative">
            <div class="text-slate-700 flex-shrink-0">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-[10px] font-normal text-slate-500 leading-none">Đến</div>
              <input
                type="text"
                v-model="destination"
                @focus="showDestSuggest = true"
                @blur="hideDestSuggestLater"
                placeholder="Nam Định (hoặc Điểm đến)"
                class="w-full bg-transparent text-slate-900 font-bold text-xs sm:text-[13px] outline-none placeholder-slate-400 p-0 border-none focus:ring-0 truncate mt-0.5"
              />
            </div>
            <button v-if="destination" @click="destination = ''" type="button" class="text-slate-400 hover:text-slate-600 text-xs p-1">✕</button>

            <!-- Dropdown Gợi Ý Điểm Đến -->
            <div
              v-if="showDestSuggest"
              class="absolute top-full left-0 mt-2 w-72 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 z-50 max-h-60 overflow-y-auto"
            >
              <div class="text-[10px] font-bold text-slate-400 px-3 py-1 uppercase tracking-wider">Điểm trả phổ biến</div>
              <button
                v-for="loc in filteredDestSuggestions"
                :key="loc.name"
                type="button"
                @mousedown="selectDest(loc.name)"
                class="w-full text-left px-3 py-2 rounded-xl hover:bg-red-50 text-xs font-semibold text-slate-700 hover:text-red-700 flex items-center justify-between transition cursor-pointer"
              >
                <span>{{ loc.name }}</span>
                <span class="text-[10px] text-slate-400 font-normal truncate max-w-[120px]">{{ loc.desc }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Vạch Phân Cách Mỏng (Chỉ hiện khi 1 chiều trên desktop) -->
        <div v-if="tripType === 'one_way'" class="w-[1px] h-8 bg-slate-300 hidden md:block mx-1"></div>

        <!-- NHÓM 2: NGÀY KHỞI HÀNH & NGÀY VỀ (RỘNG RÃI TRÊN 2 CỘT, KHÔNG CHE CHỮ) -->
        <div 
          :class="tripType === 'round_trip' ? 'grid grid-cols-1 sm:grid-cols-2 gap-2 w-full' : 'flex-[1.2] flex items-center'"
        >
          <!-- 3. Ô: NGÀY KHỞI HÀNH -->
          <div
            @click="openDatePicker"
            class="flex items-center justify-between gap-2 px-3.5 py-2 hover:bg-white rounded-xl transition-all relative cursor-pointer group"
            :class="tripType === 'round_trip' ? 'bg-white border border-slate-200/80 shadow-2xs' : 'w-full hover:bg-white/80'"
          >
            <input
              ref="datePickerInput"
              type="date"
              v-model="selectedDate"
              :min="todayStr"
              class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
            />
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="text-slate-700 group-hover:text-red-600 flex-shrink-0 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                  <line x1="16" x2="16" y1="2" y2="6"></line>
                  <line x1="8" x2="8" y1="2" y2="6"></line>
                  <line x1="3" x2="21" y1="10" y2="10"></line>
                </svg>
              </div>
              <div class="min-w-0 flex flex-col justify-center">
                <div class="text-[10px] font-normal text-slate-500 leading-none">Ngày khởi hành</div>
                <div class="text-xs sm:text-[13px] font-black text-slate-900 leading-tight mt-0.5 whitespace-nowrap">
                  {{ formatSelectedDateMain(selectedDate) }}
                </div>
                <div class="text-[11px] font-semibold text-slate-700 leading-none mt-0.5 whitespace-nowrap" v-if="formatSelectedDateSub(selectedDate)">
                  {{ formatSelectedDateSub(selectedDate) }}
                </div>
              </div>
            </div>

            <!-- 2 Nút Pill: Hôm nay / Ngày mai -->
            <div class="flex items-center gap-1.5 flex-shrink-0 z-20">
              <button
                type="button"
                @click.stop="selectedDate = todayStr"
                class="h-7 px-3 text-[11px] font-bold rounded-full transition cursor-pointer inline-flex items-center justify-center leading-none"
                :class="selectedDate === todayStr ? 'bg-[#dbeafe] text-[#1e40af]' : 'bg-[#e2e8f0] text-slate-700 hover:bg-slate-300'"
              >
                Hôm nay
              </button>
              <button
                type="button"
                @click.stop="selectedDate = tomorrowStr"
                class="h-7 px-3 text-[11px] font-bold rounded-full transition cursor-pointer inline-flex items-center justify-center leading-none"
                :class="selectedDate === tomorrowStr ? 'bg-[#fee2e2] text-[#dc2626]' : 'bg-[#fee2e2]/70 text-[#dc2626] hover:bg-[#fee2e2]'"
              >
                Ngày mai
              </button>
            </div>
          </div>

          <!-- 4. Ô: NGÀY VỀ (HIỂN THỊ KHI CHỌN KHỨ HỒI) -->
          <div
            v-if="tripType === 'round_trip'"
            @click="openReturnDatePicker"
            class="flex items-center justify-between gap-2 px-3.5 py-2 hover:bg-white rounded-xl transition-all relative cursor-pointer group bg-amber-50/70 border border-amber-300/80 shadow-2xs"
          >
            <input
              ref="returnDatePickerInput"
              type="date"
              v-model="selectedReturnDate"
              :min="selectedDate || todayStr"
              class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
            />
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="text-amber-700 group-hover:text-red-600 flex-shrink-0 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M3 12h18"></path>
                  <path d="m15 18 6-6-6-6"></path>
                </svg>
              </div>
              <div class="min-w-0 flex flex-col justify-center">
                <div class="text-[10px] font-black text-amber-800 uppercase tracking-wider leading-none">Ngày về (Khứ hồi)</div>
                <div class="text-xs sm:text-[13px] font-black text-slate-900 leading-tight mt-0.5 whitespace-nowrap">
                  {{ formatSelectedDateMain(selectedReturnDate) }}
                </div>
                <div class="text-[11px] font-semibold text-slate-700 leading-none mt-0.5 whitespace-nowrap" v-if="formatSelectedDateSub(selectedReturnDate)">
                  {{ formatSelectedDateSub(selectedReturnDate) }}
                </div>
              </div>
            </div>

            <!-- Nút chọn nhanh ngày về -->
            <div class="flex items-center gap-1.5 flex-shrink-0 z-20">
              <button
                type="button"
                @click.stop="setQuickReturn(1)"
                class="h-7 px-2.5 text-[11px] font-black rounded-full bg-amber-200 hover:bg-amber-300 text-amber-950 transition cursor-pointer"
              >
                +1 ngày
              </button>
              <button
                type="button"
                @click.stop="setQuickReturn(2)"
                class="h-7 px-2.5 text-[11px] font-black rounded-full bg-amber-200 hover:bg-amber-300 text-amber-950 transition cursor-pointer"
              >
                +2 ngày
              </button>
            </div>
          </div>
        </div>

      </div>

      <!-- HÀNG DƯỚI: PILL TÌM KIẾM NHANH BO TRÒN & NÚT TÌM KIẾM XE -->
      <div class="mt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        
        <!-- Danh Sách Pill Tìm Kiếm Nhanh Bo Tròn Viên Thuốc -->
        <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
          <button
            v-for="(rec, idx) in recentSearches"
            :key="idx"
            type="button"
            @click="applyRecentSearch(rec)"
            class="px-3 py-1 rounded-full bg-[#f1f3f5] hover:bg-slate-200 text-[#495057] hover:text-red-700 text-xs font-semibold transition cursor-pointer border border-transparent hover:border-slate-300"
          >
            {{ rec.origin }} - {{ rec.destination }}
          </button>
        </div>

        <!-- Nút Tìm Kiếm Xe Bo Tròn Màu Đỏ Cam Rực Rỡ -->
        <button
          type="button"
          @click="handleSearch"
          class="bg-gradient-to-r from-[#ff3c43] via-[#ee1d24] to-[#db1118] hover:from-[#ee1d24] hover:to-[#b80b11] active:scale-95 text-white font-bold text-sm py-2 px-6 rounded-full shadow-lg shadow-red-500/35 transition-all flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap ml-auto"
        >
          <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" x2="16.65" y1="21" y2="16.65"></line>
          </svg>
          <span>Tìm kiếm xe</span>
        </button>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  destinations: Array,
});

const today = new Date();
const todayStr = today.toISOString().split('T')[0];

const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
const tomorrowStr = tomorrow.toISOString().split('T')[0];

const tripType = ref('one_way');
const showGuide = ref(false);
const origin = ref('');
const destination = ref('');
const selectedDate = ref(todayStr);
const selectedReturnDate = ref(tomorrowStr);

const datePickerInput = ref(null);
const openDatePicker = () => {
  if (datePickerInput.value) {
    try {
      datePickerInput.value.showPicker();
    } catch (e) {
      datePickerInput.value.focus();
    }
  }
};

const returnDatePickerInput = ref(null);
const openReturnDatePicker = () => {
  if (returnDatePickerInput.value) {
    try {
      returnDatePickerInput.value.showPicker();
    } catch (e) {
      returnDatePickerInput.value.focus();
    }
  }
};

const setQuickReturn = (daysAfter) => {
  const base = selectedDate.value ? new Date(selectedDate.value) : new Date();
  base.setDate(base.getDate() + daysAfter);
  selectedReturnDate.value = base.toISOString().split('T')[0];
};

const showOriginSuggest = ref(false);
const showDestSuggest = ref(false);

const popularLocations = [
  { name: 'Hà Nội', desc: 'Bến xe Giáp Bát, Mỹ Đình, Nước Ngầm' },
  { name: 'Nam Định', desc: 'Bến xe Nam Định, Cầu Đò Quan' },
  { name: 'Đà Nẵng', desc: 'Bến xe TT Đà Nẵng, Cổng 4, Ngã Ba Huế' },
  { name: 'Thừa Thiên Huế', desc: 'Bến xe Phía Nam, An Cựu, Phú Bài' },
  { name: 'Quảng Trị', desc: 'Bến xe Đông Hà, Ngã Tư Sòng, TX Quảng Trị' },
  { name: 'Quảng Bình', desc: 'Đồng Hới, Lệ Thủy, Ba Đồn' },
  { name: 'Quy Nhơn', desc: 'Bình Định, Bến xe Quy Nhơn' },
  { name: 'Buôn Ma Thuột', desc: 'Đắk Lắk, BX Liên Tỉnh Đắk Lắk' },
  { name: 'Đà Lạt', desc: 'Lâm Đồng, BX Liên Tỉnh Đà Lạt' },
  { name: 'TP. Hồ Chí Minh', desc: 'BX Miền Đông Mới, BX Miền Tây' },
  { name: 'Cần Thơ', desc: 'Bến xe Cần Thơ, Ninh Kiều' },
];

const filteredOriginSuggestions = computed(() => {
  const q = origin.value.toLowerCase().trim();
  if (!q) return popularLocations;
  return popularLocations.filter(loc => loc.name.toLowerCase().includes(q) || loc.desc.toLowerCase().includes(q));
});

const filteredDestSuggestions = computed(() => {
  const q = destination.value.toLowerCase().trim();
  if (!q) return popularLocations;
  return popularLocations.filter(loc => loc.name.toLowerCase().includes(q) || loc.desc.toLowerCase().includes(q));
});

const selectOrigin = (name) => {
  origin.value = name;
  showOriginSuggest.value = false;
};

const selectDest = (name) => {
  destination.value = name;
  showDestSuggest.value = false;
};

const hideOriginSuggestLater = () => {
  setTimeout(() => { showOriginSuggest.value = false; }, 200);
};

const hideDestSuggestLater = () => {
  setTimeout(() => { showDestSuggest.value = false; }, 200);
};

const formatSelectedDateMain = (dateStr) => {
  if (!dateStr) return '07 Th10, 2026';
  const d = new Date(dateStr);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day} Th${month}, ${year}`;
};

const formatSelectedDateSub = (dateStr) => {
  if (!dateStr) return '(Hôm nay)';
  const isToday = dateStr === todayStr;
  const isTom = dateStr === tomorrowStr;
  return isToday ? '(Hôm nay)' : (isTom ? '(Ngày mai)' : '');
};

const recentSearches = ref([
  { origin: 'Hà Nội', destination: 'Nam Định' },
  { origin: 'Đà Nẵng', destination: 'Quảng Trị' },
  { origin: 'Đà Nẵng', destination: 'Thừa Thiên Huế' },
]);

onMounted(() => {
  try {
    const saved = localStorage.getItem('bushub_recent_searches');
    if (saved) {
      const parsed = JSON.parse(saved);
      if (Array.isArray(parsed) && parsed.length > 0) {
        recentSearches.value = parsed.map(item => ({
          origin: (item.origin || '').replace(/Thừa\s*Thiên\s*[-–—]\s*Huế/gi, 'Thừa Thiên Huế'),
          destination: (item.destination || '').replace(/Thừa\s*Thiên\s*[-–—]\s*Huế/gi, 'Thừa Thiên Huế'),
        }));
      }
    }
  } catch (e) {}
});

const swapLocations = () => {
  const temp = origin.value;
  origin.value = destination.value;
  destination.value = temp;
};

const applyRecentSearch = (rec) => {
  origin.value = rec.origin;
  destination.value = rec.destination;
};

const handleSearch = () => {
  if (origin.value || destination.value) {
    const newRecent = [
      { origin: origin.value || 'Hà Nội', destination: destination.value || 'Nam Định' },
      ...recentSearches.value.filter(item => !(item.origin === origin.value && item.destination === destination.value))
    ].slice(0, 4);
    recentSearches.value = newRecent;
    try {
      localStorage.setItem('bushub_recent_searches', JSON.stringify(newRecent));
    } catch (e) {}
  }

  router.get('/trips', {
    trip_type: tripType.value,
    origin: origin.value.trim(),
    destination: destination.value.trim(),
    date: selectedDate.value,
    return_date: tripType.value === 'round_trip' ? selectedReturnDate.value : undefined,
  });
};
</script>
