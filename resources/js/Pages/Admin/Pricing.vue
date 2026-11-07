<template>
  <div class="min-h-screen bg-slate-50 flex flex-col text-slate-800">
    <AdminHeader title="CẤU HÌNH GIÁ VÉ & CHÍNH SÁCH KHUYẾN MÃI" subtitle="Cài đặt giá linh hoạt ngày Lễ/Cuối tuần, mã Voucher giảm giá, tích lũy điểm và chính sách hoàn hủy vé" />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-6">
      <!-- Header Bar & 3 Sub-tabs Switcher -->
      <div class="bg-white p-4.5 rounded-2xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase">
              PRICING & REVENUE ENGINE
            </span>
            <h1 class="text-lg font-black text-slate-900">Quản Lý Giá Vé & Khuyến Mãi Linh Hoạt</h1>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">Tối ưu doanh thu theo nhu cầu thị trường, kích cầu bằng voucher và minh bạch chính sách hoàn vé</p>
        </div>

        <!-- 3 Nút Switch Chuyển Tab -->
        <div class="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl text-xs font-bold text-slate-600 border border-slate-200">
          <button
            @click="activeTab = 'pricing'"
            class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2"
            :class="activeTab === 'pricing' ? 'bg-[#dc2626] text-white shadow-xs font-black' : 'hover:bg-white text-slate-700 font-bold'"
          >
            <span>📈</span>
            <span>Giá Vé Linh Hoạt</span>
          </button>

          <button
            @click="activeTab = 'vouchers'"
            class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2"
            :class="activeTab === 'vouchers' ? 'bg-[#dc2626] text-white shadow-xs font-black' : 'hover:bg-white text-slate-700 font-bold'"
          >
            <span>🎟️</span>
            <span>Voucher (Sinh Viên & Cá Nhân)</span>
          </button>

          <button
            @click="activeTab = 'cancellation'"
            class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2"
            :class="activeTab === 'cancellation' ? 'bg-[#dc2626] text-white shadow-xs font-black' : 'hover:bg-white text-slate-700 font-bold'"
          >
            <span>📋</span>
            <span>Chính Sách Hủy Vé</span>
          </button>
        </div>
      </div>

      <!-- Flash Message -->
      <div v-if="$page.props.flash?.success" class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-bold flex items-center gap-2">
        <span>✅</span>
        <span>{{ $page.props.flash.success }}</span>
      </div>

      <!-- ==================== TAB 1: CÀI ĐẶT GIÁ VÉ LINH HOẠT ==================== -->
      <div v-if="activeTab === 'pricing'" class="space-y-4">
        <!-- BANNER ĐIỀU CHỈNH GIÁ TOÀN BỘ / NHIỀU CHUYẾN XE CÙNG LÚC -->
        <div class="p-5 rounded-2xl bg-gradient-to-r from-red-600 via-rose-600 to-amber-600 text-white shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2 font-black text-sm uppercase tracking-wide">
              <span>🚀</span>
              <span>Điều Chỉnh Giá Vé Hàng Loạt (Toàn Bộ Hoặc Chọn 3-4 Chuyến)</span>
            </div>
            <p class="text-xs text-red-50 max-w-2xl leading-relaxed">
              Bạn có thể tự động tăng giá hoặc giảm giá hàng loạt (theo số tiền +VNĐ hoặc tỷ lệ phần trăm +%) cho <strong>toàn bộ chuyến xe</strong>, các chuyến trong ngày hoặc <strong>chọn 3 - 4 chuyến bất kỳ</strong> cùng một lúc tại trang Quản Lý Chuyến Xe.
            </p>
          </div>
          <a
            href="/admin/trips?open_bulk=1"
            class="px-4 py-2.5 bg-white hover:bg-red-50 text-red-700 font-black text-xs rounded-xl shadow-xs transition flex items-center justify-center gap-2 whitespace-nowrap cursor-pointer flex-shrink-0"
          >
            <span>⚡ Điều Chỉnh Giá Vé Ngay</span>
            <span>→</span>
          </a>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
              <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>📈 Quy Tắc Tự Động Tăng Giá (Lễ, Tết & Cuối Tuần)</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Hệ thống tự động phát hiện ngày khởi hành trùng với các quy tắc bên dưới để phụ thu giá vé tương ứng (Ví dụ: +30% vào dịp Lễ, Tết hoặc cuối tuần)
              </p>
            </div>

            <button
              @click="showAddRuleModal = true"
              class="px-3.5 py-2 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
            >
              <span>➕</span>
              <span>Thêm Quy Tắc Giá</span>
            </button>
          </div>

          <!-- Bảng các quy tắc -->
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200">
                <tr>
                  <th class="px-4 py-3">Tên Quy Tắc</th>
                  <th class="px-4 py-3">Phân Loại</th>
                  <th class="px-4 py-3 text-center">Tỷ Lệ Tăng Giá</th>
                  <th class="px-4 py-3">Thời Gian / Ngày Áp Dụng</th>
                  <th class="px-4 py-3 text-center">Trạng Thái</th>
                  <th class="px-4 py-3 text-center">Hành Động</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="rule in pricingRules" :key="rule.id" class="hover:bg-slate-50/60 transition">
                  <td class="px-4 py-3.5">
                    <div class="font-bold text-slate-900">{{ rule.title }}</div>
                    <div class="text-[11px] text-slate-500 mt-0.5">{{ rule.description }}</div>
                  </td>
                  <td class="px-4 py-3.5">
                    <span 
                      class="px-2.5 py-1 rounded-md text-[10px] font-bold border"
                      :class="rule.rule_type === 'WEEKEND' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-red-50 text-red-700 border-red-200'"
                    >
                      {{ rule.rule_type === 'WEEKEND' ? '🗓️ Cuối Tuần' : '🎆 Lễ / Tết' }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5 text-center font-mono font-black text-sm text-red-600">
                    +{{ rule.multiplier_percent }}%
                  </td>
                  <td class="px-4 py-3.5 text-xs text-slate-600 font-medium">
                    <div v-if="rule.rule_type === 'WEEKEND'">
                      Áp dụng cố định: <strong>Thứ 6, Thứ 7, Chủ Nhật</strong>
                    </div>
                    <div v-else>
                      Từ <strong>{{ rule.start_date || '2026-02-10' }}</strong> đến <strong>{{ rule.end_date || '2026-02-24' }}</strong>
                    </div>
                  </td>
                  <td class="px-4 py-3.5 text-center">
                    <button
                      @click="toggleRule(rule.id)"
                      class="px-2.5 py-1 rounded-full text-[11px] font-bold transition cursor-pointer"
                      :class="rule.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200'"
                    >
                      {{ rule.is_active ? '✓ Đang áp dụng' : '✕ Tạm dừng' }}
                    </button>
                  </td>
                  <td class="px-4 py-3.5 text-center">
                    <button
                      @click="toggleRule(rule.id)"
                      class="text-xs font-bold text-slate-600 hover:text-red-600 cursor-pointer"
                    >
                      {{ rule.is_active ? 'Tắt' : 'Bật' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ==================== TAB 2: VOUCHER & ĐIỂM TÍCH LŨY CRM ==================== -->
      <div v-if="activeTab === 'vouchers'" class="space-y-5">
        <!-- VOUCHER PROMOTIONS -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
              <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>🎟️ Danh Sách Mã Voucher Khuyến Mãi Đang Phát Hành</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">Phân quyền rõ ràng đối tượng sử dụng: Cho Sinh Viên (.edu.vn), Cho Cá Nhân hoặc Áp dụng Tất Cả</p>
            </div>

            <button
              @click="showAddVoucherModal = true"
              class="px-3.5 py-2 bg-[#dc2626] hover:bg-red-700 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap shadow-xs"
            >
              <span>➕</span>
              <span>Tạo Voucher Mới</span>
            </button>
          </div>

          <!-- BỘ LỌC ĐỐI TƯỢNG ÁP DỤNG -->
          <div class="flex items-center gap-2 flex-wrap pb-1">
            <button
              type="button"
              @click="voucherAudienceFilter = 'ALL_FILTER'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
              :class="voucherAudienceFilter === 'ALL_FILTER' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            >
              <span>🌐</span>
              <span>Tất Cả Voucher ({{ (props.vouchers || []).length }})</span>
            </button>
            <button
              type="button"
              @click="voucherAudienceFilter = 'STUDENT'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
              :class="voucherAudienceFilter === 'STUDENT' ? 'bg-amber-500 text-white shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100'"
            >
              <span>🎓</span>
              <span>Cho Sinh Viên Dùng ({{ studentVouchersCount }})</span>
            </button>
            <button
              type="button"
              @click="voucherAudienceFilter = 'INDIVIDUAL'"
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
              :class="voucherAudienceFilter === 'INDIVIDUAL' ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-50 text-blue-800 border border-blue-200 hover:bg-blue-100'"
            >
              <span>👤</span>
              <span>Cho Cá Nhân Dùng ({{ individualVouchersCount }})</span>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
              v-for="v in filteredVouchers"
              :key="v.id"
              class="p-4 rounded-2xl border transition-all flex flex-col justify-between space-y-3 relative group"
              :class="v.is_active ? 'bg-white border-slate-200 shadow-2xs hover:border-slate-300' : 'bg-slate-50 border-slate-200 opacity-60'"
            >
              <div>
                <div class="flex items-center justify-between">
                  <span class="font-mono font-black text-base text-red-600 bg-red-50 px-2 py-0.5 rounded-lg border border-red-100">
                    {{ v.code }}
                  </span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="v.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500'"
                  >
                    {{ v.is_active ? 'Hoạt động' : 'Đã khóa' }}
                  </span>
                </div>

                <!-- ĐỐI TƯỢNG ÁP DỤNG BADGE -->
                <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                  <span
                    v-if="v.target_audience === 'STUDENT'"
                    class="px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1"
                  >
                    <span>🎓</span>
                    <span>Cho Sinh Viên (.edu.vn)</span>
                  </span>
                  <span
                    v-else-if="v.target_audience === 'INDIVIDUAL'"
                    class="px-2 py-0.5 rounded-md text-[10px] font-black bg-blue-100 text-blue-900 border border-blue-300 inline-flex items-center gap-1"
                  >
                    <span>👤</span>
                    <span>Cho Cá Nhân</span>
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1"
                  >
                    <span>🌐</span>
                    <span>Tất Cả Khách Hàng</span>
                  </span>
                </div>

                <div class="text-xs font-bold text-slate-900 mt-2">
                  {{ v.discount_type === 'PERCENT' ? `Giảm ${v.discount_value}% tối đa ${formatCurrency(v.max_discount)}` : `Giảm ngay ${formatCurrency(v.discount_value)}` }}
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-2" :title="v.description">{{ v.description }}</div>
              </div>

              <div class="pt-2 border-t border-slate-100 text-[11px] space-y-1">
                <div class="flex justify-between text-slate-500">
                  <span>Giới hạn/tài khoản:</span>
                  <strong class="text-blue-700 font-bold">{{ v.max_uses_per_user || (v.target_audience === 'STUDENT' ? 1 : 2) }} lần/acc</strong>
                </div>
                <div class="flex justify-between text-slate-500">
                  <span>Lượt dùng toàn sàn:</span>
                  <strong class="text-slate-800">{{ v.usage_count }} / {{ v.usage_limit }} lượt</strong>
                </div>
                <div class="flex justify-between text-slate-500">
                  <span>Hạn sử dụng:</span>
                  <strong class="text-slate-800">{{ v.valid_until || 'Vô thời hạn' }}</strong>
                </div>
                <div class="grid grid-cols-3 gap-1.5 mt-2.5">
                  <button
                    @click="toggleVoucher(v.id)"
                    class="py-1.5 px-2 rounded-lg font-bold text-[11px] transition cursor-pointer text-center"
                    :class="v.is_active ? 'bg-slate-100 hover:bg-slate-200 text-slate-700' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                    :title="v.is_active ? 'Tạm dừng áp dụng' : 'Kích hoạt mã'"
                  >
                    {{ v.is_active ? '⏸ Tắt' : '▶ Bật' }}
                  </button>
                  <button
                    type="button"
                    @click="openEditVoucherModal(v)"
                    class="py-1.5 px-2 rounded-lg font-bold text-[11px] bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 transition cursor-pointer text-center flex items-center justify-center gap-1"
                    title="Chỉnh sửa thông tin voucher"
                  >
                    <span>✏️</span> Sửa
                  </button>
                  <button
                    type="button"
                    @click="deleteVoucher(v)"
                    class="py-1.5 px-2 rounded-lg font-bold text-[11px] bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 transition cursor-pointer text-center flex items-center justify-center gap-1"
                    title="Xóa vĩnh viễn voucher"
                  >
                    <span>🗑️</span> Xóa
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CẤU HÌNH ĐIỂM TÍCH LŨY KHÁCH HÀNG THÂN THIẾT (LOYALTY SYSTEM) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
          <h2 class="text-sm font-black text-slate-900 flex items-center gap-2 pb-2 border-b border-slate-100">
            <span>👑 Cấu Hình Tích Lũy Điểm & Nâng Hạng Thành Viên</span>
          </h2>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 bg-slate-50/70 rounded-xl border border-slate-200 space-y-2">
              <span class="text-[11px] font-bold text-slate-500 uppercase">Tỷ Lệ Tích Điểm</span>
              <div class="text-lg font-black text-slate-900">1.000 VNĐ = 1 Điểm</div>
              <p class="text-[11px] text-slate-500">Mỗi giao dịch thanh toán thành công được cộng dồn theo SĐT khách</p>
            </div>

            <div class="p-4 bg-slate-50/70 rounded-xl border border-slate-200 space-y-2">
              <span class="text-[11px] font-bold text-slate-500 uppercase">Tỷ Lệ Quy Đổi Giảm Giá Vé</span>
              <div class="text-lg font-black text-emerald-700">100 Điểm = 1.000 VNĐ Giảm Giá</div>
              <p class="text-[11px] text-slate-500">Khách được trừ trực tiếp 1.000đ cho mỗi 100 điểm thưởng khi mua vé mới (1 điểm = 10đ)</p>
            </div>

            <div class="p-4 bg-slate-50/70 rounded-xl border border-slate-200 space-y-2">
              <span class="text-[11px] font-bold text-slate-500 uppercase">Hạn Mức VIP Kim Cương</span>
              <div class="text-lg font-black text-purple-700">Chi Tiêu >= 5.000.000 VNĐ</div>
              <p class="text-[11px] text-slate-500">Tự động nâng hạng thẻ VIP Kim Cương và giảm thêm 15% trọn đời</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== TAB 3: CHÍNH SÁCH HỦY VÉ & HOÀN TIỀN ==================== -->
      <div v-if="activeTab === 'cancellation'" class="space-y-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 space-y-4">
          <div class="pb-3 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
              <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
                <span>📋 Cấu Hình Bậc Thang Hủy Vé & Hoàn Tiền Tự Động</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Quy định hoàn tiền minh bạch khi hành khách hoặc nhà xe hủy vé trước giờ khởi hành
              </p>
            </div>
            <Link
              href="/admin/refunds"
              class="px-4 py-2 rounded-xl bg-[#dc2626] hover:bg-red-700 text-white font-bold text-xs flex items-center gap-1.5 transition shadow-xs shrink-0"
            >
              <span>💸</span>
              <span>Đi Đến Màn Hình Quản Lý Hoàn Tiền & Hủy Vé →</span>
            </Link>
          </div>

          <!-- Bảng 3 Bậc Thang Hủy Vé Chuẩn Nghiệp Vụ -->
          <div class="space-y-3">
            <div
              v-for="policy in cancellationPolicies"
              :key="policy.id"
              class="p-4 rounded-xl border border-slate-200 bg-white flex flex-col md:flex-row md:items-center justify-between gap-4"
            >
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span 
                    class="px-2.5 py-0.5 rounded-full text-xs font-black border"
                    :class="policy.refund_percent === 100 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (policy.refund_percent === 50 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-red-50 text-red-700 border-red-200')"
                  >
                    Hoàn {{ policy.refund_percent }}% (Phí hủy {{ policy.fee_percent }}%)
                  </span>
                  <span class="font-bold text-slate-900 text-sm">{{ policy.rule_name }}</span>
                </div>
                <p class="text-xs text-slate-500">{{ policy.description }}</p>
              </div>

              <!-- Input Chỉnh Sửa % Hoàn Tiền Nhanh -->
              <div class="flex items-center gap-2 shrink-0">
                <div class="flex items-center gap-1 text-xs">
                  <span class="text-slate-500 font-bold">% Hoàn:</span>
                  <input
                    type="number"
                    v-model.number="policy.refund_percent"
                    min="0"
                    max="100"
                    class="w-16 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 text-center font-bold text-slate-900"
                  />
                  <span class="font-bold text-slate-700">%</span>
                </div>

                <button
                  @click="savePolicy(policy)"
                  class="px-3.5 py-1.5 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl cursor-pointer"
                >
                  Lưu
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- MODAL: THÊM QUY TẮC GIÁ MỚI -->
    <div v-if="showAddRuleModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-5 space-y-4 shadow-xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="font-black text-slate-900 text-sm">➕ Thêm Quy Tắc Tăng Giá Linh Hoạt</h3>
          <button @click="showAddRuleModal = false" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>

        <form @submit.prevent="submitRule" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Tên Quy Tắc Điều Chỉnh Giá</label>
            <input 
              type="text" 
              v-model="ruleForm.title" 
              required 
              placeholder="Ví dụ: Phụ thu cao điểm Giỗ Tổ Hùng Vương 2026"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-medium"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Loại Dịp Áp Dụng</label>
              <select v-model="ruleForm.rule_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900">
                <option value="HOLIDAY">🎆 Dịp Lễ / Tết</option>
                <option value="WEEKEND">🗓️ Cuối Tuần (T6-CN)</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tỷ Lệ Tăng Giá (%)</label>
              <input 
                type="number" 
                v-model.number="ruleForm.multiplier_percent" 
                required 
                placeholder="30"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-red-600"
              />
            </div>
          </div>

          <div v-if="ruleForm.rule_type === 'HOLIDAY'" class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Ngày Bắt Đầu</label>
              <input type="date" v-model="ruleForm.start_date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Ngày Kết Thúc</label>
              <input type="date" v-model="ruleForm.end_date" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Mô Tả Chi Tiết Áp Dụng</label>
            <textarea 
              v-model="ruleForm.description" 
              rows="2" 
              placeholder="Ghi chú áp dụng cho toàn bộ tuyến xe giường nằm chiều từ Đà Nẵng..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900"
            ></textarea>
          </div>

          <div class="pt-2 flex items-center gap-2">
            <button
              type="submit"
              class="flex-1 py-2.5 bg-[#dc2626] hover:bg-red-700 text-white font-bold rounded-xl cursor-pointer"
            >
              Lưu Quy Tắc Giá
            </button>
            <button
              type="button"
              @click="showAddRuleModal = false"
              class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
            >
              Hủy
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: TẠO VOUCHER MỚI -->
    <div v-if="showAddVoucherModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl border border-slate-200 max-w-md w-full p-5 space-y-4 shadow-xl">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
          <h3 class="font-black text-slate-900 text-sm">🎟️ Tạo Mã Voucher Khuyến Mãi Mới</h3>
          <button @click="showAddVoucherModal = false" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>

        <form @submit.prevent="submitVoucher" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Mã Voucher (Code Viết Hoa)</label>
            <input 
              type="text" 
              v-model="voucherForm.code" 
              required 
              placeholder="Ví dụ: TET2026, VIPHOIAN"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono uppercase font-bold text-red-600"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Đối Tượng Áp Dụng</label>
            <select v-model="voucherForm.target_audience" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900">
              <option value="ALL">🌐 Cho Tất Cả (Toàn bộ khách hàng)</option>
              <option value="INDIVIDUAL">👤 Cho Cá Nhân (Hành khách cá nhân)</option>
              <option value="STUDENT">🎓 Cho Sinh Viên (Bắt buộc email trường .edu.vn)</option>
            </select>
            <p class="text-[10px] text-slate-500 mt-1">
              {{ voucherForm.target_audience === 'STUDENT' ? '🎓 Khách hàng phải có email .edu.vn hoặc tài khoản đã xác minh thẻ sinh viên mới được áp dụng.' : (voucherForm.target_audience === 'INDIVIDUAL' ? '👤 Dành riêng cho khách hàng cá nhân khi đặt vé.' : '🌐 Áp dụng tự do cho mọi khách hàng.') }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Loại Giảm Giá</label>
              <select v-model="voucherForm.discount_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900">
                <option value="PERCENT">% Theo Giá Vé</option>
                <option value="FIXED">Số Tiền Cố Định (VNĐ)</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Giá Trị Giảm</label>
              <input 
                type="number" 
                v-model.number="voucherForm.discount_value" 
                required 
                placeholder="Ví dụ: 20 (% hoặc VNĐ)"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900"
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Giới Hạn / 1 Acc</label>
              <input 
                type="number" 
                v-model.number="voucherForm.max_uses_per_user" 
                min="1"
                required 
                placeholder="2"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-blue-700"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tổng Lượt Dùng</label>
              <input 
                type="number" 
                v-model.number="voucherForm.usage_limit" 
                required 
                placeholder="100"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Hạn Sử Dụng</label>
              <input type="date" v-model="voucherForm.valid_until" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Mô Tả & Điều Kiện Áp Dụng</label>
            <input 
              type="text" 
              v-model="voucherForm.description" 
              placeholder="Ví dụ: Áp dụng cho các chuyến xuất bến trước ngày 28/02"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900"
            />
          </div>

          <div class="pt-2 flex items-center gap-2">
            <button
              type="submit"
              class="flex-1 py-2.5 bg-[#dc2626] hover:bg-red-700 text-white font-bold rounded-xl cursor-pointer"
            >
              Tạo Voucher
            </button>
            <button
              type="button"
              @click="showAddVoucherModal = false"
              class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
            >
              Hủy
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: CHỈNH SỬA VOUCHER -->
    <div v-if="showEditVoucherModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="font-black text-slate-900 text-sm">✏️ Chỉnh Sửa Mã Voucher Khuyến Mãi</h3>
          <button @click="showEditVoucherModal = false" class="text-slate-400 hover:text-slate-700">✕</button>
        </div>

        <form @submit.prevent="submitEditVoucher" class="space-y-3 text-xs">
          <div>
            <label class="block font-bold text-slate-700 mb-1">Mã Voucher (Code Viết Hoa)</label>
            <input 
              type="text" 
              v-model="editVoucherForm.code" 
              required 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold uppercase text-slate-900"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Đối Tượng Áp Dụng</label>
            <select v-model="editVoucherForm.target_audience" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900">
              <option value="ALL">🌐 Cho Tất Cả (Toàn bộ khách hàng)</option>
              <option value="INDIVIDUAL">👤 Cho Cá Nhân (Hành khách cá nhân)</option>
              <option value="STUDENT">🎓 Cho Sinh Viên (Bắt buộc email trường .edu.vn)</option>
            </select>
            <p class="text-[10px] text-slate-500 mt-1">
              {{ editVoucherForm.target_audience === 'STUDENT' ? '🎓 Khách hàng phải có email .edu.vn hoặc tài khoản đã xác minh thẻ sinh viên mới được áp dụng.' : (editVoucherForm.target_audience === 'INDIVIDUAL' ? '👤 Dành riêng cho khách hàng cá nhân khi đặt vé.' : '🌐 Áp dụng tự do cho mọi khách hàng.') }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Loại Giảm Giá</label>
              <select v-model="editVoucherForm.discount_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900">
                <option value="PERCENT">Phần trăm (%)</option>
                <option value="FIXED">Số tiền cố định (VNĐ)</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Mức Giảm ({{ editVoucherForm.discount_type === 'PERCENT' ? '%' : 'VNĐ' }})</label>
              <input 
                type="number" 
                v-model.number="editVoucherForm.discount_value" 
                required 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900"
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Giới Hạn / 1 Acc</label>
              <input 
                type="number" 
                v-model.number="editVoucherForm.max_uses_per_user" 
                min="1"
                required 
                placeholder="2"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-blue-700"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tổng Lượt Dùng</label>
              <input 
                type="number" 
                v-model.number="editVoucherForm.usage_limit" 
                required 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Hạn Sử Dụng</label>
              <input type="date" v-model="editVoucherForm.valid_until" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3" v-if="editVoucherForm.discount_type === 'PERCENT'">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Đơn Tối Thiểu (VNĐ)</label>
              <input 
                type="number" 
                v-model.number="editVoucherForm.min_order_value" 
                placeholder="0"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900"
              />
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Giảm Tối Đa (VNĐ)</label>
              <input 
                type="number" 
                v-model.number="editVoucherForm.max_discount" 
                placeholder="100000"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900"
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Mô Tả & Điều Kiện Áp Dụng</label>
            <input 
              type="text" 
              v-model="editVoucherForm.description" 
              placeholder="Ví dụ: Áp dụng cho các chuyến xuất bến trước ngày 28/02"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900"
            />
          </div>

          <div class="pt-2 flex items-center gap-2">
            <button
              type="submit"
              :disabled="isSubmittingEditVoucher"
              class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl cursor-pointer disabled:opacity-50"
            >
              {{ isSubmittingEditVoucher ? 'Đang lưu...' : 'Lưu Thay Đổi' }}
            </button>
            <button
              type="button"
              @click="showEditVoucherModal = false"
              class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
            >
              Hủy
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';

const props = defineProps({
  pricingRules: Array,
  vouchers: Array,
  cancellationPolicies: Array,
  initialTab: String,
});

const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const isVoucherRoute = typeof window !== 'undefined' && window.location.pathname.includes('/admin/vouchers');
const activeTab = ref(
  props.initialTab || (urlParams?.get('tab')) || (isVoucherRoute ? 'vouchers' : 'pricing')
);

const voucherAudienceFilter = ref('ALL_FILTER'); // 'ALL_FILTER', 'STUDENT', 'INDIVIDUAL'

const studentVouchersCount = computed(() => {
  return (props.vouchers || []).filter(v => v.target_audience === 'STUDENT').length;
});

const individualVouchersCount = computed(() => {
  return (props.vouchers || []).filter(v => v.target_audience === 'INDIVIDUAL').length;
});

const filteredVouchers = computed(() => {
  if (voucherAudienceFilter.value === 'STUDENT') {
    return (props.vouchers || []).filter(v => v.target_audience === 'STUDENT');
  }
  if (voucherAudienceFilter.value === 'INDIVIDUAL') {
    return (props.vouchers || []).filter(v => v.target_audience === 'INDIVIDUAL');
  }
  return props.vouchers || [];
});

const showAddRuleModal = ref(false);
const showAddVoucherModal = ref(false);
const showEditVoucherModal = ref(false);
const editingVoucherId = ref(null);

const ruleForm = reactive({
  title: '',
  rule_type: 'HOLIDAY',
  multiplier_percent: 30,
  start_date: '',
  end_date: '',
  description: '',
});

const voucherForm = reactive({
  code: '',
  discount_type: 'PERCENT',
  discount_value: 20,
  target_audience: 'ALL',
  usage_limit: 100,
  max_uses_per_user: 2,
  valid_until: '',
  description: '',
});

const editVoucherForm = reactive({
  code: '',
  discount_type: 'PERCENT',
  discount_value: 20,
  target_audience: 'ALL',
  min_order_value: 0,
  max_discount: 100000,
  usage_limit: 100,
  max_uses_per_user: 2,
  valid_until: '',
  description: '',
});

const isSubmittingEditVoucher = ref(false);

const openEditVoucherModal = (v) => {
  editingVoucherId.value = v.id;
  editVoucherForm.code = v.code || '';
  editVoucherForm.discount_type = v.discount_type || 'PERCENT';
  editVoucherForm.discount_value = v.discount_value ?? 0;
  editVoucherForm.target_audience = v.target_audience || 'ALL';
  editVoucherForm.min_order_value = v.min_order_value ?? 0;
  editVoucherForm.max_discount = v.max_discount ?? 100000;
  editVoucherForm.usage_limit = v.usage_limit ?? 100;
  editVoucherForm.max_uses_per_user = v.max_uses_per_user || (v.target_audience === 'STUDENT' ? 1 : 2);
  editVoucherForm.valid_until = v.valid_until ? String(v.valid_until).substring(0, 10) : '';
  editVoucherForm.description = v.description || '';
  showEditVoucherModal.value = true;
};

const submitEditVoucher = () => {
  if (!editingVoucherId.value) return;
  isSubmittingEditVoucher.value = true;
  router.post(`/admin/pricing/vouchers/${editingVoucherId.value}/update`, editVoucherForm, {
    preserveScroll: true,
    onSuccess: () => {
      showEditVoucherModal.value = false;
      editingVoucherId.value = null;
      isSubmittingEditVoucher.value = false;
    },
    onError: (errors) => {
      isSubmittingEditVoucher.value = false;
      const msg = Object.values(errors).flat().join('\n') || 'Không thể lưu thay đổi mã Voucher!';
      alert(msg);
    }
  });
};

const deleteVoucher = (v) => {
  if (confirm(`Bạn có chắc chắn muốn xóa vĩnh viễn mã Voucher [${v.code}] không?`)) {
    router.post(`/admin/pricing/vouchers/${v.id}/delete`);
  }
};

const toggleRule = (id) => {
  router.post(`/admin/pricing/rules/${id}/toggle`);
};

const toggleVoucher = (id) => {
  router.post(`/admin/pricing/vouchers/${id}/toggle`);
};

const submitRule = () => {
  router.post('/admin/pricing/rules', ruleForm, {
    onSuccess: () => {
      showAddRuleModal.value = false;
      ruleForm.title = '';
      ruleForm.description = '';
    }
  });
};

const submitVoucher = () => {
  router.post('/admin/pricing/vouchers', voucherForm, {
    onSuccess: () => {
      showAddVoucherModal.value = false;
      voucherForm.code = '';
      voucherForm.description = '';
    }
  });
};

const savePolicy = (policy) => {
  router.post(`/admin/pricing/refund-policy/${policy.id}`, {
    refund_percent: policy.refund_percent,
    description: policy.description,
  });
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};
</script>
