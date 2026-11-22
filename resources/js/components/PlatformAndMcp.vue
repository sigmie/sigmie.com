<script setup>
import { computed, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import CodePreview from './CodePreview.vue';
import GridCrosshairs from './GridCrosshairs.vue';
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
    <section id="platform" class="relative scroll-mt-16 border-t border-[#eaeaea] bg-white dark:border-white/[0.1] dark:bg-black">
        <GridCrosshairs />

        <div class="grid divide-y divide-[#eaeaea] lg:grid-cols-12 lg:divide-x lg:divide-y-0 dark:divide-white/[0.1]">
            <div class="flex min-w-0 flex-col lg:col-span-6">
                <div class="border-b border-[#eaeaea] px-5 py-7 sm:px-8 dark:border-white/[0.1]">
                    <p class="font-mono text-[11px] uppercase tracking-[0.12em] text-orange-ink dark:text-[#ff7b47]">
                        Reference
                    </p>
                    <h2 class="mt-2 text-[26px] leading-[1.1] font-semibold tracking-[-0.035em] text-[#171717] sm:text-[30px] dark:text-[#fafafa]">
                        One fluent API across your search stack.
                    </h2>
                </div>

                <div class="divide-y divide-[#eaeaea] dark:divide-white/[0.08]">
                    <div
                        v-for="item in PLATFORM_REFERENCES"
                        :key="item.id"
                        class="flex items-start justify-between gap-4 px-5 py-3.5 transition-colors sm:px-8"
                        :class="activeRefId === item.id ? 'bg-[#fafafa] dark:bg-white/[0.04]' : 'hover:bg-[#fafafa]/60 dark:hover:bg-white/[0.02]'"
                    >
                        <button
                            type="button"
                            class="min-w-0 flex-1 text-left"
                            :aria-pressed="activeRefId === item.id"
                            @click="activeRefId = item.id"
                        >
                            <span
                                class="inline-flex items-center gap-2 text-[14px] font-semibold transition-colors"
                                :class="activeRefId === item.id ? 'text-orange-ink dark:text-[#ff7b47]' : 'text-[#171717] dark:text-[#fafafa]'"
                            >
                                <span
                                    v-if="activeRefId === item.id"
                                    class="h-1.5 w-1.5 rounded-full bg-magic-orange"
                                    aria-hidden="true"
                                ></span>
                                {{ item.title }}
                            </span>
                            <span class="mt-0.5 block text-[13px] leading-snug text-[#525252] dark:text-[#a1a1aa]">
                                {{ item.summary }}
                            </span>
                        </button>
                        <Link
                            :href="item.href"
                            class="mt-0.5 shrink-0 font-mono text-[11.5px] font-medium text-[#525252] transition-colors hover:text-orange-ink dark:text-[#a1a1aa] dark:hover:text-[#ff7b47]"
                        >
                            Docs →
                        </Link>
                    </div>
                </div>

                <div class="mt-auto border-t border-[#eaeaea] dark:border-white/[0.1]">
                    <CodePreview
                        :code="activeRef.code"
                        :filename="activeRef.filename"
                        language="php"
                        :flat="true"
                        :compact="true"
                    />
                </div>
            </div>

            <div class="flex min-w-0 flex-col lg:col-span-6">
                <div class="border-b border-[#eaeaea] px-5 py-7 sm:px-8 dark:border-white/[0.1]">
                    <p class="font-mono text-[11px] uppercase tracking-[0.12em] text-orange-ink dark:text-[#ff7b47]">
                        MCP · sigmie.com/mcp
                    </p>
                    <h2 class="mt-2 text-[26px] leading-[1.1] font-semibold tracking-[-0.035em] text-[#171717] sm:text-[30px] dark:text-[#fafafa]">
                        What your coding agent actually retrieves.
                    </h2>
                    <p class="mt-2.5 text-[14px] leading-relaxed text-[#525252] dark:text-[#a1a1aa]">
                        Connect Claude Code or Cursor to <code class="font-mono text-[13px] text-[#171717] dark:text-[#fafafa]">https://sigmie.com/mcp</code> to query <code class="font-mono text-[13px] text-[#171717] dark:text-[#fafafa]">search_docs</code>, <code class="font-mono text-[13px] text-[#171717] dark:text-[#fafafa]">read_doc</code>, and <code class="font-mono text-[13px] text-[#171717] dark:text-[#fafafa]">list_docs</code> against Sigmie v2.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full bg-[#171717] px-3.5 py-1.5 font-mono text-[12px] font-medium text-white transition-colors hover:bg-black dark:bg-[#fafafa] dark:text-[#171717] dark:hover:bg-white"
                            @click="copyMcpConfig"
                        >
                            <span>{{ copiedConfig ? 'Copied .mcp.json' : 'Copy .mcp.json' }}</span>
                        </button>
                        <button
                            type="button"
                            class="rounded-full border border-[#e5e5e5] bg-white px-3.5 py-1.5 font-mono text-[12px] text-[#404040] transition-colors hover:border-[#171717]/40 hover:text-[#171717] dark:border-white/[0.14] dark:bg-[#0a0a0a] dark:text-[#d4d4d8] dark:hover:text-white"
                            :aria-expanded="showConfig"
                            @click="showConfig = !showConfig"
                        >
                            {{ showConfig ? 'Hide config' : 'View config' }}
                        </button>
                    </div>
                </div>

                <div v-if="showConfig" class="border-b border-[#eaeaea] dark:border-white/[0.1]">
                    <CodePreview
                        :code="MCP_CONFIG_JSON"
                        filename=".mcp.json"
                        language="json"
                        :flat="true"
                        :compact="true"
                    />
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#eaeaea] bg-[#fafafa] px-5 py-3 sm:px-8 dark:border-white/[0.08] dark:bg-[#0a0a0a]">
                    <span class="font-mono text-[11.5px] font-medium text-[#171717] dark:text-[#e4e4e7]">
                        tool: search_docs
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="(sample, idx) in mcpSamples"
                            :key="sample.query"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 font-mono text-[11px] transition-colors"
                            :class="activeMcpIndex === idx
                                ? 'border-[#171717] bg-[#171717] text-white dark:border-[#fafafa] dark:bg-[#fafafa] dark:text-[#171717]'
                                : 'border-[#e5e5e5] bg-white text-[#404040] hover:border-[#171717]/40 dark:border-white/[0.14] dark:bg-black dark:text-[#d4d4d8]'"
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

                <div class="flex flex-1 flex-col justify-between p-5 sm:p-8" :class="loadingMcp ? 'opacity-65 transition-opacity' : ''">
                    <div>
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <Link
                                :href="activeMcpResult.url || '/docs/v2/search'"
                                class="text-[15.5px] font-semibold text-[#171717] transition-colors hover:text-orange-ink dark:text-[#fafafa] dark:hover:text-[#ff7b47]"
                            >
                                {{ activeMcpResult.title }} →
                            </Link>
                            <span class="font-mono text-[11px] text-muted-ink dark:text-[#a1a1aa]">
                                {{ activeMcpResult.url }}
                            </span>
                        </div>

                        <pre class="no-ligatures mt-4 overflow-x-auto rounded-xl border border-[#eaeaea] bg-[#fafafa] p-4 font-mono text-[12px] leading-[1.65] whitespace-pre-wrap text-[#262626] dark:border-white/[0.1] dark:bg-[#0b0d11] dark:text-[#d4d4d8]"><code>{{ cleanedPassage }}</code></pre>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-6 border-t border-[#eaeaea] pt-4 text-[13px] font-medium dark:border-white/[0.08]">
                        <Link href="/docs/v2/mcp" class="text-[#171717] transition-colors hover:text-orange-ink dark:text-[#fafafa] dark:hover:text-[#ff7b47]">
                            MCP guide →
                        </Link>
                        <a href="/llms.txt" class="text-muted-ink transition-colors hover:text-[#171717] dark:text-[#a1a1aa] dark:hover:text-white">llms.txt</a>
                        <a href="/llms-full.txt" class="text-muted-ink transition-colors hover:text-[#171717] dark:text-[#a1a1aa] dark:hover:text-white">llms-full.txt</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
