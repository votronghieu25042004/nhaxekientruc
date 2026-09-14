<template>
  <Teleport to="body">
  <div class="relative z-[99999]">
    <!-- Floating Trigger Button -->
    <button
      @click="isOpen = !isOpen"
      type="button"
      class="fixed bottom-6 left-6 z-[99998] flex items-center gap-2.5 px-4 py-3 rounded-full bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 hover:from-blue-800 hover:to-indigo-800 text-white font-black text-xs shadow-2xl shadow-blue-600/40 hover:scale-105 active:scale-95 transition-all duration-300 cursor-pointer group border border-blue-400/30"
    >
      <div class="relative">
        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-emerald-400 rounded-full border-2 border-slate-900"></span>
      </div>
      <span>Trợ Lý AI BusHub</span>
    </button>

    <!-- Chat Modal Window -->
    <div
      v-if="isOpen"
      class="fixed bottom-24 left-4 sm:left-6 w-[92vw] sm:w-[420px] max-h-[580px] h-[580px] bg-white border border-slate-200 rounded-3xl shadow-2xl z-[99998] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200"
    >
      <!-- Chat Header -->
      <div class="bg-gradient-to-r from-blue-700 via-blue-600 to-indigo-700 p-4 text-white flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-white/15 backdrop-blur-md flex items-center justify-center border border-white/20">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <div>
            <h3 class="font-extrabold text-sm leading-tight text-white">Trợ Lý AI BusHub</h3>
            <p class="text-[11px] text-blue-100 font-medium">Hỗ trợ tra cứu vé & lịch trình 24/7</p>
          </div>
        </div>

        <div class="flex items-center space-x-1">
          <!-- Clear History Button -->
          <button
            @click="clearChatHistory"
            title="Xóa lịch sử chat"
            class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
          
          <!-- Close Button -->
          <button
            @click="isOpen = false"
            class="text-white/80 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition cursor-pointer"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Chat Messages Container -->
      <div ref="messageContainer" class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-slate-50">
        <!-- Message bubble list -->
        <div
          v-for="(msg, idx) in messages"
          :key="idx"
          :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'"
        >
          <div
            :class="[
              'max-w-[85%] rounded-2xl px-4 py-2.5 text-xs leading-relaxed shadow-xs',
              msg.role === 'user'
                ? 'bg-blue-600 text-white rounded-br-none font-medium'
                : 'bg-white text-slate-800 border border-slate-200/80 rounded-bl-none font-medium'
            ]"
          >
            <div class="whitespace-pre-wrap">{{ msg.content }}</div>
            <div
              :class="[
                'text-[9px] mt-1 text-right',
                msg.role === 'user' ? 'text-blue-200' : 'text-slate-400'
              ]"
            >
              {{ msg.time || 'Vừa xong' }}
            </div>
          </div>
        </div>

        <!-- Loading Indicator -->
        <div v-if="isLoading" class="flex justify-start">
          <div class="bg-white border border-slate-200 rounded-2xl px-4 py-3 text-xs flex items-center space-x-2 text-slate-500 shadow-xs">
            <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
            <span>AI đang tìm câu trả lời tốt nhất...</span>
          </div>
        </div>
      </div>

      <!-- Quick Suggestion Chips -->
      <div class="px-3 py-2 bg-white border-t border-slate-100 flex items-center gap-1.5 overflow-x-auto text-[11px]">
        <button
          v-for="chip in suggestionChips"
          :key="chip"
          @click="sendMessage(chip)"
          class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 font-semibold shrink-0 transition cursor-pointer border border-slate-200/60"
        >
          {{ chip }}
        </button>
      </div>

      <!-- Input Bar -->
      <div class="p-3 bg-white border-t border-slate-200 flex items-center gap-2">
        <input
          v-model="inputQuery"
          @keydown.enter="handleEnterKey"
          type="text"
          placeholder="Hỏi về vé xe, lịch trình, chính sách cọc..."
          class="flex-1 px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white"
        />
        <button
          @click="sendMessage(inputQuery)"
          :disabled="!inputQuery.trim() || isLoading"
          class="p-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 transition cursor-pointer shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
          </svg>
        </button>
      </div>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'

const STORAGE_KEY = 'bushub_ai_chat_history'

const isOpen = ref(false)
const inputQuery = ref('')
const isLoading = ref(false)
const messageContainer = ref(null)

const suggestionChips = [
  'Có chuyến Sài Gòn ➔ Đà Lạt không?',
  'Chính sách đặt cọc 30% là sao?',
  'Giá vé tuyến Đà Nẵng ➔ Đông Hà?',
]

const defaultWelcome = {
  role: 'assistant',
  content: 'Xin chào! Tôi là Trợ Lý AI của BusHub Terminal. Bạn cần tìm chuyến xe đi đâu hoặc hỗ trợ thông tin gì hôm nay?',
  time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

const messages = ref([])

onMounted(() => {
  window.addEventListener('open-bushub-ai-chat', () => { isOpen.value = true })

  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved) {
      messages.value = JSON.parse(saved)
    } else {
      messages.value = [defaultWelcome]
      saveToStorage()
    }
  } catch (e) {
    messages.value = [defaultWelcome]
  }
})

const saveToStorage = () => {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(messages.value))
  } catch (e) {
    console.error('Failed to save chat history', e)
  }
}

const clearChatHistory = () => {
  if (confirm('Bạn có chắc muốn xóa toàn bộ lịch sử chat AI?')) {
    messages.value = [defaultWelcome]
    saveToStorage()
  }
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messageContainer.value) {
      messageContainer.value.scrollTop = messageContainer.value.scrollHeight
    }
  })
}

const handleEnterKey = (e) => {
  if (!e.shiftKey) {
    e.preventDefault()
    sendMessage(inputQuery.value)
  }
}

const sendMessage = async (text) => {
  const query = (text || '').trim()
  if (!query || isLoading.value) return

  const now = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })

  // Add User message
  messages.value.push({
    role: 'user',
    content: query,
    time: now
  })
  inputQuery.value = ''
  saveToStorage()
  scrollToBottom()

  isLoading.value = true

  try {
    const res = await fetch('/api/ai/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ message: query })
    })

    const data = await res.json()
    const replyText = data.reply || data.response || 'Xin lỗi, hiện tại tôi chưa thể kết nối máy chủ để tìm dữ liệu. Vui lòng tra cứu trực tiếp trên trang Tìm Chuyến Xe nhé!'

    messages.value.push({
      role: 'assistant',
      content: replyText,
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    })
  } catch (err) {
    messages.value.push({
      role: 'assistant',
      content: 'Đã nhận câu hỏi của bạn. BusHub hỗ trợ đặt chỗ 2 tầng, chọn trạm đón tận nơi và đặt cọc 30% linh hoạt. Bạn có thể chọn chuyến xe trực tiếp trên hệ thống nhé!',
      time: new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    })
  } finally {
    isLoading.value = false
    saveToStorage()
    scrollToBottom()
  }
}
</script>
