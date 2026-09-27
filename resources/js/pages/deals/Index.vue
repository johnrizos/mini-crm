<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CalendarClock, Plus } from '@lucide/vue';
import { ref, watch } from 'vue';
import DealDialog from '@/components/crm/DealDialog.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { dealStages, formatDate, money, stageAccent } from '@/lib/crm';
import { index, move } from '@/routes/deals';
import type { Deal, DealStage, Option } from '@/types/crm';

const props = defineProps<{
    deals: Deal[];
    contacts: (Option & { company_id: number | null })[];
    companies: Option[];
    closedWindowDays: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pipeline', href: index() }],
    },
});

type Columns = Record<DealStage, Deal[]>;

function group(deals: Deal[]): Columns {
    const columns = Object.fromEntries(
        dealStages.map((s) => [s.value, [] as Deal[]]),
    ) as Columns;
    for (const deal of deals) {
        columns[deal.stage].push(deal);
    }

    return columns;
}

// A local copy so a drop shows instantly; the server response replaces it.
const columns = ref<Columns>(group(props.deals));
watch(
    () => props.deals,
    (deals) => (columns.value = group(deals)),
);

const total = (deals: Deal[]) =>
    deals.reduce((sum, deal) => sum + deal.value_cents, 0);

// Drag and drop
const dragging = ref<Deal | null>(null);
const target = ref<{ stage: DealStage; index: number } | null>(null);

function onDragStart(event: DragEvent, deal: Deal): void {
    dragging.value = deal;
    event.dataTransfer?.setData('text/plain', String(deal.id));
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function onCardOver(event: DragEvent, stage: DealStage, index: number): void {
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const after = event.clientY > rect.top + rect.height / 2;
    target.value = { stage, index: after ? index + 1 : index };
}

function onColumnOver(event: DragEvent, stage: DealStage): void {
    // Only the empty space below the cards; cards set their own target.
    if (event.target === event.currentTarget) {
        target.value = { stage, index: columns.value[stage].length };
    }
}

function onDrop(): void {
    const deal = dragging.value;
    const drop = target.value;
    reset();
    if (!deal || !drop) {
        return;
    }

    const from = columns.value[deal.stage];
    const fromIndex = from.findIndex((d) => d.id === deal.id);
    // Positions count the column without the dragged card.
    const position =
        drop.stage === deal.stage && fromIndex < drop.index
            ? drop.index - 1
            : drop.index;

    if (drop.stage === deal.stage && position === fromIndex) {
        return;
    }

    from.splice(fromIndex, 1);
    columns.value[drop.stage].splice(position, 0, {
        ...deal,
        stage: drop.stage,
    });

    router.patch(
        move.url(deal.id),
        { stage: drop.stage, position },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['deals'],
            onError: () => (columns.value = group(props.deals)),
        },
    );
}

function reset(): void {
    dragging.value = null;
    target.value = null;
}

const showIndicator = (stage: DealStage, index: number) =>
    target.value?.stage === stage && target.value.index === index;

// Create / edit dialog
const dialogOpen = ref(false);
const editing = ref<Deal | null>(null);
const newStage = ref<DealStage>('new');

function openNew(stage: DealStage = 'new'): void {
    editing.value = null;
    newStage.value = stage;
    dialogOpen.value = true;
}

function openEdit(deal: Deal): void {
    editing.value = deal;
    dialogOpen.value = true;
}

const isOverdue = (deal: Deal) =>
    !!deal.expected_close_date &&
    !['won', 'lost'].includes(deal.stage) &&
    new Date(deal.expected_close_date) < new Date(new Date().toDateString());
</script>

<template>
    <Head title="Pipeline" />

    <div class="flex min-h-0 flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Pipeline"
            :description="`Drag deals between stages. Won and lost show the last ${closedWindowDays} days.`"
        >
            <template #actions>
                <Button @click="openNew()"><Plus /> New deal</Button>
            </template>
        </PageHeader>

        <div class="-mx-4 overflow-x-auto px-4 pb-2 md:-mx-6 md:px-6">
            <div class="flex min-w-max gap-4">
                <section
                    v-for="stage in dealStages"
                    :key="stage.value"
                    class="flex w-72 shrink-0 flex-col rounded-xl bg-muted/40"
                    :aria-label="`${stage.label} deals`"
                >
                    <header
                        class="flex items-center justify-between gap-2 px-3 pt-3 pb-2"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="size-2 rounded-full"
                                :class="stageAccent[stage.value]"
                            />
                            <h2 class="text-sm font-semibold">
                                {{ stage.label }}
                            </h2>
                            <span class="text-xs text-muted-foreground">
                                {{ columns[stage.value].length }}
                            </span>
                        </div>
                        <span
                            class="text-xs font-medium text-muted-foreground tabular-nums"
                        >
                            {{ money(total(columns[stage.value])) }}
                        </span>
                    </header>

                    <div
                        class="flex min-h-24 flex-1 flex-col gap-2 p-2"
                        @dragover.prevent="onColumnOver($event, stage.value)"
                        @drop.prevent="onDrop"
                    >
                        <template
                            v-for="(deal, i) in columns[stage.value]"
                            :key="deal.id"
                        >
                            <div
                                v-if="showIndicator(stage.value, i)"
                                class="h-0.5 rounded-full bg-primary"
                            />
                            <button
                                type="button"
                                draggable="true"
                                class="w-full cursor-grab rounded-lg border bg-card p-3 text-left text-sm shadow-xs transition hover:border-foreground/20 active:cursor-grabbing"
                                :class="{
                                    'opacity-40': dragging?.id === deal.id,
                                }"
                                @dragstart="onDragStart($event, deal)"
                                @dragover.prevent="
                                    onCardOver($event, stage.value, i)
                                "
                                @dragend="reset"
                                @click="openEdit(deal)"
                            >
                                <p class="font-medium">{{ deal.title }}</p>
                                <p
                                    v-if="deal.company || deal.contact"
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        [
                                            deal.company?.name,
                                            deal.contact?.full_name,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')
                                    }}
                                </p>
                                <div
                                    class="mt-2 flex items-center justify-between gap-2 text-xs"
                                >
                                    <span class="font-semibold tabular-nums">
                                        {{ money(deal.value_cents) }}
                                    </span>
                                    <span
                                        v-if="
                                            deal.expected_close_date &&
                                            !stage.closed
                                        "
                                        class="flex items-center gap-1"
                                        :class="
                                            isOverdue(deal)
                                                ? 'text-destructive'
                                                : 'text-muted-foreground'
                                        "
                                    >
                                        <CalendarClock class="size-3" />
                                        {{
                                            formatDate(deal.expected_close_date)
                                        }}
                                    </span>
                                </div>
                            </button>
                        </template>
                        <div
                            v-if="
                                showIndicator(
                                    stage.value,
                                    columns[stage.value].length,
                                )
                            "
                            class="h-0.5 rounded-full bg-primary"
                        />
                        <button
                            v-if="!stage.closed"
                            type="button"
                            class="mt-auto flex items-center gap-1 rounded-md px-2 py-1.5 text-xs text-muted-foreground hover:bg-muted hover:text-foreground"
                            @click="openNew(stage.value)"
                        >
                            <Plus class="size-3.5" /> Add deal
                        </button>
                    </div>
                </section>
            </div>
        </div>

        <DealDialog
            v-model:open="dialogOpen"
            :deal="editing"
            :stage="newStage"
            :contacts="contacts"
            :companies="companies"
        />
    </div>
</template>
