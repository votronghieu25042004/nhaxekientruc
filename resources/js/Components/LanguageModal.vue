<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150">
      <!-- Backdrop click to close -->
      <div class="fixed inset-0" @click="closeModal"></div>

      <!-- Modal Card Box -->
      <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm sm:max-w-md overflow-hidden z-10 animate-in zoom-in-95 duration-150 border border-slate-100 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <span class="text-xl">🌐</span>
            <div>
              <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">Chọn Ngôn Ngữ / Select Language</h3>
              <p class="text-[11px] text-slate-400">Thay đổi ngôn ngữ hiển thị trên toàn bộ hệ thống</p>
            </div>
          </div>
          <button
            type="button"
            @click="closeModal"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Options: 6 Ngôn ngữ phong phú -->
        <div class="py-3 max-h-[360px] overflow-y-auto space-y-1.5 custom-scrollbar pr-1">
          <label 
            v-for="lang in SUPPORTED_LANGUAGES" 
            :key="lang.code"
            @click="tempLang = lang.code" 
            class="flex items-center justify-between p-3 rounded-2xl border transition-all cursor-pointer group select-none"
            :class="tempLang === lang.code ? 'border-red-500 bg-red-50/50 shadow-2xs' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
          >
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center font-black text-xs font-mono text-slate-800 border border-slate-200 shrink-0 shadow-2xs group-hover:border-red-300">
                {{ lang.short }}
              </div>
              <div>
                <span class="text-sm font-bold text-slate-900 block group-hover:text-red-700">{{ lang.label }}</span>
                <span class="text-xs text-slate-500 font-medium">{{ lang.native }}</span>
              </div>
            </div>

            <div class="relative flex items-center justify-center">
              <input type="radio" name="language" :value="lang.code" v-model="tempLang" class="sr-only" />
              <div
                class="w-5 h-5 rounded-full border-2 transition-all flex items-center justify-center"
                :class="tempLang === lang.code ? 'border-[#dc2626] bg-white' : 'border-slate-300 group-hover:border-slate-400'"
              >
                <div v-if="tempLang === lang.code" class="w-2.5 h-2.5 rounded-full bg-[#dc2626]"></div>
              </div>
            </div>
          </label>
        </div>

        <!-- Confirm Button -->
        <div class="pt-3 border-t border-slate-100 flex items-center gap-2">
          <button
            type="button"
            @click="closeModal"
            class="flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs sm:text-sm rounded-2xl transition cursor-pointer"
          >
            Hủy Bỏ
          </button>

          <button
            type="button"
            @click="confirmLanguage"
            class="flex-1 py-3 px-4 bg-[#dc2626] hover:bg-[#b91c1c] active:scale-98 text-white font-bold text-xs sm:text-sm rounded-2xl shadow-md shadow-red-500/25 transition-all text-center cursor-pointer flex items-center justify-center gap-1.5"
          >
            <span>✓ Xác Nhận</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { SUPPORTED_LANGUAGES, getCurrentLanguage, setWebsiteLanguage } from '@/utils/translator';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(props.modelValue);
const currentLang = ref('vi');
const tempLang = ref('vi');

watch(() => props.modelValue, (val) => {
  isOpen.value = val;
  if (val) {
    currentLang.value = getCurrentLanguage();
    tempLang.value = currentLang.value;
  }
});

onMounted(() => {
  currentLang.value = getCurrentLanguage();
  tempLang.value = currentLang.value;
});

const closeModal = () => {
  isOpen.value = false;
  emit('update:modelValue', false);
};

const confirmLanguage = () => {
  currentLang.value = tempLang.value;
  setWebsiteLanguage(currentLang.value);
  emit('change', currentLang.value);
  closeModal();
};
</script>
