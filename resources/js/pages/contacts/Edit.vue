<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import ContactForm from '@/components/crm/ContactForm.vue';
import PageHeader from '@/components/crm/PageHeader.vue';
import { edit, index, show, update } from '@/routes/contacts';
import type { BreadcrumbItem } from '@/types';
import type { Contact, Option } from '@/types/crm';

const props = defineProps<{
    contact: Contact;
    companies: Option[];
}>();

setLayoutProps<{ breadcrumbs: BreadcrumbItem[] }>({
    breadcrumbs: [
        { title: 'Contacts', href: index() },
        { title: props.contact.full_name, href: show(props.contact.id) },
        { title: 'Edit', href: edit(props.contact.id) },
    ],
});
</script>

<template>
    <Head :title="`Edit ${contact.full_name}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="`Edit ${contact.full_name}`" />
        <ContactForm
            :action="update(contact.id)"
            :cancel-href="show.url(contact.id)"
            :companies="companies"
            :contact="contact"
        />
    </div>
</template>
