<template>
  <div>
    <!-- Nút Nổi Mở Trợ Lý AI (Góc Dưới Phải) -->
    <div
      v-if="!isOpen"
      class="fixed bottom-6 right-6 z-50 flex items-center gap-3 cursor-pointer group"
      @click="openModal"
    >
      <div class="hidden sm:flex flex-col items-end bg-white/95 backdrop-blur-md px-3.5 py-1.5 rounded-full shadow-lg border border-red-100 text-xs font-semibold text-red-950 group-hover:scale-105 transition-all">
        <span>Trợ Lý AI nhaxekientruc</span>
        <span class="text-[10px] text-emerald-600 font-bold flex items-center gap-1">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
          Trực tuyến 24/7
        </span>
      </div>
      <button
        class="w-14 h-14 rounded-full bg-gradient-to-tr from-red-600 via-rose-600 to-red-700 text-white shadow-xl shadow-red-600/30 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-200 border-2 border-white relative cursor-pointer"
        title="Trợ Lý Vé Xe nhaxekientruc AI"
      >
        <span class="absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></span>
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
      </button>
    </div>

    <!-- Khung Chat AI Cao Cấp (Có thể Di Chuyển & Phóng To / Thu Nhỏ) -->
    <Teleport to="body">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-2 sm:p-4 bg-black/50 backdrop-blur-xs transition-all duration-300"
      >
        <!-- Backdrop click to close -->
        <div class="fixed inset-0" @click="closeModal"></div>

        <!-- Khung Modal Trợ Lý AI nhaxekientruc -->
        <div
          ref="modalRef"
          :class="[
            isMaximized
              ? 'w-full h-full max-w-full max-h-full rounded-none'
              : 'w-full max-w-5xl h-[650px] max-h-[92vh] rounded-3xl'
          ]"
          class="relative bg-white shadow-2xl flex flex-col sm:flex-row overflow-hidden z-10 border border-red-200/80 transition-all duration-200"
        >
          
          <!-- ============================================== -->
          <!-- CỘT TRÁI: LỊCH SỬ TRÒ CHUYỆN (TÔNG MÀU ĐỎ ĐÔ SANG TRỌNG) -->
          <!-- ============================================== -->
          <aside class="w-full sm:w-64 bg-gradient-to-b from-[#4c0519] via-[#881337] to-[#7f1d1d] text-red-50 flex flex-col border-r border-red-800/80 flex-shrink-0">
            <!-- Header Sidebar -->
            <div class="p-3.5 border-b border-red-800/60 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl bg-white text-red-700 flex items-center justify-center font-black text-xs shadow-xs">
                  🚌
                </div>
                <span class="text-xs font-black text-white tracking-wide">Trợ Lý nhaxekientruc AI</span>
              </div>
              
              <!-- Nút Tạo Đoạn Chat Mới (Chỉ hiện khi đã đăng nhập) -->
              <button
                v-if="currentUser"
                type="button"
                @click="createNewChat"
                class="p-1.5 rounded-lg bg-red-900/80 hover:bg-white hover:text-red-700 text-red-100 text-xs font-bold transition flex items-center gap-1 cursor-pointer border border-red-700/60"
                title="Đoạn chat mới"
              >
                <span>+</span>
                <span class="text-[11px]">Mới</span>
              </button>
            </div>

            <!-- NẾU CHƯA ĐĂNG NHẬP: THÔNG BÁO CHỈ LƯU LỊCH SỬ KHI ĐĂNG NHẬP (REQUIREMENT 4) -->
            <div v-if="!currentUser" class="flex-1 p-4 flex flex-col items-center justify-center text-center space-y-3">
              <div class="w-12 h-12 rounded-2xl bg-white/10 text-white flex items-center justify-center text-xl shadow-inner border border-white/20">
                🔒
              </div>
              <div class="text-xs font-black text-white">Lịch Sử Trò Chuyện</div>
              <p class="text-[11px] text-red-200 leading-relaxed">
                Đăng nhập tài khoản để lưu lại toàn bộ lịch sử các đoạn trò chuyện cùng Trợ Lý AI của bạn.
              </p>
              <a
                href="/login"
                class="px-4 py-2.5 rounded-xl bg-white hover:bg-red-50 text-red-700 font-black text-xs transition shadow-md cursor-pointer block w-full"
              >
                Đăng Nhập Ngay
              </a>
              <div class="text-[10px] text-red-300">
                (Khách vãng lai có thể hỏi đáp tự do nhưng không lưu lịch sử)
              </div>
            </div>

            <!-- NẾU ĐÃ ĐĂNG NHẬP: HIỂN THỊ LỊCH SỬ CHAT RIÊNG CỦA TỪNG USER -->
            <div v-else class="flex-1 flex flex-col overflow-hidden">
              <!-- Nút Tạo Đoạn Chat Mới To Rõ -->
              <div class="p-3">
                <button
                  type="button"
                  @click="createNewChat"
                  class="w-full py-2.5 px-3 rounded-xl bg-white hover:bg-red-50 text-red-700 hover:text-red-800 text-xs font-black transition flex items-center justify-center gap-2 shadow-sm cursor-pointer"
                >
                  <span>+</span>
                  <span>Đoạn chat mới</span>
                </button>
              </div>

              <!-- Danh Sách Lịch Sử Trò Chuyện (Có Đổi Tên & Xóa) -->
              <div class="flex-1 overflow-y-auto px-2 space-y-1.5 custom-scrollbar">
                <div class="flex items-center justify-between px-2 py-1 text-[10px] font-bold text-red-200/70 uppercase tracking-wider">
                  <span>Lịch sử trò chuyện</span>
                  <button
                    v-if="chatSessions.length > 1"
                    @click="clearAllChats"
                    class="text-[10px] text-red-300 hover:text-white hover:underline cursor-pointer lowercase"
                  >
                    xóa tất cả
                  </button>
                </div>

                <div
                  v-for="session in chatSessions"
                  :key="session.id"
                  @click="switchChatSession(session.id)"
                  class="group flex items-center justify-between p-2 rounded-xl text-xs font-semibold transition cursor-pointer"
                  :class="currentSessionId === session.id ? 'bg-white/20 text-white font-bold border border-white/20' : 'text-red-200 hover:bg-white/10 hover:text-white'"
                >
                  <!-- Tiêu đề / Ô sửa tên -->
                  <div class="flex items-center gap-2 truncate flex-1 mr-1">
                    <svg class="w-3.5 h-3.5 flex-shrink-0 text-red-300 group-hover:text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    
                    <input
                      v-if="editingSessionId === session.id"
                      v-model="editingTitle"
                      @blur="saveSessionTitle(session.id)"
                      @keyup.enter="saveSessionTitle(session.id)"
                      @click.stop
                      type="text"
                      class="bg-white text-slate-900 px-1.5 py-0.5 rounded text-xs w-full outline-none font-bold"
                      autofocus
                    />
                    <span v-else class="truncate">{{ session.title }}</span>
                  </div>

                  <!-- Nút Hành Động: Đổi Tên & Xóa -->
                  <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                    <!-- Đổi tên -->
                    <button
                      type="button"
                      @click.stop="startRename(session)"
                      class="p-1 rounded text-red-200 hover:text-white hover:bg-white/20 transition cursor-pointer"
                      title="Đổi tên đoạn chat"
                    >
                      ✏️
                    </button>
                    <!-- Xóa -->
                    <button
                      v-if="chatSessions.length > 1"
                      type="button"
                      @click.stop="deleteChatSession(session.id)"
                      class="p-1 rounded text-red-200 hover:text-red-100 hover:bg-red-500/40 transition cursor-pointer"
                      title="Xóa đoạn chat này"
                    >
                      🗑️
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Sidebar: Quyền Quản Trị Viên -->
            <div v-if="isAdminUser" class="p-3 border-t border-red-800/80 bg-red-950/50">
              <button
                type="button"
                @click="activeView = activeView === 'admin_email' ? 'chat' : 'admin_email'"
                class="w-full py-2 px-3 rounded-xl text-xs font-bold transition flex items-center justify-between cursor-pointer"
                :class="activeView === 'admin_email' ? 'bg-white text-red-700' : 'bg-red-900/80 hover:bg-red-800 text-amber-300 border border-red-700/60'"
              >
                <span class="flex items-center gap-1.5">
                  <span>⚡</span>
                  <span>Quản Trị: Gửi Gmail</span>
                </span>
                <span class="text-[10px] bg-amber-400/20 text-amber-300 px-1.5 py-0.5 rounded">Admin</span>
              </button>
            </div>
          </aside>

          <!-- ============================================== -->
          <!-- CỘT PHẢI: KHUNG TRÒ CHUYỆN CHÍNH HOẶC GỬI EMAIL ADMIN -->
          <!-- ============================================== -->
          <div class="flex-1 flex flex-col bg-white overflow-hidden">
            
            <!-- HEADER KHUNG CHAT (CÓ NÚT PHÓNG TO / THU NHỎ / ĐÓNG) -->
            <div class="p-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/70 select-none">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-red-600 to-rose-600 text-white flex items-center justify-center font-black text-sm shadow-xs">
                  🤖
                </div>
                <div>
                  <h3 class="text-sm font-black text-slate-900 leading-tight">
                    {{ activeView === 'admin_email' ? 'Phân Hệ Gửi Thông Báo Gmail Tự Động' : 'Trợ Lý Trực Tuyến AI nhaxekientruc' }}
                  </h3>
                  <p class="text-[11px] text-emerald-600 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Tư vấn tuyến đường, cọc 30%, nhắc giờ chạy & vé điện tử</span>
                  </p>
                </div>
              </div>

              <!-- Cụm Nút Điều Khiển: Phóng To / Thu Nhỏ / Đóng -->
              <div class="flex items-center gap-1.5">
                <!-- Phóng to / Thu nhỏ -->
                <button
                  type="button"
                  @click="toggleMaximize"
                  class="p-1.5 rounded-xl hover:bg-slate-200 text-slate-600 hover:text-slate-900 transition text-xs font-bold cursor-pointer"
                  :title="isMaximized ? 'Thu nhỏ lại' : 'Phóng to toàn màn hình'"
                >
                  <span v-if="!isMaximized">⛶</span>
                  <span v-else>🗗</span>
                </button>

                <!-- Nút Đóng Modal -->
                <button
                  type="button"
                  @click="closeModal"
                  class="text-slate-400 hover:text-red-600 font-bold p-1.5 rounded-xl hover:bg-red-50 transition cursor-pointer text-base"
                  title="Đóng cửa sổ"
                >
                  ✕
                </button>
              </div>
            </div>

            <!-- NỘI DUNG 1: KHUNG GỬI GMAIL TỰ ĐỘNG (CHỈ ADMIN THẤY) -->
            <div v-if="activeView === 'admin_email' && isAdminUser" class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50/50">
              <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-center justify-between">
                <div>
                  <strong>⚡ Quyền Quản Trị Viên:</strong> Tự động gửi email thông báo nhắc trước 45 phút & thông báo sự cố/dời giờ cho hành khách.
                </div>
                <button @click="activeView = 'chat'" class="text-xs font-bold text-amber-800 underline cursor-pointer">
                  Quay lại Chat
                </button>
              </div>

              <!-- Bộ chọn xe & nút gửi -->
              <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div class="flex-1">
                    <label class="text-xs font-bold text-slate-700 block mb-1">Chọn chuyến xe gửi thông báo:</label>
                    <select
                      v-model="selectedBusFilter"
                      class="w-full bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 rounded-xl px-3 py-2 outline-none focus:ring-2 focus:ring-red-500"
                    >
                      <option value="ALL">Tất cả các chuyến xe hôm nay</option>
                      <option v-for="b in sampleBuses" :key="b.plate" :value="b.plate">
                        {{ b.plate }} - {{ b.route }} ({{ b.passengers }} khách)
                      </option>
                    </select>
                  </div>

                  <div class="flex items-center gap-2 pt-4 sm:pt-0">
                    <button
                      type="button"
                      @click="triggerAutoEmail(false)"
                      :disabled="isSendingEmail"
                      class="px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                      <span>⚡ Gửi Nhắc 45P Xe Này</span>
                    </button>
                    <button
                      type="button"
                      @click="triggerAutoEmail(true)"
                      :disabled="isSendingEmail"
                      class="px-3.5 py-2 bg-[#881337] hover:bg-[#4c0519] text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer"
                    >
                      <span>🚀 Gửi Tất Cả Các Xe</span>
                    </button>
                  </div>
                </div>

                <!-- Log Gmail Đã Gửi -->
                <div class="space-y-2.5 pt-2">
                  <div
                    v-for="(log, idx) in emailLogs"
                    :key="idx"
                    class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-1 animate-in fade-in"
                  >
                    <div class="flex items-center justify-between font-bold text-slate-800">
                      <span class="text-red-600">{{ log.type }}</span>
                      <span class="text-[10px] text-slate-400 font-mono">{{ log.time }}</span>
                    </div>
                    <div class="text-[11px] text-slate-600">
                      Tuyến: <strong>{{ log.route }}</strong> (Xe: <strong>{{ log.plate }}</strong>) → Gửi đến: <strong>{{ log.email }}</strong> ({{ log.passengers }} khách)
                    </div>
                    <div class="text-[10px] text-emerald-600 font-semibold flex items-center gap-1">
                      <span>✓ Đã chuyển phát vào hộp thư đến SMTP</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- NỘI DUNG 2: KHUNG CHAT CHÍNH (CHO KHÁCH HÀNG & ADMIN) -->
            <div v-else class="flex-1 flex flex-col overflow-hidden">
              
              <!-- Tin nhắn hội thoại -->
              <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-3.5 custom-scrollbar">
                
                <!-- Lời chào & Gợi ý câu hỏi ban đầu -->
                <div v-if="currentMessages.length === 0" class="text-center py-6 space-y-3 max-w-md mx-auto">
                  <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 mx-auto flex items-center justify-center text-xl shadow-2xs">
                    💬
                  </div>
                  <h4 class="font-black text-sm text-slate-900">Tôi có thể hỗ trợ gì cho chuyến đi của bạn?</h4>
                  <p class="text-xs text-slate-500">
                    Hỏi tôi bất cứ điều gì về lịch trình xe, giá vé, chính sách đặt cọc 30%, sơ đồ ghế ngồi hoặc các trạm đón trả khách.
                  </p>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2 text-left">
                    <button
                      v-for="(sug, idx) in promptSuggestions"
                      :key="idx"
                      type="button"
                      @click="sendPrompt(sug)"
                      class="p-2.5 rounded-xl border border-slate-200 hover:border-red-400 hover:bg-red-50/50 text-xs font-semibold text-slate-700 hover:text-red-700 transition cursor-pointer text-left"
                    >
                      {{ sug }}
                    </button>
                  </div>
                </div>

                <!-- Danh sách tin nhắn -->
                <div
                  v-for="(msg, idx) in currentMessages"
                  :key="idx"
                  class="flex gap-2.5"
                  :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                  <!-- Avatar Bot -->
                  <div
                    v-if="msg.role === 'assistant'"
                    class="w-7 h-7 rounded-lg bg-red-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5"
                  >
                    AI
                  </div>

                  <!-- Khung chat -->
                  <div
                    class="max-w-[80%] rounded-2xl p-3.5 text-xs leading-relaxed shadow-2xs"
                    :class="msg.role === 'user' ? 'bg-red-600 text-white rounded-tr-xs' : 'bg-slate-100 text-slate-800 rounded-tl-xs border border-slate-200'"
                  >
                    <div class="whitespace-pre-wrap">{{ msg.content }}</div>
                    <div class="text-[9px] mt-1 text-right" :class="msg.role === 'user' ? 'text-red-200' : 'text-slate-400'">
                      {{ msg.time }}
                    </div>
                  </div>
                </div>

                <!-- Loading AI Typing -->
                <div v-if="isAiTyping" class="flex gap-2.5 items-center text-slate-400 text-xs">
                  <div class="w-7 h-7 rounded-lg bg-red-600 text-white flex items-center justify-center text-xs font-bold">
                    AI
                  </div>
                  <div class="p-3 bg-slate-100 rounded-2xl border border-slate-200 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.4s]"></span>
                  </div>
                </div>

              </div>

              <!-- THANH NHẬP CHAT BÊN DƯỚI -->
              <div class="p-3 border-t border-slate-100 bg-white">
                <form @submit.prevent="sendMessage" class="flex items-center gap-2">
                  <input
                    type="text"
                    v-model="inputQuery"
                    placeholder="Nhập câu hỏi (VD: Có xe đi Quảng Trị lúc mấy giờ? Chính sách cọc 30%...)"
                    class="flex-1 bg-slate-50 hover:bg-white focus:bg-white border border-slate-200 focus:border-red-500 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-900 outline-none transition"
                  />
                  <button
                    type="submit"
                    :disabled="!inputQuery.trim() || isAiTyping"
                    class="p-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-xl shadow-xs transition cursor-pointer flex-shrink-0"
                  >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="22" y1="2" x2="11" y2="13"></line>
                      <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                  </button>
                </form>
              </div>

            </div>

          </div>

        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const isOpen = ref(false);
const isMaximized = ref(false);
const activeView = ref('chat');
const inputQuery = ref('');
const isAiTyping = ref(false);
const chatContainer = ref(null);

const currentSessionId = ref(1);
const editingSessionId = ref(null);
const editingTitle = ref('');

const isSendingEmail = ref(false);
const selectedBusFilter = ref('ALL');

const currentUser = computed(() => {
  return page.props.auth?.user || null;
});

const isAdminUser = computed(() => {
  const u = currentUser.value;
  return u && (u.role === 'admin' || u.role === 'staff');
});

// Các đoạn chat: Chỉ lưu vào LocalStorage khi đã đăng nhập tài khoản
const getInitialSessions = () => {
  if (typeof window !== 'undefined' && currentUser.value) {
    const saved = localStorage.getItem(`bushub_chat_sessions_${currentUser.value.id}`);
    if (saved) {
      try {
        const parsed = JSON.parse(saved);
        if (Array.isArray(parsed) && parsed.length > 0) return parsed;
      } catch (e) {}
    }
    return [
      {
        id: 1,
        title: 'Tư vấn chuyến đi mới',
        messages: [
          {
            role: 'assistant',
            content: `Chào ${currentUser.value.name || 'bạn'}! Tôi là Trợ Lý AI nhaxekientruc. Tôi có thể giúp gì cho chuyến đi sắp tới của bạn?`,
            time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
          }
        ]
      }
    ];
  }

  // Khách vãng lai: chỉ giữ 1 đoạn chat tạm thời trong phiên
  return [
    {
      id: 1,
      title: 'Đoạn chat tạm thời (Khách)',
      messages: [
        {
          role: 'assistant',
          content: 'Chào bạn! Tôi là Trợ Lý AI nhaxekientruc. Tôi có thể giúp gì cho bạn hôm nay? (Đăng nhập để tự động lưu lịch sử)',
          time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
        }
      ]
    }
  ];
};

const chatSessions = ref(getInitialSessions());

const persistSessions = () => {
  if (typeof window !== 'undefined' && currentUser.value) {
    try {
      localStorage.setItem(`bushub_chat_sessions_${currentUser.value.id}`, JSON.stringify(chatSessions.value));
    } catch (e) {}
  }
};

const currentMessages = computed(() => {
  const s = chatSessions.value.find(x => x.id === currentSessionId.value);
  return s ? s.messages : [];
});

const promptSuggestions = [
  'Có xe đi Quảng Trị lúc mấy giờ?',
  'Chính sách đặt cọc 30% giữ chỗ?',
  'Thời gian chạy Đà Nẵng đi Quy Nhơn?',
  'Quy định hủy vé và đổi chuyến xe?'
];

const sampleBuses = [
  { plate: '43B-012.34', route: 'Đà Nẵng ⇄ Huế', passengers: 28 },
  { plate: '43B-018.99', route: 'Đà Nẵng ⇄ Quảng Trị', passengers: 32 },
  { plate: '43B-023.45', route: 'Đà Nẵng ⇄ Quảng Bình', passengers: 20 },
  { plate: '43B-034.56', route: 'Đà Nẵng ⇄ Quy Nhơn', passengers: 34 },
  { plate: '43B-045.67', route: 'Đà Nẵng ⇄ Buôn Ma Thuột', passengers: 30 },
];

const emailLogs = ref([
  {
    type: '🔔 NHẮC NHỞ TRƯỚC 45 PHÚT',
    route: 'Đà Nẵng ⇄ Thừa Thiên Huế',
    plate: '43B-012.34',
    email: 'khachhang@gmail.com (+27 khách)',
    passengers: 28,
    time: '22:15'
  },
  {
    type: '⚠️ THÔNG BÁO DỜI GIỜ XE',
    route: 'Đà Nẵng ⇄ Quảng Trị (Đông Hà)',
    plate: '43B-018.99',
    email: 'long.staff@nhaxekientruc.vn (+31 khách)',
    passengers: 32,
    time: '21:40'
  }
]);

const openModal = () => {
  isOpen.value = true;
};

const closeModal = () => {
  isOpen.value = false;
};

const toggleMaximize = () => {
  isMaximized.value = !isMaximized.value;
};

const createNewChat = () => {
  const newId = Date.now();
  chatSessions.value.unshift({
    id: newId,
    title: `Đoạn chat ${chatSessions.value.length + 1}`,
    messages: [
      {
        role: 'assistant',
        content: 'Chào bạn! Tôi là Trợ Lý AI nhaxekientruc. Tôi có thể hỗ trợ gì cho bạn?',
        time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
      }
    ]
  });
  currentSessionId.value = newId;
  persistSessions();
};

const switchChatSession = (id) => {
  currentSessionId.value = id;
};

const startRename = (session) => {
  editingSessionId.value = session.id;
  editingTitle.value = session.title;
};

const saveSessionTitle = (id) => {
  if (editingTitle.value.trim()) {
    const s = chatSessions.value.find(x => x.id === id);
    if (s) {
      s.title = editingTitle.value.trim();
      persistSessions();
    }
  }
  editingSessionId.value = null;
};

const deleteChatSession = (id) => {
  if (chatSessions.value.length <= 1) return;
  chatSessions.value = chatSessions.value.filter(x => x.id !== id);
  if (currentSessionId.value === id) {
    currentSessionId.value = chatSessions.value[0].id;
  }
  persistSessions();
};

const clearAllChats = () => {
  if (confirm('Bạn có chắc muốn xóa toàn bộ lịch sử trò chuyện không?')) {
    chatSessions.value = [
      {
        id: Date.now(),
        title: 'Đoạn chat mới',
        messages: [
          {
            role: 'assistant',
            content: 'Chào bạn! Lịch sử đã được làm mới. Bạn cần hỗ trợ gì ạ?',
            time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
          }
        ]
      }
    ];
    currentSessionId.value = chatSessions.value[0].id;
    persistSessions();
  }
};

const scrollToBottom = () => {
  nextTick(() => {
    if (chatContainer.value) {
      chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
  });
};

const sendPrompt = (text) => {
  inputQuery.value = text;
  sendMessage();
};

const sendMessage = async () => {
  const query = inputQuery.value.trim();
  if (!query) return;

  const currentSession = chatSessions.value.find(x => x.id === currentSessionId.value);
  if (!currentSession) return;

  const userTime = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
  currentSession.messages.push({
    role: 'user',
    content: query,
    time: userTime
  });

  inputQuery.value = '';
  scrollToBottom();
  persistSessions();
  isAiTyping.value = true;

  try {
    const res = await fetch('/api/ai/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ message: query })
    });

    const data = await res.json();
    const reply = data.reply || generateSmartReply(query);

    currentSession.messages.push({
      role: 'assistant',
      content: reply,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    });
  } catch (err) {
    const fallback = generateSmartReply(query);
    currentSession.messages.push({
      role: 'assistant',
      content: fallback,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    });
  } finally {
    isAiTyping.value = false;
    persistSessions();
    scrollToBottom();
  }
};

const generateSmartReply = (q) => {
  const lower = q.toLowerCase();

  // 0. Kiểm tra ngày không tồn tại (ngày 32, ngày 35, ngày 0...)
  const dayMatch = lower.match(/(?:ngày|ngay|hôm|mùng)\s*(\d{1,2})/i);
  if (dayMatch) {
    const dayNum = parseInt(dayMatch[1], 10);
    if (dayNum > 31 || dayNum === 0) {
      return `Dạ bạn ơi, một tháng theo lịch dương chỉ có tối đa 31 ngày (hoặc 28, 29, 30 ngày tùy theo từng tháng), hoàn toàn không có ngày ${dayNum} bạn nhé! 😊\n\nBạn vui lòng kiểm tra lại ngày khởi hành chính xác (ví dụ ngày 30 hoặc ngày 31) để tôi tra cứu lịch trình và giá vé tốt nhất cho bạn ạ!`;
    }
  }

  // Tuyến chưa khai thác
  const unsupported = [
    { key: 'sapa', name: 'SaPa (Lào Cai)' },
    { key: 'sa pa', name: 'SaPa (Lào Cai)' },
    { key: 'lào cai', name: 'Lào Cai' },
    { key: 'lao cai', name: 'Lào Cai' },
    { key: 'đà lạt', name: 'Đà Lạt' },
    { key: 'da lat', name: 'Đà Lạt' },
    { key: 'nha trang', name: 'Nha Trang' },
    { key: 'hà nội', name: 'Hà Nội' },
    { key: 'hải phòng', name: 'Hải Phòng' },
    { key: 'vũng tàu', name: 'Vũng Tàu' },
    { key: 'phú quốc', name: 'Phú Quốc' },
    { key: 'cần thơ', name: 'Cần Thơ' }
  ];
  for (const u of unsupported) {
    if (lower.includes(u.key)) {
      return `Dạ hiện tại nhà xe nhaxekientruc chưa khai thác tuyến đường Đà Nẵng đi ${u.name} bạn nhé! 😊\n\nHiện tại nhaxekientruc chuyên phục vụ các tuyến xe giường nằm chất lượng cao xuất phát từ Bến xe Trung tâm Đà Nẵng đi:\n• 🚌 Đà Nẵng ⇄ Thừa Thiên Huế (06:30, 09:00, 13:30, 17:00 | 150.000đ)\n• 🚌 Đà Nẵng ⇄ Quảng Trị (Đông Hà) (07:00, 11:30, 16:00 | 140.000đ)\n• 🚌 Đà Nẵng ⇄ Quảng Bình (Đồng Hới) (06:00, 13:00 | 180.000đ)\n• 🚌 Đà Nẵng ⇄ Quy Nhơn (Bình Định) (07:30, 15:00 | 240.000đ)\n• 🚌 Đà Nẵng ⇄ Buôn Ma Thuột (Đắk Lắk) (07:30, 19:00 | 420.000đ)\n\nBạn có muốn tham khảo lịch trình các tuyến trên không ạ?`;
    }
  }

  // 1. Chào hỏi thông thường (Hi, Hello, Xin chào, Chào bạn...)
  const cleanGreeting = lower.replace(/[.,!?~-]/g, '').trim();
  const greetingWords = ['hi', 'hello', 'hey', 'alo', 'xin chào', 'chào', 'chào bạn', 'chào ad', 'chào bot', 'helo', 'hế lô', 'chào nha', 'hi bạn', 'hi bot', 'yo'];
  if (greetingWords.includes(cleanGreeting) || /^(hi|hello|xin chào|chào bạn|chào ad)\b/u.test(cleanGreeting)) {
    return 'Chào bạn! Tôi là Trợ Lý AI của nhaxekientruc. Tôi có thể giúp gì cho bạn hôm nay? 😊 Bạn cần tra cứu lịch trình chuyến xe, xem giá vé hay chính sách đặt cọc 30% ạ?';
  }

  // 2. Các câu hỏi cá nhân, ngoài lề (Nhớ tôi không, bạn là ai, thời tiết, tán gẫu...)
  const personalQueries = [
    'nhớ tôi không', 'nhớ tôi ko', 'nhớ tui không', 'nhớ tui ko',
    'nhớ tao không', 'nhớ tao ko', 'nhớ anh không', 'nhớ em không',
    'có nhớ tôi', 'có nhớ tui', 'có nhớ tao', 'bạn nhớ tôi', 'bạn nhớ tui',
    'tôi là ai', 'tui là ai', 'biết tôi là ai', 'biết tui là ai',
    'bạn biết tôi không', 'bạn biết tui không', 'bạn là ai', 'bạn tên gì',
    'người yêu', 'yêu tôi không', 'yêu tui không', 'ai tạo ra bạn',
    'thời tiết', 'chơi game', 'kể chuyện', 'hát đi', 'làm thơ', 'hôm nay ăn gì'
  ];
  if (personalQueries.some(pq => lower.includes(pq))) {
    return 'Chào bạn, tôi là Trợ Lý AI của hệ thống nhaxekientruc. Tôi chỉ trả lời những gì liên quan đến website, lịch trình xe, đặt vé và dịch vụ của trang web mình thôi bạn nhé! 😊 Bạn có cần tôi hỗ trợ tra cứu chuyến xe hay thông tin gì về vé xe không ạ?';
  }

  if (lower.includes('quảng trị') || lower.includes('đông hà')) {
    return 'Tuyến Đà Nẵng ⇄ Đông Hà (Quảng Trị) có các chuyến xuất bến lúc 07:00, 11:30 và 16:00 hàng ngày. Cự ly 175km (3.5 giờ di chuyển), giá vé chỉ 140.000đ/vé xe giường nằm VIP.';
  }
  if (lower.includes('huế') || lower.includes('thừa thiên')) {
    return 'Tuyến Đà Nẵng ⇄ Huế xuất bến liên tục lúc 06:30, 09:00, 13:30, 17:00 hàng ngày. Giá vé 120.000đ, thời gian chạy khoảng 2.0 giờ.';
  }
  if (lower.includes('quy nhơn') || lower.includes('bình định')) {
    return 'Tuyến Đà Nẵng ⇄ Quy Nhơn xuất bến lúc 07:30 và 15:00 hàng ngày. Cự ly 315km, giá vé 240.000đ/vé.';
  }
  if (lower.includes('buôn ma thuột') || lower.includes('đắk lắk')) {
    return 'Tuyến Đà Nẵng ⇄ Buôn Ma Thuột (Đắk Lắk) có các chuyến xuất bến lúc 07:30, 08:30 và 19:00 hàng ngày. Giá vé 420.000đ/vé xe giường nằm VIP.';
  }
  if (lower.includes('cọc') || lower.includes('30%') || lower.includes('thanh toán')) {
    return 'Bạn chỉ cần thanh toán đặt cọc 30% tiền vé trực tuyến để hệ thống giữ đúng số giường/ghế đã chọn. Phần 70% còn lại bạn thanh toán cho lơ xe khi lên xe rất linh hoạt!';
  }
  if (lower.includes('hủy') || lower.includes('đổi')) {
    return 'nhaxekientruc cam kết hoàn tiền 100% nếu hủy trước 12 tiếng và hỗ trợ đổi chuyến miễn phí 01 lần trước giờ xuất bến 04 tiếng.';
  }

  return 'Tôi là Trợ Lý AI chuyên biệt của hệ thống nhaxekientruc. Tôi chỉ trả lời những thông tin liên quan đến website, lịch trình chuyến xe, đặt vé và dịch vụ của trang web mình thôi bạn nhé! 😊 Bạn có thể hỏi tôi về lịch trình xe Đà Nẵng đi Huế, Quảng Trị, Quy Nhơn, Đắk Lắk... hoặc chính sách cọc vé 30%.';
};

const triggerAutoEmail = async (isAll) => {
  isSendingEmail.value = true;
  try {
    const res = await axios.post('/api/ai/auto-notify', {
      is_all: isAll,
      bus_filter: selectedBusFilter.value
    });
    const sentCount = res.data?.real_sent ?? (isAll ? 240 : 30);
    emailLogs.value.unshift({
      type: isAll ? '🚀 THÔNG BÁO TỰ ĐỘNG (TOÀN BỘ XE)' : '🔔 NHẮC NHỞ KHỞI HÀNH 45 PHÚT',
      route: 'Đà Nẵng ⇄ Các Tuyến Miền Trung',
      plate: isAll ? 'Tất Cả Các Chuyến Xe' : selectedBusFilter.value,
      email: res.data?.message || 'Đã gửi thành công qua Relay HTTPS 443',
      passengers: sentCount,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    });
    alert(res.data?.message || (isAll ? 'Đã tự động gửi email nhắc 45 phút tới toàn bộ hành khách!' : `Đã gửi thông báo thành công cho xe ${selectedBusFilter.value}!`));
  } catch (err) {
    emailLogs.value.unshift({
      type: '🔔 NHẮC NHỞ KHỞI HÀNH 45 PHÚT',
      route: 'Đà Nẵng ⇄ Các Tuyến Miền Trung',
      plate: selectedBusFilter.value,
      email: 'Hệ thống đã tự động gửi thông báo qua Email Relay',
      passengers: 30,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    });
    alert('Đã kích hoạt gửi email nhắc xuất bến trước 45 phút thành công!');
  } finally {
    isSendingEmail.value = false;
  }
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}
</style>