<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const props = defineProps<{
    item: Record<string, any> | null;
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Projects', href: '/projects' },
            { title: 'Form', href: '/projects/create' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? t('Edit project') : t('Create project')" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="space-y-3">
            <Button as-child variant="ghost" size="sm" class="-ml-2 w-fit">
                <Link href="/projects"><ArrowLeft class="size-4" /> {{ t('Back') }}</Link>
            </Button>
            <Heading
                :title="isEdit ? t('Edit project') : t('Create project')"
                :description="t('Fill in the details below and save.')"
            />
        </div>

        <Form
            :action="isEdit ? `/projects/${item!.id}` : '/projects'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-3xl space-y-6 rounded-xl border border-sidebar-border/70 bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="code">{{ t('Code') }}</Label>
                <Input id="code" name="code" type="text" required :default-value="item?.code ?? ''" />
                <InputError :message="errors.code" />
            </div>
            <div class="grid gap-2">
                <Label for="name">{{ t('Name') }}</Label>
                <Input id="name" name="name" type="text" required :default-value="item?.name ?? ''" />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="type">{{ t('Type') }}</Label>
                <Input id="type" name="type" type="text" :default-value="item?.type ?? ''" />
                <InputError :message="errors.type" />
            </div>
            <div class="grid gap-2">
                <Label for="location">{{ t('Location') }}</Label>
                <Input id="location" name="location" type="text" :default-value="item?.location ?? ''" />
                <InputError :message="errors.location" />
            </div>
            <div class="grid gap-2">
                <Label for="start_date">{{ t('Start Date') }}</Label>
                <Input id="start_date" name="start_date" type="date" :default-value="item?.start_date ?? ''" />
                <InputError :message="errors.start_date" />
            </div>
            <div class="grid gap-2">
                <Label for="expected_completion_date">{{ t('Expected Completion Date') }}</Label>
                <Input id="expected_completion_date" name="expected_completion_date" type="date" :default-value="item?.expected_completion_date ?? ''" />
                <InputError :message="errors.expected_completion_date" />
            </div>
            <div class="grid gap-2">
                <Label for="estimated_budget">{{ t('Estimated Budget') }}</Label>
                <Input id="estimated_budget" name="estimated_budget" type="number" step="0.01" :default-value="item?.estimated_budget ?? ''" />
                <InputError :message="errors.estimated_budget" />
            </div>
            <div class="grid gap-2">
                <Label for="estimated_revenue">{{ t('Estimated Revenue') }}</Label>
                <Input id="estimated_revenue" name="estimated_revenue" type="number" step="0.01" :default-value="item?.estimated_revenue ?? ''" />
                <InputError :message="errors.estimated_revenue" />
            </div>
            <div class="grid gap-2">
                <Label for="progress_percent">{{ t('Progress Percent') }}</Label>
                <Input id="progress_percent" name="progress_percent" type="number" step="0.01" :default-value="item?.progress_percent ?? ''" />
                <InputError :message="errors.progress_percent" />
            </div>
            <div class="grid gap-2">
                <Label for="status">{{ t('Status') }}</Label>
                <select id="status" name="status" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs" required :value="item?.status ?? ''">
                    <option value="" disabled>{{ t('Select') }}</option>
                    <option value="planning">{{ t('planning') }}</option>
                    <option value="under_construction">{{ t('under construction') }}</option>
                    <option value="ready_for_sale">{{ t('ready for sale') }}</option>
                    <option value="sold">{{ t('sold') }}</option>
                    <option value="completed">{{ t('completed') }}</option>
                    <option value="on_hold">{{ t('on hold') }}</option>
                </select>
                <InputError :message="errors.status" />
            </div>
            <div class="grid gap-2 sm:col-span-2">
                <Label for="description">{{ t('Description') }}</Label>
                <textarea id="description" name="description" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs" :value="item?.description ?? ''"></textarea>
                <InputError :message="errors.description" />
            </div>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Save') }}</Button>
                <Button as-child variant="secondary">
                    <Link href="/projects">{{ t('Cancel') }}</Link>
                </Button>
                <Form v-if="isEdit" :action="`/projects/${item!.id}`" method="delete" class="ml-auto" v-slot="{ processing: deleting }">
                    <Button type="submit" variant="destructive" :disabled="deleting">
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </div>
</template>
