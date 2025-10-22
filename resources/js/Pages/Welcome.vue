<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed, watch, nextTick, onMounted } from "vue";
import axios from "axios";
import AppLayout from "../Layouts/AppLayout.vue";
import CodePreview from "../components/CodePreview.vue";
import QueryInput from "../components/QueryInput.vue";
import Menu from "../components/Menu.vue";
import Container from "../components/Container.vue";
import SearchIcon from "../components/icons/SearchIcon.vue";
import ImageIcon from "../components/icons/ImageIcon.vue";
import ImageHoverOverlay from "../components/ImageHoverOverlay.vue";
import Logo from "../components/Logo.vue";

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

// Cart and Recommendations state
const cartItems = ref([]);
const recommendationResults = ref([]);
const mmrValue = ref(0);
const isLoadingRecommendations = ref(false);
const isLoadingInitialCart = ref(true);

const presetQueries = [
    { label: "Romantic Comedy", query: "romantic comedy light-hearted love" },
    { label: "Action Thriller", query: "action thriller intense suspense" },
    { label: "Sci-Fi", query: "science fiction futuristic space" },
    { label: "Drama", query: "drama emotional powerful" },
    { label: "Documentary", query: "documentary real-life educational" },
    { label: "Horror", query: "horror scary terrifying" }
];

const codeString = computed(() => {
    const query = searchQuery.value || 'romantic comedy';

    let filterLine = '';
    if (selectedType.value === 'all') {
        filterLine = `    ->facets('type')`;
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
    ->retrieve(['title', 'type', 'description', 'cast', 'release_year'])
    ->size(20);

$response = $search->get();`;
});

const highlightedLines = computed(() => {
    const lines = [];

    // Highlight line 5 (semantic) when search is active
    if (hasSearched.value) {
        lines.push(5);
    }

    // Highlight line 10 (filters) when a specific filter is selected
    if (hasSearched.value && selectedType.value !== 'all') {
        lines.push(10);
    }

    // Highlight line 11 (queryString) when there's a search query
    if (searchQuery.value) {
        lines.push(11);
    }

    return lines;
});

const imageCodeString = computed(() => {
    const query = imageQuery.value || 'nature landscapes';

    if (imageSearchMode.value === 'image') {
        return `->queryImage('${selectedImageUrl.value || 'https://example.com/image.jpg'}')`;
    } else {
        return `->queryString('${query}')`;
    }
});

const imageHighlightedLines = computed(() => {
    // Highlight the single line when active
    return imageSearchMode.value === 'image' || imageQuery.value ? [1] : [];
});

const recommendCodeString = computed(() => {
    const seedIdsStr = cartItems.value.length > 0
        ? `['${cartItems.value.map(s => s._id).join("', '")}']`
        : "['sample-product-id']";

    const mmrLine = mmrValue.value > 0
        ? `    ->mmr(${mmrValue.value.toFixed(2)})`
        : `    // ->mmr(0.5) // Enable for diversity`;

    return `$asosIndex = app(AsosProducts::class);
$blueprint = $asosIndex->properties();

$recommendations = $asosIndex
    ->newRecommend()
    ->properties($blueprint)
    ->rrf(rrfRankConstant: 60, rankWindowSize: 10)
${mmrLine}
    ->topK(10)
    ->seedIds(${seedIdsStr})
    ->field(fieldName: 'name', weight: 1)
    ->field(fieldName: 'description', weight: 1)
    ->field(fieldName: 'category', weight: 0.5)
    ->hits();`;
});

const recommendHighlightedLines = computed(() => {
    const lines = [];

    // Highlight MMR line when enabled
    if (mmrValue.value > 0) {
        lines.push(8);
    }

    // Highlight seedIds line when items in cart
    if (cartItems.value.length > 0) {
        lines.push(10);
    }

    return lines;
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
    isLoadingImages.value = true;

    try {
        const response = await axios.post("/api/search/images/image", {
            image: imageUrl,
        });

        initialImages.value = response.data.results.map((result, i) => ({
            id: i + 1,
            url: result.image,
            title: `Similar Image ${i + 1}`,
        }));
    } catch (error) {
        console.error("Image search error:", error);
    } finally {
        isLoadingImages.value = false;
    }
};

const updateImageQuery = async () => {
    if (!imageQuery.value.trim()) {
        return;
    }

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

        selectedImageUrl.value = null;
    } catch (error) {
        console.error("Image query error:", error);
        // Fallback to generating placeholder images
        generateInitialImages();
    } finally {
        isLoadingImages.value = false;
    }
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

// Cart and Recommendations functions
const initializeCart = async () => {
    isLoadingInitialCart.value = true;
    try {
        // Fetch initial products for the cart
        const response = await axios.post("/api/search/products", {
            query: "casual wear",
        });

        if (response.data.results && response.data.results.length >= 2) {
            // Add first 2 products to cart
            cartItems.value = response.data.results.slice(0, 2);
            // Fetch initial recommendations
            fetchRecommendations();
        }
    } catch (error) {
        console.error("Cart initialization error:", error);
    } finally {
        isLoadingInitialCart.value = false;
    }
};

const addToCart = (product) => {
    // Check if product already in cart
    if (!cartItems.value.find(item => item._id === product._id)) {
        cartItems.value.push(product);
        fetchRecommendations();
    }
};

const removeFromCart = (productId) => {
    // Don't allow removing if only 1 item left
    if (cartItems.value.length <= 1) {
        return;
    }

    cartItems.value = cartItems.value.filter(item => item._id !== productId);
    fetchRecommendations();
};

const fetchRecommendations = async () => {
    if (cartItems.value.length === 0) {
        recommendationResults.value = [];
        return;
    }

    isLoadingRecommendations.value = true;

    try {
        const seedIds = cartItems.value
            .map(item => item._id)
            .filter(id => id != null)
            .map(id => String(id));

        if (seedIds.length === 0) {
            recommendationResults.value = [];
            return;
        }

        const response = await axios.post("/api/recommendations/products", {
            seed_ids: seedIds,
            mmr: mmrValue.value > 0 ? mmrValue.value : null,
        });

        // Filter out products already in cart
        const cartIds = new Set(cartItems.value.map(item => item._id));
        recommendationResults.value = (response.data.results || []).filter(
            result => !cartIds.has(result._id)
        );
    } catch (error) {
        console.error("Recommendations error:", error);
        recommendationResults.value = [];
    } finally {
        isLoadingRecommendations.value = false;
    }
};

// Watch MMR value changes and fetch new recommendations
watch(mmrValue, () => {
    if (cartItems.value.length > 0) {
        fetchRecommendations();
    }
});

// Initialize searches on mount
onMounted(() => {
    updateImageQuery();
    // Load initial Netflix search results
    performSearch('romantic comedy');
    // Initialize cart with products
    initializeCart();
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

        <!-- Product Search Demo Section -->
        <div id="semantic-search" class="relative bg-black overflow-hidden scroll-mt-20">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/4 -right-64 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 -left-64 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-12 sm:py-16 lg:py-20 lg:px-8">
                <!-- Logo -->
                <div class="mb-12">
                    <Logo class="w-40 sm:w-48" />
                </div>

                <!-- Header Section -->
                <div class="mb-12">
                    <div class="relative border border-gray-800 rounded-xl p-6 sm:p-8 bg-black/50 backdrop-blur-sm overflow-hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
                        <!-- Left Column: Title & Subtitle -->
                        <div class="text-left mb-12">
                            <h2 class="text-lg sm:text-xl font-medium text-gray-100 mb-4">
                                Experience Semantic Search
                            </h2>
                            <p class="text-base sm:text-lg text-gray-400 leading-relaxed">
                                Search thousands of fashion products using natural language. See how Sigmie's semantic search understands intent, not just keywords
                            </p>
                        </div>

                        <!-- Right Column: SVG Graphic -->
                        <div class="flex justify-center md:justify-end md:absolute md:right-0 md:bottom-0 md:pr-8">
                            <svg width="213" height="152" viewBox="0 0 213 152" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-48 h-auto">
                                <g style="mix-blend-mode:color-dodge">
                                    <foreignObject x="-24" y="36.1865" width="154.137" height="319.813"><div xmlns="http://www.w3.org/1999/xhtml" style="backdrop-filter:blur(12px);clip-path:url(#bgblur_0_856_6183_clip_path);height:100%;width:100%"></div></foreignObject>
                                    <g data-figma-bg-blur-radius="24">
                                        <path d="M0 332V60.1865L106.137 120.374V332H0Z" fill="url(#paint0_linear_856_6183)" fill-opacity="0.8"/>
                                        <path d="M105.637 120.665V331.5H0.5V61.0439L105.637 120.665Z" stroke="url(#paint1_linear_856_6183)" stroke-opacity="0.6"/>
                                    </g>
                                    <foreignObject x="82.1367" y="36.1865" width="154.137" height="319.813"><div xmlns="http://www.w3.org/1999/xhtml" style="backdrop-filter:blur(12px);clip-path:url(#bgblur_1_856_6183_clip_path);height:100%;width:100%"></div></foreignObject>
                                    <g data-figma-bg-blur-radius="24">
                                        <path d="M212.273 332V60.1865L106.137 120.374V332H212.273Z" fill="url(#paint2_linear_856_6183)" fill-opacity="0.8"/>
                                        <path d="M106.637 120.665V331.5H211.773V61.0439L106.637 120.665Z" stroke="url(#paint3_linear_856_6183)" stroke-opacity="0.6"/>
                                    </g>
                                    <foreignObject x="-24" y="-24" width="260.273" height="168.374"><div xmlns="http://www.w3.org/1999/xhtml" style="backdrop-filter:blur(12px);clip-path:url(#bgblur_2_856_6183_clip_path);height:100%;width:100%"></div></foreignObject>
                                    <g data-figma-bg-blur-radius="24">
                                        <path d="M0 60.1872L106.137 0L212.273 60.1872L106.137 120.374L0 60.1872Z" fill="url(#paint4_linear_856_6183)" fill-opacity="0.8"/>
                                        <path d="M211.259 60.1865L106.136 119.799L1.01367 60.1865L106.136 0.574219L211.259 60.1865Z" stroke="url(#paint5_linear_856_6183)" stroke-opacity="0.6"/>
                                    </g>
                                </g>
                                <defs>
                                    <clipPath id="bgblur_0_856_6183_clip_path" transform="translate(24 -36.1865)"><path d="M0 332V60.1865L106.137 120.374V332H0Z"/></clipPath>
                                    <clipPath id="bgblur_1_856_6183_clip_path" transform="translate(-82.1367 -36.1865)"><path d="M212.273 332V60.1865L106.137 120.374V332H212.273Z"/></clipPath>
                                    <clipPath id="bgblur_2_856_6183_clip_path" transform="translate(24 24)"><path d="M0 60.1872L106.137 0L212.273 60.1872L106.137 120.374L0 60.1872Z"/></clipPath>
                                    <linearGradient id="paint0_linear_856_6183" x1="53.0683" y1="89.3093" x2="53.0683" y2="351.091" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="white"/>
                                        <stop offset="1" stop-color="white" stop-opacity="0"/>
                                    </linearGradient>
                                    <linearGradient id="paint1_linear_856_6183" x1="106.137" y1="119.403" x2="2.26511" y2="334.912" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="white"/>
                                        <stop offset="1" stop-color="white" stop-opacity="0"/>
                                    </linearGradient>
                                    <linearGradient id="paint2_linear_856_6183" x1="159.205" y1="89.3093" x2="159.205" y2="351.091" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="white"/>
                                        <stop offset="1" stop-color="white" stop-opacity="0"/>
                                    </linearGradient>
                                    <linearGradient id="paint3_linear_856_6183" x1="113.579" y1="120.697" x2="212.273" y2="332" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="white"/>
                                        <stop offset="1" stop-color="white" stop-opacity="0"/>
                                    </linearGradient>
                                    <linearGradient id="paint4_linear_856_6183" x1="119.727" y1="-5.10812e-07" x2="106.137" y2="120.374" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="white"/>
                                        <stop offset="1" stop-color="white" stop-opacity="0.69"/>
                                    </linearGradient>
                                    <linearGradient id="paint5_linear_856_6183" x1="106.137" y1="0" x2="106.137" y2="120.374" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="white"/>
                                        <stop offset="1" stop-color="white" stop-opacity="0.4"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column: Search -->
                    <div class="rounded-lg p-6" style="background: linear-gradient(59.66deg, #0C0D0F 0%, #07080A 100%); border: 1px solid rgba(255, 255, 255, 0.06); box-shadow: 0px 1px 0px 1px rgba(255, 255, 255, 0.1) inset;">
                        <div class="space-y-8">
                            <!-- Search Form -->
                            <QueryInput
                                v-model="searchQuery"
                                placeholder="Search by mood, genre, or description..."
                                :disabled="isSearching"
                                @submit="performSearch()"
                            >
                                <template #icon>
                                    <SearchIcon />
                                </template>
                            </QueryInput>

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
                            <Menu
                                v-if="hasSearched"
                                :items="searchResults"
                                :loading="isSearching"
                                :max-items="10"
                                empty-message="No results found"
                            />
                        </div>
                    </div>

                    <!-- Right Column: Code Preview -->
                    <div class="hidden lg:block">
                        <div class="sticky top-24">
                            <CodePreview
                                :code="codeString"
                                filename="NetflixSearchController.php"
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
                <div class="max-w-6xl mx-auto">
                    <!-- Hero Text Section -->
                    <div class="mb-12">
                        <h2 class="text-lg sm:text-xl font-medium text-gray-100 mb-4">
                            Find with Images
                        </h2>
                        <p class="text-base sm:text-lg text-gray-400 leading-relaxed">
                            Search by text or click any image to discover visually similar content. AI-powered image search that understands visual semantics.
                        </p>
                    </div>

                    <!-- Code Preview -->
                    <div class="mb-16">
                        <CodePreview
                            :code="imageCodeString"
                            filename="ImageSearchController.php"
                            :highlight-lines="imageHighlightedLines"
                            :fade-right="true"
                            :fade-bottom="true"
                            :fade-left="false"
                            :fade-width-right="200"
                            :fade-height-bottom="48"
                        />
                    </div>

                    <!-- Query Input - Always Visible -->
                    <div class="mb-8">
                        <QueryInput
                            v-model="imageQuery"
                            placeholder="What kind of images are you looking for?"
                            :disabled="isLoadingImages || isSearchingImages"
                            @submit="updateImageQuery"
                        >
                            <template #icon>
                                <ImageIcon />
                            </template>
                        </QueryInput>
                    </div>

                    <!-- Images Gallery -->
                    <div class="mb-12">
                        <!-- Grid Layout -->
                        <div class="grid grid-cols-6 grid-rows-2 gap-2 h-[400px] transition-all duration-300" :class="{ 'blur-sm opacity-50': isLoadingImages }">
                            <!-- Image 1: 1 column × 2 rows -->
                            <button
                                v-if="initialImages[0]"
                                @click="selectImageFromGallery(initialImages[0].url)"
                                :disabled="isLoadingImages"
                                class="group relative col-span-1 row-span-2 overflow-hidden rounded-md border border-gray-800 hover:border-gray-600 transition-all duration-200 hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <img :src="initialImages[0].url" :alt="initialImages[0].title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <ImageHoverOverlay />
                            </button>

                            <!-- Image 2: 3 columns × 2 rows -->
                            <button
                                v-if="initialImages[1]"
                                @click="selectImageFromGallery(initialImages[1].url)"
                                :disabled="isLoadingImages"
                                class="group relative col-span-3 row-span-2 overflow-hidden rounded-md border border-gray-800 hover:border-gray-600 transition-all duration-200 hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <img :src="initialImages[1].url" :alt="initialImages[1].title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <ImageHoverOverlay />
                            </button>

                            <!-- Image 3: 2 columns × 1 row -->
                            <button
                                v-if="initialImages[2]"
                                @click="selectImageFromGallery(initialImages[2].url)"
                                :disabled="isLoadingImages"
                                class="group relative col-span-2 row-span-1 overflow-hidden rounded-md border border-gray-800 hover:border-gray-600 transition-all duration-200 hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <img :src="initialImages[2].url" :alt="initialImages[2].title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <ImageHoverOverlay />
                            </button>

                            <!-- Image 4: 2 columns × 1 row -->
                            <button
                                v-if="initialImages[3]"
                                @click="selectImageFromGallery(initialImages[3].url)"
                                :disabled="isLoadingImages"
                                class="group relative col-span-2 row-span-1 overflow-hidden rounded-md border border-gray-800 hover:border-gray-600 transition-all duration-200 hover:shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                            >
                                <img :src="initialImages[3].url" :alt="initialImages[3].title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <ImageHoverOverlay />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recommendations Demo Section -->
        <div id="recommendations" class="relative border-t border-gray-800 bg-black overflow-hidden scroll-mt-20">
            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="text-center mb-10 sm:mb-14">
                    <h2 class="text-lg sm:text-xl font-medium text-white mb-4 sm:mb-6">
                        Discover with Recommendations
                    </h2>
                    <p class="text-base sm:text-lg text-gray-400 max-w-3xl mx-auto leading-relaxed">
                        Select products you love and get personalized recommendations. Fine-tune diversity with MMR to balance similarity and variety.
                    </p>
                </div>

                <div class="max-w-6xl mx-auto">

                    <!-- Main Content Area -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column: Shopping Cart -->
                        <div class="lg:col-span-1">
                            <div class="bg-gray-950 border border-gray-800 rounded-lg p-6">
                                <h3 class="text-base font-medium text-white mb-4 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                    Cart ({{ cartItems.length }})
                                </h3>

                                <!-- Loading State -->
                                <div v-if="isLoadingInitialCart" class="text-center py-12">
                                    <div class="w-8 h-8 border-4 border-gray-800 border-t-gray-500 rounded-full animate-spin mx-auto"></div>
                                    <p class="mt-3 text-sm text-gray-400">Loading cart...</p>
                                </div>

                                <!-- Cart Items -->
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="item in cartItems"
                                        :key="item._id"
                                        class="relative group bg-gray-900 border border-gray-800 rounded-lg overflow-hidden hover:border-gray-700 transition-colors"
                                    >
                                        <!-- Product Image with Title Overlay -->
                                        <div class="relative aspect-square w-full">
                                            <img
                                                v-if="item.images && item.images.length > 0"
                                                :src="item.images[0]"
                                                :alt="item.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <!-- Title Overlay -->
                                            <div class="absolute inset-x-0 bottom-0 bg-black/30 p-2">
                                                <p class="text-xs font-medium text-white line-clamp-2">{{ item.name }}</p>
                                            </div>
                                        </div>

                                        <!-- Remove Button -->
                                        <div v-if="cartItems.length > 1" class="p-2 flex justify-end">
                                            <button
                                                @click="removeFromCart(item._id)"
                                                class="p-1.5 text-gray-500 hover:text-gray-300 transition-colors"
                                                title="Remove from cart"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Recommendations & Slider -->
                        <div class="lg:col-span-2">
                            <!-- Recommendations Results -->
                            <div class="bg-gray-950 border border-gray-800 rounded-lg p-6 min-h-[400px]">
                                <!-- MMR Slider -->
                                <div class="mb-6 pb-6 border-b border-gray-800">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h3 class="text-base font-medium text-white flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                                </svg>
                                                Diversity Control (MMR)
                                            </h3>
                                            <p class="text-xs text-gray-400 mt-1">
                                                Balance similarity and variety
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-medium text-white">
                                                {{ mmrValue.toFixed(1) }}
                                            </div>
                                            <div class="text-xs text-gray-500">
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
                                            :disabled="cartItems.length === 0"
                                            class="w-full h-2 bg-gray-800 rounded-lg appearance-none cursor-pointer accent-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                        />
                                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                                            <span>0.0</span>
                                            <span>0.5</span>
                                            <span>1.0</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Loading State -->
                                <div v-if="isLoadingRecommendations" class="flex flex-col items-center justify-center py-16">
                                    <div class="w-10 h-10 border-4 border-gray-800 border-t-gray-500 rounded-full animate-spin"></div>
                                    <p class="mt-4 text-sm text-gray-400">
                                        Finding recommendations...
                                    </p>
                                </div>

                                <!-- Empty Cart State -->
                                <div v-else-if="cartItems.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                                    <div class="inline-flex items-center justify-center w-14 h-14 bg-gray-900 rounded-lg mb-4">
                                        <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-base font-medium text-white mb-2">
                                        Your cart is empty
                                    </h4>
                                    <p class="text-sm text-gray-400 max-w-sm">
                                        Add products to your cart to receive personalized recommendations
                                    </p>
                                </div>

                                <!-- Results Grid -->
                                <div v-else-if="recommendationResults.length > 0" class="space-y-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-base font-medium text-white">
                                            Recommended
                                        </h4>
                                        <span class="text-xs text-gray-500">
                                            {{ recommendationResults.length }} results
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div
                                            v-for="(result, index) in recommendationResults.slice(0, 4)"
                                            :key="`${result._id || result.name}-${index}`"
                                            @click="addToCart(result)"
                                            class="group relative bg-gray-900 border border-gray-800 rounded-lg overflow-hidden hover:border-gray-700 transition-all cursor-pointer"
                                        >
                                            <!-- Product Image with Title Overlay -->
                                            <div v-if="result.images && result.images.length > 0" class="relative aspect-square w-full overflow-hidden bg-black">
                                                <img
                                                    :src="result.images[0]"
                                                    :alt="result.name"
                                                    class="w-full h-full object-cover opacity-90 group-hover:opacity-100 group-hover:scale-105 transition-all duration-300"
                                                />
                                                <!-- Title Overlay on Image -->
                                                <div class="absolute inset-x-0 bottom-0 bg-black/30 p-2">
                                                    <p class="text-xs font-medium text-white line-clamp-2">
                                                        {{ result.name }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Add Icon -->
                                            <div class="p-2 flex justify-center">
                                                <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                                    <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                    <span class="group-hover:text-gray-400 transition-colors">Add</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- No Results -->
                                <div v-else class="flex flex-col items-center justify-center py-16 text-center">
                                    <div class="inline-flex items-center justify-center w-14 h-14 bg-gray-900 rounded-lg mb-4">
                                        <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h4 class="text-base font-medium text-white mb-2">
                                        No recommendations found
                                    </h4>
                                    <p class="text-sm text-gray-400">
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
                    <div class="max-w-lg mx-auto mb-16 p-8 bg-gradient-to-br from-gray-900 to-gray-950 rounded-3xl border border-gray-800 shadow-2xl">
                        <div class="flex items-start gap-6 mb-6">
                            <img
                                src="https://github.com/nicoorfi.png"
                                alt="Nico Orfanos"
                                class="w-20 h-20 rounded-2xl border-2 border-gray-700 shadow-lg flex-shrink-0"
                            />
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xl font-semibold text-white mb-1">
                                    Nico Orfanos
                                </h3>
                                <p class="text-gray-300 text-sm mb-3">
                                    Full-Stack Developer & Elasticsearch Expert
                                </p>
                                <div class="flex items-center gap-2 text-gray-400 text-sm mb-4">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>Dortmund, Germany</span>
                                </div>
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <span class="px-3 py-1 bg-gray-800/70 border border-gray-700 text-gray-300 rounded-lg text-xs font-medium">
                                        @Sigmie
                                    </span>
                                    <span class="px-3 py-1 bg-gray-800/70 border border-gray-700 text-gray-300 rounded-lg text-xs font-medium">
                                        Laravel
                                    </span>
                                    <span class="px-3 py-1 bg-gray-800/70 border border-gray-700 text-gray-300 rounded-lg text-xs font-medium">
                                        Php
                                    </span>
                                    <span class="px-3 py-1 bg-gray-800/70 border border-gray-700 text-gray-300 rounded-lg text-xs font-medium">
                                        Python
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Social Links -->
                        <div class="flex items-center justify-center gap-8 pt-6 border-t border-gray-800">
                            <a
                                href="https://github.com/nicoorfi"
                                target="_blank"
                                class="text-gray-400 hover:text-white transition-colors"
                                title="GitHub"
                            >
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"></path>
                                </svg>
                            </a>
                            <a
                                href="https://linkedin.com/in/nicoorfi"
                                target="_blank"
                                class="text-gray-400 hover:text-white transition-colors"
                                title="LinkedIn"
                            >
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                            <a
                                href="https://www.upwork.com/freelancers/nicoorfi"
                                target="_blank"
                                class="text-gray-400 hover:text-white transition-colors"
                                title="Upwork"
                            >
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.561 13.158c-1.102 0-2.135-.467-3.074-1.227l.228-1.076.008-.042c.207-1.143.849-3.06 2.839-3.06 1.492 0 2.703 1.212 2.703 2.703 0 1.489-1.211 2.702-2.704 2.702zm0-8.14c-2.539 0-4.51 1.649-5.31 4.366-1.22-1.834-2.148-4.036-2.687-5.892H7.828v7.112c-.002 1.406-1.141 2.546-2.547 2.548-1.405-.002-2.543-1.143-2.545-2.548V3.492H0v7.112c0 2.914 2.37 5.303 5.281 5.303 2.913 0 5.283-2.389 5.283-5.303v-1.19c.529 1.107 1.182 2.229 1.974 3.221l-1.673 7.873h2.797l1.213-5.71c1.063.679 2.285 1.109 3.686 1.109 3 0 5.439-2.452 5.439-5.45 0-3-2.439-5.439-5.439-5.439z"/>
                                </svg>
                            </a>
                            <a
                                href="https://twitter.com/nicoorfi"
                                target="_blank"
                                class="text-gray-400 hover:text-white transition-colors"
                                title="Twitter"
                            >
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
                                </svg>
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
