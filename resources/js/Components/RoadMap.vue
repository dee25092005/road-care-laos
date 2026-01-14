<script setup>
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    reports: Array,
    center: { type: Array, default: () => [17.9757, 102.6331] }
});

const emit = defineEmits(['map-click', 'edit-report', 'delete-report']);

const map = ref(null);
const markerGroup = L.layerGroup();
const tempMarker = ref(null);
const isLocating = ref(false);
const accuracyCircle = ref(null);

// FIX 1: locateUser should only handle GPS, not UI strings
const locateUser = () => {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser");
        return;
    }

    isLocating.value = true;

    navigator.geolocation.getCurrentPosition((position) => {
        const { latitude, longitude, accuracy } = position.coords;
        const latlng = { lat: latitude, lng: longitude };

        map.value.setView(latlng, 13);
        if (accuracyCircle.value) {
            map.value.removeLayer(accuracyCircle.value);
        }

        accuracyCircle.value = L.circle(latlng, {
            radius: accuracy,
            color: '#3b82f6',
            fillColor: '#3b82f6',
            fillOpacity: 0.15,
            weight: 1
        }).addTo(map.value);

        if (accuracyCircle.value) {
            map.value.removeLayer(accuracyCircle.value);
        }

        if (tempMarker.value) {
            tempMarker.value.setLatLng(latlng);
        } else {
            tempMarker.value = L.marker(latlng, { draggable: true }).addTo(map.value);
            tempMarker.value.on('dragend', (e) => emit('map-click', e.target.getLatLng()));
        }

        emit('map-click', latlng);
        isLocating.value = false;
    }, (error) => {
        isLocating.value = false;

        if (error.code === error.PERMISSION_DENIED) {
            alert(
                "📍 Location Access Denied.\n\n" +
                "To use 'Locate Me', please click the lock icon in your browser address bar and change 'Location' to 'Allow'.\n\n" +
                error.message

            );
            console.log(error);
        } else {
            alert("Location error: " + error.message);
        }
    }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
}

const renderMarkers = (reportList) => {
    if (!map.value) return;

    markerGroup.clearLayers();

    reportList.forEach(report => {
        // FIX 2: Ensure coordinates are numbers
        const lat = Number(report.latitude);
        const lng = Number(report.longitude);

        if (isNaN(lat) || isNaN(lng)) return;

        const markerInstance = L.marker([lat, lng]);

        let imagesHtml = '';
        if (report.images && report.images.length > 0) {
            imagesHtml = `
    <div id="report-images-${report.id}" 
    class="scrollbar-hide mt-2"
         style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; width: 100%; max-height: 200px; overflow-y: auto;">
        ${report.images.map(img => `
            <div style="width: 100%; aspect-ratio: 16/9;">
                <img src="/storage/${img.image_path}" 
                     style="width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 4px; border: 1px solid #e5e7eb;" 
                     onerror="this.src='/images/placeholder.jpg'" />
            </div>
        `).join('')}
    </div>
            `;
        }

        // FIX 3: Define buttons INSIDE the loop so they have access to the 'report' object
        const editBtnHtml = report.can_edit
            ? `<button id="edit-btn-${report.id}" class="flex-1 bg-blue-500 text-white py-1 px-2 rounded text-xs hover:bg-blue-600">Edit/add Photo</button>`
            : '';

        const deleteBtnHtml = report.can_delete
            ? `<button id="delete-btn-${report.id}" class="flex-1 bg-red-500 text-white py-1 px-2 rounded text-xs hover:bg-red-600">Delete</button>`
            : '';

        markerInstance.bindPopup(`
                <div class="p-2 w-52">
                    <h3 class="font-bold border-b text-sm">${report.title}</h3>
                    ${imagesHtml}
                    <p class="text-xs mt-2 text-gray-600">${report.description}</p>
                    <div class="flex gap-2 mt-3">
                        ${editBtnHtml}
                        ${deleteBtnHtml}
                    </div>
                </div>
            `);

        // FIX 4: Correctly attach events once popup is opened
        markerInstance.on('popupopen', () => {
            const imgContainer = document.getElementById(`report-images-${report.id}`);
            if (imgContainer) {
                L.DomEvent.disableScrollPropagation(imgContainer);
                L.DomEvent.disableClickPropagation(imgContainer);
                imgContainer.addEventListener('touchstart', (e) => e.stopPropagation(), { passive: true });
                imgContainer.addEventListener('touchmove', (e) => e.stopPropagation(), { passive: true });
                imgContainer.addEventListener('touchend', (e) => e.stopPropagation(), { passive: true });
            }
            if (report.can_edit) {
                const el = document.getElementById(`edit-btn-${report.id}`);
                if (el) el.onclick = () => emit('edit-report', report);
            }
            if (report.can_delete) {
                const el = document.getElementById(`delete-btn-${report.id}`);
                if (el) el.onclick = () => {
                    if (confirm('Delete this report?')) emit('delete-report', report.id);
                };
            }
        });

        markerInstance.addTo(markerGroup);
    });
};

onMounted(() => {
    map.value = L.map('map-container').setView(props.center, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map.value);
    markerGroup.addTo(map.value);

    // FIX 5: Move map click listener OUTSIDE of renderMarkers loop
    map.value.on('click', (e) => {
        if (tempMarker.value) {
            tempMarker.value.setLatLng(e.latlng);
        } else {
            tempMarker.value = L.marker(e.latlng, { draggable: true }).addTo(map.value);
            tempMarker.value.on('dragend', (event) => {
                emit('map-click', event.target.getLatLng());
            });
        }
        emit('map-click', e.latlng);
    });

    renderMarkers(props.reports);
});

watch(() => props.reports, (newVal) => renderMarkers(newVal), { deep: true });

defineExpose({
    clearTempMarker: () => {
        if (tempMarker.value && map.value) map.value.removeLayer(tempMarker.value);
        tempMarker.value = null;
    }
});
</script>

<template>
    <div class="relative">
        <div id="map-container" class="h-[500px] w-full rounded-lg border shadow-inner z-0"></div>

        <button @click="locateUser" type="button"
            class="absolute bottom-5 right-5 z-[1000] bg-white p-3 rounded-full shadow-lg border hover:bg-gray-100 transition-all flex items-center justify-center"
            :class="{ 'animate-pulse bg-blue-50': isLocating }" title="Locate Me">
            <svg v-if="!isLocating" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span v-else class="text-xs font-bold text-blue-600">Finding you...</span>
        </button>
    </div>
</template>

<style>
/* 1. Force the popup width */
:deep(.leaflet-popup-content-wrapper) {
    width: 280px !important;
}

:deep(.leaflet-popup-content) {
    width: 250px !important;
    margin: 15px !important;
    overflow: hidden;
    /* Prevents images from leaking out */
}

/* 2. Custom utility to hide scrollbar for non-tailwind envs */
.scrollbar-hide {
    -ms-overflow-style: none;
    /* IE and Edge */
    scrollbar-width: none;
    /* Firefox */
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
    /* Chrome, Safari and Opera */
}

/* 3. Ensure the flex container doesn't wrap images to next line */
.leaflet-popup-content div.flex {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
}
</style>