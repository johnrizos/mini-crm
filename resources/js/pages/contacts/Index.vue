<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, Upload, Users } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { reactive, watch } from 'vue';
import NativeSelect from '@/components/crm/NativeSelect.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import Pagination from '@/components/crm/Pagination.vue';
import StatusBadge from '@/components/crm/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { contactStatuses, formatDate } from '@/lib/crm';
import { create, index, show } from '@/routes/contacts';
import { create as importCreate } from '@/routes/contacts/import';
import type { Contact, Option, Paginated } from '@/types/crm';

const props = defineProps<{
    contacts: Paginated<Contact>;
    filters: {
        search: string;
        status: string | null;
        company: number | null;
        sort: 'name' | 'recent';
    };
    companies: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Contacts', href: index() }],
    },
});

const filters = reactive({ ...props.filters });

function apply(): void {
    router.get(
        index.url(),
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            company: filters.company || undefined,
            sort: filters.sort === 'recent' ? 'recent' : undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const applyDebounced = useDebounceFn(apply, 300);

watch(() => filters.search, applyDebounced);
watch(() => [filters.status, filters.company, filters.sort], apply);

const hasFilters = () =>
    !!(filters.search || filters.status || filters.company);

function clear(): void {
    Object.assign(filters, {
        search: '',
        status: null,
        company: null,
        sort: 'name',
    });
}
</script>

<template>
    <Head title="Contacts" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Contacts"
            description="Everyone you sell to, with their company and status."
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="importCreate()"><Upload /> Import CSV</Link>
                </Button>
                <Button as-child>
                    <Link :href="create()"><Plus /> New contact</Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="relative sm:col-span-2 lg:col-span-1">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="filters.search"
                    type="search"
                    class="pl-9"
                    placeholder="Search name, email, company"
                    aria-label="Search contacts"
                />
            </div>
            <NativeSelect v-model="filters.status" aria-label="Status">
                <option :value="null">All statuses</option>
                <option
                    v-for="status in contactStatuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </NativeSelect>
            <NativeSelect v-model="filters.company" aria-label="Company">
                <option :value="null">All companies</option>
                <option
                    v-for="company in companies"
                    :key="company.id"
                    :value="company.id"
                >
                    {{ company.name }}
                </option>
            </NativeSelect>
            <NativeSelect v-model="filters.sort" aria-label="Sort">
                <option value="name">Sort by name</option>
                <option value="recent">Newest first</option>
            </NativeSelect>
        </div>

        <div
            v-if="contacts.data.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center"
        >
            <Users class="size-8 text-muted-foreground" />
            <template v-if="hasFilters()">
                <p class="font-medium">No contacts match these filters.</p>
                <Button variant="outline" size="sm" @click="clear">
                    Clear filters
                </Button>
            </template>
            <template v-else>
                <p class="font-medium">No contacts yet.</p>
                <p class="text-sm text-muted-foreground">
                    Add your first one or import a CSV from your old tool.
                </p>
            </template>
        </div>

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="hidden px-4 py-3 font-medium md:table-cell">
                            Company
                        </th>
                        <th class="hidden px-4 py-3 font-medium lg:table-cell">
                            Email
                        </th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="hidden px-4 py-3 font-medium xl:table-cell">
                            Added
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="contact in contacts.data"
                        :key="contact.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="show(contact.id)"
                                class="font-medium hover:underline"
                            >
                                {{ contact.full_name }}
                            </Link>
                            <p
                                v-if="contact.job_title"
                                class="text-xs text-muted-foreground"
                            >
                                {{ contact.job_title }}
                            </p>
                        </td>
                        <td class="hidden px-4 py-3 md:table-cell">
                            {{ contact.company?.name ?? '—' }}
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground lg:table-cell"
                        >
                            {{ contact.email }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="contact.status" />
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground xl:table-cell"
                        >
                            {{ formatDate(contact.created_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :page="contacts" />
    </div>
</template>
