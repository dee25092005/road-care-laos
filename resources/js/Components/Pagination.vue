<template>
    <div v-if="links.length > 3" class="flex flex-wrap justify-center gap-1 py-4">
        <template v-for="(link, key) in links" :key="key">
            <div v-if="link.url === null"
                class="px-4 py-2 text-sm leading-4 text-gray-400 border rounded cursor-not-allowed"
                v-html="link.label" />

            <Link v-else :href="link.url"
                class="px-4 py-2 text-sm leading-4 border rounded hover:bg-gray-100 focus:border-blue-500 focus:text-blue-500 transition-colors"
                :class="{ 'bg-blue-600 text-white hover:bg-blue-700': link.active }" v-html="link.label"
                preserve-scroll />
        </template>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ links: Array });
</script>