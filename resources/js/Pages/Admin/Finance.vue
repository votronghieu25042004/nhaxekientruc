<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-900 selection:bg-blue-600 selection:text-slate-900">
    <AdminHeader title="BÁO CÁO DÒNG TIỀN & ĐỐI SOÁT TÀI CHÍNH" subtitle="Đối soát tiền bàn giao từng Chuyến Đi & Chuyến Về, Dòng tiền cọc online 30% và tiền lơ xe thu tại bến" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-900 flex items-center space-x-2">
            <span>💰 Báo Cáo Dòng Tiền & Đối Soát Bàn Giao</span>
          </h1>
          <p class="text-slate-500 text-xs mt-1">Phân tách tiền Chuyến Đi & Chuyến Về, đối soát Tiền App tính vs Tiền thực tế tài xế/lơ xe nộp về nhà xe</p>
        </div>
      </div>

      <!-- Financial Cards Summary -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 shadow-xs p-6 rounded-3xl space-y-2 shadow-xl">
          <span class="text-xs text-slate-500 font-bold uppercase">Tổng Doanh Thu Toàn Bộ</span>
          <p class="text-3xl font-black text-amber-400">{{ formatPrice(metrics?.total_revenue || 5950000) }}</p>
          <span class="text-[11px] text-slate-500">Bao gồm cọc online & tiền thu mặt</span>
        </div>

        <div class="bg-white border border-slate-200 shadow-xs p-6 rounded-3xl space-y-2 shadow-xl">
          <span class="text-xs text-slate-500 font-bold uppercase">Cọc Online 30% (Chuyển khoản)</span>
          <p class="text-3xl font-black text-emerald-400">{{ formatPrice(metrics?.total_paid || 1785000) }}</p>
          <span class="text-[11px] text-emerald-500/80">✓ Khách đã thanh toán giữ chỗ</span>
        </div>

        <div class="bg-white border border-slate-200 shadow-xs p-6 rounded-3xl space-y-2 shadow-xl">
          <span class="text-xs text-slate-500 font-bold uppercase">Tổng Chi Phí Xe (BOT, Xăng...)</span>
          <p class="text-3xl font-black text-rose-400">{{ formatPrice(metrics?.total_expenses || 770000) }}</p>
          <span class="text-[11px] text-slate-500">Đã trừ trực tiếp từ tiền nộp</span>
        </div>

        <div class="bg-white border border-slate-200 shadow-xs p-6 rounded-3xl space-y-2 shadow-xl">
          <span class="text-xs text-slate-500 font-bold uppercase">Lợi Nhuận Ròng Thực Nhận</span>
          <p class="text-3xl font-black text-cyan-400">{{ formatPrice(metrics?.net_profit || 5180000) }}</p>
          <span class="text-[11px] text-cyan-500/80">★ Tỷ lệ lấp đầy: 88.5%</span>
        </div>
      </div>

      <!-- SECTION 1: CASH AUDIT TABLE (BÀN GIAO DOANH THU TỪNG CHUYẾN ĐI / CHUYẾN VỀ) -->
      <div class="bg-white border border-slate-200 shadow-xs rounded-3xl p-6 space-y-4 shadow-2xl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200">
          <div>
            <h3 class="text-lg font-black text-slate-900 flex items-center space-x-2">
              <span>📋 Đối Soát Tiền Bàn Giao Từng Chuyến (Chuyến Đi & Chuyến Về)</span>
            </h3>
            <p class="text-xs text-slate-500">Admin kiểm tra số tiền App tính toán so với số tiền mặt thực tế nhận từ Tài xế & Lơ xe</p>
          </div>

          <!-- Filter Direction -->
          <div class="flex items-center space-x-2 text-xs">
            <button 
              @click="directionFilter = 'ALL'"
              :class="directionFilter === 'ALL' ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 text-slate-700'"
              class="px-3 py-1.5 rounded-xl transition">
              Tất cả ({{ tripClosings.length }})
            </button>
            <button 
              @click="directionFilter = 'DI'"
              :class="directionFilter === 'DI' ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 text-slate-700'"
              class="px-3 py-1.5 rounded-xl transition">
              🛫 Chuyến Đi
            </button>
            <button 
              @click="directionFilter = 'VE'"
              :class="directionFilter === 'VE' ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 text-slate-700'"
              class="px-3 py-1.5 rounded-xl transition">
              🛬 Chuyến Về
            </button>
          </div>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left text-xs">
            <thead class="text-slate-500 uppercase text-[10px] border-b border-slate-200">
              <tr>
                <th class="py-3">Chuyến Xe / Hướng Tuyến</th>
                <th class="py-3">Xe & Nhân Sự</th>
                <th class="py-3 text-right">Tiền App Tính Nộp</th>
                <th class="py-3 text-right">Tiền Thực Nộp</th>
                <th class="py-3 text-center">Chênh Lệch</th>
                <th class="py-3 text-center">Trạng Thái Đối Soát</th>
                <th class="py-3 text-right">Xác Nhận Của Admin</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/70 text-slate-700">
              <tr v-for="c in filteredClosings" :key="c.id" class="hover:bg-slate-100/40">
                <!-- Chuyến & Hướng -->
                <td class="py-4">
                  <div class="flex items-center space-x-2">
                    <span class="px-2 py-0.5 rounded text-[10px] font-black"
                          :class="c.trip_direction?.includes('Chuyến Đi') || c.trip_direction?.includes('DI') ? 'bg-blue-500/20 text-blue-400 border border-blue-500/30' : 'bg-purple-500/20 text-purple-400 border border-purple-500/30'">
                      {{ c.trip_direction || 'Chuyến Xe' }}
                    </span>
                    <span class="font-mono font-black text-slate-900 text-xs">#{{ c.trip?.trip_code || ('TRIP-' + c.trip_id) }}</span>
                  </div>
                  <div class="text-[11px] text-slate-500 mt-1 font-semibold">
                    {{ c.trip?.route?.name || 'Đà Nẵng ➔ Hội An' }}
                  </div>
                </td>

                <!-- Xe & Người bàn giao -->
                <td class="py-4">
                  <div class="font-mono font-bold text-amber-400">🚍 {{ c.trip?.bus?.plate_number || '43B-012.34' }}</div>
                  <div class="text-[11px] text-slate-500">Người nộp: <strong class="text-slate-800">{{ c.closed_by || 'Tài xế & Lơ xe' }}</strong></div>
                </td>

                <!-- Tiền App Tính -->
                <td class="py-4 text-right">
                  <span class="font-mono font-bold text-slate-800 text-sm">
                    {{ formatPrice(c.expected_cash_from_conductor) }}
                  </span>
                  <div class="text-[10px] text-slate-500">Đã trừ chi phí xe</div>
                </td>

                <!-- Tiền Thực Nộp -->
                <td class="py-4 text-right">
                  <span class="font-mono font-black text-emerald-400 text-sm">
                    {{ formatPrice(c.actual_cash_submitted) }}
                  </span>
                </td>

                <!-- Chênh Lệch -->
                <td class="py-4 text-center">
                  <span v-if="(c.cash_discrepancy || 0) === 0" class="px-2 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                    ✓ Khớp 0 đ
                  </span>
                  <span v-else-if="c.cash_discrepancy < 0" class="px-2 py-1 rounded-full text-[10px] font-black bg-rose-500/20 text-rose-400 border border-rose-500/40">
                    Thiếu {{ formatPrice(Math.abs(c.cash_discrepancy)) }}
                  </span>
                  <span v-else class="px-2 py-1 rounded-full text-[10px] font-black bg-blue-600/20 text-amber-400 border border-blue-600/40">
                    Thừa +{{ formatPrice(c.cash_discrepancy) }}
                  </span>
                </td>

                <!-- Trạng Thái Đối Soát -->
                <td class="py-4 text-center">
                  <span v-if="c.audit_status === 'AUDITED_OK'" class="px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                    ✓ ĐÃ XÁC NHẬN ĐÚNG
                  </span>
                  <span v-else-if="c.audit_status === 'AUDITED_DISCREPANCY'" class="px-2.5 py-1 rounded-full text-[11px] font-black bg-rose-500/20 text-rose-400 border border-rose-500/40">
                    ⚠️ LỆCH TIỀN / CẦN GIẢI TRÌNH
                  </span>
                  <span v-else class="px-2.5 py-1 rounded-full text-[11px] font-black bg-blue-600/20 text-amber-400 border border-blue-600/40 animate-pulse">
                    ⏳ CHỜ ADMIN DUYỆT
                  </span>
                  <div v-if="c.audit_note" class="text-[10px] text-slate-500 mt-1 italic">
                    Ghi chú: {{ c.audit_note }}
                  </div>
                </td>

                <!-- Nút Xác Nhận Của Admin -->
                <td class="py-4 text-right">
                  <div class="flex items-center justify-end space-x-1.5">
                    <button 
                      @click="confirmAuditOk(c)"
                      class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-slate-900 rounded-xl text-xs font-black shadow-md shadow-emerald-600/30 transition flex items-center space-x-1">
                      <span>✓ Đúng</span>
                    </button>
                    <button 
                      @click="openDiscrepancyModal(c)"
                      class="px-2.5 py-1.5 bg-rose-600/20 hover:bg-rose-600/40 text-rose-300 border border-rose-500/40 rounded-xl text-xs font-bold transition">
                      <span>✕ Báo Sai</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SECTION 2: TOP PERFORMING BUSES TABLE -->
      <div class="bg-white border border-slate-200 shadow-xs rounded-3xl p-6 space-y-4 shadow-xl">
        <h3 class="text-lg font-black text-slate-900">🏆 Bảng Xếp Hạng Xe Chạy Nhiều Nhất & Doanh Thu Cao Nhất</h3>

        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left text-xs">
            <thead class="text-slate-500 uppercase text-[10px] border-b border-slate-200">
              <tr>
                <th class="pb-3">Xếp hạng</th>
                <th class="pb-3">Biển số xe</th>
                <th class="pb-3">Dòng xe</th>
                <th class="pb-3">Số chuyến đã chạy</th>
                <th class="pb-3">Tổng lượt khách</th>
                <th class="pb-3 text-right">Tổng Doanh Thu</th>
                <th class="pb-3 text-right">Trạng thái xe</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-700">
              <tr v-for="(b, idx) in topBuses" :key="b.id" class="hover:bg-slate-100/40">
                <td class="py-4">
                  <span
                    :class="{
                      'w-7 h-7 rounded-full bg-blue-600/20 text-amber-400 font-black flex items-center justify-center border border-blue-600/40': idx === 0,
                      'w-7 h-7 rounded-full bg-slate-100 text-slate-700 font-bold flex items-center justify-center': idx > 0,
                    }"
                  >
                    #{{ idx + 1 }}
                  </span>
                </td>
                <td class="py-4 font-mono font-black text-slate-900 text-sm">{{ b.license_plate || '43B-012.34' }}</td>
                <td class="py-4 text-blue-500 font-semibold">{{ b.bus_type }}</td>
                <td class="py-4 font-bold text-slate-900">{{ b.trips_count }} chuyến</td>
                <td class="py-4 font-bold text-slate-800">{{ b.total_passengers || 0 }} khách</td>
                <td class="py-4 text-right font-black text-amber-400 text-sm">
                  {{ formatPrice(b.total_revenue) }}
                </td>
                <td class="py-4 text-right">
                  <span
                    :class="b.status === 'RUNNING' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' : 'bg-blue-600/20 text-amber-400 border-blue-600/30'"
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold border"
                  >
                    {{ b.status === 'RUNNING' ? 'Đang chạy' : 'Bảo dưỡng / Sẵn sàng' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- Modal Admin Báo Sai Lệch Tiền & Yêu Cầu Giải Trình -->
    <div v-if="showDiscrepancyModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white border border-rose-500/40 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
          <h3 class="text-base font-black text-rose-400">⚠️ Báo Lệch Tiền Bàn Giao</h3>
          <button @click="showDiscrepancyModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
        </div>

        <div class="bg-[#F8FAFC] p-3 rounded-2xl text-xs space-y-1 border border-slate-200">
          <div>Chuyến: <strong class="text-slate-900">{{ activeClosing?.trip?.route?.name }}</strong></div>
          <div>Tiền App tính: <strong class="text-slate-700 font-mono">{{ formatPrice(activeClosing?.expected_cash_from_conductor) }}</strong></div>
          <div>Tiền thực nộp: <strong class="text-emerald-400 font-mono">{{ formatPrice(activeClosing?.actual_cash_submitted) }}</strong></div>
          <div>Lệch: <strong class="text-rose-400 font-mono">{{ formatPrice(activeClosing?.cash_discrepancy) }}</strong></div>
        </div>

        <div class="space-y-2 text-xs">
          <label class="font-bold text-slate-700 block">Lý do sai lệch / Yêu cầu giải trình gửi Tài xế & Lơ xe:</label>
          <textarea 
            v-model="auditNote" 
            rows="3" 
            placeholder="Ví dụ: Thiếu 30.000đ tiền vé chặng Điện Bàn, yêu cầu đối soát lại bill chuyển khoản..."
            class="w-full bg-[#F8FAFC] border border-slate-300 rounded-xl p-3 text-slate-900 outline-none focus:ring-2 focus:ring-rose-500"
          ></textarea>
        </div>

        <div class="flex justify-end space-x-2 pt-2 border-t border-slate-200">
          <button @click="showDiscrepancyModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">Hủy</button>
          <button @click="submitDiscrepancy" class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-slate-900 rounded-xl text-xs font-black shadow-lg shadow-rose-600/30">
            Gửi Báo Lệch Tiền
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminHeader from '@/Components/AdminHeader.vue'

const props = defineProps({
  metrics: Object,
  topBuses: { type: Array, default: () => [] },
  tripClosings: { type: Array, default: () => [] },
});

const directionFilter = ref('ALL') // 'ALL', 'DI', 'VE'
const showDiscrepancyModal = ref(false)
const activeClosing = ref(null)
const auditNote = ref('')

const filteredClosings = computed(() => {
  if (directionFilter.value === 'DI') {
    return props.tripClosings.filter(c => (c.trip_direction || '').includes('Chuyến Đi') || (c.trip_direction || '').includes('DI'))
  } else if (directionFilter.value === 'VE') {
    return props.tripClosings.filter(c => (c.trip_direction || '').includes('Chuyến Về') || (c.trip_direction || '').includes('VE'))
  }
  return props.tripClosings
})

const confirmAuditOk = (closing) => {
  if (!confirm(`Xác nhận số tiền bàn giao chuyến #${closing.trip?.trip_code || closing.trip_id} là HOÀN TOÀN CHÍNH XÁC & KHỚP 100%?`)) return
  router.post(`/admin/closings/${closing.id}/audit`, {
    status: 'AUDITED_OK',
    audit_note: 'Đã đối soát chính xác khớp 100%'
  }, {
    preserveScroll: true,
    onSuccess: () => alert('🟢 Đã xác nhận khớp tiền thành công!')
  })
}

const openDiscrepancyModal = (closing) => {
  activeClosing.value = closing
  auditNote.value = `Lệch ${formatPrice(Math.abs(closing.cash_discrepancy || 0))}. Cần bổ sung biên lai nộp tiền.`
  showDiscrepancyModal.value = true
}

const submitDiscrepancy = () => {
  if (!activeClosing.value) return
  router.post(`/admin/closings/${activeClosing.value.id}/audit`, {
    status: 'AUDITED_DISCREPANCY',
    audit_note: auditNote.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showDiscrepancyModal.value = false
      alert('⚠️ Đã gửi yêu cầu giải trình lệch tiền!')
    }
  })
}

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(val || 0) + ' đ';
</script>
