<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    routes: Array,
    flash: Object
})

const showAddRouteModal = ref(false)
const stopsList = ref(['', ''])

const form = useForm({
    name: '',
    origin: '',
    destination: '',
    distance_km: '',
    estimated_hours: '',
    stops: ['', ''],
    base_fare: ''
})

const addStopField = () => {
    stopsList.value.push('')
    form.stops.push('')
}

const removeStopField = (index) => {
    if (stopsList.value.length <= 2) return
    stopsList.value.splice(index, 1)
    form.stops.splice(index, 1)
}

const submitRoute = () => {
    form.post('/admin/routes', {
        onSuccess: () => {
            showAddRouteModal.value = false
            form.reset()
            stopsList.value = ['', '']
        }
    })
}

const formatCurrency = (val) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0)
</script>

<template>
    <Head title="Quản Lý Tuyến Đường - BusHub Admin" />

    <div class="min-h-screen bg-white text-slate-900 pb-20">
        <!-- Top App Bar -->
        <AdminHeader title="QUẢN LÝ TUYẾN ĐƯỜNG" subtitle="Thiết lập danh sách tuyến đường và lộ trình BusHub" />

        <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-500/20 border border-emerald-500/40 rounded-xl text-emerald-300 text-sm font-semibold flex items-center space-x-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- Header & Add Button -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-100 p-5 rounded-2xl border border-slate-300 shadow-xl">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Danh Sách Tuyến Đường Hoạt Động</h2>
                    <p class="text-xs text-slate-500">Mỗi tuyến chứa danh sách các trạm dừng cố định để tính giá vé và ghế theo từng chặng</p>
                </div>
                <button 
                    @click="showAddRouteModal = true"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center space-x-2">
                    <span class="text-lg">➕</span>
                    <span>THÊM TUYẾN ĐƯỜNG MỚI</span>
                </button>
            </div>

            <!-- Routes Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="r in routes" :key="r.id" class="bg-slate-100 border border-slate-300 rounded-2xl p-5 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-300 pb-3">
                        <div>
                            <span class="text-xs font-bold text-blue-500 uppercase tracking-wider">Mã Tuyến #{{ r.id }}</span>
                            <h3 class="text-lg font-bold text-slate-900">{{ r.name }}</h3>
                        </div>
                        <div class="text-right text-xs">
                            <span class="px-2 py-1 bg-white text-slate-700 rounded-lg font-semibold">{{ r.distance_km }} km (~{{ r.estimated_hours }}h)</span>
                        </div>
                    </div>

                    <!-- Ordered Stops List -->
                    <div>
                        <div class="text-xs font-bold text-slate-500 mb-2 uppercase">Danh sách trạm dừng theo thứ tự ({{ r.stops?.length || 0 }} trạm):</div>
                        <div class="space-y-2">
                            <div v-for="s in r.stops" :key="s.id" class="flex items-center justify-between bg-white/80 p-2.5 rounded-xl text-xs">
                                <div class="flex items-center space-x-2">
                                    <span class="w-5 h-5 rounded-full bg-slate-700 text-slate-800 flex items-center justify-center font-bold text-[11px]">
                                        {{ s.stop_order }}
                                    </span>
                                    <span class="font-bold text-slate-900">{{ s.stop_name }}</span>
                                </div>
                                <span class="text-slate-500">{{ s.distance_from_start_km }} km</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex items-center justify-between text-xs text-slate-500">
                        <span>Đã tạo: <strong>{{ r.trips_count }} chuyến xe</strong></span>
                        <Link :href="`/trips?origin=${r.origin}&destination=${r.destination}`" class="text-blue-500 hover:underline font-semibold">
                            Xem chuyến chạy »
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: THÊM TUYẾN ĐƯỜNG MỚI -->
        <div v-if="showAddRouteModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-100 border border-slate-300 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-3 border-b border-slate-300">
                    <h3 class="text-lg font-bold text-slate-900">🛣️ Thêm Tuyến Đường & Thiết Lập Trạm Dừng</h3>
                    <button @click="showAddRouteModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitRoute" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tên Tuyến Đường</label>
                        <input v-model="form.name" required type="text" placeholder="Tuyến Đà Nẵng ➔ Quy Nhơn" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Bến Đi (Gốc)</label>
                            <input v-model="form.origin" required type="text" placeholder="Bến xe TT Đà Nẵng" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Bến Đến (Đích)</label>
                            <input v-model="form.destination" required type="text" placeholder="Bến xe Quy Nhơn" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Cự ly (km)</label>
                            <input v-model="form.distance_km" required type="number" step="0.5" placeholder="300" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Thời gian chạy (giờ)</label>
                            <input v-model="form.estimated_hours" required type="number" step="0.5" placeholder="6" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Giá vé toàn tuyến (VNĐ)</label>
                            <input v-model="form.base_fare" required type="number" min="10000" placeholder="250000" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                        </div>
                    </div>

                    <!-- Dynamic Stops List -->
                    <div class="space-y-2 border-t border-slate-300 pt-3">
                        <div class="flex items-center justify-between">
                            <label class="font-bold text-blue-500">Các Trạm Dừng Theo Thứ Tự (Bến xe, Thị trấn, Ngã ba...):</label>
                            <button type="button" @click="addStopField" class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-slate-900 rounded text-[11px] font-bold">
                                + Thêm trạm
                            </button>
                        </div>

                        <div v-for="(stop, idx) in stopsList" :key="idx" class="flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-slate-700 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0">
                                {{ idx }}
                            </span>
                            <input 
                                v-model="form.stops[idx]" 
                                required 
                                type="text" 
                                :placeholder="idx === 0 ? 'Bến xuất phát' : (idx === stopsList.length - 1 ? 'Bến kết thúc' : 'Trạm dừng giữa đường...')" 
                                class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900 text-xs" />
                            <button 
                                v-if="stopsList.length > 2"
                                type="button" 
                                @click="removeStopField(idx)" 
                                class="text-rose-400 hover:text-rose-300 px-2 text-sm font-bold">
                                &times;
                            </button>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex justify-end space-x-3">
                        <button type="button" @click="showAddRouteModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg transition">
                            {{ form.processing ? 'Đang tạo...' : 'Lưu Tuyến Đường' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
