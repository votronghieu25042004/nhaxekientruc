<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-[999999] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md animate-in fade-in duration-300"
    >
      <!-- Vỏ Điện Thoại Thông Minh Mô Phỏng -->
      <div
        class="w-full max-w-sm bg-gradient-to-b from-slate-900 via-slate-900 to-black text-white rounded-[40px] shadow-2xl border-4 border-slate-700/60 overflow-hidden relative flex flex-col min-h-[640px] justify-between p-6 select-none"
      >
        <!-- Loa thoại & Camera trước (Dynamic Island) -->
        <div class="flex justify-center mb-4">
          <div class="h-5 w-28 bg-black rounded-full flex items-center justify-center gap-2 px-2 border border-slate-800">
            <span class="w-2.5 h-2.5 rounded-full bg-slate-900 border border-slate-700"></span>
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          </div>
        </div>

        <!-- MÀN HÌNH ĐANG ĐỔ CHUÔNG (RINGING) -->
        <div v-if="callState === 'ringing'" class="flex-1 flex flex-col items-center justify-between py-6 text-center animate-in zoom-in-95 duration-200">
          <div class="space-y-3">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-500/20 text-red-300 border border-red-500/30 text-xs font-bold tracking-wide uppercase">
              <span class="animate-ping inline-block w-2 h-2 rounded-full bg-red-400"></span>
              Cuộc gọi đến từ AI Tổng Đài
            </div>
            <h3 class="text-2xl font-black tracking-tight text-white">Nhà Xe Kiến Trúc</h3>
            <p class="text-sm font-semibold text-slate-400 font-mono">1900 6868 • AI Call Assistant</p>
            <p class="text-xs text-amber-300 font-medium bg-amber-950/40 px-3 py-1 rounded-xl border border-amber-600/30 max-w-xs mx-auto">
              🧧 Nhắc lịch xuất bến: Vé 0-Đồng Tết Sinh Viên
            </p>
          </div>

          <!-- Avatar AI Đang Rung Chuông -->
          <div class="relative my-8">
            <div class="absolute -inset-4 bg-emerald-500/20 rounded-full animate-ping pointer-events-none"></div>
            <div class="absolute -inset-8 bg-red-500/10 rounded-full animate-pulse pointer-events-none"></div>
            <div class="w-32 h-32 rounded-full bg-gradient-to-tr from-red-600 via-rose-600 to-amber-500 p-1 shadow-2xl relative z-10 flex items-center justify-center">
              <div class="w-full h-full bg-slate-950 rounded-full flex flex-col items-center justify-center">
                <span class="text-5xl">🤖</span>
                <span class="text-[10px] font-black uppercase text-amber-400 tracking-wider mt-1">AI Voice</span>
              </div>
            </div>
          </div>

          <!-- Nút Nghe Máy & Từ Chối Cuộc Gọi -->
          <div class="w-full flex items-center justify-around px-4 pt-6">
            <!-- Nút Từ chối -->
            <button
              type="button"
              @click="declineCall"
              class="flex flex-col items-center gap-2 group cursor-pointer"
            >
              <div class="w-16 h-16 rounded-full bg-red-600 hover:bg-red-700 active:scale-95 transition shadow-lg shadow-red-600/40 flex items-center justify-center text-2xl text-white">
                ✕
              </div>
              <span class="text-xs font-semibold text-slate-400 group-hover:text-white">Từ chối</span>
            </button>

            <!-- Nút Bắt máy -->
            <button
              type="button"
              @click="acceptCall"
              class="flex flex-col items-center gap-2 group cursor-pointer"
            >
              <div class="w-16 h-16 rounded-full bg-emerald-500 hover:bg-emerald-600 active:scale-95 transition shadow-xl shadow-emerald-500/50 flex items-center justify-center text-3xl text-white animate-bounce">
                📞
              </div>
              <span class="text-xs font-bold text-emerald-400 group-hover:text-emerald-300">Nghe máy</span>
            </button>
          </div>
        </div>

        <!-- MÀN HÌNH ĐANG THỰC HIỆN CUỘC GỌI (CONNECTED) -->
        <div v-else-if="callState === 'connected'" class="flex-1 flex flex-col justify-between py-2 text-center animate-in fade-in duration-300">
          <div class="space-y-1">
            <h3 class="text-xl font-black text-white">Nhà Xe Kiến Trúc</h3>
            <p class="text-xs font-mono text-emerald-400 font-bold flex items-center justify-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
              Đang kết nối • {{ callDurationFormatted }}
            </p>
          </div>

          <!-- Sóng âm Visualizer -->
          <div class="my-4 px-2">
            <div class="flex items-center justify-center gap-1.5 h-16 bg-slate-800/60 rounded-2xl p-3 border border-slate-700/50">
              <div
                v-for="(bar, idx) in audioBars"
                :key="idx"
                class="w-1.5 rounded-full bg-gradient-to-t from-emerald-500 to-amber-400 transition-all duration-100"
                :style="{ height: `${bar}%` }"
              ></div>
            </div>
          </div>

          <!-- Hộp Thoại Lời Nhắc Của AI (Transcription) -->
          <div class="flex-1 bg-slate-800/40 border border-slate-700/60 rounded-2xl p-4 text-left overflow-y-auto max-h-52 text-xs leading-relaxed space-y-2.5 shadow-inner">
            <div class="flex items-center gap-2 text-[11px] font-bold text-amber-400 uppercase tracking-wider">
              <span>🎙️ Lời Thoại AI Đang Phát:</span>
            </div>
            <p class="text-slate-200 font-medium">
              "{{ transcriptDisplay }}"
            </p>
            <div v-if="speechFinished" class="p-2 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-[11px] font-semibold text-center">
              ✓ Cuộc gọi thông báo hoàn tất. Bác tài và nhà xe hẹn gặp bạn tại bến!
            </div>
          </div>

          <!-- Controls trong cuộc gọi -->
          <div class="w-full flex items-center justify-center gap-6 pt-4">
            <button
              type="button"
              @click="toggleMute"
              class="w-12 h-12 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-lg text-slate-300 transition"
              :title="isMuted ? 'Bật âm' : 'Tắt âm'"
            >
              {{ isMuted ? '🔇' : '🔊' }}
            </button>
            <button
              type="button"
              @click="restartSpeech"
              class="w-12 h-12 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-lg text-amber-300 transition"
              title="Nghe lại từ đầu"
            >
              🔄
            </button>
            <button
              type="button"
              @click="hangUp"
              class="w-14 h-14 rounded-full bg-red-600 hover:bg-red-700 active:scale-95 transition shadow-lg shadow-red-600/50 flex items-center justify-center text-2xl text-white"
              title="Kết thúc cuộc gọi"
            >
              📞
            </button>
          </div>
        </div>

        <!-- MÀN HÌNH KẾT THÚC CUỘC GỌI (ENDED) -->
        <div v-else class="flex-1 flex flex-col items-center justify-center text-center space-y-4 py-8 animate-in zoom-in-95 duration-200">
          <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center text-2xl text-slate-400">
            📵
          </div>
          <h4 class="text-lg font-black text-white">Cuộc Gọi Đã Kết Thúc</h4>
          <p class="text-xs text-slate-400 max-w-xs">
            Thời lượng: <span class="font-mono text-emerald-400 font-bold">{{ callDurationFormatted }}</span>. AI sẽ tự động kích hoạt cuộc gọi thật vào số điện thoại của bạn trước chuyến đi!
          </p>
          <div class="pt-4 flex items-center gap-3">
            <button
              type="button"
              @click="startCall"
              class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white transition"
            >
              🔄 Gọi Lại Thử
            </button>
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-xs font-black text-white transition shadow-sm"
            >
              Đóng Cửa Sổ
            </button>
          </div>
        </div>

        <!-- Footer Ghi Chú Tính Năng Độc Quyền -->
        <div class="pt-3 border-t border-slate-800 text-center">
          <p class="text-[10px] text-slate-500 font-medium">
            Hệ thống AI Tự Động Nhắc Lịch • Độc Quyền Nhà Xe Kiến Trúc
          </p>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';

const props = defineProps({
  studentName: {
    type: String,
    default: 'Bạn Sinh Viên Kiến Trúc',
  },
  tripInfo: {
    type: Object,
    default: () => ({
      route: 'Đà Nẵng ⇄ Đắk Lắk (Buôn Ma Thuột)',
      time: '07:30 Sáng Mai',
      seat: 'Ghế A04 (Tầng Dưới)',
      plate: '43B-999.88',
    }),
  },
});

const isOpen = ref(false);
const callState = ref('ringing'); // 'ringing' | 'connected' | 'ended'
const callDuration = ref(0);
const isMuted = ref(false);
const speechFinished = ref(false);
const transcriptDisplay = ref('');
const audioBars = ref([20, 45, 80, 60, 30, 90, 75, 40, 65, 85, 30, 50, 70, 95, 40, 20]);

let callTimer = null;
let animTimer = null;
let audioContext = null;
let ringOscillator = null;

const fullSpeechText = computed(() => {
  return `Dạ xin chào ${props.studentName}! Đây là cuộc gọi tự động từ Trợ Lý Trí Tuệ Nhân Tạo của Nhà Xe Kiến Trúc. Hệ thống xin thông báo xác nhận: Bạn đã giữ thành công vé 0 đồng về quê đón Tết trên tuyến xe ${props.tripInfo.route}. Chuyến xe xuất bến lúc ${props.tripInfo.time}, số xe ${props.tripInfo.plate}. Bác tài và lơ xe đã sẵn sàng đón bạn, đồ án mô hình được hỗ trợ chở miễn phí hoàn toàn. Chúc bạn có một hành trình sum vầy ấm áp và thượng lộ bình an cùng Nhà Xe Kiến Trúc!`;
});

const callDurationFormatted = computed(() => {
  const mins = Math.floor(callDuration.value / 60);
  const secs = callDuration.value % 60;
  return `${mins < 10 ? '0' : ''}${mins}:${secs < 10 ? '0' : ''}${secs}`;
});

// Phát âm thanh chuông điện thoại bằng Web Audio API
const startRingtone = () => {
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;
    audioContext = new AudioCtx();
    
    const playBeep = () => {
      if (callState.value !== 'ringing' || !audioContext) return;
      const osc = audioContext.createOscillator();
      const gain = audioContext.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(440, audioContext.currentTime);
      gain.gain.setValueAtTime(0.08, audioContext.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 1.2);
      osc.connect(gain);
      gain.connect(audioContext.destination);
      osc.start();
      osc.stop(audioContext.currentTime + 1.2);
    };

    playBeep();
    const ringInterval = setInterval(() => {
      if (callState.value !== 'ringing') {
        clearInterval(ringInterval);
      } else {
        playBeep();
      }
    }, 2800);
  } catch (e) {}
};

const openCall = () => {
  isOpen.value = true;
  startCall();
};

const startCall = () => {
  callState.value = 'ringing';
  callDuration.value = 0;
  speechFinished.value = false;
  transcriptDisplay.value = '';
  startRingtone();
};

const declineCall = () => {
  callState.value = 'ended';
};

const hangUp = () => {
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel();
  }
  clearInterval(callTimer);
  clearInterval(animTimer);
  callState.value = 'ended';
};

const closeModal = () => {
  hangUp();
  isOpen.value = false;
};

const toggleMute = () => {
  isMuted.value = !isMuted.value;
  if ('speechSynthesis' in window) {
    if (isMuted.value) {
      window.speechSynthesis.pause();
    } else {
      window.speechSynthesis.resume();
    }
  }
};

const speakText = (text) => {
  if (!('speechSynthesis' in window)) {
    typewriterEffect(text);
    return;
  }

  window.speechSynthesis.cancel();
  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang = 'vi-VN';
  utterance.rate = 1.0;
  utterance.pitch = 1.05;

  // Tìm giọng Tiếng Việt nếu có
  const voices = window.speechSynthesis.getVoices();
  const viVoice = voices.find(v => v.lang.includes('vi') || v.lang.includes('VN'));
  if (viVoice) utterance.voice = viVoice;

  utterance.onend = () => {
    speechFinished.value = true;
    audioBars.value = audioBars.value.map(() => 15);
  };

  utterance.onerror = () => {
    speechFinished.value = true;
  };

  window.speechSynthesis.speak(utterance);
  typewriterEffect(text);
};

const typewriterEffect = (text) => {
  let curIndex = 0;
  transcriptDisplay.value = '';
  const interval = setInterval(() => {
    if (callState.value !== 'connected') {
      clearInterval(interval);
      return;
    }
    curIndex += 2;
    transcriptDisplay.value = text.slice(0, curIndex);
    if (curIndex >= text.length) {
      clearInterval(interval);
      speechFinished.value = true;
    }
  }, 40);
};

const acceptCall = () => {
  callState.value = 'connected';
  callDuration.value = 0;

  callTimer = setInterval(() => {
    callDuration.value++;
  }, 1000);

  // Sóng âm thanh nhảy ngẫu nhiên khi nói
  animTimer = setInterval(() => {
    if (!speechFinished.value) {
      audioBars.value = audioBars.value.map(() => Math.floor(Math.random() * 85) + 15);
    } else {
      audioBars.value = audioBars.value.map(() => 15);
    }
  }, 120);

  // Bắt đầu đọc lời thoại AI
  speakText(fullSpeechText.value);
};

const restartSpeech = () => {
  speechFinished.value = false;
  speakText(fullSpeechText.value);
};

onUnmounted(() => {
  if ('speechSynthesis' in window) {
    window.speechSynthesis.cancel();
  }
  clearInterval(callTimer);
  clearInterval(animTimer);
});

defineExpose({
  openCall,
  closeModal,
});
</script>
