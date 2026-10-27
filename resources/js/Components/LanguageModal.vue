<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-150">
      <!-- Backdrop click to close -->
      <div class="fixed inset-0" @click="closeModal"></div>

      <!-- Modal Card Box -->
      <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xs sm:max-w-sm overflow-hidden z-10 animate-in zoom-in-95 duration-150 border border-slate-100 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">Select Language</h3>
          <button
            type="button"
            @click="closeModal"
            class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
          >
            ✕
          </button>
        </div>

        <!-- Options -->
        <div class="py-2 divide-y divide-slate-100">
          <!-- Option English -->
          <label @click="tempLang = 'en'" class="flex items-center justify-between py-4 cursor-pointer group select-none">
            <span class="text-sm font-semibold text-slate-800 group-hover:text-slate-950">Tiếng Anh</span>
            <div class="relative flex items-center justify-center">
              <input type="radio" name="language" value="en" v-model="tempLang" class="sr-only" />
              <div
                class="w-5 h-5 rounded-full border-2 transition-all flex items-center justify-center"
                :class="tempLang === 'en' ? 'border-[#dc2626] bg-white' : 'border-slate-300 group-hover:border-slate-400'"
              >
                <div v-if="tempLang === 'en'" class="w-2.5 h-2.5 rounded-full bg-[#dc2626]"></div>
              </div>
            </div>
          </label>

          <!-- Option Vietnamese -->
          <label @click="tempLang = 'vi'" class="flex items-center justify-between py-4 cursor-pointer group select-none">
            <span class="text-sm font-semibold text-slate-800 group-hover:text-slate-950">Tiếng Việt</span>
            <div class="relative flex items-center justify-center">
              <input type="radio" name="language" value="vi" v-model="tempLang" class="sr-only" />
              <div
                class="w-5 h-5 rounded-full border-2 transition-all flex items-center justify-center"
                :class="tempLang === 'vi' ? 'border-[#dc2626] bg-white' : 'border-slate-300 group-hover:border-slate-400'"
              >
                <div v-if="tempLang === 'vi'" class="w-2.5 h-2.5 rounded-full bg-[#dc2626]"></div>
              </div>
            </div>
          </label>
        </div>

        <!-- Confirm Button -->
        <div class="pt-4">
          <button
            type="button"
            @click="confirmLanguage"
            class="w-full py-3.5 px-4 bg-[#dc2626] hover:bg-[#b91c1c] active:scale-98 text-white font-bold text-sm rounded-2xl shadow-md shadow-red-500/25 transition-all text-center cursor-pointer"
          >
            Confirm
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';

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
    tempLang.value = currentLang.value;
  }
});

onMounted(() => {
  const saved = localStorage.getItem('bushub_locale');
  if (saved) {
    currentLang.value = saved;
    tempLang.value = saved;
  }
});

const closeModal = () => {
  isOpen.value = false;
  emit('update:modelValue', false);
};

const confirmLanguage = () => {
  currentLang.value = tempLang.value;
  localStorage.setItem('bushub_locale', currentLang.value);
  emit('change', currentLang.value);
  closeModal();
};
</script>
