<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-900">
    <AdminHeader title="SƠ ĐỒ & BÁO CÁO CHUYẾN XE" subtitle="Chi tiết danh sách hành khách & Trạng thái thanh toán theo ghế" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-8">
      <!-- Trip Header Summary -->
      <div class="bg-white border border-slate-200 shadow-xs p-6 rounded-3xl grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <span class="text-xs text-slate-500 block">Hành trình:</span>
          <h2 class="text-xl font-black text-slate-900">{{ trip.route_name }}</h2>
          <span class="text-xs font-bold text-blue-500">{{ trip.departure_time }}</span>
        </div>
        <div>
          <span class="text-xs text-slate-500 block">Xe phụ trách:</span>
          <h3 class="text-lg font-black text-slate-900 font-mono">{{ trip.bus_plate }}</h3>
          <span class="text-xs text-slate-500">{{ trip.bus_type }} ({{ trip.total_seats }} chỗ)</span>
        </div>
        <div>
          <span class="text-xs text-slate-500 block">Tài xế lái xe:</span>
          <h3 class="text-lg font-bold text-slate-900">{{ trip.driver_name }}</h3>
          <span class="text-xs text-slate-500">📞 {{ trip.driver_phone || 'Chưa cập nhật' }}</span>
        </div>
        <div class="text-right">
          <span class="text-xs text-slate-500 block">Tổng thu chuyến xe:</span>
          <span class="text-2xl font-black text-amber-400">{{ formatPrice(financialSummary.total_revenue) }}đ</span>
          <span class="text-xs text-green-400 font-bold block mt-0.5">Đã có {{ financialSummary.booked_seats }}/{{ trip.total_seats }} khách đặt</span>
        </div>
      </div>

      <!-- Financial Metrics for this Trip -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white border border-slate-200 shadow-xs p-5 rounded-2xl">
          <span class="text-xs text-slate-500 block">Tiền cọc Online đã thu (30%):</span>
          <span class="text-xl font-black text-green-400">{{ formatPrice(financialSummary.deposit_collected_30) }}đ</span>
        </div>
        <div class="bg-white border border-slate-200 shadow-xs p-5 rounded-2xl">
          <span class="text-xs text-slate-500 block">Lơ xe đã thu tiền mặt tại bến (70%):</span>
          <span class="text-xl font-black text-emerald-400">{{ formatPrice(financialSummary.cash_collected_70) }}đ</span>
        </div>
        <div class="bg-white border border-slate-200 shadow-xs p-5 rounded-2xl">
          <span class="text-xs text-slate-500 block">Tiền còn chờ thu lúc khách lên xe (70%):</span>
          <span class="text-xl font-black text-amber-400">{{ formatPrice(financialSummary.pending_cash_70) }}đ</span>
        </div>
      </div>

      <!-- Passenger List Table -->
      <div class="bg-white border border-slate-200 shadow-xs rounded-3xl p-6 overflow-hidden space-y-4">
        <h3 class="text-lg font-black text-slate-900">Danh sách từng hành khách & Vị trí ghế ngồi</h3>

        <div class="overflow-x-auto custom-scrollbar">
          <table class="w-full text-left text-xs">
            <thead class="text-slate-500 uppercase border-b border-slate-200">
              <tr>
                <th class="pb-3">Mã vé</th>
                <th class="pb-3">Vị trí ghế</th>
                <th class="pb-3">Họ và tên khách</th>
                <th class="pb-3">Số điện thoại</th>
                <th class="pb-3">Tổng vé</th>
                <th class="pb-3">Đã cọc 30%</th>
                <th class="pb-3">Cần thu 70%</th>
                <th class="pb-3 text-right">Trạng thái</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-700">
              <tr v-for="b in bookings" :key="b.id" class="hover:bg-slate-100/40">
                <td class="py-3.5 font-mono font-bold text-blue-500">{{ b.booking_code }}</td>
                <td class="py-3.5 font-black text-amber-400 text-sm">{{ b.seats.join(', ') }}</td>
                <td class="py-3.5 font-bold text-slate-900 text-sm">{{ b.customer_name }}</td>
                <td class="py-3.5">{{ b.customer_phone }}</td>
                <td class="py-3.5 font-bold text-slate-900">{{ formatPrice(b.total_amount) }}đ</td>
                <td class="py-3.5 text-green-400 font-bold">{{ formatPrice(b.deposit_amount) }}đ</td>
                <td class="py-3.5 text-amber-400 font-bold">{{ formatPrice(b.remaining_amount) }}đ</td>
                <td class="py-3.5 text-right">
                  <span
                    :class="b.checkin_status === 'boarded' ? 'bg-green-500/20 text-green-400 border-green-500/30' : 'bg-blue-600/20 text-amber-400 border-blue-600/30'"
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold border inline-block"
                  >
                    {{ b.checkin_status === 'boarded' ? '✓ Đã lên xe' : 'Chờ soát vé' }}
                  </span>
                </td>
              </tr>
              <tr v-if="bookings.length === 0">
                <td colspan="8" class="text-center py-8 text-slate-500 italic">Chuyến xe này chưa có khách đặt vé.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
defineProps({
  trip: Object,
  financialSummary: Object,
  bookings: Array,
});

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(val || 0);
</script>
