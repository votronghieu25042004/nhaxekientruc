<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans text-slate-800">
    <AdminHeader 
      title="QUẢN LÝ HOÀN TIỀN & XỬ LÝ HỦY VÉ" 
      subtitle="Theo dõi yêu cầu hủy vé của khách hàng, quét mã QR chuyển khoản, tự động tính tỷ lệ hoàn tiền theo bậc thang và duyệt hoàn trả minh bạch" 
    />

    <main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full space-y-6">
      <!-- Flash Message -->
      <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2 shadow-xs">
        <span class="text-base">✅</span>
        <span>{{ $page.props.flash.success }}</span>
      </div>
      <div v-if="$page.props.flash?.error" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-bold flex items-center gap-2 shadow-xs">
        <span class="text-base">⚠️</span>
        <span>{{ $page.props.flash.error }}</span>
      </div>

      <!-- 1. KPI THỐNG KÊ HOÀN TIỀN & HỦY VÉ -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Tổng Vé Hủy -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tổng Vé Đã Hủy</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ kpi.total_cancelled }} <span class="text-xs text-slate-400 font-normal">vé</span></p>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Khách hủy & Nhà xe điều chỉnh</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl">
            📋
          </div>
        </div>

        <!-- Chờ Xử Lý Hoàn Tiền -->
        <div class="bg-gradient-to-br from-amber-50 to-white p-5 rounded-2xl border-2 border-amber-200 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Chờ Xử Lý Hoàn Tiền</p>
            <p class="text-2xl font-black text-amber-600 mt-1">{{ kpi.pending_refund }} <span class="text-xs text-amber-500 font-normal">vé</span></p>
            <p class="text-[11px] text-amber-600 font-semibold mt-0.5">Cần Admin / Kế toán duyệt</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl shadow-xs">
            ⏳
          </div>
        </div>

        <!-- Đã Hoàn Tiền Thành Công -->
        <div class="bg-gradient-to-br from-emerald-50 to-white p-5 rounded-2xl border-2 border-emerald-200 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Đã Hoàn Tiền</p>
            <p class="text-2xl font-black text-emerald-600 font-mono mt-1">{{ formatCurrency(kpi.total_refund_amount) }}</p>
            <p class="text-[11px] text-emerald-700 font-semibold mt-0.5">{{ kpi.total_refunded }} vé đã chuyển trả khách</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl shadow-xs">
            💸
          </div>
        </div>

        <!-- Từ Chối / Không Đủ Điều Kiện -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Từ Chối / Phí 100%</p>
            <p class="text-2xl font-black text-rose-600 mt-1">{{ kpi.rejected_count }} <span class="text-xs text-slate-400 font-normal">vé</span></p>
            <p class="text-[11px] text-rose-500 font-medium mt-0.5">Hủy &lt;12h hoặc No-show</p>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
            ⛔
          </div>
        </div>
      </div>

      <!-- 2. BẢNG QUY ĐỊNH BẬC THANG HỦY VÉ & HOÀN TIỀN (POLICY REMINDER) -->
      <div class="bg-white p-4.5 rounded-2xl border border-slate-200 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between pb-3 border-b border-slate-100 gap-2">
          <div class="flex items-center gap-2">
            <span class="text-base">📋</span>
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-900">Chính Sách Hoàn Tiền Bậc Thang Tự Động Theo Giờ Khởi Hành</h2>
          </div>
          <Link href="/admin/pricing" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
            <span>⚙️ Cấu hình mức % tại đây</span>
            <span>→</span>
          </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
          <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-start gap-3">
            <span class="text-xl">🟢</span>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-black text-emerald-800">Trước Giờ Khởi Hành &gt; 24h</span>
                <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-emerald-600 text-white">Hoàn 100%</span>
              </div>
              <p class="text-[11px] text-emerald-700 mt-0.5">Khách được hoàn trả toàn bộ 100% số tiền đã thanh toán (Phí hủy: 0%).</p>
            </div>
          </div>

          <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200 flex items-start gap-3">
            <span class="text-xl">🟡</span>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-black text-amber-800">Từ 12h Đến 24h Trước Khởi Hành</span>
                <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-amber-600 text-white">Hoàn 50%</span>
              </div>
              <p class="text-[11px] text-amber-700 mt-0.5">Khách được hoàn trả 50% số tiền đã thanh toán (Phí hủy giữ chỗ: 50%).</p>
            </div>
          </div>

          <div class="p-3 rounded-xl bg-rose-50/70 border border-rose-200 flex items-start gap-3">
            <span class="text-xl">🔴</span>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-black text-rose-800">Dưới 12h Hoặc Bỏ Chuyến (No-show)</span>
                <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-rose-600 text-white">Hoàn 0%</span>
              </div>
              <p class="text-[11px] text-rose-700 mt-0.5">Hủy quá sát giờ xe chạy, không hỗ trợ hoàn tiền tự động (Phí hủy: 100%).</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. TABS LỌC TRẠNG THÁI & TÌM KIẾM -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- 4 Sub-Tabs -->
        <div class="flex flex-wrap items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl text-xs font-bold text-slate-600 border border-slate-200">
          <button
            @click="setStatusFilter('all')"
            class="px-3.5 py-2 rounded-xl transition cursor-pointer flex items-center gap-1.5"
            :class="activeStatus === 'all' ? 'bg-[#dc2626] text-white shadow-xs font-black' : 'hover:bg-white text-slate-700 font-bold'"
          >
            <span>Tất Cả</span>
            <span class="px-1.5 py-0.2 rounded text-[10px] bg-white/20">{{ kpi.total_cancelled }}</span>
          </button>

          <button
            @click="setStatusFilter('pending')"
            class="px-3.5 py-2 rounded-xl transition cursor-pointer flex items-center gap-1.5"
            :class="activeStatus === 'pending' ? 'bg-[#dc2626] text-white shadow-xs font-black' : 'hover:bg-white text-slate-700 font-bold'"
          >
            <span>⏳ Chờ Duyệt Hoàn</span>
            <span class="px-1.5 py-0.2 rounded text-[10px] bg-amber-500 text-white font-black">{{ kpi.pending_refund }}</span>
          </button>

          <button
            @click="setStatusFilter('refunded')"
            class="px-3.5 py-2 rounded-xl transition cursor-pointer flex items-center gap-1.5"
            :class="activeStatus === 'refunded' ? 'bg-[#dc2626] text-white shadow-xs font-black' : 'hover:bg-white text-slate-700 font-bold'"
          >
            <span>✅ Đã Hoàn Tiền</span>
            <span class="px-1.5 py-0.2 rounded text-[10px] bg-white/20">{{ kpi.total_refunded }}</span>
          </button>

          <button
            @click="setStatusFilter('rejected')"
            class="px-3.5 py-2 rounded-xl transition cursor-pointer flex items-center gap-1.5"
            :class="activeStatus === 'rejected' ? 'bg-[#dc2626] text-white shadow-xs font-black' : 'hover:bg-white text-slate-700 font-bold'"
          >
            <span>❌ Từ Chối / Phí 100%</span>
            <span class="px-1.5 py-0.2 rounded text-[10px] bg-white/20">{{ kpi.rejected_count }}</span>
          </button>
        </div>

        <!-- Ô Tìm Kiếm -->
        <div class="relative w-full md:w-80">
          <input
            type="text"
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            placeholder="Tìm mã vé, tên khách, số điện thoại..."
            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-16 py-2 text-xs font-medium text-slate-900 focus:bg-white focus:outline-hidden focus:border-red-500 transition"
          />
          <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
          <button
            @click="handleSearch"
            class="absolute right-1.5 top-1 px-2.5 py-1 bg-slate-900 hover:bg-black text-white text-[11px] font-bold rounded-lg cursor-pointer"
          >
            Tìm
          </button>
        </div>
      </div>

      <!-- 4. BẢNG DANH SÁCH VÉ HỦY & XỬ LÝ HOÀN TIỀN -->
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-700 border-collapse">
            <thead class="bg-slate-100/80 text-[11px] font-black text-slate-600 uppercase border-b border-slate-200 tracking-wider">
              <tr>
                <th class="py-3 px-3.5">Mã Vé</th>
                <th class="py-3 px-3.5">Hành Khách</th>
                <th class="py-3 px-3.5">Chuyến Xe & Giờ Chạy</th>
                <th class="py-3 px-3.5 text-center">Thời Gian Hủy</th>
                <th class="py-3 px-3.5 text-center">Tài Khoản & QR Hoàn Tiền</th>
                <th class="py-3 px-3.5 text-right">Đã Thanh Toán</th>
                <th class="py-3 px-3.5 text-center">Bậc Thang</th>
                <th class="py-3 px-3.5 text-right">Tiền Hoàn Lại</th>
                <th class="py-3 px-3.5 text-center">Trạng Thái</th>
                <th class="py-3 px-3.5 text-right">Hành Động</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-if="refundRecords.data.length === 0">
                <td colspan="10" class="py-12 text-center text-slate-400 text-xs font-bold">
                  📭 Không tìm thấy bản ghi hủy vé hoặc hoàn tiền nào phù hợp với bộ lọc hiện tại.
                </td>
              </tr>

              <tr 
                v-for="record in refundRecords.data" 
                :key="record.id"
                class="hover:bg-slate-50/80 transition"
              >
                <!-- Mã Vé -->
                <td class="py-3 px-3.5 whitespace-nowrap">
                  <div class="font-mono font-black text-slate-900 text-xs">{{ record.booking_code }}</div>
                  <div class="text-[10px] text-slate-400 font-bold mt-0.5">
                    Thanh toán: <span class="text-slate-700 font-mono">{{ record.payment_method || 'MOMO' }}</span>
                  </div>
                </td>

                <!-- Hành Khách -->
                <td class="py-3 px-3.5 whitespace-nowrap">
                  <div class="font-bold text-slate-900 text-xs">{{ record.customer_name }}</div>
                  <div class="font-mono text-[11px] text-slate-500 mt-0.5">{{ record.customer_phone }}</div>
                  <div v-if="record.customer_email" class="text-[10px] text-slate-400 truncate max-w-[140px]">{{ record.customer_email }}</div>
                </td>

                <!-- Chuyến Xe & Giờ Chạy -->
                <td class="py-3 px-3.5">
                  <div class="font-bold text-slate-900 text-xs truncate max-w-[180px]">{{ record.route_name }}</div>
                  <div class="text-[11px] text-slate-500 mt-0.5 flex items-center gap-1">
                    <span>🕒</span>
                    <span class="font-mono font-semibold">{{ record.departure_time }}</span>
                  </div>
                  <div class="text-[10px] text-slate-400 font-mono">Chuyến: {{ record.trip_code }}</div>
                </td>

                <!-- Thời Gian Hủy & Số Giờ Trước Khởi Hành -->
                <td class="py-3 px-3.5 text-center whitespace-nowrap">
                  <div class="font-mono text-[11px] text-slate-700">{{ record.cancelled_at }}</div>
                  <div 
                    class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold border"
                    :class="record.hours_before >= 24 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : (record.hours_before >= 12 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200')"
                  >
                    <span v-if="record.hours_before >= 0">Trước {{ record.hours_before }}h</span>
                    <span v-else>Đã qua giờ chạy</span>
                  </div>
                </td>

                <!-- TÀI KHOẢN & MÃ QR NHẬN TIỀN HOÀN -->
                <td class="py-3 px-3.5 whitespace-nowrap">
                  <div v-if="record.refund_bank_name || record.refund_account_number" class="flex items-center gap-2">
                    <!-- Thumbnail QR (Click để xem to) -->
                    <div 
                      v-if="record.refund_qr_code"
                      @click="openQrModal(record.refund_qr_code, record.booking_code)"
                      class="w-10 h-10 p-1 bg-white rounded-xl border border-slate-200 shadow-2xs hover:scale-110 transition cursor-pointer flex items-center justify-center shrink-0 group"
                      title="Bấm để xem phóng to mã QR quét chuyển khoản"
                    >
                      <img :src="record.refund_qr_code" alt="QR" class="w-full h-full object-contain rounded" />
                    </div>
                    <div v-else class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 text-xs shrink-0">
                      💳
                    </div>

                    <div class="text-left">
                      <div class="font-bold text-slate-900 text-xs flex items-center gap-1">
                        <span>{{ record.refund_bank_name || 'Ngân hàng' }}</span>
                        <button 
                          v-if="record.refund_account_number"
                          @click="copyText(record.refund_account_number)"
                          type="button"
                          class="text-[10px] text-slate-400 hover:text-red-600 cursor-pointer"
                          title="Sao chép STK"
                        >
                          📋
                        </button>
                      </div>
                      <div class="font-mono font-black text-slate-800 text-xs">
                        {{ record.refund_account_number }}
                      </div>
                      <div v-if="record.refund_account_name" class="text-[10px] uppercase font-bold text-slate-500">
                        {{ record.refund_account_name }}
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-[11px] text-slate-400 italic">
                    Chưa gửi STK / Quầy
                  </div>
                </td>

                <!-- Đã Thanh Toán -->
                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                  <div class="font-mono font-bold text-slate-900">{{ formatCurrency(record.paid_amount) }}</div>
                  <div v-if="record.paid_amount < record.total_amount" class="text-[10px] text-amber-600 font-bold">
                    (Cọc 30%)
                  </div>
                </td>

                <!-- Bậc Thang Hoàn -->
                <td class="py-3 px-3.5 text-center whitespace-nowrap">
                  <div 
                    class="inline-block px-2.5 py-0.5 rounded-full text-xs font-black border"
                    :class="record.refund_percent === 100 ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : (record.refund_percent === 50 ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-rose-100 text-rose-800 border-rose-300')"
                  >
                    Hoàn {{ record.refund_percent }}%
                  </div>
                  <div class="text-[10px] text-slate-400 mt-0.5">Phí {{ record.fee_percent }}%</div>
                </td>

                <!-- Tiền Hoàn Lại -->
                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                  <div class="font-mono font-black text-sm" :class="record.refund_amount > 0 ? 'text-emerald-600' : 'text-slate-400'">
                    {{ formatCurrency(record.refund_amount) }}
                  </div>
                  <div v-if="record.fee_amount > 0" class="text-[10px] text-slate-400 font-mono">
                    Phí: {{ formatCurrency(record.fee_amount) }}
                  </div>
                </td>

                <!-- Trạng Thái -->
                <td class="py-3 px-3.5 text-center whitespace-nowrap">
                  <span 
                    v-if="record.status_badge === 'REFUNDED'" 
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300"
                  >
                    <span>✓</span>
                    <span>ĐÃ HOÀN TIỀN</span>
                  </span>

                  <span 
                    v-else-if="record.status_badge === 'REJECTED'" 
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-rose-100 text-rose-800 border border-rose-300"
                  >
                    <span>✕</span>
                    <span>TỪ CHỐI / 0%</span>
                  </span>

                  <span 
                    v-else 
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-amber-100 text-amber-800 border border-amber-300 animate-pulse"
                  >
                    <span>⏳</span>
                    <span>CHỜ DUYỆT</span>
                  </span>

                  <!-- Ghi chú / lý do -->
                  <div v-if="record.notes" class="text-[10px] text-slate-500 mt-1 max-w-[150px] truncate mx-auto" :title="record.notes">
                    📝 {{ record.notes }}
                  </div>
                </td>

                <!-- Hành Động -->
                <td class="py-3 px-3.5 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <!-- Nút Phê Duyệt Hoàn Tiền -->
                    <button
                      v-if="record.status_badge !== 'REFUNDED'"
                      @click="openApproveModal(record)"
                      class="px-2.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1 cursor-pointer transition shadow-2xs"
                      title="Duyệt hoàn tiền cho hành khách theo STK"
                    >
                      <span>✓</span>
                      <span>Duyệt Hoàn</span>
                    </button>

                    <!-- Nút Từ Chối Hoàn -->
                    <button
                      v-if="record.status_badge === 'PENDING'"
                      @click="openRejectModal(record)"
                      class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-700 font-bold text-xs border border-slate-200 cursor-pointer transition"
                      title="Từ chối yêu cầu hoàn tiền do vi phạm quy chế"
                    >
                      <span>✕</span>
                      <span>Từ Chối</span>
                    </button>

                    <!-- Nếu Đã Hoàn Tiền: Nút Xem Chi Tiết -->
                    <button
                      v-if="record.status_badge === 'REFUNDED'"
                      @click="viewDetails(record)"
                      class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-200 cursor-pointer transition"
                    >
                      <span>👁️ Chi Tiết</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Phân Trang (Pagination) -->
        <div v-if="refundRecords.links && refundRecords.links.length > 3" class="p-4 border-t border-slate-200 flex items-center justify-between">
          <div class="text-xs text-slate-500">
            Hiển thị <span class="font-bold text-slate-900">{{ refundRecords.from || 0 }}</span> đến <span class="font-bold text-slate-900">{{ refundRecords.to || 0 }}</span> trong tổng số <span class="font-bold text-slate-900">{{ refundRecords.total }}</span> bản ghi
          </div>
          <div class="flex items-center gap-1">
            <template v-for="(link, i) in refundRecords.links" :key="i">
              <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                class="px-3 py-1.5 rounded-lg text-xs font-bold transition"
                :class="link.active ? 'bg-[#dc2626] text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
              />
              <span
                v-else
                v-html="link.label"
                class="px-3 py-1.5 rounded-lg text-xs text-slate-400 font-medium"
              />
            </template>
          </div>
        </div>
      </div>
    </main>

    <!-- ==================== MODAL 1: PHÊ DUYỆT HOÀN TIỀN & QUÉT QR ==================== -->
    <div v-if="showApproveModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto">
      <div class="bg-white rounded-3xl border border-slate-200 max-w-lg w-full p-6 space-y-4 shadow-2xl animate-in fade-in zoom-in-95 duration-150 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-black">
              ✓
            </div>
            <div>
              <h3 class="font-black text-slate-900 text-sm">Phê Duyệt & Chuyển Tiền Hoàn Vé</h3>
              <p class="text-[11px] text-slate-500 font-mono">#{{ selectedRecord?.booking_code }}</p>
            </div>
          </div>
          <button @click="showApproveModal = false" class="text-slate-400 hover:text-slate-700 text-lg">✕</button>
        </div>

        <form @submit.prevent="submitApproveRefund" class="space-y-4 text-xs">
          <!-- KHỐI MÃ QR VÀ THÔNG TIN TÀI KHOẢN NGÂN HÀNG CỦA KHÁCH -->
          <div class="p-4 rounded-2xl bg-gradient-to-br from-emerald-50/70 via-teal-50/50 to-white border-2 border-emerald-200 text-center space-y-3">
            <span class="text-[10px] font-black uppercase tracking-wider text-emerald-800 block">
              QUÉT MÃ QR ĐỂ CHUYỂN KHOẢN HOÀN TIỀN
            </span>

            <div class="w-44 h-44 mx-auto p-2 bg-white rounded-2xl border-2 border-emerald-300 shadow-md flex items-center justify-center">
              <img 
                v-if="selectedRecord?.refund_qr_code" 
                :src="selectedRecord.refund_qr_code" 
                alt="QR Code Hoàn Tiền" 
                class="w-full h-full object-contain rounded-xl"
              />
              <div v-else class="text-slate-400 text-center text-xs">
                Chưa có ảnh QR<br>(Chuyển theo STK bên dưới)
              </div>
            </div>

            <!-- Thẻ thông tin tài khoản -->
            <div class="bg-white p-3 rounded-xl border border-emerald-200 text-left space-y-1">
              <div class="flex justify-between items-center">
                <span class="text-slate-400 text-[11px]">Ngân Hàng:</span>
                <strong class="text-slate-900 font-bold">{{ selectedRecord?.refund_bank_name || 'Ngân hàng' }}</strong>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-400 text-[11px]">Số Tài Khoản (STK):</span>
                <div class="flex items-center gap-1.5">
                  <strong class="text-emerald-700 font-mono font-black text-sm">{{ selectedRecord?.refund_account_number }}</strong>
                  <button 
                    type="button" 
                    @click="copyText(selectedRecord?.refund_account_number)" 
                    class="px-2 py-0.5 rounded bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-[10px] font-bold cursor-pointer"
                  >
                    Copy
                  </button>
                </div>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-400 text-[11px]">Tên Chủ Tài Khoản:</span>
                <strong class="text-slate-900 uppercase font-black">{{ selectedRecord?.refund_account_name }}</strong>
              </div>
              <div class="flex justify-between items-center pt-1 border-t border-slate-100">
                <span class="text-slate-500 font-bold">Số Tiền Cần Hoàn:</span>
                <strong class="text-emerald-700 font-mono font-black text-base">{{ formatCurrency(approveForm.refund_amount) }}</strong>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-slate-400 text-[11px]">Nội Dung Chuyển Khoản:</span>
                <span class="font-mono font-bold text-slate-800 text-[11px]">HOAN VE {{ selectedRecord?.booking_code }}</span>
              </div>
            </div>
          </div>

          <!-- Số Tiền Hoàn & Phương Thức -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Số Tiền Hoàn Trả (VNĐ)</label>
              <input 
                type="number" 
                v-model.number="approveForm.refund_amount" 
                required 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-black text-emerald-600 text-sm focus:bg-white focus:outline-hidden focus:border-emerald-500"
              />
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Phương Thức Hoàn</label>
              <select 
                v-model="approveForm.payment_method" 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-hidden"
              >
                <option value="BANK_TRANSFER">Chuyển Khoản Ngân Hàng (STK)</option>
                <option value="MOMO">Ví MoMo</option>
                <option value="ZALOPAY">Ví ZaloPay</option>
                <option value="VNPAY">Cổng VNPay</option>
                <option value="CASH">Tiền Mặt Tại Quầy</option>
              </select>
            </div>
          </div>

          <!-- Mã Giao Dịch & Ghi Chú Chứng Từ -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Mã Giao Dịch Chuyển Khoản / Chứng Từ Ngân Hàng</label>
            <input 
              type="text" 
              v-model="approveForm.transaction_code" 
              placeholder="Ví dụ: FT241030012345, Mã GD VCB 987654..." 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 focus:bg-white focus:outline-hidden focus:border-emerald-500 font-mono"
            />
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Ghi Chú Phê Duyệt</label>
            <input 
              type="text" 
              v-model="approveForm.notes" 
              placeholder="Ghi chú xác nhận gửi khách..." 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 focus:bg-white focus:outline-hidden focus:border-emerald-500"
            />
          </div>

          <div class="pt-2 flex items-center gap-2">
            <button
              type="submit"
              :disabled="approveForm.processing"
              class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl cursor-pointer transition shadow-md flex items-center justify-center gap-2"
            >
              <span v-if="approveForm.processing">Đang Xử Lý...</span>
              <span v-else>Xác Nhận Đã Chuyển Khoản & Duyệt Hoàn Tiền ➔</span>
            </button>
            <button
              type="button"
              @click="showApproveModal = false"
              class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
            >
              Đóng
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ==================== MODAL 2: TỪ CHỐI HOÀN TIỀN ==================== -->
    <div v-if="showRejectModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-2xl animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-sm font-black">
              ✕
            </div>
            <div>
              <h3 class="font-black text-slate-900 text-sm">Từ Chối Hoàn Tiền Vé</h3>
              <p class="text-[11px] text-slate-500 font-mono">#{{ selectedRecord?.booking_code }}</p>
            </div>
          </div>
          <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-700 text-lg">✕</button>
        </div>

        <form @submit.prevent="submitRejectRefund" class="space-y-4 text-xs">
          <p class="text-slate-600">
            Vé này sẽ được ghi nhận là <strong>Không hoàn tiền (Thu phí hủy 100%)</strong> theo đúng quy định điều hành xe. Lý do sẽ hiển thị trực tiếp cho khách hàng.
          </p>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Chọn Hoặc Nhập Lý Do Từ Chối</label>
            <select 
              v-model="rejectForm.reason" 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-900 mb-2 focus:bg-white focus:outline-hidden"
            >
              <option value="Hủy vé sát giờ khởi hành dưới 12 tiếng theo quy chế nhà xe">Hủy vé sát giờ khởi hành dưới 12 tiếng</option>
              <option value="Hành khách bỏ chuyến (No-show) không thông báo trước giờ xuất bến">Hành khách bỏ chuyến (No-show) không thông báo</option>
              <option value="Thông tin số tài khoản hoặc ngân hàng cung cấp không chính xác">Thông tin STK hoặc ngân hàng không chính xác</option>
              <option value="Vé ưu đãi đặc biệt / Vé giờ chót không áp dụng chính sách hoàn hủy">Vé ưu đãi đặc biệt không áp dụng hoàn hủy</option>
              <option value="Khác">Lý do khác (tự nhập bên dưới)</option>
            </select>

            <textarea 
              v-if="rejectForm.reason === 'Khác'"
              v-model="rejectForm.custom_reason" 
              rows="2" 
              required
              placeholder="Nhập chi tiết lý do từ chối hoàn tiền..." 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-2.5 text-slate-900 focus:bg-white focus:outline-hidden"
            ></textarea>
          </div>

          <div class="pt-2 flex items-center gap-2">
            <button
              type="submit"
              :disabled="rejectForm.processing"
              class="flex-1 py-3 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl cursor-pointer transition shadow-xs flex items-center justify-center gap-2"
            >
              <span v-if="rejectForm.processing">Đang Xử Lý...</span>
              <span v-else>Xác Nhận Từ Chối Hoàn Tiền</span>
            </button>
            <button
              type="button"
              @click="showRejectModal = false"
              class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer"
            >
              Đóng
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ==================== MODAL 3: XEM CHI TIẾT VÉ HOÀN ==================== -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-2xl animate-in fade-in zoom-in-95 duration-150 text-xs">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <h3 class="font-black text-slate-900 text-sm">Chi Tiết Hồ Sơ Hoàn Tiền #{{ selectedRecord?.booking_code }}</h3>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-700 text-lg">✕</button>
        </div>

        <div class="space-y-3">
          <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-1.5">
            <div class="flex justify-between">
              <span class="text-slate-500">Khách Hàng:</span>
              <span class="font-bold text-slate-900">{{ selectedRecord?.customer_name }} ({{ selectedRecord?.customer_phone }})</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Tài Khoản Nhận:</span>
              <span class="font-mono font-bold text-slate-900">{{ selectedRecord?.refund_bank_name }} - {{ selectedRecord?.refund_account_number }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Chủ Tài Khoản:</span>
              <span class="font-bold uppercase text-slate-900">{{ selectedRecord?.refund_account_name }}</span>
            </div>
            <div class="flex justify-between pt-1 border-t border-slate-200">
              <span class="text-slate-500">Số Tiền Đã Hoàn:</span>
              <span class="font-mono font-black text-emerald-600 text-sm">{{ formatCurrency(selectedRecord?.refund_amount) }}</span>
            </div>
          </div>

          <div v-if="selectedRecord?.refund_admin_notes || selectedRecord?.notes">
            <span class="font-bold text-slate-700 block mb-1">Ghi Chú Xử Lý:</span>
            <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl text-amber-900 text-[11px] leading-relaxed">
              {{ selectedRecord?.refund_admin_notes || selectedRecord?.notes }}
            </div>
          </div>
        </div>

        <div class="pt-2">
          <button
            @click="showDetailModal = false"
            class="w-full py-2.5 bg-slate-900 hover:bg-black text-white font-bold rounded-xl cursor-pointer"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>

    <!-- ==================== MODAL PHÓNG TO QR ==================== -->
    <div 
      v-if="showQrModal && previewQrUrl" 
      class="fixed inset-0 bg-black/80 backdrop-blur-xs z-60 flex items-center justify-center p-4"
    >
      <div class="bg-white rounded-3xl p-6 max-w-sm w-full text-center space-y-4 shadow-2xl">
        <div class="flex justify-between items-center pb-2 border-b border-slate-100">
          <h4 class="font-black text-sm text-slate-900">Mã QR Chuyển Khoản Khách Hàng</h4>
          <button @click="showQrModal = false" class="text-slate-400 hover:text-slate-700 text-lg">✕</button>
        </div>
        <div class="w-64 h-64 mx-auto p-2 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-center">
          <img :src="previewQrUrl" alt="QR Full" class="w-full h-full object-contain rounded-xl" />
        </div>
        <p class="text-xs text-slate-500 font-mono">#{{ previewQrBookingCode }}</p>
        <button 
          @click="showQrModal = false" 
          class="w-full py-2.5 bg-slate-900 text-white font-bold rounded-xl text-xs cursor-pointer"
        >
          Đóng
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminHeader from '@/Components/AdminHeader.vue';

const props = defineProps({
  refundRecords: Object,
  filters: Object,
  kpi: Object,
  policies: Array,
});

const activeStatus = ref(props.filters.status || 'all');
const searchQuery = ref(props.filters.q || '');

const showApproveModal = ref(false);
const showRejectModal = ref(false);
const showDetailModal = ref(false);
const selectedRecord = ref(null);

const showQrModal = ref(false);
const previewQrUrl = ref(null);
const previewQrBookingCode = ref('');

const approveForm = useForm({
  refund_amount: 0,
  payment_method: 'BANK_TRANSFER',
  transaction_code: '',
  notes: '',
});

const rejectForm = useForm({
  reason: 'Hủy vé sát giờ khởi hành dưới 12 tiếng theo quy chế nhà xe',
  custom_reason: '',
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const copyText = (text) => {
  if (!text) return;
  navigator.clipboard.writeText(text);
  alert('Đã sao chép STK: ' + text);
};

const openQrModal = (url, bookingCode) => {
  previewQrUrl.value = url;
  previewQrBookingCode.value = bookingCode;
  showQrModal.value = true;
};

const setStatusFilter = (status) => {
  activeStatus.value = status;
  router.get('/admin/refunds', {
    status: status,
    q: searchQuery.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const handleSearch = () => {
  router.get('/admin/refunds', {
    status: activeStatus.value,
    q: searchQuery.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const openApproveModal = (record) => {
  selectedRecord.value = record;
  approveForm.refund_amount = record.refund_amount;
  approveForm.payment_method = record.refund_bank_name ? 'BANK_TRANSFER' : (record.payment_method || 'BANK_TRANSFER');
  approveForm.transaction_code = '';
  approveForm.notes = `Đã hoàn tiền ${formatCurrency(record.refund_amount)} qua ${record.refund_bank_name || 'Ngân hàng'}`;
  showApproveModal.value = true;
};

const submitApproveRefund = () => {
  if (!selectedRecord.value) return;
  approveForm.post(`/admin/refunds/${selectedRecord.value.id}/approve`, {
    onSuccess: () => {
      showApproveModal.value = false;
    },
  });
};

const openRejectModal = (record) => {
  selectedRecord.value = record;
  rejectForm.reason = 'Hủy vé sát giờ khởi hành dưới 12 tiếng theo quy chế nhà xe';
  rejectForm.custom_reason = '';
  showRejectModal.value = true;
};

const submitRejectRefund = () => {
  if (!selectedRecord.value) return;
  const reasonToSubmit = rejectForm.reason === 'Khác' ? rejectForm.custom_reason : rejectForm.reason;
  rejectForm.transform(() => ({
    reason: reasonToSubmit,
  })).post(`/admin/refunds/${selectedRecord.value.id}/reject`, {
    onSuccess: () => {
      showRejectModal.value = false;
    },
  });
};

const viewDetails = (record) => {
  selectedRecord.value = record;
  showDetailModal.value = true;
};
</script>
