<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    reports: Array,
});

const map = ref(null);
const marker = ref(null);
const fileInput = ref(null);
const previewUrl = ref(null);
const form = useForm({
    title: '',
    description: '',
    latitude: null,
    longitude: null,
    image: null,
})


watch(() => form.image, (newFile) => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
    if (newFile) {
        previewUrl.value = URL.createObjectURL(newFile);
    }
});

const submit = () => {
    form.post(route('reports.store'), {
        onSuccess: () => {
            alert('Report submitted successfully!');
            form.reset();
            if (marker.value) {
                map.value.removeLayer(marker.value);
                marker.value = null;
            }
            if (fileInput.value) {
                fileInput.value.value = null;
            }
        }
    });
}

onMounted(() => {
    // Initialize the map
    map.value = L.map('map').setView([17.9757, 102.6331], 13);

    //add map 
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map.value);


    //handle click event to get lat lng

    map.value.on('click', (e) => {
        const { lat, lng } = e.latlng;
        form.latitude = lat;
        form.longitude = lng;



        //move or create
        if (marker.value) {
            marker.value.setLatLng(e.latlng);
        } else {
            marker.value = L.marker(e.latlng, {
                draggable: true
            }).addTo(map.value);
        }
    })
})

</script>

<template>

    <Head title="Report Road Damage" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Road Damage Map</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="mb-4 text-gray-600">Click on map to mark the location of the damage</p>
                    <div id="map" class="h-[500px] w-full rounded-lg border shadow-inner"></div>
                    <div v-if="form.latitude" class="mt-4 p-4 bg-green-50 border border-green-200 rounded">
                        <strong>Selected Location</strong> {{ form.latitude.toFixed(5) }}, {{ form.longitude.toFixed(5)
                        }}
                    </div>
                </div>
            </div>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <form @submit.prevent="submit" class="mt-6 space-y-4">
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
                        <button type="submit" :disabled="form.processing"
                            class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 disabled:opacity-50">
                            {{ form.processing ? 'Submitting...' : 'Submit Report' }}
                        </button>
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
</style>
