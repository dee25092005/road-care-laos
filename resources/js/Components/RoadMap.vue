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

const locateUser = () => {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser");
        return;
    }

    isLocating.value = true;

    navigator.geolocation.getCurrentPosition((position) => {
        const { latitude, longitude } = position.coords;
        const latlng = { lat: latitude, lng: longitude };

        //move the map view to the user's location

        map.value.setView(latlng, 16);

        //update the tempMarker position
        if (tempMarker.value) {
            tempMarker.value.setLatLng(latlng);
        } else {
            tempMarker.value = L.marker(latlng, { draggable: true }).addTo(map.value);
            tempMarker.value.on('dragend', (e) => emit('map-click', e.target.getLatLng()));
        }

        //tell index.vue about the new position
        emit('map-click', latlng);
        isLocating.value = false;
    }, (errors) => {
        isLocating.value = false;
        console.error(error);

        // Professional error handling
        switch (error.code) {
            case error.PERMISSION_DENIED:
                alert("Location access denied. Please enable location permissions in your browser settings to use this feature.");
                break;
            case error.POSITION_UNAVAILABLE:
                alert("Location information is unavailable.");
                break;
            case error.TIMEOUT:
                alert("The request to get user location timed out.");
                break;
            default:
                alert("An unknown error occurred.");
                break;
        }

    }, { enableHighAccuracy: true, timeout: 10000 });
}

const renderMarkers = (reportList) => {
    markerGroup.clearLayers();
    reportList.forEach(report => {
        const markerInstance = L.marker([report.latitude, report.longitude]);

        // Generate Gallery HTML
        let imagesHtml = '';
        if (report.images && report.images.length > 0) {
            imagesHtml = `<div class="flex overflow-x-auto gap-2 mt-2 pb-2 snap-x scrollbar-hide">
                ${report.images.map(img => `<img src="/storage/${img.image_path}" class="rounded w-full h-32 object-cover shrink-0 snap-center border" />`).join('')}
            </div>`;
        }



        // Popup logic remains here, but it triggers an emit for editing
        markerInstance.bindPopup(`
                <div class="p-2 w-52">
                    <h3 class="font-bold border-b">${report.title}</h3>
                    ${imagesHtml}
                    <p class="text-sm mt-2">${report.description}</p>
                    <div class="flex gap-2 mt-3">
                    <button id="edit-btn-${report.id}" class="flex-1 bg-blue-500 text-white py-1 rounded text-xs hover:bg-blue-600">
                        Edit
                    </button>
                    <button id="delete-btn-${report.id}" class="flex-1 bg-red-500 text-white py-1 rounded text-xs hover:bg-red-600">
                        Delete
                    </button>
                    </div>
                </div>
            `);

        markerInstance.on('popupopen', () => {
            document.getElementById(`edit-btn-${report.id}`).onclick = () => {
                emit('edit-report', report);
            };

            document.getElementById(`delete-btn-${report.id}`).onclick = () => {
                if (confirm('Are you sure you want to delete this report?')) {
                    emit('delete-report', report.id);
                }
            };
        });

        map.value.on('click', (e) => {
            if (tempMarker.value) {
                tempMarker.value.setLatLng(e.latlng);
            } else {
                // Create the marker and add the drag listener
                tempMarker.value = L.marker(e.latlng, { draggable: true }).addTo(map.value);

                // IMPORTANT: Listen for when the user STOPS dragging
                tempMarker.value.on('dragend', (event) => {
                    const newPos = event.target.getLatLng();
                    emit('map-click', newPos); // Send new coords to Parent/Form
                });
            }
            emit('map-click', e.latlng);
        });


        markerInstance.addTo(markerGroup);
    });
};

onMounted(() => {
    map.value = L.map('map-container').setView(props.center, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map.value);
    markerGroup.addTo(map.value);

    map.value.on('click', (e) => {
        if (tempMarker.value) map.value.removeLayer(tempMarker.value);
        tempMarker.value = L.marker(e.latlng, { draggable: true }).addTo(map.value);
        emit('map-click', e.latlng);
    });

    renderMarkers(props.reports);
});

watch(() => props.reports, (newVal) => renderMarkers(newVal), { deep: true });

// Method to clear the temp marker from parent
defineExpose({
    clearTempMarker: () => { if (tempMarker.value) map.value.removeLayer(tempMarker.value); tempMarker.value = null; }
});
</script>

<template>
    <div class="relative">
        <div id="map-container" class="h-[500px] w-full rounded-lg border shadow-inner"></div>

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