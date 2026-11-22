<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC] text-slate-900">
    <AdminHeader title="QUẢN LÝ LƠ XE & NHÂN SỰ" subtitle="Danh sách 10 Lơ xe đón khách & Thu tiền tận tay" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
          <h1 class="text-xl sm:text-2xl font-black text-slate-900">Danh sách Tài khoản (Lơ xe & Khách hàng)</h1>
          <p class="text-slate-500 text-xs mt-1">Cấp tài khoản nhân viên soát vé hoặc quản lý khách hàng đã đăng ký</p>
        </div>
        <button
          @click="showStaffModal = true"
          class="px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-2 cursor-pointer shadow-sm active:scale-95 shrink-0"
        >
          + Cấp Tài Khoản Lơ Xe
        </button>
      </div>

      <div class="bg-white border border-slate-200 shadow-xs rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 overflow-hidden">
        <div class="overflow-x-auto touch-pan-x custom-scrollbar">
          <table class="w-full text-left text-xs min-w-[650px]">
            <thead class="text-slate-500 uppercase border-b border-slate-200 font-black text-[10px] tracking-wider">
              <tr>
                <th class="pb-3 px-2">Họ và tên</th>
                <th class="pb-3 px-2">Email</th>
                <th class="pb-3 px-2">Số điện thoại</th>
                <th class="pb-3 px-2">Vai trò</th>
                <th class="pb-3 px-2">Số vé đã đặt</th>
                <th class="pb-3 px-2 text-right">Ngày tham gia</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50">
                <td class="py-3 px-2 font-bold text-slate-900 text-xs sm:text-sm">{{ u.name }}</td>
                <td class="py-3 px-2 font-mono text-slate-700">{{ u.email }}</td>
                <td class="py-3 px-2 text-slate-700">{{ u.phone || 'Chưa cập nhật' }}</td>
                <td class="py-3 px-2">
                  <span
                    :class="{
                      'bg-red-50 text-red-700 border-red-200': u.role === 'admin',
                      'bg-emerald-50 text-emerald-700 border-emerald-200': u.role === 'staff',
                      'bg-blue-50 text-blue-700 border-blue-200': u.role === 'customer',
                    }"
                    class="px-2.5 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold border whitespace-nowrap"
                  >
                    {{ u.role === 'admin' ? '👑 Quản trị' : (u.role === 'staff' ? '🎫 Lơ xe / Soát vé' : '🧑 Khách hàng') }}
                  </span>
                </td>
                <td class="py-3 px-2 font-bold text-slate-900">{{ u.total_bookings }} vé</td>
                <td class="py-3 px-2 text-right text-slate-500">{{ u.created_at }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Add Staff Modal -->
      <div v-if="showStaffModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 shadow-xs max-w-md w-full rounded-3xl p-6 sm:p-8 space-y-4">
          <div class="flex justify-between items-center pb-3 border-b border-slate-200">
            <h3 class="text-lg font-black text-slate-900">Cấp Tài Khoản Lơ Xe / Nhân Viên</h3>
            <button @click="showStaffModal = false" class="text-slate-500 hover:text-slate-900">✕</button>
          </div>

          <form @submit.prevent="submitStaff" class="space-y-4">
            <div>
              <label class="text-xs font-bold text-slate-700 uppercase">Họ và tên nhân viên</label>
              <input v-model="staffForm.name" type="text" required placeholder="Ví dụ: Nguyễn Văn Lơ Xe" class="w-full mt-1 bg-[#F8FAFC] border border-slate-300 rounded-xl p-3 text-slate-900 text-sm" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700 uppercase">Email</label>
              <input v-model="staffForm.email" type="email" required placeholder="staff2@nhaxekientruc.vn" class="w-full mt-1 bg-[#F8FAFC] border border-slate-300 rounded-xl p-3 text-slate-900 text-sm" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700 uppercase">Số điện thoại</label>
              <input v-model="staffForm.phone" type="tel" required placeholder="0905 888 999" class="w-full mt-1 bg-[#F8FAFC] border border-slate-300 rounded-xl p-3 text-slate-900 text-sm" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700 uppercase">Vai trò / Chức danh</label>
              <select v-model="staffForm.role" class="w-full mt-1 bg-[#F8FAFC] border border-slate-300 rounded-xl p-3 text-slate-900 text-sm font-bold">
                <option value="staff">🎫 Lơ Xe / Phụ Xe (Soát vé QR, Thu tiền mặt, Bán vé)</option>
                <option value="driver">🚌 Tài Xế (Lái xe, Nhận chuyến, Báo sự cố)</option>
                <option value="admin">👑 Quản Trị Viên (Toàn quyền điều hành)</option>
              </select>
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700 uppercase">Mật khẩu cấp</label>
              <input v-model="staffForm.password" type="password" required placeholder="Mật khẩu ban đầu (mặc định: 123456)" class="w-full mt-1 bg-[#F8FAFC] border border-slate-300 rounded-xl p-3 text-slate-900 text-sm" />
            </div>

            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 leading-relaxed">
              <span class="font-bold">📱 Hướng dẫn Lơ xe:</span> Admin tạo xong chỉ cần nhắn cho Lơ xe: <br>
              <em>"Tài khoản của em là SĐT <strong>{{ staffForm.phone || '0905xxx' }}</strong>, mật khẩu là <strong>{{ staffForm.password || '123456' }}</strong> nhé"</em>.<br>
              Lơ xe mở link <code>/login</code> trên điện thoại, đăng nhập bằng SĐT sẽ tự động chuyển vào giao diện Lơ xe.
            </div>

            <button type="submit" class="w-full py-3.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-sm shadow-lg shadow-emerald-500/20 cursor-pointer">
              XÁC NHẬN CẤP TÀI KHOẢN
            </button>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

defineProps({
  users: Array,
});

const showStaffModal = ref(false);

const staffForm = reactive({
  name: '',
  email: '',
  phone: '',
  role: 'staff',
  password: '123456',
});

const submitStaff = () => {
  router.post('/admin/users/staff', staffForm, {
    onSuccess: () => {
      showStaffModal.value = false;
      staffForm.name = '';
      staffForm.email = '';
      staffForm.phone = '';
    }
  });
};
</script>
