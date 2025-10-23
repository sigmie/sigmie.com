<script setup>
import { computed } from 'vue';

const props = defineProps({
    code: {
        type: String,
        required: true
    },
    filename: {
        type: String,
        default: 'code.php'
    },
    highlightLines: {
        type: Array,
        default: () => []
    },
    fadeRight: {
        type: Boolean,
        default: true
    },
    fadeBottom: {
        type: Boolean,
        default: true
    },
    fadeLeft: {
        type: Boolean,
        default: false
    },
    fadeLength: {
        type: Number,
        default: 64 // pixels - for backwards compatibility
    },
    fadeWidthRight: {
        type: Number,
        default: null // uses fadeLength if not specified
    },
    fadeWidthLeft: {
        type: Number,
        default: null // uses fadeLength if not specified
    },
    fadeHeightBottom: {
        type: Number,
        default: null // uses fadeLength if not specified
    }
});

const rightFadeWidth = computed(() => props.fadeWidthRight ?? props.fadeLength);
const leftFadeWidth = computed(() => props.fadeWidthLeft ?? props.fadeLength);
const bottomFadeHeight = computed(() => props.fadeHeightBottom ?? props.fadeLength);

const maskImage = computed(() => {
    const gradients = [];

    // Create mask gradients - opaque in content, transparent at edges
    if (props.fadeRight) {
        gradients.push(`linear-gradient(to right, black, black calc(100% - ${rightFadeWidth.value}px), transparent 100%)`);
    } else {
        gradients.push(`linear-gradient(to right, black, black)`);
    }

    if (props.fadeBottom) {
        gradients.push(`linear-gradient(to bottom, black, black calc(100% - ${bottomFadeHeight.value}px), transparent 100%)`);
    } else {
        gradients.push(`linear-gradient(to bottom, black, black)`);
    }

    if (props.fadeLeft) {
        gradients.push(`linear-gradient(to left, black, black calc(100% - ${leftFadeWidth.value}px), transparent 100%)`);
    } else {
        gradients.push(`linear-gradient(to left, black, black)`);
    }

    return gradients.join(', ');
});

const parsedLines = computed(() => {
    const lines = props.code.split('\n');
    return lines.map((line, index) => ({
        tokens: parseTokens(line),
        isHighlighted: props.highlightLines.includes(index + 1)
    }));
});

const parseTokens = (text) => {
    if (!text) return [];

    // Check if line is a comment
    if (text.trim().startsWith('//')) {
        return [{ type: 'comment', value: text }];
    }

    const tokens = [];
    let current = '';
    let inString = false;
    let stringChar = '';

    for (let i = 0; i < text.length; i++) {
        const char = text[i];
        const next = text[i + 1] || '';

        // Handle strings
        if ((char === "'" || char === '"') && !inString) {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            inString = true;
            stringChar = char;
            current = char;
        } else if (char === stringChar && inString) {
            current += char;
            tokens.push({ type: 'string', value: current });
            current = '';
            inString = false;
        } else if (inString) {
            current += char;
        }
        // Handle method calls ->
        else if (char === '-' && next === '>') {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            tokens.push({ type: 'operator', value: '->' });
            i++; // skip next char
        }
        // Handle double colon
        else if (char === ':' && next === ':') {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            tokens.push({ type: 'operator', value: '::' });
            i++;
        }
        // Handle special chars
        else if (['(', ')', '[', ']', ',', ';', '='].includes(char)) {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            tokens.push({ type: char === ',' ? 'comma' : char === ';' ? 'semicolon' : 'bracket', value: char });
        }
        // Handle whitespace
        else if (char === ' ') {
            if (current) {
                tokens.push(...tokenizeNonString(current));
                current = '';
            }
            tokens.push({ type: 'space', value: ' ' });
        } else {
            current += char;
        }
    }

    if (current) {
        if (inString) {
            tokens.push({ type: 'string', value: current });
        } else {
            tokens.push(...tokenizeNonString(current));
        }
    }

    return tokens;
};

const tokenizeNonString = (text) => {
    if (!text) return [];

    // Check if it's a keyword
    const keywords = ['function', 'var', 'for', 'if', 'else', 'return', 'const', 'let', 'class', 'new'];
    if (keywords.includes(text)) {
        return [{ type: 'keyword', value: text }];
    }

    // Check if it's a variable
    if (text.startsWith('$')) {
        return [{ type: 'variable', value: text }];
    }

    // Check if it's a function/method name (camelCase)
    if (/^[a-z][a-zA-Z0-9]*$/.test(text) && text.length > 1) {
        return [{ type: 'function', value: text }];
    }

    // Check if it's a number
    if (/^\d+$/.test(text)) {
        return [{ type: 'number', value: text }];
    }

    // Otherwise it's text
    return [{ type: 'text', value: text }];
};

const getTokenClass = (token) => {
    switch (token.type) {
        case 'keyword':
            return 'text-fuchsia-400';
        case 'variable':
            return 'text-fuchsia-400';
        case 'string':
            return 'text-cyan-300';
        case 'function':
            return 'text-orange-400';
        case 'method':
            return 'text-gray-300';
        case 'operator':
            return 'text-gray-400';
        case 'bracket':
            return 'text-gray-400';
        case 'comma':
        case 'semicolon':
            return 'text-gray-500';
        case 'number':
            return 'text-orange-400';
        case 'comment':
            return 'text-gray-500';
        case 'text':
            return 'text-gray-300';
        default:
            return 'text-gray-300';
    }
};

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(props.code);
    } catch (err) {
        console.error('Failed to copy code:', err);
    }
};
</script>

<template>
    <div class="relative w-full">
        <div class="relative rounded-t-lg overflow-hidden border border-gray-800 border-b-0 bg-black" :style="{ maskImage: maskImage, WebkitMaskImage: maskImage, maskComposite: 'intersect', WebkitMaskComposite: 'source-in' }">
            <div class="px-3 sm:px-4 pt-6 sm:pt-8 pb-8 sm:pb-12 overflow-x-auto overflow-y-hidden" style="white-space: pre-wrap;">
                <div class="flex font-mono text-sm leading-relaxed min-h-[auto]">
                    <!-- Line Numbers -->
                    <div class="select-none pr-6 text-right text-gray-600 mr-4 min-w-[3rem]">
                        <div
                            v-for="(line, index) in parsedLines"
                            :key="`num-${index}`"
                            class="code-line-num"
                        >
                            {{ index + 1 }}
                        </div>
                    </div>
                    <!-- Code Content -->
                    <div class="flex-1">
                        <div
                            v-for="(line, index) in parsedLines"
                            :key="`line-${index}`"
                            class="code-line"
                            :class="{
                                'highlight-change': line.isHighlighted,
                                'opacity-50': line.tokens.length === 0
                            }"
                        >
                            <template v-if="line.tokens.length > 0">
                                <span
                                    v-for="(token, tIndex) in line.tokens"
                                    :key="`${index}-${tIndex}`"
                                    :class="getTokenClass(token)"
                                >{{ token.value }}</span>
                            </template>
                            <span v-else>&nbsp;</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content fade overlays - now handled by background gradient -->
        <!-- Keeping this div for potential future use, but overlays removed -->
    </div>
</template>

<style scoped>
.code-line-num {
    line-height: 1.75rem;
}

.code-line {
    line-height: 1.75rem;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.highlight-change {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(147, 51, 234, 0.12) 100%);
    animation: highlight-pulse 0.5s ease-in-out;
}

@keyframes highlight-pulse {
    0% {
        background: transparent;
        transform: translateX(0);
    }
    50% {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.25) 0%, rgba(147, 51, 234, 0.2) 100%);
        transform: translateX(2px);
    }
    100% {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(147, 51, 234, 0.12) 100%);
        transform: translateX(0);
    }
}
</style>
