<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
    <AdminHeader 
      title="Kho Hóa Đơn & Lưu Trữ Chứng Từ PDF" 
      subtitle="Rà soát và tra cứu toàn bộ hóa đơn thanh toán vé xe, gói hội viên VIP và biên nhận hoàn tiền đã lưu về thư mục PDF" 
    />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-6">
      <!-- 4 THẺ THỐNG KÊ DOANH THU & CHỨNG TỪ -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tổng Hóa Đơn Đã Xuất</p>
          <p class="text-xl sm:text-2xl font-black text-slate-900 mt-1 font-mono">
            {{ metrics?.total_count || invoices.length }} <span class="text-xs text-slate-400 font-normal">chứng từ</span>
          </p>
          <p class="text-[10px] text-emerald-600 font-bold mt-0.5">✓ Tự động đối soát hệ thống</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tổng Thu Vé & Hội Viên</p>
          <p class="text-xl sm:text-2xl font-black text-emerald-600 mt-1 font-mono">
            {{ formatCurrency(metrics?.total_revenue || 0) }}
          </p>
          <p class="text-[10px] text-slate-500 font-medium mt-0.5">Tiền thanh toán 100% & Cọc 30%</p>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-200/90 shadow-xs">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tổng Tiền Hoàn Trả</p>
          <p class="text-xl sm:text-2xl font-black text-rose-600 mt-1 font-mono">
            {{ formatCurrency(metrics?.total_refund || 0) }}
          </p>
          <p class="text-[10px] text-rose-500 font-medium mt-0.5">Biên lai hủy vé đã duyệt</p>
        </div>

        <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-800">
          <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Thư Mục Lưu Trữ PDF</p>
          <p class="text-xs font-mono font-black text-emerald-400 mt-1 truncate" title="E:\nhaxekientruc\PDF">
            📁 E:\nhaxekientruc\PDF
          </p>
          <p class="text-[10px] text-slate-300 font-medium mt-1">✓ Chuẩn hóa đơn PDF in ấn</p>
        </div>
      </div>

      <!-- THANH TÌM KIẾM & BỘ LỌC CHỨNG TỪ -->
      <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/90 shadow-xs space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
          <!-- Ô tìm kiếm -->
          <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">🔍</span>
            <input
              v-model="searchQuery"
              @keyup.enter="handleSearch"
              type="text"
              placeholder="Tìm theo Mã chứng từ (#ve-..., #HV-...), Tên khách, SĐT..."
              class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500"
            />
          </div>

          <!-- Lọc loại hóa đơn -->
          <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
            <button
              v-for="t in [
                { key: 'ALL', label: 'Tất Cả' },
                { key: 'TICKET', label: '🎫 Vé Xe' },
                { key: 'MEMBERSHIP', label: '👑 Gói Hội Viên' },
                { key: 'REFUND', label: '🚫 Hoàn Tiền' }
              ]"
              :key="t.key"
              type="button"
              @click="changeTypeFilter(t.key)"
              :class="currentType === t.key ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
              class="px-3.5 py-2 rounded-xl transition cursor-pointer whitespace-nowrap"
            >
              {{ t.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- BẢNG DANH SÁCH HÓA ĐƠN & CHỨNG TỪ PDF -->
      <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wide">
              Danh Sách Hóa Đơn & Chứng Từ ({{ filteredInvoices.length }})
            </h3>
          </div>
          <span class="text-xs text-slate-400">Bấm "Xem" để xem trước hóa đơn hoặc "Tải PDF"</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 uppercase tracking-wider text-[10px] font-bold border-b border-slate-200">
                <th class="p-3.5 pl-5">Mã Chứng Từ</th>
                <th class="p-3.5">Loại Hóa Đơn</th>
                <th class="p-3.5">Khách Hàng</th>
                <th class="p-3.5">Dịch Vụ / Tuyến Đường</th>
                <th class="p-3.5">Số Tiền</th>
                <th class="p-3.5">Hình Thức</th>
                <th class="p-3.5">Thời Gian</th>
                <th class="p-3.5 text-center">Trạng Thái PDF</th>
                <th class="p-3.5 pr-5 text-right">Thao Tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="filteredInvoices.length === 0">
                <td colspan="9" class="p-12 text-center text-slate-400 text-xs">
                  Không tìm thấy hóa đơn hay chứng từ nào phù hợp với điều kiện tìm kiếm.
                </td>
              </tr>

              <tr
                v-for="inv in filteredInvoices"
                :key="inv.id"
                class="hover:bg-slate-50/80 transition"
              >
                <!-- Mã -->
                <td class="p-3.5 pl-5 font-mono font-black text-slate-900">
                  #{{ inv.code }}
                </td>

                <!-- Loại -->
                <td class="p-3.5">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[10px] font-black border"
                    :class="inv.badge_class || 'bg-slate-100 text-slate-700 border-slate-300'"
                  >
                    {{ inv.type_label }}
                  </span>
                </td>

                <!-- Khách hàng -->
                <td class="p-3.5">
                  <div class="font-bold text-slate-900">{{ inv.customer_name || 'Khách vãng lai' }}</div>
                  <div class="text-[11px] text-slate-500 font-mono">{{ inv.customer_phone }}</div>
                </td>

                <!-- Dịch vụ -->
                <td class="p-3.5 text-slate-700 font-medium max-w-xs truncate" :title="inv.service_name">
                  {{ inv.service_name }}
                </td>

                <!-- Số tiền -->
                <td class="p-3.5 font-mono font-black text-xs" :class="inv.type === 'REFUND' ? 'text-rose-600' : 'text-emerald-700'">
                  {{ inv.type === 'REFUND' ? '-' : '+' }}{{ formatCurrency(inv.amount) }}
                </td>

                <!-- Hình thức -->
                <td class="p-3.5 text-slate-600 font-semibold">
                  {{ inv.payment_method }}
                </td>

                <!-- Thời gian -->
                <td class="p-3.5 text-slate-500 text-[11px] font-mono">
                  {{ inv.created_at }}
                </td>

                <!-- Trạng thái PDF -->
                <td class="p-3.5 text-center">
                  <span
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                    :class="inv.has_pdf ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                  >
                    <span>{{ inv.has_pdf ? '🟢' : '⚡' }}</span>
                    <span>{{ inv.has_pdf ? 'Đã lưu PDF' : 'Tự sinh khi tải' }}</span>
                  </span>
                </td>

                <!-- Thao tác -->
                <td class="p-3.5 pr-5 text-right whitespace-nowrap">
                  <div class="inline-flex items-center gap-1.5">
                    <button
                      type="button"
                      @click="previewInvoice(inv)"
                      class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer flex items-center gap-1"
                      title="Xem trước hóa đơn"
                    >
                      <span>👁️</span>
                      <span>Xem</span>
                    </button>

                    <a
                      :href="inv.download_url"
                      target="_blank"
                      class="px-2.5 py-1.5 rounded-xl bg-red-50 hover:bg-red-100 text-red-700 font-bold text-xs border border-red-200 transition cursor-pointer flex items-center gap-1"
                      title="Tải file PDF về máy"
                    >
                      <span>📥</span>
                      <span>Tải PDF</span>
                    </a>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- MODAL XEM TRƯỚC HÓA ĐƠN TRỰC TIẾP TRÊN TRANG -->
    <div
      v-if="selectedPreview"
      class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-3 sm:p-6 animate-in fade-in"
    >
      <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-lg">📑</span>
            <h3 class="text-sm font-black text-slate-900 uppercase">
              Xem Hóa Đơn #{{ selectedPreview.code }}
            </h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="selectedPreview.badge_class">
              {{ selectedPreview.type_label }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <a
              :href="selectedPreview.download_url"
              target="_blank"
              class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 cursor-pointer"
            >
              <span>📥 Tải PDF</span>
            </a>
            <button
              @click="selectedPreview = null"
              class="w-8 h-8 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-base font-bold flex items-center justify-center cursor-pointer"
            >
              ✕
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-auto bg-slate-100 p-2 sm:p-4">
          <iframe
            :src="selectedPreview.view_url"
            class="w-full h-[65vh] rounded-2xl bg-white border border-slate-200 shadow-inner"
          ></iframe>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';

const props = defineProps({
  invoices: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  metrics: { type: Object, default: () => ({}) },
});

const searchQuery = ref(props.filters?.q || '');
const currentType = ref(props.filters?.type || 'ALL');
const selectedPreview = ref(null);

const filteredInvoices = computed(() => {
  return props.invoices.filter(inv => {
    if (currentType.value !== 'ALL' && inv.type !== currentType.value) {
      return false;
    }
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase().trim();
      const matchCode = (inv.code || '').toLowerCase().includes(q);
      const matchName = (inv.customer_name || '').toLowerCase().includes(q);
      const matchPhone = (inv.customer_phone || '').toLowerCase().includes(q);
      const matchService = (inv.service_name || '').toLowerCase().includes(q);
      if (!matchCode && !matchName && !matchPhone && !matchService) return false;
    }
    return true;
  });
});

const handleSearch = () => {
  router.get('/admin/invoices', {
    q: searchQuery.value,
    type: currentType.value,
  }, { preserveState: true, preserveScroll: true });
};

const changeTypeFilter = (t) => {
  currentType.value = t;
};

const previewInvoice = (inv) => {
  selectedPreview.value = inv;
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};
</script>
