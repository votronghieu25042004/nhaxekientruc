<template>
  <div class="min-h-screen flex flex-col bg-slate-950 text-slate-100 justify-center items-center px-4 py-12">
    <div class="w-full max-w-md space-y-6">
      <!-- Logo Header -->
      <div class="text-center space-y-2">
        <a href="/" class="inline-flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-amber-500 flex items-center justify-center text-white shadow-xl shadow-blue-600/30">
            <span class="font-black text-xl">BusHub</span>
          </div>
        </a>
        <h2 class="text-2xl font-black text-white">ĐĂNG NHẬP HỆ THỐNG</h2>
        <p class="text-xs text-slate-400">Nhà xe BusHub Terminal (BusHub Terminal)</p>
      </div>

      <!-- Quick 1-Click Role Login Demo Buttons -->
      <div class="bg-slate-900 border border-slate-800 p-4 rounded-3xl space-y-2.5">
        <span class="text-[11px] font-bold text-slate-400 uppercase block text-center">⚡ Đăng nhập nhanh thử nghiệm (3 Roles)</span>
        <div class="grid grid-cols-3 gap-2">
          <button
            type="button"
            @click="quickLogin('admin@bushub.vn', '123456')"
            class="p-2.5 rounded-2xl bg-blue-600/10 hover:bg-blue-600/20 border border-blue-600/30 text-amber-300 text-xs font-bold text-center transition-all cursor-pointer"
          >
            👑 Admin<br><span class="text-[9px] opacity-70 font-normal">Quản trị</span>
          </button>
          <button
            type="button"
            @click="quickLogin('staff@bushub.vn', '123456')"
            class="p-2.5 rounded-2xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-xs font-bold text-center transition-all cursor-pointer"
          >
            🎫 Lơ Xe<br><span class="text-[9px] opacity-70 font-normal">Soát vé QR</span>
          </button>
          <button
            type="button"
            @click="quickLogin('hieu@gmail.com', '123456')"
            class="p-2.5 rounded-2xl bg-blue-600/10 hover:bg-blue-600/20 border border-blue-600/30 text-blue-300 text-xs font-bold text-center transition-all cursor-pointer"
          >
            🧑 Khách Hàng<br><span class="text-[9px] opacity-70 font-normal">Đặt vé</span>
          </button>
        </div>
      </div>

      <!-- Login Form -->
      <div class="bg-slate-900 border border-slate-800 p-6 sm:p-8 rounded-3xl shadow-2xl">
        <form @submit.prevent="submit" class="space-y-4">
          <div v-if="errors.email" class="p-3 bg-rose-500/10 border border-rose-500/30 rounded-xl text-xs text-rose-400 font-bold">
            {{ errors.email }}
          </div>

          <div>
            <label class="text-xs font-bold text-slate-300 uppercase">Email đăng nhập</label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="admin@bushub.vn hoặc email của bạn"
              class="w-full mt-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-600 text-sm"
            />
          </div>

          <div>
            <label class="text-xs font-bold text-slate-300 uppercase">Mật khẩu</label>
            <input
              v-model="form.password"
              type="password"
              required
              placeholder="Nhập mật khẩu"
              class="w-full mt-1 bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-blue-600 text-sm"
            />
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-blue-600 to-amber-500 hover:from-blue-600 hover:to-amber-600 text-white font-black text-sm tracking-wide shadow-lg shadow-blue-600/30 transition-all cursor-pointer"
          >
            ĐĂNG NHẬP
          </button>

          <div class="text-center pt-2 text-xs text-slate-400">
            Chưa có tài khoản? <a href="/register" class="text-blue-500 font-bold hover:underline">Đăng ký ngay</a>
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
  email: 'admin@bushub.vn',
  password: '123456',
  remember: true,
});

const submit = () => {
  form.post('/login');
};

const quickLogin = (email, pwd) => {
  form.email = email;
  form.password = pwd;
  submit();
};
</script>
