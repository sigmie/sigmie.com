<script setup>
import { Head, Link, router as Inertia } from "@inertiajs/vue3";
import Search from "./Search.vue";
import Banner from "./Banner.vue";
import { onMounted, onUnmounted, ref, computed, nextTick, watch } from "vue";
import { SigmieSearch } from "@sigmie/vue";
import axios from "axios";

let showMenu = ref(false);
const toggleNav = () => (showMenu.value = !showMenu.value);
const hideNav = () => (showMenu.value = true);

onMounted(() => {
    hideNav();
});

let visit = (url) => {
    showMenu.value = true;
    Inertia.visit(url);
};

const props = defineProps({
    navigation: Object,
});

const searchQuery = ref('');
const searchFocused = ref(false);
const showSearchModal = ref(false);
const modalSearchQuery = ref('');
const modalSearchInput = ref(null);
const searchResults = ref([]);
const isSearching = ref(false);
const selectedResultIndex = ref(0);
let searchTimeout = null;

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        Inertia.visit(`/search?q=${encodeURIComponent(searchQuery.value)}`);
    }
};

const openSearchModal = () => {
    showSearchModal.value = true;
    modalSearchQuery.value = '';
    searchResults.value = [];
    selectedResultIndex.value = 0;
    nextTick(() => {
        modalSearchInput.value?.focus();
    });
};

const closeSearchModal = () => {
    showSearchModal.value = false;
    searchResults.value = [];
    // Clear any pending search timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
    }
};

const searchDocs = async () => {
    if (!modalSearchQuery.value.trim()) {
        searchResults.value = [];
        selectedResultIndex.value = 0;
        return;
    }

    isSearching.value = true;

    try {
        const response = await axios.post('/api/search/docs', {
            query: modalSearchQuery.value
        });
        searchResults.value = response.data.results || [];
        selectedResultIndex.value = 0;
    } catch (error) {
        console.error('Docs search error:', error);
        searchResults.value = [];
        selectedResultIndex.value = 0;
    } finally {
        isSearching.value = false;
    }
};

const handleModalSearch = (result = null) => {
    if (result) {
        Inertia.visit(result.url);
        closeSearchModal();
    } else if (searchResults.value.length > 0) {
        Inertia.visit(searchResults.value[selectedResultIndex.value].url);
        closeSearchModal();
    }
};

const handleKeyboardNavigation = (e) => {
    if (!showSearchModal.value || searchResults.value.length === 0) return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        selectedResultIndex.value = Math.min(selectedResultIndex.value + 1, searchResults.value.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        selectedResultIndex.value = Math.max(selectedResultIndex.value - 1, 0);
    }
};

// Watch for query changes with debouncing
watch(modalSearchQuery, () => {
    // Clear previous timeout
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    // Set searching state immediately for UI feedback
    if (modalSearchQuery.value.trim()) {
        isSearching.value = true;
    } else {
        searchResults.value = [];
        isSearching.value = false;
        return;
    }

    // Debounce search by 300ms
    searchTimeout = setTimeout(() => {
        searchDocs();
    }, 300);
});

onMounted(() => {
    const handleKeyDown = (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            openSearchModal();
        }
        if (e.key === 'Escape' && showSearchModal.value) {
            closeSearchModal();
        }

        // Keyboard navigation
        handleKeyboardNavigation(e);
    };

    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    // Clean up timeout on component unmount
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});
</script>

<template>
    <div>
        <!-- Mobile Navigation -->
        <div
            v-if="!showMenu"
            class="lg:hidden fixed inset-0 top-16 z-40 bg-black"
        >
            <div class="h-full overflow-y-auto px-6 py-6">
                <nav class="space-y-6">
                    <div v-for="(section, index) in navigation" :key="index">
                        <h5 class="mb-3 font-semibold text-sm text-white">
                            {{ section.title }}
                        </h5>
                        <ul class="space-y-1">
                            <li v-for="(link, linkIndex) in section.links" :key="linkIndex">
                                <button
                                    @click.prevent="() => visit(link.href)"
                                    :class="[
                                        'flex items-center w-full px-2 py-1.5 text-sm rounded-md transition-colors text-left',
                                        $page.url === link.href
                                            ? 'bg-gray-900 text-white font-medium'
                                            : 'text-gray-400 hover:text-white hover:bg-gray-900/50'
                                    ]"
                                >
                                    {{ link.title }}
                                </button>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        <header
            class="bg-black h-16 fixed top-0 right-0 z-40 border-b border-gray-800 lg:left-72 left-0"
        >
            <nav class="h-full flex items-center justify-center px-6">
                <!-- Center Search - Click to open modal -->
                <div class="hidden md:block max-w-md w-full absolute left-1/2 transform -translate-x-1/2">
                    <button
                        @click="openSearchModal"
                        class="w-full pl-10 pr-20 py-2.5 text-sm text-gray-500 bg-gray-900/50 border border-gray-800 rounded-lg hover:border-gray-700 hover:bg-gray-900 transition-all text-left relative group"
                    >
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 group-hover:text-gray-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <span class="group-hover:text-gray-400 transition-colors">Search documentation...</span>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <kbd class="hidden sm:inline-block px-2 py-0.5 text-xs text-gray-500 bg-gray-950 rounded border border-gray-700 font-mono">
                                ⌘K
                            </kbd>
                        </div>
                    </button>
                </div>

                <!-- Right side - GitHub & Mobile menu -->
                <div class="absolute right-0 flex items-center space-x-4 pr-6">
                    <!-- GitHub -->
                    <a
                        href="https://github.com/sigmie/sigmie"
                        target="_blank"
                        class="hidden sm:block p-2 text-gray-400 hover:text-white transition-colors"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                    </a>

                    <!-- Mobile menu -->
                    <button
                        @click="toggleNav"
                        class="lg:hidden p-2 text-gray-400 hover:text-white"
                    >
                        <svg v-if="showMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </nav>
        </header>

        <!-- Search Modal -->
        <Transition name="fade">
            <div
                v-if="showSearchModal"
                class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-start justify-center pt-16"
                @click.self="closeSearchModal"
            >
                <div class="bg-gradient-to-br from-gray-900 to-gray-950 rounded-xl shadow-2xl w-full max-w-4xl mx-4 border border-gray-800 max-h-[calc(100vh-8rem)] flex flex-col">
                    <!-- Search Input -->
                    <div class="px-6 pt-6 pb-4 flex-shrink-0">
                        <div class="border-b border-gray-800 pb-4 focus-within:border-gray-700 transition-colors">
                            <div class="relative flex gap-3 items-center">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input
                                    ref="modalSearchInput"
                                    v-model="modalSearchQuery"
                                    @keyup.enter="handleModalSearch()"
                                    type="text"
                                    placeholder="Search documentation..."
                                    class="flex-1 py-3 text-lg bg-transparent text-gray-100 placeholder-gray-500 border-0 focus:outline-none focus:ring-0"
                                />
                                <button
                                    @click="closeSearchModal"
                                    class="text-gray-400 hover:text-gray-200 transition-colors p-1"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div v-if="modalSearchQuery.trim()" class="px-6 flex-1 overflow-y-auto">
                        <!-- Loading State -->
                        <div v-if="isSearching" class="py-12 text-center">
                            <div class="w-8 h-8 border-4 border-gray-700 border-t-gray-400 rounded-full animate-spin mx-auto"></div>
                            <p class="mt-3 text-sm text-gray-500">Searching...</p>
                        </div>

                        <!-- Results -->
                        <div v-else-if="searchResults.length > 0" class="py-2 space-y-2">
                            <button
                                v-for="(result, index) in searchResults"
                                :key="result._id"
                                @click="handleModalSearch(result)"
                                @mouseenter="selectedResultIndex = index"
                                :class="[
                                    'w-full text-left px-5 py-4 rounded-lg transition-all group relative',
                                    selectedResultIndex === index
                                        ? 'bg-gray-800 ring-2 ring-blue-500/50'
                                        : 'hover:bg-gray-800/50'
                                ]"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-gray-100 group-hover:text-white transition-colors mb-2">
                                            {{ result.title }}
                                        </div>
                                        <div class="mb-2">
                                            <div class="text-xs font-mono text-blue-400 truncate">
                                                {{ result.url }}
                                            </div>
                                        </div>
                                        <div v-if="result.content" class="text-sm text-gray-400 line-clamp-2 leading-relaxed">
                                            {{ result.content.substring(0, 200) }}{{ result.content.length > 200 ? '...' : '' }}
                                        </div>
                                    </div>
                                </div>
                                <div v-if="selectedResultIndex === index" class="absolute right-4 top-4">
                                    <kbd class="px-2 py-1 text-xs font-mono bg-gray-700 text-gray-300 rounded border border-gray-600">Enter</kbd>
                                </div>
                            </button>
                        </div>

                        <!-- No Results -->
                        <div v-else class="py-12 text-center">
                            <svg class="w-12 h-12 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-400 text-sm">No results found for "{{ modalSearchQuery }}"</p>
                        </div>
                    </div>

                    <!-- Footer with keyboard hint -->
                    <div v-if="searchResults.length > 0" class="px-6 py-4 flex items-center justify-between border-t border-gray-800 flex-shrink-0">
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span class="flex items-center gap-1.5">
                                <kbd class="px-2 py-1 bg-gray-800 rounded border border-gray-700 text-gray-300 font-mono">↑</kbd>
                                <kbd class="px-2 py-1 bg-gray-800 rounded border border-gray-700 text-gray-300 font-mono">↓</kbd>
                                <span>Navigate</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <kbd class="px-2 py-1 bg-gray-800 rounded border border-gray-700 text-gray-300 font-mono">Enter</kbd>
                                <span>Open</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <kbd class="px-2 py-1 bg-gray-800 rounded border border-gray-700 text-gray-300 font-mono">Esc</kbd>
                                <span>Close</span>
                            </span>
                        </div>
                        <div class="text-xs text-gray-600">
                            {{ searchResults.length }} result{{ searchResults.length !== 1 ? 's' : '' }}
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style type="text/css">
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>