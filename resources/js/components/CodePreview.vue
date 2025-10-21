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
    }
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
    <div class="relative">
        <div class="relative bg-black rounded-t-lg overflow-hidden border border-gray-800 border-b-0">
            <!-- Fade overlays -->
            <div v-if="fadeRight" class="absolute right-0 top-0 bottom-0 w-32 bg-gradient-to-l from-black to-black/0 pointer-events-none z-10"></div>
            <div v-if="fadeBottom" class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-black via-black/50 to-black/0 pointer-events-none z-10"></div>
            <div v-if="fadeLeft" class="absolute left-0 top-0 bottom-0 w-32 bg-gradient-to-r from-black to-black/0 pointer-events-none z-10"></div>

            <div class="p-6 overflow-x-auto overflow-y-hidden">
                <div class="flex font-mono text-sm leading-relaxed min-h-[300px]">
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
    </div>
</template>

<style scoped>
.code-line-num {
    line-height: 1.75rem;
}

.code-line {
    line-height: 1.75rem;
}

.highlight-change {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 51, 234, 0.08) 100%);
}
</style>
