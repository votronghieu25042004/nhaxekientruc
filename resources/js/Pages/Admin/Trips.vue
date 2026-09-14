<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    trips: Array,
    routesList: Array,
    driversList: Array,
    conductorsList: Array,
    busesList: Array,
    flash: Object
})

const showCreateTripModal = ref(false)
const showCounterModal = ref(false)
const showPriceModal = ref(false)
const showAssignModal = ref(false)
const selectedTripForCounter = ref(null)
const selectedTripForPrice = ref(null)
const selectedTripForAssign = ref(null)

const tripForm = useForm({
    route_id: '',
    bus_id: '',
    driver_id: '',
    conductor_id: '',
    departure_time: '',
    arrival_time: '',
    base_price: 150000,
    floor1_price: 150000,
    floor2_price: 140000,
    floor1_seat_type: 'Giường Nằm VIP (Tầng 1)',
    floor2_seat_type: 'Giường Nằm Tiêu Chuẩn (Tầng 2)'
})

const priceForm = useForm({
    floor1_price: '',
    floor2_price: '',
    floor1_seat_type: '',
    floor2_seat_type: '',
    reason: 'Phụ thu dịp Lễ / Điều chỉnh giá ngày thường'
})

const counterForm = useForm({
    trip_id: '',
    customer_name: '',
    customer_phone: '',
    pickup_stop_id: '',
    dropoff_stop_id: '',
    seat_number: '',
    payment_method: 'CASH'
})

const assignForm = useForm({
    driver_id: ''
})

const submitTrip = () => {
    tripForm.post('/admin/trips', {
        onSuccess: () => {
            showCreateTripModal.value = false
            tripForm.reset()
        }
    })
}

const openPriceModal = (trip) => {
    selectedTripForPrice.value = trip
    priceForm.floor1_price = trip.floor1_price || trip.base_price
    priceForm.floor2_price = trip.floor2_price || trip.base_price
    priceForm.floor1_seat_type = trip.floor1_seat_type || 'Giường Nằm VIP (Tầng 1)'
    priceForm.floor2_seat_type = trip.floor2_seat_type || 'Giường Nằm Tiêu Chuẩn (Tầng 2)'
    showPriceModal.value = true
}

const submitPrice = () => {
    if (!selectedTripForPrice.value) return
    priceForm.post(`/admin/trips/${selectedTripForPrice.value.id}/price`, {
        onSuccess: () => {
            showPriceModal.value = false
        }
    })
}

const openAssignModal = (trip) => {
    selectedTripForAssign.value = trip
    assignForm.driver_id = trip.driver_id || ''
    showAssignModal.value = true
}

const submitAssign = () => {
    if (!selectedTripForAssign.value) return
    assignForm.post(`/admin/trips/${selectedTripForAssign.value.id}/assign-driver`, {
        onSuccess: () => {
            showAssignModal.value = false
        }
    })
}

const openCounterForTrip = (trip) => {
    selectedTripForCounter.value = trip
    counterForm.trip_id = trip.id
    const r = props.routesList?.find(x => x.id === trip.route_id)
    if (r && r.stops && r.stops.length >= 2) {
        counterForm.pickup_stop_id = r.stops[0].id
        counterForm.dropoff_stop_id = r.stops[r.stops.length - 1].id
    }
    showCounterModal.value = true
}

const submitCounter = () => {
    counterForm.post('/admin/counter-booking', {
        onSuccess: () => {
            showCounterModal.value = false
            counterForm.reset()
        }
    })
}

const getStatusBadge = (status) => {
    switch (status) {
        case 'WAITING_DRIVER_CONFIRM':
            return { label: '⏳ Chờ TX xác nhận', class: 'bg-blue-600/15 text-amber-700 border border-amber-300 font-bold' }
        case 'DRIVER_CONFIRMED':
            return { label: '✅ TX đã nhận chuyến', class: 'bg-teal-500/15 text-teal-700 border border-teal-300 font-bold' }
        case 'READY':
            return { label: '🟢 Đã duyệt an toàn', class: 'bg-emerald-500/15 text-emerald-700 border border-emerald-300 font-bold' }
        case 'IN_TRANSIT':
            return { label: '⚡ Đang lăn bánh', class: 'bg-blue-500/15 text-blue-700 border border-blue-300 font-black animate-pulse' }
        case 'COMPLETED':
        case 'ARRIVED':
            return { label: '🏁 Đã đến bến', class: 'bg-slate-500/15 text-slate-700 border border-slate-300 font-bold' }
        case 'CLOSED':
            return { label: '🔒 Đã chốt sổ', class: 'bg-purple-500/15 text-purple-700 border border-purple-300 font-bold' }
        case 'OPEN_FOR_SALE':
        case 'SCHEDULED':
        default:
            return { label: '✨ Đang mở bán vé', class: 'bg-emerald-500/15 text-emerald-700 border border-emerald-300 font-bold' }
    }
}

const formatCurrency = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0)
</script>

<template>
    <Head title="Quản Lý Chuyến Xe - BusHub Admin" />

    <div class="min-h-screen bg-white text-slate-900 pb-20">
        <!-- Top App Bar -->
        <AdminHeader title="QUẢN LÝ CHUYẾN XE" subtitle="Điều phối Chuyến xe, Định giá tầng & Sửa giá dịp Lễ Tết" />

        <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-500/20 border border-emerald-500/40 rounded-xl text-emerald-300 text-sm font-semibold flex items-center space-x-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 bg-rose-500/20 border border-rose-500/40 rounded-xl text-rose-300 text-sm font-semibold flex items-center space-x-2">
                <span>⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Control Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-100 p-5 rounded-2xl border border-slate-300 shadow-xl">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Danh Sách Chuyến Xe BusHub</h2>
                    <p class="text-xs text-slate-500">Mỗi chuyến xe có cấu hình giá riêng theo từng tầng (nằm/ngồi), dễ dàng tăng giá dịp lễ hoặc giảm giá ngày thường</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button 
                        @click="showCreateTripModal = true"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center space-x-2">
                        <span class="text-lg">➕</span>
                        <span>TẠO CHUYẾN XE MỚI</span>
                    </button>
                </div>
            </div>

            <!-- Trips Table -->
            <div class="bg-slate-100 rounded-2xl border border-slate-300 shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-white/80 text-slate-500 uppercase font-semibold border-b border-slate-300">
                            <tr>
                                <th class="p-4">Mã Chuyến</th>
                                <th class="p-4">Tuyến Đường</th>
                                <th class="p-4">Giá Từng Tầng (Nằm/Ngồi)</th>
                                <th class="p-4">Xe / Tài Xế / Lơ Xe</th>
                                <th class="p-4">Khởi Hành</th>
                                <th class="p-4">Trạng Thái</th>
                                <th class="p-4 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/60">
                            <tr v-for="t in trips" :key="t.id" class="hover:bg-slate-200/30 transition">
                                <td class="p-4 font-mono font-bold text-blue-500">
                                    {{ t.trip_code }}
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 text-sm">{{ t.route_name }}</div>
                                    <div class="text-slate-500">{{ t.total_passengers }} / {{ t.total_seats }} vé</div>
                                </td>
                                <td class="p-4">
                                    <div class="text-blue-500 font-bold">T1 ({{ t.floor1_seat_type }}): {{ formatCurrency(t.floor1_price) }}</div>
                                    <div class="text-indigo-400 font-bold mt-0.5">T2 ({{ t.floor2_seat_type }}): {{ formatCurrency(t.floor2_price) }}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                        <span>🚍 {{ t.bus_plate }}</span>
                                    </div>
                                    <div class="text-slate-700 text-xs mt-1 flex items-center justify-between">
                                        <span>👨‍✈️ TX: <strong>{{ t.driver_name || 'Chưa gán' }}</strong></span>
                                        <button 
                                            @click="openAssignModal(t)"
                                            class="ml-1.5 px-2 py-0.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-md font-bold text-[10px] transition">
                                            Đổi TX
                                        </button>
                                    </div>
                                    <div class="text-slate-500 text-[11px] mt-0.5">🎫 Lơ xe: {{ t.conductor_name || 'Tự động' }}</div>
                                </td>
                                <td class="p-4 font-semibold text-slate-800">
                                    {{ t.departure_time }}
                                </td>
                                <td class="p-4">
                                    <span :class="getStatusBadge(t.status).class" class="px-2.5 py-1 rounded-lg text-xs inline-flex items-center">
                                        {{ getStatusBadge(t.status).label }}
                                    </span>
                                </td>
                                <td class="p-4 text-right space-x-1.5">
                                    <button 
                                        @click="openPriceModal(t)"
                                        class="px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-slate-900 rounded-lg font-bold text-xs shadow transition">
                                        ✏️ Sửa Giá Dịp Lễ
                                    </button>
                                    <button 
                                        @click="openCounterForTrip(t)"
                                        class="px-2.5 py-1.5 bg-blue-600 hover:bg-blue-600 text-slate-900 rounded-lg font-bold text-xs shadow transition">
                                        + Bán Quầy
                                    </button>
                                    <Link 
                                        :href="`/trips/${t.id}`"
                                        class="px-2.5 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-900 rounded-lg font-bold text-xs transition">
                                        Sơ đồ ghế
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL 1: TẠO CHUYẾN XE MỚI VỚI GIÁ TỪNG TẦNG -->
        <div v-if="showCreateTripModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-100 border border-slate-300 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-300">
                    <h3 class="text-lg font-bold text-slate-900">🚍 Tạo Chuyến Xe Mới & Định Giá Từng Tầng</h3>
                    <button @click="showCreateTripModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitTrip" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Chọn Tuyến Đường</label>
                        <select v-model="tripForm.route_id" required class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                            <option value="">-- Chọn tuyến chạy --</option>
                            <option v-for="r in routesList" :key="r.id" :value="r.id">
                                {{ r.name }} ({{ r.distance_km }} km)
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Gán Xe Chạy</label>
                            <select v-model="tripForm.bus_id" required class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                                <option value="">-- Chọn xe --</option>
                                <option v-for="b in busesList" :key="b.id" :value="b.id">
                                    {{ b.license_plate }} - {{ b.bus_type }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Gán Tài Xế Lái (8 Tài xế có sẵn)</label>
                            <select v-model="tripForm.driver_id" required class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                                <option value="">-- Chọn tài xế --</option>
                                <option v-for="d in driversList" :key="d.id" :value="d.id">
                                    {{ d.name }} ({{ d.phone }} - {{ d.license_class }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Gán Lơ Xe Phụ Trách (10 Lơ xe có sẵn)</label>
                            <select v-model="tripForm.conductor_id" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                                <option value="">-- Chọn lơ xe --</option>
                                <option v-for="c in conductorsList" :key="c.id" :value="c.id">
                                    {{ c.name }} ({{ c.phone }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Giá Vé Cơ Sở Chung (VNĐ)</label>
                            <input v-model="tripForm.base_price" required type="number" min="10000" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                    </div>

                    <!-- Config Floor Prices & Types -->
                    <div class="p-3 bg-white/80 rounded-xl border border-slate-300 space-y-3">
                        <div class="font-bold text-blue-500">Thiết Lập Giá & Loại Ghế Cho Từng Tầng:</div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tên loại ghế Tầng 1</label>
                                <input v-model="tripForm.floor1_seat_type" placeholder="Giường Nằm VIP (Tầng 1)" class="w-full bg-slate-100 border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Giá vé Tầng 1 (VNĐ)</label>
                                <input v-model="tripForm.floor1_price" required type="number" class="w-full bg-slate-100 border border-slate-600 rounded-xl px-3 py-2 text-slate-900 font-bold text-blue-500" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Tên loại ghế Tầng 2</label>
                                <input v-model="tripForm.floor2_seat_type" placeholder="Giường Nằm Tiêu Chuẩn (Tầng 2)" class="w-full bg-slate-100 border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Giá vé Tầng 2 (VNĐ)</label>
                                <input v-model="tripForm.floor2_price" required type="number" class="w-full bg-slate-100 border border-slate-600 rounded-xl px-3 py-2 text-slate-900 font-bold text-indigo-400" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Thời Gian Khởi Hành</label>
                            <input v-model="tripForm.departure_time" required type="datetime-local" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Thời Gian Dự Kiến Đến</label>
                            <input v-model="tripForm.arrival_time" required type="datetime-local" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex justify-end space-x-3">
                        <button type="button" @click="showCreateTripModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="tripForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg transition">
                            {{ tripForm.processing ? 'Đang tạo...' : 'Xác Nhận Tạo Chuyến' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: ĐIỀU CHỈNH GIÁ VÉ DỊP LỄ / ĐỔI GIÁ -->
        <div v-if="showPriceModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-100 border border-slate-300 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-300">
                    <h3 class="text-lg font-bold text-slate-900">✏️ Sửa Giá Vé Chuyến {{ selectedTripForPrice?.trip_code }}</h3>
                    <button @click="showPriceModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <div class="text-xs text-slate-700">
                    <div>Tuyến: <strong class="text-slate-900">{{ selectedTripForPrice?.route_name }}</strong></div>
                    <div>Xe: <strong class="text-slate-900">{{ selectedTripForPrice?.bus_plate }}</strong></div>
                </div>

                <form @submit.prevent="submitPrice" class="space-y-4 text-xs">
                    <div class="p-3 bg-white/80 rounded-xl border border-slate-300 space-y-3">
                        <div>
                            <label class="block font-semibold text-blue-500 mb-1">Giá Vé Tầng 1 (Dưới / VIP) - VNĐ</label>
                            <input v-model="priceForm.floor1_price" required type="number" min="10000" class="w-full bg-slate-100 border border-slate-600 rounded-xl px-3 py-2 text-slate-900 font-bold text-sm" />
                        </div>
                        <div>
                            <label class="block font-semibold text-indigo-400 mb-1">Giá Vé Tầng 2 (Trên) - VNĐ</label>
                            <input v-model="priceForm.floor2_price" required type="number" min="10000" class="w-full bg-slate-100 border border-slate-600 rounded-xl px-3 py-2 text-slate-900 font-bold text-sm" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Lý do điều chỉnh giá</label>
                        <input v-model="priceForm.reason" type="text" placeholder="Phụ thu Lễ 30/4, Tết Dương Lịch hoặc hết lễ giảm về giá chuẩn..." class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex justify-end space-x-3">
                        <button type="button" @click="showPriceModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="priceForm.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-slate-900 font-bold rounded-xl shadow-lg transition">
                            {{ priceForm.processing ? 'Đang lưu...' : 'Lưu Thay Đổi Giá' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 3: BÁN VÉ TẠI QUẦY (THU 100%) -->
        <div v-if="showCounterModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-100 border border-slate-300 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-300">
                    <h3 class="text-lg font-bold text-slate-900">🎟️ Bán Vé Tại Quầy (Chuyến {{ selectedTripForCounter?.trip_code }})</h3>
                    <button @click="showCounterModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitCounter" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Họ Tên Khách Hàng</label>
                            <input v-model="counterForm.customer_name" required type="text" placeholder="Nguyễn Văn A" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Số Điện Thoại</label>
                            <input v-model="counterForm.customer_phone" required type="text" placeholder="090xxxxxxx" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Chọn Ghế</label>
                            <select v-model="counterForm.seat_number" required class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                                <option value="">-- Chọn ghế --</option>
                                <option v-for="i in 18" :key="'A' + i" :value="'A' + (i < 10 ? '0' + i : i)">
                                    Ghế A{{ i < 10 ? '0' + i : i }} (Tầng 1 - {{ formatCurrency(selectedTripForCounter?.floor1_price) }})
                                </option>
                                <option v-for="i in 18" :key="'B' + i" :value="'B' + (i < 10 ? '0' + i : i)">
                                    Ghế B{{ i < 10 ? '0' + i : i }} (Tầng 2 - {{ formatCurrency(selectedTripForCounter?.floor2_price) }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Hình Thức Thu Tiền (Thu đủ 100%)</label>
                            <select v-model="counterForm.payment_method" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                                <option value="CASH">Tiền mặt tại quầy (Đã thu)</option>
                                <option value="TRANSFER">Chuyển khoản QR ngân hàng (Đã thu)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex justify-end space-x-3">
                        <button type="button" @click="showCounterModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="counterForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg transition">
                            {{ counterForm.processing ? 'Đang xuất vé...' : 'Xuất Vé & Thu Đủ Tiền' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- MODAL 4: PHÂN CÔNG / ĐỔI TÀI XẾ CHO CHUYẾN XE (KÈM CHỐNG TRÙNG GIỜ) -->
        <div v-if="showAssignModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
                            <span>👨‍✈️ Điều Phối / Phân Công Bác Tài</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Chuyến: <span class="font-bold text-blue-600">{{ selectedTripForAssign?.trip_code }}</span> ({{ selectedTripForAssign?.route_name }})</p>
                    </div>
                    <button @click="showAssignModal = false" class="text-slate-500 hover:text-slate-700 text-2xl leading-none">&times;</button>
                </div>

                <div class="p-3 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-900 flex items-start space-x-2">
                    <span class="text-base">🛡️</span>
                    <div>
                        <strong class="font-bold">Hệ thống chống trùng lịch:</strong>
                        <p class="mt-0.5 text-amber-800 leading-relaxed">Hệ thống sẽ tự động khóa và từ chối nếu Bác tài đã có chuyến chạy trong cùng khung giờ xuất bến hoặc chưa nghỉ ngơi đủ 1 tiếng.</p>
                    </div>
                </div>

                <form @submit.prevent="submitAssign" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">Chọn Bác Tài (8 Tài xế BusHub Terminal)</label>
                        <select v-model="assignForm.driver_id" required class="w-full bg-slate-50 border border-slate-300 rounded-2xl px-3.5 py-2.5 text-slate-900 font-semibold focus:bg-white focus:border-blue-600 outline-none transition">
                            <option value="">-- Chọn tài xế điều phối --</option>
                            <option v-for="d in driversList" :key="d.id" :value="d.id">
                                {{ d.name }} • SĐT: {{ d.phone }} • Hạng: {{ d.license_class }} ({{ d.years_experience || 5 }} năm KN)
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
                        <button type="button" @click="showAssignModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition">
                            Hủy bỏ
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-600 text-white rounded-xl font-black shadow-lg shadow-blue-600/20 transition flex items-center space-x-1.5">
                            <span>⚡ Gửi Lệnh Điều Phối</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>
