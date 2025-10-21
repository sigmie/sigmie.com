<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import axios from "axios";

const jobTitle = ref("");
const jobDescription = ref("");
const searchResults = ref([]);
const isSearching = ref(false);
const hasSearched = ref(false);
const expandedResumes = ref(new Set());

const performSearch = async () => {
    if (!jobTitle.value.trim() || !jobDescription.value.trim()) {
        return;
    }

    isSearching.value = true;
    hasSearched.value = true;
    expandedResumes.value = new Set();

    try {
        const response = await axios.post("/api/search/resumes", {
            title: jobTitle.value,
            description: jobDescription.value,
        });

        searchResults.value = response.data.results || [];
    } catch (error) {
        console.error("Search error:", error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const toggleResume = (resumeId) => {
    const newSet = new Set(expandedResumes.value);
    if (newSet.has(resumeId)) {
        newSet.delete(resumeId);
    } else {
        newSet.add(resumeId);
    }
    expandedResumes.value = newSet;
};

const isExpanded = (resumeId) => {
    return expandedResumes.value.has(resumeId);
};
</script>

<template>
    <Head>
        <title>Resume Search - Sigmie</title>
        <meta name="description" content="Search for the perfect candidate using AI-powered semantic search. Match job requirements with resumes intelligently." />
    </Head>

    <div class="min-h-screen bg-white dark:bg-black">
        <!-- Header with Back Button -->
        <div class="border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-black">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 py-6 lg:px-8">
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Home
                </Link>
            </div>
        </div>

        <!-- Main Content -->
        <div class="relative border-t border-gray-200 dark:border-gray-800 bg-gradient-to-b from-white to-gray-50 dark:from-black dark:to-gray-900/50 overflow-hidden">
            <!-- Decorative background elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute top-1/4 -right-64 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 -left-64 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl px-4 sm:px-6 py-16 sm:py-20 lg:py-28 lg:px-8">
                <div class="text-center mb-10 sm:mb-14">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 border border-indigo-200 dark:border-indigo-800 rounded-full mb-6">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-sm font-medium bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            AI-Powered Recruiting
                        </span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold text-gray-900 dark:text-gray-100 mb-4 sm:mb-6">
                        Find the
                        <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Perfect Candidate
                        </span>
                    </h1>
                    <p class="text-base sm:text-lg lg:text-xl text-gray-600 dark:text-gray-400 max-w-3xl mx-auto leading-relaxed">
                        Describe your ideal role and let AI find the best matching resumes. Weighted semantic search ensures the most relevant candidates rise to the top.
                    </p>
                </div>

                <div class="max-w-5xl mx-auto">
                    <!-- Search Form -->
                    <form @submit.prevent="performSearch()" class="mb-8 sm:mb-10">
                        <div class="bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-xl">
                            <div class="space-y-5">
                                <!-- Job Title -->
                                <div>
                                    <label for="job-title" class="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                        Job Title
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <input
                                            id="job-title"
                                            v-model="jobTitle"
                                            type="text"
                                            placeholder="e.g., Senior Frontend Developer"
                                            class="w-full pl-12 pr-4 py-3 text-sm sm:text-base bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 dark:text-gray-100 placeholder-gray-400"
                                            :disabled="isSearching"
                                        />
                                    </div>
                                </div>

                                <!-- Job Description -->
                                <div>
                                    <label for="job-description" class="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                        Job Description
                                    </label>
                                    <textarea
                                        id="job-description"
                                        v-model="jobDescription"
                                        rows="6"
                                        placeholder="Describe the role, required skills, experience level, and any specific technologies or qualifications..."
                                        class="w-full px-4 py-3 text-sm sm:text-base bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 dark:text-gray-100 placeholder-gray-400 resize-none"
                                        :disabled="isSearching"
                                    ></textarea>
                                </div>

                                <!-- Search Button -->
                                <button
                                    type="submit"
                                    class="w-full px-8 py-4 font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
                                    :disabled="isSearching || !jobTitle.trim() || !jobDescription.trim()"
                                >
                                    <span class="flex items-center justify-center gap-2">
                                        <span>{{ isSearching ? "Searching..." : "Find Candidates" }}</span>
                                        <svg v-if="!isSearching" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <div v-else class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Results -->
                    <div v-if="hasSearched" class="space-y-6">
                        <div v-if="isSearching" class="text-center py-16 sm:py-20">
                            <div class="relative inline-flex">
                                <div class="w-12 h-12 border-4 border-gray-200 dark:border-gray-700 border-t-indigo-600 rounded-full animate-spin"></div>
                                <div class="absolute inset-0 w-12 h-12 border-4 border-transparent border-t-purple-600 rounded-full animate-spin" style="animation-duration: 1.5s; animation-direction: reverse;"></div>
                            </div>
                            <p class="mt-6 text-base font-medium text-gray-600 dark:text-gray-400">Analyzing resumes...</p>
                        </div>

                        <div v-else-if="searchResults.length === 0" class="text-center py-16 sm:py-20">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No candidates found</h3>
                            <p class="text-base text-gray-600 dark:text-gray-400">Try adjusting your search criteria</p>
                        </div>

                        <div v-else>
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-100">
                                        Found {{ searchResults.length }} matching candidates
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Ranked by semantic similarity
                                    </p>
                                </div>
                                <button
                                    @click="hasSearched = false; jobTitle = ''; jobDescription = ''; searchResults = []"
                                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    New search
                                </button>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(result, index) in searchResults"
                                    :key="result._id"
                                    class="group bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-6 hover:border-indigo-500 dark:hover:border-indigo-400 hover:shadow-xl transition-all duration-300"
                                >
                                    <div class="flex items-start justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full text-white font-bold text-sm">
                                                {{ index + 1 }}
                                            </div>
                                            <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                                                {{ result.category }}
                                            </span>
                                        </div>
                                        <button
                                            @click="toggleResume(result._id)"
                                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors"
                                        >
                                            <span>{{ isExpanded(result._id) ? 'Show Less' : 'Show More' }}</span>
                                            <svg
                                                class="w-4 h-4 transition-transform duration-200"
                                                :class="{ 'rotate-180': isExpanded(result._id) }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <div
                                        v-html="result.resume_html"
                                        :class="isExpanded(result._id) ? 'max-h-none' : 'max-h-48 overflow-hidden relative'"
                                        class="prose prose-sm dark:prose-invert max-w-none prose-headings:text-gray-900 dark:prose-headings:text-gray-100 prose-p:text-gray-600 dark:prose-p:text-gray-400 prose-strong:text-gray-900 dark:prose-strong:text-gray-100 prose-li:text-gray-600 dark:prose-li:text-gray-400 transition-all duration-300"
                                    ></div>

                                    <!-- Gradient overlay for collapsed state -->
                                    <div
                                        v-if="!isExpanded(result._id)"
                                        class="h-24 bg-gradient-to-t from-white dark:from-gray-900 to-transparent -mt-24 relative pointer-events-none"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Ensure HTML content is styled properly */
</style>
