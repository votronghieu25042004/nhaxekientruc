<template>
  <div class="min-h-screen bg-slate-50 flex flex-col text-slate-800">
    <AdminHeader title="QUẢN LÝ VẬT TƯ & CHI PHÍ ĐỘI XE (FLEET & INVENTORY)" subtitle="Theo dõi lịch sử sửa chữa phụ tùng, kiểm soát định mức tiêu hao nhiên liệu (L/100km) và chi phí cố định (Bảo hiểm, BOT, Đăng kiểm - Chỗ để xe hoàn toàn miễn phí 0đ)" />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-6">
      <!-- Header & Sub-tabs Switcher -->
      <div class="bg-white p-4.5 rounded-2xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 uppercase">
              FLEET & INVENTORY MODULE
            </span>
            <h1 class="text-lg font-black text-slate-900">Quản Lý Vật Tư, Nhiên Liệu & Chi Phí Xe</h1>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">Theo dõi lịch sử thay phụ tùng xe, giám sát tiêu hao lít dầu/100km chống thất thoát và quản lý hạn giấy tờ xe</p>
        </div>

        <!-- 3 Nút Switch Chuyển Phân Hệ -->
        <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold text-slate-600">
          <button
            @click="activeSubTab = 'maintenance'"
            class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5"
            :class="activeSubTab === 'maintenance' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/60 font-black' : 'hover:bg-slate-200/60 text-slate-600'"
          >
            <span>🔧</span>
            <span>Sửa Chữa & Phụ Tùng</span>
          </button>

          <button
            @click="activeSubTab = 'fuel'"
            class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5"
            :class="activeSubTab === 'fuel' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/60 font-black' : 'hover:bg-slate-200/60 text-slate-600'"
          >
            <span>⛽</span>
            <span>Định Mức Nhiên Liệu</span>
          </button>

          <button
            @click="activeSubTab = 'fixed'"
            class="px-3 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5"
            :class="activeSubTab === 'fixed' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/60 font-black' : 'hover:bg-slate-200/60 text-slate-600'"
          >
            <span>🛡️</span>
            <span>Chi Phí Cố Định & BOT</span>
          </button>
        </div>
      </div>

      <!-- Flash Message -->
      <div v-if="$page.props.flash?.success" class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-bold flex items-center gap-2">
        <span>✅</span>
        <span>{{ $page.props.flash.success }}</span>
      </div>

      <!-- ==================== SUBTAB 1: LỊCH SỬ SỬA CHỮA & THAY PHỤ TÙNG ==================== -->
      <div v-if="activeSubTab === 'maintenance'" class="space-y-4">
        <!-- 3 Thẻ Thống Kê Nhanh -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="p-4 bg-white rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
              <span class="text-[11px] font-bold text-slate-400 uppercase">Tổng Chi Phí Bảo Dưỡng</span>
              <div class="text-xl font-black text-slate-900 font-mono mt-0.5">{{ formatCurrency(totalMaintenanceCost) }}</div>
              <div class="text-[11px] text-slate-500 mt-0.5">{{ maintenanceRecords.length }} lần sửa chữa trong tháng</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-lg">🔧</div>
          </div>

          <div class="p-4 bg-white rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
              <span class="text-[11px] font-bold text-slate-400 uppercase">Lốp & Dầu Máy Đã Thay</span>
              <div class="text-xl font-black text-emerald-700 mt-0.5">8 Lốp Michelin • 3 Đợt Dầu</div>
              <div class="text-[11px] text-emerald-600 mt-0.5">✓ Kiểm tra định kỳ an toàn</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-lg">🛞</div>
          </div>

          <div class="p-4 bg-white rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
              <span class="text-[11px] font-bold text-slate-400 uppercase">Tỷ Lệ Xe Đạt Chuẩn</span>
              <div class="text-xl font-black text-blue-700 mt-0.5">9 / 10 Xe (90%)</div>
              <div class="text-[11px] text-slate-500 mt-0.5">1 xe đang hoàn tất bảo dưỡng</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-lg">🚌</div>
          </div>
        </div>

        <!-- Bảng danh sách sửa chữa & thay thế phụ tùng -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
              <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>🔧 Nhật Ký Sửa Chữa & Thay Thế Phụ Tùng Phương Tiện</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Ví dụ: Xe 43B-067.89 vừa thay 4 lốp Michelin, thay nhớt; Xe 43B-012.34 bảo dưỡng phanh ABS...
              </p>
            </div>

            <button
              @click="showAddMaintenanceModal = true"
              class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            >
              <span>➕</span>
              <span>Ghi Nhận Bảo Dưỡng / Phụ Tùng</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200">
                <tr>
                  <th class="px-4 py-3">Biển Số Xe</th>
                  <th class="px-4 py-3">Hạng Mục Sửa Chữa / Phụ Tùng</th>
                  <th class="px-4 py-3">Gara / Đơn Vị Thực Hiện</th>
                  <th class="px-4 py-3 text-center">Số ODO (km)</th>
                  <th class="px-4 py-3 text-right">Chi Phí</th>
                  <th class="px-4 py-3 text-center">Ngày Thực Hiện</th>
                  <th class="px-4 py-3 text-center">Kỳ Kế Tiếp</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="rec in maintenanceRecords" :key="rec.id" class="hover:bg-slate-50/60 transition">
                  <td class="px-4 py-3.5">
                    <span class="font-mono font-black text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                      {{ rec.plate_number }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5">
                    <div class="font-bold text-slate-900">{{ rec.service_title }}</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">{{ rec.notes }}</div>
                  </td>
                  <td class="px-4 py-3.5 text-slate-700 font-medium">
                    {{ rec.garage_name }}
                  </td>
                  <td class="px-4 py-3.5 text-center font-mono font-bold text-slate-800">
                    {{ Number(rec.odo_km).toLocaleString('vi-VN') }} km
                  </td>
                  <td class="px-4 py-3.5 text-right font-mono font-black text-red-600">
                    {{ formatCurrency(rec.cost) }}
                  </td>
                  <td class="px-4 py-3.5 text-center text-slate-600 font-medium">
                    {{ rec.service_date }}
                  </td>
                  <td class="px-4 py-3.5 text-center font-mono text-[11px] text-slate-500">
                    {{ rec.next_service_km ? `${Number(rec.next_service_km).toLocaleString('vi-VN')} km` : 'Chưa đặt' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ==================== SUBTAB 2: ĐỊNH MỨC TIÊU HAO NHIÊN LIỆU (L/100KM) ==================== -->
      <div v-if="activeSubTab === 'fuel'" class="space-y-4">
        <!-- Thống Kê Định Mức Tiêu Hao Nhiên Liệu -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="p-4 bg-white rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
              <span class="text-[11px] font-bold text-slate-400 uppercase">Định Mức Tiêu Chuẩn Nhà Xe</span>
              <div class="text-xl font-black text-slate-900 font-mono mt-0.5">22.0 Lít / 100km</div>
              <div class="text-[11px] text-slate-500 mt-0.5">Quy định chung xe Giường Nằm 34-36 chỗ</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-lg">📏</div>
          </div>

          <div class="p-4 bg-white rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
              <span class="text-[11px] font-bold text-slate-400 uppercase">Tiêu Hao Thực Tế Trung Bình</span>
              <div class="text-xl font-black text-emerald-700 font-mono mt-0.5">21.8 Lít / 100km</div>
              <div class="text-[11px] text-emerald-600 mt-0.5">✓ Tiết kiệm 0.2L/100km trên cao tốc</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-lg">⛽</div>
          </div>

          <div class="p-4 bg-white rounded-2xl border border-slate-200 flex items-center justify-between">
            <div>
              <span class="text-[11px] font-bold text-slate-400 uppercase">Kiểm Soát Chống Thất Thoát</span>
              <div class="text-xl font-black text-amber-700 mt-0.5">1 Chuyến Vượt Định Mức</div>
              <div class="text-[11px] text-amber-700/80 mt-0.5">Đã đối soát GPS xác nhận kẹt xe</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-lg">⚠️</div>
          </div>
        </div>

        <!-- Bảng Theo Dõi Nhiên Liệu Từng Chuyến -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
              <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>⛽ Bảng Theo Dõi Cấp Dầu & Suất Tiêu Hao (Lít Dầu / 100km)</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                So sánh lượng dầu đổ thực tế so với định mức 22L/100km để cảnh báo rò rỉ, bòn rút dầu hoặc kỹ năng lái xe
              </p>
            </div>

            <button
              @click="showAddFuelModal = true"
              class="px-3.5 py-2 bg-[#dc2626] hover:bg-red-700 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap shadow-xs"
            >
              <span>➕</span>
              <span>Ghi Nhận Đổ Dầu</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200">
                <tr>
                  <th class="px-4 py-3">Biển Số Xe</th>
                  <th class="px-4 py-3">Mã Chuyến & Bác Tài</th>
                  <th class="px-4 py-3 text-center">Quãng Đường (km)</th>
                  <th class="px-4 py-3 text-center">Lít Dầu Đổ</th>
                  <th class="px-4 py-3 text-center">Tiêu Hao Thực Tế</th>
                  <th class="px-4 py-3 text-center">Định Mức Chuẩn</th>
                  <th class="px-4 py-3 text-center">Chênh Lệch (Lít)</th>
                  <th class="px-4 py-3">Đánh Giá & Ghi Chú</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="f in fuelLogs" :key="f.id" class="hover:bg-slate-50/60 transition">
                  <td class="px-4 py-3.5">
                    <span class="font-mono font-black text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                      {{ f.plate_number }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5">
                    <div class="font-bold text-slate-900">{{ f.driver_name }}</div>
                    <div class="text-[11px] text-slate-400 font-mono">Chuyến #{{ f.trip_code }} • {{ f.log_date }}</div>
                  </td>
                  <td class="px-4 py-3.5 text-center font-mono font-bold text-slate-800">
                    {{ f.distance_km }} km
                  </td>
                  <td class="px-4 py-3.5 text-center font-mono font-bold text-blue-700">
                    {{ f.liters_filled }} L
                  </td>
                  <td class="px-4 py-3.5 text-center font-mono font-black text-sm" :class="f.fuel_rate_actual > 22.5 ? 'text-red-600' : 'text-emerald-700'">
                    {{ f.fuel_rate_actual }} L/100km
                  </td>
                  <td class="px-4 py-3.5 text-center font-mono text-slate-500">
                    {{ f.fuel_rate_standard }} L/100km
                  </td>
                  <td class="px-4 py-3.5 text-center font-mono font-bold">
                    <span 
                      v-if="f.discrepancy_liters > 0" 
                      class="px-2 py-0.5 rounded text-[11px] bg-red-50 text-red-700 border border-red-200 font-black"
                    >
                      +{{ f.discrepancy_liters }} L
                    </span>
                    <span 
                      v-else-if="f.discrepancy_liters < 0" 
                      class="px-2 py-0.5 rounded text-[11px] bg-emerald-50 text-emerald-700 border border-emerald-200 font-black"
                    >
                      {{ f.discrepancy_liters }} L
                    </span>
                    <span v-else class="text-slate-400 font-medium">0.0 L (Khớp)</span>
                  </td>
                  <td class="px-4 py-3.5 text-xs text-slate-600">
                    {{ f.notes }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ==================== SUBTAB 3: CHI PHÍ CỐ ĐỊNH (BẾN BÃI, BẢO HIỂM, BOT) ==================== -->
      <div v-if="activeSubTab === 'fixed'" class="space-y-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
              <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>🛡️ Quản Lý Các Khoản Chi Cố Định & Hạn Giấy Tờ Xe</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Phí bảo hiểm TNDS/thân vỏ, phí đường bộ BOT (ePass/VETC) và hạn đăng kiểm định kỳ (Chỗ để & bãi đỗ xe riêng của nhaxekientruc hoàn toàn miễn phí 0đ)
              </p>
            </div>

            <button
              @click="showAddFixedModal = true"
              class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            >
              <span>➕</span>
              <span>Thêm Khoản Chi Cố Định</span>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="item in fixedExpenses"
              :key="item.id"
              class="p-4 rounded-2xl border transition-all flex flex-col justify-between space-y-3 bg-white border-slate-200"
            >
              <div>
                <div class="flex items-center justify-between">
                  <span class="font-mono font-black text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                    🚍 {{ item.plate_number }}
                  </span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="item.status === 'EXPIRING_SOON' ? 'bg-amber-50 text-amber-700 border border-amber-200 animate-pulse' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                  >
                    {{ item.status === 'EXPIRING_SOON' ? '⚠️ Sắp Đến Hạn Đóng' : '✓ Đang Hiệu Lực' }}
                  </span>
                </div>

                <h3 class="text-xs font-bold text-slate-900 mt-2">{{ item.title }}</h3>
                <div class="text-[11px] text-slate-500 mt-0.5">Đơn vị: <strong>{{ item.provider_name }}</strong> • Kỳ: {{ item.billing_period }}</div>
              </div>

              <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                <div>
                  <span class="text-slate-400">Số tiền: </span>
                  <strong class="font-mono font-black text-red-600 text-sm">{{ formatCurrency(item.amount) }}</strong>
                </div>
                <div class="text-right">
                  <span class="text-slate-400 text-[11px]">Hạn hiệu lực: </span>
                  <strong class="font-medium text-slate-800">{{ item.expiry_date }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- MODAL: GHI NHẬN SỬA CHỮA / PHỤ TÙNG -->
    <div v-if="showAddMaintenanceModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-5 space-y-4 shadow-xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="font-black text-slate-900 text-sm">🔧 Ghi Nhận Lịch Sử Bảo Dưỡng / Phụ Tùng</h3>
          <button @click="showAddMaintenanceModal = false" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>

        <form @submit.prevent="submitMaintenance" class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Biển Số Xe</label>
              <select v-model="maintForm.plate_number" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-slate-900">
                <option v-for="b in buses" :key="b.id" :value="b.license_plate">
                  {{ b.license_plate }} ({{ b.bus_type }})
                </option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Loại Hạng Mục</label>
              <select v-model="maintForm.service_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900">
                <option value="TIRE">🛞 Thay Lốp Michelin</option>
                <option value="OIL">🛢️ Thay Nhớt / Lọc Dầu</option>
                <option value="BRAKE">🛑 Hệ Thống Phanh ABS</option>
                <option value="AIR_CON">❄️ Điều Hòa / Lốc Lạnh</option>
                <option value="OVERHAUL">⚙️ Đại Tu Động Cơ</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nội Dung Sửa Chữa Cụ Thể</label>
            <input 
              type="text" 
              v-model="maintForm.service_title" 
              required 
              placeholder="Ví dụ: Xe 43B-067.89 vừa thay 4 lốp Michelin, thay nhớt Castrol"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-medium"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Chi Phí (VNĐ)</label>
              <input 
                type="number" 
                v-model.number="maintForm.cost" 
                required 
                placeholder="32500000"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-red-600"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Gara / Xưởng Thực Hiện</label>
              <input 
                type="text" 
                v-model="maintForm.garage_name" 
                required 
                placeholder="Trung tâm lốp Michelin Đà Nẵng"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Số ODO Hiện Tại (km)</label>
              <input 
                type="number" 
                v-model.number="maintForm.odo_km" 
                required 
                placeholder="145200"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono text-slate-900"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Ngày Thực Hiện</label>
              <input type="date" v-model="maintForm.service_date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Ghi Chú Kỹ Thuật</label>
            <input 
              type="text" 
              v-model="maintForm.notes" 
              placeholder="Áp suất lốp 9.0 bar, cân mâm bấm chì chuẩn..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900"
            />
          </div>

          <div class="pt-2 flex items-center gap-2">
            <button
              type="submit"
              class="flex-1 py-2.5 bg-slate-900 hover:bg-black text-white font-bold rounded-xl cursor-pointer"
            >
              Lưu Bảo Dưỡng
            </button>
            <button
              type="button"
              @click="showAddMaintenanceModal = false"
              class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
            >
              Hủy
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: GHI NHẬN ĐỔ DẦU -->
    <div v-if="showAddFuelModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-5 space-y-4 shadow-xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="font-black text-slate-900 text-sm">⛽ Ghi Nhận Đổ Dầu & Kiểm Soát Định Mức</h3>
          <button @click="showAddFuelModal = false" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>

        <form @submit.prevent="submitFuel" class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Biển Số Xe</label>
              <select v-model="fuelForm.plate_number" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-slate-900">
                <option v-for="b in buses" :key="b.id" :value="b.license_plate">
                  {{ b.license_plate }}
                </option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Mã Chuyến</label>
              <input type="text" v-model="fuelForm.trip_code" required placeholder="BH-0039" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono text-slate-900" />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Tài Xế Nhận Dầu</label>
            <input type="text" v-model="fuelForm.driver_name" required placeholder="Nguyễn Văn Hùng" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Quãng Đường Chạy (km)</label>
              <input type="number" v-model.number="fuelForm.distance_km" required placeholder="310" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono text-slate-900" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Số Lít Dầu Thực Đổ (L)</label>
              <input type="number" step="0.1" v-model.number="fuelForm.liters_filled" required placeholder="68.2" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-blue-700" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Định Mức Tiêu Chuẩn</label>
              <input type="number" step="0.1" v-model.number="fuelForm.fuel_rate_standard" class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3 py-2 font-mono text-slate-600" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Ngày Đổ Dầu</label>
              <input type="date" v-model="fuelForm.log_date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Ghi Chú Đổ Dầu</label>
            <input type="text" v-model="fuelForm.notes" placeholder="Cây dầu Petrolimex số 12..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
          </div>

          <div class="pt-2 flex items-center gap-2">
            <button
              type="submit"
              class="flex-1 py-2.5 bg-[#dc2626] hover:bg-red-700 text-white font-bold rounded-xl cursor-pointer"
            >
              Lưu Dữ Liệu Dầu
            </button>
            <button
              type="button"
              @click="showAddFuelModal = false"
              class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
            >
              Hủy
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: THÊM CHI PHÍ CỐ ĐỊNH -->
    <div v-if="showAddFixedModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-5 space-y-4 shadow-xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="font-black text-slate-900 text-sm">🛡️ Thêm Khoản Chi Cố Định & Giấy Tờ Xe</h3>
          <button @click="showAddFixedModal = false" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>

        <form @submit.prevent="submitFixed" class="space-y-3 text-xs">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Biển Số Xe</label>
              <select v-model="fixedForm.plate_number" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-slate-900">
                <option v-for="b in buses" :key="b.id" :value="b.license_plate">
                  {{ b.license_plate }}
                </option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Phân Loại Chi Phí</label>
              <select v-model="fixedForm.expense_category" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900">
                <option value="INSURANCE">🛡️ Phí Bảo Hiểm Thân Vỏ / TNDS</option>
                <option value="BOT_TOLL">🛣️ Phí Đường Bộ BOT (VETC / ePass)</option>
                <option value="REGISTRATION">📋 Phí Đăng Kiểm Định Kỳ</option>
              </select>
            </div>
          </div>

          <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-900 text-xs">
            🏢 <strong>Chỗ để xe nội bộ:</strong> Toàn bộ xe của nhaxekientruc đều được đỗ miễn phí <strong>100% (0đ)</strong> tại bãi xe trung tâm, không cần ghi nhận chi phí bến bãi.
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Tên Khoản Chi Phí</label>
            <input 
              type="text" 
              v-model="fixedForm.title" 
              required 
              placeholder="Ví dụ: Phí bến đỗ & xuất bến cố định tháng 10/2026"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-medium"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Số Tiền (VNĐ)</label>
              <input 
                type="number" 
                v-model.number="fixedForm.amount" 
                required 
                placeholder="4500000"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-red-600"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Kỳ Thanh Toán</label>
              <input 
                type="text" 
                v-model="fixedForm.billing_period" 
                required 
                placeholder="Tháng 10/2026"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Đơn Vị Thu Phí</label>
              <input type="text" v-model="fixedForm.provider_name" required placeholder="Bến xe Trung tâm Đà Nẵng" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Hạn Hiệu Lực / Hết Hạn</label>
              <input type="date" v-model="fixedForm.expiry_date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
            </div>
          </div>

          <div class="pt-2 flex items-center gap-2">
            <button
              type="submit"
              class="flex-1 py-2.5 bg-slate-900 hover:bg-black text-white font-bold rounded-xl cursor-pointer"
            >
              Lưu Khoản Chi
            </button>
            <button
              type="button"
              @click="showAddFixedModal = false"
              class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
            >
              Hủy
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';

const props = defineProps({
  maintenanceRecords: Array,
  fuelLogs: Array,
  fixedExpenses: Array,
  buses: Array,
});

const activeSubTab = ref('maintenance'); // 'maintenance', 'fuel', 'fixed'

const showAddMaintenanceModal = ref(false);
const showAddFuelModal = ref(false);
const showAddFixedModal = ref(false);

const maintForm = reactive({
  plate_number: props.buses?.[0]?.license_plate || '43B-067.89',
  service_title: '',
  service_type: 'TIRE',
  cost: 32500000,
  garage_name: '',
  odo_km: 145000,
  next_service_km: 185000,
  service_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const fuelForm = reactive({
  plate_number: props.buses?.[0]?.license_plate || '43B-067.89',
  driver_name: '',
  trip_code: 'BH-0039',
  distance_km: 310,
  liters_filled: 68.2,
  fuel_rate_standard: 22.0,
  log_date: new Date().toISOString().split('T')[0],
  notes: '',
});

const fixedForm = reactive({
  plate_number: props.buses?.[0]?.license_plate || '43B-012.34',
  expense_category: 'INSURANCE',
  title: 'Bảo hiểm trách nhiệm dân sự & thân vỏ',
  provider_name: 'Bảo hiểm PVI',
  amount: 15000000,
  billing_period: 'Năm 2026 - 2027',
  expiry_date: '',
});

const totalMaintenanceCost = computed(() => {
  return props.maintenanceRecords.reduce((sum, r) => sum + (r.cost || 0), 0);
});

const submitMaintenance = () => {
  router.post('/admin/fleet-inventory/maintenance', maintForm, {
    onSuccess: () => {
      showAddMaintenanceModal.value = false;
      maintForm.service_title = '';
      maintForm.notes = '';
    }
  });
};

const submitFuel = () => {
  router.post('/admin/fleet-inventory/fuel', fuelForm, {
    onSuccess: () => {
      showAddFuelModal.value = false;
      fuelForm.driver_name = '';
      fuelForm.notes = '';
    }
  });
};

const submitFixed = () => {
  router.post('/admin/fleet-inventory/fixed-expense', fixedForm, {
    onSuccess: () => {
      showAddFixedModal.value = false;
      fixedForm.title = '';
      fixedForm.provider_name = '';
    }
  });
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};
</script>
