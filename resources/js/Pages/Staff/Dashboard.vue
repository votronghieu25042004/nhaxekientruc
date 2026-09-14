<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    trips: Array,
    selectedTrip: Object,
    occupiedSeats: Array,
    stops: Array,
    user: Object,
    flash: Object
})

const currentTrip = ref(props.selectedTrip || (props.trips && props.trips.length > 0 ? props.trips[0] : null))
const showRoadsideModal = ref(false)
const showExpenseModal = ref(false)
const showClosingModal = ref(false)
const showIncidentModal = ref(false)
const showLostFoundModal = ref(false)
const showChangeSeatModal = ref(false)
const selectedBookingForSeatChange = ref(null)
const searchQuery = ref('')

// Forms
const roadsideForm = useForm({
    customer_name: '',
    customer_phone: '',
    pickup_stop_id: '',
    dropoff_stop_id: '',
    seat_number: '',
    payment_method: 'CASH'
})

// Tính toán danh sách ghế thực sự CÒN TRỐNG trên chuyến xe (để đón khách dọc đường)
const availableEmptySeats = computed(() => {
    if (!currentTrip.value) return []
    
    // Tất cả ghế của xe
    const allSeats = []
    for (let i = 1; i <= 17; i++) {
        const num = i < 10 ? '0' + i : '' + i
        allSeats.push({ seat_number: 'A' + num, floor: 1, label: `Ghế A${num} (Tầng 1)` })
    }
    for (let i = 1; i <= 17; i++) {
        const num = i < 10 ? '0' + i : '' + i
        allSeats.push({ seat_number: 'B' + num, floor: 2, label: `Ghế B${num} (Tầng 2)` })
    }

    // Danh sách ghế đã bị chiếm
    const occupiedList = new Set()
    
    // Ghế từ props occupiedSeats
    if (props.occupiedSeats && Array.isArray(props.occupiedSeats)) {
        props.occupiedSeats.forEach(s => occupiedList.add(s))
    }

    // Ghế từ bookings của chuyến
    if (currentTrip.value.bookings && Array.isArray(currentTrip.value.bookings)) {
        currentTrip.value.bookings.forEach(b => {
            if (b.status !== 'CANCELLED' && b.booking_seats) {
                b.booking_seats.forEach(bs => occupiedList.add(bs.seat_number))
            }
        })
    }

    // Lọc ra các ghế thực sự trống
    return allSeats.filter(s => !occupiedList.has(s.seat_number))
})


const expenseForm = useForm({
    expense_type: 'Xăng dầu',
    amount: '',
    description: ''
})

const closingForm = useForm({
    actual_cash_submitted: '',
    notes: ''
})

const incidentForm = useForm({
    type: 'Điều hòa hỏng',
    description: '',
    severity: 'MEDIUM'
})

const lostFoundForm = useForm({
    item_name: '',
    seat_number: '',
    description: ''
})

const changeSeatForm = useForm({
    new_seat_number: ''
})

// Switch trip
const switchTrip = (tripId) => {
    router.get('/conductor', { trip_id: tripId }, { preserveState: true })
}

// Update trip status
const updateStatus = (status) => {
    if (!currentTrip.value) return
    router.post(`/conductor/trips/${currentTrip.value.id}/status`, { status }, {
        onSuccess: () => router.reload()
    })
}

// Update current stop
const updateStop = (stopOrder) => {
    if (!currentTrip.value) return
    router.post(`/conductor/trips/${currentTrip.value.id}/stop`, { stop_order: stopOrder }, {
        onSuccess: () => router.reload()
    })
}

// Manual Check-in
const checkinManual = (bookingId) => {
    router.post(`/conductor/bookings/${bookingId}/checkin`, {}, {
        onSuccess: () => router.reload()
    })
}

// Open Change Seat
const openChangeSeat = (b) => {
    selectedBookingForSeatChange.value = b
    showChangeSeatModal.value = true
}

const submitChangeSeat = () => {
    if (!selectedBookingForSeatChange.value) return
    changeSeatForm.post(`/conductor/bookings/${selectedBookingForSeatChange.value.id}/change-seat`, {
        onSuccess: () => {
            showChangeSeatModal.value = false
            changeSeatForm.reset()
            router.reload()
        }
    })
}

// Submit roadside ticket
const submitRoadside = () => {
    if (!currentTrip.value) return
    roadsideForm.post(`/conductor/trips/${currentTrip.value.id}/roadside-ticket`, {
        onSuccess: () => {
            showRoadsideModal.value = false
            roadsideForm.reset()
            router.reload()
        }
    })
}

// Submit expense
const submitExpense = () => {
    if (!currentTrip.value) return
    expenseForm.post(`/conductor/trips/${currentTrip.value.id}/expenses`, {
        onSuccess: () => {
            showExpenseModal.value = false
            expenseForm.reset()
            router.reload()
        }
    })
}

// Submit incident
const submitIncident = () => {
    if (!currentTrip.value) return
    incidentForm.post(`/conductor/trips/${currentTrip.value.id}/incident`, {
        onSuccess: () => {
            showIncidentModal.value = false
            incidentForm.reset()
            router.reload()
        }
    })
}

// Submit lost & found
const submitLostFound = () => {
    if (!currentTrip.value) return
    lostFoundForm.post(`/conductor/trips/${currentTrip.value.id}/lost-found`, {
        onSuccess: () => {
            showLostFoundModal.value = false
            lostFoundForm.reset()
            router.reload()
        }
    })
}

// Submit trip closing
const submitClosing = () => {
    if (!currentTrip.value) return
    closingForm.post(`/conductor/trips/${currentTrip.value.id}/close`, {
        onSuccess: () => {
            showClosingModal.value = false
            router.reload()
        }
    })
}

// Filtered Bookings for searching
const filteredBookings = computed(() => {
    if (!currentTrip.value || !currentTrip.value.bookings) return []
    if (!searchQuery.value) return currentTrip.value.bookings
    const q = searchQuery.value.toLowerCase()
    return currentTrip.value.bookings.filter(b => 
        b.customer_name.toLowerCase().includes(q) ||
        b.customer_phone.includes(q) ||
        b.booking_code.toLowerCase().includes(q)
    )
})

// Passengers boarding at current stop
const boardingAtCurrentStop = computed(() => {
    if (!currentTrip.value || !currentTrip.value.bookings) return []
    const curOrder = currentTrip.value.current_stop_order || 0
    return currentTrip.value.bookings.filter(b => b.pickup_order === curOrder)
})

// Passengers alighting at current stop
const alightingAtCurrentStop = computed(() => {
    if (!currentTrip.value || !currentTrip.value.bookings) return []
    const curOrder = currentTrip.value.current_stop_order || 0
    return currentTrip.value.bookings.filter(b => b.dropoff_order === curOrder)
})

// Financial stats
const totalRevenue = computed(() => {
    if (!currentTrip.value || !currentTrip.value.bookings) return 0
    return currentTrip.value.bookings.reduce((sum, b) => sum + parseFloat(b.total_amount || 0), 0)
})

const cashCollected = computed(() => {
    if (!currentTrip.value || !currentTrip.value.bookings) return 0
    return currentTrip.value.bookings
        .filter(b => b.payment_method === 'CASH' && b.payment_status === 'PAID')
        .reduce((sum, b) => sum + parseFloat(b.paid_amount || 0), 0)
})

const totalExpenses = computed(() => {
    if (!currentTrip.value || !currentTrip.value.expenses) return 0
    return currentTrip.value.expenses.reduce((sum, e) => sum + parseFloat(e.amount || 0), 0)
})

const openRoadsideForStop = (stopId) => {
    roadsideForm.pickup_stop_id = stopId
    showRoadsideModal.value = true
}

const formatCurrency = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0)
import AiChatModal from '@/Components/AiChatModal.vue'
</script>

<template>
    <Head title="Bảng Điều Khiển Lơ Xe - BusHub Terminal" />

    <div class="min-h-screen bg-white text-slate-900 pb-20">
        <!-- Top App Bar -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 px-4 py-3">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-black text-white text-base shadow-sm shadow-blue-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-lg font-bold text-slate-900 leading-tight">BusHub Điều Phối & Lơ Xe</h1>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                LƠ XE & ĐIỀU HÀNH
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">Xin chào, {{ user?.name || 'Nhân viên' }} ({{ user?.role }})</p>
                    </div>
                </div>

                <div class="flex items-center flex-wrap gap-2">
                    <!-- Nút Quay Lại Admin -->
                    <Link href="/admin" class="px-3.5 py-2 bg-gradient-to-r bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black flex items-center space-x-1.5 shadow-lg shadow-blue-600/40 transition ring-2 ring-blue-500">
                        <span>📊 Admin Portal</span>
                    </Link>

                    <!-- Nút App Tài Xế -->
                    <Link href="/driver" class="px-3 py-2 bg-slate-50 hover:bg-slate-200 text-slate-800 border border-slate-200 rounded-xl text-xs font-bold flex items-center space-x-1.5 transition">
                        <span>👨‍✈️ App Tài xế</span>
                    </Link>

                    <!-- Quét QR -->
                    <Link href="/scanner" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold flex items-center space-x-1.5 shadow-md shadow-indigo-600/30 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <span>Quét QR</span>
                    </Link>

                    <!-- Trang chủ -->
                    <Link href="/" class="px-3 py-2 bg-slate-50 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition">
                        🏠 Trang chủ
                    </Link>
                </div>
            </div>
        </header>

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

            <!-- Trip Selector & Control Bar -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xl">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Chuyến xe đang vận hành</span>
                        <div class="flex items-center space-x-3 mt-1">
                            <select 
                                v-if="trips && trips.length" 
                                :value="currentTrip?.id" 
                                @change="switchTrip($event.target.value)"
                                class="bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-slate-900 font-bold text-lg focus:ring-2 focus:ring-blue-600 focus:outline-none">
                                <option v-for="t in trips" :key="t.id" :value="t.id">
                                    {{ t.trip_code }} | {{ t.route?.name }} ({{ t.departure_time?.slice(11, 16) }}) - Xe {{ t.bus?.license_plate }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Status Buttons -->
                    <div class="flex flex-wrap items-center gap-2" v-if="currentTrip">
                        <span class="px-3 py-1.5 rounded-xl text-xs font-bold uppercase"
                            :class="{
                                'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': currentTrip.status === 'SCHEDULED' || currentTrip.status === 'OPEN_FOR_SALE' || currentTrip.status === 'BOARDING',
                                'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': currentTrip.status === 'IN_TRANSIT',
                                'bg-blue-500/20 text-blue-400 border border-blue-500/30': currentTrip.status === 'ARRIVED',
                                'bg-purple-500/20 text-purple-400 border border-purple-500/30': currentTrip.status === 'CLOSED',
                            }">
                            {{ currentTrip.status }}
                        </span>

                        <button 
                            v-if="currentTrip.status === 'SCHEDULED' || currentTrip.status === 'OPEN_FOR_SALE' || currentTrip.status === 'BOARDING'"
                            @click="updateStatus('IN_TRANSIT')" 
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-emerald-600/30 transition">
                            🚀 Khởi Hành (IN_TRANSIT)
                        </button>

                        <button 
                            v-if="currentTrip.status === 'IN_TRANSIT'"
                            @click="updateStatus('ARRIVED')" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-600/30 transition">
                            🏁 Cập Bến (ARRIVED)
                        </button>

                        <button 
                            v-if="currentTrip.status !== 'CLOSED'"
                            @click="showClosingModal = true" 
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-600/30 transition">
                            🔒 Chốt Chuyến & Đối Soát
                        </button>
                    </div>
                </div>

                <!-- Bus & Driver Brief Info -->
                <div v-if="currentTrip" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 pt-4 border-t border-slate-200/60 text-xs text-slate-700">
                    <div><span class="text-slate-500">Xe:</span> <strong class="text-slate-900">{{ currentTrip.bus?.license_plate }} ({{ currentTrip.bus?.bus_type }})</strong></div>
                    <div><span class="text-slate-500">Tài xế:</span> <strong class="text-slate-900">{{ currentTrip.driver?.name }} ({{ currentTrip.driver?.phone }})</strong></div>
                    <div><span class="text-slate-500">Tổng khách:</span> <strong class="text-slate-900">{{ currentTrip.bookings?.length || 0 }} khách</strong></div>
                    <div><span class="text-slate-500">Tiền mặt giữ:</span> <strong class="text-emerald-400 font-bold">{{ formatCurrency(cashCollected) }}</strong></div>
                </div>
            </div>

            <!-- Route Progress & Current Stop -->
            <div v-if="currentTrip && stops && stops.length" class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xl space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                        <span>📍 TIẾN ĐỘ CHẶNG ĐƯỜNG & VỊ TRÍ HIỆN TẠI</span>
                    </h2>
                    <span class="text-xs text-slate-500">Bấm vào trạm để cập nhật vị trí xe</span>
                </div>

                <!-- Progress Bar / Stop list -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div 
                        v-for="stop in stops" 
                        :key="stop.id"
                        @click="updateStop(stop.stop_order)"
                        class="p-3 rounded-xl border transition cursor-pointer relative flex flex-col justify-between"
                        :class="{
                            'bg-blue-600/20 border-blue-500 ring-2 ring-blue-600 shadow-lg': (currentTrip.current_stop_order || 0) === stop.stop_order,
                            'bg-white/80 border-slate-200 hover:border-slate-500': (currentTrip.current_stop_order || 0) !== stop.stop_order
                        }">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="w-6 h-6 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold"
                                    :class="{'bg-blue-600 text-slate-900': (currentTrip.current_stop_order || 0) === stop.stop_order}">
                                    {{ stop.stop_order }}
                                </span>
                                <span v-if="(currentTrip.current_stop_order || 0) === stop.stop_order" class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-600 text-slate-900 animate-pulse">
                                    ĐANG Ở ĐÂY
                                </span>
                            </div>
                            <div class="font-bold text-sm text-slate-900 mt-2 leading-tight">{{ stop.stop_name }}</div>
                            <div class="text-xs text-slate-500">{{ stop.distance_from_start_km }} km</div>
                        </div>

                        <button 
                            @click.stop="openRoadsideForStop(stop.id)"
                            class="mt-3 w-full py-1 text-[11px] font-semibold bg-slate-50 hover:bg-blue-600 text-slate-800 hover:text-slate-900 rounded border border-slate-300 transition">
                            + Bắt khách ở đây
                        </button>
                    </div>
                </div>
            </div>

            <!-- Current Stop Boarding & Alighting Alerts -->
            <div v-if="currentTrip" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Khách lên -->
                <div class="bg-emerald-950/30 border border-emerald-800/50 rounded-2xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-emerald-400 flex items-center space-x-1.5">
                            <span>🟢 KHÁCH SẮP LÊN TẠI TRẠM NÀY ({{ boardingAtCurrentStop.length }})</span>
                        </h3>
                        <button @click="showRoadsideModal = true" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition">
                            + Đón khách dọc đường
                        </button>
                    </div>
                    <div v-if="boardingAtCurrentStop.length === 0" class="text-xs text-slate-500 py-3 text-center">
                        Chưa có khách lên tại trạm hiện tại
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="b in boardingAtCurrentStop" :key="b.id" class="bg-slate-50/90 p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-900 text-sm">{{ b.customer_name }} ({{ b.customer_phone }})</div>
                                <div class="text-xs text-slate-500">
                                    Ghế: <span class="font-bold text-blue-600">{{ b.booking_seats?.map(s => s.seat_number).join(', ') }}</span> ➔ Xuống tại: <span class="text-slate-900">{{ b.dropoff_stop?.stop_name }}</span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button 
                                    v-if="b.checkin_status !== 'CHECKED_IN'"
                                    @click="checkinManual(b.id)"
                                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition">
                                    Check-in & Thu {{ formatCurrency(b.total_amount - b.paid_amount) }}
                                </button>
                                <span v-else class="px-2 py-1 bg-emerald-500/20 text-emerald-400 rounded text-xs font-bold">
                                    Đã lên xe
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Khách xuống -->
                <div class="bg-rose-950/30 border border-rose-800/50 rounded-2xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-rose-400 flex items-center space-x-1.5">
                            <span>🔴 KHÁCH SẮP XUỐNG TẠI TRẠM NÀY ({{ alightingAtCurrentStop.length }})</span>
                        </h3>
                        <span class="text-xs text-slate-500">Ghế sẽ được giải phóng cho chặng sau</span>
                    </div>
                    <div v-if="alightingAtCurrentStop.length === 0" class="text-xs text-slate-500 py-3 text-center">
                        Chưa có khách xuống tại trạm hiện tại
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="b in alightingAtCurrentStop" :key="b.id" class="bg-slate-50/90 p-3 rounded-xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-900 text-sm">{{ b.customer_name }}</div>
                                <div class="text-xs text-slate-500">
                                    Ghế: <span class="font-bold text-rose-400">{{ b.booking_seats?.map(s => s.seat_number).join(', ') }}</span> (Lên từ: {{ b.pickup_stop?.stop_name }})
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-rose-500/20 text-rose-400 rounded text-xs font-bold">
                                Chuẩn bị xuống
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action 6-Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
                <button @click="showRoadsideModal = true" class="p-3.5 bg-gradient-to-r bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl shadow-lg flex flex-col items-center justify-center space-y-1 transition">
                    <span class="text-xl">➕</span>
                    <span class="text-xs">ĐẶT VÉ DỌC ĐƯỜNG</span>
                </button>

                <Link href="/scanner" class="p-3.5 bg-slate-50 hover:bg-slate-200 border border-slate-200 text-slate-900 font-bold rounded-2xl shadow-lg flex flex-col items-center justify-center space-y-1 transition">
                    <span class="text-xl">📷</span>
                    <span class="text-xs">QUÉT QR CHECK-IN</span>
                </Link>

                <Link :href="currentTrip ? `/trips/${currentTrip.id}` : '#'" class="p-3.5 bg-slate-50 hover:bg-slate-200 border border-slate-200 text-slate-900 font-bold rounded-2xl shadow-lg flex flex-col items-center justify-center space-y-1 transition">
                    <span class="text-xl">💺</span>
                    <span class="text-xs">SƠ ĐỒ GHẾ</span>
                </Link>

                <button @click="showExpenseModal = true" class="p-3.5 bg-slate-50 hover:bg-slate-200 border border-slate-200 text-slate-900 font-bold rounded-2xl shadow-lg flex flex-col items-center justify-center space-y-1 transition">
                    <span class="text-xl">⛽</span>
                    <span class="text-xs">THÊM CHI PHÍ</span>
                </button>

                <button @click="showIncidentModal = true" class="p-3.5 bg-slate-50 hover:bg-slate-200 border border-slate-200 text-slate-900 font-bold rounded-2xl shadow-lg flex flex-col items-center justify-center space-y-1 transition">
                    <span class="text-xl">⚠️</span>
                    <span class="text-xs">BÁO SỰ CỐ</span>
                </button>

                <button @click="showLostFoundModal = true" class="p-3.5 bg-slate-50 hover:bg-slate-200 border border-slate-200 text-slate-900 font-bold rounded-2xl shadow-lg flex flex-col items-center justify-center space-y-1 transition">
                    <span class="text-xl">🎒</span>
                    <span class="text-xs">ĐỒ THẤT LẠC</span>
                </button>
            </div>

            <!-- Passenger List & Search Table -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xl space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Danh Sách Hành Khách Trên Chuyến</h2>
                        <p class="text-xs text-slate-500">Bao gồm cả vé đặt Online, Quầy và Vé bắt Dọc đường</p>
                    </div>
                    <div class="w-full sm:w-72">
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Tìm tên, SĐT, mã vé..." 
                            class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-600" />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700">
                        <thead class="bg-white/60 text-slate-500 uppercase font-semibold border-b border-slate-200">
                            <tr>
                                <th class="p-3">Mã Vé / Nguồn</th>
                                <th class="p-3">Khách Hàng</th>
                                <th class="p-3">Ghế</th>
                                <th class="p-3">Chặng Đường</th>
                                <th class="p-3">Thanh Toán</th>
                                <th class="p-3">Trạng Thái</th>
                                <th class="p-3 text-right">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/60">
                            <tr v-for="b in filteredBookings" :key="b.id" class="hover:bg-slate-200/30 transition">
                                <td class="p-3 font-mono font-bold text-blue-600">
                                    <div>{{ b.booking_code }}</div>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-normal"
                                        :class="{
                                            'bg-blue-500/20 text-blue-300': b.booking_source === 'ONLINE',
                                            'bg-emerald-500/20 text-emerald-300': b.booking_source === 'STAFF_ON_BUS',
                                            'bg-purple-500/20 text-purple-300': b.booking_source === 'ADMIN',
                                        }">
                                        {{ b.booking_source }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-900 text-sm">{{ b.customer_name }}</div>
                                    <div class="text-slate-500">{{ b.customer_phone }}</div>
                                </td>
                                <td class="p-3 font-bold text-amber-400 text-sm">
                                    {{ b.booking_seats?.map(s => s.seat_number).join(', ') }}
                                </td>
                                <td class="p-3">
                                    <span class="text-emerald-400 font-semibold">{{ b.pickup_stop?.stop_name }}</span>
                                    <span class="text-slate-500 mx-1">➔</span>
                                    <span class="text-rose-400 font-semibold">{{ b.dropoff_stop?.stop_name }}</span>
                                </td>
                                <td class="p-3">
                                    <div class="font-bold text-slate-900">{{ formatCurrency(b.total_amount) }}</div>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded font-semibold"
                                        :class="b.payment_status === 'PAID' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-yellow-500/20 text-yellow-400'">
                                        {{ b.payment_status === 'PAID' ? 'Đã thu đủ' : 'Cọc / Chưa trả' }} ({{ b.payment_method }})
                                    </span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded text-xs font-bold"
                                        :class="b.checkin_status === 'CHECKED_IN' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-700 text-slate-700'">
                                        {{ b.checkin_status === 'CHECKED_IN' ? '✅ Đã Check-in' : '⏳ Chưa lên' }}
                                    </span>
                                </td>
                                <td class="p-3 text-right space-x-1.5">
                                    <button 
                                        v-if="b.checkin_status !== 'CHECKED_IN'"
                                        @click="checkinManual(b.id)"
                                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-xs shadow transition">
                                        Check-in
                                    </button>
                                    <button 
                                        @click="openChangeSeat(b)"
                                        class="px-2.5 py-1 bg-slate-700 hover:bg-slate-600 text-slate-800 rounded font-bold text-xs transition">
                                        Đổi Ghế
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL 1: ĐẶT VÉ DỌC ĐƯỜNG -->
        <div v-if="showRoadsideModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-50 border border-slate-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                        <span>🚌 Đón Khách Dọc Đường (Seat Segment)</span>
                    </h3>
                    <button @click="showRoadsideModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitRoadside" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Điểm đón (Lên xe)</label>
                            <select v-model="roadsideForm.pickup_stop_id" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900">
                                <option value="">-- Chọn trạm đón --</option>
                                <option v-for="s in stops" :key="s.id" :value="s.id">
                                    {{ s.stop_order }}. {{ s.stop_name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Điểm trả (Xuống xe)</label>
                            <select v-model="roadsideForm.dropoff_stop_id" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900">
                                <option value="">-- Chọn trạm trả --</option>
                                <option v-for="s in stops" :key="s.id" :value="s.id">
                                    {{ s.stop_order }}. {{ s.stop_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Họ và tên khách</label>
                            <input v-model="roadsideForm.customer_name" required type="text" placeholder="Nguyễn Văn A" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Số điện thoại</label>
                            <input v-model="roadsideForm.customer_phone" required type="text" placeholder="090xxxxxxx" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                                                <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-semibold text-slate-700">Chọn ghế trống trên xe</label>
                                <span class="text-[11px] font-bold" :class="availableEmptySeats.length > 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ availableEmptySeats.length > 0 ? `Còn ${availableEmptySeats.length} ghế trống` : '❌ Hết ghế trống' }}
                                </span>
                            </div>
                            
                            <div v-if="availableEmptySeats.length === 0" class="p-3 bg-rose-500/20 border border-rose-500/40 rounded-xl text-rose-300 text-xs text-center font-bold">
                                ⚠️ Toàn bộ ghế trên xe đã kín chỗ ở chặng này! Không thể đón thêm khách dọc đường.
                            </div>

                            <select v-else v-model="roadsideForm.seat_number" required class="w-full bg-white border border-slate-200 rounded-lg p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-blue-600">
                                <option value="">-- Chọn ghế còn trống ({{ availableEmptySeats.length }} ghế) --</option>
                                <option v-for="s in availableEmptySeats" :key="s.seat_number" :value="s.seat_number">
                                    {{ s.label }} {{ s.floor === 1 ? '🌟 VIP' : '🛏️ Giường Tiêu Chuẩn' }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Hình thức thu tiền</label>
                            <select v-model="roadsideForm.payment_method" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900">
                                <option value="CASH">Tiền mặt (Lơ xe giữ)</option>
                                <option value="TRANSFER">Chuyển khoản QR nhà xe</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex justify-end space-x-3">
                        <button type="button" @click="showRoadsideModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="roadsideForm.processing" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl shadow-lg transition">
                            {{ roadsideForm.processing ? 'Đang tạo...' : 'Xác Nhận Đặt & Thu Tiền' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: ĐỔI GHẾ (SEAT SWAP) -->
        <div v-if="showChangeSeatModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-50 border border-slate-200 rounded-2xl max-w-sm w-full p-5 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="text-base font-bold text-slate-900">💺 Đổi Ghế Cho Khách Hàng</h3>
                    <button @click="showChangeSeatModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <div class="text-xs text-slate-700">
                    <div>Khách: <strong class="text-slate-900">{{ selectedBookingForSeatChange?.customer_name }}</strong></div>
                    <div>Ghế hiện tại: <strong class="text-blue-600">{{ selectedBookingForSeatChange?.booking_seats?.map(s => s.seat_number).join(', ') }}</strong></div>
                </div>

                <form @submit.prevent="submitChangeSeat" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Chọn ghế mới muốn chuyển đến</label>
                        <select v-model="changeSeatForm.new_seat_number" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900 font-bold">
                            <option value="">-- Chọn ghế mới --</option>
                            <option v-for="i in 17" :key="'A' + i" :value="'A' + (i < 10 ? '0' + i : i)">
                                Ghế A{{ i < 10 ? '0' + i : i }} (Tầng 1)
                            </option>
                            <option v-for="i in 17" :key="'B' + i" :value="'B' + (i < 10 ? '0' + i : i)">
                                Ghế B{{ i < 10 ? '0' + i : i }} (Tầng 2)
                            </option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex justify-end space-x-2">
                        <button type="button" @click="showChangeSeatModal = false" class="px-3 py-1.5 bg-slate-700 text-slate-700 rounded-lg font-bold">Hủy</button>
                        <button type="submit" :disabled="changeSeatForm.processing" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg transition">
                            Xác Nhận Đổi Ghế
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: BÁO SỰ CỐ -->
        <div v-if="showIncidentModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-50 border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">⚠️ Báo Cáo Sự Cố Chuyến Xe</h3>
                    <button @click="showIncidentModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitIncident" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Loại sự cố</label>
                        <select v-model="incidentForm.type" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900">
                            <option value="Hỏng xe giữa đường">Hỏng xe giữa đường</option>
                            <option value="Điều hòa hỏng">Điều hòa hỏng / Yếu</option>
                            <option value="Trễ chuyến do tắc đường">Trễ chuyến do tắc đường</option>
                            <option value="Ghế hỏng không ngả được">Ghế hỏng không ngả được</option>
                            <option value="Khách gặp vấn đề sức khỏe">Khách gặp vấn đề sức khỏe</option>
                            <option value="Khác">Sự cố khác</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Mức độ nghiêm trọng</label>
                        <select v-model="incidentForm.severity" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900">
                            <option value="LOW">Thấp (Tiếp tục chạy bình thường)</option>
                            <option value="MEDIUM">Trung bình (Cần hỗ trợ tại bến)</option>
                            <option value="HIGH">Cao (Cần xe cứu hộ / Điều xe thay thế)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Mô tả chi tiết sự cố</label>
                        <textarea v-model="incidentForm.description" required rows="3" placeholder="Mô tả sự việc, vị trí đang đứng..." class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex justify-end space-x-3">
                        <button type="button" @click="showIncidentModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="incidentForm.processing" class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-slate-900 font-bold rounded-xl shadow-lg transition">
                            Gửi Báo Cáo
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: ĐỒ THẤT LẠC (LOST & FOUND) -->
        <div v-if="showLostFoundModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-50 border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">🎒 Ghi Nhận Đồ Thất Lạc (Lost & Found)</h3>
                    <button @click="showLostFoundModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitLostFound" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tên đồ vật / Tài sản</label>
                        <input v-model="lostFoundForm.item_name" required type="text" placeholder="Điện thoại iPhone 15, Balo đen..." class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Vị trí tìm thấy (Số ghế / Hộc đồ)</label>
                        <input v-model="lostFoundForm.seat_number" type="text" placeholder="Ghế A12 hoặc hàng cuối" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Mô tả đặc điểm đồ vật</label>
                        <textarea v-model="lostFoundForm.description" rows="2" placeholder="Ốp lưng màu đỏ, có ví tiền bên trong..." class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex justify-end space-x-3">
                        <button type="button" @click="showLostFoundModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="lostFoundForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition">
                            Lưu Thông Tin
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: THÊM CHI PHÍ -->
        <div v-if="showExpenseModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-50 border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">⛽ Ghi Nhận Chi Phí Chuyến Đi</h3>
                    <button @click="showExpenseModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitExpense" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Loại chi phí</label>
                        <select v-model="expenseForm.expense_type" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900">
                            <option value="Xăng dầu">Xăng / Dầu</option>
                            <option value="Phí cầu đường">Phí cầu đường (BOT)</option>
                            <option value="Bến bãi">Bến bãi / Đỗ xe</option>
                            <option value="Rửa xe / Bảo dưỡng nhẹ">Rửa xe / Bảo dưỡng nhẹ</option>
                            <option value="Ăn uống nhân viên">Ăn uống nhân sự</option>
                            <option value="Khác">Chi phí phát sinh khác</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Số tiền (VNĐ)</label>
                        <input v-model="expenseForm.amount" required type="number" min="1000" placeholder="500000" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Ghi chú / Diễn giải</label>
                        <input v-model="expenseForm.description" required type="text" placeholder="Đổ dầu cây xăng Petrolimex..." class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex justify-end space-x-3">
                        <button type="button" @click="showExpenseModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Đóng</button>
                        <button type="submit" :disabled="expenseForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition">
                            Lưu Chi Phí
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: CHỐT CHUYẾN & ĐỐI SOÁT TIỀN MẶT -->
        <div v-if="showClosingModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-50 border border-slate-200 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-900">🔒 Chốt Chuyến & Đối Soát Doanh Thu</h3>
                    <button @click="showClosingModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <div class="space-y-3 bg-white/80 p-4 rounded-xl text-xs">
                    <div class="flex justify-between"><span class="text-slate-500">Tổng doanh thu vé:</span> <strong class="text-slate-900">{{ formatCurrency(totalRevenue) }}</strong></div>
                    <div class="flex justify-between"><span class="text-slate-500">Tổng chi phí phát sinh:</span> <strong class="text-rose-400">- {{ formatCurrency(totalExpenses) }}</strong></div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 font-bold"><span class="text-slate-700">Lợi nhuận chuyến:</span> <strong class="text-emerald-400 text-sm">{{ formatCurrency(totalRevenue - totalExpenses) }}</strong></div>
                    <div class="flex justify-between border-t border-slate-200 pt-2"><span class="text-slate-500">Tiền mặt Lơ xe phải nộp:</span> <strong class="text-blue-600 text-sm">{{ formatCurrency(cashCollected) }}</strong></div>
                </div>

                <form @submit.prevent="submitClosing" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Số tiền mặt thực tế nộp lại (VNĐ)</label>
                        <input v-model="closingForm.actual_cash_submitted" required type="number" :placeholder="cashCollected" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900 font-bold text-sm" />
                        <span class="text-[11px] text-slate-500 mt-1 block">Hệ thống sẽ tự tính chênh lệch thừa/thiếu nếu số nộp khác số tiền mặt đã thu.</span>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Ghi chú khi chốt chuyến</label>
                        <textarea v-model="closingForm.notes" rows="2" placeholder="Ghi chú về chuyến xe, tình hình khách..." class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-slate-900"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex justify-end space-x-3">
                        <button type="button" @click="showClosingModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="closingForm.processing" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl shadow-lg transition">
                            {{ closingForm.processing ? 'Đang xử lý...' : 'Xác Nhận Chốt Chuyến & Khóa Sổ' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <AiChatModal />
</div>
</template>
