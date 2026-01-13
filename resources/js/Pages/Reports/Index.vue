<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    reports: Array,
});

const page = usePage();
const flashMessage = ref(null);
const map = ref(null);
const marker = ref(null); // This is for the "Click to add" temp marker
const fileInput = ref(null);
const previewUrl = ref(null);
const isEditing = ref(false);
const selectedReportId = ref(null);

// 1. Define the group OUTSIDE everything so it's a constant reference
const markerGroup = L.layerGroup();

const form = useForm({
    title: '',
    description: '',
    latitude: null,
    longitude: null,
    image: null,
});

// Flash message logic
let flashTimeout;
watch(() => page.props.flash?.message, (newVal) => {
    flashMessage.value = newVal;
    if (newVal) {
        clearTimeout(flashTimeout);
        flashTimeout = setTimeout(() => { flashMessage.value = null }, 4000);
    }
}, { immediate: true });

// Image Preview
watch(() => form.image, (newFile) => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    if (newFile) previewUrl.value = URL.createObjectURL(newFile);
});

// 2. THE RENDER FUNCTION (Unified name)
const renderMarkers = (reportList) => {
    if (!map.value) return;

    markerGroup.clearLayers(); // Clear old pins
    markerGroup.addTo(map.value); // Ensure group is on map

    reportList.forEach(report => {
        const imagesHtml = report.images && report.images.length > 0
            ? report.images.map(img => `
                    <div class="snap-center shrink-0 w-full">
                        <img src="/storage/${img.image_path}" 
                             class="rounded shadow-sm w-full h-32 object-cover border"
                             onerror="this.src='https://via.placeholder.com/150?text=No+Image'"/>
                    </div>
                `).join('')
            : `<img src="https://via.placeholder.com/150?text=No+Image" class="w-full h-32 object-cover rounded"/>`;

        const markerInstance = L.marker([report.latitude, report.longitude]);

        markerInstance.on('popupopen', () => {
            isEditing.value = true;
            selectedReportId.value = report.id;
            form.title = report.title;
            form.description = report.description;
            form.latitude = report.latitude;
            form.longitude = report.longitude;
        });

        markerInstance.bindPopup(`
                <div class="p-2 w-52">
                    <h3 class="font-bold text-lg border-b mb-2">${report.title}</h3>
                    <div class="relative mt-2">
                        <div class="flex overflow-x-auto snap-x snap-mandatory scrollbar-hide gap-1">
                            ${imagesHtml}
                        </div>
                        <div class="text-[10px] text-gray-400 mt-1 text-center">
                            ${report.images?.length > 1 ? '← Swipe to see more (' + report.images.length + ') →' : ''}
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">${report.description}</p>
                    <button onclick="window.dispatchEvent(new CustomEvent('edit-report', {detail: ${report.id}}))" 
                            class="mt-3 w-full bg-blue-500 text-white text-xs py-1 rounded">
                        + Add Image
                    </button>
                </div>
            `);

        markerInstance.addTo(markerGroup);
    });
};

const resetFormMode = () => {
    isEditing.value = false;
    selectedReportId.value = null;
    form.reset();
    if (marker.value) {
        map.value.removeLayer(marker.value);
        marker.value = null;
    }

};

const submit = () => {
    const url = isEditing.value ? route('reports.update', selectedReportId.value) : route('reports.store');

    form.transform((data) => ({
        ...data,
        _method: isEditing.value ? 'put' : 'post',
    })).post(url, {
        forceFormData: true,
        onSuccess: () => {
            // Force flash message to show
            flashMessage.value = page.props.flash.message;
            form.reset();
            isEditing.value = false;
            selectedReportId.value = null;
            if (fileInput.value) fileInput.value.value = null;
            if (marker.value) {
                map.value.removeLayer(marker.value);
                marker.value = null;
            }
        },
    });
};

// 3. The Watcher - This handles both initial load and updates
watch(() => props.reports, (newReports) => {
    renderMarkers(newReports);
}, { deep: true });

onMounted(() => {
    map.value = L.map('map').setView([17.9757, 102.6331], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map.value);

    map.value.on('click', (e) => {
        if (isEditing.value) resetFormMode();
        const { lat, lng } = e.latlng;
        form.latitude = lat;
        form.longitude = lng;
        if (marker.value) {
            marker.value.setLatLng(e.latlng);
        } else {
            marker.value = L.marker(e.latlng, { draggable: true }).addTo(map.value);
        }
    });

    // 4. Initial load call
    renderMarkers(props.reports);

    window.addEventListener('edit-report', (e) => {
        const report = props.reports.find(r => r.id === e.detail);
        if (report) {
            isEditing.value = true;
            selectedReportId.value = report.id;
            form.title = report.title;
            form.description = report.description;
            form.latitude = report.latitude;
            form.longitude = report.longitude;
            document.getElementById('report-form')?.scrollIntoView({ behavior: 'smooth' });
        }
    });
});
</script>



<template>

    <Head title="Report Road Damage" />
    <AuthenticatedLayout>
        <!-- add loading spin -->
        <div v-if="form.processing"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900 bg-opacity-50">
            <div class="flex flex-col items-center">
                <div class="h-16 w-16 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
                <p class="mt-4 text-white font-bold">Uploading Report...</p>
            </div>
        </div>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Road Damage Map</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="h-16 mb-2">
                    <div v-if="flashMessage"
                        class="w-full p-3 bg-green-600 text-white rounded-lg shadow-md flex items-center justify-between">
                        <span>✅ {{ flashMessage }} </span>
                        <button @click="flashMessage = null" class="ml-2 font-bold hover:text-gray-200">✕</button>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="mb-4 text-gray-600">📍 Click on map to mark the location of the damage</p>
                    <div id="map" class="h-[500px] w-full rounded-lg border shadow-inner"></div>
                </div>
            </div>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <form id="report-form" @submit.prevent="submit" class="mt-6 space-y-4">
                        <div>
                            <label class="block font-medium text-sm text-gray-700 "> What is the Problem?</label>
                            <input v-model="form.title" type="text" placeholder="e.g., Big pothole near School"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 "> Description</label>
                            <textarea v-model="form.description"
                                placeholder="Provide more details about the road damage..."
                                class="w-full border-gray-300 rounded-md shadow-sm"></textarea>
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 ">
                                latitude</label>
                            <input v-model="form.latitude" type="text" placeholder="Latitude" readonly
                                class="w-full border-gray-300 rounded-md shadow-sm">

                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 ">
                                latitude</label>
                            <input v-model="form.longitude" type="text" placeholder="Longitude" readonly
                                class="w-full border-gray-300 rounded-md shadow-sm ">

                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700 ">
                                Upload Image</label>
                            <input type="file" ref="fileInput" @input="form.image = $event.target.files[0]"
                                class="w-full border border-gray-300 rounded-md p-2">
                            <!-- show preview img upload-->
                            <div v-if="form.image" class="mt-4">
                                <strong class="block mb-2">Image Preview:</strong>
                                <img :src="previewUrl" alt="Image Preview" class="max-w-xs rounded">
                            </div>

                        </div>
                        <div v-if="form.progress" class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                :style="{ width: form.progress.percentage + '%' }">
                            </div>
                            <p class="text-xs text-blue-600 mt-1 text-right">Uploading: {{ form.progress.percentage }}%
                            </p>
                        </div>
                        <!-- <button type="submit" :disabled="form.processing"
                            class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 disabled:opacity-50">
                            {{ form.processing ? 'Submitting...' : 'Submit Report' }}
                        </button> -->
                        <div class="flex gap-2">
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 disabled:opacity-50">
                                {{ isEditing ? 'Add Image to Gallery' : 'Submit New Report' }}
                            </button>

                            <button v-if="isEditing" type="button" @click="resetFormMode"
                                class="bg-gray-500 text-white px-4 py-2 rounded shadow hover:bg-gray-600">
                                Cancel Update
                            </button>
                        </div>
                        <div v-if="form.errors" class="text-red-500">

                            {{ form.errors }}
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>

</template>

<style>
#map {
    z-index: 1;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}


.scrollbar-hide {
    -ms-overflow-style: none;

    scrollbar-width: none;

}

.snap-x {
    scroll-snap-type: x mandatory;
}

.snap-center {
    scroll-snap-align: center;
}
</style>
