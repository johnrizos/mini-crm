<script setup lang="ts">
import { contactStatuses, label, statusClasses } from '@/lib/crm';
import type { ContactStatus } from '@/types/crm';

defineProps<{ status: ContactStatus }>();
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap"
        :class="statusClasses[status]"
    >
        {{ label(contactStatuses, status) }}
    </span>
</template>
