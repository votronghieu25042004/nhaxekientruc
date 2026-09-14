<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'

const props = defineProps({
    buses: Array,
    flash: Object
})

const showAddBusModal = ref(false)
const showStatusModal = ref(false)
const selectedBus = ref(null)
const customAmenity = ref('')

const busForm = useForm({
    license_plate: '',
    bus_type: 'Giường nằm 34 chỗ',
    total_seats: 34,
    floors: 2,
    amenities: ['Wifi tốc độ cao', 'Điều hòa 2 chiều', 'Cổng sạc Type-C/USB', 'Chăn gối kháng khuẩn'],
    status: 'ACTIVE',
    current_km: 10000
})

const statusForm = useForm({
    status: 'ACTIVE',
    maintenance_notes: ''
})

const amenitiesOptions = ref([
    'Wifi tốc độ cao', 
    'Điều hòa 2 chiều', 
    'Cổng sạc Type-C/USB', 
    'Chăn gối kháng khuẩn', 
    'Màn hình LCD riêng', 
    'Massage tự động', 
    'Toilet khép kín',
    'Nước suối & Khăn lạnh',
    'Tủ lạnh mini',
    'Đèn đọc sách cá nhân'
])

const addCustomAmenity = () => {
    if (!customAmenity.value.trim()) return
    const val = customAmenity.value.trim()
    if (!amenitiesOptions.value.includes(val)) {
        amenitiesOptions.value.push(val)
    }
    if (!busForm.amenities.includes(val)) {
        busForm.amenities.push(val)
    }
    customAmenity.value = ''
}

const submitBus = () => {
    busForm.post('/admin/buses', {
        onSuccess: () => {
            showAddBusModal.value = false
            busForm.reset()
        }
    })
}

const openStatusModal = (bus) => {
    selectedBus.value = bus
    statusForm.status = bus.status
    statusForm.maintenance_notes = bus.maintenance_notes || ''
    showStatusModal.value = true
}

const submitStatus = () => {
    if (!selectedBus.value) return
    statusForm.post(`/admin/buses/${selectedBus.value.id}/toggle-status`, {
        onSuccess: () => {
            showStatusModal.value = false
        }
    })
}

const deleteBus = (busId) => {
    if (confirm('Bạn có chắc chắn muốn xóa xe này?')) {
        router.delete(`/admin/buses/${busId}`)
    }
}
</script>

<template>
    <Head title="Quản Lý Đội Xe - BusHub Admin" />

    <div class="min-h-screen bg-white text-slate-900 pb-20">
        <!-- Top App Bar -->
        <AdminHeader title="QUẢN LÝ ĐỘI XE" subtitle="Bảo dưỡng xe, Cấu hình ghế & Tiện nghi cao cấp" />

        <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-500/20 border border-emerald-500/40 rounded-xl text-emerald-300 text-sm font-semibold flex items-center space-x-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- Control Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-100 p-5 rounded-2xl border border-slate-300 shadow-xl">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Danh Sách Đội Xe BusHub</h2>
                    <p class="text-xs text-slate-500">Tổng cộng {{ buses?.length || 0 }} xe đang thuộc quản lý nhà xe</p>
                </div>
                <button 
                    @click="showAddBusModal = true"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center space-x-2">
                    <span class="text-lg">➕</span>
                    <span>THÊM XE MỚI</span>
                </button>
            </div>

            <!-- Buses Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="b in buses" :key="b.id" class="bg-slate-100 border border-slate-300 rounded-2xl p-5 shadow-xl space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-300 pb-3">
                            <div>
                                <span class="text-xs font-bold text-blue-500 uppercase tracking-wider">Biển Số Xe</span>
                                <h3 class="text-xl font-black text-slate-900 font-mono">{{ b.license_plate }}</h3>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase"
                                :class="{
                                    'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': b.status === 'ACTIVE' || b.status === 'RUNNING',
                                    'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': b.status === 'MAINTENANCE',
                                    'bg-rose-500/20 text-rose-400 border border-rose-500/30': b.status === 'BROKEN' || b.status === 'SUSPENDED',
                                }">
                                {{ b.status }}
                            </span>
                        </div>

                        <div class="space-y-2 mt-4 text-xs text-slate-700">
                            <div>Loại xe: <strong class="text-slate-900">{{ b.bus_type }}</strong></div>
                            <div>Số chỗ: <strong class="text-slate-900">{{ b.total_seats }} chỗ ({{ b.floors }} tầng)</strong></div>
                            <div>Số km đã chạy: <strong class="text-slate-900">{{ b.current_km ? b.current_km.toLocaleString() : 0 }} km</strong></div>
                            <div v-if="b.maintenance_notes" class="p-2 bg-yellow-500/10 border border-yellow-500/30 rounded-lg text-yellow-300 text-[11px]">
                                ⚠️ {{ b.maintenance_notes }}
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div class="mt-3">
                            <div class="text-[11px] font-bold text-slate-500 mb-1.5">Tiện nghi trang bị:</div>
                            <div class="flex flex-wrap gap-1">
                                <span v-for="a in (b.amenities || [])" :key="a" class="px-2 py-0.5 bg-white border border-slate-300 rounded text-[10px] text-slate-700">
                                    {{ a }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex items-center justify-between">
                        <button @click="openStatusModal(b)" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-slate-900 rounded-lg text-xs font-bold transition">
                            ⚙️ Đổi Trạng Thái / Bảo Dưỡng
                        </button>
                        <button @click="deleteBus(b.id)" class="text-rose-400 hover:text-rose-300 text-xs font-bold">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: THÊM XE MỚI VỚI TIỆN NGHI TÙY CHỈNH -->
        <div v-if="showAddBusModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-100 border border-slate-300 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-300">
                    <h3 class="text-lg font-bold text-slate-900">🚍 Thêm Xe Mới & Tiện Nghi Tùy Chỉnh</h3>
                    <button @click="showAddBusModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitBus" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Biển Số Xe</label>
                        <input v-model="busForm.license_plate" required type="text" placeholder="43B-123.45" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900 font-mono uppercase" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Loại Xe</label>
                            <select v-model="busForm.bus_type" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                                <option value="Giường nằm 34 chỗ">Giường nằm 34 chỗ</option>
                                <option value="Limousine 34 phòng VIP">Limousine 34 phòng VIP</option>
                                <option value="Cung điện 22 phòng">Cung điện 22 phòng</option>
                                <option value="Ghế ngồi cao cấp 29 chỗ">Ghế ngồi cao cấp 29 chỗ</option>
                                <option value="Xe 2 Tầng: Tầng dưới Ngồi - Tầng trên Nằm">Xe 2 Tầng: Tầng dưới Ngồi - Tầng trên Nằm</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Tổng Số Ghế</label>
                            <input v-model="busForm.total_seats" required type="number" min="10" max="60" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Số Tầng</label>
                            <select v-model="busForm.floors" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                                <option :value="1">1 tầng</option>
                                <option :value="2">2 tầng</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Trạng Thái Ban Đầu</label>
                            <select v-model="busForm.status" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                                <option value="ACTIVE">ACTIVE (Sẵn sàng chạy)</option>
                                <option value="MAINTENANCE">MAINTENANCE (Bảo dưỡng)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Custom Amenities Section -->
                    <div class="border-t border-slate-300 pt-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-blue-500">Danh Sách Tiện Nghi:</label>
                        </div>

                        <!-- Add Custom Amenity Input -->
                        <div class="flex items-center space-x-2">
                            <input 
                                v-model="customAmenity" 
                                type="text" 
                                placeholder="Gõ thêm tiện nghi mới (vd: Tủ lạnh mini, Mát-xa 8 điểm...)" 
                                @keydown.enter.prevent="addCustomAmenity"
                                class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900 text-xs" />
                            <button 
                                type="button" 
                                @click="addCustomAmenity" 
                                class="px-3 py-2 bg-blue-600 hover:bg-blue-600 text-slate-900 rounded-xl text-xs font-bold shrink-0">
                                + Thêm
                            </button>
                        </div>

                        <!-- Amenities Checkboxes -->
                        <div class="grid grid-cols-2 gap-2 max-h-36 overflow-y-auto pr-1">
                            <label v-for="opt in amenitiesOptions" :key="opt" class="flex items-center space-x-2 text-slate-700 bg-white/60 p-2 rounded-xl border border-slate-300/60 cursor-pointer">
                                <input type="checkbox" :value="opt" v-model="busForm.amenities" class="rounded bg-white border-slate-600 text-blue-600 focus:ring-blue-600" />
                                <span class="text-[11px]">{{ opt }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex justify-end space-x-3">
                        <button type="button" @click="showAddBusModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="busForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg transition">
                            {{ busForm.processing ? 'Đang thêm...' : 'Lưu Xe Mới' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL: ĐỔI TRẠNG THÁI / BẢO DƯỠNG -->
        <div v-if="showStatusModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-100 border border-slate-300 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-300">
                    <h3 class="text-lg font-bold text-slate-900">⚙️ Cập Nhật Xe {{ selectedBus?.license_plate }}</h3>
                    <button @click="showStatusModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitStatus" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Trạng Thái Vận Hành</label>
                        <select v-model="statusForm.status" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                            <option value="ACTIVE">ACTIVE (Sẵn sàng chạy)</option>
                            <option value="RUNNING">RUNNING (Đang trên đường)</option>
                            <option value="MAINTENANCE">MAINTENANCE (Đang bảo dưỡng định kỳ)</option>
                            <option value="BROKEN">BROKEN (Hỏng hóc, chờ sửa chữa)</option>
                            <option value="SUSPENDED">SUSPENDED (Tạm ngưng sử dụng)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Ghi Chú Bảo Dưỡng / Lý Do</label>
                        <textarea v-model="statusForm.maintenance_notes" rows="3" placeholder="Thay dầu nhớt máy, bảo dưỡng điều hòa tại Garage BusHub..." class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900"></textarea>
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex justify-end space-x-3">
                        <button type="button" @click="showStatusModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="statusForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg transition">
                            Cập Nhật Trạng Thái
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
