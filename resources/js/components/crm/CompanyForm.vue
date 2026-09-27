<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import type { UrlMethodPair } from '@inertiajs/core';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Company } from '@/types/crm';

const props = defineProps<{
    action: UrlMethodPair;
    cancelHref: string;
    company?: Company;
}>();

const form = useForm({
    name: props.company?.name ?? '',
    domain: props.company?.domain ?? '',
    industry: props.company?.industry ?? '',
});
</script>

<template>
    <form class="max-w-xl space-y-6" @submit.prevent="form.submit(action)">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                autocomplete="organization"
                :aria-invalid="!!form.errors.name"
            />
            <InputError :message="form.errors.name" />
        </div>
        <div class="grid gap-2">
            <Label for="domain">Website domain</Label>
            <Input
                id="domain"
                v-model="form.domain"
                placeholder="example.com"
                :aria-invalid="!!form.errors.domain"
            />
            <InputError :message="form.errors.domain" />
        </div>
        <div class="grid gap-2">
            <Label for="industry">Industry</Label>
            <Input
                id="industry"
                v-model="form.industry"
                :aria-invalid="!!form.errors.industry"
            />
            <InputError :message="form.errors.industry" />
        </div>

        <div class="flex gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ company ? 'Save changes' : 'Add company' }}
            </Button>
            <Button variant="ghost" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
