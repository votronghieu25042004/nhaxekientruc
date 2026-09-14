<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-800 font-sans">
    <Navbar />

    <!-- Top Search Header & Quick Date Navigation -->
    <div class="bg-white border-b border-slate-200 sticky top-16 z-30 shadow-xs">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
          <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#2563EB] border border-blue-200 flex items-center justify-center font-black">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
          </div>
          <div>
            <div class="font-black text-slate-900 text-base sm:text-lg flex items-center gap-2">
              <span>{{ searchOrigin || 'Đà Nẵng' }}</span>
              <span class="text-[#2563EB]">➔</span>
              <span>{{ searchDestination || 'Quảng Trị (Đông Hà)' }}</span>
            </div>
            <div class="text-xs text-slate-500 font-medium">
              Ngày đi: <strong class="text-slate-900">{{ searchDate || 'Hôm nay' }}</strong> • Tìm thấy <strong class="text-[#2563EB]">{{ filteredTrips.length }}</strong> chuyến xe phù hợp
            </div>
          </div>
        </div>

        <!-- Date Shift Buttons -->
        <div class="flex items-center space-x-2 text-xs font-bold">
          <button @click="shiftDate(-1)" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition cursor-pointer shadow-2xs">
            ← Ngày trước
          </button>
          <span class="px-3.5 py-2 rounded-xl bg-blue-50 text-[#2563EB] border border-blue-200 font-black">
            {{ searchDate || 'Hôm nay' }}
          </span>
          <button @click="shiftDate(1)" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 transition cursor-pointer shadow-2xs">
            Ngày sau →
          </button>
        </div>
      </div>
    </div>

    <!-- Main Container: Sidebar Filter (Left) + Trips List (Right) -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      
      <!-- ==================== SIDEBAR FILTER EXACT MATCH ==================== -->
      <aside class="lg:col-span-4 bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-5 sticky top-36">
        
        <!-- Filter Header: "Lọc" + "Xóa tất cả" -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h2 class="text-lg font-black text-slate-900 tracking-tight">Lọc</h2>
          <button
            type="button"
            @click="resetAllFilters"
            class="text-xs font-bold text-slate-600 hover:text-[#2563EB] underline cursor-pointer"
          >
            Xóa tất cả
          </button>
        </div>

        <!-- Quick Filter Pills / Chips Grid -->
        <div class="flex flex-wrap gap-2 pt-1">
          <!-- Trả tận nhà (9) -->
          <button
            type="button"
            @click="toggleChip('home_dropoff')"
            :class="[
              'px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              selectedChips.includes('home_dropoff')
                ? 'bg-blue-50 border-[#2563EB] text-[#2563EB]'
                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
            ]"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Trả tận nhà (9)</span>
          </button>

          <!-- Nhà xe -->
          <button
            type="button"
            @click="toggleChip('operator')"
            :class="[
              'px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              selectedChips.includes('operator')
                ? 'bg-blue-50 border-[#2563EB] text-[#2563EB]'
                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
            ]"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-8 4h4m5 4H7a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v13a2 2 0 01-2 2z"/></svg>
            <span>Nhà xe</span>
          </button>

          <!-- Điểm lên -->
          <button
            type="button"
            @click="toggleChip('pickup_point')"
            :class="[
              'px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              selectedChips.includes('pickup_point')
                ? 'bg-blue-50 border-[#2563EB] text-[#2563EB]'
                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
            ]"
          >
            <span>Điểm lên</span>
          </button>

          <!-- Điểm xuống -->
          <button
            type="button"
            @click="toggleChip('dropoff_point')"
            :class="[
              'px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              selectedChips.includes('dropoff_point')
                ? 'bg-blue-50 border-[#2563EB] text-[#2563EB]'
                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
            ]"
          >
            <span>Điểm xuống</span>
          </button>

          <!-- Loại xe -->
          <button
            type="button"
            @click="toggleChip('bus_type')"
            :class="[
              'px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              selectedChips.includes('bus_type')
                ? 'bg-blue-50 border-[#2563EB] text-[#2563EB]'
                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
            ]"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            <span>Loại xe</span>
          </button>

          <!-- 06:00-12:00 (4) -->
          <button
            type="button"
            @click="toggleTimeSlot('morning')"
            :class="[
              'px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              selectedTimeSlots.includes('morning')
                ? 'bg-blue-50 border-[#2563EB] text-[#2563EB]'
                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
            ]"
          >
            <span>🌤️</span>
            <span>06:00-12:00 (4)</span>
          </button>

          <!-- 12:00-18:00 (6) -->
          <button
            type="button"
            @click="toggleTimeSlot('afternoon')"
            :class="[
              'px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              selectedTimeSlots.includes('afternoon')
                ? 'bg-blue-50 border-[#2563EB] text-[#2563EB]'
                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
            ]"
          >
            <span>☀️</span>
            <span>12:00-18:00 (6)</span>
          </button>

          <!-- 18:00-24:00 (5) -->
          <button
            type="button"
            @click="toggleTimeSlot('evening')"
            :class="[
              'px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              selectedTimeSlots.includes('evening')
                ? 'bg-blue-50 border-[#2563EB] text-[#2563EB]'
                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'
            ]"
          >
            <span>🌆</span>
            <span>18:00-24:00 (5)</span>
          </button>

          <!-- 00:00-06:00 -->
          <button
            type="button"
            @click="toggleTimeSlot('early')"
            :class="[
              'px-3 py-1.5 rounded-xl border text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
              selectedTimeSlots.includes('early')
                ? 'bg-blue-50 border-[#2563EB] text-[#2563EB]'
                : 'bg-slate-100 border-slate-200 text-slate-500 hover:border-slate-300'
            ]"
          >
            <span>🌙</span>
            <span>00:00-06:00</span>
          </button>
        </div>

        <!-- Accordion List Sections -->
        <div class="divide-y divide-slate-100 text-xs font-bold">
          
          <!-- Accordion 1: Thời gian khởi hành -->
          <div class="py-3">
            <button @click="openAccordion.departure = !openAccordion.departure" class="w-full flex items-center justify-between text-slate-900 hover:text-[#2563EB] cursor-pointer">
              <span>Thời gian khởi hành</span>
              <svg class="w-4 h-4 text-slate-400 transition-transform" :class="openAccordion.departure ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div v-if="openAccordion.departure" class="pt-3 space-y-2 font-medium text-slate-600">
              <label class="flex items-center justify-between cursor-pointer">
                <span>00:00 - 06:00 (Sáng sớm)</span>
                <input type="checkbox" value="early" v-model="selectedTimeSlots" class="rounded text-[#2563EB] focus:ring-[#2563EB]" />
              </label>
              <label class="flex items-center justify-between cursor-pointer">
                <span>06:01 - 12:00 (Buổi sáng)</span>
                <input type="checkbox" value="morning" v-model="selectedTimeSlots" class="rounded text-[#2563EB] focus:ring-[#2563EB]" />
              </label>
              <label class="flex items-center justify-between cursor-pointer">
                <span>12:01 - 18:00 (Buổi chiều)</span>
                <input type="checkbox" value="afternoon" v-model="selectedTimeSlots" class="rounded text-[#2563EB] focus:ring-[#2563EB]" />
              </label>
              <label class="flex items-center justify-between cursor-pointer">
                <span>18:01 - 23:59 (Buổi tối)</span>
                <input type="checkbox" value="evening" v-model="selectedTimeSlots" class="rounded text-[#2563EB] focus:ring-[#2563EB]" />
              </label>
            </div>
          </div>

          <!-- Accordion 2: Thời gian đến -->
          <div class="py-3">
            <button @click="openAccordion.arrival = !openAccordion.arrival" class="w-full flex items-center justify-between text-slate-900 hover:text-[#2563EB] cursor-pointer">
              <span>Thời gian đến</span>
              <svg class="w-4 h-4 text-slate-400 transition-transform" :class="openAccordion.arrival ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
          </div>

          <!-- Accordion 3: Loại xe -->
          <div class="py-3">
            <button @click="openAccordion.busType = !openAccordion.busType" class="w-full flex items-center justify-between text-slate-900 hover:text-[#2563EB] cursor-pointer">
              <span>Loại xe</span>
              <svg class="w-4 h-4 text-slate-400 transition-transform" :class="openAccordion.busType ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div v-if="openAccordion.busType" class="pt-3 space-y-2 font-medium text-slate-600">
              <label class="flex items-center justify-between cursor-pointer">
                <span>Xe Giường Nằm VIP</span>
                <input type="checkbox" value="sleeper" v-model="selectedTypes" class="rounded text-[#2563EB] focus:ring-[#2563EB]" />
              </label>
              <label class="flex items-center justify-between cursor-pointer">
                <span>Limousine 34 Phòng</span>
                <input type="checkbox" value="limo" v-model="selectedTypes" class="rounded text-[#2563EB] focus:ring-[#2563EB]" />
              </label>
              <label class="flex items-center justify-between cursor-pointer">
                <span>Ghế Ngồi Cao Cấp</span>
                <input type="checkbox" value="seat" v-model="selectedTypes" class="rounded text-[#2563EB] focus:ring-[#2563EB]" />
              </label>
            </div>
          </div>

          <!-- Accordion 4: Tính năng xe khách -->
          <div class="py-3">
            <button class="w-full flex items-center justify-between text-slate-900 hover:text-[#2563EB] cursor-pointer">
              <span>Tính năng xe khách</span>
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
          </div>

          <!-- Accordion 5: Nhà xe -->
          <div class="py-3">
            <button class="w-full flex items-center justify-between text-slate-900 hover:text-[#2563EB] cursor-pointer">
              <div>
                <span>Nhà xe</span>
                <div class="text-[10px] text-slate-400 font-normal">1 selected</div>
              </div>
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
          </div>

          <!-- Accordion 6: Điểm lên xe -->
          <div class="py-3">
            <button class="w-full flex items-center justify-between text-slate-900 hover:text-[#2563EB] cursor-pointer">
              <span>Điểm lên xe</span>
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
          </div>

          <!-- Accordion 7: Điểm xuống xe -->
          <div class="py-3">
            <button class="w-full flex items-center justify-between text-slate-900 hover:text-[#2563EB] cursor-pointer">
              <span>Điểm xuống xe</span>
              <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
          </div>

          <!-- Accordion 8: Tiện nghi (Open by default) -->
          <div class="py-3">
            <button @click="openAccordion.amenities = !openAccordion.amenities" class="w-full flex items-center justify-between text-slate-900 hover:text-[#2563EB] cursor-pointer">
              <span>Tiện nghi</span>
              <svg class="w-4 h-4 text-slate-400 transition-transform" :class="openAccordion.amenities ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
            </button>

            <!-- Amenities Content Box -->
            <div v-if="openAccordion.amenities" class="pt-3 space-y-3 font-medium">
              <input
                v-model="amenitySearch"
                type="text"
                placeholder="Tìm kiếm tiện nghi"
                class="w-full px-3.5 py-2 rounded-2xl bg-slate-100 text-slate-800 placeholder-slate-400 text-xs font-medium border-0 focus:ring-2 focus:ring-[#2563EB]"
              />

              <!-- Amenities Checkbox List -->
              <div class="space-y-2.5 max-h-48 overflow-y-auto pr-1">
                <label class="flex items-center justify-between text-xs cursor-pointer hover:text-[#2563EB]">
                  <div class="flex items-center gap-2">
                    <span class="text-blue-500 font-black">📶</span>
                    <span>WIFI</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-[10px] text-slate-400">15</span>
                    <input type="checkbox" v-model="amenities.wifi" class="rounded text-[#2563EB] focus:ring-[#2563EB] w-4 h-4 border-slate-300 cursor-pointer" />
                  </div>
                </label>

                <label class="flex items-center justify-between text-xs cursor-pointer hover:text-[#2563EB]">
                  <div class="flex items-center gap-2">
                    <span class="text-blue-500">🍾</span>
                    <span>Nước đóng chai</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-[10px] text-slate-400">15</span>
                    <input type="checkbox" v-model="amenities.water" class="rounded text-[#2563EB] focus:ring-[#2563EB] w-4 h-4 border-slate-300 cursor-pointer" />
                  </div>
                </label>

                <label class="flex items-center justify-between text-xs cursor-pointer hover:text-[#2563EB]">
                  <div class="flex items-center gap-2">
                    <span class="text-purple-500">🛏️</span>
                    <span>Chăn/mền</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-[10px] text-slate-400">15</span>
                    <input type="checkbox" v-model="amenities.blanket" class="rounded text-[#2563EB] focus:ring-[#2563EB] w-4 h-4 border-slate-300 cursor-pointer" />
                  </div>
                </label>

                <label class="flex items-center justify-between text-xs cursor-pointer hover:text-[#2563EB]">
                  <div class="flex items-center gap-2">
                    <span class="text-blue-600">🛌</span>
                    <span>Gối</span>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="text-[10px] text-slate-400">8</span>
                    <input type="checkbox" v-model="amenities.pillow" class="rounded text-[#2563EB] focus:ring-[#2563EB] w-4 h-4 border-slate-300 cursor-pointer" />
                  </div>
                </label>
              </div>

              <div class="pt-2 text-[11px] text-[#2563EB] font-bold hover:underline cursor-pointer">
                Xem tất cả các tiện nghi
              </div>
            </div>
          </div>

        </div>

      </aside>

      <!-- ==================== RIGHT TRIP CARDS LIST ==================== -->
      <section class="lg:col-span-8 space-y-4">
        
        <!-- Sorting Header Bar -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs flex flex-wrap items-center justify-between gap-3 text-xs">
          <span class="font-bold text-slate-600">Sắp xếp theo:</span>
          <div class="flex items-center space-x-2">
            <button
              @click="sortBy = 'time'"
              :class="['px-3 py-1.5 rounded-xl font-bold transition cursor-pointer', sortBy === 'time' ? 'bg-[#2563EB] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
            >
              Giờ chạy sớm nhất
            </button>
            <button
              @click="sortBy = 'price_asc'"
              :class="['px-3 py-1.5 rounded-xl font-bold transition cursor-pointer', sortBy === 'price_asc' ? 'bg-[#2563EB] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
            >
              Giá thấp nhất
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredTrips.length === 0" class="bg-white rounded-3xl border border-slate-200 p-12 text-center shadow-sm">
          <div class="w-16 h-16 bg-blue-50 text-[#2563EB] rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <h3 class="font-bold text-slate-800 text-base">Không tìm thấy chuyến xe nào theo bộ lọc</h3>
          <p class="text-xs text-slate-500 mt-1">Vui lòng chọn ngày khác hoặc xóa bớt bộ lọc.</p>
          <button @click="resetAllFilters" class="mt-4 px-4 py-2 bg-[#2563EB] text-white rounded-xl text-xs font-bold hover:bg-[#D84315] transition cursor-pointer">
            Xóa toàn bộ lọc
          </button>
        </div>

        <!-- Trip Cards Loop -->
        <div
          v-for="trip in filteredTrips"
          :key="trip.id"
          class="bg-white rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all p-5 sm:p-6 space-y-4"
        >
          <!-- Trip Card Header -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-slate-100">
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 rounded-2xl bg-blue-50 text-[#2563EB] border border-blue-200 flex items-center justify-center font-black">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M4 16c0 .88.39 1.67 1 2.22V20c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h8v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1c-.83 0-1.5-.67-1.5-1.5S6.67 14 7.5 14s1.5.67 1.5 1.5S8.33 17 7.5 17zm9 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm-10.5-6V6h12v5H6z"/></svg>
              </div>
              <div>
                <div class="flex items-center space-x-2">
                  <span class="font-black text-slate-900 text-base">BusHub Terminal</span>
                  <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-[#00613D] font-bold text-[11px] border border-emerald-200">
                    {{ trip.bus?.type || 'Giường Nằm VIP' }}
                  </span>
                </div>
                <div class="text-xs text-slate-500 mt-0.5">
                  Biển số: <strong class="text-slate-800">{{ trip.bus?.plate_number || '43B-012.34' }}</strong> • Đánh giá: <strong class="text-blue-600">4.9 ★</strong>
                </div>
              </div>
            </div>

            <!-- Price & Remaining Seats -->
            <div class="text-right">
              <div class="text-lg sm:text-xl font-black text-[#2563EB]">
                {{ formatPrice(trip.base_price || 150000) }}
              </div>
              <div class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-full inline-block mt-0.5">
                Còn {{ trip.available_seats !== undefined ? trip.available_seats : 18 }} chỗ trống
              </div>
            </div>
          </div>

          <!-- Journey Timeline Row -->
          <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
            <div class="sm:col-span-8 flex items-center space-x-4">
              <!-- Departure Time -->
              <div>
                <div class="text-xl font-black text-slate-900">{{ trip.departure_time?.substring(0, 5) || '08:00' }}</div>
                <div class="text-xs font-bold text-slate-600">{{ trip.route?.origin_location?.name || trip.route?.origin || 'Bến xe Đà Nẵng' }}</div>
              </div>

              <!-- Bar -->
              <div class="flex-1 flex flex-col items-center px-2">
                <span class="text-[10px] text-slate-400 font-semibold mb-1">{{ trip.route?.duration_hours || 3.5 }}h di chuyển</span>
                <div class="w-full flex items-center">
                  <div class="w-2.5 h-2.5 rounded-full bg-[#2563EB]"></div>
                  <div class="flex-1 h-0.5 bg-blue-200 border-t border-dashed border-blue-500"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-emerald-600"></div>
                </div>
                <span class="text-[9px] text-slate-400 mt-1 font-medium">Chạy trực tiếp</span>
              </div>

              <!-- Arrival Time -->
              <div class="text-right">
                <div class="text-xl font-black text-slate-900">{{ trip.arrival_time?.substring(0, 5) || '11:30' }}</div>
                <div class="text-xs font-bold text-slate-600">{{ trip.route?.destination_location?.name || trip.route?.destination || 'Bến xe Đông Hà' }}</div>
              </div>
            </div>

            <!-- CTA Button -->
            <div class="sm:col-span-4 flex justify-end">
              <Link
                :href="`/trips/${trip.id}`"
                class="w-full sm:w-auto px-6 py-3 bg-[#2563EB] hover:bg-[#D84315] text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-md shadow-blue-600/20 active:scale-95 transition-all text-center cursor-pointer"
              >
                Chọn Chỗ & Đặt Vé
              </Link>
            </div>
          </div>

          <!-- Amenities Badges Footer -->
          <div class="pt-3 border-t border-slate-100 flex flex-wrap gap-2 text-[11px] text-slate-500 font-medium">
            <span class="inline-flex items-center gap-1 bg-slate-50 px-2.5 py-1 rounded-lg">📶 WiFi 5G miễn phí</span>
            <span class="inline-flex items-center gap-1 bg-slate-50 px-2.5 py-1 rounded-lg">🍾 Nước suối & Khăn lạnh</span>
            <span class="inline-flex items-center gap-1 bg-slate-50 px-2.5 py-1 rounded-lg">⚡ Cổng sạc Type-C</span>
            <span class="inline-flex items-center gap-1 bg-slate-50 px-2.5 py-1 rounded-lg text-emerald-700 font-semibold">🛡️ Bảo hiểm hành khách 100%</span>
          </div>
        </div>

      </section>

    </main>

    <Footer />
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Navbar from '@/Components/Navbar.vue'
import Footer from '@/Components/Footer.vue'

const props = defineProps({
  trips: { type: Array, default: () => [] },
})

const searchOrigin = computed(() => new URLSearchParams(window.location.search).get('origin') || 'Đà Nẵng')
const searchDestination = computed(() => new URLSearchParams(window.location.search).get('destination') || 'Quảng Trị (Đông Hà)')
const searchDate = computed(() => new URLSearchParams(window.location.search).get('date') || 'Hôm nay')

const sortBy = ref('time')
const selectedChips = ref(['home_dropoff'])
const selectedTimeSlots = ref([])
const selectedTypes = ref([])
const amenitySearch = ref('')

const openAccordion = reactive({
  departure: true,
  arrival: false,
  busType: false,
  amenities: true,
})

const amenities = reactive({
  wifi: true,
  water: true,
  blanket: false,
  pillow: false,
})

const toggleChip = (chip) => {
  const idx = selectedChips.value.indexOf(chip)
  if (idx > -1) selectedChips.value.splice(idx, 1)
  else selectedChips.value.push(chip)
}

const toggleTimeSlot = (slot) => {
  const idx = selectedTimeSlots.value.indexOf(slot)
  if (idx > -1) selectedTimeSlots.value.splice(idx, 1)
  else selectedTimeSlots.value.push(slot)
}

const resetAllFilters = () => {
  selectedChips.value = []
  selectedTimeSlots.value = []
  selectedTypes.value = []
  sortBy.value = 'time'
}

const formatPrice = (p) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(p || 0)
}

const shiftDate = (days) => {
  const cur = new Date()
  cur.setDate(cur.getDate() + days)
  const dateStr = cur.toISOString().split('T')[0]
  router.get('/trips', {
    origin: searchOrigin.value,
    destination: searchDestination.value,
    date: dateStr,
  }, { preserveState: true })
}

const filteredTrips = computed(() => {
  let list = [...(props.trips || [])]

  if (sortBy.value === 'price_asc') {
    list.sort((a, b) => (a.base_price || 0) - (b.base_price || 0))
  }

  return list
})
</script>
