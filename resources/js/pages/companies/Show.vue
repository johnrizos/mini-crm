<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { Globe, Pencil, UserPlus } from '@lucide/vue';
import ConfirmDelete from '@/components/crm/ConfirmDelete.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import StatusBadge from '@/components/crm/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dealStages, label, money, stageAccent } from '@/lib/crm';
import { destroy, edit, index, show } from '@/routes/companies';
import {
    create as createContact,
    show as showContact,
} from '@/routes/contacts';
import type { BreadcrumbItem } from '@/types';
import type { Company, Contact, Deal } from '@/types/crm';

const props = defineProps<{
    company: Company;
    contacts: Contact[];
    deals: Deal[];
}>();

setLayoutProps<{ breadcrumbs: BreadcrumbItem[] }>({
    breadcrumbs: [
        { title: 'Companies', href: index() },
        { title: props.company.name, href: show(props.company.id) },
    ],
});
</script>

<template>
    <Head :title="company.name" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="company.name"
            :description="company.industry ?? undefined"
        >
            <template #actions>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="edit(company.id)"><Pencil /> Edit</Link>
                </Button>
                <ConfirmDelete
                    :action="destroy(company.id)"
                    :title="`Delete ${company.name}?`"
                    description="Its contacts and deals are kept, just no longer linked to a company."
                />
            </template>
        </PageHeader>

        <a
            v-if="company.domain"
            :href="`https://${company.domain}`"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex w-fit items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
        >
            <Globe class="size-4" /> {{ company.domain }}
        </a>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle>Contacts ({{ contacts.length }})</CardTitle>
                    <Button variant="ghost" size="sm" as-child>
                        <Link
                            :href="
                                createContact({
                                    query: { company: company.id },
                                })
                            "
                        >
                            <UserPlus /> Add
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="contacts.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No contacts at this company yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="contact in contacts"
                            :key="contact.id"
                            class="flex items-center justify-between gap-3 py-2"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="showContact(contact.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ contact.full_name }}
                                </Link>
                                <p
                                    v-if="contact.job_title"
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ contact.job_title }}
                                </p>
                            </div>
                            <StatusBadge :status="contact.status" />
                        </li>
                    </ul>
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
                        No deals with this company yet.
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
                                <span class="truncate">{{ deal.title }}</span>
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
    </div>
</template>
