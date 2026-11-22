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
                <div class="border-b border-[#eaeaea] px-6 pt-16 pb-8 sm:px-10 dark:border-white/[0.1]">
                    <h2 class="text-balance text-[28px] leading-[1.08] font-semibold tracking-[-0.035em] text-[#171717] sm:text-[34px] dark:text-[#fafafa]">
                        One builder for the whole index.
                    </h2>
                </div>

                <div class="divide-y divide-[#eaeaea] dark:divide-white/[0.08]">
                    <div
                        v-for="item in PLATFORM_REFERENCES"
                        :key="item.id"
                        class="flex items-start justify-between gap-4 px-6 py-3.5 transition-colors duration-150 sm:px-10"
                        :class="activeRefId === item.id ? 'bg-[#fafafa] dark:bg-white/[0.04]' : 'hover:bg-[#fafafa]/60 dark:hover:bg-white/[0.02]'"
                    >
                        <button
                            type="button"
                            class="min-w-0 flex-1 text-left"
                            :aria-pressed="activeRefId === item.id"
                            @click="activeRefId = item.id"
                        >
                            <span
                                class="text-[14.5px] font-semibold text-[#171717] dark:text-[#fafafa]"
                            >
                                {{ item.title }}
                            </span>
                            <span class="mt-0.5 block text-[13.5px] leading-snug text-[#525252] dark:text-[#a1a1aa]">
                                {{ item.summary }}
                            </span>
                        </button>
                        <Link
                            :href="item.href"
                            class="mt-0.5 shrink-0 text-[13px] font-medium text-[#525252] underline decoration-[#d4d4d4] underline-offset-4 transition-colors hover:text-[#171717] hover:decoration-[#171717] dark:text-[#a1a1aa] dark:decoration-white/25 dark:hover:text-white"
                            :aria-label="`${item.title} docs`"
                        >
                            Docs
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
                <div class="border-b border-[#eaeaea] px-6 pt-16 pb-8 sm:px-10 dark:border-white/[0.1]">
                    <h2 class="text-balance text-[28px] leading-[1.08] font-semibold tracking-[-0.035em] text-[#171717] sm:text-[34px] dark:text-[#fafafa]">
                        The docs, inside your coding agent.
                    </h2>
                    <p class="mt-3 text-[15px] leading-relaxed text-[#525252] dark:text-[#a1a1aa]">
                        Connect Claude Code or Cursor to <code class="font-mono text-[13px] text-[#171717] dark:text-[#fafafa]">https://sigmie.com/mcp</code> to query <code class="font-mono text-[13px] text-[#171717] dark:text-[#fafafa]">search_docs</code>, <code class="font-mono text-[13px] text-[#171717] dark:text-[#fafafa]">read_doc</code>, and <code class="font-mono text-[13px] text-[#171717] dark:text-[#fafafa]">list_docs</code> against Sigmie v2.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="press rounded-full bg-[#171717] px-4 py-2 text-[13px] font-medium text-white hover:bg-black dark:bg-[#fafafa] dark:text-[#171717] dark:hover:bg-white"
                            @click="copyMcpConfig"
                        >
                            <span>{{ copiedConfig ? 'Copied .mcp.json' : 'Copy .mcp.json' }}</span>
                        </button>
                        <button
                            type="button"
                            class="press rounded-full border border-[#e5e5e5] bg-white px-4 py-2 text-[13px] font-medium text-[#404040] hover:border-[#171717]/40 hover:text-[#171717] dark:border-white/[0.14] dark:bg-[#0a0a0a] dark:text-[#d4d4d8] dark:hover:text-white"
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

                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#eaeaea] bg-[#fafafa] px-6 py-3 sm:px-10 dark:border-white/[0.08] dark:bg-[#0a0a0a]">
                    <span class="text-[13px] text-[#525252] dark:text-[#a1a1aa]">
                        Ask <code class="font-mono text-[12.5px] text-[#171717] dark:text-[#fafafa]">search_docs</code>
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="(sample, idx) in mcpSamples"
                            :key="sample.query"
                            type="button"
                            class="press rounded-full border px-2.5 py-1 text-[12.5px]"
                            :class="activeMcpIndex === idx
                                ? 'border-[#171717] bg-[#171717] text-white dark:border-[#fafafa] dark:bg-[#fafafa] dark:text-[#171717]'
                                : 'border-[#e5e5e5] bg-white text-[#404040] hover:border-[#171717]/40 dark:border-white/[0.14] dark:bg-black dark:text-[#d4d4d8]'"
                            @click="runMcpSample(sample, idx)"
                        >
                            {{ sample.query }}
                        </button>
                    </div>
                </div>

                <div class="flex flex-1 flex-col justify-between px-6 py-8 sm:px-10" :class="loadingMcp ? 'opacity-65 transition-opacity' : ''">
                    <div>
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <Link
                                :href="activeMcpResult.url || '/docs/v2/search'"
                                class="text-[15.5px] font-semibold text-[#171717] transition-colors hover:text-orange-ink dark:text-[#fafafa] dark:hover:text-[#ff7b47]"
                            >
                                {{ activeMcpResult.title }}
                            </Link>
                            <span class="text-[13px] text-[#737373] dark:text-[#8b8b93]">
                                {{ activeMcpResult.url }}
                            </span>
                        </div>

                        <pre class="no-ligatures mt-4 overflow-x-auto rounded-xl border border-[#eaeaea] bg-[#fafafa] p-4 font-mono text-[12px] leading-[1.65] whitespace-pre-wrap text-[#262626] dark:border-white/[0.1] dark:bg-[#0b0d11] dark:text-[#d4d4d8]"><code>{{ cleanedPassage }}</code></pre>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-6 border-t border-[#eaeaea] pt-4 text-[13px] font-medium dark:border-white/[0.08]">
                        <Link href="/docs/v2/mcp" class="text-[#171717] transition-colors hover:text-orange-ink dark:text-[#fafafa] dark:hover:text-[#ff7b47]">
                            Read the MCP guide
                        </Link>
                        <a href="/llms.txt" class="text-muted-ink transition-colors hover:text-[#171717] dark:text-[#a1a1aa] dark:hover:text-white">llms.txt</a>
                        <a href="/llms-full.txt" class="text-muted-ink transition-colors hover:text-[#171717] dark:text-[#a1a1aa] dark:hover:text-white">llms-full.txt</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
