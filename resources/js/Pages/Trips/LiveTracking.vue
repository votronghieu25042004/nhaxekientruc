<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  trip: { type: Object, required: true },
  stops: { type: Array, default: () => [] }
})

const liveData = ref({
  current_lat: Number(props.trip.current_lat || 16.0544),
  current_lng: Number(props.trip.current_lng || 108.2022),
  current_speed: Number(props.trip.current_speed_kmh || 65),
  gps_updated_at: props.trip.gps_updated_at,
  current_stop_order: props.trip.current_stop_order || 1,
  status: props.trip.status || 'DEPARTED'
})

const isDarkMode = ref(false)
const mapLayerMode = ref('google')
const mapContainer = ref(null)
const isSharing = ref(false)
const shareCopied = ref(false)
let leafletMap = null
let busMarker = null
let busAccuracyCircle = null
let mapTileLayer = null
let routePolyline = null
let traveledPolyline = null
let stopMarkers = []
let pollTimer = null

const CITY_COORDINATES = {
  'đà nẵng': [16.0544, 108.2022],
  'huế': [16.4637, 107.5909],
  'thừa thiên': [16.4637, 107.5909],
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
  'buôn ma thuột': [12.6667, 108.0500],
  'đắk lắk': [12.6667, 108.0500],
  'sài gòn': [10.7769, 106.7009],
  'hà nội': [21.0285, 105.8542]
}

const getStopLatLng = (stopName, idx, total) => {
  if (!stopName) return [16.0544 + (idx * 0.22), 108.2022 - (idx * 0.12)]
  const lower = String(stopName).toLowerCase()
  for (const [key, coords] of Object.entries(CITY_COORDINATES)) {
    if (lower.includes(key)) {
      return [coords[0] + (idx * 0.015), coords[1] + (idx * 0.015)]
    }
  }
  return [16.0544 + (idx * 0.25), 108.2022 - (idx * 0.15)]
}

const updateTileLayer = () => {
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
    attribution = '&copy; CartoDB Dark'
  } else if (mapLayerMode.value === 'satellite') {
    tileUrl = 'https://mt{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}'
    subdomains = ['0', '1', '2', '3']
    attribution = '&copy; Google Satellite'
  }

  mapTileLayer = window.L.tileLayer(tileUrl, {
    attribution,
    maxZoom: 20,
    subdomains
  }).addTo(leafletMap)
}

const setMapMode = (mode) => {
  mapLayerMode.value = mode
  updateTileLayer()
}

// Lấy danh sách tọa độ trạm dừng
const stopCoordinates = computed(() => {
  const list = props.stops && props.stops.length > 0 ? props.stops : [
    { stop_order: 1, stop_name: props.trip.route?.origin || 'Bến xe xuất phát' },
    { stop_order: 2, stop_name: 'Trạm dừng chân Quốc Lộ 1A' },
    { stop_order: 3, stop_name: props.trip.route?.destination || 'Bến xe đích đến' }
  ]
  return list.map((s, idx) => ({
    ...s,
    latLng: getStopLatLng(s.stop_name, idx, list.length)
  }))
})

// Tải lộ trình đường bộ thực tế qua OSRM Driving API
const fetchRealRoadRoute = async () => {
  if (!leafletMap || stopCoordinates.value.length < 2) return
  const coordsList = stopCoordinates.value.map(s => s.latLng)
  
  // OSRM format: lon,lat;lon,lat;...
  const osrmWaypoints = coordsList.map(c => `${c[1].toFixed(5)},${c[0].toFixed(5)}`).join(';')
  const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${osrmWaypoints}?overview=full&geometries=geojson`

  try {
    const res = await fetch(osrmUrl)
    const data = await res.json()
    if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
      const geoPoints = data.routes[0].geometry.coordinates.map(pt => [pt[1], pt[0]])
      drawRoutePolyline(geoPoints)
      return
    }
  } catch (e) {
    console.warn('Không tải được OSRM, sử dụng tọa độ đường nối nội suy:', e)
  }

  // Fallback nếu OSRM timeout: nội suy đường cong
  drawRoutePolyline(coordsList)
}

const drawRoutePolyline = (points) => {
  if (!leafletMap || !window.L) return
  if (routePolyline) {
    try { leafletMap.removeLayer(routePolyline) } catch (e) {}
  }
  routePolyline = window.L.polyline(points, {
    color: '#2563eb',
    weight: 6,
    opacity: 0.85,
    smoothFactor: 1
  }).addTo(leafletMap)
}

const initMap = () => {
  const el = mapContainer.value || document.getElementById('customer-live-map')
  if (!el || !window.L) return

  const initialCenter = [liveData.value.current_lat, liveData.value.current_lng]
  leafletMap = window.L.map(el, {
    center: initialCenter,
    zoom: 13,
    zoomControl: true
  })

  updateTileLayer()

  // Icon trạm dừng
  stopCoordinates.value.forEach((s) => {
    const isCurrent = s.stop_order === liveData.value.current_stop_order
    const isPassed = s.stop_order < liveData.value.current_stop_order

    const stopIcon = window.L.divIcon({
      className: 'custom-stop-marker',
      html: `
        <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-black shadow-md border-2 text-white ${
          isPassed ? 'bg-slate-400 border-white' : (isCurrent ? 'bg-amber-500 border-white ring-4 ring-amber-300 animate-bounce' : 'bg-blue-600 border-white')
        }">
          ${s.stop_order}
        </div>
      `,
      iconSize: [28, 28],
      iconAnchor: [14, 14]
    })

    const marker = window.L.marker(s.latLng, { icon: stopIcon }).addTo(leafletMap)
    marker.bindPopup(`
      <div class="p-2 text-xs font-sans">
        <span class="text-[10px] font-bold text-slate-400 uppercase block">Trạm dừng ${s.stop_order}</span>
        <strong class="font-black text-sm text-slate-800 block">${s.stop_name}</strong>
        <span class="text-slate-500 block mt-1">${s.address || 'Quốc Lộ 1A'}</span>
      </div>
    `)
    stopMarkers.push(marker)
  })

  // Icon Xe Buýt đang chạy với hiệu ứng Radar phát sóng
  const busIcon = window.L.divIcon({
    className: 'custom-bus-live-icon',
    html: `
      <div class="relative flex items-center justify-center">
        <div class="absolute -inset-4 rounded-full bg-red-500/25 animate-ping"></div>
        <div class="absolute -inset-2 rounded-full bg-red-600/40 animate-pulse"></div>
        <div class="relative w-12 h-12 rounded-2xl bg-gradient-to-tr from-red-600 via-rose-600 to-red-700 border-2 border-white shadow-xl flex items-center justify-center text-white text-xl">
          🚍
        </div>
        <div class="absolute -bottom-5 whitespace-nowrap px-2 py-0.5 rounded-full bg-slate-900/90 text-[10px] font-black text-amber-300 shadow-md border border-slate-700">
          ${props.trip.bus?.license_plate || 'Xe Đang Chạy'}
        </div>
      </div>
    `,
    iconSize: [48, 48],
    iconAnchor: [24, 24]
  })

  busMarker = window.L.marker(initialCenter, { icon: busIcon }).addTo(leafletMap)
  busMarker.bindPopup(`
    <div class="p-2 text-xs font-sans">
      <div class="font-black text-sm text-red-600 flex items-center gap-1.5 mb-1">
        <span>🚍 ${props.trip.bus?.license_plate || 'Xe Khách'}</span>
      </div>
      <div>• Tuyến: <strong>${props.trip.route?.name || ''}</strong></div>
      <div>• Tốc độ: <strong>${liveData.value.current_speed} km/h</strong></div>
      <div>• Bác tài: <strong>${props.trip.driver?.name || 'Tài xế'}</strong></div>
      <div class="text-[10px] text-emerald-600 font-bold mt-1">● Tín hiệu GPS trực tiếp từ buồng lái</div>
    </div>
  `)

  busAccuracyCircle = window.L.circle(initialCenter, {
    radius: 40,
    color: '#ef4444',
    fillColor: '#f87171',
    fillOpacity: 0.15,
    weight: 1.5
  }).addTo(leafletMap)

  fetchRealRoadRoute()

  setTimeout(() => {
    if (leafletMap) leafletMap.invalidateSize()
  }, 200)
}

const updateBusPosition = (lat, lng, speed) => {
  if (!leafletMap || !busMarker) return
  const latLng = [lat, lng]
  busMarker.setLatLng(latLng)
  if (busAccuracyCircle) {
    busAccuracyCircle.setLatLng(latLng)
  }
}

const centerToBus = () => {
  if (leafletMap && busMarker) {
    leafletMap.setView(busMarker.getLatLng(), 15, { animate: true })
    busMarker.openPopup()
  }
}

const pollLiveGps = async () => {
  try {
    const res = await axios.get(`/trips/${props.trip.id}/live-tracking`, {
      headers: { 'Accept': 'application/json' }
    })
    if (res.data) {
      liveData.value.current_lat = Number(res.data.current_lat || liveData.value.current_lat)
      liveData.value.current_lng = Number(res.data.current_lng || liveData.value.current_lng)
      liveData.value.current_speed = Number(res.data.current_speed || 0)
      liveData.value.gps_updated_at = res.data.gps_updated_at
      liveData.value.current_stop_order = res.data.current_stop_order || liveData.value.current_stop_order
      liveData.value.status = res.data.status || liveData.value.status

      updateBusPosition(liveData.value.current_lat, liveData.value.current_lng, liveData.value.current_speed)
    }
  } catch (err) {
    // Silent catch during auto-poll
  }
}

const copyShareLink = () => {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(window.location.href)
    shareCopied.value = true
    setTimeout(() => { shareCopied.value = false }, 3000)
  }
}

onMounted(() => {
  // Load Leaflet dynamically if not loaded
  if (window.L) {
    initMap()
  } else {
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    document.head.appendChild(link)

    const script = document.createElement('script')
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
    script.onload = () => initMap()
    document.head.appendChild(script)
  }

  // Auto poll every 6 seconds
  pollTimer = setInterval(pollLiveGps, 6000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
  if (leafletMap) {
    try { leafletMap.remove() } catch (e) {}
  }
})
</script>

<template>
  <Head :title="`Theo Dõi Xe Trực Tuyến - ${trip.route?.name || 'Nhà Xe Kiến Trúc'}`" />

  <div :class="isDarkMode ? 'dark bg-slate-950 text-slate-100' : 'bg-slate-50 text-slate-900'" class="min-h-screen flex flex-col font-sans transition-colors duration-300">
    
    <!-- HEADER BAR -->
    <header class="bg-gradient-to-r from-[#8B0000] via-[#A50000] to-[#700000] text-white px-4 py-3 shadow-lg sticky top-0 z-40 border-b border-red-900/50">
      <div class="max-w-6xl mx-auto flex items-center justify-between gap-3">
        
        <div class="flex items-center gap-3">
          <Link href="/" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 transition text-white text-sm" title="Về trang chủ">
            <i class="fa-solid fa-arrow-left"></i>
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded-full bg-emerald-500 text-slate-950 font-black text-[10px] uppercase tracking-wider flex items-center gap-1 shadow-xs">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                LIVE GPS
              </span>
              <h1 class="font-black text-sm sm:text-base tracking-tight truncate">
                {{ trip.route?.name || 'Lộ Trình Xe Khách' }}
              </h1>
            </div>
            <p class="text-[11px] text-red-200/90 font-mono mt-0.5">
              Mã chuyến: <strong class="text-white">#{{ trip.trip_code || trip.id }}</strong> • Biển số: <strong class="text-amber-300">{{ trip.bus?.license_plate || '43B-012.34' }}</strong>
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button 
            type="button" 
            @click="copyShareLink"
            class="px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold text-xs flex items-center gap-1.5 transition cursor-pointer border border-white/20"
          >
            <i class="fa-solid" :class="shareCopied ? 'fa-check text-emerald-300' : 'fa-share-nodes'"></i>
            <span>{{ shareCopied ? 'Đã sao chép link!' : 'Chia sẻ' }}</span>
          </button>
          
          <button 
            type="button" 
            @click="isDarkMode = !isDarkMode"
            class="p-2 rounded-xl bg-white/10 hover:bg-white/20 transition text-white text-xs cursor-pointer"
            :title="isDarkMode ? 'Bật chế độ sáng' : 'Bật chế độ tối'"
          >
            <i class="fa-solid" :class="isDarkMode ? 'fa-sun text-amber-300' : 'fa-moon'"></i>
          </button>
        </div>

      </div>
    </header>

    <!-- MAIN BODY: MAP & DASHBOARD -->
    <main class="flex-1 max-w-6xl w-full mx-auto p-3 sm:p-5 flex flex-col gap-4">
      
      <!-- TOP STATUS CARDS -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        
        <div class="p-3.5 rounded-2xl border shadow-xs" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Vận Tốc Hiện Tại</span>
          <div class="flex items-baseline gap-1 mt-1">
            <span class="text-2xl sm:text-3xl font-black font-mono text-red-600 dark:text-red-400">{{ liveData.current_speed }}</span>
            <span class="text-xs font-bold text-slate-400">km/h</span>
          </div>
          <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">● Đang hành trình</span>
        </div>

        <div class="p-3.5 rounded-2xl border shadow-xs" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Trục Đường Đang Đi</span>
          <div class="text-base sm:text-lg font-black text-slate-800 dark:text-slate-100 mt-1 truncate">
            Quốc Lộ 1A / AH1
          </div>
          <span class="text-[10px] text-slate-400">Tốc độ chuẩn 60 - 90 km/h</span>
        </div>

        <div class="p-3.5 rounded-2xl border shadow-xs" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Bác Tài Cầm Lái</span>
          <div class="text-sm sm:text-base font-black text-slate-800 dark:text-slate-100 mt-1 truncate">
            {{ trip.driver?.name || 'Tài xế chính' }}
          </div>
          <a :href="`tel:${trip.driver?.phone || '0905123456'}`" class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 mt-0.5">
            <i class="fa-solid fa-phone text-[10px]"></i>
            <span>{{ trip.driver?.phone || '0905.123.456' }}</span>
          </a>
        </div>

        <div class="p-3.5 rounded-2xl border shadow-xs" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
          <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Lơ Xe Phụ Trách</span>
          <div class="text-sm sm:text-base font-black text-slate-800 dark:text-slate-100 mt-1 truncate">
            {{ trip.conductor?.name || 'Lơ xe hỗ trợ' }}
          </div>
          <a :href="`tel:${trip.conductor?.phone || '0905654321'}`" class="text-[11px] font-bold text-red-600 dark:text-red-400 hover:underline inline-flex items-center gap-1 mt-0.5">
            <i class="fa-solid fa-phone text-[10px]"></i>
            <span>{{ trip.conductor?.phone || '0905.654.321' }}</span>
          </a>
        </div>

      </div>

      <!-- MAP CONTAINER -->
      <div class="relative rounded-3xl overflow-hidden border shadow-xl flex-1 min-h-[420px] sm:min-h-[500px]" :class="isDarkMode ? 'border-slate-800' : 'border-slate-200'">
        
        <!-- Map Floating Controls Header -->
        <div class="absolute top-3 left-3 right-3 z-[1000] flex items-center justify-between gap-2 pointer-events-none">
          
          <div class="pointer-events-auto bg-slate-900/90 backdrop-blur-md text-white px-3 py-1.5 rounded-2xl shadow-lg border border-slate-700/60 text-xs flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-ping"></span>
            <span class="font-bold">Vị Trí Xe Di Chuyển Thời Gian Thực</span>
          </div>

          <div class="pointer-events-auto flex items-center gap-1.5 bg-white/95 dark:bg-slate-900/95 p-1 rounded-2xl shadow-lg border border-slate-200 dark:border-slate-800 text-xs">
            <button
              type="button"
              @click="setMapMode('google')"
              class="px-2.5 py-1 rounded-xl font-black transition cursor-pointer"
              :class="mapLayerMode === 'google' ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-black'"
            >
              Google
            </button>
            <button
              type="button"
              @click="setMapMode('satellite')"
              class="px-2.5 py-1 rounded-xl font-black transition cursor-pointer"
              :class="mapLayerMode === 'satellite' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-black'"
            >
              Vệ Tinh
            </button>
            <button
              type="button"
              @click="setMapMode('mapbox_dark')"
              class="px-2.5 py-1 rounded-xl font-black transition cursor-pointer"
              :class="mapLayerMode === 'mapbox_dark' ? 'bg-slate-950 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-black'"
            >
              Đêm
            </button>
            <button
              type="button"
              @click="centerToBus"
              class="px-2.5 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-bold hover:bg-slate-200 transition cursor-pointer"
              title="Căn tâm về vị trí xe"
            >
              <i class="fa-solid fa-crosshairs text-red-500 mr-1"></i> Căn Tâm
            </button>
          </div>

        </div>

        <!-- The Leaflet Map Canvas -->
        <div id="customer-live-map" ref="mapContainer" class="w-full h-full min-h-[420px] sm:min-h-[500px]"></div>

        <!-- Floating Bottom Road Status Banner -->
        <div class="absolute bottom-3 left-3 right-3 z-[1000] pointer-events-auto bg-slate-900/95 backdrop-blur-md text-white p-3 sm:p-4 rounded-2xl shadow-2xl border border-slate-700/80 flex flex-col sm:flex-row items-center justify-between gap-3">
          <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-lg shrink-0 shadow-md">
              🛣️
            </div>
            <div>
              <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Lộ Trình Xe Chạy</div>
              <p class="text-xs sm:text-sm font-bold text-slate-100">
                Đang chạy trên Quốc Lộ 1A • Tuyến <span class="text-amber-300">{{ trip.route?.origin }} ➔ {{ trip.route?.destination }}</span>
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <span class="text-[11px] text-slate-400 font-mono">
              Cập nhật GPS: <strong class="text-emerald-400">Vừa xong</strong>
            </span>
            <button 
              type="button" 
              @click="centerToBus"
              class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-black text-xs transition cursor-pointer shadow-md"
            >
              Xem Vị Trí Xe
            </button>
          </div>
        </div>

      </div>

      <!-- STOPS TIMELINE -->
      <div class="p-5 rounded-3xl border shadow-xs" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
        <h3 class="font-black text-base text-slate-900 dark:text-white flex items-center gap-2 mb-4">
          <i class="fa-solid fa-route text-red-600"></i>
          <span>DANH SÁCH CÁC TRẠM ĐÓN & TRẢ KHÁCH TRÊN TUYẾN</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div 
            v-for="s in stopCoordinates" 
            :key="s.id || s.stop_order"
            class="p-3.5 rounded-2xl border transition-all"
            :class="s.stop_order < liveData.current_stop_order ? 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800 opacity-60' : (s.stop_order === liveData.current_stop_order ? 'bg-amber-500/10 border-amber-500/40 dark:border-amber-500/30' : 'bg-white dark:bg-slate-800/80 border-slate-200 dark:border-slate-700')"
          >
            <div class="flex items-center justify-between text-xs mb-1">
              <span class="px-2 py-0.5 rounded-full font-black text-[10px]" :class="s.stop_order < liveData.current_stop_order ? 'bg-slate-200 text-slate-600' : (s.stop_order === liveData.current_stop_order ? 'bg-amber-500 text-slate-950 font-black animate-pulse' : 'bg-blue-100 text-blue-700')">
                {{ s.stop_order < liveData.current_stop_order ? 'Đã đi qua' : (s.stop_order === liveData.current_stop_order ? 'Đang hướng tới' : 'Trạm tiếp theo') }}
              </span>
              <span class="font-bold text-slate-400 text-[11px]">Trạm #{{ s.stop_order }}</span>
            </div>
            <strong class="font-black text-sm text-slate-900 dark:text-white block mt-1">{{ s.stop_name }}</strong>
            <span class="text-xs text-slate-500 block mt-0.5">{{ s.address || 'Trục đường Quốc Lộ 1A' }}</span>
          </div>
        </div>
      </div>

    </main>

  </div>
</template>

<style scoped>
:deep(.leaflet-popup-content-wrapper) {
  border-radius: 1rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
}
:deep(.custom-bus-live-icon), :deep(.custom-stop-marker) {
  background: transparent;
  border: none;
}
</style>
