<script setup>
import { computed, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import CodePreview from './CodePreview.vue';
import { MCP_CONFIG_JSON, PLATFORM_REFERENCES } from '../data/landingExamples';

const props = defineProps({
    initialMcp: {
        type: Array,
        default: () => [],
    },
});

const activeRefId = ref(PLATFORM_REFERENCES[0].id);
const activeRef = computed(() => (
    PLATFORM_REFERENCES.find((item) => item.id === activeRefId.value) || PLATFORM_REFERENCES[0]
));

const mcpSamples = props.initialMcp?.length
    ? props.initialMcp
    : [
          {
              query: 'hybrid semantic search',
              title: 'Add semantic search',
              url: '/docs/v2/search#add-semantic-search',
              page: 'search',
              content: "Call semantic() to match by vector as well as by keyword:\n\n$sigmie->newSearch('articles')\n    ->properties($props)\n    ->semantic()\n    ->queryString('artificial intelligence')\n    ->get();",
              total: 10,
          },
      ];

const activeMcpIndex = ref(0);
const activeMcpResult = ref(mcpSamples[0]);
const loadingMcp = ref(false);
const showConfig = ref(false);
const copiedConfig = ref(false);
let copyTimer;
let mcpReqSeq = 0;

const cleanedPassage = computed(() => (
    String(activeMcpResult.value?.content || '')
        .replace(/\s*\/\/\s*\[tl!.*?\]/g, '')
        .trim()
        .slice(0, 420)
));

const runMcpSample = async (sample, idx) => {
    activeMcpIndex.value = idx;
    activeMcpResult.value = sample;
    const seq = ++mcpReqSeq;
    loadingMcp.value = true;

    try {
        const response = await axios.post('/api/search/docs', { query: sample.query });
        if (seq !== mcpReqSeq) return;
        const top = response.data?.results?.[0];
        if (top) {
            activeMcpResult.value = {
                query: sample.query,
                title: top.title,
                url: top.url,
                page: top.page,
                content: top.content,
                total: response.data?.total || response.data?.results?.length || 1,
            };
        }
    } catch {
        loadingMcp.value = false;
    } finally {
        if (seq === mcpReqSeq) loadingMcp.value = false;
    }
};

const copyMcpConfig = async () => {
    try {
        await navigator.clipboard.writeText(MCP_CONFIG_JSON);
        copiedConfig.value = true;
        clearTimeout(copyTimer);
        copyTimer = setTimeout(() => {
            copiedConfig.value = false;
        }, 1600);
    } catch {
        copiedConfig.value = false;
    }
};

onUnmounted(() => clearTimeout(copyTimer));
</script>

<template>
    <section id="platform" class="scroll-mt-20 bg-canvas-white py-16 sm:py-20 lg:py-24 dark:bg-[#08080a]">
        <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-12 lg:items-start">
                <div class="min-w-0 lg:col-span-6">
                    <p class="font-mono text-[11.5px] uppercase tracking-[0.09em] text-orange-ink dark:text-[#ff7b47]">
                        Reference
                    </p>
                    <h2 class="mt-2.5 text-[26px] leading-[1.12] font-semibold tracking-[-0.03em] text-graphite sm:text-[32px] dark:text-[#f5f5f4]">
                        One fluent API across your search stack.
                    </h2>

                    <div class="mt-5 divide-y divide-light-steel border-y border-light-steel dark:divide-[#242422] dark:border-[#242422]">
                        <div
                            v-for="item in PLATFORM_REFERENCES"
                            :key="item.id"
                            class="flex items-start justify-between gap-4 py-2.5 transition-colors"
                            :class="activeRefId === item.id ? 'bg-ghostly-gray/90 -mx-3 px-3 rounded-xl dark:bg-[#111113]' : ''"
                        >
                            <button
                                type="button"
                                class="min-w-0 flex-1 text-left"
                                :aria-pressed="activeRefId === item.id"
                                @click="activeRefId = item.id"
                            >
                                <span
                                    class="inline-flex items-center gap-2 text-[14px] font-semibold transition-colors"
                                    :class="activeRefId === item.id ? 'text-orange-ink dark:text-[#ff7b47]' : 'text-graphite dark:text-[#f5f5f4]'"
                                >
                                    <span
                                        v-if="activeRefId === item.id"
                                        class="h-1.5 w-1.5 rounded-full bg-magic-orange"
                                        aria-hidden="true"
                                    ></span>
                                    {{ item.title }}
                                </span>
                                <span class="mt-0.5 block text-[12.5px] leading-snug text-charcoal/90 dark:text-[#b4b4b0]">
                                    {{ item.summary }}
                                </span>
                            </button>
                            <Link
                                :href="item.href"
                                class="mt-0.5 shrink-0 font-mono text-[11.5px] font-medium text-charcoal transition-colors hover:text-orange-ink dark:text-[#b4b4b0] dark:hover:text-[#ff7b47]"
                            >
                                Docs →
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4 min-w-0">
                        <CodePreview
                            :code="activeRef.code"
                            :filename="activeRef.filename"
                            language="php"
                            :compact="true"
                        />
                    </div>
                </div>

                <div class="min-w-0 lg:col-span-6">
                    <p class="font-mono text-[11.5px] uppercase tracking-[0.09em] text-orange-ink dark:text-[#ff7b47]">
                        MCP · sigmie.com/mcp
                    </p>
                    <h2 class="mt-2.5 text-[26px] leading-[1.12] font-semibold tracking-[-0.03em] text-graphite sm:text-[32px] dark:text-[#f5f5f4]">
                        What your coding agent actually retrieves.
                    </h2>
                    <p class="mt-3 text-[14.5px] leading-relaxed text-charcoal dark:text-[#b4b4b0]">
                        Point Claude Code or Cursor at <code class="font-mono text-[13px] text-graphite dark:text-[#f5f5f4]">https://sigmie.com/mcp</code> to run <code class="font-mono text-[13px] text-graphite dark:text-[#f5f5f4]">search_docs</code>, <code class="font-mono text-[13px] text-graphite dark:text-[#f5f5f4]">read_doc</code>, and <code class="font-mono text-[13px] text-graphite dark:text-[#f5f5f4]">list_docs</code> directly against Sigmie v2.
                    </p>

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full bg-graphite px-3.5 py-1.5 font-mono text-[12px] font-medium text-white transition-colors hover:bg-black dark:bg-[#f5f5f4] dark:text-graphite dark:hover:bg-white"
                            @click="copyMcpConfig"
                        >
                            <span>{{ copiedConfig ? 'Copied .mcp.json' : 'Copy .mcp.json' }}</span>
                        </button>
                        <button
                            type="button"
                            class="rounded-full border border-light-steel bg-ghostly-gray px-3.5 py-1.5 font-mono text-[12px] text-charcoal transition-colors hover:border-graphite/40 hover:text-graphite dark:border-[#242422] dark:bg-[#111113] dark:text-[#d4d4d8] dark:hover:text-white"
                            :aria-expanded="showConfig"
                            @click="showConfig = !showConfig"
                        >
                            {{ showConfig ? 'Hide config' : 'View config' }}
                        </button>
                    </div>

                    <div v-if="showConfig" class="mt-4 min-w-0">
                        <CodePreview
                            :code="MCP_CONFIG_JSON"
                            filename=".mcp.json"
                            language="json"
                            :compact="true"
                        />
                    </div>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-light-steel bg-ghostly-gray/75 dark:border-[#242422] dark:bg-[#111113]">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-light-steel px-4 py-2.5 dark:border-[#242422]">
                            <span class="font-mono text-[11.5px] text-graphite dark:text-[#e4e4e7]">
                                tool: search_docs
                            </span>
                            <div class="flex flex-wrap gap-1">
                                <button
                                    v-for="(sample, idx) in mcpSamples"
                                    :key="sample.query"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 font-mono text-[11px] transition-colors"
                                    :class="activeMcpIndex === idx
                                        ? 'border-graphite bg-graphite text-white dark:border-[#f5f5f4] dark:bg-[#f5f5f4] dark:text-graphite'
                                        : 'border-light-steel bg-canvas-white text-charcoal hover:border-graphite/40 dark:border-[#2a2a28] dark:bg-black dark:text-[#d4d4d8]'"
                                    @click="runMcpSample(sample, idx)"
                                >
                                    <span
                                        v-if="activeMcpIndex === idx"
                                        class="h-1.5 w-1.5 rounded-full bg-magic-orange"
                                        aria-hidden="true"
                                    ></span>
                                    {{ sample.query }}
                                </button>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5" :class="loadingMcp ? 'opacity-65 transition-opacity' : ''">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <Link
                                    :href="activeMcpResult.url || '/docs/v2/search'"
                                    class="text-[15px] font-semibold text-graphite transition-colors hover:text-orange-ink dark:text-[#f5f5f4] dark:hover:text-[#ff7b47]"
                                >
                                    {{ activeMcpResult.title }} →
                                </Link>
                                <span class="font-mono text-[11px] text-muted-ink dark:text-[#9ca3af]">
                                    {{ activeMcpResult.url }}
                                </span>
                            </div>

                            <pre class="no-ligatures mt-3 overflow-x-auto rounded-xl border border-light-steel bg-canvas-white p-3.5 font-mono text-[12px] leading-[1.65] whitespace-pre-wrap text-charcoal dark:border-[#242422] dark:bg-[#0b0d11] dark:text-[#d4d4d8]"><code>{{ cleanedPassage }}</code></pre>

                            <div class="mt-4 flex flex-wrap items-center gap-5 text-[12.5px] font-medium">
                                <Link href="/docs/v2/mcp" class="text-graphite transition-colors hover:text-orange-ink dark:text-[#f5f5f4] dark:hover:text-[#ff7b47]">
                                    MCP guide →
                                </Link>
                                <a href="/llms.txt" class="text-muted-ink transition-colors hover:text-graphite dark:text-[#9ca3af] dark:hover:text-white">llms.txt</a>
                                <a href="/llms-full.txt" class="text-muted-ink transition-colors hover:text-graphite dark:text-[#9ca3af] dark:hover:text-white">llms-full.txt</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
