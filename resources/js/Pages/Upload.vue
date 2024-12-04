<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Upload Documents
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="uploadDocuments" class="space-y-6">
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <svg class="w-8 h-8 mb-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500">
                                            <span class="font-semibold">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            PDF, PNG, JPG or JPEG (MAX. 10MB)
                                        </p>
                                    </div>
                                    <input 
                                        type="file" 
                                        class="hidden" 
                                        multiple 
                                        ref="files"
                                        @change="handleFileChange" 
                                        accept=".pdf,.jpg,.jpeg,.png"
                                    />
                                </label>
                            </div>

                            <div v-if="selectedFiles.length > 0" class="mt-4">
                                <h3 class="text-lg font-medium">Selected Files:</h3>
                                <ul class="mt-2 space-y-2">
                                    <li v-for="file in selectedFiles" :key="file.name" class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                        <span>{{ file.name }}</span>
                                        <button @click="removeFile(file)" type="button" class="text-red-500 hover:text-red-700">
                                            Remove
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="flex justify-end">
                                <button 
                                    type="submit" 
                                    class="px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600"
                                    :disabled="isUploading || selectedFiles.length === 0"
                                >
                                    {{ isUploading ? 'Uploading...' : 'Upload Files' }}
                                </button>
                            </div>
                        </form>

                        <div v-if="successMessage" class="mt-4 p-4 bg-green-100 text-green-700 rounded">
                            {{ successMessage }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const files = ref(null);
const selectedFiles = ref([]);
const successMessage = ref('');
const isUploading = ref(false);

const handleFileChange = (event) => {
    selectedFiles.value = Array.from(event.target.files);
};

const removeFile = (fileToRemove) => {
    selectedFiles.value = selectedFiles.value.filter(file => file !== fileToRemove);
};

const uploadDocuments = async () => {
    if (selectedFiles.value.length === 0) return;

    isUploading.value = true;
    const formData = new FormData();
    
    selectedFiles.value.forEach(file => {
        formData.append('documents[]', file);
    });

    try {
        await router.post('/upload', formData, {
            onSuccess: () => {
                successMessage.value = 'Files uploaded successfully!';
                selectedFiles.value = [];
                if (files.value) files.value.value = '';
            },
            onError: (errors) => {
                console.error(errors);
            },
            onFinish: () => {
                isUploading.value = false;
            }
        });
    } catch (error) {
        console.error('Upload failed:', error);
        isUploading.value = false;
    }
};
</script>
