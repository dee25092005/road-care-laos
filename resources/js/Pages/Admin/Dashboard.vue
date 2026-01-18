<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// Define Props


const props = defineProps({
    reports: {
        type: Array,
        default: () => []
    },
    pendingCount: Number,
    fixedCount: Number
})

const updateStatus = (id, newStatus) => {
    if (confirm(`change status to ${newStatus}?`)) {
        router.put(route('reports.updateStatus', id), { status: newStatus });
    }

}

const deleteReport = (id) => {
    if (confirm('Are you sure you want to delete this report?')) {
        router.delete(route('reports.destroy', id));
    }
}

const getStatusClass = (status) => {
    if (status === 'fixed') return 'bg-green-100 text-green-800';
    return 'bg-yellow-100 text-yellow-800';
}

</script>

<template>
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto py-12 px-6 bg-gray-100 p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 uppercase font-bold mb-2">Total Reports</p>
                    <h3 class="text-3xl font-black">{{ reports.length }}</h3>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 uppercase font-bold mb-2">Pending</p>
                    <h3 class="text-3xl font-black">{{ pendingCount }}</h3>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 uppercase font-bold mb-2">Fixed</p>
                    <h3 class="text-3xl font-black">{{ fixedCount }}</h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden max-w-7xl mx-auto mt-8">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="p-4 font-bold text-gray-600">Image</th>
                        <th class="p-4 font-bold text-gray-600">Problem</th>
                        <th class="p-4 font-bold text-gray-600">Location</th>
                        <th class="p-4 font-bold text-gray-600">Status</th>
                        <th class="p-4 font-bold text-gray-600 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="report in reports" :key="report.id" class="border-b hover:bg-gray-50 transition">

                        <td class="p-4">
                            <img :src="'/storage/' + report.images[0]?.image_path"
                                class="w-16 h-12 object-cover rounded shadow-sm">
                        </td>
                        <td class="p-4">
                            <p class="font-bold text-gray-800 ">{{ report.title }}</p>
                            <p class="text-gray-500">{{ report.description }}</p>

                        </td>
                        <td class="p-4 text-sm text-gray-600">
                            {{ Number(report.latitude).toFixed(4) }}, {{ Number(report.longitude).toFixed(4) }}
                        </td>
                        <td class="p-4">
                            <span :class="getStatusClass(report.status)"
                                class="px-2 py-1 rounded-full text-[10px] font-bold uppercase">{{ report.status
                                }}</span>
                        </td>

                        <td class="p-4 text-right">
                            <button v-if="$page.props.auth.user.is_admin" @click="updateStatus(report.id, 'fixed')"
                                class="text-white bg-green-600 hover:text-green-800 font-bold text-sm px-4 py-2 rounded-full mr-3 shadow-sm">Mark
                                Fixed</button>
                            <button v-if="$page.props.auth.user.is_admin" @click="deleteReport(report.id)"
                                class="text-white bg-red-600 hover:text-red-800 font-bold text-sm rounded-full px-4 py-2 shadow-sm">Delete</button>
                        </td>

                    </tr>
                </tbody>
            </table>

        </div>
    </AuthenticatedLayout>

</template>