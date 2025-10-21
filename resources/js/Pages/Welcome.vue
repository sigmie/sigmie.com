<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed, watch, nextTick, onMounted } from "vue";
import axios from "axios";
import AppLayout from "../Layouts/AppLayout.vue";
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
const showAllResults = ref(false);

// Image search state
const imageSearchResults = ref([]);
const imageQuery = ref("nature landscapes");
const initialImages = ref([]);
const isLoadingImages = ref(false);
const isSearchingImages = ref(false);
const imageSearchMode = ref('text'); // 'text' or 'image'
const selectedImageUrl = ref(null);
const imageGridStyle = ref(1); // 1, 2, 3, 4 for different grid styles

// Recommendations state
const recommendationSeeds = ref([]);
const recommendationResults = ref([]);
const mmrValue = ref(0);
const isLoadingRecommendations = ref(false);
const titleSearchQuery = ref("");
const titleSearchResults = ref([]);

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

const imageCodeString = computed(() => {
    if (imageSearchMode.value === 'image') {
        return `->queryImage('https://example.com/image.jpg')`;
    } else {
        const query = imageQuery.value || 'nature landscapes';
        return `->queryString('${query}')`;
    }
});

const imageHighlightedLines = computed(() => {
    return imageSearchMode.value === 'image' ? [8] : [8];
});

const recommendCodeString = computed(() => {
    const seedIdsStr = recommendationSeeds.value.length > 0
        ? `['${recommendationSeeds.value.map(s => s._id).join("', '")}']`
        : "['wireless-earbuds']";

    const mmrLine = mmrValue.value > 0
        ? `    ->mmr(${mmrValue.value.toFixed(2)})`
        : `    // ->mmr(0.5) // Enable for diversity`;

    return `$netflixIndex = app(NetflixTitles::class);
$blueprint = $netflixIndex->properties();

$recommendations = $netflixIndex
    ->newRecommend()
    ->properties($blueprint)
    ->rrf(rrfRankConstant: 60, rankWindowSize: 10)
${mmrLine}
    ->topK(10)
    ->seedIds(${seedIdsStr})
    ->field(fieldName: 'listed_in', weight: 2)
    ->field(fieldName: 'title', weight: 1)
    ->hits();`;
});

const recommendHighlightedLines = computed(() => {
    return mmrValue.value > 0 ? [8] : [];
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

// Generate initial images based on query (fallback)
const generateInitialImages = () => {
    const seed = imageQuery.value.replace(/\s+/g, '-');
    initialImages.value = Array.from({ length: 9 }, (_, i) => ({
        id: i + 1,
        url: `https://picsum.photos/seed/${seed}-${i + 1}/400/300`,
        title: `${imageQuery.value} ${i + 1}`,
    }));
};

// Image search functions
const selectImageFromGallery = async (imageUrl) => {
    selectedImageUrl.value = imageUrl;
    imageSearchMode.value = 'image';
    isSearchingImages.value = true;

    // Clear previous results to show loading state
    imageSearchResults.value = [];

    try {
        const response = await axios.post("/api/search/images/image", {
            image: imageUrl,
        });

        imageSearchResults.value = response.data.results.map((result, i) => ({
            id: i + 1,
            url: result.image,
            title: `Similar Image ${i + 1}`,
            width: 400,
            height: 600
        }));
    } catch (error) {
        console.error("Image search error:", error);
        imageSearchResults.value = [];
    } finally {
        isSearchingImages.value = false;
    }
};

const updateImageQuery = async () => {
    if (!imageQuery.value.trim()) {
        return;
    }

    // Cycle grid style on each query
    imageGridStyle.value = imageGridStyle.value === 4 ? 1 : imageGridStyle.value + 1;

    isLoadingImages.value = true;
    imageSearchMode.value = 'text';

    try {
        const response = await axios.post("/api/search/images/text", {
            query: imageQuery.value,
        });

        initialImages.value = response.data.results.map((result, i) => ({
            id: i + 1,
            url: result.image,
            title: `${imageQuery.value} ${i + 1}`,
        }));

        // Reset search state
        imageSearchResults.value = [];
        selectedImageUrl.value = null;
    } catch (error) {
        console.error("Image query error:", error);
        // Fallback to generating placeholder images
        generateInitialImages();
    } finally {
        isLoadingImages.value = false;
    }
};

const resetImageSearch = () => {
    imageSearchResults.value = [];
    imageSearchMode.value = 'text';
    selectedImageUrl.value = null;
    // Reload initial images
    updateImageQuery();
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

// Recommendations functions
const searchTitles = async () => {
    if (!titleSearchQuery.value.trim()) {
        titleSearchResults.value = [];
        return;
    }

    try {
        const response = await axios.post("/api/search/netflix", {
            query: titleSearchQuery.value,
        });

        titleSearchResults.value = response.data.results || [];
    } catch (error) {
        console.error("Title search error:", error);
        titleSearchResults.value = [];
    }
};

const addSeedTitle = (title) => {
    if (!recommendationSeeds.value.find(s => s._id === title._id)) {
        recommendationSeeds.value.push(title);
        fetchRecommendations();
    }
    titleSearchQuery.value = "";
    titleSearchResults.value = [];
};

const removeSeedTitle = (titleId) => {
    recommendationSeeds.value = recommendationSeeds.value.filter(s => s._id !== titleId);
    if (recommendationSeeds.value.length > 0) {
        fetchRecommendations();
    } else {
        recommendationResults.value = [];
    }
};

const fetchRecommendations = async () => {
    if (recommendationSeeds.value.length === 0) {
        recommendationResults.value = [];
        return;
    }

    isLoadingRecommendations.value = true;

    try {
        const seedIds = recommendationSeeds.value
            .map(s => s._id)
            .filter(id => id != null)
            .map(id => String(id));

        if (seedIds.length === 0) {
            recommendationResults.value = [];
            return;
        }

        const response = await axios.post("/api/recommendations/netflix", {
            seed_ids: seedIds,
            mmr: mmrValue.value > 0 ? mmrValue.value : null,
        });

        recommendationResults.value = response.data.results || [];
    } catch (error) {
        console.error("Recommendations error:", error);
        recommendationResults.value = [];
    } finally {
        isLoadingRecommendations.value = false;
    }
};

// Watch MMR value changes and fetch new recommendations
watch(mmrValue, () => {
    if (recommendationSeeds.value.length > 0) {
        fetchRecommendations();
    }
});

// Initialize image gallery on mount
onMounted(() => {
    updateImageQuery();
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

    <AppLayout :navigation="navigation" :show-top-bar="false">
        <template #default>

        <!-- Netflix Search Demo Section -->
        <div id="semantic-search" class="relative bg-black overflow-hidden scroll-mt-20">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/4 -right-64 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 -left-64 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-12 sm:py-16 lg:py-20 lg:px-8">
                <!-- Logo -->
                <div class="mb-12">
                    <img src="https://raw.githubusercontent.com/sigmie/art/refs/heads/main/logo/svg/logo-full-white.svg" alt="Sigmie" class="w-40 sm:w-48" />
                </div>

                <!-- Header Section -->
                <div class="border border-gray-800 rounded-xl p-6 mb-12 bg-black">
                    <div class="flex flex-col justify-center">
                        <h2 class="text-lg sm:text-xl font-medium text-gray-100 mb-4">
                            Experience Semantic Search
                        </h2>
                        <p class="text-base sm:text-lg text-gray-400 leading-relaxed">
                            Search 8,000+ Netflix titles using natural language. See how Sigmie's semantic search understands intent, not just keywords
                        </p>
                    </div>
                </div>

                <!-- Main Content Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column: Search -->
                    <div class="space-y-6">
                        <!-- Search Box Display or Input -->
                        <div v-if="hasSearched" class="border border-gray-800 rounded-xl p-6 mb-6 bg-black">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <span class="text-lg font-medium text-gray-100">{{ searchQuery }}</span>
                                </div>
                                <span class="text-lg font-medium text-gray-400">Found {{ searchResults.length }} Results</span>
                            </div>
                        </div>

                        <!-- Search Form -->
                        <form v-else @submit.prevent="performSearch()" class="">
                            <div class="border-b border-gray-800 pb-4">
                                <div class="relative flex gap-2 sm:gap-3 items-center">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search by mood, genre, or description..."
                                        class="flex-1 py-3 text-base bg-transparent focus:outline-none text-gray-100 placeholder-gray-500"
                                        :disabled="isSearching"
                                        @keyup.enter="performSearch()"
                                    />
                                </div>
                            </div>
                        </form>

                        <!-- Preset Queries -->
                        <div v-if="!hasSearched" class="space-y-2">
                            <p class="text-sm font-medium text-gray-400 mb-3">Try these popular searches:</p>
                            <div class="flex flex-col gap-2">
                                <button
                                    v-for="preset in presetQueries"
                                    :key="preset.query"
                                    @click="performSearch(preset.query)"
                                    class="group text-left px-4 py-2 text-sm font-medium text-gray-300 bg-gray-800/50 border border-gray-700 rounded-lg hover:border-blue-500 hover:text-blue-400 hover:bg-gray-800 transition-all duration-200"
                                    :disabled="isSearching"
                                >
                                    <span class="flex items-center gap-2">
                                        {{ preset.label }}
                                        <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Results -->
                        <div v-if="hasSearched" class="border border-gray-800 rounded-xl bg-black overflow-hidden">
                            <div v-if="isSearching" class="text-center py-12">
                                <div class="relative inline-flex">
                                    <div class="w-8 h-8 border-3 border-gray-700 border-t-blue-600 rounded-full animate-spin"></div>
                                </div>
                                <p class="mt-3 text-sm text-gray-400">Searching...</p>
                            </div>

                            <div v-else-if="searchResults.length === 0" class="text-center py-12">
                                <p class="text-sm text-gray-400">No results found</p>
                            </div>

                            <div v-else>
                                <!-- Results Header -->
                                <div class="border-b border-gray-800 px-6 py-4 flex items-center justify-between cursor-pointer hover:bg-gray-900/50 transition-colors" @click="showAllResults = !showAllResults">
                                    <h4 class="text-sm font-medium text-gray-400">Results</h4>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-400">{{ showAllResults ? 'Show Less' : 'Show More' }}</span>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': showAllResults }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Results Content -->
                                <div v-show="showAllResults" class="p-6 space-y-6">
                                    <div
                                        v-for="(result, index) in searchResults.slice(0, 10)"
                                        :key="`${result._id || result.title}-${index}`"
                                        class="border-b border-gray-800 pb-6 last:border-b-0"
                                    >
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-3">
                                                    <span class="px-3 py-1 text-xs font-medium rounded-full border border-gray-700 text-gray-400">
                                                        {{ result.type }}
                                                    </span>
                                                    <h5 class="text-lg font-medium text-gray-100">
                                                        {{ result.title }}
                                                    </h5>
                                                </div>
                                                <div class="space-y-1 text-sm text-gray-400">
                                                    <p v-if="result.release_year">Release Date: {{ result.release_year }}</p>
                                                    <p v-if="result.director">Director: {{ result.director }}</p>
                                                    <p v-if="result.cast">Actors: {{ result.cast }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Code Preview -->
                    <div class="hidden lg:block">
                        <div class="sticky top-24">
                            <CodePreview
                                :code="codeString"
                                filename="SearchController.php"
                                :highlight-lines="highlightedLines"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Search Demo Section -->
        <div id="image-search" class="relative border-t border-gray-800 bg-black overflow-hidden scroll-mt-20">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/3 -left-64 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/3 -right-64 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <!-- Hero Text Section -->
                <div class="mb-12">
                    <h2 class="text-lg sm:text-xl font-medium text-gray-100 mb-4">
                        Find with Images
                    </h2>
                    <p class="text-base sm:text-lg text-gray-400 leading-relaxed">
                        Search by text or click any image to discover visually similar content. AI-powered image search that understands visual semantics.
                    </p>
                </div>

                <div class="max-w-6xl mx-auto">
                    <!-- Code Preview -->
                    <div class="mb-8">
                        <CodePreview
                            :code="imageCodeString"
                            filename="ImageSearchController.php"
                            :highlight-lines="imageHighlightedLines"
                            :fade-right="false"
                            :fade-bottom="false"
                            :fade-left="false"
                        />
                    </div>

                    <!-- Query Input - Always Visible -->
                    <form @submit.prevent="updateImageQuery" class="mb-8">
                        <div class="border-b border-gray-800 pb-4">
                            <div class="relative flex gap-2 sm:gap-3 items-center">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <input
                                    v-model="imageQuery"
                                    type="text"
                                    placeholder="What kind of images are you looking for?"
                                    class="flex-1 py-3 text-base bg-transparent focus:outline-none text-gray-100 placeholder-gray-500"
                                    :disabled="isLoadingImages || isSearchingImages"
                                    @keyup.enter="updateImageQuery()"
                                />
                            </div>
                        </div>
                    </form>

                    <!-- Initial Images Gallery - Always Visible -->
                    <div v-if="!imageSearchResults.length" class="mb-12">
                        <!-- Loading State for Initial Images -->
                        <div v-if="isLoadingImages" class="text-center py-12">
                            <div class="relative inline-flex">
                                <div class="w-12 h-12 border-4 border-gray-200 dark:border-gray-700 border-t-purple-600 rounded-full animate-spin"></div>
                                <div class="absolute inset-0 w-12 h-12 border-4 border-transparent border-t-pink-600 rounded-full animate-spin" style="animation-duration: 1.5s; animation-direction: reverse;"></div>
                            </div>
                            <p class="mt-4 text-base font-medium text-gray-600 dark:text-gray-400">Loading images...</p>
                        </div>

                        <!-- Initial Images Gallery - Single Row -->
                        <div v-else class="overflow-x-auto pb-4">
                            <div class="flex gap-4" style="min-width: max-content;">
                                <button
                                    v-for="image in initialImages.slice(0, 5)"
                                    :key="image.id"
                                    @click="selectImageFromGallery(image.url)"
                                    :disabled="isSearchingImages"
                                    class="group relative flex-shrink-0 w-64 h-40 overflow-hidden rounded-xl border border-gray-800 hover:border-purple-500 transition-all duration-200 hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                                >
                                    <img :src="image.url" :alt="image.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Searching State -->
                    <div v-if="isSearchingImages && !imageSearchResults.length" class="text-center py-16">
                        <div class="relative inline-flex">
                            <div class="w-12 h-12 border-4 border-gray-200 dark:border-gray-700 border-t-purple-600 rounded-full animate-spin"></div>
                            <div class="absolute inset-0 w-12 h-12 border-4 border-transparent border-t-pink-600 rounded-full animate-spin" style="animation-duration: 1.5s; animation-direction: reverse;"></div>
                        </div>
                        <p class="mt-4 text-base font-medium text-gray-600 dark:text-gray-400">Finding similar images...</p>
                    </div>

                    <!-- Results Masonry Grid -->
                    <div v-if="imageSearchResults.length && !isSearchingImages" class="space-y-6">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                            <div class="text-center sm:text-left">
                                <h3 class="text-xl sm:text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Similar Images Found
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ imageSearchResults.length }} visually similar results • Click any to search again
                                </p>
                            </div>
                            <button
                                @click="resetImageSearch"
                                class="inline-flex items-center gap-2 px-6 py-3 text-base font-medium text-white bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-md hover:shadow-lg"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                New Search
                            </button>
                        </div>

                        <!-- Pinterest-style Masonry Grid -->
                        <div class="columns-2 sm:columns-3 lg:columns-4 gap-4 space-y-4">
                            <button
                                v-for="(image, index) in imageSearchResults"
                                :key="image.id"
                                @click="selectImageFromGallery(image.url)"
                                class="break-inside-avoid mb-4 block w-full"
                            >
                                <div class="group relative bg-gray-100 dark:bg-gray-800 rounded-xl overflow-hidden hover:shadow-2xl transition-all duration-300 cursor-pointer hover:-translate-y-1 border-2 border-transparent hover:border-purple-500">
                                    <img
                                        :src="image.url"
                                        :alt="image.title"
                                        class="w-full h-auto group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                    />
                                    <div class="absolute inset-0 bg-gradient-to-t from-purple-900/80 via-purple-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <div class="absolute inset-0 flex flex-col items-center justify-center gap-2">
                                            <div class="bg-white dark:bg-gray-900 rounded-full p-3 transform scale-90 group-hover:scale-100 transition-transform duration-200 shadow-xl">
                                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                            <span class="text-white font-medium text-xs bg-purple-600/90 px-3 py-1 rounded-full">
                                                Search Similar
                                            </span>
                                        </div>
                                        <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/80 to-transparent">
                                            <p class="text-white font-medium text-sm">{{ image.title }}</p>
                                        </div>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations Demo Section -->
        <div id="recommendations" class="relative border-t border-gray-800 bg-black overflow-hidden scroll-mt-20">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/4 -right-64 w-96 h-96 bg-green-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 -left-64 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="text-center mb-10 sm:mb-14">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-950/30 dark:to-blue-950/30 border border-green-200 dark:border-green-800 rounded-full mb-6">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        <span class="text-sm font-medium bg-gradient-to-r from-green-600 to-blue-600 bg-clip-text text-transparent">
                            Smart Recommendations
                        </span>
                    </div>
                    <h2 class="text-lg sm:text-xl font-medium text-gray-900 dark:text-gray-100 mb-4 sm:mb-6">
                        Discover with
                        <span class="bg-gradient-to-r from-green-600 via-blue-600 to-purple-600 bg-clip-text text-transparent">
                            Recommendations
                        </span>
                    </h2>
                    <p class="text-base sm:text-lg lg:text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto leading-relaxed">
                        Select titles you love and get personalized recommendations. Fine-tune diversity with MMR to balance similarity and variety.
                    </p>
                </div>

                <div class="max-w-6xl mx-auto">
                    <!-- Code Preview -->
                    <div class="mb-8 sm:mb-10">
                        <CodePreview
                            :code="recommendCodeString"
                            filename="NetflixSearchController.php"
                            :highlight-lines="recommendHighlightedLines"
                        />
                    </div>

                    <!-- Main Content Area -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column: Title Selector -->
                        <div class="lg:col-span-1 space-y-4">
                            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-lg">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Select Titles
                                </h3>

                                <!-- Title Search Input -->
                                <div class="relative mb-4">
                                    <input
                                        v-model="titleSearchQuery"
                                        @input="searchTitles"
                                        type="text"
                                        placeholder="Search for titles..."
                                        class="w-full px-4 py-3 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 dark:text-gray-100 placeholder-gray-400"
                                    />
                                    <!-- Search Results Dropdown -->
                                    <div v-if="titleSearchResults.length > 0" class="absolute z-10 w-full mt-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl max-h-64 overflow-y-auto">
                                        <button
                                            v-for="title in titleSearchResults.slice(0, 10)"
                                            :key="title._id"
                                            @click="addSeedTitle(title)"
                                            class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-b-0 transition-colors"
                                        >
                                            <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">{{ title.title }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ title.type }} • {{ title.release_year }}
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                <!-- Selected Seeds -->
                                <div v-if="recommendationSeeds.length > 0" class="space-y-2">
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                        Selected ({{ recommendationSeeds.length }}):
                                    </p>
                                    <div
                                        v-for="seed in recommendationSeeds"
                                        :key="seed._id"
                                        class="group relative bg-gradient-to-r from-green-50 to-blue-50 dark:from-green-950/30 dark:to-blue-950/30 border border-green-200 dark:border-green-800 rounded-lg p-3"
                                    >
                                        <button
                                            @click="removeSeedTitle(seed._id)"
                                            class="absolute top-2 right-2 shrink-0 p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>

                                        <div class="pr-6">
                                            <div class="mb-2">
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2">{{ seed.title }}</p>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                                    {{ seed.type }} • {{ seed.release_year }}
                                                </p>
                                            </div>

                                            <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                                                <div v-if="seed.director" class="flex items-start gap-1.5">
                                                    <svg class="w-3 h-3 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <span class="line-clamp-1 flex-1">{{ seed.director }}</span>
                                                </div>
                                                <div v-if="seed.cast" class="flex items-start gap-1.5">
                                                    <svg class="w-3 h-3 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                    <span class="line-clamp-1 flex-1">{{ seed.cast }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty State -->
                                <div v-else class="text-center py-8">
                                    <div class="inline-flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Search and select titles to get recommendations
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: MMR Slider & Results -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- MMR Slider -->
                            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-lg">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                            </svg>
                                            Diversity Control (MMR)
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            Balance similarity and variety
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                            {{ mmrValue.toFixed(1) }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ mmrValue === 0 ? 'Disabled' : mmrValue < 0.5 ? 'Similar' : 'Diverse' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="relative">
                                    <input
                                        v-model.number="mmrValue"
                                        type="range"
                                        min="0"
                                        max="1"
                                        step="0.1"
                                        :disabled="recommendationSeeds.length === 0"
                                        class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                    />
                                    <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        <span>0.0 (Similar)</span>
                                        <span>0.5</span>
                                        <span>1.0 (Diverse)</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recommendations Results -->
                            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-lg min-h-[400px]">
                                <!-- Loading State -->
                                <div v-if="isLoadingRecommendations" class="flex flex-col items-center justify-center py-16">
                                    <div class="relative inline-flex">
                                        <div class="w-12 h-12 border-4 border-gray-200 dark:border-gray-700 border-t-green-600 rounded-full animate-spin"></div>
                                        <div class="absolute inset-0 w-12 h-12 border-4 border-transparent border-t-blue-600 rounded-full animate-spin" style="animation-duration: 1.5s; animation-direction: reverse;"></div>
                                    </div>
                                    <p class="mt-4 text-base font-medium text-gray-600 dark:text-gray-400">
                                        Finding recommendations...
                                    </p>
                                </div>

                                <!-- No Seeds Selected -->
                                <div v-else-if="recommendationSeeds.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-100 to-blue-100 dark:from-green-900/30 dark:to-blue-900/30 rounded-full mb-4">
                                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                        Start by selecting titles
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 max-w-sm">
                                        Search and add titles you like to receive personalized recommendations based on your preferences
                                    </p>
                                </div>

                                <!-- Results Grid -->
                                <div v-else-if="recommendationResults.length > 0" class="space-y-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                            Recommended for you
                                        </h4>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ recommendationResults.length }} results
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div
                                            v-for="(result, index) in recommendationResults"
                                            :key="`${result._id || result.title}-${index}`"
                                            class="group relative bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-green-500 dark:hover:border-green-400 hover:shadow-lg transition-all duration-200"
                                        >
                                            <div class="absolute top-3 right-3">
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full"
                                                    :class="result.type === 'Movie'
                                                        ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                                                        : 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'">
                                                    {{ result.type }}
                                                </span>
                                            </div>

                                            <div class="pr-16 mb-3">
                                                <h5 class="font-medium text-sm text-gray-900 dark:text-gray-100 line-clamp-2 mb-2 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                                                    {{ result.title }}
                                                </h5>
                                                <p v-if="result.release_year" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    {{ result.release_year }}
                                                </p>
                                            </div>

                                            <div class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                                                <div v-if="result.director" class="flex items-start gap-2">
                                                    <svg class="w-3.5 h-3.5 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <span class="line-clamp-1 flex-1">{{ result.director }}</span>
                                                </div>
                                                <div v-if="result.cast" class="flex items-start gap-2">
                                                    <svg class="w-3.5 h-3.5 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                    <span class="line-clamp-2 flex-1">{{ result.cast }}</span>
                                                </div>
                                                <div v-if="result.listed_in" class="flex items-start gap-2">
                                                    <svg class="w-3.5 h-3.5 shrink-0 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                    </svg>
                                                    <span class="line-clamp-2 flex-1">{{ result.listed_in }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- No Results -->
                                <div v-else class="flex flex-col items-center justify-center py-16 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                        No recommendations found
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Try selecting different titles or adjusting the MMR slider
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- About Me Section -->
        <div id="about" class="border-t border-gray-800 bg-black scroll-mt-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="text-center mb-12 sm:mb-16">
                    <h2 class="text-lg sm:text-xl font-medium text-gray-100 mb-4">
                        About Me
                    </h2>
                    <p class="text-lg text-gray-400 max-w-2xl mx-auto">
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
                            <h3 class="text-lg font-medium text-gray-100 mb-2">
                                Nico Orfanos
                            </h3>
                            <p class="text-gray-400 mb-4">
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
                        <h3 class="text-lg font-medium text-gray-100 text-center mb-8">
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
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-medium">
                                        JD
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">John D.</p>
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
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-teal-600 rounded-full flex items-center justify-center text-white font-medium">
                                        SM
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">Sarah M.</p>
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
                                    <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center text-white font-medium">
                                        MK
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100">Michael K.</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Digital Agency</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CTA -->
                    <div class="text-center">
                        <div class="inline-flex flex-col items-center p-8 bg-gradient-to-br from-blue-950/30 to-purple-950/30 rounded-2xl border border-blue-800">
                            <h3 class="text-lg font-medium text-gray-100 mb-3">
                                Ready to work together?
                            </h3>
                            <p class="text-gray-400 mb-6 max-w-md">
                                I'm available for freelance projects. Let's build something amazing together!
                            </p>
                            <a
                                href="https://www.upwork.com/freelancers/nicoorfi"
                                target="_blank"
                                class="inline-flex items-center gap-3 px-8 py-4 text-lg font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl"
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
        </template>
    </AppLayout>
</template>

<style scoped>
/* No custom styles needed - component handles everything */
</style>
