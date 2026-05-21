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
    if (!jobTitle.value.trim() || !jobDescription.value.trim()) return;

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
    newSet.has(resumeId) ? newSet.delete(resumeId) : newSet.add(resumeId);
    expandedResumes.value = newSet;
};

const isExpanded = (resumeId) => expandedResumes.value.has(resumeId);
</script>

<template>
    <Head>
        <title>Resume Search - Sigmie</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
        <meta name="description" content="Search for the perfect candidate using AI-powered semantic search. Match job requirements with resumes intelligently with Sigmie." />
        <meta name="keywords" content="resume search, candidate search, semantic search, AI recruiting, job matching, resume matching, talent search" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <meta name="language" content="en-us" />
        <link rel="canonical" href="https://sigmie.com/resumes" />

        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://sigmie.com/resumes" />
        <meta property="og:title" content="Resume Search - Sigmie" />
        <meta property="og:description" content="Search for the perfect candidate using AI-powered semantic search." />
        <meta property="og:site_name" content="Sigmie" />

        <meta name="twitter:card" content="summary" />
        <meta name="twitter:url" content="https://sigmie.com/resumes" />
        <meta name="twitter:title" content="Resume Search - Sigmie" />
        <meta name="twitter:description" content="Search for the perfect candidate using AI-powered semantic search." />
    </Head>

    <div class="min-h-screen bg-canvas-white dark:bg-black font-sans text-graphite dark:text-white">
        <div class="border-b border-light-steel dark:border-gray-800">
            <div class="mx-auto max-w-7xl px-6 lg:px-8 py-6">
                <Link
                    href="/"
                    class="inline-flex items-center gap-2 text-[14px] font-medium text-charcoal hover:text-graphite dark:text-gray-400 dark:hover:text-white transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to home
                </Link>
            </div>
        </div>

        <div class="bg-canvas-white dark:bg-black">
            <div class="mx-auto max-w-5xl px-6 lg:px-8 py-20 sm:py-24">
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-ghostly-gray dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-full mb-8">
                        <svg class="w-4 h-4 text-magic-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="text-[13px] font-medium text-graphite dark:text-white">AI-Powered recruiting</span>
                    </div>
                    <h1 class="text-[40px] sm:text-[64px] leading-[0.9] font-semibold text-graphite dark:text-white tracking-tight text-balance mb-6">
                        Find the perfect <span class="text-magic-orange">candidate</span>
                    </h1>
                    <p class="text-[17px] sm:text-[20px] leading-[1.5] text-charcoal dark:text-gray-400 max-w-2xl mx-auto">
                        Describe the role and let semantic search bring the most relevant resumes to the top.
                    </p>
                </div>

                <form @submit.prevent="performSearch()" class="mb-12">
                    <div class="bg-canvas-white dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-xl p-8 shadow-xl">
                        <div class="space-y-6">
                            <div>
                                <label for="job-title" class="block text-[13px] font-semibold uppercase tracking-wider text-subtle-gray mb-2">
                                    Job title
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-subtle-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <input
                                        id="job-title"
                                        v-model="jobTitle"
                                        type="text"
                                        placeholder="e.g., Senior Frontend Developer"
                                        class="w-full pl-11 pr-4 py-3 text-[15px] bg-ghostly-gray dark:bg-gray-800 border border-light-steel dark:border-gray-700 rounded-full focus:outline-none focus:border-carbon-black dark:focus:border-white text-graphite dark:text-white placeholder-subtle-gray transition-colors"
                                        :disabled="isSearching"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="job-description" class="block text-[13px] font-semibold uppercase tracking-wider text-subtle-gray mb-2">
                                    Job description
                                </label>
                                <textarea
                                    id="job-description"
                                    v-model="jobDescription"
                                    rows="6"
                                    placeholder="Describe the role, required skills, experience level, and any specific technologies or qualifications…"
                                    class="w-full px-5 py-3 text-[15px] bg-ghostly-gray dark:bg-gray-800 border border-light-steel dark:border-gray-700 rounded-xl focus:outline-none focus:border-carbon-black dark:focus:border-white text-graphite dark:text-white placeholder-subtle-gray resize-none transition-colors"
                                    :disabled="isSearching"
                                ></textarea>
                            </div>

                            <button
                                type="submit"
                                class="w-full px-8 py-3.5 text-[15px] font-medium text-canvas-white bg-carbon-black hover:bg-graphite rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="isSearching || !jobTitle.trim() || !jobDescription.trim()"
                            >
                                <span class="flex items-center justify-center gap-2">
                                    <span>{{ isSearching ? "Searching…" : "Find candidates" }}</span>
                                    <svg v-if="!isSearching" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <span v-else class="w-4 h-4 border-2 border-canvas-white border-t-transparent rounded-full animate-spin"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>

                <div v-if="hasSearched" class="space-y-6">
                    <div v-if="isSearching" class="text-center py-20">
                        <div class="w-10 h-10 border-2 border-light-steel border-t-magic-orange rounded-full animate-spin mx-auto"></div>
                        <p class="mt-6 text-[14px] text-charcoal dark:text-gray-400">Analyzing resumes…</p>
                    </div>

                    <div v-else-if="searchResults.length === 0" class="text-center py-20">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-ghostly-gray dark:bg-gray-800 rounded-full mb-4">
                            <svg class="w-8 h-8 text-subtle-gray" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-[17px] font-semibold text-graphite dark:text-white mb-2">No candidates found</h3>
                        <p class="text-[14px] text-charcoal dark:text-gray-400">Try adjusting your search criteria</p>
                    </div>

                    <div v-else>
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-[24px] leading-[1.33] font-semibold text-graphite dark:text-white tracking-tight">
                                    {{ searchResults.length }} matching candidates
                                </h3>
                                <p class="text-[13px] text-subtle-gray mt-1">
                                    Ranked by semantic similarity
                                </p>
                            </div>
                            <button
                                @click="hasSearched = false; jobTitle = ''; jobDescription = ''; searchResults = []"
                                class="flex items-center gap-2 px-4 py-2 text-[13px] font-medium text-charcoal hover:text-graphite dark:text-gray-400 dark:hover:text-white transition-colors rounded-full hover:bg-ghostly-gray dark:hover:bg-gray-900"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                New search
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="(result, index) in searchResults"
                                :key="result._id"
                                class="group bg-canvas-white dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-xl p-8 transition-shadow hover:shadow-xl"
                            >
                                <div class="flex items-start justify-between gap-4 mb-6">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-8 h-8 bg-carbon-black text-canvas-white rounded-full font-semibold text-[13px]">
                                            {{ index + 1 }}
                                        </div>
                                        <span class="inline-flex items-center px-3 py-1 text-[12px] font-medium rounded-full bg-ghostly-gray dark:bg-gray-800 text-graphite dark:text-gray-300 border border-light-steel dark:border-gray-700">
                                            {{ result.category }}
                                        </span>
                                    </div>
                                    <button
                                        @click="toggleResume(result._id)"
                                        class="inline-flex items-center gap-2 px-4 py-1.5 text-[13px] font-medium text-magic-orange hover:text-graphite transition-colors rounded-full"
                                    >
                                        <span>{{ isExpanded(result._id) ? 'Show less' : 'Show more' }}</span>
                                        <svg
                                            class="w-4 h-4 transition-transform duration-200"
                                            :class="{ 'rotate-180': isExpanded(result._id) }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>

                                <div
                                    v-html="result.resume_html"
                                    :class="isExpanded(result._id) ? 'max-h-none' : 'max-h-48 overflow-hidden relative'"
                                    class="prose prose-sm dark:prose-invert max-w-none prose-headings:text-graphite dark:prose-headings:text-white prose-p:text-charcoal dark:prose-p:text-gray-400 prose-strong:text-graphite dark:prose-strong:text-white prose-li:text-charcoal dark:prose-li:text-gray-400 transition-all duration-300"
                                ></div>

                                <div
                                    v-if="!isExpanded(result._id)"
                                    class="h-24 bg-gradient-to-t from-canvas-white dark:from-gray-900 to-transparent -mt-24 relative pointer-events-none"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
