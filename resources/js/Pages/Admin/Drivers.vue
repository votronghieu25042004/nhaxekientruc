<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    drivers: Array,
    conductors: Array,
    flash: Object
})

const activePersonnelTab = ref('drivers') // 'drivers' (Bác Tài) hoặc 'conductors' (Lơ Xe)
const showAddDriverModal = ref(false)
const showAddConductorModal = ref(false)
const showEditDocsModal = ref(false)
const selectedPersonnel = ref(null)
const personnelType = ref('driver') // 'driver' or 'conductor'

const driverForm = useForm({
    name: '',
    phone: '',
    email: '',
    cccd_number: '',
    license_number: '',
    license_class: 'Hạng E (Xe khách > 30 chỗ)',
    years_experience: 5,
    license_expires_at: '',
})

const conductorForm = useForm({
    name: '',
    phone: '',
    email: '',
    role: 'conductor',
    password: 'password123',
})

const editDocsForm = useForm({
    name: '',
    phone: '',
    email: '',
    cccd_number: '',
    license_number: '',
    license_class: 'Hạng E (Xe khách > 30 chỗ)',
    years_experience: 5,
    license_expires_at: '',
    avatar_url: '',
    cccd_url: '',
    gplx_url: '',
    avatar_file: null,
    cccd_file: null,
    gplx_file: null,
})

const openEditDocsModal = (person, type = 'driver') => {
    selectedPersonnel.value = person
    personnelType.value = type
    editDocsForm.name = person.name || ''
    editDocsForm.phone = person.phone || ''
    editDocsForm.email = person.email || ''
    editDocsForm.cccd_number = person.cccd_number || ''
    editDocsForm.license_number = person.license_number || ''
    editDocsForm.license_class = person.license_class || 'Hạng E (Xe khách > 30 chỗ)'
    editDocsForm.years_experience = person.years_experience || 5
    editDocsForm.license_expires_at = person.license_expires_raw || ''
    editDocsForm.avatar_url = person.avatar || person.photo_path || ''
    editDocsForm.cccd_url = person.cccd_path || ''
    editDocsForm.gplx_url = person.gplx_path || ''
    editDocsForm.avatar_file = null
    editDocsForm.cccd_file = null
    editDocsForm.gplx_file = null
    showEditDocsModal.value = true
}

const handleFileUpload = (e, field) => {
    const file = e.target.files[0]
    if (file) {
        editDocsForm[field] = file
        const reader = new FileReader()
        reader.onload = (ev) => {
            if (field === 'avatar_file') editDocsForm.avatar_url = ev.target.result
            if (field === 'cccd_file') editDocsForm.cccd_url = ev.target.result
            if (field === 'gplx_file') editDocsForm.gplx_url = ev.target.result
        }
        reader.readAsDataURL(file)
    }
}

const submitEditDocs = () => {
    if (!selectedPersonnel.value) return
    const id = selectedPersonnel.value.id
    if (personnelType.value === 'driver') {
        editDocsForm.post(`/admin/drivers/${id}/upload-docs`, {
            preserveScroll: true,
            onSuccess: () => {
                showEditDocsModal.value = false
            }
        })
    } else {
        // Cập nhật lơ xe
        editDocsForm.post(`/admin/users/staff`, {
            preserveScroll: true,
            onSuccess: () => {
                showEditDocsModal.value = false
            }
        })
    }
}

const submitDriver = () => {
    driverForm.post('/admin/drivers', {
        onSuccess: () => {
            showAddDriverModal.value = false
            driverForm.reset()
        }
    })
}

const submitConductor = () => {
    conductorForm.post('/admin/users/staff', {
        onSuccess: () => {
            showAddConductorModal.value = false
            conductorForm.reset()
        }
    })
}

const expiringDriversCount = computed(() => {
    return props.drivers?.filter(d => d.is_expiring_soon || d.is_expired).length || 0
})

const formatCurrency = (val) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0)
}
</script>

<template>
    <Head title="Quản Lý Nhân Sự (Tài Xế & Lơ Xe) - nhaxekientruc Admin" />

    <div class="min-h-screen bg-slate-50 text-slate-800 pb-20">
        <!-- Top App Bar -->
        <AdminHeader title="QUẢN LÝ NHÂN SỰ VẬN TẢI (TÀI XẾ & LƠ XE)" subtitle="Quản lý hồ sơ bác tài, thời hạn bằng lái GPLX và theo dõi đội ngũ lơ xe thu tiền cọc" />

        <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-bold flex items-center space-x-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- Header & Toggle Switcher: [ Bác Tài ] <-> [ Lơ Xe ] -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 uppercase">
                            PERSONNEL & CREW MANAGEMENT
                        </span>
                        <h2 class="text-base font-black text-slate-900">Đội Ngũ Nhân Sự Tuyến Đường</h2>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Sử dụng nút Switch để chuyển qua lại giữa danh sách Bác Tài và Lơ Xe cầm tiền doanh thu</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- NÚT TOGGLE SWITCH: [ BÁC TÀI ] <-> [ LƠ XE ] THEO ĐÚNG YÊU CẦU -->
                    <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-bold text-slate-600">
                        <button
                            @click="activePersonnelTab = 'drivers'"
                            class="px-3.5 py-2 rounded-lg transition cursor-pointer flex items-center gap-1.5"
                            :class="activePersonnelTab === 'drivers' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/60 font-black' : 'hover:bg-slate-200/60 text-slate-600'"
                        >
                            <span>👨‍✈️</span>
                            <span>Bác Tài ({{ drivers?.length || 0 }})</span>
                            <span v-if="expiringDriversCount > 0" class="w-2 h-2 rounded-full bg-red-500 animate-pulse" title="Có bằng lái sắp hết hạn"></span>
                        </button>

                        <button
                            @click="activePersonnelTab = 'conductors'"
                            class="px-3.5 py-2 rounded-lg transition cursor-pointer flex items-center gap-1.5"
                            :class="activePersonnelTab === 'conductors' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200/60 font-black' : 'hover:bg-slate-200/60 text-slate-600'"
                        >
                            <span>🎫</span>
                            <span>Lơ Xe ({{ conductors?.length || 0 }})</span>
                        </button>
                    </div>

                    <!-- Nút Thêm Mới -->
                    <button 
                        v-if="activePersonnelTab === 'drivers'"
                        @click="showAddDriverModal = true"
                        class="px-4 py-2.5 bg-[#dc2626] hover:bg-red-700 text-white font-bold rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer shadow-xs whitespace-nowrap"
                    >
                        <span>➕</span>
                        <span>THÊM HỒ SƠ TÀI XẾ</span>
                    </button>

                    <button 
                        v-else
                        @click="showAddConductorModal = true"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer shadow-xs whitespace-nowrap"
                    >
                        <span>➕</span>
                        <span>CẤP TÀI KHOẢN LƠ XE</span>
                    </button>
                </div>
            </div>

            <!-- ==================== 1. CHẾ ĐỘ: BÁC TÀI (TÀI XẾ) ==================== -->
            <div v-if="activePersonnelTab === 'drivers'" class="space-y-4">
                <!-- Thẻ thống kê thời hạn bằng lái GPLX -->
                <div v-if="expiringDriversCount > 0" class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-base">⚠️</span>
                        <span><strong>CẢNH BÁO AN TOÀN GTĐB:</strong> Có <strong>{{ expiringDriversCount }} Bác tài</strong> sắp hết hạn hoặc đã quá hạn bằng lái (dưới 30 ngày). Cần đổi bằng gấp để không bị đình chỉ điều xe!</span>
                    </div>
                </div>

                <!-- Drivers Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div 
                        v-for="d in drivers" 
                        :key="d.id" 
                        class="bg-white border rounded-2xl p-5 transition flex flex-col justify-between space-y-3.5"
                        :class="[
                            d.is_expired ? 'border-red-400 bg-red-50/20' : (d.is_expiring_soon ? 'border-amber-300 bg-amber-50/20' : 'border-slate-200 hover:border-slate-300')
                        ]"
                    >
                        <!-- Driver Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-800 border border-slate-200 flex items-center justify-center font-black text-base shrink-0 overflow-hidden shadow-2xs">
                                    <img v-if="d.avatar || d.photo_path" :src="d.avatar || d.photo_path" class="w-full h-full object-cover" />
                                    <span v-else>{{ d.name.slice(0, 1) }}</span>
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-900 leading-snug">{{ d.name }}</h3>
                                    <p class="text-xs text-slate-500 font-mono">SĐT: {{ d.phone }}</p>
                                    <p class="text-[10px] text-slate-400 font-mono">CCCD: {{ d.cccd_number }}</p>
                                </div>
                            </div>

                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                ID #{{ d.id }}
                            </span>
                        </div>

                        <!-- CẢNH BÁO HẾT HẠN BẰNG LÁI (< 30 NGÀY HOẶC QUÁ HẠN) -->
                        <div class="pt-1">
                            <div 
                                v-if="d.is_expired"
                                class="p-2 rounded-xl bg-red-50 border border-red-200 text-red-700 text-[11px] font-bold flex items-center gap-1.5"
                            >
                                <span>🚨</span>
                                <span>GPLX ĐÃ QUÁ HẠN TỪ {{ d.license_expires_at }} (ĐÌNH CHỈ LÁI!)</span>
                            </div>

                            <div 
                                v-else-if="d.is_expiring_soon"
                                class="p-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-[11px] font-bold flex items-center gap-1.5"
                            >
                                <span>⚠️</span>
                                <span>GPLX HẾT HẠN SAU {{ d.days_left }} NGÀY (Hạn: {{ d.license_expires_at }})</span>
                            </div>

                            <div 
                                v-else
                                class="p-1.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-600 text-[11px] flex items-center justify-between"
                            >
                                <span class="text-slate-400 font-medium">Hạn GPLX:</span>
                                <span class="font-bold text-slate-800">{{ d.license_expires_at }} (Còn {{ d.days_left }} ngày)</span>
                            </div>
                        </div>

                        <!-- Chi tiết năng lực & GPLX -->
                        <div class="space-y-1.5 text-xs text-slate-600 border-t border-slate-100 pt-2.5">
                            <div class="flex justify-between">
                                <span class="text-slate-400">GPLX:</span>
                                <strong class="text-slate-800 font-mono">{{ d.license_class }} ({{ d.license_number }})</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Kinh nghiệm:</span>
                                <strong class="text-slate-800">{{ d.years_experience }} năm đường dài</strong>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-400">Đánh giá khách:</span>
                                <span class="font-bold text-amber-600 flex items-center gap-1">
                                    ★ {{ d.avg_rating }} / 5.0
                                </span>
                            </div>
                        </div>

                        <!-- Chi tiết hồ sơ CCCD & GPLX xem nhanh -->
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-50 p-1 flex flex-col items-center">
                                <span class="text-[9px] font-bold text-slate-500 uppercase mb-0.5">🪪 CCCD Gốc</span>
                                <img v-if="d.cccd_path" :src="d.cccd_path" class="w-full h-14 object-cover rounded-lg shadow-2xs border border-slate-200 cursor-pointer hover:scale-105 transition" @click="openEditDocsModal(d, 'driver')" />
                                <span v-else class="text-[10px] text-slate-400 py-2">Chưa có</span>
                            </div>
                            <div class="rounded-xl border border-slate-200 overflow-hidden bg-slate-50 p-1 flex flex-col items-center">
                                <span class="text-[9px] font-bold text-slate-500 uppercase mb-0.5">🚗 Bằng GPLX</span>
                                <img v-if="d.gplx_path" :src="d.gplx_path" class="w-full h-14 object-cover rounded-lg shadow-2xs border border-slate-200 cursor-pointer hover:scale-105 transition" @click="openEditDocsModal(d, 'driver')" />
                                <span v-else class="text-[10px] text-slate-400 py-2">Chưa có</span>
                            </div>
                        </div>

                        <!-- Footer Trạng Thái & Nút Cập Nhật Hồ Sơ GPLX -->
                        <div class="pt-2.5 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span 
                                    class="px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                    :class="d.status === 'ACTIVE' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600'"
                                >
                                    {{ d.status === 'ACTIVE' ? '✓ Đang trực chạy' : 'Nghỉ phép' }}
                                </span>
                                <span class="text-slate-400 text-[11px]">{{ d.total_trips }} lượt chạy</span>
                            </div>

                            <button
                                @click="openEditDocsModal(d, 'driver')"
                                class="w-full py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer shadow-xs active:scale-[0.99]"
                            >
                                <span>📷</span>
                                <span>Xem & Cập Nhật Hồ Sơ Giấy Tờ</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== 2. CHẾ ĐỘ: LƠ XE (PHỤ XE & CẦM TIỀN DOANH THU) ==================== -->
            <div v-if="activePersonnelTab === 'conductors'" class="space-y-4">
                <!-- Banner giải thích vai trò Lơ Xe -->
                <div class="p-3.5 bg-blue-50/70 border border-blue-200 rounded-xl text-xs text-blue-900 leading-relaxed">
                    <strong>🎫 Nghiệp vụ Lơ xe (Phụ xe):</strong> Lơ xe là người trực tiếp cầm tiền mặt thanh toán 70% còn lại, tiền vé đón dọc đường và kiểm tra mã QR soát vé. Hết mỗi chuyến xe, lơ xe bàn giao tiền mặt cho Kế toán quầy đối soát.
                </div>

                <!-- Conductors Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div 
                        v-for="c in conductors" 
                        :key="c.id" 
                        class="bg-white border border-slate-200 hover:border-slate-300 rounded-2xl p-5 transition flex flex-col justify-between space-y-3.5"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center font-black text-base shrink-0">
                                    🎫
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-900 leading-snug">{{ c.name }}</h3>
                                    <p class="text-xs text-slate-500 font-mono">SĐT: {{ c.phone }}</p>
                                </div>
                            </div>

                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Lơ Xe
                            </span>
                        </div>

                        <!-- Tiền Mặt Đang Cầm Trong Ca Trực -->
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tiền Thu Hộ Đang Giữ (Cọc & Dọc Đường)</span>
                            <div class="text-base font-black text-emerald-700 font-mono">{{ formatCurrency(c.cash_held) }}</div>
                            <div class="text-[10px] text-slate-500">Chờ chốt ca nộp về nhà xe</div>
                        </div>

                        <!-- Thông tin ca trực & xe phụ trách -->
                        <div class="space-y-1.5 text-xs text-slate-600 border-t border-slate-100 pt-2.5">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Xe phụ trách:</span>
                                <strong class="text-slate-800 font-mono">🚍 {{ c.assigned_bus }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Tuyến chạy:</span>
                                <strong class="text-slate-800 truncate max-w-[170px]">{{ c.active_route }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Ca trực:</span>
                                <span class="font-medium text-slate-800">{{ c.active_shift }}</span>
                            </div>
                        </div>

                        <!-- Footer Trạng thái -->
                        <div class="pt-2.5 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    ✓ Đang theo xe
                                </span>
                                <span class="text-slate-400 text-[11px]">{{ c.total_trips }} lượt chạy</span>
                            </div>

                            <button
                                @click="openEditDocsModal(c, 'conductor')"
                                class="w-full py-2 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer shadow-xs active:scale-[0.99]"
                            >
                                <span>📷</span>
                                <span>Hồ Sơ Ảnh & CCCD Nhân Viên</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MODAL: THÊM TÀI XẾ MỚI ==================== -->
        <div v-if="showAddDriverModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900">👨‍✈️ Thêm Hồ Sơ Bác Tài Mới</h3>
                    <button @click="showAddDriverModal = false" class="text-slate-400 hover:text-slate-700 text-lg">&times;</button>
                </div>

                <form @submit.prevent="submitDriver" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Họ Và Tên Tài Xế</label>
                        <input v-model="driverForm.name" required type="text" placeholder="Ví dụ: Nguyễn Văn Hùng" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-medium" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Số Điện Thoại</label>
                        <input v-model="driverForm.phone" required type="tel" placeholder="0905.123.456" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-mono" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Số GPLX</label>
                            <input v-model="driverForm.license_number" required type="text" placeholder="GPLX-43000008" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-mono uppercase" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Hạng Bằng Lái</label>
                            <select v-model="driverForm.license_class" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-medium">
                                <option value="Hạng E (Xe khách > 30 chỗ)">Hạng E (Xe > 30 chỗ)</option>
                                <option value="Hạng D (Xe khách 16-30 chỗ)">Hạng D (Xe 16-30 chỗ)</option>
                                <option value="Hạng FC (Xe container/rơ-mooc)">Hạng FC</option>
                            </select>
                        </div>
                    </div>

                    <!-- TRƯỜNG BẮT BUỘC: NGÀY HẾT HẠN BẰNG LÁI (GPLX) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Ngày Hết Hạn GPLX <span class="text-red-500">*</span>
                            </label>
                            <input 
                                v-model="driverForm.license_expires_at" 
                                required 
                                type="date" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold" 
                            />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Số Năm Kinh Nghiệm</label>
                            <input v-model.number="driverForm.years_experience" type="number" placeholder="5" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-mono" />
                        </div>
                    </div>

                    <div class="pt-2 flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2.5 bg-[#dc2626] hover:bg-red-700 text-white font-bold rounded-xl cursor-pointer">
                            LƯU HỒ SƠ TÀI XẾ
                        </button>
                        <button type="button" @click="showAddDriverModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL: CẤP TÀI KHOẢN LƠ XE MỚI ==================== -->
        <div v-if="showAddConductorModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-base font-black text-slate-900">🎫 Cấp Tài Khoản Lơ Xe / Phụ Xe</h3>
                    <button @click="showAddConductorModal = false" class="text-slate-400 hover:text-slate-700 text-lg">&times;</button>
                </div>

                <form @submit.prevent="submitConductor" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Họ Và Tên Lơ Xe</label>
                        <input v-model="conductorForm.name" required type="text" placeholder="Nguyễn Văn Phúc" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-medium" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Số Điện Thoại (Dùng Đăng Nhập App)</label>
                        <input v-model="conductorForm.phone" required type="tel" placeholder="0905.221.133" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-mono" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Email</label>
                        <input v-model="conductorForm.email" required type="email" placeholder="phuc.conductor@nhaxekientruc.vn" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Mật Khẩu Cấp Ban Đầu</label>
                        <input v-model="conductorForm.password" required type="password" placeholder="Mặc định: password123" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div class="p-3 bg-blue-50/70 rounded-xl border border-blue-200/60 text-[11px] text-blue-900">
                        📱 Sau khi tạo, lơ xe chỉ cần mở link <code>/login</code> trên điện thoại, đăng nhập bằng SĐT sẽ tự động chuyển vào cổng Lơ xe để quét vé và thu tiền.
                    </div>

                    <div class="pt-2 flex items-center gap-2">
                        <button type="submit" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl cursor-pointer">
                            XÁC NHẬN CẤP TÀI KHOẢN
                        </button>
                        <button type="button" @click="showAddConductorModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ==================== MODAL: CẬP NHẬT GPLX & TẢI ẢNH CCCD / GPLX ==================== -->
        <div v-if="showEditDocsModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full p-6 shadow-2xl space-y-4 my-8">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-2xl bg-red-100 text-red-700 font-black flex items-center justify-center text-lg">
                            📷
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900">
                                Cập Nhật Hồ Sơ Giấy Tờ & GPLX
                            </h3>
                            <p class="text-xs text-slate-500">{{ selectedPersonnel?.name }} ({{ personnelType === 'driver' ? 'Bác Tài' : 'Lơ Xe' }})</p>
                        </div>
                    </div>
                    <button @click="showEditDocsModal = false" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 font-bold flex items-center justify-center cursor-pointer">&times;</button>
                </div>

                <form @submit.prevent="submitEditDocs" class="space-y-4 text-xs">
                    <!-- Ảnh chân dung Avatar -->
                    <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200/80 flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-white border border-slate-300 overflow-hidden shrink-0 flex items-center justify-center shadow-xs">
                            <img v-if="editDocsForm.avatar_url" :src="editDocsForm.avatar_url" class="w-full h-full object-cover" />
                            <span v-else class="text-2xl">👤</span>
                        </div>
                        <div class="flex-1 space-y-1.5">
                            <label class="block font-bold text-slate-800">Ảnh Chân Dung (Avatar)</label>
                            <input 
                                type="file" 
                                accept="image/*" 
                                @change="handleFileUpload($event, 'avatar_file')"
                                class="text-xs text-slate-600 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-red-600 file:text-white cursor-pointer"
                            />
                            <input 
                                v-model="editDocsForm.avatar_url" 
                                type="text" 
                                placeholder="Hoặc dán URL ảnh chân dung..." 
                                class="w-full bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-slate-800 text-[11px]" 
                            />
                        </div>
                    </div>

                    <!-- Thông tin cơ bản -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Số CCCD / CMND <span class="text-red-500">*</span></label>
                            <input v-model="editDocsForm.cccd_number" required type="text" placeholder="048092000xxx" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-mono font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Số Điện Thoại</label>
                            <input v-model="editDocsForm.phone" required type="tel" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-mono font-bold" />
                        </div>
                    </div>

                    <!-- Mục GPLX (Dành cho tài xế) -->
                    <div v-if="personnelType === 'driver'" class="p-3.5 bg-amber-50/50 rounded-2xl border border-amber-200/80 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-amber-900 uppercase text-[11px] tracking-wider">Thông Tin Giấy Phép Lái Xe (GPLX)</span>
                            <span class="text-[10px] text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full font-bold">Quy định Tổng Cục Đường Bộ</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Số Bằng Lái (GPLX)</label>
                                <input v-model="editDocsForm.license_number" required type="text" placeholder="GPLX-43000001" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-mono font-bold uppercase" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Hạng Bằng Lái</label>
                                <select v-model="editDocsForm.license_class" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold">
                                    <option value="Hạng E (Xe khách > 30 chỗ)">Hạng E (Xe khách > 30 chỗ)</option>
                                    <option value="Hạng D (Xe khách 16-30 chỗ)">Hạng D (Xe khách 16-30 chỗ)</option>
                                    <option value="Hạng FC (Xe container/rơ-mooc)">Hạng FC</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Ngày Hết Hạn GPLX <span class="text-red-500">*</span></label>
                                <input v-model="editDocsForm.license_expires_at" required type="date" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold" />
                            </div>
                            <div>
                                <label class="block font-bold text-slate-700 mb-1">Số Năm Kinh Nghiệm</label>
                                <input v-model.number="editDocsForm.years_experience" type="number" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-900 font-bold" />
                            </div>
                        </div>
                    </div>

                    <!-- Upload ảnh CCCD và ảnh GPLX -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Thẻ ảnh CCCD -->
                        <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                            <label class="block font-bold text-slate-800">Ảnh CCCD (Mặt Trước / Sau)</label>
                            <div class="h-24 bg-white border border-slate-200 rounded-xl overflow-hidden flex items-center justify-center shadow-inner">
                                <img v-if="editDocsForm.cccd_url" :src="editDocsForm.cccd_url" class="w-full h-full object-cover" />
                                <div v-else class="text-center text-slate-400">
                                    <span class="text-2xl block">🪪</span>
                                    <span class="text-[10px]">Chưa tải ảnh CCCD</span>
                                </div>
                            </div>
                            <input 
                                type="file" 
                                accept="image/*" 
                                @change="handleFileUpload($event, 'cccd_file')" 
                                class="text-[11px] text-slate-600 file:mr-1 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-slate-800 file:text-white cursor-pointer w-full"
                            />
                        </div>

                        <!-- Thẻ ảnh GPLX -->
                        <div v-if="personnelType === 'driver'" class="p-3 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                            <label class="block font-bold text-slate-800">Ảnh Bằng Lái (GPLX)</label>
                            <div class="h-24 bg-white border border-slate-200 rounded-xl overflow-hidden flex items-center justify-center shadow-inner">
                                <img v-if="editDocsForm.gplx_url" :src="editDocsForm.gplx_url" class="w-full h-full object-cover" />
                                <div v-else class="text-center text-slate-400">
                                    <span class="text-2xl block">🚗</span>
                                    <span class="text-[10px]">Chưa tải ảnh GPLX</span>
                                </div>
                            </div>
                            <input 
                                type="file" 
                                accept="image/*" 
                                @change="handleFileUpload($event, 'gplx_file')" 
                                class="text-[11px] text-slate-600 file:mr-1 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-slate-800 file:text-white cursor-pointer w-full"
                            />
                        </div>
                    </div>

                    <div class="pt-2 flex items-center gap-2">
                        <button type="submit" class="flex-1 py-3 bg-[#dc2626] hover:bg-red-700 text-white font-black rounded-xl cursor-pointer shadow-md transition active:scale-[0.99]">
                            ✓ LƯU HỒ SƠ & GIẤY TỜ XÁC THỰC
                        </button>
                        <button type="button" @click="showEditDocsModal = false" class="px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer transition">
                            Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
