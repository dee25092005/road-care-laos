<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RoadMap from '@/Components/RoadMap.vue';
import ReportForm from '@/Components/ReportForm.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ reports: Array });
const mapRef = ref(null);
const isEditing = ref(false);
const selectedId = ref(null);

const form = useForm({
    title: '', description: '', latitude: null, longitude: null, images: []
});

const handleMapClick = (latlng) => {
    if (isEditing.value) reset();
    form.latitude = latlng.lat;
    form.longitude = latlng.lng;
};

const deleteReport = (id) => {
    router.delete(route('reports.destroy', id), {
        onSuccess: () => {
            if (isEditing.value && selectedId.value === id) {
                reset();
            }
        }
    });
}

const handleEditRequest = (report) => {
    isEditing.value = true;
    selectedId.value = report.id;
    form.title = report.title;
    form.description = report.description;
    form.latitude = report.latitude;
    form.longitude = report.longitude;
};

const reset = () => {
    isEditing.value = false;
    selectedId.value = null;
    form.reset();
    mapRef.value?.clearTempMarker();
};

const submit = () => {
    if (isEditing.value) {

        form.put(route('reports.update', selectedId.value), {
            onSuccess: () => reset()
        });

    } else {
        form.post(route('reports.store'), {
            onSuccess: () => reset()
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-12 px-6">
            <div v-if="$page.props.flash.message"
                class="mb-4 p-4 bg-green-600 text-white rounded border border-green-200">
                {{ $page.props.flash.message }}
            </div>
            <RoadMap ref="mapRef" :reports="reports" @map-click="handleMapClick" @edit-report="handleEditRequest"
                @delete-report="deleteReport" />

            <div class="mt-10 bg-white p-6 rounded-lg shadow">
                <ReportForm :form="form" :isEditing="isEditing" @submit="submit" @cancel="reset" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>