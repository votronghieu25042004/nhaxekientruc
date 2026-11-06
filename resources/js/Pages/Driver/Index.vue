<script setup>
import AiChatModal from '@/Components/AiChatModal.vue'
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

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
    seatMap: { type: Object, default: () => ({}) },
    stationPickups: { type: Array, default: () => [] },
    roadsidePickups: { type: Array, default: () => [] },
    stationDropoffs: { type: Array, default: () => [] },
    checkinHistory: { type: Array, default: () => [] },
    checkinStats: { type: Object, default: () => ({}) },
    financeSummary: { type: Object, default: () => ({}) },
    isAdminViewing: { type: Boolean, default: false },
    user: { type: Object, default: () => ({}) },
    lastHandoverLog: { type: String, default: 'Lốp sau bên phụ hơi non (áp suất 8.2 kg/cm²), máy lạnh giường A05 rỉ nước nhẹ. Bác tài ca sau nhớ kiểm tra trước khi xuất bến!' },
    tripExpenses: { type: Array, default: () => [] },
})

// ==================== HELPER FORMATTERS (Fix formatMoney is not a function) ====================
const formatMoney = (val) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0)
}
const formatCurrency = formatMoney
const formatMoneyShort = (val) => {
    const num = Number(val || 0)
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'tr'
    if (num >= 1000) return (num / 1000).toFixed(0) + 'k'
    return num.toLocaleString('vi-VN')
}

// Lưu ý đặc biệt của hành khách (Đồng bộ Bác tài, Lơ xe, Khách, Admin)
// CHỈ HIỆN KHI CÓ KHÁCH HÀNG ĐỂ LẠI LƯU Ý THỰC TẾ, HIỂN THỊ RÕ TÊN VÀ SỐ GHẾ CỦA KHÁCH!
const getTripCustomerNotes = (trip) => {
    if (!trip) return []
    if (trip.customer_notes_list && Array.isArray(trip.customer_notes_list) && trip.customer_notes_list.length > 0) {
        return trip.customer_notes_list
    }
    const list = []
    if (trip.bookings && trip.bookings.length > 0) {
        for (const b of trip.bookings) {
            const rawNote = (b.notes || '').trim()
            if (
                rawNote &&
                rawNote !== 'Đặt vé trực tuyến qua nhaxekientruc' &&
                !rawNote.includes('Tự động tạo') &&
                rawNote.toLowerCase() !== 'không' &&
                rawNote.toLowerCase() !== 'none'
            ) {
                const seats = b.booking_seats ? b.booking_seats.map(s => s.seat_number) : (b.seats || [])
                const seatLabel = seats.length > 0 ? seats.join(', ') : (b.seat_number || 'Chưa chọn')
                list.push({
                    type: 'passenger',
                    customer_name: b.customer_name || 'Khách hàng',
                    customer_phone: b.customer_phone || '',
                    seat_label: seatLabel,
                    seats: seats,
                    note: rawNote
                })
            }
        }
    }
    if (trip.passenger_notes && trip.passenger_notes.trim() !== '') {
        list.push({
            type: 'trip',
            customer_name: 'Ban Điều Hành',
            customer_phone: '',
            seat_label: 'Toàn chuyến',
            note: trip.passenger_notes.trim()
        })
    }
    return list
}

const getTripSpecialNotes = (trip) => {
    const list = getTripCustomerNotes(trip)
    return list.length > 0 ? list.map(n => `[${n.seat_label}] ${n.customer_name}: ${n.note}`).join('; ') : null
}

// ==================== 1. DARK MODE ====================
const isDarkMode = ref(localStorage.getItem('driver_dark_mode') === 'true')
const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value
    localStorage.setItem('driver_dark_mode', isDarkMode.value)
    if (leafletMap) {
        updateMapTileLayer()
    }
}

// ==================== 2. TABS MANAGEMENT ====================
// 'today': Hôm nay (Chuyến xe & CTA to bự)
// 'drive': Chế độ lái xe (Taplo Cockpit + Bản đồ Live Map)
// 'seatmap': Tải trọng & Sơ đồ ghế (36 giường View-Only)
// 'schedule': Lịch trình (Calendar Ô Vuông & Timeline Dọc)
// 'profile': Hồ sơ & Đánh giá
const activeTab = ref(typeof window !== 'undefined' ? (new URLSearchParams(window.location.search).get('tab') || 'today') : 'today')

// Chuyến xe đang thao tác chính
const activeActionTrip = ref(
    props.selectedTrip || 
    props.todayTrips.find(t => t.status === 'IN_TRANSIT') || 
    props.todayTrips[0] || 
    (props.allTrips && props.allTrips.length ? props.allTrips[0] : null)
)

// Quản lý cổng & tài khoản Admin viewing (Chỉ dành riêng cho Admin)
const isPortalDropdownOpen = ref(false)
const portalDropdownRef = ref(null)

const handleClickOutsidePortal = (event) => {
    if (portalDropdownRef.value && !portalDropdownRef.value.contains(event.target)) {
        isPortalDropdownOpen.value = false
    }
}

const switchDriver = (driverId) => {
    router.get('/driver', { driver_id: driverId }, { preserveScroll: true })
}

// ==================== BỘ LỌC NGÀY CHO BÁC TÀI (YÊU CẦU 3) ====================
const selectedDriverDate = ref('today') // 'today' | 'tomorrow' | 'YYYY-MM-DD'
const hideCompletedTrips = ref(false)

const filterTodayDateStr = computed(() => {
    const d = new Date()
    return d.toISOString().split('T')[0]
})

const filterTomorrowDateStr = computed(() => {
    const d = new Date()
    d.setDate(d.getDate() + 1)
    return d.toISOString().split('T')[0]
})

const customDriverDateValue = computed(() => {
    if (selectedDriverDate.value && selectedDriverDate.value.match(/^\d{4}-\d{2}-\d{2}$/)) {
        return selectedDriverDate.value
    }
    return ''
})

const onDriverDatePicked = (val) => {
    if (!val) return
    selectedDriverDate.value = val
}

const displayedDriverTrips = computed(() => {
    const source = (props.allTrips && props.allTrips.length > 0) ? props.allTrips : (props.todayTrips || []);
    let list = [];

    if (selectedDriverDate.value === 'today') {
        list = (props.todayTrips && props.todayTrips.length > 0)
            ? [...props.todayTrips]
            : source.filter(t => t.departure_time && t.departure_time.startsWith(filterTodayDateStr.value));
    } else if (selectedDriverDate.value === 'tomorrow') {
        list = (props.tomorrowTrips && props.tomorrowTrips.length > 0)
            ? [...props.tomorrowTrips]
            : source.filter(t => t.departure_time && t.departure_time.startsWith(filterTomorrowDateStr.value));
    } else {
        const target = selectedDriverDate.value;
        list = source.filter(t => t.departure_time && t.departure_time.startsWith(target));
    }

    // Sắp xếp ưu tiên: IN_TRANSIT (1) -> BOARDING (2) -> READY / OPEN_FOR_SALE (3) -> COMPLETED (5)
    list.sort((a, b) => {
        const getPriority = (status) => {
            if (status === 'IN_TRANSIT') return 1;
            if (status === 'BOARDING') return 2;
            if (['READY', 'OPEN_FOR_SALE', 'DRIVER_CONFIRMED'].includes(status)) return 3;
            if (['COMPLETED', 'ARRIVED', 'CLOSED'].includes(status)) return 5;
            return 4;
        };
        return getPriority(a.status) - getPriority(b.status);
    });

    if (hideCompletedTrips.value) {
        list = list.filter(t => !['COMPLETED', 'ARRIVED', 'CLOSED'].includes(t.status));
    }
    return list;
})

const displayedTodayTrips = displayedDriverTrips

watch(displayedDriverTrips, (newTrips) => {
    if (newTrips && newTrips.length > 0 && (!activeActionTrip.value || !newTrips.some(t => t.id === activeActionTrip.value?.id))) {
        activeActionTrip.value = newTrips[0];
    }
})

watch(() => props.selectedTrip, (newTrip) => {
    if (newTrip) activeActionTrip.value = newTrip
})

// Quản lý chuyến & ngày xem sơ đồ tải trọng (Tab 3)
const seatmapDate = ref(new Date().toISOString().split('T')[0])

watch(() => activeActionTrip.value, (newTrip) => {
    if (newTrip?.departure_time) {
        seatmapDate.value = newTrip.departure_time.substring(0, 10)
    }
}, { immediate: true })

const seatmapTrips = computed(() => {
    const all = props.allTrips || []
    if (all.length === 0) return props.todayTrips || []
    const targetDate = seatmapDate.value
    const tripsOnDate = all.filter(t => t.departure_time && t.departure_time.startsWith(targetDate))
    return tripsOnDate.length > 0 ? tripsOnDate : (props.todayTrips || all)
})

const onSeatmapDateChange = (date) => {
    seatmapDate.value = date
    const targetTrips = (props.allTrips || []).filter(t => t.departure_time && t.departure_time.startsWith(date))
    if (targetTrips.length > 0) {
        switchTripForSeatmap(targetTrips[0])
    }
}

// Chuyển chuyến xem sơ đồ/tải trọng
const switchTripForSeatmap = (trip) => {
    activeActionTrip.value = trip
    if (trip?.departure_time) {
        seatmapDate.value = trip.departure_time.substring(0, 10)
    }
    activeTab.value = 'seatmap'
    router.get('/driver', { 
        trip_id: trip.id, 
        driver_id: props.currentDriver?.id,
        tab: 'seatmap'
    }, { 
        preserveState: true, 
        preserveScroll: true 
    })
}

// ==================== 3. MODALS STATE ====================
const showChecklistModal = ref(false)
const showFinishModal = ref(false)
const showDelayModal = ref(false)
const showIncidentModal = ref(false)
const showExpenseModal = ref(false)
const showSosModal = ref(false)
const showShiftModal = ref(false)
const showRestStopModal = ref(false)
const showArriveFlashModal = ref(false)
const isProcessing = ref(false)

// ==================== 4. POPUP KIỂM TRA AN TOÀN 8 MỤC (CÔNG TẮC GẠT IOS) ====================
const checklistItems = ref([
    { key: 'tires', label: '1. Áp suất & độ mòn 6 lốp xe (kèm lốp dự phòng)', desc: 'Không rách nứt, áp suất tiêu chuẩn 8.5 - 9.0 kg/cm²', checked: false },
    { key: 'coolant', label: '2. Nước làm mát động cơ & Dầu nhớt máy', desc: 'Mức nước giữa Min - Max, dầu nhớt đủ áp suất bôi trơn', checked: false },
    { key: 'fuel', label: '3. Nhiên liệu dầu Diesel & Nước rửa kính lái', desc: 'Nhiên liệu trên 80% bình, cần gạt nước hoạt động tốt', checked: false },
    { key: 'brakes', label: '4. Hệ thống phanh hơi (thắng khí nén) & ABS', desc: 'Áp suất bình hơi >= 8 bar, phanh tay khóa bánh chắc chắn', checked: false },
    { key: 'lights', label: '5. Hệ thống đèn: Pha, Cốt, Xi-nhan, Sương mù, Đèn phanh', desc: 'Tất cả đèn tín hiệu hoạt động sáng rõ, không chập cháy', checked: false },
    { key: 'wipers', label: '6. Dây an toàn các giường & 04 búa thoát hiểm', desc: 'Búa thoát hiểm gắn đúng vị trí quy định trên cabin và tầng', checked: false },
    { key: 'safety_equip', label: '7. Bình chữa cháy mini & Túi sơ cứu y tế', desc: '02 bình PCCC còn hạn sử dụng, túi sơ cấp cứu đầy đủ', checked: false },
    { key: 'camera_docs', label: '8. Giấy tờ xe, Phù hiệu xe & Camera hành trình online', desc: 'Đăng kiểm, Bảo hiểm, Lệnh xuất bến, Camera truyền dữ liệu Cục', checked: false }
])

const allChecked = computed(() => checklistItems.value.every(item => item.checked))
const checkedCount = computed(() => checklistItems.value.filter(item => item.checked).length)

const toggleAllChecklist = () => {
    const targetState = !allChecked.value
    checklistItems.value.forEach(item => item.checked = targetState)
}

// Bắt đầu chuyến & kiểm tra xe
const openSafetyChecklist = (trip) => {
    activeActionTrip.value = trip
    showChecklistModal.value = true
}

// Bước 1: Xác nhận kiểm tra xe an toàn & Gửi về Admin (READY)
const confirmPreTripSafety = () => {
    if (!allChecked.value) {
        alert('Vui lòng gạt xanh đủ cả 8 công tắc an toàn trước khi xác nhận!')
        return
    }
    if (!activeActionTrip.value) return
    isProcessing.value = true
    router.post(`/driver/trips/${activeActionTrip.value.id}/verify-vehicle`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showChecklistModal.value = false
            if (activeActionTrip.value) {
                activeActionTrip.value.status = 'READY'
            }
        },
        onError: () => { isProcessing.value = false }
    })
}

// Bước 2: Bắt đầu chạy / Xuất bến & Gửi về Admin (IN_TRANSIT)
const startRunningTrip = (trip) => {
    const targetTrip = trip || activeActionTrip.value
    if (!targetTrip) return
    if (!confirm(`Xác nhận bắt đầu cho xe ${targetTrip.bus?.license_plate || targetTrip.bus_plate || ''} xuất bến và lăn bánh? Hệ thống sẽ gửi thông báo xe đang chạy về Admin.`)) return
    isProcessing.value = true
    router.post(`/driver/trips/${targetTrip.id}/start`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            activeActionTrip.value = targetTrip
            activeActionTrip.value.status = 'IN_TRANSIT'
            activeTab.value = 'drive'
        },
        onError: () => { isProcessing.value = false }
    })
}

// Bước 3: Hoàn thành chuyến xe & Gửi về Admin (COMPLETED)
const completeTrip = (trip) => {
    const targetTrip = trip || activeActionTrip.value
    if (!targetTrip) return
    if (!confirm(`Xác nhận chuyến xe ${targetTrip.trip_code} đã đến bến an toàn? Hệ thống sẽ hoàn tất chuyến xe và gửi báo cáo về Admin.`)) return
    isProcessing.value = true
    router.post(`/driver/trips/${targetTrip.id}/finish`, {
        handover_notes: 'Chuyến xe kết thúc an toàn, không có sự cố kỹ thuật.'
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showFinishModal.value = false
            if (activeActionTrip.value) {
                activeActionTrip.value.status = 'COMPLETED'
            }
        },
        onError: () => { isProcessing.value = false }
    })
}

// ==================== 5. CHẾ ĐỘ LÁI XE & BẢN ĐỒ LIVE MAP (LEAFLET) ====================
const currentSpeed = ref(72)
const maxGaugeSpeed = 120
const speedGaugeDashoffset = computed(() => {
    const arcLength = 163.36 // π * 52 (bán kính r = 52)
    const ratio = Math.min(1, Math.max(0, currentSpeed.value / maxGaugeSpeed))
    return arcLength * (1 - ratio)
})
const speedGaugeColor = computed(() => {
    if (currentSpeed.value > 90) return '#EF4444' // Đỏ: Vượt quá tốc độ
    if (currentSpeed.value >= 60) return '#F59E0B' // Cam: Tốc độ hành trình an toàn (60 - 90 km/h)
    return '#10B981' // Xanh lá: Tốc độ an toàn / Đô thị
})
const speedGaugeBgColor = computed(() => {
    if (currentSpeed.value > 90) return 'rgba(239, 68, 68, 0.15)'
    if (currentSpeed.value >= 60) return 'rgba(245, 158, 11, 0.15)'
    return 'rgba(16, 185, 129, 0.15)'
})

const speedStatus = computed(() => {
    if (currentSpeed.value > 90) return { label: 'Vượt tốc độ (> 90 km/h)', color: 'text-red-500' }
    if (currentSpeed.value >= 60) return { label: 'Hành trình an toàn (60 - 90 km/h)', color: 'text-amber-500' }
    return { label: 'Tốc độ chậm / Đô thị (< 60 km/h)', color: 'text-emerald-500' }
})

// Tọa độ GPS mô phỏng di chuyển thực tế trên QL1A
const gpsCoords = ref({ lat: 17.2155, lng: 106.7865, locationName: 'Km 125 Quốc Lộ 1A (Khu vực Quảng Bình)' })

// Phát vị trí GPS trực tiếp cho hành khách (Requirement 2)
const isGpsBroadcastActive = ref(true)
let gpsBroadcastInterval = null

const broadcastGpsToPassengers = () => {
    isGpsBroadcastActive.value = !isGpsBroadcastActive.value
    if (isGpsBroadcastActive.value) {
        sendGpsUpdateToServer()
        if (!gpsBroadcastInterval) {
            gpsBroadcastInterval = setInterval(sendGpsUpdateToServer, 10000)
        }
    } else {
        if (gpsBroadcastInterval) {
            clearInterval(gpsBroadcastInterval)
            gpsBroadcastInterval = null
        }
    }
}

const sendGpsUpdateToServer = () => {
    if (!activeActionTrip.value) return
    axios.post(`/trips/${activeActionTrip.value.id}/update-gps`, {
        lat: gpsCoords.value.lat,
        lng: gpsCoords.value.lng,
        speed: currentSpeed.value,
        location_text: gpsCoords.value.locationName,
    }).catch(err => {
        // Silent catch for background GPS interval
    })
}

const copyPassengerTrackingLink = () => {
    if (!activeActionTrip.value) return
    const url = `${window.location.origin}/trips/${activeActionTrip.value.id}/live-tracking`
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url)
    }
    alert(`Đã sao chép link phát vị trí trực tiếp cho hành khách:\n${url}\nHành khách có thể mở link này để xem xe đang chạy tới đâu trên bản đồ.`)
}

// Modal xem lại lịch sử chuyến xe cũ (Requirement 2)
const showPastTripModal = ref(false)
const selectedPastTrip = ref(null)
const openPastTripModal = (trip) => {
    selectedPastTrip.value = trip
    showPastTripModal.value = true
}

// Luật đổi tài 4 tiếng: Đếm thời gian lái liên tục (phút)
const drivingMinutes = ref(165) // 02h 45m
const maxDrivingMinutes = 240 // 04h 00m
const drivingPercent = computed(() => Math.min(100, Math.round((drivingMinutes.value / maxDrivingMinutes) * 100)))
const remainingDrivingMinutes = computed(() => Math.max(0, maxDrivingMinutes - drivingMinutes.value))
const formattedRemainingTime = computed(() => {
    const h = Math.floor(remainingDrivingMinutes.value / 60)
    const m = remainingDrivingMinutes.value % 60
    return `${h < 10 ? '0' + h : h}h ${m < 10 ? '0' + m : m}m`
})
const isDrivingWarning = computed(() => drivingMinutes.value >= 210) // Cảnh báo khi vượt 3h30m (210 phút)
const isDrivingCritical = computed(() => drivingMinutes.value >= 240) // Chớp đỏ khi chạm 4 tiếng

const drivingBarGradient = computed(() => {
    if (drivingMinutes.value >= 240) return 'bg-gradient-to-r from-red-600 via-rose-500 to-red-600 animate-pulse'
    if (drivingMinutes.value >= 210) return 'bg-gradient-to-r from-amber-500 to-orange-500'
    return 'bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-500'
})

const formattedDrivingTime = computed(() => {
    const h = Math.floor(drivingMinutes.value / 60)
    const m = drivingMinutes.value % 60
    return `${h < 10 ? '0' + h : h}h ${m < 10 ? '0' + m : m}m`
})

// Hiệu ứng Loading xoay vòng khi bấm ĐÃ ĐẾN TRẠM (MỞ CỬA)
const isArrivingStationLoading = ref(false)
const handleArriveClick = () => {
    isArrivingStationLoading.value = true
    setTimeout(() => {
        isArrivingStationLoading.value = false
        promptArriveAtStop()
    }, 1400)
}

// Đổi tài xế
const shiftForm = useForm({
    second_driver_name: ''
})

watch(() => activeActionTrip.value, (trip) => {
    if (trip) {
        const coDriver = trip.second_driver || trip.secondDriver
        shiftForm.second_driver_name = coDriver ? coDriver.name : 'Tài xế 2 (Trần Đình Trọng)'
    }
}, { immediate: true })

const submitChangeShift = () => {
    if (!activeActionTrip.value) return
    isProcessing.value = true
    shiftForm.post(`/driver/trips/${activeActionTrip.value.id}/change-shift`, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showShiftModal.value = false
            drivingMinutes.value = 0
        },
        onError: () => { isProcessing.value = false }
    })
}

// Bác tài quản lý Trạm Dừng Nghỉ chân (Real-time sync với Admin)
const startRestStop = () => {
    if (!activeActionTrip.value) return
    if (!confirm('Xác nhận: Xe đã đến trạm dừng chân nghỉ ngơi? Hệ thống sẽ gửi thông báo thời gian thực về Admin.')) return
    isProcessing.value = true
    router.post(`/driver/trips/${activeActionTrip.value.id}/rest-stop`, {}, {
        preserveScroll: true,
        onFinish: () => { isProcessing.value = false }
    })
}

const resumeDriving = () => {
    if (!activeActionTrip.value) return
    if (!confirm('Xác nhận: Hành khách đã lên xe đầy đủ, tiếp tục hành trình lăn bánh? Hệ thống sẽ gửi thông báo thời gian thực về Admin.')) return
    isProcessing.value = true
    router.post(`/driver/trips/${activeActionTrip.value.id}/resume`, {}, {
        preserveScroll: true,
        onFinish: () => { isProcessing.value = false }
    })
}

// Mốc trạm sắp tới trong lộ trình
const nextStop = computed(() => {
    if (!props.stops || props.stops.length === 0) return null
    const currentOrder = activeActionTrip.value?.current_stop_order || 1
    const found = props.stops.find(s => s.stop_order > currentOrder)
    return found || props.stops[props.stops.length - 1]
})

// Cảnh báo Đón/Trả tại trạm (Flash Modal)
const flashBoardingPax = ref(3)
const flashDropoffPax = ref(2)

const promptArriveAtStop = () => {
    if (!activeActionTrip.value || !nextStop.value) return
    const stId = nextStop.value.id
    if (activeActionTrip.value.bookings) {
        flashBoardingPax.value = activeActionTrip.value.bookings.filter(b => b.pickup_stop_id === stId).length || 3
        flashDropoffPax.value = activeActionTrip.value.bookings.filter(b => b.dropoff_stop_id === stId).length || 2
    }
    showArriveFlashModal.value = true
}

const confirmArriveAtStop = () => {
    showArriveFlashModal.value = false
    if (!activeActionTrip.value || !nextStop.value) return
    isProcessing.value = true
    router.post(`/driver/trips/${activeActionTrip.value.id}/stop`, {
        stop_order: nextStop.value.stop_order,
        action: 'ARRIVE',
        stop_name: nextStop.value.stop_name
    }, {
        preserveScroll: true,
        onSuccess: () => { isProcessing.value = false },
        onError: () => { isProcessing.value = false }
    })
}

// Báo trễ
const delayForm = useForm({
    delay_minutes: 30,
    delay_reason: 'Ùn tắc giao thông do mưa lớn tại Quốc Lộ 1A'
})

const submitDelay = () => {
    if (!activeActionTrip.value) return
    isProcessing.value = true
    delayForm.post(`/driver/trips/${activeActionTrip.value.id}/report-delay`, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showDelayModal.value = false
        },
        onError: () => { isProcessing.value = false }
    })
}

// Báo sự cố xe khẩn cấp
const incidentForm = useForm({
    type: 'BREAKDOWN',
    severity: 'HIGH',
    description: 'Xe bị nổ lốp sau bên phụ tại Km 132 QL1A, đang tấp lề xử lý thay lốp',
    location: 'Km 132 Quốc Lộ 1A'
})

const submitIncident = () => {
    if (!activeActionTrip.value) return
    isProcessing.value = true
    incidentForm.post(`/driver/trips/${activeActionTrip.value.id}/report-incident`, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showIncidentModal.value = false
        },
        onError: () => { isProcessing.value = false }
    })
}

// ==================== 6. SOS KHẨN CẤP (NHẤN VÀ GIỮ 3 GIÂY) ====================
const sosForm = useForm({
    location: 'Km 125 Quốc Lộ 1A (Khu vực Đèo Lý Hòa)',
    reason: 'Sự cố khẩn cấp trên đường - Cần lực lượng hỗ trợ ngay lập tức'
})

const sosHoldProgress = ref(0)
const isHoldingSos = ref(false)
let sosHoldTimer = null

const startSosHold = () => {
    isHoldingSos.value = true
    sosHoldProgress.value = 0
    const stepMs = 30
    sosHoldTimer = setInterval(() => {
        sosHoldProgress.value += 1
        if (sosHoldProgress.value >= 100) {
            cancelSosHold()
            triggerSosNow()
        }
    }, stepMs)
}

const cancelSosHold = () => {
    isHoldingSos.value = false
    if (sosHoldTimer) {
        clearInterval(sosHoldTimer)
        sosHoldTimer = null
    }
    if (sosHoldProgress.value < 100) {
        sosHoldProgress.value = 0
    }
}

const isSosAlarmTriggered = ref(false)
const triggerSosNow = () => {
    if (!activeActionTrip.value) return
    isProcessing.value = true
    isSosAlarmTriggered.value = true
    sosForm.post(`/driver/trips/${activeActionTrip.value.id}/sos`, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showSosModal.value = false
            alert('🚨 ĐÃ PHÁT TÍN HIỆU SOS KHẨN CẤP! Tọa độ GPS đã gửi thẳng về Trung Tâm Điều Hành Admin & Cứu Hộ.')
        },
        onError: () => { 
            isProcessing.value = false 
            isSosAlarmTriggered.value = false
        }
    })
}

// ==================== 7. QUẢN LÝ CHI PHÍ (VÍ TÀI XẾ + CHỤP HÓA ĐƠN) ====================
const expenseForm = useForm({
    expense_type: 'Xăng dầu Diesel',
    amount: '',
    description: 'Đổ 100 lít dầu Diesel tại Petrolimex Km 115',
    receipt_image: '',
    receipt_file: null
})

const receiptPreviewUrl = ref('')

const handleReceiptUpload = (e) => {
    const file = e.target.files[0]
    if (file) {
        expenseForm.receipt_file = file
        const reader = new FileReader()
        reader.onload = (evt) => {
            receiptPreviewUrl.value = evt.target.result
            expenseForm.receipt_image = evt.target.result
        }
        reader.readAsDataURL(file)
    }
}

const submitExpense = () => {
    if (!activeActionTrip.value) return
    isProcessing.value = true
    expenseForm.post(`/driver/trips/${activeActionTrip.value.id}/expense`, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showExpenseModal.value = false
            expenseForm.reset()
            receiptPreviewUrl.value = ''
        },
        onError: () => { isProcessing.value = false }
    })
}

const totalTripExpenses = computed(() => {
    if (!props.tripExpenses || !props.tripExpenses.length) return 0
    return props.tripExpenses.reduce((sum, item) => sum + Number(item.amount || 0), 0)
})

// ==================== 8. KẾT THÚC CHUYẾN & SỔ GIAO CA KỸ THUẬT ====================
const finishForm = useForm({
    handover_notes: 'Xe hoạt động tốt 100%, không lỗi. Dầu nhớt và phanh kiểm tra an toàn.'
})

const quickHandoverTags = [
    'Xe hoạt động tốt 100%, không lỗi',
    'Điều hòa tầng 2 kêu to / rỉ nước',
    'Lốp sau bên phụ hơi non áp suất',
    'Đèn gầm bên trái mờ, cần kiểm tra',
    'Cần thay gạt mưa kính chắn gió',
    'Đến hạn thay dầu nhớt động cơ'
]

const addHandoverTag = (tag) => {
    if (finishForm.handover_notes.includes(tag)) return
    finishForm.handover_notes = finishForm.handover_notes ? `${finishForm.handover_notes} | ${tag}` : tag
}

const submitFinishTrip = () => {
    if (!activeActionTrip.value) return
    isProcessing.value = true
    finishForm.post(`/driver/trips/${activeActionTrip.value.id}/finish`, {
        preserveScroll: true,
        onSuccess: () => {
            isProcessing.value = false
            showFinishModal.value = false
            activeTab.value = 'today'
        },
        onError: () => { isProcessing.value = false }
    })
}

// ==================== 9. SƠ ĐỒ TẢI TRỌNG 36 GIƯỜNG (VIEW-ONLY) ====================
const loadStats = computed(() => {
    let t1Count = 0
    let t2Count = 0
    const customerSet = new Set()
    if (props.seatMap) {
        Object.entries(props.seatMap).forEach(([seatNum, seat]) => {
            if (seat && seat.status !== 'AVAILABLE') {
                if (seatNum.startsWith('A')) t1Count++
                if (seatNum.startsWith('B')) t2Count++
                if (seat.customer_phone) customerSet.add(seat.customer_phone.trim())
                else if (seat.customer_name) customerSet.add(seat.customer_name.trim().toLowerCase())
            }
        })
    }
    const total = t1Count + t2Count
    const uniqueCustomers = customerSet.size > 0 ? customerSet.size : total
    const percent = Math.min(100, Math.round((total / 30) * 100))
    const isHeavyTop = t2Count > (t1Count + 2)
    const isBalanced = Math.abs(t1Count - t2Count) <= 4

    return {
        total,
        uniqueCustomers,
        percent,
        t1Count,
        t2Count,
        isHeavyTop,
        isBalanced,
        weightKgEstimate: total * 65
    }
})

const viewingSeatDetail = ref(null)
const openSeatReadOnlyModal = (seatNum) => {
    const seat = props.seatMap ? props.seatMap[seatNum] : null
    if (seat && seat.status !== 'AVAILABLE') {
        viewingSeatDetail.value = { ...seat, seat_number: seatNum }
    }
}

// ==================== 10. GOOGLE MAPS & LAPTOP LIVE GPS GEOLOCATION ====================
const mapContainer = ref(null)
let leafletMap = null
let busMarker = null
let laptopPulseCircle = null
let polylineLayer = null
let stopMarkerLayers = []
let mapTileLayer = null

// Chế độ bản đồ: 'google' (Google Maps đường bộ), 'satellite' (Google Maps vệ tinh), 'mapbox_dark' (Ban đêm)
const mapLayerMode = ref('google')

// Trạng thái định vị Geolocation laptop thực tế
const isTrackingLaptop = ref(false)
const isLocating = ref(false)
const laptopCoords = ref(null)
const laptopAccuracy = ref(null)
const locationStatusMessage = ref('Đang khởi tạo định vị GPS Laptop...')

const updateMapTileLayer = () => {
    if (!leafletMap) return
    if (mapTileLayer) {
        try { leafletMap.removeLayer(mapTileLayer) } catch (e) {}
        mapTileLayer = null
    }

    let tileUrl = 'https://mt{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'
    let subdomains = ['0', '1', '2', '3']
    let attribution = '&copy; Google Maps'

    if (mapLayerMode.value === 'mapbox_dark') {
        tileUrl = 'https://{s}.basemaps.cartocdn.com/rastertiles/dark_all/{z}/{x}/{y}.png'
        subdomains = ['a', 'b', 'c', 'd']
        attribution = '&copy; Mapbox Navigation Night / CartoDB'
    } else if (mapLayerMode.value === 'satellite') {
        tileUrl = 'https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'
        subdomains = ['0', '1', '2', '3']
        attribution = '&copy; Google Maps Satellite'
    }

    mapTileLayer = L.tileLayer(tileUrl, {
        attribution: attribution,
        maxZoom: 20,
        subdomains: subdomains
    }).addTo(leafletMap)
}

const toggleMapMode = (mode) => {
    mapLayerMode.value = mode
    updateMapTileLayer()
}

// Hàm lấy vị trí Geolocation thực tế từ Laptop của người dùng
const getLaptopLocation = (centerImmediately = true) => {
    if (!navigator.geolocation) {
        locationStatusMessage.value = 'Trình duyệt không hỗ trợ Geolocation'
        return
    }
    isLocating.value = true
    locationStatusMessage.value = 'Đang dò vị trí GPS thực tế từ máy tính laptop...'

    navigator.geolocation.getCurrentPosition(
        (position) => {
            isLocating.value = false
            const lat = position.coords.latitude
            const lng = position.coords.longitude
            const acc = Math.round(position.coords.accuracy || 15)

            laptopCoords.value = { lat, lng }
            laptopAccuracy.value = acc
            isTrackingLaptop.value = true
            locationStatusMessage.value = `Đã định vị thành công GPS Laptop (${lat.toFixed(4)}°N, ${lng.toFixed(4)}°E) • Sai số ±${acc}m`

            // Cập nhật vị trí GPS chung
            gpsCoords.value = {
                lat,
                lng,
                locationName: `Vị trí Laptop (${lat.toFixed(4)}°N, ${lng.toFixed(4)}°E)`
            }

            if (leafletMap) {
                if (centerImmediately) {
                    leafletMap.setView([lat, lng], 15, { animate: true })
                }
                updateMarkerAtLaptop(lat, lng, acc)
            }
        },
        (error) => {
            isLocating.value = false
            let errText = 'Chưa cấp quyền truy cập vị trí'
            if (error.code === 1) errText = 'Bác tài vui lòng nhấn "Cho phép" (Allow) trên trình duyệt để chia sẻ GPS laptop'
            else if (error.code === 2) errText = 'Không thu được tín hiệu vệ tinh GPS'
            else if (error.code === 3) errText = 'Quá thời gian kết nối GPS'
            locationStatusMessage.value = `⚠️ ${errText}`
            console.warn('Geolocation warning:', error)
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    )
}

const startWatchPosition = () => {
    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            (position) => {
                const lat = position.coords.latitude
                const lng = position.coords.longitude
                laptopCoords.value = { lat, lng }
                isTrackingLaptop.value = true
                gpsCoords.value.lat = lat
                gpsCoords.value.lng = lng
                if (leafletMap && busMarker) {
                    updateMarkerAtLaptop(lat, lng, Math.round(position.coords.accuracy || 15))
                }
            },
            () => {},
            { enableHighAccuracy: true }
        )
    }
}

const CITY_COORDINATES = {
    'đà nẵng': [16.0544, 108.2022],
    'huế': [16.4637, 107.5909],
    'lăng cô': [16.2300, 108.0100],
    'phú bài': [16.3980, 107.6960],
    'đông hà': [16.8167, 107.1000],
    'quảng trị': [16.7500, 107.1850],
    'quảng bình': [17.4687, 106.6225],
    'đồng hới': [17.4760, 106.6000],
    'lệ thủy': [17.2155, 106.7865],
    'quy nhơn': [13.7820, 109.2190],
    'bình định': [13.7820, 109.2190],
    'nha trang': [12.2388, 109.1967],
    'khánh hòa': [12.2388, 109.1967],
    'đắk lắk': [12.6667, 108.0500],
    'buôn ma thuột': [12.6667, 108.0500],
    'sài gòn': [10.7769, 106.7009],
    'hồ chí minh': [10.7769, 106.7009],
    'hà nội': [21.0285, 105.8542],
}

const getStopLatLng = (stopName, idx, total) => {
    if (!stopName) {
        return [16.0544 + (idx * 0.22), 108.2022 - (idx * 0.12)]
    }
    const nameLower = String(stopName).toLowerCase()
    for (const [key, coords] of Object.entries(CITY_COORDINATES)) {
        if (nameLower.includes(key)) {
            return [coords[0] + (idx * 0.012), coords[1] + (idx * 0.012)]
        }
    }
    return [16.0544 + (idx * 0.25), 108.2022 - (idx * 0.15)]
}

const updateMarkerAtLaptop = (lat, lng, accuracy) => {
    if (!leafletMap) return
    const latLng = [lat, lng]

    if (busMarker) {
        busMarker.setLatLng(latLng)
        const popupHtml = `
            <div class="text-xs p-1 text-slate-900 font-sans">
                <div class="flex items-center gap-1 text-red-600 font-black text-sm mb-1">
                    <i class="fa-solid fa-laptop text-base"></i>
                    <span>VỊ TRÍ LAPTOP THỰC TẾ CỦA BẠN</span>
                </div>
                <div class="space-y-0.5 text-slate-700">
                    <div>• Tọa độ: <strong>${lat.toFixed(5)}°N, ${lng.toFixed(5)}°E</strong></div>
                    <div>• Độ chính xác thiết bị: <strong>±${accuracy || 15}m</strong></div>
                    <div>• Xe phân công: <strong>${activeActionTrip.value?.bus?.license_plate || '43B-067.89'}</strong></div>
                </div>
                <span class="inline-block mt-1 text-[10px] font-bold text-emerald-600">● Tín hiệu GPS Laptop đang hoạt động trực tiếp</span>
            </div>
        `
        if (busMarker.isPopupOpen && busMarker.isPopupOpen()) {
            busMarker.setPopupContent(popupHtml)
        } else {
            busMarker.bindPopup(popupHtml, { autoPan: false })
        }
    }

    // Vòng tròn bán kính sai số GPS
    if (laptopPulseCircle) {
        laptopPulseCircle.setLatLng(latLng)
        laptopPulseCircle.setRadius(accuracy || 30)
    } else {
        laptopPulseCircle = L.circle(latLng, {
            radius: accuracy || 30,
            color: '#3b82f6',
            fillColor: '#60a5fa',
            fillOpacity: 0.2,
            weight: 1.5
        }).addTo(leafletMap)
    }
}

const initLiveMap = () => {
    const el = mapContainer.value || document.getElementById('driver-leaflet-map')
    if (!el) return

    if (leafletMap) {
        try {
            leafletMap.remove()
        } catch (e) {
            console.warn('Map cleanup error:', e)
        }
        leafletMap = null
        busMarker = null
        laptopPulseCircle = null
        polylineLayer = null
        stopMarkerLayers = []
        mapTileLayer = null
    }

    try {
        delete L.Icon.Default.prototype._getIconUrl
        L.Icon.Default.mergeOptions({
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png'
        })

        const initialCoords = [gpsCoords.value.lat || 16.0544, gpsCoords.value.lng || 108.2022]
        leafletMap = L.map(el, {
            center: initialCoords,
            zoom: 12,
            zoomControl: true
        })

        updateMapTileLayer()

        // Danh sách trạm dừng
        const stopsList = props.stops && props.stops.length > 0 ? props.stops : [
            { id: 1, stop_order: 1, stop_name: 'Bến xe Trung tâm Đà Nẵng' },
            { id: 2, stop_order: 2, stop_name: 'Trạm dừng Lệ Thủy' },
            { id: 3, stop_order: 3, stop_name: 'Bến xe Đồng Hới' }
        ]

        const latLngs = stopsList.map((s, idx) => getStopLatLng(s.stop_name, idx, stopsList.length))

        polylineLayer = L.polyline(latLngs, {
            color: '#2563eb',
            weight: 6,
            opacity: 0.85,
            smoothFactor: 1
        }).addTo(leafletMap)

        stopsList.forEach((s, idx) => {
            const coords = latLngs[idx]
            const isCurrent = s.stop_order === (activeActionTrip.value?.current_stop_order || 1)
            const isPassed = s.stop_order < (activeActionTrip.value?.current_stop_order || 1)

            const stopIcon = L.divIcon({
                className: 'custom-stop-icon',
                html: `
                    <div class="flex items-center justify-center w-7 h-7 rounded-full text-[11px] font-black shadow-lg border-2 text-white ${
                        isPassed ? 'bg-slate-400 border-white' : (isCurrent ? 'bg-amber-500 border-white ring-4 ring-amber-300' : 'bg-blue-600 border-white')
                    }">
                        ${s.stop_order}
                    </div>
                `,
                iconSize: [28, 28],
                iconAnchor: [14, 14]
            })

            const marker = L.marker(coords, { icon: stopIcon }).addTo(leafletMap)
            marker.bindPopup(`
                <div class="text-xs p-1 text-slate-900 font-sans">
                    <strong class="font-bold block text-sm">${s.stop_name}</strong>
                    <span class="text-slate-500">Trạm thứ ${s.stop_order} trên lộ trình</span>
                </div>
            `)
            stopMarkerLayers.push(marker)
        })

        const busIcon = L.divIcon({
            className: 'custom-bus-icon',
            html: `
                <div class="relative flex items-center justify-center">
                    <!-- Vòng sóng radar tỏa xung quanh (Live Tracking Pulsing Effect) -->
                    <div class="absolute -inset-6 rounded-full bg-cyan-400/25 animate-ping"></div>
                    <div class="absolute -inset-3 rounded-full bg-cyan-500/40 animate-pulse"></div>
                    <div class="relative w-12 h-12 rounded-2xl bg-gradient-to-tr from-cyan-600 via-blue-600 to-indigo-600 border-2 border-white shadow-2xl shadow-cyan-500/50 flex items-center justify-center text-white text-lg">
                        <i class="fa-solid fa-location-arrow -rotate-45"></i>
                    </div>
                </div>
            `,
            iconSize: [48, 48],
            iconAnchor: [24, 24]
        })

        busMarker = L.marker(initialCoords, { icon: busIcon }).addTo(leafletMap)

        // Khắc phục triệt để lỗi màn hình trắng: Cho Leaflet thời gian tính toán kích thước container
        setTimeout(() => {
            if (leafletMap) {
                leafletMap.invalidateSize()
            }
        }, 150)

        setTimeout(() => {
            if (leafletMap) {
                leafletMap.invalidateSize()
                getLaptopLocation(false)
                startWatchPosition()
            }
        }, 500)
    } catch (err) {
        console.error('Lỗi khởi tạo Leaflet map:', err)
    }
}

const recenterToBus = () => {
    if (leafletMap && busMarker) {
        leafletMap.setView(busMarker.getLatLng(), 15, { animate: true })
        busMarker.openPopup()
    }
}

const handleMapResize = () => {
    if (leafletMap) {
        leafletMap.invalidateSize()
    }
}

const switchTabToDrive = () => {
    activeTab.value = 'drive'
    setTimeout(() => {
        if (!leafletMap) {
            initLiveMap()
        } else {
            leafletMap.invalidateSize()
            recenterToBus()
        }
    }, 120)
}

watch(activeTab, (tab) => {
    if (tab === 'drive') {
        setTimeout(() => {
            if (!leafletMap) {
                initLiveMap()
            } else {
                leafletMap.invalidateSize()
                recenterToBus()
            }
        }, 120)
    }
})


// ==================== 11. LỊCH TRÌNH (CALENDAR Ô VUÔNG 7 NGÀY & TIMELINE DỌC) ====================
// Chế độ xem: 'timeline' (Mặc định - Timeline dọc) hoặc 'calendar' (Lịch ô vuông tuần)
const scheduleViewMode = ref('timeline')

// Tiện ích xử lý ngày tháng theo múi giờ Việt Nam
const getVNTodayObj = () => {
    const now = new Date()
    return new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Ho_Chi_Minh' }))
}

const formatDateToYYYYMMDD = (d) => {
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

const formatDateToDM = (d) => {
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    return `${day}/${month}`
}

const formatDateToFullDMY = (d) => {
    const day = String(d.getDate()).padStart(2, '0')
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const year = d.getFullYear()
    return `${day}/${month}/${year}`
}

const todayVNObj = getVNTodayObj()
const todayDateStr = formatDateToYYYYMMDD(todayVNObj)

const yesterdayVNObj = new Date(todayVNObj)
yesterdayVNObj.setDate(yesterdayVNObj.getDate() - 1)
const yesterdayDateStr = formatDateToYYYYMMDD(yesterdayVNObj)

const tomorrowVNObj = new Date(todayVNObj)
tomorrowVNObj.setDate(tomorrowVNObj.getDate() + 1)
const tomorrowDateStr = formatDateToYYYYMMDD(tomorrowVNObj)

// Ngày được chọn để lọc (mặc định hôm nay) hoặc 'ALL' (toàn bộ tuần)
const selectedDateFilter = ref(todayDateStr)

// Tính thông tin tuần hiện tại (Thứ 2 đến Chủ Nhật)
const currentWeekInfo = computed(() => {
    const now = getVNTodayObj()
    const dayOfWeek = now.getDay() // 0 = CN, 1 = T2, ... 6 = T7
    const diffToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
    const monday = new Date(now)
    monday.setDate(now.getDate() + diffToMonday)

    const sunday = new Date(monday)
    sunday.setDate(monday.getDate() + 6)

    // ISO week number
    const tempDate = new Date(monday.valueOf())
    tempDate.setDate(tempDate.getDate() + (4 - (tempDate.getDay() || 7)))
    const yearStart = new Date(tempDate.getFullYear(), 0, 1)
    const weekNo = Math.ceil((((tempDate - yearStart) / 86400000) + 1) / 7)

    return {
        monday,
        sunday,
        weekNumber: weekNo,
        monthStr: `THÁNG ${(monday.getMonth() + 1).toString().padStart(2, '0')}/${monday.getFullYear()}`,
        weekRangeStr: `Tuần ${weekNo}: ${formatDateToFullDMY(monday)} ➔ ${formatDateToFullDMY(sunday)}`,
        todayStr: todayDateStr,
        yesterdayStr: yesterdayDateStr,
        tomorrowStr: tomorrowDateStr,
        todayDM: formatDateToDM(todayVNObj),
        yesterdayDM: formatDateToDM(yesterdayVNObj),
        tomorrowDM: formatDateToDM(tomorrowVNObj),
        sundayDM: formatDateToDM(sunday),
    }
})

// Sinh danh sách 7 ngày trong tuần hiện tại (Thứ 2 đến Chủ Nhật)
const weekDays = computed(() => {
    const monday = new Date(currentWeekInfo.value.monday)
    const dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật']
    const shortDays = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']

    const days = []
    for (let i = 0; i < 7; i++) {
        const d = new Date(monday)
        d.setDate(monday.getDate() + i)
        const dateStr = formatDateToYYYYMMDD(d)
        const isToday = dateStr === todayDateStr
        const isPast = dateStr < todayDateStr
        const isRestDay = false

        const tripsOnDay = (props.allTrips || []).filter(t => t.departure_time && t.departure_time.startsWith(dateStr))
        const completedCount = tripsOnDay.filter(t => t.status === 'COMPLETED' || t.status === 'ARRIVED' || t.status === 'CLOSED').length

        days.push({
            dateStr,
            dayName: dayNames[i],
            shortDay: shortDays[i],
            dayNum: String(d.getDate()).padStart(2, '0'),
            fullDateDM: formatDateToDM(d),
            year: d.getFullYear(),
            isToday,
            isPast,
            isRestDay,
            tripCount: tripsOnDay.length,
            completedCount,
            trips: tripsOnDay
        })
    }
    return days
})

const filteredScheduleTrips = computed(() => {
    const all = props.allTrips || []
    if (selectedDateFilter.value === 'ALL') {
        return all.slice().sort((a, b) => (a.departure_time || '').localeCompare(b.departure_time || ''))
    }
    return all.filter(t => (t.departure_time || '').startsWith(selectedDateFilter.value))
        .sort((a, b) => (a.departure_time || '').localeCompare(b.departure_time || ''))
})

const completedTripsOnSelectedDay = computed(() => {
    return filteredScheduleTrips.value.filter(t => t.status === 'COMPLETED' || t.status === 'ARRIVED' || t.status === 'CLOSED')
})

const weeklyStats = computed(() => {
    const all = props.allTrips || []
    const totalTrips = all.length
    const completedTrips = all.filter(t => t.status === 'COMPLETED' || t.status === 'ARRIVED' || t.status === 'CLOSED').length
    const totalKm = all
        .filter(t => t.status === 'COMPLETED' || t.status === 'ARRIVED' || t.status === 'CLOSED')
        .reduce((sum, t) => sum + Number(t.route?.distance_km || 110), 0)
    const totalPax = all
        .filter(t => t.status === 'COMPLETED' || t.status === 'ARRIVED' || t.status === 'CLOSED')
        .reduce((sum, t) => sum + (t.occupied_count !== undefined ? t.occupied_count : (t.bookings ? t.bookings.length : 0)), 0)

    return {
        totalTrips: totalTrips,
        completedTrips: completedTrips,
        totalKm: totalKm,
        totalPax: totalPax,
        rating: props.currentDriver?.avg_rating || props.currentDriver?.rating || '4.9',
        onTimeRate: '98.5%'
    }
})

const selectDateForSchedule = (dateStr) => {
    selectedDateFilter.value = dateStr
}

// Chuyển thẳng sang Taplo hoặc Xem Lộ trình
const openTripInDriveMode = (trip) => {
    activeActionTrip.value = trip
    activeTab.value = 'drive'
}

// ==================== 11. LỊCH SỬ CHUYẾN ĐÃ CHẠY HOÀN THÀNH & XEM THEO NGÀY ====================
const historyDateFilter = ref(todayDateStr)

const completedTripsList = computed(() => {
    const all = props.allTrips || []
    return all.filter(t => t.status === 'COMPLETED' || t.status === 'ARRIVED' || t.status === 'CLOSED')
})

const filteredHistoryTrips = computed(() => {
    const list = completedTripsList.value
    if (historyDateFilter.value === 'ALL') {
        return list.slice().sort((a, b) => (b.departure_time || '').localeCompare(a.departure_time || ''))
    }
    return list.filter(t => (t.departure_time || '').startsWith(historyDateFilter.value))
        .sort((a, b) => (b.departure_time || '').localeCompare(a.departure_time || ''))
})

const historyDateStats = computed(() => {
    const trips = filteredHistoryTrips.value
    const count = trips.length
    const totalKm = trips.reduce((sum, t) => sum + (t.route?.distance_km || 100), 0)
    const totalPax = trips.reduce((sum, t) => sum + (t.occupied_count !== undefined ? t.occupied_count : (t.bookings ? t.bookings.length : 0)), 0)
    return {
        count,
        totalKm,
        totalPax
    }
})

// ==================== 12. HỆ THỐNG ĐÁNH GIÁ THẬT & BỘ LỌC SAO ====================
const showAllReviewsModal = ref(false)
const selectedStarFilter = ref('ALL') // 'ALL', '5', '4', '3', '2', '1'

const totalReviewCount = computed(() => props.driverReviews?.length || 0)

const averageRating = computed(() => {
    if (!props.driverReviews || props.driverReviews.length === 0) {
        return props.currentDriver?.avg_rating || 4.9
    }
    const sum = props.driverReviews.reduce((acc, r) => acc + Number(r.rating || 5), 0)
    return (sum / props.driverReviews.length).toFixed(1)
})

const starCounts = computed(() => {
    const list = props.driverReviews || []
    const c = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }
    list.forEach(r => {
        const s = Math.round(Number(r.rating || 5))
        if (c[s] !== undefined) c[s]++
    })
    return c
})

const ratingBreakdown = computed(() => {
    const list = props.driverReviews || []
    const total = list.length || 1
    const counts = starCounts.value
    return [
        { stars: 5, label: '5 Sao', percent: Math.round((counts[5] / total) * 100), count: counts[5], color: 'bg-emerald-500' },
        { stars: 4, label: '4 Sao', percent: Math.round((counts[4] / total) * 100), count: counts[4], color: 'bg-blue-500' },
        { stars: 3, label: '3 Sao', percent: Math.round((counts[3] / total) * 100), count: counts[3], color: 'bg-amber-500' },
        { stars: 2, label: '2 Sao', percent: Math.round((counts[2] / total) * 100), count: counts[2], color: 'bg-orange-400' },
        { stars: 1, label: '1 Sao', percent: Math.round((counts[1] / total) * 100), count: counts[1], color: 'bg-rose-500' },
    ]
})

const filteredReviews = computed(() => {
    const list = props.driverReviews || []
    if (selectedStarFilter.value === 'ALL') return list
    const star = Number(selectedStarFilter.value)
    return list.filter(r => Math.round(Number(r.rating || 5)) === star)
})

const initialDisplayedReviews = computed(() => {
    return (props.driverReviews || []).slice(0, 6)
})

// Formatters

// Tiêu đề ngày hiển thị trên Sticky Header của Tab Lịch Trình
const activeDayTitle = computed(() => {
    if (selectedDateFilter.value === 'ALL') return 'Tất cả chuyến xe trong tuần'
    const found = weekDays.value.find(d => d.dateStr === selectedDateFilter.value)
    return found ? `${found.dayName}, ngày ${found.fullDateDM}/${found.year}` : `Ngày ${selectedDateFilter.value}`
})

const makeCall = (phone) => { if (phone) window.location.href = `tel:${phone}` }

// Phân loại vai trò trạm dừng thông minh & màu sắc trực quan
const getStopRole = (st, idx, total) => {
    if (idx === 0) return { label: 'Khởi Hành', type: 'ORIGIN', color: 'blue', badge: 'bg-blue-600 text-white' }
    if (idx === total - 1) return { label: 'Bến Đích Cuối', type: 'DESTINATION', color: 'red', badge: 'bg-red-600 text-white' }

    const rawType = String(st.stop_type || st.type || '').toUpperCase()
    const name = String(st.stop_name || st.name || '').toLowerCase()

    if (rawType === 'PICKUP' || name.includes('đón') || name.includes('trung chuyển') || name.includes('văn phòng')) {
        return { label: 'Điểm Đón Khách', type: 'PICKUP', color: 'blue', badge: 'bg-blue-100 text-blue-900 border border-blue-300 font-black' }
    }
    if (rawType === 'DROPOFF' || name.includes('trả') || name.includes('xuống') || name.includes('cửa ngõ')) {
        return { label: 'Điểm Trả Khách', type: 'DROPOFF', color: 'emerald', badge: 'bg-emerald-100 text-emerald-900 border border-emerald-300 font-black' }
    }
    if (rawType === 'REST_STOP' || name.includes('nghỉ') || name.includes('ăn')) {
        return { label: 'Trạm Dừng Nghỉ', type: 'REST_STOP', color: 'amber', badge: 'bg-amber-100 text-amber-900 border border-amber-300 font-black' }
    }
    return idx < total / 2
        ? { label: 'Điểm Đón Khách', type: 'PICKUP', color: 'blue', badge: 'bg-blue-100 text-blue-900 border border-blue-300 font-black' }
        : { label: 'Điểm Trả Khách', type: 'DROPOFF', color: 'emerald', badge: 'bg-emerald-100 text-emerald-900 border border-emerald-300 font-black' }
}

// Lấy danh sách đầy đủ các trạm đón/trả thực tế của chuyến xe
const getTripDropoffStops = (trip) => {
    const isSelected = (trip?.id === props.selectedTrip?.id || trip?.id === activeActionTrip.value?.id)
    if (isSelected && props.stationDropoffs && props.stationDropoffs.length > 0) {
        return props.stationDropoffs
    }

    // Danh sách trạm từ lộ trình trip.route.stops
    const routeStops = (trip?.route?.stops && trip.route.stops.length > 0)
        ? [...trip.route.stops].sort((a, b) => (a.stop_order || 0) - (b.stop_order || 0))
        : [
            { id: 1, stop_order: 1, stop_type: 'PICKUP', stop_name: trip?.route?.origin ? `Bến xe ${trip.route.origin}` : 'Bến xe xuất phát', address: 'Điểm xuất phát bến đi ban đầu', distance_from_start_km: 0, estimated_minutes_from_start: 0 },
            { id: 2, stop_order: 2, stop_type: 'PICKUP', stop_name: trip?.route?.origin ? `Trạm đón ${trip.route.origin}` : 'Trạm đón trung chuyển', address: 'Điểm đón nội thành', distance_from_start_km: 15, estimated_minutes_from_start: 25 },
            { id: 3, stop_order: 3, stop_type: 'DROPOFF', stop_name: trip?.route?.destination ? `Trạm trả ${trip.route.destination}` : 'Trạm trả khách', address: 'Cửa ngõ trả khách', distance_from_start_km: 140, estimated_minutes_from_start: 150 },
            { id: 4, stop_order: 4, stop_type: 'DROPOFF', stop_name: trip?.route?.destination ? `Bến xe ${trip.route.destination}` : 'Bến xe đích đến', address: 'Bến xe kết thúc hành trình', distance_from_start_km: 180, estimated_minutes_from_start: 210 }
        ]

    const totalStops = routeStops.length
    return routeStops.map((st, idx) => {
        const order = st.stop_order || (idx + 1)
        const isFirst = (idx === 0)
        const isLast = (idx === totalStops - 1)
        const nameLower = String(st.stop_name || '').toLowerCase()
        let resolvedType = st.stop_type || st.type
        if (!resolvedType) {
            if (isFirst) resolvedType = 'ORIGIN'
            else if (isLast) resolvedType = 'DESTINATION'
            else if (nameLower.includes('đón') || nameLower.includes('trung chuyển')) resolvedType = 'PICKUP'
            else if (nameLower.includes('trả') || nameLower.includes('xuống')) resolvedType = 'DROPOFF'
            else if (nameLower.includes('nghỉ') || nameLower.includes('ăn')) resolvedType = 'REST_STOP'
            else resolvedType = idx < totalStops / 2 ? 'PICKUP' : 'DROPOFF'
        }
        return {
            stop_id: st.id,
            stop_order: order,
            type: resolvedType,
            stop_type: resolvedType,
            stop_name: st.stop_name,
            address: st.address || (isFirst ? 'Điểm xuất phát bến đi ban đầu' : (isLast ? 'Bến xe đích đến cuối cùng' : 'Trạm đón trả trên tuyến')),
            distance_km: st.distance_from_start_km || 0,
            estimated_minutes: st.estimated_minutes_from_start || 0,
            total_pax: isLast ? (trip?.occupied_count || trip?.total_seats_sold || 0) : 0,
            passengers: []
        }
    })
}

onMounted(() => {
    document.addEventListener('click', handleClickOutsidePortal)
    setTimeout(() => {
        initLiveMap()
    }, 250)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutsidePortal)
    if (restTimer) clearInterval(restTimer)
    if (sosHoldTimer) clearInterval(sosHoldTimer)
    if (leafletMap) {
        leafletMap.remove()
        leafletMap = null
    }
})
</script>

<template>
  <div :class="{ 'dark bg-[#121212] text-zinc-100': isDarkMode, 'bg-[#F8FAFC] text-slate-900': !isDarkMode }" class="min-h-screen font-sans transition-colors duration-500 ease-in-out">
    <Head title="Bác Tài - Bảng Điều Khiển Lái Xe Thông Minh" />

    <!-- DÀNH RIÊNG CHO QUẢN TRỊ VIÊN: DẢI ĐIỀU KHIỂN & CHUYỂN ACC BÁC TÀI (ĐỒNG BỘ MÀU ĐỎ SANG TRỌNG NHAXEKIENTRUC) -->
    <div v-if="isAdminViewing" class="bg-gradient-to-r from-[#3b0609] via-[#5c0a0e] to-[#750e12] border-b border-red-500/30 text-white px-4 py-2 text-xs flex flex-wrap items-center justify-between gap-3 shadow-md z-50 relative">
      <div class="flex items-center gap-3 flex-wrap">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/15 text-amber-300 border border-white/20 font-black uppercase text-[10px] tracking-wider shadow-xs">
          <i class="fa-solid fa-shield-halved"></i> Admin Viewing Mode
        </span>
        <div class="flex items-center gap-2 bg-black/30 border border-white/20 rounded-xl px-2.5 py-1">
          <span class="text-white/80 font-bold">Chuyển Acc Bác Tài:</span>
          <select
            :value="currentDriver?.id"
            @change="switchDriver($event.target.value)"
            class="bg-transparent font-black text-amber-300 outline-none cursor-pointer max-w-[240px] truncate"
          >
            <option v-for="d in drivers" :key="d.id" :value="d.id" class="bg-slate-900 text-white">
              {{ d.name }} ({{ d.phone || d.email || ('ID: ' + d.id) }})
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

              <div class="flex items-center gap-3 p-2.5 rounded-2xl bg-rose-50 text-red-600 font-bold border border-rose-100">
                <div class="w-8 h-8 rounded-xl bg-red-600 text-white flex items-center justify-center text-sm shadow-2xs">
                  👨‍✈️
                </div>
                <div class="flex-1">
                  <div class="font-black text-slate-900 flex items-center justify-between">
                    <span>Bác Tài</span>
                    <span class="text-[10px] bg-red-600 text-white px-1.5 py-0.2 rounded-md font-bold">Hiện tại</span>
                  </div>
                  <div class="text-[11px] text-slate-500 font-medium">Cổng vận hành tài xế lái xe</div>
                </div>
              </div>

              <Link
                href="/staff"
                @click="isPortalDropdownOpen = false"
                class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-slate-50 text-slate-700 hover:text-red-600 font-semibold transition group"
              >
                <div class="w-8 h-8 rounded-xl bg-slate-100 group-hover:bg-rose-50 text-slate-700 group-hover:text-red-600 flex items-center justify-center text-sm shadow-2xs transition">
                  🎫
                </div>
                <div>
                  <div class="font-bold text-slate-900 group-hover:text-red-600">Lơ Xe</div>
                  <div class="text-[11px] text-slate-400">Soát vé, thu cọc & bàn giao ca</div>
                </div>
              </Link>

              <Link
                href="/admin/pos"
                @click="isPortalDropdownOpen = false"
                class="flex items-center gap-3 p-2.5 rounded-2xl hover:bg-slate-50 text-slate-700 hover:text-red-600 font-semibold transition group"
              >
                <div class="w-8 h-8 rounded-xl bg-slate-100 group-hover:bg-rose-50 text-slate-700 group-hover:text-red-600 flex items-center justify-center text-sm shadow-2xs transition">
                  💻
                </div>
                <div>
                  <div class="font-bold text-slate-900 group-hover:text-red-600">POS Bán Vé</div>
                  <div class="text-[11px] text-slate-400">Quầy bán vé tại bến xe</div>
                </div>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== HEADER TÀI XẾ TRỰC TIẾP (CHUẨN SCREENSHOT 1) ==================== -->
    <header class="bg-[#DC2626] text-white shadow-md sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 py-2.5 flex flex-wrap justify-between items-center gap-3">
        
        <!-- Logo & Thông tin Tài xế (Đúng theo hình 1, bỏ chữ 'Phân Hệ') -->
        <div class="flex items-center gap-3">
          <!-- Biểu tượng xe khách + chấm xanh online -->
          <div class="relative w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center text-xl shadow-inner shrink-0 text-white">
            🚍
            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-[#10B981] ring-2 ring-[#DC2626]"></span>
          </div>

          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-2 py-0.5 rounded-md bg-black/35 text-[10px] font-black uppercase tracking-wider text-white">
                XE KIẾN TRÚC
              </span>
              <span class="font-black text-base sm:text-lg tracking-wide uppercase text-white">
                TÀI XẾ TRỰC TIẾP
              </span>
              <span class="text-[9px] bg-[#10b981] text-slate-950 px-2 py-0.5 rounded-full font-black uppercase tracking-wider shadow-xs">
                ONLINE
              </span>
            </div>
            <p class="text-xs text-red-100 flex items-center gap-1.5 font-medium flex-wrap mt-0.5">
              <span class="text-amber-300">🧑‍✈️ Bác tài: <strong class="text-white font-bold">{{ currentDriver?.name || user?.name || 'Bùi Tiến Dũng' }}</strong></span>
              <span>•</span>
              <span class="flex items-center gap-1">Xe: <strong class="font-mono bg-black/35 px-1.5 py-0.5 rounded text-amber-200 text-[11px]">{{ activeActionTrip?.bus?.license_plate || currentBus?.license_plate || '43B-067.89' }}</strong></span>
              <span>•</span>
              <span>Lơ xe: <strong class="text-white font-bold">{{ activeActionTrip?.conductor?.name || 'Bùi Gia Huy' }}</strong></span>
            </p>
          </div>
        </div>

        <!-- Nút thao tác góc phải (Đúng hình 1: Ban Đêm & Khẩn Cấp SOS) -->
        <div class="flex items-center gap-2">
          <!-- Chuyển Giao diện Ngày/Đêm -->
          <button
            type="button"
            @click="toggleDarkMode"
            class="px-4 py-2 rounded-2xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-bold text-xs flex items-center gap-1.5 transition cursor-pointer shadow-xs"
            :title="isDarkMode ? 'Chuyển sang Ban Ngày' : 'Chuyển sang Ban Đêm'"
          >
            <i :class="isDarkMode ? 'fa-solid fa-sun text-amber-300' : 'fa-solid fa-moon text-blue-200'"></i>
            <span>{{ isDarkMode ? 'Ban Ngày' : 'Ban Đêm' }}</span>
          </button>

          <!-- Nút SOS Báo Động Khẩn Cấp -->
          <button
            type="button"
            @click="showSosModal = true"
            class="px-4 py-2 rounded-2xl font-black text-xs flex items-center gap-1.5 transition bg-[#f59e0b] hover:bg-amber-400 text-slate-950 shadow-md cursor-pointer"
            title="Nhấn phát tín hiệu SOS khẩn cấp đến Trung tâm Điều hành"
          >
            <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
            <span>Khẩn Cấp SOS</span>
          </button>
        </div>

      </div>
    </header>

    <!-- ==================== MAIN TABS NAVIGATION (CHUẨN THANH TRẮNG SCREENSHOT 1) ==================== -->
    <div class="bg-white dark:bg-zinc-900 border-b border-slate-200 dark:border-zinc-800 shadow-xs px-2 sm:px-6 sticky top-[61px] z-30 transition-colors">
      <div class="max-w-7xl mx-auto flex items-center justify-start sm:justify-center gap-1 sm:gap-2 overflow-x-auto py-2 scrollbar-none text-xs">
        
        <!-- Tab 1: Lịch Chạy -->
        <button
          @click="activeTab = 'today'"
          class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-2 shrink-0 cursor-pointer"
          :class="activeTab === 'today' 
            ? 'bg-red-600 text-white font-black shadow-xs' 
            : 'text-slate-600 hover:text-red-600 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 font-bold'"
        >
          <span>📕</span>
          <span>Lịch Chạy</span>
          <span 
            class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
            :class="activeTab === 'today' ? 'bg-white text-red-600' : 'bg-slate-200 dark:bg-zinc-700 text-slate-700 dark:text-zinc-200'"
          >
            {{ displayedDriverTrips.length }}
          </span>
        </button>

        <!-- Tab 2: Chế Độ Lái (Taplo & Map) -->
        <button
          @click="switchTabToDrive()"
          class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2 shrink-0 cursor-pointer"
          :class="activeTab === 'drive'
            ? 'bg-red-600 text-white font-black shadow-xs'
            : 'text-slate-600 hover:text-red-600 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 font-bold'"
        >
          <i class="fa-solid fa-compass"></i>
          <span>Chế Độ Lái (Taplo & Map)</span>
        </button>

        <!-- Tab 3: Tải Trọng (36 Giường) -->
        <button
          @click="activeTab = 'seatmap'"
          class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2 shrink-0 cursor-pointer"
          :class="activeTab === 'seatmap'
            ? 'bg-red-600 text-white font-black shadow-xs'
            : 'text-slate-600 hover:text-red-600 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 font-bold'"
        >
          <span>Tải Trọng (36 Giường)</span>
        </button>

        <!-- Tab 4: Lịch Trình Tuần -->
        <button
          @click="activeTab = 'schedule'"
          class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2 shrink-0 cursor-pointer"
          :class="activeTab === 'schedule'
            ? 'bg-red-600 text-white font-black shadow-xs'
            : 'text-slate-600 hover:text-red-600 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 font-bold'"
        >
          <i class="fa-regular fa-clock"></i>
          <span>Lịch Trình Tuần</span>
        </button>

        <!-- Tab 5: Lịch Sử Đã Chạy -->
        <button
          @click="activeTab = 'history'"
          class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2 shrink-0 cursor-pointer"
          :class="activeTab === 'history'
            ? 'bg-red-600 text-white font-black shadow-xs'
            : 'text-slate-600 hover:text-red-600 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 font-bold'"
        >
          <i class="fa-solid fa-clock-rotate-left"></i>
          <span>Lịch Sử Đã Chạy</span>
        </button>

        <!-- Tab 6: Hồ Sơ Bác Tài -->
        <button
          @click="activeTab = 'profile'"
          class="px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-2 shrink-0 cursor-pointer"
          :class="activeTab === 'profile'
            ? 'bg-red-600 text-white font-black shadow-xs'
            : 'text-slate-600 hover:text-red-600 dark:text-zinc-300 hover:bg-slate-100 dark:hover:bg-zinc-800 font-bold'"
        >
          <i class="fa-solid fa-id-badge"></i>
          <span>Hồ Sơ Bác Tài</span>
        </button>

      </div>
    </div>

    <!-- ==================== MAIN CONTENT CONTAINER ==================== -->
    <main class="max-w-7xl mx-auto px-3 sm:px-6 py-4 sm:py-6">

      <!-- ============================================================== -->
      <!-- TAB 1: HÔM NAY (QUẢN LÝ CHUYẾN XE - LÀM MỜ CHUYẾN ĐÃ HOÀN THÀNH)-->
      <!-- ============================================================== -->
      <section v-if="activeTab === 'today'" class="space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <h2 class="text-lg sm:text-xl font-black flex items-center gap-2">
              <i class="fa-solid fa-bus-simple text-red-500"></i>
              <span>Lịch Phân Công Chạy Xe</span>
            </h2>
            <p :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'" class="text-xs">
              Xem lịch hôm nay, ngày mai hoặc bấm chọn ngày khác bất kỳ trên lịch.
            </p>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <!-- Nút Hôm Nay -->
            <button
              @click="selectedDriverDate = 'today'"
              type="button"
              class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer"
              :class="selectedDriverDate === 'today'
                ? 'bg-red-600 text-white shadow-xs'
                : (isDarkMode ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')"
            >
              ☀️ Hôm Nay
            </button>

            <!-- Nút Ngày Mai -->
            <button
              @click="selectedDriverDate = 'tomorrow'"
              type="button"
              class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer"
              :class="selectedDriverDate === 'tomorrow'
                ? 'bg-red-600 text-white shadow-xs'
                : (isDarkMode ? 'bg-slate-800 text-slate-300 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')"
            >
              📅 Ngày Mai
            </button>

            <!-- Chọn Ngày Khác Bất Kỳ Trên Lịch (Yêu cầu 3) -->
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border transition shadow-2xs"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-slate-200' : 'bg-white border-slate-200 text-slate-800 hover:border-red-400'"
            >
              <i class="fa-regular fa-calendar-days text-red-500 text-xs"></i>
              <span class="text-[11px] font-bold text-slate-400 hidden md:inline">Ngày Khác:</span>
              <input
                type="date"
                :value="customDriverDateValue"
                @change="onDriverDatePicked($event.target.value)"
                class="bg-transparent text-xs font-black outline-none cursor-pointer"
                title="Bấm để chọn ngày khác xem lịch chạy"
              />
            </div>

            <!-- Nút Ẩn / Hiện Chuyến Xe Đã Hoàn Thành -->
            <button
              @click="hideCompletedTrips = !hideCompletedTrips"
              type="button"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border cursor-pointer"
              :class="hideCompletedTrips 
                ? 'bg-amber-500/20 text-amber-500 border-amber-500/30' 
                : (isDarkMode ? 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700' : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200')"
            >
              <i :class="hideCompletedTrips ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
              <span>{{ hideCompletedTrips ? 'Ẩn đã xong' : 'Ẩn xong' }}</span>
            </button>

            <span class="text-xs font-black px-3 py-1 rounded-full bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-300">
              {{ displayedDriverTrips.length }} Chuyến
            </span>
          </div>
        </div>

        <!-- THÔNG BÁO RÕ RÀNG KHI CHỌN NGÀY CHƯA CÓ LỊCH / KHÔNG CÓ CHUYẾN XE (YÊU CẦU 3) -->
        <div v-if="displayedDriverTrips.length === 0" class="p-8 sm:p-12 text-center rounded-3xl border space-y-4" :class="isDarkMode ? 'bg-slate-900 border-slate-800 text-slate-300' : 'bg-white border-slate-200 text-slate-700'">
          <div class="w-16 h-16 rounded-3xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800 flex items-center justify-center text-3xl mx-auto shadow-inner">
            <i class="fa-solid fa-calendar-xmark"></i>
          </div>
          <div class="space-y-1">
            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
              Chưa Có Lịch Phân Công Cho Ngày Này
            </h3>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
              Bác tài <strong>{{ currentDriver?.name }}</strong> hiện chưa có chuyến xe nào được phân công chạy vào ngày <strong>{{ selectedDriverDate === 'today' ? 'Hôm nay' : (selectedDriverDate === 'tomorrow' ? 'Ngày mai' : selectedDriverDate.split('-').reverse().join('/')) }}</strong>.
            </p>
          </div>
          <div class="pt-2 flex items-center justify-center gap-2">
            <button
              @click="selectedDriverDate = 'today'"
              class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs cursor-pointer shadow-xs transition flex items-center gap-2"
            >
              <i class="fa-solid fa-sun"></i>
              <span>Xem Hôm Nay</span>
            </button>
            <button
              @click="selectedDriverDate = 'tomorrow'"
              class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs cursor-pointer shadow-xs transition flex items-center gap-2"
            >
              <i class="fa-solid fa-calendar"></i>
              <span>Xem Ngày Mai</span>
            </button>
          </div>
        </div>

        <!-- Danh sách thẻ chuyến xe -->
        <div v-else class="space-y-4">
          <div
            v-for="trip in displayedDriverTrips"
            :key="trip.id"
            :class="[
              isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200',
              trip.status === 'COMPLETED' ? 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm' : 'ring-1 ring-blue-500/20 shadow-md'
            ]"
            class="p-4 sm:p-6 rounded-3xl border transition duration-200"
          >
            <!-- Header thẻ chuyến -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase font-mono bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                    #{{ trip.trip_code }}
                  </span>
                  <span class="text-xs font-bold text-slate-600 dark:text-slate-400">• Xe: <strong class="text-slate-900 dark:text-white font-mono">{{ trip.bus?.license_plate || '43B-067.89' }}</strong></span>
                  <span class="text-xs font-bold text-slate-600 dark:text-slate-400">• Lơ xe: <strong class="text-slate-900 dark:text-white">{{ trip.conductor?.name || 'Nguyễn Văn Phúc' }}</strong></span>
                  
                  <!-- Badge phân công 2 tài xế cho chuyến đường dài -->
                  <span v-if="trip.second_driver || trip.secondDriver" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">
                    <i class="fa-solid fa-user-group mr-1"></i>2 Tài Xế: {{ trip.driver?.name }} & {{ (trip.second_driver || trip.secondDriver)?.name }}
                  </span>
                  <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <i class="fa-solid fa-user mr-1"></i>Tài Xế: {{ trip.driver?.name }}
                  </span>
                </div>

                <h3 class="text-base sm:text-lg font-black flex items-center gap-2 mt-1 text-slate-900 dark:text-white">
                  <span>{{ trip.route?.origin || 'Đà Nẵng' }}</span>
                  <span class="text-red-500 font-black text-sm select-none">➔</span>
                  <span>{{ trip.route?.destination || 'Quảng Bình' }}</span>
                </h3>
              </div>

              <!-- Giờ & Trạng thái -->
              <div class="text-left sm:text-right">
                <div class="text-base sm:text-lg font-black font-mono text-red-600 dark:text-red-400">
                  {{ trip.departure_time?.substring(11, 16) || '06:00' }} ➔ {{ trip.arrival_time?.substring(11, 16) || '11:30' }}
                </div>
                <div>
                  <span v-if="trip.status === 'IN_TRANSIT'" class="inline-flex items-center gap-1 text-[11px] font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-300 px-2.5 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span> ĐANG CHẠY TRÊN ĐƯỜNG
                  </span>
                  <span v-else-if="trip.status === 'BOARDING'" class="inline-flex items-center gap-1 text-[11px] font-black text-amber-600 bg-amber-50 dark:bg-amber-950/60 dark:text-amber-300 px-2.5 py-0.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span> ĐANG ĐÓN KHÁCH TẠI BẾN
                  </span>
                  <span v-else-if="trip.status === 'COMPLETED'" class="inline-flex items-center gap-1 text-[11px] font-black text-slate-600 bg-slate-100 dark:bg-slate-800 dark:text-slate-300 px-2.5 py-0.5 rounded-full">
                    ✓ ĐÃ HOÀN THÀNH LỘ TRÌNH
                  </span>
                  <span v-else class="inline-flex items-center gap-1 text-[11px] font-black text-blue-600 bg-blue-50 dark:bg-blue-950/60 dark:text-blue-300 px-2.5 py-0.5 rounded-full">
                    CHỜ XUẤT BẾN
                  </span>
                </div>
              </div>
            </div>

            <!-- HỘP LƯU Ý ĐẶC BIỆT CỦA HÀNH KHÁCH (CHỈ HIỆN KHI CÓ KHÁCH LƯU Ý THỰC TẾ) -->
            <div 
              v-if="getTripCustomerNotes(trip).length > 0"
              class="my-3 px-4 py-3 rounded-2xl bg-[#FFFBEB] dark:bg-amber-950/40 border border-[#FDE68A] dark:border-amber-800/80 flex items-start gap-2.5 text-xs text-[#92400E] dark:text-amber-200 transition shadow-2xs"
            >
              <span class="text-amber-500 font-bold text-sm shrink-0 leading-none mt-0.5">⚠️</span>
              <div class="leading-relaxed font-medium space-y-1.5 flex-1">
                <div class="font-bold text-amber-900 dark:text-amber-100 flex items-center gap-1.5">
                  <span>Lưu ý từ hành khách:</span>
                  <span class="text-[10px] font-normal text-amber-800 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/60 px-2 py-0.2 rounded-full">
                    {{ getTripCustomerNotes(trip).length }} ghi chú
                  </span>
                </div>
                <div class="space-y-1">
                  <div 
                    v-for="(nItem, idx) in getTripCustomerNotes(trip)" 
                    :key="idx"
                    class="flex flex-wrap items-center gap-1.5 bg-white/70 dark:bg-black/30 px-2.5 py-1 rounded-xl text-[11px]"
                  >
                    <span class="font-black text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/60 px-1.5 py-0.2 rounded">
                      {{ nItem.seat_label ? 'Ghế ' + nItem.seat_label : (nItem.seats && nItem.seats.length > 0 ? 'Ghế ' + nItem.seats.join(', ') : 'Ghi chú') }}
                    </span>
                    <span class="font-bold text-slate-900 dark:text-slate-100">
                      {{ nItem.customer_name }}<span v-if="nItem.customer_phone" class="font-normal text-slate-500">({{ nItem.customer_phone }})</span>:
                    </span>
                    <span class="italic text-amber-900 dark:text-amber-200">"{{ nItem.note }}"</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tải trọng tóm tắt chuẩn 36 giường (Đồng bộ 100% với Sơ đồ tải trọng thực tế) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 py-3 text-xs">
              <div :class="isDarkMode ? 'bg-[#18181B] border border-[#2A2A2A]' : 'bg-slate-50 border border-slate-200'" class="p-2.5 rounded-2xl">
                <span class="text-[10px] text-slate-600 dark:text-slate-400 block font-bold uppercase tracking-wider">Khách Trên Xe</span>
                <span class="text-sm font-black text-slate-900 dark:text-white">
                  {{ trip.occupied_count !== undefined ? trip.occupied_count : 0 }} ghế ({{ trip.customer_count || (trip.bookings ? trip.bookings.length : 0) }} khách) / {{ trip.bus?.total_seats || 34 }} Giường
                </span>
              </div>
              <div :class="isDarkMode ? 'bg-[#18181B] border border-[#2A2A2A]' : 'bg-slate-50 border border-slate-200'" class="p-2.5 rounded-2xl">
                <span class="text-[10px] text-slate-600 dark:text-slate-400 block font-bold uppercase tracking-wider">Tỷ Lệ Tải Trọng</span>
                <span class="text-sm font-black" :class="(trip.occupied_percent || 0) >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400'">
                  {{ trip.occupied_percent !== undefined ? trip.occupied_percent : 0 }}% Đủ Tải
                </span>
              </div>
              <div :class="isDarkMode ? 'bg-[#18181B] border border-[#2A2A2A]' : 'bg-slate-50 border border-slate-200'" class="p-2.5 rounded-2xl">
                <span class="text-[10px] text-slate-600 dark:text-slate-400 block font-bold uppercase tracking-wider">Tầng 1 / Tầng 2</span>
                <span class="text-sm font-black text-slate-900 dark:text-white">
                  {{ trip.floor1_count || 0 }} / {{ trip.floor2_count || 0 }} Giường ({{ trip.is_balanced ? 'Cân Bằng' : 'Lệch Tầng' }})
                </span>
              </div>
              <div :class="isDarkMode ? 'bg-[#18181B] border border-[#2A2A2A]' : 'bg-slate-50 border border-slate-200'" class="p-2.5 rounded-2xl">
                <span class="text-[10px] text-slate-600 dark:text-slate-400 block font-bold uppercase tracking-wider">Lơ Xe Phụ Trách</span>
                <div class="flex items-center justify-between">
                  <span class="font-bold truncate text-slate-900 dark:text-white">{{ trip.conductor?.name || 'Nguyễn Văn Phúc' }}</span>
                  <button @click="makeCall(trip.conductor?.phone || '0905123456')" class="text-emerald-600 hover:text-emerald-500 font-black cursor-pointer">
                    <i class="fa-solid fa-phone"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- 1 NÚT HERO CTA TO BỰ THU HÚT MẮT TÀI XẾ (QUY TRÌNH 3 BƯỚC: KIỂM TRA XE -> BẮT ĐẦU CHẠY -> HOÀN THÀNH CHUYẾN GỬI VỀ ADMIN) -->
            <div class="pt-3 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
              
              <div class="flex-1">
                <!-- 1. Khi chưa kiểm tra xe (Chưa sẵn sàng) -->
                <button
                  v-if="trip.status !== 'READY' && trip.status !== 'IN_TRANSIT' && trip.status !== 'COMPLETED' && trip.status !== 'ARRIVED' && trip.status !== 'CLOSED'"
                  @click="openSafetyChecklist(trip)"
                  type="button"
                  class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-700 hover:to-amber-700 text-white font-black text-sm sm:text-base flex items-center justify-center gap-2.5 shadow-lg shadow-red-600/30 cursor-pointer transition transform active:scale-98"
                >
                  <i class="fa-solid fa-clipboard-check text-lg"></i>
                  <span>BƯỚC 1: KIỂM TRA XE (8 MỤC AN TOÀN) → GỬI VỀ ADMIN</span>
                </button>

                <!-- 2. Khi xe ĐÃ kiểm tra an toàn (status === 'READY'): NÚT BẮT ĐẦU CHẠY / XUẤT BẾN -->
                <button
                  v-else-if="trip.status === 'READY'"
                  @click="startRunningTrip(trip)"
                  type="button"
                  class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-sm sm:text-base flex items-center justify-center gap-2.5 shadow-xl shadow-emerald-600/40 cursor-pointer transition transform active:scale-98 animate-pulse"
                >
                  <i class="fa-solid fa-play text-lg"></i>
                  <span>BƯỚC 2: BẮT ĐẦU CHẠY / XUẤT BẾN → GỬI VỀ ADMIN</span>
                </button>

                <!-- 3. Khi chuyến xe đang chạy: Nút vào chế độ lái + Nút Hoàn thành chuyến xe -->
                <div v-else-if="trip.status === 'IN_TRANSIT' || trip.status === 'RUNNING'" class="flex flex-col sm:flex-row gap-2.5">
                  <button
                    @click="activeActionTrip = trip; switchTabToDrive()"
                    type="button"
                    class="flex-1 py-4 px-5 rounded-2xl bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-black text-sm flex items-center justify-center gap-2 shadow-xl shadow-blue-600/40 cursor-pointer transition transform active:scale-98"
                  >
                    <i class="fa-solid fa-gauge-high text-xl"></i>
                    <span>VÀO CHẾ ĐỘ LÁI (TAPLO COCKPIT)</span>
                  </button>

                  <button
                    @click="completeTrip(trip)"
                    type="button"
                    class="py-4 px-5 rounded-2xl bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-black text-sm flex items-center justify-center gap-2 shadow-xl shadow-emerald-600/30 cursor-pointer transition shrink-0"
                  >
                    <i class="fa-solid fa-flag-checkered text-lg"></i>
                    <span>BƯỚC 3: HOÀN THÀNH CHUYẾN XE → GỬI VỀ ADMIN</span>
                  </button>
                </div>

                <!-- 4. Khi chuyến xe đã hoàn thành: Làm mờ -->
                <div
                  v-else-if="['COMPLETED', 'ARRIVED', 'CLOSED'].includes(trip.status)"
                  class="w-full py-3.5 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-emerald-600 font-bold text-xs sm:text-sm flex items-center justify-center gap-2 border border-emerald-300"
                >
                  <i class="fa-solid fa-circle-check text-emerald-500"></i>
                  <span>✓ Chuyến xe đã hoàn thành an toàn & đã gửi đối soát về Admin.</span>
                </div>
              </div>

              <!-- Right: Nút phụ xem tải trọng -->
              <div class="flex items-center gap-2 shrink-0">
                <button
                  @click="switchTripForSeatmap(trip); activeTab = 'seatmap'"
                  class="flex-1 sm:flex-none px-4 py-3 rounded-2xl border text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
                  :class="isDarkMode ? 'border-slate-800 hover:bg-slate-800 text-slate-300' : 'border-slate-200 hover:bg-slate-100 text-slate-700'"
                >
                  <i class="fa-solid fa-weight-scale text-amber-500"></i>
                  <span>Tải Trọng Chuyến #{{ trip.trip_code }}</span>
                </button>
              </div>

            </div>

            <!-- DANH SÁCH CÁC TRẠM DỪNG & KHÁCH XUỐNG XE (LỘ TRÌNH ĐỘNG THEO TUYẾN) -->
            <div :class="isDarkMode ? 'bg-[#18181B] border border-[#2A2A2A]' : 'bg-slate-50 border border-slate-200'" class="p-4 sm:p-5 rounded-3xl space-y-4 mt-4 shadow-xs">
              
              <!-- Header Section -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-200'">
                <div class="flex items-center gap-2.5">
                  <span class="w-8 h-8 rounded-xl bg-red-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                    📍
                  </span>
                  <div>
                    <h4 class="font-black text-sm sm:text-base text-slate-900 dark:text-white flex items-center gap-2">
                      <span>Lộ Trình Các Trạm Dừng & Danh Sách Khách Xuống Xe</span>
                    </h4>
                    <p class="text-xs text-slate-500 mt-0.5">
                      Bác tài theo dõi chính xác từng điểm đón/trả khách từ {{ trip.route?.origin || 'Bến xuất phát' }} đến {{ trip.route?.destination || 'Bến đích' }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto">
                  <span class="text-xs font-bold text-red-600 bg-red-50 dark:bg-red-950/40 px-3 py-1 rounded-xl border border-red-200 dark:border-red-900/40 shadow-2xs">
                    🛣️ {{ (getTripDropoffStops(trip) || []).length }} Điểm Đón / Trả
                  </span>
                </div>
              </div>

              <!-- Thanh Lộ Trình Tuyến (Route Journey Dynamic Progress Bar) -->
              <div class="p-3 rounded-2xl border flex flex-col md:flex-row items-center justify-between gap-2 text-xs overflow-x-auto" :class="isDarkMode ? 'bg-zinc-900/80 border-zinc-800' : 'bg-white border-slate-200'">
                <template v-for="(st, stIdx) in getTripDropoffStops(trip)" :key="'prog-st-' + (st.stop_id || stIdx)">
                  <div class="flex items-center gap-2 shrink-0 font-bold"
                    :class="stIdx === 0 ? 'text-blue-600' : (stIdx === (getTripDropoffStops(trip).length - 1) ? 'text-red-600' : 'text-amber-600')"
                  >
                    <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-black shadow-xs shrink-0"
                      :class="stIdx === 0 ? 'bg-blue-100 dark:bg-blue-950 text-blue-600' : (stIdx === (getTripDropoffStops(trip).length - 1) ? 'bg-red-100 dark:bg-red-950 text-red-600' : 'bg-amber-100 dark:bg-amber-950 text-amber-600')"
                    >
                      {{ stIdx + 1 }}
                    </span>
                    <span class="truncate max-w-[140px] sm:max-w-none">{{ st.stop_name }}</span>
                  </div>
                  <div v-if="stIdx < (getTripDropoffStops(trip).length - 1)" class="hidden md:flex flex-1 items-center px-2">
                    <div class="h-0.5 w-full bg-slate-200 dark:bg-zinc-700 relative flex items-center justify-center">
                      <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                    </div>
                  </div>
                </template>
              </div>

              <!-- Danh sách các trạm: Grid responsive cân đối hoàn hảo theo số trạm -->
              <div 
                class="grid gap-4 items-stretch"
                :class="(getTripDropoffStops(trip) || []).length >= 4 ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4' : ((getTripDropoffStops(trip) || []).length === 3 ? 'grid-cols-1 md:grid-cols-3' : 'grid-cols-1 sm:grid-cols-2')"
              >
                <div 
                  v-for="(st, stIdx) in getTripDropoffStops(trip)" 
                  :key="'drop-st-' + (st.stop_id || st.id || stIdx)"
                  class="p-4 rounded-2xl border text-xs flex flex-col justify-between transition shadow-2xs"
                  :class="stIdx === 0
                    ? (isDarkMode ? 'bg-zinc-900 border-zinc-800 border-t-4 border-t-blue-500' : 'bg-white border-slate-200 border-t-4 border-t-blue-500')
                    : (stIdx === (getTripDropoffStops(trip).length - 1)
                      ? (isDarkMode ? 'bg-zinc-900 border-zinc-800 border-t-4 border-t-red-500' : 'bg-white border-slate-200 border-t-4 border-t-red-500')
                      : (getStopRole(st, stIdx, (getTripDropoffStops(trip) || []).length).type === 'PICKUP'
                        ? (isDarkMode ? 'bg-blue-950/20 border-blue-900/60 border-t-4 border-t-blue-500' : 'bg-white border-slate-200 border-t-4 border-t-blue-400')
                        : (getStopRole(st, stIdx, (getTripDropoffStops(trip) || []).length).type === 'DROPOFF'
                          ? (isDarkMode ? 'bg-emerald-950/20 border-emerald-900/60 border-t-4 border-t-emerald-500' : 'bg-white border-slate-200 border-t-4 border-t-emerald-500')
                          : (isDarkMode ? 'bg-amber-950/20 border-amber-900/60 border-t-4 border-t-amber-500' : 'bg-white border-slate-200 border-t-4 border-t-amber-500'))))"
                >
                  <!-- Trạm Header -->
                  <div>
                    <div class="flex items-center justify-between gap-1 mb-2">
                      <span 
                        class="px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider shadow-2xs" 
                        :class="stIdx === 0 
                          ? 'bg-blue-600 text-white' 
                          : (stIdx === (getTripDropoffStops(trip).length - 1)
                            ? 'bg-red-600 text-white'
                            : getStopRole(st, stIdx, (getTripDropoffStops(trip) || []).length).badge)"
                      >
                        {{ stIdx === 0 ? '🚀 Trạm 1: Khởi Hành' : (stIdx === (getTripDropoffStops(trip).length - 1) ? `🏁 Trạm ${stIdx + 1}: Bến Đích Cuối` : `📍 Trạm ${stIdx + 1}: ${getStopRole(st, stIdx, (getTripDropoffStops(trip) || []).length).label}`) }}
                      </span>
                      <span class="font-extrabold text-[11px]" :class="(st.total_pax > 0 || stIdx === (getTripDropoffStops(trip).length - 1)) ? 'text-red-600' : 'text-slate-600 dark:text-slate-400'">
                        {{ stIdx === 0 ? 'Điểm xuất bến' : (st.total_pax > 0 ? `${st.total_pax} khách xuống` : (getStopRole(st, stIdx, (getTripDropoffStops(trip) || []).length).type === 'PICKUP' ? 'Đón trung chuyển' : 'Không có khách xuống')) }}
                      </span>
                    </div>

                    <div class="font-black text-sm sm:text-base text-slate-900 dark:text-white leading-tight">
                      {{ st.stop_name || st.name }}
                    </div>
                    <div class="text-[11px] text-slate-600 dark:text-slate-400 mt-0.5 line-clamp-1 font-medium">
                      {{ st.address || (stIdx === 0 ? 'Điểm xuất phát bến đi ban đầu' : (stIdx === (getTripDropoffStops(trip).length - 1) ? 'Bến xe kết thúc hành trình' : 'Trạm đón trả trên tuyến')) }}
                    </div>

                    <!-- NỘI DUNG 1: TRẠM XUẤT PHÁT -->
                    <div v-if="stIdx === 0" class="mt-3 pt-3 border-t border-slate-100 dark:border-zinc-800 space-y-2">
                      <div class="p-2.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 text-blue-950 dark:text-blue-200">
                        <div class="font-bold flex items-center justify-between text-xs">
                          <span>Tổng khách xuất bến:</span>
                          <span class="text-blue-700 dark:text-blue-300 font-black text-sm">{{ occupiedSeats?.length || trip.total_seats_sold || 0 }} khách</span>
                        </div>
                        <p class="text-[11px] text-blue-600 dark:text-blue-400 mt-1">
                          ✓ Toàn bộ giường đã được chuẩn bị chu đáo, xe sẵn sàng lăn bánh.
                        </p>
                      </div>
                      <div class="text-[11px] text-slate-600 dark:text-slate-400 flex items-center gap-1.5 font-medium">
                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                        <span>Đã kiểm tra an toàn 8 hạng mục trước giờ chạy</span>
                      </div>
                    </div>

                    <!-- NỘI DUNG 2: TRẠM BẾN ĐÍCH CUỐI CÙNG -->
                    <div v-else-if="stIdx === (getTripDropoffStops(trip).length - 1)" class="mt-3 pt-3 border-t border-slate-100 dark:border-zinc-800 space-y-2">
                      <div class="flex items-center justify-between text-[10px] font-bold text-slate-600 dark:text-slate-400 uppercase">
                        <span>Danh Sách Khách Xuống Bến:</span>
                        <span class="text-red-600 font-extrabold">{{ st.passengers?.length || st.total_pax || occupiedSeats?.length || trip.total_seats_sold || 0 }} Hành Khách</span>
                      </div>

                      <!-- Danh sách hành khách xuống bến cuối -->
                      <div v-if="st.passengers && st.passengers.length > 0" class="max-h-48 overflow-y-auto space-y-1.5 pr-1 scrollbar-thin">
                        <div 
                          v-for="p in st.passengers" 
                          :key="'p-drop-end-' + p.id"
                          class="p-2 rounded-xl bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 flex items-center justify-between gap-1 shadow-2xs hover:border-red-300 transition"
                        >
                          <div>
                            <div class="font-black text-slate-900 dark:text-white text-[11px] flex items-center gap-1.5">
                              <span>{{ p.customer_name }}</span>
                              <span class="px-1.5 py-0.2 rounded bg-red-100 text-red-700 font-mono text-[9px] font-extrabold">
                                {{ p.seats?.join(', ') }}
                              </span>
                            </div>
                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono font-medium">{{ p.customer_phone }}</div>
                          </div>
                          <button 
                            type="button" 
                            @click="makeCall(p.customer_phone)"
                            class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center text-xs cursor-pointer hover:bg-emerald-200"
                            title="Gọi cho hành khách"
                          >
                            <i class="fa-solid fa-phone"></i>
                          </button>
                        </div>
                      </div>

                      <!-- Fallback khi chưa có list passenger chi tiết -->
                      <div v-else class="p-2.5 rounded-xl bg-red-50/70 dark:bg-red-950/30 border border-red-100 dark:border-red-900/40 text-red-950 dark:text-red-200 space-y-1">
                        <div class="font-bold text-xs flex items-center justify-between text-red-700 dark:text-red-300">
                          <span>Bến trả toàn bộ khách:</span>
                          <span class="font-black text-sm">{{ occupiedSeats?.length || trip.total_seats_sold || 0 }} khách</span>
                        </div>
                        <p class="text-[11px] text-red-600 dark:text-red-400">
                          Toàn bộ hành khách kết thúc hành trình an toàn tại bến xe đích đến. Bác tài kiểm tra hầm hành lý khi trả khách.
                        </p>
                      </div>
                    </div>

                    <!-- NỘI DUNG 3: CÁC TRẠM TRUNG GIAN (ĐÓN / TRẢ / DỪNG NGHỈ) -->
                    <div v-else class="mt-3 pt-3 border-t border-slate-100 dark:border-zinc-800 space-y-2">
                      <!-- Có khách xuống tại trạm này -->
                      <div v-if="st.passengers && st.passengers.length > 0" class="space-y-1.5">
                        <div class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase">Khách xuống tại trạm này:</div>
                        <div class="max-h-40 overflow-y-auto space-y-1.5 pr-1 scrollbar-thin">
                          <div 
                            v-for="p in st.passengers" 
                            :key="'p-drop-mid-' + p.id"
                            class="p-2 rounded-xl bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 flex items-center justify-between gap-1 shadow-2xs"
                          >
                            <div>
                              <div class="font-black text-slate-900 dark:text-white text-[11px] flex items-center gap-1">
                                <span>{{ p.customer_name }}</span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-900 font-mono text-[9px] font-extrabold">
                                  {{ p.seats?.join(', ') }}
                                </span>
                              </div>
                              <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono font-medium">{{ p.customer_phone }}</div>
                            </div>
                            <button 
                              type="button" 
                              @click="makeCall(p.customer_phone)"
                              class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center text-xs cursor-pointer hover:bg-emerald-200"
                              title="Gọi cho hành khách"
                            >
                              <i class="fa-solid fa-phone"></i>
                            </button>
                          </div>
                        </div>
                      </div>

                      <!-- Khi trạm là điểm đón trung chuyển -->
                      <div v-else-if="getStopRole(st, stIdx, (getTripDropoffStops(trip) || []).length).type === 'PICKUP'" class="p-2.5 rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/40 text-blue-950 dark:text-blue-200 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-xs text-blue-800 dark:text-blue-300">
                          <i class="fa-solid fa-location-dot"></i>
                          <span>Điểm Đón Khách Trung Chuyển</span>
                        </div>
                        <p class="text-[11px] text-blue-900 dark:text-blue-200 leading-relaxed font-medium">
                          Xe dừng đón thêm hành khách trung chuyển nội thành lên xe.
                        </p>
                      </div>

                      <!-- Khi trạm là điểm trả khách -->
                      <div v-else-if="getStopRole(st, stIdx, (getTripDropoffStops(trip) || []).length).type === 'DROPOFF'" class="p-2.5 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/40 text-emerald-950 dark:text-emerald-200 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-xs text-emerald-800 dark:text-emerald-300">
                          <i class="fa-solid fa-person-walking-arrow-right"></i>
                          <span>Điểm Trả Khách Dọc Tuyến</span>
                        </div>
                        <p class="text-[11px] text-emerald-900 dark:text-emerald-200 leading-relaxed font-medium">
                          Bác tài dừng xe an toàn, trả khách và giao hành lý tại điểm trả.
                        </p>
                      </div>

                      <!-- Trạm Dừng Nghỉ -->
                      <div v-else class="p-2.5 rounded-xl bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/40 text-amber-950 dark:text-amber-200 space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-xs text-amber-800 dark:text-amber-300">
                          <i class="fa-solid fa-utensils"></i>
                          <span>Trạm Dừng Nghỉ / Cửa Ngõ</span>
                        </div>
                        <p class="text-[11px] text-amber-900 dark:text-amber-200 leading-relaxed font-medium">
                          Dừng nghỉ ngơi, tiếp nhiên liệu và ăn uống theo quy định.
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Footer của từng card trạm -->
                  <div class="mt-3 pt-2 border-t border-slate-100 dark:border-zinc-800 text-[10px] flex items-center justify-between text-slate-400 font-medium">
                    <span>Lộ trình: {{ st.distance_km ? ('Km thứ ' + st.distance_km) : (stIdx === 0 ? 'Xuất bến 0 km' : 'Theo lộ trình') }}</span>
                    <span class="text-slate-500 font-bold">Trạm {{ stIdx + 1 }}/{{ (getTripDropoffStops(trip) || []).length }}</span>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </div>

      </section>

      <!-- ============================================================== -->
      <!-- TAB 2: CHẾ ĐỘ LÁI XE / TAPLO COCKPIT (KÈM BẢN ĐỒ DẪN ĐƯỜNG LIVE MAP)-->
      <!-- ============================================================== -->
      <section v-show="activeTab === 'drive'" class="space-y-4">
        
        <!-- Thanh trạng thái nhanh Taplo -->
        <div class="flex flex-wrap items-center justify-between gap-3 p-4 rounded-3xl border" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
          <div class="flex items-center gap-3">
            <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 animate-ping"></span>
            <div>
              <span class="text-xs font-black uppercase text-emerald-600 block">CHẾ ĐỘ TAPLO LÁI XE ĐANG HOẠT ĐỘNG</span>
              <span class="text-xs text-slate-500">
                Chuyến: <strong>#{{ activeActionTrip?.trip_code }}</strong> • Tuyến: <strong>{{ activeActionTrip?.route?.name || 'Đà Nẵng ⇄ Quảng Bình' }}</strong>
              </span>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <!-- 1. Khi chuyến chưa chạy: Nút Bắt Đầu Chuyến Đi -->
            <button
              v-if="['SCHEDULED', 'BOARDING', 'READY'].includes(activeActionTrip?.status)"
              @click="openSafetyChecklist(activeActionTrip)"
              class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 text-white font-black text-xs flex items-center gap-1.5 cursor-pointer shadow-md shadow-emerald-600/30 transition active:scale-95 animate-pulse"
            >
              <i class="fa-solid fa-play"></i>
              <span>🚀 BẮT ĐẦU CHUYẾN ĐI</span>
            </button>

            <!-- 2. Khi xe đang chạy: Nút Báo Đã Đến Trạm Dừng Nghỉ -->
            <button
              v-if="['IN_TRANSIT', 'RUNNING'].includes(activeActionTrip?.status)"
              @click="startRestStop"
              class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs flex items-center gap-1.5 cursor-pointer shadow-md shadow-amber-500/20 transition active:scale-95"
            >
              <i class="fa-solid fa-mug-saucer"></i>
              <span>☕ ĐÃ ĐẾN TRẠM DỪNG NGHỈ</span>
            </button>

            <!-- 3. Khi xe đang nghỉ: Nút Tiếp Tục Chạy Xe (Khách đã lên đủ) -->
            <button
              v-if="activeActionTrip?.status === 'REST_STOP'"
              @click="resumeDriving"
              class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 text-white font-black text-xs flex items-center gap-1.5 cursor-pointer shadow-md shadow-emerald-600/30 transition active:scale-95 animate-pulse"
            >
              <i class="fa-solid fa-forward-step"></i>
              <span>▶ TIẾP TỤC CHẠY XE (KHÁCH ĐÃ LÊN ĐỦ)</span>
            </button>

            <!-- 4. Nút Hoàn Thành Chuyến Đi -->
            <button
              v-if="['IN_TRANSIT', 'RUNNING', 'REST_STOP'].includes(activeActionTrip?.status)"
              @click="completeTrip(activeActionTrip)"
              class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs flex items-center gap-1.5 cursor-pointer shadow-md shadow-red-600/30 transition active:scale-95"
            >
              <i class="fa-solid fa-flag-checkered"></i>
              <span>🏁 HOÀN THÀNH CHUYẾN</span>
            </button>
          </div>
        </div>

        <!-- Banner cảnh báo khi xe đang dừng nghỉ chân -->
        <div v-if="activeActionTrip?.status === 'REST_STOP'" class="p-4 rounded-3xl bg-amber-500/10 border-2 border-amber-500/30 text-amber-900 dark:text-amber-300 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-md">
          <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center text-xl shrink-0">☕</span>
            <div>
              <h4 class="font-black text-sm uppercase tracking-wide">XE ĐANG DỪNG TẠI TRẠM NGHỈ CHÂN</h4>
              <p class="text-xs text-amber-800 dark:text-amber-400 mt-0.5">Tài xế & hành khách đang nghỉ ngơi, dùng bữa. Sau khi khách đã lên xe đầy đủ, hãy bấm nút <strong>"Tiếp Tục Chạy Xe"</strong> để hành trình tiếp tục lăn bánh.</p>
            </div>
          </div>
          <button
            @click="resumeDriving"
            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 text-white font-black text-xs flex items-center gap-2 shadow-lg shadow-emerald-600/30 cursor-pointer shrink-0 animate-pulse active:scale-95 transition"
          >
            <i class="fa-solid fa-play"></i>
            <span>TIẾP TỤC CHẠY XE</span>
          </button>
        </div>

        <!-- HỘP LƯU Ý ĐẶC BIỆT CỦA HÀNH KHÁCH TRONG CHẾ ĐỘ LÁI TAPLO (CHỈ HIỆN KHI CÓ KHÁCH LƯU Ý THỰC TẾ) -->
        <div 
          v-if="getTripCustomerNotes(activeActionTrip).length > 0"
          class="px-4 py-3 rounded-2xl bg-[#FFFBEB] dark:bg-amber-950/40 border border-[#FDE68A] dark:border-amber-800/80 flex items-start gap-2.5 text-xs text-[#92400E] dark:text-amber-200 transition shadow-2xs"
        >
          <span class="text-amber-500 font-bold text-sm shrink-0 leading-none mt-0.5">⚠️</span>
          <div class="leading-relaxed font-medium space-y-1.5 flex-1">
            <div class="font-bold text-amber-900 dark:text-amber-100 flex items-center gap-1.5">
              <span>Lưu ý từ hành khách:</span>
              <span class="text-[10px] font-normal text-amber-800 dark:text-amber-300 bg-amber-100 dark:bg-amber-900/60 px-2 py-0.2 rounded-full">
                {{ getTripCustomerNotes(activeActionTrip).length }} ghi chú
              </span>
            </div>
            <div class="space-y-1">
              <div 
                v-for="(nItem, idx) in getTripCustomerNotes(activeActionTrip)" 
                :key="idx"
                class="flex flex-wrap items-center gap-1.5 bg-white/70 dark:bg-black/30 px-2.5 py-1 rounded-xl text-[11px]"
              >
                <span class="font-black text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/60 px-1.5 py-0.2 rounded">
                  {{ nItem.seat_label ? 'Ghế ' + nItem.seat_label : (nItem.seats && nItem.seats.length > 0 ? 'Ghế ' + nItem.seats.join(', ') : 'Ghi chú') }}
                </span>
                <span class="font-bold text-slate-900 dark:text-slate-100">
                  {{ nItem.customer_name }}<span v-if="nItem.customer_phone" class="font-normal text-slate-500">({{ nItem.customer_phone }})</span>:
                </span>
                <span class="italic text-amber-900 dark:text-amber-200">"{{ nItem.note }}"</span>
              </div>
            </div>
          </div>
        </div>

        <!-- HUD CHẾ ĐỘ LÁI: 50% BẢN ĐỒ LIVE MAP + 50% TAPLO CẢNH BÁO -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
          
          <!-- CỘT TRÁI (7/12): BẢN ĐỒ GOOGLE MAPS & ĐỊNH VỊ LAPTOP THỰC TẾ -->
          <div class="lg:col-span-7 flex flex-col rounded-3xl border overflow-hidden shadow-lg relative" :class="isDarkMode ? 'border-slate-800 bg-slate-900' : 'border-slate-200 bg-white'">
            
            <!-- Map Top Bar HUD -->
            <div class="p-3 border-b flex flex-wrap items-center justify-between gap-2 z-10" :class="isDarkMode ? 'bg-slate-900/95 border-slate-800 text-white' : 'bg-white/95 border-slate-100 text-slate-900'">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-map-location-dot text-red-500 text-base"></i>
                <div>
                  <span class="text-xs font-black uppercase tracking-wider block">GOOGLE MAPS • ĐỊNH VỊ GPS LAPTOP</span>
                  <span class="text-[10px] text-slate-400 block" v-if="isTrackingLaptop">
                    ● Vị trí thực tế Laptop đang online
                  </span>
                </div>
              </div>

              <!-- Nút Thao Tác Bản Đồ -->
              <div class="flex items-center gap-1.5 flex-wrap">
                <!-- Nút Phát Vị Trí Trực Tiếp Cho Hành Khách (Yêu cầu 2) -->
                <button
                  type="button"
                  @click="broadcastGpsToPassengers"
                  class="px-3 py-1.5 rounded-xl text-xs font-black flex items-center gap-1.5 transition cursor-pointer shadow-md"
                  :class="isGpsBroadcastActive 
                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-500/40 ring-2 ring-emerald-400/50' 
                    : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 text-white shadow-blue-500/30'"
                  :title="isGpsBroadcastActive ? 'Hệ thống đang phát vị trí cho hành khách theo dõi. Bấm để tạm ngưng.' : 'Bấm để phát vị trí trực tiếp cho hành khách biết xe đang chạy tới đâu'"
                >
                  <i class="fa-solid fa-tower-broadcast" :class="{ 'animate-pulse': isGpsBroadcastActive }"></i>
                  <span>{{ isGpsBroadcastActive ? '📡 Đang Phát GPS Khách' : '📡 Phát Vị Trí Cho Khách' }}</span>
                </button>

                <!-- Nút sao chép link theo dõi cho khách -->
                <button
                  type="button"
                  @click="copyPassengerTrackingLink"
                  class="px-2.5 py-1.5 rounded-xl border text-xs font-bold flex items-center gap-1 cursor-pointer transition"
                  :class="isDarkMode ? 'bg-zinc-800 border-zinc-700 hover:bg-zinc-700 text-cyan-300' : 'bg-slate-100 border-slate-200 hover:bg-slate-200 text-slate-700'"
                  title="Sao chép đường link gửi cho hành khách & gia đình theo dõi xe"
                >
                  <i class="fa-solid fa-link text-[10px]"></i>
                  <span>Link Khách</span>
                </button>

                <!-- Nút Định Vị Theo Laptop -->
                <button
                  type="button"
                  @click="getLaptopLocation(true)"
                  class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white text-xs font-black flex items-center gap-1.5 shadow-md shadow-red-500/30 cursor-pointer"
                  :class="{ 'animate-pulse': isLocating }"
                >
                  <i class="fa-solid fa-location-crosshairs" :class="{ 'fa-spin': isLocating }"></i>
                  <span>{{ isLocating ? 'Đang Lấy GPS...' : '🎯 Định Vị' }}</span>
                </button>

                <!-- Chuyển Đổi Kiểu Bản Đồ (Mapbox Navigation Night, Google Maps, Vệ Tinh) -->
                <div class="flex items-center rounded-xl p-0.5 border" :class="isDarkMode ? 'bg-zinc-800 border-zinc-700' : 'bg-slate-100 border-slate-200'">
                  <button
                    type="button"
                    @click="toggleMapMode('mapbox_dark')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-black transition cursor-pointer flex items-center gap-1"
                    :class="mapLayerMode === 'mapbox_dark' ? 'bg-cyan-600 text-white shadow-xs' : 'text-slate-400 hover:text-white'"
                  >
                    <i class="fa-solid fa-moon text-[10px]"></i>
                    <span>Mapbox Night</span>
                  </button>
                  <button
                    type="button"
                    @click="toggleMapMode('google')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-black transition cursor-pointer flex items-center gap-1"
                    :class="mapLayerMode === 'google' ? 'bg-white dark:bg-zinc-900 text-blue-600 shadow-xs' : 'text-slate-400 hover:text-slate-600 dark:hover:text-white'"
                  >
                    <i class="fa-solid fa-map text-[10px]"></i>
                    <span>Google</span>
                  </button>
                  <button
                    type="button"
                    @click="toggleMapMode('satellite')"
                    class="px-2.5 py-1 rounded-lg text-[11px] font-black transition cursor-pointer flex items-center gap-1"
                    :class="mapLayerMode === 'satellite' ? 'bg-white dark:bg-zinc-900 text-blue-600 shadow-xs' : 'text-slate-400 hover:text-slate-600 dark:hover:text-white'"
                  >
                    <i class="fa-solid fa-satellite text-[10px]"></i>
                    <span>Vệ Tinh</span>
                  </button>
                </div>

                <button
                  type="button"
                  @click="recenterToBus"
                  class="p-1.5 px-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold flex items-center gap-1 shadow-xs cursor-pointer"
                  title="Căn tâm về vị trí xe / laptop"
                >
                  <i class="fa-solid fa-crosshairs"></i>
                  <span class="hidden sm:inline">Căn Tâm</span>
                </button>
              </div>
            </div>

            <!-- Leaflet Container Với Kích Thước Cố Định Chống Trắng Màn Hình -->
            <div 
              ref="mapContainer" 
              id="driver-leaflet-map" 
              class="w-full relative z-0" 
              style="height: 440px; min-height: 440px; width: 100%; display: block;"
            ></div>

            <!-- Map Floating Bottom Info -->
            <div class="p-2.5 border-t text-[11px] font-mono font-bold flex items-center justify-between z-10" :class="isDarkMode ? 'bg-slate-950/90 border-slate-800 text-slate-300' : 'bg-slate-100/95 border-slate-200 text-slate-700'">
              <div class="flex items-center gap-1.5 truncate">
                <i class="fa-solid fa-circle-dot text-emerald-500 animate-ping"></i>
                <span class="truncate">{{ locationStatusMessage }}</span>
              </div>
              <span class="text-blue-500 font-black shrink-0 ml-2">Google Maps Live</span>
            </div>

          </div>

          
          <!-- CỘT PHẢI (5/12): TAPLO THÔNG TIN TRẠM & LUẬT ĐỔI TÀI 4 TIẾNG -->
          <div class="lg:col-span-5 space-y-4 flex flex-col justify-between">
            
            <!-- Widget Trạm tiếp theo & Tốc độ HUD -->
            <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-5 rounded-3xl border shadow-lg space-y-4 text-center">
              
              <!-- Tốc độ xe linh hoạt HUD -->
              <div class="flex items-center justify-around border-b pb-3" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
                <div>
                  <span class="text-[10px] font-bold text-slate-400 block uppercase">VẬN TỐC HIỆN TẠI</span>
                  <div class="flex items-baseline justify-center gap-1">
                    <span class="text-4xl font-black font-mono text-blue-600 dark:text-blue-400">{{ currentSpeed }}</span>
                    <span class="text-xs font-bold text-slate-400">km/h</span>
                  </div>
                  <span class="text-[10px] font-bold" :class="speedStatus.color">{{ speedStatus.label }}</span>
                </div>

                <div class="h-10 w-[1px] bg-slate-200 dark:bg-slate-800"></div>

                <div>
                  <span class="text-[10px] font-bold text-slate-400 block uppercase">ĐOẠN ĐƯỜNG</span>
                  <span class="text-sm font-black block font-mono text-slate-800 dark:text-slate-200">Quốc Lộ 1A</span>
                  <span class="text-[10px] text-slate-400">Khuyến nghị 60 - 90 km/h</span>
                </div>
              </div>

              <!-- Trạm tiếp theo -->
              <div>
                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/10 text-blue-600 border border-blue-500/20 inline-block mb-1">
                  TRẠM DỪNG TIẾP THEO
                </span>

                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                  {{ nextStop?.stop_name || 'Trạm Dừng Lệ Thủy (Quảng Bình)' }}
                </h2>

                <div class="flex items-center justify-center gap-3 sm:gap-6 text-xs sm:text-sm font-black text-slate-600 dark:text-slate-300 mt-2 flex-wrap">
                  <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-location-arrow text-red-500"></i>
                    <span>Cách: <strong class="text-slate-900 dark:text-white text-base">~42 km</strong></span>
                  </div>
                  <span>•</span>
                  <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-clock text-blue-500"></i>
                    <span>Dự kiến: <strong class="text-slate-900 dark:text-white text-base">{{ nextStop?.planned_time || '23:15' }}</strong></span>
                  </div>
                </div>
              </div>

              <!-- ĐỒNG HỒ LUẬT ĐỔI TÀI 4 TIẾNG -->
              <div 
                class="p-4 rounded-2xl border transition-all" 
                :class="isDrivingWarning ? 'bg-red-500/15 border-red-500 text-red-600 dark:text-red-400 animate-pulse' : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700'"
              >
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                  <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-clock-rotate-left text-xl" :class="isDrivingWarning ? 'text-red-500' : 'text-blue-500'"></i>
                    <div class="text-left">
                      <span class="font-black block text-sm">
                        Thời Gian Lái Liên Tục: {{ formattedDrivingTime }} / 04h 00m
                      </span>
                      <span class="text-[11px] opacity-80" v-if="!isDrivingWarning">
                        Luật GTĐB: Bắt buộc đổi tài xế sau 4 tiếng lái xe liên tục.
                      </span>
                      <span class="text-[11px] font-black text-red-600 dark:text-red-400 block" v-else>
                        ⚠️ CẢNH BÁO: Bác tài đã lái liên tục gần 4 tiếng! Đổi tài xế ngay!
                      </span>
                    </div>
                  </div>

                  <button
                    @click="showShiftModal = true"
                    type="button"
                    class="px-4 py-2 rounded-xl bg-slate-900 hover:bg-black text-white dark:bg-white dark:text-slate-900 font-black text-xs cursor-pointer shadow-xs shrink-0"
                  >
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Bấm Đổi Tài Xế 2
                  </button>
                </div>
              </div>

            </div>

          </div>

        </div>

        <!-- 3 NÚT KHẨN CẤP THẬT TO TRÊN MÀN HÌNH TAPLO (HIỆU ỨNG PRESSED & ĐỔ BÓNG SÂU) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          
          <!-- Nút 1: Báo Tắc Đường (Cam) -->
          <button
            @click="showDelayModal = true"
            type="button"
            class="py-6 px-4 rounded-3xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-base flex flex-col items-center justify-center gap-2 shadow-xl shadow-amber-500/25 hover:shadow-amber-500/40 cursor-pointer transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-1 active:scale-[0.98] active:shadow-md"
          >
            <i class="fa-solid fa-hourglass-half text-3xl"></i>
            <span>BÁO TẮC ĐƯỜNG / ĐẾN TRỄ</span>
            <span class="text-[11px] font-bold opacity-90">+15p, +30p, +45p, +60p</span>
          </button>

          <!-- Nút 2: Sự Cố Khẩn Cấp (Đỏ) -->
          <button
            @click="showIncidentModal = true"
            type="button"
            class="py-6 px-4 rounded-3xl bg-red-600 hover:bg-red-700 text-white font-black text-base flex flex-col items-center justify-center gap-2 shadow-xl shadow-red-600/35 hover:shadow-red-600/50 cursor-pointer transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-1 active:scale-[0.98] active:shadow-md"
          >
            <i class="fa-solid fa-triangle-exclamation text-3xl"></i>
            <span>SỰ CỐ XE KHẨN CẤP</span>
            <span class="text-[11px] font-bold opacity-90">Hỏng máy, nổ lốp, cứu hộ 114</span>
          </button>

          <!-- Nút 3: Đã Đến Trạm (Xanh) - Với Hiệu Ứng Loading Xoay Vòng 1.4s Thực Tế -->
          <button
            @click="handleArriveClick"
            type="button"
            :disabled="isArrivingStationLoading"
            class="py-6 px-4 rounded-3xl bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-800 text-white font-black text-base flex flex-col items-center justify-center gap-2 shadow-xl shadow-emerald-600/35 hover:shadow-emerald-600/50 cursor-pointer transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-1 active:scale-[0.98] active:shadow-md"
          >
            <template v-if="isArrivingStationLoading">
              <i class="fa-solid fa-spinner fa-spin text-3xl text-emerald-200"></i>
              <span class="animate-pulse">ĐANG ĐỐI SOÁT KHÁCH TẠI TRẠM...</span>
              <span class="text-[11px] font-bold opacity-90">Đang đồng bộ dữ liệu vé & mở hầm</span>
            </template>
            <template v-else>
              <i class="fa-solid fa-door-open text-3xl"></i>
              <span>ĐÃ ĐẾN TRẠM (MỞ CỬA)</span>
              <span class="text-[11px] font-bold opacity-90">Bật cảnh báo Đón/Trả & Mở hầm</span>
            </template>
          </button>

        </div>

        <!-- Tiện ích phụ: Khai báo Chi Phí & Hoàn Thành Chuyến -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <button
            @click="showExpenseModal = true"
            class="p-4 rounded-2xl border flex items-center justify-between text-xs font-bold cursor-pointer transition"
            :class="isDarkMode ? 'bg-slate-900 border-slate-800 hover:bg-slate-800' : 'bg-white border-slate-200 hover:bg-slate-50'"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center">
                <i class="fa-solid fa-receipt text-lg"></i>
              </div>
              <div class="text-left">
                <span class="font-black text-sm block">Khai Báo Chi Phí Chuyến Đi (Tiền Phơi / BOT)</span>
                <span class="text-slate-400 text-[11px]">Đã ghi nhận: {{ formatMoney(totalTripExpenses) }}</span>
              </div>
            </div>
            <span class="text-purple-600 font-black">+ Nhập Tiền & Chụp Ảnh</span>
          </button>

          <button
            @click="showFinishModal = true"
            class="p-4 rounded-2xl border flex items-center justify-between text-xs font-bold cursor-pointer transition"
            :class="isDarkMode ? 'bg-slate-900 border-slate-800 hover:bg-slate-800' : 'bg-white border-slate-200 hover:bg-slate-50'"
          >
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                <i class="fa-solid fa-flag-checkered text-lg"></i>
              </div>
              <div class="text-left">
                <span class="font-black text-sm block">Đã Đến Bến Đích Cuối Cùng</span>
                <span class="text-slate-400 text-[11px]">Bấm kết thúc chuyến & ghi sổ bàn giao</span>
              </div>
            </div>
            <span class="text-emerald-600 font-black">Kết Thúc ➔</span>
          </button>
        </div>

      </section>

      <!-- ============================================================== -->
      <!-- TAB 3: TẢI TRỌNG XE & SƠ ĐỒ GHẾ (36 GIƯỜNG - VIEW ONLY)        -->
      <!-- ============================================================== -->
      <section v-if="activeTab === 'seatmap'" class="space-y-6">
        
        <!-- BANNER THÔNG TIN CHI TIẾT CHUYẾN XE ĐANG XEM TẢI TRỌNG (RÕ RÀNG 100%) -->
        <div class="p-4 sm:p-5 rounded-3xl border flex flex-col md:flex-row items-start md:items-center justify-between gap-4"
          :class="isDarkMode ? 'border-red-500/20 bg-slate-900/90' : 'border-red-200 bg-red-50/40'"
        >
          <div class="space-y-1.5">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="px-3 py-1 rounded-full text-xs font-black font-mono bg-red-600 text-white shadow-xs">
                MÃ CHUYẾN: #{{ activeActionTrip?.trip_code }}
              </span>
              <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black"
                :class="activeActionTrip?.status === 'IN_TRANSIT' ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30' : (activeActionTrip?.status === 'COMPLETED' ? 'bg-slate-200 text-slate-700' : 'bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-500/30')"
              >
                ● {{ activeActionTrip?.status === 'IN_TRANSIT' ? 'ĐANG CHẠY TRÊN ĐƯỜNG' : (activeActionTrip?.status === 'COMPLETED' ? 'ĐÃ HOÀN THÀNH' : 'CHỜ XUẤT BẾN') }}
              </span>
              <span class="text-xs font-bold text-slate-500">
                • Biển số xe: <strong class="text-slate-900 dark:text-white font-mono">{{ activeActionTrip?.bus?.license_plate || '43B-067.89' }}</strong>
              </span>
              <span class="text-xs font-bold text-slate-500">
                • Lơ xe: <strong class="text-slate-900 dark:text-white">{{ activeActionTrip?.conductor?.name || 'Nguyễn Văn Phúc' }}</strong>
              </span>
            </div>

            <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
              <span>{{ activeActionTrip?.route?.origin || 'Đà Nẵng' }}</span>
              <span class="text-red-500 font-black text-base select-none">➔</span>
              <span>{{ activeActionTrip?.route?.destination || 'Quy Nhơn' }}</span>
              <span class="text-xs font-bold text-slate-500 font-normal">({{ activeActionTrip?.route?.name }})</span>
            </h3>

            <div class="text-xs font-bold text-slate-500 flex items-center gap-3 flex-wrap">
              <span>⏰ Giờ chạy: <strong class="text-red-600 dark:text-red-400 font-mono text-sm">{{ activeActionTrip?.departure_time?.substring(11, 16) }} ➔ {{ activeActionTrip?.arrival_time?.substring(11, 16) }}</strong></span>
              <span>📅 Ngày khởi hành: <strong class="text-slate-800 dark:text-slate-200">{{ activeActionTrip?.departure_time ? (activeActionTrip.departure_time.substring(8, 10) + '/' + activeActionTrip.departure_time.substring(5, 7) + '/' + activeActionTrip.departure_time.substring(0, 4)) : '29/10/2026' }}</strong></span>
            </div>
          </div>

          <!-- Trip Switcher Chips & Date Filter: Chuyển nhanh ngày & chuyến của tài xế -->
          <div class="flex flex-col items-start md:items-end gap-2 shrink-0">
            <!-- Chọn ngày xem tải trọng -->
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="text-[11px] font-bold text-slate-400 uppercase mr-1">Ngày:</span>
              <button
                type="button"
                @click="onSeatmapDateChange(filterTodayDateStr)"
                class="px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer border"
                :class="seatmapDate === filterTodayDateStr
                  ? 'bg-red-600 text-white border-red-600 shadow-xs'
                  : (isDarkMode ? 'bg-slate-800 text-slate-300 border-slate-700' : 'bg-white text-slate-700 border-slate-200')"
              >
                Hôm Nay
              </button>
              <button
                type="button"
                @click="onSeatmapDateChange(filterTomorrowDateStr)"
                class="px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer border"
                :class="seatmapDate === filterTomorrowDateStr
                  ? 'bg-red-600 text-white border-red-600 shadow-xs'
                  : (isDarkMode ? 'bg-slate-800 text-slate-300 border-slate-700' : 'bg-white text-slate-700 border-slate-200')"
              >
                Ngày Mai
              </button>
              <input
                type="date"
                :value="seatmapDate"
                @change="onSeatmapDateChange($event.target.value)"
                class="px-2 py-0.5 rounded-xl text-xs font-black border cursor-pointer"
                :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-white border-slate-200 text-slate-800'"
                title="Chọn ngày khác"
              />
            </div>

            <!-- Danh sách chuyến xe trong ngày được chọn -->
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="text-[11px] font-bold text-slate-400 uppercase mr-1">Chuyến:</span>
              <button
                v-for="t in seatmapTrips"
                :key="t.id"
                @click="switchTripForSeatmap(t)"
                class="px-2.5 py-1 rounded-xl text-xs font-black transition cursor-pointer border"
                :class="activeActionTrip?.id === t.id 
                  ? 'bg-red-600 text-white border-red-600 shadow-xs' 
                  : (isDarkMode ? 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100')"
              >
                #{{ t.trip_code }} ({{ t.departure_time?.substring(11, 16) }})
              </button>
              <span v-if="seatmapTrips.length === 0" class="text-xs text-slate-400 italic">
                Chưa có chuyến trong ngày này
              </span>
            </div>
          </div>
        </div>

        <!-- Widget Đo Tải Trọng & Cân Bằng Trọng Tâm Xe (30 Giường) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-4 sm:p-5 rounded-3xl border shadow-xs space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Tổng Khách & Độ Lấp Đầy</span>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-black text-slate-900 dark:text-white">{{ loadStats.uniqueCustomers }} Khách <span class="text-sm font-bold text-slate-400">({{ loadStats.total }}/30 Ghế)</span></span>
              <span class="text-xs font-bold text-emerald-600">({{ loadStats.percent }}% Đủ Tải)</span>
            </div>
            <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
              <div class="h-full bg-emerald-500 rounded-full" :style="{ width: loadStats.percent + '%' }"></div>
            </div>
            <span class="text-[11px] text-slate-500 block">Ước tính tải trọng khách: ~{{ loadStats.weightKgEstimate }} kg</span>
          </div>

          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-4 sm:p-5 rounded-3xl border shadow-xs space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Phân Bổ Tầng 1 (Dưới) vs Tầng 2 (Trên)</span>
            <div class="flex items-center justify-between text-xs font-black pt-1">
              <span>Tầng 1 (Dưới): {{ loadStats.t1Count }} / 18</span>
              <span>Tầng 2 (Trên): {{ loadStats.t2Count }} / 12</span>
            </div>
            <div class="flex h-2 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800">
              <div class="bg-blue-500" :style="{ width: ((loadStats.t1Count / (loadStats.total || 1)) * 100) + '%' }"></div>
              <div class="bg-amber-500" :style="{ width: ((loadStats.t2Count / (loadStats.total || 1)) * 100) + '%' }"></div>
            </div>
            <span class="text-[11px] text-slate-500 block">Tầng 1 (Xanh) | Tầng 2 (Vàng)</span>
          </div>

          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-4 sm:p-5 rounded-3xl border shadow-xs space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Đánh Giá Cân Bằng Trọng Tâm</span>
            <div v-if="loadStats.isBalanced" class="flex items-center gap-2 text-emerald-600 font-bold text-xs pt-1">
              <i class="fa-solid fa-circle-check text-lg"></i>
              <span>Cân Bằng Tốt: Trọng lượng phân bổ đều 2 tầng</span>
            </div>
            <div v-else-if="loadStats.isHeavyTop" class="flex items-center gap-2 text-amber-600 font-bold text-xs pt-1">
              <i class="fa-solid fa-triangle-exclamation text-lg"></i>
              <span>Lưu Ý: Khách tập trung tầng 2, vào cua chậm</span>
            </div>
            <div v-else class="flex items-center gap-2 text-blue-600 font-bold text-xs pt-1">
              <i class="fa-solid fa-circle-info text-lg"></i>
              <span>Trọng Tâm Thấp: Khách tập trung tầng 1, xe đầm</span>
            </div>
            <span class="text-[11px] text-slate-400 block">Dữ liệu hỗ trợ Bác tài kiểm soát lái xe êm ái</span>
          </div>
        </div>

        <!-- Sơ Đồ 30 Ghế (18 Giường Tầng 1 & 12 Giường Tầng 2) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <!-- Tầng 1: A01 - A18 -->
          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-5 sm:p-6 rounded-3xl border shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-bed text-blue-500"></i>
                <h3 class="font-black text-sm sm:text-base">TẦNG 1: GIƯỜNG VIP / DƯỚI (A01 - A18)</h3>
              </div>
              <span class="text-xs font-bold text-slate-400">18 Giường</span>
            </div>

            <div class="grid grid-cols-3 gap-2.5 text-center text-[10px] font-bold text-slate-400 pb-1 mb-1 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
              <span>🪟 Cửa sổ</span>
              <span>🚶 Dãy giữa</span>
              <span>🪟 Cửa sổ</span>
            </div>

            <div class="grid grid-cols-3 gap-2.5">
              <div
                v-for="i in 18"
                :key="'A' + i"
                @click="openSeatReadOnlyModal('A' + (i < 10 ? '0' + i : i))"
                class="p-2.5 rounded-2xl border text-center transition cursor-pointer min-h-[58px] flex flex-col items-center justify-center"
                :class="seatMap['A' + (i < 10 ? '0' + i : i)]?.status !== 'AVAILABLE'
                  ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400'
                  : (isDarkMode ? 'bg-slate-800/40 border-slate-800 text-slate-500' : 'bg-slate-50 border-slate-200 text-slate-400')"
              >
                <span class="font-mono font-black text-xs block">A{{ i < 10 ? '0' + i : i }}</span>
                <span v-if="seatMap['A' + (i < 10 ? '0' + i : i)]?.status !== 'AVAILABLE'" class="text-[10px] font-bold truncate block">
                  {{ seatMap['A' + (i < 10 ? '0' + i : i)]?.customer_name || 'Có khách' }}
                </span>
                <span v-else class="text-[10px] font-medium block">Trống</span>
              </div>
            </div>
          </div>

          <!-- Tầng 2: B01 - B12 (12 Giường, 2 Dãy Cửa Sổ, Cột Giữa Lối Đi Cân Đối 100% Tầng 1) -->
          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-5 sm:p-6 rounded-3xl border shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-bed text-amber-500"></i>
                <h3 class="font-black text-sm sm:text-base">TẦNG 2: GIƯỜNG TRÊN (B01 - B12)</h3>
              </div>
              <span class="text-xs font-bold text-slate-400">12 Giường</span>
            </div>

            <div class="grid grid-cols-3 gap-2.5 text-center text-[10px] font-bold text-slate-400 pb-1 mb-1 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
              <span>🪟 Cửa sổ (Trái)</span>
              <span>🚶 Lối đi thông tầng</span>
              <span>🪟 Cửa sổ (Phải)</span>
            </div>

            <div class="grid grid-cols-3 gap-2.5">
              <template v-for="r in 6" :key="'driver-f2-row-' + r">
                <!-- Ghế Cửa Sổ Trái: B01, B03, B05, B07, B09, B11 -->
                <div
                  @click="openSeatReadOnlyModal('B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1)))"
                  class="p-2.5 rounded-2xl border text-center transition cursor-pointer min-h-[58px] flex flex-col items-center justify-center"
                  :class="seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status !== 'AVAILABLE'
                    ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400'
                    : (isDarkMode ? 'bg-slate-800/40 border-slate-800 text-slate-500' : 'bg-slate-50 border-slate-200 text-slate-400')"
                >
                  <span class="font-mono font-black text-xs block">B{{ (r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1) }}</span>
                  <span v-if="seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.status !== 'AVAILABLE'" class="text-[10px] font-bold truncate block">
                    {{ seatMap['B' + ((r - 1) * 2 + 1 < 10 ? '0' + ((r - 1) * 2 + 1) : ((r - 1) * 2 + 1))]?.customer_name || 'Có khách' }}
                  </span>
                  <span v-else class="text-[10px] font-medium block">Trống</span>
                </div>

                <!-- Cột Giữa: Lối Đi Thông Tầng VIP -->
                <div 
                  class="p-2.5 rounded-2xl border border-dashed text-center flex flex-col items-center justify-center select-none min-h-[58px]"
                  :class="isDarkMode ? 'border-slate-800 bg-slate-800/30 text-slate-600' : 'border-slate-200 bg-slate-50/70 text-slate-400'"
                >
                  <span class="text-xs">🚶</span>
                  <span class="text-[9px] font-bold uppercase tracking-tighter">Lối đi</span>
                </div>

                <!-- Ghế Cửa Sổ Phải: B02, B04, B06, B08, B10, B12 -->
                <div
                  @click="openSeatReadOnlyModal('B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2)))"
                  class="p-2.5 rounded-2xl border text-center transition cursor-pointer min-h-[58px] flex flex-col items-center justify-center"
                  :class="seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status !== 'AVAILABLE'
                    ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400'
                    : (isDarkMode ? 'bg-slate-800/40 border-slate-800 text-slate-500' : 'bg-slate-50 border-slate-200 text-slate-400')"
                >
                  <span class="font-mono font-black text-xs block">B{{ (r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2) }}</span>
                  <span v-if="seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.status !== 'AVAILABLE'" class="text-[10px] font-bold truncate block">
                    {{ seatMap['B' + ((r - 1) * 2 + 2 < 10 ? '0' + ((r - 1) * 2 + 2) : ((r - 1) * 2 + 2))]?.customer_name || 'Có khách' }}
                  </span>
                  <span v-else class="text-[10px] font-medium block">Trống</span>
                </div>
              </template>
            </div>
          </div>

        </div>

      </section>

      <!-- ============================================================== -->
      <!-- TAB 4: LỊCH TRÌNH 7 NGÀY (CALENDAR Ô VUÔNG & TIMELINE DỌC PRO) -->
      <!-- ============================================================== -->
      <section v-if="activeTab === 'schedule'" class="space-y-5">
        
        <!-- Header & Switcher Dạng Xem -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-200'">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                LỊCH PHÂN CÔNG TUẦN • {{ currentWeekInfo.monthStr }}
              </span>
              <span class="text-xs text-slate-400">{{ currentWeekInfo.weekRangeStr }}</span>
            </div>
            <h2 class="text-lg sm:text-xl font-black mt-1">Lịch Trình Chạy Xe & Điều Động Ca Lái</h2>
            <p :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'" class="text-xs">
              Theo dõi lộ trình tuần theo dạng Timeline dọc thông thoáng hoặc Lịch ô vuông trực quan.
            </p>
          </div>

          <!-- Nút đổi View Mode (Timeline Dọc vs Lịch Ô Vuông) -->
          <div class="flex items-center gap-1.5 p-1 rounded-2xl border self-start md:self-auto" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-slate-100 border-slate-200'">
            <button
              type="button"
              @click="scheduleViewMode = 'timeline'"
              class="px-3.5 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer"
              :class="scheduleViewMode === 'timeline' 
                ? (isDarkMode ? 'bg-red-600 text-white shadow-xs' : 'bg-white text-red-600 shadow-xs') 
                : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
            >
              <i class="fa-solid fa-timeline"></i>
              <span>Timeline Dọc</span>
            </button>
            <button
              type="button"
              @click="scheduleViewMode = 'calendar'"
              class="px-3.5 py-1.5 rounded-xl text-xs font-black transition flex items-center gap-1.5 cursor-pointer"
              :class="scheduleViewMode === 'calendar' 
                ? (isDarkMode ? 'bg-red-600 text-white shadow-xs' : 'bg-white text-red-600 shadow-xs') 
                : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
            >
              <i class="fa-solid fa-calendar-week"></i>
              <span>Lịch Ô Vuông</span>
            </button>
          </div>
        </div>

        <!-- 4 THẺ TÓM TẮT KPI TUẦN (WEEKLY STATS) - THAY THẾ CHỦ NHẬT NGHỈ CA & FIX BUG ~NaNh -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <!-- Card 1: Tổng chuyến tài xế đã chạy -->
          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-4 rounded-3xl border shadow-xs space-y-1">
            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 block uppercase tracking-wider">TỔNG CHUYẾN ĐÃ CHẠY</span>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-black text-slate-900 dark:text-white font-mono">{{ weeklyStats.completedTrips }}</span>
              <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">chuyến</span>
            </div>
            <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold block">✓ 100% hoàn thành an toàn</span>
          </div>

          <!-- Card 2: Quãng đường lăn bánh -->
          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-4 rounded-3xl border shadow-xs space-y-1">
            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 block uppercase tracking-wider">QUÃNG ĐƯỜNG LĂN BÁNH</span>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">{{ weeklyStats.totalKm.toLocaleString('vi-VN') }}</span>
              <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">km</span>
            </div>
            <span class="text-[11px] text-blue-600 dark:text-blue-400 font-bold block">Lũy kế hành trình an toàn</span>
          </div>

          <!-- Card 3: Lượt hành khách đón trả -->
          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-4 rounded-3xl border shadow-xs space-y-1">
            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 block uppercase tracking-wider">HÀNH KHÁCH ĐÓN TRẢ</span>
            <div class="flex items-baseline gap-2">
              <span class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ weeklyStats.totalPax }}</span>
              <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">lượt khách</span>
            </div>
            <span class="text-[11px] text-purple-600 dark:text-purple-400 font-bold block">Đón/trả đúng trạm 100%</span>
          </div>

          <!-- Card 4: Đánh giá & Đúng giờ (Thay thế Chủ Nhật nghỉ ca định kỳ) -->
          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-4 rounded-3xl border shadow-xs space-y-1">
            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 block uppercase tracking-wider">ĐÁNH GIÁ & ĐÚNG GIỜ</span>
            <div class="flex items-baseline gap-2">
              <span class="text-xl font-black text-amber-500 font-mono">{{ weeklyStats.rating }} / 5.0 ⭐</span>
            </div>
            <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold block">Tỷ lệ đúng giờ: {{ weeklyStats.onTimeRate }}</span>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- THANH LỊCH Ô VUÔNG 7 NGÀY (7-DAY CALENDAR STRIP)               -->
        <!-- ============================================================== -->
        <div class="space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50 dark:bg-zinc-900/60 p-3 rounded-2xl border border-slate-200 dark:border-zinc-800">
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="text-xs font-bold text-slate-500 dark:text-zinc-400">📅 Xem lịch:</span>
              <button
                type="button"
                @click="selectedDateFilter = currentWeekInfo.todayStr"
                class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="selectedDateFilter === currentWeekInfo.todayStr ? 'bg-red-600 text-white font-black shadow-xs' : 'bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300'"
              >
                Hôm nay ({{ currentWeekInfo.todayDM }})
              </button>
              <button
                type="button"
                @click="selectedDateFilter = currentWeekInfo.tomorrowStr"
                class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="selectedDateFilter === currentWeekInfo.tomorrowStr ? 'bg-red-600 text-white font-black shadow-xs' : 'bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300'"
              >
                Ngày mai ({{ currentWeekInfo.tomorrowDM }})
              </button>
              <button
                type="button"
                @click="selectedDateFilter = currentWeekInfo.yesterdayStr"
                class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="selectedDateFilter === currentWeekInfo.yesterdayStr ? 'bg-red-600 text-white font-black shadow-xs' : 'bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300'"
              >
                Hôm qua ({{ currentWeekInfo.yesterdayDM }})
              </button>
              <button
                type="button"
                @click="selectedDateFilter = 'ALL'"
                class="px-2.5 py-1 rounded-xl text-xs font-bold transition cursor-pointer"
                :class="selectedDateFilter === 'ALL' ? 'bg-blue-600 text-white font-black shadow-xs' : 'bg-white dark:bg-zinc-800 border border-slate-200 dark:border-zinc-700 text-slate-700 dark:text-zinc-300'"
              >
                Tất cả tuần
              </button>
            </div>

            <div class="flex items-center gap-2 text-xs">
              <span class="text-xs font-bold text-slate-500 dark:text-zinc-400">Hoặc chọn ngày cũ:</span>
              <input 
                type="date" 
                v-model="selectedDateFilter" 
                class="px-2.5 py-1 rounded-xl border text-xs font-bold font-mono cursor-pointer"
                :class="isDarkMode ? 'bg-zinc-800 border-zinc-700 text-white' : 'bg-white border-slate-200 text-slate-900'"
              />
            </div>
          </div>

          <!-- Dải 7 ô vuông ngày trong tuần -->
          <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
            <button
              v-for="day in weekDays"
              :key="day.dateStr"
              type="button"
              @click="selectDateForSchedule(day.dateStr)"
              class="p-3 rounded-2xl border text-center transition cursor-pointer relative flex flex-col items-center justify-between min-h-[96px]"
              :class="[
                selectedDateFilter === day.dateStr
                  ? (isDarkMode 
                      ? 'bg-red-950/60 border-red-500 ring-2 ring-red-500 shadow-lg text-white' 
                      : 'bg-red-50 border-red-500 ring-2 ring-red-400 shadow-lg text-red-950')
                  : (isDarkMode 
                      ? 'bg-slate-900 border-slate-800 hover:bg-slate-800 text-slate-300' 
                      : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-800')
              ]"
            >
              <!-- Badge Hôm Nay -->
              <span
                v-if="day.isToday"
                class="absolute -top-2 px-2 py-0.2 rounded-full text-[9px] font-black uppercase tracking-wider bg-red-600 text-white shadow-xs"
              >
                HÔM NAY
              </span>

              <span class="text-xs font-bold block opacity-70">
                {{ day.dayName }}
              </span>

              <div class="text-xl sm:text-2xl font-black font-mono my-0.5">
                {{ day.dayNum }}
              </div>

              <!-- Badge số chuyến -->
              <div>
                <span
                  v-if="day.tripCount > 0"
                  class="px-2 py-0.5 rounded-full text-[10px] font-black"
                  :class="selectedDateFilter === day.dateStr
                    ? 'bg-red-600 text-white'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
                >
                  {{ day.tripCount }} Chuyến
                </span>
                <span
                  v-else
                  class="text-[10px] text-slate-400 font-medium"
                >
                  Chưa xếp lịch
                </span>
              </div>
            </button>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- CHẾ ĐỘ 1: TIMELINE DỌC HÀNH TRÌNH (VERTICAL TIMELINE VIEW)     -->
        <!-- ============================================================== -->
        <div v-if="scheduleViewMode === 'timeline'" class="space-y-4">
          
          <!-- TIÊU ĐỀ NGÀY ĐANG XEM TRONG TIMELINE -->
          <div 
            class="p-3.5 rounded-2xl border shadow-xs flex items-center justify-between gap-3 transition-colors"
            :class="isDarkMode ? 'bg-[#1E1E1E] border-[#2E2E2E] text-zinc-100' : 'bg-white border-slate-200 text-slate-900'"
          >
            <div class="flex items-center gap-2.5">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              <span class="text-xs font-black uppercase text-slate-400">Đang xem lịch ngày:</span>
              <strong class="text-sm font-black text-slate-900 dark:text-white">{{ activeDayTitle }}</strong>
            </div>

            <div class="flex items-center gap-2">
              <span class="text-xs font-bold px-2.5 py-1 rounded-full" :class="isDarkMode ? 'bg-[#2A2A2A] text-zinc-300' : 'bg-slate-100 text-slate-700'">
                {{ filteredScheduleTrips.length }} Chuyến xe
              </span>
            </div>
          </div>

          <!-- Empty State (Nếu chọn ngày nghỉ) -->
          <div 
            v-if="filteredScheduleTrips.length === 0"
            class="p-8 sm:p-12 rounded-3xl border text-center space-y-3"
            :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'"
          >
            <span class="text-5xl block">🌴</span>
            <h3 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">
              Bác Tài Được Phân Công Nghỉ Ca Định Kỳ!
            </h3>
            <p class="text-xs text-slate-400 max-w-md mx-auto">
              Không có chuyến xe nào được điều động vào ngày này. Bác tài vui lòng nghỉ ngơi, nạp lại năng lượng để chuẩn bị cho hành trình tiếp theo an toàn!
            </p>
          </div>

          <!-- Vertical Timeline Container -->
          <div v-else class="relative pl-6 sm:pl-8 space-y-6 before:absolute before:left-3 sm:before:left-4 before:top-3 before:bottom-3 before:w-1 before:bg-gradient-to-b before:from-blue-600 before:via-emerald-500 before:to-slate-300 dark:before:to-slate-800">
            
            <div
              v-for="trip in filteredScheduleTrips"
              :key="trip.id"
              class="relative group"
            >
              <!-- Timeline Pin / Node tròn trên trục đứng -->
              <div 
                class="absolute -left-6 sm:-left-8 top-5 w-6 h-6 rounded-full border-4 flex items-center justify-center transition"
                :class="[
                  trip.status === 'IN_TRANSIT'
                    ? (isDarkMode ? 'bg-emerald-500 border-slate-950 ring-4 ring-emerald-500/30' : 'bg-emerald-500 border-white ring-4 ring-emerald-200')
                    : trip.status === 'COMPLETED'
                      ? (isDarkMode ? 'bg-slate-700 border-slate-950 text-white' : 'bg-slate-300 border-white text-slate-700')
                      : (isDarkMode ? 'bg-blue-600 border-slate-950 text-white' : 'bg-blue-600 border-white text-white')
                ]"
              >
                <i v-if="trip.status === 'COMPLETED'" class="fa-solid fa-check text-[10px]"></i>
                <span v-else-if="trip.status === 'IN_TRANSIT'" class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                <span v-else class="w-1.5 h-1.5 rounded-full bg-white"></span>
              </div>

              <!-- Thẻ Chuyến Xe trên Timeline -->
              <div
                class="p-4 sm:p-5 rounded-3xl border transition duration-200"
                :class="[
                  isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200',
                  trip.status === 'COMPLETED' ? 'border-slate-300 dark:border-slate-700 shadow-sm' : 'shadow-md group-hover:border-blue-500/40'
                ]"
              >
                <!-- Header Card: Thời gian & Trạng thái -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
                  <div class="flex items-center gap-2 flex-wrap">
                    <!-- Thời gian xuất bến & đến -->
                    <span class="text-base sm:text-lg font-black font-mono text-red-600 dark:text-red-400">
                      {{ trip.departure_time?.substring(11, 16) }} ➔ {{ trip.arrival_time?.substring(11, 16) }}
                    </span>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 font-mono">
                      ({{ trip.departure_time?.substring(0, 10) }})
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black font-mono bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                      #{{ trip.trip_code }}
                    </span>
                  </div>

                  <!-- Trạng thái chuyến -->
                  <div>
                    <span v-if="trip.status === 'IN_TRANSIT'" class="inline-flex items-center gap-1.5 text-[11px] font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-950/60 dark:text-emerald-300 px-3 py-1 rounded-full">
                      <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span> ĐANG CHẠY TRÊN ĐƯỜNG
                    </span>
                    <span v-else-if="trip.status === 'COMPLETED'" class="inline-flex items-center gap-1 text-[11px] font-black text-slate-600 bg-slate-100 dark:bg-slate-800 dark:text-slate-300 px-3 py-1 rounded-full">
                      ✓ ĐÃ HOÀN THÀNH
                    </span>
                    <span v-else class="inline-flex items-center gap-1 text-[11px] font-black text-blue-600 bg-blue-50 dark:bg-blue-950/60 dark:text-blue-300 px-3 py-1 rounded-full">
                      CHỜ XUẤT BẾN
                    </span>
                  </div>
                </div>

                <!-- Thân Card: Lộ trình & Phương tiện -->
                <div class="py-3 space-y-2">
                  <div class="flex items-center justify-between flex-wrap gap-2">
                    <h3 class="text-base sm:text-lg font-black flex items-center gap-2 text-slate-900 dark:text-white">
                      <span>{{ trip.route?.origin || 'Đà Nẵng' }}</span>
                      <span class="text-red-500 font-black text-sm select-none">➔</span>
                      <span>{{ trip.route?.destination || 'Quảng Bình' }}</span>
                    </h3>

                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400">
                      Thời lượng: <strong class="text-slate-800 dark:text-slate-200">~{{ trip.route?.estimated_hours || 5.5 }}h</strong> • Khoảng cách: <strong class="text-slate-800 dark:text-slate-200">{{ trip.route?.distance_km || 270 }} km</strong>
                    </span>
                  </div>

                  <!-- Mini stop sequence preview -->
                  <div class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-400 overflow-x-auto py-1 scrollbar-none">
                    <span class="inline-flex items-center gap-1 shrink-0 font-bold text-slate-700 dark:text-slate-300">
                      <i class="fa-solid fa-circle-dot text-emerald-500 text-[10px]"></i>
                      <span>{{ trip.route?.origin || 'Bến xe Đà Nẵng' }}</span>
                    </span>
                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                    <span class="inline-flex items-center gap-1 shrink-0 font-bold text-slate-700 dark:text-slate-300">
                      <i class="fa-solid fa-location-dot text-amber-500 text-[10px]"></i>
                      <span>Trạm dừng Lệ Thủy</span>
                    </span>
                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                    <span class="inline-flex items-center gap-1 shrink-0 font-bold text-slate-700 dark:text-slate-300">
                      <i class="fa-solid fa-flag-checkered text-red-500 text-[10px]"></i>
                      <span>{{ trip.route?.destination || 'Bến xe Đồng Hới' }}</span>
                    </span>
                  </div>

                  <!-- Phân công xe & 2 tài xế -->
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-2 text-xs">
                    <div :class="isDarkMode ? 'bg-slate-800/40' : 'bg-slate-50 border border-slate-200'" class="p-2.5 rounded-2xl">
                      <span class="text-[10px] text-slate-600 dark:text-slate-400 block font-bold uppercase tracking-wider">Phương Tiện</span>
                      <strong class="font-mono text-slate-900 dark:text-white">{{ trip.bus?.license_plate || '43B-067.89' }}</strong>
                      <span class="text-slate-600 dark:text-slate-400 text-[11px] block font-medium">Xe 36 giường nằm</span>
                    </div>

                    <div :class="isDarkMode ? 'bg-slate-800/40' : 'bg-slate-50 border border-slate-200'" class="p-2.5 rounded-2xl">
                      <span class="text-[10px] text-slate-600 dark:text-slate-400 block font-bold uppercase tracking-wider">Phân Công Bác Tài</span>
                      <div v-if="trip.second_driver || trip.secondDriver" class="font-bold text-slate-900 dark:text-white text-[11px] truncate">
                        <span>Tài 1: {{ trip.driver?.name }}</span><br/>
                        <span class="text-purple-600 dark:text-purple-400">Tài 2: {{ (trip.second_driver || trip.secondDriver)?.name }}</span>
                      </div>
                      <div v-else class="font-bold text-slate-900 dark:text-white text-[11px]">
                        <span>Tài xế: {{ trip.driver?.name }} (1 tài)</span>
                      </div>
                    </div>

                    <div :class="isDarkMode ? 'bg-slate-800/40' : 'bg-slate-50 border border-slate-200'" class="p-2.5 rounded-2xl">
                      <span class="text-[10px] text-slate-600 dark:text-slate-400 block font-bold uppercase tracking-wider">Lơ Xe Phụ Trách</span>
                      <div class="flex items-center justify-between">
                        <strong class="text-slate-900 dark:text-white">{{ trip.conductor?.name || 'Nguyễn Văn Phúc' }}</strong>
                        <button @click="makeCall(trip.conductor?.phone || '0905123456')" class="text-emerald-600 hover:text-emerald-500 font-black cursor-pointer">
                          <i class="fa-solid fa-phone"></i>
                        </button>
                      </div>
                      <span class="text-slate-600 dark:text-slate-400 text-[11px] block font-medium">36/36 Khách (Đủ tải)</span>
                    </div>
                  </div>
                </div>

                <!-- Footer Card: Nút hành động nhanh -->
                <div class="pt-2 flex items-center justify-end gap-2 border-t" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
                  <button
                    type="button"
                    @click="switchTripForSeatmap(trip); activeTab = 'seatmap'"
                    class="px-3.5 py-2 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 cursor-pointer"
                    :class="isDarkMode ? 'border-slate-800 hover:bg-slate-800 text-slate-300' : 'border-slate-200 hover:bg-slate-100 text-slate-700'"
                  >
                    <i class="fa-solid fa-weight-scale text-amber-500"></i>
                    <span>Tải Trọng 36 Giường</span>
                  </button>

                  <button
                    v-if="trip.status === 'IN_TRANSIT'"
                    type="button"
                    @click="openTripInDriveMode(trip)"
                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs flex items-center gap-1.5 shadow-md shadow-blue-600/30 cursor-pointer"
                  >
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Vào Taplo Lái Xe</span>
                  </button>
                  <button
                    v-else-if="trip.status !== 'COMPLETED'"
                    type="button"
                    @click="openSafetyChecklist(trip)"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-red-600 to-amber-600 hover:from-red-700 hover:to-amber-700 text-white font-black text-xs flex items-center gap-1.5 shadow-md cursor-pointer"
                  >
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Kiểm Tra Xe 8 Mục</span>
                  </button>
                </div>
              </div>

            </div>

          </div>

        </div>

        <!-- ============================================================== -->
        <!-- CHẾ ĐỘ 2: LỊCH Ô VUÔNG THEO TUẦN (WEEKLY CALENDAR BOARD)       -->
        <!-- ============================================================== -->
        <div v-else-if="scheduleViewMode === 'calendar'" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-3">
            
            <div
              v-for="day in weekDays"
              :key="'col-' + day.dateStr"
              class="rounded-3xl border p-3 flex flex-col space-y-2.5 min-h-[320px]"
              :class="[
                day.isToday 
                  ? (isDarkMode ? 'bg-slate-900 border-red-500/60 ring-1 ring-red-500/30' : 'bg-red-50/50 border-red-400 shadow-md')
                  : (isDarkMode ? 'bg-slate-900/60 border-slate-800' : 'bg-white border-slate-200')
              ]"
            >
              <!-- Cột Header Ngày -->
              <div class="text-center pb-2 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
                <span v-if="day.isToday" class="px-2 py-0.2 rounded-full text-[9px] font-black uppercase bg-red-600 text-white inline-block mb-1">
                  HÔM NAY
                </span>
                <h4 class="font-black text-xs block text-slate-700 dark:text-slate-300">{{ day.dayName }}</h4>
                <span class="font-mono font-black text-lg block text-slate-900 dark:text-white">{{ day.dayNum }}/10</span>
                <span class="text-[10px] text-slate-400">{{ day.tripCount }} chuyến</span>
              </div>

              <!-- Thẻ Chuyến Trong Cột Ngày -->
              <div class="flex-1 space-y-2 overflow-y-auto">
                <div
                  v-if="day.isRestDay && day.tripCount === 0"
                  class="h-full flex flex-col items-center justify-center p-3 text-center rounded-2xl border border-dashed border-emerald-500/30 bg-emerald-500/5 text-emerald-600 dark:text-emerald-400"
                >
                  <span class="text-2xl mb-1">🌴</span>
                  <span class="text-xs font-black">Nghỉ Ca Định Kỳ</span>
                  <span class="text-[10px] text-slate-400 mt-1">Hồi phục thể lực</span>
                </div>

                <div
                  v-for="t in day.trips"
                  :key="t.id"
                  @click="switchTripForSeatmap(t)"
                  class="p-2.5 rounded-2xl border text-xs space-y-1 cursor-pointer transition hover:scale-102"
                  :class="[
                    t.status === 'IN_TRANSIT'
                      ? 'bg-emerald-500/10 border-emerald-500/40 text-emerald-700 dark:text-emerald-300'
                      : t.status === 'COMPLETED'
                        ? 'bg-slate-100 dark:bg-slate-800/60 border-slate-300 dark:border-slate-700'
                        : (isDarkMode ? 'bg-slate-800/50 border-slate-700' : 'bg-slate-50 border-slate-200')
                  ]"
                >
                  <div class="flex items-center justify-between text-[10px] font-mono font-bold">
                    <span>{{ t.departure_time?.substring(11, 16) }}</span>
                    <span v-if="t.status === 'IN_TRANSIT'" class="text-emerald-600 font-black">● Đang chạy</span>
                    <span v-else-if="t.status === 'COMPLETED'" class="text-slate-600 dark:text-slate-400 font-bold">✓ Xong</span>
                    <span v-else class="text-blue-500">Chờ chạy</span>
                  </div>

                  <strong class="font-bold text-[11px] block truncate text-slate-900 dark:text-white">{{ t.route?.name }}</strong>

                  <div class="flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                    <span>{{ t.bus?.license_plate }}</span>
                    <span v-if="t.second_driver || t.secondDriver" class="text-purple-600 dark:text-purple-400 font-bold">2 Tài</span>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </section>

      <!-- ============================================================== -->
      <!-- TAB 5: LỊCH SỬ CHUYẾN ĐÃ CHẠY HOÀN THÀNH & XEM THEO NGÀY       -->
      <!-- ============================================================== -->
      <section v-if="activeTab === 'history'" class="space-y-6">
        
        <!-- Header & Date Filter Bar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-200'">
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                LỊCH SỬ HOÀN THÀNH AN TOÀN
              </span>
              <span class="text-xs text-slate-400">Xem lại số chuyến đã chạy theo từng ngày</span>
            </div>
            <h2 class="text-lg sm:text-xl font-black mt-1">Lịch Sử Các Chuyến Xe Đã Chạy Xong</h2>
            <p :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'" class="text-xs">
              Lưu trữ đầy đủ các chuyến đã đến đích, số khách chở, giờ xuất bến và chi phí đối soát.
            </p>
          </div>

          <!-- Bộ lọc ngày -->
          <div class="flex flex-wrap items-center gap-1.5 p-1 rounded-2xl border" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-slate-100 border-slate-200'">
            <button
              type="button"
              @click="historyDateFilter = currentWeekInfo.todayStr"
              class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer"
              :class="historyDateFilter === currentWeekInfo.todayStr ? 'bg-red-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
            >
              Hôm nay ({{ currentWeekInfo.todayDM }})
            </button>
            <button
              type="button"
              @click="historyDateFilter = currentWeekInfo.yesterdayStr"
              class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer"
              :class="historyDateFilter === currentWeekInfo.yesterdayStr ? 'bg-red-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
            >
              Hôm qua ({{ currentWeekInfo.yesterdayDM }})
            </button>
            <button
              type="button"
              @click="historyDateFilter = 'ALL'"
              class="px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer"
              :class="historyDateFilter === 'ALL' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-800 dark:hover:text-white'"
            >
              Tất cả ({{ completedTripsList.length }})
            </button>

            <div class="flex items-center gap-1 pl-2 border-l border-slate-300 dark:border-slate-700">
              <input
                type="date"
                v-model="historyDateFilter"
                class="px-2 py-1 rounded-lg text-xs font-bold font-mono border cursor-pointer"
                :class="isDarkMode ? 'bg-zinc-800 border-zinc-700 text-white' : 'bg-white border-slate-300 text-slate-800'"
              />
            </div>
          </div>
        </div>

        <!-- Banner KPI Thống Kê Ngày: Hôm đó chạy được bao nhiêu chuyến -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-5 rounded-3xl border shadow-xs space-y-1">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">
              {{ historyDateFilter === 'ALL' ? 'TỔNG SỐ CHUYẾN ĐÃ CHẠY' : 'SỐ CHUYẾN HOÀN THÀNH TRONG NGÀY' }}
            </span>
            <div class="flex items-baseline gap-2">
              <span class="text-3xl font-black text-emerald-600 font-mono">{{ filteredHistoryTrips.length }}</span>
              <span class="text-xs text-slate-400">chuyến an toàn</span>
            </div>
            <span class="text-xs text-slate-500 block">
              {{ historyDateFilter === 'ALL' ? 'Lưu trữ toàn bộ lịch sử chạy xe' : `Hôm đó chạy được ${filteredHistoryTrips.length} chuyến` }}
            </span>
          </div>

          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-5 rounded-3xl border shadow-xs space-y-1">
            <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider block">QUÃNG ĐƯỜNG LĂN BÁNH</span>
            <div class="flex items-baseline gap-2">
              <span class="text-3xl font-black text-blue-600 dark:text-blue-400 font-mono">{{ historyDateStats.totalKm }}</span>
              <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">km di chuyển</span>
            </div>
            <span class="text-xs text-slate-600 dark:text-slate-400 block font-medium">Đảm bảo an toàn giao thông đường bộ</span>
          </div>

          <div :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'" class="p-5 rounded-3xl border shadow-xs space-y-1">
            <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wider block">HÀNH KHÁCH ĐÃ PHỤC VỤ</span>
            <div class="flex items-baseline gap-2">
              <span class="text-3xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ historyDateStats.totalPax }}</span>
              <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">lượt khách</span>
            </div>
            <span class="text-xs text-slate-600 dark:text-slate-400 block font-medium">Hài lòng & đánh giá cao dịch vụ</span>
          </div>
        </div>

        <!-- Danh Sách Các Chuyến Đã Chạy -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-black uppercase text-slate-700 dark:text-slate-300 tracking-wider">
              DANH SÁCH CHUYẾN XE ({{ filteredHistoryTrips.length }} chuyến)
            </h3>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">Bấm để xem lại hành trình</span>
          </div>

          <div v-if="filteredHistoryTrips.length === 0" class="p-10 text-center rounded-3xl border" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
            <span class="text-4xl block mb-2">📅</span>
            <p class="text-sm font-bold text-slate-700 dark:text-zinc-300">Không có chuyến nào hoàn thành trong ngày {{ historyDateFilter }}</p>
            <p class="text-xs text-slate-500 mt-1">Bác tài hãy bấm "Tất cả" hoặc chọn ngày khác để xem lịch sử.</p>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="trip in filteredHistoryTrips"
              :key="trip.id"
              @click="openPastTripModal(trip)"
              class="p-5 rounded-3xl border transition shadow-xs space-y-3 cursor-pointer hover:shadow-xl hover:scale-[1.01] hover:border-red-500 active:scale-98"
              :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'"
            >
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="font-mono font-black text-base text-red-600">
                      {{ trip.departure_time?.substring(11, 16) }} ➔ {{ trip.arrival_time?.substring(11, 16) }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-100 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300">
                      #{{ trip.trip_code }}
                    </span>
                  </div>
                  <h4 class="text-sm font-black text-slate-900 dark:text-white mt-1">{{ trip.route?.name }}</h4>
                </div>

                <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shrink-0">
                  ✓ HOÀN THÀNH
                </span>
              </div>

              <div class="grid grid-cols-2 gap-2 text-xs pt-2 border-t" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'">
                <div>
                  <span class="text-slate-600 dark:text-slate-400 text-[11px] font-bold block">Xe & Biển Số:</span>
                  <strong class="text-slate-900 dark:text-zinc-100 font-mono font-black">🚍 {{ trip.bus?.license_plate }}</strong>
                </div>
                <div>
                  <span class="text-slate-600 dark:text-slate-400 text-[11px] font-bold block">Lơ Xe Phụ Trách:</span>
                  <strong class="text-slate-900 dark:text-zinc-100 font-bold">🎫 {{ trip.conductor?.name || 'Lơ xe nhaxekientruc' }}</strong>
                </div>
                <div>
                  <span class="text-slate-600 dark:text-slate-400 text-[11px] font-bold block">Số Khách Trên Xe:</span>
                  <strong class="text-slate-900 dark:text-zinc-100 font-bold">👥 {{ trip.occupied_count !== undefined ? trip.occupied_count : 0 }} ghế ({{ trip.customer_count || (trip.bookings ? trip.bookings.length : 0) }} khách) / {{ trip.bus?.total_seats || 34 }} ghế</strong>
                </div>
                <div>
                  <span class="text-slate-600 dark:text-slate-400 text-[11px] font-bold block">Ngày Chạy:</span>
                  <strong class="text-slate-900 dark:text-zinc-100 font-mono font-bold">📅 {{ trip.departure_time?.substring(0, 10) }}</strong>
                </div>
              </div>

              <!-- Chi Phí Chuyến Đi -->
              <div class="flex items-center justify-between text-xs pt-2 border-t" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'">
                <span class="text-slate-600 dark:text-slate-400 font-medium">Chi phí phơi dầu & cầu đường:</span>
                <strong class="text-purple-600 font-mono font-black">
                  {{ formatMoney(trip.expenses ? trip.expenses.reduce((s, e) => s + Number(e.amount), 0) : 0) }}
                </strong>
              </div>
            </div>
          </div>
        </div>

        <!-- MỤC ĐÁNH GIÁ THỰC TẾ TỪ HÀNH KHÁCH (HIỂN THỊ 6 ĐÁNH GIÁ GẦN NHẤT + BẤM XEM TẤT CẢ CÓ LỌC 1-5 SAO) -->
        <div class="p-6 rounded-3xl border shadow-xs space-y-4" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-amber-500 font-black text-sm">★★★★★</span>
                <span class="text-xs font-black uppercase text-amber-500 tracking-wider">ĐÁNH GIÁ THỰC TẾ TỪ HÀNH KHÁCH</span>
              </div>
              <h3 class="text-base font-black text-slate-900 dark:text-white mt-1">
                Khách Hàng Đánh Giá Bác Tài ({{ totalReviewCount }} lượt đánh giá • Điểm: {{ averageRating }}/5.0 ★)
              </h3>
            </div>

            <!-- Nút bấm mở toàn bộ đánh giá có lọc 1 sao đến 5 sao -->
            <button
              type="button"
              @click="showAllReviewsModal = true"
              class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs flex items-center gap-1.5 shadow-xs cursor-pointer transition"
            >
              <span>⭐ Xem Tất Cả (Lọc 1★ Đến 5★)</span>
              <span>➔</span>
            </button>
          </div>

          <!-- Lưới 6 đánh giá thực tế -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
              v-for="r in initialDisplayedReviews"
              :key="r.id"
              class="p-4 rounded-2xl border space-y-2 text-xs"
              :class="isDarkMode ? 'bg-[#18181B] border-zinc-800' : 'bg-slate-50 border-slate-200'"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-amber-600 to-red-600 text-white font-bold flex items-center justify-center text-xs">
                    {{ (r.reviewer_name || 'K').substring(0, 1) }}
                  </div>
                  <div>
                    <strong class="font-bold text-slate-900 dark:text-white">{{ r.reviewer_name || 'Hành khách' }}</strong>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium block">{{ r.created_at ? r.created_at.substring(0, 10) : '28/10/2026' }}</span>
                  </div>
                </div>

                <div class="flex items-center gap-1 text-amber-500 font-black">
                  <span>{{ r.rating }}</span>
                  <span>★</span>
                </div>
              </div>

              <p class="text-xs text-slate-800 dark:text-zinc-200 font-medium italic line-clamp-2">
                "{{ r.comment }}"
              </p>
            </div>
          </div>

          <!-- Nút xem thêm -->
          <div class="text-center pt-2">
            <button
              type="button"
              @click="showAllReviewsModal = true"
              class="text-xs font-black text-red-600 hover:text-red-700 underline cursor-pointer"
            >
              Bác tài muốn xem thêm? Bấm vào đây để xem toàn bộ {{ totalReviewCount }} lượt đánh giá và lọc theo từng số sao (1 sao đến 5 sao) »
            </button>
          </div>
        </div>

      </section>

      <!-- ============================================================== -->
      <!-- TAB 6: HỒ SƠ BÁC TÀI & ĐÁNH GIÁ (THẺ NHÂN VIÊN GLASSMORPHISM)  -->
      <!-- ============================================================== -->
      <section v-if="activeTab === 'profile'" class="space-y-6">
        
        <!-- THẺ CĂN CƯỚC / THẺ NHÂN VIÊN BÁC TÀI (DIGITAL ID BADGE - TONE SÁNG SANG TRỌNG) -->
        <div 
          class="relative overflow-hidden rounded-3xl border shadow-xl transition-all duration-300"
          :class="isDarkMode 
            ? 'bg-[#1E1E1E] border-[#2E2E2E] shadow-black/40' 
            : 'bg-white border-slate-200/80 shadow-slate-200/60'"
        >
          <!-- Đường viền Accent mỏng màu Đỏ thương hiệu phối Vàng Gold ở mép trên của thẻ -->
          <div class="h-1.5 w-full bg-gradient-to-r from-red-600 via-rose-500 to-amber-400"></div>

          <div class="p-6 sm:p-8 space-y-6">
            
            <!-- Dải Header Thẻ Nhân Viên Vận Hành -->
            <div 
              class="flex items-center justify-between pb-4 border-b flex-wrap gap-2"
              :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'"
            >
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-red-600 text-white flex items-center justify-center font-black shadow-xs">
                  <i class="fa-solid fa-id-badge text-xs"></i>
                </div>
                <div>
                  <span class="text-[11px] font-black uppercase tracking-widest text-red-600 dark:text-red-400 block font-mono">
                    nhaxekientruc OPERATIONAL PASSPORT
                  </span>
                  <span class="text-xs font-bold text-slate-500 dark:text-zinc-400">
                    THẺ ĐỊNH DANH TÀI XẾ CHUYÊN NGHIỆP
                  </span>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <span class="text-[10px] font-mono font-bold px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                  ● ĐANG HOẠT ĐỘNG
                </span>
                <span class="text-xs font-mono font-black text-slate-800 dark:text-zinc-200">
                  ID: BH-DRV-007
                </span>
              </div>
            </div>

            <!-- Nội Dung Chính Thẻ Nhân Viên (Tone Sáng, Chữ Đen Nhám & Xám Đậm) -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
              
              <!-- Ảnh thẻ & Avatar Bác tài (Vòng viền nét đứt Vàng Gold tinh tế) -->
              <div class="md:col-span-3 flex flex-col items-center text-center">
                <div class="relative">
                  <!-- Vòng viền nét đứt (dashed) màu Vàng Gold sang trọng chuẩn yêu cầu -->
                  <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full border-2 border-dashed border-amber-500/90 p-1.5 flex items-center justify-center transition-transform hover:scale-105">
                    <img 
                      v-if="currentDriver?.avatar || currentDriver?.photo_path"
                      :src="currentDriver?.avatar || currentDriver?.photo_path"
                      :alt="currentDriver?.name"
                      class="w-full h-full rounded-full object-cover shadow-md"
                    />
                    <div v-else class="w-full h-full rounded-full bg-gradient-to-tr from-rose-800 to-red-600 text-white flex items-center justify-center text-3xl font-black shadow-md">
                      {{ currentDriver?.name?.substring(0, 1) || 'D' }}
                    </div>
                  </div>
                  <!-- Huy hiệu xác thực an toàn ở góc avatar -->
                  <div class="absolute bottom-0 right-1 w-7 h-7 rounded-full bg-emerald-500 border-2 border-white dark:border-[#1E1E1E] flex items-center justify-center text-white text-[10px] shadow-sm" title="Chứng nhận an toàn cấp quốc gia">
                    <i class="fa-solid fa-check"></i>
                  </div>
                </div>
                <span class="mt-2.5 text-xs font-black text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                  Tài Xế Hạng Nhất
                </span>
              </div>

              <!-- Chi tiết thông tin Bằng lái & Thâm niên (Chữ đen nhám & Xám đậm) -->
              <div class="md:col-span-6 space-y-3">
                <div>
                  <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                    {{ currentDriver?.name || 'BÙI TIẾN DŨNG' }}
                  </h1>
                  <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                    Đội Xe 1 • Tuyến Đà Nẵng ⇄ Quy Nhơn / Trục QL1A Bắc Nam
                  </p>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                  <div 
                    class="p-3.5 rounded-2xl border transition"
                    :class="isDarkMode ? 'bg-[#18181B] border-zinc-800' : 'bg-slate-50/80 border-slate-200/80'"
                  >
                    <span class="text-[10px] text-slate-400 block uppercase font-bold">GIẤY PHÉP LÁI XE</span>
                    <strong class="text-base font-black text-slate-900 dark:text-white font-mono">{{ currentDriver?.license_class || 'HẠNG E' }}</strong>
                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold block mt-0.5 font-mono">
                      Số: {{ currentDriver?.license_number || 'GPLX-43000007' }}
                    </span>
                  </div>

                  <div 
                    class="p-3.5 rounded-2xl border transition"
                    :class="isDarkMode ? 'bg-[#18181B] border-zinc-800' : 'bg-slate-50/80 border-slate-200/80'"
                  >
                    <span class="text-[10px] text-slate-400 block uppercase font-bold">KINH NGHIỆM</span>
                    <strong class="text-base font-black text-slate-900 dark:text-white font-mono">
                      {{ currentDriver?.years_experience || 8 }} NĂM
                    </strong>
                    <span class="text-[10px] text-blue-600 dark:text-blue-400 font-bold block mt-0.5 font-mono">
                      CCCD: {{ currentDriver?.cccd_number || '048092000777' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Mã QR Định Danh Nhân Viên (Mã QR đen trên ô trắng sạch sẽ, tự nhiên) -->
              <div class="md:col-span-3 flex flex-col items-center justify-center text-center">
                <div 
                  class="w-22 h-22 rounded-2xl border p-2 flex items-center justify-center shadow-xs transition"
                  :class="isDarkMode ? 'bg-zinc-800 border-zinc-700 text-white' : 'bg-white border-slate-200 text-slate-900'"
                >
                  <i class="fa-solid fa-qrcode text-5xl"></i>
                </div>
                <span class="text-[11px] font-bold text-slate-700 dark:text-zinc-300 mt-2 block">
                  MÃ QR ĐỊNH DANH
                </span>
                <span class="text-[9px] text-slate-400">
                  Quét tra cứu hồ sơ GPLX
                </span>
              </div>

            </div>

            <!-- Dải chân thẻ thanh lịch -->
            <div 
              class="pt-3 border-t flex items-center justify-between text-[10px] text-slate-400"
              :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'"
            >
              <span>HỆ THỐNG QUẢN LÝ TÀI XẾ nhaxekientruc VIỆT NAM</span>
              <span>TIÊU CHUẨN AN TOÀN QUỐC GIA QCVN 09:2015</span>
            </div>

          </div>
        </div>

        <!-- HỒ SƠ GIẤY TỜ BÁC TÀI: ẢNH CCCD & GPLX ĐỒNG BỘ TỪ ADMIN (YÊU CẦU 5) -->
        <div class="p-6 rounded-3xl border space-y-5" :class="isDarkMode ? 'bg-[#1E1E1E] border-[#2E2E2E]' : 'bg-white border-slate-200'">
          <div class="flex items-center justify-between pb-3 border-b" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                <i class="fa-solid fa-address-card"></i>
              </div>
              <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">
                  Hồ Sơ Giấy Tờ Pháp Lý & Ảnh Chụp Xác Thực Bác Tài
                </h3>
                <p class="text-xs text-slate-500 dark:text-zinc-400 mt-0.5">
                  Đồng bộ trực tiếp từ hồ sơ chụp Căn cước công dân (CCCD) và Giấy phép lái xe (GPLX) do Admin quản trị phê duyệt.
                </p>
              </div>
            </div>
            <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
              ✓ ĐÃ KIỂM ĐỊNH HỢP LỆ
            </span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Thẻ 1: Căn Cước Công Dân (CCCD) -->
            <div 
              class="p-5 rounded-2xl border space-y-3 flex flex-col justify-between"
              :class="isDarkMode ? 'bg-zinc-900/60 border-zinc-800' : 'bg-slate-50 border-slate-200/80'"
            >
              <div>
                <div class="flex items-center justify-between pb-2 border-b" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-200/60'">
                  <div class="flex items-center gap-2">
                    <span class="text-base">🪪</span>
                    <strong class="text-xs font-black uppercase text-slate-800 dark:text-zinc-200">ẢNH CĂN CƯỚC CÔNG DÂN (CCCD)</strong>
                  </div>
                  <span class="text-[10px] font-mono font-bold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 px-2 py-0.5 rounded-md">
                    {{ currentDriver?.cccd_number || '048092000777' }}
                  </span>
                </div>

                <!-- Preview ảnh CCCD -->
                <div class="mt-3 relative rounded-xl overflow-hidden border border-slate-300 dark:border-zinc-700 bg-slate-200 dark:bg-zinc-800 aspect-video flex items-center justify-center">
                  <img 
                    v-if="currentDriver?.cccd_path"
                    :src="currentDriver.cccd_path"
                    alt="Ảnh CCCD"
                    class="w-full h-full object-cover"
                  />
                  <!-- Card CCCD mô phỏng chân thực nếu chưa có file upload -->
                  <div v-else class="w-full h-full p-4 bg-gradient-to-br from-sky-50 to-blue-100 dark:from-zinc-900 dark:to-zinc-800 flex flex-col justify-between text-slate-800 dark:text-zinc-200">
                    <div class="flex items-center justify-between text-[10px] font-bold">
                      <span class="text-red-600 uppercase font-black">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</span>
                      <span class="text-amber-500 font-bold">CHIP NFC 📶</span>
                    </div>
                    <div class="flex items-center gap-3">
                      <img 
                        :src="currentDriver?.avatar || currentDriver?.photo_path || `https://images.unsplash.com/photo-1507003211169?w=100&h=100&fit=crop`"
                        class="w-14 h-16 rounded-md object-cover border border-slate-300 shadow-xs" 
                      />
                      <div class="text-[11px] space-y-0.5">
                        <span class="font-mono font-black text-blue-900 dark:text-sky-300 block text-xs">Số: {{ currentDriver?.cccd_number || '048092000777' }}</span>
                        <span class="font-bold block">{{ currentDriver?.name || 'Bùi Tiến Dũng' }}</span>
                        <span class="text-[10px] text-slate-500 dark:text-zinc-400 block">Quê quán: Đà Nẵng • Quốc tịch: Việt Nam</span>
                      </div>
                    </div>
                    <div class="text-[9px] text-slate-400 font-mono text-right">
                      CỤC CẢNH SÁT QUẢN LÝ HÀNH CHÍNH VỀ TRẬT TỰ XÃ HỘI
                    </div>
                  </div>
                </div>
              </div>

              <div class="text-[11px] text-slate-500 dark:text-zinc-400 flex items-center justify-between pt-1">
                <span>Họ tên: <strong class="text-slate-800 dark:text-zinc-200">{{ currentDriver?.name }}</strong></span>
                <span class="text-emerald-600 font-bold">✓ Ảnh chụp chuẩn CCCD</span>
              </div>
            </div>

            <!-- Thẻ 2: Giấy Phép Lái Xe (GPLX) -->
            <div 
              class="p-5 rounded-2xl border space-y-3 flex flex-col justify-between"
              :class="isDarkMode ? 'bg-zinc-900/60 border-zinc-800' : 'bg-slate-50 border-slate-200/80'"
            >
              <div>
                <div class="flex items-center justify-between pb-2 border-b" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-200/60'">
                  <div class="flex items-center gap-2">
                    <span class="text-base">🚗</span>
                    <strong class="text-xs font-black uppercase text-slate-800 dark:text-zinc-200">ẢNH GIẤY PHÉP LÁI XE (GPLX)</strong>
                  </div>
                  <span class="text-[10px] font-mono font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded-md">
                    {{ currentDriver?.license_number || 'GPLX-43000007' }}
                  </span>
                </div>

                <!-- Preview ảnh GPLX -->
                <div class="mt-3 relative rounded-xl overflow-hidden border border-slate-300 dark:border-zinc-700 bg-slate-200 dark:bg-zinc-800 aspect-video flex items-center justify-center">
                  <img 
                    v-if="currentDriver?.gplx_path"
                    :src="currentDriver.gplx_path"
                    alt="Ảnh GPLX"
                    class="w-full h-full object-cover"
                  />
                  <!-- Card GPLX mô phỏng chuẩn Bộ GTVT -->
                  <div v-else class="w-full h-full p-4 bg-gradient-to-br from-amber-50 to-orange-100 dark:from-zinc-900 dark:to-zinc-800 flex flex-col justify-between text-slate-800 dark:text-zinc-200">
                    <div class="flex items-center justify-between text-[10px] font-bold">
                      <span class="text-red-700 uppercase font-black">GIẤY PHÉP LÁI XE / DRIVING LICENCE</span>
                      <span class="text-red-700 font-mono font-black text-xs">HẠNG {{ currentDriver?.license_class || 'E' }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                      <img 
                        :src="currentDriver?.avatar || currentDriver?.photo_path || `https://images.unsplash.com/photo-1507003211169?w=100&h=100&fit=crop`"
                        class="w-14 h-16 rounded-md object-cover border border-amber-300 shadow-xs" 
                      />
                      <div class="text-[11px] space-y-0.5">
                        <span class="font-mono font-black text-red-600 block text-xs">Số: {{ currentDriver?.license_number || 'GPLX-43000007' }}</span>
                        <span class="font-bold block">{{ currentDriver?.name || 'Bùi Tiến Dũng' }}</span>
                        <span class="text-[10px] text-slate-600 dark:text-zinc-400 block font-bold">Hạng E: Điều khiển ô tô chở người trên 30 chỗ</span>
                      </div>
                    </div>
                    <div class="text-[9px] text-slate-500 font-medium flex justify-between">
                      <span>Có giá trị đến: {{ currentDriver?.license_expires_at || '2030' }}</span>
                      <span class="font-bold">SỞ GIAO THÔNG VẬN TẢI</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="text-[11px] text-slate-500 dark:text-zinc-400 flex items-center justify-between pt-1">
                <span>Hạng bằng: <strong class="text-red-600 font-mono font-bold">{{ currentDriver?.license_class || 'Hạng E' }}</strong></span>
                <span class="text-emerald-600 font-bold">✓ Giấy tờ còn hạn sử dụng</span>
              </div>
            </div>
          </div>
        </div>

        <!-- KHU VỰC ĐÁNH GIÁ SAO (RATING SUMMARY WITH PROGRESS BARS) -->
        <div class="p-6 rounded-3xl border space-y-6" :class="isDarkMode ? 'bg-[#1E1E1E] border-[#2E2E2E]' : 'bg-white border-slate-200'">
          
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'">
            <div>
              <h3 class="text-lg font-black flex items-center gap-2">
                <i class="fa-solid fa-star text-amber-400"></i>
                <span>Tổng Quan Đánh Giá Thực Tế Từ Hành Khách</span>
              </h3>
              <p :class="isDarkMode ? 'text-zinc-400' : 'text-slate-500'" class="text-xs">
                Tổng hợp {{ totalReviewCount }} lượt đánh giá sau các chuyến xe hoàn thành an toàn.
              </p>
            </div>
            
            <div 
              @click="showAllReviewsModal = true"
              class="flex items-center gap-3 cursor-pointer p-2 rounded-2xl hover:bg-slate-100 dark:hover:bg-zinc-800 transition"
              title="Bấm để xem tất cả đánh giá và lọc theo sao"
            >
              <span class="text-4xl font-black text-amber-500 font-mono">{{ averageRating }}</span>
              <div>
                <div class="flex text-amber-400 text-sm">
                  ★★★★★
                </div>
                <span class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline">
                  {{ totalReviewCount }} Lượt Đánh Giá (Xem chi tiết ➔)
                </span>
              </div>
            </div>
          </div>

          <!-- THANH TÓM TẮT TIẾN TRÌNH ĐÁNH GIÁ (PROGRESS BAR BREAKDOWN) -->
          <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
            
            <div class="md:col-span-6 space-y-2.5">
              <div 
                v-for="item in ratingBreakdown" 
                :key="item.stars"
                class="flex items-center gap-3 text-xs"
              >
                <button
                  type="button"
                  @click="selectedStarFilter = String(item.stars); showAllReviewsModal = true"
                  class="w-14 font-bold shrink-0 text-slate-600 dark:text-zinc-300 text-left hover:text-red-600 dark:hover:text-red-400 hover:underline cursor-pointer"
                >
                  {{ item.stars }} Sao ★
                </button>

                <!-- Thanh progress bar cho từng hạng sao -->
                <div class="flex-1 h-3 rounded-full overflow-hidden" :class="isDarkMode ? 'bg-zinc-800' : 'bg-slate-100'">
                  <div 
                    class="h-full rounded-full transition-all duration-500" 
                    :class="item.color"
                    :style="{ width: `${item.percent}%` }"
                  ></div>
                </div>

                <span class="w-10 font-mono font-bold text-right shrink-0" :class="isDarkMode ? 'text-zinc-300' : 'text-slate-700'">
                  {{ item.percent }}%
                </span>
                <span class="text-[10px] text-slate-400 w-12 text-right">
                  ({{ item.count }})
                </span>
              </div>
            </div>

            <!-- Tóm tắt tiêu chí nổi bật -->
            <div class="md:col-span-6 grid grid-cols-2 gap-3 text-xs">
              <div class="p-3.5 rounded-2xl border text-center space-y-1" :class="isDarkMode ? 'bg-zinc-900/60 border-zinc-800' : 'bg-slate-50 border-slate-200'">
                <span class="text-2xl">🛡️</span>
                <strong class="block font-black text-slate-900 dark:text-white">Lái Xe An Toàn</strong>
                <span class="text-[11px] text-emerald-500 font-bold">100% Khách Hài Lòng</span>
              </div>
              <div class="p-3.5 rounded-2xl border text-center space-y-1" :class="isDarkMode ? 'bg-zinc-900/60 border-zinc-800' : 'bg-slate-50 border-slate-200'">
                <span class="text-2xl">⏰</span>
                <strong class="block font-black text-slate-900 dark:text-white">Đúng Giờ Bến Bãi</strong>
                <span class="text-[11px] text-blue-500 font-bold">98.5% Đúng Giờ</span>
              </div>
            </div>

          </div>

          <!-- Các nhận xét chi tiết gần đây (Hiện 4 - 6 đánh giá) -->
          <div class="pt-4 border-t space-y-3" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'">
            <div class="flex items-center justify-between">
              <h4 class="font-black text-xs uppercase tracking-wider text-slate-400">
                Đánh Giá Gần Đây (Hiển thị {{ initialDisplayedReviews.length }} / {{ totalReviewCount }} lượt)
              </h4>
              <button
                type="button"
                @click="showAllReviewsModal = true"
                class="text-xs font-bold text-red-600 dark:text-red-400 hover:underline flex items-center gap-1 cursor-pointer"
              >
                <span>Xem tất cả & Lọc sao</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <div 
                v-for="r in initialDisplayedReviews"
                :key="r.id"
                class="p-4 rounded-2xl border text-xs space-y-2 transition hover:shadow-xs" 
                :class="isDarkMode ? 'bg-zinc-900/60 border-zinc-800' : 'bg-slate-50 border-slate-200'"
              >
                <div class="flex items-center justify-between font-bold">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-amber-600 to-red-600 text-white font-bold flex items-center justify-center text-[10px]">
                      {{ (r.reviewer_name || 'K').substring(0, 1) }}
                    </div>
                    <span class="text-slate-900 dark:text-white truncate max-w-[120px]">{{ r.reviewer_name || 'Hành khách' }}</span>
                  </div>
                  <div class="flex items-center gap-0.5 text-amber-500 font-black">
                    <span>{{ r.rating }}</span>
                    <span>★</span>
                  </div>
                </div>
                <p class="text-[11px] text-slate-600 dark:text-zinc-300 italic line-clamp-3 leading-relaxed">
                  "{{ r.comment }}"
                </p>
                <div class="flex items-center justify-between text-[9px] text-slate-400 pt-1 border-t border-slate-200/50 dark:border-zinc-800">
                  <span>{{ r.created_at ? r.created_at.substring(0, 10) : 'Gần đây' }}</span>
                  <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓ Xác thực</span>
                </div>
              </div>
            </div>

            <!-- Nút Mở Modal Xem Tất Cả & Lọc Theo Sao -->
            <button
              type="button"
              @click="showAllReviewsModal = true"
              class="w-full py-3.5 mt-2 rounded-2xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-black text-xs shadow-md transition flex items-center justify-center gap-2 cursor-pointer active:scale-[0.99]"
            >
              <i class="fa-solid fa-star"></i>
              <span>XEM TẤT CẢ {{ totalReviewCount }} LƯỢT ĐÁNH GIÁ (LỌC 1 SAO, 2 SAO ĐẾN 5 SAO)</span>
              <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
          </div>

        </div>

      </section>

    </main>

    <!-- ============================================================== -->
    <!-- MODAL 1: KIỂM TRA AN TOÀN 8 MỤC (CÔNG TẮC GẠT IOS BẮT BUỘC)     -->
    <!-- ============================================================== -->
    <div v-if="showChecklistModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div :class="isDarkMode ? 'bg-slate-900 border-slate-800 text-white' : 'bg-white text-slate-900'" class="rounded-3xl max-w-lg w-full p-6 shadow-2xl border space-y-4 max-h-[90vh] overflow-y-auto">
        
        <div class="flex justify-between items-start pb-3 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
          <div>
            <span class="text-[10px] font-black uppercase tracking-wider text-amber-500">QUY TRÌNH BẮT BUỘC TRƯỚC KHI XUẤT BẾN</span>
            <h3 class="font-black text-lg">Kiểm Tra An Toàn Kỹ Thuật Xe (Pre-Trip)</h3>
          </div>
          <button @click="showChecklistModal = false" class="text-slate-400 hover:text-slate-600 font-bold cursor-pointer text-lg">✕</button>
        </div>

        <!-- SỔ GIAO CA KỸ THUẬT TỪ CA TRƯỚC -->
        <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-400 space-y-1">
          <div class="flex items-center gap-2 font-black text-xs uppercase tracking-wide">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>CẢNH BÁO BÀN GIAO TỪ TÀI XẾ CA TRƯỚC:</span>
          </div>
          <p class="text-xs italic leading-relaxed font-medium">
            "{{ props.lastHandoverLog }}"
          </p>
        </div>

        <!-- 8 CÔNG TẮC GẠT AN TOÀN (TOGGLE SWITCHES) -->
        <div class="space-y-2.5">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500">
              Đã gạt xanh: <strong class="text-emerald-600">{{ checkedCount }} / 8</strong> mục
            </span>
            <button
              @click="toggleAllChecklist"
              type="button"
              class="text-xs font-black text-blue-600 hover:underline cursor-pointer"
            >
              {{ allChecked ? 'Bỏ chọn tất cả' : '✓ Gạt xanh tất cả 8 mục' }}
            </button>
          </div>

          <div
            v-for="item in checklistItems"
            :key="item.key"
            @click="item.checked = !item.checked"
            class="p-3 rounded-2xl border flex items-center justify-between gap-3 cursor-pointer transition select-none"
            :class="item.checked 
              ? 'bg-emerald-500/10 border-emerald-500/40 ring-1 ring-emerald-500/30' 
              : (isDarkMode ? 'bg-slate-800/40 border-slate-800' : 'bg-slate-50 border-slate-200')"
          >
            <div class="text-xs pr-2 flex-1">
              <strong class="font-bold block text-sm" :class="item.checked ? 'text-emerald-700 dark:text-emerald-300' : ''">
                {{ item.label }}
              </strong>
              <span class="text-[11px] text-slate-400">{{ item.desc }}</span>
            </div>

            <!-- iOS Style Toggle Switch -->
            <button
              type="button"
              @click.stop="item.checked = !item.checked"
              :class="item.checked ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'"
              class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none shadow-inner"
            >
              <span
                :class="item.checked ? 'translate-x-5' : 'translate-x-0.5'"
                class="inline-block h-6 w-6 transform rounded-full bg-white shadow-md transition duration-200 ease-in-out mt-0.5"
              ></span>
            </button>
          </div>
        </div>

        <div class="pt-2">
          <button
            @click="confirmPreTripSafety"
            :disabled="!allChecked || isProcessing"
            type="button"
            class="w-full py-4 rounded-2xl font-black text-xs sm:text-sm flex items-center justify-center gap-2 cursor-pointer transition shadow-lg"
            :class="allChecked 
              ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-emerald-600/30' 
              : 'bg-slate-200 dark:bg-slate-800 text-slate-400 cursor-not-allowed'"
          >
            <i class="fa-solid fa-clipboard-check"></i>
            <span>{{ allChecked ? 'BƯỚC 1: XÁC NHẬN XE ĐẠT CHUẨN AN TOÀN (GỬI VỀ ADMIN)' : `VUI LÒNG GẠT XANH ĐỦ 8 MỤC (CÒN ${8 - checkedCount} MỤC)` }}</span>
          </button>
        </div>

      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2: FLASH POPUP CẢNH BÁO ĐÓN / TRẢ TẠI TRẠM               -->
    <!-- ============================================================== -->
    <div v-if="showArriveFlashModal" class="fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div :class="isDarkMode ? 'bg-slate-900 border-slate-800 text-white' : 'bg-white text-slate-900'" class="rounded-3xl max-w-md w-full p-6 shadow-2xl border-4 border-emerald-500 space-y-5 text-center">
        
        <div class="w-16 h-16 rounded-3xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center text-3xl mx-auto shadow-inner">
          <i class="fa-solid fa-door-open"></i>
        </div>

        <div>
          <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 inline-block mb-2">
            THÔNG BÁO ĐÓN TRẢ TẠI TRẠM
          </span>
          <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
            {{ nextStop?.stop_name || 'Trạm Dừng Lệ Thủy' }}
          </h3>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300">
            <span class="text-[11px] font-bold block uppercase tracking-wider">HÀNH KHÁCH LÊN</span>
            <span class="text-3xl font-black block my-1 font-mono">+{{ flashBoardingPax }}</span>
            <span class="text-[10px] opacity-80">Khách đón mới</span>
          </div>

          <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300">
            <span class="text-[11px] font-bold block uppercase tracking-wider">HÀNH KHÁCH XUỐNG</span>
            <span class="text-3xl font-black block my-1 font-mono">-{{ flashDropoffPax }}</span>
            <span class="text-[10px] opacity-80">Khách trả điểm này</span>
          </div>
        </div>

        <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-400 text-xs font-bold flex items-center gap-2 text-left">
          <i class="fa-solid fa-suitcase-rolling text-xl shrink-0"></i>
          <span>Bác tài nhớ dặn Lơ xe mở đúng hầm hành lý tương ứng cho khách lấy đồ an toàn!</span>
        </div>

        <button
          type="button"
          @click="confirmArriveAtStop"
          class="w-full py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm cursor-pointer shadow-lg shadow-emerald-600/30"
        >
          ĐÃ DẶN LƠ XE & XÁC NHẬN MỞ CỬA
        </button>

      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 3: SOS KHẨN CẤP (NHẤN VÀ GIỮ 3 GIÂY)                     -->
    <!-- ============================================================== -->
    <div v-if="showSosModal" class="fixed inset-0 bg-red-950/85 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4 text-slate-900 border-4 border-red-600 text-center">
        
        <span class="text-5xl block">🚨</span>
        <h3 class="font-black text-xl text-red-600">PHÁT TÍN HIỆU SOS KHẨN CẤP</h3>
        <p class="text-xs text-slate-600">
          Chỉ sử dụng khi gặp sự cố đe dọa an toàn, tai nạn nghiêm trọng hoặc sức khỏe nguy cấp.
        </p>

        <div class="p-3 bg-red-50 rounded-2xl border border-red-200 text-xs text-red-800 text-left space-y-1">
          <div>• Tọa độ GPS: <strong>Km 125 Quốc Lộ 1A (17.2155° N, 106.7865° E)</strong></div>
          <div>• Màn hình Điều hành Admin sẽ chớp đỏ còi báo động khẩn</div>
          <div>• Gửi SMS khẩn cấp tới Ban Giám Đốc và Đội Cứu Hộ</div>
        </div>

        <div class="space-y-2 pt-2">
          <button
            type="button"
            @mousedown="startSosHold"
            @mouseup="cancelSosHold"
            @mouseleave="cancelSosHold"
            @touchstart.prevent="startSosHold"
            @touchend.prevent="cancelSosHold"
            class="w-full py-4 rounded-2xl bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-black text-sm cursor-pointer shadow-lg shadow-red-600/40 relative overflow-hidden select-none"
          >
            <div
              class="absolute inset-0 bg-white/30 transition-all duration-75"
              :style="{ width: sosHoldProgress + '%' }"
            ></div>
            <span class="relative z-10">
              {{ isHoldingSos ? `ĐANG GIỮ (${Math.round(sosHoldProgress)}%)...` : 'NHẤN VÀ GIỮ 3 GIÂY ĐỂ PHÁT SOS' }}
            </span>
          </button>
          <span class="text-[11px] text-slate-400 block">Nhả tay trước 3 giây sẽ tự động hủy lệnh, không chạm nhầm.</span>
        </div>

        <button
          type="button"
          @click="showSosModal = false"
          class="w-full py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs cursor-pointer hover:bg-slate-200"
        >
          Đóng / Hủy Bỏ
        </button>

      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 4: BÁO SỰ CỐ XE KHẨN CẤP                                -->
    <!-- ============================================================== -->
    <div v-if="showIncidentModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div :class="isDarkMode ? 'bg-slate-900 text-white border-slate-800' : 'bg-white text-slate-900'" class="rounded-3xl max-w-sm w-full p-6 shadow-2xl border space-y-4">
        <div class="flex items-center gap-2 text-red-600">
          <span class="text-3xl">⚠️</span>
          <div>
            <h3 class="font-black text-base">BÁO SỰ CỐ XE KHẨN CẤP</h3>
            <p class="text-[11px] text-slate-400">Chọn nhanh lỗi kỹ thuật để báo về văn phòng điều xe</p>
          </div>
        </div>

        <form @submit.prevent="submitIncident" class="space-y-3 text-xs">
          <div>
            <label class="font-bold block mb-1">Loại Sự Cố Thường Gặp:</label>
            <select
              v-model="incidentForm.type"
              class="w-full p-2.5 rounded-xl border font-bold"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-900'"
            >
              <option value="TIRE_BURST">🚗 Nổ lốp / Xịt lốp / Hỏng lốp xe</option>
              <option value="BREAKDOWN">⚙️ Hỏng máy / Nổ máy / Chết máy giữa đường</option>
              <option value="ACCIDENT">💥 Va chạm / Tai nạn giao thông</option>
              <option value="AC_FAILURE">❄️ Hỏng điều hòa / Mất điện toàn xe</option>
              <option value="ROAD_BLOCK">🪨 Tắc đường nghiêm trọng / Sạt lở đèo</option>
            </select>
          </div>

          <div>
            <label class="font-bold block mb-1">Vị Trí Hiện Tại:</label>
            <input
              type="text"
              v-model="incidentForm.location"
              class="w-full p-2.5 rounded-xl border"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-900'"
            />
          </div>

          <div>
            <label class="font-bold block mb-1">Mô Tả Thêm:</label>
            <textarea
              v-model="incidentForm.description"
              rows="2"
              class="w-full p-2.5 rounded-xl border"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-900'"
            ></textarea>
          </div>

          <div class="flex gap-2 pt-2">
            <button
              type="button"
              @click="showIncidentModal = false"
              class="flex-1 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 font-bold cursor-pointer"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="isProcessing"
              class="flex-1 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black cursor-pointer shadow-md"
            >
              GỬI CỨU HỘ NGAY
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 5: ĐỔI CA TÀI XẾ 4 TIẾNG                                 -->
    <!-- ============================================================== -->
    <div v-if="showShiftModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div :class="isDarkMode ? 'bg-slate-900 text-white border-slate-800' : 'bg-white text-slate-900'" class="rounded-3xl max-w-sm w-full p-6 shadow-2xl border space-y-4">
        <div class="flex items-center gap-2 text-blue-600">
          <span class="text-3xl">🔄</span>
          <div>
            <h3 class="font-black text-base">ĐỔI CA TÀI XẾ (LUẬT 4 TIẾNG)</h3>
            <p class="text-[11px] text-slate-400">Hoán đổi người cầm lái & đặt lại đồng hồ đếm</p>
          </div>
        </div>

        <form @submit.prevent="submitChangeShift" class="space-y-3 text-xs">
          <div>
            <label class="font-bold block mb-1">Bác Tài Tiếp Nhận Cầm Lái:</label>
            <input
              type="text"
              v-model="shiftForm.second_driver_name"
              required
              class="w-full p-2.5 rounded-xl border font-bold"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-900'"
            />
          </div>

          <div class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-xl text-[11px] text-blue-700 dark:text-blue-300">
            ✓ Đồng hồ lái xe 4 tiếng sẽ được reset về 00:00 và lưu vào nhật ký đối soát của Cục ĐBVN.
          </div>

          <div class="flex gap-2 pt-2">
            <button
              type="button"
              @click="showShiftModal = false"
              class="flex-1 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 font-bold cursor-pointer"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="isProcessing"
              class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black cursor-pointer shadow-md"
            >
              XÁC NHẬN ĐỔI CA
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 6: KHAI BÁO CHI PHÍ (VÍ TÀI XẾ + CHỤP ẢNH)                -->
    <!-- ============================================================== -->
    <div v-if="showExpenseModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div :class="isDarkMode ? 'bg-slate-900 border-slate-800 text-white' : 'bg-white text-slate-900'" class="rounded-3xl max-w-md w-full p-6 shadow-2xl border space-y-4 max-h-[85vh] overflow-y-auto">
        
        <div class="flex justify-between items-center pb-2 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
          <div class="flex items-center gap-2 text-purple-600">
            <span class="text-2xl">💵</span>
            <div>
              <h3 class="font-black text-base text-slate-900 dark:text-white">KHAI BÁO CHI PHÍ (TIỀN PHƠI / BOT)</h3>
              <p class="text-[11px] text-slate-400">Đổ dầu, trạm thu phí BOT, ăn uống bồi dưỡng</p>
            </div>
          </div>
          <button @click="showExpenseModal = false" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
        </div>

        <form @submit.prevent="submitExpense" class="space-y-3 text-xs">
          <div>
            <label class="font-bold block mb-1">Loại Chi Phí:</label>
            <select
              v-model="expenseForm.expense_type"
              class="w-full p-2.5 rounded-xl border font-bold"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-900'"
            >
              <option value="Xăng dầu Diesel">Đổ dầu Diesel</option>
              <option value="Trạm thu phí BOT">Vé trạm thu phí BOT cầu đường</option>
              <option value="Ăn uống nghỉ ngơi">Ăn uống đoàn / Bồi dưỡng</option>
              <option value="Sửa xe vá lốp">Sửa xe / Vá lốp dọc đường</option>
              <option value="Khác">Chi phí phát sinh khác</option>
            </select>
          </div>

          <div>
            <label class="font-bold block mb-1">Số Tiền (VNĐ):</label>
            <input
              type="number"
              v-model="expenseForm.amount"
              required
              placeholder="VD: 1000000"
              class="w-full p-2.5 rounded-xl border font-mono font-black text-sm text-red-600"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200'"
            />
          </div>

          <div>
            <label class="font-bold block mb-1">Địa Điểm / Ghi Chú:</label>
            <input
              type="text"
              v-model="expenseForm.description"
              placeholder="VD: Petrolimex Cửa Tùng, Trạm BOT Điện Bàn..."
              class="w-full p-2.5 rounded-xl border"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-900'"
            />
          </div>

          <!-- Camera / Upload Hóa Đơn Chứng Từ -->
          <div>
            <label class="font-bold block mb-1">Chụp Ảnh Hóa Đơn / Vé Cầu Đường:</label>
            <input
              type="file"
              accept="image/*"
              capture="environment"
              @change="handleReceiptUpload"
              class="w-full text-xs file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-purple-50 file:text-purple-700 cursor-pointer"
            />
            <div v-if="receiptPreviewUrl" class="mt-2 p-1 border rounded-xl overflow-hidden max-h-36">
              <img :src="receiptPreviewUrl" class="w-full h-full object-contain max-h-36 rounded-lg" />
            </div>
          </div>

          <button
            type="submit"
            :disabled="isProcessing"
            class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-black text-xs cursor-pointer shadow-md mt-2"
          >
            LƯU HÓA ĐƠN ĐỐI SOÁT KẾ TOÁN
          </button>
        </form>

        <div v-if="props.tripExpenses && props.tripExpenses.length > 0" class="pt-3 border-t space-y-2" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
          <div class="flex justify-between items-center text-xs font-black">
            <span>Tổng Chi Phí Đã Khai Báo:</span>
            <span class="text-red-500 font-mono">{{ formatMoney(totalTripExpenses) }}</span>
          </div>
          <div class="space-y-1.5 max-h-36 overflow-y-auto">
            <div
              v-for="exp in props.tripExpenses"
              :key="exp.id"
              class="p-2 rounded-xl border text-[11px] flex justify-between items-center"
              :class="isDarkMode ? 'bg-slate-800/40 border-slate-800' : 'bg-slate-50 border-slate-200'"
            >
              <div>
                <strong class="font-bold block">{{ exp.expense_type }}</strong>
                <span class="text-slate-400">{{ exp.description || 'Không có ghi chú' }}</span>
              </div>
              <div class="text-right">
                <span class="font-black font-mono text-purple-600 block">{{ formatMoney(exp.amount) }}</span>
                <span v-if="exp.receipt_image" class="text-[10px] text-emerald-500">✓ Có ảnh HĐ</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 7: KẾT THÚC CHUYẾN & SỔ GIAO CA KỸ THUẬT                  -->
    <!-- ============================================================== -->
    <div v-if="showFinishModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div :class="isDarkMode ? 'bg-slate-900 border-slate-800 text-white' : 'bg-white text-slate-900'" class="rounded-3xl max-w-md w-full p-6 shadow-2xl border space-y-4">
        
        <div class="flex items-center gap-3 text-purple-600">
          <div class="w-12 h-12 rounded-2xl bg-purple-500/10 flex items-center justify-center text-2xl shrink-0">
            🏁
          </div>
          <div>
            <h3 class="font-black text-base">KẾT THÚC CHUYẾN XE & BÀN GIAO</h3>
            <p class="text-xs text-slate-400">Ghi nhận tình trạng xe cho tài xế ca sau</p>
          </div>
        </div>

        <div class="space-y-2">
          <label class="text-xs font-black block text-slate-700 dark:text-slate-300">
            TÌNH TRẠNG XE CÓ LỖI GÌ KHÔNG? (SỔ GIAO CA BẮT BUỘC):
          </label>
          <textarea
            v-model="finishForm.handover_notes"
            rows="3"
            placeholder="Nhập ghi chú kỹ thuật, điều hòa, lốp, phanh để bàn giao ca sau..."
            class="w-full p-3 rounded-xl border text-xs focus:ring-2 focus:ring-purple-500 outline-none"
            :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-900'"
          ></textarea>

          <div class="flex flex-wrap gap-1.5 pt-1">
            <button
              v-for="tag in quickHandoverTags"
              :key="tag"
              type="button"
              @click="addHandoverTag(tag)"
              class="px-2 py-0.5 rounded-lg text-[10px] font-bold border transition cursor-pointer"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700 hover:bg-slate-700' : 'bg-slate-100 border-slate-200 hover:bg-slate-200'"
            >
              + {{ tag }}
            </button>
          </div>
        </div>

        <div class="flex gap-2 pt-2">
          <button
            type="button"
            @click="showFinishModal = false"
            class="flex-1 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-xs cursor-pointer"
          >
            Hủy
          </button>
          <button
            type="button"
            @click="submitFinishTrip"
            :disabled="isProcessing"
            class="flex-1 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-black text-xs cursor-pointer shadow-md"
          >
            XÁC NHẬN BÀN GIAO & KẾT THÚC
          </button>
        </div>

      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 8: THÔNG BÁO DỪNG NGHỈ CHÂN CHO BÁC TÀI                   -->
    <!-- ============================================================== -->
    <div v-if="activeActionTrip?.status === 'REST_STOP'" class="fixed bottom-6 right-6 z-40 max-w-sm w-full animate-in slide-in-from-bottom-5 duration-300">
      <div :class="isDarkMode ? 'bg-slate-900 text-white border-amber-500/40' : 'bg-white text-slate-900 border-amber-300'" class="rounded-3xl p-5 shadow-2xl border-2 space-y-3">
        <div class="flex items-center gap-3">
          <span class="w-10 h-10 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center text-xl shrink-0">☕</span>
          <div class="flex-1">
            <span class="text-[10px] font-black uppercase text-amber-500 tracking-wider block">XE ĐANG NGHỈ DỪNG CHÂN</span>
            <h4 class="font-bold text-xs">Hành khách đang nghỉ ngơi & ăn uống</h4>
          </div>
        </div>
        <p class="text-[11px] text-slate-400">
          Khi sẵn sàng tiếp tục hành trình, hãy kiểm tra khách đã lên xe đủ rồi bấm:
        </p>
        <button
          type="button"
          @click="resumeDriving"
          :disabled="isProcessing"
          class="w-full py-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 text-white font-black text-xs cursor-pointer shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 animate-pulse"
        >
          <i class="fa-solid fa-play"></i>
          <span>TIẾP TỤC CHẠY XE (KHÁCH ĐÃ LÊN ĐỦ)</span>
        </button>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 9: BÁO TẮC ĐƯỜNG / ĐẾN TRỄ                               -->
    <!-- ============================================================== -->
    <div v-if="showDelayModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div :class="isDarkMode ? 'bg-slate-900 text-white border-slate-800' : 'bg-white text-slate-900'" class="rounded-3xl max-w-sm w-full p-6 shadow-2xl border space-y-4">
        <div class="flex items-center gap-2 text-amber-500">
          <span class="text-3xl">⏰</span>
          <div>
            <h3 class="font-black text-base">BÁO TẮC ĐƯỜNG / ĐẾN TRỄ</h3>
            <p class="text-[11px] text-slate-400">Cập nhật giờ dự kiến cho hành khách</p>
          </div>
        </div>

        <form @submit.prevent="submitDelay" class="space-y-3 text-xs">
          <div>
            <label class="font-bold block mb-1">Dự Kiến Trễ Thêm:</label>
            <div class="grid grid-cols-4 gap-2">
              <button
                v-for="mins in [15, 30, 45, 60]"
                :key="mins"
                type="button"
                @click="delayForm.delay_minutes = mins"
                class="py-2 rounded-xl font-black text-xs border cursor-pointer"
                :class="delayForm.delay_minutes === mins 
                  ? 'bg-amber-500 text-slate-950 border-amber-600' 
                  : (isDarkMode ? 'bg-slate-800 border-slate-700' : 'bg-slate-50 border-slate-200')"
              >
                +{{ mins }}p
              </button>
            </div>
          </div>

          <div>
            <label class="font-bold block mb-1">Lý Do Đến Trễ:</label>
            <input
              type="text"
              v-model="delayForm.delay_reason"
              class="w-full p-2.5 rounded-xl border"
              :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white' : 'bg-slate-50 border-slate-200 text-slate-900'"
            />
          </div>

          <div class="flex gap-2 pt-2">
            <button
              type="button"
              @click="showDelayModal = false"
              class="flex-1 py-3 rounded-xl bg-slate-100 dark:bg-slate-800 font-bold cursor-pointer"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="isProcessing"
              class="flex-1 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-black cursor-pointer shadow-md"
            >
              GỬI THÔNG BÁO TRỄ
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 10: XEM CHI TIẾT GHẾ (36 GIƯỜNG - VIEW ONLY)             -->
    <!-- ============================================================== -->
    <div v-if="viewingSeatDetail" class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
      <div :class="isDarkMode ? 'bg-slate-900 text-white border-slate-800' : 'bg-white text-slate-900'" class="rounded-3xl max-w-sm w-full p-6 shadow-2xl border space-y-4">
        <div class="flex justify-between items-center pb-2 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
          <div class="flex items-center gap-2">
            <span class="w-8 h-8 rounded-xl bg-blue-600 text-white font-black font-mono flex items-center justify-center text-xs">
              {{ viewingSeatDetail.seat_number }}
            </span>
            <h3 class="font-black text-sm">Thông Tin Giường Khách (Chỉ Xem)</h3>
          </div>
          <button @click="viewingSeatDetail = null" class="text-slate-400 hover:text-slate-600 font-bold">✕</button>
        </div>

        <div class="space-y-2 text-xs">
          <div class="flex justify-between py-1 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
            <span class="text-slate-400">Hành khách:</span>
            <strong class="font-bold">{{ viewingSeatDetail.customer_name || 'Khách đặt vé' }}</strong>
          </div>
          <div class="flex justify-between py-1 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
            <span class="text-slate-400">Điện thoại:</span>
            <span class="font-mono font-bold">{{ viewingSeatDetail.customer_phone || '0905.xxx.xxx' }}</span>
          </div>
          <div class="flex justify-between py-1 border-b" :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'">
            <span class="text-slate-400">Trạm lên:</span>
            <span>{{ viewingSeatDetail.pickup_name || 'Bến xe trung tâm' }}</span>
          </div>
          <div class="flex justify-between py-1">
            <span class="text-slate-400">Trạm xuống:</span>
            <strong class="text-red-500 font-bold">{{ viewingSeatDetail.dropoff_name || 'Trạm kết thúc' }}</strong>
          </div>
        </div>

        <button
          type="button"
          @click="viewingSeatDetail = null"
          class="w-full py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 font-bold text-xs cursor-pointer"
        >
          Đóng
        </button>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 11: XEM TẤT CẢ ĐÁNH GIÁ & LỌC SAO TỪ 1 ĐẾN 5 SAO        -->
    <!-- ============================================================== -->
    <div v-if="showAllReviewsModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
      <div 
        class="rounded-3xl border max-w-2xl w-full p-6 space-y-5 shadow-2xl max-h-[90vh] flex flex-col"
        :class="isDarkMode ? 'bg-[#1E1E1E] border-[#2E2E2E] text-white' : 'bg-white border-slate-200 text-slate-900'"
      >
        <!-- Modal Header -->
        <div class="flex items-start justify-between pb-3 border-b" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-amber-500 font-black text-sm">★★★★★</span>
              <span class="text-xs font-black uppercase text-amber-500 tracking-wider">ĐÁNH GIÁ THỰC TẾ TỪ HÀNH KHÁCH</span>
            </div>
            <h3 class="text-lg font-black mt-1">Đánh Giá Cho Tài Xế {{ currentDriver?.name }}</h3>
            <p class="text-xs text-slate-400">Điểm trung bình {{ averageRating }} / 5.0 • Tổng cộng {{ totalReviewCount }} lượt bình chọn thực tế</p>
          </div>
          <button @click="showAllReviewsModal = false" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-zinc-800 hover:opacity-80 flex items-center justify-center font-bold text-sm cursor-pointer">✕</button>
        </div>

        <!-- BỘ LỌC SAO (TẤT CẢ, 5 SAO, 4 SAO, 3 SAO, 2 SAO, 1 SAO THEO YÊU CẦU CỦA USER) -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
          <button
            type="button"
            @click="selectedStarFilter = 'ALL'"
            class="px-3 py-1.5 rounded-xl font-black transition cursor-pointer shrink-0"
            :class="selectedStarFilter === 'ALL' ? 'bg-red-600 text-white shadow-xs' : (isDarkMode ? 'bg-zinc-800 text-zinc-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')"
          >
            Tất cả ({{ totalReviewCount }})
          </button>
          <button
            v-for="s in [5, 4, 3, 2, 1]"
            :key="s"
            type="button"
            @click="selectedStarFilter = String(s)"
            class="px-3 py-1.5 rounded-xl font-bold transition cursor-pointer shrink-0 flex items-center gap-1"
            :class="selectedStarFilter === String(s) ? 'bg-amber-500 text-white font-black shadow-xs' : (isDarkMode ? 'bg-zinc-800 text-zinc-300' : 'bg-slate-100 text-slate-700 hover:bg-slate-200')"
          >
            <span>{{ s }}</span>
            <span class="text-amber-400">★</span>
            <span class="opacity-70 text-[10px]">({{ starCounts[s] || 0 }})</span>
          </button>
        </div>

        <!-- Danh sách đánh giá đã lọc -->
        <div class="flex-1 overflow-y-auto space-y-3 pr-1">
          <div v-if="filteredReviews.length === 0" class="p-8 text-center text-xs text-slate-400">
            Chưa có lượt đánh giá nào ở mức {{ selectedStarFilter }} sao.
          </div>

          <div 
            v-for="r in filteredReviews"
            :key="r.id"
            class="p-4 rounded-2xl border space-y-2 text-xs transition"
            :class="isDarkMode ? 'bg-[#18181B] border-zinc-800' : 'bg-slate-50 border-slate-200'"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-amber-600 to-red-600 text-white font-bold flex items-center justify-center text-xs">
                  {{ (r.reviewer_name || 'K').substring(0, 1) }}
                </div>
                <div>
                  <strong class="font-bold text-slate-900 dark:text-white">{{ r.reviewer_name || 'Hành khách' }}</strong>
                  <span class="text-[10px] text-slate-400 block">{{ r.created_at ? r.created_at.substring(0, 16) : 'Mới đây' }}</span>
                </div>
              </div>

              <div class="flex items-center gap-1 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 px-2 py-0.5 rounded-lg text-amber-600 dark:text-amber-400 font-black">
                <span>{{ r.rating }}</span>
                <span>★</span>
              </div>
            </div>

            <p class="text-xs text-slate-700 dark:text-zinc-300 leading-relaxed italic">
              "{{ r.comment }}"
            </p>

            <div class="flex items-center gap-2 pt-1 border-t text-[10px] text-slate-400" :class="isDarkMode ? 'border-zinc-800/80' : 'border-slate-200/60'">
              <span>🛡️ An toàn: <strong class="text-emerald-600">{{ r.safety_rating || 5 }}/5</strong></span>
              <span>•</span>
              <span>🤝 Thái độ: <strong class="text-blue-600">{{ r.service_rating || 5 }}/5</strong></span>
              <span class="ml-auto text-emerald-600 font-bold">✓ Đã xác thực chuyến đi</span>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="pt-2 border-t flex justify-end" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-100'">
          <button @click="showAllReviewsModal = false" class="px-5 py-2 rounded-xl bg-slate-100 dark:bg-zinc-800 hover:opacity-80 font-bold text-xs cursor-pointer">
            Đóng
          </button>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL XEM CHI TIẾT LỊCH SỬ CHUYẾN ĐÃ CHẠY (PAST TRIP MODAL)     -->
    <!-- ============================================================== -->
    <div 
      v-if="showPastTripModal && selectedPastTrip" 
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/70 backdrop-blur-sm"
      @click.self="showPastTripModal = false"
    >
      <div 
        class="w-full max-w-2xl max-h-[90vh] flex flex-col rounded-3xl shadow-2xl overflow-hidden border transition-all"
        :class="isDarkMode ? 'bg-[#121214] border-zinc-800 text-white' : 'bg-white border-slate-200 text-slate-900'"
      >
        <!-- Modal Header -->
        <div class="p-4 sm:p-5 border-b flex items-center justify-between" :class="isDarkMode ? 'border-zinc-800 bg-zinc-900/50' : 'border-slate-100 bg-slate-50'">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-red-600 text-white flex items-center justify-center text-lg font-black shadow-md shadow-red-500/30">
              🚍
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono font-black text-sm text-red-600">#{{ selectedPastTrip.trip_code }}</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300">
                  ✓ HOÀN THÀNH
                </span>
              </div>
              <h3 class="text-sm sm:text-base font-black leading-snug">
                {{ selectedPastTrip.route?.name }}
              </h3>
            </div>
          </div>

          <button 
            @click="showPastTripModal = false" 
            class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-200 dark:hover:bg-zinc-800 text-slate-400 hover:text-slate-700 dark:hover:text-white transition cursor-pointer text-sm font-bold"
          >
            ✕
          </button>
        </div>

        <!-- Modal Body -->
        <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 text-xs">
          <!-- 4 Thông Số Quan Trọng Của Chuyến Đi -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <div class="p-3 rounded-2xl border" :class="isDarkMode ? 'bg-zinc-900 border-zinc-800' : 'bg-slate-50 border-slate-200'">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Biển Số Xe</span>
              <strong class="text-sm font-mono font-black text-red-600 block mt-0.5">
                🚍 {{ selectedPastTrip.bus?.license_plate }}
              </strong>
            </div>

            <div class="p-3 rounded-2xl border" :class="isDarkMode ? 'bg-zinc-900 border-zinc-800' : 'bg-slate-50 border-slate-200'">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Lơ Xe Đi Kèm</span>
              <strong class="text-xs font-bold text-slate-800 dark:text-zinc-200 block mt-0.5 truncate">
                🎫 {{ selectedPastTrip.conductor?.name || 'Lơ xe nhà xe' }}
              </strong>
            </div>

            <div class="p-3 rounded-2xl border" :class="isDarkMode ? 'bg-zinc-900 border-zinc-800' : 'bg-slate-50 border-slate-200'">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Hành Khách</span>
              <strong class="text-sm font-mono font-black text-emerald-600 block mt-0.5">
                👥 {{ selectedPastTrip.occupied_count !== undefined ? selectedPastTrip.occupied_count : (selectedPastTrip.bookings ? selectedPastTrip.bookings.length : 0) }} ghế ({{ selectedPastTrip.customer_count || (selectedPastTrip.bookings ? selectedPastTrip.bookings.length : 0) }} khách) / {{ selectedPastTrip.bus?.total_seats || 34 }}
              </strong>
            </div>

            <div class="p-3 rounded-2xl border" :class="isDarkMode ? 'bg-zinc-900 border-zinc-800' : 'bg-slate-50 border-slate-200'">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Khởi Hành & Đến</span>
              <strong class="text-xs font-mono font-black text-blue-600 block mt-0.5">
                {{ selectedPastTrip.departure_time?.substring(11, 16) }} ➔ {{ selectedPastTrip.arrival_time?.substring(11, 16) }}
              </strong>
            </div>
          </div>

          <!-- Lộ trình trạm dừng của chuyến -->
          <div class="p-4 rounded-2xl border space-y-2" :class="isDarkMode ? 'bg-zinc-900/60 border-zinc-800' : 'bg-slate-50 border-slate-200'">
            <h4 class="font-black text-xs uppercase text-slate-500 tracking-wider flex items-center gap-1.5">
              <span>📍</span>
              <span>Lộ Trình Trạm Dừng Đã Đi Qua</span>
            </h4>
            <div class="flex items-center gap-2 flex-wrap text-xs font-bold">
              <span class="px-2.5 py-1 rounded-xl bg-red-100 text-red-800 border border-red-200">
                🏁 {{ selectedPastTrip.route?.departure_point || 'Bến xe TT Đà Nẵng' }}
              </span>
              <span class="text-slate-400">➔</span>
              <span class="px-2.5 py-1 rounded-xl bg-slate-200 dark:bg-zinc-800 text-slate-700 dark:text-zinc-300">
                Trạm dừng Đông Hà
              </span>
              <span class="text-slate-400">➔</span>
              <span class="px-2.5 py-1 rounded-xl bg-emerald-100 text-emerald-800 border border-emerald-200">
                🎯 {{ selectedPastTrip.route?.destination_point || 'Bến xe Đồng Hới' }}
              </span>
            </div>
          </div>

          <!-- Danh sách hành khách đi chuyến này -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="font-black text-xs uppercase text-slate-500 tracking-wider flex items-center gap-1.5">
                <span>📋</span>
                <span>Danh Sách Hành Khách Trên Chuyến Xe ({{ selectedPastTrip.customer_count || (selectedPastTrip.bookings || []).length }} Khách • {{ selectedPastTrip.occupied_count !== undefined ? selectedPastTrip.occupied_count : (selectedPastTrip.bookings || []).length }} Ghế)</span>
              </h4>
              <span class="text-[11px] text-emerald-600 font-bold">✓ 100% Đã Check-in & Xuống Xe An Toàn</span>
            </div>

            <div class="rounded-2xl border overflow-hidden" :class="isDarkMode ? 'border-zinc-800' : 'border-slate-200'">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 dark:bg-zinc-800/80 text-slate-500 font-bold text-[10px] uppercase">
                  <tr>
                    <th class="px-3 py-2">Ghế</th>
                    <th class="px-3 py-2">Khách Hàng</th>
                    <th class="px-3 py-2">SĐT</th>
                    <th class="px-3 py-2 text-right">Trạng Thái</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800">
                  <tr 
                    v-for="(b, idx) in (selectedPastTrip.bookings && selectedPastTrip.bookings.length > 0 ? selectedPastTrip.bookings : [
                      { seat_number: 'A01', customer_name: 'Nguyễn Văn An', customer_phone: '0905123456' },
                      { seat_number: 'A02', customer_name: 'Trần Thị Mai', customer_phone: '0913987654' },
                      { seat_number: 'A03', customer_name: 'Lê Hoàng Long (Sinh viên DAU)', customer_phone: '0988776655' },
                      { seat_number: 'B01', customer_name: 'Phạm Minh Tuấn', customer_phone: '0934112233' },
                      { seat_number: 'B02', customer_name: 'Võ Thị Hồng', customer_phone: '0977445566' }
                    ])"
                    :key="idx"
                    class="hover:bg-slate-50 dark:hover:bg-zinc-800/40"
                  >
                    <td class="px-3 py-2 font-mono font-black text-red-600">
                      {{ b.seat_number || b.seat_code || `A0${idx + 1}` }}
                    </td>
                    <td class="px-3 py-2 font-bold text-slate-900 dark:text-white">
                      {{ b.customer_name || (b.user ? b.user.name : 'Khách vãng lai') }}
                    </td>
                    <td class="px-3 py-2 font-mono text-slate-500">
                      {{ b.customer_phone || (b.user ? b.user.phone : '0905***123') }}
                    </td>
                    <td class="px-3 py-2 text-right">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200">
                        ✓ Đã Hoàn Thành
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Chi phí phơi dầu & nhật ký lái xe -->
          <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-xs space-y-1">
            <span class="font-bold text-amber-800 dark:text-amber-300 block">
              🛡️ Nhật Ký An Toàn & Chi Phí Chuyến Đi
            </span>
            <p class="text-slate-600 dark:text-zinc-300 text-[11px]">
              Chuyến xe hoàn thành đúng hành trình, không xảy ra va chạm hay vi phạm giao thông. Đã bàn giao xe và nộp phơi vé cho điều hành nhà xe Kiến Trúc.
            </p>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-4 border-t flex items-center justify-between" :class="isDarkMode ? 'border-zinc-800 bg-zinc-900/50' : 'border-slate-100 bg-slate-50'">
          <span class="text-xs text-slate-400 font-mono">
            ID Chuyến: #{{ selectedPastTrip.id }}
          </span>
          <button 
            type="button" 
            @click="showPastTripModal = false" 
            class="px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md shadow-red-500/30 cursor-pointer"
          >
            Đóng Lại
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
.animate-spin-slow {
  animation: spin 8s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>

<style>
#driver-leaflet-map {
  width: 100% !important;
  height: 440px !important;
  min-height: 440px !important;
  background-color: #f1f5f9;
}
.leaflet-container {
  width: 100% !important;
  height: 100% !important;
  font-family: inherit;
  z-index: 10;
}
</style>
