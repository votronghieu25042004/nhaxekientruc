<script setup>
import { ref, computed, onBeforeUnmount, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import { Html5Qrcode } from 'html5-qrcode'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const props = defineProps({
    trips: { type: Array, default: () => [] },
    allBuses: { type: Array, default: () => [] },
    currentBus: { type: Object, default: () => ({}) },
    selectedTrip: { type: Object, default: null },
    occupiedSeats: { type: Array, default: () => [] },
    stops: { type: Array, default: () => [] },
    stationPickups: { type: Array, default: () => [] },
    roadsidePickups: { type: Array, default: () => [] },
    stationDropoffs: { type: Array, default: () => [] },
    financeSummary: { type: Object, default: () => ({}) },
    seatMap: { type: Object, default: () => ({}) },
    checkinHistory: { type: Array, default: () => [] },
    checkinStats: { type: Object, default: () => ({}) },
    conductorAccounts: { type: Array, default: () => [] },
    activeConductor: { type: Object, default: () => ({}) },
    isAdminViewing: { type: Boolean, default: false },
    user: { type: Object, default: () => ({}) },
    pastShifts: { type: Array, default: () => [] },
    availableDateTabs: { type: Array, default: () => [] },
})

// Current Trip State
const currentTrip = ref(props.selectedTrip || (props.trips && props.trips.length > 0 ? props.trips[0] : null))

// Chế độ khóa khi chuyến xe đã kết thúc (COMPLETED hoặc CLOSED)
const isTripCompleted = computed(() => {
    return currentTrip.value?.status === 'COMPLETED' || currentTrip.value?.status === 'CLOSED'
})

// Bộ lọc ngày cho thanh chọn chuyến: 'today' (Mặc định hôm nay) | 'tomorrow' (Ngày mai)
const getInitialCategory = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search)
        const caParam = urlParams.get('ca')
        if (caParam === 'tomorrow' || caParam === 'today') return caParam
    }
    if (props.selectedTrip?.day_category === 'tomorrow') return 'tomorrow'
    return 'today'
}
const activeDateCategory = ref(getInitialCategory())

// Luôn đồng bộ ngày của ca khi chọn chuyến (không bị nhảy về hôm nay khi chọn ca ngày mai)
watch(() => props.selectedTrip, (newTrip) => {
    if (newTrip) {
        currentTrip.value = newTrip
        if (newTrip.day_category) {
            activeDateCategory.value = newTrip.day_category
        }
    }
}, { immediate: true })

// Tab 5: Lịch sử ca làm việc cũ (Lọc theo ngày & Phân trang 1, 2, 3...)
const shiftDateFilter = ref('ALL') // 'ALL', 'today', 'yesterday', 'custom'
const shiftCustomDate = ref('')
const currentShiftPage = ref(1)
const shiftPageSize = ref(6)

const filteredPastShifts = computed(() => {
    let list = props.pastShifts || []
    if (shiftDateFilter.value === 'today') {
        const todayD = new Date()
        const d = String(todayD.getDate()).padStart(2, '0')
        const m = String(todayD.getMonth() + 1).padStart(2, '0')
        const y = todayD.getFullYear()
        const dateStr = `${d}/${m}/${y}`
        list = list.filter(s => s.dep_date && s.dep_date.includes(dateStr))
    } else if (shiftDateFilter.value === 'yesterday') {
        const yestD = new Date()
        yestD.setDate(yestD.getDate() - 1)
        const d = String(yestD.getDate()).padStart(2, '0')
        const m = String(yestD.getMonth() + 1).padStart(2, '0')
        const y = yestD.getFullYear()
        const dateStr = `${d}/${m}/${y}`
        list = list.filter(s => s.dep_date && s.dep_date.includes(dateStr))
    } else if (shiftDateFilter.value === 'custom' && shiftCustomDate.value) {
        const parts = shiftCustomDate.value.split('-')
        if (parts.length === 3) {
            const dateStr = `${parts[2]}/${parts[1]}/${parts[0]}`
            list = list.filter(s => s.dep_date && s.dep_date.includes(dateStr))
        }
    }
    return list
})

const totalShiftPages = computed(() => {
    return Math.ceil(filteredPastShifts.value.length / shiftPageSize.value) || 1
})

const paginatedPastShifts = computed(() => {
    const start = (currentShiftPage.value - 1) * shiftPageSize.value
    return filteredPastShifts.value.slice(start, start + shiftPageSize.value)
})

watch(shiftDateFilter, () => { currentShiftPage.value = 1 })
watch(shiftCustomDate, () => { currentShiftPage.value = 1 })

const customStaffDateValue = computed(() => {
    if (activeDateCategory.value && activeDateCategory.value.match(/^\d{4}-\d{2}-\d{2}$/)) {
        return activeDateCategory.value
    }
    return ''
})

const onStaffDatePicked = (val) => {
    if (!val) return
    activeDateCategory.value = val
    const matches = props.trips ? props.trips.filter(t => (t.departure_time && t.departure_time.startsWith(val)) || t.date_key === val) : []
    if (matches.length > 0) {
        changeTrip(matches[0].id)
    }
}

const filteredDateTrips = computed(() => {
    if (!props.trips) return []
    if (activeDateCategory.value === 'today') {
        const list = props.trips.filter(t => t.day_category === 'today' || t.is_today)
        return list.length > 0 ? list : props.trips.filter(t => t.day_category !== 'past')
    }
    if (activeDateCategory.value === 'tomorrow') {
        return props.trips.filter(t => t.day_category === 'tomorrow')
    }
    // Hỗ trợ chọn bất kỳ ngày nào theo định dạng YYYY-MM-DD hoặc date_key / category
    const targetKey = activeDateCategory.value
    return props.trips.filter(t => 
        t.day_category === targetKey || 
        t.date_key === targetKey || 
        (t.departure_time && t.departure_time.startsWith(targetKey))
    )
})

// Danh sách hành khách chưa lên xe để kích hoạt cảnh báo khẩn cấp khi gần xuất bến
const unboardedPassengers = computed(() => {
    const list = []
    if (props.stationPickups) {
        props.stationPickups.forEach(s => {
            if (s.passengers) {
                s.passengers.forEach(p => {
                    if (p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW' && p.checkin_status !== 'CANCELLED') {
                        list.push(p)
                    }
                })
            }
        })
    }
    if (props.roadsidePickups) {
        props.roadsidePickups.forEach(p => {
            if (p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW' && p.checkin_status !== 'CANCELLED') {
                list.push(p)
            }
        })
    }
    return list
})

// Cảnh báo khẩn cấp CHỈ kích hoạt khi thời gian thực còn <= 10 phút trước giờ xuất bến hoặc xe đang chạy
const isUrgentWarning = computed(() => {
    if (isTripCompleted.value || unboardedPassengers.value.length === 0) return false
    if (currentTrip.value?.status === 'IN_TRANSIT' || currentTrip.value?.status === 'RUNNING') return true
    if (currentTrip.value?.departure_time) {
        const depDate = new Date(currentTrip.value.departure_time)
        if (!isNaN(depDate.getTime())) {
            const diffMinutes = Math.floor((depDate.getTime() - Date.now()) / 60000)
            return diffMinutes <= 10 && diffMinutes >= -180
        }
    }
    return false
})

// Modal tra cứu lịch sử chuyến cũ (Thay thế thanh kéo ngang dài xấu xí)
const showPastTripsModal = ref(false)
const pastTripsSearch = ref('')
const pastTripDateFilter = ref('ALL') // 'ALL', 'yesterday' (27/10), 'day_before' (26/10), 'three_days' (25/10), 'older', 'CUSTOM'
const pastTripCustomDate = ref('')

const pastTripsList = computed(() => {
    if (!props.trips) return []
    return props.trips.filter(t => t.day_category === 'past' || (!t.is_today && t.day_category !== 'tomorrow'))
})

const filteredPastTrips = computed(() => {
    let list = pastTripsList.value

    // Lọc theo ngày: Hôm qua (27/10), Hôm kia (26/10), 3 ngày trước (25/10), hoặc chọn ngày cũ
    if (pastTripDateFilter.value === 'yesterday') {
        list = list.filter(t => t.departure_time && t.departure_time.includes('2026-10-27'))
    } else if (pastTripDateFilter.value === 'day_before') {
        list = list.filter(t => t.departure_time && t.departure_time.includes('2026-10-26'))
    } else if (pastTripDateFilter.value === 'three_days') {
        list = list.filter(t => t.departure_time && t.departure_time.includes('2026-10-25'))
    } else if (pastTripDateFilter.value === 'older') {
        list = list.filter(t => {
            const dateStr = t.departure_time?.substring(0, 10)
            return dateStr && dateStr <= '2026-10-25'
        })
    } else if (pastTripDateFilter.value === 'CUSTOM' && pastTripCustomDate.value) {
        list = list.filter(t => t.departure_time && t.departure_time.includes(pastTripCustomDate.value))
    }

    if (pastTripsSearch.value.trim()) {
        const q = pastTripsSearch.value.toLowerCase().trim()
        list = list.filter(t => 
            (t.route?.name && t.route.name.toLowerCase().includes(q)) ||
            (t.trip_code && t.trip_code.toLowerCase().includes(q)) ||
            (t.bus?.license_plate && t.bus.license_plate.toLowerCase().includes(q)) ||
            (t.departure_time && t.departure_time.includes(q))
        )
    }
    return list
})

// Xác định có đang xem lại chuyến xe trong quá khứ hay không
const isPastTripSelected = computed(() => {
    return isTripCompleted.value || currentTrip.value?.day_category === 'past' || (!currentTrip.value?.is_today && currentTrip.value?.day_category !== 'tomorrow')
})

const selectActiveTrip = () => {
    activeDateCategory.value = 'today'
    const todayTrip = props.trips?.find(t => t.day_category === 'today' || t.is_today)
    if (todayTrip) {
        changeTrip(todayTrip.id)
    }
}

// 3 Main Tabs:
// 'seatmap': Tab 1 - Sơ đồ ghế & Bán vé dọc đường
// 'pickups': Tab 2 - Danh sách Đón / Trả
// 'checkin': Tab 3 - Quét QR & Check-in
const activeMainTab = ref('seatmap')

// Sub-tab inside Tab 2: 'fixed' | 'roadside' | 'dropoff'
// Sub-tab inside Tab 2: 'all_list' (Danh Sách Khách & Gọi Nhanh) | 'fixed' | 'roadside' | 'dropoff'
const activePickupSubTab = ref('all_list')
const passengerStatusFilter = ref('ALL') // 'ALL', 'WAITING', 'BOARDED', 'ROADSIDE', 'NO_SHOW'
const passengerSearchQuery = ref('')

// Bộ lọc ẩn khách đã lên xe ở Tab 2
const hideBoardedPax = ref(false)

// Search & Filter
const searchQuery = ref('')
const exceptionSearchQuery = ref('')
const conductorSearch = ref('')
const copiedNotice = ref('')

// Portal Dropdown & Account Selector (Admin Only)
const isPortalDropdownOpen = ref(false)
const portalDropdownRef = ref(null)

const handleClickOutsidePortal = (event) => {
    if (portalDropdownRef.value && !portalDropdownRef.value.contains(event.target)) {
        isPortalDropdownOpen.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutsidePortal)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutsidePortal)
})

const switchConductor = (conductorId) => {
    router.get('/conductor', { conductor_id: conductorId }, { preserveState: false })
}

// Modals
const showScannerModal = ref(false)
const showRoadsideModal = ref(false)
const showNoShowModal = ref(false)
const showCollectCashModal = ref(false)
const showChangeSeatModal = ref(false)
const showHistoryModal = ref(false)
const showClosingModal = ref(false)
const showSeatDetailModal = ref(false)
const showConductorAccountsModal = ref(false)
const showCashConfirmModal = ref(false)
const showReleaseSeatModal = ref(false)
const showYellowCheckinModal = ref(false)

// Selected entities for modals
const selectedBooking = ref(null)
const selectedSeatDetail = ref(null)
const pendingCashPax = ref(null)
const pendingReleaseSeat = ref(null)
const yellowSeatDetail = ref(null)
const isProcessing = ref(false)

// QR Scanner State
const qrScanInput = ref('')
const qrScanResult = ref(null)
const scannerError = ref(null)
const isCameraActive = ref(false)
let html5QrCode = null

// Roadside Ticket Form
const roadsideForm = useForm({
    customer_name: '',
    customer_phone: '',
    pickup_stop_id: 'current_location',
    dropoff_stop_id: '',
    seat_number: '',
    price: '',
    payment_method: 'CASH',
})

// Seat Swap Form (Tình huống 3: Khách đổi chỗ cho người nhà)
const seatSwapForm = useForm({
    new_seat_number: '',
})

// Closing Form
const closingForm = useForm({
    actual_cash_submitted: '',
    notes: '',
})

// Switch Trip
const changeTrip = (tripId) => {
    const target = props.trips?.find(t => t.id === tripId)
    const targetCa = target?.day_category || activeDateCategory.value
    const params = { 
        trip_id: tripId, 
        bus_id: props.currentBus?.id,
        ca: targetCa 
    }
    if (props.activeConductor?.id) {
        params.conductor_id = props.activeConductor.id
    }
    router.get('/conductor', params, { preserveState: false, preserveScroll: true })
}

const changeBus = (busId) => {
    const params = { bus_id: busId }
    if (props.activeConductor?.id) {
        params.conductor_id = props.activeConductor.id
    }
    router.get('/conductor', params, { preserveState: false })
}

// Flat list of all passengers (Gộp toàn bộ khách đón tại bến & đón dọc đường)
const allPassengers = computed(() => {
    const list = []
    if (props.stationPickups) {
        props.stationPickups.forEach(st => {
            if (st.passengers) {
                st.passengers.forEach(p => {
                    list.push({
                        ...p,
                        pickup_name: p.pickup_name || p.pickup_stop_name || st.stop_name,
                        dropoff_name: p.dropoff_name || p.dropoff_stop_name || 'Điểm trả',
                        remaining_amount: Number(p.remaining_amount ?? Math.max(0, (p.total_amount || 0) - (p.paid_amount || 0))),
                        total_amount: Number(p.total_amount || 0),
                    })
                })
            }
        })
    }
    if (props.roadsidePickups) {
        props.roadsidePickups.forEach(p => {
            const pKey = p.sub_id || (p.id + '_' + (p.seats ? p.seats.join('_') : ''))
            if (!list.some(x => (x.sub_id || (x.id + '_' + (x.seats ? x.seats.join('_') : ''))) === pKey)) {
                list.push({
                    ...p,
                    pickup_name: p.pickup_name || p.pickup_stop_name || 'Dọc đường',
                    dropoff_name: p.dropoff_name || p.dropoff_stop_name || 'Điểm trả',
                    remaining_amount: Number(p.remaining_amount ?? Math.max(0, (p.total_amount || 0) - (p.paid_amount || 0))),
                    total_amount: Number(p.total_amount || 0),
                    is_roadside: true
                })
            }
        })
    }
    return list
})

// Danh sách hành khách lọc theo trạng thái (Tất cả, Chưa lên xe, Đã lên xe, Dọc đường, Bỏ chuyến) & Tìm kiếm
const filteredTripPassengers = computed(() => {
    let list = allPassengers.value

    if (passengerStatusFilter.value === 'WAITING') {
        list = list.filter(p => p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW')
    } else if (passengerStatusFilter.value === 'BOARDED') {
        list = list.filter(p => p.checkin_status === 'CHECKED_IN')
    } else if (passengerStatusFilter.value === 'ROADSIDE') {
        list = list.filter(p => p.is_roadside)
    } else if (passengerStatusFilter.value === 'NO_SHOW') {
        list = list.filter(p => p.checkin_status === 'NO_SHOW')
    }

    if (passengerSearchQuery.value) {
        const q = passengerSearchQuery.value.toLowerCase().trim()
        list = list.filter(p => 
            (p.customer_name && p.customer_name.toLowerCase().includes(q)) ||
            (p.customer_phone && p.customer_phone.includes(q)) ||
            (p.booking_code && p.booking_code.toLowerCase().includes(q)) ||
            (p.seats && p.seats.some(s => s.toLowerCase().includes(q)))
        )
    }
    return list
})

// Thu tiền mặt riêng (không check-in ngay)
const submitCollectCashOnly = (booking) => {
    if (isTripCompleted.value) {
        alert('Chuyến xe đã kết thúc (COMPLETED). Không thể thao tác!')
        return
    }
    isProcessing.value = true
    router.post(`/conductor/bookings/${booking.id}/collect-payment`, {}, {
        preserveScroll: true,
        onSuccess: () => { isProcessing.value = false },
        onError: () => { isProcessing.value = false }
    })
}

// Filtered Passengers for Global Search
const filteredPassengers = computed(() => {
    if (!searchQuery.value) return allPassengers.value
    const q = searchQuery.value.toLowerCase().trim()
    return allPassengers.value.filter(p => 
        (p.customer_name && p.customer_name.toLowerCase().includes(q)) ||
        (p.customer_phone && p.customer_phone.includes(q)) ||
        (p.booking_code && p.booking_code.toLowerCase().includes(q)) ||
        (p.seats && p.seats.some(s => s.toLowerCase().includes(q)))
    )
})

// Tình huống 1: Tìm kiếm nhanh khi khách mạng yếu / mất sóng 4G
const exceptionSearchResults = computed(() => {
    if (!exceptionSearchQuery.value) return []
    const q = exceptionSearchQuery.value.toLowerCase().trim()
    return allPassengers.value.filter(p => 
        (p.customer_name && p.customer_name.toLowerCase().includes(q)) ||
        (p.customer_phone && p.customer_phone.includes(q)) ||
        (p.booking_code && p.booking_code.toLowerCase().includes(q)) ||
        (p.seats && p.seats.some(s => s.toLowerCase().includes(q)))
    )
})

// Available Empty Seats (Màu Xám - Trống)
const availableEmptySeats = computed(() => {
    const list = []
    const occupied = props.occupiedSeats || []
    for (let i = 1; i <= 18; i++) {
        const num = i < 10 ? '0' + i : '' + i
        const sA = 'A' + num
        const sB = 'B' + num
        if (!occupied.includes(sA)) list.push({ seat_number: sA, floor: 1, label: `Ghế A${num} (Tầng 1 - VIP)` })
        if (!occupied.includes(sB)) list.push({ seat_number: sB, floor: 2, label: `Ghế B${num} (Tầng 2 - Tiêu chuẩn)` })
    }
    return list
})

// Calculated Roadside Fare
const calculatedRoadsideFare = computed(() => {
    if (!roadsideForm.dropoff_stop_id || !props.stops || props.stops.length === 0) return 150000
    if (roadsideForm.pickup_stop_id === 'current_location') {
        const dStop = props.stops.find(s => s.id == roadsideForm.dropoff_stop_id)
        if (!dStop) return 150000
        const curOrder = currentTrip.value?.current_stop_order || 1
        const distOrder = Math.max(1, (dStop.stop_order || 1) - curOrder)
        return Math.max(80000, distOrder * 60000)
    }
    const pStop = props.stops.find(s => s.id == roadsideForm.pickup_stop_id)
    const dStop = props.stops.find(s => s.id == roadsideForm.dropoff_stop_id)
    if (!pStop || !dStop) return 150000
    const dist = Math.abs((dStop.distance_from_start_km || dStop.stop_order * 30) - (pStop.distance_from_start_km || pStop.stop_order * 30))
    return Math.max(50000, Math.round(dist * 1200 / 1000) * 1000)
})

// Format Currency
const formatMoney = (val) => {
    if (!val && val !== 0) return '0đ'
    return Number(val).toLocaleString('vi-VN') + 'đ'
}

// Format Currency Short for Seat Cards (e.g. 140k)
const formatMoneyShort = (val) => {
    if (!val || isNaN(val)) return '0đ'
    const num = Number(val)
    if (num >= 1000) {
        return Math.round(num / 1000) + 'k'
    }
    return new Intl.NumberFormat('vi-VN').format(num) + 'đ'
}

// 1-Click Call Helper
const makeCall = (phone) => {
    if (phone) window.location.href = `tel:${phone}`
}

// Copy Helper
const copyText = (text, label) => {
    navigator.clipboard.writeText(text)
    copiedNotice.value = `Đã sao chép ${label}: ${text}`
    setTimeout(() => { copiedNotice.value = '' }, 2500)
}

// Danh sách 20 tài khoản lơ xe sau lọc
const filteredConductors = computed(() => {
    if (!props.conductorAccounts) return []
    if (!conductorSearch.value) return props.conductorAccounts
    const q = conductorSearch.value.toLowerCase().trim()
    return props.conductorAccounts.filter(c => 
        (c.name && c.name.toLowerCase().includes(q)) ||
        (c.email && c.email.toLowerCase().includes(q)) ||
        (c.phone && c.phone.includes(q))
    )
})

// Lưu ý đặc biệt của hành khách (Đồng bộ Bác tài, Lơ xe, Khách, Admin)
// CHỈ HIỆN KHI CÓ KHÁCH HÀNG ĐỂ LẠI LƯU Ý THỰC TẾ, HIỂN THỊ RÕ TÊN VÀ SỐ GHẾ CỦA KHÁCH!
const currentTripCustomerNotes = computed(() => {
    if (props.currentTrip?.customer_notes_list && Array.isArray(props.currentTrip.customer_notes_list) && props.currentTrip.customer_notes_list.length > 0) {
        return props.currentTrip.customer_notes_list
    }
    const list = []
    if (props.passengers && props.passengers.length > 0) {
        for (const p of props.passengers) {
            const rawNote = (p.notes || '').trim()
            if (
                rawNote &&
                rawNote !== 'Đặt vé trực tuyến qua nhaxekientruc' &&
                !rawNote.includes('Tự động tạo') &&
                rawNote.toLowerCase() !== 'không' &&
                rawNote.toLowerCase() !== 'none'
            ) {
                list.push({
                    type: 'passenger',
                    customer_name: p.name || p.customer_name || 'Khách hàng',
                    customer_phone: p.phone || p.customer_phone || '',
                    seat_label: p.seat_number || (p.seats ? p.seats.join(', ') : 'Chưa chọn'),
                    note: rawNote
                })
            }
        }
    }
    if (props.currentTrip?.passenger_notes && props.currentTrip.passenger_notes.trim() !== '') {
        list.push({
            type: 'trip',
            customer_name: 'Ban Điều Hành',
            customer_phone: '',
            seat_label: 'Toàn chuyến',
            note: props.currentTrip.passenger_notes.trim()
        })
    }
    return list
})
const currentTripSpecialNotes = computed(() => {
    return currentTripCustomerNotes.value.length > 0
        ? currentTripCustomerNotes.value.map(n => `[${n.seat_label}] ${n.customer_name}: ${n.note}`).join('; ')
        : null
})

// --- 1. QR CODE SCANNER LOGIC ---
const openQrScanner = () => {
    if (isTripCompleted.value) {
        alert('Chuyến xe đã hoàn thành (COMPLETED). Không thể soát vé cho chuyến này!')
        return
    }
    showScannerModal.value = true
    qrScanResult.value = null
    scannerError.value = null
    qrScanInput.value = ''

    setTimeout(() => {
        startCamera()
    }, 300)
}

const closeQrScanner = () => {
    stopCamera()
    showScannerModal.value = false
}

const startCamera = async () => {
    try {
        await stopCamera()
        await nextTick()
        const readerEl = document.getElementById('qr-reader')
        if (!readerEl) return
        readerEl.innerHTML = ''

        html5QrCode = new Html5Qrcode('qr-reader')
        isCameraActive.value = true
        scannerError.value = null

        await html5QrCode.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 },
            },
            (decodedText) => {
                handleScanSuccess(decodedText)
            },
            () => {}
        )
    } catch (err) {
        isCameraActive.value = false
        scannerError.value = 'Không thể mở camera. Vui lòng cấp quyền truy cập camera trong cài đặt trình duyệt hoặc nhập mã vé / SĐT bên dưới.'
    }
}

const stopCamera = async () => {
    if (html5QrCode) {
        try {
            if (html5QrCode.isScanning) {
                await html5QrCode.stop()
            }
            html5QrCode.clear()
        } catch (e) {}
        html5QrCode = null
    }
    isCameraActive.value = false
}

const handleScanSuccess = async (code) => {
    stopCamera()
    await processCheckinCode(code)
}

const submitManualCodeCheckin = async () => {
    if (!qrScanInput.value.trim()) return
    await processCheckinCode(qrScanInput.value.trim())
}

const processCheckinCode = async (code) => {
    isProcessing.value = true
    qrScanResult.value = null
    scannerError.value = null

    try {
        const res = await axios.post('/conductor/scan-qr', {
            code_or_token: code,
            trip_id: currentTrip.value ? currentTrip.value.id : null,
        })

        qrScanResult.value = res.data

        router.reload({ only: ['stationPickups', 'roadsidePickups', 'stationDropoffs', 'financeSummary', 'seatMap', 'occupiedSeats', 'checkinHistory', 'checkinStats'] })
    } catch (err) {
        if (err.response && err.response.data) {
            qrScanResult.value = err.response.data
        } else {
            qrScanResult.value = {
                success: false,
                error_type: 'SYSTEM_ERROR',
                message: 'Lỗi kiểm tra vé hoặc mất kết nối máy chủ.',
            }
        }
    } finally {
        isProcessing.value = false
    }
}

// --- 2. MANUAL CHECK-IN & CASH COLLECTION ---
// Xác nhận thu tiền mặt cho khách trước khi cho lên xe
const openManualCheckinPrompt = (passenger, needCash = false) => {
    if (isTripCompleted.value) {
        alert('Chuyến xe đã kết thúc (COMPLETED). Không thể thay đổi dữ liệu!')
        return
    }
    if (passenger.remaining_amount > 0 || needCash) {
        pendingCashPax.value = passenger
        showCashConfirmModal.value = true
    } else {
        confirmManualCheckin(passenger, false)
    }
}

const submitCashCheckinConfirmed = () => {
    if (!pendingCashPax.value) return
    confirmManualCheckin(pendingCashPax.value, true)
    showCashConfirmModal.value = false
    pendingCashPax.value = null
}

const confirmManualCheckin = (booking, collectCash = false) => {
    if (isTripCompleted.value) {
        alert('Chuyến xe đã kết thúc (COMPLETED). Không thể thao tác!')
        return
    }
    isProcessing.value = true
    router.post(`/conductor/bookings/${booking.id}/checkin`, { collect_cash: collectCash }, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showSeatDetailModal.value = false
            showYellowCheckinModal.value = false
            exceptionSearchQuery.value = ''
            if (qrScanResult.value && qrScanResult.value.booking && qrScanResult.value.booking.id === booking.id) {
                qrScanResult.value.needs_cash_collection = false
                qrScanResult.value.success = true
                qrScanResult.value.message = 'ĐÃ THU ĐỦ TIỀN MẶT VÀ XÁC NHẬN LÊN XE THÀNH CÔNG!'
            }
        },
        onError: () => { isProcessing.value = false }
    })
}

// --- 3. SEAT CLICK HANDLERS (BẢNG ĐIỀU KHIỂN ĐA NĂNG) ---
const handleSeatClick = (seatCode) => {
    const seat = props.seatMap ? props.seatMap[seatCode] : null
    if (!seat) return

    if (seat.status === 'AVAILABLE') {
        // Ghế Xám (Trống) -> Mở form bán vé dọc đường
        if (isTripCompleted.value) {
            alert('Chuyến xe đã kết thúc (COMPLETED). Không thể bán vé mới!')
            return
        }
        openRoadsideModalWithSeat(seatCode)
    } else if (seat.status === 'WEB_BOOKED') {
        // Ghế Vàng (Khách đặt web nhưng chưa lên) -> Mở Popup Check-in & Thu tiền chi tiết
        yellowSeatDetail.value = seat
        showYellowCheckinModal.value = true
    } else if (seat.status === 'NO_SHOW') {
        // Ghế Đỏ (Bỏ chuyến) -> Mở Popup xác nhận giải phóng thành ghế trống
        if (isTripCompleted.value) {
            alert('Chuyến xe đã kết thúc (COMPLETED). Không thể giải phóng ghế!')
            return
        }
        pendingReleaseSeat.value = seat
        showReleaseSeatModal.value = true
    } else {
        // Ghế Xanh (Đã lên xe) -> Mở form xem lại vé, đổi ghế
        selectedSeatDetail.value = seat
        showSeatDetailModal.value = true
    }
}

// Giải phóng ghế No-show về trạng thái trống (Xám)
const submitReleaseSeatConfirmed = () => {
    if (!pendingReleaseSeat.value) return
    isProcessing.value = true
    router.post(`/conductor/bookings/${pendingReleaseSeat.value.booking_id}/release-seat`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showReleaseSeatModal.value = false
            pendingReleaseSeat.value = null
        },
        onError: () => { isProcessing.value = false }
    })
}

// Bỏ chuyến
const openNoShowModal = (booking) => {
    if (isTripCompleted.value) {
        alert('Chuyến xe đã kết thúc (COMPLETED). Không thể thao tác!')
        return
    }
    selectedBooking.value = booking
    showNoShowModal.value = true
}

const confirmNoShow = () => {
    if (!selectedBooking.value) return
    isProcessing.value = true
    router.post(`/conductor/bookings/${selectedBooking.value.id}/no-show`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showNoShowModal.value = false
            selectedBooking.value = null
            showSeatDetailModal.value = false
            showYellowCheckinModal.value = false
        },
        onError: () => { isProcessing.value = false }
    })
}

// --- 4. ROADSIDE TICKET BOOKING ---
const openRoadsideModalWithSeat = (seatNum) => {
    if (isTripCompleted.value) {
        alert('Chuyến xe đã hoàn thành (COMPLETED). Không thể bán vé mới!')
        return
    }
    roadsideForm.reset()
    roadsideForm.pickup_stop_id = 'current_location'
    if (seatNum) {
        roadsideForm.seat_number = seatNum
    } else if (availableEmptySeats.value.length > 0) {
        roadsideForm.seat_number = availableEmptySeats.value[0].seat_number
    }
    if (props.stops && props.stops.length >= 2) {
        roadsideForm.dropoff_stop_id = props.stops[props.stops.length - 1].id
    }
    roadsideForm.price = calculatedRoadsideFare.value || currentTrip.value?.base_price || 150000
    showSeatDetailModal.value = false
    showRoadsideModal.value = true
}

const setRoadsidePricePreset = (percent) => {
    const base = Number(currentTrip.value?.base_price || 150000)
    if (percent === 100) {
        roadsideForm.price = base
    } else {
        roadsideForm.price = Math.round((base * (percent / 100)) / 10000) * 10000
    }
}

const submitRoadsideTicket = () => {
    if (!currentTrip.value || isTripCompleted.value) return
    roadsideForm.post(`/conductor/trips/${currentTrip.value.id}/roadside-ticket`, {
        preserveScroll: true,
        onSuccess: () => {
            showRoadsideModal.value = false
            roadsideForm.reset()
        }
    })
}

// --- 5. ĐỔI GHẾ CHO KHÁCH (SEAT SWAP) ---
const openChangeSeatModal = (booking) => {
    if (isTripCompleted.value) {
        alert('Chuyến xe đã hoàn thành (COMPLETED). Không thể đổi ghế!')
        return
    }
    selectedBooking.value = booking
    seatSwapForm.reset()
    if (availableEmptySeats.value.length > 0) {
        seatSwapForm.new_seat_number = availableEmptySeats.value[0].seat_number
    }
    showSeatDetailModal.value = false
    showChangeSeatModal.value = true
}

const submitChangeSeat = () => {
    if (!selectedBooking.value) return
    seatSwapForm.post(`/conductor/bookings/${selectedBooking.value.id}/change-seat`, {
        preserveScroll: true,
        onSuccess: () => {
            showChangeSeatModal.value = false
            selectedBooking.value = null
        }
    })
}

// --- 6. SHIFT CLOSING (Chốt ca) ---
const openClosingModal = () => {
    if (isTripCompleted.value) {
        alert('Chuyến xe này đã hoàn tất chốt ca (COMPLETED/CLOSED)!')
        return
    }
    closingForm.actual_cash_submitted = props.financeSummary?.cash_collected_amount || ''
    closingForm.notes = ''
    showClosingModal.value = true
}

const submitClosing = () => {
    if (!currentTrip.value) return
    closingForm.post(`/conductor/trips/${currentTrip.value.id}/close`, {
        preserveScroll: true,
        onSuccess: () => {
            showClosingModal.value = false
        }
    })
}

let conductorMapInstance = null
let conductorMarker = null
let routePolyline = null

// Trạng thái xe đã xuất bến chưa
const isTripDeparted = computed(() => {
    const s = currentTrip.value?.status
    return s === 'IN_TRANSIT' || s === 'RUNNING' || s === 'COMPLETED' || s === 'ARRIVED' || s === 'CLOSED'
})

// Chế độ bản đồ Live Map: 'google' (Google Maps đường bộ), 'satellite' (Google Maps vệ tinh), 'dark' (Ban đêm)
const conductorMapLayerMode = ref('google')
let conductorMapTileLayer = null

const updateConductorMapTileLayer = () => {
    if (!conductorMapInstance) return
    if (conductorMapTileLayer) conductorMapInstance.removeLayer(conductorMapTileLayer)
    let tileUrl = ''
    let attribution = ''
    let subdomains = []

    if (conductorMapLayerMode.value === 'google') {
        tileUrl = 'https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'
        attribution = '&copy; Google Maps'
    } else if (conductorMapLayerMode.value === 'satellite') {
        tileUrl = 'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'
        attribution = '&copy; Google Maps Vệ Tinh'
    } else if (conductorMapLayerMode.value === 'dark') {
        tileUrl = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
        subdomains = ['a', 'b', 'c', 'd']
        attribution = '&copy; CARTO'
    } else {
        tileUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
        subdomains = ['a', 'b', 'c']
        attribution = '&copy; OpenStreetMap'
    }

    conductorMapTileLayer = L.tileLayer(tileUrl, {
        subdomains,
        maxZoom: 20,
        attribution
    }).addTo(conductorMapInstance)
}

const setConductorMapLayer = (mode) => {
    conductorMapLayerMode.value = mode
    updateConductorMapTileLayer()
}

const initConductorMap = () => {
    nextTick(() => {
        const el = document.getElementById('conductor-live-route-map')
        if (!el) return
        if (conductorMapInstance) {
            conductorMapInstance.remove()
            conductorMapInstance = null
        }

        const defaultLat = 16.0544
        const defaultLng = 108.2022
        conductorMapInstance = L.map(el, {
            center: [defaultLat, defaultLng],
            zoom: 10,
        })

        updateConductorMapTileLayer()

        const stopsList = props.stops || []
        const latLngs = []
        const isCompletedTrip = isTripCompleted.value || currentTrip.value?.status === 'COMPLETED' || currentTrip.value?.status === 'CLOSED'

        stopsList.forEach((s, idx) => {
            const sLat = 16.0544 + (idx * 0.22)
            const sLng = 108.2022 - (idx * 0.12)
            latLngs.push([sLat, sLng])
            
            let statusText = 'Sắp đến'
            let markerColor = '#64748b'

            if (isCompletedTrip) {
                if (idx === stopsList.length - 1) {
                    markerColor = '#059669' // Bến đích xanh đậm
                    statusText = 'Đã đến bến đích 🏁'
                } else {
                    markerColor = '#10b981' // Đã qua xanh lá
                    statusText = '✓ Đã qua'
                }
            } else if (!isTripDeparted.value) {
                // Xe chưa chạy: chưa qua trạm nào!
                if (idx === 0) {
                    markerColor = '#f59e0b' // Vàng cam: Bến xuất phát chờ xuất bến
                    statusText = 'Bến xuất phát (Chờ lên xe)'
                } else {
                    markerColor = '#64748b'
                    statusText = 'Sắp đến'
                }
            } else {
                const curOrder = currentTrip.value?.current_stop_order || 0
                const isPast = idx < curOrder
                const isCurrent = idx === curOrder
                markerColor = isCurrent ? '#dc2626' : (isPast ? '#10b981' : '#64748b')
                statusText = isCurrent ? 'Đang dừng đón' : (isPast ? '✓ Đã qua' : 'Sắp đến')
            }

            const markerHtml = `<div style="background-color: ${markerColor}; color: white; border-radius: 50%; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 11px; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">${idx + 1}</div>`
            const icon = L.divIcon({ html: markerHtml, className: 'custom-stop-marker', iconSize: [26, 26] })
            L.marker([sLat, sLng], { icon }).addTo(conductorMapInstance)
                .bindPopup(`<b>Trạm ${idx + 1}: ${s.stop_name}</b><br>${s.address || ''}<br>Trạng thái: ${statusText}`)
        })

        if (latLngs.length > 1) {
            routePolyline = L.polyline(latLngs, { color: isCompletedTrip ? '#10b981' : '#dc2626', weight: 4, dashArray: '6, 6' }).addTo(conductorMapInstance)
            conductorMapInstance.fitBounds(routePolyline.getBounds(), { padding: [40, 40] })
        }

        const currentOrder = isCompletedTrip 
            ? Math.max(0, latLngs.length - 1)
            : (isTripDeparted.value ? (currentTrip.value?.current_stop_order || 0) : 0)
        const busLat = latLngs[currentOrder] ? latLngs[currentOrder][0] : defaultLat
        const busLng = latLngs[currentOrder] ? latLngs[currentOrder][1] : defaultLng

        const busStatusLabel = isCompletedTrip ? '(Đã về bến đích)' : (!isTripDeparted.value ? '(Chờ xuất bến)' : '(Đang chạy)')
        const busBg = isCompletedTrip ? '#10b981' : (!isTripDeparted.value ? '#f59e0b' : '#dc2626')
        const busColor = '#ffffff'
        const busHtml = `<div style="background-color: ${busBg}; color: ${busColor}; border-radius: 12px; padding: 4px 8px; font-weight: bold; font-size: 11px; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.4); display: flex; align-items: center; gap: 4px;">🚍 ${props.currentBus?.license_plate || '43B-012.34'} ${busStatusLabel}</div>`
        const busIcon = L.divIcon({ html: busHtml, className: 'custom-bus-marker', iconSize: [160, 32] })
        conductorMarker = L.marker([busLat, busLng], { icon: busIcon }).addTo(conductorMapInstance)

        setTimeout(() => {
            if (conductorMapInstance) conductorMapInstance.invalidateSize()
        }, 150)
    })
}

const switchMainTab = (tab) => {
    activeMainTab.value = tab
    if (tab === 'map') {
        initConductorMap()
        setTimeout(() => {
            if (conductorMapInstance) conductorMapInstance.invalidateSize()
        }, 200)
    }
}

onBeforeUnmount(() => {
    stopCamera()
    if (conductorMapInstance) {
        conductorMapInstance.remove()
        conductorMapInstance = null
    }
})
</script>

<template>
  <div class="min-h-screen bg-slate-100 text-slate-900 flex flex-col font-sans pb-16" style="font-family: 'Plus Jakarta Sans', 'Be Vietnam Pro', system-ui, sans-serif;">
    <Head title="Cổng Lơ Xe & Soát Vé Di Động - nhaxekientruc" />

    <!-- TOAST NOTIFICATION -->
    <div v-if="copiedNotice" class="fixed top-4 right-4 z-50 bg-slate-950 text-white px-4 py-2.5 rounded-2xl shadow-2xl border border-slate-700 text-xs font-bold flex items-center gap-2 animate-bounce">
      <i class="fa-solid fa-circle-check text-emerald-400 text-base"></i>
      <span>{{ copiedNotice }}</span>
    </div>

    <!-- DÀNH RIÊNG CHO QUẢN TRỊ VIÊN: DẢI ĐIỀU KHIỂN & CHUYỂN ACC (ĐỒNG BỘ MÀU ĐỎ SANG TRỌNG CỦA NHAXEKIENTRUC, KHÔNG ĐỂ MÀU ĐEN) -->
    <div v-if="isAdminViewing" class="bg-gradient-to-r from-[#3b0609] via-[#5c0a0e] to-[#750e12] border-b border-red-500/30 text-white px-4 py-2 text-xs flex flex-wrap items-center justify-between gap-3 shadow-md z-40 relative">
      <div class="flex items-center gap-3 flex-wrap">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/15 text-amber-300 border border-white/20 font-black uppercase text-[10px] tracking-wider shadow-xs">
          <i class="fa-solid fa-shield-halved"></i> Admin Viewing Mode
        </span>
        <div class="flex items-center gap-2 bg-black/30 border border-white/20 rounded-xl px-2.5 py-1">
          <span class="text-white/80 font-bold">Chuyển Acc Lơ Xe:</span>
          <select
            :value="activeConductor?.id"
            @change="switchConductor($event.target.value)"
            class="bg-transparent font-black text-amber-300 outline-none cursor-pointer max-w-[240px] truncate"
          >
            <option v-for="c in conductorAccounts" :key="c.id" :value="c.id" class="bg-slate-900 text-white">
              {{ c.name }} ({{ c.email }})
            </option>
          </select>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <Link
          href="/admin"
          class="px-3 py-1 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold transition flex items-center gap-1.5 border border-white/20 cursor-pointer shadow-xs"
        >
          <i class="fa-solid fa-arrow-left"></i>
          <span>Quay Lại Admin</span>
        </Link>
        <!-- Portal switcher dropdown -->
        <div class="relative" ref="portalDropdownRef">
          <button
            @click.stop="isPortalDropdownOpen = !isPortalDropdownOpen"
            class="flex items-center space-x-2 p-1.5 pr-2.5 rounded-xl bg-white/15 hover:bg-white/25 border border-white/20 transition cursor-pointer text-left text-xs text-white shadow-xs"
          >
            <div class="w-6 h-6 rounded-full bg-red-600 text-white font-bold flex items-center justify-center text-xs">
              🛡️
            </div>
            <span class="font-bold text-white">Cổng Vai Trò</span>
            <svg class="w-3.5 h-3.5 text-white/70 transition-transform" :class="{ 'rotate-180': isPortalDropdownOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Dropdown Menu -->
          <div
            v-if="isPortalDropdownOpen"
            class="absolute right-0 mt-2 w-64 bg-white rounded-3xl shadow-2xl border border-slate-200 p-2 z-50 text-slate-900 animate-in fade-in zoom-in-95 duration-150"
          >
            <div class="px-3.5 py-2.5 border-b border-slate-100">
              <p class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Chuyển Đổi Cổng Thông Tin</p>
              <p class="text-xs font-bold text-slate-800 mt-0.5">Chọn vai trò để trải nghiệm</p>
            </div>

            <div class="p-1 space-y-1 text-xs">
              <Link
                href="/admin"
                @click="isPortalDropdownOpen = false"
                class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-slate-50 text-slate-700 hover:text-red-600 font-semibold transition group"
              >
                <div class="w-8 h-8 rounded-xl bg-slate-100 group-hover:bg-rose-50 text-slate-700 group-hover:text-red-600 flex items-center justify-center text-sm shadow-2xs transition">
                  🛡️
                </div>
                <div>
                  <div class="font-bold text-slate-900 group-hover:text-red-600">Quản Trị Viên</div>
                  <div class="text-[11px] text-slate-400">Bảng điều khiển Super Admin</div>
                </div>
              </Link>

              <Link
                href="/"
                @click="isPortalDropdownOpen = false"
                class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-slate-50 text-slate-700 hover:text-red-600 font-semibold transition group"
              >
                <div class="w-8 h-8 rounded-xl bg-slate-100 group-hover:bg-rose-50 text-slate-700 group-hover:text-red-600 flex items-center justify-center text-sm shadow-2xs transition">
                  🌐
                </div>
                <div>
                  <div class="font-bold text-slate-900 group-hover:text-red-600">Trang Khách</div>
                  <div class="text-[11px] text-slate-400">Giao diện đặt vé & tra cứu khách</div>
                </div>
              </Link>

              <Link
                href="/driver"
                @click="isPortalDropdownOpen = false"
                class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-slate-50 text-slate-700 hover:text-red-600 font-semibold transition group"
              >
                <div class="w-8 h-8 rounded-xl bg-slate-100 group-hover:bg-rose-50 text-slate-700 group-hover:text-red-600 flex items-center justify-center text-sm shadow-2xs transition">
                  👨‍✈️
                </div>
                <div>
                  <div class="font-bold text-slate-900 group-hover:text-red-600">Bác Tài</div>
                  <div class="text-[11px] text-slate-400">Cổng vận hành tài xế lái xe</div>
                </div>
              </Link>

              <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-rose-50 text-red-600 font-bold border border-rose-100">
                <div class="w-8 h-8 rounded-xl bg-red-600 text-white flex items-center justify-center text-sm shadow-2xs">
                  🎫
                </div>
                <div class="flex-1">
                  <div class="font-black text-slate-900 flex items-center justify-between">
                    <span>Lơ Xe</span>
                    <span class="text-[10px] bg-red-600 text-white px-1.5 py-0.2 rounded-md font-bold">Hiện tại</span>
                  </div>
                  <div class="text-[11px] text-slate-500 font-medium">Soát vé, thu cọc & bàn giao ca</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TOP NAVIGATION BAR (LƠ XE) -->
    <header class="bg-gradient-to-r from-red-600 via-rose-600 to-red-700 text-white shadow-lg sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap justify-between items-center gap-4">
        <!-- Brand & Staff Info -->
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center font-black text-xl shadow-inner text-white">
            🎫
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-black text-base sm:text-lg tracking-wide uppercase">PHÂN HỆ LƠ XE TRỰC TIẾP</span>
              <span class="text-[10px] bg-[#10b981] text-slate-950 px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider shadow-xs">ONLINE</span>
            </div>
            <p class="text-xs text-red-100 flex items-center gap-1.5 font-medium flex-wrap mt-0.5">
              <span class="font-bold text-white">{{ user?.name || 'Bùi Gia Huy' }}</span>
              <span>•</span>
              <span class="font-mono text-amber-200 font-bold">Xe: {{ currentBus?.license_plate || currentTrip?.bus?.license_plate || '43B-067.89' }}</span>
              <span>•</span>
              <span>Tài xế: {{ currentTrip?.driver?.name || 'Bùi Tiến Dũng' }}</span>
            </p>
          </div>
        </div>

        <!-- Conductor Actions Group (Pill Buttons như hình chụp) -->
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Bán vé dọc đường -->
          <button
            @click="openRoadsideModalWithSeat(null)"
            :disabled="isTripCompleted"
            :class="{
              'opacity-40 cursor-not-allowed bg-white/10': isTripCompleted,
              'bg-white/15 hover:bg-white/25 cursor-pointer': !isTripCompleted
            }"
            class="px-4 py-2 rounded-full border border-white/25 text-white font-bold text-xs flex items-center gap-1.5 transition shadow-xs"
            :title="isTripCompleted ? 'Chuyến xe đã kết thúc - Chế độ Read-only' : 'Bán vé dọc đường'"
          >
            <i class="fa-solid fa-plus-circle text-amber-300"></i>
            <span>Bán vé dọc đường</span>
          </button>

          <!-- Nhật ký Check-in -->
          <button
            @click="showHistoryModal = true"
            class="px-4 py-2 rounded-full bg-white/15 hover:bg-white/25 border border-white/25 text-white font-bold text-xs flex items-center gap-1.5 transition cursor-pointer shadow-xs"
          >
            <i class="fa-solid fa-clipboard-check text-emerald-300"></i>
            <span>Nhật Ký Check-in</span>
            <span v-if="checkinStats?.total_qr_scanned || checkinStats?.total_roadside_sold" class="px-1.5 py-0.2 bg-amber-400 text-slate-950 font-black rounded-full text-[10px]">
              {{ (checkinStats?.total_qr_scanned || 0) + (checkinStats?.total_roadside_sold || 0) }}
            </span>
          </button>

          <!-- Tra cứu chuyến cũ -->
          <button
            @click="showPastTripsModal = true"
            class="px-4 py-2 rounded-full bg-white/15 hover:bg-white/25 border border-white/25 text-white font-bold text-xs flex items-center gap-1.5 transition cursor-pointer shadow-xs"
          >
            <i class="fa-solid fa-clock-rotate-left text-amber-200"></i>
            <span>Tra Cứu Chuyến Cũ</span>
            <span class="px-2 py-0.5 rounded-full bg-white/25 text-white text-[10px] font-mono font-bold">
              {{ pastTripsList.length }}
            </span>
          </button>

          <!-- Chốt ca -->
          <button
            @click="openClosingModal"
            :disabled="isTripCompleted"
            :class="{
              'opacity-50 cursor-not-allowed bg-slate-600 text-white': isTripCompleted,
              'bg-[#f59e0b] hover:bg-amber-400 text-slate-950 shadow-md cursor-pointer': !isTripCompleted
            }"
            class="px-5 py-2 rounded-full font-black text-xs flex items-center gap-1.5 transition"
          >
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>{{ isTripCompleted ? 'Đã Chốt Ca' : 'Chốt Ca' }}</span>
          </button>
        </div>
      </div>
    </header>

      <!-- TRIP SELECTOR BAR HIỆN ĐẠI, SÁNG SỦA, GỌN GÀNG, KHÔNG BỊ KÉO DÀI -->
      <div class="bg-white px-4 py-3 border-t border-b border-slate-200 shadow-xs">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3 text-xs">
          <!-- Cụm Chọn Ca Hôm Nay, Ngày Mai & Nút Tra Cứu Chuyến Cũ -->
          <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1.5 bg-slate-100 p-1.5 rounded-2xl border border-slate-200 shadow-2xs overflow-x-auto max-w-full">
              <!-- Render danh sách ngày ca chạy liên tục 3 ngày trở lên -->
              <template v-if="availableDateTabs && availableDateTabs.length > 0">
                <button
                  v-for="dTab in availableDateTabs"
                  :key="dTab.category"
                  @click="activeDateCategory = dTab.category"
                  :class="{
                    'bg-gradient-to-r from-red-600 to-rose-600 text-white font-black shadow-xs': activeDateCategory === dTab.category,
                    'text-slate-700 hover:text-slate-900 font-bold hover:bg-white': activeDateCategory !== dTab.category
                  }"
                  class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition cursor-pointer shrink-0"
                >
                  <span>{{ dTab.label }}</span>
                  <span class="px-1.5 py-0.5 rounded-md text-[10px] font-mono font-bold" :class="activeDateCategory === dTab.category ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-800'">
                    {{ dTab.count }}
                  </span>
                </button>
              </template>
              <template v-else>
                <button
                  @click="activeDateCategory = 'today'"
                  :class="{
                    'bg-gradient-to-r from-red-600 to-rose-600 text-white font-black shadow-xs': activeDateCategory === 'today',
                    'text-slate-700 hover:text-slate-900 font-bold hover:bg-white': activeDateCategory !== 'today'
                  }"
                  class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer"
                >
                  <span>☀️ Ca Hôm Nay</span>
                  <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold" :class="activeDateCategory === 'today' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-800'">
                    {{ trips.filter(t => t.day_category === 'today' || t.is_today).length }}
                  </span>
                </button>

                <button
                  @click="activeDateCategory = 'tomorrow'"
                  :class="{
                    'bg-gradient-to-r from-red-600 to-rose-600 text-white font-black shadow-xs': activeDateCategory === 'tomorrow',
                    'text-slate-700 hover:text-slate-900 font-bold hover:bg-white': activeDateCategory !== 'tomorrow'
                  }"
                  class="px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition cursor-pointer"
                >
                  <span>📅 Ca Ngày Mai</span>
                  <span class="px-2 py-0.5 rounded-md text-xs font-mono font-bold" :class="activeDateCategory === 'tomorrow' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-800'">
                    {{ trips.filter(t => t.day_category === 'tomorrow').length }}
                  </span>
                </button>
              </template>
            </div>

            <!-- CHỌN NGÀY KHÁC BẤT KỲ TRÊN LỊCH (YÊU CẦU 3) -->
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-2xl bg-white border border-slate-200 shadow-2xs hover:border-red-400 transition">
              <i class="fa-regular fa-calendar-days text-red-600 text-xs"></i>
              <span class="text-[11px] font-bold text-slate-500 hidden sm:inline">Chọn Ngày Khác:</span>
              <input
                type="date"
                :value="customStaffDateValue"
                @change="onStaffDatePicked($event.target.value)"
                class="bg-transparent text-xs font-black text-slate-800 outline-none cursor-pointer"
                title="Bấm để chọn ngày khác xem lịch chạy"
              />
            </div>
          </div>

          <!-- DANH SÁCH CHUYẾN ĐƯỢC PHÂN CÔNG TRONG NGÀY (CHUẨN SCREENSHOT MẪU 2) -->
          <div class="space-y-3 pt-1">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-wider">
                  DANH SÁCH CHUYẾN ĐƯỢC PHÂN CÔNG TRONG NGÀY
                </h3>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold text-red-700 bg-red-50 border border-red-200">
                  {{ currentTrip?.full_date_title || 'Thứ Tư, ngày 04/11/2026' }}
                </span>
              </div>
              <p class="text-xs text-slate-400 flex items-center gap-1">
                <span class="text-amber-500">💡</span>
                <span>Bấm vào từng thẻ chuyến xe bên dưới để chuyển đổi sơ đồ ghế & sổ thu ngân</span>
              </p>
            </div>

            <!-- Thông báo khi ngày chọn không có chuyến nào -->
            <div v-if="filteredDateTrips.length === 0" class="flex items-center gap-2 p-4 rounded-3xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold shadow-2xs">
              <i class="fa-solid fa-calendar-xmark text-amber-600 text-base"></i>
              <span>Ngày {{ activeDateCategory.match(/^\d{4}-\d{2}-\d{2}$/) ? activeDateCategory.split('-').reverse().join('/') : activeDateCategory }} xe chưa có lịch chạy!</span>
              <button
                @click="activeDateCategory = 'today'"
                class="ml-auto px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-black text-xs cursor-pointer transition shadow-2xs"
              >
                Về hôm nay
              </button>
            </div>

            <!-- Cards Grid (Chuẩn Image 2) -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="tr in filteredDateTrips"
                :key="tr.id"
                @click="changeTrip(tr.id)"
                :class="{
                  'bg-white border-2 border-red-500 shadow-md ring-4 ring-red-500/10': currentTrip && currentTrip.id === tr.id,
                  'bg-white border border-slate-200 hover:border-slate-300 shadow-2xs': !currentTrip || currentTrip.id !== tr.id
                }"
                class="rounded-3xl p-5 transition cursor-pointer flex flex-col justify-between space-y-3 relative group"
              >
                <!-- Dòng 1: Ca chạy, Mã chuyến & Trạng thái -->
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span 
                      class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider shadow-2xs text-white"
                      :class="currentTrip && currentTrip.id === tr.id ? 'bg-red-600' : 'bg-amber-600'"
                    >
                      {{ tr.time_of_day_label ? 'CA ' + tr.time_of_day_label.toUpperCase() : 'CA CHẠY' }} • {{ tr.departure_time_formatted || tr.departure_time?.substring(11, 16) }} ➔ {{ tr.arrival_time_formatted || '08:30' }}
                    </span>
                    <span class="font-mono text-xs font-bold text-slate-500">
                      #{{ tr.trip_code }}
                    </span>
                  </div>

                  <!-- Trạng thái bên phải -->
                  <span
                    class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider border"
                    :class="{
                      'bg-emerald-50 text-emerald-700 border-emerald-200': tr.status === 'COMPLETED' || tr.status === 'CLOSED',
                      'bg-red-50 text-red-700 border-red-200': tr.status === 'BOARDING' || tr.status === 'OPEN_FOR_SALE',
                      'bg-blue-50 text-blue-700 border-blue-200': tr.status === 'IN_TRANSIT',
                    }"
                  >
                    {{ tr.status === 'COMPLETED' || tr.status === 'CLOSED' ? '✓ HOÀN THÀNH CA' : (tr.status === 'IN_TRANSIT' ? 'ĐANG CHẠY' : 'ĐANG ĐÓN KHÁCH') }}
                  </span>
                </div>

                <!-- Dòng 2: Tuyến đường & Nút chọn -->
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <h4 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                      <span>{{ tr.route?.name }}</span>
                    </h4>
                    <p class="text-xs text-slate-500 mt-0.5 font-medium">
                      {{ tr.is_return_trip ? 'Chiều Khứ Hồi' : 'Chiều Đi' }} ({{ tr.status === 'IN_TRANSIT' ? 'Đang chạy trên đường' : 'Đang đón khách' }}) • {{ tr.route?.stops?.[0]?.stop_name || 'BX Trung Tâm Đà Nẵng (Cổng A1)' }}
                    </p>
                  </div>

                  <div class="shrink-0 text-right">
                    <span v-if="currentTrip && currentTrip.id === tr.id" class="text-xs font-bold text-red-600 flex items-center gap-1">
                      <span>✓</span>
                      <span>ĐANG MỞ SƠ ĐỒ</span>
                    </span>
                    <span v-else class="text-xs font-bold text-slate-500 group-hover:text-red-600 flex items-center gap-1 transition">
                      <span>Bấm để chọn chuyến</span>
                      <span>➔</span>
                    </span>
                  </div>
                </div>

                <!-- Dòng 3: Pills xe, tài xế & sĩ số -->
                <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="px-2.5 py-1 rounded-xl bg-amber-50 text-amber-900 font-bold border border-amber-200 flex items-center gap-1 font-mono text-[11px]">
                      <span>🚌 Xe:</span>
                      <strong>{{ tr.bus?.license_plate || '43B-012.34' }}</strong>
                    </span>
                    <span class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800 font-bold border border-slate-200 flex items-center gap-1 text-[11px]">
                      <span>👤 TX:</span>
                      <span>{{ tr.driver?.name || 'Nguyễn Văn Hùng' }}</span>
                    </span>
                  </div>

                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-800 font-bold border border-emerald-200 text-[11px]">
                      Đã lên: {{ tr.boarded_seats_count ?? tr.boarded_count ?? 0 }}/{{ tr.booked_seats_count ?? tr.occupied_count ?? (tr.total_passengers || 0) }} ghế
                      <span class="text-slate-500 font-medium">({{ tr.customer_count ?? tr.booking_count ?? 0 }} khách)</span>
                      <span class="text-slate-400 font-normal">/ {{ tr.total_seats || 34 }} ghế</span>
                    </span>
                    <span v-if="(tr.uncollected_amount ?? 0) > 0" class="px-2 py-1 rounded-xl bg-rose-50 text-rose-700 font-bold border border-rose-200 text-[11px]">
                      Thiếu: {{ formatMoney(tr.uncollected_amount) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    <!-- MAIN BODY -->
    <main class="max-w-7xl mx-auto px-4 py-5 flex-1 w-full space-y-5">
      <!-- THÔNG BÁO RÕ RÀNG KHI CHỌN NGÀY CHƯA CÓ LỊCH / KHÔNG CÓ CHUYẾN XE (YÊU CẦU 3) -->
      <div v-if="filteredDateTrips.length === 0" class="bg-white rounded-3xl border border-slate-200 p-8 sm:p-12 text-center shadow-xs space-y-4">
        <div class="w-16 h-16 rounded-3xl bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center text-3xl mx-auto shadow-inner">
          <i class="fa-solid fa-calendar-xmark"></i>
        </div>
        <div class="space-y-1">
          <h3 class="text-base sm:text-lg font-black text-slate-800">
            Chưa Có Lịch Phân Công Cho Ngày Này
          </h3>
          <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto">
            Xe <strong>{{ currentBus?.license_plate }}</strong> hiện chưa có chuyến xe nào được phân công hoặc mở bán vào ngày <strong>{{ activeDateCategory.match(/^\d{4}-\d{2}-\d{2}$/) ? activeDateCategory.split('-').reverse().join('/') : activeDateCategory }}</strong>.
          </p>
        </div>
        <div class="pt-2 flex items-center justify-center gap-2">
          <button
            @click="activeDateCategory = 'today'"
            class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs cursor-pointer shadow-xs transition flex items-center gap-2"
          >
            <i class="fa-solid fa-sun"></i>
            <span>Xem Ca Hôm Nay</span>
          </button>
          <button
            @click="activeDateCategory = 'tomorrow'"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer shadow-xs transition flex items-center gap-2"
          >
            <i class="fa-solid fa-calendar"></i>
            <span>Xem Ca Ngày Mai</span>
          </button>
        </div>
      </div>

      <!-- CẢNH BÁO RÕ RÀNG: ĐANG XEM LẠI CHUYẾN XE TRONG QUÁ KHỨ (YÊU CẦU 4) -->
      <div v-if="filteredDateTrips.length > 0 && isPastTripSelected" class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white px-5 py-4 rounded-3xl shadow-lg border-2 border-amber-300 flex flex-wrap items-center justify-between gap-4 animate-in fade-in">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl shadow-inner shrink-0">
            📜
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h4 class="font-black text-sm uppercase tracking-wide drop-shadow-xs">
                BẠN ĐANG XEM LẠI CHUYẾN XE TRONG QUÁ KHỨ (LƯU TRỮ ĐỐI SOÁT)
              </h4>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-white text-orange-950 shadow-2xs">
                🔒 CHỈ XEM • READ-ONLY
              </span>
            </div>
            <p class="text-xs text-amber-100 mt-1 leading-relaxed">
              Dữ liệu của chuyến: <strong class="text-white">{{ currentTrip?.route?.name }}</strong> (Mã: #{{ currentTrip?.trip_code }} • Khởi hành: {{ currentTrip?.departure_time_formatted || currentTrip?.departure_time }}). Mọi tính năng soát vé, đổi chỗ và bán vé đã được khóa an toàn.
            </p>
          </div>
        </div>

        <button
          @click="selectActiveTrip"
          type="button"
          class="px-4 py-2.5 rounded-xl bg-white text-orange-950 hover:bg-amber-50 font-black text-xs shadow-md transition flex items-center gap-2 cursor-pointer shrink-0"
        >
          <span>↩ Quay Lại Ca Hôm Nay</span>
        </button>
      </div>

      <!-- HỘP LƯU Ý ĐẶC BIỆT CỦA HÀNH KHÁCH (CHỈ HIỆN KHI CÓ KHÁCH LƯU Ý THỰC TẾ) -->
      <div 
        v-if="currentTripCustomerNotes && currentTripCustomerNotes.length > 0"
        class="bg-[#FFFBEB] border border-[#FDE68A] text-[#92400E] px-5 py-3.5 rounded-3xl shadow-xs transition"
      >
        <div class="flex items-start gap-3">
          <span class="text-amber-500 font-bold text-base shrink-0 leading-none mt-0.5">⚠️</span>
          <div class="flex-1 space-y-2 text-xs sm:text-sm font-medium">
            <div class="font-bold text-amber-900 flex items-center gap-2">
              <span>Lưu ý từ hành khách:</span>
              <span class="text-[11px] font-normal text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full border border-amber-200">
                {{ currentTripCustomerNotes.length }} ghi chú
              </span>
            </div>
            <div class="space-y-1.5">
              <div 
                v-for="(nItem, idx) in currentTripCustomerNotes" 
                :key="idx"
                class="flex flex-wrap items-center gap-2 bg-white/70 px-3 py-1.5 rounded-xl border border-amber-200 text-amber-950 text-xs"
              >
                <span class="font-black text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded-md">
                  {{ nItem.seat_label ? 'Ghế ' + nItem.seat_label : (nItem.seats && nItem.seats.length > 0 ? 'Ghế ' + nItem.seats.join(', ') : 'Ghi chú') }}
                </span>
                <span class="font-bold text-slate-900">
                  {{ nItem.customer_name }}
                  <span v-if="nItem.customer_phone" class="text-slate-500 font-normal">({{ nItem.customer_phone }})</span>:
                </span>
                <span class="italic text-amber-900 font-medium">"{{ nItem.note }}"</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- CẢNH BÁO CHO CHUYẾN ĐÃ HOÀN THÀNH (READ ONLY) -->
      <div v-if="isTripCompleted && !isPastTripSelected" class="bg-amber-100 border border-amber-300 text-amber-950 px-5 py-3.5 rounded-3xl shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center font-black text-lg shrink-0">
            <i class="fa-solid fa-lock"></i>
          </div>
          <div>
            <h4 class="font-black text-sm uppercase tracking-wide flex items-center gap-2">
              <span>CHUYẾN XE ĐÃ HOÀN THÀNH - CHẾ ĐỘ CHỈ XEM (READ-ONLY)</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-500 text-slate-950 font-black">{{ currentTrip?.status }}</span>
            </h4>
            <p class="text-xs text-amber-900 font-medium mt-0.5">
              Chuyến xe này đã kết thúc hành trình. Tất cả các nút thao tác (Đổi ghế, Bỏ chuyến, Lên xe, Bán vé dọc đường) đã được vô hiệu hóa để bảo vệ tính toàn vẹn dữ liệu.
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs font-bold text-amber-900 bg-white/80 px-3 py-1.5 rounded-xl border border-amber-300">
            🔒 Chỉ xem dữ liệu
          </span>
        </div>
      </div>

      <!-- CẢNH BÁO KHẨN CẤP: HÀNH KHÁCH CHƯA LÊN XE KHI SẮP XUẤT BẾN (<= 10 PHÚT) HOẶC XE ĐANG CHẠY TRÊN ĐƯỜNG -->
      <div 
        v-if="isUrgentWarning"
        class="bg-amber-500/10 border-2 border-amber-500/40 rounded-3xl p-4 shadow-sm flex flex-wrap items-center justify-between gap-4 text-amber-950 animate-in fade-in"
      >
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl font-black shadow-sm shrink-0 animate-bounce">
            ⚠️
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h4 class="font-black text-sm uppercase tracking-wide text-amber-950 flex items-center gap-1.5">
                <span>CẢNH BÁO: CÒN {{ unboardedPassengers.length }} HÀNH KHÁCH CHƯA LÊN XE!</span>
              </h4>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-600 text-white shadow-2xs animate-pulse">
                SẮP XUẤT BẾN TRONG 10 PHÚT
              </span>
            </div>
            <p class="text-xs text-amber-900 font-medium mt-0.5 leading-relaxed">
              {{ currentTrip?.status === 'IN_TRANSIT' ? 'Xe đang chạy trên hành trình nhưng vẫn còn khách chưa check-in!' : 'Chuyến xe sắp xuất bến trong 10 phút. Lơ xe vui lòng gọi điện thoại nhắc nhở khách khẩn cấp!' }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <a
            v-for="p in unboardedPassengers.slice(0, 3)"
            :key="p.id"
            :href="'tel:' + p.customer_phone"
            class="px-3 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-xs transition cursor-pointer"
            :title="'Bấm để gọi ' + p.customer_name"
          >
            <span>📞</span>
            <span>{{ p.customer_name?.split(' ').pop() }}: {{ p.customer_phone }}</span>
            <span class="text-amber-200 text-[10px] font-normal">(Gọi ngay)</span>
          </a>
          <button
            @click="activeMainTab = 'pickups'; activePickupSubTab = 'all_list'; passengerStatusFilter = 'WAITING'"
            class="px-3 py-1.5 rounded-xl bg-white text-slate-800 hover:bg-slate-50 border border-slate-300 font-black text-xs shadow-2xs transition flex items-center gap-1 cursor-pointer"
          >
            <span>Xem tất cả ({{ unboardedPassengers.length }}) ➔</span>
          </button>
        </div>
      </div>

      <!-- TRIP BANNER & 3 INFO CARDS & 4 FINANCIAL METRICS (CHUẨN IMAGE 2) -->
      <div v-if="currentTrip" class="bg-white rounded-3xl p-6 border border-slate-200 shadow-2xs space-y-5">
        <!-- Top Title & Route Details -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="space-y-2">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider"
                :class="{
                  'bg-emerald-100 text-emerald-800': currentTrip.status === 'IN_TRANSIT',
                  'bg-blue-100 text-blue-800': currentTrip.status === 'BOARDING',
                  'bg-amber-100 text-amber-800': currentTrip.status === 'OPEN_FOR_SALE',
                  'bg-slate-200 text-slate-800': currentTrip.status === 'COMPLETED' || currentTrip.status === 'CLOSED',
                }">
                {{ currentTrip.status === 'IN_TRANSIT' ? 'ĐANG CHẠY TRÊN ĐƯỜNG' : (currentTrip.status === 'BOARDING' ? 'ĐANG ĐÓN KHÁCH TẠI BẾN' : (currentTrip.status === 'COMPLETED' || currentTrip.status === 'CLOSED' ? 'ĐÃ HOÀN THÀNH CHUYẾN' : 'MỞ BÁN VÉ')) }}
              </span>
              <span class="font-mono text-xs text-slate-600 font-bold bg-slate-100 px-3 py-1 rounded-full border border-slate-200">
                Mã chuyến: {{ currentTrip.trip_code }}
              </span>
              <span class="text-xs font-bold px-3 py-1 rounded-full bg-red-50 text-red-700 border border-red-200 flex items-center gap-1">
                {{ currentTrip.full_date_title || 'Thứ Tư, ngày 04/11/2026' }}
              </span>
              <span class="text-xs font-bold px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200">
                {{ currentTrip.is_return_trip ? 'Chiều Khứ Hồi' : 'Chiều Đi (Xuất bến Đà Nẵng)' }}
              </span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
              {{ currentTrip.route?.name || 'Đà Nẵng ⇄ Thừa Thiên Huế' }}
            </h1>

            <p class="text-xs text-slate-600 flex flex-wrap items-center gap-1.5 font-medium">
              <span>Khởi hành: <strong class="text-red-600 font-bold font-mono">{{ currentTrip.departure_time_formatted || currentTrip.departure_time?.substring(11, 16) }}</strong></span>
              <span>➔ Dự kiến đến: <strong class="text-slate-900 font-bold font-mono">{{ currentTrip.arrival_time_formatted || '08:30' }}</strong></span>
              <span class="text-slate-400">({{ currentTrip.duration_label || '2 giờ 30 phút' }})</span>
              <span>•</span>
              <span>Bến xuất phát: <strong class="text-slate-800">{{ currentTrip.route?.stops?.[0]?.stop_name || 'BX Trung Tâm Đà Nẵng (Cổng A1)' }}</strong></span>
            </p>
          </div>

          <!-- 3 Info Header Cards (Chuẩn Image 2) -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 shrink-0">
            <!-- 1. Biển số xe -->
            <div class="p-3.5 bg-amber-50/80 rounded-2xl border border-amber-200 flex items-center gap-3 shadow-2xs">
              <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center text-lg font-black shrink-0 shadow-2xs">
                🚌
              </div>
              <div>
                <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block">BIỂN SỐ ĐẦU XE</span>
                <div class="text-sm font-black text-slate-900 font-mono">{{ currentTrip.bus?.license_plate || '43B-012.34' }}</div>
                <span class="text-[11px] text-amber-700 font-medium">{{ currentTrip.bus?.bus_type || 'Limousine 34 Giường' }}</span>
              </div>
            </div>

            <!-- 2. Tài xế -->
            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 flex items-center gap-3 shadow-2xs">
              <div class="w-10 h-10 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center text-lg font-black shrink-0 shadow-2xs">
                🛞
              </div>
              <div>
                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block">TÀI XẾ LÁI CHÍNH</span>
                <div class="text-sm font-black text-slate-900">{{ currentTrip.driver?.name || 'Nguyễn Văn Hùng' }}</div>
                <span class="text-[11px] text-slate-600 font-mono">📞 {{ currentTrip.driver?.phone || '0905.312.889' }}</span>
              </div>
            </div>

            <!-- 3. Lơ xe -->
            <div class="p-3.5 bg-red-50/80 rounded-2xl border border-red-200 flex items-center gap-3 shadow-2xs">
              <div class="w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center text-lg font-black shrink-0 shadow-2xs">
                👤
              </div>
              <div>
                <span class="text-[10px] font-bold text-red-700 uppercase tracking-wider block">LƠ XE PHỤ TRÁCH</span>
                <div class="text-sm font-black text-slate-900">{{ user?.name || activeConductor?.name || 'Trần Văn Điều Phối' }}</div>
                <span class="text-[11px] text-red-600 font-medium">Đang mở ca thu ngân</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 4 Financial & Passenger Metric Cards (Chuẩn Image 2) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 pt-2 border-t border-slate-100">
          <!-- Card 1: Khách lên xe -->
          <div class="p-4 bg-slate-50/70 rounded-2xl border border-slate-200 shadow-2xs flex flex-col justify-between space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">1. ĐÃ LÊN XE (CHECK-IN)</span>
              <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">👥</span>
            </div>
            <div class="text-2xl font-black text-emerald-600 flex items-baseline gap-1.5 font-mono flex-wrap">
              <span>{{ financeSummary?.boarded_seats_count ?? financeSummary?.boarded_count ?? 0 }}</span>
              <span class="text-slate-400 font-normal text-lg">/</span>
              <span>{{ financeSummary?.booked_seats_count ?? financeSummary?.total_passengers ?? 0 }}</span>
              <span class="text-xs font-medium text-slate-500 ml-1">ghế</span>
              <span class="text-xs text-slate-400 font-normal">({{ financeSummary?.customer_count ?? currentTrip?.customer_count ?? 0 }} khách)</span>
            </div>
            <div class="text-[11px] text-slate-500 font-medium pt-1 border-t border-slate-200/80 flex items-center gap-2">
              <span>Chờ đón: <b>{{ financeSummary?.waiting_count || 0 }}</b></span>
              <span>•</span>
              <span>Bỏ: <b>{{ financeSummary?.no_show_count || 0 }}</b></span>
              <span>•</span>
              <span>Trống: <b>{{ Math.max(0, (currentTrip.bus?.total_seats || 34) - (financeSummary?.booked_seats_count ?? financeSummary?.total_passengers ?? 0)) }}</b></span>
            </div>
          </div>

          <!-- Card 2: Đã thu Online / Cọc -->
          <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-200/80 shadow-2xs flex flex-col justify-between space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">2. ĐÃ THU ONLINE / CỌC</span>
              <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold">💳</span>
            </div>
            <div class="text-2xl font-black text-blue-700 font-mono">
              {{ formatMoney(financeSummary?.online_paid_amount || 0) }}
            </div>
            <div class="text-[11px] text-slate-500 font-medium pt-1 border-t border-blue-100 flex items-center justify-between">
              <span>VNPAY • MoMo • VietQR</span>
              <span class="text-blue-600 font-bold">Đã đối soát</span>
            </div>
          </div>

          <!-- Card 3: Tiền mặt ĐÃ THU -->
          <div class="p-4 bg-emerald-50/50 rounded-2xl border border-emerald-200/80 shadow-2xs flex flex-col justify-between space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">3. TIỀN MẶT ĐÃ THU</span>
              <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold">💵</span>
            </div>
            <div class="text-2xl font-black text-emerald-600 font-mono">
              {{ formatMoney(financeSummary?.cash_collected_amount || 0) }}
            </div>
            <div class="text-[11px] text-slate-500 font-medium pt-1 border-t border-emerald-100 flex items-center justify-between">
              <span>Tiền mặt lơ xe đang giữ</span>
              <button @click="showSettlementModal = true" class="text-emerald-700 font-bold hover:underline cursor-pointer">
                Xem sổ quỹ ➔
              </button>
            </div>
          </div>

          <!-- Card 4: Còn thiếu (Cần thu) -->
          <div class="p-4 rounded-2xl border shadow-2xs flex flex-col justify-between space-y-2"
            :class="{
              'bg-red-50/70 border-red-200 text-red-700': (financeSummary?.uncollected_amount || 0) > 0,
              'bg-slate-50/70 border-slate-200 text-slate-800': (financeSummary?.uncollected_amount || 0) === 0
            }"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider" :class="(financeSummary?.uncollected_amount || 0) > 0 ? 'text-red-600' : 'text-slate-600'">
                4. CÒN THIẾU (CẦN THU)
              </span>
              <span class="w-7 h-7 rounded-lg flex items-center justify-center text-sm font-bold" :class="(financeSummary?.uncollected_amount || 0) > 0 ? 'bg-red-100 text-red-600' : 'bg-slate-200 text-slate-500'">
                ⚠️
              </span>
            </div>
            <div class="text-2xl font-black font-mono" :class="(financeSummary?.uncollected_amount || 0) > 0 ? 'text-red-600' : 'text-slate-900'">
              {{ formatMoney(financeSummary?.uncollected_amount || 0) }}
            </div>
            <div class="text-[11px] font-bold pt-1 border-t border-slate-200/80">
              <span v-if="(financeSummary?.uncollected_amount || 0) > 0" class="text-red-600 flex items-center gap-1 animate-pulse">
                <span>⚠️</span>
                <span>Thu khi lên xe</span>
              </span>
              <span v-else class="text-emerald-600 flex items-center gap-1">
                <span>🟢</span>
                <span>Đã thu đủ 100% tiền vé trên xe</span>
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= 5 TABS CHÍNH CỦA MÀN HÌNH LƠ XE ================= -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 p-1.5 bg-white rounded-3xl border border-slate-200 shadow-xs">
        <!-- Tab 1: Sơ đồ ghế & Bán vé dọc đường -->
        <button
          @click="switchMainTab('seatmap')"
          :class="{
            'bg-red-600 text-white font-black shadow-md': activeMainTab === 'seatmap',
            'text-slate-600 hover:text-slate-900 font-bold': activeMainTab !== 'seatmap'
          }"
          class="py-3 px-2 rounded-2xl text-xs sm:text-sm flex items-center justify-center gap-1.5 transition cursor-pointer"
        >
          <i class="fa-solid fa-couch"></i>
          <span>1. Sơ Đồ Ghế</span>
          <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono"
            :class="activeMainTab === 'seatmap' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700'"
          >
            {{ (financeSummary?.boarded_count || 0) }}/34
          </span>
        </button>

        <!-- Tab 2: Danh sách Đón / Trả -->
        <button
          @click="switchMainTab('pickups')"
          :class="{
            'bg-red-600 text-white font-black shadow-md': activeMainTab === 'pickups',
            'text-slate-600 hover:text-slate-900 font-bold': activeMainTab !== 'pickups'
          }"
          class="py-3 px-2 rounded-2xl text-xs sm:text-sm flex items-center justify-center gap-1.5 transition cursor-pointer"
        >
          <i class="fa-solid fa-users"></i>
          <span>2. Đón/Trả Khách</span>
          <span v-if="financeSummary?.waiting_count > 0" class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-amber-400 text-slate-950 font-black animate-pulse">
            Chờ {{ financeSummary.waiting_count }}
          </span>
        </button>

        <!-- Tab 3: Quét QR & Check-in -->
        <button
          @click="switchMainTab('checkin')"
          :class="{
            'bg-red-600 text-white font-black shadow-md': activeMainTab === 'checkin',
            'text-slate-600 hover:text-slate-900 font-bold': activeMainTab !== 'checkin'
          }"
          class="py-3 px-2 rounded-2xl text-xs sm:text-sm flex items-center justify-center gap-1.5 transition cursor-pointer"
        >
          <i class="fa-solid fa-qrcode"></i>
          <span>3. Quét QR Vé</span>
        </button>

        <!-- Tab 4: Bản Đồ Live & Định Vị Tuyến Đường -->
        <button
          @click="switchMainTab('map')"
          :class="{
            'bg-red-600 text-white font-black shadow-md': activeMainTab === 'map',
            'text-slate-600 hover:text-slate-900 font-bold': activeMainTab !== 'map'
          }"
          class="py-3 px-2 rounded-2xl text-xs sm:text-sm flex items-center justify-center gap-1.5 transition cursor-pointer"
        >
          <span>🗺️</span>
          <span>4. Bản Đồ Live</span>
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
        </button>

        <!-- Tab 5: Lịch Sử Ca Chạy Cũ (Chốt Ca & Doanh Thu) -->
        <button
          @click="switchMainTab('history')"
          :class="{
            'bg-red-600 text-white font-black shadow-md': activeMainTab === 'history',
            'text-slate-600 hover:text-slate-900 font-bold': activeMainTab !== 'history'
          }"
          class="py-3 px-2 rounded-2xl text-xs sm:text-sm flex items-center justify-center gap-1.5 transition cursor-pointer col-span-2 sm:col-span-1"
        >
          <span>📜</span>
          <span>5. Lịch Sử Ca Cũ</span>
          <span v-if="pastShifts?.length" class="px-1.5 py-0.5 rounded-full text-[10px] font-mono bg-white/20 text-white">
            {{ pastShifts.length }}
          </span>
        </button>
      </div>

      <!-- ================= TAB 1: SƠ ĐỒ GHẾ & BÁN VÉ DỌC ĐƯỜNG ================= -->
      <section v-if="activeMainTab === 'seatmap'" class="space-y-4">
        <!-- Chú thích 4 màu ghế rõ ràng -->
        <div class="bg-white rounded-3xl p-4 border border-slate-200 shadow-xs">
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <!-- Xám: Ghế trống -->
            <div class="p-2.5 rounded-2xl bg-slate-50 border border-slate-200 text-slate-700 flex items-center gap-2">
              <span class="w-4 h-4 rounded-md bg-slate-200 border border-slate-400 shrink-0"></span>
              <div>
                <strong class="block text-slate-900 font-bold">Màu Xám: Ghế Trống</strong>
                <span class="text-[10px] text-slate-500">Bấm vào để bán vé dọc đường</span>
              </div>
            </div>

            <!-- Vàng: Khách đặt web chưa lên -->
            <div class="p-2.5 rounded-2xl bg-amber-50 border border-amber-300 text-amber-950 flex items-center gap-2">
              <span class="w-4 h-4 rounded-md bg-amber-400 border border-amber-500 shrink-0"></span>
              <div>
                <strong class="block text-amber-950 font-bold">Màu Vàng: Chưa Lên Xe</strong>
                <span class="text-[10px] text-amber-800">Bấm để Check-in & Thu tiền</span>
              </div>
            </div>

            <!-- Xanh: Đã lên xe -->
            <div class="p-2.5 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-950 flex items-center gap-2">
              <span class="w-4 h-4 rounded-md bg-emerald-500 border border-emerald-600 shrink-0"></span>
              <div>
                <strong class="block text-emerald-950 font-bold">Màu Xanh: Đã Lên Xe</strong>
                <span class="text-[10px] text-emerald-800">Bấm để xem vé hoặc Đổi chỗ</span>
              </div>
            </div>

            <!-- Đỏ: Bỏ chuyến (No-show) -->
            <div class="p-2.5 rounded-2xl bg-rose-50 border border-rose-300 text-rose-950 flex items-center gap-2">
              <span class="w-4 h-4 rounded-md bg-rose-500 border border-rose-600 shrink-0"></span>
              <div>
                <strong class="block text-rose-950 font-bold">Màu Đỏ: Bỏ Chuyến</strong>
                <span class="text-[10px] text-rose-800">Bấm để Giải phóng thành ghế trống</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 2-Floor Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- TẦNG 1: GHẾ VIP / DƯỚI (A01 - A17) -->
          <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
              <h3 class="font-black text-sm text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-red-600"></i>
                <span>TẦNG 1: GIƯỜNG VIP / DƯỚI (A01 - A18)</span>
              </h3>
              <span class="text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg">18 Ghế</span>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center text-[10px] font-bold text-slate-500 pb-1 border-b border-slate-100 select-none">
              <span>🪟 Cửa sổ</span>
              <span>🚶 Dãy giữa</span>
              <span>🪟 Cửa sổ</span>
            </div>

            <div class="grid grid-cols-3 gap-3">
              <div
                v-for="i in 18"
                :key="'A' + i"
                @click="handleSeatClick('A' + (i < 10 ? '0' + i : i))"
                :class="{
                  // Xanh: Đã lên xe
                  'bg-emerald-100/90 border-emerald-500 text-emerald-950 shadow-xs': seatMap['A' + (i < 10 ? '0' + i : i)]?.status === 'CHECKED_IN',
                  // Vàng: Khách đặt web (chưa lên xe)
                  'bg-amber-100/90 border-amber-400 text-amber-950 shadow-xs': seatMap['A' + (i < 10 ? '0' + i : i)]?.status === 'WEB_BOOKED',
                  // Đỏ: Bỏ chuyến (No-show)
                  'bg-rose-100/90 border-rose-400 text-rose-950 shadow-xs': seatMap['A' + (i < 10 ? '0' + i : i)]?.status === 'NO_SHOW',
                  // Xám: Ghế Trống
                  'bg-slate-100 hover:bg-slate-200 border-slate-300 text-slate-700 border-dashed': !seatMap['A' + (i < 10 ? '0' + i : i)] || seatMap['A' + (i < 10 ? '0' + i : i)]?.status === 'AVAILABLE'
                }"
                class="p-2.5 rounded-2xl border-2 text-center transition cursor-pointer flex flex-col items-center justify-center min-h-[82px] hover:scale-102"
              >
                <div class="font-black text-xs font-mono">A{{ i < 10 ? '0' + i : i }}</div>

                <!-- Checked-in (Xanh) -->
                <template v-if="seatMap['A' + (i < 10 ? '0' + i : i)]?.status === 'CHECKED_IN'">
                  <div class="text-[10px] font-bold text-emerald-800 mt-1 truncate max-w-full">
                    ✓ {{ seatMap['A' + (i < 10 ? '0' + i : i)]?.customer_name || 'Đã lên' }}
                  </div>
                  <!-- Nhãn tiền bạc trực tiếp lên ghế (Requirement 7) -->
                  <div v-if="(seatMap['A' + (i < 10 ? '0' + i : i)]?.remaining_amount || 0) > 0" class="text-[9px] font-black text-red-700 bg-red-100 px-1 py-0.2 rounded border border-red-300 mt-0.5 truncate max-w-full">
                    ⚠️ Thu: {{ formatMoneyShort(seatMap['A' + (i < 10 ? '0' + i : i)]?.remaining_amount) }}
                  </div>
                  <div v-else class="text-[9px] font-medium text-emerald-700 mt-0.5">
                    ✓ Đã TT đủ
                  </div>
                </template>

                <!-- Web Booked (Vàng) -->
                <template v-else-if="seatMap['A' + (i < 10 ? '0' + i : i)]?.status === 'WEB_BOOKED'">
                  <div class="text-[10px] font-black text-amber-900 mt-1 truncate max-w-full">
                    {{ seatMap['A' + (i < 10 ? '0' + i : i)]?.customer_name || 'Đặt web' }}
                  </div>
                  <!-- Nhãn tiền bạc trực tiếp lên ghế (Requirement 7) -->
                  <div v-if="(seatMap['A' + (i < 10 ? '0' + i : i)]?.remaining_amount || 0) > 0" class="text-[9px] font-black text-red-600 bg-red-100 px-1 py-0.2 rounded border border-red-300 mt-0.5 truncate max-w-full animate-pulse">
                    ⚠️ Thu: {{ formatMoneyShort(seatMap['A' + (i < 10 ? '0' + i : i)]?.remaining_amount) }}
                  </div>
                  <div v-else class="text-[9px] font-bold text-emerald-800 mt-0.5">
                    ✓ Đã TT đủ
                  </div>
                </template>

                <!-- No-show (Đỏ) -->
                <template v-else-if="seatMap['A' + (i < 10 ? '0' + i : i)]?.status === 'NO_SHOW'">
                  <div class="text-[10px] font-black text-rose-700 mt-1 truncate max-w-full">
                    ✕ Bỏ chuyến
                  </div>
                  <div class="text-[9px] text-rose-600 font-bold mt-0.5">
                    👉 Bấm giải phóng
                  </div>
                </template>

                <!-- Available (Xám) -->
                <template v-else>
                  <div class="text-[10px] font-bold text-slate-500 mt-1">
                    + Trống
                  </div>
                  <div class="text-[9px] text-slate-400 mt-0.5">
                    Bán vé
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- TẦNG 2: GIƯỜNG TIÊU CHUẨN / TRÊN (B01 - B12 - 2 BÊN CỬA SỔ, DÃY GIỮA LỐI ĐI) -->
          <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
              <h3 class="font-black text-sm text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-blue-600"></i>
                <span>TẦNG 2: GIƯỜNG TIÊU CHUẨN / TRÊN (B01 - B12)</span>
              </h3>
              <span class="text-xs font-bold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg">12 Ghế</span>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center text-[10px] font-bold text-slate-500 pb-1 border-b border-slate-100 select-none">
              <span>🪟 Cửa sổ (Trái)</span>
              <span>🚶 Lối đi thông tầng</span>
              <span>🪟 Cửa sổ (Phải)</span>
            </div>

            <div class="grid grid-cols-3 gap-3">
              <template v-for="r in 6" :key="'f2-row-' + r">
                <!-- Ghế Cửa Sổ Trái: B01, B03, B05, B07, B09, B11 -->
                <div
                  @click="handleSeatClick('B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1)))"
                  :class="{
                    'bg-emerald-100/90 border-emerald-500 text-emerald-950 shadow-xs': seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'CHECKED_IN',
                    'bg-amber-100/90 border-amber-400 text-amber-950 shadow-xs': seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'WEB_BOOKED',
                    'bg-rose-100/90 border-rose-400 text-rose-950 shadow-xs': seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'NO_SHOW',
                    'bg-slate-100 hover:bg-slate-200 border-slate-300 text-slate-700 border-dashed': !seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))] || seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'AVAILABLE'
                  }"
                  class="p-2.5 rounded-2xl border-2 text-center transition cursor-pointer flex flex-col items-center justify-center min-h-[82px] hover:scale-102"
                >
                  <div class="font-black text-xs font-mono">B{{ (r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1) }}</div>
                  <template v-if="seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'CHECKED_IN'">
                    <div class="text-[10px] font-bold text-emerald-800 mt-1 truncate max-w-full">
                      ✓ {{ seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.customer_name || 'Đã lên' }}
                    </div>
                    <div v-if="(seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.remaining_amount || 0) > 0" class="text-[9px] font-black text-red-700 bg-red-100 px-1 py-0.2 rounded border border-red-300 mt-0.5 truncate max-w-full">
                      ⚠️ Thu: {{ formatMoneyShort(seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.remaining_amount) }}
                    </div>
                    <div v-else class="text-[9px] font-medium text-emerald-700 mt-0.5">✓ Đã TT đủ</div>
                  </template>
                  <template v-else-if="seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'WEB_BOOKED'">
                    <div class="text-[10px] font-black text-amber-900 mt-1 truncate max-w-full">
                      {{ seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.customer_name || 'Đặt web' }}
                    </div>
                    <div v-if="(seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.remaining_amount || 0) > 0" class="text-[9px] font-black text-red-600 bg-red-100 px-1 py-0.2 rounded border border-red-300 mt-0.5 truncate max-w-full animate-pulse">
                      ⚠️ Thu: {{ formatMoneyShort(seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.remaining_amount) }}
                    </div>
                    <div v-else class="text-[9px] font-bold text-emerald-800 mt-0.5">✓ Đã TT đủ</div>
                  </template>
                  <template v-else-if="seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status === 'NO_SHOW'">
                    <div class="text-[10px] font-black text-rose-700 mt-1 truncate max-w-full">✕ Bỏ chuyến</div>
                    <div class="text-[9px] text-rose-600 font-bold mt-0.5">👉 Bấm giải phóng</div>
                  </template>
                  <template v-else>
                    <div class="text-[10px] font-bold text-slate-500 mt-1">+ Trống</div>
                    <div class="text-[9px] text-slate-400 mt-0.5">Bán vé</div>
                  </template>
                </div>

                <!-- Cột Giữa: Lối Đi Thông Tầng VIP -->
                <div class="p-2.5 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/70 text-center flex flex-col items-center justify-center min-h-[82px] select-none text-slate-400">
                  <span class="text-sm">🚶</span>
                  <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mt-0.5">Lối Đi</span>
                  <span class="text-[8px] text-slate-300">Thông tầng</span>
                </div>

                <!-- Ghế Cửa Sổ Phải: B02, B04, B06, B08, B10, B12 -->
                <div
                  @click="handleSeatClick('B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2)))"
                  :class="{
                    'bg-emerald-100/90 border-emerald-500 text-emerald-950 shadow-xs': seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'CHECKED_IN',
                    'bg-amber-100/90 border-amber-400 text-amber-950 shadow-xs': seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'WEB_BOOKED',
                    'bg-rose-100/90 border-rose-400 text-rose-950 shadow-xs': seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'NO_SHOW',
                    'bg-slate-100 hover:bg-slate-200 border-slate-300 text-slate-700 border-dashed': !seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))] || seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'AVAILABLE'
                  }"
                  class="p-2.5 rounded-2xl border-2 text-center transition cursor-pointer flex flex-col items-center justify-center min-h-[82px] hover:scale-102"
                >
                  <div class="font-black text-xs font-mono">B{{ (r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2) }}</div>
                  <template v-if="seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'CHECKED_IN'">
                    <div class="text-[10px] font-bold text-emerald-800 mt-1 truncate max-w-full">
                      ✓ {{ seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.customer_name || 'Đã lên' }}
                    </div>
                    <div v-if="(seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.remaining_amount || 0) > 0" class="text-[9px] font-black text-red-700 bg-red-100 px-1 py-0.2 rounded border border-red-300 mt-0.5 truncate max-w-full">
                      ⚠️ Thu: {{ formatMoneyShort(seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.remaining_amount) }}
                    </div>
                    <div v-else class="text-[9px] font-medium text-emerald-700 mt-0.5">✓ Đã TT đủ</div>
                  </template>
                  <template v-else-if="seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'WEB_BOOKED'">
                    <div class="text-[10px] font-black text-amber-900 mt-1 truncate max-w-full">
                      {{ seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.customer_name || 'Đặt web' }}
                    </div>
                    <div v-if="(seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.remaining_amount || 0) > 0" class="text-[9px] font-black text-red-600 bg-red-100 px-1 py-0.2 rounded border border-red-300 mt-0.5 truncate max-w-full animate-pulse">
                      ⚠️ Thu: {{ formatMoneyShort(seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.remaining_amount) }}
                    </div>
                    <div v-else class="text-[9px] font-bold text-emerald-800 mt-0.5">✓ Đã TT đủ</div>
                  </template>
                  <template v-else-if="seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status === 'NO_SHOW'">
                    <div class="text-[10px] font-black text-rose-700 mt-1 truncate max-w-full">✕ Bỏ chuyến</div>
                    <div class="text-[9px] text-rose-600 font-bold mt-0.5">👉 Bấm giải phóng</div>
                  </template>
                  <template v-else>
                    <div class="text-[10px] font-bold text-slate-500 mt-1">+ Trống</div>
                    <div class="text-[9px] text-slate-400 mt-0.5">Bán vé</div>
                  </template>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- ================= DANH SÁCH HÀNH KHÁCH & GỌI ĐIỆN NHANH DƯỚI SƠ ĐỒ GHẾ (CÓ THÊM 1 CÁI NỚ Ở DƯỚI CÁI NÀY - REQUIREMENT) ================= -->
        <div class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-4">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-phone-volume text-emerald-600 animate-pulse"></i>
                <span>DANH SÁCH HÀNH KHÁCH & GỌI ĐIỆN NHANH (1-CLICK CALL)</span>
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Lơ xe bấm trực tiếp vào nút gọi để quay số ngay, kiểm tra tiền cọc, thu tiền mặt và cho khách lên xe
              </p>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold bg-slate-100 text-slate-700 px-3 py-1.5 rounded-xl">
                Tổng: <strong>{{ allPassengers.length }}</strong> khách
              </span>
            </div>
          </div>

          <!-- Search Input & Filter Pills -->
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
              <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input
                v-model="passengerSearchQuery"
                type="text"
                placeholder="🔍 Tìm nhanh: Gõ Số Ghế (vd: B04), SĐT hoặc Tên khách..."
                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-red-500 outline-none"
              />
            </div>
            <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
              <button
                v-for="f in [
                  { key: 'ALL', label: 'Tất Cả' },
                  { key: 'WAITING', label: 'Chưa Lên Xe' },
                  { key: 'BOARDED', label: 'Đã Lên Xe' },
                  { key: 'ROADSIDE', label: 'Khách Dọc Đường' },
                  { key: 'NO_SHOW', label: 'Bỏ Chuyến' }
                ]"
                :key="f.key"
                @click="passengerStatusFilter = f.key"
                :class="passengerStatusFilter === f.key ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-3.5 py-2 rounded-xl transition whitespace-nowrap cursor-pointer"
              >
                {{ f.label }}
              </button>
            </div>
          </div>

          <!-- Passenger Items Cards (MATCH 100% GIAO DIỆN BÁC TÀI & LƠ XE) -->
          <div v-if="filteredTripPassengers.length > 0" class="space-y-3">
            <div
              v-for="p in filteredTripPassengers"
              :key="'tab1-pax-' + (p.sub_id || (p.id + '_' + (p.seats ? p.seats.join('_') : '')))"
              class="p-4 rounded-2xl border transition flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
              :class="{
                'bg-emerald-50/50 border-emerald-300': p.checkin_status === 'CHECKED_IN',
                'bg-amber-50/40 border-amber-300': p.checkin_status === 'WAITING' || !p.checkin_status,
                'bg-rose-50/40 border-rose-300 opacity-75': p.checkin_status === 'NO_SHOW'
              }"
            >
              <!-- Left: Seat Badge & Passenger Info -->
              <div class="flex items-start space-x-3.5">
                <!-- Seat Code Badge -->
                <div
                  class="w-12 h-12 rounded-2xl flex flex-col items-center justify-center font-black font-mono shrink-0 shadow-xs"
                  :class="{
                    'bg-emerald-600 text-white': p.checkin_status === 'CHECKED_IN',
                    'bg-amber-500 text-white': p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW',
                    'bg-rose-600 text-white': p.checkin_status === 'NO_SHOW'
                  }"
                >
                  <span class="text-xs">{{ p.seats ? p.seats.join(',') : 'A01' }}</span>
                  <span class="text-[9px] font-sans font-bold opacity-90">
                    {{ p.seats && p.seats[0]?.startsWith('A') ? 'T1' : 'T2' }}
                  </span>
                </div>

                <!-- Customer Details -->
                <div class="space-y-1">
                  <div class="flex items-center space-x-2 flex-wrap">
                    <h4 class="font-black text-slate-900 text-sm">{{ p.customer_name }}</h4>
                    <span v-if="p.group_info" class="text-[10px] bg-blue-100 text-blue-800 font-extrabold px-2 py-0.5 rounded-md">
                      {{ p.group_info }}
                    </span>
                    <span v-if="p.is_roadside" class="text-[10px] bg-purple-100 text-purple-800 font-extrabold px-2 py-0.5 rounded-md">
                      Dọc đường
                    </span>
                    <span class="text-[10px] font-mono text-slate-400">#{{ p.booking_code }}</span>
                  </div>

                  <div class="text-xs text-slate-600 flex items-center space-x-2 flex-wrap">
                    <span>
                      <i class="fa-solid fa-route text-red-600 mr-1"></i>
                      {{ p.pickup_name || p.pickup_stop_name || 'Điểm đón' }} ➔ {{ p.dropoff_name || p.dropoff_stop_name || 'Điểm trả' }}
                    </span>
                    <span>•</span>
                    <span class="font-black text-slate-900">{{ formatMoney(p.total_amount) }}</span>
                  </div>

                  <!-- Thông tin thanh toán (Chuẩn logic: VNPAY / Chưa thanh toán tiền mặt) -->
                  <div class="text-xs flex items-center gap-2 flex-wrap pt-0.5">
                    <span
                      v-if="p.payment_status === 'PAID' || p.remaining_amount <= 0"
                      class="text-[11px] text-emerald-800 bg-emerald-100/80 px-2.5 py-0.5 rounded-lg inline-flex items-center gap-1 font-bold"
                    >
                      <i class="fa-solid fa-circle-check text-emerald-600"></i>
                      ✓ Đã thanh toán ({{ p.payment_method || 'VNPAY' }})
                    </span>
                    <span
                      v-else
                      class="text-[11px] text-amber-900 bg-amber-100/90 px-2.5 py-0.5 rounded-lg inline-flex items-center gap-1 font-bold"
                    >
                      <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                      {{ p.payment_badge || `⚠️ Chưa thanh toán (Còn thiếu ${formatMoney(p.remaining_amount)} tiền mặt)` }}
                    </span>

                    <span v-if="p.notes" class="text-[11px] text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                      📍 {{ p.notes }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Right: 1-Click Call & Action Buttons -->
              <div class="flex items-center flex-wrap gap-2 w-full md:w-auto justify-end pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                <!-- NÚT GỌI ĐIỆN 1-CLICK -->
                <button
                  @click="makeCall(p.customer_phone)"
                  class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black flex items-center space-x-1.5 shadow-xs transition cursor-pointer"
                >
                  <i class="fa-solid fa-phone-volume animate-bounce"></i>
                  <span>Gọi {{ p.customer_phone }}</span>
                </button>

                <!-- Check-in / Cho Lên Xe -->
                <span
                  v-if="p.checkin_status === 'CHECKED_IN'"
                  class="px-3.5 py-2 bg-emerald-100 text-emerald-800 font-black rounded-xl text-xs border border-emerald-300 flex items-center gap-1"
                >
                  <i class="fa-solid fa-check-double"></i> Đã Lên Xe
                </span>
                <template v-else-if="p.checkin_status !== 'NO_SHOW'">
                  <!-- Nếu còn thiếu tiền: Thu tiền & Cho lên xe -->
                  <button
                    v-if="p.remaining_amount > 0"
                    @click="confirmManualCheckin(p, true)"
                    :disabled="isProcessing"
                    class="px-3.5 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black rounded-xl text-xs shadow-xs transition flex items-center gap-1 cursor-pointer"
                    title="Bắt buộc thu tiền mặt trước khi cho lên xe"
                  >
                    <i class="fa-solid fa-money-bill-wave"></i> Thu {{ formatMoney(p.remaining_amount) }} & Cho Lên Xe
                  </button>
                  <!-- Nếu đã trả đủ: Cho Lên Xe (Đã trả đủ) -->
                  <button
                    v-else
                    @click="confirmManualCheckin(p, false)"
                    :disabled="isProcessing"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl text-xs shadow-xs transition flex items-center gap-1 cursor-pointer"
                  >
                    <i class="fa-solid fa-user-check"></i> Cho Lên Xe (Đã trả đủ)
                  </button>
                </template>

                <!-- Nút Thu Tiền Mặt Riêng -->
                <button
                  v-if="p.remaining_amount > 0 && p.checkin_status !== 'NO_SHOW'"
                  @click="submitCollectCashOnly(p)"
                  :disabled="isProcessing"
                  class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1 cursor-pointer"
                >
                  Thu {{ formatMoney(p.remaining_amount) }}
                </button>

                <!-- Nút Đổi Ghế -->
                <button
                  v-if="p.checkin_status !== 'NO_SHOW'"
                  @click="openChangeSeatModal(p)"
                  class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs border border-slate-200 transition cursor-pointer"
                >
                  Đổi Ghế
                </button>

                <!-- Bỏ Chuyến (No-show) -->
                <button
                  v-if="p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW'"
                  @click="openNoShowModal(p)"
                  class="px-3 py-2 bg-slate-50 hover:bg-rose-50 text-slate-500 hover:text-rose-600 font-bold rounded-xl text-xs border border-slate-200 transition cursor-pointer"
                >
                  Bỏ Chuyến
                </button>

                <!-- Giải phóng ghế khi No-show -->
                <button
                  v-if="p.checkin_status === 'NO_SHOW'"
                  @click="submitReleaseSeatConfirmed(p)"
                  class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-xl text-xs shadow-xs transition cursor-pointer"
                >
                  Giải Phóng Ghế
                </button>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-slate-400 text-xs font-bold">
            Không tìm thấy hành khách nào phù hợp với bộ lọc hiện tại.
          </div>
        </div>
      </section>

      <!-- ================= TAB 2: DANH SÁCH ĐÓN / TRẢ ================= -->
      <section v-if="activeMainTab === 'pickups'" class="space-y-4">
        <!-- Sub-tabs bar -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
          <div class="flex items-center gap-2 overflow-x-auto">
            <!-- 1. Danh Sách Khách & Gọi Nhanh (MATCH GIAO DIỆN MẪU) -->
            <button
              @click="activePickupSubTab = 'all_list'"
              :class="{
                'bg-red-600 text-white font-black shadow-md': activePickupSubTab === 'all_list',
                'bg-white text-slate-700 hover:bg-slate-100 font-bold border border-slate-200': activePickupSubTab !== 'all_list'
              }"
              class="px-4 py-2 rounded-2xl text-xs flex items-center gap-2 cursor-pointer transition"
            >
              <i class="fa-solid fa-list-check"></i>
              <span>📋 Danh Sách Khách & Gọi Nhanh (1-Click Call)</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-white/20 font-mono">{{ allPassengers.length }}</span>
            </button>

            <!-- 2. Đón Tại Điểm Cố Định -->
            <button
              @click="activePickupSubTab = 'fixed'"
              :class="{
                'bg-slate-900 text-white font-black': activePickupSubTab === 'fixed',
                'bg-white text-slate-700 hover:bg-slate-100 font-bold border border-slate-200': activePickupSubTab !== 'fixed'
              }"
              class="px-4 py-2 rounded-2xl text-xs flex items-center gap-2 cursor-pointer transition"
            >
              <i class="fa-solid fa-building-flag"></i>
              <span>🏢 Theo Bến & Điểm Đón Cố Định</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-white/20 font-mono">{{ stationPickups.length }} bến</span>
            </button>

            <button
              @click="activePickupSubTab = 'roadside'"
              :class="{
                'bg-amber-500 text-slate-950 font-black': activePickupSubTab === 'roadside',
                'bg-white text-slate-700 hover:bg-slate-100 font-bold border border-slate-200': activePickupSubTab !== 'roadside'
              }"
              class="px-4 py-2 rounded-2xl text-xs flex items-center gap-2 cursor-pointer transition"
            >
              <i class="fa-solid fa-road"></i>
              <span>Đón Dọc Đường (Web)</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-black/15 font-mono">{{ roadsidePickups.length }}</span>
            </button>

            <button
              @click="activePickupSubTab = 'dropoff'"
              :class="{
                'bg-blue-600 text-white font-black': activePickupSubTab === 'dropoff',
                'bg-white text-slate-700 hover:bg-slate-100 font-bold border border-slate-200': activePickupSubTab !== 'dropoff'
              }"
              class="px-4 py-2 rounded-2xl text-xs flex items-center gap-2 cursor-pointer transition"
            >
              <i class="fa-solid fa-person-walking-arrow-right"></i>
              <span>Điểm Trả Khách (Xuống Xe)</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-white/20 font-mono">{{ stationDropoffs.length }} trạm</span>
            </button>
          </div>

          <!-- BỘ LỌC SWITCH: [ ẨN KHÁCH ĐÃ LÊN XE ] (REQUIREMENT 6) -->
          <div class="flex items-center gap-2">
            <label class="flex items-center gap-2 cursor-pointer select-none bg-white px-3.5 py-2 rounded-2xl border border-slate-200 shadow-xs hover:border-slate-300 transition">
              <input
                type="checkbox"
                v-model="hideBoardedPax"
                class="rounded text-red-600 focus:ring-red-500 w-4 h-4 cursor-pointer"
              />
              <span class="text-xs font-bold text-slate-700 flex items-center gap-1">
                <i class="fa-solid fa-filter text-red-600 text-[11px]"></i>
                <span>Ẩn khách đã lên xe</span>
              </span>
              <span v-if="hideBoardedPax" class="text-[10px] font-black px-2 py-0.5 bg-amber-400 text-slate-950 rounded-full">
                Đang bật
              </span>
            </label>
          </div>
        </div>

        <!-- Global Search input inside Tab 2 -->
        <div class="bg-white rounded-2xl p-3 border border-slate-200 shadow-xs flex items-center gap-3">
          <i class="fa-solid fa-magnifying-glass text-slate-400 pl-2"></i>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Tìm theo Tên, 4 số cuối SĐT, Mã vé hoặc Số ghế (B04, A01)..."
            class="w-full text-xs sm:text-sm bg-transparent outline-none text-slate-900"
          />
          <button v-if="searchQuery" @click="searchQuery = ''" class="text-xs text-slate-400 hover:text-slate-700 font-bold cursor-pointer">
            ✕ Xóa
          </button>
        </div>

        <!-- ================= SUB-TAB 1: DANH SÁCH HÀNH KHÁCH & GỌI ĐIỆN NHANH (1-CLICK CALL) ================= -->
        <div v-if="activePickupSubTab === 'all_list'" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-4">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pb-3 border-b border-slate-100">
            <div>
              <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-phone-volume text-emerald-600 animate-pulse"></i>
                <span>DANH SÁCH HÀNH KHÁCH & GỌI ĐIỆN NHANH (1-CLICK CALL)</span>
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Lơ xe bấm trực tiếp vào nút gọi để quay số ngay, kiểm tra tiền cọc, thu tiền mặt và cho khách lên xe
              </p>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold bg-slate-100 text-slate-700 px-3 py-1.5 rounded-xl">
                Tổng: <strong>{{ allPassengers.length }}</strong> khách
              </span>
            </div>
          </div>

          <!-- Search Input & Filter Pills -->
          <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
              <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
              <input
                v-model="passengerSearchQuery"
                type="text"
                placeholder="🔍 Tìm nhanh: Gõ Số Ghế (vd: B04), SĐT hoặc Tên khách..."
                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:ring-2 focus:ring-red-500 outline-none"
              />
            </div>
            <div class="flex items-center gap-1.5 overflow-x-auto text-xs font-bold">
              <button
                v-for="f in [
                  { key: 'ALL', label: 'Tất Cả' },
                  { key: 'WAITING', label: 'Chưa Lên Xe' },
                  { key: 'BOARDED', label: 'Đã Lên Xe' },
                  { key: 'ROADSIDE', label: 'Khách Dọc Đường' },
                  { key: 'NO_SHOW', label: 'Bỏ Chuyến' }
                ]"
                :key="f.key"
                @click="passengerStatusFilter = f.key"
                :class="passengerStatusFilter === f.key ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                class="px-3.5 py-2 rounded-xl transition whitespace-nowrap cursor-pointer"
              >
                {{ f.label }}
              </button>
            </div>
          </div>

          <!-- Passenger Items Cards (MATCH 100% GIAO DIỆN BÁC TÀI & LƠ XE) -->
          <div v-if="filteredTripPassengers.length > 0" class="space-y-3">
            <div
              v-for="p in filteredTripPassengers"
              :key="'tab2-pax-' + (p.sub_id || (p.id + '_' + (p.seats ? p.seats.join('_') : '')))"
              class="p-4 rounded-2xl border transition flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
              :class="{
                'bg-emerald-50/50 border-emerald-300': p.checkin_status === 'CHECKED_IN',
                'bg-amber-50/40 border-amber-300': p.checkin_status === 'WAITING' || !p.checkin_status,
                'bg-rose-50/40 border-rose-300 opacity-75': p.checkin_status === 'NO_SHOW'
              }"
            >
              <!-- Left: Seat Badge & Passenger Info -->
              <div class="flex items-start space-x-3.5">
                <!-- Seat Code Badge -->
                <div
                  class="w-12 h-12 rounded-2xl flex flex-col items-center justify-center font-black font-mono shrink-0 shadow-xs"
                  :class="{
                    'bg-emerald-600 text-white': p.checkin_status === 'CHECKED_IN',
                    'bg-amber-500 text-white': p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW',
                    'bg-rose-600 text-white': p.checkin_status === 'NO_SHOW'
                  }"
                >
                  <span class="text-xs">{{ p.seats ? p.seats.join(',') : 'A01' }}</span>
                  <span class="text-[9px] font-sans font-bold opacity-90">
                    {{ p.seats && p.seats[0]?.startsWith('A') ? 'T1' : 'T2' }}
                  </span>
                </div>

                <!-- Customer Details -->
                <div class="space-y-1">
                  <div class="flex items-center space-x-2 flex-wrap">
                    <h4 class="font-black text-slate-900 text-sm">{{ p.customer_name }}</h4>
                    <span v-if="p.group_info" class="text-[10px] bg-blue-100 text-blue-800 font-extrabold px-2 py-0.5 rounded-md">
                      {{ p.group_info }}
                    </span>
                    <span v-if="p.is_roadside" class="text-[10px] bg-purple-100 text-purple-800 font-extrabold px-2 py-0.5 rounded-md">
                      Dọc đường
                    </span>
                    <span class="text-[10px] font-mono text-slate-400">#{{ p.booking_code }}</span>
                  </div>

                  <div class="text-xs text-slate-600 flex items-center space-x-2 flex-wrap">
                    <span>
                      <i class="fa-solid fa-route text-red-600 mr-1"></i>
                      {{ p.pickup_name || p.pickup_stop_name || 'Điểm đón' }} ➔ {{ p.dropoff_name || p.dropoff_stop_name || 'Điểm trả' }}
                    </span>
                    <span>•</span>
                    <span class="font-black text-slate-900">{{ formatMoney(p.total_amount) }}</span>
                  </div>

                  <!-- Thông tin thanh toán (Chuẩn logic: VNPAY / Chưa thanh toán tiền mặt) -->
                  <div class="text-xs flex items-center gap-2 flex-wrap pt-0.5">
                    <span
                      v-if="p.payment_status === 'PAID' || p.remaining_amount <= 0"
                      class="text-[11px] text-emerald-800 bg-emerald-100/80 px-2.5 py-0.5 rounded-lg inline-flex items-center gap-1 font-bold"
                    >
                      <i class="fa-solid fa-circle-check text-emerald-600"></i>
                      ✓ Đã thanh toán ({{ p.payment_method || 'VNPAY' }})
                    </span>
                    <span
                      v-else
                      class="text-[11px] text-amber-900 bg-amber-100/90 px-2.5 py-0.5 rounded-lg inline-flex items-center gap-1 font-bold"
                    >
                      <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                      {{ p.payment_badge || `⚠️ Chưa thanh toán (Còn thiếu ${formatMoney(p.remaining_amount)} tiền mặt)` }}
                    </span>

                    <span v-if="p.notes" class="text-[11px] text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                      📍 {{ p.notes }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Right: 1-Click Call & Action Buttons -->
              <div class="flex items-center flex-wrap gap-2 w-full md:w-auto justify-end pt-2 md:pt-0 border-t md:border-t-0 border-slate-100">
                <!-- NÚT GỌI ĐIỆN 1-CLICK -->
                <button
                  @click="makeCall(p.customer_phone)"
                  class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black flex items-center space-x-1.5 shadow-xs transition cursor-pointer"
                >
                  <i class="fa-solid fa-phone-volume animate-bounce"></i>
                  <span>Gọi {{ p.customer_phone }}</span>
                </button>

                <!-- Check-in / Cho Lên Xe -->
                <span
                  v-if="p.checkin_status === 'CHECKED_IN'"
                  class="px-3.5 py-2 bg-emerald-100 text-emerald-800 font-black rounded-xl text-xs border border-emerald-300 flex items-center gap-1"
                >
                  <i class="fa-solid fa-check-double"></i> Đã Lên Xe
                </span>
                <template v-else-if="p.checkin_status !== 'NO_SHOW'">
                  <!-- Nếu còn thiếu tiền: Thu tiền & Cho lên xe -->
                  <button
                    v-if="p.remaining_amount > 0"
                    @click="confirmManualCheckin(p, true)"
                    :disabled="isProcessing"
                    class="px-3.5 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black rounded-xl text-xs shadow-xs transition flex items-center gap-1 cursor-pointer"
                    title="Bắt buộc thu tiền mặt trước khi cho lên xe"
                  >
                    <i class="fa-solid fa-money-bill-wave"></i> Thu {{ formatMoney(p.remaining_amount) }} & Cho Lên Xe
                  </button>
                  <!-- Nếu đã trả đủ: Cho Lên Xe (Đã trả đủ) -->
                  <button
                    v-else
                    @click="confirmManualCheckin(p, false)"
                    :disabled="isProcessing"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl text-xs shadow-xs transition flex items-center gap-1 cursor-pointer"
                  >
                    <i class="fa-solid fa-user-check"></i> Cho Lên Xe (Đã trả đủ)
                  </button>
                </template>

                <!-- Nút Thu Tiền Mặt Riêng -->
                <button
                  v-if="p.remaining_amount > 0 && p.checkin_status !== 'NO_SHOW'"
                  @click="submitCollectCashOnly(p)"
                  :disabled="isProcessing"
                  class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-xs transition flex items-center gap-1 cursor-pointer"
                >
                  Thu {{ formatMoney(p.remaining_amount) }}
                </button>

                <!-- Nút Đổi Ghế -->
                <button
                  v-if="p.checkin_status !== 'NO_SHOW'"
                  @click="openChangeSeatModal(p)"
                  class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs border border-slate-200 transition cursor-pointer"
                >
                  Đổi Ghế
                </button>

                <!-- Bỏ Chuyến (No-show) -->
                <button
                  v-if="p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW'"
                  @click="openNoShowModal(p)"
                  class="px-3 py-2 bg-slate-50 hover:bg-rose-50 text-slate-500 hover:text-rose-600 font-bold rounded-xl text-xs border border-slate-200 transition cursor-pointer"
                >
                  Bỏ Chuyến
                </button>

                <!-- Giải phóng ghế khi No-show -->
                <button
                  v-if="p.checkin_status === 'NO_SHOW'"
                  @click="submitReleaseSeatConfirmed(p)"
                  class="px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-xl text-xs shadow-xs transition cursor-pointer"
                >
                  Giải Phóng Ghế
                </button>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-slate-400 text-xs font-bold">
            Không tìm thấy hành khách nào phù hợp với bộ lọc hiện tại.
          </div>
        </div>

        <!-- SUB-TAB A: ĐÓN CỐ ĐỊNH (Không trùng khách đón dọc đường) -->
        <div v-if="activePickupSubTab === 'fixed'" class="space-y-4">
          <div v-for="station in stationPickups" :key="station.stop_id" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-4">
            <div class="flex flex-wrap justify-between items-center gap-2 pb-3 border-b border-slate-100">
              <div>
                <div class="flex items-center gap-2">
                  <span class="w-6 h-6 rounded-full bg-red-100 text-red-700 font-black text-xs flex items-center justify-center">
                    {{ station.stop_order }}
                  </span>
                  <h2 class="text-base font-black text-slate-900">{{ station.stop_name }}</h2>
                </div>
                <p class="text-xs text-slate-500 mt-0.5 ml-8">📍 {{ station.address || 'Điểm đón cố định' }}</p>
              </div>

              <div class="flex items-center gap-2">
                <span class="text-xs font-bold px-3 py-1 rounded-xl bg-slate-100 text-slate-700">
                  Tổng: {{ station.total_pax }} khách
                </span>
                <span class="text-xs font-black px-3 py-1 rounded-xl bg-emerald-100 text-emerald-800">
                  ✓ Đã lên: {{ station.boarded_count }}
                </span>
                <span v-if="station.waiting_count > 0" class="text-xs font-black px-3 py-1 rounded-xl bg-amber-100 text-amber-800 animate-pulse">
                  ⏳ Đang đợi: {{ station.waiting_count }}
                </span>
              </div>
            </div>

            <!-- Danh sách hành khách tại bến (áp dụng bộ lọc ẩn khách đã lên xe) -->
            <div v-if="station.passengers && station.passengers.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <template
                v-for="p in station.passengers"
                :key="'st-pax-' + (p.sub_id || (p.id + '_' + (p.seats ? p.seats.join('_') : '')))"
              >
                <div
                  v-if="!hideBoardedPax || p.checkin_status !== 'CHECKED_IN'"
                  :class="{
                    'bg-emerald-50/70 border-emerald-300': p.checkin_status === 'CHECKED_IN',
                    'bg-amber-50/50 border-amber-300': p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW',
                    'bg-slate-50 border-slate-200 opacity-60': p.checkin_status === 'NO_SHOW'
                  }"
                  class="p-4 rounded-2xl border space-y-3"
                >
                  <!-- Line 1: Seat & Name & Phone (1-Click Call) -->
                  <div class="flex justify-between items-start gap-2">
                    <div>
                      <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-xl bg-slate-900 text-white font-mono font-black text-xs">
                          Ghế: {{ p.seats ? p.seats.join(', ') : 'N/A' }}
                        </span>
                        <span class="font-black text-sm text-slate-900">{{ p.customer_name }}</span>
                      </div>

                      <!-- 1-Click Call -->
                      <div class="mt-2">
                        <button
                          @click="makeCall(p.customer_phone)"
                          class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-2 cursor-pointer shadow-sm transition transform active:scale-95"
                          title="Gọi điện nhanh 1-Click"
                        >
                          <i class="fa-solid fa-phone-volume text-sm animate-bounce"></i>
                          <span class="font-mono text-sm tracking-wide">{{ p.customer_phone }}</span>
                          <span class="text-[10px] bg-white/20 px-1.5 py-0.2 rounded font-normal">Gọi ngay</span>
                        </button>
                      </div>
                    </div>

                    <div class="text-right">
                      <span
                        v-if="p.checkin_status === 'CHECKED_IN'"
                        class="px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-600 text-white"
                      >
                        ✓ ĐÃ LÊN XE
                      </span>
                      <span
                        v-else-if="p.checkin_status === 'NO_SHOW'"
                        class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-600 text-white"
                      >
                        ✕ BỎ CHUYẾN
                      </span>
                      <span
                        v-else
                        class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500 text-slate-950"
                      >
                        ⏳ CHƯA LÊN
                      </span>
                    </div>
                  </div>

                  <!-- Chi tiết tài chính vé & Nhãn thanh toán chính xác (Requirement 1) -->
                  <div class="p-2.5 rounded-xl bg-white/90 border border-slate-200 text-xs space-y-1">
                    <div class="flex justify-between items-center">
                      <span class="text-slate-500">Giá vé:</span>
                      <strong class="font-mono text-slate-900 font-bold">{{ formatMoney(p.total_amount) }}</strong>
                    </div>
                    <div class="flex justify-between items-center">
                      <span class="text-slate-500">Trạng thái thanh toán:</span>
                      <span
                        class="text-[10px] px-2 py-0.5 rounded-md font-black"
                        :class="{
                          'bg-emerald-100 text-emerald-800': p.payment_status === 'PAID',
                          'bg-amber-100 text-amber-800': p.paid_amount > 0 && p.payment_status !== 'PAID',
                          'bg-red-100 text-red-800': p.paid_amount === 0
                        }"
                      >
                        {{ p.payment_badge }}
                      </span>
                    </div>
                    <div class="flex justify-between items-center pt-1 border-t border-slate-100">
                      <span class="font-bold" :class="p.remaining_amount > 0 ? 'text-red-600' : 'text-slate-500'">Còn thiếu (Cần thu):</span>
                      <strong
                        class="font-mono font-black text-sm"
                        :class="p.remaining_amount > 0 ? 'text-red-600 animate-pulse' : 'text-emerald-600'"
                      >
                        {{ formatMoney(p.remaining_amount) }}
                      </strong>
                    </div>
                    <div class="text-[11px] text-slate-400 pt-0.5">Trả tại: {{ p.dropoff_stop_name }}</div>
                  </div>

                  <!-- Action Buttons: Cho lên xe & Thu tiền (Disabled nếu COMPLETED) -->
                  <div class="flex items-center gap-2 pt-1">
                    <template v-if="p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW'">
                      <!-- Nút Lên xe: Nếu còn tiền thì bật popup hỏi nhỏ xác nhận đã thu tiền (Requirement 1) -->
                      <button
                        @click="openManualCheckinPrompt(p, p.remaining_amount > 0)"
                        :disabled="isTripCompleted"
                        :class="{
                          'opacity-50 cursor-not-allowed bg-slate-300 text-slate-600': isTripCompleted,
                          'bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 cursor-pointer shadow-sm active:scale-95': !isTripCompleted && p.remaining_amount > 0,
                          'bg-emerald-600 hover:bg-emerald-500 text-white cursor-pointer shadow-sm active:scale-95': !isTripCompleted && p.remaining_amount === 0
                        }"
                        class="flex-1 py-2.5 rounded-xl font-black text-xs flex items-center justify-center gap-1.5 transition"
                      >
                        <i class="fa-solid" :class="p.remaining_amount > 0 ? 'fa-money-bill-wave' : 'fa-check'"></i>
                        <span v-if="p.remaining_amount > 0">Thu {{ formatMoney(p.remaining_amount) }} & Lên Xe</span>
                        <span v-else>Cho Lên Xe (Đã trả đủ)</span>
                      </button>

                      <button
                        @click="openNoShowModal(p)"
                        :disabled="isTripCompleted"
                        :class="isTripCompleted ? 'opacity-40 cursor-not-allowed' : 'hover:bg-rose-100 cursor-pointer'"
                        class="px-3 py-2.5 rounded-xl bg-rose-50 text-rose-700 font-bold text-xs"
                        title="Đánh dấu khách bỏ chuyến"
                      >
                        Bỏ chuyến
                      </button>
                    </template>

                    <div v-else-if="p.checkin_status === 'CHECKED_IN'" class="flex-1 flex items-center justify-between text-xs text-emerald-800 bg-emerald-100/60 p-2 rounded-xl">
                      <span class="font-bold">✓ Đã lên xe lúc: {{ p.checked_in_at || 'Mới đây' }}</span>
                      <button
                        @click="openChangeSeatModal({ id: p.id, customer_name: p.customer_name, seat_number: p.seats[0] })"
                        :disabled="isTripCompleted"
                        :class="isTripCompleted ? 'opacity-40 cursor-not-allowed' : 'hover:underline text-blue-700 cursor-pointer'"
                        class="font-bold text-[11px]"
                      >
                        Đổi ghế
                      </button>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>

        <!-- SUB-TAB B: ĐÓN DỌC ĐƯỜNG (WEB) -->
        <div v-if="activePickupSubTab === 'roadside'" class="space-y-4">
          <div class="bg-amber-50 rounded-3xl p-5 border border-amber-200">
            <div class="flex items-center gap-2 text-amber-900 font-black text-sm">
              <i class="fa-solid fa-road text-base text-amber-600"></i>
              <span>KHÁCH ĐÓN DỌC ĐƯỜNG (ĐẶT QUA WEB HOẶC BẮT DỌC ĐƯỜNG)</span>
            </div>
            <p class="text-xs text-amber-800 mt-1">
              Khách đứng tại các ngã ba, cây xăng, khu công nghiệp hoặc vị trí tự do theo lộ trình.
            </p>
          </div>

          <div v-if="roadsidePickups && roadsidePickups.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <template
              v-for="p in roadsidePickups"
              :key="'road-pax-' + (p.sub_id || (p.id + '_' + (p.seats ? p.seats.join('_') : '')))"
            >
              <div
                v-if="!hideBoardedPax || p.checkin_status !== 'CHECKED_IN'"
                :class="{
                  'bg-emerald-50/70 border-emerald-300': p.checkin_status === 'CHECKED_IN',
                  'bg-amber-50/50 border-amber-300': p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW',
                  'bg-slate-50 border-slate-200 opacity-60': p.checkin_status === 'NO_SHOW'
                }"
                class="p-4 rounded-2xl border space-y-3 bg-white"
              >
                <!-- Line 1: Seat & Name & Phone -->
                <div class="flex justify-between items-start gap-2">
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="px-2.5 py-1 rounded-xl bg-amber-500 text-slate-950 font-mono font-black text-xs">
                        Ghế: {{ p.seats ? p.seats.join(', ') : 'N/A' }}
                      </span>
                      <span class="font-black text-sm text-slate-900">{{ p.customer_name }}</span>
                    </div>

                    <div class="mt-2">
                      <button
                        @click="makeCall(p.customer_phone)"
                        class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-2 cursor-pointer shadow-sm transition transform active:scale-95"
                      >
                        <i class="fa-solid fa-phone-volume text-sm animate-bounce"></i>
                        <span class="font-mono text-sm tracking-wide">{{ p.customer_phone }}</span>
                        <span class="text-[10px] bg-white/20 px-1.5 py-0.2 rounded font-normal">Gọi ngay</span>
                      </button>
                    </div>
                  </div>

                  <div class="text-right">
                    <span
                      v-if="p.checkin_status === 'CHECKED_IN'"
                      class="px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-600 text-white"
                    >
                      ✓ ĐÃ LÊN XE
                    </span>
                    <span
                      v-else-if="p.checkin_status === 'NO_SHOW'"
                      class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-600 text-white"
                    >
                      ✕ BỎ CHUYẾN
                    </span>
                    <span
                      v-else
                      class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500 text-slate-950"
                    >
                      ⏳ CHƯA LÊN
                    </span>
                  </div>
                </div>

                <!-- Vị trí đón tự do -->
                <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900 space-y-1">
                  <div class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-location-dot text-red-600"></i>
                    <span>Điểm đón: {{ p.notes || 'Đón dọc đường theo lộ trình' }}</span>
                  </div>
                  <div class="text-[11px] text-amber-800">Điểm xuống: {{ p.dropoff_stop_name }}</div>
                </div>

                <!-- Chi tiết thanh toán -->
                <div class="p-2.5 rounded-xl bg-white border border-slate-200 text-xs space-y-1">
                  <div class="flex justify-between items-center">
                    <span class="text-slate-500">Giá vé:</span>
                    <strong class="font-mono text-slate-900 font-bold">{{ formatMoney(p.total_amount) }}</strong>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-slate-500">Trạng thái:</span>
                    <span
                      class="text-[10px] px-2 py-0.5 rounded-md font-black"
                      :class="p.payment_status === 'PAID' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                    >
                      {{ p.payment_badge }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center pt-1 border-t border-slate-100">
                    <span class="font-bold text-red-600">Còn thiếu (Cần thu):</span>
                    <strong class="font-mono font-black text-sm text-red-600">
                      {{ formatMoney(p.remaining_amount) }}
                    </strong>
                  </div>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2 pt-1">
                  <template v-if="p.checkin_status !== 'CHECKED_IN' && p.checkin_status !== 'NO_SHOW'">
                    <button
                      @click="openManualCheckinPrompt(p, p.remaining_amount > 0)"
                      :disabled="isTripCompleted"
                      :class="{
                        'opacity-50 cursor-not-allowed bg-slate-300 text-slate-600': isTripCompleted,
                        'bg-gradient-to-r from-amber-500 to-amber-600 text-slate-950 cursor-pointer shadow-sm active:scale-95': !isTripCompleted && p.remaining_amount > 0,
                        'bg-emerald-600 text-white cursor-pointer shadow-sm active:scale-95': !isTripCompleted && p.remaining_amount === 0
                      }"
                      class="flex-1 py-2.5 rounded-xl font-black text-xs flex items-center justify-center gap-1.5 transition"
                    >
                      <i class="fa-solid" :class="p.remaining_amount > 0 ? 'fa-money-bill-wave' : 'fa-check'"></i>
                      <span v-if="p.remaining_amount > 0">Thu {{ formatMoney(p.remaining_amount) }} & Lên Xe</span>
                      <span v-else>Cho Lên Xe</span>
                    </button>

                    <button
                      @click="openNoShowModal(p)"
                      :disabled="isTripCompleted"
                      :class="isTripCompleted ? 'opacity-40 cursor-not-allowed' : 'hover:bg-rose-100 cursor-pointer'"
                      class="px-3 py-2.5 rounded-xl bg-rose-50 text-rose-700 font-bold text-xs"
                    >
                      Bỏ chuyến
                    </button>
                  </template>

                  <div v-else-if="p.checkin_status === 'CHECKED_IN'" class="flex-1 flex items-center justify-between text-xs text-emerald-800 bg-emerald-100/60 p-2 rounded-xl">
                    <span class="font-bold">✓ Đã đón lên xe: {{ p.checked_in_at || 'Mới đây' }}</span>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div v-else class="text-center py-10 bg-white rounded-3xl border border-slate-200 text-slate-500 text-xs">
            Chưa có khách nào đăng ký đón dọc đường trong chuyến này.
          </div>
        </div>

        <!-- SUB-TAB C: ĐIỂM TRẢ KHÁCH -->
        <div v-if="activePickupSubTab === 'dropoff'" class="space-y-4">
          <div v-for="station in stationDropoffs" :key="'drop-' + station.stop_id" class="bg-white rounded-3xl p-5 border border-slate-200 shadow-xs space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
              <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 font-black text-xs flex items-center justify-center">
                  {{ station.stop_order }}
                </span>
                <h3 class="text-base font-black text-slate-900">{{ station.stop_name }}</h3>
              </div>
              <span class="text-xs font-black px-3 py-1 rounded-xl bg-blue-100 text-blue-800 font-mono">
                {{ station.total_pax }} khách xuống
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
              <div v-for="p in station.passengers" :key="'p-drop-' + p.id" class="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                <div class="flex justify-between items-center">
                  <span class="px-2 py-0.5 rounded-lg bg-slate-900 text-white font-mono font-bold text-[10px]">
                    Ghế: {{ p.seats.join(', ') }}
                  </span>
                  <strong class="text-slate-900">{{ p.customer_name }}</strong>
                </div>
                <div class="text-[11px] text-slate-500 font-mono flex items-center justify-between pt-1">
                  <span>{{ p.customer_phone }}</span>
                  <button @click="makeCall(p.customer_phone)" class="text-blue-600 font-bold hover:underline">
                    Gọi
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= TAB 3: QUÉT QR & CHECK-IN (REQUIREMENT 9: 2 TRƯỜNG HỢP RÕ RÀNG) ================= -->
      <section v-if="activeMainTab === 'checkin'" class="space-y-5">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Left: Camera Viewfinder & Fast Scanner -->
          <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
              <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-camera text-red-600"></i>
                <span>Camera Quét Mã QR Của Khách</span>
              </h2>
              <button
                v-if="!isCameraActive"
                @click="startCamera"
                :disabled="isTripCompleted"
                :class="isTripCompleted ? 'opacity-40 cursor-not-allowed bg-slate-300 text-slate-700' : 'bg-emerald-600 text-white cursor-pointer'"
                class="px-3 py-1.5 rounded-xl font-bold text-xs flex items-center gap-1"
              >
                <i class="fa-solid fa-play"></i> Bật Camera
              </button>
              <button
                v-else
                @click="stopCamera"
                class="px-3 py-1.5 rounded-xl bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1 cursor-pointer"
              >
                <i class="fa-solid fa-stop"></i> Tắt Camera
              </button>
            </div>

            <!-- Camera Viewfinder Box -->
            <div class="relative rounded-3xl overflow-hidden bg-black aspect-video flex items-center justify-center border-2 border-red-500">
              <div id="qr-reader" class="w-full h-full"></div>
              <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                <div class="w-48 h-48 border-2 border-dashed border-red-400 rounded-2xl relative">
                  <div class="w-full h-0.5 bg-red-500 shadow-lg shadow-red-500 absolute top-1/2 -translate-y-1/2 animate-pulse"></div>
                </div>
              </div>
            </div>

            <!-- Direct Code Input Fallback -->
            <div class="space-y-2 pt-2">
              <label class="text-xs font-bold text-slate-700 uppercase">
                Nhập Mã Vé hoặc SĐT (Dự phòng):
              </label>
              <div class="flex gap-2">
                <input
                  v-model="qrScanInput"
                  type="text"
                  placeholder="Mã vé (KT-..., BH-...) hoặc SĐT (0905...)"
                  class="flex-1 px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 font-mono"
                  @keyup.enter="submitManualCodeCheckin"
                />
                <button
                  @click="submitManualCodeCheckin"
                  :disabled="isProcessing || isTripCompleted"
                  :class="isTripCompleted ? 'opacity-40 cursor-not-allowed bg-slate-400' : 'bg-red-600 hover:bg-red-500 cursor-pointer'"
                  class="px-4 py-2.5 rounded-xl text-white font-black text-xs"
                >
                  Kiểm tra
                </button>
              </div>
            </div>

            <!-- LIVE SCAN RESULT BOX (REQUIREMENT 9: PHÂN TÁCH 2 TRƯỜNG HỢP) -->
            <div v-if="qrScanResult" class="p-4 rounded-2xl space-y-3"
              :class="{
                'bg-emerald-50 border-2 border-emerald-500 text-emerald-950': qrScanResult.success && !qrScanResult.needs_cash_collection,
                'bg-amber-50 border-2 border-amber-500 text-amber-950': qrScanResult.needs_cash_collection,
                'bg-red-50 border-2 border-red-500 text-red-950': !qrScanResult.success && !qrScanResult.needs_cash_collection
              }"
            >
              <!-- TRƯỜNG HỢP 1: KHÁCH ĐÃ THANH TOÁN FULL 100% -->
              <template v-if="qrScanResult.success && !qrScanResult.needs_cash_collection">
                <div class="flex items-start gap-2.5">
                  <span class="text-3xl">✅</span>
                  <div>
                    <div class="font-black text-base text-emerald-800">
                      ✓ ĐÃ THANH TOÁN 100% ONLINE (VÉ HỢP LỆ)
                    </div>
                    <p class="text-xs mt-0.5 font-bold text-emerald-900">{{ qrScanResult.message }}</p>
                  </div>
                </div>

                <div v-if="qrScanResult.booking" class="bg-white p-3.5 rounded-2xl border border-emerald-200 text-xs space-y-1.5 font-medium">
                  <div class="flex justify-between">
                    <span class="text-slate-500">Khách hàng:</span>
                    <strong class="text-slate-900 font-black">{{ qrScanResult.booking.customer_name }}</strong>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-500">Số ghế:</span>
                    <strong class="font-mono text-emerald-700 font-black text-sm">{{ qrScanResult.booking.seats ? qrScanResult.booking.seats.join(', ') : 'A01' }}</strong>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-500">Tổng tiền vé:</span>
                    <strong class="font-mono text-slate-900 font-bold">{{ formatMoney(qrScanResult.booking.total_amount) }}</strong>
                  </div>
                  <div class="p-2 rounded-xl bg-emerald-100 text-emerald-900 font-bold text-center mt-2">
                    ✓ Hệ thống đã tự động Check-in. Lơ xe chỉ cần mời khách lên đúng ghế!
                  </div>

                  <button
                    @click="startCamera(); qrScanResult = null; qrScanInput = ''"
                    class="w-full mt-2.5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition"
                  >
                    <i class="fa-solid fa-camera"></i>
                    <span>Quét Vé Khách Tiếp Theo</span>
                  </button>
                </div>
              </template>

              <!-- TRƯỜNG HỢP 2: KHÁCH MỚI CỌC 30% HOẶC CHƯA THANH TOÁN (ÉP BUỘC THU TIỀN) -->
              <template v-else-if="qrScanResult.needs_cash_collection">
                <div class="flex items-start gap-2.5">
                  <span class="text-3xl animate-bounce">⚠️</span>
                  <div>
                    <div class="font-black text-base text-red-700 uppercase">
                      CẢNH BÁO: CHƯA THU ĐỦ TIỀN VÉ!
                    </div>
                    <p class="text-xs mt-0.5 font-bold text-red-900 leading-relaxed">
                      Khách mới thanh toán một phần hoặc chọn trả tiền mặt. Bắt buộc lơ xe phải cầm tiền trước khi cho lên xe!
                    </p>
                  </div>
                </div>

                <div v-if="qrScanResult.booking" class="bg-white p-4 rounded-2xl border-2 border-red-300 space-y-3">
                  <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Hành khách:</span>
                    <strong class="text-slate-900 font-black text-sm">{{ qrScanResult.booking.customer_name }}</strong>
                  </div>
                  <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Số ghế đặt:</span>
                    <strong class="font-mono text-slate-900 font-black text-sm bg-slate-100 px-2 py-0.5 rounded">{{ qrScanResult.booking.seats ? qrScanResult.booking.seats.join(', ') : 'A05' }}</strong>
                  </div>

                  <!-- PHÉP TÍNH MINH BẠCH -->
                  <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs space-y-1.5">
                    <div class="flex justify-between text-slate-600">
                      <span>Tổng tiền vé:</span>
                      <span class="font-mono font-bold">{{ formatMoney(qrScanResult.booking.total_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-blue-700">
                      <span>Đã cọc qua Web:</span>
                      <span class="font-mono font-bold">- {{ formatMoney(qrScanResult.booking.paid_amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                      <span class="font-black text-red-600 uppercase text-xs">SỐ TIỀN CÒN PHẢI THU:</span>
                      <span class="font-mono font-black text-xl text-red-600">
                        {{ formatMoney(qrScanResult.remaining_amount) }}
                      </span>
                    </div>
                  </div>

                  <!-- BLOCKER ACTION BUTTON -->
                  <button
                    @click="confirmManualCheckin(qrScanResult.booking, true)"
                    class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-700 hover:to-amber-700 text-white font-black text-sm cursor-pointer shadow-lg shadow-red-600/30 flex items-center justify-center gap-2 transition transform active:scale-95"
                  >
                    <i class="fa-solid fa-money-bill-wave text-base"></i>
                    <span>ĐÃ THU ĐỦ {{ formatMoney(qrScanResult.remaining_amount) }} & CHO LÊN XE</span>
                  </button>
                </div>
              </template>

              <!-- LỖI QUÉT MÃ / VÉ KHÔNG HỢP LỆ -->
              <template v-else>
                <div class="flex items-start gap-2.5">
                  <span class="text-2xl">❌</span>
                  <div>
                    <div class="font-black text-sm text-red-700">CẢNH BÁO: VÉ KHÔNG HỢP LỆ</div>
                    <p class="text-xs mt-0.5 font-bold text-red-800 leading-relaxed">{{ qrScanResult.message }}</p>
                  </div>
                </div>

                <button
                  @click="startCamera(); qrScanResult = null; qrScanInput = ''"
                  class="w-full mt-2.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center justify-center gap-1.5 cursor-pointer shadow-xs transition"
                >
                  <i class="fa-solid fa-rotate-right"></i>
                  <span>Quét Lại Mã Khác</span>
                </button>
              </template>
            </div>
          </div>

          <!-- Right: TÌNH HUỐNG 1 - TÌM KIẾM NHANH KHÁCH MẠNG YẾU / MẤT SÓNG 4G -->
          <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
              <span class="text-2xl">📶</span>
              <div>
                <h2 class="text-base font-black text-slate-900">TÌNH HUỐNG 1: KHÁCH MẤT 4G / PIN YẾU</h2>
                <p class="text-xs text-slate-500">Check-in ngoại lệ không cần quét mã QR</p>
              </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
              Nếu điện thoại của khách hết pin hoặc không có kết nối mạng để mở mã QR, lơ xe chỉ cần nhập <strong>4 số cuối số điện thoại</strong> hoặc <strong>họ tên</strong> khách để tìm nhanh:
            </p>

            <div class="relative">
              <input
                v-model="exceptionSearchQuery"
                type="text"
                placeholder="Gõ 4 số cuối SĐT (VD: 1133) hoặc tên..."
                class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-300 text-xs sm:text-sm font-bold text-slate-900 outline-none focus:border-red-500 focus:bg-white transition"
              />
              <span v-if="exceptionSearchQuery" @click="exceptionSearchQuery = ''" class="absolute right-3 top-3.5 text-xs text-slate-400 hover:text-slate-700 cursor-pointer">✕</span>
            </div>

            <!-- Exception Search Results -->
            <div v-if="exceptionSearchQuery" class="space-y-2 pt-2 max-h-72 overflow-y-auto">
              <div v-if="exceptionSearchResults.length === 0" class="text-center py-4 text-xs text-slate-400 font-bold">
                Không tìm thấy khách hàng nào khớp với "{{ exceptionSearchQuery }}".
              </div>

              <div
                v-for="p in exceptionSearchResults"
                :key="'exc-' + p.id"
                class="p-3.5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between gap-3 text-xs"
              >
                <div>
                  <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-lg bg-slate-900 text-white font-mono font-black text-[11px]">
                      {{ p.seats ? p.seats.join(', ') : 'N/A' }}
                    </span>
                    <strong class="text-slate-900 font-bold">{{ p.customer_name }}</strong>
                  </div>
                  <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                    {{ p.customer_phone }} • {{ p.pickup_stop_name }}
                  </div>
                  <div class="text-[10px] font-bold mt-1" :class="p.remaining_amount > 0 ? 'text-red-600' : 'text-emerald-700'">
                    {{ p.remaining_amount > 0 ? '⚠️ Còn thiếu: ' + formatMoney(p.remaining_amount) : '✓ Đã thanh toán đủ' }}
                  </div>
                </div>

                <div>
                  <button
                    v-if="p.checkin_status !== 'CHECKED_IN'"
                    @click="openManualCheckinPrompt(p, p.remaining_amount > 0)"
                    :disabled="isTripCompleted"
                    :class="{
                      'opacity-50 cursor-not-allowed bg-slate-300 text-slate-600': isTripCompleted,
                      'bg-emerald-600 hover:bg-emerald-500 text-white cursor-pointer': !isTripCompleted
                    }"
                    class="px-3.5 py-2 rounded-xl font-black text-xs shadow-xs"
                  >
                    Cho lên xe
                  </button>
                  <span v-else class="text-xs font-black text-emerald-700">✓ Đã lên</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= TAB 4: BẢN ĐỒ LIVE & TUYẾN ĐƯỜNG ĐANG CHẠY ================= -->
      <section v-if="activeMainTab === 'map'" class="space-y-5">
        <!-- Top Telemetry Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
          <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase text-slate-400">VẬN TỐC GPS HIỆN TẠI</p>
              <p class="text-2xl font-black text-slate-900 mt-1">
                {{ isTripCompleted ? '0' : (isTripDeparted ? '62' : '0') }} <span class="text-xs text-slate-500 font-medium">km/h</span>
              </p>
              <p class="text-[11px] font-bold mt-0.5" :class="isTripCompleted ? 'text-emerald-600' : (isTripDeparted ? 'text-emerald-600' : 'text-amber-600')">
                {{ isTripCompleted ? '🏁 Đã về bến an toàn' : (isTripDeparted ? '● Chạy đúng tốc độ' : '● Xe chưa xuất bến (Đang chờ tại bến)') }}
              </p>
            </div>
            <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl" :class="isTripCompleted ? 'bg-emerald-50 text-emerald-600' : (isTripDeparted ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600')">
              {{ isTripCompleted ? '🏁' : (isTripDeparted ? '⚡' : '⏸️') }}
            </div>
          </div>

          <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase text-slate-400">TRẠM TIẾP THEO</p>
              <p class="text-base font-black text-slate-900 mt-1 truncate max-w-[150px]">
                {{ isTripCompleted ? (stops?.[stops.length - 1]?.stop_name || 'Bến đích') : (!isTripDeparted ? (stops?.[0]?.stop_name || 'Bến xuất phát') : (stops && stops[currentTrip?.current_stop_order || 0] ? stops[currentTrip.current_stop_order].stop_name : 'Bến đến')) }}
              </p>
              <p class="text-[11px] font-bold mt-0.5" :class="isTripCompleted ? 'text-emerald-600 font-black' : (isTripDeparted ? 'text-blue-600' : 'text-slate-400')">
                {{ isTripCompleted ? '🏁 Hoàn thành hành trình' : (isTripDeparted ? 'Dự kiến đến: 15 phút' : 'Chờ xuất bến đón khách') }}
              </p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">📍</div>
          </div>

          <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase text-slate-400">TIẾN ĐỘ HÀNH TRÌNH</p>
              <p class="text-2xl font-black text-slate-900 mt-1">
                {{ isTripCompleted ? (stops?.length || 4) : (isTripDeparted ? ((currentTrip?.current_stop_order || 0) + 1) : 0) }} / {{ stops?.length || 4 }} <span class="text-xs text-slate-500 font-medium">trạm</span>
              </p>
              <p class="text-[11px] font-bold mt-0.5" :class="isTripCompleted ? 'text-emerald-600' : 'text-purple-600'">
                {{ isTripCompleted ? '100% Hoàn thành an toàn' : 'Lộ trình QL1A' }}
              </p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">🛣️</div>
          </div>

          <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase text-slate-400">XE & BÁC TÀI PHỐI HỢP</p>
              <p class="text-sm font-black text-slate-900 mt-1 font-mono">{{ currentBus?.license_plate || '43B-012.34' }}</p>
              <p class="text-[11px] text-slate-600 font-medium mt-0.5">Tài xế: {{ currentTrip?.driver?.name || 'Nguyễn Văn Hùng' }}</p>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">🚌</div>
          </div>
        </div>

        <!-- Live Route Map Container -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs p-5 space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" :class="isTripDeparted ? 'bg-red-600 animate-ping' : 'bg-amber-500'"></span>
                <h3 class="font-black text-base text-slate-900">BẢN ĐỒ VỊ TRÍ XE & LỘ TRÌNH (GOOGLE MAPS REAL-TIME GPS)</h3>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">Theo dõi trực tiếp vị trí xe di chuyển trên Google Maps, danh sách trạm dừng và khoảng cách còn lại</p>
            </div>

            <!-- Layer Switcher & Refresh Buttons (Giống Cockpit Bác Tài) -->
            <div class="flex items-center gap-1.5 flex-wrap">
              <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl border border-slate-200 text-xs">
                <button
                  type="button"
                  @click="setConductorMapLayer('google')"
                  class="px-2.5 py-1 rounded-xl font-bold transition flex items-center gap-1 cursor-pointer"
                  :class="conductorMapLayerMode === 'google' ? 'bg-white text-red-600 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900'"
                >
                  <i class="fa-solid fa-map text-red-500"></i>
                  <span>Google Maps</span>
                </button>
                <button
                  type="button"
                  @click="setConductorMapLayer('satellite')"
                  class="px-2.5 py-1 rounded-xl font-bold transition flex items-center gap-1 cursor-pointer"
                  :class="conductorMapLayerMode === 'satellite' ? 'bg-white text-blue-600 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900'"
                >
                  <i class="fa-solid fa-satellite text-blue-500"></i>
                  <span>Vệ Tinh</span>
                </button>
                <button
                  type="button"
                  @click="setConductorMapLayer('dark')"
                  class="px-2.5 py-1 rounded-xl font-bold transition flex items-center gap-1 cursor-pointer"
                  :class="conductorMapLayerMode === 'dark' ? 'bg-slate-900 text-white shadow-xs font-black' : 'text-slate-600 hover:text-slate-900'"
                >
                  <i class="fa-solid fa-moon text-amber-400"></i>
                  <span>Ban Đêm</span>
                </button>
              </div>

              <button
                @click="initConductorMap"
                class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition cursor-pointer self-start sm:self-auto"
                title="Làm mới vị trí bản đồ"
              >
                <span>🔄</span>
                <span>Làm mới GPS</span>
              </button>
            </div>
          </div>

          <!-- Leaflet Map Element -->
          <div id="conductor-live-route-map" class="w-full h-96 sm:h-[450px] rounded-2xl border border-slate-200 overflow-hidden relative z-10 bg-slate-50"></div>

          <!-- Stops Timeline below map -->
          <div class="pt-2 border-t border-slate-100">
            <h4 class="text-xs font-bold uppercase text-slate-400 tracking-wider mb-3">Lộ Trình Các Trạm Dừng & Điểm Đón / Trả:</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
              <div
                v-for="(st, sIdx) in (stops || [])"
                :key="st.id"
                class="p-3 rounded-2xl border transition"
                :class="[
                  isTripCompleted
                    ? (sIdx === stops.length - 1 ? 'bg-emerald-100/90 border-emerald-400 ring-2 ring-emerald-500' : 'bg-emerald-50/60 border-emerald-200')
                    : (!isTripDeparted
                        ? (sIdx === 0 ? 'bg-amber-50 border-amber-300 ring-2 ring-amber-400' : 'bg-slate-50 border-slate-200')
                        : (sIdx === (currentTrip?.current_stop_order || 0)
                            ? 'bg-red-50 border-red-300 ring-2 ring-red-500'
                            : (sIdx < (currentTrip?.current_stop_order || 0)
                                ? 'bg-emerald-50/60 border-emerald-200'
                                : 'bg-slate-50 border-slate-200')))
                ]"
              >
                <div class="flex items-center justify-between text-[11px] mb-1">
                  <span
                    class="font-black font-mono px-2 py-0.5 rounded-md"
                    :class="[
                      isTripCompleted
                        ? (sIdx === stops.length - 1 ? 'bg-emerald-700 text-white' : 'bg-emerald-600 text-white')
                        : (!isTripDeparted
                            ? (sIdx === 0 ? 'bg-amber-500 text-white' : 'bg-slate-300 text-slate-700')
                            : (sIdx === (currentTrip?.current_stop_order || 0)
                                ? 'bg-red-600 text-white'
                                : (sIdx < (currentTrip?.current_stop_order || 0)
                                    ? 'bg-emerald-600 text-white'
                                    : 'bg-slate-300 text-slate-700')))
                    ]"
                  >
                    Trạm {{ sIdx + 1 }}
                  </span>
                  <span
                    class="font-bold text-[10px]"
                    :class="[
                      isTripCompleted
                        ? 'text-emerald-800 font-black'
                        : (!isTripDeparted
                            ? (sIdx === 0 ? 'text-amber-700 font-black animate-pulse' : 'text-slate-400')
                            : (sIdx === (currentTrip?.current_stop_order || 0)
                                ? 'text-red-700 font-black animate-pulse'
                                : (sIdx < (currentTrip?.current_stop_order || 0)
                                    ? 'text-emerald-700 font-bold'
                                    : 'text-slate-400')))
                    ]"
                  >
                    {{
                      isTripCompleted
                        ? (sIdx === stops.length - 1 ? '🏁 ĐÃ ĐẾN BẾN ĐÍCH' : '✓ ĐÃ QUA')
                        : (!isTripDeparted
                            ? (sIdx === 0 ? '● BẾN XUẤT PHÁT (CHỜ LÊN XE)' : '⏳ SẮP ĐẾN')
                            : (sIdx === (currentTrip?.current_stop_order || 0)
                                ? '● ĐANG DỪNG ĐÓN'
                                : (sIdx < (currentTrip?.current_stop_order || 0)
                                    ? '✓ ĐÃ QUA'
                                    : '⏳ SẮP ĐẾN')))
                    }}
                  </span>
                </div>
                <h5 class="font-bold text-slate-900 text-xs">{{ st.stop_name }}</h5>
                <p class="text-[10px] text-slate-500 mt-0.5 line-clamp-1">{{ st.address || 'Quốc lộ 1A' }}</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= TAB 5: LỊCH SỬ CA CHẠY CŨ (CHỐT CA & DOANH THU) ================= -->
      <section v-if="activeMainTab === 'history'" class="space-y-5">
        <!-- Summary metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase text-slate-400">TỔNG SỐ CA ĐÃ CHẠY HOÀN THÀNH</p>
              <p class="text-3xl font-black text-slate-900 mt-1">{{ pastShifts?.length || 0 }} <span class="text-sm font-normal text-slate-400">chuyến</span></p>
              <p class="text-[11px] text-emerald-600 font-bold mt-0.5">100% hoàn thành an toàn</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">🏁</div>
          </div>

          <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase text-slate-400">TỔNG TIỀN MẶT ĐÃ NỘP VỀ QUỸ</p>
              <p class="text-2xl font-black text-emerald-600 font-mono mt-1">
                {{ formatMoney(pastShifts?.reduce((acc, s) => acc + (s.net_remitted || 0), 0) || 0) }}
              </p>
              <p class="text-[11px] text-slate-500 font-medium mt-0.5">Đã kế toán đối soát & xác nhận</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl">💰</div>
          </div>

          <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
              <p class="text-[10px] font-black uppercase text-slate-400">TỔNG HÀNH KHÁCH ĐÃ ĐÓN</p>
              <p class="text-3xl font-black text-amber-500 mt-1">
                {{ pastShifts?.reduce((acc, s) => acc + (s.total_passengers || 0), 0) || 0 }} <span class="text-sm font-normal text-slate-400">lượt khách</span>
              </p>
              <p class="text-[11px] text-slate-500 font-medium mt-0.5">Cả khách web và khách dọc đường</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl">👥</div>
          </div>
        </div>

        <!-- Past Shifts List Table -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
          <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <h3 class="font-black text-base text-slate-900">NHẬT KÝ LỊCH SỬ CÁC CA CHẠY ĐÃ HOÀN THÀNH</h3>
              <p class="text-xs text-slate-500 mt-0.5">Bảng đối soát chi tiết tiền vé, chi phí dọc đường và biên lai chốt ca</p>
            </div>

            <!-- Date Filter & Pagination Control -->
            <div class="flex items-center gap-2 flex-wrap">
              <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-2xl border border-slate-200 text-xs">
                <button
                  type="button"
                  @click="shiftDateFilter = 'ALL'"
                  class="px-2.5 py-1 rounded-xl font-bold transition cursor-pointer"
                  :class="shiftDateFilter === 'ALL' ? 'bg-white text-slate-900 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900'"
                >
                  Tất cả
                </button>
                <button
                  type="button"
                  @click="shiftDateFilter = 'today'"
                  class="px-2.5 py-1 rounded-xl font-bold transition cursor-pointer"
                  :class="shiftDateFilter === 'today' ? 'bg-white text-red-600 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900'"
                >
                  Hôm nay
                </button>
                <button
                  type="button"
                  @click="shiftDateFilter = 'yesterday'"
                  class="px-2.5 py-1 rounded-xl font-bold transition cursor-pointer"
                  :class="shiftDateFilter === 'yesterday' ? 'bg-white text-blue-600 shadow-xs font-black' : 'text-slate-600 hover:text-slate-900'"
                >
                  Hôm qua
                </button>
              </div>

              <!-- Date Picker Input -->
              <input
                type="date"
                v-model="shiftCustomDate"
                @change="shiftDateFilter = 'custom'"
                class="px-2.5 py-1.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none hover:bg-slate-50 focus:ring-2 focus:ring-red-500 cursor-pointer"
                title="Lọc theo ngày cụ thể"
              />

              <span class="text-xs font-bold px-3 py-1 bg-slate-100 text-slate-700 rounded-xl font-mono">
                {{ filteredPastShifts.length }} ca
              </span>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold tracking-wider border-b border-slate-200">
                <tr>
                  <th class="px-5 py-3">Mã Chuyến</th>
                  <th class="px-5 py-3">Tuyến Đường</th>
                  <th class="px-5 py-3">Phương Tiện & Bác Tài</th>
                  <th class="px-5 py-3 text-center">Khởi Hành</th>
                  <th class="px-5 py-3 text-center">Khách Đón</th>
                  <th class="px-5 py-3 text-right">Tiền Mặt Thu</th>
                  <th class="px-5 py-3 text-right">Chi Phí Dọc Đường</th>
                  <th class="px-5 py-3 text-right">Thực Nộp Quỹ</th>
                  <th class="px-5 py-3 text-center">Đối Soát</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="shift in (paginatedPastShifts || [])"
                  :key="shift.id"
                  class="hover:bg-slate-50/80 transition"
                >
                  <td class="px-5 py-3.5 font-mono font-black text-red-700 whitespace-nowrap">
                    {{ shift.trip_code }}
                  </td>
                  <td class="px-5 py-3.5 font-bold text-slate-900 whitespace-nowrap">
                    {{ shift.route_name }}
                  </td>
                  <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap">
                    <span class="font-mono font-bold text-slate-800">{{ shift.bus_plate }}</span>
                    <span class="text-[11px] text-slate-400 block">Tài xế: {{ shift.driver_name }}</span>
                  </td>
                  <td class="px-5 py-3.5 text-center font-mono whitespace-nowrap">
                    <span class="font-bold text-slate-800">{{ shift.dep_time }}</span>
                    <span class="text-[10px] text-slate-400 block">{{ shift.dep_date }}</span>
                  </td>
                  <td class="px-5 py-3.5 text-center whitespace-nowrap">
                    <span class="px-2.5 py-0.5 rounded-full font-black bg-blue-50 text-blue-700 border border-blue-200">
                      {{ shift.total_passengers }} khách
                    </span>
                  </td>
                  <td class="px-5 py-3.5 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                    {{ formatMoney(shift.cash_collected) }}
                  </td>
                  <td class="px-5 py-3.5 text-right font-mono text-slate-500 whitespace-nowrap">
                    {{ formatMoney(shift.total_expenses) }}
                  </td>
                  <td class="px-5 py-3.5 text-right font-mono font-black text-slate-900 whitespace-nowrap">
                    {{ formatMoney(shift.net_remitted) }}
                  </td>
                  <td class="px-5 py-3.5 text-center whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1">
                      <span>✓</span>
                      <span>ĐÃ ĐỐI SOÁT</span>
                    </span>
                  </td>
                </tr>
                <tr v-if="!paginatedPastShifts || paginatedPastShifts.length === 0">
                  <td colspan="9" class="text-center py-8 text-slate-400">
                    Không tìm thấy ca chạy nào phù hợp với bộ lọc ngày
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Bar (Trang 1, 2, 3...) -->
          <div v-if="totalShiftPages > 1" class="p-4 border-t border-slate-100 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <span class="text-xs text-slate-500 font-medium">
              Hiển thị trang <b class="text-slate-800">{{ currentShiftPage }}</b> / <b class="text-slate-800">{{ totalShiftPages }}</b> (Tổng {{ filteredPastShifts.length }} ca đã hoàn thành)
            </span>

            <div class="flex items-center gap-1">
              <button
                type="button"
                :disabled="currentShiftPage <= 1"
                @click="currentShiftPage--"
                class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
              >
                ← Trước
              </button>

              <button
                v-for="p in totalShiftPages"
                :key="p"
                type="button"
                @click="currentShiftPage = p"
                class="w-8 h-8 rounded-xl text-xs font-bold transition cursor-pointer flex items-center justify-center"
                :class="currentShiftPage === p ? 'bg-red-600 text-white font-black shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'"
              >
                {{ p }}
              </button>

              <button
                type="button"
                :disabled="currentShiftPage >= totalShiftPages"
                @click="currentShiftPage++"
                class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-white disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
              >
                Sau →
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- ================= MODAL: 20 TÀI KHOẢN LƠ XE THẬT (REQUIREMENT 10) ================= -->
    <div v-if="showConductorAccountsModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[92vh] overflow-y-auto">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center font-black text-xl">
              👥
            </div>
            <div>
              <h3 class="font-black text-base text-slate-900">DANH SÁCH 20 TÀI KHOẢN LƠ XE THẬT</h3>
              <p class="text-xs text-slate-500">Mật khẩu mặc định toàn bộ hệ thống: <strong>123456</strong></p>
            </div>
          </div>
          <button @click="showConductorAccountsModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-lg">✕</button>
        </div>

        <!-- 2 TÀI KHOẢN CHÍNH NỔI BẬT LÀM VÍ DỤ -->
        <div class="space-y-2">
          <div class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
            <i class="fa-solid fa-star text-amber-500"></i>
            <span>2 Tài Khoản Chính Được Giao Xe Cụ Thể (Làm Ví Dụ)</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Tài khoản 1 -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-amber-50 to-orange-50 border-2 border-amber-400 space-y-2 shadow-xs">
              <div class="flex justify-between items-start">
                <span class="px-2 py-0.5 rounded-md bg-amber-500 text-slate-950 font-black text-[10px] uppercase">
                  Tài khoản chính 1
                </span>
                <span class="font-mono text-xs font-bold text-amber-900 bg-white px-2 py-0.5 rounded border border-amber-300">Xe: 43B-067.89</span>
              </div>
              <div>
                <strong class="text-sm font-black text-slate-900 block">Nguyễn Văn Phúc (Lơ Xe Trưởng)</strong>
                <span class="text-xs text-slate-600 block">Tuyến: Đà Nẵng ⇄ Quảng Bình</span>
              </div>
              <div class="p-2.5 rounded-xl bg-white/90 border border-amber-200 text-xs space-y-1 font-mono">
                <div class="flex justify-between">
                  <span class="text-slate-500 font-sans">Email:</span>
                  <strong class="text-slate-900">loxe1@nhaxekientruc.vn</strong>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500 font-sans">Mật khẩu:</span>
                  <strong class="text-emerald-700">123456</strong>
                </div>
              </div>
              <button
                @click="copyText('loxe1@nhaxekientruc.vn', 'Email Lơ xe 1')"
                class="w-full py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs cursor-pointer shadow-xs transition"
              >
                📋 Sao chép thông tin đăng nhập
              </button>
            </div>

            <!-- Tài khoản 2 -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-400 space-y-2 shadow-xs">
              <div class="flex justify-between items-start">
                <span class="px-2 py-0.5 rounded-md bg-blue-600 text-white font-black text-[10px] uppercase">
                  Tài khoản chính 2
                </span>
                <span class="font-mono text-xs font-bold text-blue-900 bg-white px-2 py-0.5 rounded border border-blue-300">Xe: 43B-012.34</span>
              </div>
              <div>
                <strong class="text-sm font-black text-slate-900 block">Lê Quốc Đạt (Lơ Xe Tuyến Huế)</strong>
                <span class="text-xs text-slate-600 block">Tuyến: Đà Nẵng ⇄ Huế</span>
              </div>
              <div class="p-2.5 rounded-xl bg-white/90 border border-blue-200 text-xs space-y-1 font-mono">
                <div class="flex justify-between">
                  <span class="text-slate-500 font-sans">Email:</span>
                  <strong class="text-slate-900">loxe2@nhaxekientruc.vn</strong>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500 font-sans">Mật khẩu:</span>
                  <strong class="text-emerald-700">123456</strong>
                </div>
              </div>
              <button
                @click="copyText('loxe2@nhaxekientruc.vn', 'Email Lơ xe 2')"
                class="w-full py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs cursor-pointer shadow-xs transition"
              >
                📋 Sao chép thông tin đăng nhập
              </button>
            </div>
          </div>
        </div>

        <!-- TÌM KIẾM & BẢNG 18 TÀI KHOẢN CÒN LẠI -->
        <div class="space-y-3 pt-2">
          <div class="flex justify-between items-center gap-2">
            <span class="text-xs font-black uppercase text-slate-700">18 Tài Khoản Lơ Xe Khác (loxe3 - loxe20)</span>
            <input
              v-model="conductorSearch"
              type="text"
              placeholder="Tìm theo tên hoặc email..."
              class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-300 text-xs outline-none focus:border-red-500"
            />
          </div>

          <div class="border border-slate-200 rounded-2xl overflow-hidden max-h-64 overflow-y-auto">
            <table class="w-full text-xs text-left">
              <thead class="bg-slate-100 text-slate-600 uppercase font-black text-[10px] sticky top-0">
                <tr>
                  <th class="p-2.5">Họ và tên</th>
                  <th class="p-2.5">Email đăng nhập</th>
                  <th class="p-2.5">SĐT</th>
                  <th class="p-2.5 text-center">Mật khẩu</th>
                  <th class="p-2.5 text-center">Sao chép</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="c in filteredConductors" :key="c.id" class="hover:bg-slate-50 transition">
                  <td class="p-2.5 font-bold text-slate-900">{{ c.name }}</td>
                  <td class="p-2.5 font-mono text-slate-600">{{ c.email }}</td>
                  <td class="p-2.5 font-mono text-slate-500">{{ c.phone }}</td>
                  <td class="p-2.5 font-mono text-center font-bold text-emerald-700">123456</td>
                  <td class="p-2.5 text-center">
                    <button
                      @click="copyText(c.email, 'Email ' + c.name)"
                      class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] cursor-pointer"
                    >
                      Copy
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: XÁC NHẬN ĐÃ THU TIỀN MẶT KHI LÊN XE (REQUIREMENT 1) ================= -->
    <div v-if="showCashConfirmModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-2.5 text-amber-600">
          <span class="text-3xl">💵</span>
          <div>
            <h3 class="font-black text-base text-slate-900">XÁC NHẬN THU TIỀN MẶT</h3>
            <p class="text-xs text-slate-500">Quy chuẩn soát vé trước khi cho lên xe</p>
          </div>
        </div>

        <p class="text-xs text-slate-700 leading-relaxed">
          Bạn đã thu <strong>{{ formatMoney(pendingCashPax?.remaining_amount) }}</strong> tiền mặt từ khách <strong>{{ pendingCashPax?.customer_name }}</strong> chưa?
        </p>

        <div class="p-4 rounded-2xl bg-red-50 border-2 border-red-300 text-center space-y-1">
          <div class="text-[10px] uppercase font-bold text-red-700">Số tiền mặt cần thu</div>
          <div class="text-2xl font-black text-red-600 font-mono">
            {{ formatMoney(pendingCashPax?.remaining_amount) }}
          </div>
          <div class="text-[10px] text-red-600 font-medium">Ghế: {{ pendingCashPax?.seats ? pendingCashPax.seats.join(', ') : 'N/A' }}</div>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            @click="showCashConfirmModal = false"
            class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer"
          >
            Chưa thu
          </button>
          <button
            type="button"
            @click="submitCashCheckinConfirmed"
            :disabled="isProcessing"
            class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs cursor-pointer shadow-md"
          >
            XÁC NHẬN ĐÃ THU
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: POPUP CHECK-IN & THU TIỀN GHẾ VÀNG (REQUIREMENT 8) ================= -->
    <div v-if="showYellowCheckinModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <span class="text-2xl">⚡</span>
            <div>
              <h3 class="font-black text-base text-slate-900">CHECK-IN & THU TIỀN</h3>
              <p class="text-xs text-slate-500">Kiểm tra thông tin vé trước khi cho lên xe</p>
            </div>
          </div>
          <button @click="showYellowCheckinModal = false" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>

        <div class="space-y-3 text-xs">
          <!-- Thông tin chặng & ghế -->
          <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-1">
            <div class="flex justify-between">
              <span class="text-slate-500">Tuyến chặng:</span>
              <strong class="text-slate-900 font-bold">{{ currentTrip?.route?.name }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Điểm trả:</span>
              <strong class="text-slate-800">{{ yellowSeatDetail?.dropoff_name }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Khách hàng:</span>
              <strong class="text-slate-900 font-black">{{ yellowSeatDetail?.customer_name }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Loại ghế:</span>
              <strong class="text-blue-700 font-mono font-bold">{{ yellowSeatDetail?.floor === 1 ? 'Giường VIP (Tầng 1)' : 'Giường tiêu chuẩn (Tầng 2)' }} - Ghế {{ yellowSeatDetail?.seat_number }}</strong>
            </div>
          </div>

          <!-- NÚT GỌI SỐ NHANH (BẤM VÀO GHẾ ĐÃ ĐẶT CÓ GỌI SỐ NHANH - REQUIREMENT) -->
          <div v-if="yellowSeatDetail?.customer_phone" class="p-3 rounded-2xl bg-emerald-50 border border-emerald-300 flex items-center justify-between gap-2 shadow-xs">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs animate-bounce">
                <i class="fa-solid fa-phone-volume"></i>
              </div>
              <div>
                <span class="text-[10px] text-emerald-800 font-bold block uppercase">Số điện thoại khách:</span>
                <span class="font-mono font-black text-sm text-slate-900">{{ yellowSeatDetail.customer_phone }}</span>
              </div>
            </div>
            <button
              type="button"
              @click="makeCall(yellowSeatDetail.customer_phone)"
              class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs flex items-center gap-1.5 shadow-sm active:scale-95 transition cursor-pointer"
            >
              <i class="fa-solid fa-phone"></i>
              <span>GỌI NGAY</span>
            </button>
          </div>

          <!-- PHÉP TÍNH MINH BẠCH (REQUIREMENT 8) -->
          <div class="p-4 rounded-2xl bg-amber-50/70 border-2 border-amber-300 space-y-2">
            <div class="flex justify-between text-slate-600">
              <span>Tổng giá vé:</span>
              <strong class="font-mono text-slate-900">{{ formatMoney(yellowSeatDetail?.total_amount) }}</strong>
            </div>
            <div class="flex justify-between text-blue-700">
              <span>Đã thanh toán (Cọc):</span>
              <strong class="font-mono">- {{ formatMoney(yellowSeatDetail?.paid_amount) }}</strong>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-amber-300">
              <span class="font-black text-red-600 uppercase">SỐ TIỀN CẦN THU LÚC NÀY:</span>
              <span class="font-mono font-black text-2xl text-red-600">
                {{ formatMoney(yellowSeatDetail?.remaining_amount) }}
              </span>
            </div>
          </div>
        </div>

        <div class="space-y-2 pt-1">
          <!-- Button kép xác nhận thu tiền và cho lên xe -->
          <button
            @click="confirmManualCheckin({ id: yellowSeatDetail.booking_id, customer_name: yellowSeatDetail.customer_name }, yellowSeatDetail.remaining_amount > 0)"
            class="w-full py-3.5 rounded-2xl bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-700 hover:to-amber-700 text-white font-black text-xs cursor-pointer shadow-lg shadow-red-600/30 flex items-center justify-center gap-2 transition transform active:scale-95"
          >
            <i class="fa-solid fa-money-bill-wave"></i>
            <span v-if="(yellowSeatDetail?.remaining_amount || 0) > 0">Xác Nhận Đã Thu {{ formatMoney(yellowSeatDetail.remaining_amount) }} & Cho Lên Xe</span>
            <span v-else>Xác Nhận Cho Khách Lên Xe (Đã Trả Đủ)</span>
          </button>

          <!-- Báo bỏ chuyến -->
          <button
            @click="openNoShowModal({ id: yellowSeatDetail.booking_id, customer_name: yellowSeatDetail.customer_name, seats: [yellowSeatDetail.seat_number] })"
            class="w-full py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs cursor-pointer"
          >
            ✕ Báo khách bỏ chuyến (No-show)
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: GIẢI PHÓNG GHẾ ĐỎ THÀNH GHẾ TRỐNG (REQUIREMENT 4) ================= -->
    <div v-if="showReleaseSeatModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-2.5 text-rose-600">
          <span class="text-3xl">🔄</span>
          <div>
            <h3 class="font-black text-base text-slate-900">GIẢI PHÓNG GHẾ TRỐNG</h3>
            <p class="text-xs text-slate-500">Khôi phục ghế để bán vé dọc đường</p>
          </div>
        </div>

        <p class="text-xs text-slate-700 leading-relaxed">
          Bạn muốn giải phóng ghế <strong>{{ pendingReleaseSeat?.seat_number }}</strong> này để bán cho khách khác?
        </p>

        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 text-xs text-slate-600 space-y-1.5">
          <div>Khách bỏ chuyến: <strong>{{ pendingReleaseSeat?.customer_name }}</strong></div>
          <div v-if="pendingReleaseSeat?.customer_phone" class="flex items-center justify-between p-2 rounded-xl bg-emerald-50 border border-emerald-200">
            <span>SĐT: <strong class="font-mono text-slate-900">{{ pendingReleaseSeat.customer_phone }}</strong></span>
            <button
              type="button"
              @click="makeCall(pendingReleaseSeat.customer_phone)"
              class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center gap-1.5 shadow-xs cursor-pointer"
            >
              <i class="fa-solid fa-phone-volume animate-bounce"></i> Gọi lại thử
            </button>
          </div>
          <div class="text-[11px] text-emerald-700 font-bold">👉 Ghế sẽ lập tức biến thành Màu xám (Trống) để đón khách dọc đường mới.</div>
        </div>

        <div class="flex gap-2">
          <button
            type="button"
            @click="showReleaseSeatModal = false"
            class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer"
          >
            Hủy
          </button>
          <button
            type="button"
            @click="submitReleaseSeatConfirmed"
            :disabled="isProcessing"
            class="flex-1 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs cursor-pointer shadow-md"
          >
            ĐỒNG Ý GIẢI PHÓNG
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: CHI TIẾT GHẾ TỪ SƠ ĐỒ XE (GHẾ XANH) ================= -->
    <div v-if="showSeatDetailModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 rounded-xl bg-slate-900 text-white font-mono font-black text-sm">
              {{ selectedSeatDetail?.seat_number }}
            </span>
            <span class="text-xs text-slate-500 font-bold">
              {{ selectedSeatDetail?.floor === 1 ? 'Tầng 1 (VIP)' : 'Tầng 2 (Tiêu chuẩn)' }}
            </span>
          </div>
          <button @click="showSeatDetailModal = false" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>

        <div class="space-y-3 text-xs">
          <div>
            <span class="text-slate-500 block text-[11px]">Hành khách:</span>
            <strong class="text-slate-900 font-black text-sm">{{ selectedSeatDetail?.customer_name }}</strong>
            <div class="text-blue-600 font-bold mt-1">
              <button @click="makeCall(selectedSeatDetail?.customer_phone)" class="flex items-center gap-1.5 hover:underline cursor-pointer">
                <i class="fa-solid fa-phone"></i> {{ selectedSeatDetail?.customer_phone }}
              </button>
            </div>
          </div>

          <!-- NÚT GỌI SỐ NHANH CHO KHÁCH (Ghế Xanh) -->
          <div v-if="selectedSeatDetail?.customer_phone" class="p-3 rounded-2xl bg-emerald-50 border border-emerald-300 flex items-center justify-between gap-2 shadow-xs">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm shadow-xs animate-bounce">
                <i class="fa-solid fa-phone-volume"></i>
              </div>
              <div>
                <span class="text-[10px] text-emerald-800 font-bold block uppercase">Số điện thoại khách:</span>
                <span class="font-mono font-black text-sm text-slate-900">{{ selectedSeatDetail.customer_phone }}</span>
              </div>
            </div>
            <button
              type="button"
              @click="makeCall(selectedSeatDetail.customer_phone)"
              class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs flex items-center gap-1.5 shadow-sm active:scale-95 transition cursor-pointer"
            >
              <i class="fa-solid fa-phone"></i>
              <span>GỌI NGAY</span>
            </button>
          </div>

          <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
            <div class="flex justify-between">
              <span class="text-slate-500">Trạng thái ghế:</span>
              <span class="font-black text-emerald-700">✓ ĐÃ LÊN XE</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Giá vé:</span>
              <strong class="font-mono text-slate-900 font-bold">{{ formatMoney(selectedSeatDetail?.total_amount) }}</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Đã thanh toán:</span>
              <span class="font-bold font-mono text-emerald-700">{{ formatMoney(selectedSeatDetail?.paid_amount) }}</span>
            </div>
            <div class="text-[11px] text-blue-700 font-semibold bg-blue-50 px-2 py-0.5 rounded-md">
              {{ selectedSeatDetail?.payment_badge || '✓ Đã thanh toán đủ' }}
            </div>
          </div>
        </div>

        <div class="space-y-2 pt-2 border-t border-slate-100">
          <!-- Đổi ghế cho người nhà -->
          <button
            @click="openChangeSeatModal({ id: selectedSeatDetail.booking_id, customer_name: selectedSeatDetail.customer_name, seat_number: selectedSeatDetail.seat_number })"
            :disabled="isTripCompleted"
            :class="isTripCompleted ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer hover:bg-blue-100'"
            class="w-full py-2.5 rounded-xl bg-blue-50 text-blue-700 font-bold text-xs border border-blue-200"
          >
            🔄 Đổi chỗ cho người nhà
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: BÁN VÉ DỌC ĐƯỜNG (REQUIREMENT 3: MẶC ĐỊNH VỊ TRÍ HIỆN TẠI) ================= -->
    <div v-if="showRoadsideModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <span class="text-xl">🎟️</span>
            <h3 class="font-black text-base text-slate-900">BÁN VÉ DỌC ĐƯỜNG TRÊN XE</h3>
          </div>
          <button @click="showRoadsideModal = false" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>

        <form @submit.prevent="submitRoadsideTicket" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-700 uppercase">Ghế Trống Cho Khách</label>
            <select
              v-model="roadsideForm.seat_number"
              required
              class="w-full mt-1.5 p-3 rounded-xl bg-slate-50 border border-slate-300 text-xs font-bold text-slate-900 outline-none focus:border-red-500 font-mono"
            >
              <option value="" disabled>-- Chọn ghế trống --</option>
              <option v-for="s in availableEmptySeats" :key="s.seat_number" :value="s.seat_number">
                {{ s.label }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <!-- Điểm lên (Đón) với Mặc định Vị trí hiện tại (Requirement 3) -->
            <div>
              <label class="text-xs font-bold text-slate-700 uppercase">Điểm Lên (Đón)</label>
              <select
                v-model="roadsideForm.pickup_stop_id"
                required
                class="w-full mt-1.5 p-2.5 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 font-bold"
              >
                <option value="current_location">📍 Đón dọc đường (Vị trí hiện tại)</option>
                <option v-for="st in stops" :key="'p' + st.id" :value="st.id">
                  {{ st.stop_order }}. {{ st.stop_name }}
                </option>
              </select>
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700 uppercase">Điểm Xuống (Trả)</label>
              <select
                v-model="roadsideForm.dropoff_stop_id"
                required
                class="w-full mt-1.5 p-2.5 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 font-medium"
              >
                <option v-for="st in stops" :key="'d' + st.id" :value="st.id">
                  {{ st.stop_order }}. {{ st.stop_name }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-700 uppercase">Họ và tên khách</label>
            <input
              v-model="roadsideForm.customer_name"
              type="text"
              required
              placeholder="Ví dụ: Anh Nam (Bắt xe dọc đường)"
              class="w-full mt-1.5 p-3 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 font-bold"
            />
          </div>

          <div>
            <label class="text-xs font-bold text-slate-700 uppercase">Số điện thoại liên hệ</label>
            <input
              v-model="roadsideForm.customer_phone"
              type="text"
              required
              placeholder="0905..."
              class="w-full mt-1.5 p-3 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900 font-mono"
            />
          </div>

          <!-- Giá Vé Linh Hoạt Theo Vị Trí Xe & Tùy Biến Lơ Xe (Không Bắt Buộc Cố Định) -->
          <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 text-xs space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="text-slate-600 font-bold uppercase text-[11px]">Giá vé niêm yết tuyến:</span>
              <span class="font-mono font-bold text-slate-700">{{ formatMoney(currentTrip?.base_price || 150000) }}</span>
            </div>

            <!-- Nút chọn nhanh mức giảm theo chặng đón dọc đường -->
            <div>
              <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 mb-1.5">
                <span>⚡ Chọn nhanh theo chặng xe chạy:</span>
                <span class="text-red-600 font-bold font-mono">Lơ xe tự điều chỉnh</span>
              </div>
              <div class="grid grid-cols-3 gap-1.5">
                <button
                  type="button"
                  @click="setRoadsidePricePreset(100)"
                  :class="roadsideForm.price == (currentTrip?.base_price || 150000) ? 'bg-slate-900 text-white font-black' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold'"
                  class="py-1.5 px-2 rounded-xl text-[10px] transition cursor-pointer text-center"
                >
                  100% Nguyên vé
                </button>
                <button
                  type="button"
                  @click="setRoadsidePricePreset(70)"
                  :class="roadsideForm.price == Math.round(((currentTrip?.base_price || 150000) * 0.7) / 10000) * 10000 ? 'bg-amber-600 text-white font-black' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold'"
                  class="py-1.5 px-2 rounded-xl text-[10px] transition cursor-pointer text-center"
                >
                  -30% Giữa chặng
                </button>
                <button
                  type="button"
                  @click="setRoadsidePricePreset(50)"
                  :class="roadsideForm.price == Math.round(((currentTrip?.base_price || 150000) * 0.5) / 10000) * 10000 ? 'bg-purple-600 text-white font-black' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold'"
                  class="py-1.5 px-2 rounded-xl text-[10px] transition cursor-pointer text-center"
                >
                  -50% Chặng ngắn
                </button>
              </div>
            </div>

            <!-- Ô nhập giá thực thu linh hoạt -->
            <div>
              <label class="block font-bold text-slate-700 text-[11px] uppercase mb-1">
                Tiền thực thu từ khách (VNĐ) <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input
                  v-model.number="roadsideForm.price"
                  type="number"
                  step="5000"
                  min="10000"
                  required
                  placeholder="Nhập số tiền thực thu..."
                  class="w-full p-2.5 rounded-xl bg-white border border-red-300 text-red-600 text-sm font-black font-mono outline-none focus:border-red-500 shadow-2xs"
                />
                <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">VNĐ</span>
              </div>
              <p class="text-[10px] text-slate-500 mt-1">Lơ xe tự động giảm giá tùy vào vị trí xe đang chạy tới đâu dọc đường.</p>
            </div>
          </div>

          <button
            type="submit"
            :disabled="roadsideForm.processing || isTripCompleted"
            class="w-full py-3.5 rounded-2xl bg-red-600 hover:bg-red-500 text-white font-black text-xs cursor-pointer shadow-md transition"
          >
            XUẤT VÉ & XÁC NHẬN LÊN XE
          </button>
        </form>
      </div>
    </div>

    <!-- ================= MODAL: TÌNH HUỐNG 3 - ĐỔI GHẾ CHO NGƯỜI NHÀ ================= -->
    <div v-if="showChangeSeatModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-2 text-blue-600">
          <span class="text-2xl">🔄</span>
          <div>
            <h3 class="font-black text-base text-slate-900">ĐỔI GHẾ CHO HÀNH KHÁCH</h3>
            <p class="text-xs text-slate-500">Tình huống khách lên xe đổi chỗ cho người nhà</p>
          </div>
        </div>

        <div class="p-3 rounded-2xl bg-blue-50 border border-blue-200 text-xs space-y-1">
          <div class="flex justify-between">
            <span class="text-slate-500">Khách hàng:</span>
            <strong class="text-slate-900 font-black">{{ selectedBooking?.customer_name }}</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Ghế hiện tại:</span>
            <strong class="text-red-600 font-black font-mono">{{ selectedBooking?.seats ? selectedBooking.seats.join(', ') : selectedBooking?.seat_number }}</strong>
          </div>
        </div>

        <form @submit.prevent="submitChangeSeat" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-700 uppercase">
              Chọn Ghế Mới Còn Trống (Màu Xám)
            </label>
            <select
              v-model="seatSwapForm.new_seat_number"
              required
              class="w-full mt-1.5 p-3 rounded-xl bg-slate-50 border border-slate-300 text-xs font-bold text-slate-900 outline-none focus:border-blue-500 font-mono"
            >
              <option value="" disabled>-- Chọn ghế trống mới --</option>
              <option v-for="s in availableEmptySeats" :key="'swap-' + s.seat_number" :value="s.seat_number">
                {{ s.label }}
              </option>
            </select>
          </div>

          <div class="flex gap-2">
            <button
              type="button"
              @click="showChangeSeatModal = false"
              class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="seatSwapForm.processing"
              class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs cursor-pointer shadow-md"
            >
              XÁC NHẬN ĐỔI GHẾ
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ================= MODAL: ĐÁNH DẤU BỎ CHUYẾN (NO-SHOW) ================= -->
    <div v-if="showNoShowModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-2 text-rose-600">
          <span class="text-2xl">⚠️</span>
          <h3 class="font-black text-base text-slate-900">XÁC NHẬN BỎ CHUYẾN (NO-SHOW)</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">
          Xe đã qua điểm đón hoặc liên hệ không được khách <strong>{{ selectedBooking?.customer_name }}</strong>?
        </p>
        <div class="p-3 bg-rose-50 rounded-2xl border border-rose-200 text-xs text-rose-800 font-medium space-y-1">
          <div>👉 <strong>Ghế {{ selectedBooking?.seats ? selectedBooking.seats.join(', ') : '' }}</strong> sẽ <strong>lập tức chuyển thành Ghế Trống (Màu xám)</strong> để bạn bấm vào bán vé dọc đường ngay lập tức!</div>
          <div class="text-[11px] text-emerald-700 font-bold">✓ Tiền vé này sẽ không còn bị cộng vào mục "CÒN THIẾU" của bạn nữa.</div>
        </div>
        <div class="flex gap-2">
          <button
            @click="showNoShowModal = false"
            class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs cursor-pointer"
          >
            Quay lại
          </button>
          <button
            @click="confirmNoShow"
            :disabled="isProcessing"
            class="flex-1 py-3 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black text-xs cursor-pointer shadow-md"
          >
            XÁC NHẬN BỎ CHUYẾN
          </button>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: XEM LỊCH SỬ SOÁT VÉ & BÁN VÉ ================= -->
    <div v-if="showHistoryModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[85vh] overflow-y-auto">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <span class="text-2xl">📜</span>
            <div>
              <h3 class="font-black text-base text-slate-900">NHẬT KÝ SOÁT VÉ & BÁN VÉ</h3>
              <p class="text-xs text-slate-500">Xem lại nãy giờ đã quét bao nhiêu vé và thu bao nhiêu tiền mặt</p>
            </div>
          </div>
          <button @click="showHistoryModal = false" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
          <div class="p-3 rounded-2xl bg-blue-50 border border-blue-200">
            <span class="text-[10px] text-blue-700 uppercase font-bold block">Quét QR Soát Vé</span>
            <span class="text-lg font-black text-blue-900">{{ checkinStats?.total_qr_scanned || 0 }} vé</span>
          </div>
          <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200">
            <span class="text-[10px] text-amber-700 uppercase font-bold block">Bán Vé Dọc Đường</span>
            <span class="text-lg font-black text-amber-900">{{ checkinStats?.total_roadside_sold || 0 }} vé</span>
          </div>
          <div class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 col-span-2 sm:col-span-1">
            <span class="text-[10px] text-emerald-700 uppercase font-bold block">Tiền Mặt Đã Thu</span>
            <span class="text-lg font-black text-emerald-900">{{ formatMoney(checkinStats?.total_cash_collected || 0) }}</span>
          </div>
        </div>

        <div class="space-y-2 pt-2">
          <div class="text-xs font-bold text-slate-500 uppercase">Dòng Thời Gian Thao Tác (Gần nhất trước):</div>
          <div v-if="checkinHistory && checkinHistory.length > 0" class="space-y-2">
            <div
              v-for="(item, idx) in checkinHistory"
              :key="'hist-' + idx"
              class="p-3 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between text-xs"
            >
              <div class="flex items-center gap-2.5">
                <span class="font-mono text-[11px] text-slate-400">{{ item.time }}</span>
                <div>
                  <span
                    class="px-2 py-0.5 rounded-md text-[10px] font-black mr-1.5"
                    :class="{
                      'bg-blue-100 text-blue-800': item.type === 'QR_SCAN',
                      'bg-amber-100 text-amber-800': item.type === 'ROADSIDE_SALE',
                      'bg-emerald-100 text-emerald-800': item.type === 'MANUAL',
                      'bg-rose-100 text-rose-800': item.type === 'NO_SHOW'
                    }"
                  >
                    {{ item.type_label }}
                  </span>
                  <strong class="text-slate-900">{{ item.customer_name }}</strong>
                  <span class="text-slate-500 font-mono ml-1">(Ghế: {{ item.seats ? item.seats.join(', ') : 'N/A' }})</span>
                </div>
              </div>

              <div class="text-right">
                <span v-if="item.paid_amount > 0" class="font-black text-emerald-700 block">
                  +{{ formatMoney(item.paid_amount) }}
                </span>
                <span class="text-[10px] text-slate-400 font-medium">
                  {{ item.payment_method }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-6 text-slate-400 text-xs font-bold">
            Chưa có thao tác check-in hoặc bán vé nào trong chuyến này.
          </div>
        </div>
      </div>
    </div>

    <!-- ================= MODAL: CHỐT CA CHUYẾN XE ================= -->
    <div v-if="showClosingModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <span class="text-xl">💰</span>
            <h3 class="font-black text-base text-slate-900">CHỐT CA & NỘP TIỀN MẶT</h3>
          </div>
          <button @click="showClosingModal = false" class="text-slate-400 hover:text-slate-700 font-bold">✕</button>
        </div>

        <div class="space-y-3 text-xs">
          <div class="p-4 rounded-2xl bg-gradient-to-br from-red-600 to-red-700 text-white space-y-1 shadow-md">
            <span class="text-[10px] uppercase font-bold text-red-200">👉 Con Số Chính Xác Lơ Xe Phải Nộp:</span>
            <div class="text-2xl font-black text-amber-300">
              {{ formatMoney(financeSummary?.cash_collected_amount || 0) }}
            </div>
            <p class="text-[10px] text-red-100 leading-snug pt-1">
              (Gồm tiền thu khách đặt web trả sau + tiền bán vé dọc đường).
            </p>
          </div>

          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
            <div class="flex justify-between">
              <span class="text-slate-500">Tiền đã thanh toán Online (VNPAY):</span>
              <strong class="text-blue-700">{{ formatMoney(financeSummary?.online_paid_amount || 0) }}</strong>
            </div>
            <p class="text-[10px] text-slate-400">Lơ xe không cầm tiền này.</p>
          </div>
        </div>

        <form @submit.prevent="submitClosing" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-700 uppercase">
              Số Tiền Mặt Thực Tế Lơ Xe Nộp (VNĐ)
            </label>
            <input
              v-model="closingForm.actual_cash_submitted"
              type="number"
              required
              :placeholder="financeSummary?.cash_collected_amount?.toString() || '0'"
              class="w-full mt-1.5 p-3.5 rounded-2xl bg-slate-50 border border-slate-300 font-mono font-black text-lg text-slate-900 outline-none focus:border-red-500"
            />
          </div>

          <div>
            <label class="text-xs font-bold text-slate-700 uppercase">Ghi chú bàn giao</label>
            <textarea
              v-model="closingForm.notes"
              rows="2"
              placeholder="Ghi chú đối soát..."
              class="w-full mt-1.5 p-3 rounded-xl bg-slate-50 border border-slate-300 text-xs text-slate-900"
            ></textarea>
          </div>

          <button
            type="submit"
            :disabled="closingForm.processing"
            class="w-full py-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm tracking-wide shadow-lg shadow-emerald-600/30 cursor-pointer"
          >
            XÁC NHẬN CHỐT CHUYẾN & NỘP TIỀN
          </button>
        </form>
      </div>
    </div>

    <!-- ================= MODAL: TRA CỨU LỊCH SỬ CHUYẾN CŨ (THAY THẾ KÉO NGANG) ================= -->
    <div v-if="showPastTripsModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4 animate-in fade-in duration-150">
      <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[88vh] flex flex-col">
        <!-- Header: Đổi màu đỏ thương hiệu tươi sáng, loại bỏ màu đen u ám -->
        <div class="flex justify-between items-start pb-3 border-b border-slate-100 shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-rose-500 to-red-600 text-white flex items-center justify-center text-lg shadow-md shadow-red-500/25 ring-4 ring-red-50">
              <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="font-black text-base text-slate-900 uppercase">LỊCH SỬ CÁC CHUYẾN XE ĐÃ CHẠY</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-100 text-red-700">Lơ Xe Tra Cứu</span>
              </div>
              <p class="text-xs text-slate-500 mt-0.5">Tra cứu nhanh các chuyến xe đã hoàn thành hôm qua, hôm kia hoặc chọn ngày cũ</p>
            </div>
          </div>
          <button @click="showPastTripsModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold flex items-center justify-center transition cursor-pointer">
            ✕
          </button>
        </div>

        <!-- Quick Date Filter Pills + Date Picker (Yêu cầu: Lơ xe muốn xem trước ngày hôm kia) -->
        <div class="shrink-0 space-y-2 bg-slate-50/80 p-3 rounded-2xl border border-slate-100">
          <div class="flex items-center justify-between text-xs font-bold text-slate-700">
            <span class="flex items-center gap-1.5">
              <i class="fa-solid fa-calendar-day text-red-600"></i>
              Lọc theo ngày chạy xe:
            </span>
            <span v-if="pastTripDateFilter !== 'ALL'" class="text-[11px] text-red-600 cursor-pointer hover:underline font-bold" @click="pastTripDateFilter = 'ALL'; pastTripCustomDate = ''">
              ↺ Xem tất cả
            </span>
          </div>

          <div class="flex items-center gap-1.5 flex-wrap">
            <button
              type="button"
              @click="pastTripDateFilter = 'ALL'; pastTripCustomDate = ''"
              class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="pastTripDateFilter === 'ALL' ? 'bg-red-600 text-white shadow-xs' : 'bg-white hover:bg-slate-200 text-slate-700 border border-slate-200'"
            >
              Tất cả ({{ pastTripsList.length }})
            </button>
            <button
              type="button"
              @click="pastTripDateFilter = 'yesterday'"
              class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="pastTripDateFilter === 'yesterday' ? 'bg-red-600 text-white shadow-xs' : 'bg-white hover:bg-slate-200 text-slate-700 border border-slate-200'"
            >
              Hôm qua (27/10)
            </button>
            <button
              type="button"
              @click="pastTripDateFilter = 'day_before'"
              class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="pastTripDateFilter === 'day_before' ? 'bg-red-600 text-white shadow-xs' : 'bg-white hover:bg-slate-200 text-slate-700 border border-slate-200'"
            >
              Hôm kia (26/10)
            </button>
            <button
              type="button"
              @click="pastTripDateFilter = 'three_days'"
              class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="pastTripDateFilter === 'three_days' ? 'bg-red-600 text-white shadow-xs' : 'bg-white hover:bg-slate-200 text-slate-700 border border-slate-200'"
            >
              3 ngày trước (25/10)
            </button>
            <button
              type="button"
              @click="pastTripDateFilter = 'older'"
              class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
              :class="pastTripDateFilter === 'older' ? 'bg-red-600 text-white shadow-xs' : 'bg-white hover:bg-slate-200 text-slate-700 border border-slate-200'"
            >
              Trước hôm kia (≤25/10)
            </button>

            <!-- Date Picker for ANY older date -->
            <div class="flex items-center gap-1.5 bg-white px-2 py-1 rounded-xl border border-slate-200 ml-auto shadow-2xs">
              <span class="text-[10px] font-bold text-slate-500">📅 Chọn ngày:</span>
              <input
                type="date"
                v-model="pastTripCustomDate"
                @change="pastTripDateFilter = 'CUSTOM'"
                class="bg-transparent text-xs font-mono font-bold text-slate-800 outline-none cursor-pointer"
              />
            </div>
          </div>
        </div>

        <!-- Search input -->
        <div class="shrink-0">
          <div class="relative">
            <input
              v-model="pastTripsSearch"
              type="text"
              placeholder="Tìm theo tuyến đường, biển số xe (43B-...), mã chuyến..."
              class="w-full pl-9 pr-8 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 outline-none focus:border-red-500 focus:bg-white transition"
            />
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-400 text-xs"></i>
            <button
              v-if="pastTripsSearch"
              @click="pastTripsSearch = ''"
              class="absolute right-3 top-2 text-xs text-slate-400 hover:text-slate-600"
            >
              ✕
            </button>
          </div>
        </div>

        <!-- Scrollable Vertical Trip List (Gọn gàng, thẳng hàng dọc, không kéo ngang) -->
        <div class="flex-1 overflow-y-auto space-y-2.5 pr-1">
          <div v-if="filteredPastTrips.length === 0" class="text-center py-10 text-slate-400 text-xs font-bold">
            Không tìm thấy chuyến xe cũ nào phù hợp với từ khóa tìm kiếm hoặc ngày đã chọn.
          </div>

          <div
            v-for="tr in filteredPastTrips"
            :key="'past-tr-' + tr.id"
            class="p-4 rounded-2xl border transition-all flex flex-wrap items-center justify-between gap-3"
            :class="currentTrip && currentTrip.id === tr.id ? 'bg-red-50/70 border-red-300 ring-2 ring-red-200' : 'bg-slate-50/70 hover:bg-slate-100/80 border-slate-200'"
          >
            <!-- Left Info -->
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-md bg-gradient-to-r from-red-600 to-rose-600 text-white font-mono font-black text-xs shadow-2xs">
                  {{ tr.departure_time_formatted || tr.departure_time?.substring(11, 16) }}
                </span>
                <span class="text-xs font-bold text-slate-500 font-mono">
                  {{ tr.departure_time?.substring(0, 10) }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800">
                  {{ tr.status === 'COMPLETED' ? '✓ Hoàn thành' : (tr.status === 'CLOSED' ? 'Đã đóng' : tr.status) }}
                </span>
              </div>

              <h4 class="font-black text-sm text-slate-900">
                {{ tr.route?.name }}
                <span v-if="tr.is_return_trip" class="text-amber-600 font-black text-xs">(Khứ hồi)</span>
              </h4>

              <div class="flex items-center gap-3 text-xs text-slate-600">
                <span>Xe: <strong class="font-mono text-slate-900">{{ tr.bus?.license_plate || '43B-098.76' }}</strong></span>
                <span>•</span>
                <span>Tài xế: <strong>{{ tr.driver?.name || 'Nguyễn Văn Hùng' }}</strong></span>
              </div>
            </div>

            <!-- Right Action Button -->
            <div>
              <button
                @click="changeTrip(tr.id); showPastTripsModal = false"
                :class="currentTrip && currentTrip.id === tr.id ? 'bg-gradient-to-r from-red-600 to-rose-600 text-white font-black shadow-xs' : 'bg-white hover:bg-slate-200 text-slate-800 border border-slate-300 font-bold'"
                class="px-4 py-2 rounded-xl text-xs shadow-2xs transition flex items-center gap-1.5 cursor-pointer"
              >
                <span>{{ currentTrip && currentTrip.id === tr.id ? '✓ Đang Xem Chuyến Này' : '👁️ Xem Chuyến Này' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="pt-3 border-t border-slate-100 flex justify-between items-center text-xs text-slate-500 shrink-0">
          <span>Tổng số: <strong>{{ filteredPastTrips.length }}</strong> chuyến xe cũ</span>
          <button
            @click="showPastTripsModal = false"
            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold transition cursor-pointer"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.15);
  border-radius: 4px;
}
</style>
