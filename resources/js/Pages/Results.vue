<template>
    <div>
        <h1 class="text-2xl font-bold mb-4">Uploaded Documents</h1>

        <table class="table-auto w-full border-collapse border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-200 px-4 py-2">#</th>
                    <th class="border border-gray-200 px-4 py-2">File Name</th>
                    <th class="border border-gray-200 px-4 py-2">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(file, index) in files" :key="file.id">
                    <td class="border border-gray-200 px-4 py-2">{{ index + 1 }}</td>
                    <td class="border border-gray-200 px-4 py-2">{{ file.file_path }}</td>
                    <td class="border border-gray-200 px-4 py-2">
                        <span v-if="file.status === 'processed'" class="text-green-500">Processed</span>
                        <span v-else class="text-yellow-500">Pending</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { Inertia } from '@inertiajs/inertia';

export default {
    setup() {
        const files = ref([]);

        onMounted(() => {
            Inertia.visit('/api/files', {
                onSuccess: ({ props }) => {
                    files.value = props.files || [];
                },
            });
        });

        return { files };
    },
};
</script>

<style>
/* Add custom styles here if needed */
</style>
