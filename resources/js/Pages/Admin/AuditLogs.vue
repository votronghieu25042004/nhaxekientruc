<template>
  <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-sans">
    <!-- Header Admin Đồng Bộ Hệ Thống -->
    <AdminHeader 
      title="NHẬT KÝ HỆ THỐNG & AUDIT LOGS" 
      subtitle="Bảng ghi vết hoạt động bất biến (Immutable Audit Trail) phục vụ kiểm toán vận hành & đối soát tài chính" 
    />

    <main class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 space-y-6">
      <!-- Banner Giới Thiệu Chức Năng Audit Logs -->
      <div class="bg-linear-to-r from-slate-900 via-slate-800 to-indigo-950 rounded-3xl p-6 text-white shadow-xl relative overflow-hidden border border-slate-700/50">
        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-red-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="space-y-2 max-w-3xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-500/20 border border-red-500/30 text-red-300 text-xs font-bold uppercase tracking-wider">
              <span>🛡️ Kiểm Toán Hoạt Động Doanh Nghiệp</span>
              <span>•</span>
              <span>Chuẩn ISO/IEC 27001</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
              Nhật Ký Vận Hành Toàn Diện (System Audit Trail)
            </h2>
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal">
              Cơ chế ghi vết bất biến tự động ghi nhận mọi tác vụ điều hành: <b>thay đổi giá vé, điều chuyển tài xế, đối soát tiền mặt ca chạy, duyệt chi phí nhiên liệu / cầu đường, bán vé tại quầy POS, đổi ghế cho khách</b> và các hoạt động của từng đầu xe. Đảm bảo 100% tính minh bạch và truy vết trách nhiệm người dùng.
            </p>
          </div>

          <!-- Quick Stats Cards -->
          <div class="flex items-center gap-3 shrink-0">
            <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-4 text-center min-w-[120px]">
              <span class="text-[11px] text-slate-300 font-bold block uppercase">Tổng Bản Ghi</span>
              <span class="text-2xl font-black text-amber-400 font-mono">{{ totalCount || (logs?.total || 58) }}</span>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-2xl p-4 text-center min-w-[120px]">
              <span class="text-[11px] text-slate-300 font-bold block uppercase">Trạng Thái Log</span>
              <span class="text-xs font-black text-emerald-400 flex items-center justify-center gap-1 mt-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Bất biến (Active)</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Category Tabs (Phân Chia Xem Riêng Theo Admin / Từng Xe / Thu Chi / Vé) -->
      <div class="flex items-center gap-2 overflow-x-auto pb-1 custom-scrollbar text-xs">
        <button
          type="button"
          @click="setCategory('ALL')"
          :class="selectedCategory === 'ALL' ? 'bg-slate-900 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 font-bold border border-slate-200'"
          class="px-4 py-2 rounded-xl transition cursor-pointer shrink-0 flex items-center gap-1.5"
        >
          <span>📋</span>
          <span>Tất cả nhật ký</span>
        </button>
        <button
          type="button"
          @click="setCategory('ADMIN')"
          :class="selectedCategory === 'ADMIN' ? 'bg-red-600 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 font-bold border border-slate-200'"
          class="px-4 py-2 rounded-xl transition cursor-pointer shrink-0 flex items-center gap-1.5"
        >
          <span>🛡️</span>
          <span>Nhật ký Quản trị Admin</span>
        </button>
        <button
          type="button"
          @click="setCategory('BUS')"
          :class="selectedCategory === 'BUS' ? 'bg-indigo-600 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 font-bold border border-slate-200'"
          class="px-4 py-2 rounded-xl transition cursor-pointer shrink-0 flex items-center gap-1.5"
        >
          <span>🚍</span>
          <span>Hoạt động theo từng xe</span>
        </button>
        <button
          type="button"
          @click="setCategory('FINANCE')"
          :class="selectedCategory === 'FINANCE' ? 'bg-emerald-600 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 font-bold border border-slate-200'"
          class="px-4 py-2 rounded-xl transition cursor-pointer shrink-0 flex items-center gap-1.5"
        >
          <span>💰</span>
          <span>Thu chi & Quyết toán ca</span>
        </button>
        <button
          type="button"
          @click="setCategory('TICKETS')"
          :class="selectedCategory === 'TICKETS' ? 'bg-amber-600 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-100 text-slate-700 font-bold border border-slate-200'"
          class="px-4 py-2 rounded-xl transition cursor-pointer shrink-0 flex items-center gap-1.5"
        >
          <span>🎫</span>
          <span>Vé quầy, dọc đường & Đổi ghế</span>
        </button>
      </div>

      <!-- Thanh Tìm Kiếm & Bộ Lọc Nâng Cao (Theo Xe, Vai Trò, Thao Tác) -->
      <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-4">
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3">
          <!-- Ô Tìm Kiếm To & Rộng -->
          <div class="relative flex-1">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
              🔍
            </span>
            <input 
              type="text" 
              v-model="searchTerm" 
              @keyup.enter="handleSearch"
              placeholder="Tìm theo người thực hiện, nội dung chi tiết, mã chuyến, biển số xe..." 
              class="w-full pl-10 pr-24 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition"
            />
            <button 
              @click="handleSearch" 
              class="absolute right-2 top-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition cursor-pointer"
            >
              Tìm kiếm
            </button>
          </div>

          <!-- Lọc theo Từng Xe (Biển số) -->
          <div class="flex items-center gap-1.5 shrink-0">
            <span class="text-xs font-bold text-slate-500 whitespace-nowrap">🚍 Xe:</span>
            <select 
              v-model="selectedBusPlate" 
              @change="handleSearch"
              class="bg-slate-50 border border-slate-200 rounded-2xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-red-500 cursor-pointer"
            >
              <option value="ALL">-- Tất cả xe --</option>
              <option v-for="(label, plate) in busList" :key="plate" :value="plate">
                {{ label }}
              </option>
            </select>
          </div>

          <!-- Lọc theo Vai Trò Người Thực Hiện -->
          <div class="flex items-center gap-1.5 shrink-0">
            <span class="text-xs font-bold text-slate-500 whitespace-nowrap">👤 Vai trò:</span>
            <select 
              v-model="selectedRole" 
              @change="handleSearch"
              class="bg-slate-50 border border-slate-200 rounded-2xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-red-500 cursor-pointer"
            >
              <option v-for="(label, key) in roleList" :key="key" :value="key">
                {{ label }}
              </option>
            </select>
          </div>

          <!-- Bộ Lọc Phân Loại Hoạt Động -->
          <div class="flex items-center gap-1.5 shrink-0">
            <span class="text-xs font-bold text-slate-500 whitespace-nowrap">⚡ Thao tác:</span>
            <select 
              v-model="selectedAction" 
              @change="handleSearch"
              class="bg-slate-50 border border-slate-200 rounded-2xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-red-500 cursor-pointer"
            >
              <option v-for="(label, key) in actionTypes" :key="key" :value="key">
                {{ label }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Bảng Danh Sách Bản Ghi Audit Logs -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-600 uppercase font-black text-[11px] border-b border-slate-200">
              <tr>
                <th class="p-4 w-36">Thời Gian</th>
                <th class="p-4 w-48">Người Thực Hiện</th>
                <th class="p-4 w-32">Phương Tiện</th>
                <th class="p-4 w-40">Thao Tác</th>
                <th class="p-4 w-32">Thực Thể</th>
                <th class="p-4">Chi Tiết Giao Dịch & Thay Đổi</th>
                <th class="p-4 text-center w-24">Phân Loại</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-if="!logList || logList.length === 0">
                <td colspan="7" class="p-12 text-center text-slate-400 font-medium">
                  Không tìm thấy bản ghi nhật ký hoạt động nào phù hợp với bộ lọc tìm kiếm.
                </td>
              </tr>
              <tr 
                v-for="log in logList" 
                :key="log.id" 
                class="hover:bg-slate-50/80 transition"
              >
                <!-- Thời gian -->
                <td class="p-4 whitespace-nowrap">
                  <div class="font-mono font-bold text-slate-900 text-xs">
                    {{ formatDateTime(log.created_at).time }}
                  </div>
                  <div class="text-[10px] text-slate-400 font-mono">
                    {{ formatDateTime(log.created_at).date }}
                  </div>
                </td>

                <!-- Người thực hiện -->
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-xs font-black text-slate-700 shrink-0">
                      {{ (log.user_name || 'H').substring(0, 1) }}
                    </div>
                    <div>
                      <strong class="font-bold text-slate-900 block truncate max-w-[170px] text-xs">
                        {{ log.user_name || 'Hệ thống tự động' }}
                      </strong>
                      <span 
                        :class="getRoleBadge(log.user_role).class"
                        class="text-[9px] font-black px-1.5 py-0.2 rounded-md inline-block uppercase"
                      >
                        {{ getRoleBadge(log.user_role).label }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Phương tiện / Biển số xe -->
                <td class="p-4 whitespace-nowrap">
                  <span 
                    v-if="log.bus_plate" 
                    class="font-mono font-black text-xs px-2 py-0.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-800"
                  >
                    🚍 {{ log.bus_plate }}
                  </span>
                  <span v-else class="text-slate-300 font-mono text-xs">—</span>
                </td>

                <!-- Phân loại thao tác -->
                <td class="p-4 whitespace-nowrap">
                  <span 
                    :class="getActionBadge(log.action).class" 
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold inline-flex items-center gap-1 border"
                  >
                    <span>{{ getActionBadge(log.action).icon }}</span>
                    <span>{{ getActionBadge(log.action).label }}</span>
                  </span>
                </td>

                <!-- Thực thể liên quan -->
                <td class="p-4 whitespace-nowrap">
                  <div class="font-mono font-bold text-slate-800 text-xs">
                    {{ log.entity_type || 'SYSTEM' }}
                  </div>
                  <div v-if="log.entity_id" class="text-[10px] font-mono text-red-600 font-bold">
                    #{{ log.entity_id }}
                  </div>
                </td>

                <!-- Chi tiết thay đổi -->
                <td class="p-4 text-slate-800 leading-relaxed font-medium">
                  {{ log.description }}
                </td>

                <!-- Mức độ -->
                <td class="p-4 text-center whitespace-nowrap">
                  <span 
                    :class="getSeverityBadge(log.action).class"
                    class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                  >
                    {{ getSeverityBadge(log.action).label }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Phân Trang -->
        <div v-if="logs && logs.links && logs.links.length > 3" class="px-6 py-4 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50 text-xs text-slate-500">
          <div>
            Hiển thị <b>{{ logs.from || 1 }} - {{ logs.to || logList.length }}</b> trên tổng số <b>{{ logs.total || logList.length }}</b> bản ghi kiểm toán
          </div>

          <div class="flex items-center gap-1 flex-wrap">
            <Link
              v-for="(link, idx) in logs.links"
              :key="idx"
              :href="link.url || '#'"
              v-html="link.label"
              :class="[
                'px-3 py-1.5 rounded-xl font-bold transition',
                link.active ? 'bg-red-600 text-white shadow-xs' : (link.url ? 'hover:bg-slate-200 text-slate-700 bg-white border border-slate-200' : 'text-slate-300 pointer-events-none')
              ]"
            />
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';

const props = defineProps({
  logs: Object,
  filters: Object,
  actionTypes: Object,
  busList: Object,
  roleList: Object,
  totalCount: Number,
});

const searchTerm = ref(props.filters?.q || '');
const selectedAction = ref(props.filters?.action || 'ALL');
const selectedBusPlate = ref(props.filters?.bus_plate || 'ALL');
const selectedRole = ref(props.filters?.role || 'ALL');
const selectedCategory = ref(props.filters?.category || 'ALL');

const logList = computed(() => {
  if (props.logs?.data) return props.logs.data;
  if (Array.isArray(props.logs)) return props.logs;
  return [];
});

const setCategory = (cat) => {
  selectedCategory.value = cat;
  if (cat === 'ADMIN') {
    selectedRole.value = 'admin';
  } else if (cat === 'ALL') {
    selectedRole.value = 'ALL';
    selectedBusPlate.value = 'ALL';
    selectedAction.value = 'ALL';
  }
  handleSearch();
};

const handleSearch = () => {
  router.get('/admin/logs', {
    q: searchTerm.value,
    action: selectedAction.value,
    bus_plate: selectedBusPlate.value,
    role: selectedRole.value,
    category: selectedCategory.value,
  }, {
    preserveState: true,
    replace: true,
  });
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return { time: '00:00', date: '29/10/2026' };
  const d = new Date(dateStr);
  const time = d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  const date = d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
  return { time, date };
};

const getRoleBadge = (role) => {
  switch (role) {
    case 'admin':
      return { label: 'Admin', class: 'bg-red-100 text-red-800' };
    case 'driver':
      return { label: 'Tài Xế', class: 'bg-blue-100 text-blue-800' };
    case 'conductor':
      return { label: 'Lơ Xe', class: 'bg-amber-100 text-amber-800' };
    default:
      return { label: 'Hệ Thống', class: 'bg-slate-100 text-slate-700' };
  }
};

const getActionBadge = (action) => {
  switch (action) {
    case 'UPDATE_PRICE':
      return { label: 'Sửa giá vé', icon: '🏷️', class: 'bg-amber-50 text-amber-800 border-amber-300' };
    case 'ASSIGN_DRIVER':
      return { label: 'Xếp tài xế', icon: '👨‍✈️', class: 'bg-blue-50 text-blue-800 border-blue-300' };
    case 'SETTLE_CASH':
      return { label: 'Nộp tiền mặt', icon: '💰', class: 'bg-emerald-50 text-emerald-800 border-emerald-300' };
    case 'CREATE_TRIP':
      return { label: 'Tạo chuyến mới', icon: '✨', class: 'bg-purple-50 text-purple-800 border-purple-300' };
    case 'CANCEL_TICKET':
      return { label: 'Hủy vé & hoàn', icon: '↩️', class: 'bg-rose-50 text-rose-800 border-rose-300' };
    case 'APPROVE_EXPENSE':
      return { label: 'Duyệt chi phí', icon: '⛽', class: 'bg-teal-50 text-teal-800 border-teal-300' };
    case 'POS_BOOKING':
      return { label: 'Bán vé POS', icon: '🏢', class: 'bg-indigo-50 text-indigo-800 border-indigo-300' };
    case 'ROAD_TICKET':
    case 'ROADSIDE_TICKET':
      return { label: 'Bán vé trên xe', icon: '🚍', class: 'bg-sky-50 text-sky-800 border-sky-300' };
    case 'SEAT_SWAP':
      return { label: 'Đổi ghế', icon: '🔄', class: 'bg-blue-50 text-blue-800 border-blue-300' };
    case 'REPORT_INCIDENT':
      return { label: 'Sự cố xe', icon: '⚠️', class: 'bg-red-50 text-red-800 border-red-300' };
    default:
      return { label: action || 'Thao tác', icon: '📝', class: 'bg-slate-100 text-slate-700 border-slate-200' };
  }
};

const getSeverityBadge = (action) => {
  if (['CANCEL_TICKET', 'REPORT_INCIDENT'].includes(action)) {
    return { label: 'Cảnh báo', class: 'bg-rose-100 text-rose-700' };
  }
  if (['UPDATE_PRICE', 'SETTLE_CASH'].includes(action)) {
    return { label: 'Tài chính', class: 'bg-amber-100 text-amber-800' };
  }
  if (['ASSIGN_DRIVER', 'CREATE_TRIP', 'APPROVE_EXPENSE'].includes(action)) {
    return { label: 'Vận hành', class: 'bg-blue-100 text-blue-700' };
  }
  return { label: 'Thông tin', class: 'bg-slate-100 text-slate-600' };
};
</script>
