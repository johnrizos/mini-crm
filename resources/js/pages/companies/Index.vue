<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, Plus, Search } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import Pagination from '@/components/crm/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { money } from '@/lib/crm';
import { create, index, show } from '@/routes/companies';
import type { Company, Paginated } from '@/types/crm';

const props = defineProps<{
    companies: Paginated<Company>;
    filters: { search: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Companies', href: index() }],
    },
});

const search = ref(props.filters.search);

watch(
    search,
    useDebounceFn(() => {
        router.get(
            index.url(),
            { search: search.value || undefined },
            { preserveState: true, replace: true },
        );
    }, 300),
);
</script>

<template>
    <Head title="Companies" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Companies"
            description="The organizations your contacts work for."
        >
            <template #actions>
                <Button as-child>
                    <Link :href="create()"><Plus /> New company</Link>
                </Button>
            </template>
        </PageHeader>

        <div class="relative max-w-sm">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                type="search"
                class="pl-9"
                placeholder="Search name or domain"
                aria-label="Search companies"
            />
        </div>

        <div
            v-if="companies.data.length === 0"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center"
        >
            <Building2 class="size-8 text-muted-foreground" />
            <p class="font-medium">
                {{
                    search
                        ? 'No companies match your search.'
                        : 'No companies yet.'
                }}
            </p>
        </div>

        <div v-else class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="hidden px-4 py-3 font-medium md:table-cell">
                            Industry
                        </th>
                        <th class="px-4 py-3 text-right font-medium">
                            Contacts
                        </th>
                        <th
                            class="hidden px-4 py-3 text-right font-medium sm:table-cell"
                        >
                            Open pipeline
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="company in companies.data"
                        :key="company.id"
                        class="hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <Link
                                :href="show(company.id)"
                                class="font-medium hover:underline"
                            >
                                {{ company.name }}
                            </Link>
                            <p
                                v-if="company.domain"
                                class="text-xs text-muted-foreground"
                            >
                                {{ company.domain }}
                            </p>
                        </td>
                        <td
                            class="hidden px-4 py-3 text-muted-foreground md:table-cell"
                        >
                            {{ company.industry ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">
                            {{ company.contacts_count }}
                        </td>
                        <td
                            class="hidden px-4 py-3 text-right tabular-nums sm:table-cell"
                        >
                            {{
                                company.open_deals_value_cents
                                    ? money(company.open_deals_value_cents)
                                    : '—'
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :page="companies" />
    </div>
</template>
