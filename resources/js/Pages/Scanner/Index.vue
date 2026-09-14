<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-900">
    <!-- Scanner Header -->
    <header class="bg-white border-b border-slate-200 p-4 sticky top-0 z-50">
      <div class="max-w-xl mx-auto flex items-center justify-between">
        <a href="/" class="text-xs text-slate-400 hover:text-slate-900 flex items-center gap-1">
          &larr; Về trang chủ
        </a>
        <div class="text-center">
          <h1 class="text-base font-black text-slate-900">HỆ THỐNG SOÁT VÉ QR CODE</h1>
          <p class="text-[11px] text-amber-400 font-medium">Dành cho Nhân viên bến xe / Lơ xe</p>
        </div>
        <div class="w-12"></div>
      </div>
    </header>

    <main class="max-w-xl mx-auto p-4 flex-1 w-full space-y-6">
      <!-- Camera Viewport Card -->
      <div class="bg-white border border-slate-200 shadow-xs rounded-3xl p-6 shadow-2xl relative overflow-hidden">
        <div class="text-center mb-4">
          <p class="text-xs text-slate-400">Hướng camera điện thoại vào mã QR vé của hành khách</p>
        </div>

        <div class="relative rounded-2xl overflow-hidden bg-black aspect-square max-w-xs mx-auto border-2 border-blue-500/40">
          <div id="qr-reader" class="w-full h-full"></div>
          <!-- Scanning Laser Animation Line -->
          <div class="absolute inset-x-0 h-0.5 bg-blue-500 shadow-[0_0_12px_#f97316] animate-bounce top-1/2 pointer-events-none"></div>
        </div>

        <div class="mt-4 flex gap-2">
          <button
            v-if="!cameraActive"
            @click="startScanner"
            class="flex-1 py-3 rounded-2xl bg-blue-600 hover:bg-blue-600 font-bold text-sm text-slate-900 transition-colors cursor-pointer"
          >
            Bật Camera Quét Mã
          </button>
          <button
            v-else
            @click="stopScanner"
            class="flex-1 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 font-bold text-sm text-slate-700 transition-colors cursor-pointer"
          >
            Tắt Camera
          </button>
        </div>
      </div>

      <!-- Manual QR Token Input (For Testing/Demo) -->
      <div class="bg-white/60 border border-slate-200 p-4 rounded-2xl">
        <label class="text-xs font-bold text-slate-400 block mb-1">Hoặc nhập mã vé/mã QR để kiểm tra:</label>
        <div class="flex gap-2">
          <input
            v-model="manualToken"
            type="text"
            placeholder="Ví dụ: SB-XXXXXX hoặc mã QR..."
            class="flex-1 bg-[#F8FAFC] border border-slate-300 rounded-xl px-3 py-2 text-xs text-slate-900"
          />
          <button
            @click="verifyToken(manualToken)"
            class="px-4 py-2 bg-slate-100 hover:bg-blue-600 text-slate-900 font-bold text-xs rounded-xl transition-colors"
          >
            Kiểm tra
          </button>
        </div>
      </div>

      <!-- Verified Ticket Modal / Card -->
      <div v-if="verifiedTicket" class="bg-white border-2 border-blue-500 p-6 rounded-3xl space-y-4 animate-fade-in shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
          <div>
            <span class="text-xs text-slate-400">Mã vé:</span>
            <span class="text-lg font-black text-blue-600 font-mono block">{{ verifiedTicket.booking_code }}</span>
          </div>
          <span
            :class="verifiedTicket.checkin_status === 'boarded' ? 'bg-rose-500/20 text-rose-400 border-rose-500/30' : 'bg-green-500/20 text-green-400 border-green-500/30'"
            class="px-3 py-1 rounded-full text-xs font-bold border"
          >
            {{ verifiedTicket.checkin_status === 'boarded' ? 'ĐÃ CHECK-IN TRƯỚC ĐÓ' : 'HỢP LỆ - CHƯA LÊN XE' }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-3 text-xs text-slate-700">
          <div>
            <span class="text-slate-400 block">Hành khách:</span>
            <span class="font-bold text-slate-900 text-sm">{{ verifiedTicket.customer_name }}</span>
          </div>
          <div>
            <span class="text-slate-400 block">SĐT:</span>
            <span class="font-bold text-slate-900 text-sm">{{ verifiedTicket.customer_phone }}</span>
          </div>
          <div>
            <span class="text-slate-400 block">Vị trí ghế:</span>
            <span class="text-base font-black text-amber-400">{{ verifiedTicket.seats.join(', ') }}</span>
          </div>
          <div>
            <span class="text-slate-400 block">Chuyến:</span>
            <span class="font-semibold text-slate-900">{{ verifiedTicket.from_station }} ➔ {{ verifiedTicket.to_station }}</span>
          </div>
        </div>

        <!-- Financial Breakdown & Action -->
        <div class="bg-[#F8FAFC] p-4 rounded-2xl border border-slate-200 space-y-2">
          <div class="flex justify-between text-xs text-slate-400">
            <span>Tổng vé:</span>
            <span class="text-slate-900 font-bold">{{ formatPrice(verifiedTicket.total_amount) }}đ</span>
          </div>
          <div class="flex justify-between text-xs text-green-400 font-bold">
            <span>Đã cọc online (30%):</span>
            <span>{{ formatPrice(verifiedTicket.deposit_amount) }}đ</span>
          </div>
          <div class="flex justify-between text-sm font-black text-amber-400 pt-2 border-t border-slate-200">
            <span>CẦN THU NỐT (70%):</span>
            <span>{{ formatPrice(verifiedTicket.remaining_amount) }}đ</span>
          </div>
        </div>

        <div v-if="verifiedTicket.checkin_status !== 'boarded'">
          <button
            @click="confirmCheckin"
            :disabled="checkingIn"
            class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-slate-900 font-black text-base shadow-xl shadow-green-500/30 transition-all cursor-pointer flex items-center justify-center gap-2"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
            XÁC NHẬN ĐÃ THU {{ formatPrice(verifiedTicket.remaining_amount) }}đ & CHECK-IN
          </button>
        </div>
        <div v-else class="text-center p-3 bg-rose-500/10 rounded-2xl text-xs text-rose-400 font-bold">
          ⚠️ Vé này đã được check-in lúc {{ verifiedTicket.checked_in_at }}
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';
import axios from 'axios';

const cameraActive = ref(false);
let html5QrCode = null;
const manualToken = ref('');
const verifiedTicket = ref(null);
const checkingIn = ref(false);

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(val || 0);

const startScanner = async () => {
  try {
    html5QrCode = new Html5Qrcode('qr-reader');
    await html5QrCode.start(
      { facingMode: 'environment' },
      { fps: 10, qrbox: 250 },
      (decodedText) => {
        verifyToken(decodedText);
      },
      () => {}
    );
    cameraActive.value = true;
  } catch (err) {
    alert('Không thể mở camera. Bạn có thể nhập mã vé trực tiếp để kiểm tra!');
  }
};

const stopScanner = async () => {
  if (html5QrCode && cameraActive.value) {
    await html5QrCode.stop();
    cameraActive.value = false;
  }
};

const verifyToken = async (token) => {
  if (!token) return;
  try {
    const res = await axios.post('/scanner/verify', { qr_token: token });
    if (res.data.valid) {
      verifiedTicket.value = res.data.booking;
    } else {
      alert(res.data.message || 'Mã QR không hợp lệ!');
    }
  } catch (err) {
    alert('Không tìm thấy thông tin vé!');
  }
};

const confirmCheckin = async () => {
  if (!verifiedTicket.value) return;
  checkingIn.value = true;
  try {
    const res = await axios.post('/scanner/checkin', {
      booking_id: verifiedTicket.value.id,
      staff_name: 'Nhân viên soát vé quầy',
    });
    if (res.data.success) {
      alert(res.data.message);
      verifiedTicket.value.checkin_status = 'boarded';
      verifiedTicket.value.checked_in_at = res.data.checked_in_at;
    } else {
      alert(res.data.message);
    }
  } catch (err) {
    alert('Lỗi check-in');
  } finally {
    checkingIn.value = false;
  }
};

onMounted(() => {
  startScanner();
});

onUnmounted(() => {
  stopScanner();
});
</script>
