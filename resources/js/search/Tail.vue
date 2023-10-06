<script setup>
import TailText from "./TailText.vue";
import TailItem from "./TailItem.vue";
import { ArrowUpIcon, ArrowDownIcon } from "@heroicons/vue/20/solid";
import ShortcutButton from "./ShortcutButton.vue";
</script>

<template>
    <div
        class="absolute rounded-b-lg bottom-0 h-21 w-full px-4 py-2 flex flex-row justify-between items-center border-t border-zinc-300"
    >
        <TailItem>
            <div class="flex flex-row space-x-2">
                <ShortcutButton>
                    <ArrowUpIcon
                        class="h-4"
                    ></ArrowUpIcon>
                </ShortcutButton>
                <ShortcutButton>
                    <ArrowDownIcon
                        class="h-4"
                    ></ArrowDownIcon>
                </ShortcutButton>
            </div>
            <TailText> to navigate </TailText>
        </TailItem>

        <TailItem>
            <ShortcutButton> ESC </ShortcutButton>
            <TailText> to close</TailText>
        </TailItem>

        <TailItem>
            <ShortcutButton> ENTER </ShortcutButton>
            <TailText> to enter</TailText>
        </TailItem>
    </div>
</template>
