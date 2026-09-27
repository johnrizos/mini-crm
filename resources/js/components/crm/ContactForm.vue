<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import type { UrlMethodPair } from '@inertiajs/core';
import NativeSelect from '@/components/crm/NativeSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { contactStatuses } from '@/lib/crm';
import type { Contact, ContactStatus, Option } from '@/types/crm';

const props = defineProps<{
    action: UrlMethodPair;
    cancelHref: string;
    companies: Option[];
    contact?: Contact;
    companyId?: number | null;
}>();

const form = useForm({
    first_name: props.contact?.first_name ?? '',
    last_name: props.contact?.last_name ?? '',
    email: props.contact?.email ?? '',
    phone: props.contact?.phone ?? '',
    job_title: props.contact?.job_title ?? '',
    status: (props.contact?.status ?? 'lead') as ContactStatus,
    company_id: props.contact?.company_id ?? props.companyId ?? null,
});
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="form.submit(action)">
        <div class="grid gap-6 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="first_name">First name</Label>
                <Input
                    id="first_name"
                    v-model="form.first_name"
                    required
                    autocomplete="given-name"
                    :aria-invalid="!!form.errors.first_name"
                />
                <InputError :message="form.errors.first_name" />
            </div>
            <div class="grid gap-2">
                <Label for="last_name">Last name</Label>
                <Input
                    id="last_name"
                    v-model="form.last_name"
                    required
                    autocomplete="family-name"
                    :aria-invalid="!!form.errors.last_name"
                />
                <InputError :message="form.errors.last_name" />
            </div>
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    :aria-invalid="!!form.errors.email"
                />
                <InputError :message="form.errors.email" />
            </div>
            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    :aria-invalid="!!form.errors.phone"
                />
                <InputError :message="form.errors.phone" />
            </div>
            <div class="grid gap-2">
                <Label for="job_title">Job title</Label>
                <Input
                    id="job_title"
                    v-model="form.job_title"
                    :aria-invalid="!!form.errors.job_title"
                />
                <InputError :message="form.errors.job_title" />
            </div>
            <div class="grid gap-2">
                <Label for="company_id">Company</Label>
                <NativeSelect
                    id="company_id"
                    v-model="form.company_id"
                    :aria-invalid="!!form.errors.company_id"
                >
                    <option :value="null">No company</option>
                    <option
                        v-for="company in companies"
                        :key="company.id"
                        :value="company.id"
                    >
                        {{ company.name }}
                    </option>
                </NativeSelect>
                <InputError :message="form.errors.company_id" />
            </div>
            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <NativeSelect id="status" v-model="form.status">
                    <option
                        v-for="status in contactStatuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </NativeSelect>
                <InputError :message="form.errors.status" />
            </div>
        </div>

        <div class="flex gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ contact ? 'Save changes' : 'Add contact' }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
