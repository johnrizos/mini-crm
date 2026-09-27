<script setup lang="ts">
import { Head, useForm, usePoll } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, FileUp, LoaderCircle } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { timeAgo } from '@/lib/crm';
import { index } from '@/routes/contacts';
import { create, store } from '@/routes/contacts/import';

type ImportRow = {
    id: number;
    filename: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    total_rows: number;
    imported: number;
    skipped: number;
    errors: { row: number; messages: string[] }[];
    created_at: string | null;
};

const props = defineProps<{
    imports: ImportRow[];
    maxRows: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Contacts', href: index() },
            { title: 'Import', href: create() },
        ],
    },
});

const form = useForm({ file: null as File | null });
const input = ref<HTMLInputElement>();
const dragging = ref(false);

function pick(file: File | undefined): void {
    form.file = file ?? null;
    form.clearErrors();
}

function upload(): void {
    form.submit(store(), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            if (input.value) {
                input.value.value = '';
            }
        },
    });
}

// Refresh while a job is still running. The queue usually finishes in a second or two.
const running = computed(() =>
    props.imports.some(
        (i) => i.status === 'pending' || i.status === 'processing',
    ),
);
const { start, stop } = usePoll(
    1500,
    { only: ['imports'] },
    { autoStart: false },
);
watch(running, (isRunning) => (isRunning ? start() : stop()), {
    immediate: true,
});
</script>

<template>
    <Head title="Import contacts" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Import contacts"
            description="Bring your contacts over from a spreadsheet or another CRM."
        />

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Upload a CSV</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-4 text-sm">
                    <form class="flex flex-col gap-4" @submit.prevent="upload">
                        <label
                            class="flex cursor-pointer flex-col items-center gap-2 rounded-lg border border-dashed p-8 text-center transition-colors"
                            :class="
                                dragging
                                    ? 'border-primary bg-primary/5'
                                    : 'hover:bg-muted/40'
                            "
                            @dragover.prevent="dragging = true"
                            @dragleave="dragging = false"
                            @drop.prevent="
                                dragging = false;
                                pick($event.dataTransfer?.files[0]);
                            "
                        >
                            <FileUp class="size-6 text-muted-foreground" />
                            <span v-if="form.file" class="font-medium">{{
                                form.file.name
                            }}</span>
                            <span v-else>
                                <span class="font-medium">Choose a file</span>
                                or drop it here
                            </span>
                            <span class="text-xs text-muted-foreground">
                                .csv, up to 2 MB and
                                {{ maxRows.toLocaleString() }} rows
                            </span>
                            <input
                                ref="input"
                                type="file"
                                accept=".csv,text/csv"
                                class="sr-only"
                                @change="
                                    pick(
                                        ($event.target as HTMLInputElement)
                                            .files?.[0],
                                    )
                                "
                            />
                        </label>
                        <InputError :message="form.errors.file" />
                        <div>
                            <Button
                                type="submit"
                                :disabled="!form.file || form.processing"
                            >
                                Import
                            </Button>
                        </div>
                    </form>

                    <div class="space-y-2 text-muted-foreground">
                        <p>
                            Required columns:
                            <code class="text-foreground">first_name</code>,
                            <code class="text-foreground">last_name</code>,
                            <code class="text-foreground">email</code>.
                            Optional:
                            <code class="text-foreground">phone</code>,
                            <code class="text-foreground">job_title</code>,
                            <code class="text-foreground">company</code>,
                            <code class="text-foreground">status</code>.
                        </p>
                        <p>
                            Common spellings like "First Name", "Surname" or
                            "E-mail" work, and so do semicolon-separated files
                            from Excel. Rows with an email you already have are
                            skipped. Companies are matched by name or created.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Recent imports</CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="imports.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Nothing imported yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li v-for="item in imports" :key="item.id" class="py-3">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <LoaderCircle
                                        v-if="
                                            item.status === 'pending' ||
                                            item.status === 'processing'
                                        "
                                        class="size-4 shrink-0 animate-spin text-muted-foreground"
                                    />
                                    <CircleCheck
                                        v-else-if="item.status === 'completed'"
                                        class="size-4 shrink-0 text-emerald-600"
                                    />
                                    <CircleAlert
                                        v-else
                                        class="size-4 shrink-0 text-destructive"
                                    />
                                    <span class="truncate font-medium">{{
                                        item.filename
                                    }}</span>
                                </span>
                                <span
                                    v-if="item.created_at"
                                    class="shrink-0 text-xs text-muted-foreground"
                                >
                                    {{ timeAgo(item.created_at) }}
                                </span>
                            </div>
                            <p
                                v-if="item.status === 'completed'"
                                class="mt-1 pl-6 text-muted-foreground"
                            >
                                {{ item.imported }} imported,
                                {{ item.skipped }} skipped of
                                {{ item.total_rows }} rows
                            </p>
                            <p
                                v-else-if="item.status !== 'failed'"
                                class="mt-1 pl-6 text-muted-foreground"
                            >
                                Importing…
                            </p>
                            <details
                                v-if="item.errors.length"
                                class="mt-2 pl-6"
                            >
                                <summary
                                    class="cursor-pointer text-muted-foreground"
                                >
                                    {{
                                        item.status === 'failed'
                                            ? 'Why it failed'
                                            : `${item.errors.length} rows need attention`
                                    }}
                                </summary>
                                <ul class="mt-2 space-y-1 text-xs">
                                    <li
                                        v-for="error in item.errors"
                                        :key="error.row"
                                    >
                                        <span
                                            v-if="error.row"
                                            class="font-medium"
                                            >Row {{ error.row }}:</span
                                        >
                                        {{ error.messages.join(' ') }}
                                    </li>
                                </ul>
                            </details>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
