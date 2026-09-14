<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
import { Head, Link } from '@inertiajs/vue3'

const props = defineProps({
    stats: Object,
    recentTrips: Array,
    recentLogs: Array,
    user: Object
})

const formatCurrency = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0)
</script>

<template>
    <Head title="Trung Tâm Điều Hành Tổng - BusHub Admin" />

    <div class="min-h-screen bg-white text-slate-900 pb-20">
        <!-- Top App Bar -->
        <AdminHeader title="BusHub QUẢN TRỊ TỔNG" subtitle="Điều phối toàn bộ nhà xe: Tuyến, Xe, Tài xế, Lơ xe, Doanh thu" />

        <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
            <!-- 4 Quick Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-slate-100 border border-slate-300 p-5 rounded-2xl shadow-xl">
                    <div class="text-xs font-semibold text-slate-500 uppercase">Tổng Đội Xe BusHub</div>
                    <div class="text-2xl font-black text-slate-900 mt-1">{{ stats?.total_buses || 0 }} xe</div>
                    <div class="text-[11px] text-emerald-400 mt-2 flex items-center space-x-1">
                        <span>🟢 Đang chạy: {{ stats?.running_buses || 0 }}</span>
                        <span class="text-slate-500">•</span>
                        <span class="text-yellow-400">Bảo dưỡng: {{ stats?.maintenance_buses || 0 }}</span>
                    </div>
                </div>

                <div class="bg-slate-100 border border-slate-300 p-5 rounded-2xl shadow-xl">
                    <div class="text-xs font-semibold text-slate-500 uppercase">Chuyến Xe Hôm Nay</div>
                    <div class="text-2xl font-black text-slate-900 mt-1">{{ stats?.today_trips || 0 }} chuyến</div>
                    <div class="text-[11px] text-slate-500 mt-2">
                        Tổng khách đi hôm nay: <strong class="text-blue-500">{{ stats?.today_passengers || 0 }} khách</strong>
                    </div>
                </div>

                <div class="bg-slate-100 border border-slate-300 p-5 rounded-2xl shadow-xl">
                    <div class="text-xs font-semibold text-slate-500 uppercase">Doanh Thu Hôm Nay</div>
                    <div class="text-2xl font-black text-emerald-400 mt-1">{{ formatCurrency(stats?.today_revenue) }}</div>
                    <div class="text-[11px] text-slate-500 mt-2">
                        Đã thu: <span class="text-slate-900 font-bold">{{ formatCurrency(stats?.today_collected) }}</span>
                    </div>
                </div>

                <div class="bg-slate-100 border border-slate-300 p-5 rounded-2xl shadow-xl">
                    <div class="text-xs font-semibold text-slate-500 uppercase">Chờ Thu (Cọc / Trên Xe)</div>
                    <div class="text-2xl font-black text-amber-400 mt-1">{{ formatCurrency(stats?.today_pending) }}</div>
                    <div class="text-[11px] text-slate-500 mt-2">
                        Lơ xe thu trực tiếp khi khách lên
                    </div>
                </div>
            </div>

            <!-- Quick Management Shortcuts -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <Link href="/admin/trips" class="p-4 bg-gradient-to-r bg-blue-600 hover:from-blue-600 hover:to-amber-500 text-slate-900 rounded-2xl font-bold shadow-lg flex items-center space-x-3 transition">
                    <span class="text-2xl">➕</span>
                    <div>
                        <div class="text-sm">TẠO CHUYẾN XE</div>
                        <div class="text-[11px] opacity-80 font-normal">Điều phối tuyến, xe, tài xế</div>
                    </div>
                </Link>

                <Link href="/admin/routes" class="p-4 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-900 rounded-2xl font-bold shadow-lg flex items-center space-x-3 transition">
                    <span class="text-2xl">🛣️</span>
                    <div>
                        <div class="text-sm">TUYẾN ĐƯỜNG</div>
                        <div class="text-[11px] text-slate-500 font-normal">Thiết lập các trạm dừng</div>
                    </div>
                </Link>

                <Link href="/admin/buses" class="p-4 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-900 rounded-2xl font-bold shadow-lg flex items-center space-x-3 transition">
                    <span class="text-2xl">🚍</span>
                    <div>
                        <div class="text-sm">QUẢN LÝ XE</div>
                        <div class="text-[11px] text-slate-500 font-normal">Bảo dưỡng, số ghế, tiện nghi</div>
                    </div>
                </Link>

                <Link href="/admin/drivers" class="p-4 bg-slate-100 hover:bg-slate-200 border border-slate-300 text-slate-900 rounded-2xl font-bold shadow-lg flex items-center space-x-3 transition">
                    <span class="text-2xl">👨‍✈️</span>
                    <div>
                        <div class="text-sm">HỒ SƠ TÀI XẾ</div>
                        <div class="text-[11px] text-slate-500 font-normal">Bằng lái & Đánh giá sao</div>
                    </div>
                </Link>
            </div>

            <!-- Recent Trips & Audit Logs Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Recent Trips Table (8 cols) -->
                <div class="lg:col-span-8 bg-slate-100 rounded-2xl border border-slate-300 p-5 shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900">Chuyến Xe Đang Vận Hành</h2>
                        <Link href="/admin/trips" class="text-xs text-blue-500 hover:underline font-semibold">Xem tất cả »</Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-white/60 text-slate-500 uppercase font-semibold border-b border-slate-300">
                                <tr>
                                    <th class="p-3">Mã Chuyến</th>
                                    <th class="p-3">Tuyến / Xe</th>
                                    <th class="p-3">Giờ Chạy</th>
                                    <th class="p-3">Khách / Doanh Thu</th>
                                    <th class="p-3">Trạng Thái</th>
                                    <th class="p-3 text-right">Chi Tiết</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/60">
                                <tr v-for="t in recentTrips" :key="t.id" class="hover:bg-slate-200/30 transition">
                                    <td class="p-3 font-mono font-bold text-blue-500">{{ t.trip_code }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ t.route_name }}</div>
                                        <div class="text-slate-500">{{ t.bus_plate }} ({{ t.driver_name }})</div>
                                    </td>
                                    <td class="p-3 text-slate-700">{{ t.departure_time }}</td>
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ t.total_passengers }} khách</div>
                                        <div class="text-emerald-400 font-semibold">{{ formatCurrency(t.total_revenue) }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase"
                                            :class="{
                                                'bg-yellow-500/20 text-yellow-400': t.status === 'OPEN_FOR_SALE' || t.status === 'SCHEDULED',
                                                'bg-emerald-500/20 text-emerald-400': t.status === 'IN_TRANSIT',
                                                'bg-blue-500/20 text-blue-400': t.status === 'ARRIVED',
                                                'bg-purple-500/20 text-purple-400': t.status === 'CLOSED',
                                            }">
                                            {{ t.status }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right">
                                        <Link :href="`/trips/${t.id}`" class="text-blue-500 hover:underline font-bold">
                                            Sơ đồ ghế
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Audit Logs (4 cols) -->
                <div class="lg:col-span-4 bg-slate-100 rounded-2xl border border-slate-300 p-5 shadow-xl space-y-4">
                    <h2 class="text-base font-bold text-slate-900">Nhật Ký Hoạt Động (Audit Log)</h2>
                    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                        <div v-for="log in recentLogs" :key="log.id" class="p-3 bg-white/70 border border-slate-300/80 rounded-xl text-xs space-y-1">
                            <div class="flex items-center justify-between text-slate-500">
                                <strong class="text-blue-500">{{ log.action }}</strong>
                                <span class="text-[10px]">{{ log.created_at?.slice(11, 16) }}</span>
                            </div>
                            <p class="text-slate-800">{{ log.description }}</p>
                            <div class="text-[10px] text-slate-500">Bởi: {{ log.user_name || 'Hệ thống' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
