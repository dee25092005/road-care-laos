<script setup>
defineProps({
    show: Boolean,
    title: String,
    message: String,
    loading: Boolean
})

</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-2xl shadow-xl max-w-sm w-full mx-4">
            <h3 class="text-xl font-bold mb-2">{{ title }}</h3>
            <p class="text-gray-600 mb-6">{{ message }} </p>
            <div class="flex justify-end gap-3">
                <button @click="$emit('close')" class="px-2 py-2 text-gray-500 font-bold">Cancel</button>
                <button @click="$emit('confirm')" :disabled="loading"
                    class="px-4 py-2 bg-red-600 text-white font-bold rounded-full hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span v-if="loading" class="animate-spin h-5 w-5 border-2 border-t-transparent rounded-full">
                        Confirm
                    </span>
                    <span v-else>Confirm</span>
                </button>
            </div>
        </div>
    </div>


</template>