<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed, onMounted, nextTick, ref } from "vue";
import AppLayout from "../Layouts/AppLayout.vue";
import LeftSidebar from "../components/LeftSidebar.vue";
import RightSidebar from "../components/RightSidebar.vue";
import TableOfContents from "../TableOfContents.vue";
import GridCrosshairs from "../components/GridCrosshairs.vue";
import { useTheme } from "../composables/useTheme";

const props = defineProps({
    html: String,
    title: String,
    pageHeading: String,
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

const currentSection = computed(() => {
    const i = currentIndex.value;
    if (i < 0) return null;
    return props.navigation.find((section) => section.links?.some((l) => l.href === flatLinks.value[i].href))?.title ?? null;
});

const { initTheme } = useTheme();
initTheme();

const copiedMarkdown = ref(false);

const cleanedHtml = computed(() => {
    if (!props.html) return '';
    // Strip leading # characters that leak through from the markdown heading anchors
    let html = props.html.replace(/>(\s*)#+ /g, '>$1');
    // Strip the leading <h1>...</h1> — the page wrapper already renders the title
    html = html.replace(/^\s*<h1\b[^>]*>[\s\S]*?<\/h1>\s*/i, '');
    // Downgrade any further <h1> in the body to <h2> so the page has a single H1
    html = html.replace(/<(\/?)h1(\b[^>]*)>/gi, '<$1h2$2>');
    // Wide tables scroll inside their own box instead of widening the page on
    // phones; tabindex lets keyboard users scroll it.
    html = html.replace(/<table\b/gi, '<div class="table-scroll" tabindex="0"><table').replace(/<\/table>/gi, '</table></div>');
    return html;
});

const page = usePage();
const markdownUrl = computed(() => `${page.url.split(/[?#]/)[0]}.md`);

const githubMarkdownUrl = computed(() => {
    if (typeof window === 'undefined') return '';
    const path = window.location.pathname.replace('/docs/v2/', '');
    return `https://github.com/sigmie/sigmie/blob/master/docs/${path}.md`;
});

// Copies the page's own Markdown, served at the same path plus `.md`.
const copyPageAsMarkdown = async () => {
    try {
        const response = await fetch(`${window.location.pathname}.md`);
        await navigator.clipboard.writeText(await response.text());
        copiedMarkdown.value = true;
        setTimeout(() => { copiedMarkdown.value = false; }, 2000);
    } catch (err) {
        console.error('Failed to copy markdown:', err);
    }
};

// Sigmie filter DSL highlighting for plain (no-language) code blocks and
// inline <code> in the docs prose. PHP/JSON/shell blocks are left untouched.
const FILTER_SHAPE = /\w:\S|[<>]=?\s*\S|\bAND\b|\bOR\b|\bNOT\b|\bIN\s*\(|\.\.|:\*|^#/i;
const PHP_MARKERS = /\$\w|->|=>|::|\bnew\s|\buse\s|\bclass\s|\bfunction\s|\bnamespace\s|;\s*$|^\s*\/\//m;

const escapeHtml = (s) => s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

// Ordered regex table — first match wins. Patterns are anchored at start.
// Number comes before identifier so 100..500 doesn't get swallowed as one
// word; range `..` comes before single `.`; `>=`/`<=` come before `>`/`<`.
const FILTER_PATTERNS = [
    [/^#[^\n]*/, 'comment'],
    [/^\s+/, 'space'],
    [/^"[^"\n]*"/, 'value'],
    [/^'[^'\n]*'/, 'value'],
    [/^\.\./, 'bracket'],
    [/^(?:>=|<=|!=)/, 'op'],
    [/^[<>:=!]/, 'op'],
    [/^[\[\]()*,]/, 'bracket'],
    [/^-?\d+(?:\.\d+)?/, 'number'],
    [/^(?:AND|OR|NOT|IN)\b/i, 'logic'],
    [/^(?:true|false|null)\b/, 'number'],
    [/^[a-zA-Z_][a-zA-Z_0-9.]*/, 'field'],
    [/^./, 'text'],
];

const tokenizeFilterLine = (text) => {
    const out = [];
    let rest = text;
    while (rest.length > 0) {
        let matched = false;
        for (const [pattern, type] of FILTER_PATTERNS) {
            const m = pattern.exec(rest);
            if (!m) continue;
            const value = m[0];
            let resolvedType = type;
            // AND/OR/NOT can legitimately appear inside field names mid-token,
            // but with leading word-boundary they should be the literal logic
            // keyword.
            out.push({ type: resolvedType, value });
            rest = rest.slice(value.length);
            matched = true;
            break;
        }
        if (!matched) {
            out.push({ type: 'text', value: rest[0] });
            rest = rest.slice(1);
        }
    }
    return out;
};

const renderFilterTokens = (tokens) => tokens.map((t) => {
    if (t.type === 'space') return t.value;
    const cls = `sig-filter-${t.type}`;
    return `<span class="${cls}">${escapeHtml(t.value)}</span>`;
}).join('');

const looksLikeFilter = (text) => {
    const trimmed = text.trim();
    if (!trimmed) return false;
    if (PHP_MARKERS.test(trimmed)) return false;
    return FILTER_SHAPE.test(trimmed);
};

const highlightLineElement = (el) => {
    if (el.dataset.filterHighlighted === '1') return;
    const text = el.textContent;
    if (!looksLikeFilter(text)) return;
    el.innerHTML = renderFilterTokens(tokenizeFilterLine(text));
    el.dataset.filterHighlighted = '1';
};

const blockLooksLikeFilter = (code) => {
    const sample = Array.from(code.querySelectorAll('.line'), (l) => l.textContent.trim()).filter(Boolean);
    if (sample.length === 0) return false;
    // Require at least one non-empty line to look like filter syntax and no PHP markers anywhere.
    const joined = sample.join('\n');
    if (PHP_MARKERS.test(joined)) return false;
    return sample.some((line) => FILTER_SHAPE.test(line));
};

const highlightFilterBlocks = () => {
    document.querySelectorAll('article pre > code').forEach((code) => {
        if (code.dataset.filterHighlighted === '1') return;
        if (!blockLooksLikeFilter(code)) return;

        code.querySelectorAll('.line').forEach(highlightLineElement);
        code.dataset.filterHighlighted = '1';
    });

    document.querySelectorAll('article p > code, article li > code, article td > code, article strong > code').forEach((code) => {
        if (code.closest('pre')) return;
        if (code.dataset.filterHighlighted === '1') return;
        const text = code.textContent;
        if (PHP_MARKERS.test(text) || !FILTER_SHAPE.test(text)) return;
        code.innerHTML = renderFilterTokens(tokenizeFilterLine(text));
        code.dataset.filterHighlighted = '1';
    });
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
        highlightFilterBlocks();
    });
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :navigation="navigation">
        <div class="relative mx-auto flex max-w-site border-x border-[#eaeaea] dark:border-white/[0.1]">
            <GridCrosshairs />

            <LeftSidebar
                v-if="navigation"
                :navigation="navigation"
                :current-path="$page.url"
            />

            <main class="min-w-0 flex-1">
                <header class="relative overflow-hidden border-b border-[#eaeaea] px-6 pt-12 pb-9 sm:px-10 sm:pt-16 dark:border-white/[0.1]">
                    <div aria-hidden="true" class="brand-glow pointer-events-none absolute inset-0 opacity-60"></div>

                    <div class="relative max-w-2xl">
                        <p v-if="currentSection" class="text-[13px] font-medium text-[#737373] dark:text-[#8b8b93]">
                            {{ currentSection }}
                        </p>
                        <h1 class="mt-2 text-balance break-words text-[34px] leading-[1.06] font-semibold tracking-[-0.04em] text-[#171717] sm:text-[44px] dark:text-[#fafafa]">
                            {{ pageHeading || title }}
                        </h1>
                        <p v-if="description" class="mt-4 text-[16px] leading-relaxed text-[#525252] sm:text-[17px] dark:text-[#a1a1aa]">
                            {{ description }}
                        </p>

                        <div class="mt-6 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="press inline-flex items-center gap-2 rounded-full border px-4 py-2 text-[13px] font-medium"
                                :class="copiedMarkdown
                                    ? 'border-[#0f7a3d]/40 bg-white text-[#0f7a3d] dark:border-[#44c67f]/40 dark:bg-[#0a0a0a] dark:text-[#44c67f]'
                                    : 'border-[#e5e5e5] bg-white text-[#404040] hover:border-[#171717]/40 hover:text-[#171717] dark:border-white/[0.14] dark:bg-[#0a0a0a] dark:text-[#d4d4d8] dark:hover:text-white'"
                                @click="copyPageAsMarkdown"
                            >
                                <svg v-if="!copiedMarkdown" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ copiedMarkdown ? 'Copied' : 'Copy page' }}
                            </button>
                            <a
                                :href="markdownUrl"
                                class="press inline-flex items-center rounded-full border border-[#e5e5e5] bg-white px-4 py-2 text-[13px] font-medium text-[#404040] hover:border-[#171717]/40 hover:text-[#171717] dark:border-white/[0.14] dark:bg-[#0a0a0a] dark:text-[#d4d4d8] dark:hover:text-white"
                            >
                                View as Markdown
                            </a>
                        </div>
                    </div>
                </header>

                <div class="px-6 pt-8 pb-16 sm:px-10 sm:pt-10">
                    <article class="max-w-2xl">
                        <details class="group mb-8 rounded-xl border border-[#eaeaea] bg-[#fafafa] xl:hidden dark:border-white/[0.1] dark:bg-[#0a0a0a]">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-[13px] font-medium text-[#171717] dark:text-[#fafafa]">
                                On this page
                                <svg class="h-4 w-4 text-[#737373] transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <div class="border-t border-[#eaeaea] px-4 py-3 dark:border-white/[0.1]">
                                <TableOfContents :html="cleanedHtml" />
                            </div>
                        </details>

                        <div v-html="cleanedHtml" class="doc-content"></div>

                        <a
                            :href="githubMarkdownUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-14 inline-flex items-center gap-2 text-[13px] font-medium text-[#525252] underline decoration-[#d4d4d4] underline-offset-4 transition-colors hover:text-[#171717] hover:decoration-[#171717] dark:text-[#a1a1aa] dark:decoration-white/25 dark:hover:text-white"
                        >
                            Edit this page on GitHub
                        </a>
                    </article>
                </div>

                <nav
                    v-if="prevLink || nextLink"
                    aria-label="Previous and next page"
                    class="grid grid-cols-2 divide-x divide-[#eaeaea] border-t border-[#eaeaea] dark:divide-white/[0.1] dark:border-white/[0.1]"
                >
                    <Link
                        v-if="prevLink"
                        :href="prevLink.href"
                        class="group flex flex-col gap-1 px-6 py-6 transition-colors hover:bg-[#fafafa] sm:px-10 dark:hover:bg-white/[0.03]"
                    >
                        <span class="text-[13px] text-[#737373] dark:text-[#8b8b93]">Previous</span>
                        <span class="text-[15px] font-semibold text-[#171717] transition-colors group-hover:text-orange-ink dark:text-[#fafafa] dark:group-hover:text-[#ff7b47]">
                            {{ prevLink.title }}
                        </span>
                    </Link>
                    <div v-else></div>
                    <Link
                        v-if="nextLink"
                        :href="nextLink.href"
                        class="group flex flex-col items-end gap-1 px-6 py-6 text-right transition-colors hover:bg-[#fafafa] sm:px-10 dark:hover:bg-white/[0.03]"
                    >
                        <span class="text-[13px] text-[#737373] dark:text-[#8b8b93]">Next</span>
                        <span class="text-[15px] font-semibold text-[#171717] transition-colors group-hover:text-orange-ink dark:text-[#fafafa] dark:group-hover:text-[#ff7b47]">
                            {{ nextLink.title }}
                        </span>
                    </Link>
                </nav>
            </main>

            <RightSidebar :html="cleanedHtml" />
        </div>
    </AppLayout>
</template>

<style type="text/css">
/* Doc prose in the landing page's neutrals: ink #171717, body #404040,
   secondary #525252, muted #737373, lines #eaeaea (dark: #fafafa, #d4d4d8,
   #a1a1aa, #8b8b93, white/10). */
.doc-content {
    font-size: 16px;
    line-height: 1.75;
    color: #404040;
}
.dark .doc-content { color: #d4d4d8; }

.doc-content h2,
.doc-content h3,
.doc-content h4 {
    color: #171717;
    font-weight: 600;
    scroll-margin-top: 5rem;
}
.dark .doc-content h2,
.dark .doc-content h3,
.dark .doc-content h4 { color: #fafafa; }

.doc-content h2 {
    font-size: 26px;
    line-height: 1.2;
    letter-spacing: -0.035em;
    margin-top: 3.5rem;
    margin-bottom: 1rem;
}

.doc-content h3 {
    font-size: 20px;
    line-height: 1.3;
    letter-spacing: -0.025em;
    margin-top: 2.5rem;
    margin-bottom: 0.75rem;
}

.doc-content h4 {
    font-size: 17px;
    letter-spacing: -0.015em;
    margin-top: 2rem;
    margin-bottom: 0.75rem;
}

.doc-content > :first-child { margin-top: 0; }

.doc-content p {
    margin: 1.25rem 0;
}

.doc-content a {
    color: #171717;
    font-weight: 500;
    text-decoration: underline;
    text-decoration-color: rgb(195 58 4 / 0.45);
    text-decoration-thickness: 1px;
    text-underline-offset: 4px;
    transition: color 150ms, text-decoration-color 150ms;
}
.doc-content a:hover {
    color: var(--color-orange-ink);
    text-decoration-color: currentColor;
}
.dark .doc-content a { color: #fafafa; text-decoration-color: rgb(255 123 71 / 0.5); }
.dark .doc-content a:hover { color: #ff7b47; }

.doc-content strong {
    color: #171717;
    font-weight: 600;
}
.dark .doc-content strong { color: #fafafa; }

.doc-content ul,
.doc-content ol {
    margin: 1.25rem 0;
    padding-left: 1.25rem;
}
.doc-content li { margin: 0.4rem 0; padding-left: 0.25rem; }
.doc-content ul li { list-style-type: disc; }
.doc-content ol li { list-style-type: decimal; }
.doc-content li::marker { color: #a1a1aa; }
.dark .doc-content li::marker { color: #52525b; }

.doc-content blockquote {
    border-left: 2px solid #eaeaea;
    padding-left: 1rem;
    color: #525252;
    margin: 1.5rem 0;
}
.dark .doc-content blockquote { color: #a1a1aa; border-left-color: rgb(255 255 255 / 0.1); }

/* Tables sit in a framed card like the landing panels; rows divide. */
.doc-content .table-scroll {
    overflow-x: auto;
    margin: 2rem 0;
    border: 1px solid #eaeaea;
    border-radius: 12px;
    scrollbar-width: thin;
}
.dark .doc-content .table-scroll { border-color: rgb(255 255 255 / 0.1); }

.doc-content table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14.5px;
}

.doc-content th,
.doc-content td {
    padding: 0.7rem 1rem;
    text-align: left;
    vertical-align: top;
    border-bottom: 1px solid #eaeaea;
}
.doc-content tr:last-child td { border-bottom: 0; }
.dark .doc-content th,
.dark .doc-content td { border-bottom-color: rgb(255 255 255 / 0.1); }

.doc-content th {
    font-size: 13px;
    font-weight: 500;
    color: #525252;
    background-color: #fafafa;
}
.dark .doc-content th { color: #a1a1aa; background-color: #0a0a0a; }

@media (max-width: 639px) {
    .doc-content th,
    .doc-content td { padding: 0.5rem 0.75rem; font-size: 14px; }
    .doc-content pre code { font-size: 13px; }
}

.doc-content hr {
    border: 0;
    border-top: 1px solid #eaeaea;
    margin: 3rem 0;
}
.dark .doc-content hr { border-top-color: rgb(255 255 255 / 0.1); }

/* Code blocks */
.doc-content pre {
    background-color: var(--code-bg);
    border: 1px solid var(--code-border);
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

/* One grid row per Phiki .line, as wide as the longest line, so a
   highlighted line paints a band across the whole block. */
.doc-content pre code {
    display: grid;
    width: max-content;
    min-width: 100%;
    padding: 1rem 0;
    background-color: transparent;
    font-size: 0.875rem;
    line-height: 1.7;
}
.doc-content pre code .line { padding: 0 1rem; }
.doc-content pre code .line.highlight { background-color: var(--code-highlight); }

/* Inline code */
.doc-content code:not(pre code) {
    background-color: #fafafa;
    color: #171717;
    border: 1px solid #eaeaea;
    border-radius: 6px;
    padding: 0.1rem 0.35rem;
    font-size: 0.85em;
    font-family: var(--font-mono);
    overflow-wrap: break-word;
}
.dark .doc-content code:not(pre code) {
    background-color: rgb(255 255 255 / 0.06);
    color: #fafafa;
    border-color: rgb(255 255 255 / 0.1);
}

/* Sigmie filter DSL syntax highlighting (applied client-side in
   Document.vue's highlightFilterBlocks), in the shared code palette. */
.doc-content .sig-filter-field { color: var(--code-property); }
.doc-content .sig-filter-op,
.doc-content .sig-filter-bracket { color: var(--code-punctuation); }
.doc-content .sig-filter-value { color: var(--code-string); }
.doc-content .sig-filter-logic { color: var(--code-keyword); }
.doc-content .sig-filter-number { color: var(--code-number); }
.doc-content .sig-filter-text { color: inherit; }
.doc-content .sig-filter-comment { color: var(--code-comment); }

/* Copy code button */
.copy-code-button {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.375rem;
    background-color: var(--code-bar);
    border: 1px solid var(--code-border);
    border-radius: 9999px;
    color: var(--code-muted);
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s ease, color 0.2s ease;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
}
.copy-code-button:hover { color: var(--code-fg); }
.copy-code-button.copied { color: var(--code-string); }
pre:hover .copy-code-button { opacity: 1; }
@media (hover: none) {
    .copy-code-button { opacity: 1; }
}
.copy-code-button .hidden { display: none; }

.heading-permalink {
    margin-right: 0.5rem;
    text-decoration: none;
    color: #a1a1aa;
    transition: color 0.15s;
}
.heading-permalink:hover { color: #171717; }
.dark .heading-permalink:hover { color: #fafafa; }

/* Callouts take the ranking mode colours: info keyword blue, danger
   hybrid orange, warning ink. */
.doc-content .callout {
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin: 1.5rem 0;
    border: 1px solid #eaeaea;
    border-left: 3px solid var(--callout, #171717);
    background-color: #fafafa;
    color: #404040;
}
.dark .doc-content .callout { background-color: #0a0a0a; border-color: rgb(255 255 255 / 0.1); border-left-color: var(--callout, #fafafa); color: #d4d4d8; }
.doc-content .callout.info { --callout: #0062d1; }
.doc-content .callout.danger { --callout: var(--color-orange-ink); }
.dark .doc-content .callout.info { --callout: #3ba7ff; }
.dark .doc-content .callout.danger { --callout: #ff7b47; }
</style>
