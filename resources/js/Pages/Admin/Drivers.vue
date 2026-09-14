<script setup>
import AdminHeader from '@/Components/AdminHeader.vue'
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const props = defineProps({
    drivers: Array,
    flash: Object
})

const showAddDriverModal = ref(false)

const driverForm = useForm({
    name: '',
    phone: '',
    license_number: '',
    license_class: 'Hạng E',
    years_experience: 5
})

const submitDriver = () => {
    driverForm.post('/admin/drivers', {
        onSuccess: () => {
            showAddDriverModal.value = false
            driverForm.reset()
        }
    })
}
</script>

<template>
    <Head title="Quản Lý Tài Xế - BusHub Admin" />

    <div class="min-h-screen bg-white text-slate-900 pb-20">
        <!-- Top App Bar -->
        <AdminHeader title="QUẢN LÝ TÀI XẾ" subtitle="Hồ sơ 8 Tài xế & Đánh giá năng lực lái xe đường dài" />

        <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="p-4 bg-emerald-500/20 border border-emerald-500/40 rounded-xl text-emerald-300 text-sm font-semibold flex items-center space-x-2">
                <span>✅</span>
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- Control Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-100 p-5 rounded-2xl border border-slate-300 shadow-xl">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Danh Sách Tài Xế Lái Xe</h2>
                    <p class="text-xs text-slate-500">Đội ngũ tài xế chuyên nghiệp đạt chuẩn an toàn BusHub Terminal</p>
                </div>
                <button 
                    @click="showAddDriverModal = true"
                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg shadow-blue-600/30 transition flex items-center space-x-2">
                    <span class="text-lg">➕</span>
                    <span>THÊM TÀI XẾ MỚI</span>
                </button>
            </div>

            <!-- Drivers Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="d in drivers" :key="d.id" class="bg-slate-100 border border-slate-300 rounded-2xl p-5 shadow-xl space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-full bg-slate-700 flex items-center justify-center font-bold text-lg text-blue-500">
                            {{ d.name.slice(0, 1) }}
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">{{ d.name }}</h3>
                            <p class="text-xs text-slate-500">SĐT: {{ d.phone }}</p>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs text-slate-700 border-t border-slate-300/60 pt-3">
                        <div>Bằng lái: <strong class="text-slate-900">{{ d.license_class }} (Số: {{ d.license_number }})</strong></div>
                        <div>Kinh nghiệm: <strong class="text-slate-900">{{ d.years_experience }} năm lái xe đường dài</strong></div>
                        <div class="flex items-center space-x-1">
                            <span>Đánh giá hành khách:</span>
                            <span class="font-bold text-amber-400">★ {{ d.avg_rating }} / 5.0</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex items-center justify-between text-xs">
                        <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-semibold">
                            {{ d.status }}
                        </span>
                        <span class="text-slate-500">ID: #{{ d.id }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: THÊM TÀI XẾ MỚI -->
        <div v-if="showAddDriverModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-slate-100 border border-slate-300 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-300">
                    <h3 class="text-lg font-bold text-slate-900">👨‍✈️ Thêm Hồ Sơ Tài Xế Mới</h3>
                    <button @click="showAddDriverModal = false" class="text-slate-500 hover:text-slate-900 text-xl">&times;</button>
                </div>

                <form @submit.prevent="submitDriver" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Họ Và Tên Tài Xế</label>
                        <input v-model="driverForm.name" required type="text" placeholder="Nguyễn Văn A" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Số Điện Thoại</label>
                        <input v-model="driverForm.phone" required type="text" placeholder="091xxxxxxx" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Số GPLX</label>
                            <input v-model="driverForm.license_number" required type="text" placeholder="E-12345678" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900 font-mono uppercase" />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 mb-1">Hạng Bằng Lái</label>
                            <select v-model="driverForm.license_class" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900">
                                <option value="Hạng E">Hạng E (Xe khách > 30 chỗ)</option>
                                <option value="Hạng D">Hạng D (Xe khách 16-30 chỗ)</option>
                                <option value="Hạng FC">Hạng FC</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Số Năm Kinh Nghiệm</label>
                        <input v-model="driverForm.years_experience" required type="number" min="1" max="40" class="w-full bg-white border border-slate-600 rounded-xl px-3 py-2 text-slate-900" />
                    </div>

                    <div class="pt-3 border-t border-slate-300 flex justify-end space-x-3">
                        <button type="button" @click="showAddDriverModal = false" class="px-4 py-2 bg-slate-700 text-slate-700 rounded-xl font-bold">Hủy</button>
                        <button type="submit" :disabled="driverForm.processing" class="px-5 py-2 bg-blue-600 hover:bg-blue-600 text-slate-900 font-bold rounded-xl shadow-lg transition">
                            {{ driverForm.processing ? 'Đang thêm...' : 'Lưu Hồ Sơ Tài Xế' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
