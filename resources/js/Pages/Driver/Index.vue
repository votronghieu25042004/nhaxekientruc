<script setup>
import AiChatModal from '@/Components/AiChatModal.vue';
import { ref, computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'

const props = defineProps({
    drivers: { type: Array, default: () => [] },
    currentDriver: { type: Object, default: () => ({}) },
    selectedTrip: { type: Object, default: null },
    todayTrips: { type: Array, default: () => [] },
    tomorrowTrips: { type: Array, default: () => [] },
    upcomingTrips: { type: Array, default: () => [] },
    historyTrips: { type: Array, default: () => [] },
    allTrips: { type: Array, default: () => [] },
    occupiedSeats: { type: Array, default: () => [] },
    stops: { type: Array, default: () => [] },
    notifications: { type: Array, default: () => [] },
    driverReviews: { type: Array, default: () => [] },
})

// Current Active Tab
const activeTab = ref('today') // 'today', 'seatmap', 'schedule', 'detail', 'reviews', 'profile'
const showNotifModal = ref(false)

// Modals
const showRejectModal = ref(false)
const showChecklistModal = ref(false)
const showIssueModal = ref(false)
const showIncidentModal = ref(false)
const showDelayModal = ref(false)
const showRoadsideModal = ref(false)
const showChangeSeatModal = ref(false)
const showExpenseModal = ref(false)
const showLostFoundModal = ref(false)
const showClosingModal = ref(false)

const activeActionTrip = ref(props.selectedTrip || props.todayTrips[0] || props.allTrips[0] || null)
const selectedBookingForSeatChange = ref(null)

// Roadside Booking Form (Đón khách dọc đường)
const roadsideForm = useForm({
    customer_name: '',
    customer_phone: '',
    pickup_stop_id: '',
    dropoff_stop_id: '',
    seat_number: '',
    payment_method: 'CASH'
})

// Expense Form
const expenseForm = useForm({
    expense_type: 'Xăng dầu',
    amount: '',
    description: ''
})

// Lost & Found Form
const lostFoundForm = useForm({
    item_name: '',
    seat_number: '',
    description: ''
})

// Closing Trip Form
const closingForm = useForm({
    actual_cash_submitted: '',
    notes: ''
})

// Change Seat Form
const changeSeatForm = useForm({
    new_seat_number: ''
})

// Reject Form
const rejectReason = ref('Sức khỏe không đảm bảo (Ốm, sốt)')
const rejectNote = ref('')

// Checklist Form
const defaultChecklistItems = [
    { key: 'tires', label: '1. Áp suất & Độ mòn lốp xe', checked: true },
    { key: 'brakes', label: '2. Hệ thống phanh (thắng)', checked: true },
    { key: 'lights', label: '3. Hệ thống đèn chiếu sáng', checked: true },
    { key: 'ac', label: '4. Điều hòa / Máy lạnh', checked: true },
    { key: 'doors', label: '5. Cửa tự động & Thoát hiểm', checked: true },
    { key: 'mirrors', label: '6. Gương chiếu hậu & Camera', checked: true },
    { key: 'fuel', label: '7. Dầu nhớt & Nhiên liệu đầy đủ', checked: true },
    { key: 'fire_safety', label: '8. Bình PCCC & Búa cứu hộ', checked: true }
]
const checklistItems = ref([...defaultChecklistItems])
const newChecklistName = ref('')
const showAddChecklistInput = ref(false)

const addCustomChecklistItem = () => {
    if (!newChecklistName.value.trim()) return
    checklistItems.value.push({
        key: 'custom_' + Date.now(),
        label: `${checklistItems.value.length + 1}. ${newChecklistName.value.trim()}`,
        checked: true
    })
    newChecklistName.value = ''
    showAddChecklistInput.value = false
}

const allChecked = computed(() => {
    return checklistItems.value.length > 0 && checklistItems.value.every(item => item.checked)
})

// Vehicle Issue Form
const issueType = ref('Điều hòa làm lạnh yếu / không mát')
const customIssueType = ref('')
const issueSeverity = ref('MEDIUM')
const issueDesc = ref('')

// Incident Form
const incidentType = ref('BREAKDOWN')
const customIncidentType = ref('')
const incidentSeverity = ref('HIGH')
const incidentLocation = ref('')
const incidentDesc = ref('')

// Delay Form
const delayMinutes = ref(30)
const delayReason = ref('Ùn tắc giao thông giờ cao điểm')

// Schedule Filter
const scheduleFilter = ref('ALL') // 'ALL', 'WAITING', 'CONFIRMED', 'RUNNING', 'COMPLETED'

// Helper switch driver
const switchDriver = (id) => {
    router.get('/driver', { driver_id: id }, { preserveScroll: true })
}

const selectTripForDetail = (trip) => {
    activeActionTrip.value = trip
    activeTab.value = 'detail'
}

const selectTripForSeatmap = (trip) => {
    activeActionTrip.value = trip
    activeTab.value = 'seatmap'
}

// Tính toán danh sách ghế thực sự CÒN TRỐNG trên chuyến xe (để đón khách dọc đường)
const availableEmptySeats = computed(() => {
    const trip = activeActionTrip.value || props.selectedTrip
    if (!trip) return []
    
    const allSeats = []
    for (let i = 1; i <= 17; i++) {
        const num = i < 10 ? '0' + i : '' + i
        allSeats.push({ seat_number: 'A' + num, floor: 1, label: `Ghế A${num} (Tầng 1)` })
    }
    for (let i = 1; i <= 17; i++) {
        const num = i < 10 ? '0' + i : '' + i
        allSeats.push({ seat_number: 'B' + num, floor: 2, label: `Ghế B${num} (Tầng 2)` })
    }

    const occupiedList = new Set()
    if (props.occupiedSeats && Array.isArray(props.occupiedSeats)) {
        props.occupiedSeats.forEach(s => occupiedList.add(s))
    }
    if (trip.bookings && Array.isArray(trip.bookings)) {
        trip.bookings.forEach(b => {
            if (b.status !== 'CANCELLED' && b.booking_seats) {
                b.booking_seats.forEach(bs => occupiedList.add(bs.seat_number))
            }
        })
    }

    return allSeats.filter(s => !occupiedList.has(s.seat_number))
})

// Check-in Manual
const manualCheckin = (bookingId) => {
    router.post(`/conductor/checkin/${bookingId}`, {}, {
        preserveScroll: true,
        onSuccess: () => alert('🟢 Check-in vé cho khách thành công!')
    })
}

// Change Seat
const openChangeSeat = (b) => {
    selectedBookingForSeatChange.value = b
    showChangeSeatModal.value = true
}

const submitChangeSeat = () => {
    if (!selectedBookingForSeatChange.value) return
    changeSeatForm.post(`/conductor/bookings/${selectedBookingForSeatChange.value.id}/change-seat`, {
        preserveScroll: true,
        onSuccess: () => {
            showChangeSeatModal.value = false
            changeSeatForm.reset()
            alert('💺 Đã chuyển đổi số ghế thành công!')
        }
    })
}

// Submit roadside ticket
const submitRoadside = () => {
    const trip = activeActionTrip.value || props.selectedTrip
    if (!trip) return
    roadsideForm.post(`/conductor/trips/${trip.id}/roadside-ticket`, {
        preserveScroll: true,
        onSuccess: () => {
            showRoadsideModal.value = false
            roadsideForm.reset()
            alert('🚌 Đã bắt vé đón khách dọc đường & Thu 100% tiền thành công!')
        }
    })
}

// Submit expense
const submitExpense = () => {
    const trip = activeActionTrip.value || props.selectedTrip
    if (!trip) return
    expenseForm.post(`/conductor/trips/${trip.id}/expense`, {
        preserveScroll: true,
        onSuccess: () => {
            showExpenseModal.value = false
            expenseForm.reset()
            alert('💰 Đã lưu chi phí vận hành chuyến xe!')
        }
    })
}

// Submit lost & found
const submitLostFound = () => {
    const trip = activeActionTrip.value || props.selectedTrip
    if (!trip) return
    lostFoundForm.post(`/conductor/trips/${trip.id}/lost-found`, {
        preserveScroll: true,
        onSuccess: () => {
            showLostFoundModal.value = false
            lostFoundForm.reset()
            alert('🎒 Đã ghi nhận đồ thất lạc để trả lại hành khách!')
        }
    })
}

// Submit closing

// Tài xế cập nhật trạm dừng dọc đường (Đến trạm hoặc Bỏ qua)
const updateStopStatus = (trip, stop, action) => {
    const actionLabel = action === 'ARRIVE' ? `Xác nhận xe đã ĐẾN TRẠM "${stop.stop_name}"?` : `Xác nhận BỎ QUA trạm "${stop.stop_name}" (Không có khách đón/trả)?`
    if (!confirm(actionLabel)) return

    router.post(`/driver/trips/${trip.id}/stop`, {
        stop_order: stop.stop_order,
        stop_name: stop.stop_name,
        action: action
    }, {
        preserveScroll: true,
        onSuccess: () => {
            alert(action === 'ARRIVE' ? `🟢 Đã ghi nhận xe dừng tại ${stop.stop_name}!` : `⏭️ Đã ghi nhận bỏ qua trạm ${stop.stop_name}!`)
        }
    })
}

const submitClosing = () => {
    const trip = activeActionTrip.value || props.selectedTrip
    if (!trip) return
    closingForm.post(`/conductor/trips/${trip.id}/close`, {
        preserveScroll: true,
        onSuccess: () => {
            showClosingModal.value = false
            closingForm.reset()
            alert('🏁 Chốt chuyến và bàn giao doanh thu thành công!')
        }
    })
}

// Driver Primary Actions
const acceptTrip = (trip) => {
    if (!confirm(`Xác nhận nhận chuyến #${trip.trip_code} (${trip.route?.name || ''})?`)) return
    router.post(`/driver/trips/${trip.id}/accept`, {}, {
        preserveScroll: true,
        onSuccess: () => alert('🟢 Đã nhận chuyến thành công!')
    })
}

const submitReject = () => {
    if (!activeActionTrip.value) return
    router.post(`/driver/trips/${activeActionTrip.value.id}/reject`, {
        reason: rejectReason.value,
        note: rejectNote.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false
            alert('Đã gửi thông báo từ chối chuyến đến Ban điều hành!')
        }
    })
}

const submitChecklist = () => {
    if (!activeActionTrip.value) return
    router.post(`/driver/trips/${activeActionTrip.value.id}/verify-vehicle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showChecklistModal.value = false
            alert('🟢 Đã xác nhận xe đủ điều kiện an toàn & Sẵn sàng khởi hành!')
        }
    })
}

const submitVehicleIssue = () => {
    if (!activeActionTrip.value) return
    const finalType = issueType.value === 'CUSTOM' ? (customIssueType.value || 'Lỗi xe khác') : issueType.value
    router.post(`/driver/trips/${activeActionTrip.value.id}/report-vehicle-issue`, {
        issue_type: finalType,
        severity: issueSeverity.value,
        description: issueDesc.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showIssueModal.value = false
            issueDesc.value = ''
            customIssueType.value = ''
            alert('Đã gửi báo cáo lỗi xe tới Kỹ thuật & Quản trị!')
        }
    })
}

const submitIncident = () => {
    if (!activeActionTrip.value) return
    const finalType = incidentType.value === 'OTHER' && customIncidentType.value ? customIncidentType.value : incidentType.value
    router.post(`/driver/trips/${activeActionTrip.value.id}/report-incident`, {
        incident_type: finalType,
        severity: incidentSeverity.value,
        location: incidentLocation.value,
        description: incidentDesc.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showIncidentModal.value = false
            incidentDesc.value = ''
            incidentLocation.value = ''
            customIncidentType.value = ''
            alert('🚨 Đã phát tín hiệu cảnh báo khẩn cấp tới Tổng đài điều hành BusHub!')
        }
    })
}

const submitDelay = () => {
    if (!activeActionTrip.value) return
    router.post(`/driver/trips/${activeActionTrip.value.id}/report-delay`, {
        delay_minutes: delayMinutes.value,
        reason: delayReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showDelayModal.value = false
            alert(`Đã ghi nhận báo trễ ${delayMinutes.value} phút!`)
        }
    })
}

const startTrip = (trip) => {
    if (!confirm(`Xác nhận cho xe #${trip.bus?.plate_number || ''} KHỞI HÀNH lăn bánh rời bến?`)) return
    router.post(`/driver/trips/${trip.id}/start`, {}, {
        preserveScroll: true,
        onSuccess: () => alert('🚀 Chuyến xe đã chính thức khởi hành!')
    })
}

const finishTrip = (trip) => {
    if (!confirm(`Xác nhận chuyến xe #${trip.trip_code} ĐÃ ĐẾN BẾN AN TOÀN và kết thúc?`)) return
    router.post(`/driver/trips/${trip.id}/finish`, {}, {
        preserveScroll: true,
        onSuccess: () => alert('🏁 Chúc mừng bạn đã hoàn thành chuyến xe an toàn!')
    })
}

const formatPrice = (val) => new Intl.NumberFormat('vi-VN').format(val || 0) + ' đ'

const formatTime = (dateStr) => {
    if (!dateStr) return '--:--'
    const d = new Date(dateStr)
    return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

// Lọc lịch chuyến
const filteredScheduleTrips = computed(() => {
    let list = props.allTrips
    if (scheduleFilter.value === 'WAITING') {
        return list.filter(t => t.status === 'OPEN_FOR_SALE' || !t.accepted_at)
    } else if (scheduleFilter.value === 'CONFIRMED') {
        return list.filter(t => t.status === 'DRIVER_CONFIRMED' || t.status === 'READY')
    } else if (scheduleFilter.value === 'RUNNING') {
        return list.filter(t => t.status === 'IN_TRANSIT' || t.status === 'BOARDING')
    } else if (scheduleFilter.value === 'COMPLETED') {
        return list.filter(t => t.status === 'COMPLETED' || t.status === 'ARRIVED')
    }
    return list
})
</script>

<template>
    <Head title="App Tài Xế & Vận Hành - BusHub Terminal" />

    <div class="min-h-screen bg-[#F8FAFC] text-slate-800 font-sans pb-24 md:pb-12 selection:bg-blue-600 selection:text-slate-900">
        <!-- 1. Top Bar Navigation -->
        <header class="bg-white/95 backdrop-blur border-b border-slate-200 shadow-xs sticky top-0 z-40 px-4 py-3 shadow-xl">
            <div class="max-w-6xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Driver Info & Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-amber-500 to-blue-600 flex items-center justify-center font-black text-slate-900 text-xl shadow-lg shadow-blue-600/30 shrink-0 ring-2 ring-blue-500/50">
                        👨‍✈️
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-base font-black text-slate-900 leading-tight">BUSHUB BÁC TÀI</h1>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                LÁI XE & VẬN HÀNH
                            </span>
                        </div>
                        <div class="flex items-center space-x-2 text-xs text-slate-400">
                            <span class="font-bold text-amber-600">{{ currentDriver?.name || 'Tài xế' }}</span>
                            <span>•</span>
                            <span class="text-[11px] bg-slate-100 px-2 py-0.5 rounded text-slate-700 font-mono">GPLX: {{ currentDriver?.license_number || 'Hạng E' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right Quick Links & Notifications -->
                <div class="flex items-center flex-wrap gap-2">
                    <!-- Switch Driver Dropdown -->
                    <div class="relative">
                        <select 
                            :value="currentDriver?.id" 
                            @change="switchDriver($event.target.value)"
                            class="bg-slate-100 text-xs font-bold text-slate-800 border border-slate-300 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-blue-600 outline-none cursor-pointer">
                            <option v-for="d in drivers" :key="d.id" :value="d.id">
                                👨‍✈️ {{ d.name }} (ID: #{{ d.id }})
                            </option>
                        </select>
                    </div>

                    <!-- Notification Button -->
                    <button 
                        @click="showNotifModal = !showNotifModal"
                        class="relative p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition border border-slate-300">
                        🔔
                        <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-[10px] font-black text-slate-900 flex items-center justify-center">
                            {{ notifications.length }}
                        </span>
                    </button>

                    <!-- NÚT QUAY LẠI ADMIN -->
                    <Link href="/admin" class="px-3.5 py-1.5 bg-gradient-to-r bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black flex items-center space-x-1.5 shadow-lg shadow-blue-600/30 ring-2 ring-blue-500 transition">
                        <span>📊 Quay lại Admin</span>
                    </Link>

                    <!-- NÚT APP LƠ XE -->
                    <Link href="/conductor" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-slate-900 rounded-xl text-xs font-bold flex items-center space-x-1 shadow-md transition">
                        <span>📱 App Lơ xe</span>
                    </Link>

                    <!-- Trang chủ -->
                    <Link href="/" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-xl text-xs font-bold transition">
                        🏠 Trang chủ
                    </Link>
                </div>
            </div>
        </header>

        <!-- Notification Popover Modal -->
        <div v-if="showNotifModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-start justify-center p-4 pt-16" @click.self="showNotifModal = false">
            <div class="bg-white border border-slate-300 rounded-2xl w-full max-w-md shadow-2xl p-4 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="font-black text-slate-900 flex items-center space-x-2">
                        <span>🔔 Thông Báo Điều Hành</span>
                        <span class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full font-bold">{{ notifications.length }}</span>
                    </h3>
                    <button @click="showNotifModal = false" class="text-slate-400 hover:text-slate-900 text-lg">✕</button>
                </div>
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    <div v-for="notif in notifications" :key="notif.id" class="p-3 rounded-xl bg-slate-100/80 border border-slate-300/80 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-blue-600">🚍 {{ notif.title }}</span>
                            <span class="text-[10px] text-slate-400">{{ notif.time }}</span>
                        </div>
                        <p class="text-xs text-slate-700 leading-relaxed">{{ notif.content }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Body Container -->
        <main class="max-w-6xl mx-auto px-4 pt-4 space-y-5">
            <!-- 2. Navigation Tabs (App Style) -->
            <div class="flex items-center space-x-2 overflow-x-auto pb-1 no-scrollbar">
                <button 
                    @click="activeTab = 'today'"
                    :class="activeTab === 'today' ? 'bg-blue-600 text-white ring-2 ring-blue-500 shadow-lg shadow-blue-600/30 font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs shrink-0 flex items-center space-x-1.5 transition">
                    <span>🏠</span>
                    <span>Hôm nay ({{ todayTrips.length }})</span>
                </button>
                <button 
                    @click="activeTab = 'seatmap'"
                    :class="activeTab === 'seatmap' ? 'bg-blue-600 text-white ring-2 ring-blue-500 shadow-lg shadow-blue-600/30 font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs shrink-0 flex items-center space-x-1.5 transition">
                    <span>💺</span>
                    <span>Sơ đồ ghế & Đón khách</span>
                </button>
                <button 
                    @click="activeTab = 'schedule'"
                    :class="activeTab === 'schedule' ? 'bg-blue-600 text-white ring-2 ring-blue-500 shadow-lg shadow-blue-600/30 font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs shrink-0 flex items-center space-x-1.5 transition">
                    <span>📅</span>
                    <span>Lịch 7 ngày</span>
                </button>
                <button 
                    @click="activeTab = 'detail'"
                    :class="activeTab === 'detail' ? 'bg-blue-600 text-white ring-2 ring-blue-500 shadow-lg shadow-blue-600/30 font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs shrink-0 flex items-center space-x-1.5 transition">
                    <span>🔍</span>
                    <span>Chi tiết chuyến</span>
                </button>
                <button 
                    @click="activeTab = 'reviews'"
                    :class="activeTab === 'reviews' ? 'bg-blue-600 text-white ring-2 ring-blue-500 shadow-lg shadow-blue-600/30 font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs shrink-0 flex items-center space-x-1.5 transition">
                    <span>⭐</span>
                    <span>Khách đánh giá ({{ driverReviews.length }})</span>
                </button>
                <button 
                    @click="activeTab = 'profile'"
                    :class="activeTab === 'profile' ? 'bg-blue-600 text-white ring-2 ring-blue-500 shadow-lg shadow-blue-600/30 font-black' : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'"
                    class="px-4 py-2.5 rounded-xl font-bold text-xs shrink-0 flex items-center space-x-1.5 transition">
                    <span>👤</span>
                    <span>Hồ sơ tài xế</span>
                </button>
            </div>

            <!-- TAB 1: HÔM NAY (Hôm nay tôi có chuyến không?) -->
            <section v-if="activeTab === 'today'" class="space-y-4">
                <!-- Status Banner Card -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase font-extrabold tracking-wider text-blue-600">
                            HÔM NAY – {{ new Date().toLocaleDateString('vi-VN') }}
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">
                            {{ todayTrips.length > 0 ? `Bạn có ${todayTrips.length} chuyến xe được giao` : 'Hôm nay bạn không có lịch chạy' }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">
                            Tài xế có thể kiểm tra an toàn xe, đón khách bắt xe dọc đường và check-in như Lơ xe.
                        </p>
                    </div>

                    <!-- Quick stats badge -->
                    <div class="flex items-center space-x-3 bg-slate-50 px-4 py-2.5 rounded-xl border border-slate-200 shrink-0">
                        <div class="text-center">
                            <div class="text-lg font-black text-blue-600">{{ todayTrips.length }}</div>
                            <div class="text-[10px] text-slate-400 uppercase font-bold">Hôm nay</div>
                        </div>
                        <div class="w-px h-8 bg-slate-100"></div>
                        <div class="text-center">
                            <div class="text-lg font-black text-emerald-400">{{ tomorrowTrips.length }}</div>
                            <div class="text-[10px] text-slate-400 uppercase font-bold">Ngày mai</div>
                        </div>
                        <div class="w-px h-8 bg-slate-100"></div>
                        <div class="text-center">
                            <div class="text-lg font-black text-amber-600">{{ currentDriver?.avg_rating || '5.0' }}</div>
                            <div class="text-[10px] text-slate-400 uppercase font-bold">Đánh giá</div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách chuyến xe hôm nay -->
                <div v-if="todayTrips.length > 0" class="space-y-4">
                    <div 
                        v-for="trip in todayTrips" 
                        :key="trip.id"
                        class="bg-white border border-slate-200 shadow-xs rounded-2xl p-5 shadow-xl hover:border-slate-300 transition relative overflow-hidden">
                        
                        <!-- Top status badge -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-200">
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-1 rounded-lg bg-blue-600/20 text-blue-600 border border-blue-500/30 text-xs font-black">
                                    #{{ trip.trip_code || ('TRIP-' + trip.id) }}
                                </span>
                                <span class="text-xs font-bold text-slate-400">
                                    🕒 Khởi hành: <strong class="text-slate-900 text-sm font-black">{{ formatTime(trip.departure_time) }}</strong>
                                </span>
                            </div>

                            <!-- State Badge -->
                            <div>
                                <span v-if="trip.status === 'IN_TRANSIT'" class="px-3 py-1 rounded-full text-xs font-black bg-blue-500/20 text-blue-400 border border-blue-500/40 animate-pulse">
                                    🚀 ĐANG CHẠY TRÊN ĐƯỜNG
                                </span>
                                <span v-else-if="trip.status === 'READY'" class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                    🟢 XE ĐÃ SẴN SÀNG
                                </span>
                                <span v-else-if="trip.status === 'DRIVER_CONFIRMED'" class="px-3 py-1 rounded-full text-xs font-black bg-blue-600/20 text-amber-600 border border-blue-600/40">
                                    🟡 ĐÃ XÁC NHẬN NHẬN CHUYẾN
                                </span>
                                <span v-else-if="trip.status === 'COMPLETED' || trip.status === 'ARRIVED'" class="px-3 py-1 rounded-full text-xs font-black bg-purple-500/20 text-purple-400 border border-purple-500/40">
                                    🏁 ĐÃ HOÀN THÀNH
                                </span>
                                <span v-else class="px-3 py-1 rounded-full text-xs font-black bg-rose-500/20 text-rose-400 border border-rose-500/40">
                                    🔔 CHỜ TÀI XẾ NHẬN CHUYẾN
                                </span>
                            </div>
                        </div>

                        <!-- Route & Bus & Conductor Details -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 py-4">
                            <!-- Route -->
                            <div class="space-y-1">
                                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tuyến đường</div>
                                <div class="text-base font-black text-slate-900 flex items-center space-x-1.5">
                                    <span>🛣️</span>
                                    <span>{{ trip.route?.name || 'Đà Nẵng → Hội An' }}</span>
                                </div>
                                <div class="text-xs text-slate-400 flex items-center space-x-1">
                                    <span>Trạm:</span>
                                    <span class="text-slate-700 font-semibold">{{ trip.route?.stops?.length || 5 }} điểm dừng</span>
                                </div>
                            </div>

                            <!-- Bus Info -->
                            <div class="space-y-1">
                                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Xe được giao</div>
                                <div class="text-base font-black text-amber-600 flex items-center space-x-1.5 font-mono">
                                    <span>🚍</span>
                                    <span>{{ trip.bus?.plate_number || '43B-012.34' }}</span>
                                </div>
                                <div class="text-xs text-slate-700">
                                    {{ trip.bus?.bus_type || 'Giường Nằm Cao Cấp' }} • {{ trip.bus?.total_seats || 34 }} chỗ
                                </div>
                            </div>

                            <!-- Conductor Info -->
                            <div class="space-y-1">
                                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Lơ xe đi cùng</div>
                                <div class="text-base font-black text-emerald-400 flex items-center space-x-1.5">
                                    <span>👥</span>
                                    <span>{{ trip.conductor?.name || 'Trần Văn Lơ Xe' }}</span>
                                </div>
                                <div class="text-xs text-slate-400 flex items-center space-x-2">
                                    <span>📞 SĐT: {{ trip.conductor?.phone || '0905.123.456' }}</span>
                                    <a :href="'tel:' + (trip.conductor?.phone || '0905123456')" class="text-blue-600 hover:underline font-bold">Gọi ngay</a>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons Bar for Driver -->
                        <div class="pt-3 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2.5">
                            <!-- Left: Details & Seat Map & Inspection buttons -->
                            <div class="flex items-center flex-wrap gap-2">
                                <button 
                                    @click="selectTripForSeatmap(trip)" 
                                    class="px-3.5 py-2 rounded-xl bg-blue-600/20 hover:bg-blue-600/30 text-xs font-black text-blue-600 border border-blue-500/40 transition flex items-center space-x-1.5">
                                    <span>💺 Sơ Đồ Ghế & Đón Khách</span>
                                </button>

                                <button 
                                    @click="selectTripForDetail(trip)" 
                                    class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-800 border border-slate-300 transition flex items-center space-x-1.5">
                                    <span>🔍 Xem Tuyến</span>
                                </button>

                                <button 
                                    @click="activeActionTrip = trip; showChecklistModal = true" 
                                    class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-amber-300 border border-blue-600/30 transition flex items-center space-x-1.5">
                                    <span>📋 Kiểm Tra Xe ({{ checklistItems.length }} Mục)</span>
                                </button>

                                <button 
                                    @click="activeActionTrip = trip; showIssueModal = true" 
                                    class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-rose-300 border border-rose-500/30 transition flex items-center space-x-1.5">
                                    <span>⚠️ Báo Lỗi Xe</span>
                                </button>
                            </div>

                            <!-- Right: Primary Workflow Buttons -->
                            <div class="flex items-center flex-wrap gap-2">
                                <!-- Chờ nhận chuyến -->
                                <template v-if="trip.status === 'OPEN_FOR_SALE' || (!trip.accepted_at && trip.status !== 'IN_TRANSIT' && trip.status !== 'COMPLETED')">
                                    <button 
                                        @click="activeActionTrip = trip; showRejectModal = true" 
                                        class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-rose-900/40 text-xs font-bold text-rose-400 border border-rose-500/30 transition">
                                        ✕ Báo Không Thể Nhận
                                    </button>
                                    <button 
                                        @click="acceptTrip(trip)" 
                                        class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-600 text-xs font-black text-slate-900 shadow-lg shadow-blue-600/30 transition flex items-center space-x-1.5">
                                        <span>✓ NHẬN CHUYẾN</span>
                                    </button>
                                    <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>

                                <!-- Đã xác nhận -> Sẵn sàng -->
                                <template v-else-if="trip.status === 'DRIVER_CONFIRMED'">
                                    <button 
                                        @click="activeActionTrip = trip; showChecklistModal = true" 
                                        class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-blue-600 text-xs font-black text-slate-900 shadow-lg shadow-amber-600/30 transition flex items-center space-x-1.5">
                                        <span>✓ XÁC NHẬN SẴN SÀNG</span>
                                    </button>
                                    <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>

                                <!-- Sẵn sàng -> Khởi hành -->
                                <template v-else-if="trip.status === 'READY'">
                                    <button 
                                        @click="startTrip(trip)" 
                                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-black text-slate-900 shadow-lg shadow-emerald-600/40 ring-2 ring-emerald-400 transition flex items-center space-x-1.5 animate-pulse">
                                        <span>🚀 KHỞI HÀNH (LĂN BÁNH)</span>
                                    </button>
                                    <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>

                                <!-- Đang chạy -> Sự cố / Báo trễ / Hoàn thành -->
                                <template v-else-if="trip.status === 'IN_TRANSIT'">
                                    <button 
                                        @click="activeActionTrip = trip; showDelayModal = true" 
                                        class="px-3.5 py-2 rounded-xl bg-amber-600/20 hover:bg-blue-700/40 text-xs font-bold text-amber-300 border border-blue-600/40 transition">
                                        ⏰ Báo Trễ
                                    </button>
                                    <button 
                                        @click="activeActionTrip = trip; showIncidentModal = true" 
                                        class="px-3.5 py-2 rounded-xl bg-rose-600/20 hover:bg-rose-600/40 text-xs font-bold text-rose-400 border border-rose-500/40 transition">
                                        🚨 Báo Sự Cố
                                    </button>
                                    <button 
                                        @click="finishTrip(trip)" 
                                        class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-xs font-black text-slate-900 shadow-lg shadow-purple-600/40 transition flex items-center space-x-1.5">
                                        <span>🏁 ĐÃ ĐẾN BẾN (KẾT THÚC)</span>
                                    </button>
                                    <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>

                                <!-- Hoàn thành -->
                                <template v-else-if="trip.status === 'COMPLETED' || trip.status === 'ARRIVED'">
                                    <span class="text-xs font-bold text-slate-400 italic">✓ Chuyến đã kết thúc an toàn</span>
                                    <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state if no trips today -->
                <div v-else class="text-center py-12 bg-white/60 rounded-2xl border border-slate-200 p-8 space-y-3">
                    <div class="text-5xl">🌴</div>
                    <h3 class="text-lg font-black text-slate-900">Bạn không có chuyến nào hôm nay</h3>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Hãy kiểm tra tab "Lịch 7 ngày" để xem trước các chuyến sắp tới được phân công nhé.
                    </p>
                    <button @click="activeTab = 'schedule'" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-xs font-bold text-blue-600 rounded-xl border border-slate-300">
                        Xem lịch ngày mai & 7 ngày tới →
                    </button>
                </div>
            </section>

            <!-- TAB 2: SƠ ĐỒ GHẾ & ĐÓN KHÁCH DỌC ĐƯỜNG (NGHIỆP VỤ LƠ XE DÀNH CHO TÀI XẾ) -->
            <section v-else-if="activeTab === 'seatmap'" class="space-y-5">
                <!-- Top Quick Actions Bar for Conductor Ops -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 flex flex-wrap items-center justify-between gap-3 shadow-xl">
                    <div>
                        <span class="text-[11px] font-black text-blue-600 uppercase font-mono">QUẢN LÝ HÀNH KHÁCH & VẬN HÀNH</span>
                        <h2 class="text-lg font-black text-slate-900">Chuyến #{{ activeActionTrip?.trip_code || ('TRIP-' + activeActionTrip?.id) }} ({{ activeActionTrip?.route?.name }})</h2>
                    </div>

                    <div class="flex items-center flex-wrap gap-2">
                        <!-- Nút Bắt xe đón khách dọc đường -->
                        <button 
                            @click="showRoadsideModal = true"
                            class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-slate-900 rounded-xl text-xs font-black shadow-lg shadow-emerald-600/30 flex items-center space-x-1.5 transition">
                            <span>🚌 + ĐÓN KHÁCH DỌC ĐƯỜNG</span>
                        </button>

                        <!-- Khai báo chi phí -->
                        <button 
                            @click="showExpenseModal = true"
                            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 rounded-xl text-xs font-bold flex items-center space-x-1.5 transition">
                            <span>💰 Khai Báo Chi Phí</span>
                        </button>

                        <!-- Đồ thất lạc -->
                        <button 
                            @click="showLostFoundModal = true"
                            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 rounded-xl text-xs font-bold flex items-center space-x-1.5 transition">
                            <span>🎒 Đồ Thất Lạc</span>
                        </button>

                        <!-- Chốt chuyến -->
                        <button 
                            @click="showClosingModal = true"
                            class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-amber-300 border border-blue-600/40 rounded-xl text-xs font-bold flex items-center space-x-1.5 transition">
                            <span>🏁 Chốt Chuyến</span>
                        </button>
                    </div>
                </div>

                <!-- 2-Floor Seat Map Layout -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Floor 1: VIP -->
                    <div class="bg-white border border-slate-200 shadow-xs rounded-2xl p-5 shadow-xl space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <span class="font-black text-amber-600 text-sm flex items-center space-x-2">
                                <span>💺 TẦNG 1: GHẾ / GIƯỜNG VIP (DƯỚI)</span>
                            </span>
                            <span class="text-xs bg-slate-100 px-2 py-0.5 rounded text-slate-400 font-mono">17 Ghế</span>
                        </div>

                        <div class="grid grid-cols-3 gap-2.5 pt-2">
                            <div 
                                v-for="i in 17" 
                                :key="'A' + i"
                                class="p-3 rounded-xl border text-center transition flex flex-col items-center justify-center space-y-1 relative"
                                :class="{
                                    'bg-blue-600/20 border-blue-500/40 text-blue-600': occupiedSeats.includes('A' + (i < 10 ? '0' + i : i)),
                                    'bg-slate-50/80 border-slate-200 text-slate-400 hover:border-slate-600': !occupiedSeats.includes('A' + (i < 10 ? '0' + i : i))
                                }">
                                <span class="text-xs font-mono font-black">A{{ i < 10 ? '0' + i : i }}</span>
                                <span class="text-[10px] font-bold" :class="occupiedSeats.includes('A' + (i < 10 ? '0' + i : i)) ? 'text-blue-300' : 'text-emerald-400'">
                                    {{ occupiedSeats.includes('A' + (i < 10 ? '0' + i : i)) ? 'Đã có khách' : 'Trống' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Floor 2: Standard -->
                    <div class="bg-white border border-slate-200 shadow-xs rounded-2xl p-5 shadow-xl space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                            <span class="font-black text-cyan-400 text-sm flex items-center space-x-2">
                                <span>🛏️ TẦNG 2: GIƯỜNG TIÊU CHUẨN (TRÊN)</span>
                            </span>
                            <span class="text-xs bg-slate-100 px-2 py-0.5 rounded text-slate-400 font-mono">17 Ghế</span>
                        </div>

                        <div class="grid grid-cols-3 gap-2.5 pt-2">
                            <div 
                                v-for="i in 17" 
                                :key="'B' + i"
                                class="p-3 rounded-xl border text-center transition flex flex-col items-center justify-center space-y-1 relative"
                                :class="{
                                    'bg-blue-600/20 border-blue-500/40 text-blue-600': occupiedSeats.includes('B' + (i < 10 ? '0' + i : i)),
                                    'bg-slate-50/80 border-slate-200 text-slate-400 hover:border-slate-600': !occupiedSeats.includes('B' + (i < 10 ? '0' + i : i))
                                }">
                                <span class="text-xs font-mono font-black">B{{ i < 10 ? '0' + i : i }}</span>
                                <span class="text-[10px] font-bold" :class="occupiedSeats.includes('B' + (i < 10 ? '0' + i : i)) ? 'text-blue-300' : 'text-emerald-400'">
                                    {{ occupiedSeats.includes('B' + (i < 10 ? '0' + i : i)) ? 'Đã có khách' : 'Trống' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Passenger Manifest Table & Check-in / Change Seat -->
                <div class="bg-white border border-slate-200 shadow-xs rounded-2xl p-5 shadow-xl space-y-3">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                        <h3 class="font-black text-slate-900 text-sm uppercase tracking-wider flex items-center space-x-2">
                            <span>🎫 Danh Sách Hành Khách & Thao Tác Check-In</span>
                        </h3>
                        <span class="text-xs text-slate-400">Tổng: {{ activeActionTrip?.bookings?.length || 0 }} khách</span>
                    </div>

                    <div v-if="activeActionTrip?.bookings && activeActionTrip.bookings.length > 0" class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50/80 text-slate-400 uppercase text-[10px] border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Khách Hàng</th>
                                    <th class="p-3">Ghế</th>
                                    <th class="p-3">Đón / Trả</th>
                                    <th class="p-3">Tiền Vé</th>
                                    <th class="p-3">Trạng Thái</th>
                                    <th class="p-3 text-right">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr v-for="b in activeActionTrip.bookings" :key="b.id" class="hover:bg-slate-100/40">
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ b.customer_name }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ b.customer_phone }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="font-mono font-black text-amber-600 bg-blue-600/10 px-2 py-0.5 rounded border border-blue-600/20">
                                            {{ b.booking_seats?.map(s => s.seat_number).join(', ') || 'A01' }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-slate-700 font-semibold">{{ b.pickup_stop?.stop_name || 'Đà Nẵng' }}</div>
                                        <div class="text-[10px] text-slate-500">➔ {{ b.dropoff_stop?.stop_name || 'Hội An' }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="font-bold text-emerald-400">{{ formatPrice(b.total_price) }}</span>
                                    </td>
                                    <td class="p-3">
                                        <span v-if="b.checkin_status === 'CHECKED_IN'" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                            ✓ Đã lên xe
                                        </span>
                                        <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-600/20 text-amber-600 border border-blue-600/40">
                                            ⏳ Chưa check-in
                                        </span>
                                    </td>
                                    <td class="p-3 text-right space-x-1.5">
                                        <button 
                                            v-if="b.checkin_status !== 'CHECKED_IN'"
                                            @click="manualCheckin(b.id)"
                                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-slate-900 rounded-lg text-xs font-bold transition">
                                            ✓ Check-in
                                        </button>
                                        <button 
                                            @click="openChangeSeat(b)"
                                            class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-lg text-xs font-bold transition">
                                            Đổi Ghế
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-6 text-slate-400 text-xs">
                        Chưa có hành khách nào trên chuyến này. Bạn có thể bấm "+ Đón khách dọc đường" để bắt vé trực tiếp.
                    </div>
                </div>
            </section>

            <!-- TAB 3: LỊCH CHUYẾN 7 NGÀY & LỊCH SỬ -->
            <section v-else-if="activeTab === 'schedule'" class="space-y-4">
                <!-- Filter Bar -->
                <div class="bg-white p-3 rounded-2xl border border-slate-200 flex items-center justify-between flex-wrap gap-2">
                    <div class="text-xs font-bold text-slate-700">Lọc theo trạng thái:</div>
                    <div class="flex items-center space-x-1.5 overflow-x-auto">
                        <button 
                            @click="scheduleFilter = 'ALL'"
                            :class="scheduleFilter === 'ALL' ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3 py-1 rounded-lg text-xs font-bold transition">
                            Tất cả ({{ allTrips.length }})
                        </button>
                        <button 
                            @click="scheduleFilter = 'WAITING'"
                            :class="scheduleFilter === 'WAITING' ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3 py-1 rounded-lg text-xs font-bold transition">
                            Chờ nhận
                        </button>
                        <button 
                            @click="scheduleFilter = 'CONFIRMED'"
                            :class="scheduleFilter === 'CONFIRMED' ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3 py-1 rounded-lg text-xs font-bold transition">
                            Đã nhận & Sẵn sàng
                        </button>
                        <button 
                            @click="scheduleFilter = 'RUNNING'"
                            :class="scheduleFilter === 'RUNNING' ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3 py-1 rounded-lg text-xs font-bold transition">
                            Đang chạy
                        </button>
                        <button 
                            @click="scheduleFilter = 'COMPLETED'"
                            :class="scheduleFilter === 'COMPLETED' ? 'bg-blue-600 text-white font-black' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                            class="px-3 py-1 rounded-lg text-xs font-bold transition">
                            Hoàn thành
                        </button>
                    </div>
                </div>

                <!-- Schedule List -->
                <div class="space-y-3">
                    <div 
                        v-for="trip in filteredScheduleTrips" 
                        :key="trip.id"
                        class="bg-white border border-slate-200 shadow-xs rounded-2xl p-4 shadow hover:border-slate-300 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-black text-blue-600 font-mono">#{{ trip.trip_code || ('TRIP-' + trip.id) }}</span>
                                <span class="text-xs text-slate-400">• {{ formatDate(trip.departure_time) }} Lúc {{ formatTime(trip.departure_time) }}</span>
                            </div>
                            <div class="text-base font-bold text-slate-900 flex items-center space-x-1.5">
                                <span>🛣️</span>
                                <span>{{ trip.route?.name || 'Tuyến BusHub' }}</span>
                            </div>
                            <div class="text-xs text-slate-400 flex items-center space-x-3">
                                <span>🚍 Xe: <strong class="text-amber-600">{{ trip.bus?.plate_number || '43B-012.34' }}</strong></span>
                                <span>👥 Lơ xe: <strong class="text-emerald-400">{{ trip.conductor?.name || 'Trần Văn Lơ Xe' }}</strong></span>
                            </div>
                        </div>

                        <!-- Right buttons -->
                        <div class="flex items-center space-x-2 shrink-0">
                            <button 
                                @click="selectTripForSeatmap(trip)" 
                                class="px-3 py-1.5 rounded-xl bg-blue-600/20 hover:bg-blue-600/30 text-xs font-black text-blue-600 border border-blue-500/40">
                                💺 Sơ đồ ghế
                            </button>
                            <button 
                                @click="selectTripForDetail(trip)" 
                                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold text-slate-800 border border-slate-300">
                                Chi tiết
                            </button>
                            <span v-if="trip.status === 'COMPLETED'" class="text-xs font-bold text-purple-400 bg-purple-500/10 px-2.5 py-1 rounded-lg">
                                ✓ Hoàn thành
                            </span>
                            <span v-else-if="trip.status === 'IN_TRANSIT'" class="text-xs font-bold text-blue-400 bg-blue-500/10 px-2.5 py-1 rounded-lg animate-pulse">
                                🚀 Đang chạy
                            </span>
                            <button 
                                v-else-if="trip.status === 'OPEN_FOR_SALE' || !trip.accepted_at" 
                                @click="acceptTrip(trip)"
                                class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-600 text-xs font-black text-slate-900">
                                Nhận chuyến
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TAB 4: CHI TIẾT CHUYẾN XE (Xem tuyến, trạm dừng, xe, lơ xe) -->
            <section v-else-if="activeTab === 'detail'" class="space-y-4">
                <div v-if="activeActionTrip" class="space-y-4">
                    <!-- Top card header -->
                    <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs shadow-xl space-y-3">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                            <div>
                                <span class="text-xs font-black text-blue-600 uppercase font-mono">CHUYẾN #{{ activeActionTrip.trip_code || ('TRIP-' + activeActionTrip.id) }}</span>
                                <h2 class="text-xl font-black text-slate-900 mt-0.5">{{ activeActionTrip.route?.name || 'Đà Nẵng → Hội An' }}</h2>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-slate-400">Giờ dự kiến:</div>
                                <div class="text-lg font-black text-amber-600">{{ formatTime(activeActionTrip.departure_time) }} - {{ formatDate(activeActionTrip.departure_time) }}</div>
                            </div>
                        </div>

                        <!-- 3 columns spec -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                            <!-- Column 1: Bus details -->
                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 space-y-1.5">
                                <div class="font-extrabold text-blue-600 uppercase text-[11px]">🚍 THÔNG TIN XE</div>
                                <div class="text-sm font-black text-slate-900 font-mono">{{ activeActionTrip.bus?.plate_number || '43B-012.34' }}</div>
                                <div class="text-slate-700">Dòng xe: {{ activeActionTrip.bus?.bus_type || 'Giường Nằm Cao Cấp' }}</div>
                                <div class="text-slate-700">Sức chứa: {{ activeActionTrip.bus?.total_seats || 34 }} chỗ ngồi/nằm</div>
                                <div class="text-slate-400">Tiện nghi: {{ activeActionTrip.bus?.amenities || 'Wifi, Điều hòa, Nước suối, Cổng sạc USB' }}</div>
                            </div>

                            <!-- Column 2: Crew -->
                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 space-y-1.5">
                                <div class="font-extrabold text-emerald-400 uppercase text-[11px]">👥 TỔ PHỤC VỤ CHUYẾN</div>
                                <div><strong class="text-slate-400">Tài xế lái chính:</strong> <span class="text-slate-900 font-bold">{{ currentDriver?.name || 'Võ Trọng Hiếu' }}</span></div>
                                <div><strong class="text-slate-400">Lơ xe phụ trách:</strong> <span class="text-emerald-400 font-bold">{{ activeActionTrip.conductor?.name || 'Trần Văn Lơ Xe' }}</span></div>
                                <div><strong class="text-slate-400">Hotline Lơ xe:</strong> <a :href="'tel:' + (activeActionTrip.conductor?.phone || '0905123456')" class="text-blue-600 font-bold hover:underline">{{ activeActionTrip.conductor?.phone || '0905.123.456' }}</a></div>
                                <div class="text-slate-400">Nhiệm vụ lơ xe: Check-in vé, thu tiền mặt 70%, đón khách dọc đường.</div>
                            </div>

                            <!-- Column 3: Passenger Summary -->
                            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 space-y-1.5">
                                <div class="font-extrabold text-purple-400 uppercase text-[11px]">🎫 TÓM TẮT HÀNH KHÁCH</div>
                                <div class="text-2xl font-black text-slate-900">{{ activeActionTrip.bookings?.length || 0 }} <span class="text-xs font-normal text-slate-400">khách đã đặt</span></div>
                                <div class="text-slate-400 text-[11px]">Tài xế có thể kiểm soát và thao tác trực tiếp trên Sơ đồ ghế.</div>
                            </div>
                        </div>
                    </div>

                                        <!-- BẢNG ĐIỀU KHIỂN TRẠM DỪNG DỌC ĐƯỜNG (CHỈ DÀNH CHO TÀI XẾ) -->
                    <div class="p-5 rounded-3xl bg-white border border-slate-200 shadow-xs shadow-2xl space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-200">
                            <div>
                                <h3 class="font-black text-slate-900 text-base flex items-center space-x-2">
                                    <span>🚦 BẢNG ĐIỀU KHIỂN LỘ TRÌNH & TRẠM DỪNG (QUYỀN TÀI XẾ)</span>
                                </h3>
                                <p class="text-xs text-slate-400">Chỉ Tài xế có quyền bấm xác nhận Đến Trạm hoặc Bỏ Qua Trạm khi không có khách</p>
                            </div>
                            <div class="flex items-center space-x-2 text-xs">
                                <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-400 font-bold border border-emerald-500/30">
                                    Trạm hiện tại: #{{ activeActionTrip.current_stop_order || 1 }}
                                </span>
                            </div>
                        </div>

                        <!-- Progress Line -->
                        <div class="space-y-3">
                            <div 
                                v-for="(stop, idx) in (activeActionTrip.route?.stops || stops || [
                                    { stop_order: 1, stop_name: 'Bến xe Trung tâm Đà Nẵng', stop_type: 'ORIGIN' },
                                    { stop_order: 2, stop_name: 'Trạm Ngũ Hành Sơn', stop_type: 'PICKUP' },
                                    { stop_order: 3, stop_name: 'Trạm Điện Ngọc', stop_type: 'PICKUP' },
                                    { stop_order: 4, stop_name: 'Trạm Vĩnh Điện', stop_type: 'PICKUP' },
                                    { stop_order: 5, stop_name: 'Bến xe Hội An', stop_type: 'DESTINATION' }
                                ])" 
                                :key="idx"
                                class="p-4 rounded-2xl border transition flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                :class="{
                                    'bg-emerald-950/40 border-emerald-500/50 shadow-lg shadow-emerald-900/20': (activeActionTrip.current_stop_order || 1) === stop.stop_order,
                                    'bg-slate-50 border-slate-200': (activeActionTrip.current_stop_order || 1) !== stop.stop_order
                                }">
                                
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-xs shrink-0"
                                         :class="(activeActionTrip.current_stop_order || 1) >= stop.stop_order ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-100 text-slate-400'">
                                        {{ stop.stop_order || (idx + 1) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm flex items-center space-x-2">
                                            <span>{{ stop.stop_name }}</span>
                                            <span v-if="(activeActionTrip.current_stop_order || 1) === stop.stop_order" class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/30 text-emerald-300 animate-pulse border border-emerald-400/40">
                                                📍 XE ĐANG Ở ĐÂY
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-400">
                                            {{ idx === 0 ? 'Bến xuất phát' : (idx === (activeActionTrip.route?.stops?.length || 5) - 1 ? 'Bến kết thúc' : 'Trạm đón/trả dọc đường') }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Driver Stop Action Buttons & Permanent Status Logs -->
                                <div class="flex items-center space-x-2 shrink-0">
                                    <!-- TH 1: Trạm đã có nhật ký ghi nhận (ĐÃ ĐẾN hoặc BỎ QUA) -> Ẩn vĩnh viễn các nút bấm -->
                                    <template v-if="activeActionTrip.stop_status_logs && activeActionTrip.stop_status_logs[stop.stop_order]">
                                        <div v-if="activeActionTrip.stop_status_logs[stop.stop_order].status === 'ARRIVED'"
                                             class="px-3.5 py-2 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-xs font-black flex items-center space-x-1.5 shadow-sm">
                                            <span>✅ ĐÃ ĐẾN TRẠM ({{ activeActionTrip.stop_status_logs[stop.stop_order].time || 'Đã ghi nhận' }})</span>
                                        </div>
                                        <div v-else-if="activeActionTrip.stop_status_logs[stop.stop_order].status === 'SKIPPED'"
                                             class="px-3.5 py-2 rounded-xl bg-blue-600/15 text-amber-300 border border-blue-600/30 text-xs font-bold flex items-center space-x-1.5 opacity-80">
                                            <span>⏭️ ĐÃ BỎ QUA TRẠM ({{ activeActionTrip.stop_status_logs[stop.stop_order].time || 'Đã bỏ qua' }})</span>
                                        </div>
                                        <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>

                                    <!-- TH 2: Chuyến xe đã hoàn thành toàn bộ -->
                                    <template v-else-if="activeActionTrip.status === 'COMPLETED'">
                                        <div class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-400 text-xs font-semibold border border-slate-300">
                                            <span>🏁 Chuyến đã hoàn thành</span>
                                        </div>
                                        <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>

                                    <!-- TH 3: Trạm trước đó đã trôi qua -->
                                    <template v-else-if="(activeActionTrip.current_stop_order || 1) > stop.stop_order">
                                        <div class="px-3 py-1.5 rounded-xl bg-slate-100/80 text-slate-400 text-xs font-semibold border border-slate-300/60">
                                            <span>✔️ Đã qua trạm</span>
                                        </div>
                                        <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>

                                    <!-- TH 4: Trạm hiện tại hoặc trạm kế tiếp chưa xử lý -> Hiện nút thao tác cho Tài xế -->
                                    <template v-else>
                                        <button 
                                            @click="updateStopStatus(activeActionTrip, stop, 'ARRIVE')"
                                            :class="(activeActionTrip.current_stop_order || 1) === stop.stop_order ? 'bg-emerald-600 hover:bg-emerald-500 text-slate-900 font-black ring-2 ring-emerald-400 shadow-emerald-900/30' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold'"
                                            class="px-3.5 py-2 rounded-xl text-xs transition flex items-center space-x-1.5 shadow-md cursor-pointer">
                                            <span>🟢 ĐÃ ĐẾN TRẠM</span>
                                        </button>
                                        
                                        <button 
                                            v-if="idx > 0 && idx < (activeActionTrip.route?.stops?.length || 5) - 1"
                                            @click="updateStopStatus(activeActionTrip, stop, 'SKIP')"
                                            class="px-3 py-2 bg-slate-100 hover:bg-amber-900/40 text-amber-300 border border-blue-600/30 rounded-xl text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
                                            <span>⏭️ BỎ QUA</span>
                                        </button>
                                        <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TAB 5: ĐÁNH GIÁ TỪ KHÁCH HÀNG -->
            <section v-else-if="activeTab === 'reviews'" class="space-y-4">
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-xl font-black text-slate-900">⭐ ĐÁNH GIÁ TỪ HÀNH KHÁCH</h2>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-600/20 text-amber-600 border border-blue-600/30">
                                    {{ driverReviews.length }} Lượt đánh giá
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Nhận xét thực tế từ hành khách đi các chuyến xe của Bác tài {{ currentDriver?.name }}</p>
                        </div>

                        <!-- Score overview -->
                        <div class="flex items-center space-x-4 bg-slate-50 p-3 rounded-2xl border border-slate-200">
                            <div class="text-center">
                                <div class="text-2xl font-black text-amber-600">★ {{ currentDriver?.avg_rating || '5.0' }}</div>
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Hài lòng chung</div>
                            </div>
                            <div class="w-px h-8 bg-slate-100"></div>
                            <div class="text-center">
                                <div class="text-2xl font-black text-emerald-400">99%</div>
                                <div class="text-[10px] text-slate-400 uppercase font-bold">Lái an toàn</div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews List -->
                    <div v-if="driverReviews.length > 0" class="space-y-3">
                        <div 
                            v-for="rv in driverReviews" 
                            :key="rv.id"
                            class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2 hover:border-slate-300 transition">
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-sm font-black text-slate-700">
                                        {{ rv.is_anonymous ? '🕶️' : '👤' }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-xs flex items-center space-x-2">
                                            <span>{{ rv.reviewer_name || (rv.is_anonymous ? 'Hành khách ẩn danh' : 'Hành khách BusHub') }}</span>
                                            <span v-if="rv.is_anonymous" class="text-[9px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-400 font-normal">Ẩn danh</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400">{{ formatDate(rv.created_at) }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-1 text-amber-600 font-black text-sm">
                                    <span v-for="s in rv.rating" :key="s">★</span>
                                </div>
                            </div>

                            <p class="text-xs text-slate-700 italic bg-white/60 p-3 rounded-xl border border-slate-200/50">
                                “{{ rv.comment || 'Chuyến đi rất tốt, bác tài lái xe cẩn thận, đúng giờ!' }}”
                            </p>

                            <div class="flex items-center space-x-3 text-[11px] text-slate-400 pt-1">
                                <span>🛡️ An toàn: <strong class="text-emerald-400">{{ rv.safety_rating || 5 }}/5</strong></span>
                                <span>•</span>
                                <span>🤝 Phục vụ: <strong class="text-amber-600">{{ rv.service_rating || 5 }}/5</strong></span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-slate-400 text-xs">
                        Chưa có đánh giá nào cho Bác tài này.
                    </div>
                </div>
            </section>

            <!-- TAB 6: HỒ SƠ TÀI XẾ & LỊCH SỬ CHUYẾN -->
            <section v-else-if="activeTab === 'profile'" class="space-y-4">
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs shadow-xl space-y-4">
                    <div class="flex items-center space-x-4 pb-4 border-b border-slate-200">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-500 to-blue-600 flex items-center justify-center font-black text-slate-900 text-3xl shadow-lg ring-2 ring-blue-500/60">
                            👨‍✈️
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-xl font-black text-slate-900">{{ currentDriver?.name || 'Tài xế' }}</h2>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                    HOẠT ĐỘNG
                                </span>
                            </div>
                            <p class="text-xs text-slate-400">Mã định danh: ID #{{ currentDriver?.id }} • Đội xe BusHub Terminal Đà Nẵng</p>
                            <div class="flex items-center space-x-3 mt-1.5 text-xs text-amber-600 font-bold">
                                <span>{{ currentDriver?.avg_rating || '5.0' }} / 5.0 ({{ driverReviews.length }} lượt đánh giá)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Specs Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                            <span class="text-slate-400">Giấy phép lái xe:</span>
                            <div class="text-sm font-black text-slate-900 font-mono">{{ currentDriver?.license_number || 'Hạng E (Số: B2-99887766)' }}</div>
                            <span class="text-[10px] text-emerald-400">✓ Đầy đủ điều kiện lái xe khách trên 30 chỗ</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                            <span class="text-slate-400">Kinh nghiệm lái xe:</span>
                            <div class="text-sm font-black text-slate-900">{{ currentDriver?.experience_years || 8 }} năm đường dài Bắc - Nam</div>
                            <span class="text-[10px] text-slate-400">Thông thạo tuyến QL1A, Cao tốc</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                            <span class="text-slate-400">Số điện thoại liên hệ:</span>
                            <div class="text-sm font-black text-blue-600">{{ currentDriver?.phone || '0912345678' }}</div>
                            <span class="text-[10px] text-slate-400">Sẵn sàng nhận lệnh điều động</span>
                        </div>
                    </div>
                </div>

                <!-- Lịch sử các chuyến hoàn thành -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs shadow-xl space-y-3">
                    <h3 class="font-black text-slate-900 text-sm uppercase tracking-wider">Lịch Sử Các Chuyến Đã Chạy ({{ historyTrips.length }})</h3>
                    <div v-if="historyTrips.length > 0" class="space-y-2">
                        <div v-for="h in historyTrips" :key="h.id" class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                            <div>
                                <span class="font-bold text-slate-900">{{ h.route?.name || 'Đà Nẵng → Hội An' }}</span>
                                <div class="text-[11px] text-slate-400">Xe {{ h.bus?.plate_number }} • {{ formatDate(h.departure_time) }}</div>
                            </div>
                            <span class="px-2.5 py-1 rounded bg-purple-500/20 text-purple-400 font-bold text-[11px]">
                                ✓ Hoàn thành
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-xs text-slate-400 py-3">Chưa có chuyến lịch sử nào.</div>
                </div>
            </section>
        </main>

        <!-- ================= MODALS ================= -->

        <!-- 1. Modal Bắt Khách Dọc Đường (Chỉ chọn ghế trống & thu 100%) -->
        <div v-if="showRoadsideModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-300 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="text-base font-black text-slate-900 flex items-center space-x-2">
                        <span>🚌 ĐÓN KHÁCH DỌC ĐƯỜNG (BẮT VÉ TRỰC TIẾP)</span>
                    </h3>
                    <button @click="showRoadsideModal = false" class="text-slate-400 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitRoadside" class="space-y-3 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Trạm đón</label>
                            <select v-model="roadsideForm.pickup_stop_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-blue-600">
                                <option value="">-- Chọn trạm đón --</option>
                                <option v-for="s in (activeActionTrip?.route?.stops || stops)" :key="s.id" :value="s.id">{{ s.stop_name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Trạm trả</label>
                            <select v-model="roadsideForm.dropoff_stop_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-blue-600">
                                <option value="">-- Chọn trạm trả --</option>
                                <option v-for="s in (activeActionTrip?.route?.stops || stops)" :key="s.id" :value="s.id">{{ s.stop_name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tên khách</label>
                            <input v-model="roadsideForm.customer_name" required type="text" placeholder="Nguyễn Văn A" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-blue-600" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">SĐT khách</label>
                            <input v-model="roadsideForm.customer_phone" required type="text" placeholder="090xxxxxxx" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-blue-600" />
                        </div>
                    </div>

                    <!-- Chỉ chọn ghế còn trống! -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block font-semibold text-slate-700">Chọn ghế trống trên xe</label>
                            <span class="text-[11px] font-bold" :class="availableEmptySeats.length > 0 ? 'text-emerald-400' : 'text-rose-400'">
                                {{ availableEmptySeats.length > 0 ? `Còn ${availableEmptySeats.length} ghế trống` : '❌ Hết ghế trống' }}
                            </span>
                        </div>
                        
                        <div v-if="availableEmptySeats.length === 0" class="p-3 bg-rose-500/20 border border-rose-500/40 rounded-xl text-rose-300 text-xs text-center font-bold">
                            ⚠️ Toàn bộ ghế trên xe đã kín chỗ! Không thể đón thêm khách dọc đường.
                        </div>

                        <select v-else v-model="roadsideForm.seat_number" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-blue-600">
                            <option value="">-- Chọn ghế còn trống ({{ availableEmptySeats.length }} ghế) --</option>
                            <option v-for="s in availableEmptySeats" :key="s.seat_number" :value="s.seat_number">
                                {{ s.label }} {{ s.floor === 1 ? '🌟 VIP' : '🛏️ Giường Tiêu Chuẩn' }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Hình thức thu tiền mặt</label>
                        <select v-model="roadsideForm.payment_method" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none">
                            <option value="CASH">Tiền mặt tại xe (Thu 100% khi lên xe)</option>
                            <option value="TRANSFER">Chuyển khoản QR nhà xe (100%)</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-2 pt-3 border-t border-slate-200">
                        <button type="button" @click="showRoadsideModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="roadsideForm.processing || availableEmptySeats.length === 0" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-slate-900 rounded-xl font-black shadow-lg shadow-emerald-600/40 transition">
                            {{ roadsideForm.processing ? 'Đang tạo vé...' : '✓ Xác Nhận Đặt Ghế & Thu Tiền' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Modal Đổi Ghế Cho Khách -->
        <div v-if="showChangeSeatModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-300 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="text-base font-black text-slate-900">💺 Đổi Ghế Cho Khách Hàng</h3>
                    <button @click="showChangeSeatModal = false" class="text-slate-400 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl text-xs space-y-1 border border-slate-200">
                    <div>Khách: <strong class="text-slate-900">{{ selectedBookingForSeatChange?.customer_name }}</strong></div>
                    <div>Ghế hiện tại: <strong class="text-blue-600 font-mono">{{ selectedBookingForSeatChange?.booking_seats?.map(s => s.seat_number).join(', ') }}</strong></div>
                </div>

                <form @submit.prevent="submitChangeSeat" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Chọn ghế trống mới</label>
                        <select v-model="changeSeatForm.new_seat_number" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none focus:ring-2 focus:ring-blue-600">
                            <option value="">-- Chọn ghế mới --</option>
                            <option v-for="s in availableEmptySeats" :key="s.seat_number" :value="s.seat_number">
                                {{ s.label }}
                            </option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-200">
                        <button type="button" @click="showChangeSeatModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="changeSeatForm.processing" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-600 text-slate-900 rounded-xl font-black">
                            Xác Nhận Đổi Ghế
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. Modal Khai Báo Chi Phí (Xăng dầu, Cầu đường...) -->
        <div v-if="showExpenseModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-300 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="text-base font-black text-slate-900">💰 Khai Báo Chi Phí Chuyến Xe</h3>
                    <button @click="showExpenseModal = false" class="text-slate-400 hover:text-slate-900 text-xl">&times;</button>
                </div>
                <form @submit.prevent="submitExpense" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Khoản mục chi phí</label>
                        <select v-model="expenseForm.expense_type" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900">
                            <option value="Xăng dầu">Xăng dầu</option>
                            <option value="Vé cầu đường BOT">Vé cầu đường BOT</option>
                            <option value="Bến bãi / Đỗ xe">Bến bãi / Đỗ xe</option>
                            <option value="Rửa xe / Vệ sinh">Rửa xe / Vệ sinh</option>
                            <option value="Nước suối / Khăn lạnh">Nước suối / Khăn lạnh phát khách</option>
                            <option value="Sửa chữa khẩn cấp">Sửa chữa khẩn cấp</option>
                            <option value="Khác">Chi phí khác</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Số tiền (VNĐ)</label>
                        <input v-model="expenseForm.amount" required type="number" placeholder="Ví dụ: 350000" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900" />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Ghi chú / Số hóa đơn</label>
                        <textarea v-model="expenseForm.description" rows="2" placeholder="Ghi chú chi tiết..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-200">
                        <button type="button" @click="showExpenseModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="expenseForm.processing" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-600 text-slate-900 rounded-xl font-black">
                            Lưu Chi Phí
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. Modal Ghi Nhận Đồ Thất Lạc -->
        <div v-if="showLostFoundModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-300 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="text-base font-black text-slate-900">🎒 Ghi Nhận Đồ Thất Lạc</h3>
                    <button @click="showLostFoundModal = false" class="text-slate-400 hover:text-slate-900 text-xl">&times;</button>
                </div>
                <form @submit.prevent="submitLostFound" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tên món đồ / Hành lý</label>
                        <input v-model="lostFoundForm.item_name" required type="text" placeholder="Ví dụ: Ví tiền màu nâu, Điện thoại iPhone..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900" />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Vị trí tìm thấy (Số ghế / Hộc đồ)</label>
                        <input v-model="lostFoundForm.seat_number" type="text" placeholder="Ví dụ: Ghế A05 hoặc hộc để đồ tầng 1" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900" />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Mô tả chi tiết</label>
                        <textarea v-model="lostFoundForm.description" rows="2" placeholder="Đặc điểm nhận dạng..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-200">
                        <button type="button" @click="showLostFoundModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="lostFoundForm.processing" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-600 text-slate-900 rounded-xl font-black">
                            Lưu Thông Tin
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 5. Modal Chốt Chuyến & Bàn Giao Tiền -->
        <div v-if="showClosingModal" class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-300 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="text-base font-black text-slate-900">🏁 Chốt Chuyến & Bàn Giao Tiền Mặt</h3>
                    <button @click="showClosingModal = false" class="text-slate-400 hover:text-slate-900 text-xl">&times;</button>
                </div>
                <form @submit.prevent="submitClosing" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Số tiền mặt thực nộp về bến (VNĐ)</label>
                        <input v-model="closingForm.actual_cash_submitted" required type="number" placeholder="Nhập số tiền..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900 font-mono text-sm" />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Ghi chú bàn giao</label>
                        <textarea v-model="closingForm.notes" rows="2" placeholder="Ghi chú thêm nếu có..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-900"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2 pt-2 border-t border-slate-200">
                        <button type="button" @click="showClosingModal = false" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="closingForm.processing" class="px-4 py-1.5 bg-purple-600 hover:bg-purple-500 text-slate-900 rounded-xl font-black">
                            Xác Nhận Chốt Chuyến
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 6. Modal Từ Chối Chuyến -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border border-slate-300 rounded-2xl w-full max-w-md shadow-2xl p-5 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="font-black text-rose-400 text-sm flex items-center space-x-2">
                        <span>✕ Báo Không Thể Nhận Chuyến</span>
                    </h3>
                    <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-900">✕</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Lý do không thể nhận chuyến:</label>
                        <select v-model="rejectReason" class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none">
                            <option value="Sức khỏe không đảm bảo (Ốm, sốt)">Sức khỏe không đảm bảo (Ốm, sốt)</option>
                            <option value="Việc gia đình đột xuất">Việc gia đình đột xuất</option>
                            <option value="Đang nghỉ phép theo quy định">Đang nghỉ phép theo quy định</option>
                            <option value="Trùng lịch chạy chuyến khác">Trùng lịch chạy chuyến khác</option>
                            <option value="Khác">Lý do khác</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Ghi chú thêm:</label>
                        <textarea v-model="rejectNote" rows="2" placeholder="Nhập chi tiết nếu có..." class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200">
                    <button @click="showRejectModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">Hủy</button>
                    <button @click="submitReject" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-slate-900 rounded-xl text-xs font-black">Gửi Báo Cáo</button>
                </div>
            </div>
        </div>

        <!-- 7. Modal Checklist Kiểm Tra Xe -->
        <div v-if="showChecklistModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border border-slate-300 rounded-2xl w-full max-w-lg shadow-2xl p-5 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <div>
                        <h3 class="font-black text-amber-600 text-sm flex items-center space-x-2">
                            <span>📋 KIỂM TRA XE AN TOÀN TRƯỚC CHUYẾN</span>
                        </h3>
                        <p class="text-[11px] text-slate-400">Xe: {{ activeActionTrip?.bus?.plate_number || '43B-012.34' }} ({{ checklistItems.length }} hạng mục)</p>
                    </div>
                    <button @click="showChecklistModal = false" class="text-slate-400 hover:text-slate-900">✕</button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                    <label 
                        v-for="item in checklistItems" 
                        :key="item.key" 
                        class="flex items-center space-x-2.5 p-3 rounded-xl bg-slate-100/80 border border-slate-300 cursor-pointer hover:bg-slate-100">
                        <input type="checkbox" v-model="item.checked" class="w-4 h-4 rounded text-blue-600 bg-white focus:ring-0">
                        <span class="font-bold text-slate-800">{{ item.label }}</span>
                    </label>
                </div>

                <!-- Form Thêm Mục Kiểm Tra Mới -->
                <div class="pt-2 border-t border-slate-200 space-y-2">
                    <div v-if="!showAddChecklistInput">
                        <button 
                            @click="showAddChecklistInput = true" 
                            type="button" 
                            class="text-xs font-bold text-blue-600 hover:underline flex items-center space-x-1">
                            <span>+ Thêm hạng mục kiểm tra xe mới</span>
                        </button>
                    </div>
                    <div v-else class="flex items-center gap-2">
                        <input 
                            v-model="newChecklistName" 
                            type="text" 
                            placeholder="Ví dụ: Kiểm tra rèm cửa, dây an toàn..." 
                            class="flex-1 bg-slate-100 border border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-900 outline-none focus:ring-2 focus:ring-blue-600"
                        />
                        <button @click="addCustomChecklistItem" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-600 text-slate-900 rounded-xl text-xs font-bold">
                            Thêm
                        </button>
                        <button @click="showAddChecklistInput = false" class="px-2 py-1.5 text-slate-400 text-xs">
                            Hủy
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-200">
                    <button @click="showChecklistModal = false; showIssueModal = true" class="text-xs text-rose-400 hover:underline font-bold">
                        ⚠️ Phát hiện lỗi xe? Báo lỗi ngay
                    </button>
                    <button 
                        @click="submitChecklist" 
                        :disabled="!allChecked"
                        :class="allChecked ? 'bg-emerald-600 hover:bg-emerald-500 text-slate-900 shadow-lg shadow-emerald-600/30' : 'bg-slate-100 text-slate-500 cursor-not-allowed'"
                        class="px-5 py-2.5 rounded-xl text-xs font-black transition">
                        ✓ XÁC NHẬN ĐỦ ĐIỀU KIỆN SẴN SÀNG
                    </button>
                </div>
            </div>
        </div>

        <!-- 8. Modal Báo Lỗi Xe -->
        <div v-if="showIssueModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border border-slate-300 rounded-2xl w-full max-w-md shadow-2xl p-5 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="font-black text-rose-400 text-sm flex items-center space-x-2">
                        <span>⚠️ BÁO LỖI KỸ THUẬT XE</span>
                    </h3>
                    <button @click="showIssueModal = false" class="text-slate-400 hover:text-slate-900">✕</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Hạng mục bị lỗi:</label>
                        <select v-model="issueType" class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none">
                            <option value="Điều hòa làm lạnh yếu / không mát">Điều hòa làm lạnh yếu / không mát</option>
                            <option value="Lốp non hơi / có dấu hiệu mòn">Lốp non hơi / có dấu hiệu mòn</option>
                            <option value="Đèn chiếu sáng / xi nhan bị chập">Đèn chiếu sáng / xi nhan bị chập</option>
                            <option value="Cửa tự động kẹt / đóng chậm">Cửa tự động kẹt / đóng chậm</option>
                            <option value="Ghế nằm / giường nằm bị hỏng nâng hạ">Ghế nằm / giường nằm bị hỏng nâng hạ</option>
                            <option value="Động cơ phát ra tiếng động lạ">Động cơ phát ra tiếng động lạ</option>
                            <option value="CUSTOM">➕ Khác: Tự nhập loại lỗi mới không có trong danh sách</option>
                        </select>
                    </div>

                    <div v-if="issueType === 'CUSTOM'">
                        <label class="font-bold text-blue-600 block mb-1">Tên lỗi mới:</label>
                        <input 
                            v-model="customIssueType" 
                            type="text" 
                            placeholder="Nhập tên lỗi xe..." 
                            class="w-full bg-slate-100 border border-blue-500/50 rounded-xl p-2.5 text-slate-900 outline-none"
                        />
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Mức độ khẩn cấp:</label>
                        <select v-model="issueSeverity" class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none">
                            <option value="LOW">Thấp (Chạy được, bảo dưỡng sau)</option>
                            <option value="MEDIUM">Trung bình (Cần kiểm tra trước khi chạy)</option>
                            <option value="HIGH">Nghiêm trọng (Không an toàn để chạy ngay)</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Mô tả chi tiết:</label>
                        <textarea v-model="issueDesc" rows="3" placeholder="Ví dụ: Điều hòa hàng ghế số 12 tầng 2 không thổi gió..." class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200">
                    <button @click="showIssueModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">Hủy</button>
                    <button @click="submitVehicleIssue" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-slate-900 rounded-xl text-xs font-black">Gửi Cho Kỹ Thuật</button>
                </div>
            </div>
        </div>

        <!-- 9. Modal Báo Sự Cố Khẩn Cấp -->
        <div v-if="showIncidentModal" class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border border-rose-600/50 rounded-2xl w-full max-w-md shadow-2xl p-5 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="font-black text-rose-500 text-sm flex items-center space-x-2">
                        <span>🚨 PHÁT TÍN HIỆU SỰ CỐ KHẨN CẤP</span>
                    </h3>
                    <button @click="showIncidentModal = false" class="text-slate-400 hover:text-slate-900">✕</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Loại sự cố:</label>
                        <select v-model="incidentType" class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none">
                            <option value="BREAKDOWN">Hỏng xe giữa đường (Chết máy, đứt curoa...)</option>
                            <option value="FLAT_TIRE">Thủng lốp / nổ lốp xe</option>
                            <option value="TRAFFIC_JAM">Kẹt xe nghiêm trọng trên quốc lộ/cao tốc</option>
                            <option value="ACCIDENT">Va chạm / tai nạn giao thông</option>
                            <option value="ROAD_BLOCKED">Đường bị ngập lụt / chặn đường</option>
                            <option value="PASSENGER_MEDICAL">Hành khách gặp vấn đề y tế khẩn cấp</option>
                            <option value="OTHER">➕ Sự cố khác (Tự nhập loại sự cố mới)</option>
                        </select>
                    </div>

                    <div v-if="incidentType === 'OTHER'">
                        <label class="font-bold text-amber-600 block mb-1">Loại sự cố:</label>
                        <input 
                            v-model="customIncidentType" 
                            type="text" 
                            placeholder="Nhập loại sự cố..." 
                            class="w-full bg-slate-100 border border-blue-600/50 rounded-xl p-2.5 text-slate-900 outline-none"
                        />
                    </div>

                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Vị trí hiện tại:</label>
                        <input v-model="incidentLocation" type="text" placeholder="Ví dụ: Km 945 Quốc lộ 1A" class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none">
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Mô tả tình huống:</label>
                        <textarea v-model="incidentDesc" rows="3" placeholder="Chi tiết tình hình cần hỗ trợ..." class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200">
                    <button @click="showIncidentModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">Hủy</button>
                    <button @click="submitIncident" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-slate-900 rounded-xl text-xs font-black shadow-xl shadow-rose-600/50">PHÁT BÁO ĐỘNG NGAY</button>
                </div>
            </div>
        </div>

        <!-- 10. Modal Báo Trễ Chuyến -->
        <div v-if="showDelayModal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border border-slate-300 rounded-2xl w-full max-w-md shadow-2xl p-5 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h3 class="font-black text-amber-600 text-sm flex items-center space-x-2">
                        <span>⏰ BÁO TRỄ GIỜ DỰ KIẾN</span>
                    </h3>
                    <button @click="showDelayModal = false" class="text-slate-400 hover:text-slate-900">✕</button>
                </div>
                <div class="space-y-3 text-xs">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Thời gian trễ dự kiến:</label>
                        <select v-model="delayMinutes" class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none">
                            <option :value="15">Trễ 15 phút</option>
                            <option :value="30">Trễ 30 phút</option>
                            <option :value="45">Trễ 45 phút</option>
                            <option :value="60">Trễ 1 tiếng (60 phút)</option>
                            <option :value="90">Trễ 1 tiếng 30 phút (90 phút)</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Lý do trễ giờ:</label>
                        <select v-model="delayReason" class="w-full bg-slate-100 border border-slate-300 rounded-xl p-2.5 text-slate-900 outline-none">
                            <option value="Ùn tắc giao thông giờ cao điểm">Ùn tắc giao thông giờ cao điểm</option>
                            <option value="Thời tiết mưa bão, sương mù hạn chế tốc độ">Thời tiết mưa bão, sương mù</option>
                            <option value="Chờ đón khách theo điều phối">Chờ đón khách theo điều phối</option>
                            <option value="Đường thi công / Phân luồng giao thông">Đường thi công / Phân luồng</option>
                            <option value="Khác">Lý do khác</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200">
                    <button @click="showDelayModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl text-xs font-bold">Hủy</button>
                    <button @click="submitDelay" class="px-4 py-2 bg-amber-600 hover:bg-blue-600 text-slate-900 rounded-xl text-xs font-black">Lưu Báo Trễ</button>
                </div>
            </div>
        </div>

        <!-- 3. Bottom Quick Bar (Mobile UX) -->
        <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-lg border-t border-slate-200 px-4 py-2 flex items-center justify-around md:hidden shadow-2xl">
            <button @click="activeTab = 'today'" :class="activeTab === 'today' ? 'text-blue-600 font-bold' : 'text-slate-400'" class="flex flex-col items-center text-[10px]">
                <span class="text-base">🏠</span>
                <span>Hôm nay</span>
            </button>
            <button @click="activeTab = 'seatmap'" :class="activeTab === 'seatmap' ? 'text-blue-600 font-bold' : 'text-slate-400'" class="flex flex-col items-center text-[10px]">
                <span class="text-base">💺</span>
                <span>Sơ đồ ghế</span>
            </button>
            <button @click="activeTab = 'schedule'" :class="activeTab === 'schedule' ? 'text-blue-600 font-bold' : 'text-slate-400'" class="flex flex-col items-center text-[10px]">
                <span class="text-base">📅</span>
                <span>Lịch chuyến</span>
            </button>
            <button @click="activeTab = 'reviews'" :class="activeTab === 'reviews' ? 'text-blue-600 font-bold' : 'text-slate-400'" class="flex flex-col items-center text-[10px]">
                <span class="text-base">⭐</span>
                <span>Đánh giá</span>
            </button>
            <button @click="activeTab = 'profile'" :class="activeTab === 'profile' ? 'text-blue-600 font-bold' : 'text-slate-400'" class="flex flex-col items-center text-[10px]">
                <span class="text-base">👤</span>
                <span>Hồ sơ</span>
            </button>
        </nav>
    </div>
    <!-- Floating Persistent AI Assistant -->
    <AiChatModal />
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
