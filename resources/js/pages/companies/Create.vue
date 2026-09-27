<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CompanyForm from '@/components/crm/CompanyForm.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import { create, index, store } from '@/routes/companies';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Companies', href: index() },
            { title: 'New company', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New company" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader title="New company" />
        <CompanyForm :action="store()" :cancel-href="index.url()" />
    </div>
</template>
