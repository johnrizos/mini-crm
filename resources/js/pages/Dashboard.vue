<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    activityTypes,
    contactStatuses,
    dealStages,
    formatDate,
    label,
    money,
    stageAccent,
    timeAgo,
} from '@/lib/crm';
import { dashboard } from '@/routes';
import { show as showContact } from '@/routes/contacts';
import { index as dealsIndex } from '@/routes/deals';
import type { Activity, ContactStatus, Deal, DealStage } from '@/types/crm';

const props = defineProps<{
    stats: {
        contacts: number;
        companies: number;
        open_deals: number;
        pipeline_cents: number;
        won_this_month_cents: number;
        win_rate: number | null;
    };
    pipeline: { stage: DealStage; deals: number; value_cents: number }[];
    contactsByStatus: { status: ContactStatus; total: number }[];
    closingSoon: Deal[];
    recentActivities: Activity[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const tiles = computed(() => [
    {
        label: 'Open pipeline',
        value: money(props.stats.pipeline_cents),
        hint: `${props.stats.open_deals} open deals`,
    },
    {
        label: 'Won this month',
        value: money(props.stats.won_this_month_cents),
        hint: new Date().toLocaleDateString('en-GB', { month: 'long' }),
    },
    {
        label: 'Win rate',
        value: props.stats.win_rate === null ? '—' : `${props.stats.win_rate}%`,
        hint: 'Deals closed in the last 90 days',
    },
    {
        label: 'Contacts',
        value: props.stats.contacts.toLocaleString(),
        hint: `at ${props.stats.companies} companies`,
    },
]);

const maxStageValue = computed(() =>
    Math.max(1, ...props.pipeline.map((p) => p.value_cents)),
);
const maxStatus = computed(() =>
    Math.max(1, ...props.contactsByStatus.map((s) => s.total)),
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader title="Dashboard" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card v-for="tile in tiles" :key="tile.label" class="gap-2 py-5">
                <CardContent class="space-y-1">
                    <p class="text-sm text-muted-foreground">
                        {{ tile.label }}
                    </p>
                    <p
                        class="text-2xl font-semibold tracking-tight tabular-nums"
                    >
                        {{ tile.value }}
                    </p>
                    <p class="text-xs text-muted-foreground">{{ tile.hint }}</p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Pipeline by stage</CardTitle>
                    <Link
                        :href="dealsIndex()"
                        class="text-sm text-muted-foreground hover:text-foreground"
                    >
                        Open board →
                    </Link>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-4">
                        <li
                            v-for="row in pipeline"
                            :key="row.stage"
                            class="space-y-1.5"
                        >
                            <div
                                class="flex items-baseline justify-between gap-3 text-sm"
                            >
                                <span class="font-medium">{{
                                    label(dealStages, row.stage)
                                }}</span>
                                <span
                                    class="text-muted-foreground tabular-nums"
                                >
                                    {{ row.deals }} ·
                                    {{ money(row.value_cents) }}
                                </span>
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full"
                                    :class="stageAccent[row.stage]"
                                    :style="{
                                        width: `${(row.value_cents / maxStageValue) * 100}%`,
                                    }"
                                />
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Contacts by status</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-3">
                        <li
                            v-for="row in contactsByStatus"
                            :key="row.status"
                            class="grid grid-cols-[6rem_1fr_2.5rem] items-center gap-3 text-sm"
                        >
                            <span>{{
                                label(contactStatuses, row.status)
                            }}</span>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-foreground/70"
                                    :style="{
                                        width: `${(row.total / maxStatus) * 100}%`,
                                    }"
                                />
                            </div>
                            <span
                                class="text-right text-muted-foreground tabular-nums"
                                >{{ row.total }}</span
                            >
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Closing in the next 2 weeks</CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="closingSoon.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Nothing due soon.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="deal in closingSoon"
                            :key="deal.id"
                            class="flex items-center justify-between gap-3 py-2"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {{ deal.title }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        deal.company?.name ??
                                        deal.contact?.full_name ??
                                        '—'
                                    }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="tabular-nums">
                                    {{ money(deal.value_cents) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(deal.expected_close_date) }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Recent activity</CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="recentActivities.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Calls, emails and notes you log on contacts show up
                        here.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="activity in recentActivities"
                            :key="activity.id"
                            class="flex items-start justify-between gap-4 py-2.5"
                        >
                            <div class="min-w-0">
                                <p class="text-xs text-muted-foreground">
                                    {{ label(activityTypes, activity.type) }}
                                    with
                                    <Link
                                        v-if="activity.contact"
                                        :href="showContact(activity.contact.id)"
                                        class="font-medium text-foreground hover:underline"
                                    >
                                        {{ activity.contact.full_name }}
                                    </Link>
                                </p>
                                <p class="truncate">{{ activity.body }}</p>
                            </div>
                            <time
                                class="shrink-0 text-xs text-muted-foreground"
                                :datetime="activity.happened_at"
                            >
                                {{ timeAgo(activity.happened_at) }}
                            </time>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
