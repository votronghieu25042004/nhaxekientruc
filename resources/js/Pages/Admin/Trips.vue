<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
import { ref, computed, onMounted } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    trips: { type: Array, default: () => [] },
    routesList: { type: Array, default: () => [] },
    driversList: { type: Array, default: () => [] },
    busesList: { type: Array, default: () => [] },
    conductorsList: { type: Array, default: () => [] },
    selectedDate: { type: String, default: () => new Date().toISOString().split('T')[0] },
    dailyStats: { type: Object, default: () => ({}) },
    allAssignmentTrips: { type: Array, default: () => [] },
    flash: Object
})

const todayIso = new Date().toISOString().split('T')[0];
const tomorrowObj = new Date();
tomorrowObj.setDate(tomorrowObj.getDate() + 1);
const tomorrowIso = tomorrowObj.toISOString().split('T')[0];
const dayAfterObj = new Date();
dayAfterObj.setDate(dayAfterObj.getDate() + 2);
const dayAfterIso = dayAfterObj.toISOString().split('T')[0];
const day4Obj = new Date();
day4Obj.setDate(day4Obj.getDate() + 3);
const day4Iso = day4Obj.toISOString().split('T')[0];

const showCreateTripModal = ref(false)
const showCounterModal = ref(false)
const showPriceModal = ref(false)
const showBulkPriceModal = ref(false)
const showAssignModal = ref(false)
const showIncidentModal = ref(false)
const showSeatMapModal = ref(false)

const selectedTripIds = ref([])
const isAllSelected = computed(() => {
    if (!paginatedTrips.value.length) return false
    return paginatedTrips.value.every(t => selectedTripIds.value.includes(t.id))
})

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        const currentIds = paginatedTrips.value.map(t => t.id)
        selectedTripIds.value = selectedTripIds.value.filter(id => !currentIds.includes(id))
    } else {
        const toAdd = paginatedTrips.value.map(t => t.id).filter(id => !selectedTripIds.value.includes(id))
        selectedTripIds.value = [...selectedTripIds.value, ...toAdd]
    }
}

const select3or4TripsQuickly = (count = 3) => {
    const list = paginatedTrips.value.slice(0, count).map(t => t.id)
    selectedTripIds.value = list
}

const bulkPriceForm = useForm({
    scope: 'selected',
    trip_ids: [],
    type: 'add_percent',
    amount: 30,
    target_floor: 'all',
    reason: 'Tự động tăng giá 30% dịp Lễ / Tết / Cuối tuần',
    date: props.selectedDate || todayIso
})

const openBulkPriceModal = (defaultScope = null) => {
    if (defaultScope) {
        bulkPriceForm.scope = defaultScope
    } else if (selectedTripIds.value.length > 0) {
        bulkPriceForm.scope = 'selected'
    } else {
        bulkPriceForm.scope = 'all_filtered'
    }
    bulkPriceForm.trip_ids = [...selectedTripIds.value]
    showBulkPriceModal.value = true
}

const submitBulkPrice = () => {
    bulkPriceForm.trip_ids = [...selectedTripIds.value]
    bulkPriceForm.date = currentDateFilter.value
    bulkPriceForm.post('/admin/trips/bulk-adjust-prices', {
        onSuccess: () => {
            showBulkPriceModal.value = false
            selectedTripIds.value = []
        }
    })
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search)
        if (urlParams.get('open_bulk') === '1') {
            openBulkPriceModal('all_filtered')
        }
    }
})

const selectedTripForCounter = ref(null)
const selectedTripForPrice = ref(null)
const selectedTripForAssign = ref(null)
const selectedTripForIncident = ref(null)
const selectedTripForSeatMap = ref(null)

// Bộ lọc ngày
const currentDateFilter = ref(props.selectedDate || todayIso)
const customDate = ref(props.selectedDate !== 'ALL' ? props.selectedDate : '')
const filterByDate = (dateVal) => {
    currentDateFilter.value = dateVal
    router.get('/admin/trips', { date: dateVal }, { preserveState: false, preserveScroll: true })
}

// Seat Map Modal Helper (Sơ đồ ghế 30 chỗ chuẩn)
const openSeatMapModal = (trip) => {
    selectedTripForSeatMap.value = trip
    showSeatMapModal.value = true
}

const tripForm = useForm({
    route_id: '',
    bus_id: '',
    driver_id: '',
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
    driver_id: '',
    second_driver_id: '',
    conductor_id: '',
    second_conductor_id: '',
    bus_id: ''
})

const incidentForm = useForm({
    action_type: 'DELAY',
    reason: '',
    new_time: '',
    backup_bus: '',
    custom_title: '',
    note: ''
})

// Kiểm tra trùng lịch
const checkTimeOverlap = (depA, arrA, depB, arrB) => {
    if (!depA || !arrA || !depB || !arrB) return false
    const startA = new Date(depA).getTime()
    const endA = new Date(arrA).getTime()
    const startB = new Date(depB).getTime()
    const endB = new Date(arrB).getTime()
    return (startA < endB) && (endA > startB)
}

const getDriverConflict = (driverId) => {
    if (!driverId || !selectedTripForAssign.value) return null
    const curTrip = selectedTripForAssign.value
    const curDep = curTrip.departure_time
    const curArr = curTrip.arrival_time
    return props.allAssignmentTrips.find(t => {
        if (t.id === curTrip.id) return false
        if (t.driver_id == driverId || t.second_driver_id == driverId) {
            return checkTimeOverlap(curDep, curArr, t.departure_time, t.arrival_time)
        }
        return false
    })
}

const getConductorConflict = (conductorId) => {
    if (!conductorId || !selectedTripForAssign.value) return null
    const curTrip = selectedTripForAssign.value
    const curDep = curTrip.departure_time
    const curArr = curTrip.arrival_time
    return props.allAssignmentTrips.find(t => {
        if (t.id === curTrip.id) return false
        if (t.conductor_id == conductorId || t.second_conductor_id == conductorId) {
            return checkTimeOverlap(curDep, curArr, t.departure_time, t.arrival_time)
        }
        return false
    })
}

const getBusConflict = (busId) => {
    if (!busId || !selectedTripForAssign.value) return null
    const curTrip = selectedTripForAssign.value
    const curDep = curTrip.departure_time
    const curArr = curTrip.arrival_time
    return props.allAssignmentTrips.find(t => {
        if (t.id === curTrip.id) return false
        if (t.bus_id == busId) {
            return checkTimeOverlap(curDep, curArr, t.departure_time, t.arrival_time)
        }
        return false
    })
}

const submitCreateTrip = () => {
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
    priceForm.floor2_price = trip.floor2_price || Math.max(0, trip.base_price - 10000)
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
    assignForm.second_driver_id = trip.second_driver_id || ''
    assignForm.conductor_id = trip.conductor_id || ''
    assignForm.second_conductor_id = trip.second_conductor_id || ''
    assignForm.bus_id = trip.bus_id || ''
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

const openIncidentModal = (trip) => {
    selectedTripForIncident.value = trip
    showIncidentModal.value = true
}

const submitIncident = () => {
    if (!selectedTripForIncident.value) return
    incidentForm.post(`/admin/trips/${selectedTripForIncident.value.id}/notify-incident`, {
        onSuccess: () => {
            showIncidentModal.value = false
        }
    })
}

const sendReminderForTrip = (trip) => {
    if (confirm(`Bạn có chắc muốn tự động gửi email nhắc 45 phút tới toàn bộ ${trip.total_passengers} hành khách của chuyến ${trip.trip_code}?`)) {
        router.post(`/admin/trips/${trip.id}/notify-reminder`)
    }
}

const sendAllReminders = () => {
    if (confirm('Hệ thống sẽ quét tất cả các chuyến xe chuẩn bị chạy hôm nay và gửi email nhắc 45 phút kèm mã QR cho toàn bộ hành khách. Bạn có muốn tiếp tục?')) {
        router.post('/admin/trips/notify-all-reminders')
    }
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
            return { label: '⏳ Chờ TX nhận', class: 'bg-amber-50 text-amber-800 border border-amber-300 font-bold' }
        case 'DRIVER_CONFIRMED':
            return { label: '✅ TX đã nhận', class: 'bg-teal-50 text-teal-800 border border-teal-300 font-bold' }
        case 'READY':
            return { label: '🔧 Đã kiểm xe', class: 'bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold' }
        case 'IN_TRANSIT':
        case 'RUNNING':
            return { label: '🟢 Đang lăn bánh', class: 'bg-emerald-500 text-white font-black shadow-xs animate-pulse' }
        case 'COMPLETED':
        case 'ARRIVED':
            return { label: '🏁 Đã đến bến', class: 'bg-slate-100 text-slate-700 border border-slate-300 font-bold' }
        case 'DELAYED':
            return { label: '⚠️ Dời giờ chạy', class: 'bg-amber-100 text-amber-900 border border-amber-400 font-bold' }
        case 'CANCELLED':
            return { label: '⛔ Đã hủy chuyến', class: 'bg-rose-100 text-rose-900 border border-rose-300 font-bold' }
        case 'SCHEDULED':
        default:
            return { label: '✨ Mở bán vé', class: 'bg-blue-50 text-blue-700 border border-blue-200 font-bold' }
    }
}

// Bộ lọc tìm kiếm & phân trang
const searchQuery = ref('')
const statusFilter = ref('ALL')
const pageSize = ref(10)
const currentPage = ref(1)
const viewMode = ref('table') // 'table' | 'cards'

const filteredTrips = computed(() => {
    if (!props.trips) return []
    const list = props.trips.filter(t => {
        if (statusFilter.value !== 'ALL') {
            if (statusFilter.value === 'UPCOMING') {
                if (['COMPLETED', 'ARRIVED', 'CLOSED', 'CANCELLED'].includes(t.status)) return false
            }
            else if (statusFilter.value === 'RUNNING' && !(t.status === 'RUNNING' || t.status === 'IN_TRANSIT')) return false
            else if (statusFilter.value === 'COMPLETED' && !(t.status === 'COMPLETED' || t.status === 'ARRIVED')) return false
            else if (statusFilter.value === 'SCHEDULED' && t.status !== 'SCHEDULED' && t.status !== 'OPEN_FOR_SALE') return false
            else if (statusFilter.value === 'DELAYED_CANCELLED' && !(t.status === 'DELAYED' || t.status === 'CANCELLED')) return false
        }
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.toLowerCase().trim()
            const matchCode = (t.trip_code || '').toLowerCase().includes(q)
            const matchRoute = (t.route_name || '').toLowerCase().includes(q)
            const matchDriver = (t.driver_name || '').toLowerCase().includes(q)
            const matchPlate = (t.bus_plate || '').toLowerCase().includes(q)
            if (!matchCode && !matchRoute && !matchDriver && !matchPlate) return false
        }
        return true
    })

    const getPriority = (status) => {
        if (status === 'RUNNING' || status === 'IN_TRANSIT') return 1
        if (status === 'BOARDING') return 2
        if (status === 'SCHEDULED' || status === 'OPEN_FOR_SALE') return 3
        if (status === 'DELAYED') return 4
        if (status === 'COMPLETED' || status === 'ARRIVED' || status === 'CLOSED') return 10
        if (status === 'CANCELLED') return 11
        return 5
    }

    return list.slice().sort((a, b) => {
        const pA = getPriority(a.status)
        const pB = getPriority(b.status)
        if (pA !== pB) return pA - pB
        const timeA = new Date(a.departure_time || 0).getTime()
        const timeB = new Date(b.departure_time || 0).getTime()
        if (pA >= 10) return timeB - timeA
        return timeA - timeB
    })
})

const totalPages = computed(() => {
    if (!filteredTrips.value.length) return 1
    return Math.ceil(filteredTrips.value.length / pageSize.value)
})

const paginatedTrips = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value
    return filteredTrips.value.slice(start, start + pageSize.value)
})

const pageNumbers = computed(() => {
    const total = totalPages.value
    const cur = currentPage.value
    const delta = 2
    const range = []
    for (let i = Math.max(1, cur - delta); i <= Math.min(total, cur + delta); i++) {
        range.push(i)
    }
    return range
})

const formatCurrency = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0)
</script>

<template>
    <Head title="Quản Lý Lịch Trình Chuyến Đi | nhaxekientruc Admin" />

    <div class="min-h-screen bg-[#F8FAFC] text-slate-800 pb-20 font-sans selection:bg-red-600 selection:text-white" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">
        <!-- 1. Top App Header -->
        <AdminHeader title="LỊCH TRÌNH VẬN HÀNH CHUYẾN ĐI" subtitle="Điều phối 30 chỗ thời gian thực • Quản lý Tài xế, Lơ xe & Nhắc 45P tự động" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-6">
            
            <!-- Flash Notification -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-300 rounded-2xl text-emerald-900 text-xs sm:text-sm font-bold flex items-center gap-2 shadow-xs">
                <span class="text-base">✓</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.flash?.error" class="p-4 bg-red-50 border border-red-300 rounded-2xl text-red-900 text-xs sm:text-sm font-bold flex items-center gap-2 shadow-xs">
                <span class="text-base">⚠️</span>
                <span>{{ $page.props.flash.error }}</span>
            </div>

            <!-- ============================================================== -->
            <!-- 2. DATE HORIZON BAR & OPERATIONAL KPI COMMAND CENTER           -->
            <!-- ============================================================== -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm space-y-4">
                
                <!-- Date Horizon Selector -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-red-600 to-rose-600 text-white flex items-center justify-center text-lg shadow-md shadow-red-600/20">
                            📅
                        </div>
                        <div>
                            <h3 class="font-black text-sm text-slate-900 uppercase tracking-wide flex items-center gap-2">
                                <span>LỊCH TRÌNH THEO NGÀY</span>
                                <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold font-mono">
                                    {{ dailyStats.is_all ? 'TẤT CẢ NGÀY' : dailyStats.date_formatted }}
                                </span>
                            </h3>
                            <p class="text-xs text-slate-500 font-medium">Bấm chọn ngày để xem lịch phân bổ xe, tài xế và số vé đã đặt</p>
                        </div>
                    </div>

                    <!-- Date Quick Tabs & Date Picker -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            @click="filterByDate('ALL')"
                            class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5"
                            :class="currentDateFilter === 'ALL' ? 'bg-red-600 text-white shadow-md shadow-red-600/20' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        >
                            <span>🌐</span>
                            <span>Tất Cả Ngày</span>
                        </button>

                        <button
                            type="button"
                            @click="filterByDate(todayIso)"
                            class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5"
                            :class="currentDateFilter === todayIso ? 'bg-red-600 text-white shadow-md shadow-red-600/20' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        >
                            <span>☀️</span>
                            <span>Hôm Nay ({{ todayIso.split('-')[2] }}/{{ todayIso.split('-')[1] }})</span>
                        </button>

                        <button
                            type="button"
                            @click="filterByDate(tomorrowIso)"
                            class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5"
                            :class="currentDateFilter === tomorrowIso ? 'bg-red-600 text-white shadow-md shadow-red-600/20' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        >
                            <span>📅</span>
                            <span>Ngày Mai ({{ tomorrowIso.split('-')[2] }}/{{ tomorrowIso.split('-')[1] }})</span>
                        </button>

                        <button
                            type="button"
                            @click="filterByDate(dayAfterIso)"
                            class="px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-1.5"
                            :class="currentDateFilter === dayAfterIso ? 'bg-red-600 text-white shadow-md shadow-red-600/20' : 'bg-slate-100 hover:bg-slate-200 text-slate-700'"
                        >
                            <span>🗓️</span>
                            <span>Ngày Kia ({{ dayAfterIso.split('-')[2] }}/{{ dayAfterIso.split('-')[1] }})</span>
                        </button>

                        <div class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 shadow-2xs">
                            <span class="text-xs text-slate-400">📅</span>
                            <input 
                                type="date"
                                v-model="customDate"
                                @change="filterByDate(customDate)"
                                class="bg-transparent text-xs font-bold text-slate-800 focus:outline-none cursor-pointer"
                                title="Chọn ngày bất kỳ"
                            />
                        </div>
                    </div>
                </div>

                <!-- 4 KPI Operational Cards (Clean, Modern, Slate/Red Branding) -->
                <div v-if="dailyStats" class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 pt-1">
                    
                    <!-- KPI 1: Tổng chuyến -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/70 border border-slate-200/80 shadow-2xs space-y-1">
                        <div class="flex items-center justify-between text-slate-500 text-[11px] font-bold uppercase">
                            <span>Tổng Chuyến Chạy</span>
                            <span class="text-base">🚍</span>
                        </div>
                        <div class="text-2xl font-black font-mono text-slate-900">
                            {{ dailyStats.total_trips }} <span class="text-xs font-sans font-bold text-slate-400">chuyến</span>
                        </div>
                        <div class="text-[10.5px] font-bold text-emerald-700 flex items-center gap-1">
                            <span>🟢 Đang chạy: {{ (trips || []).filter(t => t.status === 'RUNNING' || t.status === 'IN_TRANSIT').length }}</span>
                            <span>•</span>
                            <span class="text-slate-500">Sắp chạy: {{ (trips || []).filter(t => ['OPEN_FOR_SALE', 'BOARDING', 'SCHEDULED', 'READY'].includes(t.status)).length }}</span>
                        </div>
                    </div>

                    <!-- KPI 2: Xe hoạt động -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/70 border border-slate-200/80 shadow-2xs space-y-1">
                        <div class="flex items-center justify-between text-slate-500 text-[11px] font-bold uppercase">
                            <span>Xe Điều Động</span>
                            <span class="text-base">📋</span>
                        </div>
                        <div class="text-2xl font-black font-mono text-red-600">
                            {{ dailyStats.active_buses_count }} <span class="text-xs font-sans font-bold text-slate-400">xe</span>
                        </div>
                        <div class="text-[10.5px] text-slate-500 font-bold truncate" :title="dailyStats.bus_plates?.join(', ')">
                            Biển số: {{ dailyStats.bus_plates?.slice(0, 3).join(', ') }}{{ dailyStats.bus_plates?.length > 3 ? '...' : '' }}
                        </div>
                    </div>

                    <!-- KPI 3: Hành khách & Lấp đầy -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/70 border border-slate-200/80 shadow-2xs space-y-1">
                        <div class="flex items-center justify-between text-slate-500 text-[11px] font-bold uppercase">
                            <span>Lấp Đầy Ghế</span>
                            <span class="text-base">💺</span>
                        </div>
                        <div class="text-2xl font-black font-mono text-emerald-700">
                            {{ dailyStats.total_passengers }} <span class="text-xs font-sans font-bold text-slate-400">/ {{ dailyStats.total_capacity }}</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-emerald-500 h-full rounded-full" :style="`width: ${Math.min(100, dailyStats.occupancy_rate || 0)}%`"></div>
                        </div>
                    </div>

                    <!-- KPI 4: Doanh thu dự kiến -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-50/60 to-orange-50/40 border border-amber-200/80 shadow-2xs space-y-1">
                        <div class="flex items-center justify-between text-amber-900 text-[11px] font-bold uppercase">
                            <span>Doanh Thu Dự Kiến</span>
                            <span class="text-base">💰</span>
                        </div>
                        <div class="text-xl sm:text-2xl font-black font-mono text-red-600">
                            {{ formatCurrency(dailyStats.total_revenue) }}
                        </div>
                        <div class="text-[10.5px] text-amber-800 font-bold flex items-center gap-1">
                            <span>✓ Tính theo giá vé từng ghế</span>
                        </div>
                    </div>

                </div>

            </div>

            <!-- ============================================================== -->
            <!-- 3. ACTION TOOLBAR & FILTER CONTROLS                            -->
            <!-- ============================================================== -->
            <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <!-- Quick Search Input -->
                <div class="relative flex-1 max-w-md">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
                    <input 
                        v-model="searchQuery" 
                        @input="currentPage = 1" 
                        type="text" 
                        placeholder="Tìm mã chuyến, tuyến đường, tài xế, biển số..." 
                        class="w-full bg-slate-50 border border-slate-200 hover:border-slate-300 focus:border-red-500 focus:bg-white rounded-2xl pl-10 pr-8 py-2.5 text-xs sm:text-sm font-semibold text-slate-900 outline-none transition shadow-2xs"
                    />
                    <button 
                        v-if="searchQuery" 
                        @click="searchQuery = ''; currentPage = 1" 
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs font-bold cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <!-- Status Filter Pills & View Switcher -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl text-xs font-bold">
                        <button 
                            @click="statusFilter = 'ALL'; currentPage = 1" 
                            :class="statusFilter === 'ALL' ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-xl transition cursor-pointer"
                        >
                            Tất cả ({{ trips.length }})
                        </button>
                        <button 
                            @click="statusFilter = 'UPCOMING'; currentPage = 1" 
                            :class="statusFilter === 'UPCOMING' ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1"
                        >
                            <span>⚡</span>
                            <span>Sắp chạy</span>
                        </button>
                        <button 
                            @click="statusFilter = 'RUNNING'; currentPage = 1" 
                            :class="statusFilter === 'RUNNING' ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1"
                        >
                            <span>🟢</span>
                            <span>Lăn bánh</span>
                        </button>
                        <button 
                            @click="statusFilter = 'COMPLETED'; currentPage = 1" 
                            :class="statusFilter === 'COMPLETED' ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                            class="px-3 py-1.5 rounded-xl transition cursor-pointer"
                        >
                            Đã về bến
                        </button>
                    </div>

                    <!-- View Switcher (Table vs Cards) -->
                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl text-xs font-bold">
                        <button 
                            type="button"
                            @click="viewMode = 'table'" 
                            :class="viewMode === 'table' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="px-2.5 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1"
                            title="Dạng Bảng"
                        >
                            <span>📋</span>
                            <span class="hidden sm:inline">Bảng</span>
                        </button>
                        <button 
                            type="button"
                            @click="viewMode = 'cards'" 
                            :class="viewMode === 'cards' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-500 hover:text-slate-900'"
                            class="px-2.5 py-1.5 rounded-xl transition cursor-pointer flex items-center gap-1"
                            title="Dạng Thẻ"
                        >
                            <span>🗂️</span>
                            <span class="hidden sm:inline">Thẻ</span>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <button 
                            @click="sendAllReminders"
                            class="px-3.5 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black rounded-2xl shadow-xs transition flex items-center gap-1.5 text-xs cursor-pointer"
                            title="Gửi email nhắc 45 phút cho toàn bộ hành khách hôm nay"
                        >
                            <span>🔔</span>
                            <span class="hidden sm:inline">Nhắc 45P Toàn Bộ</span>
                        </button>

                        <button 
                            @click="openBulkPriceModal(null)"
                            class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-black rounded-2xl shadow-xs transition flex items-center gap-1.5 text-xs cursor-pointer"
                            title="Tự động tăng giá toàn bộ chuyến xe hoặc các chuyến đã chọn"
                        >
                            <span>⚡</span>
                            <span>Sửa Giá Hàng Loạt</span>
                        </button>

                        <button 
                            @click="showCreateTripModal = true"
                            class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-black rounded-2xl shadow-md shadow-red-600/25 transition flex items-center gap-1.5 text-xs cursor-pointer active:scale-95"
                        >
                            <span>➕</span>
                            <span>Tạo Chuyến Mới</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Bulk Selection Floating Banner -->
            <div v-if="selectedTripIds.length > 0" class="p-4 bg-gradient-to-r from-red-950 via-slate-900 to-red-900 text-white rounded-3xl shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 border border-red-500/40 animate-in fade-in">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center font-black text-sm">✓</span>
                    <div>
                        <div class="text-xs font-bold">
                            Đang chọn <strong class="text-amber-300 font-mono text-sm">{{ selectedTripIds.length }}</strong> chuyến xe.
                        </div>
                        <div class="text-[11px] text-red-200">
                            Gợi ý: 
                            <button @click="select3or4TripsQuickly(3)" class="underline hover:text-white font-bold cursor-pointer">Chọn 3 chuyến đầu</button> • 
                            <button @click="select3or4TripsQuickly(4)" class="underline hover:text-white font-bold cursor-pointer">Chọn 4 chuyến đầu</button>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="openBulkPriceModal('selected')"
                        class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs transition cursor-pointer flex items-center gap-1.5 shadow-md"
                    >
                        <span>⚡</span>
                        <span>Điều Chỉnh Giá {{ selectedTripIds.length }} Chuyến Này</span>
                    </button>
                    <button
                        @click="selectedTripIds = []"
                        class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs transition cursor-pointer"
                    >
                        Bỏ chọn
                    </button>
                </div>
            </div>

            <!-- ============================================================== -->
            <!-- 4. MASTER TRIPS TABLE / CARDS VIEW                             -->
            <!-- ============================================================== -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                
                <!-- 4A. TABLE VIEW -->
                <div v-if="viewMode === 'table'" class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700 border-collapse">
                        <thead class="bg-slate-50/80 text-slate-600 uppercase font-black text-[11px] border-b border-slate-200 select-none">
                            <tr>
                                <th class="p-4 w-12 text-center">
                                    <input 
                                        type="checkbox" 
                                        :checked="isAllSelected" 
                                        @change="toggleSelectAll" 
                                        class="w-4 h-4 accent-red-600 rounded cursor-pointer" 
                                        title="Chọn toàn bộ" 
                                    />
                                </th>
                                <th class="p-4">Mã Chuyến</th>
                                <th class="p-4">Tuyến Đường & Sức Chứa (30 Chỗ)</th>
                                <th class="p-4">Giá Vé</th>
                                <th class="p-4">Xe & Kíp Chạy</th>
                                <th class="p-4">Lịch Trình</th>
                                <th class="p-4">Trạng Thái</th>
                                <th class="p-4 text-right">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="filteredTrips.length === 0">
                                <td colspan="8" class="p-12 text-center text-slate-400 font-bold text-xs">
                                    Không tìm thấy chuyến xe nào phù hợp với bộ lọc tìm kiếm.
                                </td>
                            </tr>
                            <tr 
                                v-for="t in paginatedTrips" 
                                :key="t.id" 
                                class="hover:bg-slate-50/80 transition-colors" 
                                :class="selectedTripIds.includes(t.id) ? 'bg-amber-50/40' : ''"
                            >
                                <!-- Checkbox -->
                                <td class="p-4 text-center">
                                    <input 
                                        type="checkbox" 
                                        :value="t.id" 
                                        v-model="selectedTripIds" 
                                        class="w-4 h-4 accent-red-600 rounded cursor-pointer" 
                                    />
                                </td>
                                
                                <!-- Mã chuyến -->
                                <td class="p-4">
                                    <button 
                                        @click="openSeatMapModal(t)"
                                        class="font-mono font-black text-red-600 hover:text-red-700 hover:underline cursor-pointer text-xs"
                                        title="Xem nhanh sơ đồ ghế"
                                    >
                                        {{ t.trip_code }}
                                    </button>
                                </td>
                                
                                <!-- Tuyến đường & Sức chứa 30 chỗ -->
                                <td class="p-4 space-y-1.5">
                                    <div class="font-black text-slate-900 text-xs sm:text-sm flex items-center gap-1.5">
                                        <span>{{ t.route_name }}</span>
                                    </div>
                                    <!-- Thanh đo sức chứa 30 ghế chuẩn -->
                                    <div class="space-y-1 max-w-[220px]">
                                        <div class="flex justify-between text-[10.5px] font-bold text-slate-500">
                                            <span>Đã đặt: <strong class="text-slate-800">{{ t.booked_seats_count ?? t.total_passengers }}</strong> / 30 ghế</span>
                                            <span class="text-emerald-700 font-mono">{{ Math.round(((t.booked_seats_count ?? t.total_passengers) / 30) * 100) }}%</span>
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div 
                                                class="h-full rounded-full transition-all duration-300"
                                                :class="((t.booked_seats_count ?? t.total_passengers) / 30) >= 0.8 ? 'bg-red-500' : (((t.booked_seats_count ?? t.total_passengers) / 30) >= 0.4 ? 'bg-emerald-500' : 'bg-amber-500')"
                                                :style="`width: ${Math.min(100, Math.round(((t.booked_seats_count ?? t.total_passengers) / 30) * 100))}%`"
                                            ></div>
                                        </div>
                                    </div>
                                    <!-- Ghi chú nếu có -->
                                    <div v-if="t.customer_notes_list && t.customer_notes_list.length > 0" class="pt-0.5">
                                        <div class="text-[10px] text-amber-900 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200 inline-block font-medium truncate max-w-[240px]">
                                            ⚠️ {{ t.customer_notes_list[0]?.note }}
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Giá vé -->
                                <td class="p-4 space-y-0.5 font-mono">
                                    <div class="text-red-700 font-black text-xs sm:text-sm">{{ formatCurrency(t.floor1_price || t.base_price) }}</div>
                                    <div class="text-[10px] text-slate-400 font-sans font-bold">Đồng giá vé</div>
                                </td>

                                <!-- Xe & Kíp chạy -->
                                <td class="p-4 space-y-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded-md bg-amber-400 text-slate-950 font-mono font-black text-[10.5px] shadow-2xs">
                                            {{ t.bus_plate }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 font-bold">VIP 30 Chỗ</span>
                                    </div>
                                    <div class="text-slate-700 text-[11px] space-y-0.5 font-medium">
                                        <div class="truncate max-w-[180px]">👨‍✈️ TX: <strong class="text-slate-900">{{ t.driver_name }}</strong></div>
                                        <div class="truncate max-w-[180px] text-slate-500">🎫 Lơ: {{ t.conductor_name || 'Nguyễn Văn Phúc' }}</div>
                                    </div>
                                    <button 
                                        v-if="!['COMPLETED', 'ARRIVED', 'CLOSED'].includes(t.status)"
                                        @click="openAssignModal(t)"
                                        class="text-[10px] font-bold text-slate-600 hover:text-red-600 underline cursor-pointer"
                                    >
                                        Đổi Tài Xế / Lơ Xe
                                    </button>
                                </td>

                                <!-- Lịch trình giờ chạy -->
                                <td class="p-4 space-y-1">
                                    <div class="flex items-center gap-1.5 font-mono text-xs sm:text-sm">
                                        <span class="text-red-600 font-black">{{ t.dep_time_formatted || t.departure_time?.substring(11, 16) }}</span>
                                        <span class="text-slate-400">➔</span>
                                        <span class="text-slate-800 font-bold">{{ t.arr_time_formatted || t.arrival_time?.substring(11, 16) }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium">
                                        📅 {{ t.dep_date_formatted || t.departure_time?.substring(0, 10) }} <span class="text-slate-400">(~{{ t.duration_hours || '4.5' }}h)</span>
                                    </div>
                                </td>

                                <!-- Trạng thái -->
                                <td class="p-4">
                                    <span :class="getStatusBadge(t.status).class" class="px-2.5 py-1 rounded-full text-[11px] inline-flex items-center gap-1">
                                        {{ getStatusBadge(t.status).label }}
                                    </span>
                                </td>

                                <!-- Thao tác -->
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Nút Sơ đồ ghế chính -->
                                        <button 
                                            @click="openSeatMapModal(t)"
                                            class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs transition inline-flex items-center gap-1 cursor-pointer shadow-xs active:scale-95"
                                            title="Xem trực quan sơ đồ 30 ghế và danh sách khách"
                                        >
                                            <span>💺</span>
                                            <span>Sơ Đồ Ghế</span>
                                        </button>

                                        <!-- Các nút phụ khi chưa kết thúc chuyến -->
                                        <template v-if="!['COMPLETED', 'ARRIVED', 'CLOSED'].includes(t.status)">
                                            <button 
                                                @click="sendReminderForTrip(t)"
                                                class="p-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs transition cursor-pointer"
                                                title="Gửi email nhắc giờ chạy 45P"
                                            >
                                                🔔
                                            </button>
                                            <button 
                                                @click="openIncidentModal(t)"
                                                class="p-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-bold text-xs transition cursor-pointer"
                                                title="Báo sự cố dời giờ chạy"
                                            >
                                                ⚠️
                                            </button>
                                            <button 
                                                @click="openPriceModal(t)"
                                                class="p-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer"
                                                title="Chỉnh sửa giá vé"
                                            >
                                                ✏️
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 4B. CARDS VIEW -->
                <div v-else class="p-6">
                    <div v-if="filteredTrips.length === 0" class="p-12 text-center text-slate-400 font-bold text-xs">
                        Không tìm thấy chuyến xe nào phù hợp.
                    </div>
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                        <div 
                            v-for="t in paginatedTrips" 
                            :key="t.id"
                            class="bg-white rounded-3xl border transition-all p-5 space-y-4 hover:shadow-lg relative"
                            :class="selectedTripIds.includes(t.id) ? 'border-amber-400 bg-amber-50/20 shadow-md ring-2 ring-amber-400/30' : 'border-slate-200/80'"
                        >
                            <div class="flex items-start justify-between gap-2 pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <input 
                                        type="checkbox" 
                                        :value="t.id" 
                                        v-model="selectedTripIds" 
                                        class="w-4 h-4 accent-red-600 rounded cursor-pointer" 
                                    />
                                    <div>
                                        <div class="font-mono font-black text-red-600 text-sm">#{{ t.trip_code }}</div>
                                        <div class="text-[11px] text-slate-500 font-medium">📅 {{ t.dep_date_formatted }}</div>
                                    </div>
                                </div>
                                <span :class="getStatusBadge(t.status).class" class="px-2.5 py-0.5 rounded-full text-[10px]">
                                    {{ getStatusBadge(t.status).label }}
                                </span>
                            </div>

                            <div class="space-y-1">
                                <h4 class="font-black text-slate-900 text-sm truncate">{{ t.route_name }}</h4>
                                <div class="flex items-center justify-between text-xs font-mono bg-slate-50 px-3 py-2 rounded-xl">
                                    <span class="text-red-600 font-black">{{ t.dep_time_formatted }}</span>
                                    <span class="text-slate-400">➔ ~{{ t.duration_hours || '4.5' }}h ➔</span>
                                    <span class="text-slate-800 font-black">{{ t.arr_time_formatted }}</span>
                                </div>
                            </div>

                            <!-- Xe, Tài Xế & Sức chứa 30 chỗ -->
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <div class="p-2.5 bg-slate-50 rounded-xl space-y-0.5">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Xe Buýt</span>
                                    <div class="font-black text-slate-800 font-mono">{{ t.bus_plate }}</div>
                                    <div class="text-[10px] text-slate-500">VIP 30 Chỗ</div>
                                </div>
                                <div class="p-2.5 bg-slate-50 rounded-xl space-y-0.5">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Tài Xế</span>
                                    <div class="font-bold text-slate-800 truncate">{{ t.driver_name }}</div>
                                    <div class="text-[10px] text-slate-500 truncate">{{ t.conductor_name || 'Lơ xe' }}</div>
                                </div>
                            </div>

                            <!-- Occupancy Bar -->
                            <div class="space-y-1">
                                <div class="flex justify-between text-xs font-bold text-slate-600">
                                    <span>Đã đặt: <b>{{ t.booked_seats_count ?? t.total_passengers }}</b> / 30 ghế</span>
                                    <span class="text-red-600 font-mono">{{ formatCurrency(t.floor1_price || t.base_price) }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div 
                                        class="bg-emerald-500 h-full rounded-full" 
                                        :style="`width: ${Math.min(100, Math.round(((t.booked_seats_count ?? t.total_passengers) / 30) * 100))}%`"
                                    ></div>
                                </div>
                            </div>

                            <!-- Footer Card Actions -->
                            <div class="pt-2 border-t border-slate-100 flex items-center justify-between gap-1.5">
                                <button 
                                    @click="openSeatMapModal(t)"
                                    class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs transition cursor-pointer text-center shadow-xs flex items-center justify-center gap-1.5"
                                >
                                    <span>💺</span>
                                    <span>Sơ Đồ Ghế</span>
                                </button>
                                <template v-if="!['COMPLETED', 'ARRIVED', 'CLOSED'].includes(t.status)">
                                    <button 
                                        @click="sendReminderForTrip(t)"
                                        class="p-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs transition cursor-pointer"
                                        title="Gửi email nhắc 45P cho chuyến xe này"
                                    >
                                        🔔
                                    </button>
                                    <button 
                                        @click="openIncidentModal(t)"
                                        class="p-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 font-bold text-xs transition cursor-pointer"
                                        title="Báo sự cố dời giờ chạy"
                                    >
                                        ⚠️
                                    </button>
                                    <button 
                                        @click="openPriceModal(t)"
                                        class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer"
                                        title="Chỉnh sửa giá vé"
                                    >
                                        ✏️
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4C. PAGINATION FOOTER -->
                <div v-if="filteredTrips.length > 0" class="px-6 py-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/50">
                    <div class="text-xs text-slate-500 font-medium">
                        Hiển thị <b class="text-slate-900">{{ (currentPage - 1) * pageSize + 1 }} - {{ Math.min(currentPage * pageSize, filteredTrips.length) }}</b> trên <b class="text-slate-900">{{ filteredTrips.length }}</b> chuyến xe
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            :disabled="currentPage === 1" 
                            @click="currentPage--" 
                            class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                        >
                            ← Trước
                        </button>

                        <div class="flex items-center gap-1">
                            <button 
                                v-for="p in pageNumbers" 
                                :key="p" 
                                @click="currentPage = p" 
                                class="w-7 h-7 rounded-lg text-xs font-black transition cursor-pointer" 
                                :class="currentPage === p ? 'bg-red-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'"
                            >
                                {{ p }}
                            </button>
                        </div>

                        <button 
                            :disabled="currentPage >= totalPages" 
                            @click="currentPage++" 
                            class="px-3.5 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
                        >
                            Sau →
                        </button>
                    </div>
                </div>

            </div>

        </div>

        <!-- ============================================================== -->
        <!-- 5. SEAT MAP MODAL (30 GHẾ CHUẨN: 18 TẦNG 1 & 12 TẦNG 2)        -->
        <!-- ============================================================== -->
        <div v-if="showSeatMapModal && selectedTripForSeatMap" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-4xl w-full p-6 shadow-2xl space-y-5 max-h-[92vh] overflow-y-auto">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-3 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 font-mono font-black text-xs">
                                #{{ selectedTripForSeatMap.trip_code }}
                            </span>
                            <h3 class="text-base font-black text-slate-900">{{ selectedTripForSeatMap.route_name }}</h3>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                            <span>🚍 Biển số: <strong class="text-slate-900 font-mono">{{ selectedTripForSeatMap.bus_plate }} (30 Chỗ)</strong></span>
                            <span>•</span>
                            <span>⏰ Xuất bến: <strong class="text-red-600 font-mono">{{ selectedTripForSeatMap.dep_time_formatted }} ➔ {{ selectedTripForSeatMap.arr_time_formatted }}</strong></span>
                            <span>•</span>
                            <span>👨‍✈️ TX: <strong>{{ selectedTripForSeatMap.driver_name }}</strong></span>
                        </p>
                    </div>

                    <button 
                        @click="showSeatMapModal = false" 
                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold flex items-center justify-center text-base transition cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <!-- Modal Content: Sơ đồ 30 ghế 2 tầng CÂN ĐỐI 100% -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- Cột Trái (7/12): Sơ đồ ghế 2 tầng cân xứng -->
                    <div class="lg:col-span-7 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-4">
                        <div class="flex items-center justify-between text-xs font-black uppercase text-slate-800">
                            <span>Sơ Đồ Ghế Xe 30 Chỗ (Chuẩn Vận Hành)</span>
                            <div class="flex items-center gap-2 text-[10px] normal-case">
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-emerald-600"></span> Đã đặt</span>
                                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded bg-white border border-slate-300"></span> Trống</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3.5">
                            
                            <!-- TẦNG 1: 18 GHẾ VIP (A01 - A18, 6 HÀNG X 3 CỘT) -->
                            <div class="bg-white p-3 rounded-2xl border border-slate-200 text-center shadow-2xs space-y-2">
                                <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                                    <span class="text-[11px] font-black text-slate-800">TẦNG DƯỚI (18 GHẾ)</span>
                                    <span class="text-[10px] font-mono font-bold text-red-600">A01 - A18</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1 text-[9px] font-bold text-slate-400 pb-1 border-b border-slate-100 select-none">
                                    <span>🪟 Trái</span>
                                    <span>🚶 Giữa</span>
                                    <span>🪟 Phải</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1.5">
                                    <div
                                        v-for="i in 18"
                                        :key="'admin-A' + i"
                                        class="p-1 rounded-xl text-center font-mono font-bold text-xs transition border flex flex-col items-center justify-center min-h-[44px]"
                                        :class="selectedTripForSeatMap.seat_map_data && selectedTripForSeatMap.seat_map_data['A' + (i < 10 ? '0' + i : i)]
                                            ? 'bg-emerald-600 border-emerald-500 text-white shadow-2xs'
                                            : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'"
                                    >
                                        <div class="font-black text-[11px]">A{{ i < 10 ? '0' + i : i }}</div>
                                        <div v-if="selectedTripForSeatMap.seat_map_data && selectedTripForSeatMap.seat_map_data['A' + (i < 10 ? '0' + i : i)]" class="text-[8px] font-bold truncate">
                                            {{ selectedTripForSeatMap.seat_map_data['A' + (i < 10 ? '0' + i : i)].is_boarded ? '✓ Lên' : 'Có vé' }}
                                        </div>
                                        <div v-else class="text-[8px] text-slate-300">Trống</div>
                                    </div>
                                </div>
                            </div>

                            <!-- TẦNG 2: 12 GHẾ TIÊU CHUẨN (B01 - B12, 6 HÀNG X 2 CỬA SỔ, CỘT GIỮA LỐI ĐI) -->
                            <div class="bg-white p-3 rounded-2xl border border-slate-200 text-center shadow-2xs space-y-2">
                                <div class="flex items-center justify-between pb-1.5 border-b border-slate-100">
                                    <span class="text-[11px] font-black text-slate-800">TẦNG TRÊN (12 GHẾ)</span>
                                    <span class="text-[10px] font-mono font-bold text-red-600">B01 - B12</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1 text-[9px] font-bold text-slate-400 pb-1 border-b border-slate-100 select-none">
                                    <span>🪟 Trái</span>
                                    <span>🚶 Lối đi</span>
                                    <span>🪟 Phải</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1.5">
                                    <template v-for="r in 6" :key="'admin-f2-row-' + r">
                                        <!-- Ghế Trái: B01, B03, B05, B07, B09, B11 -->
                                        <div
                                            class="p-1 rounded-xl text-center font-mono font-bold text-xs transition border flex flex-col items-center justify-center min-h-[44px]"
                                            :class="selectedTripForSeatMap.seat_map_data && selectedTripForSeatMap.seat_map_data['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]
                                                ? 'bg-emerald-600 border-emerald-500 text-white shadow-2xs'
                                                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'"
                                        >
                                            <div class="font-black text-[11px]">B{{ (r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1) }}</div>
                                            <div v-if="selectedTripForSeatMap.seat_map_data && selectedTripForSeatMap.seat_map_data['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]" class="text-[8px] font-bold truncate">
                                                {{ selectedTripForSeatMap.seat_map_data['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))].is_boarded ? '✓ Lên' : 'Có vé' }}
                                            </div>
                                            <div v-else class="text-[8px] text-slate-300">Trống</div>
                                        </div>

                                        <!-- Cột Giữa: Lối Đi Thông Tầng VIP -->
                                        <div class="p-1 rounded-xl border border-dashed border-slate-200 bg-slate-50/70 text-center flex flex-col items-center justify-center select-none text-slate-400 min-h-[44px]">
                                            <span class="text-[10px]">🚶</span>
                                            <span class="text-[7.5px] font-bold uppercase tracking-tighter">Lối đi</span>
                                        </div>

                                        <!-- Ghế Phải: B02, B04, B06, B08, B10, B12 -->
                                        <div
                                            class="p-1 rounded-xl text-center font-mono font-bold text-xs transition border flex flex-col items-center justify-center min-h-[44px]"
                                            :class="selectedTripForSeatMap.seat_map_data && selectedTripForSeatMap.seat_map_data['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]
                                                ? 'bg-emerald-600 border-emerald-500 text-white shadow-2xs'
                                                : 'bg-white border-slate-200 text-slate-700 hover:border-slate-300'"
                                        >
                                            <div class="font-black text-[11px]">B{{ (r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2) }}</div>
                                            <div v-if="selectedTripForSeatMap.seat_map_data && selectedTripForSeatMap.seat_map_data['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]" class="text-[8px] font-bold truncate">
                                                {{ selectedTripForSeatMap.seat_map_data['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))].is_boarded ? '✓ Lên' : 'Có vé' }}
                                            </div>
                                            <div v-else class="text-[8px] text-slate-300">Trống</div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Cột Phải (5/12): Danh sách hành khách đã đặt vé -->
                    <div class="lg:col-span-5 bg-white p-4 rounded-2xl border border-slate-200 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                                <h4 class="font-black text-xs uppercase text-slate-900">Danh Sách Vé Đã Đặt</h4>
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-mono text-[10px] font-bold">
                                    {{ selectedTripForSeatMap.bookings_list?.length || 0 }} đơn
                                </span>
                            </div>

                            <div class="divide-y divide-slate-100 max-h-[360px] overflow-y-auto mt-2 space-y-1">
                                <div 
                                    v-for="b in selectedTripForSeatMap.bookings_list" 
                                    :key="b.booking_code"
                                    class="py-2.5 px-2 hover:bg-slate-50 rounded-xl transition flex items-center justify-between gap-2 text-xs"
                                >
                                    <div>
                                        <div class="font-black text-slate-900 flex items-center gap-1.5">
                                            <span>{{ b.customer_name }}</span>
                                            <span class="text-[10px] font-mono text-red-600 bg-red-50 px-1 rounded">#{{ b.seats_label }}</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ b.customer_phone }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-mono font-bold text-slate-800 text-[11px]">{{ formatCurrency(b.total_amount) }}</div>
                                        <span 
                                            class="text-[9.5px] font-bold px-1.5 py-0.2 rounded"
                                            :class="b.is_boarded ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                        >
                                            {{ b.is_boarded ? '✓ Đã lên' : '⏳ Chờ đón' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 text-right">
                            <button 
                                @click="showSeatMapModal = false"
                                class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition cursor-pointer"
                            >
                                Đóng
                            </button>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <!-- ============================================================== -->
        <!-- 6. MODALS: TẠO CHUYẾN, SỬA GIÁ, PHÂN CÔNG, BÁO SỰ CỐ           -->
        <!-- ============================================================== -->

        <!-- MODAL TẠO CHUYẾN MỚI -->
        <div v-if="showCreateTripModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>➕</span>
                        <span>Tạo Chuyến Xe Mới (30 Chỗ)</span>
                    </h3>
                    <button @click="showCreateTripModal = false" class="text-slate-400 hover:text-slate-800 text-xl font-bold cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="submitCreateTrip" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tuyến Đường Vận Hành *</label>
                        <select v-model="tripForm.route_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white outline-none">
                            <option value="" disabled>-- Chọn tuyến đường --</option>
                            <option v-for="r in routesList" :key="r.id" :value="r.id">{{ r.name }} ({{ r.origin }} ➔ {{ r.destination }})</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Xe Buýt (30 Chỗ) *</label>
                            <select v-model="tripForm.bus_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white outline-none">
                                <option value="" disabled>-- Chọn xe buýt --</option>
                                <option v-for="b in busesList" :key="b.id" :value="b.id">{{ b.license_plate }} ({{ b.bus_type }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Tài Xế Phụ Trách *</label>
                            <select v-model="tripForm.driver_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white outline-none">
                                <option value="" disabled>-- Chọn tài xế --</option>
                                <option v-for="d in driversList" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Giờ Khởi Hành *</label>
                            <input v-model="tripForm.departure_time" type="datetime-local" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white outline-none" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Giờ Đến Dự Kiến *</label>
                            <input v-model="tripForm.arrival_time" type="datetime-local" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white outline-none" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Giá Vé Tầng 1 (VNĐ) *</label>
                            <input v-model="tripForm.floor1_price" type="number" step="5000" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white outline-none" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Giá Vé Tầng 2 (VNĐ) *</label>
                            <input v-model="tripForm.floor2_price" type="number" step="5000" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white outline-none" />
                        </div>
                    </div>

                    <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="showCreateTripModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer">
                            Hủy
                        </button>
                        <button type="submit" :disabled="tripForm.processing" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md transition cursor-pointer">
                            {{ tripForm.processing ? 'Đang tạo...' : 'Tạo Chuyến Xe' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL ĐIỀU CHỈNH GIÁ HÀNG LOẠT -->
        <div v-if="showBulkPriceModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>⚡</span>
                        <span>Điều Chỉnh Giá Vé Hàng Loạt</span>
                    </h3>
                    <button @click="showBulkPriceModal = false" class="text-slate-400 hover:text-slate-800 text-xl font-bold cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="submitBulkPrice" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Phạm Vi Điều Chỉnh</label>
                        <select v-model="bulkPriceForm.scope" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800">
                            <option value="all_filtered">Tất cả chuyến đang lọc (Ngày: {{ currentDateFilter }})</option>
                            <option value="selected" :disabled="selectedTripIds.length === 0">Các chuyến đã chọn ({{ selectedTripIds.length }} chuyến)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Kiểu Tăng/Giảm</label>
                            <select v-model="bulkPriceForm.type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800">
                                <option value="add_percent">Tăng theo % (Ví dụ: +30%)</option>
                                <option value="sub_percent">Giảm theo % (Ví dụ: -10%)</option>
                                <option value="set_fixed">Đặt giá cố định (VNĐ)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Mức Điều Chỉnh</label>
                            <input v-model="bulkPriceForm.amount" type="number" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800" />
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Lý Do Điều Chỉnh</label>
                        <input v-model="bulkPriceForm.reason" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800" />
                    </div>

                    <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="showBulkPriceModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer">
                            Hủy
                        </button>
                        <button type="submit" :disabled="bulkPriceForm.processing" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md transition cursor-pointer">
                            {{ bulkPriceForm.processing ? 'Đang cập nhật...' : 'Áp Dụng Thay Đổi Giá' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL PHÂN CÔNG TÀI XẾ / LƠ XE -->
        <div v-if="showAssignModal && selectedTripForAssign" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>🔄</span>
                        <span>Điều Phối Tài Xế & Lơ Xe</span>
                    </h3>
                    <button @click="showAssignModal = false" class="text-slate-400 hover:text-slate-800 text-xl font-bold cursor-pointer">✕</button>
                </div>

                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-700 space-y-1">
                    <div class="font-bold">Chuyến: {{ selectedTripForAssign.trip_code }} ({{ selectedTripForAssign.route_name }})</div>
                    <div>Xuất bến: {{ selectedTripForAssign.dep_time_formatted }} • Xe: {{ selectedTripForAssign.bus_plate }}</div>
                </div>

                <form @submit.prevent="submitAssign" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tài Xế Chính *</label>
                        <select v-model="assignForm.driver_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800">
                            <option v-for="d in driversList" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Lơ Xe Phụ Trách</label>
                        <select v-model="assignForm.conductor_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800">
                            <option value="">-- Chưa phân công --</option>
                            <option v-for="c in conductorsList" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Phương Tiện (Xe Buýt 30 Chỗ)</label>
                        <select v-model="assignForm.bus_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800">
                            <option v-for="b in busesList" :key="b.id" :value="b.id">{{ b.license_plate }} ({{ b.bus_type }})</option>
                        </select>
                    </div>

                    <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="showAssignModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer">
                            Hủy
                        </button>
                        <button type="submit" :disabled="assignForm.processing" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md transition cursor-pointer">
                            {{ assignForm.processing ? 'Đang lưu...' : 'Lưu Phân Công' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL SỬA GIÁ ĐƠN LẺ -->
        <div v-if="showPriceModal && selectedTripForPrice" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>✏️</span>
                        <span>Sửa Giá Vé Chuyến {{ selectedTripForPrice.trip_code }}</span>
                    </h3>
                    <button @click="showPriceModal = false" class="text-slate-400 hover:text-slate-800 text-xl font-bold cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="submitPrice" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Giá Vé Tầng 1 (VNĐ)</label>
                        <input v-model="priceForm.floor1_price" type="number" step="5000" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Giá Vé Tầng 2 (VNĐ)</label>
                        <input v-model="priceForm.floor2_price" type="number" step="5000" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800" />
                    </div>

                    <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="showPriceModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer">
                            Hủy
                        </button>
                        <button type="submit" :disabled="priceForm.processing" class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs shadow-md transition cursor-pointer">
                            {{ priceForm.processing ? 'Đang lưu...' : 'Cập Nhật Giá' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL BÁO SỰ CỐ -->
        <div v-if="showIncidentModal && selectedTripForIncident" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-in fade-in">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>⚠️</span>
                        <span>Báo Sự Cố Chuyến {{ selectedTripForIncident.trip_code }}</span>
                    </h3>
                    <button @click="showIncidentModal = false" class="text-slate-400 hover:text-slate-800 text-xl font-bold cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="submitIncident" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Loại Thông Báo</label>
                        <select v-model="incidentForm.action_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-800">
                            <option value="DELAY">⚠️ Dời giờ xuất bến (Hoãn giờ)</option>
                            <option value="CANCEL">⛔ Sự cố hư xe / Hủy chuyến</option>
                            <option value="WEATHER">⛈️ Thời tiết xấu / Mưa bão / Sạt lở</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Lý Do Chi Tiết</label>
                        <input v-model="incidentForm.reason" type="text" required placeholder="Lý do gửi email tới hành khách..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800" />
                    </div>

                    <div v-if="incidentForm.action_type === 'DELAY'">
                        <label class="block font-bold text-slate-700 mb-1">Thời Gian Khởi Hành Mới</label>
                        <input v-model="incidentForm.new_time" type="text" placeholder="VD: Dời 30 phút, xuất bến 09:30" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-medium text-slate-800" />
                    </div>

                    <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                        <button type="button" @click="showIncidentModal = false" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer">
                            Hủy
                        </button>
                        <button type="submit" :disabled="incidentForm.processing" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-black text-xs shadow-md transition cursor-pointer">
                            {{ incidentForm.processing ? 'Đang gửi...' : 'Gửi Email Thông Báo Khẩn' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</template>