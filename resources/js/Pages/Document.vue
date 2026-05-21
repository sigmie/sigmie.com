<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, nextTick, ref } from "vue";
import DocsSidebar from "../components/DocsSidebar.vue";
import Navbar from "../Navbar.vue";
import LeftSidebar from "../components/LeftSidebar.vue";
import RightSidebar from "../components/RightSidebar.vue";
import TableOfContents from "../TableOfContents.vue";
import { useTheme } from "../composables/useTheme";

const props = defineProps({
    html: String,
    title: String,
    description: String,
    navigation: Object,
    href: String,
    card: String,
});

const flatLinks = computed(() => {
    if (!props.navigation || !Array.isArray(props.navigation)) return [];
    return props.navigation.flatMap((section) => section.links || []);
});

const currentIndex = computed(() => {
    if (typeof window === 'undefined') return -1;
    return flatLinks.value.findIndex((l) => l.href === window.location.pathname);
});

const prevLink = computed(() => {
    const i = currentIndex.value;
    return i > 0 ? flatLinks.value[i - 1] : null;
});

const nextLink = computed(() => {
    const i = currentIndex.value;
    return i >= 0 && i < flatLinks.value.length - 1 ? flatLinks.value[i + 1] : null;
});

const showMobileToc = ref(false);

const { initTheme } = useTheme();
initTheme();

const copiedMarkdown = ref(false);
const showActionsDropdown = ref(false);
const dropdownRef = ref(null);

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showActionsDropdown.value = false;
    }
};

const cleanedHtml = computed(() => {
    if (!props.html) return '';
    // Strip leading # characters that leak through from the markdown heading anchors
    let html = props.html.replace(/>(\s*)#+ /g, '>$1');
    // Strip the leading <h1>...</h1> — page already shows the title above the article
    html = html.replace(/^\s*<h1\b[^>]*>[\s\S]*?<\/h1>\s*/i, '');
    return html;
});

const githubMarkdownUrl = computed(() => {
    if (typeof window === 'undefined') return '';
    const path = window.location.pathname.replace('/docs/v2/', '');
    return `https://github.com/sigmie/sigmie/blob/master/docs/${path}.md`;
});

const htmlToMarkdown = () => {
    const article = document.querySelector('article');
    if (!article) return '';

    let markdown = `# ${props.title}\n\n`;
    markdown += article.innerText || article.textContent;
    return markdown;
};

const copyPageAsMarkdown = async () => {
    try {
        await navigator.clipboard.writeText(htmlToMarkdown());
        copiedMarkdown.value = true;
        setTimeout(() => { copiedMarkdown.value = false; }, 2000);
    } catch (err) {
        console.error('Failed to copy markdown:', err);
    }
};

const addCopyButtonsToCodeBlocks = () => {
    const codeBlocks = document.querySelectorAll('article pre');

    codeBlocks.forEach((pre) => {
        if (pre.querySelector('.copy-code-button')) return;

        pre.style.position = 'relative';

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

onMounted(() => {
    nextTick(() => {
        const headings = document.querySelectorAll('article h1, article h2, article h3, article h4');
        headings.forEach(heading => {
            if (heading.textContent.startsWith('#')) {
                heading.textContent = heading.textContent.replace(/^#+\s*/, '');
            }
        });

        addCopyButtonsToCodeBlocks();
    });

    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-canvas-white dark:bg-black font-sans text-graphite dark:text-white">
        <Navbar :navigation="navigation" />

        <div class="pt-16">
            <div class="max-w-[88rem] mx-auto px-6 lg:px-8">
                <div class="flex gap-10 xl:gap-14">
                    <LeftSidebar
                        v-if="navigation"
                        :navigation="navigation"
                        :current-path="$page.url"
                    />

                    <main class="flex-1 min-w-0 py-10">
                        <article class="max-w-2xl">
                            <div class="flex items-start justify-between gap-4 mb-6">
                                <h1 class="text-[36px] leading-[1.15] font-semibold tracking-tight text-graphite dark:text-white flex-1">
                                    {{ title }}
                                </h1>

                                <div ref="dropdownRef" class="relative flex-shrink-0">
                                    <div class="flex items-center border border-light-steel dark:border-gray-700 rounded-full overflow-hidden bg-canvas-white dark:bg-gray-900">
                                        <button
                                            @click="copyPageAsMarkdown"
                                            :class="[
                                                'flex items-center gap-2 px-4 py-2 text-[13px] font-medium transition-colors',
                                                copiedMarkdown
                                                    ? 'bg-magic-green/15 text-magic-green'
                                                    : 'text-charcoal hover:text-graphite hover:bg-ghostly-gray dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800'
                                            ]"
                                        >
                                            <svg v-if="!copiedMarkdown" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>{{ copiedMarkdown ? 'Copied' : 'Copy page' }}</span>
                                        </button>

                                        <button
                                            @click="showActionsDropdown = !showActionsDropdown"
                                            aria-label="More page actions"
                                            class="px-2.5 py-2 text-charcoal hover:text-graphite hover:bg-ghostly-gray dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-800 border-l border-light-steel dark:border-gray-700 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                    </div>

                                    <div
                                        v-if="showActionsDropdown"
                                        class="absolute right-0 mt-2 w-56 bg-canvas-white dark:bg-gray-900 border border-light-steel dark:border-gray-800 rounded-xl shadow-xl py-2 z-10"
                                    >
                                        <a
                                            :href="githubMarkdownUrl"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center gap-3 px-4 py-2 text-[13px] text-charcoal dark:text-gray-300 hover:bg-ghostly-gray dark:hover:bg-gray-800 transition-colors"
                                            @click="showActionsDropdown = false"
                                        >
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                                            </svg>
                                            <span>View markdown</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <p v-if="description" class="text-[16px] leading-[1.55] text-charcoal dark:text-gray-400 mb-10 -mt-2">
                                {{ description }}
                            </p>

                            <details class="xl:hidden mb-8 rounded-lg border border-light-steel dark:border-gray-800 bg-ghostly-gray dark:bg-gray-900 [&[open]>summary>svg]:rotate-180" @toggle="(e) => showMobileToc = e.target.open">
                                <summary class="flex items-center justify-between gap-2 px-4 py-3 cursor-pointer text-[13px] font-semibold text-graphite dark:text-white list-none">
                                    On this page
                                    <svg class="w-4 h-4 text-subtle-gray transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </summary>
                                <div class="px-4 pb-3 border-t border-light-steel dark:border-gray-800 pt-3">
                                    <TableOfContents :html="cleanedHtml" />
                                </div>
                            </details>

                            <div v-html="cleanedHtml" class="doc-content"></div>

                            <footer class="mt-16 pt-8 border-t border-light-steel dark:border-gray-800">
                                <a
                                    :href="githubMarkdownUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 text-[13px] font-medium text-subtle-gray hover:text-graphite dark:hover:text-white transition-colors mb-10"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit this page on GitHub
                                </a>

                                <nav v-if="prevLink || nextLink" class="grid grid-cols-2 gap-4">
                                    <Link
                                        v-if="prevLink"
                                        :href="prevLink.href"
                                        class="group flex flex-col items-start gap-1 p-4 rounded-xl border border-light-steel dark:border-gray-800 hover:border-graphite dark:hover:border-white transition-colors"
                                    >
                                        <span class="text-[12px] text-subtle-gray">Previous</span>
                                        <span class="inline-flex items-center gap-2 text-[14px] font-semibold text-graphite dark:text-white group-hover:text-magic-orange transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                            </svg>
                                            {{ prevLink.title }}
                                        </span>
                                    </Link>
                                    <div v-else></div>
                                    <Link
                                        v-if="nextLink"
                                        :href="nextLink.href"
                                        class="group flex flex-col items-end gap-1 p-4 rounded-xl border border-light-steel dark:border-gray-800 hover:border-graphite dark:hover:border-white transition-colors text-right"
                                    >
                                        <span class="text-[12px] text-subtle-gray">Next</span>
                                        <span class="inline-flex items-center gap-2 text-[14px] font-semibold text-graphite dark:text-white group-hover:text-magic-orange transition-colors">
                                            {{ nextLink.title }}
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </span>
                                    </Link>
                                </nav>
                            </footer>
                        </article>
                    </main>

                    <RightSidebar :html="cleanedHtml" />
                </div>
            </div>
        </div>

        <div class="lg:hidden">
            <DocsSidebar :navigation="navigation" :current-path="$page.url" />
        </div>
    </div>
</template>

<style type="text/css">
.toc-sticky-container {
    position: sticky;
    top: 5rem;
    align-self: flex-start;
}

/* Doc content typography */
.doc-content {
    font-size: 16px;
    line-height: 1.7;
    color: var(--color-charcoal);
}
.dark .doc-content { color: rgb(209 213 219); }

.doc-content h1,
.doc-content h2,
.doc-content h3,
.doc-content h4 {
    color: var(--color-graphite);
    font-weight: 600;
    letter-spacing: -0.01em;
    scroll-margin-top: 5rem;
}
.dark .doc-content h1,
.dark .doc-content h2,
.dark .doc-content h3,
.dark .doc-content h4 { color: #fff; }

.doc-content h2 {
    font-size: 24px;
    line-height: 1.33;
    margin-top: 3rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--color-light-steel);
}
.dark .doc-content h2 { border-bottom-color: #262626; }

.doc-content h3 {
    font-size: 20px;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
}

.doc-content h4 {
    font-size: 17px;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
}

.doc-content p {
    margin: 1.25rem 0;
}

.doc-content a {
    color: var(--color-magic-orange);
    font-weight: 500;
    text-decoration: underline;
    text-decoration-thickness: 1px;
    text-underline-offset: 3px;
}
.doc-content a:hover { color: var(--color-graphite); }
.dark .doc-content a:hover { color: #fff; }

.doc-content strong {
    color: var(--color-graphite);
    font-weight: 600;
}
.dark .doc-content strong { color: #fff; }

.doc-content ul,
.doc-content ol {
    margin: 1.5rem 0;
    padding-left: 1.5rem;
}
.doc-content li { margin: 0.5rem 0; }
.doc-content ul li { list-style-type: disc; }
.doc-content ol li { list-style-type: decimal; }

.doc-content blockquote {
    border-left: 2px solid var(--color-light-steel);
    padding-left: 1rem;
    color: var(--color-charcoal);
    font-style: italic;
    margin: 1.5rem 0;
}

.doc-content table {
    width: 100%;
    border-collapse: collapse;
    margin: 2rem 0;
}

.doc-content th {
    text-align: left;
    font-weight: 600;
    color: var(--color-graphite);
    background-color: var(--color-ghostly-gray);
    padding: 0.75rem 1rem;
    border: 1px solid var(--color-light-steel);
}
.dark .doc-content th { color: #fff; background-color: #111827; border-color: #262626; }

.doc-content td {
    padding: 0.75rem 1rem;
    border: 1px solid var(--color-light-steel);
}
.dark .doc-content td { border-color: #262626; }

.doc-content hr {
    border: 0;
    border-top: 1px solid var(--color-light-steel);
    margin: 3rem 0;
}
.dark .doc-content hr { border-top-color: #262626; }

/* Code blocks */
.doc-content pre {
    background-color: #0a0c11;
    border: 1px solid #1f2937;
    border-radius: 12px;
    overflow-x: auto;
    margin: 1.5rem 0;
    padding: 0;
    scrollbar-width: thin;
    scrollbar-color: rgba(107, 114, 128, 0.3) transparent;
}
.doc-content pre::-webkit-scrollbar { height: 8px; }
.doc-content pre::-webkit-scrollbar-track { background: transparent; }
.doc-content pre::-webkit-scrollbar-thumb { background-color: rgba(107, 114, 128, 0.3); border-radius: 4px; }
.doc-content pre::-webkit-scrollbar-thumb:hover { background-color: rgba(107, 114, 128, 0.5); }

.doc-content pre code {
    display: block;
    padding: 1rem;
    background-color: transparent;
    font-size: 0.875rem;
    line-height: 1.7;
    color: #e5e7eb;
}

/* Inline code */
.doc-content code:not(pre code) {
    background-color: var(--color-ghostly-gray);
    color: var(--color-graphite);
    border: 1px solid var(--color-light-steel);
    border-radius: 6px;
    padding: 0.125rem 0.375rem;
    font-size: 0.875em;
    font-family: var(--font-mono);
}
.dark .doc-content code:not(pre code) {
    background-color: rgba(100, 116, 139, 0.15);
    color: #e5e7eb;
    border-color: rgba(100, 116, 139, 0.25);
}

/* Copy code button */
.copy-code-button {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.375rem;
    background-color: rgba(107, 114, 128, 0.15);
    border: 1px solid rgba(107, 114, 128, 0.3);
    border-radius: 9999px;
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
    background-color: rgba(107, 114, 128, 0.25);
    color: #d1d5db;
}
.copy-code-button.copied {
    background-color: rgba(68, 198, 127, 0.2);
    border-color: rgba(68, 198, 127, 0.4);
    color: var(--color-magic-green);
}
pre:hover .copy-code-button { opacity: 1; }
.copy-code-button .hidden { display: none; }

.heading-permalink {
    margin-right: 0.5rem;
    text-decoration: none;
    color: var(--color-subtle-gray);
    transition: color 0.15s;
}
.heading-permalink:hover { color: var(--color-graphite); }
.dark .heading-permalink:hover { color: #fff; }

/* Table of contents */
.table-of-contents li::marker { content: ""; }
.table-of-contents > li > a {
    text-decoration: none;
    font-weight: 500;
    color: var(--color-graphite);
}
.table-of-contents > li > a:hover { color: var(--color-magic-orange); }
.table-of-contents > li > ul > li > a {
    text-decoration: none;
    color: var(--color-charcoal);
}
.table-of-contents > li > ul > li > a:hover { color: var(--color-graphite); }

/* Torchlight code focus */
.torchlight.has-focus-lines .line:not(.line-focus) {
    transition: filter 0.35s, opacity 0.35s;
    filter: blur(0.095rem);
    opacity: 0.5;
}
.torchlight.has-focus-lines:hover .line:not(.line-focus) {
    filter: blur(0px);
    opacity: 1;
}
.torchlight summary:focus { outline: none; }
.torchlight details > summary::marker,
.torchlight details > summary::-webkit-details-marker { display: none; }
.torchlight details .summary-caret::after { pointer-events: none; }
.torchlight .summary-caret-empty::after,
.torchlight details .summary-caret-middle::after,
.torchlight details .summary-caret-end::after { content: " "; }
.torchlight details[open] .summary-caret-start::after { content: "-"; }
.torchlight details:not([open]) .summary-caret-start::after { content: "+"; }
.torchlight details[open] .summary-hide-when-open { display: none; }
.torchlight details:not([open]) .summary-hide-when-open { display: initial; }

/* Callouts */
.callout {
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin: 1.5rem 0;
    border: 1px solid var(--color-light-steel);
    background-color: var(--color-ghostly-gray);
}
.dark .callout { background-color: rgba(31, 41, 55, 0.5); border-color: #262626; }
.callout.danger {
    background-color: rgba(255, 83, 16, 0.08);
    border-color: rgba(255, 83, 16, 0.3);
    color: var(--color-magic-orange);
}
.callout.warning {
    background-color: var(--color-graphite);
    border-color: var(--color-graphite);
    color: var(--color-canvas-white);
}
.callout.info {
    background-color: rgba(0, 152, 241, 0.08);
    border-color: rgba(0, 152, 241, 0.3);
    color: var(--color-product-blue);
}
.callout.danger::before,
.callout.warning::before,
.callout.info::before { font-weight: 600; }
</style>
