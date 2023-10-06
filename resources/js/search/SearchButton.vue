<script setup>
import ShortcutButton from "./ShortcutButton.vue";
import CommandIcon from "./CommandIcon.vue";
import { ref, defineEmits, onMounted, onUnmounted } from "vue";

const isWindows = ref(navigator.userAgent.indexOf("Win") !== -1);
const emit = defineEmits(["openSearch"]);
function handleKeyDown(e) {
    // On CMD + K or CTRL + K
    if ((e.keyCode === 75 && e.metaKey) || (e.keyCode === 75 && e.ctrlKey)) {
        e.preventDefault();
        emit("openSearch");
    }
}

function addKeydownListener() {
    document.addEventListener("keydown", handleKeyDown);
}

function removeKeydownListener() {
    document.removeEventListener("keydown", handleKeyDown);
}

onMounted(() => {
    addKeydownListener();
});

onUnmounted(() => {
    removeKeydownListener();
});
</script>

<template>
    <button
        @click="() => emit('openSearch')"
        class="border-transparent focus:border-transparent focus:ring-0 max-w-md mx-auto flex flex-row justify-between text-zinc-400 w-full border border-zinc-200 backdrop-filter backdrop-blur bg-zinc-100/70 rounded-lg items-center px-3 py-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-400"
    >
        <div class="text-zinc-600 text-sm">Search documentation...</div>
        <ShortcutButton>
            <CommandIcon v-if="!isWindows"></CommandIcon>
            <span v-else> Ctrl </span>
            <span> K </span>
        </ShortcutButton>
    </button>
</template>
