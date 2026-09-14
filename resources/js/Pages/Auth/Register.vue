<template>
  <div class="min-h-screen flex flex-col bg-slate-950 text-slate-100 justify-center items-center px-4 py-12">
    <div class="w-full max-w-md space-y-6">
      <div class="text-center space-y-2">
        <a href="/" class="inline-flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-amber-500 flex items-center justify-center text-white shadow-xl shadow-blue-600/30">
            <span class="font-black text-xl">BusHub</span>
          </div>
        </a>
        <h2 class="text-2xl font-black text-white">ĐĂNG KÝ TÀI KHOẢN</h2>
        <p class="text-xs text-slate-400">Hành khách Nhà xe BusHub Terminal</p>
      </div>

      <div class="bg-slate-900 border border-slate-800 p-6 sm:p-8 rounded-3xl shadow-2xl">
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-300 uppercase">Họ và tên</label>
            <input v-model="form.name" type="text" required placeholder="Ví dụ: Võ Trọng Hiếu" class="w-full mt-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm" />
            <span v-if="errors.name" class="text-rose-400 text-xs mt-1 block">{{ errors.name }}</span>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-300 uppercase">Email</label>
            <input v-model="form.email" type="email" required placeholder="hieu@gmail.com" class="w-full mt-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm" />
            <span v-if="errors.email" class="text-rose-400 text-xs mt-1 block">{{ errors.email }}</span>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-300 uppercase">Số điện thoại</label>
            <input v-model="form.phone" type="tel" required placeholder="0905 123 456" class="w-full mt-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm" />
            <span v-if="errors.phone" class="text-rose-400 text-xs mt-1 block">{{ errors.phone }}</span>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-300 uppercase">Mật khẩu</label>
            <input v-model="form.password" type="password" required placeholder="Tối thiểu 6 ký tự" class="w-full mt-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm" />
            <span v-if="errors.password" class="text-rose-400 text-xs mt-1 block">{{ errors.password }}</span>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-300 uppercase">Xác nhận mật khẩu</label>
            <input v-model="form.password_confirmation" type="password" required placeholder="Nhập lại mật khẩu" class="w-full mt-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white text-sm" />
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-amber-500 hover:from-blue-600 hover:to-amber-600 text-white font-black text-sm tracking-wide shadow-lg shadow-blue-600/30 transition-all cursor-pointer"
          >
            ĐĂNG KÝ TÀI KHOẢN
          </button>

          <div class="text-center pt-2 text-xs text-slate-400">
            Đã có tài khoản? <a href="/login" class="text-blue-500 font-bold hover:underline">Đăng nhập</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({
  errors: Object,
});

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/register');
};
</script>
