<script setup>
import { ref, watch } from 'vue';

// Define Props
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    isEditing: {
        type: Boolean,
        default: false
    }
});

// Define Emits
const emit = defineEmits(['submit', 'cancel']);

const previewUrls = ref([]);
const fileInput = ref(null);

// Type-safe file handler to satisfy the linter
const handleFileChange = (event) => {
    const files = Array.from(event.target.files || []);
    props.form.images = files;

    // Clean up old preview URLs to prevent memory leaks
    previewUrls.value.forEach(url => URL.revokeObjectURL(url));

    previewUrls.value = files.map(file => URL.createObjectURL(file));
};

watch(() => props.form.images, (newFiles) => {
    // When the form is reset (e.g., after submission), clear the previews and file input.
    if (!newFiles || newFiles.length === 0) {
        previewUrls.value.forEach(url => URL.revokeObjectURL(url));
        previewUrls.value = [];
        if (fileInput.value) {
            fileInput.value.value = '';
        }
    }
});
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-4">
        <div>
            <label class="block text-sm font-medium">Problem Title</label>
            <input v-model="form.title" type="text" class="w-full border-gray-300 rounded-md">
            <div v-if="form.errors?.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
        </div>

        <div>
            <label class="block text-sm font-medium">Description</label>
            <textarea v-model="form.description" class="w-full border-gray-300 rounded-md"></textarea>
            <div v-if="form.errors?.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-gray-500">Latitude</label>
                <input v-model="form.latitude" type="text" readonly
                    class="w-full bg-gray-50 border-gray-200 rounded-md">
            </div>
            <div>
                <label class="block text-xs text-gray-500">Longitude</label>
                <input v-model="form.longitude" type="text" readonly
                    class="bg-gray-50 border-gray-200 rounded-md w-full">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium">Photo</label>
            <input type="file" multiple ref="fileInput" @change="handleFileChange"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <div v-if="form.errors?.images" class="text-red-500 text-xs mt-1">{{ form.errors.images }}</div>
            <div v-if="previewUrls.length > 0" class="mt-2 grid grid-cols-3 gap-2">
                <div v-for="(url, index) in previewUrls" :key="index">
                    <img :src="url" class="w-full h-24 object-cover rounded shadow-sm border">
                </div>
            </div>
        </div>

        <div class="flex gap-2 pt-4">
            <button type="submit" :disabled="form.processing"
                class="flex-1 bg-green-600 text-white py-2 rounded font-bold hover:bg-green-700 disabled:opacity-50 transition">
                {{ isEditing ? 'Update & Add Image' : 'Submit New Report' }}
            </button>
            <button v-if="isEditing" type="button" @click="$emit('cancel')"
                class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">
                Cancel
            </button>
        </div>
    </form>
</template>