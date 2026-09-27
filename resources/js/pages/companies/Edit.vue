<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import CompanyForm from '@/components/crm/CompanyForm.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import { edit, index, show, update } from '@/routes/companies';
import type { BreadcrumbItem } from '@/types';
import type { Company } from '@/types/crm';

const props = defineProps<{ company: Company }>();

setLayoutProps<{ breadcrumbs: BreadcrumbItem[] }>({
    breadcrumbs: [
        { title: 'Companies', href: index() },
        { title: props.company.name, href: show(props.company.id) },
        { title: 'Edit', href: edit(props.company.id) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${company.name}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="`Edit ${company.name}`" />
        <CompanyForm
            :action="update(company.id)"
            :cancel-href="show.url(company.id)"
            :company="company"
        />
    </div>
</template>
