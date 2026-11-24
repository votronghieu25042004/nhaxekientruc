<template>
  <div class="relative" ref="dropdownRef">
    <!-- NÚT CHUÔNG THÔNG BÁO -->
    <button
      type="button"
      @click="toggleDropdown"
      class="relative p-2 rounded-xl transition duration-200 cursor-pointer flex items-center justify-center"
      :class="customButtonClass || 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200'"
      title="Trung tâm thông báo"
      aria-label="Xem thông báo"
    >
      <span class="text-base sm:text-lg">🔔</span>
      <!-- Badge số lượng chưa đọc -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 px-1.5 py-0.2 rounded-full bg-red-600 text-white font-black text-[10px] min-w-4.5 text-center shadow-xs animate-bounce"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- POPUP DANH SÁCH THÔNG BÁO -->
    <div
      v-if="isOpen"
      class="absolute right-0 mt-2 w-80 sm:w-96 bg-white border border-slate-200 rounded-3xl shadow-2xl z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 text-slate-800"
    >
      <!-- Header Popup -->
      <div class="p-3.5 sm:p-4 bg-slate-50 border-b border-slate-100 space-y-2.5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-base">🔔</span>
            <h4 class="text-xs sm:text-sm font-black text-slate-900 uppercase tracking-wide">
              Trung Tâm Thông Báo
            </h4>
            <span v-if="unreadCount > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">
              {{ unreadCount }} mới
            </span>
          </div>

          <!-- Nút Đọc tất cả trên Header -->
          <button
            v-if="unreadCount > 0"
            type="button"
            @click="markAllAsRead"
            class="px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 text-[11px] font-bold text-red-600 border border-red-200 transition cursor-pointer flex items-center gap-1 shadow-2xs"
            title="Đánh dấu tất cả là đã đọc"
          >
            <span>✓ Đọc tất cả</span>
          </button>
        </div>

        <!-- Tab chuyển: Tất cả vs Chưa đọc (khi có nhiều thông báo) -->
        <div class="flex items-center gap-1 bg-slate-200/60 p-1 rounded-xl text-xs font-bold">
          <button
            type="button"
            @click="activeTab = 'all'"
            :class="activeTab === 'all' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            class="flex-1 py-1 rounded-lg transition text-center cursor-pointer"
          >
            Tất cả ({{ notifications.length }})
          </button>
          <button
            type="button"
            @click="activeTab = 'unread'"
            :class="activeTab === 'unread' ? 'bg-white text-red-600 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
            class="flex-1 py-1 rounded-lg transition text-center cursor-pointer flex items-center justify-center gap-1"
          >
            <span>Chưa đọc</span>
            <span v-if="unreadCount > 0" class="px-1.5 py-0.2 rounded-full bg-red-600 text-white text-[9px] font-black">
              {{ unreadCount }}
            </span>
          </button>
        </div>
      </div>

      <!-- Danh sách thông báo -->
      <div class="max-h-80 sm:max-h-96 overflow-y-auto divide-y divide-slate-100">
        <div
          v-if="displayedNotifications.length === 0"
          class="p-8 text-center text-slate-400 text-xs space-y-2"
        >
          <div class="text-3xl">📭</div>
          <p v-if="activeTab === 'unread'">Tuyệt vời! Bạn không còn thông báo nào chưa đọc.</p>
          <p v-else>Hiện không có thông báo nào trong hộp thư!</p>
        </div>

        <div
          v-for="item in displayedNotifications"
          :key="item.id"
          @click="handleClickItem(item)"
          class="p-3.5 hover:bg-slate-50 transition cursor-pointer flex items-start gap-3 relative group"
          :class="{ 'bg-red-50/40': !item.is_read }"
        >
          <!-- Icon -->
          <div class="w-9 h-9 rounded-xl bg-slate-100 group-hover:bg-red-50 group-hover:border-red-200 flex items-center justify-center text-base shrink-0 border border-slate-200 transition shadow-2xs">
            {{ item.icon || '📌' }}
          </div>

          <!-- Nội dung -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-1">
              <h5 class="text-xs font-black text-slate-900 truncate group-hover:text-red-600 transition">
                {{ item.title }}
              </h5>
              <span class="text-[10px] text-slate-400 shrink-0 font-medium">
                {{ item.time }}
              </span>
            </div>
            <p class="text-[11px] text-slate-600 mt-0.5 line-clamp-2 leading-relaxed">
              {{ item.content }}
            </p>
            <div class="mt-1 flex items-center gap-1.5 text-[10px] font-bold text-red-600">
              <span>Bấm để xem chi tiết</span>
              <span>→</span>
            </div>
          </div>

          <!-- Chấm đỏ chưa đọc -->
          <span
            v-if="!item.is_read"
            class="w-2.5 h-2.5 rounded-full bg-red-500 shrink-0 self-center shadow-xs"
          ></span>
        </div>
      </div>

      <!-- Footer Popup -->
      <div class="p-2.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
        <span class="font-medium">
          Nhà xe Kiến Trúc • Thông báo tức thời
        </span>
        <button
          v-if="unreadCount > 0"
          type="button"
          @click="markAllAsRead"
          class="text-red-600 font-bold hover:underline cursor-pointer"
        >
          Đọc tất cả ({{ unreadCount }})
        </button>
      </div>
    </div>

    <!-- MODAL CHI TIẾT THÔNG BÁO (DÙNG TELEPORT TO BODY ĐỂ CĂN GIỮA MÀN HÌNH CHUẨN XÁC) -->
    <Teleport to="body">
      <div
        v-if="showDetailModal && selectedNotification"
        class="fixed inset-0 z-[999999] bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto animate-in fade-in"
        @click.self="showDetailModal = false"
      >
        <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-100 space-y-4 my-auto animate-in zoom-in-95 text-slate-800">
          <!-- Header modal -->
          <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 border border-red-100 flex items-center justify-center text-2xl shadow-xs shrink-0">
                {{ selectedNotification.icon || '🔔' }}
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-wider">
                    {{ selectedNotification.time || 'Thông báo' }}
                  </span>
                  <span v-if="!selectedNotification.is_read" class="px-2 py-0.5 rounded-md bg-red-100 text-red-600 text-[10px] font-bold">
                    Mới
                  </span>
                </div>
                <h3 class="text-base font-black text-slate-900 mt-1">
                  {{ selectedNotification.title }}
                </h3>
              </div>
            </div>
            <button
              type="button"
              @click="showDetailModal = false"
              class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1 rounded-lg hover:bg-slate-100 transition cursor-pointer"
            >
              ✕
            </button>
          </div>

          <!-- Tóm tắt ngắn -->
          <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 text-xs font-semibold text-slate-700 leading-relaxed">
            {{ selectedNotification.content }}
          </div>

          <!-- Nội dung chi tiết -->
          <div class="text-xs text-slate-600 leading-relaxed whitespace-pre-line bg-white p-1">
            {{ selectedNotification.detail || selectedNotification.content }}
          </div>

          <!-- Nút hành động -->
          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="showDetailModal = false"
              class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-50 transition cursor-pointer"
            >
              Đóng
            </button>
            <button
              v-if="selectedNotification.link"
              type="button"
              @click="handleNavigate(selectedNotification.link)"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs shadow-md transition cursor-pointer flex items-center gap-1.5"
            >
              <span>{{ selectedNotification.action_label || 'Xem Chi Tiết Ngay' }}</span>
              <span>→</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  customButtonClass: { type: String, default: '' },
  role: { type: String, default: 'customer' }
});

const READ_STORAGE_KEY = 'nhaxekientruc_read_notifs';

const isOpen = ref(false);
const dropdownRef = ref(null);
const notifications = ref([]);
const unreadCount = ref(0);
const activeTab = ref('all'); // 'all' hoặc 'unread'

const showDetailModal = ref(false);
const selectedNotification = ref(null);

const displayedNotifications = computed(() => {
  if (activeTab.value === 'unread') {
    return notifications.value.filter(n => !n.is_read);
  }
  return notifications.value;
});

const getReadIds = () => {
  try {
    const raw = localStorage.getItem(READ_STORAGE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    return [];
  }
};

const saveReadId = (id) => {
  try {
    const ids = getReadIds();
    if (!ids.includes(id)) {
      ids.push(id);
      localStorage.setItem(READ_STORAGE_KEY, JSON.stringify(ids));
    }
  } catch (e) {}

  // Gửi đồng bộ lên backend
  try {
    fetch('/api/notifications/mark-read', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ id })
    });
  } catch (e) {}
};

const fetchNotifications = async () => {
  try {
    const res = await fetch('/api/notifications');
    const data = await res.json();
    if (data.success) {
      const readIds = getReadIds();
      const rawList = data.notifications || [];
      notifications.value = rawList.map(n => {
        const isRead = n.is_read || readIds.includes(n.id);
        return { ...n, is_read: isRead };
      });
      unreadCount.value = notifications.value.filter(n => !n.is_read).length;
    }
  } catch (e) {
    // Fallback rỗng khi có lỗi mạng hoặc chưa đăng nhập
    notifications.value = [];
    unreadCount.value = 0;
  }
};

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value && notifications.value.length === 0) {
    fetchNotifications();
  }
};

const markAllAsRead = () => {
  const allIds = notifications.value.map(n => n.id);
  try {
    localStorage.setItem(READ_STORAGE_KEY, JSON.stringify(allIds));
  } catch (e) {}

  notifications.value.forEach(n => {
    n.is_read = true;
  });
  unreadCount.value = 0;

  // Gửi đồng bộ lên backend
  try {
    fetch('/api/notifications/mark-read', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ all: true, ids: allIds })
    });
  } catch (e) {}
};

const handleClickItem = (item) => {
  saveReadId(item.id);
  item.is_read = true;
  unreadCount.value = notifications.value.filter(n => !n.is_read).length;
  isOpen.value = false;
  selectedNotification.value = item;
  showDetailModal.value = true;
};

const handleNavigate = (link) => {
  showDetailModal.value = false;
  if (link && link !== '#') {
    router.visit(link);
  }
};

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  fetchNotifications();
  document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
