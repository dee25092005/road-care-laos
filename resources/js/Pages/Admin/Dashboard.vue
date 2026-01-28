<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from 'vue-toastification';
import Pagination from '@/Components/Pagination.vue';


// Define Props
const props = defineProps({
    reports: Object,
    pendingCount: Number,
    fixedCount: Number,
    filters: Object,
})

//define pagination
const limit = ref(props.filters?.limit || 10);
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

const updateView = () => {
    router.get(route('admin.dashboard'), { limit: limit.value, search: search.value, status: statusFilter.value, page: 1 }, { preserveState: true, replace: true });
}

// Edit Form Data
const editForm = useForm({
    id: null,
    title: '',
    description: '',
});


const statusForm = useForm({
    status: ''
});

const toast = useToast();
const confirmDelete = ref(false);
const ShowModal = ref(false);
const selectedReportId = ref(null);

//open edit modal
const openEdit = (report) => {
    editForm.id = report.id;
    editForm.title = report.title;
    editForm.description = report.description;
    ShowModal.value = true;
}

const submitUpdate = () => {
    editForm.put(route('reports.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            ShowModal.value = false;
            toast.success('Report updated successfully');
        }
    })
}

const actionForm = useForm({
    status: '',
    id: null,
});


//togglesStatus
const toggleSatus = (report) => {
    const newStatus = report.status === 'pending' ? 'fixed' : 'pending';

    actionForm.id = report.id;
    actionForm.status = newStatus;
    report.isProcessing = true;
    actionForm.put(route('reports.updateStatus', report.id), {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Status updated successfully');
            toast.success(newStatus === 'fixed' ? 'Report marked as fixed' : 'Report status undone');
        }
    });


}

const deleteReport = () => {
    if (!actionForm.id) return console.error('No report selected for deletion');
    actionForm.delete(route('reports.destroy', actionForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmDelete.value = false;
            selectedReportId.value = null;
            toast.success('Report deleted successfully');
        }
    });
}

const openDeleteModal = (id) => {
    actionForm.id = id;
    confirmDelete.value = true;
}

const executeDelete = () => {
    if (!actionForm.id) return console.error('No report selected for deletion');

    deleteReport();

}


const closeConfirmModal = () => {
    confirmDelete.value = false;
}

const getStatusClass = (status) => {
    if (status === 'fixed') return 'bg-green-100 text-green-800';
    return 'bg-yellow-100 text-yellow-800';
}

const handleSearch = () => {
    updateView();
}

</script>

<template>
    <AuthenticatedLayout>

        <div class="max-w-7xl mx-auto py-12 px-6 bg-gray-100 p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 uppercase font-bold mb-2">Total Reports</p>
                    <h3 class="text-3xl font-black">{{ reports.total }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-yellow-500">
                    <p class="text-sm text-gray-500 uppercase font-bold mb-2">Pending</p>
                    <h3 class="text-3xl font-black">{{ pendingCount }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                    <p class="text-sm text-gray-500 uppercase font-bold mb-2">Fixed</p>
                    <h3 class="text-3xl font-black">{{ fixedCount }}</h3>
                </div>
            </div>

        </div>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden max-w-7xl mx-auto mt-8">
            <div class="mb-4 p-4 flex justify-between items-center gap-4">
                <input v-model="search" type="text" @input="handleSearch" placeholder="Search reports..." class="
                    border border-gray-300 rounded-full px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500
                ">

            </div>

            <div class="mb-4 p-4 flex justify-between items-center bg-white rounded-t-xl border-b">
                <div class="flex items-center gap-2 ">
                    <span class="text-sm font-bold text-gray-600">Show:</span>
                    <select v-model="limit" @change="updateView" class="border rounded-lg text-sm px-2 py-1">
                        <option v-for="n in [5, 10, 25, 50, 100]" :key="n" :value="n">{{ n }}</option>
                    </select>

                </div>
                <div @click="statusFilter = 'pending'; updateView()"
                    class="cursor-pointer hover:scale-105 transition ...">
                    <span
                        class="flex items-center gap-2 border-l pl-4 bg-yellow-200 hover:bg-yellow-300 rounded-full p-2">
                        <p>Pending Button</p>
                        <h3>{{ pendingCount }}</h3>
                    </span>

                </div>
                <div class="flex items-center gap-2 border-l pl-4">
                    <span class="text-sm font-bold text-gray-600">Filter Status:</span>
                    <select v-model="statusFilter" @change="updateView" class="border rounded-lg text-sm px-2 py-1">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>

            </div>
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
                    <tr v-for="report in reports.data" :key="report.id" class="border-b hover:bg-gray-50 transition">

                        <td class="p-4">
                            <img :src="'/storage/' + report.images[0]?.image_path" v-if="report.images.length > 0"
                                alt="Report Image" class="w-16 h-12 object-cover rounded shadow-sm">
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
                            <button @click="toggleSatus(report)" :disabled="report.isprocessing"
                                class="text-white bg-green-600 hover:text-green-800 font-bold text-sm px-4 py-2 rounded-full mr-3 shadow-sm">
                                <span v-if="actionForm.processing"
                                    class="animate-spin h-5 w-5 border-2 border-t-transparent rounded-full"></span>
                                <span v-else>
                                    {{ report.status === 'pending' ? 'Mark Fixed' : 'Undo' }}
                                </span>
                            </button>
                            <button @click="openEdit(report)"
                                class="text-white bg-yellow-600 hover:text-yellow-800 font-bold text-sm rounded-full px-4 py-2 shadow-sm mr-3">Edit</button>
                            <div v-if="showEditModal" class="...">
                                <form @submit.prevent="submitUpdate">
                                    <input v-model="editForm.title" class="...">
                                    <textarea v-model="editForm.description" class="..."></textarea>
                                    <button type="submit" class="..." :disabled="editForm.processing">
                                        {{ editForm.processing ? 'Saving...' : 'Update Report' }}
                                    </button>
                                </form>
                            </div>

                            <button v-if="$page.props.auth.user.is_admin" @click="openDeleteModal(report.id)"
                                class="text-white bg-red-600 hover:text-red-800 font-bold text-sm rounded-full px-4 py-2 shadow-sm">Delete
                                <span>
                                    <!-- add icon not svg -->


                                </span></button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <!-- Pagination -->
            <div v-if="reports.links && reports.links.length > 3" class="p-4 border-t bg-gray-50 flex justify-center">
                <Pagination :links="reports.links" />
            </div>

        </div>

        <ConfirmationModal :show="confirmDelete" title="Delete Report" message="Are you sure? This cannot be undone."
            :loading="actionForm.processing" @close="confirmDelete = false" @confirm="executeDelete" />

        <div v-if="ShowModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Edit Report</h3>
                <form @submit.prevent="submitUpdate" class="space-y-4">
                    <input v-model="editForm.title" class="w-full border rounded-lg p-2" placeholder="Title">
                    <textarea v-model="editForm.description" class="w-full border rounded-lg p-2" rows="4"></textarea>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="ShowModal = false" class="text-gray-500">Cancel</button>
                        <button type="submit" :disabled="editForm.processing"
                            class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold">
                            {{ editForm.processing ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>

</template>