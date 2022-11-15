<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3';

defineProps({
html: String,
});
</script>

<template>
    <div v-html="html"></div>
</template>
