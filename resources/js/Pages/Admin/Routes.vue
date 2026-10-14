<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    routes: Array,
    buses: Array,
    drivers: Array,
    conductors: Array,
    flash: Object
})

const showAddRouteModal = ref(false)
const showEditRouteModal = ref(false)
const showStopsModal = ref(false)
const showAddDailyTripModal = ref(false)
const showEditTripTimeModal = ref(false)

const selectedRoute = ref(null)
const selectedTrip = ref(null)
const editingStops = ref([])

// Form Thêm Tuyến Mới
const addRouteForm = useForm({
    name: '',
    origin: '',
    destination: '',
    distance_km: '',
    estimated_hours: '',
})

// Form Sửa Tuyến Hiện Có
const editRouteForm = useForm({
    name: '',
    origin: '',
    destination: '',
    distance_km: '',
    estimated_hours: '',
})

// Form Sửa Giờ Chạy
const editTripTimeForm = useForm({
    departure_time: '',
    base_price: 150000,
})

// Form Thêm Khung Giờ Chạy Hằng Ngày
const addDailyTripForm = useForm({
    departure_time: '06:00',
    base_price: 150000,
    bus_id: '',
    driver_id: '',
    conductor_id: '',
})

// --- MODAL SỬA TUYẾN ĐƯỜNG ---
const openEditRouteModal = (route) => {
    selectedRoute.value = route
    editRouteForm.name = route.name
    editRouteForm.origin = route.origin
    editRouteForm.destination = route.destination
    editRouteForm.distance_km = route.distance_km
    editRouteForm.estimated_hours = route.estimated_hours
    showEditRouteModal.value = true
}

const submitEditRoute = () => {
    if (!selectedRoute.value) return
    editRouteForm.post(`/admin/routes/${selectedRoute.value.id}/update`, {
        onSuccess: () => {
            showEditRouteModal.value = false
        }
    })
}

const confirmDeleteRoute = (route) => {
    if (confirm(`Bạn có chắc chắn muốn xóa tuyến [${route.name}]? Tất cả các chuyến xe chưa hoàn thành của tuyến này sẽ bị hủy bỏ!`)) {
        router.delete(`/admin/routes/${route.id}`)
    }
}

// --- MODAL TRẠM DỪNG & ETA ---
const openStopsModal = (route) => {
    selectedRoute.value = route
    if (route.stops && route.stops.length > 0) {
        editingStops.value = route.stops.map(s => ({
            id: s.id,
            stop_name: s.stop_name,
            distance_from_start_km: s.distance_from_start_km,
            estimated_minutes_from_start: s.estimated_minutes_from_start,
        }))
    } else {
        editingStops.value = [
            { stop_name: route.origin, distance_from_start_km: 0, estimated_minutes_from_start: 0 },
            { stop_name: 'Trạm Nghỉ Giữa Chặng', distance_from_start_km: Math.round(route.distance_km / 2), estimated_minutes_from_start: Math.round((route.distance_km / 2 / 55) * 60) },
            { stop_name: route.destination, distance_from_start_km: route.distance_km, estimated_minutes_from_start: Math.round((route.distance_km / 55) * 60) }
        ]
    }
    showStopsModal.value = true
}

const addStopRow = () => {
    const prevDist = editingStops.value.length > 0 ? editingStops.value[editingStops.value.length - 1].distance_from_start_km : 0
    const nextDist = prevDist + 40
    editingStops.value.push({
        stop_name: '',
        distance_from_start_km: nextDist,
        estimated_minutes_from_start: Math.round((nextDist / 55) * 60),
    })
}

const removeStopRow = (index) => {
    if (editingStops.value.length <= 2) {
        alert('Tuyến đường phải có tối thiểu 2 điểm: Điểm xuất phát và Điểm đích!')
        return
    }
    editingStops.value.splice(index, 1)
}

const updateDistance = (index) => {
    const stop = editingStops.value[index]
    const dist = parseFloat(stop.distance_from_start_km) || 0
    stop.estimated_minutes_from_start = Math.round((dist / 55) * 60)
}

const saveRouteStops = () => {
    if (!selectedRoute.value) return
    router.post(`/admin/routes/${selectedRoute.value.id}/stops`, {
        stops: editingStops.value,
    }, {
        onSuccess: () => {
            showStopsModal.value = false
        }
    })
}

// --- THÊM TUYẾN MỚI ---
const submitAddRoute = () => {
    addRouteForm.post('/admin/routes', {
        onSuccess: () => {
            showAddRouteModal.value = false
            addRouteForm.reset()
        }
    })
}

// --- CHUYẾN XE ĐI HẰNG NGÀY (DAILY TRIPS) ---
const openAddDailyTripModal = (route) => {
    selectedRoute.value = route
    addDailyTripForm.departure_time = '06:00'
    addDailyTripForm.base_price = 150000
    addDailyTripForm.bus_id = props.buses?.[0]?.id || ''
    addDailyTripForm.driver_id = props.drivers?.[0]?.id || ''
    addDailyTripForm.conductor_id = props.conductors?.[0]?.id || ''
    showAddDailyTripModal.value = true
}

const submitAddDailyTrip = () => {
    if (!selectedRoute.value) return
    addDailyTripForm.post(`/admin/routes/${selectedRoute.value.id}/daily-trip`, {
        onSuccess: () => {
            showAddDailyTripModal.value = false
        }
    })
}

const openEditTripTimeModal = (route, trip) => {
    selectedRoute.value = route
    selectedTrip.value = trip
    editTripTimeForm.departure_time = trip.departure_time
    editTripTimeForm.base_price = trip.base_price
    showEditTripTimeModal.value = true
}

const submitEditTripTime = () => {
    if (!selectedTrip.value) return
    editTripTimeForm.post(`/admin/routes/daily-trip/${selectedTrip.value.id}/update-time`, {
        onSuccess: () => {
            showEditTripTimeModal.value = false
        }
    })
}

const confirmDeleteDailyTrip = (trip) => {
    if (confirm(`Bạn có chắc chắn muốn hủy chuyến xe lúc [${trip.departure_time}] (${trip.trip_code})? Thao tác này sẽ đồng bộ hủy khung giờ này trên toàn hệ thống!`)) {
        router.delete(`/admin/routes/daily-trip/${trip.id}`)
    }
}

const formatMinutesToHours = (mins) => {
    if (!mins || mins === 0) return '0h:00m'
    const h = Math.floor(mins / 60)
    const m = mins % 60
    return `${h}h:${m < 10 ? '0' + m : m}m`
}

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(val || 0) + 'đ'
</script>

<template>
    <Head title="Quản Lý Tuyến Đường Cố Định - nhaxekientruc Admin" />

    <div class="min-h-screen bg-slate-50 text-slate-800 pb-20">
        <!-- Top App Bar -->
        <AdminHeader 
            title="QUẢN LÝ TUYẾN ĐƯỜNG CỐ ĐỊNH & CHUYẾN XE HẰNG NGÀY" 
            subtitle="Thiết lập lộ trình liên tỉnh, khung giờ xuất bến hằng ngày, khoảng cách km, danh sách trạm dừng và tính ETA đồng bộ toàn hệ thống" 
        />

        <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center space-x-2 shadow-xs">
                <span class="text-base">✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs font-bold flex items-center space-x-2 shadow-xs">
                <span class="text-base">⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- Control Bar (Thống Kê & Nút Thêm Tuyến Mới) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wider">
                            ĐỒNG BỘ 4 VAI TRÒ
                        </span>
                        <h2 class="text-base font-black text-slate-900">Danh Sách Tuyến Đường Cố Định ({{ routes?.length || 0 }} Tuyến)</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Mọi thay đổi về giờ giấc, trạm dừng, quãng đường sẽ được <b>tự động đồng bộ ngay lập tức</b> sang Khách hàng, Bác tài, Lơ xe và Admin.
                    </p>
                </div>

                <button 
                    @click="showAddRouteModal = true"
                    class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl text-xs transition flex items-center gap-2 cursor-pointer whitespace-nowrap shadow-sm shadow-red-500/20 active:scale-95"
                >
                    <span>➕</span>
                    <span>THÊM TUYẾN CỐ ĐỊNH MỚI</span>
                </button>
            </div>

            <!-- Routes Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div 
                    v-for="r in routes" 
                    :key="r.id" 
                    class="bg-white border border-slate-200 hover:border-slate-300 rounded-3xl p-6 transition flex flex-col justify-between space-y-5 shadow-2xs hover:shadow-md"
                >
                    <!-- Header Card Tuyến -->
                    <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200 uppercase tracking-wider">
                                    MÃ TUYẾN #{{ r.id }}
                                </span>
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                    {{ r.total_daily_trips || 0 }} chuyến/ngày
                                </span>
                            </div>
                            <h3 class="text-base font-black text-slate-900 mt-1.5 flex items-center gap-2">
                                <span>{{ r.name }}</span>
                            </h3>
                            <div class="text-xs text-slate-600 mt-1 flex items-center gap-2">
                                <span class="font-bold text-red-600">{{ r.origin }}</span>
                                <span>➔</span>
                                <span class="font-bold text-blue-600">{{ r.destination }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-1.5">
                            <span class="px-3 py-1 bg-slate-100 text-slate-800 rounded-xl text-xs font-mono font-bold border border-slate-200">
                                {{ r.distance_km }} km • ~{{ r.estimated_hours }}h
                            </span>
                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="openEditRouteModal(r)"
                                    class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold rounded-lg border border-amber-200 text-[11px] transition cursor-pointer flex items-center gap-1"
                                    title="Sửa tên, quãng đường, thời gian chạy"
                                >
                                    <span>✏️</span>
                                    <span>Sửa Tuyến</span>
                                </button>
                                <button
                                    type="button"
                                    @click="confirmDeleteRoute(r)"
                                    class="p-1 rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition cursor-pointer"
                                    title="Xóa tuyến này"
                                >
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== KHUNG: CHUYẾN XE ĐI HẰNG NGÀY (DAILY OPERATING TRIPS) ==================== -->
                    <div class="p-4 rounded-2xl bg-slate-50/80 border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm">🕒</span>
                                <span class="font-black text-xs text-slate-900 uppercase tracking-wide">
                                    Chuyến Xe Đi Hằng Ngày ({{ r.daily_trips?.length || 0 }} khung giờ)
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="openAddDailyTripModal(r)"
                                class="px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg text-[11px] transition cursor-pointer flex items-center gap-1 shadow-2xs"
                            >
                                <span>➕</span>
                                <span>Thêm Giờ Chạy</span>
                            </button>
                        </div>

                        <!-- Danh sách các chuyến chạy hàng ngày -->
                        <div v-if="r.daily_trips && r.daily_trips.length > 0" class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            <div 
                                v-for="dt in r.daily_trips" 
                                :key="dt.id"
                                class="p-2.5 rounded-xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between text-xs hover:border-slate-300 transition"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-12 text-center py-1 bg-red-50 text-red-700 font-mono font-black text-xs rounded-lg border border-red-200">
                                        {{ dt.departure_time }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900 font-mono">➔ {{ dt.arrival_time }}</span>
                                            <span class="font-mono text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.2 rounded border border-slate-200">
                                                {{ dt.bus_plate }}
                                            </span>
                                            <span class="text-[10px] text-emerald-700 font-mono font-bold">
                                                {{ formatPrice(dt.base_price) }}
                                            </span>
                                        </div>
                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                            Bác tài: <b>{{ dt.driver_name }}</b> • Lơ xe: <b>{{ dt.conductor_name }}</b>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button
                                        type="button"
                                        @click="openEditTripTimeModal(r, dt)"
                                        class="px-2 py-1 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 rounded-lg text-[11px] font-bold border border-slate-200 transition cursor-pointer"
                                        title="Sửa giờ giấc khởi hành"
                                    >
                                        ✏️ Sửa Giờ
                                    </button>
                                    <button
                                        type="button"
                                        @click="confirmDeleteDailyTrip(dt)"
                                        class="p-1 rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition cursor-pointer"
                                        title="Xóa khung giờ chạy này"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-4 rounded-xl bg-white border border-dashed border-slate-300 text-center text-xs text-slate-400 space-y-1">
                            <p>Chưa có chuyến xe chạy hằng ngày nào cho tuyến này.</p>
                            <button 
                                type="button" 
                                @click="openAddDailyTripModal(r)" 
                                class="text-red-600 font-bold hover:underline cursor-pointer"
                            >
                                + Thêm khung giờ chạy đầu tiên ngay
                            </button>
                        </div>
                    </div>

                    <!-- Ordered Stops List with ETA -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-600 uppercase text-[11px] flex items-center gap-1.5">
                                <span>📍</span>
                                <span>Trạm Dừng & ETA Bác Tài ({{ r.stops?.length || 0 }} trạm):</span>
                            </span>
                            <span class="text-[11px] text-blue-600 font-bold cursor-pointer hover:underline" @click="openStopsModal(r)">
                                ✏️ Thêm / Sửa trạm
                            </span>
                        </div>

                        <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                            <div 
                                v-for="(s, idx) in r.stops" 
                                :key="s.id" 
                                class="flex items-center justify-between bg-slate-50/70 hover:bg-slate-100 p-2.5 rounded-xl text-xs border border-slate-100 transition"
                            >
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-5 h-5 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-[10px]">
                                        {{ idx + 1 }}
                                    </span>
                                    <div>
                                        <span class="font-bold text-slate-900">{{ s.stop_name }}</span>
                                        <span v-if="idx === 0" class="ml-1 text-[10px] bg-emerald-50 text-emerald-700 px-1.5 py-0.2 rounded font-semibold">Bến đi</span>
                                        <span v-else-if="idx === (r.stops.length - 1)" class="ml-1 text-[10px] bg-red-50 text-red-700 px-1.5 py-0.2 rounded font-semibold">Bến đến</span>
                                    </div>
                                </div>

                                <div class="text-right flex items-center gap-3 text-slate-600">
                                    <span class="font-mono">{{ s.distance_from_start_km }} km</span>
                                    <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-mono font-bold text-[11px] border border-blue-100">
                                        ETA: {{ formatMinutesToHours(s.estimated_minutes_from_start) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <button
                            @click="openStopsModal(r)"
                            class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold rounded-xl border border-blue-200 transition flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>📍</span>
                            <span>Cài Đặt Trạm Dừng & Đo Khoảng Cách</span>
                        </button>

                        <Link :href="`/trips?origin=${r.origin}&destination=${r.destination}`" class="text-slate-600 hover:text-red-600 font-bold flex items-center gap-1">
                            <span>Xem Khách Đặt Vé »</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MODAL 1: SỬA TUYẾN ĐƯỜNG CỐ ĐỊNH ==================== -->
        <div v-if="showEditRouteModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 max-w-lg w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-900">✏️ Chỉnh Sửa Tuyến Đường Cố Định</h3>
                        <p class="text-xs text-slate-500">Mã Tuyến: <strong class="text-slate-800">#{{ selectedRoute?.id }}</strong></p>
                    </div>
                    <button @click="showEditRouteModal = false" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">&times;</button>
                </div>

                <form @submit.prevent="submitEditRoute" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tên Tuyến Đường</label>
                        <input v-model="editRouteForm.name" required type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Điểm Xuất Phát (Bến A)</label>
                            <input v-model="editRouteForm.origin" required type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Điểm Đến (Bến B)</label>
                            <input v-model="editRouteForm.destination" required type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Quãng Đường (km)</label>
                            <input v-model.number="editRouteForm.distance_km" required type="number" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-slate-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Thời Gian Chạy Dự Kiến (giờ)</label>
                            <input v-model.number="editRouteForm.estimated_hours" required type="number" step="0.5" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-slate-900" />
                        </div>
                    </div>

                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-900 text-[11px] leading-relaxed">
                        💡 <b>Tự động đồng bộ:</b> Khi bạn thay đổi thời gian chạy dự kiến, hệ thống sẽ tự động tính lại giờ cập bến (Arrival Time) cho tất cả các chuyến xe của tuyến này và đồng bộ trên màn hình Bác tài, Lơ xe, Khách hàng!
                    </div>

                    <div class="pt-2 flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl cursor-pointer shadow-sm">
                            LƯU THAY ĐỔI & ĐỒNG BỘ TOÀN HỆ THỐNG
                        </button>
                        <button type="button" @click="showEditRouteModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL 2: SỬA GIỜ GIẤC CHUYẾN CHẠY ==================== -->
        <div v-if="showEditTripTimeModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-900">🕒 Sửa Giờ Khởi Hành Chuyến Xe</h3>
                        <p class="text-xs text-slate-500">Mã chuyến: <strong class="font-mono text-red-600">{{ selectedTrip?.trip_code }}</strong></p>
                    </div>
                    <button @click="showEditTripTimeModal = false" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">&times;</button>
                </div>

                <form @submit.prevent="submitEditTripTime" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Giờ Khởi Hành Mới (HH:mm)</label>
                        <input 
                            v-model="editTripTimeForm.departure_time" 
                            type="time" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-base font-mono font-black text-slate-900 focus:bg-white focus:outline-red-500 text-center" 
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Giá Vé Tiêu Chuẩn (VNĐ)</label>
                        <input 
                            v-model.number="editTripTimeForm.base_price" 
                            type="number" 
                            step="5000" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono font-bold text-slate-900" 
                        />
                    </div>

                    <div class="p-3 bg-blue-50 rounded-xl border border-blue-200 text-blue-900 text-[11px] leading-relaxed">
                        🌐 <b>Đồng bộ tức thì:</b> Sau khi cập nhật giờ khởi hành mới, giờ đến dự kiến sẽ tự động nhảy theo thời lượng tuyến. Bác tài, Lơ xe và Khách hàng sẽ thấy ngay khung giờ mới.
                    </div>

                    <div class="pt-2 flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl cursor-pointer shadow-sm">
                            XÁC NHẬN ĐỔI GIỜ CHẠY
                        </button>
                        <button type="button" @click="showEditTripTimeModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                            Đóng
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL 3: THÊM KHUNG GIỜ CHẠY HẰNG NGÀY MỚI ==================== -->
        <div v-if="showAddDailyTripModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-black text-slate-900">➕ Thêm Khung Giờ Chạy Hằng Ngày</h3>
                        <p class="text-xs text-slate-500">Tuyến: <strong class="text-slate-800">{{ selectedRoute?.name }}</strong></p>
                    </div>
                    <button @click="showAddDailyTripModal = false" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">&times;</button>
                </div>

                <form @submit.prevent="submitAddDailyTrip" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Giờ Xuất Bến (HH:mm)</label>
                        <input 
                            v-model="addDailyTripForm.departure_time" 
                            type="time" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-base font-mono font-black text-slate-900 focus:bg-white focus:outline-red-500 text-center" 
                        />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Giá Vé Áp Dụng (VNĐ)</label>
                        <input 
                            v-model.number="addDailyTripForm.base_price" 
                            type="number" 
                            step="5000" 
                            required 
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-mono font-bold text-slate-900" 
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Xe Phục Vụ</label>
                            <select v-model="addDailyTripForm.bus_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 font-bold text-slate-800">
                                <option v-for="b in buses" :key="b.id" :value="b.id">{{ b.license_plate }} ({{ b.bus_type }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Bác Tài Phụ Trách</label>
                            <select v-model="addDailyTripForm.driver_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-2 font-bold text-slate-800">
                                <option v-for="d in drivers" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-900 text-[11px] leading-relaxed">
                        ✓ <b>Tự động tạo lịch 4 ngày liên tiếp:</b> Khung giờ này sẽ tự động tạo chuyến cho Hôm nay và 3 ngày tiếp theo để khách hàng và tài xế có thể xem và đặt vé ngay.
                    </div>

                    <div class="pt-2 flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl cursor-pointer shadow-sm">
                            TẠO KHUNG GIỜ CHẠY MỚI
                        </button>
                        <button type="button" @click="showAddDailyTripModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL 4: QUẢN LÝ TRẠM DỪNG & TÍNH ETA ==================== -->
        <div v-if="showStopsModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 max-w-2xl w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="font-black text-slate-900 text-base">Quản Lý Danh Sách Trạm Dừng & Đo ETA</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Tuyến: <strong class="text-slate-800">{{ selectedRoute?.name }}</strong> (Tổng: {{ selectedRoute?.distance_km }} km)
                        </p>
                    </div>
                    <button @click="showStopsModal = false" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">✕</button>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-700">Lộ trình theo thứ tự (Bến A ➔ Trạm B ➔ Bến C):</span>
                        <button
                            type="button"
                            @click="addStopRow"
                            class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold rounded-lg border border-emerald-200 transition flex items-center gap-1 cursor-pointer"
                        >
                            <span>➕</span>
                            <span>Thêm Trạm Dừng</span>
                        </button>
                    </div>

                    <div class="space-y-2">
                        <div 
                            v-for="(stop, idx) in editingStops" 
                            :key="idx"
                            class="flex items-center gap-2.5 p-3 rounded-2xl bg-slate-50 border border-slate-200"
                        >
                            <span class="w-6 h-6 rounded-full bg-slate-200 text-slate-800 font-bold flex items-center justify-center text-xs shrink-0">
                                {{ idx + 1 }}
                            </span>

                            <!-- Tên trạm dừng -->
                            <div class="flex-1">
                                <input
                                    type="text"
                                    v-model="stop.stop_name"
                                    required
                                    placeholder="Ví dụ: Bến xe TT Đà Nẵng, Trạm dừng Lăng Cô, Bến xe Phía Nam Huế..."
                                    class="w-full bg-white border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-blue-500"
                                />
                            </div>

                            <!-- Khoảng cách km từ bến xuất phát -->
                            <div class="w-32 shrink-0">
                                <div class="relative">
                                    <input
                                        type="number"
                                        v-model.number="stop.distance_from_start_km"
                                        @input="updateDistance(idx)"
                                        min="0"
                                        class="w-full bg-white border border-slate-200 rounded-xl pl-2 pr-7 py-1.5 text-xs font-mono font-bold text-slate-900 focus:outline-blue-500 text-right"
                                    />
                                    <span class="absolute right-2 top-1.5 text-[10px] text-slate-400 font-bold">km</span>
                                </div>
                            </div>

                            <!-- ETA dự kiến tự động tính -->
                            <div class="w-28 shrink-0 text-center">
                                <span class="px-2 py-1 bg-blue-50 text-blue-700 font-mono font-bold text-xs rounded-lg border border-blue-200 block">
                                    ETA: {{ formatMinutesToHours(stop.estimated_minutes_from_start) }}
                                </span>
                            </div>

                            <!-- Nút xóa trạm -->
                            <button
                                type="button"
                                @click="removeStopRow(idx)"
                                class="w-7 h-7 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center shrink-0 cursor-pointer"
                                title="Xóa trạm dừng này"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-200/60 text-[11px] text-blue-900 leading-relaxed">
                        <strong>💡 Cách hệ thống tính ETA:</strong> Khi nhập số km khoảng cách từ điểm xuất phát, hệ thống tự động tính ETA cho bác tài dựa trên vận tốc chạy xe đường dài tiêu chuẩn (55 km/h). Bác tài và lơ xe có thể nhìn thấy giờ dự kiến đến trạm để báo trước cho hành khách chuẩn bị hành lý.
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
                    <button
                        type="button"
                        @click="saveRouteStops"
                        class="flex-1 py-2.5 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl cursor-pointer"
                    >
                        Lưu Danh Sách Trạm & Cập Nhật ETA
                    </button>
                    <button
                        type="button"
                        @click="showStopsModal = false"
                        class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
                    >
                        Đóng
                    </button>
                </div>
            </div>
        </div>

        <!-- ==================== MODAL 5: THÊM TUYẾN ĐƯỜNG MỚI ==================== -->
        <div v-if="showAddRouteModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-slate-200 max-w-xl w-full p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900">🛣️ Thêm Tuyến Đường Mới</h3>
                    <button @click="showAddRouteModal = false" class="text-slate-400 hover:text-slate-700 text-xl cursor-pointer">&times;</button>
                </div>

                <form @submit.prevent="submitAddRoute" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tên Tuyến Đường</label>
                        <input v-model="addRouteForm.name" required type="text" placeholder="Ví dụ: Tuyến Đà Nẵng ➔ Quy Nhơn" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Điểm Xuất Phát (Bến A)</label>
                            <input v-model="addRouteForm.origin" required type="text" placeholder="Đà Nẵng" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Điểm Đến (Bến B)</label>
                            <input v-model="addRouteForm.destination" required type="text" placeholder="Quy Nhơn" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Khoảng Cách (km)</label>
                            <input v-model.number="addRouteForm.distance_km" required type="number" placeholder="310" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-slate-900" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Thời Gian Chạy Dự Kiến (giờ)</label>
                            <input v-model.number="addRouteForm.estimated_hours" required type="number" step="0.5" placeholder="5.5" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono font-bold text-slate-900" />
                        </div>
                    </div>

                    <div class="pt-2 flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl cursor-pointer shadow-sm">
                            XÁC NHẬN TẠO TUYẾN
                        </button>
                        <button type="button" @click="showAddRouteModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
