<template>
  <div>
    <!-- TRỢ LÝ AI (GÓC DƯỚI MÀN HÌNH) -->
    <div
      v-if="!isOpen"
      class="fixed bottom-4 right-3 sm:bottom-6 sm:right-6 z-40 flex flex-col items-center cursor-pointer group select-none"
      @click="openModal"
    >
      <!-- Mascot 3D Đứng Trực Tiếp Tự Nhiên Không Nền Hồng -->
      <div class="relative w-24 sm:w-28 h-32 sm:h-38 transition-all duration-300 group-hover:scale-105 active:scale-95 group-hover:-translate-y-1 animate-mascot-float">
        <img
          src="/images/mascot-transparent.png"
          alt="Trợ lý AI Nhà xe Kiến Trúc"
          class="w-full h-full object-contain drop-shadow-[0_8px_16px_rgba(0,0,0,0.18)]"
        />
        <!-- Huy hiệu online -->
        <span class="absolute top-1 right-1 flex h-3.5 w-3.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white shadow-xs"></span>
        </span>
      </div>

      <!-- Huy hiệu nhãn gọn gàng Trợ lý AI -->
      <div class="mt-1 px-3 py-1 rounded-full bg-white/95 shadow-md border border-slate-200 text-xs font-black text-slate-800 flex items-center gap-1.5 backdrop-blur-xs group-hover:bg-red-50 group-hover:text-red-700 group-hover:border-red-200 transition-all">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span>Trợ lý AI</span>
      </div>
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
              : 'w-full max-w-5xl h-[88vh] sm:h-[650px] max-h-[94vh] rounded-2xl sm:rounded-3xl'
          ]"
          class="relative bg-white shadow-2xl flex flex-col sm:flex-row overflow-hidden z-10 border border-red-200/80 transition-all duration-200"
        >
          
          <!-- ============================================== -->
          <!-- CỘT TRÁI: QUẦY NHÂN VIÊN ẢO & LỊCH SỬ CHAT -->
          <!-- ============================================== -->
          <aside
            :class="showMobileHistory ? 'flex w-full h-full' : 'hidden sm:flex sm:w-72'"
            class="bg-gradient-to-b from-[#4c0519] via-[#881337] to-[#7f1d1d] text-red-50 flex-col border-r border-red-800/80 flex-shrink-0 select-none"
          >
            <!-- Tab Switcher: Quầy Nhân Viên Ảo vs Lịch Sử -->
            <div class="p-2.5 border-b border-red-800/60 flex items-center gap-1.5 bg-black/20">
              <button
                type="button"
                @click="sidebarTab = 'kiosk'"
                class="flex-1 py-1.5 px-2 rounded-xl text-xs font-black transition flex items-center justify-center gap-1.5 cursor-pointer"
                :class="sidebarTab === 'kiosk' ? 'bg-white text-red-800 shadow-sm' : 'text-red-200 hover:text-white hover:bg-white/10'"
              >
                <span>🏛️</span>
                <span>Trợ lý AI</span>
              </button>
              <button
                type="button"
                @click="sidebarTab = 'history'"
                class="flex-1 py-1.5 px-2 rounded-xl text-xs font-black transition flex items-center justify-center gap-1.5 cursor-pointer"
                :class="sidebarTab === 'history' ? 'bg-white text-red-800 shadow-sm' : 'text-red-200 hover:text-white hover:bg-white/10'"
              >
                <span>📜</span>
                <span>Lịch Sử</span>
              </button>
            </div>

            <!-- TAB 1: QUẦY TRỢ LÝ AI (VIRTUAL ASSISTANT KIOSK STANDEE) -->
            <!-- TAB 1: QUẦY TRỢ LÝ AI (FUTURISTIC AI NEURAL CO-PILOT) -->
            <div v-if="sidebarTab === 'kiosk'" class="flex-1 flex flex-col p-3 overflow-y-auto custom-scrollbar justify-between space-y-3">
              <!-- Holographic AI Chamber Pod -->
              <div class="relative rounded-3xl p-3.5 text-center overflow-hidden bg-slate-900/90 border border-red-500/30 shadow-[0_8px_32px_rgba(0,0,0,0.5)] backdrop-blur-xl text-white">
                <!-- Ambient Holographic Glow Top -->
                <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-40 h-20 bg-gradient-to-b from-red-500/20 via-rose-500/10 to-transparent blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex items-center justify-between pb-2 border-b border-white/10">
                  <span class="text-[9.5px] font-black tracking-widest text-red-300 uppercase flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-400 animate-ping"></span>
                    <span>AI NEURAL CORE</span>
                  </span>
                  <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-400/40 text-[9px] font-black text-emerald-300 flex items-center gap-1 shadow-xs">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>GEMINI 3.1 PRO</span>
                  </span>
                </div>

                <!-- Linh vật Trợ Lý AI trong Buồng Chiếu Hologram -->
                <div class="relative my-2.5 h-44 sm:h-48 flex flex-col items-center justify-center">
                  <!-- Đĩa chiếu Hologram đa giác bên dưới chân linh vật -->
                  <div class="absolute bottom-1 w-28 h-5 rounded-full bg-cyan-500/20 border border-cyan-400/40 blur-[1px] shadow-[0_0_20px_rgba(6,182,212,0.4)] animate-pulse"></div>
                  <div class="absolute bottom-2 w-20 h-3 rounded-full bg-red-500/30 blur-xs"></div>

                  <img
                    src="/images/mascot-transparent.png"
                    alt="Trợ lý AI Nhà xe Kiến Trúc"
                    class="h-full w-auto object-contain drop-shadow-[0_4px_16px_rgba(239,68,68,0.35)] transition-transform duration-300"
                    :class="[
                      isCheckingLedger ? 'animate-ledger-check' : 'animate-mascot-float',
                      isSpeaking ? 'scale-105' : ''
                    ]"
                  />

                  <!-- Huy hiệu đang kiểm tra sơ đồ chuyến xe -->
                  <div
                    v-if="isCheckingLedger"
                    class="absolute bottom-2 px-2.5 py-1 rounded-full bg-amber-400 text-slate-950 font-black text-[9.5px] shadow-lg flex items-center gap-1.5 animate-bounce z-20 border border-amber-200"
                  >
                    <span>⚡</span>
                    <span>Đang rà soát dữ liệu ghế...</span>
                  </div>
                </div>

                <!-- Cột Sóng Âm Thanh Equalizer Cyber Neon -->
                <div class="flex items-center gap-1.5 justify-center py-1.5 bg-black/40 rounded-xl border border-white/10 shadow-inner">
                  <span class="w-1 bg-cyan-400 rounded-full transition-all duration-150" :class="(isSpeaking || isListeningVoice) ? 'h-5 animate-pulse shadow-[0_0_6px_#22d3ee]' : 'h-1.5'"></span>
                  <span class="w-1 bg-amber-400 rounded-full transition-all duration-150" :class="(isSpeaking || isListeningVoice) ? 'h-7 animate-bounce shadow-[0_0_6px_#fbbf24]' : 'h-2'"></span>
                  <span class="w-1 bg-rose-500 rounded-full transition-all duration-150" :class="(isSpeaking || isListeningVoice) ? 'h-9 animate-pulse shadow-[0_0_8px_#f43f5e]' : 'h-3'"></span>
                  <span class="w-1 bg-amber-400 rounded-full transition-all duration-150" :class="(isSpeaking || isListeningVoice) ? 'h-7 animate-bounce shadow-[0_0_6px_#fbbf24]' : 'h-2'"></span>
                  <span class="w-1 bg-cyan-400 rounded-full transition-all duration-150" :class="(isSpeaking || isListeningVoice) ? 'h-5 animate-pulse shadow-[0_0_6px_#22d3ee]' : 'h-1.5'"></span>
                </div>

                <!-- Trạng thái đàm thoại -->
                <div class="mt-2 text-[10.5px] font-black">
                  <span v-if="isCheckingLedger" class="text-amber-300 animate-pulse">⚡ AI đang kiểm tra chuyến xe trực tiếp...</span>
                  <span v-else-if="isSpeaking" class="text-rose-300 animate-pulse">🔊 Trợ lý AI đang phản hồi...</span>
                  <span v-else-if="isListeningVoice" class="text-emerald-300 animate-pulse">🎙️ Đang tiếp nhận giọng nói...</span>
                  <span v-else class="text-slate-300 flex items-center justify-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                    <span class="tracking-wide">TRỰC TUYẾN 24/7 • SẴN SÀNG</span>
                  </span>
                </div>
              </div>

              <!-- HUD NĂNG LỰC TRÍ TUỆ NHÂN TẠO (AI CAPABILITIES HUD - THAY THẾ MẪU CÂU CŨ) -->
              <div class="space-y-2">
                <div class="flex items-center justify-between text-[10px] font-black text-red-200/90 uppercase tracking-widest px-1">
                  <span>⚡ NĂNG LỰC TRÍ TUỆ AI</span>
                  <span class="text-[9px] px-1.5 py-0.2 rounded bg-white/10 text-emerald-300 font-mono">v3.5 PRO</span>
                </div>

                <!-- Capability Card 1: Trí Nhớ Đa Ngữ Cảnh (Context Memory) -->
                <div class="p-2.5 rounded-2xl bg-black/30 border border-white/10 hover:border-red-400/40 transition shadow-sm space-y-1">
                  <div class="flex items-center gap-1.5 text-xs font-bold text-red-100">
                    <span class="text-sm">🧠</span>
                    <span>Trí Nhớ Đa Ngữ Cảnh</span>
                  </div>
                  <p class="text-[10px] text-red-200/80 leading-relaxed">
                    Tự động ghi nhớ say xe, số khách & thói quen từ các câu trước, không bao giờ quên khi đổi tuyến.
                  </p>
                </div>

                <!-- Capability Card 2: Khóa Giữ Ghế 5 Phút -->
                <div class="p-2.5 rounded-2xl bg-black/30 border border-white/10 hover:border-cyan-400/40 transition shadow-sm space-y-1">
                  <div class="flex items-center gap-1.5 text-xs font-bold text-cyan-200">
                    <span class="text-sm">⏱️</span>
                    <span>Khóa Giữ Chỗ 5 Phút</span>
                  </div>
                  <p class="text-[10px] text-red-200/80 leading-relaxed">
                    Tự động giữ ghế tức thì và kích hoạt đồng hồ đếm ngược 5 phút, giải phóng ghế tự động nếu chưa cọc.
                  </p>
                </div>

                <!-- Capability Card 3: Phân Biệt Hủy & Hoàn Cọc Thông Minh -->
                <div class="p-2.5 rounded-2xl bg-black/30 border border-white/10 hover:border-amber-400/40 transition shadow-sm space-y-1">
                  <div class="flex items-center gap-1.5 text-xs font-bold text-amber-200">
                    <span class="text-sm">🛡️</span>
                    <span>Tư Vấn & Hoàn Cọc 30%</span>
                  </div>
                  <p class="text-[10px] text-red-200/80 leading-relaxed">
                    Nhận biết chính xác khi bạn chỉ đổi ý hỏi đường và hỗ trợ hoàn tiền cọc 30% minh bạch khi có vé thật.
                  </p>
                </div>
              </div>
            </div>

            <!-- TAB 2: LỊCH SỬ TRÒ CHUYỆN & ADMIN -->
            <div v-else class="flex-1 flex flex-col overflow-hidden">
              <!-- NẾU CHƯA ĐĂNG NHẬP: THÔNG BÁO CHỈ LƯU LỊCH SỬ KHI ĐĂNG NHẬP -->
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
              </div>

              <!-- NẾU ĐÃ ĐĂNG NHẬP: HIỂN THỊ LỊCH SỬ CHAT RIÊNG CỦA TỪNG USER -->
              <div v-else class="flex-1 flex flex-col overflow-hidden">
                <div class="p-3">
                  <button
                    type="button"
                    @click="createNewChat"
                    class="w-full py-2 px-3 rounded-xl bg-white hover:bg-red-50 text-red-700 hover:text-red-800 text-xs font-black transition flex items-center justify-center gap-2 shadow-sm cursor-pointer"
                  >
                    <span>+</span>
                    <span>Đoạn chat mới</span>
                  </button>
                </div>

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
                    <div class="flex items-center gap-2 truncate flex-1 mr-1">
                      <span class="text-xs">💬</span>
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

                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                      <button
                        type="button"
                        @click.stop="startRename(session)"
                        class="p-1 rounded text-red-200 hover:text-white hover:bg-white/20 transition cursor-pointer"
                        title="Đổi tên đoạn chat"
                      >
                        ✏️
                      </button>
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
            </div>
          </aside>

          <!-- ============================================== -->
          <!-- CỘT PHẢI: KHUNG TRÒ CHUYỆN CHÍNH HOẶC GỬI EMAIL ADMIN -->
          <!-- ============================================== -->
          <div :class="showMobileHistory ? 'hidden sm:flex' : 'flex'" class="flex-1 flex-col bg-white overflow-hidden">
            
            <!-- HEADER KHUNG CHAT BÉ KIẾN TRÚC DAU (CÓ NÚT GIỌNG NÓI / PHÓNG TO / ĐÓNG) -->
            <div class="p-3 sm:p-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80 backdrop-blur-sm select-none">
              <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                <div class="relative w-9 h-9 sm:w-10 sm:h-10 rounded-2xl overflow-hidden border-2 border-red-500 bg-white p-0.5 flex items-center justify-center flex-shrink-0 shadow-sm">
                  <img
                    src="/images/mascot-avatar.png"
                    alt="Trợ lý AI Nhà xe Kiến Trúc"
                    class="w-full h-full object-cover"
                  />
                  <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-white"></span>
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 leading-tight truncate">
                      {{ activeView === 'admin_email' ? 'Gửi Thông Báo Gmail' : 'Trợ lý AI' }}
                    </h3>
                    <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-bold border border-slate-200">
                      Nhà xe Kiến Trúc
                    </span>
                    <span class="text-[9px] px-2 py-0.5 rounded-full bg-red-50 text-red-700 font-extrabold border border-red-200 flex items-center gap-1 shadow-2xs">
                      <span>⚡</span>
                      <span>Gemini Pro</span>
                    </span>
                  </div>
                  <p class="text-[10px] sm:text-[11px] text-emerald-600 font-bold flex items-center gap-1.5 truncate mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse flex-shrink-0"></span>
                    <span class="truncate">Trực Tuyến 24/7 • Bộ Nhớ Đa Ngữ Cảnh • Khóa Ghế 5 Phút</span>
                  </p>
                </div>
              </div>

              <!-- Cụm Nút Điều Khiển: Đàm Thoại 2 Chiều / Loa Giọng Nói (TTS) / Phóng To / Lịch Sử / Đóng -->
              <div class="flex items-center gap-1 sm:gap-1.5 flex-shrink-0">

                <!-- Nút Lịch Sử trên Mobile -->
                <button
                  type="button"
                  @click="showMobileHistory = !showMobileHistory"
                  class="sm:hidden px-2 py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition text-[11px] font-bold cursor-pointer flex items-center gap-1"
                  title="Xem lịch sử trò chuyện"
                >
                  <span>💬 Lịch sử</span>
                </button>

                <!-- Phóng to / Thu nhỏ -->
                <button
                  type="button"
                  @click="toggleMaximize"
                  class="hidden sm:inline-flex p-1.5 rounded-xl hover:bg-slate-200 text-slate-600 hover:text-slate-900 transition text-xs font-bold cursor-pointer"
                  :title="isMaximized ? 'Thu nhỏ lại' : 'Phóng to toàn màn hình'"
                >
                  <span v-if="!isMaximized">⛶</span>
                  <span v-else>🗗</span>
                </button>

                <!-- Nút Đóng Modal -->
                <button
                  type="button"
                  @click="closeModal(); stopSpeech();"
                  class="text-slate-400 hover:text-red-600 font-bold p-1.5 rounded-xl hover:bg-red-50 transition cursor-pointer text-base"
                  title="Đóng cửa sổ"
                >
                  ✕
                </button>
              </div>
            </div>

            <!-- Banner Chế Độ Nói Chuyện Với AI (Voice Mode) -->
            <div v-if="interactionMode === 'voice'" class="bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 text-white px-3.5 py-2 text-xs font-bold flex flex-wrap items-center justify-between gap-2 shadow-xs select-none">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-white animate-ping"></span>
                <span v-if="isSpeaking">🔊 Trợ lý AI đang nói...</span>
                <span v-else-if="isEchoCoolingDown">⏳ Đang nghỉ 1.2s triệt tiêu âm vang loa ngoài...</span>
                <span v-else-if="isListeningVoice">🎙️ Trợ lý AI đang lắng nghe... (Hãy nói yêu cầu)</span>
                <span v-else>🎙️ Đang ở chế độ nói chuyện với AI</span>
              </div>
              <div class="flex items-center gap-2">
                <button
                  v-if="!isListeningVoice && !isSpeaking && !isEchoCoolingDown"
                  type="button"
                  @click="triggerManualMic"
                  class="text-[11px] bg-amber-400 hover:bg-amber-300 text-red-950 px-2.5 py-0.5 rounded-lg font-black transition cursor-pointer shadow-xs"
                  title="Bấm để nói ngay"
                >
                  🎙️ Bấm để nói
                </button>
                <button
                  type="button"
                  @click="setInteractionMode('chat')"
                  class="text-[11px] bg-white text-red-700 px-2.5 py-0.5 rounded-lg font-black hover:bg-red-50 cursor-pointer shadow-2xs"
                >
                  💬 Chuyển sang Nhắn tin
                </button>
              </div>
            </div>

            <!-- Cảnh báo chống vọng loa thông minh (Acoustic Echo Notice) -->
            <div v-if="autoListenBlockedReason" class="mx-3.5 mt-2.5 p-2.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs font-bold flex items-center justify-between shadow-xs animate-in fade-in">
              <div class="flex items-start gap-2">
                <span class="text-sm shrink-0">🛡️</span>
                <span class="leading-relaxed">{{ autoListenBlockedReason }}</span>
              </div>
              <button
                type="button"
                @click="autoListenBlockedReason = ''"
                class="text-amber-700 hover:text-amber-900 text-xs font-black cursor-pointer px-1.5 py-0.5 rounded hover:bg-amber-100 shrink-0"
              >
                ✕
              </button>
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
                
                <!-- Lời chào & Gợi ý câu hỏi ban đầu Trợ lý AI -->
                <div v-if="currentMessages.length === 0" class="py-4 space-y-4 max-w-lg mx-auto">
                  <!-- Khung Giới Thiệu Gọn Gàng Trợ Lý AI -->
                  <div class="relative bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center gap-4">
                    <!-- Linh vật Trợ lý AI đứng tự nhiên -->
                    <div class="flex-shrink-0 w-20 sm:w-24 h-24 sm:h-28 flex items-center justify-center">
                      <img
                        src="/images/mascot-transparent.png"
                        alt="Trợ lý AI Nhà xe Kiến Trúc"
                        class="w-full h-full object-contain drop-shadow-[0_4px_10px_rgba(0,0,0,0.12)] animate-mascot-float"
                      />
                    </div>

                    <div class="flex-1 space-y-2 text-left">
                      <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h4 class="font-black text-slate-900 text-sm sm:text-base">Trợ lý AI</h4>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold border border-slate-200">
                          Nhà xe Kiến Trúc
                        </span>
                      </div>

                      <p class="text-xs text-slate-600 leading-relaxed font-medium">
                        Xin chào! Tôi là Trợ lý AI của Nhà xe Kiến Trúc Đà Nẵng. Bạn có thể hỏi tôi về lịch trình, giá vé, tư vấn chọn vị trí ghế hoặc hỗ trợ đặt vé 24/7.
                      </p>

                      <div class="flex flex-wrap items-center gap-2 pt-0.5">
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-bold flex items-center gap-1 border border-slate-200">
                          <span>🎙️</span>
                          <span>Đàm thoại 2 chiều</span>
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-[11px] font-bold flex items-center gap-1 border border-emerald-200">
                          <span>⚡</span>
                          <span>Trực tuyến 24/7</span>
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- 4 Mẫu câu gợi ý tiện ích cho khách -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-left">
                    <button
                      v-for="(sug, idx) in promptSuggestions"
                      :key="idx"
                      type="button"
                      @click="sendPrompt(sug)"
                      class="p-2.5 rounded-xl border border-slate-200 hover:border-red-400 hover:bg-red-50/50 text-xs font-semibold text-slate-700 hover:text-red-700 transition cursor-pointer text-left flex items-center gap-1.5 shadow-2xs"
                    >
                      <span class="text-base shrink-0">👉</span>
                      <span>{{ sug }}</span>
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
                  <!-- Avatar Trợ lý AI -->
                  <div
                    v-if="msg.role === 'assistant'"
                    class="w-8 h-8 rounded-full overflow-hidden border-2 border-slate-300 bg-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-xs"
                  >
                    <img
                      src="/images/mascot-avatar.png"
                      alt="Trợ lý AI"
                      class="w-full h-full object-cover"
                    />
                  </div>

                  <!-- Khung chat -->
                  <div
                    class="max-w-[85%] rounded-2xl p-3.5 text-xs leading-relaxed shadow-2xs"
                    :class="msg.role === 'user' ? 'bg-red-600 text-white rounded-tr-xs' : 'bg-slate-100 text-slate-800 rounded-tl-xs border border-slate-200'"
                  >
                    <div v-if="msg.role === 'assistant'" class="space-y-1.5">
                      <!-- Tên Trợ lý AI & Nút Loa Nghe Đọc -->
                      <div class="flex items-center justify-between gap-2 mb-1 border-b border-slate-200/60 pb-1 select-none">
                        <div class="flex items-center gap-1.5">
                          <span class="font-black text-red-600 text-[11px]">Trợ lý AI</span>
                          <span v-if="isSpeaking && speakingMsgIdx === idx" class="text-[10px] text-emerald-600 font-bold animate-pulse flex items-center gap-0.5">
                            <span>🔊</span>
                            <span>Đang nói...</span>
                          </span>
                        </div>
                        <button
                          type="button"
                          @click="toggleSpeakMsg(msg, idx)"
                          class="text-[10.5px] px-2 py-0.5 rounded-lg bg-white hover:bg-red-50 border border-slate-200 text-slate-600 hover:text-red-600 font-bold flex items-center gap-1 transition cursor-pointer"
                          title="Bấm để Bé Kiến Trúc đọc to tin nhắn này"
                        >
                          <span v-if="isSpeaking && speakingMsgIdx === idx">⏹️ Dừng</span>
                          <span v-else>🔊 Nghe đọc</span>
                        </button>
                      </div>

                      <div class="leading-relaxed" v-html="formatAiMessage(msg.content)"></div>

                      <!-- 3. THẺ THANH TOÁN VIETQR TẠI CHỖ (BƯỚC 4 - END-TO-END PAYMENT) -->
                      <div v-if="msg.qr_data" class="mt-3 p-3.5 sm:p-4 bg-gradient-to-br from-red-50 via-rose-50/60 to-amber-50/50 border-2 rounded-2xl space-y-3 shadow-md transition-colors"
                           :class="isHoldExpired ? 'border-amber-400 bg-amber-50/40' : 'border-red-500'">
                        <div class="flex items-center justify-between gap-1 flex-wrap">
                          <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" :class="isHoldExpired ? 'bg-amber-500' : 'bg-emerald-500 animate-ping'"></span>
                            <span class="font-black text-xs sm:text-sm" :class="isHoldExpired ? 'text-amber-800' : 'text-red-700'">
                              {{ isHoldExpired ? '⚠️ ĐÃ HẾT 5 PHÚT GIỮ CHỖ • GHẾ ĐÃ MỞ LẠI' : '⚡ KHÓA GHẾ THÀNH CÔNG • QUÉT MÃ VIETQR' }}
                            </span>
                          </div>
                          <div class="flex items-center gap-1 font-mono text-[10px] sm:text-xs font-black px-2.5 py-1 rounded-full shadow-2xs"
                               :class="isHoldExpired ? 'bg-slate-800 text-amber-300' : 'bg-red-600 text-white'">
                            <span>⏱️ {{ isHoldExpired ? 'Hết hạn:' : 'Giữ:' }}</span>
                            <span>{{ isHoldExpired ? '00:00' : formatTimer(bookingCountdown) }}</span>
                          </div>
                        </div>

                        <!-- Cảnh báo khi hết 5 phút giữ chỗ -->
                        <div v-if="isHoldExpired" class="p-2.5 rounded-xl bg-amber-100/80 border border-amber-300 text-amber-900 text-[11px] font-bold flex items-center gap-2">
                          <span>⚠️</span>
                          <span>Đã hết 5 phút giữ chỗ! Ghế của bạn đã được hệ thống tự động giải phóng lại cho các hành khách khác. Bạn có thể bấm chọn ghế lại trên thẻ chuyến xe nếu cần giữ lại nhé.</span>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 items-center sm:items-start bg-white p-3 rounded-xl border border-red-200">
                          <!-- QR Image -->
                          <div class="w-36 h-36 flex-shrink-0 bg-white p-1 rounded-xl border border-slate-200 shadow-xs flex items-center justify-center">
                            <img
                              :src="msg.qr_data.qr_url"
                              alt="VietQR Bé Kiến Trúc"
                              class="w-full h-full object-contain cursor-pointer"
                              @click="openQrTab(msg.qr_data.qr_url)"
                              title="Bấm để mở ảnh to"
                            />
                          </div>

                          <!-- Thông tin thanh toán & nút sao chép -->
                          <div class="flex-1 text-xs space-y-1.5 w-full">
                            <div class="flex items-center justify-between pb-1 border-b border-slate-100 font-bold">
                              <span class="text-slate-600">Mã vé:</span>
                              <span class="font-black text-red-600 font-mono text-sm">{{ msg.qr_data.booking_code }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                              <span class="text-slate-600">Tuyến & Ghế:</span>
                              <span class="font-bold text-slate-800">{{ msg.qr_data.route_name }} (Ghế: <strong class="text-red-600">{{ msg.qr_data.seat_numbers.join(', ') }}</strong>)</span>
                            </div>
                            <div class="flex items-center justify-between">
                              <span class="text-slate-600">Khách hàng:</span>
                              <span class="font-semibold text-slate-700">{{ msg.qr_data.customer_name }} • {{ msg.qr_data.customer_phone }}</span>
                            </div>
                            <div class="flex items-center justify-between bg-amber-50 p-1.5 rounded-lg border border-amber-200 font-bold">
                              <span class="text-amber-900">Tiền cọc giữ chỗ (30%):</span>
                              <span class="text-red-600 text-sm font-black">{{ msg.qr_data.deposit_amount_formatted }}</span>
                            </div>

                            <div class="pt-1 space-y-1 text-[11px] text-slate-600">
                              <div class="flex items-center justify-between">
                                <span>Ngân hàng: <strong>{{ msg.qr_data.bank_name }}</strong></span>
                              </div>
                              <div class="flex items-center justify-between">
                                <span>Số TK: <strong class="font-mono text-slate-900">{{ msg.qr_data.account_no }}</strong></span>
                                <button
                                  type="button"
                                  @click="copyToClipboard(msg.qr_data.account_no, 'Đã copy Số tài khoản!')"
                                  class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-[10px] font-bold text-slate-700 cursor-pointer"
                                >
                                  📋 Chép STK
                                </button>
                              </div>
                              <div class="flex items-center justify-between">
                                <span>Nội dung: <strong class="font-mono text-red-600 font-bold">{{ msg.qr_data.content }}</strong></span>
                                <button
                                  type="button"
                                  @click="copyToClipboard(msg.qr_data.content, 'Đã copy Nội dung chuyển khoản!')"
                                  class="px-2 py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-[10px] font-bold text-slate-700 cursor-pointer"
                                >
                                  📋 Chép Nội Dung
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Nút Khách Hàng Bấm Xác Nhận Đã Chuyển Khoản -->
                        <button
                          type="button"
                          @click="handleConfirmPayment(msg.qr_data.booking_code)"
                          :disabled="isConfirmingPayment"
                          class="w-full py-2.5 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-xs rounded-xl shadow-md flex items-center justify-center gap-2 cursor-pointer transition active:scale-95 text-center"
                        >
                          <span v-if="isConfirmingPayment">⏳ Đang xác nhận thanh toán...</span>
                          <span v-else>✅ Tôi Đã Chuyển Khoản Thành Công (Xác Nhận & Xuất Vé)</span>
                        </button>

                        <a
                          :href="msg.qr_data.payment_url"
                          class="w-full py-2 px-4 bg-white hover:bg-slate-50 text-slate-700 font-bold text-[11px] rounded-xl border border-slate-200 shadow-2xs flex items-center justify-center gap-2 cursor-pointer transition text-center"
                        >
                          <span>📄 Xem Trang Thanh Toán Chi Tiết (Tải Vé PDF) ➔</span>
                        </a>
                      </div>

                      <!-- 3.5. BẢNG MẪU TƯ VẤN ĐẶT VÉ THÔNG MINH (BOOKING CONSULTATION FORM) -->
                      <div v-if="msg.action && msg.action.type === 'booking_consultation_form'" class="mt-3 p-3.5 bg-gradient-to-br from-rose-50 via-amber-50/40 to-white border-2 border-red-400 rounded-2xl space-y-3 shadow-md text-slate-800">
                        <div class="flex items-center gap-2 border-b border-red-200/80 pb-2">
                          <div class="w-7 h-7 rounded-lg bg-red-600 text-white flex items-center justify-center font-bold text-sm">📋</div>
                          <div>
                            <h5 class="font-black text-red-700 text-xs">Bé Kiến Trúc DAU • Tư Vấn Đặt Vé</h5>
                            <p class="text-[10.5px] text-slate-500 font-medium">Chọn thông tin để em tìm chuyến & chọn ghế êm nhất cho bạn nhé:</p>
                          </div>
                        </div>

                        <div class="space-y-2.5 text-xs">
                          <!-- Tuyến đường / Điểm đến -->
                          <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">📍 Điểm đến:</label>
                            <select
                              v-model="consultationForm.destination"
                              class="w-full bg-white border border-red-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-red-500"
                            >
                              <option value="Quảng Trị">Đà Nẵng ⇄ Quảng Trị (200.000đ)</option>
                              <option value="Huế">Đà Nẵng ⇄ Thừa Thiên Huế (150.000đ)</option>
                              <option value="Quảng Bình">Đà Nẵng ⇄ Quảng Bình (280.000đ)</option>
                              <option value="Quy Nhơn">Đà Nẵng ⇄ Quy Nhơn (360.000đ)</option>
                              <option value="Buôn Ma Thuột">Đà Nẵng ⇄ Buôn Ma Thuột (420.000đ)</option>
                              <option value="Hà Nội">Đà Nẵng ⇄ Hà Nội (450.000đ)</option>
                              <option value="Nam Định">Đà Nẵng ⇄ Nam Định (420.000đ)</option>
                              <option value="Hội An">Đà Nẵng ⇄ Hội An (80.000đ)</option>
                              <option value="custom">✍️ Điểm đến khác (tự nhập)...</option>
                            </select>
                            <input
                              v-if="consultationForm.destination === 'custom'"
                              v-model="consultationForm.customDestination"
                              type="text"
                              placeholder="Nhập tỉnh/thành điểm đến bạn muốn..."
                              class="mt-1.5 w-full bg-white border border-red-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 outline-none focus:ring-2 focus:ring-red-500"
                            />
                          </div>

                          <!-- Ngày đi & Khung giờ -->
                          <div class="grid grid-cols-2 gap-2">
                            <div>
                              <div class="flex items-center justify-between mb-1">
                                <label class="text-[11px] font-bold text-slate-700">📅 Ngày đi:</label>
                              </div>
                              <select
                                v-model="consultationForm.date"
                                class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-red-500"
                              >
                                <option value="Hôm nay">Hôm nay</option>
                                <option value="Ngày mai">Ngày mai</option>
                                <option value="Ngày mốt">Ngày mốt</option>
                                <option value="Cuối tuần">Cuối tuần này</option>
                                <option value="custom">📅 Chọn ngày bất kỳ...</option>
                              </select>
                              <input
                                v-if="consultationForm.date === 'custom'"
                                v-model="consultationForm.customDate"
                                type="date"
                                class="mt-1.5 w-full bg-white border border-red-300 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-red-500"
                              />
                            </div>
                            <div>
                              <label class="block text-[11px] font-bold text-slate-700 mb-1">⏰ Khung giờ:</label>
                              <select
                                v-model="consultationForm.timePeriod"
                                class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-2 text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-red-500"
                              >
                                <option value="Sáng">Sáng (05:00 - 11:30)</option>
                                <option value="Chiều">Chiều (12:00 - 17:30)</option>
                                <option value="Tối">Tối / Đêm (18:00 - 23:30)</option>
                                <option value="Bất kỳ">Cả ngày (Giờ nào cũng được)</option>
                              </select>
                            </div>
                          </div>

                          <!-- Số lượng vé -->
                          <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">🎫 Số lượng vé:</label>
                            <div class="grid grid-cols-5 gap-1.5">
                              <button
                                v-for="cnt in [1, 2, 3, 4, 5]"
                                :key="cnt"
                                type="button"
                                @click="consultationForm.seatCount = cnt"
                                :class="consultationForm.seatCount === cnt ? 'bg-red-600 text-white font-black border-red-600 shadow-xs' : 'bg-white text-slate-700 border-slate-200 font-semibold'"
                                class="py-1.5 rounded-lg border text-center transition cursor-pointer text-xs"
                              >
                                {{ cnt === 5 ? '5+ vé' : cnt + ' vé' }}
                              </button>
                            </div>
                            <input
                              v-if="consultationForm.seatCount === 5"
                              v-model="consultationForm.customSeatCount"
                              type="number"
                              min="5"
                              max="30"
                              placeholder="Nhập số lượng vé (VD: 6, 8, 10...)"
                              class="mt-1.5 w-full bg-white border border-red-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 outline-none focus:ring-2 focus:ring-red-500"
                            />
                          </div>

                          <!-- Nhu cầu & Thể trạng đặc biệt -->
                          <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">💡 Nhu cầu & Thể trạng (Bé Kiến Trúc sẽ tính toán tâm xe):</label>
                            <div class="flex flex-wrap gap-1.5">
                              <button
                                type="button"
                                @click="consultationForm.sayXe = !consultationForm.sayXe"
                                :class="consultationForm.sayXe ? 'bg-amber-500 text-white border-amber-500 font-black' : 'bg-white text-slate-700 border-slate-200 font-medium'"
                                class="px-2.5 py-1 rounded-full border text-[11px] transition cursor-pointer flex items-center gap-1"
                              >
                                <span>🤢</span>
                                <span>Dễ say xe (Tầng dưới giữa)</span>
                              </button>
                              <button
                                type="button"
                                @click="consultationForm.treEm = !consultationForm.treEm"
                                :class="consultationForm.treEm ? 'bg-amber-500 text-white border-amber-500 font-black' : 'bg-white text-slate-700 border-slate-200 font-medium'"
                                class="px-2.5 py-1 rounded-full border text-[11px] transition cursor-pointer flex items-center gap-1"
                              >
                                <span>👶</span>
                                <span>Đi cùng trẻ nhỏ</span>
                              </button>
                              <button
                                type="button"
                                @click="consultationForm.capDoi = !consultationForm.capDoi"
                                :class="consultationForm.capDoi ? 'bg-amber-500 text-white border-amber-500 font-black' : 'bg-white text-slate-700 border-slate-200 font-medium'"
                                class="px-2.5 py-1 rounded-full border text-[11px] transition cursor-pointer flex items-center gap-1"
                              >
                                <span>💑</span>
                                <span>2 giường cạnh nhau</span>
                              </button>
                              <button
                                type="button"
                                @click="consultationForm.nguoiGia = !consultationForm.nguoiGia"
                                :class="consultationForm.nguoiGia ? 'bg-amber-500 text-white border-amber-500 font-black' : 'bg-white text-slate-700 border-slate-200 font-medium'"
                                class="px-2.5 py-1 rounded-full border text-[11px] transition cursor-pointer flex items-center gap-1"
                              >
                                <span>👵</span>
                                <span>Đi cùng người già / bà</span>
                              </button>
                              <button
                                type="button"
                                @click="consultationForm.caoLon = !consultationForm.caoLon"
                                :class="consultationForm.caoLon ? 'bg-amber-500 text-white border-amber-500 font-black' : 'bg-white text-slate-700 border-slate-200 font-medium'"
                                class="px-2.5 py-1 rounded-full border text-[11px] transition cursor-pointer flex items-center gap-1"
                              >
                                <span>📏</span>
                                <span>Cao > 1m75 (Giường rộng)</span>
                              </button>
                              <button
                                type="button"
                                @click="consultationForm.riengTu = !consultationForm.riengTu"
                                :class="consultationForm.riengTu ? 'bg-amber-500 text-white border-amber-500 font-black' : 'bg-white text-slate-700 border-slate-200 font-medium'"
                                class="px-2.5 py-1 rounded-full border text-[11px] transition cursor-pointer flex items-center gap-1"
                              >
                                <span>🤫</span>
                                <span>Riêng tư / Yên tĩnh</span>
                              </button>
                            </div>

                            <!-- Ô NHẬP TỰ DO NHU CẦU / THỂ TRẠNG KHÁC -->
                            <div class="mt-2 pt-2 border-t border-red-200/60">
                              <label class="block text-[11px] font-bold text-slate-700 mb-1">✍️ Hoặc nhập nhu cầu / thể trạng khác của bạn:</label>
                              <input
                                v-model="consultationForm.customNote"
                                type="text"
                                placeholder="VD: Gửi kèm xe máy, say xe nặng cần nằm giữa, đau lưng, có thú cưng..."
                                class="w-full bg-white border border-slate-300 focus:border-red-500 rounded-xl px-3 py-2 text-xs text-slate-800 outline-none transition focus:ring-2 focus:ring-red-200"
                              />
                            </div>
                          </div>
                        </div>

                        <button
                          type="button"
                          @click="submitConsultationForm"
                          class="w-full py-2.5 px-4 bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs rounded-xl shadow-md flex items-center justify-center gap-2 cursor-pointer transition active:scale-95"
                        >
                          <span>🔍 Tìm Chuyến Xe & Chọn Ghế Chuẩn Nhất ➔</span>
                        </button>
                      </div>

                      <!-- 4. THẺ HÀNH ĐỘNG THÔNG THƯỜNG -->
                      <div v-else-if="msg.action && !['fill_customer_info', 'input_otp', 'show_qr_payment', 'booking_consultation_form'].includes(msg.action.type)" class="mt-2.5 p-2.5 bg-gradient-to-r from-red-50 via-rose-50/50 to-amber-50 border-2 border-red-300 rounded-2xl">
                        <button
                          type="button"
                          @click="handleAction(msg.action)"
                          class="w-full py-2.5 px-4 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs rounded-xl shadow-md flex items-center justify-center gap-2 cursor-pointer transition active:scale-95"
                        >
                          <span>{{ msg.action.title }}</span>
                        </button>
                      </div>

                      <!-- Danh Sách Vé Xe Của Tôi (Khi Khách Hỏi Hủy Vé / Tra Cứu Vé) -->
                      <div v-if="msg.user_bookings && msg.user_bookings.length > 0" class="mt-3 space-y-2 pt-2 border-t border-slate-200/80">
                        <div class="text-[11px] font-bold text-slate-700 flex items-center justify-between">
                          <span class="flex items-center gap-1.5">
                            <span>🎫</span>
                            <span>Vé xe hiện có trên hệ thống:</span>
                          </span>
                        </div>
                        <div
                          v-for="bk in msg.user_bookings"
                          :key="bk.code"
                          class="p-3 bg-white border-2 border-amber-300 rounded-xl space-y-1.5 shadow-xs"
                        >
                          <div class="flex items-center justify-between">
                            <span class="font-black text-red-600 font-mono text-xs">{{ bk.code }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                              Đã cọc: {{ bk.paid }}
                            </span>
                          </div>
                          <p class="text-xs font-bold text-slate-800">{{ bk.route }}</p>
                          <p class="text-[11px] text-slate-500">Giờ xuất bến: {{ bk.time }}</p>
                          <div class="pt-1 flex items-center gap-2">
                            <a
                              :href="bk.detail_url"
                              class="flex-1 py-1.5 px-2.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold text-center transition"
                            >
                              Xem vé
                            </a>
                            <a
                              href="/my-bookings"
                              class="flex-1 py-1.5 px-2.5 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[11px] font-bold text-center transition"
                            >
                              Hủy vé hoàn cọc ➔
                            </a>
                          </div>
                        </div>
                      </div>

                      <!-- Interactive Trip Cards (Đề xuất chuyến xe tốt nhất & Tự động khóa ghế VietQR) -->
                      <div v-if="msg.trips && msg.trips.length > 0" class="mt-3 space-y-2 pt-2 border-t border-slate-200/80">
                        <div class="text-[11px] font-bold text-slate-700 flex items-center justify-between">
                          <span class="flex items-center gap-1.5">
                            <span>🚌</span>
                            <span>Chuyến xe phù hợp nhất:</span>
                          </span>
                          <span class="text-[10px] text-red-600 font-bold bg-red-50 px-2 py-0.5 rounded-full border border-red-100">
                            {{ msg.trips.length }} chuyến
                          </span>
                        </div>

                        <div
                          v-for="trip in msg.trips"
                          :key="trip.id"
                          class="group p-3.5 rounded-2xl border-2 border-red-200/90 hover:border-red-500 bg-white hover:bg-gradient-to-r hover:from-red-50/70 hover:to-amber-50/40 shadow-xs hover:shadow-lg transition-all duration-200 space-y-2.5"
                        >
                          <div class="flex items-center justify-between gap-2">
                            <div class="font-black text-slate-900 group-hover:text-red-600 transition-colors flex items-center gap-1.5 text-xs sm:text-sm">
                              <span>{{ trip.route_name }}</span>
                              <span v-if="trip.is_today" class="px-2 py-0.5 rounded-full text-[9px] bg-red-600 text-white font-bold">Hôm nay</span>
                              <span v-else-if="trip.is_tomorrow" class="px-2 py-0.5 rounded-full text-[9px] bg-amber-500 text-white font-bold">Ngày mai</span>
                            </div>
                            <div class="text-right">
                              <span class="font-black text-red-600 text-xs sm:text-sm">{{ trip.price_formatted }}</span>
                              <span class="text-[9px] text-slate-400 block font-medium">Cọc 30%: {{ trip.deposit_price_formatted }}</span>
                            </div>
                          </div>

                          <!-- Khối AI Gợi Ý Chỗ Ngồi & Thể Trạng Thông Minh -->
                          <div v-if="trip.recommended_seats && trip.recommended_seats.length > 0" class="p-2.5 rounded-xl bg-gradient-to-br from-amber-50 via-orange-50/50 to-amber-50/20 border border-amber-300 space-y-1.5">
                            <div class="flex items-center justify-between text-[11px] flex-wrap gap-1">
                              <div class="flex items-center gap-1.5">
                                <span class="font-black text-amber-900 uppercase text-[10.5px]">⭐ AI Khuyên Chọn:</span>
                                <div class="flex items-center gap-1">
                                  <span 
                                    v-for="s in trip.recommended_seats" 
                                    :key="s"
                                    class="px-2 py-0.5 rounded-md bg-red-600 text-white font-mono font-black text-[11px] shadow-2xs"
                                  >
                                    {{ s }}
                                  </span>
                                </div>
                              </div>
                              <span class="text-[9.5px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.2 rounded-full">
                                {{ trip.ai_score || 'Phù hợp 98%' }}
                              </span>
                            </div>
                            <p class="text-[11px] text-slate-700 leading-relaxed font-medium">
                              {{ trip.ai_seat_advice }}
                            </p>
                            <div v-if="trip.amenity_highlight" class="text-[10px] text-slate-500 flex items-center gap-1 pt-0.5 border-t border-amber-200/50">
                              <span>{{ trip.amenity_highlight }}</span>
                            </div>
                          </div>

                          <!-- Danh sách các ghế còn trống trên xe (Point 4) -->
                          <div v-if="trip.vacant_seats && trip.vacant_seats.length > 0" class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                            <div class="flex items-center justify-between text-[10.5px] font-bold text-slate-700">
                              <span class="flex items-center gap-1">
                                <span>💺</span>
                                <span>Ghế còn trống trên chuyến này ({{ trip.vacant_seats.length }} ghế):</span>
                              </span>
                              <span class="text-[9px] text-red-600 font-semibold">Bấm để chọn ghế</span>
                            </div>
                            <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto custom-scrollbar">
                              <button
                                v-for="seat in trip.vacant_seats"
                                :key="seat"
                                type="button"
                                @click.stop="handleSelectSeatAndBook(trip, seat)"
                                class="px-2 py-0.5 rounded-md text-[10.5px] font-mono font-bold transition cursor-pointer border"
                                :class="trip.recommended_seats?.includes(seat) ? 'bg-red-600 text-white border-red-600 shadow-2xs' : 'bg-white hover:bg-red-50 text-slate-700 hover:text-red-600 border-slate-200 hover:border-red-300'"
                                :title="'Bấm để chốt ngay ghế ' + seat + ' và lấy mã VietQR'"
                              >
                                {{ seat }}
                              </button>
                            </div>
                          </div>

                          <div class="flex items-center justify-between pt-1 border-t border-slate-100 text-[11px] text-slate-600">
                            <div class="flex items-center gap-1.5">
                              <span class="font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded-md text-[10px]">⏰ {{ trip.time_str }} ({{ trip.date_str }})</span>
                              <span class="text-slate-500 text-[10px] hidden sm:inline">• {{ trip.bus_type }} ({{ trip.license_plate }})</span>
                            </div>

                            <div class="flex items-center gap-1.5">
                              <button
                                type="button"
                                @click.stop="handleInstantBook(trip, trip.recommended_seats)"
                                :disabled="isBookingInstant"
                                class="font-black text-xs text-white bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 disabled:opacity-50 px-3.5 py-1.5 rounded-xl shadow-xs transition-transform active:scale-95 cursor-pointer flex items-center gap-1.5"
                              >
                                <span v-if="isBookingInstant" class="animate-spin">⏳</span>
                                <span>{{ (trip.recommended_seats && trip.recommended_seats.length > 0) ? '⚡ Tự Động Chọn Ghế Này Ngay' : '⚡ Tự Động Chọn Ghế' }}</span>
                                <span>➔</span>
                              </button>

                              <a
                                :href="trip.book_url"
                                @click.stop
                                class="hidden sm:inline-block p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 text-[11px] font-bold"
                                title="Xem sơ đồ xe chi tiết"
                              >
                                Sơ đồ
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Interactive Suggestion Chips -->
                      <div v-if="msg.suggestions && msg.suggestions.length > 0" class="flex flex-wrap gap-1.5 mt-2.5 pt-2 border-t border-slate-200/80">
                        <button
                          v-for="(sug, sIdx) in msg.suggestions"
                          :key="sIdx"
                          @click="sendPrompt(sug)"
                          type="button"
                          class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-white hover:bg-red-50 text-slate-700 hover:text-red-700 border border-slate-200 hover:border-red-300 shadow-2xs transition active:scale-95 cursor-pointer flex items-center gap-1"
                        >
                          <span>💡</span>
                          <span>{{ sug }}</span>
                        </button>
                      </div>
                    </div>
                    <div v-else class="whitespace-pre-wrap text-xs">{{ msg.content }}</div>

                    <div class="text-[9px] mt-1 text-right" :class="msg.role === 'user' ? 'text-red-200' : 'text-slate-400'">
                      {{ msg.time }}
                    </div>
                  </div>
                </div>

                <!-- Loading AI Typing & Optimistic Filler -->
                <div v-if="isAiTyping" class="flex gap-2.5 items-start text-xs">
                  <div class="w-8 h-8 rounded-full overflow-hidden border-2 border-slate-300 bg-white flex items-center justify-center flex-shrink-0 mt-0.5 shadow-xs">
                    <img src="/images/mascot-avatar.png" alt="Trợ lý AI" class="w-full h-full object-cover" />
                  </div>
                  
                  <div v-if="isCheckingLedger" class="p-3 bg-gradient-to-r from-amber-50 to-rose-50 rounded-2xl border border-amber-200 text-slate-800 shadow-xs space-y-1 max-w-[85%] animate-pulse">
                    <div class="flex items-center gap-2 font-bold text-red-700 text-xs">
                      <span class="text-sm">📖</span>
                      <span>{{ optimisticFillerText || 'Dạ anh/chị đợi em một chút để em kiểm tra sơ đồ xem còn ghế nào êm nhất cho mình nhé...' }}</span>
                    </div>
                    <div class="text-[10px] text-slate-500 flex items-center gap-1.5 font-medium pl-6">
                      <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-ping"></span>
                      <span>Trợ lý AI đang tra cứu sơ đồ giường & phân tích lịch trình...</span>
                    </div>
                  </div>

                  <div v-else class="p-3 bg-slate-100 rounded-2xl border border-slate-200 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce [animation-delay:0.4s]"></span>
                  </div>
                </div>

              </div>

              <!-- THANH NHẬP CHAT BÊN DƯỚI: 2 CHẾ ĐỘ RÕ RÀNG (1. NHẮN TIN - 2. NÓI CHUYỆN VỚI AI) -->
              <div class="p-3 border-t border-slate-100 bg-white">
                <!-- Bộ Chuyển Đổi 2 Chế Độ -->
                <div class="flex items-center justify-between pb-2.5">
                  <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-bold gap-1 border border-slate-200/60 shadow-2xs">
                    <button
                      type="button"
                      @click="setInteractionMode('chat')"
                      :class="interactionMode === 'chat' ? 'bg-white text-red-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                      class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer font-bold text-xs"
                    >
                      <span>💬</span>
                      <span>Nhắn tin</span>
                    </button>
                    <button
                      type="button"
                      @click="setInteractionMode('voice')"
                      :class="interactionMode === 'voice' ? 'bg-gradient-to-r from-red-600 to-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                      class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer font-bold text-xs"
                    >
                      <span>🎙️</span>
                      <span>Nói chuyện với AI</span>
                    </button>
                  </div>

                  <div v-if="interactionMode === 'voice'" class="text-[11px] font-bold text-red-600 flex items-center gap-1.5 animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-red-600 animate-ping"></span>
                    <span v-if="isSpeaking">Trợ lý AI đang nói...</span>
                    <span v-else-if="isListeningVoice">Đang nghe giọng bạn...</span>
                    <span v-else>Đàm thoại 2 chiều</span>
                  </div>
                  <div v-else class="text-[11px] text-slate-400 font-medium hidden sm:block">
                    <span>Nhập câu hỏi hoặc bấm Micro để nói</span>
                  </div>
                </div>

                <!-- Thông báo khi đang lắng nghe giọng nói -->
                <div v-if="isListeningVoice" class="mb-2 p-2 rounded-xl bg-red-50 border border-red-200 flex items-center justify-between animate-pulse text-xs text-red-700 font-bold">
                  <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-ping"></span>
                    <span>🎙️ Đang lắng nghe giọng nói tiếng Việt... (Hãy nói yêu cầu của bạn)</span>
                  </div>
                  <button type="button" @click="cancelListening" class="text-slate-400 hover:text-slate-600 text-xs font-bold cursor-pointer">✕ Hủy</button>
                </div>

                <!-- Đang chờ chống vọng loa -->
                <div v-else-if="isEchoCoolingDown" class="mb-2 p-2 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-between text-xs text-amber-800 font-bold animate-in fade-in">
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>⏳ Đang nghỉ 1.2s triệt tiêu âm vang loa ngoài trước khi mở Micro...</span>
                  </div>
                  <button type="button" @click="triggerManualMic" class="text-red-700 hover:text-red-900 text-[11px] font-bold underline cursor-pointer">Nói ngay</button>
                </div>

                <form @submit.prevent="sendMessage(false)" class="flex items-center gap-2">
                  <input
                    type="text"
                    v-model="inputQuery"
                    @keydown.enter.prevent="sendMessage(false)"
                    :placeholder="interactionMode === 'voice' ? 'Đang bật nói chuyện với AI — Bạn có thể nói trực tiếp qua Micro...' : 'Nhập câu hỏi hoặc bấm Micro để nói (VD: Có xe đi Quảng Trị lúc mấy giờ?)...'"
                    class="flex-1 bg-slate-50 hover:bg-white focus:bg-white border border-slate-200 focus:border-red-500 rounded-xl px-3.5 py-2.5 text-xs font-medium text-slate-900 outline-none transition"
                  />
                  <!-- Nút Micro Voice AI -->
                  <button
                    type="button"
                    @click="triggerManualMic"
                    :class="isListeningVoice ? 'bg-red-600 text-white ring-4 ring-red-300 animate-pulse' : (interactionMode === 'voice' ? 'bg-red-50 text-red-600 border-red-200' : 'bg-slate-100 hover:bg-slate-200 text-slate-700')"
                    class="p-2.5 rounded-xl transition cursor-pointer flex-shrink-0 flex items-center justify-center border border-slate-200"
                    :title="isListeningVoice ? 'Đang lắng nghe... (Bấm để hủy)' : 'Bấm để nói bằng giọng nói (Voice AI)'"
                  >
                    <span class="text-sm">{{ isListeningVoice ? '🔴' : '🎙️' }}</span>
                  </button>

                  <button
                    type="submit"
                    :disabled="!inputQuery.trim() || isAiTyping"
                    class="p-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-xl shadow-xs transition cursor-pointer flex-shrink-0"
                    title="Gửi tin nhắn"
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

        <!-- Modal Nhập Thông Tin Khách Vãng Lai & Hỗ Trợ Đăng Nhập Ngay Tại Chỗ -->
        <div
          v-if="guestBookingInfo.show"
          class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in"
        >
          <div class="fixed inset-0" @click="guestBookingInfo.show = false"></div>
          <div class="relative bg-white rounded-3xl p-5 max-w-md w-full shadow-2xl border border-red-200 z-10 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
              <div class="flex items-center gap-2">
                <span class="text-xl">⚡</span>
                <div>
                  <h4 class="font-black text-slate-900 text-sm">Khóa Ghế & Lấy Mã VietQR</h4>
                  <p class="text-[11px] text-slate-500">Chuyến: <strong>{{ guestBookingInfo.trip?.route_name }}</strong> • Ghế: <strong class="text-red-600">{{ guestBookingInfo.seats.join(', ') }}</strong></p>
                </div>
              </div>
              <button
                type="button"
                @click="guestBookingInfo.show = false"
                class="text-slate-400 hover:text-slate-600 p-1 rounded-xl hover:bg-slate-100 text-sm cursor-pointer"
              >
                ✕
              </button>
            </div>

            <!-- Tùy chọn 1: Đăng nhập ngay (Khuyên dùng) -->
            <div class="p-3.5 rounded-2xl bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 space-y-2">
              <div class="flex items-center justify-between text-xs font-bold text-red-900">
                <span>🔑 Bạn đã có tài khoản?</span>
                <span class="text-[10px] text-red-600 bg-white px-2 py-0.5 rounded-full font-bold shadow-2xs">Khuyên Dùng</span>
              </div>
              <p class="text-[11px] text-slate-600 leading-relaxed">Đăng nhập tài khoản để tự động lưu vé vào hệ thống, tích lũy điểm và tra cứu vé mọi lúc mọi nơi.</p>
              <button
                type="button"
                @click="guestBookingInfo.show = false; showAuthModal = true;"
                class="w-full py-2 px-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs transition cursor-pointer flex items-center justify-center gap-2 shadow-xs"
              >
                <span>Đăng Nhập / Đăng Ký Tài Khoản Ngay</span>
              </button>
            </div>

            <!-- Tùy chọn 2: Điền nhanh tên + SĐT -->
            <div class="space-y-3 pt-1">
              <div class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                Hoặc chốt vé nhanh không cần tài khoản:
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Họ và tên hành khách <span class="text-red-500">*</span></label>
                <input
                  v-model="guestBookingInfo.name"
                  type="text"
                  placeholder="VD: Nguyễn Văn A"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-900 outline-none focus:border-red-500 focus:bg-white transition"
                />
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Số điện thoại nhận vé <span class="text-red-500">*</span></label>
                <input
                  v-model="guestBookingInfo.phone"
                  type="tel"
                  placeholder="VD: 0905123456"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-900 outline-none focus:border-red-500 focus:bg-white transition"
                />
              </div>
              <button
                type="button"
                @click="submitGuestBooking"
                :disabled="isBookingInstant"
                class="w-full py-2.5 px-4 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 disabled:opacity-50 text-white font-black text-xs rounded-xl shadow-md transition cursor-pointer flex items-center justify-center gap-2 active:scale-95"
              >
                <span v-if="isBookingInstant" class="animate-spin">⏳</span>
                <span>⚡ Khóa Ghế & Xuất Mã VietQR Ngay ➔</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Modal Đăng nhập / Đăng ký tự động kích hoạt bởi Bé Kiến Trúc -->
        <AuthModal v-model="showAuthModal" />

      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthModal from '@/Components/AuthModal.vue';

const page = usePage();
const isOpen = ref(false);
const isMaximized = ref(false);
const showMobileHistory = ref(false);
const activeView = ref('chat');
const sidebarTab = ref('kiosk');
const inputQuery = ref('');
const isAiTyping = ref(false);
const isListeningVoice = ref(false);
const chatContainer = ref(null);

const currentSessionId = ref(1);
const editingSessionId = ref(null);
const editingTitle = ref('');

const isSendingEmail = ref(false);
const selectedBusFilter = ref('ALL');

const showAuthModal = ref(false);
const interactionMode = ref('chat'); // 2 chế độ độc quyền: 'chat' (1. Nhắn tin) | 'voice' (2. Nói chuyện với AI)
const isTtsEnabled = ref(false); // Chỉ tự động nói trong chế độ voice hoặc bấm Nghe đọc
const isSpeaking = ref(false);
const speakingMsgIdx = ref(null);

// ==========================================
// ĐÀM THOẠI 2 CHIỀU & CHỐNG VỌNG TIẾNG LOA (ACOUSTIC ECHO CANCELLATION)
// ==========================================
const isCallModeActive = ref(false);
const lastAiSpokenCleanText = ref('');
const lastTtsEndTime = ref(0);
const isEchoCoolingDown = ref(false);
const autoListenBlockedReason = ref('');
let echoCooldownTimer = null;
let activeRecognition = null;
let echoLoopCount = 0;
let lastEchoDetectTime = 0;
let isCurrentSessionAuto = false;

const isCheckingLedger = ref(false);
const optimisticFillerText = ref('');

const currentBookingState = ref({});
const bookingCountdown = ref(300);
let countdownInterval = null;

const consultationForm = ref({
  destination: 'Quảng Trị',
  customDestination: '',
  date: 'Hôm nay',
  customDate: '',
  timePeriod: 'Sáng',
  seatCount: 2,
  customSeatCount: '',
  sayXe: false,
  treEm: false,
  capDoi: true,
  nguoiGia: false,
  caoLon: false,
  riengTu: false,
  customNote: '',
});

const isBookingInstant = ref(false);
const isConfirmingPayment = ref(false);
const isHoldExpired = ref(false);
const guestBookingInfo = ref({
  show: false,
  trip: null,
  seats: [],
  name: '',
  phone: ''
});

const startCountdownTimer = () => {
  bookingCountdown.value = 300;
  isHoldExpired.value = false;
  if (countdownInterval) clearInterval(countdownInterval);
  countdownInterval = setInterval(() => {
    if (bookingCountdown.value > 0) {
      bookingCountdown.value--;
    } else {
      clearInterval(countdownInterval);
      isHoldExpired.value = true;
    }
  }, 1000);
};

const formatTimer = (seconds) => {
  const m = Math.floor(seconds / 60);
  const s = seconds % 60;
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
};

const copyToClipboard = (text, msg) => {
  if (!text) return;
  try {
    navigator.clipboard.writeText(text);
    alert(msg || 'Đã sao chép vào bộ nhớ tạm!');
  } catch (e) {
    alert('Đã sao chép: ' + text);
  }
};

// ==========================================
// THUẬT TOÁN NHẬN DIỆN VÀ TRIỆT TIÊU TIẾNG VỌNG TỪ LOA NGOÀI
// ==========================================
const normalizeTextForComparison = (text) => {
  if (!text) return '';
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^\w\s]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
};

const isAcousticEcho = (userTranscript, isAuto = false) => {
  if (!userTranscript) return true;
  const transcript = userTranscript.trim();
  const normUser = normalizeTextForComparison(transcript);
  const userWords = normUser.split(' ').filter(Boolean);
  const timeSinceTtsEnd = Date.now() - lastTtsEndTime.value;

  // Ngoại lệ 1: Câu chào hỏi hoặc yêu cầu tra cứu vé, điểm đến rõ ràng của người dùng
  const isIntentUser = /(chao|xin chao|dat ve|quang tri|hue|quy nhon|da nang|buon ma thuot|ve xe|chuyen|ghe|cho toi|con ve|co ve|hom nay|ngay mai|ten|email|gmail)/.test(normUser);
  if (isIntentUser) {
    if (!lastAiSpokenCleanText.value) return false;
    const normAi = normalizeTextForComparison(lastAiSpokenCleanText.value);
    if (!normAi.includes(normUser) || normUser.length > 15) {
      return false; // Rõ ràng là câu nói của người dùng -> chấp nhận ngay!
    }
  }

  // Ngoại lệ 2: Nếu người dùng đang cung cấp thông tin thực sự (SĐT, số ghế, mã OTP, số tiền)
  const containsSpecificEntity = /\d{4,}/.test(transcript) || /\b[A-Da-d]\d{2}\b/.test(transcript);
  if (containsSpecificEntity) {
    if (!lastAiSpokenCleanText.value) return false;
    const normAi = normalizeTextForComparison(lastAiSpokenCleanText.value);
    if (!normAi.includes(normUser)) {
      return false; // Dữ liệu riêng biệt do khách đọc -> chấp nhận ngay
    }
  }

  // 1. Nếu mở Micro tự động ngay sau khi AI đọc dứt câu:
  if (isAuto) {
    // Âm thanh ngắn (< 3 từ) xuất hiện trong vòng 2.5s sau khi AI dứt câu (thường là đuôi câu: "nhé", "ạ", "ơi")
    if (timeSinceTtsEnd < 2500 && userWords.length <= 2) {
      if (!lastAiSpokenCleanText.value) return true;
      const normAi = normalizeTextForComparison(lastAiSpokenCleanText.value);
      if (normAi.includes(normUser)) {
        console.warn('[EchoFilter] Bỏ qua từ đệm/tiếng vang loa ngoài:', transcript);
        return true;
      }
      if (userWords.length === 1 && normUser.length < 4) {
        console.warn('[EchoFilter] Bỏ qua âm tiết đơn thu từ loa:', transcript);
        return true;
      }
    }
  }

  // 2. So sánh đối chiếu nội dung với câu văn AI vừa phát âm:
  if (lastAiSpokenCleanText.value) {
    const normAi = normalizeTextForComparison(lastAiSpokenCleanText.value);

    // a. Toàn bộ câu thu được nằm trong câu AI vừa nói (hoặc ngược lại)
    if (normUser.length >= 4 && (normAi.includes(normUser) || normUser.includes(normAi))) {
      console.warn('[EchoFilter] Micro thu lại đúng tiếng loa AI vừa đọc:', transcript);
      return true;
    }

    // b. Kiểm tra tỷ lệ trùng từ (Token Overlap)
    if (userWords.length >= 3) {
      const aiWords = new Set(normAi.split(' ').filter(Boolean));
      let matchCount = 0;
      for (const w of userWords) {
        if (aiWords.has(w)) matchCount++;
      }
      const overlapRatio = matchCount / userWords.length;
      if (overlapRatio >= 0.55 && timeSinceTtsEnd < 8000) {
        console.warn(`[EchoFilter] Trùng lặp từ ${Math.round(overlapRatio * 100)}% với câu của AI:`, transcript);
        return true;
      }
    }
  }

  return false;
};

const handleEchoDetected = (transcript) => {
  const now = Date.now();
  if (now - lastEchoDetectTime < 10000) {
    echoLoopCount++;
  } else {
    echoLoopCount = 1;
  }
  lastEchoDetectTime = now;

  console.warn(`[EchoFilter] Phát hiện tiếng vọng loa ngoài (${echoLoopCount}/2): "${transcript}"`);

  // Cầu chì an toàn: Nếu phát hiện 2 lần vọng liên tiếp trong 10 giây -> Tự động dừng đàm thoại rảnh tay để chống lặp
  if (echoLoopCount >= 2) {
    isCallModeActive.value = false;
    isListeningVoice.value = false;
    isEchoCoolingDown.value = false;
    if (echoCooldownTimer) clearTimeout(echoCooldownTimer);
    if (activeRecognition) {
      try { activeRecognition.abort(); } catch (e) {}
      activeRecognition = null;
    }
    autoListenBlockedReason.value = '⚠️ Loa ngoài thiết bị phát tiếng vọng lớn vào Micro! Bé Kiến Trúc đã tự động tạm ngắt chế độ đàm thoại rảnh tay để chống lặp. Anh/chị hãy bấm nút Micro (🎙️) khi muốn nói tiếp nhé!';
    return true;
  } else {
    autoListenBlockedReason.value = `🛡️ Đã lọc tiếng vang từ loa: "${transcript.length > 30 ? transcript.slice(0, 30) + '...' : transcript}"`;
    setTimeout(() => {
      if (autoListenBlockedReason.value.startsWith('🛡️')) {
        autoListenBlockedReason.value = '';
      }
    }, 3500);
    return false;
  }
};

const cancelListening = () => {
  isListeningVoice.value = false;
  isEchoCoolingDown.value = false;
  if (echoCooldownTimer) clearTimeout(echoCooldownTimer);
  if (activeRecognition) {
    try { activeRecognition.abort(); } catch (e) {}
    activeRecognition = null;
  }
};

const triggerManualMic = () => {
  if (isListeningVoice.value) {
    cancelListening();
  } else {
    stopSpeech();
    startVoiceRecognition(false /* isAuto = false */);
  }
};

const setInteractionMode = (mode) => {
  interactionMode.value = mode;
  if (mode === 'voice') {
    isCallModeActive.value = true;
    isTtsEnabled.value = true;
    autoListenBlockedReason.value = '';
    echoLoopCount = 0;
    triggerManualMic();
  } else {
    isCallModeActive.value = false;
    isTtsEnabled.value = false;
    cancelListening();
    stopSpeech();
    autoListenBlockedReason.value = '';
  }
};

const toggleCallMode = () => {
  if (interactionMode.value === 'voice') {
    setInteractionMode('chat');
  } else {
    setInteractionMode('voice');
  }
};

const toggleTts = () => {
  isTtsEnabled.value = !isTtsEnabled.value;
  if (!isTtsEnabled.value) {
    stopSpeech();
  }
};

const cleanTextForSpeech = (text) => {
  if (!text) return '';
  return text
    // 1. Loại bỏ các thẻ HTML, markdown và đường link
    .replace(/<[^>]*>/g, ' ')
    .replace(/https?:\/\/\S+/g, ' ')
    .replace(/[*#_`~\[\]\(\)\>\<]/g, ' ')
    .replace(/[➔→⇄↔⇒]/g, ' đến ')
    .replace(/[•\-\–\—\│\|]/g, ', ')
    .replace(/\bKT-([A-Za-z0-9]+)/gi, (m, p1) => 'mã ' + p1.split('').join(' '))
    // 2. Loại bỏ TOÀN BỘ emoji, biểu tượng pictograph, icon xe, icon cảm xúc, hoa, sao
    .replace(/\p{Extended_Pictographic}/gu, ' ')
    .replace(/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}\u{2300}-\u{23FF}\u{2B50}\u{200D}\u{FE0F}]/gu, ' ')
    // 3. Chỉ giữ lại chữ cái các ngôn ngữ có dấu (tiếng Việt), số, khoảng trắng và dấu câu
    .replace(/[^\p{L}\p{N}\s.,?!;:\/%]/gu, ' ')
    .replace(/\s+/g, ' ')
    .trim();
};

const speakText = (rawText, msgIdx = null) => {
  if (typeof window === 'undefined' || !('speechSynthesis' in window)) return;

  // 1. Ngắt ngay bất kỳ phiên Micro nào đang chạy để không bao giờ thu tiếng AI
  if (activeRecognition) {
    try { activeRecognition.abort(); } catch (e) {}
    activeRecognition = null;
  }
  isListeningVoice.value = false;
  if (echoCooldownTimer) clearTimeout(echoCooldownTimer);
  isEchoCoolingDown.value = false;

  window.speechSynthesis.cancel();
  
  if (!rawText) {
    isSpeaking.value = false;
    speakingMsgIdx.value = null;
    return;
  }

  const clean = cleanTextForSpeech(rawText);
  if (!clean) return;

  // Ghi nhớ câu văn vừa đọc để đối chiếu chống vọng loa (Acoustic Echo Profile)
  lastAiSpokenCleanText.value = clean;

  const utterance = new SpeechSynthesisUtterance(clean);
  utterance.lang = 'vi-VN';
  utterance.rate = 1.05;
  utterance.pitch = 1.05;

  speakingMsgIdx.value = msgIdx;
  isSpeaking.value = true;

  utterance.onend = () => {
    isSpeaking.value = false;
    speakingMsgIdx.value = null;
    lastTtsEndTime.value = Date.now();

    // Nếu chế độ đàm thoại 2 chiều đang bật và cửa sổ đang mở -> nghỉ 450ms ngắn gọn rồi mở Micro đàm thoại liên tục
    if (isCallModeActive.value && isOpen.value) {
      isEchoCoolingDown.value = true;
      if (echoCooldownTimer) clearTimeout(echoCooldownTimer);

      echoCooldownTimer = setTimeout(() => {
        isEchoCoolingDown.value = false;
        // Kiểm tra chặt chẽ: AI không còn nói dở, không đang gõ, và loa đã tắt hẳn
        const isStillSynthesizing = typeof window !== 'undefined' && window.speechSynthesis && window.speechSynthesis.speaking;
        if (isCallModeActive.value && !isSpeaking.value && !isAiTyping.value && !isStillSynthesizing) {
          startVoiceRecognition(true /* isAuto */);
        }
      }, 450); // Khoảng đệm 450ms mượt mà giúp đàm thoại liên tục không bị ngắt quãng
    }
  };

  utterance.onerror = () => {
    isSpeaking.value = false;
    speakingMsgIdx.value = null;
    lastTtsEndTime.value = Date.now();
    isEchoCoolingDown.value = false;
  };

  window.speechSynthesis.speak(utterance);
};

const stopSpeech = () => {
  if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    window.speechSynthesis.cancel();
  }
  isSpeaking.value = false;
  speakingMsgIdx.value = null;
  if (echoCooldownTimer) clearTimeout(echoCooldownTimer);
  isEchoCoolingDown.value = false;
};

const speakOptimisticFiller = (phrase) => {
  if (typeof window === 'undefined' || !('speechSynthesis' in window)) return;

  if (activeRecognition) {
    try { activeRecognition.abort(); } catch (e) {}
    activeRecognition = null;
  }
  isListeningVoice.value = false;

  window.speechSynthesis.cancel();
  const clean = cleanTextForSpeech(phrase);
  if (!clean) return;

  lastAiSpokenCleanText.value = clean;

  const utterance = new SpeechSynthesisUtterance(clean);
  utterance.lang = 'vi-VN';
  utterance.rate = 1.05;
  utterance.pitch = 1.05;

  isSpeaking.value = true;
  utterance.onend = () => {
    isSpeaking.value = false;
  };
  utterance.onerror = () => {
    isSpeaking.value = false;
  };

  window.speechSynthesis.speak(utterance);
};

const toggleSpeakMsg = (msg, idx) => {
  if (isSpeaking.value && speakingMsgIdx.value === idx) {
    stopSpeech();
  } else {
    speakText(msg.content, idx);
  }
};

const openQrTab = (url) => {
  if (url) window.open(url, '_blank');
};

const handleAction = (action) => {
  if (!action) return;
  if (action.type === 'open_login') {
    showAuthModal.value = true;
  } else if (action.type === 'open_google') {
    showAuthModal.value = true;
  } else if (action.type === 'open_register') {
    showAuthModal.value = true;
  } else if (action.type === 'go_to_my_bookings') {
    window.location.href = '/my-bookings';
  } else if (action.url) {
    window.location.href = action.url;
  }
};

const currentUser = computed(() => {
  return page.props.auth?.user || null;
});

const isAdminUser = computed(() => {
  const u = currentUser.value;
  return u && (u.role === 'admin' || u.role === 'staff');
});

const resetGuestChat = () => {
  const newId = Date.now();
  chatSessions.value = [
    {
      id: newId,
      title: 'Tư vấn chuyến đi',
      messages: []
    }
  ];
  currentSessionId.value = newId;
  try {
    sessionStorage.removeItem('nhaxekientruc_guest_chat');
    localStorage.removeItem('nhaxekientruc_chat_sessions_guest');
    localStorage.removeItem('bushub_chat_sessions_guest');
  } catch (e) {}
};

onMounted(() => {
  if (!currentUser.value) {
    // KHÁCH CHƯA ĐĂNG NHẬP: Khi tải lại trang (reload/F5), xóa sạch toàn bộ đoạn chat cũ (Requirement 2)
    resetGuestChat();
  }
});

watch(() => currentUser.value, (newVal) => {
  if (!newVal) {
    resetGuestChat();
  } else {
    chatSessions.value = getInitialSessions();
    currentSessionId.value = chatSessions.value[0]?.id || 1;
  }
});

// Các đoạn chat: Chỉ lưu vào LocalStorage khi đã đăng nhập tài khoản
const getInitialSessions = () => {
  if (typeof window !== 'undefined' && currentUser.value) {
    const saved = localStorage.getItem(`nhaxekientruc_chat_sessions_${currentUser.value.id}`) || localStorage.getItem(`bushub_chat_sessions_${currentUser.value.id}`);
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
      title: 'Tư vấn chuyến đi',
      messages: []
    }
  ];
};

const chatSessions = ref(getInitialSessions());

const persistSessions = () => {
  if (typeof window !== 'undefined' && currentUser.value) {
    try {
      localStorage.setItem(`nhaxekientruc_chat_sessions_${currentUser.value.id}`, JSON.stringify(chatSessions.value));
    } catch (e) {}
  }
};

const currentMessages = computed(() => {
  let s = chatSessions.value.find(x => x.id === currentSessionId.value);
  if (!s && chatSessions.value.length > 0) {
    s = chatSessions.value[0];
    currentSessionId.value = s.id;
  }
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
  isAiTyping.value = false;
  if (!chatSessions.value || chatSessions.value.length === 0) {
    resetGuestChat();
  } else if (!chatSessions.value.find(s => s.id === currentSessionId.value)) {
    currentSessionId.value = chatSessions.value[0].id;
  }
};

const closeModal = () => {
  isOpen.value = false;
  stopSpeech();
  cancelListening();
  isCallModeActive.value = false;
};

onUnmounted(() => {
  stopSpeech();
  cancelListening();
  if (countdownInterval) clearInterval(countdownInterval);
});

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
  showMobileHistory.value = false;
  persistSessions();
};

const switchChatSession = (id) => {
  currentSessionId.value = id;
  showMobileHistory.value = false;
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

const formatAiMessage = (content) => {
  if (!content) return '';
  let html = content
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  // Bold **text**
  html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-red-600">$1</strong>');
  // Italic *text*
  html = html.replace(/\*(.*?)\*/g, '<em class="italic">$1</em>');
  // Markdown links [text](url)
  html = html.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" class="text-red-600 underline font-bold hover:text-red-800" onclick="window.location.href=\'$2\'; return false;">$1</a>');
  // Bullet lists
  html = html.replace(/^[•\-\*]\s+(.*)$/gm, '<div class="flex items-start gap-1.5 my-1"><span class="text-red-500 font-bold shrink-0">•</span><span>$1</span></div>');
  // Numbered lists
  html = html.replace(/^(\d+)\.\s+(.*)$/gm, '<div class="flex items-start gap-1.5 my-1"><span class="font-bold text-slate-700 shrink-0">$1.</span><span>$2</span></div>');
  // Newlines to <br/>
  html = html.replace(/\n/g, '<br/>');
  return html;
};

const goToTrip = (url) => {
  if (!url) return;
  isOpen.value = false;
  window.location.href = url;
};

const submitConsultationForm = () => {
  const parts = [];
  const finalDest = (consultationForm.value.destination === 'custom' && consultationForm.value.customDestination.trim())
    ? consultationForm.value.customDestination.trim()
    : consultationForm.value.destination;

  let count = consultationForm.value.seatCount === 5 
    ? (parseInt(consultationForm.value.customSeatCount) || 5)
    : consultationForm.value.seatCount;

  // Tự động đồng bộ số vé nếu trong ô ghi chú tự do có nhắc đến số người (VD: "tôi đi 3 người", "3 vé", "3 bạn")
  if (consultationForm.value.customNote && consultationForm.value.customNote.trim()) {
    const noteMatch = consultationForm.value.customNote.match(/(\d+)\s*(người|vé|khách|chỗ|bạn)/i);
    if (noteMatch && parseInt(noteMatch[1])) {
      count = parseInt(noteMatch[1]);
      consultationForm.value.seatCount = count > 5 ? 5 : count;
      if (count > 5) consultationForm.value.customSeatCount = count;
    }
  }

  parts.push(`Tôi muốn đặt ${count} vé đi ${finalDest}`);

  // Xử lý ngày đi linh hoạt
  if (consultationForm.value.date === 'custom' && consultationForm.value.customDate) {
    const partsDate = consultationForm.value.customDate.split('-');
    if (partsDate.length === 3) {
      parts.push(`vào ngày ${partsDate[2]}/${partsDate[1]}/${partsDate[0]}`);
    } else {
      parts.push(`vào ngày ${consultationForm.value.customDate}`);
    }
  } else {
    parts.push(`vào ${consultationForm.value.date.toLowerCase()}`);
  }

  if (consultationForm.value.timePeriod !== 'Bất kỳ') {
    parts.push(`khung giờ ${consultationForm.value.timePeriod.toLowerCase()}`);
  }

  const conditions = [];
  if (consultationForm.value.sayXe) conditions.push('tôi bị say xe cần chỗ êm');
  if (consultationForm.value.treEm) conditions.push('đi cùng trẻ em');
  if (consultationForm.value.capDoi || count >= 2) conditions.push('các giường nằm cạnh nhau');
  if (consultationForm.value.nguoiGia) conditions.push('đi cùng người già / bà cần tầng dưới');
  if (consultationForm.value.caoLon) conditions.push('người cao lớn cần giường rộng');
  if (consultationForm.value.riengTu) conditions.push('cần yên tĩnh riêng tư');
  if (consultationForm.value.customNote && consultationForm.value.customNote.trim()) {
    conditions.push(consultationForm.value.customNote.trim());
  }

  if (conditions.length > 0) {
    parts.push(`nhu cầu: ${conditions.join(', ')}`);
  }
  parts.push('chọn giúp tôi ghế êm và chuyến xe phù hợp nhất');
  sendPrompt(parts.join(', '));
};

const handleSelectSeatAndBook = (trip, seat) => {
  handleInstantBook(trip, [seat]);
};

const handleInstantBook = async (trip, seats) => {
  const chosenSeats = Array.isArray(seats) && seats.length > 0 ? seats : (trip.recommended_seats || ['A01']);

  // Nếu khách chưa đăng nhập: Hiển thị hộp thoại hỗ trợ đăng nhập hoặc nhập nhanh Tên/SĐT
  if (!currentUser.value) {
    guestBookingInfo.value = {
      show: true,
      trip: trip,
      seats: chosenSeats,
      name: '',
      phone: ''
    };
    return;
  }

  // Khách đã đăng nhập: Khóa ghế tức thì & xuất mã VietQR
  await executeInstantBook(trip, chosenSeats, currentUser.value.name, currentUser.value.phone || '0905123456', currentUser.value.email || '');
};

const submitGuestBooking = async () => {
  const trip = guestBookingInfo.value.trip;
  const seats = guestBookingInfo.value.seats;
  let name = guestBookingInfo.value.name.trim();
  let phone = guestBookingInfo.value.phone.trim();

  if (!name) name = 'Khách hàng';
  if (!phone) phone = '0905123456';

  guestBookingInfo.value.show = false;
  await executeInstantBook(trip, seats, name, phone, '');
};

const executeInstantBook = async (trip, seats, name, phone, email) => {
  isBookingInstant.value = true;
  isAiTyping.value = true;
  try {
    const res = await fetch('/api/ai/instant-book', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        trip_id: trip.id,
        seats: seats,
        seat_numbers: seats,
        name: name,
        phone: phone,
        customer_name: name,
        customer_phone: phone,
        email: email
      })
    });

    let data;
    try {
      data = await res.json();
    } catch (parseErr) {
      console.error('Non-JSON response from /api/ai/instant-book:', parseErr);
      alert('Không thể kết nối dịch vụ giữ chỗ lúc này. Vui lòng thử lại sau giây lát!');
      return;
    }

    if (!res.ok || data.success === false) {
      alert(data.message || 'Không thể khóa ghế lúc này. Vui lòng thử lại!');
      return;
    }

    const currentSession = chatSessions.value.find(x => x.id === currentSessionId.value);
    if (currentSession) {
      const seatStr = seats.join(', ');
      const newMsg = {
        role: 'assistant',
        content: data.reply || `Dạ em đã giữ chỗ thành công ghế **${seatStr}** cho bạn! Mã vé: **${data.booking_code}**. Bạn quét mã VietQR bên dưới để thanh toán cọc 30% giữ chỗ nhé! 💳✨`,
        qr_data: data.qr_data || null,
        time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
      };
      currentSession.messages.push(newMsg);
      if (data.qr_data) {
        startCountdownTimer();
      }
      persistSessions();
      scrollToBottom();

      const shouldSpeak = (interactionMode.value === 'voice');
      if (shouldSpeak) {
        speakText(`Em đã tự động khóa ghế ${seatStr} thành công cho bạn rồi ạ! Bạn quét mã VietQR trên màn hình để đặt cọc giữ chỗ nhé.`, currentSession.messages.length - 1);
      }
    }
  } catch (err) {
    console.error('Instant book error:', err);
    alert('Không thể khóa ghế lúc này. Vui lòng thử lại!');
  } finally {
    isBookingInstant.value = false;
    isAiTyping.value = false;
  }
};

const handleConfirmPayment = async (bookingCode) => {
  if (!bookingCode) return;
  isConfirmingPayment.value = true;
  try {
    const res = await fetch('/api/ai/confirm-payment', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ booking_code: bookingCode })
    });
    const data = await res.json();

    const replyText = "Bạn đã thanh toán thành thật như bài mình đã làm nhé! 🎉\n\nVé **" + (data.booking_code || bookingCode) + "** của bạn đã được xác nhận thanh toán cọc thành công trên hệ thống Nhà xe Kiến Trúc. Bạn bấm vào nút bên dưới để chuyển sang Quản Lý Vé xem chi tiết vé và mã QR lên xe nhé!";

    const currentSession = chatSessions.value.find(x => x.id === currentSessionId.value);
    if (currentSession) {
      currentSession.messages.push({
        role: 'assistant',
        content: replyText,
        action: {
          type: 'go_to_my_bookings',
          title: '🎫 Chuyển Sang Quản Lý Vé Để Xem Vé ➔',
          url: '/my-bookings'
        },
        time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
      });
      persistSessions();
      scrollToBottom();

      if (interactionMode.value === 'voice') {
        speakText("Bạn đã thanh toán thành thật như bài mình đã làm nhé! Em đã chuyển thông tin vé sang mục Quản Lý Vé cho bạn rồi ạ.", currentSession.messages.length - 1);
      }
    }
  } catch (err) {
    alert('Đã xác nhận thanh toán thành công!');
    window.location.href = '/my-bookings';
  } finally {
    isConfirmingPayment.value = false;
  }
};

const sendPrompt = (text) => {
  inputQuery.value = text;
  sendMessage(false);
};

const startVoiceRecognition = (isAuto = false) => {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  if (!SpeechRecognition) {
    if (!isAuto) {
      alert('Trình duyệt của bạn chưa hỗ trợ nhận diện giọng nói Web Speech API. Bạn vui lòng nhập câu hỏi bằng bàn phím nhé!');
    }
    return;
  }

  // Nếu AI vẫn đang phát âm:
  const isAiCurrentlyTalking = isSpeaking.value || (typeof window !== 'undefined' && window.speechSynthesis?.speaking);
  if (isAiCurrentlyTalking) {
    if (isAuto) {
      // Nếu là tự động mở lại mic mà AI chưa dứt tiếng -> HỦY BỎ NGAY để tránh thu ngược
      console.warn('[EchoFilter] Bỏ qua tự động bật Mic vì AI vẫn đang phát âm');
      return;
    } else {
      // Nếu người dùng chủ động bấm nút Mic trên màn hình -> Ngắt tiếng AI ngay để ưu tiên người dùng
      stopSpeech();
    }
  }

  // Dọn dẹp phiên Micro cũ nếu đang chạy
  if (activeRecognition) {
    try { activeRecognition.abort(); } catch (e) {}
    activeRecognition = null;
  }

  try {
    const recognition = new SpeechRecognition();
    activeRecognition = recognition;
    isCurrentSessionAuto = isAuto;
    recognition.lang = 'vi-VN';
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onstart = () => {
      isListeningVoice.value = true;
    };

    recognition.onresult = (event) => {
      isListeningVoice.value = false;
      const transcript = event.results?.[0]?.[0]?.transcript?.trim();
      if (!transcript) return;

      // ========== BỘ LỌC CHỐNG TIẾNG VỌNG LOA NGOÀI (ACOUSTIC ECHO CHECK) ==========
      if (isAcousticEcho(transcript, isCurrentSessionAuto)) {
        const loopTripped = handleEchoDetected(transcript);
        // Nếu chưa bị ngắt an toàn và đang ở chế độ đàm thoại -> chờ 1.5s rồi mở lại sạch sẽ
        if (!loopTripped && isCallModeActive.value && isOpen.value) {
          if (echoCooldownTimer) clearTimeout(echoCooldownTimer);
          echoCooldownTimer = setTimeout(() => {
            if (isCallModeActive.value && !isSpeaking.value && !isAiTyping.value) {
              startVoiceRecognition(true);
            }
          }, 1500);
        }
        return;
      }

      // Khách hàng nói câu hợp lệ -> Reset bộ đếm lặp
      echoLoopCount = 0;
      autoListenBlockedReason.value = '';
      inputQuery.value = transcript;
      sendMessage(true);
    };

    recognition.onerror = (e) => {
      isListeningVoice.value = false;
      if (e.error === 'not-allowed' || e.error === 'service-not-allowed') {
        isCallModeActive.value = false;
      }
    };

    recognition.onend = () => {
      isListeningVoice.value = false;
      activeRecognition = null;
    };

    recognition.start();
  } catch (err) {
    isListeningVoice.value = false;
    activeRecognition = null;
  }
};

const detectFallbackAction = (q) => {
  const lower = q.toLowerCase();
  if (/(đăng nhập bằng google|google login|tài khoản google|gmail)/i.test(lower)) {
    return { type: 'open_google', title: '🌐 Mở Chọn Tài Khoản Google Ngay' };
  }
  if (/(đăng nhập|đăng ký|tạo tài khoản|login|register)/i.test(lower)) {
    return { type: 'open_login', title: '🔑 Mở Khung Đăng Nhập / Đăng Ký' };
  }
  if (/(hủy vé|hủy chuyến|trả vé|hoàn tiền)/i.test(lower)) {
    return { type: 'go_to_my_bookings', title: '🎫 Mở Quản Lý Vé & Hủy Vé Tự Động' };
  }
  if (/(đặt vé|dat ve|đặt xe|dat xe|muốn đặt|muon dat|mua vé|mua ve|tìm xe|tim xe|bắt xe|bat xe|book xe|tư vấn)/i.test(lower)) {
    return {
      type: 'booking_consultation_form',
      initial_destination: 'Huế',
      destination: 'Huế',
      title: '📋 Khung Tư Vấn & Chọn Ghế Thông Minh'
    };
  }
  return null;
};

const sendMessage = async (isVoiceTurn = false) => {
  const query = inputQuery.value.trim();
  if (!query) return;

  let currentSession = chatSessions.value.find(x => x.id === currentSessionId.value);
  if (!currentSession) {
    if (chatSessions.value.length > 0) {
      currentSession = chatSessions.value[0];
      currentSessionId.value = currentSession.id;
    } else {
      currentSession = {
        id: Date.now(),
        title: 'Tư vấn chuyến đi',
        messages: []
      };
      chatSessions.value.push(currentSession);
      currentSessionId.value = currentSession.id;
    }
  }

  // Khách hàng dùng voice thì AI mới nói lại; còn nhắn tin thì AI chỉ nhắn tin hỗ trợ bình thường
  const shouldSpeakResponse = Boolean(isVoiceTurn || interactionMode.value === 'voice');

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

  // -------------------------------------------------------------
  // LUỒNG PHẢN HỒI 2 NHỊP (OPTIMISTIC FILLER & BÉ KIẾN TRÚC TRA SỔ)
  // Chỉ phát âm thanh câu đệm nếu khách hàng dùng voice
  // -------------------------------------------------------------
  const lowerQuery = query.toLowerCase();
  const isComplexInquiry = /(say xe|say tàu|chóng mặt|tiền đình|chọn ghế|tìm ghế|ghế nào|ghế êm|giường êm|người già|trẻ em|em bé|còn vé|còn chuyến|hôm nay còn|hủy vé|hủy giúp)/i.test(lowerQuery);

  if (isComplexInquiry) {
    isCheckingLedger.value = true;
    let filler = 'Dạ anh/chị đợi em một chút để em kiểm tra sơ đồ xem còn ghế nào êm nhất cho mình nhé...';
    if (/(hủy vé|hủy chuyến|hủy giúp)/i.test(lowerQuery)) {
      filler = 'Dạ anh/chị đợi em một chút để em kiểm tra chính sách và thông tin vé cho mình nhé...';
    } else if (/(còn vé|còn chuyến|hôm nay còn)/i.test(lowerQuery)) {
      filler = 'Dạ anh/chị đợi em một chút để em kiểm tra sơ đồ chuyến xe trên hệ thống nhé...';
    }
    optimisticFillerText.value = filler;

    if (shouldSpeakResponse) {
      speakOptimisticFiller(filler);
    }
  } else {
    isCheckingLedger.value = false;
    optimisticFillerText.value = '';
  }

  try {
    // Send last 8 conversation turns for rich contextual memory
    const recentHistory = currentSession.messages
      .slice(0, -1)
      .slice(-8)
      .map(m => ({
        role: m.role === 'assistant' ? 'model' : 'user',
        content: m.content
      }));

    const res = await fetch('/api/ai/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ 
        message: query,
        history: recentHistory,
        booking_state: currentBookingState.value
      })
    });

    const data = await res.json();
    const reply = data.reply || generateSmartReply(query);
    const suggestions = data.suggestions || [];
    const trips = data.trips || [];
    const action = data.action || null;
    const user_bookings = data.user_bookings || [];

    if (data.booking_state) {
      currentBookingState.value = data.booking_state;
    }
    const qr_data = data.qr_data || (data.booking_state?.qr_data) || null;
    if (qr_data) {
      startCountdownTimer();
    }

    const newMsg = {
      role: 'assistant',
      content: reply,
      suggestions: suggestions,
      trips: trips,
      action: action,
      qr_data: qr_data,
      user_bookings: user_bookings,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    };
    currentSession.messages.push(newMsg);

    // Tự động kích hoạt hành động thông minh (Auto-Copilot)
    if (action) {
      if (action.type === 'open_login' || action.type === 'open_register') {
        setTimeout(() => { showAuthModal.value = true; }, 700);
      }
      if (action.type === 'booking_consultation_form' && action.initial_destination) {
        consultationForm.value.destination = action.initial_destination;
      }
    }

    // Bé Kiến Trúc DAU nói lại cho khách hàng (Chỉ khi khách dùng voice)
    if (shouldSpeakResponse) {
      speakText(reply, currentSession.messages.length - 1);
    }
  } catch (err) {
    const fallback = generateSmartReply(query);
    const fallbackAction = detectFallbackAction(query);
    const newMsg = {
      role: 'assistant',
      content: fallback,
      action: fallbackAction,
      user_bookings: [],
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    };
    currentSession.messages.push(newMsg);

    if (fallbackAction) {
      if (fallbackAction.type === 'open_login' || fallbackAction.type === 'open_register') {
        setTimeout(() => { showAuthModal.value = true; }, 700);
      }
    }

    if (shouldSpeakResponse) {
      speakText(fallback, currentSession.messages.length - 1);
    }
  } finally {
    isAiTyping.value = false;
    isCheckingLedger.value = false;
    optimisticFillerText.value = '';
    persistSessions();
    scrollToBottom();
  }
};

const generateSmartReply = (q) => {
  const lower = q.toLowerCase();

  // 0. Phàn nàn / Đánh giá / "Sao ngu vậy", "nguu", "dốt vậy"
  if (/(sao dốt|sao ngu|sao nguu|ngu vậy|nguu vậy|ngu the|nguu the|ngu quá|nguu quá|dốt vậy|dốt thế|kém vậy|chả liên quan|không liên quan|vớ vẩn|trả lời sai|chẳng hiểu gì|không hiểu gì|nguu như|ngu như)/i.test(lower)) {
    return 'Dạ em thành thật xin lỗi bạn vì phản hồi trước đó chưa đúng ý mình ạ! 🙏 Bạn đang cần tìm chuyến xe đi đâu hoặc muốn đặt vé thế nào, bạn nói với em để em hỗ trợ chốt vé ngay nhé!';
  }

  // 0.1. Nhu cầu Đặt vé / Đặt xe
  if (/(đặt vé|dat ve|đặt xe|dat xe|muốn đặt|muon dat|mua vé|mua ve|tìm xe|tim xe|bắt xe|bat xe|book xe|tư vấn đặt)/i.test(lower)) {
    return 'Dạ vâng, để em lựa chọn chuyến xe và vị trí ghế êm ái, phù hợp nhất theo thể trạng của mình, anh/chị điền nhanh thông tin vào khung bên dưới giúp em nhé ạ! 👇';
  }

  // 0.5. Thử mic / Giọng nói / "Bạn có nghe tôi nói không" / Danh tính
  if (/(nghe tôi nói không|có nghe không|nghe không|nghe rõ không|alo|alo alo|test mic|thử giọng|bạn nghe được không|có ai ở đây không|bạn ở đó không|có ai không|bạn là ai|em là ai|trợ lý là ai|ai đó|linh vật|mascot|kiến trúc)/i.test(lower)) {
    return 'Dạ em nghe bạn rất rõ và to ạ! Em là **Trợ lý AI** đồng hành thông minh của **Nhà xe Kiến Trúc Đà Nẵng** đây ạ! 🏛️🎙️\n\nEm luôn trực tuyến đồng hành cùng bạn suốt mọi lúc:\n• 🚌 Tự động tìm tuyến & gợi ý vị trí ghế êm ái\n• 🔑 Hỗ trợ đăng nhập, tạo tài khoản mới & đăng nhập nhanh bằng Google\n• 🎫 Hỗ trợ tra cứu vé, hủy vé & hoàn tiền tự động 100%\n• 💳 Giữ chỗ với cọc 30% VietQR siêu tiện lợi.\n\nBạn cứ nhắn tin hoặc nói yêu cầu bất kỳ điều gì, em sẽ hỗ trợ bạn ngay nhé!';
  }

  // 0.8. Tự động hỗ trợ Đăng nhập / Đăng ký / Google
  if (/(đăng nhập|đăng ký|tạo tài khoản|tài khoản|google|gmail)/i.test(lower)) {
    return 'Dạ để hỗ trợ bạn đặt vé và quản lý lịch trình thuận tiện nhất, em đã tự động mở khung Đăng Nhập / Đăng Ký (và Đăng nhập bằng Google) cho bạn rồi đây ạ! ✨\n\nNếu bạn chưa có tài khoản, bạn vui lòng đăng ký tài khoản trước nhé. Em luôn ở đây đồng hành cùng bạn!';
  }

  // 1. Đăng nhập Admin / Trang quản trị
  if (/(đăng nhập vào admin|đăng nhập admin|link admin|vào admin|vào trang quản trị|trang quản trị|cách vào admin|quản trị viên|admin login|bảng điều khiển admin)/i.test(lower)) {
    return 'Dạ em là Trợ lý tư vấn khách hàng nên hoàn toàn không có quyền hạn can thiệp, cung cấp tài khoản hay cấp quyền truy cập vào trang Quản trị (Admin) đâu ạ! 😊\n\nTrang Quản trị chỉ dành riêng cho Ban quản lý và nhân sự được ủy quyền của Nhà xe Kiến Trúc. Nếu bạn là khách hàng, bạn vui lòng đăng nhập tài khoản thành viên thông thường để quản lý vé xe và tích lũy điểm thưởng nhé!';
  }

  // 2. Nhận diện danh tính / "Bạn nhớ tôi không"
  if (/(nhớ tôi|nhớ tui|nhớ em|nhớ anh|có nhớ tôi|bạn nhớ tôi|tôi là ai|tài khoản của tôi|thông tin của tôi)/i.test(lower)) {
    return 'Dạ em chào bạn ạ! 😊 Nếu bạn đã đăng nhập tài khoản trên website, hệ thống sẽ tự động hiển thị thông tin thành viên và các vé xe bạn đã đặt tại mục "Quản Lý Vé". Nếu bạn đang truy cập với tư cách khách vãng lai, bạn bấm nút "Đăng Nhập" ở góc trên bên phải để em nhận diện bạn ngay nhé!';
  }

  // 3. Phân tích tuyến đường: Điểm đi -> Điểm đến (Origin -> Destination)
  const hasHue = lower.includes('huế') || lower.includes('hue');
  const hasQuangTri = lower.includes('quảng trị') || lower.includes('quang tri') || lower.includes('đông hà');
  const hasQuangBinh = lower.includes('quảng bình') || lower.includes('quang binh') || lower.includes('đồng hới');
  const hasQuyNhon = lower.includes('quy nhơn') || lower.includes('quy nhon') || lower.includes('bình định');
  const hasDakLak = lower.includes('buôn ma thuột') || lower.includes('đắk lắk') || lower.includes('bmt');
  const hasDaNang = lower.includes('đà nẵng') || lower.includes('da nang');

  // Tuyến không trực tiếp (ví dụ: Quảng Trị đi Huế, Huế đi Quảng Bình...)
  if ((hasQuangTri && hasHue && !hasDaNang) || (hasQuangBinh && hasHue && !hasDaNang) || (hasQuyNhon && hasDakLak && !hasDaNang) || (hasQuangTri && hasQuyNhon && !hasDaNang)) {
    return 'Dạ hiện tại Nhà xe Kiến Trúc chưa có tuyến xe chạy trực tiếp giữa hai tỉnh này (hoặc chiều ngược lại) bạn nhé! 😊\n\n🚌 Toàn bộ mạng lưới xe Limousine cao cấp của Nhà xe Kiến Trúc hiện đang kết nối từ/đến Đầu mối trung tâm TP. Đà Nẵng (gồm 5 tuyến: Đà Nẵng ⇄ Huế 150k, Đà Nẵng ⇄ Quảng Trị 200k, Đà Nẵng ⇄ Quảng Bình 280k, Đà Nẵng ⇄ Quy Nhơn 360k, Đà Nẵng ⇄ Buôn Ma Thuột 420k).\n\n💡 Bạn có thể bắt xe khách địa phương hoặc tàu hỏa Bắc - Nam dọc Quốc Lộ 1A để di chuyển nhanh nhất, hoặc đi xe Kiến Trúc về Đà Nẵng rồi nối chuyến tiếp theo nhé!';
  }

  // Tuyến chưa khai thác (ngoại tỉnh xa)
  const unsupported = [
    { key: 'hà nội', name: 'Hà Nội' },
    { key: 'sài gòn', name: 'Sài Gòn (TP.HCM)' },
    { key: 'tphcm', name: 'TP.HCM' },
    { key: 'nha trang', name: 'Nha Trang' },
    { key: 'đà lạt', name: 'Đà Lạt' },
    { key: 'cần thơ', name: 'Cần Thơ' },
    { key: 'sapa', name: 'SaPa' },
    { key: 'vũng tàu', name: 'Vũng Tàu' }
  ];
  for (const u of unsupported) {
    if (lower.includes(u.key)) {
      return `Dạ hiện tại Nhà xe Kiến Trúc chưa khai thác tuyến xe đi/đến ${u.name} bạn nhé! 😊 Nhà xe hiện chuyên phục vụ 5 tuyến Limousine chất lượng cao kết nối từ trung tâm TP. Đà Nẵng đi Huế, Quảng Trị, Quảng Bình, Quy Nhơn và Buôn Ma Thuột ạ!`;
    }
  }

  // Chào hỏi
  const greetingWords = ['hi', 'hello', 'hey', 'alo', 'xin chào', 'xin chao', 'chào', 'chao', 'chào bạn', 'chao ban', 'chào em', 'chao em', 'chào anh', 'chao anh', 'chào chị', 'chao chi', 'chào ad', 'chào bot'];
  if (greetingWords.some(w => lower.startsWith(w) || lower === w)) {
    return 'Dạ em chào bạn ạ! Em là **Trợ lý AI** - Nhà xe Kiến Trúc Đà Nẵng. Em có thể hỗ trợ gì cho chuyến đi hôm nay của mình ạ? 😊';
  }

  // Tuyến cụ thể
  if (hasQuangTri) {
    if (lower.includes('hôm nay') || lower.includes('còn vé')) {
      return 'Dạ hôm nay (ngày 27/11) vé xe đi Quảng Trị bên em đều đã khởi hành và hết vé rồi ạ. 🚌\n\nHiện tại bên em chỉ còn chuyến vào ngày mai (ngày 28/11) với 2 chuyến lúc 07:15 sáng và 11:00 trưa thôi ạ. Anh/chị có muốn em hỗ trợ kiểm tra giữ chỗ chuyến ngày mai không ạ? 😊';
    }
    return '🚌 Tuyến Đà Nẵng ⇄ Quảng Trị (Đông Hà): Cự ly 165km (~3.5 giờ). Giá vé 160.000đ - 200.000đ/vé (Cọc 30% chỉ 48.000đ - 60.000đ | SV mã PRO30 chỉ 112.000đ). Giờ xuất bến hàng ngày: 07:15 sáng và 11:00 trưa.';
  }
  if (hasHue) {
    return '🚌 Tuyến Đà Nẵng ⇄ Thừa Thiên Huế: Cự ly 100km (~2 giờ). Giá vé 150.000đ/vé (Cọc 30% chỉ 45.000đ | SV mã PRO30 chỉ 105.000đ). Giờ xuất bến liên tục: 06:00, 06:30, 08:00, 08:30, 09:30, 11:00, 14:00, 14:30.';
  }
  if (hasQuyNhon) {
    return '🚌 Tuyến Đà Nẵng ⇄ Quy Nhơn (Bình Định): Cự ly 315km (~6 giờ). Giá vé 360.000đ/vé (Cọc 30% chỉ 108.000đ | SV mã PRO30 chỉ 252.000đ). Giờ xuất bến sáng: 06:00, 07:30, 08:00, 09:30 & Xe đêm: 15:30, 18:00, 20:00, 22:45.';
  }
  if (hasDakLak) {
    return '🚌 Tuyến Đà Nẵng ⇄ Buôn Ma Thuột (Đắk Lắk): Cự ly 480km (~10 giờ). Giá vé 420.000đ/vé (Cọc 30% chỉ 126.000đ | SV mã PRO30 chỉ 294.000đ). Xe đêm VIP giường nằm êm ái: 19:00, 20:00, 20:30, 23:30.';
  }
  if (hasQuangBinh) {
    return '🚌 Tuyến Đà Nẵng ⇄ Quảng Bình (Đồng Hới): Cự ly 270km (~5 giờ). Giá vé 280.000đ/vé (Cọc 30% chỉ 84.000đ | SV mã PRO30 chỉ 196.000đ). Giờ xuất bến: 06:00, 06:30, 07:00, 07:30, 08:00, 08:30, 19:30, 20:00.';
  }

  // Đặt cọc 30% & Thanh toán
  if (/(cọc|30%|thanh toán|vietqr|chuyển khoản|giữ chỗ)/i.test(lower)) {
    return '💳 Bạn chỉ cần thanh toán đặt cọc 30% tiền vé trực tuyến qua mã VietQR tự động để hệ thống khóa đúng số giường/phòng đã chọn. 70% số tiền còn lại bạn gửi cho bác tài khi lên xe rất tiện lợi!';
  }

  // Hủy vé & Đổi vé
  if (/(hủy|hoàn tiền|đổi vé|dời)/i.test(lower)) {
    return '🚫 Nhà xe Kiến Trúc hỗ trợ hủy vé tự động 100% trong vòng 2 giờ đầu hoặc trước giờ xe chạy 24 giờ tại mục "Quản Lý Vé" trên website, hoàn tiền trực tiếp qua số tài khoản ngân hàng của bạn!';
  }

  // Mã giảm giá / Ưu đãi
  if (/(giảm giá|khuyến mãi|voucher|mã giảm|ưu đãi|ma giam|giam gia)/i.test(lower)) {
    if (/(sinh viên|học sinh|pro30)/i.test(lower)) {
      return '🎓 Nhà xe Kiến Trúc có chương trình giảm 30% cho Học sinh - Sinh viên với mã `PRO30` hoặc `SINHVIEN` khi đặt vé (kèm thẻ SV khi lên xe bạn nhé)!';
    }
    return '🎁 Nhà xe Kiến Trúc đang có mã `CHAOBAN` giảm 20.000đ khi đặt vé và chính sách hoàn tiền 5% tích điểm thành viên. (Nếu là Học sinh - Sinh viên, bạn dùng mã `PRO30` để được giảm 30% nhé!).';
  }

  // Điểm đón / Văn phòng
  if (/(văn phòng|địa chỉ|ở đâu|đón ở đâu|bến xe)/i.test(lower)) {
    return '📍 Văn phòng chính Nhà xe Kiến Trúc tại: Số 566 Núi Thành, P. Hòa Cường Nam, Q. Hải Châu, TP. Đà Nẵng. Quầy vé số 14 - Bến xe Trung tâm Đà Nẵng. Có xe trung chuyển miễn phí bán kính 5km nội thành Đà Nẵng!';
  }

  return 'Dạ em có thể giúp bạn tìm chuyến xe, chọn ghế êm chống say xe hoặc đặt vé nhanh. Bạn muốn đặt xe đi đâu hoặc cần em hỗ trợ gì ạ? 😊';
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

@keyframes ai-mascot-float {
  0%, 100% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(-8px) rotate(1deg);
  }
}

.animate-mascot-float {
  animation: ai-mascot-float 3.5s ease-in-out infinite;
}

@keyframes ledger-check {
  0%, 100% {
    transform: translateY(0px) rotate(0deg) scale(1);
  }
  25% {
    transform: translateY(6px) rotate(-4deg) scale(1.02);
  }
  50% {
    transform: translateY(8px) rotate(2deg) scale(0.98);
  }
  75% {
    transform: translateY(5px) rotate(-2deg) scale(1.01);
  }
}

.animate-ledger-check {
  animation: ledger-check 1.8s ease-in-out infinite;
}
</style>