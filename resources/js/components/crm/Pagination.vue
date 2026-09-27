<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/crm';

defineProps<{ page: Paginated<unknown> }>();
</script>

<template>
    <nav
        v-if="page.meta.total > 0"
        class="flex items-center justify-between gap-4 text-sm text-muted-foreground"
        aria-label="Pagination"
    >
        <p>{{ page.meta.from }}–{{ page.meta.to }} of {{ page.meta.total }}</p>
        <div v-if="page.meta.last_page > 1" class="flex gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="!page.links.prev"
                :as="page.links.prev ? Link : 'button'"
                :href="page.links.prev ?? undefined"
                preserve-scroll
            >
                <ChevronLeft /> Previous
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="!page.links.next"
                :as="page.links.next ? Link : 'button'"
                :href="page.links.next ?? undefined"
                preserve-scroll
            >
                Next <ChevronRight />
            </Button>
        </div>
    </nav>
</template>
