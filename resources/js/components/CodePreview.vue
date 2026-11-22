<script setup>
import { computed, onUnmounted, ref } from 'vue';

const props = defineProps({
    code: {
        type: String,
        required: true,
    },
    filename: {
        type: String,
        default: 'code.php',
    },
    language: {
        type: String,
        default: 'php',
        validator: (value) => value === 'php' || value === 'json',
    },
    highlightLines: {
        type: Array,
        default: () => [],
    },
    live: {
        type: Boolean,
        default: false,
    },
    maxHeightClass: {
        type: String,
        default: '',
    },
    frameless: {
        type: Boolean,
        default: false,
    },
    flat: {
        type: Boolean,
        default: false,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const copied = ref(false);
let copyTimer;

const languageLabel = computed(() => (props.language === 'json' ? 'JSON' : 'PHP'));

const parsedLines = computed(() => {
    const parse = props.language === 'json' ? parseJsonLine : parsePhpLine;

    return props.code.split('\n').map((line, index) => ({
        text: line,
        tokens: parse(line),
        isHighlighted: props.highlightLines.includes(index + 1),
    }));
});

const PHP_KEYWORDS = new Set([
    'use', 'class', 'extends', 'implements', 'new', 'public', 'protected',
    'private', 'function', 'return', 'array', 'namespace', 'interface',
    'string', 'int', 'void', 'bool', 'true', 'false', 'null',
]);

const parsePhpLine = (text) => {
    if (!text) return [];
    if (text.trim().startsWith('//')) {
        return [{ type: 'comment', value: text }];
    }

    const tokens = [];
    let current = '';
    let inString = false;
    let stringChar = '';

    const flush = () => {
        if (!current) return;
        tokens.push(...tokenizePhpWord(current));
        current = '';
    };

    for (let i = 0; i < text.length; i++) {
        const char = text[i];
        const next = text[i + 1] || '';

        if ((char === "'" || char === '"') && !inString) {
            flush();
            inString = true;
            stringChar = char;
            current = char;
            continue;
        }

        if (char === stringChar && inString) {
            current += char;
            tokens.push(...expandStringToken(current));
            current = '';
            inString = false;
            continue;
        }

        if (inString) {
            current += char;
            continue;
        }

        if (char === '/' && next === '/') {
            flush();
            tokens.push({ type: 'comment', value: text.slice(i) });
            break;
        }

        if (char === '=' && next === '>') {
            flush();
            tokens.push({ type: 'operator', value: '=>' });
            i += 1;
            continue;
        }

        if (char === '-' && next === '>') {
            flush();
            tokens.push({ type: 'operator', value: '->' });
            i += 1;
            continue;
        }

        if (char === ':' && next === ':') {
            flush();
            tokens.push({ type: 'operator', value: '::' });
            i += 1;
            continue;
        }

        if ('()[]{},;:=?\\'.includes(char)) {
            flush();
            const type = char === ',' ? 'comma' : char === ';' ? 'semicolon' : 'bracket';
            tokens.push({ type, value: char });
            continue;
        }

        if (char === ' ') {
            flush();
            tokens.push({ type: 'space', value: ' ' });
            continue;
        }

        current += char;
    }

    if (current) {
        tokens.push(...(inString ? expandStringToken(current) : tokenizePhpWord(current)));
    }

    return tokens;
};

const tokenizePhpWord = (text) => {
    if (!text) return [];
    if (PHP_KEYWORDS.has(text)) return [{ type: 'keyword', value: text }];
    if (text.startsWith('$')) return [{ type: 'variable', value: text }];
    if (/^[A-Z][A-Za-z0-9]+$/.test(text)) return [{ type: 'class', value: text }];
    if (/^[a-z][A-Za-z0-9]*$/.test(text) && text.length > 1) return [{ type: 'function', value: text }];
    if (/^-?\d+(\.\d+)?$/.test(text)) return [{ type: 'number', value: text }];
    return [{ type: 'text', value: text }];
};

const FILTER_SHAPE = /:[^\s'"]|[<>]=?\s*\d|\bAND\b|\bOR\b|\bNOT\b|\[[^\]]*\.\.[^\]]*\]/i;

const expandStringToken = (raw) => {
    if (raw.length < 2) return [{ type: 'string', value: raw }];
    const quote = raw[0];
    if (quote !== "'" && quote !== '"') return [{ type: 'string', value: raw }];
    const inner = raw.slice(1, -1);
    if (!FILTER_SHAPE.test(inner)) return [{ type: 'string', value: raw }];

    return [
        { type: 'string', value: quote },
        ...tokenizeFilter(inner),
        { type: 'string', value: quote },
    ];
};

const tokenizeFilter = (text) => {
    const out = [];
    let i = 0;

    while (i < text.length) {
        const ch = text[i];

        if (/\s/.test(ch)) {
            const start = i;
            while (i < text.length && /\s/.test(text[i])) i += 1;
            out.push({ type: 'space', value: text.slice(start, i) });
            continue;
        }

        if (ch === '"' || ch === "'") {
            const open = ch;
            const start = i;
            i += 1;
            while (i < text.length && text[i] !== open) i += 1;
            if (i < text.length) i += 1;
            out.push({ type: 'filter-value', value: text.slice(start, i) });
            continue;
        }

        if ((ch === '<' || ch === '>') && text[i + 1] === '=') {
            out.push({ type: 'filter-op', value: ch + '=' });
            i += 2;
            continue;
        }

        if ('<>:=!'.includes(ch)) {
            out.push({ type: 'filter-op', value: ch });
            i += 1;
            continue;
        }

        if (ch === '[' || ch === ']') {
            out.push({ type: 'filter-bracket', value: ch });
            i += 1;
            continue;
        }

        if (ch === '.' && text[i + 1] === '.') {
            out.push({ type: 'filter-bracket', value: '..' });
            i += 2;
            continue;
        }

        if (/[A-Za-z0-9_.]/.test(ch)) {
            const start = i;
            while (i < text.length && /[A-Za-z0-9_.]/.test(text[i])) i += 1;
            const word = text.slice(start, i);
            const upper = word.toUpperCase();
            if (upper === 'AND' || upper === 'OR' || upper === 'NOT') {
                out.push({ type: 'filter-logic', value: word });
            } else if (word === 'true' || word === 'false' || word === 'null' || /^-?\d+(\.\d+)?$/.test(word)) {
                out.push({ type: 'filter-number', value: word });
            } else {
                out.push({ type: 'filter-field', value: word });
            }
            continue;
        }

        out.push({ type: 'filter-text', value: ch });
        i += 1;
    }

    return out;
};

const parseJsonLine = (text) => {
    const tokens = [];
    let i = 0;

    while (i < text.length) {
        const ch = text[i];

        if (ch === ' ' || ch === '\t') {
            tokens.push({ type: 'space', value: ch });
            i += 1;
            continue;
        }

        if (ch === '"') {
            let j = i + 1;
            let value = '"';
            while (j < text.length) {
                value += text[j];
                if (text[j] === '"' && text[j - 1] !== '\\') {
                    j += 1;
                    break;
                }
                j += 1;
            }
            let k = j;
            while (k < text.length && text[k] === ' ') k += 1;
            tokens.push({ type: text[k] === ':' ? 'key' : 'string', value });
            i = j;
            continue;
        }

        if (ch === '-' || (ch >= '0' && ch <= '9')) {
            let j = i + 1;
            while (j < text.length && /[0-9.]/.test(text[j])) j += 1;
            tokens.push({ type: 'number', value: text.slice(i, j) });
            i = j;
            continue;
        }

        if (/[A-Za-z]/.test(ch)) {
            let j = i + 1;
            while (j < text.length && /[A-Za-z]/.test(text[j])) j += 1;
            const word = text.slice(i, j);
            const type = word === 'true' || word === 'false' || word === 'null' ? 'boolean' : 'text';
            tokens.push({ type, value: word });
            i = j;
            continue;
        }

        tokens.push({ type: 'punctuation', value: ch });
        i += 1;
    }

    return tokens;
};

const getTokenClass = (token) => {
    switch (token.type) {
        case 'keyword':
        case 'filter-logic':
        case 'boolean':
            return 'text-[#ff7b47]';
        case 'operator':
        case 'filter-op':
            return 'text-[#ff8f61]';
        case 'string':
        case 'filter-value':
            return 'text-[#63df9a]';
        case 'variable':
            return 'text-[#f4f4f5]';
        case 'function':
            return 'text-[#93c5fd]';
        case 'class':
        case 'key':
            return 'text-[#e2e8f0]';
        case 'number':
        case 'filter-number':
            return 'text-[#fbbf24]';
        case 'filter-field':
            return 'text-[#e9d5ff]';
        case 'comment':
            return 'text-[#6b7280] italic';
        case 'comma':
        case 'semicolon':
        case 'bracket':
        case 'punctuation':
        case 'filter-bracket':
            return 'text-[#71717a]';
        default:
            return 'text-[#e4e4e7]';
    }
};

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(props.code);
        copied.value = true;
        clearTimeout(copyTimer);
        copyTimer = setTimeout(() => {
            copied.value = false;
        }, 1600);
    } catch {
        copied.value = false;
    }
};

onUnmounted(() => clearTimeout(copyTimer));
</script>

<template>
    <div
        class="overflow-hidden bg-[#0b0d11] text-[#e4e4e7]"
        :class="frameless || flat ? '' : 'rounded-2xl border border-white/[0.11] shadow-[0_24px_48px_-24px_rgba(0,0,0,0.28)]'"
    >
        <div
            v-if="!frameless"
            class="flex items-center justify-between gap-2 border-b border-white/[0.08] bg-[#111318] px-4 py-2"
        >
            <div class="flex min-w-0 items-center gap-2">
                <span class="truncate font-mono text-[12px] text-[#e4e4e7]">{{ filename }}</span>
                <span class="shrink-0 rounded border border-white/12 bg-white/[0.04] px-1.5 py-0.5 font-mono text-[10px] uppercase tracking-[0.06em] text-[#a1a1aa]">
                    {{ languageLabel }}
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <slot name="badge" />
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-full border border-white/12 bg-white/[0.04] px-2.5 py-1 font-mono text-[11px] text-[#d4d4d8] transition-colors hover:border-white/25 hover:text-white"
                    :class="copied ? 'border-magic-green/40 text-[#63df9a]' : ''"
                    @click="copyCode"
                >
                    <svg v-if="copied" class="h-3 w-3 text-[#63df9a]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    {{ copied ? 'Copied' : 'Copy' }}
                </button>
            </div>
        </div>

        <div
            tabindex="0"
            role="region"
            :aria-label="`${filename} source code`"
            class="overflow-x-auto scrollbar-thin focus:outline-none focus-visible:ring-1 focus-visible:ring-magic-orange"
            :class="maxHeightClass"
        >
            <div
                class="no-ligatures flex min-w-max font-mono whitespace-pre"
                :class="compact ? 'px-3.5 py-3 text-[12px] leading-[1.6]' : 'px-4 py-4 text-[13px] leading-[1.7] sm:px-5 sm:py-5'"
            >
                <div class="select-none pr-4 text-right text-[#71717a]">
                    <div
                        v-for="(line, index) in parsedLines"
                        :key="`n-${index}`"
                        :class="compact ? 'code-line-compact' : 'code-line'"
                    >
                        {{ index + 1 }}
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div
                        v-for="(line, index) in parsedLines"
                        :key="`${index}-${line.text}-${line.isHighlighted}`"
                        class="rounded-sm px-2 -mx-2"
                        :class="[compact ? 'code-line-compact' : 'code-line', line.isHighlighted ? 'highlight-change' : '']"
                    >
                        <template v-if="line.tokens.length">
                            <span
                                v-for="(token, tokenIndex) in line.tokens"
                                :key="`${index}-${tokenIndex}`"
                                :class="getTokenClass(token)"
                            >{{ token.value }}</span>
                        </template>
                        <span v-else> </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.code-line {
    line-height: 1.7rem;
    transition: background-color 0.3s ease;
}

.code-line-compact {
    line-height: 1.45rem;
    transition: background-color 0.3s ease;
}

.highlight-change {
    background: rgba(255, 83, 16, 0.16);
    box-shadow: inset 2px 0 0 #ff5310;
    animation: highlight-pulse 0.55s ease-out;
}

@keyframes highlight-pulse {
    0% { background: rgba(255, 83, 16, 0.32); }
    100% { background: rgba(255, 83, 16, 0.16); }
}

@media (prefers-reduced-motion: reduce) {
    .code-line { transition: none; }
    .highlight-change { animation: none; }
}
</style>
