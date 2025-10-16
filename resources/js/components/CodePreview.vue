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

    // Check if it's a variable
    if (text.startsWith('$')) {
        return [{ type: 'variable', value: text }];
    }

    // Check if it's a function
    if (['app', 'get', 'class'].includes(text)) {
        return [{ type: 'function', value: text }];
    }

    // Check if it's a number
    if (/^\d+$/.test(text)) {
        return [{ type: 'number', value: text }];
    }

    // Otherwise it's a method or text
    return [{ type: 'method', value: text }];
};

const getTokenClass = (token) => {
    switch (token.type) {
        case 'variable':
            return 'text-purple-400 font-semibold';
        case 'string':
            return 'text-green-400';
        case 'function':
            return 'text-cyan-400';
        case 'method':
            return 'text-blue-300';
        case 'operator':
            return 'text-gray-400';
        case 'bracket':
            return 'text-yellow-300';
        case 'comma':
        case 'semicolon':
            return 'text-gray-500';
        case 'number':
            return 'text-orange-400';
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
    <div class="relative group">
        <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl blur opacity-20 group-hover:opacity-30 transition duration-300"></div>
        <div class="relative bg-gray-900 dark:bg-gray-950 rounded-xl overflow-hidden border border-gray-800">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                    </svg>
                    <span class="text-xs font-medium text-gray-400">{{ filename }}</span>
                </div>
                <button
                    @click="copyCode"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 rounded transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Copy
                </button>
            </div>
            <div class="p-4 overflow-x-auto">
                <div class="flex font-mono text-xs sm:text-sm leading-relaxed">
                    <!-- Line Numbers -->
                    <div class="select-none pr-4 text-right text-gray-600 border-r border-gray-800 mr-6 min-w-[2rem]">
                        <div
                            v-for="(line, index) in parsedLines"
                            :key="`num-${index}`"
                            class="code-line-num"
                        >
                            {{ index + 1 }}
                        </div>
                    </div>
                    <!-- Code Content -->
                    <div class="flex-1 relative">
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
    padding: 0.25rem 0;
    line-height: 1.5rem;
}

.code-line {
    padding: 0.25rem 0.75rem;
    margin: 0 -0.75rem;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    position: relative;
    line-height: 1.5rem;
    min-height: 1.5rem;
}

@keyframes highlightPulse {
    0% {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.4) 0%, rgba(147, 51, 234, 0.3) 100%);
        transform: translateX(-4px);
        box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
    }
    50% {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.6) 0%, rgba(147, 51, 234, 0.5) 100%);
        transform: translateX(0);
        box-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
    }
    100% {
        background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(147, 51, 234, 0.15) 100%);
        transform: translateX(0);
        box-shadow: 0 0 10px rgba(59, 130, 246, 0.2);
    }
}

@keyframes slideIn {
    0% {
        opacity: 0;
        transform: translateX(-20px);
    }
    100% {
        opacity: 1;
        transform: translateX(0);
    }
}

.highlight-change {
    animation: highlightPulse 1.2s ease-out, slideIn 0.3s ease-out;
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.2) 0%, rgba(147, 51, 234, 0.15) 100%);
    border-left: 3px solid rgb(59, 130, 246);
    padding-left: calc(0.75rem - 3px);
    position: relative;
}

.highlight-change::before {
    content: '+';
    position: absolute;
    left: -1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgb(34, 197, 94);
    font-weight: bold;
    font-size: 0.875rem;
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-50%) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateY(-50%) scale(1);
    }
}
</style>
