<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed, watch, nextTick } from "vue";
import axios from "axios";
import Sidebar from "../Sidebar.vue";
import Navbar from "../Navbar.vue";
import Banner from "../Banner.vue";
import CodePreview from "../components/CodePreview.vue";

defineProps({
    title: String,
    posts: Object,
    navigation: Object,
});

const searchQuery = ref("");
const searchResults = ref([]);
const isSearching = ref(false);
const hasSearched = ref(false);
const selectedType = ref("all");

// Image search state
const uploadedImage = ref(null);
const imagePreview = ref(null);
const isCropping = ref(false);
const cropArea = ref({ x: 0, y: 0, width: 100, height: 100 });
const imageSearchResults = ref([]);
const imageQuery = ref("nature landscapes");
const initialImages = ref([]);

const presetQueries = [
    { label: "Woman protagonist", query: "woman" },
    { label: "Action thrillers", query: "action thriller" },
    { label: "Romantic comedies", query: "romantic comedy" },
    { label: "Sci-fi adventures", query: "science fiction space" },
    { label: "Crime dramas", query: "crime drama detective" },
    { label: "Family animations", query: "family animation kids" }
];

const codeString = computed(() => {
    const query = searchQuery.value || 'romantic comedy';

    let filterLine = '';
    if (selectedType.value === 'all') {
        filterLine = `    ->filters('type:"TV Show" OR type:"Movie"')`;
    } else {
        filterLine = `    ->filters('type:"${selectedType.value}"')`;
    }

    return `$netflixIndex = app(NetflixTitles::class);
$blueprint = $netflixIndex->properties();

$search = $netflixIndex
    ->newSearch()
    ->properties($blueprint)
    ->semantic()
    ->noResultsOnEmptySearch()
    ->disableKeywordSearch()
${filterLine}
    ->queryString('${query}')
    ->retrieve(['type', 'title', 'director', 'cast'])
    ->size(20);

$response = $search->get();`;
});

const highlightedLines = computed(() => {
    // Line 10 is the filter line, highlight it when a specific filter is selected
    return hasSearched.value && selectedType.value !== 'all' ? [10] : [];
});

const filteredResults = computed(() => {
    if (selectedType.value === "all") {
        return searchResults.value;
    }
    return searchResults.value.filter(result => result.type === selectedType.value);
});

const performSearch = async (query = null) => {
    const searchTerm = query || searchQuery.value;

    if (!searchTerm.trim()) {
        return;
    }

    if (query) {
        searchQuery.value = query;
    }

    isSearching.value = true;
    hasSearched.value = true;
    selectedType.value = "all";

    try {
        const response = await axios.post("/api/search/netflix", {
            query: searchTerm,
        });

        searchResults.value = response.data.results || [];
    } catch (error) {
        console.error("Search error:", error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

// Generate initial images based on query
const generateInitialImages = () => {
    const seed = imageQuery.value.replace(/\s+/g, '-');
    initialImages.value = Array.from({ length: 9 }, (_, i) => ({
        id: i + 1,
        url: `https://picsum.photos/seed/${seed}-${i + 1}/400/300`,
        title: `${imageQuery.value} ${i + 1}`,
    }));
};

// Initialize on mount
generateInitialImages();

// Image search functions
const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file && file.type.startsWith('image/')) {
        uploadedImage.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target.result;
            isCropping.value = true;
            // Reset crop area
            cropArea.value = { x: 10, y: 10, width: 80, height: 80 };
        };
        reader.readAsDataURL(file);
    }
};

const selectImageFromGallery = (imageUrl) => {
    imagePreview.value = imageUrl;
    isCropping.value = true;
    uploadedImage.value = null; // No file uploaded, using URL
    cropArea.value = { x: 10, y: 10, width: 80, height: 80 };
};

const updateImageQuery = () => {
    generateInitialImages();
    // Reset search state
    imagePreview.value = null;
    isCropping.value = false;
    imageSearchResults.value = [];
};

const performImageSearch = async () => {
    // Placeholder function - will be implemented later
    isCropping.value = false;

    // Generate placeholder results
    imageSearchResults.value = Array.from({ length: 12 }, (_, i) => ({
        id: i + 1,
        url: `https://picsum.photos/seed/${i + 1}/400/600`,
        title: `Similar Image ${i + 1}`,
        width: Math.random() > 0.5 ? 400 : 300,
        height: Math.random() * 400 + 300
    }));
};

const resetImageSearch = () => {
    uploadedImage.value = null;
    imagePreview.value = null;
    isCropping.value = false;
    imageSearchResults.value = [];
};

// Preserve scroll position when filtering
let savedScrollY = 0;

watch(selectedType, async (newVal, oldVal) => {
    if (oldVal !== null && hasSearched.value) {
        // Save current scroll position
        savedScrollY = window.scrollY;

        // Wait for DOM to update
        await nextTick();

        // Restore scroll position smoothly
        window.scrollTo({
            top: savedScrollY,
            behavior: 'instant' // Use instant to prevent any visible scroll jump
        });
    }
});
</script>

<template>
    <Head>
        <title>Sigmie - A different Elasticsearch library</title>
        <meta name="description" content="Sigmie is a modern, developer-friendly Elasticsearch library that handles all complexities, letting you focus solely on search relevance. Simple API, production-ready." />
        <meta name="keywords" content="elasticsearch, search library, full-text search, sigmie, php, laravel, search api, elasticsearch php" />
        <meta property="og:title" content="Sigmie - A different Elasticsearch library" />
        <meta property="og:description" content="Sigmie is a modern, developer-friendly Elasticsearch library that handles all complexities, letting you focus solely on search relevance." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://sigmie.com" />
        <meta property="og:image" content="https://sigmie.com/og-image.png" />
        <meta property="og:site_name" content="Sigmie" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Sigmie - A different Elasticsearch library" />
        <meta name="twitter:description" content="Sigmie is a modern, developer-friendly Elasticsearch library that handles all complexities, letting you focus solely on search relevance." />
        <meta name="twitter:image" content="https://sigmie.com/og-image.png" />
        <link rel="canonical" href="https://sigmie.com" />
    </Head>
    <div class="min-h-screen bg-white dark:bg-black">
        <!-- Netflix Search Demo Section -->
        <div class="relative border-t border-gray-200 dark:border-gray-800 bg-gradient-to-b from-white to-gray-50 dark:from-black dark:to-gray-900/50 overflow-hidden">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/4 -right-64 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 -left-64 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="text-center mb-10 sm:mb-14">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-950/30 dark:to-purple-950/30 border border-blue-200 dark:border-blue-800 rounded-full mb-6">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span class="text-sm font-medium bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Live Demo
                        </span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold text-gray-900 dark:text-gray-100 mb-4 sm:mb-6">
                        Experience
                        <span class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Semantic Search
                        </span>
                    </h2>
                    <p class="text-base sm:text-lg lg:text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto leading-relaxed">
                        Search 8,000+ Netflix titles using natural language. See how Sigmie's semantic search understands intent, not just keywords.
                    </p>
                </div>

                <div class="max-w-4xl mx-auto">
                    <!-- Code Preview -->
                    <div class="mb-8 sm:mb-10">
                        <CodePreview
                            :code="codeString"
                            filename="SearchController.php"
                            :highlight-lines="highlightedLines"
                        />
                    </div>

                    <!-- Search Form -->
                    <form @submit.prevent="performSearch()" class="mb-8 sm:mb-10">
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-500 rounded-2xl blur-xl opacity-20"></div>
                            <div class="relative flex gap-2 sm:gap-3 p-2 bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl transition-all duration-300 focus-within:border-blue-500 dark:focus-within:border-blue-400">
                                <div class="flex-1 flex items-center gap-3 px-3">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search by mood, genre, or description..."
                                        class="flex-1 py-3 text-sm sm:text-base bg-transparent focus:outline-none dark:text-gray-100 placeholder-gray-400"
                                        :disabled="isSearching"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    class="group relative px-6 sm:px-8 py-3 sm:py-4 font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl disabled:hover:shadow-lg overflow-hidden"
                                    :disabled="isSearching || !searchQuery.trim()"
                                >
                                    <span class="relative z-10 flex items-center gap-2">
                                        <span class="hidden sm:inline">{{ isSearching ? "Searching..." : "Search" }}</span>
                                        <svg v-if="!isSearching" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                        </svg>
                                        <div v-else class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Preset Queries -->
                    <div v-if="!hasSearched" class="mb-12">
                        <p class="text-center text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">
                            Try these popular searches:
                        </p>
                        <div class="flex flex-wrap justify-center gap-2 sm:gap-3">
                            <button
                                v-for="preset in presetQueries"
                                :key="preset.query"
                                @click="performSearch(preset.query)"
                                class="group px-4 sm:px-5 py-2 sm:py-2.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full hover:border-blue-500 dark:hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400 hover:shadow-md transition-all duration-200"
                                :disabled="isSearching"
                            >
                                <span class="flex items-center gap-2">
                                    {{ preset.label }}
                                    <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Type Filters -->
                    <div v-if="hasSearched && !isSearching" class="mb-8 flex items-center justify-center gap-2">
                        <button
                            @click="selectedType = 'all'"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="selectedType === 'all'
                                ? 'bg-gray-900 dark:bg-white text-white dark:text-black shadow-md'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                        >
                            All ({{ searchResults.length }})
                        </button>
                        <button
                            @click="selectedType = 'Movie'"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="selectedType === 'Movie'
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600'"
                        >
                            Movies ({{ searchResults.filter(r => r.type === 'Movie').length }})
                        </button>
                        <button
                            @click="selectedType = 'TV Show'"
                            class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                            :class="selectedType === 'TV Show'
                                ? 'bg-purple-600 text-white shadow-md'
                                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600'"
                        >
                            TV Shows ({{ searchResults.filter(r => r.type === 'TV Show').length }})
                        </button>
                    </div>

                    <!-- Results -->
                    <div v-if="hasSearched" class="space-y-6">
                        <div v-if="isSearching" class="text-center py-16 sm:py-20">
                            <div class="relative inline-flex">
                                <div class="w-12 h-12 border-4 border-gray-200 dark:border-gray-700 border-t-blue-600 rounded-full animate-spin"></div>
                                <div class="absolute inset-0 w-12 h-12 border-4 border-transparent border-t-purple-600 rounded-full animate-spin" style="animation-duration: 1.5s; animation-direction: reverse;"></div>
                            </div>
                            <p class="mt-6 text-base font-medium text-gray-600 dark:text-gray-400">Searching through 8,000+ titles...</p>
                        </div>

                        <div v-else-if="searchResults.length === 0" class="text-center py-16 sm:py-20">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No results found</h3>
                            <p class="text-base text-gray-600 dark:text-gray-400 mb-6">Try a different search term or one of our suggestions above</p>
                            <button
                                @click="hasSearched = false; searchQuery = ''; searchResults = []"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Try again
                            </button>
                        </div>

                        <div v-else>
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100">
                                        <span v-if="selectedType === 'all'">Found {{ searchResults.length }} results</span>
                                        <span v-else>Showing {{ filteredResults.length }} {{ selectedType === 'Movie' ? 'movies' : 'TV shows' }}</span>
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Powered by semantic search
                                    </p>
                                </div>
                                <button
                                    @click="hasSearched = false; searchQuery = ''; searchResults = []; selectedType = 'all'"
                                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    New search
                                </button>
                            </div>

                            <div v-if="filteredResults.length === 0" class="text-center py-12">
                                <p class="text-gray-600 dark:text-gray-400">No {{ selectedType === 'Movie' ? 'movies' : 'TV shows' }} found in these results.</p>
                            </div>

                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5" id="results-grid">
                                <div
                                    v-for="(result, index) in filteredResults"
                                    :key="`${result._id || result.title}-${index}`"
                                    class="group relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-xl transition-all duration-300"
                                >
                                    <div class="absolute top-3 right-3">
                                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full"
                                            :class="result.type === 'Movie'
                                                ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                                : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'">
                                            {{ result.type }}
                                        </span>
                                    </div>

                                    <div class="pr-16 mb-4">
                                        <h3 class="font-bold text-base text-gray-900 dark:text-gray-100 line-clamp-2 mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                            {{ result.title }}
                                        </h3>
                                        <p v-if="result.release_year" class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ result.release_year }}
                                        </p>
                                    </div>

                                    <div class="space-y-2.5 text-xs text-gray-600 dark:text-gray-400">
                                        <div v-if="result.director" class="flex items-start gap-2">
                                            <svg class="w-4 h-4 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="line-clamp-1 flex-1">{{ result.director }}</span>
                                        </div>
                                        <div v-if="result.cast" class="flex items-start gap-2">
                                            <svg class="w-4 h-4 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            <span class="line-clamp-2 flex-1">{{ result.cast }}</span>
                                        </div>
                                        <div v-if="result.country" class="flex items-start gap-2">
                                            <svg class="w-4 h-4 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"></path>
                                            </svg>
                                            <span class="line-clamp-1 flex-1">{{ result.country }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Search Demo Section -->
        <div class="relative border-t border-gray-200 dark:border-gray-800 bg-gradient-to-b from-gray-50 to-white dark:from-gray-900/50 dark:to-black overflow-hidden">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/3 -left-64 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/3 -right-64 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="text-center mb-10 sm:mb-14">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950/30 dark:to-pink-950/30 border border-purple-200 dark:border-purple-800 rounded-full mb-6">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-sm font-medium bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Visual Search
                        </span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold text-gray-900 dark:text-gray-100 mb-4 sm:mb-6">
                        Find with
                        <span class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 bg-clip-text text-transparent">
                            Images
                        </span>
                    </h2>
                    <p class="text-base sm:text-lg lg:text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto leading-relaxed">
                        Upload an image, crop the region of interest, and discover visually similar content. Pinterest-style search powered by AI.
                    </p>
                </div>

                <div class="max-w-6xl mx-auto">
                    <!-- Query Input -->
                    <div v-if="!imageSearchResults.length && !isCropping" class="mb-8">
                        <form @submit.prevent="updateImageQuery" class="max-w-2xl mx-auto">
                            <div class="relative">
                                <div class="absolute inset-0 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl blur-lg opacity-20"></div>
                                <div class="relative flex gap-2 p-2 bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl shadow-lg transition-all duration-300 focus-within:border-purple-500 dark:focus-within:border-purple-400">
                                    <div class="flex-1 flex items-center gap-3 px-3">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <input
                                            v-model="imageQuery"
                                            type="text"
                                            placeholder="What kind of images are you looking for?"
                                            class="flex-1 py-3 text-sm sm:text-base bg-transparent focus:outline-none dark:text-gray-100 placeholder-gray-400"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        class="px-6 sm:px-8 py-3 sm:py-4 font-semibold text-white bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-md hover:shadow-lg"
                                    >
                                        <span class="hidden sm:inline">Update</span>
                                        <svg class="sm:hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Upload / Crop Interface -->
                    <div v-if="!imageSearchResults.length" class="mb-12">
                        <!-- Initial Images Gallery -->
                        <div v-if="!imagePreview" class="space-y-8">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 text-center">
                                    Select an image or upload your own
                                </h3>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                                    <button
                                        v-for="image in initialImages"
                                        :key="image.id"
                                        @click="selectImageFromGallery(image.url)"
                                        class="group relative aspect-[4/3] overflow-hidden rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-purple-500 dark:hover:border-purple-400 transition-all duration-200 hover:shadow-lg"
                                    >
                                        <img
                                            :src="image.url"
                                            :alt="image.title"
                                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                        />
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                            <div class="absolute bottom-2 left-2 right-2">
                                                <p class="text-white text-sm font-medium truncate">{{ image.title }}</p>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Upload Area -->
                            <div class="relative">
                                <div class="relative">
                                    <div class="absolute inset-0 flex items-center">
                                        <div class="w-full border-t border-gray-300 dark:border-gray-700"></div>
                                    </div>
                                    <div class="relative flex justify-center text-sm">
                                        <span class="px-4 bg-white dark:bg-black text-gray-500 dark:text-gray-400">Or upload your own</span>
                                    </div>
                                </div>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="handleImageUpload"
                                    id="image-upload"
                                    class="hidden"
                                />
                                <label
                                    for="image-upload"
                                    class="mt-6 flex flex-col items-center justify-center w-full p-8 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl hover:border-purple-500 dark:hover:border-purple-400 transition-colors cursor-pointer bg-white dark:bg-gray-900"
                                >
                                    <div class="relative mb-4">
                                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                            </svg>
                                        </div>
                                        <div class="absolute inset-0 w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full blur-xl opacity-50"></div>
                                    </div>
                                    <p class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
                                        Upload an image to search
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                        Click to browse or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        PNG, JPG, GIF up to 10MB
                                    </p>
                                </label>
                            </div>
                        </div>

                        <!-- Cropping Interface -->
                        <div v-else class="space-y-6">
                            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 border border-gray-200 dark:border-gray-800">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        Select the area to search
                                    </h3>
                                    <button
                                        @click="resetImageSearch"
                                        class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                                    >
                                        Cancel
                                    </button>
                                </div>

                                <!-- Image with crop overlay -->
                                <div class="relative max-w-3xl mx-auto mb-6">
                                    <img
                                        :src="imagePreview"
                                        alt="Uploaded image"
                                        class="w-full rounded-lg"
                                    />
                                    <!-- Crop overlay -->
                                    <div
                                        class="absolute border-4 border-purple-500 rounded-lg shadow-lg cursor-move"
                                        :style="{
                                            left: cropArea.x + '%',
                                            top: cropArea.y + '%',
                                            width: cropArea.width + '%',
                                            height: cropArea.height + '%'
                                        }"
                                    >
                                        <div class="absolute inset-0 bg-purple-500/20 rounded"></div>
                                        <!-- Corner handles -->
                                        <div class="absolute -top-2 -left-2 w-4 h-4 bg-white dark:bg-gray-800 border-2 border-purple-500 rounded-full"></div>
                                        <div class="absolute -top-2 -right-2 w-4 h-4 bg-white dark:bg-gray-800 border-2 border-purple-500 rounded-full"></div>
                                        <div class="absolute -bottom-2 -left-2 w-4 h-4 bg-white dark:bg-gray-800 border-2 border-purple-500 rounded-full"></div>
                                        <div class="absolute -bottom-2 -right-2 w-4 h-4 bg-white dark:bg-gray-800 border-2 border-purple-500 rounded-full"></div>
                                    </div>
                                </div>

                                <div class="flex justify-center">
                                    <button
                                        @click="performImageSearch"
                                        class="inline-flex items-center gap-3 px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        Find Similar Images
                                    </button>
                                </div>
                            </div>

                            <div class="text-center text-sm text-gray-500 dark:text-gray-400">
                                <p>Drag the corners to adjust the crop area, then click "Find Similar Images"</p>
                            </div>
                        </div>
                    </div>

                    <!-- Results Masonry Grid -->
                    <div v-if="imageSearchResults.length" class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">
                                    Similar Images Found
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ imageSearchResults.length }} visually similar results
                                </p>
                            </div>
                            <button
                                @click="resetImageSearch"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 border border-gray-200 dark:border-gray-700 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                New Search
                            </button>
                        </div>

                        <!-- Pinterest-style Masonry Grid -->
                        <div class="columns-2 sm:columns-3 lg:columns-4 gap-4 space-y-4">
                            <div
                                v-for="(image, index) in imageSearchResults"
                                :key="image.id"
                                class="break-inside-avoid mb-4"
                            >
                                <div class="group relative bg-gray-100 dark:bg-gray-800 rounded-xl overflow-hidden hover:shadow-xl transition-all duration-300 cursor-pointer">
                                    <img
                                        :src="image.url"
                                        :alt="image.title"
                                        class="w-full h-auto"
                                        loading="lazy"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <div class="absolute bottom-0 left-0 right-0 p-4">
                                            <p class="text-white font-medium text-sm">{{ image.title }}</p>
                                            <div class="flex items-center gap-2 mt-2">
                                                <div class="flex items-center gap-1 text-white/80 text-xs">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                                    </svg>
                                                    {{ Math.floor(Math.random() * 100) + 10 }}
                                                </div>
                                                <div class="flex items-center gap-1 text-white/80 text-xs">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    {{ Math.floor(Math.random() * 500) + 50 }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- About Me Section -->
        <div class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-black">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="text-center mb-12 sm:mb-16">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                        About Me
                    </h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Hi, I'm Nico Orfanos – crafting elegant code from Dortmund, Germany
                    </p>
                </div>

                <div class="max-w-6xl mx-auto">
                    <!-- Profile Card -->
                    <div class="flex flex-col md:flex-row items-center gap-8 mb-16 p-8 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                        <img
                            src="https://github.com/nicoorfi.png"
                            alt="Nico Orfanos"
                            class="w-32 h-32 rounded-full border-4 border-white dark:border-gray-800 shadow-lg"
                        />
                        <div class="flex-1 text-center md:text-left">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                                Nico Orfanos
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                Full-Stack Developer & Elasticsearch Expert
                            </p>
                            <div class="flex flex-wrap justify-center md:justify-start gap-3 mb-4">
                                <span class="inline-flex items-center gap-2 px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm font-medium">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                    </svg>
                                    Dortmund, Germany
                                </span>
                                <span class="inline-flex items-center gap-2 px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-sm font-medium">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                    </svg>
                                    @sigmie
                                </span>
                            </div>
                            <div class="flex flex-wrap justify-center md:justify-start gap-3">
                                <a
                                    href="https://github.com/nicoorfi"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    GitHub
                                </a>
                                <a
                                    href="https://twitter.com/nicoorfi"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-gray-300 dark:hover:border-gray-600 transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
                                    </svg>
                                    Twitter
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Client Reviews -->
                    <div class="mb-12">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 text-center mb-8">
                            Client Reviews
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Review 1 -->
                            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:shadow-lg transition-shadow">
                                <div class="flex items-center gap-1 mb-4">
                                    <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 mb-4 italic">
                                    "Excellent work on implementing our Elasticsearch search functionality. Very professional and responsive."
                                </p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        JD
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">John D.</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">E-commerce Platform</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Review 2 -->
                            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:shadow-lg transition-shadow">
                                <div class="flex items-center gap-1 mb-4">
                                    <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 mb-4 italic">
                                    "Great developer! Built a complex search solution with Laravel and Vue.js. Highly recommended."
                                </p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-teal-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        SM
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">Sarah M.</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">SaaS Startup</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Review 3 -->
                            <div class="p-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl hover:shadow-lg transition-shadow">
                                <div class="flex items-center gap-1 mb-4">
                                    <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 mb-4 italic">
                                    "Nico delivered clean, maintainable code and excellent documentation. A pleasure to work with!"
                                </p>
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center text-white font-semibold">
                                        MK
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">Michael K.</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Digital Agency</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div class="text-center">
                        <div class="inline-flex flex-col items-center p-8 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-950/30 dark:to-purple-950/30 rounded-2xl border border-blue-200 dark:border-blue-800">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                                Ready to work together?
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md">
                                I'm available for freelance projects. Let's build something amazing together!
                            </p>
                            <a
                                href="https://www.upwork.com/freelancers/nicoorfi"
                                target="_blank"
                                class="inline-flex items-center gap-3 px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl"
                            >
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.561 13.158c-1.102 0-2.135-.467-3.074-1.227l.228-1.076.008-.042c.207-1.143.849-3.06 2.839-3.06 1.492 0 2.703 1.212 2.703 2.703-.001 1.489-1.212 2.702-2.704 2.702zm0-8.14c-2.539 0-4.51 1.649-5.31 4.366-1.22-1.834-2.148-4.036-2.687-5.892H7.828v7.112c-.002 1.406-1.141 2.546-2.547 2.548-1.405-.002-2.543-1.143-2.545-2.548V3.492H0v7.112c0 2.914 2.37 5.303 5.281 5.303 2.913 0 5.283-2.389 5.283-5.303v-1.19c.529 1.107 1.182 2.229 1.974 3.221l-1.673 7.873h2.797l1.213-5.71c1.063.679 2.285 1.109 3.686 1.109 3 0 5.439-2.452 5.439-5.45 0-3-2.439-5.439-5.439-5.439z"></path>
                                </svg>
                                Hire me on Upwork
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* No custom styles needed - component handles everything */
</style>
