<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ContactForm from '@/components/crm/ContactForm.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import { create, index, store } from '@/routes/contacts';
import type { Option } from '@/types/crm';

defineProps<{
    companies: Option[];
    companyId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Contacts', href: index() },
            { title: 'New contact', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New contact" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader title="New contact" />
        <ContactForm
            :action="store()"
            :cancel-href="index.url()"
            :companies="companies"
            :company-id="companyId"
        />
    </div>
</template>
