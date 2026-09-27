<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Three pipeline columns, tallest first. -->
        <rect x="3" y="5" width="9" height="30" rx="3" fill="currentColor" />
        <rect
            x="15.5"
            y="5"
            width="9"
            height="22"
            rx="3"
            fill="currentColor"
            opacity="0.75"
        />
        <rect
            x="28"
            y="5"
            width="9"
            height="14"
            rx="3"
            fill="currentColor"
            opacity="0.5"
        />
    </svg>
</template>
