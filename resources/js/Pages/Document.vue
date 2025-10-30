<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, nextTick, ref } from "vue";
import DocsSidebar from "../components/DocsSidebar.vue";
import Navbar from "../Navbar.vue";
import LeftSidebar from "../components/LeftSidebar.vue";
import RightSidebar from "../components/RightSidebar.vue";
import { useTheme } from "../composables/useTheme";

const props = defineProps({
    html: String,
    title: String,
    description: String,
    navigation: Object,
    href: String,
    card: String,
});

// Initialize theme
const { initTheme, theme, toggleTheme } = useTheme();
initTheme();

// State for copy button and dropdown
const copiedMarkdown = ref(false);
const showActionsDropdown = ref(false);
const dropdownRef = ref(null);
const copiedCodeBlocks = ref(new Set());

// Handle click outside dropdown
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showActionsDropdown.value = false;
    }
};

// Clean up hash symbols from headings in the HTML
const cleanedHtml = computed(() => {
    if (!props.html) return '';
    // Remove # symbols at the start of headings
    return props.html.replace(/>(\s*)#+ /g, '>$1');
});

// Compute GitHub markdown URL
const githubMarkdownUrl = computed(() => {
    const path = window.location.pathname.replace('/docs/v2/', '');
    return `https://github.com/sigmie/sigmie/blob/master/docs/${path}.md`;
});

// Function to convert HTML to markdown (simple conversion)
const htmlToMarkdown = (html) => {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;

    const article = document.querySelector('article');
    if (!article) return '';

    let markdown = `# ${props.title}\n\n`;

    // Get clean text content with basic markdown formatting
    const content = article.innerText || article.textContent;
    markdown += content;

    return markdown;
};

// Copy page as markdown
const copyPageAsMarkdown = async () => {
    const markdown = htmlToMarkdown(props.html);

    try {
        await navigator.clipboard.writeText(markdown);
        copiedMarkdown.value = true;
        setTimeout(() => {
            copiedMarkdown.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy markdown:', err);
    }
};

// Add copy buttons to code blocks
const addCopyButtonsToCodeBlocks = () => {
    const codeBlocks = document.querySelectorAll('article pre');

    codeBlocks.forEach((pre, index) => {
        // Skip if already has a copy button
        if (pre.querySelector('.copy-code-button')) return;

        // Make pre relative for absolute positioning
        pre.style.position = 'relative';

        // Create copy button
        const button = document.createElement('button');
        button.className = 'copy-code-button';
        button.setAttribute('aria-label', 'Copy code');
        button.innerHTML = `
            <svg class="copy-icon" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            <svg class="check-icon hidden" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        `;

        button.onclick = async (e) => {
            e.preventDefault();
            const code = pre.querySelector('code');
            if (code) {
                try {
                    await navigator.clipboard.writeText(code.textContent);
                    const copyIcon = button.querySelector('.copy-icon');
                    const checkIcon = button.querySelector('.check-icon');

                    // Add success feedback
                    button.classList.add('copied');
                    copyIcon.classList.add('hidden');
                    checkIcon.classList.remove('hidden');

                    setTimeout(() => {
                        button.classList.remove('copied');
                        copyIcon.classList.remove('hidden');
                        checkIcon.classList.add('hidden');
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy code:', err);
                }
            }
        };

        pre.appendChild(button);
    });
};

// Also clean up after mount
onMounted(() => {
    // Load collapsed sections from localStorage
    const saved = localStorage.getItem('collapsedSections');
    if (saved) {
        try {
            collapsedSections.value = new Set(JSON.parse(saved));
        } catch (e) {
            console.error('Failed to parse collapsed sections:', e);
        }
    }

    nextTick(() => {
        const headings = document.querySelectorAll('article h1, article h2, article h3, article h4');
        headings.forEach(heading => {
            if (heading.textContent.startsWith('#')) {
                heading.textContent = heading.textContent.replace(/^#+\s*/, '');
            }
        });

        // Add copy buttons to code blocks
        addCopyButtonsToCodeBlocks();

        // Inject JSON-LD structured data
        const existingScript = document.querySelector('script[data-seo="article-ld"]');
        if (existingScript) existingScript.remove();

        const articleSchema = {
            '@context': 'https://schema.org',
            '@type': 'Article',
            'headline': props.title,
            'description': props.description,
            'image': props.card,
            'url': props.href,
            'publisher': {
                '@type': 'Organization',
                'name': 'Sigmie',
                'logo': {
                    '@type': 'ImageObject',
                    'url': 'https://sigmie.com/logo.svg'
                }
            },
            'datePublished': '2024-01-01',
            'dateModified': '2024-01-01'
        };

        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.setAttribute('data-seo', 'article-ld');
        script.textContent = JSON.stringify(articleSchema);
        document.head.appendChild(script);
    });

    // Add click outside listener for dropdown
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    // Remove click outside listener
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head :title="title">
        <!-- Primary Meta Tags -->
        <meta name="title" :content="title" />
        <meta name="description" :content="description" />
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />
        <meta name="language" content="en-us" />
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
        <link rel="canonical" :href="href" />

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="href" />
        <meta property="og:title" :content="title" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="card" />
        <meta property="og:site_name" content="Sigmie Documentation" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" :content="href" />
        <meta name="twitter:title" :content="title" />
        <meta name="twitter:description" :content="description" />
        <meta name="twitter:image" :content="card" />

    </Head>

    <div class="min-h-screen bg-white dark:bg-black">
        <Navbar :navigation="navigation" />

        <!-- Main Content Container - Everything within max-w-[92rem] -->
        <div class="pt-16">
            <div class="max-w-[92rem] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex gap-8">
                    <!-- Left Sidebar -->
                    <LeftSidebar
                        v-if="navigation"
                        :navigation="navigation"
                        :current-path="$page.url"
                        class="hidden lg:block"
                    />

                    <!-- Article Content -->
                    <main class="flex-1 min-w-0 py-8">
                        <article class="max-w-3xl">
                            <!-- Page Title with Actions Split Button -->
                            <div class="flex items-start justify-between gap-4 mb-8">
                                <h1 class="text-4xl font-bold text-gray-900 dark:text-gray-100 flex-1">{{ title }}</h1>

                                <!-- Split Button (Copy page + dropdown) -->
                                <div ref="dropdownRef" class="relative flex-shrink-0">
                                    <div class="flex items-center border border-gray-300 dark:border-gray-700 rounded-lg overflow-hidden">
                                        <!-- Main Action: Copy page -->
                                        <button
                                            @click="copyPageAsMarkdown"
                                            :class="[
                                                'flex items-center gap-2 px-3 py-2 text-sm transition-colors',
                                                copiedMarkdown
                                                    ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400'
                                                    : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800'
                                            ]"
                                        >
                                            <svg v-if="!copiedMarkdown" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span>{{ copiedMarkdown ? 'Copied!' : 'Copy page' }}</span>
                                        </button>

                                        <!-- Dropdown Toggle -->
                                        <button
                                            @click="showActionsDropdown = !showActionsDropdown"
                                            class="px-2 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 border-l border-gray-300 dark:border-gray-700 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Dropdown Menu -->
                                    <div
                                        v-if="showActionsDropdown"
                                        class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg shadow-lg py-2 z-10"
                                    >
                                        <a
                                            :href="githubMarkdownUrl"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                            @click="showActionsDropdown = false"
                                        >
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                            </svg>
                                            <span>View Markdown</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-html="cleanedHtml"
                                class="prose dark:prose-invert max-w-none
                                       prose-headings:scroll-mt-20 prose-headings:font-semibold
                                       prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4 prose-h2:pb-2 prose-h2:border-b prose-h2:border-gray-300 dark:prose-h2:border-gray-800
                                       prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-4
                                       prose-h4:text-lg prose-h4:mt-6 prose-h4:mb-3
                                       prose-p:text-gray-700 dark:prose-p:text-gray-300 prose-p:leading-7
                                       prose-a:text-blue-600 dark:prose-a:text-blue-400 prose-a:no-underline prose-a:font-medium hover:prose-a:underline
                                       prose-blockquote:border-l-4 prose-blockquote:border-gray-300 dark:prose-blockquote:border-gray-700 prose-blockquote:pl-4 prose-blockquote:italic
                                       prose-ul:my-6 prose-ol:my-6 prose-li:my-2
                                       prose-table:my-8 prose-thead:border-b prose-thead:border-gray-300 dark:prose-thead:border-gray-700
                                       prose-tr:border-b prose-tr:border-gray-200 dark:prose-tr:border-gray-800
                                       prose-th:text-left prose-th:py-2 prose-th:px-4 prose-th:font-semibold
                                       prose-td:py-2 prose-td:px-4"
                            ></div>
                        </article>
                    </main>

                    <!-- Right Sidebar -->
                    <RightSidebar
                        :html="cleanedHtml"
                        class="hidden xl:block"
                    />
                </div>
            </div>
        </div>

        <!-- Mobile Sidebar (keep the existing DocsSidebar for mobile) -->
        <div class="lg:hidden">
            <DocsSidebar :navigation="navigation" :current-path="$page.url" />
        </div>
    </div>
</template>

<style type="text/css">
/* Ensure sticky positioning works */
.toc-sticky-container {
    position: sticky;
    top: 5rem; /* 80px from top (navbar height + spacing) */
    align-self: flex-start;
}

/* Copy button for code blocks */
.copy-code-button {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.375rem;
    background-color: rgba(107, 114, 128, 0.1);
    border: 1px solid rgba(107, 114, 128, 0.3);
    border-radius: 0.375rem;
    color: #9ca3af;
    cursor: pointer;
    opacity: 0;
    transition: all 0.2s ease;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
}

.copy-code-button:hover {
    background-color: rgba(107, 114, 128, 0.2);
    color: #d1d5db;
    border-color: rgba(107, 114, 128, 0.4);
}

.copy-code-button.copied {
    background-color: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.3);
    color: #10b981;
}

pre:hover .copy-code-button {
    opacity: 1;
}

.copy-code-button .hidden {
    display: none;
}

/* Code block styling */
.prose :where(pre):not(:where([class~="not-prose"] *)) {
    background-color: #0a0c11 !important;
    @apply border border-gray-800/50 rounded-lg overflow-x-auto my-6 shadow-sm;
    padding: 0;
}

.prose :where(pre code):not(:where([class~="not-prose"] *)) {
    @apply block p-4 bg-transparent;
    font-size: 0.875rem;
    line-height: 1.7;
    color: #e5e7eb;
}

/* Inline code styling */
.prose :where(code):not(:where([class~="not-prose"] *)):not(pre code) {
    background-color: rgba(100, 116, 139, 0.12) !important;
    @apply text-gray-800 dark:text-gray-200 rounded px-1.5 py-0.5 text-sm font-mono;
    border: 1px solid rgba(100, 116, 139, 0.2);
    font-size: 0.875em;
    font-weight: 400;
}

/* Code block improvements */
.prose pre {
    scrollbar-width: thin;
    scrollbar-color: rgba(107, 114, 128, 0.3) transparent;
}

.prose pre::-webkit-scrollbar {
    height: 8px;
}

.prose pre::-webkit-scrollbar-track {
    background: transparent;
}

.prose pre::-webkit-scrollbar-thumb {
    background-color: rgba(107, 114, 128, 0.3);
    border-radius: 4px;
}

.prose pre::-webkit-scrollbar-thumb:hover {
    background-color: rgba(107, 114, 128, 0.5);
}

.heading-permalink {
    @apply mr-2 no-underline text-gray-400 dark:text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors;
}

/* No special first-letter styling needed */

.table-of-contents li::marker {
    content: "";
}

.table-of-contents > li > a {
    @apply no-underline font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400;
}

.table-of-contents > li > ul > li > a {
    @apply no-underline font-normal text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100;
}

.table-of-contents > li > ul > li > ul > li > a {
    @apply no-underline text-gray-500 dark:text-gray-500 font-normal hover:text-gray-700 dark:hover:text-gray-300;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4 {
    @apply text-gray-900 dark:text-gray-100 font-semibold;
}

.prose h1 {
    @apply text-3xl;
}

.prose h2 {
    @apply text-2xl mt-12 mb-6;
}

.prose h3 {
    @apply text-xl mt-8 mb-4;
}

.prose p {
    @apply text-gray-700 dark:text-gray-400 leading-7;
}

.prose a {
    @apply text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 no-underline hover:underline;
}

.prose strong {
    @apply text-gray-900 dark:text-gray-100 font-semibold;
}

.prose ul, .prose ol {
    @apply text-gray-700 dark:text-gray-400;
}

.prose li {
    @apply my-2;
}

.prose blockquote {
    @apply border-l-4 border-gray-300 dark:border-gray-700 pl-4 italic text-gray-600 dark:text-gray-400;
}

.prose table {
    @apply w-full border-collapse;
}

.prose th {
    @apply text-left font-medium text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-900 p-3 border border-gray-300 dark:border-gray-800;
}

.prose td {
    @apply p-3 border border-gray-300 dark:border-gray-800 text-gray-700 dark:text-gray-400;
}

/*
  Blur and dim the lines that don't have the `.line-focus` class,
  but are within a code block that contains any focus lines.
*/
.torchlight.has-focus-lines .line:not(.line-focus) {
    transition: filter 0.35s, opacity 0.35s;
    filter: blur(0.095rem);
    opacity: 0.5;
}

/*
  When the code block is hovered, bring all the lines into focus.
*/
.torchlight.has-focus-lines:hover .line:not(.line-focus) {
    filter: blur(0px);
    opacity: 1;
}

.torchlight summary:focus {
    outline: none;
}

/* Hide the default markers, as we provide our own */
.torchlight details > summary::marker,
.torchlight details > summary::-webkit-details-marker {
    display: none;
}

.torchlight details .summary-caret::after {
    pointer-events: none;
}

/* Add spaces to keep everything aligned */
.torchlight .summary-caret-empty::after,
.torchlight details .summary-caret-middle::after,
.torchlight details .summary-caret-end::after {
    content: " ";
}

/* Show a minus sign when the block is open. */
.torchlight details[open] .summary-caret-start::after {
    content: "-";
}

/* And a plus sign when the block is closed. */
.torchlight details:not([open]) .summary-caret-start::after {
    content: "+";
}

/* Hide the [...] indicator when open. */
.torchlight details[open] .summary-hide-when-open {
    display: none;
}

/* Show the [...] indicator when closed. */
.torchlight details:not([open]) .summary-hide-when-open {
    display: initial;
}

.callout {
    @apply bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-800 p-4 rounded-geist my-6;
}

.callout.danger {
    @apply bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-800 text-red-700 dark:text-red-400;
}

.callout.danger::before {
    @apply font-medium;
}

.callout.warning {
    @apply bg-yellow-50 dark:bg-yellow-900/20 border-yellow-300 dark:border-yellow-800 text-yellow-700 dark:text-yellow-400;
}

.callout.warning::before {
    @apply font-medium;
}

.callout.info::before {
    @apply font-medium;
}

.callout.info {
    @apply bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-800 text-blue-700 dark:text-blue-400;
}
</style>
