<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import NativeSelect from '@/components/crm/NativeSelect.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dealStages } from '@/lib/crm';
import { destroy, store, update } from '@/routes/deals';
import type { Deal, DealStage, Option } from '@/types/crm';

const open = defineModel<boolean>('open', { required: true });

const props = defineProps<{
    deal: Deal | null;
    stage: DealStage;
    contacts: (Option & { company_id: number | null })[];
    companies: Option[];
}>();

const form = useForm({
    title: '',
    value: '' as string | number,
    stage: 'new' as DealStage,
    contact_id: null as number | null,
    company_id: null as number | null,
    expected_close_date: '',
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    form.defaults({
        title: props.deal?.title ?? '',
        value: props.deal ? props.deal.value_cents / 100 : '',
        stage: props.deal?.stage ?? props.stage,
        contact_id: props.deal?.contact_id ?? null,
        company_id: props.deal?.company_id ?? null,
        expected_close_date: props.deal?.expected_close_date ?? '',
    });
    form.reset();
});

// Picking a contact fills in their company, unless one is already chosen.
watch(
    () => form.contact_id,
    (id) => {
        const contact = props.contacts.find((c) => c.id === id);
        if (contact?.company_id && !form.company_id) {
            form.company_id = contact.company_id;
        }
    },
);

function submit(): void {
    form.submit(props.deal ? update(props.deal.id) : store(), {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}

function remove(): void {
    if (!props.deal) {
        return;
    }

    router.visit(destroy(props.deal.id), {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogTitle>{{ deal ? 'Edit deal' : 'New deal' }}</DialogTitle>
            <DialogDescription class="sr-only">
                Title, value, stage and the people involved.
            </DialogDescription>

            <form id="deal-form" class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="deal-title">Title</Label>
                    <Input
                        id="deal-title"
                        v-model="form.title"
                        required
                        placeholder="e.g. Annual license"
                        :aria-invalid="!!form.errors.title"
                    />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="deal-value">Value (€)</Label>
                        <Input
                            id="deal-value"
                            v-model="form.value"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            :aria-invalid="!!form.errors.value"
                        />
                        <InputError :message="form.errors.value" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="deal-stage">Stage</Label>
                        <NativeSelect id="deal-stage" v-model="form.stage">
                            <option
                                v-for="option in dealStages"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </NativeSelect>
                    </div>
                    <div class="grid gap-2">
                        <Label for="deal-contact">Contact</Label>
                        <NativeSelect
                            id="deal-contact"
                            v-model="form.contact_id"
                        >
                            <option :value="null">None</option>
                            <option
                                v-for="contact in contacts"
                                :key="contact.id"
                                :value="contact.id"
                            >
                                {{ contact.name }}
                            </option>
                        </NativeSelect>
                        <InputError :message="form.errors.contact_id" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="deal-company">Company</Label>
                        <NativeSelect
                            id="deal-company"
                            v-model="form.company_id"
                        >
                            <option :value="null">None</option>
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
                        <Label for="deal-close">Expected close</Label>
                        <Input
                            id="deal-close"
                            v-model="form.expected_close_date"
                            type="date"
                        />
                        <InputError
                            :message="form.errors.expected_close_date"
                        />
                    </div>
                </div>
            </form>

            <DialogFooter class="gap-2 sm:justify-between">
                <Button
                    v-if="deal"
                    variant="ghost"
                    class="text-destructive hover:text-destructive"
                    @click="remove"
                >
                    Delete deal
                </Button>
                <span v-else />
                <Button
                    type="submit"
                    form="deal-form"
                    :disabled="form.processing"
                >
                    {{ deal ? 'Save changes' : 'Add deal' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
