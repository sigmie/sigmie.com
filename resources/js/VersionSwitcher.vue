<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    currentVersion: {
        type: String,
        default: 'v0'
    },
    availableVersions: {
        type: Array,
        default: () => [
            { value: 'v0', label: 'v0.x', status: 'stable' },
            { value: 'v1', label: 'v1.x', status: 'beta' },
            { value: 'v2', label: 'v2.x', status: 'dev' }
        ]
    }
});

const isOpen = ref(false);
const dropdownRef = ref(null);

const currentVersionLabel = computed(() => {
    const version = props.availableVersions.find(v => v.value === props.currentVersion);
    return version ? version.label : props.currentVersion;
});

const currentVersionStatus = computed(() => {
    const version = props.availableVersions.find(v => v.value === props.currentVersion);
    return version ? version.status : null;
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const selectVersion = (version) => {
    if (version === props.currentVersion) {
        isOpen.value = false;
        return;
    }
    
    // Store version preference
    localStorage.setItem('preferred-doc-version', version);
    
    // Navigate to the new version
    const currentPath = window.location.pathname;
    const newPath = currentPath.replace(`/docs/${props.currentVersion}/`, `/docs/${version}/`);
    
    // Check if the new version path exists by trying to navigate
    router.visit(newPath, {
        onError: () => {
            // If the page doesn't exist, go to the introduction page of the new version
            router.visit(`/docs/${version}/introduction`);
        }
    });
    
    isOpen.value = false;
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

const getStatusColor = (status) => {
    switch(status) {
        case 'stable':
            return 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800';
        case 'beta':
            return 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800';
        case 'dev':
            return 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
        default:
            return 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800';
    }
};
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <button
            @click="toggleDropdown"
            class="flex items-center gap-2 px-3 py-1.5 text-geist-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-geist hover:bg-gray-100 dark:hover:bg-gray-800 transition-all duration-200"
            aria-expanded="isOpen"
            aria-haspopup="true"
        >
            <span>{{ currentVersionLabel }}</span>
            <span 
                v-if="currentVersionStatus"
                :class="getStatusColor(currentVersionStatus)"
                class="px-1.5 py-0.5 text-xs font-medium rounded border"
            >
                {{ currentVersionStatus }}
            </span>
            <svg
                :class="{ 'rotate-180': isOpen }"
                class="w-4 h-4 transition-transform duration-200"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-black border border-gray-200 dark:border-gray-800 rounded-geist shadow-geist-md overflow-hidden z-50"
            >
                <div class="py-1">
                    <button
                        v-for="version in availableVersions"
                        :key="version.value"
                        @click="selectVersion(version.value)"
                        :class="{
                            'bg-gray-50 dark:bg-gray-900': version.value === currentVersion
                        }"
                        class="w-full px-4 py-2 text-left text-geist-sm hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors duration-150 flex items-center justify-between group"
                    >
                        <span 
                            :class="{
                                'text-gray-900 dark:text-gray-100 font-medium': version.value === currentVersion,
                                'text-gray-700 dark:text-gray-300': version.value !== currentVersion
                            }"
                        >
                            {{ version.label }}
                        </span>
                        <span 
                            :class="getStatusColor(version.status)"
                            class="px-1.5 py-0.5 text-xs font-medium rounded border opacity-75 group-hover:opacity-100"
                        >
                            {{ version.status }}
                        </span>
                    </button>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-800 px-4 py-2">
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        Select a version to view its documentation
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.rotate-180 {
    transform: rotate(180deg);
}
</style>