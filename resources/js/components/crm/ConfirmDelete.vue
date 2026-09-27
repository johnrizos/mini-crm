<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { UrlMethodPair } from '@inertiajs/core';
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const props = defineProps<{
    action: UrlMethodPair;
    title: string;
    description: string;
    label?: string;
}>();

const processing = ref(false);

function confirm(): void {
    router.visit(props.action, {
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
    });
}
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <slot>
                <Button variant="outline" size="sm">
                    <Trash2 /> {{ label ?? 'Delete' }}
                </Button>
            </slot>
        </DialogTrigger>
        <DialogContent>
            <DialogTitle>{{ title }}</DialogTitle>
            <DialogDescription>{{ description }}</DialogDescription>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="confirm"
                >
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
