<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 selection:bg-[#dc2626] selection:text-white flex flex-col">
    <AdminHeader 
      title="QUẢN TRỊ TÀI KHOẢN & PHÂN QUYỀN (RBAC)" 
      subtitle="Quản lý tài khoản nhân viên, cấp vai trò phân quyền và kiểm soát phạm vi truy cập dữ liệu toàn hệ thống" 
    />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">
      <!-- Flash Alert -->
      <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs sm:text-sm font-bold flex items-center gap-2">
        <span>✅</span>
        <span>{{ $page.props.flash.success }}</span>
      </div>

      <!-- Top Stats Bar -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-1.5 transition hover:border-slate-300">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Tổng Nhân Sự</span>
            <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold">👥</span>
          </div>
          <p class="text-2xl font-black text-slate-900 font-mono">{{ users.length }} tài khoản</p>
          <span class="text-[11px] text-emerald-700 block">✓ {{ activeUsersCount }} tài khoản đang hoạt động</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-1.5 transition hover:border-slate-300">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Super Admin & Kế Toán</span>
            <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">💼</span>
          </div>
          <p class="text-2xl font-black text-emerald-700 font-mono">{{ adminAndAccountantCount }} người</p>
          <span class="text-[11px] text-slate-500 block">Quyền xem số liệu doanh thu & tài chính</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-1.5 transition hover:border-slate-300">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Điều Phối & Bán Vé</span>
            <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">🎟️</span>
          </div>
          <p class="text-2xl font-black text-blue-700 font-mono">{{ dispatcherAndCashierCount }} người</p>
          <span class="text-[11px] text-blue-700 block">Vận hành ca tuyến, ẩn dữ liệu dòng tiền</span>
        </div>

        <div class="bg-white border border-slate-200 p-5 rounded-2xl space-y-1.5 transition hover:border-slate-300">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-500 font-bold uppercase tracking-wider">Tài Xế & Lơ Xe</span>
            <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">📱</span>
          </div>
          <p class="text-2xl font-black text-amber-800 font-mono">{{ mobileOnlyCount }} thành viên</p>
          <span class="text-[11px] text-amber-800 block">Cổng Mobile riêng, không vào Web Admin</span>
        </div>
      </div>

      <!-- Navigation Tabs: Danh sách tài khoản vs Ma trận phân quyền -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200">
        <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-xl text-xs font-bold text-slate-600">
          <button
            @click="activeTab = 'users'"
            :class="activeTab === 'users' ? 'bg-[#dc2626] text-white shadow-xs' : 'hover:bg-white text-slate-700'"
            class="px-4 py-2 rounded-lg transition cursor-pointer flex items-center gap-2"
          >
            <span>👤</span>
            <span>Danh Sách Tài Khoản Nhân Viên</span>
          </button>
          <button
            @click="activeTab = 'matrix'"
            :class="activeTab === 'matrix' ? 'bg-[#dc2626] text-white shadow-xs' : 'hover:bg-white text-slate-700'"
            class="px-4 py-2 rounded-lg transition cursor-pointer flex items-center gap-2"
          >
            <span>🛡️</span>
            <span>Ma Trận Phân Quyền (RBAC)</span>
          </button>
        </div>

        <div class="flex items-center gap-2.5">
          <button 
            @click="showCreateUserModal = true"
            class="px-4 py-2 bg-[#dc2626] hover:bg-[#b91c1c] text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
          >
            <span>➕</span>
            <span>CẤP TÀI KHOẢN MỚI</span>
          </button>
        </div>
      </div>

      <!-- ==================== TAB 1: DANH SÁCH TÀI KHOẢN NHÂN VIÊN ==================== -->
      <div v-if="activeTab === 'users'" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900">Danh Sách Người Dùng & Trạng Thái Đăng Nhập</h3>
            <p class="text-xs text-slate-500 mt-0.5">Mỗi tài khoản được gắn một vai trò nghiệp vụ với phân quyền chặt chẽ</p>
          </div>

          <!-- Filters -->
          <div class="flex flex-wrap items-center gap-2">
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Tìm tên, SĐT, email..." 
              class="px-3.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"
            />
            <select 
              v-model="roleFilter" 
              class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none"
            >
              <option value="ALL">Tất cả vai trò</option>
              <option value="super_admin">Super Admin (Giám Đốc)</option>
              <option value="accountant">Kế Toán Trưởng</option>
              <option value="dispatcher">Điều Hành Viên</option>
              <option value="cashier">Nhân Viên Bán Vé</option>
              <option value="conductor">Tài Xế / Lơ Xe (Mobile)</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200">
              <tr>
                <th class="py-3 px-3">Nhân Viên</th>
                <th class="py-3 px-3">Tên Đăng Nhập / SĐT</th>
                <th class="py-3 px-3">Email Liên Hệ</th>
                <th class="py-3 px-3 text-center">Vai Trò Hệ Thống</th>
                <th class="py-3 px-3 text-center">Trạng Thái</th>
                <th class="py-3 px-3 text-right">Thao Tác Phân Quyền</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr v-for="u in paginatedUsers" :key="u.id" class="hover:bg-slate-50/70 transition">
                <td class="py-3.5 px-3">
                  <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-700 text-xs">
                      {{ (u.name || 'U').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <span class="font-bold text-slate-900 block">{{ u.name }}</span>
                      <span class="text-[10px] text-slate-400 font-mono">UID #{{ u.id }}</span>
                    </div>
                  </div>
                </td>

                <td class="py-3.5 px-3 font-mono font-bold text-slate-800">
                  {{ u.phone || '0905' + (100000 + u.id) }}
                </td>

                <td class="py-3.5 px-3 text-slate-500 font-mono">
                  {{ u.email }}
                </td>

                <td class="py-3.5 px-3 text-center">
                  <span 
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold whitespace-nowrap inline-flex items-center gap-1"
                    :class="getRoleBadgeClass(u.role)"
                  >
                    <span>{{ getRoleIcon(u.role) }}</span>
                    <span>{{ getRoleLabel(u.role) }}</span>
                  </span>
                </td>

                <td class="py-3.5 px-3 text-center">
                  <button 
                    @click="toggleUserStatus(u)"
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold transition cursor-pointer"
                    :class="u.status === 'LOCKED' ? 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100'"
                    :title="u.status === 'LOCKED' ? 'Bấm để Mở khóa tài khoản' : 'Bấm để Khóa tài khoản'"
                  >
                    {{ u.status === 'LOCKED' ? '🔒 Đã Khóa' : '🟢 Hoạt Động' }}
                  </button>
                </td>

                <td class="py-3.5 px-3 text-right">
                  <div class="flex items-center justify-end space-x-1.5">
                    <button 
                      @click="openEditModal(u)"
                      class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition cursor-pointer"
                    >
                      ✏️ Sửa Quyền
                    </button>
                    <button 
                      @click="toggleUserStatus(u)"
                      class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer"
                      :class="u.status === 'LOCKED' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                    >
                      {{ u.status === 'LOCKED' ? 'Mở Khóa' : 'Khóa' }}
                    </button>
                    <button 
                      v-if="u.status !== 'DELETED'"
                      @click="confirmDeleteUser(u)"
                      class="px-2 py-1.5 rounded-lg text-xs font-bold text-slate-500 hover:text-red-700 hover:bg-red-50 border border-slate-200 transition cursor-pointer"
                      title="Xóa tài khoản"
                    >
                      🗑️ Xóa
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredUsers.length === 0">
                <td colspan="6" class="text-center py-8 text-slate-400">
                  Không tìm thấy tài khoản nhân viên nào phù hợp với bộ lọc
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- THANH PHÂN TRANG CHO DANH SÁCH NGƯỜI DÙNG -->
        <div v-if="filteredUsers && filteredUsers.length > 0" class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <span class="text-xs text-slate-500 font-medium">
              Hiển thị <b class="text-slate-900">{{ (currentPage - 1) * pageSize + 1 }} - {{ Math.min(currentPage * pageSize, filteredUsers.length) }}</b> trên tổng số <b class="text-slate-900">{{ filteredUsers.length }}</b> tài khoản
            </span>
            <select 
              v-model.number="pageSize" 
              class="px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-700 focus:outline-none"
            >
              <option :value="8">8 người/trang</option>
              <option :value="15">15 người/trang</option>
              <option :value="30">30 người/trang</option>
              <option :value="50">50 người/trang</option>
            </select>
          </div>

          <div class="flex items-center gap-1.5">
            <button
              :disabled="currentPage === 1"
              @click="currentPage--"
              class="px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            >
              ← Trang Trước
            </button>

            <div class="flex items-center gap-1">
              <template v-for="(p, idx) in visiblePages" :key="idx">
                <span v-if="p === '...'" class="px-1.5 text-xs text-slate-400 font-bold select-none">...</span>
                <button
                  v-else
                  @click="currentPage = p"
                  class="w-7 h-7 rounded-lg text-xs font-black transition cursor-pointer"
                  :class="currentPage === p ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
                >
                  {{ p }}
                </button>
              </template>
            </div>

            <button
              :disabled="currentPage >= totalPages"
              @click="currentPage++"
              class="px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
            >
              Kế Tiếp →
            </button>
          </div>
        </div>
      </div>

      <!-- ==================== TAB 2: MA TRẬN PHÂN QUYỀN (RBAC MATRIX) ==================== -->
      <div v-if="activeTab === 'matrix'" class="space-y-6">
        <!-- Role Simulator Banner -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-lg font-black">🧪</span>
            <div>
              <h4 class="font-bold text-slate-900 text-sm">Trình Giả Lập Trải Nghiệm Giao Diện (Role Simulator)</h4>
              <p class="text-xs text-slate-500">Chọn vai trò bên dưới để kiểm tra các màn hình mà nhân sự đó được phép hoặc bị cấm truy cập</p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold">
            <button 
              v-for="(r, key) in rolesDefinition" 
              :key="key"
              @click="simulatedRole = key"
              :class="simulatedRole === key ? 'bg-[#dc2626] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
              class="px-3 py-1.5 rounded-lg transition cursor-pointer"
            >
              {{ r.name.split('(')[0].trim() }}
            </button>
          </div>
        </div>

        <!-- Simulated Role Details -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-400 uppercase">Quyền hạn của vai trò đang xem:</span>
            <span class="text-xs font-black px-2.5 py-0.5 rounded-full" :class="rolesDefinition[simulatedRole]?.badge_class">
              {{ rolesDefinition[simulatedRole]?.name }}
            </span>
          </div>
          <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200 leading-relaxed">
            ℹ️ {{ rolesDefinition[simulatedRole]?.description }}
          </p>
        </div>

        <!-- Matrix Table -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
          <div>
            <h3 class="text-base font-black text-slate-900">Bảng Ma Trận Phân Quyền Chi Tiết (Role Permission Matrix)</h3>
            <p class="text-xs text-slate-500 mt-0.5">Xác nhận phân định trách nhiệm không ai nhìn thấy dữ liệu vượt quá thẩm quyền</p>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] font-bold border-b border-slate-200">
                <tr>
                  <th class="py-3 px-3">Phân Hệ & Chức Năng Admin</th>
                  <th class="py-3 px-3 text-center">Super Admin<br/><span class="text-[9px] text-purple-700 font-semibold">(Giám Đốc)</span></th>
                  <th class="py-3 px-3 text-center">Kế Toán Trưởng<br/><span class="text-[9px] text-emerald-700 font-semibold">(Tài Chính)</span></th>
                  <th class="py-3 px-3 text-center">Điều Hành Viên<br/><span class="text-[9px] text-blue-700 font-semibold">(Dispatch)</span></th>
                  <th class="py-3 px-3 text-center">Nhân Viên Bán Vé<br/><span class="text-[9px] text-amber-700 font-semibold">(Quầy Vé)</span></th>
                  <th class="py-3 px-3 text-center">Tài Xế / Lơ Xe<br/><span class="text-[9px] text-slate-600 font-semibold">(Mobile App)</span></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700">
                <tr v-for="m in modulesMatrix" :key="m.name" class="hover:bg-slate-50/70 transition">
                  <td class="py-3 px-3">
                    <div class="font-bold text-slate-900 flex items-center space-x-2">
                      <span>{{ m.icon }}</span>
                      <span>{{ m.name }}</span>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">{{ m.desc }}</div>
                  </td>

                  <!-- Super Admin -->
                  <td class="py-3 px-3 text-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                      ✓
                    </span>
                  </td>

                  <!-- Kế Toán -->
                  <td class="py-3 px-3 text-center">
                    <span v-if="m.accountant" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                      ✓
                    </span>
                    <span v-else class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400 font-bold">
                      ✕
                    </span>
                  </td>

                  <!-- Điều Hành Viên -->
                  <td class="py-3 px-3 text-center">
                    <span v-if="m.dispatcher" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                      ✓
                    </span>
                    <span v-else-if="m.hideForDispatcher" class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 whitespace-nowrap">
                      🚫 Bị Ẩn
                    </span>
                    <span v-else class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400 font-bold">
                      ✕
                    </span>
                  </td>

                  <!-- Nhân Viên Bán Vé -->
                  <td class="py-3 px-3 text-center">
                    <span v-if="m.cashier" class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                      ✓
                    </span>
                    <span v-else class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-400 font-bold">
                      ✕
                    </span>
                  </td>

                  <!-- Conductor Mobile Only -->
                  <td class="py-3 px-3 text-center">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500 whitespace-nowrap">
                      ⛔ Cấm Web
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal Cấp Tài Khoản Mới -->
    <div v-if="showCreateUserModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white border border-slate-200 rounded-2xl p-6 max-w-md w-full shadow-lg space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="text-base font-black text-slate-900">➕ Cấp Tài Khoản Nhân Viên Mới</h3>
          <button @click="showCreateUserModal = false" class="text-slate-400 hover:text-slate-600 text-xl cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitCreateUser" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Họ và Tên Nhân Viên:</label>
            <input 
              v-model="newUserForm.name" 
              type="text" 
              required 
              placeholder="Ví dụ: Hoàng Minh Trí" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 font-medium"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Số Điện Thoại (Tên đăng nhập):</label>
            <input 
              v-model="newUserForm.phone" 
              type="text" 
              required 
              placeholder="0905123456" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 font-mono font-bold"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Email Công Ty:</label>
            <input 
              v-model="newUserForm.email" 
              type="email" 
              required 
              placeholder="nhanvien@nhaxekientruc.vn" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 font-mono"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Cấp Vai Trò Phân Quyền:</label>
            <select 
              v-model="newUserForm.role" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 font-bold focus:bg-white focus:outline-none"
            >
              <option value="accountant">💼 Kế Toán Trưởng (Xem tài chính, chốt ca lơ xe)</option>
              <option value="dispatcher">🚍 Điều Hành Viên (Lịch xe, GPS - Ẩn doanh thu)</option>
              <option value="cashier">🎟️ Nhân Viên Bán Vé Quầy (POS Bán vé & CRM)</option>
              <option value="super_admin">👑 Super Admin (Giám Đốc - Toàn quyền)</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Mật Khẩu Khởi Tạo:</label>
            <input 
              v-model="newUserForm.password" 
              type="password" 
              required 
              placeholder="Mật khẩu ít nhất 6 ký tự" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 font-mono"
            />
          </div>

          <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showCreateUserModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">Hủy</button>
            <button type="submit" class="px-5 py-2 bg-[#dc2626] hover:bg-[#b91c1c] text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer">
              Xác Nhận Cấp Tài Khoản
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Chỉnh Sửa Quyền & Đổi Mật Khẩu -->
    <div v-if="showEditModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white border border-slate-200 rounded-2xl p-6 max-w-md w-full shadow-lg space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="text-base font-black text-slate-900">✏️ Điều Chỉnh Vai Trò: {{ activeEditUser?.name }}</h3>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-xl cursor-pointer">&times;</button>
        </div>

        <form @submit.prevent="submitEditUser" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Họ Tên:</label>
            <input 
              v-model="editUserForm.name" 
              type="text" 
              required 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 font-medium"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Số Điện Thoại:</label>
            <input 
              v-model="editUserForm.phone" 
              type="text" 
              required 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 font-mono font-bold"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Vai Trò Phân Quyền:</label>
            <select 
              v-model="editUserForm.role" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 font-bold focus:bg-white focus:outline-none"
            >
              <option value="super_admin">👑 Super Admin (Giám Đốc - Toàn quyền)</option>
              <option value="accountant">💼 Kế Toán Trưởng (Tài chính, chốt ca)</option>
              <option value="dispatcher">🚍 Điều Hành Viên (Lịch xe, GPS - Ẩn doanh thu)</option>
              <option value="cashier">🎟️ Nhân Viên Bán Vé Quầy (POS Bán vé & CRM)</option>
              <option value="conductor">📱 Tài Xế / Lơ Xe (Mobile Only)</option>
            </select>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Mật Khẩu Mới (Để trống nếu giữ nguyên):</label>
            <input 
              v-model="editUserForm.password" 
              type="password" 
              placeholder="Nhập nếu muốn đổi mật khẩu" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 font-mono"
            />
          </div>

          <div class="flex justify-end space-x-2 pt-3 border-t border-slate-100">
            <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold cursor-pointer">Hủy</button>
            <button type="submit" class="px-5 py-2 bg-[#dc2626] hover:bg-[#b91c1c] text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer">
              Lưu Quyền Hạn
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL XÁC NHẬN XÓA TÀI KHOẢN -->
    <div v-if="showDeleteUserModal && userToDelete" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-2xl animate-in zoom-in-95">
        <div class="w-14 h-14 rounded-2xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center text-2xl mx-auto shadow-2xs">
          🗑️
        </div>

        <div class="text-center space-y-2">
          <h3 class="text-base font-black text-slate-900">
            Xác Nhận Xóa Vĩnh Viễn Tài Khoản?
          </h3>
          <p class="text-xs text-slate-600 leading-relaxed">
            Bạn có chắc chắn muốn xóa tài khoản của <strong class="text-slate-900 font-black">{{ userToDelete.name }}</strong> (SĐT: <span class="font-mono font-bold text-red-600">{{ userToDelete.phone }}</span>)?
          </p>
          <div class="p-3.5 bg-red-50 border border-red-200 rounded-2xl text-[11.5px] text-red-950 text-left space-y-1.5 leading-relaxed">
            <p class="font-black text-red-900">⚠️ Cảnh báo:</p>
            <p>• Tài khoản sẽ bị <strong>XÓA VĨNH VIỄN</strong>.</p>
            <p>• Dữ liệu tài khoản sẽ biến mất hoàn toàn. Người dùng này có thể dùng lại email/SĐT để đăng ký mới nếu muốn.</p>
            <p class="text-slate-600 border-t border-red-200 pt-1">* Nếu chỉ muốn chặn đăng nhập, hãy dùng chức năng <strong>"Khóa"</strong> tài khoản.</p>
          </div>
        </div>

        <div class="flex items-center gap-2 pt-2">
          <button
            @click="showDeleteUserModal = false; userToDelete = null"
            class="flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-700 font-bold text-xs hover:bg-slate-100 transition cursor-pointer"
          >
            Hủy Bỏ
          </button>
          <button
            @click="executeDeleteUser"
            class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md transition cursor-pointer active:scale-95"
          >
            Xác Nhận Xóa
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminHeader from '@/Components/AdminHeader.vue'

const props = defineProps({
  users: { type: Array, default: () => [] },
  rolesDefinition: { type: Object, default: () => ({}) },
})

const activeTab = ref('users') // 'users' or 'matrix'
const searchQuery = ref('')
const roleFilter = ref('ALL')
const simulatedRole = ref('dispatcher')

const currentPage = ref(1)
const pageSize = ref(8)

const showCreateUserModal = ref(false)
const showEditModal = ref(false)
const activeEditUser = ref(null)

const newUserForm = ref({
  name: '',
  phone: '',
  email: '',
  role: 'cashier',
  password: 'password123',
})

const editUserForm = ref({
  name: '',
  phone: '',
  role: '',
  password: '',
})

// Counts
const activeUsersCount = computed(() => props.users.filter(u => u.status !== 'LOCKED').length)
const adminAndAccountantCount = computed(() => props.users.filter(u => u.role === 'super_admin' || u.role === 'accountant').length)
const dispatcherAndCashierCount = computed(() => props.users.filter(u => u.role === 'dispatcher' || u.role === 'cashier').length)
const mobileOnlyCount = computed(() => props.users.filter(u => u.role === 'conductor' || u.role === 'driver').length)

// Filtered Users
const filteredUsers = computed(() => {
  return props.users.filter(u => {
    if (roleFilter.value !== 'ALL' && u.role !== roleFilter.value) return false
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase()
      const matchName = (u.name || '').toLowerCase().includes(q)
      const matchPhone = (u.phone || '').toLowerCase().includes(q)
      const matchEmail = (u.email || '').toLowerCase().includes(q)
      if (!matchName && !matchPhone && !matchEmail) return false
    }
    return true
  })
})

// Watch search & role filter to reset current page
watch([searchQuery, roleFilter, pageSize], () => {
  currentPage.value = 1
})

const totalPages = computed(() => {
  if (!filteredUsers.value || filteredUsers.value.length === 0) return 1
  return Math.ceil(filteredUsers.value.length / pageSize.value)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const cur = currentPage.value
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1)
  }
  const pages = [1]
  let start = Math.max(2, cur - 1)
  let end = Math.min(total - 1, cur + 1)
  if (start > 2) pages.push('...')
  for (let i = start; i <= end; i++) pages.push(i)
  if (end < total - 1) pages.push('...')
  pages.push(total)
  return pages
})

const paginatedUsers = computed(() => {
  if (!filteredUsers.value) return []
  const start = (currentPage.value - 1) * pageSize.value
  return filteredUsers.value.slice(start, start + pageSize.value)
})

const getRoleBadgeClass = (role) => {
  switch (role) {
    case 'super_admin': return 'bg-purple-50 text-purple-700 border border-purple-200'
    case 'accountant': return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'dispatcher': return 'bg-blue-50 text-blue-700 border border-blue-200'
    case 'cashier': return 'bg-amber-50 text-amber-800 border border-amber-200'
    case 'conductor':
    case 'driver': return 'bg-slate-100 text-slate-700 border border-slate-200'
    default: return 'bg-slate-50 text-slate-700 border border-slate-200'
  }
}

const getRoleIcon = (role) => {
  switch (role) {
    case 'super_admin': return '👑'
    case 'accountant': return '💼'
    case 'dispatcher': return '🚍'
    case 'cashier': return '🎟️'
    case 'conductor': return '🎫'
    case 'driver': return '👨‍✈️'
    default: return '👤'
  }
}

const getRoleLabel = (role) => {
  switch (role) {
    case 'super_admin': return 'Super Admin (Giám Đốc)'
    case 'accountant': return 'Kế Toán Trưởng'
    case 'dispatcher': return 'Điều Hành Viên'
    case 'cashier': return 'Nhân Viên Bán Vé'
    case 'conductor': return 'Lơ Xe (Mobile Only)'
    case 'driver': return 'Tài Xế (Mobile Only)'
    default: return role || 'Nhân Viên'
  }
}

const userToDelete = ref(null)
const showDeleteUserModal = ref(false)

const toggleUserStatus = (u) => {
  const actionText = u.status === 'LOCKED' ? 'Mở khóa' : 'Khóa'
  if (!confirm(`Xác nhận ${actionText} quyền truy cập của tài khoản ${u.name} (${u.phone || u.email})?\n\n${u.status !== 'LOCKED' ? 'Khi bị khóa, tài khoản này sẽ không thể đăng nhập và sẽ nhận thông báo liên hệ Hotline: 1900 6067.' : 'Tài khoản sẽ được mở khóa và đăng nhập lại bình thường.'}`)) return
  router.post(`/admin/roles/users/${u.id}/toggle-status`, {}, {
    preserveScroll: true,
  })
}

const confirmDeleteUser = (u) => {
  userToDelete.value = u
  showDeleteUserModal.value = true
}

const executeDeleteUser = () => {
  if (!userToDelete.value) return
  router.delete(`/admin/users/${userToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteUserModal.value = false
      userToDelete.value = null
    },
    onError: (err) => {
      showDeleteUserModal.value = false
      userToDelete.value = null
      alert(err?.status || err?.message || 'Có lỗi xảy ra khi xóa tài khoản. Vui lòng thử lại!')
    }
  })
}

const openEditModal = (u) => {
  activeEditUser.value = u
  editUserForm.value = {
    name: u.name,
    phone: u.phone || '',
    role: u.role,
    password: '',
  }
  showEditModal.value = true
}

const submitEditUser = () => {
  if (!activeEditUser.value) return
  router.post(`/admin/roles/users/${activeEditUser.value.id}/update`, editUserForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      showEditModal.value = false
    }
  })
}

const submitCreateUser = () => {
  router.post('/admin/users/staff', newUserForm.value, {
    preserveScroll: true,
    onSuccess: () => {
      showCreateUserModal.value = false
      newUserForm.value = {
        name: '',
        phone: '',
        email: '',
        role: 'cashier',
        password: 'password123',
      }
    }
  })
}

// Matrix Definitions
const modulesMatrix = [
  { icon: '🖥️', name: 'Màn hình POS Bán Vé Quầy', desc: 'Chọn chuyến, bấm sơ đồ ghế tầng 1 & tầng 2, in bill nhiệt K80', accountant: false, dispatcher: false, cashier: true },
  { icon: '👥', name: 'Hành Khách & CRM Đi Xe', desc: 'Tra cứu thông tin khách hàng, số lần đi, phân hạng VIP & lịch sử số ghế', accountant: true, dispatcher: false, cashier: true },
  { icon: '🗺️', name: 'Live GPS Tracking & Báo Động SOS', desc: 'Bản đồ theo dõi tốc độ, vị trí thực tế của xe và xử lý còi báo động khẩn', accountant: false, dispatcher: true, cashier: false },
  { icon: '🚍', name: 'Quản Lý Đội Xe & Bảo Dưỡng', desc: 'Theo dõi 10 xe, trạng thái kỹ thuật và sơ đồ cấu hình ghế', accountant: false, dispatcher: true, cashier: false },
  { icon: '🛠️', name: 'Vật Tư & Chi Phí Xe', desc: 'Lịch sử thay vỏ/nhớt, kiểm soát tiêu hao dầu L/100km và phí cố định BOT/Bến bãi', accountant: true, dispatcher: false, cashier: false },
  { icon: '📅', name: 'Lịch Trình & Chuyến Xe', desc: 'Xếp lịch tài xế, tạo chuyến định kỳ và dời giờ xuất bến khi có sự cố', accountant: false, dispatcher: true, cashier: false },
  { icon: '🛣️', name: 'Tuyến Đường & Trạm Dừng ETA', desc: 'Thêm bến trung chuyển, khoảng cách km và tự động tính ETA cho xe', accountant: false, dispatcher: true, cashier: false },
  { icon: '👨‍✈️', name: 'Nhân Sự (Bác Tài & Lơ Xe)', desc: 'Cảnh báo bằng lái xe < 30 ngày, quản lý tiền quỹ thu trên chuyến của lơ xe', accountant: false, dispatcher: true, cashier: false },
  { icon: '🏷️', name: 'Cấu Hình Giá & Khuyến Mãi', desc: 'Tự động tăng giá 30% dịp Lễ/Tết/Cuối tuần, Voucher mã giảm giá và chính sách hoàn hủy', accountant: false, dispatcher: false, cashier: false },
  { icon: '💰', name: 'Báo Cáo Doanh Thu & Đối Soát', desc: 'Doanh thu toàn hệ thống, lợi nhuận ròng, tiền cọc 30% và thu tiền chốt ca lơ xe', accountant: true, dispatcher: false, hideForDispatcher: true, cashier: false },
  { icon: '🛡️', name: 'Quản Trị Tài Khoản & RBAC', desc: 'Cấp quyền đăng nhập, đổi mật khẩu và khóa tài khoản nhân sự', accountant: false, dispatcher: false, cashier: false },
]
</script>
