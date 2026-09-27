<script setup lang="ts">
import { Head, Link, router, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    Building2,
    Calendar,
    Mail,
    MessageSquareText,
    Pencil,
    Phone,
    PhoneCall,
    Users,
    X,
} from '@lucide/vue';
import type { Component } from 'vue';
import ConfirmDelete from '@/components/crm/ConfirmDelete.vue';
import NativeSelect from '@/components/crm/NativeSelect.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import StatusBadge from '@/components/crm/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    activityTypes,
    dealStages,
    formatDate,
    label,
    money,
    stageAccent,
    timeAgo,
} from '@/lib/crm';
import { destroy as destroyActivity } from '@/routes/activities';
import { show as showCompany } from '@/routes/companies';
import { destroy, edit, index, show } from '@/routes/contacts';
import { store as storeActivity } from '@/routes/contacts/activities';
import { index as dealsIndex } from '@/routes/deals';
import type { BreadcrumbItem } from '@/types';
import type { Activity, ActivityType, Contact, Deal } from '@/types/crm';

const props = defineProps<{
    contact: Contact;
    deals: Deal[];
    activities: Activity[];
}>();

setLayoutProps<{ breadcrumbs: BreadcrumbItem[] }>({
    breadcrumbs: [
        { title: 'Contacts', href: index() },
        { title: props.contact.full_name, href: show(props.contact.id) },
    ],
});

const icons: Record<ActivityType, Component> = {
    note: MessageSquareText,
    call: PhoneCall,
    email: Mail,
    meeting: Users,
};

const form = useForm({
    type: 'note' as ActivityType,
    body: '',
    deal_id: null as number | null,
});

function log(): void {
    form.submit(storeActivity(props.contact.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('body'),
    });
}

function remove(activity: Activity): void {
    router.visit(destroyActivity(activity.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="contact.full_name" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="contact.full_name">
            <template #title>
                <span class="flex flex-wrap items-center gap-3">
                    {{ contact.full_name }}
                    <StatusBadge :status="contact.status" />
                </span>
            </template>
            <template #actions>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="edit(contact.id)"><Pencil /> Edit</Link>
                </Button>
                <ConfirmDelete
                    :action="destroy(contact.id)"
                    :title="`Delete ${contact.full_name}?`"
                    description="Their activity history is deleted too. Deals stay on the board without a contact."
                />
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <div class="flex flex-col gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl class="grid gap-3 text-sm">
                            <div class="flex items-center gap-3">
                                <Mail
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                                <a
                                    :href="`mailto:${contact.email}`"
                                    class="truncate hover:underline"
                                    >{{ contact.email }}</a
                                >
                            </div>
                            <div
                                v-if="contact.phone"
                                class="flex items-center gap-3"
                            >
                                <Phone
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                                <a
                                    :href="`tel:${contact.phone}`"
                                    class="hover:underline"
                                    >{{ contact.phone }}</a
                                >
                            </div>
                            <div
                                v-if="contact.company"
                                class="flex items-center gap-3"
                            >
                                <Building2
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                                <span class="min-w-0">
                                    <Link
                                        :href="showCompany(contact.company.id)"
                                        class="hover:underline"
                                        >{{ contact.company.name }}</Link
                                    ><span
                                        v-if="contact.job_title"
                                        class="text-muted-foreground"
                                    >
                                        · {{ contact.job_title }}</span
                                    >
                                </span>
                            </div>
                            <div class="flex items-center gap-3">
                                <Calendar
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                                <span class="text-muted-foreground">
                                    Added {{ formatDate(contact.created_at) }}
                                </span>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Deals</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p
                            v-if="deals.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            No deals yet.
                            <Link :href="dealsIndex()" class="underline">
                                Open the pipeline
                            </Link>
                            to add one.
                        </p>
                        <ul v-else class="divide-y text-sm">
                            <li
                                v-for="deal in deals"
                                :key="deal.id"
                                class="flex items-center justify-between gap-3 py-2"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="size-2 shrink-0 rounded-full"
                                        :class="stageAccent[deal.stage]"
                                    />
                                    <span class="truncate">{{
                                        deal.title
                                    }}</span>
                                </span>
                                <span class="shrink-0 text-muted-foreground">
                                    {{ label(dealStages, deal.stage) }} ·
                                    {{ money(deal.value_cents) }}
                                </span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Activity</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-6">
                    <form class="grid gap-3" @submit.prevent="log">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="type">Type</Label>
                                <NativeSelect id="type" v-model="form.type">
                                    <option
                                        v-for="type in activityTypes"
                                        :key="type.value"
                                        :value="type.value"
                                    >
                                        {{ type.label }}
                                    </option>
                                </NativeSelect>
                            </div>
                            <div class="grid gap-2">
                                <Label for="deal_id">Related deal</Label>
                                <NativeSelect
                                    id="deal_id"
                                    v-model="form.deal_id"
                                >
                                    <option :value="null">None</option>
                                    <option
                                        v-for="deal in deals"
                                        :key="deal.id"
                                        :value="deal.id"
                                    >
                                        {{ deal.title }}
                                    </option>
                                </NativeSelect>
                                <InputError :message="form.errors.deal_id" />
                            </div>
                        </div>
                        <div class="grid gap-2">
                            <Label for="body" class="sr-only"
                                >What happened?</Label
                            >
                            <textarea
                                id="body"
                                v-model="form.body"
                                rows="3"
                                required
                                placeholder="What happened? e.g. Called about the renewal, sending a proposal on Friday."
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm dark:bg-input/30"
                            />
                            <InputError :message="form.errors.body" />
                        </div>
                        <div>
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="form.processing || !form.body.trim()"
                            >
                                Log
                                {{
                                    label(
                                        activityTypes,
                                        form.type,
                                    ).toLowerCase()
                                }}
                            </Button>
                        </div>
                    </form>

                    <p
                        v-if="activities.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Nothing logged yet.
                    </p>
                    <ol
                        v-else
                        class="relative flex flex-col gap-5 border-l pl-6"
                    >
                        <li
                            v-for="activity in activities"
                            :key="activity.id"
                            class="group relative"
                        >
                            <span
                                class="absolute top-0 -left-[37px] flex size-6 items-center justify-center rounded-full border bg-background"
                            >
                                <component
                                    :is="icons[activity.type]"
                                    class="size-3.5 text-muted-foreground"
                                />
                            </span>
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            label(activityTypes, activity.type)
                                        }}
                                        ·
                                        <time
                                            :datetime="activity.happened_at"
                                            :title="
                                                formatDate(activity.happened_at)
                                            "
                                            >{{
                                                timeAgo(activity.happened_at)
                                            }}</time
                                        >
                                        <template v-if="activity.deal">
                                            · {{ activity.deal.title }}
                                        </template>
                                    </p>
                                    <p class="text-sm whitespace-pre-line">
                                        {{ activity.body }}
                                    </p>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7 shrink-0 opacity-0 group-hover:opacity-100 focus-visible:opacity-100"
                                    :aria-label="`Delete this ${activity.type}`"
                                    @click="remove(activity)"
                                >
                                    <X />
                                </Button>
                            </div>
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
