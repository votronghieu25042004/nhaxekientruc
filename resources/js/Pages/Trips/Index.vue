<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans">
    <Navbar />

    <!-- 1. THANH TÌM KIẾM THEO CHUẨN VEXERE (Ảnh 1) CÓ AUTOCOMPLETE KHI GÕ ĐÀ NẴNG, HUẾ... -->
    <div class="bg-white border-b border-slate-200 py-4 shadow-xs sticky top-[68px] z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <!-- Khung tìm kiếm ngang Pill / Bo tròn -->
        <div class="bg-white border border-slate-300 hover:border-slate-400 rounded-2xl md:rounded-full shadow-sm p-1.5 md:p-2 flex flex-col md:flex-row items-stretch md:items-center gap-2 md:gap-0 transition-all">
          
          <!-- Box: TỪ (Có Autocomplete Suggestions) -->
          <div class="flex-1 flex items-center gap-3 px-4 py-2 border-b md:border-b-0 md:border-r border-slate-200 hover:bg-slate-50/80 rounded-xl md:rounded-l-full transition-colors relative">
            <div class="text-slate-700 flex-shrink-0">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M8 6v6"></path>
                <path d="M15 6v6"></path>
                <path d="M2 12h19.6"></path>
                <path d="M18 18h3s.5-1.7.8-2.8c.1-.4.2-.8.2-1.2 0-3.3-2.7-6-6-6H7c-3.3 0-6 2.7-6 6 0 .4.1.8.2 1.2.3 1.1.8 2.8.8 2.8h3"></path>
                <circle cx="7" cy="18" r="2"></circle>
                <circle cx="16" cy="18" r="2"></circle>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 leading-none">Từ</div>
              <input
                type="text"
                v-model="originInput"
                @focus="showOriginSuggest = true"
                @blur="hideOriginSuggestLater"
                @keydown.enter="executeSearch"
                placeholder="Chọn điểm đi (Hà Nội, Đà Nẵng...)"
                class="w-full bg-transparent text-slate-900 font-bold text-sm outline-none placeholder-slate-400 p-0 border-none focus:ring-0 truncate"
              />
            </div>
            <button
              v-if="originInput"
              @click="originInput = ''"
              type="button"
              class="text-slate-400 hover:text-slate-600 text-xs p-1"
            >
              ✕
            </button>

            <!-- Dropdown Gợi Ý Điểm Đi -->
            <div
              v-if="showOriginSuggest && filteredOriginSuggestions.length > 0"
              class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 py-2 z-50 max-h-56 overflow-y-auto animate-in fade-in duration-100"
            >
              <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Địa điểm gợi ý</div>
              <button
                v-for="(item, idx) in filteredOriginSuggestions"
                :key="idx"
                type="button"
                @mousedown="selectOrigin(item.name)"
                class="w-full text-left px-3.5 py-2 hover:bg-red-50 text-xs font-semibold text-slate-800 hover:text-red-700 flex items-center justify-between transition-colors cursor-pointer"
              >
                <div class="flex items-center gap-2">
                  <span>📍</span>
                  <span class="font-bold">{{ item.name }}</span>
                </div>
                <span class="text-[10px] text-slate-400">{{ item.desc }}</span>
              </button>
            </div>
          </div>

          <!-- Nút Đảo Chiều Tròn Đen ở giữa ⇄ -->
          <div class="flex justify-center -my-2 md:my-0 md:-mx-3 z-10">
            <button
              type="button"
              @click="swapLocations"
              title="Đảo chiều điểm đi - đến"
              class="w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-950 text-white flex items-center justify-center shadow-md hover:scale-105 active:scale-95 transition-all cursor-pointer border-2 border-white flex-shrink-0"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="m8 3 4 4-4 4"></path>
                <path d="M4 7h8"></path>
                <path d="m16 21-4-4 4-4"></path>
                <path d="M20 17h-8"></path>
              </svg>
            </button>
          </div>

          <!-- Box: ĐẾN (Có Autocomplete Suggestions) -->
          <div class="flex-1 flex items-center gap-3 px-4 py-2 border-b md:border-b-0 md:border-r border-slate-200 hover:bg-slate-50/80 rounded-xl transition-colors relative">
            <div class="text-slate-700 flex-shrink-0">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <rect width="16" height="16" x="4" y="3" rx="2"></rect>
                <path d="M4 11h16"></path>
                <path d="M8 15h.01"></path>
                <path d="M16 15h.01"></path>
                <path d="M6 19v2"></path>
                <path d="M18 19v2"></path>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 leading-none">ĐẾN</div>
              <input
                type="text"
                v-model="destinationInput"
                @focus="showDestSuggest = true"
                @blur="hideDestSuggestLater"
                @keydown.enter="executeSearch"
                placeholder="Chọn điểm đến (Quảng Trị, Nam Định...)"
                class="w-full bg-transparent text-slate-900 font-bold text-sm outline-none placeholder-slate-400 p-0 border-none focus:ring-0 truncate"
              />
            </div>
            <button
              v-if="destinationInput"
              @click="destinationInput = ''"
              type="button"
              class="text-slate-400 hover:text-slate-600 text-xs p-1"
            >
              ✕
            </button>

            <!-- Dropdown Gợi Ý Điểm Đến -->
            <div
              v-if="showDestSuggest && filteredDestSuggestions.length > 0"
              class="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 py-2 z-50 max-h-56 overflow-y-auto animate-in fade-in duration-100"
            >
              <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Địa điểm gợi ý</div>
              <button
                v-for="(item, idx) in filteredDestSuggestions"
                :key="idx"
                type="button"
                @mousedown="selectDest(item.name)"
                class="w-full text-left px-3.5 py-2 hover:bg-red-50 text-xs font-semibold text-slate-800 hover:text-red-700 flex items-center justify-between transition-colors cursor-pointer"
              >
                <div class="flex items-center gap-2">
                  <span>📍</span>
                  <span class="font-bold">{{ item.name }}</span>
                </div>
                <span class="text-[10px] text-slate-400">{{ item.desc }}</span>
              </button>
            </div>
          </div>

          <!-- Box: NGÀY KHỞI HÀNH (CÓ THỂ CLICK CHỌN MỞ LỊCH TRỰC TIẾP) -->
          <div
            @click="openTripDatePicker"
            class="flex-[1.2] flex items-center justify-between gap-3 px-4 py-2 hover:bg-slate-50/80 rounded-xl transition-colors relative cursor-pointer group"
          >
            <input
              ref="tripDatePicker"
              type="date"
              v-model="dateInput"
              @change="executeSearch"
              class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
            />
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <div class="text-slate-700 group-hover:text-red-600 flex-shrink-0 transition-colors">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect>
                  <line x1="16" x2="16" y1="2" y2="6"></line>
                  <line x1="8" x2="8" y1="2" y2="6"></line>
                  <line x1="3" x2="21" y1="10" y2="10"></line>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 leading-none">Ngày khởi hành</div>
                <div class="text-sm font-bold text-slate-900 flex items-center gap-1.5 mt-0.5">
                  <span>{{ formatSelectedDate(dateInput) }}</span>
                </div>
              </div>
            </div>

            <!-- 2 Pill Buttons: Hôm nay / Ngày mai -->
            <div class="flex items-center gap-1.5 flex-shrink-0">
              <button
                type="button"
                @click="setDateQuick('today')"
                class="px-2.5 py-1 text-xs font-bold rounded-full transition-all cursor-pointer"
                :class="isTodaySelected ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              >
                Hôm nay
              </button>
              <button
                type="button"
                @click="setDateQuick('tomorrow')"
                class="px-2.5 py-1 text-xs font-bold rounded-full transition-all cursor-pointer"
                :class="isTomorrowSelected ? 'bg-[#dc2626] text-white' : 'bg-red-50 text-red-700 hover:bg-red-100'"
              >
                Ngày mai
              </button>
            </div>
          </div>

          <!-- Nút Tìm Kiếm Tròn Đỏ (Icon Kính lúp SVG Trắng) -->
          <div class="p-1 flex items-center justify-end">
            <button
              type="button"
              @click="executeSearch"
              class="w-12 h-12 md:w-11 md:h-11 rounded-xl md:rounded-full bg-[#dc2626] hover:bg-[#b91c1c] active:scale-95 text-white flex items-center justify-center shadow-md shadow-red-500/25 transition-all cursor-pointer flex-shrink-0"
              title="Tìm chuyến xe"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" x2="16.65" y1="21" y2="16.65"></line>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN BODY: CỘT BỘ LỌC SIDEBAR (Ảnh 2) & DANH SÁCH CHUYẾN XE -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 flex-1 w-full">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- ============================================== -->
        <!-- CỘT TRÁI: SIDEBAR BỘ LỌC CHUẨN ẢNH 2 (3.5 cols) -->
        <!-- ============================================== -->
        <aside class="lg:col-span-4 xl:col-span-3.5 bg-white rounded-2xl border border-slate-200/90 shadow-sm p-4 sticky top-[152px] max-h-[calc(100vh-170px)] overflow-y-auto custom-scrollbar">
          
          <!-- Header Bộ Lọc + Nút Bỏ Lọc -->
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
              <span>Lọc</span>
              <span v-if="activeFilterCount > 0" class="w-5 h-5 rounded-full bg-red-600 text-white text-[10px] font-black flex items-center justify-center">
                {{ activeFilterCount }}
              </span>
            </h2>
            <button
              type="button"
              @click="resetAllFilters"
              class="text-xs font-bold text-red-600 hover:text-red-800 hover:underline cursor-pointer flex items-center gap-1"
            >
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                <path d="M3 3v5h5"></path>
              </svg>
              <span>Bỏ lọc</span>
            </button>
          </div>

          <!-- Dãy Badge / Pill Filter Nhanh Bo Viền Tròn (Theo Ảnh 2) -->
          <div class="py-3 border-b border-slate-100 flex flex-wrap gap-1.5">
            <button
              type="button"
              @click="toggleFastFilter('door_dropoff')"
              class="px-2.5 py-1 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer select-none"
              :class="fastFilters.door_dropoff ? 'bg-red-50 border-red-500 text-red-700 font-bold' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
            >
              <span>🏠 Đón/Trả tận nơi</span>
            </button>

            <button
              type="button"
              @click="openAccordion('operator')"
              class="px-2.5 py-1 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer select-none"
              :class="accordions.operator ? 'bg-indigo-50 border-indigo-400 text-indigo-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
            >
              <span>🚌 Nhà xe</span>
            </button>

            <button
              type="button"
              @click="openAccordion('pickup')"
              class="px-2.5 py-1 rounded-lg border text-xs font-semibold flex items-center gap-1 transition-all cursor-pointer select-none"
              :class="accordions.pickup ? 'bg-indigo-50 border-indigo-400 text-indigo-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
            >
              <span>Điểm lên</span>
            </button>

            <button
              type="button"
              @click="openAccordion('dropoff')"
              class="px-2.5 py-1 rounded-lg border text-xs font-semibold flex items-center gap-1 transition-all cursor-pointer select-none"
              :class="accordions.dropoff ? 'bg-indigo-50 border-indigo-400 text-indigo-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
            >
              <span>Điểm xuống</span>
            </button>

            <button
              type="button"
              @click="openAccordion('busType')"
              class="px-2.5 py-1 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer select-none"
              :class="accordions.busType ? 'bg-indigo-50 border-indigo-400 text-indigo-700' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
            >
              <span>🚌 Loại xe</span>
            </button>

            <button
              type="button"
              @click="toggleFastFilter('afternoon')"
              class="px-2.5 py-1 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer select-none"
              :class="fastFilters.afternoon ? 'bg-amber-50 border-amber-500 text-amber-800 font-bold' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
            >
              <span>☀️ Chiều (12:00 - 18:00)</span>
            </button>

            <button
              type="button"
              @click="toggleFastFilter('night')"
              class="px-2.5 py-1 rounded-lg border text-xs font-semibold flex items-center gap-1.5 transition-all cursor-pointer select-none"
              :class="fastFilters.night ? 'bg-indigo-50 border-indigo-500 text-indigo-800 font-bold' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'"
            >
              <span>🌙 Tối (18:00 - 24:00)</span>
            </button>
          </div>

          <!-- CÁC ACCORDIONS NHÓM LỌC -->
          <div class="divide-y divide-slate-100 text-sm">
            
            <!-- 1. THỜI GIAN KHỞI HÀNH -->
            <div class="py-3">
              <button
                type="button"
                @click="accordions.depTime = !accordions.depTime"
                class="w-full flex items-center justify-between text-left font-bold text-slate-900 group cursor-pointer"
              >
                <span>Thời gian khởi hành</span>
                <svg
                  class="w-4 h-4 text-slate-400 transition-transform duration-200 group-hover:text-slate-700"
                  :class="accordions.depTime ? 'rotate-180' : ''"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6"></path>
                </svg>
              </button>

              <div v-show="accordions.depTime" class="mt-2.5 space-y-2 pl-1 animate-in fade-in duration-150">
                <label 
                  v-for="t in depTimeOptions" 
                  :key="t.id" 
                  @click.prevent="toggleDepTime(t.id)"
                  class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-950 cursor-pointer select-none"
                >
                  <input 
                    type="radio" 
                    name="single_dep_time"
                    :checked="selectedDepTime === t.id" 
                    class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300 cursor-pointer" 
                  />
                  <span :class="selectedDepTime === t.id ? 'font-bold text-red-600' : ''">{{ t.label }}</span>
                </label>
              </div>
            </div>

            <!-- 2. THỜI GIAN ĐẾN -->
            <div class="py-3">
              <button
                type="button"
                @click="accordions.arrTime = !accordions.arrTime"
                class="w-full flex items-center justify-between text-left font-bold text-slate-900 group cursor-pointer"
              >
                <span>Thời gian đến</span>
                <svg
                  class="w-4 h-4 text-slate-400 transition-transform duration-200 group-hover:text-slate-700"
                  :class="accordions.arrTime ? 'rotate-180' : ''"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6"></path>
                </svg>
              </button>

              <div v-show="accordions.arrTime" class="mt-2.5 space-y-2 pl-1 animate-in fade-in duration-150">
                <label v-for="t in arrTimeOptions" :key="t.id" class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-950 cursor-pointer select-none">
                  <input type="checkbox" v-model="selectedArrTimes" :value="t.id" class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300" />
                  <span>{{ t.label }}</span>
                </label>
              </div>
            </div>

            <!-- 3. LOẠI XE -->
            <div class="py-3">
              <button
                type="button"
                @click="accordions.busType = !accordions.busType"
                class="w-full flex items-center justify-between text-left font-bold text-slate-900 group cursor-pointer"
              >
                <span>Loại xe</span>
                <svg
                  class="w-4 h-4 text-slate-400 transition-transform duration-200 group-hover:text-slate-700"
                  :class="accordions.busType ? 'rotate-180' : ''"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6"></path>
                </svg>
              </button>

              <div v-show="accordions.busType" class="mt-2.5 space-y-2 pl-1 animate-in fade-in duration-150">
                <label v-for="bt in busTypeOptions" :key="bt.id" class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-950 cursor-pointer select-none">
                  <input type="checkbox" v-model="selectedBusTypes" :value="bt.id" class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300" />
                  <span>{{ bt.label }}</span>
                </label>
              </div>
            </div>

            <!-- 4. TÍNH NĂNG XE KHÁCH -->
            <div class="py-3">
              <button
                type="button"
                @click="accordions.features = !accordions.features"
                class="w-full flex items-center justify-between text-left font-bold text-slate-900 group cursor-pointer"
              >
                <span>Tính năng xe khách</span>
                <svg
                  class="w-4 h-4 text-slate-400 transition-transform duration-200 group-hover:text-slate-700"
                  :class="accordions.features ? 'rotate-180' : ''"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6"></path>
                </svg>
              </button>

              <div v-show="accordions.features" class="mt-2.5 space-y-2 pl-1 animate-in fade-in duration-150">
                <label v-for="feat in featureOptions" :key="feat.id" class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-950 cursor-pointer select-none">
                  <input type="checkbox" v-model="selectedFeatures" :value="feat.id" class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300" />
                  <span>{{ feat.label }}</span>
                </label>
              </div>
            </div>

            <!-- 5. NHÀ XE -->
            <div class="py-3">
              <button
                type="button"
                @click="accordions.operator = !accordions.operator"
                class="w-full flex items-center justify-between text-left font-bold text-slate-900 group cursor-pointer"
              >
                <span>Nhà xe</span>
                <svg
                  class="w-4 h-4 text-slate-400 transition-transform duration-200 group-hover:text-slate-700"
                  :class="accordions.operator ? 'rotate-180' : ''"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6"></path>
                </svg>
              </button>

              <div v-show="accordions.operator" class="mt-2.5 space-y-2 pl-1 animate-in fade-in duration-150">
                <label v-for="op in operatorOptions" :key="op.id" class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-950 cursor-pointer select-none">
                  <input type="checkbox" v-model="selectedOperators" :value="op.id" class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300" />
                  <span>{{ op.label }}</span>
                </label>
              </div>
            </div>

            <!-- 6. ĐIỂM LÊN XE (LỌC ĐỘNG THEO ĐIỂM ĐI TÌM KIẾM) -->
            <div class="py-3">
              <button
                type="button"
                @click="accordions.pickup = !accordions.pickup"
                class="w-full flex items-center justify-between text-left font-bold text-slate-900 group cursor-pointer"
              >
                <div class="flex items-center gap-1.5">
                  <span>Điểm lên xe</span>
                  <span v-if="originInput" class="text-[10px] text-red-600 font-normal">({{ originInput }})</span>
                </div>
                <svg
                  class="w-4 h-4 text-slate-400 transition-transform duration-200 group-hover:text-slate-700"
                  :class="accordions.pickup ? 'rotate-180' : ''"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6"></path>
                </svg>
              </button>

              <div v-show="accordions.pickup" class="mt-2.5 space-y-2 pl-1 animate-in fade-in duration-150">
                <div v-if="dynamicPickupStops.length === 0" class="text-xs text-slate-400 italic">
                  Không có điểm lên phù hợp
                </div>
                <label v-for="st in dynamicPickupStops" :key="st" class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-950 cursor-pointer select-none">
                  <input type="checkbox" v-model="selectedPickupStops" :value="st" class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300" />
                  <span>{{ st }}</span>
                </label>
              </div>
            </div>

            <!-- 7. ĐIỂM XUỐNG XE (LỌC ĐỘNG THEO ĐIỂM ĐẾN TÌM KIẾM) -->
            <div class="py-3">
              <button
                type="button"
                @click="accordions.dropoff = !accordions.dropoff"
                class="w-full flex items-center justify-between text-left font-bold text-slate-900 group cursor-pointer"
              >
                <div class="flex items-center gap-1.5">
                  <span>Điểm xuống xe</span>
                  <span v-if="destinationInput" class="text-[10px] text-red-600 font-normal">({{ destinationInput }})</span>
                </div>
                <svg
                  class="w-4 h-4 text-slate-400 transition-transform duration-200 group-hover:text-slate-700"
                  :class="accordions.dropoff ? 'rotate-180' : ''"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6"></path>
                </svg>
              </button>

              <div v-show="accordions.dropoff" class="mt-2.5 space-y-2 pl-1 animate-in fade-in duration-150">
                <div v-if="dynamicDropoffStops.length === 0" class="text-xs text-slate-400 italic">
                  Không có điểm xuống phù hợp
                </div>
                <label v-for="st in dynamicDropoffStops" :key="st" class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-950 cursor-pointer select-none">
                  <input type="checkbox" v-model="selectedDropoffStops" :value="st" class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300" />
                  <span>{{ st }}</span>
                </label>
              </div>
            </div>

            <!-- 8. TIỆN NGHI & DỊCH VỤ -->
            <div class="py-3">
              <button
                type="button"
                @click="accordions.amenities = !accordions.amenities"
                class="w-full flex items-center justify-between text-left font-bold text-slate-900 group cursor-pointer"
              >
                <span>Tiện nghi</span>
                <svg
                  class="w-4 h-4 text-slate-400 transition-transform duration-200 group-hover:text-slate-700"
                  :class="accordions.amenities ? 'rotate-180' : ''"
                  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                >
                  <path d="m6 9 6 6 6-6"></path>
                </svg>
              </button>

              <div v-show="accordions.amenities" class="mt-2.5 space-y-2 pl-1 animate-in fade-in duration-150">
                <label v-for="am in amenityOptions" :key="am.id" class="flex items-center gap-2 text-xs text-slate-700 hover:text-slate-950 cursor-pointer select-none">
                  <input type="checkbox" v-model="selectedAmenities" :value="am.id" class="rounded text-red-600 focus:ring-red-500 w-4 h-4 border-slate-300" />
                  <span>{{ am.label }}</span>
                </label>
              </div>
            </div>

          </div>
        </aside>

        <!-- ============================================== -->
        <!-- CỘT PHẢI: DANH SÁCH CHUYẾN XE (8.5 cols) -->
        <!-- ============================================== -->
        <section class="lg:col-span-8 xl:col-span-8.5 space-y-4">
          
          <!-- Date Tabs Switcher: Hôm nay, Ngày mai, Ngày kia, Chọn ngày khác -->
          <div class="bg-white p-3.5 rounded-2xl border border-slate-200/90 shadow-2xs flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 overflow-x-auto pb-0.5">
              <button
                type="button"
                @click="setDateQuick('today')"
                :class="isTodaySelected ? 'bg-red-600 text-white font-black shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold'"
                class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer shrink-0"
              >
                <span>☀️ Hôm nay ({{ formatDayMonth(todayStr) }})</span>
              </button>

              <button
                type="button"
                @click="setDateQuick('tomorrow')"
                :class="isTomorrowSelected ? 'bg-red-600 text-white font-black shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold'"
                class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer shrink-0"
              >
                <span>📅 Ngày mai ({{ formatDayMonth(tomorrowStr) }})</span>
              </button>

              <button
                type="button"
                @click="setDateQuick('day_after')"
                :class="isDayAfterSelected ? 'bg-red-600 text-white font-black shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold'"
                class="px-4 py-2.5 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer shrink-0"
              >
                <span>🗓️ Ngày kia ({{ formatDayMonth(dayAfterStr) }})</span>
              </button>
            </div>

            <div class="flex items-center gap-2">
              <label class="text-xs font-bold text-slate-600 flex items-center gap-2 cursor-pointer bg-slate-50 hover:bg-slate-100 px-3.5 py-2 rounded-xl border border-slate-200 shadow-2xs">
                <span>📆 Chọn ngày:</span>
                <input
                  type="date"
                  v-model="dateInput"
                  @change="executeSearch"
                  class="bg-transparent font-bold text-xs text-slate-900 outline-none cursor-pointer"
                />
              </label>
            </div>
          </div>

          <!-- Thanh Tiêu Đề Kết Quả & Bộ Sắp Xếp -->
          <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h1 class="text-base sm:text-lg font-black text-slate-900">
                <span v-if="originInput || destinationInput">
                  Tuyến: {{ originInput || 'Tất cả' }} <span class="text-red-600">→</span> {{ destinationInput || 'Tất cả' }}
                </span>
                <span v-else>Chuyến xe ngày {{ formatSelectedDate(dateInput) }}</span>
              </h1>
              <p class="text-xs text-slate-500 mt-0.5">
                Tìm thấy <span class="font-bold text-red-600">{{ filteredTrips.length }} chuyến xe cố định</span> (Khởi hành từ Bến xe Đà Nẵng)
              </p>
            </div>

            <!-- Sắp xếp -->
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold text-slate-500 whitespace-nowrap">Sắp xếp:</span>
              <select
                v-model="sortBy"
                class="bg-slate-50 hover:bg-slate-100 text-xs font-bold text-slate-800 rounded-xl px-3 py-2 border border-slate-200 outline-none focus:ring-2 focus:ring-red-500 cursor-pointer"
              >
                <option value="scheduled_first">Xe chưa đi lên đầu</option>
                <option value="time_asc">Giờ đi sớm nhất</option>
                <option value="price_asc">Giá vé tăng dần</option>
                <option value="price_desc">Giá vé giảm dần</option>
              </select>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredTrips.length === 0" class="bg-white rounded-2xl border border-slate-200/90 p-12 text-center shadow-2xs">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-2xl mb-4">
              🔍
            </div>
            <h3 class="text-base font-bold text-slate-800">Không tìm thấy chuyến xe nào</h3>
            <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
              Không có chuyến xe nào khớp với điều kiện tìm kiếm. Vui lòng bấm bỏ lọc để xem toàn bộ danh sách.
            </p>
            <div class="mt-4">
              <button
                type="button"
                @click="resetAllFilters"
                class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-md transition-all cursor-pointer"
              >
                Bỏ Lọc & Xem Tất Cả Chuyến Xe
              </button>
            </div>
          </div>

          <!-- Danh sách các Thẻ Chuyến Xe -->
          <div
            v-for="trip in filteredTrips"
            :key="trip.id"
            class="bg-white rounded-2xl border border-slate-200/90 hover:border-red-300 hover:shadow-md transition-all p-5 shadow-2xs relative overflow-hidden group"
            :class="{ 'opacity-75 bg-slate-50/50': trip.status === 'RUNNING' || trip.status === 'COMPLETED' }"
          >
            <!-- Đường viền chỉ trạng thái màu cam khi xe đang chạy -->
            <div
              v-if="trip.status === 'RUNNING'"
              class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-500 via-orange-500 to-red-500"
            ></div>

            <!-- Hàng trên: Tuyến đường, Trạng thái & Biển số xe -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-slate-100">
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="text-base font-black text-slate-900 group-hover:text-red-600 transition-colors">
                    {{ trip.route?.name || (trip.route?.origin + ' ⇄ ' + trip.route?.destination) }}
                  </h3>

                  <!-- Badge Trạng thái Đang Chạy -->
                  <span
                    v-if="trip.status === 'RUNNING'"
                    class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-orange-50 text-orange-700 border border-orange-200 flex items-center gap-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-orange-600"></span>
                    <span>ĐÃ XUẤT BẾN • ĐANG CHẠY</span>
                  </span>

                  <!-- Badge Trạng thái Sắp chạy -->
                  <span
                    v-else-if="trip.status === 'SCHEDULED'"
                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200"
                  >
                    Sắp khởi hành
                  </span>

                  <!-- Badge Đã hoàn tất -->
                  <span
                    v-else-if="trip.status === 'COMPLETED'"
                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200"
                  >
                    ✓ Đã hoàn thành
                  </span>

                  <!-- Loại xe -->
                  <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ trip.bus?.bus_type || trip.bus?.model || 'VIP Giường Nằm' }}
                  </span>
                </div>

                <div class="text-xs text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
                  <span>Biển số xe: <strong class="text-slate-900 font-mono bg-slate-100 px-1.5 py-0.5 rounded">{{ trip.bus?.license_plate || trip.bus?.plate_number || '43B-012.34' }}</strong></span>
                  <span>•</span>
                  <span>Bác tài: <strong class="text-slate-800">{{ trip.driver?.name || 'Bác tài nhaxekientruc' }}</strong> ({{ trip.driver?.phone || '0905.123.456' }})</span>
                </div>
              </div>

              <!-- Giá Vé & Cọc 30% -->
              <div class="text-left sm:text-right">
                <div class="text-xl font-black" :class="trip.status === 'RUNNING' ? 'text-slate-500' : 'text-red-600'">
                  {{ formatPrice(trip.price || trip.base_price || 140000) }}
                </div>
                <div class="text-[11px] font-bold text-emerald-600 mt-0.5">
                  Cọc linh hoạt 30% chỉ {{ formatPrice((trip.price || trip.base_price || 140000) * 0.3) }}
                </div>
              </div>
            </div>

            <!-- Hàng dưới: Lịch trình giờ chạy & Nút Đặt Vé -->
            <div class="pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              
              <!-- Timeline điểm đón -> điểm trả -->
              <div class="flex items-center gap-4 flex-1">
                <div>
                  <div class="text-lg font-black text-slate-900">
                    {{ formatTime(trip.departure_time) }}
                  </div>
                  <div class="text-xs text-slate-500 font-bold truncate max-w-[120px]" :title="trip.route?.origin">
                    {{ trip.route?.origin || 'Điểm đón' }}
                  </div>
                </div>

                <div class="flex-1 max-w-[180px] flex flex-col items-center">
                  <span
                    v-if="trip.status === 'RUNNING'"
                    class="text-[10px] font-bold text-orange-700 bg-orange-100 px-2 py-0.5 rounded-full"
                  >
                    ⚡ Đã xuất bến
                  </span>
                  <span
                    v-else
                    class="text-[10px] text-slate-500 font-bold"
                    :title="'Thời gian di chuyển thực tế (Đã tính ~1h dự phòng kẹt xe & nghỉ ngơi tại trạm dừng)'"
                  >
                    {{ getTripDuration(trip) }}
                  </span>

                  <!-- Line mũi tên -->
                  <div class="w-full flex items-center mt-1">
                    <div class="w-2.5 h-2.5 rounded-full" :class="trip.status === 'RUNNING' ? 'bg-orange-500' : 'bg-red-600'"></div>
                    <div
                      class="flex-1 h-0.5 border-t border-dashed"
                      :class="trip.status === 'RUNNING' ? 'border-orange-300' : 'border-slate-300'"
                    ></div>
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-600"></div>
                  </div>
                </div>

                <div>
                  <div class="text-lg font-black text-slate-900">
                    {{ formatTime(trip.arrival_time || trip.departure_time) }}
                  </div>
                  <div class="text-xs text-slate-500 font-bold truncate max-w-[120px]" :title="trip.route?.destination">
                    {{ trip.route?.destination || 'Điểm đến' }}
                  </div>
                </div>
              </div>

              <!-- Nút Đặt Vé / Khóa khi Xe Đang Chạy hoặc Đã Quá Giờ Khởi Hành -->
              <div>
                <!-- 1. Xe Đã Chạy hoặc Quá Giờ Khởi Hành: Khóa đặt vé -->
                <button
                  v-if="isTripPast(trip) || trip.status === 'RUNNING' || trip.status === 'COMPLETED' || trip.can_book === false"
                  type="button"
                  disabled
                  class="inline-block w-full sm:w-auto px-5 py-2.5 bg-slate-100 text-slate-400 font-bold text-xs uppercase tracking-wider rounded-xl border border-slate-200 text-center cursor-not-allowed select-none"
                >
                  ⚡ Xe Đã Chạy • Ngừng Đặt
                </button>

                <!-- 2. Xe Sắp Khởi Hành: Cho phép chọn ghế đặt vé -->
                <button
                  v-else
                  type="button"
                  @click="handleBookTrip(trip.id)"
                  class="inline-block w-full sm:w-auto px-6 py-3 bg-[#dc2626] hover:bg-[#b91c1c] active:scale-98 text-white font-black text-sm uppercase tracking-wider rounded-xl shadow-md shadow-red-500/25 text-center transition-all cursor-pointer"
                >
                  Chọn Ghế & Đặt Vé
                </button>
              </div>
            </div>
          </div>
        </section>

      </div>
    </main>

    <Footer />
    <AiChatModal />
    <AuthModal v-model="showAuthModal" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Footer from '@/Components/Footer.vue';
import AiChatModal from '@/Components/AiChatModal.vue';
import AuthModal from '@/Components/AuthModal.vue';

const page = usePage();
const showAuthModal = ref(false);

const handleBookTrip = (tripId) => {
  if (!page.props.auth?.user) {
    showAuthModal.value = true;
    return;
  }
  router.visit(`/trips/${tripId}`);
};


const tripDatePicker = ref(null);
const openTripDatePicker = () => {
  if (tripDatePicker.value) {
    try {
      tripDatePicker.value.showPicker();
    } catch (e) {
      tripDatePicker.value.focus();
    }
  }
};

const isTripPast = (trip) => {
  if (trip.can_book === false) return true;
  if (['COMPLETED', 'CLOSED', 'CANCELLED'].includes(trip.status)) return true;
  if (trip.status === 'RUNNING' || trip.status === 'IN_TRANSIT') return true;
  if (trip.status === 'BOARDING' || trip.status === 'OPEN_FOR_SALE' || trip.status === 'READY') {
    return false; // Chuyến mở bán hoặc đang đón khách luôn cho phép đặt
  }
  if (!trip.departure_time) return false;
  const depStr = trip.departure_time.replace(' ', 'T');
  const dep = new Date(depStr);
  const now = props.current_time ? new Date(props.current_time.replace(' ', 'T')) : new Date();
  return dep <= now;
};

const isBookableTrip = (trip) => {
  if (trip.can_book === false) return false;
  if (trip.is_expired === true) return false;
  if (['COMPLETED', 'CLOSED', 'CANCELLED', 'IN_TRANSIT'].includes(trip.status)) return false;
  if (trip.status === 'BOARDING') return true;
  if (isTripPast(trip)) return false;
  return true;
};

const getTripDuration = (trip) => {
  if (trip.departure_time && trip.arrival_time) {
    const dep = new Date(trip.departure_time);
    const arr = new Date(trip.arrival_time);
    const diffMs = arr.getTime() - dep.getTime();
    if (diffMs > 0) {
      const hours = Math.round((diffMs / (1000 * 60 * 60)) * 10) / 10;
      return `${hours}h di chuyển`;
    }
  }
  const est = parseFloat(trip.route?.estimated_hours || 3.5);
  return `${est}h di chuyển`;
};

const props = defineProps({
  trips: [Array, Object],
  routes: Array,
  filters: Object,
  current_time: String,
});

const baseDate = props.current_time ? new Date(props.current_time) : new Date();
const todayStr = baseDate.toISOString().split('T')[0];
const tomorrow = new Date(baseDate);
tomorrow.setDate(tomorrow.getDate() + 1);
const tomorrowStr = tomorrow.toISOString().split('T')[0];
const dayAfter = new Date(baseDate);
dayAfter.setDate(dayAfter.getDate() + 2);
const dayAfterStr = dayAfter.toISOString().split('T')[0];

const originInput = ref(props.filters?.origin || '');
const destinationInput = ref(props.filters?.destination || '');
const dateInput = ref(props.filters?.date || todayStr);
const sortBy = ref('scheduled_first');

const isTodaySelected = computed(() => dateInput.value === todayStr);
const isTomorrowSelected = computed(() => dateInput.value === tomorrowStr);
const isDayAfterSelected = computed(() => dateInput.value === dayAfterStr);

// Autocomplete Suggestions
const showOriginSuggest = ref(false);
const showDestSuggest = ref(false);

const popularLocations = [
  { name: 'Đà Nẵng', desc: 'Bến xe TT Đà Nẵng, Cổng 4, Ngã Ba Huế' },
  { name: 'Thừa Thiên Huế', desc: 'Bến xe Phía Nam, An Cựu, Phú Bài' },
  { name: 'Quảng Trị', desc: 'Bến xe Đông Hà, Ngã Tư Sòng, TX Quảng Trị' },
  { name: 'Quảng Bình', desc: 'Đồng Hới, Lệ Thủy, Ba Đồn' },
  { name: 'Quy Nhơn', desc: 'Bình Định, Bến xe Quy Nhơn' },
  { name: 'Buôn Ma Thuột', desc: 'Đắk Lắk, BX Liên Tỉnh Đắk Lắk' },
  { name: 'Đà Lạt', desc: 'Lâm Đồng, BX Liên Tỉnh Đà Lạt' },
  { name: 'TP. Hồ Chí Minh', desc: 'BX Miền Đông Mới, BX Miền Tây' },
  { name: 'Cần Thơ', desc: 'Bến xe Cần Thơ, Ninh Kiều' },
  { name: 'Hà Nội', desc: 'Bến xe Giáp Bát, Mỹ Đình, Nước Ngầm' },
  { name: 'Nam Định', desc: 'Bến xe Nam Định, Cầu Đò Quan' },
];

const filteredOriginSuggestions = computed(() => {
  const q = originInput.value.toLowerCase().trim();
  if (!q) return popularLocations;
  return popularLocations.filter(loc => loc.name.toLowerCase().includes(q) || loc.desc.toLowerCase().includes(q));
});

const filteredDestSuggestions = computed(() => {
  const q = destinationInput.value.toLowerCase().trim();
  if (!q) return popularLocations;
  return popularLocations.filter(loc => loc.name.toLowerCase().includes(q) || loc.desc.toLowerCase().includes(q));
});

const selectOrigin = (name) => {
  originInput.value = name;
  showOriginSuggest.value = false;
  executeSearch();
};

const selectDest = (name) => {
  destinationInput.value = name;
  showDestSuggest.value = false;
  executeSearch();
};

const hideOriginSuggestLater = () => {
  setTimeout(() => { showOriginSuggest.value = false; }, 200);
};

const hideDestSuggestLater = () => {
  setTimeout(() => { showDestSuggest.value = false; }, 200);
};

const alertRunningTrip = () => {
  alert('⚠️ Chuyến xe này đã xuất bến và đang di chuyển trên đường. Hệ thống đã đóng cổng đặt vé trực tuyến cho chuyến này. Quý khách vui lòng chọn chuyến xe tiếp theo!');
};

// Accordions
const accordions = reactive({
  depTime: false,
  arrTime: false,
  busType: false,
  features: false,
  operator: false,
  pickup: true,
  dropoff: true,
  amenities: false,
});

const openAccordion = (name) => { accordions[name] = true; };

// Fast Filter Badges
const fastFilters = reactive({
  door_dropoff: false,
  afternoon: false,
  night: false,
});

const selectedDepTime = ref('');
const toggleDepTime = (typeId) => {
  if (selectedDepTime.value === typeId) {
    selectedDepTime.value = '';
    fastFilters.afternoon = false;
    fastFilters.night = false;
  } else {
    selectedDepTime.value = typeId;
    fastFilters.afternoon = (typeId === 'afternoon');
    fastFilters.night = (typeId === 'night');
  }
};

const toggleFastFilter = (type) => {
  if (type === 'afternoon' || type === 'night') {
    toggleDepTime(type);
  } else {
    fastFilters[type] = !fastFilters[type];
  }
};

// Checkboxes
const selectedArrTimes = ref([]);
const selectedBusTypes = ref([]);
const selectedFeatures = ref([]);
const selectedOperators = ref([]);
const selectedPickupStops = ref([]);
const selectedDropoffStops = ref([]);
const selectedAmenities = ref([]);

const depTimeOptions = [
  { id: 'early', label: 'Sáng sớm (00:00 - 06:00)' },
  { id: 'morning', label: 'Buổi sáng (06:00 - 12:00)' },
  { id: 'afternoon', label: 'Buổi chiều (12:00 - 18:00)' },
  { id: 'night', label: 'Buổi tối (18:00 - 24:00)' },
];

const arrTimeOptions = [
  { id: 'early', label: 'Sáng sớm (00:00 - 06:00)' },
  { id: 'morning', label: 'Buổi sáng (06:00 - 12:00)' },
  { id: 'afternoon', label: 'Buổi chiều (12:00 - 18:00)' },
  { id: 'night', label: 'Buổi tối (18:00 - 24:00)' },
];

const busTypeOptions = [
  { id: 'limousine', label: 'Limousine VIP 34 Phòng' },
  { id: 'sleeper', label: 'VIP Giường Nằm 40 Chỗ' },
  { id: 'seater', label: 'Ghế Ngồi Cao Cấp 28 Chỗ' },
  { id: 'palace', label: 'Cung Điện Di Động VIP' },
];

const featureOptions = [
  { id: 'wifi', label: 'Wifi tốc độ cao miễn phí' },
  { id: 'usb', label: 'Cổng sạc Type-C & USB' },
  { id: 'water', label: 'Nước uống & khăn lạnh' },
  { id: 'curtain', label: 'Rèm che riêng tư từng phòng' },
  { id: 'uv', label: 'Khử khuẩn ion kháng khuẩn' },
];

const operatorOptions = [
  { id: 'express', label: 'nhaxekientruc Express' },
  { id: 'luxury', label: 'nhaxekientruc VIP Luxury' },
  { id: 'royal', label: 'nhaxekientruc Royal Star' },
];

const amenityOptions = [
  { id: 'home_drop', label: 'Trả tận nhà' },
  { id: 'home_pick', label: 'Đón tận nơi' },
  { id: 'deposit_30', label: 'Cọc linh hoạt 30%' },
  { id: 'qr_ticket', label: 'Vé điện tử QR Check-in' },
];

const dynamicPickupStops = computed(() => {
  const stopsSet = new Set();
  const relevantTrips = rawTrips.value;
  
  // 1. Gather from trips on screen
  if (relevantTrips && relevantTrips.length > 0) {
    relevantTrips.forEach(t => {
      const stops = t.route?.stops || [];
      const count = stops.length;
      stops.forEach((s, idx) => {
        const order = s.stop_order || (idx + 1);
        const nameLower = (s.stop_name || '').toLowerCase();
        if ((order <= Math.max(2, Math.ceil(count / 2)) || nameLower.includes('đón')) && !nameLower.includes('trả khách')) {
          if (s.stop_name) stopsSet.add(s.stop_name.trim());
        }
      });
    });
  }
  
  // 2. If set is empty, gather from props.routes matching search
  if (stopsSet.size === 0 && props.routes && props.routes.length > 0) {
    const orig = (originInput.value || '').toLowerCase().trim();
    const dest = (destinationInput.value || '').toLowerCase().trim();
    const matchingRoutes = props.routes.filter(r => {
      const rOrig = (r.origin || '').toLowerCase();
      const rDest = (r.destination || '').toLowerCase();
      if (orig && !rOrig.includes(orig)) return false;
      if (dest && !rDest.includes(dest)) return false;
      return true;
    });
    const targetRoutes = matchingRoutes.length > 0 ? matchingRoutes : props.routes;
    targetRoutes.forEach(r => {
      const stops = r.stops || [];
      const count = stops.length;
      stops.forEach((s, idx) => {
        const order = s.stop_order || (idx + 1);
        const nameLower = (s.stop_name || '').toLowerCase();
        if ((order <= Math.max(2, Math.ceil(count / 2)) || nameLower.includes('đón')) && !nameLower.includes('trả khách')) {
          if (s.stop_name) stopsSet.add(s.stop_name.trim());
        }
      });
    });
  }
  
  return Array.from(stopsSet);
});

const dynamicDropoffStops = computed(() => {
  const stopsSet = new Set();
  const relevantTrips = rawTrips.value;
  
  if (relevantTrips && relevantTrips.length > 0) {
    relevantTrips.forEach(t => {
      const stops = t.route?.stops || [];
      const count = stops.length;
      stops.forEach((s, idx) => {
        const order = s.stop_order || (idx + 1);
        const nameLower = (s.stop_name || '').toLowerCase();
        if ((order > Math.floor(count / 2) || nameLower.includes('trả')) && idx > 0) {
          if (s.stop_name) stopsSet.add(s.stop_name.trim());
        }
      });
    });
  }
  
  if (stopsSet.size === 0 && props.routes && props.routes.length > 0) {
    const orig = (originInput.value || '').toLowerCase().trim();
    const dest = (destinationInput.value || '').toLowerCase().trim();
    const matchingRoutes = props.routes.filter(r => {
      const rOrig = (r.origin || '').toLowerCase();
      const rDest = (r.destination || '').toLowerCase();
      if (orig && !rOrig.includes(orig)) return false;
      if (dest && !rDest.includes(dest)) return false;
      return true;
    });
    const targetRoutes = matchingRoutes.length > 0 ? matchingRoutes : props.routes;
    targetRoutes.forEach(r => {
      const stops = r.stops || [];
      const count = stops.length;
      stops.forEach((s, idx) => {
        const order = s.stop_order || (idx + 1);
        const nameLower = (s.stop_name || '').toLowerCase();
        if ((order > Math.floor(count / 2) || nameLower.includes('trả')) && idx > 0) {
          if (s.stop_name) stopsSet.add(s.stop_name.trim());
        }
      });
    });
  }
  
  return Array.from(stopsSet);
});

const activeFilterCount = computed(() => {
  let count = 0;
  if (fastFilters.door_dropoff) count++;
  if (selectedDepTime.value) count++;
  count += selectedArrTimes.value.length;
  count += selectedBusTypes.value.length;
  count += selectedFeatures.value.length;
  count += selectedOperators.value.length;
  count += selectedPickupStops.value.length;
  count += selectedDropoffStops.value.length;
  count += selectedAmenities.value.length;
  if (originInput.value) count++;
  if (destinationInput.value) count++;
  return count;
});

const setDateQuick = (type) => {
  if (type === 'today') { dateInput.value = todayStr; }
  else if (type === 'tomorrow') { dateInput.value = tomorrowStr; }
  else if (type === 'day_after') { dateInput.value = dayAfterStr; }
  executeSearch();
};

const formatDayMonth = (dateStr) => {
  if (!dateStr) return '';
  const parts = dateStr.split('-');
  return `${parts[2]}/${parts[1]}`;
};

const formatSelectedDate = (dateStr) => {
  if (!dateStr) return 'Chọn ngày';
  const d = new Date(dateStr);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  const isToday = dateStr === todayStr;
  const isTom = dateStr === tomorrowStr;
  const isDayAft = dateStr === dayAfterStr;
  const tag = isToday ? '(Hôm nay)' : (isTom ? '(Ngày mai)' : (isDayAft ? '(Ngày kia)' : ''));
  return `${day}/${month}/${year} ${tag}`;
};

const swapLocations = () => {
  const temp = originInput.value;
  originInput.value = destinationInput.value;
  destinationInput.value = temp;
  executeSearch();
};

const rawTrips = computed(() => {
  if (Array.isArray(props.trips)) return props.trips;
  if (props.trips && Array.isArray(props.trips.data)) return props.trips.data;
  return [];
});

// SẮP XẾP XE CHƯA ĐI LÊN ĐẦU TIÊN, XE ĐANG ĐI HOẶC HOÀN THÀNH XUỐNG DƯỚI (THEO YÊU CẦU CỦA BẠN)
const filteredTrips = computed(() => {
  let list = [...rawTrips.value];

  if (selectedDepTime.value) {
    list = list.filter(t => {
      const hour = new Date(t.departure_time).getHours();
      if (selectedDepTime.value === 'early') return hour >= 0 && hour < 6;
      if (selectedDepTime.value === 'morning') return hour >= 6 && hour < 12;
      if (selectedDepTime.value === 'afternoon') return hour >= 12 && hour < 18;
      if (selectedDepTime.value === 'night') return hour >= 18 && hour < 24;
      return true;
    });
  }

  if (selectedPickupStops.value.length > 0) {
    list = list.filter(t => {
      const stops = t.route?.stops || [];
      return stops.some(s => selectedPickupStops.value.includes(s.stop_name));
    });
  }

  if (selectedDropoffStops.value.length > 0) {
    list = list.filter(t => {
      const stops = t.route?.stops || [];
      return stops.some(s => selectedDropoffStops.value.includes(s.stop_name));
    });
  }

  if (selectedBusTypes.value.length > 0) {
    list = list.filter(t => {
      const type = (t.bus?.bus_type || '').toLowerCase();
      return selectedBusTypes.value.some(b => type.includes(b.toLowerCase()));
    });
  }

  // THUẬT TOÁN ƯU TIÊN TUYỆT ĐỐI: Xe chưa đi & còn đặt vé được (isBookableTrip) LUÔN LÊN ĐẦU TIÊN
  list.sort((a, b) => {
    const bookableA = isBookableTrip(a);
    const bookableB = isBookableTrip(b);

    if (bookableA !== bookableB) {
      return bookableA ? -1 : 1; // Xe chưa chạy (bookable) luôn xếp trên xe đã chạy/ngừng đặt
    }

    // Nếu cùng nhóm (cùng chưa đi hoặc cùng đã đi): sắp xếp theo tiêu chí người dùng chọn
    if (sortBy.value === 'price_asc') {
      return (a.price || a.base_price || 0) - (b.price || b.base_price || 0);
    } else if (sortBy.value === 'price_desc') {
      return (b.price || b.base_price || 0) - (a.price || a.base_price || 0);
    } else {
      return new Date(a.departure_time) - new Date(b.departure_time);
    }
  });

  return list;
});

const executeSearch = () => {
  router.get('/trips', {
    origin: originInput.value.trim(),
    destination: destinationInput.value.trim(),
    date: dateInput.value,
  }, { preserveState: true, preserveScroll: true });
};

const resetAllFilters = () => {
  originInput.value = '';
  destinationInput.value = '';
  dateInput.value = todayStr;
  fastFilters.door_dropoff = false;
  fastFilters.afternoon = false;
  fastFilters.night = false;
  selectedDepTime.value = '';
  selectedArrTimes.value = [];
  selectedBusTypes.value = [];
  selectedFeatures.value = [];
  selectedOperators.value = [];
  selectedPickupStops.value = [];
  selectedDropoffStops.value = [];
  selectedAmenities.value = [];
  sortBy.value = 'scheduled_first';
  router.get('/trips');
};

const formatPrice = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const formatTime = (timeStr) => {
  if (!timeStr) return '08:00';
  if (timeStr.length >= 16) {
    return timeStr.substring(11, 16);
  }
  return timeStr.substring(0, 5);
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>
