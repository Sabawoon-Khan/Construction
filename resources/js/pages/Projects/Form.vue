<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { V2FormPage } from '@/components/v2';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';
import { translateStatus } from '@/lib/translateStatus';

const props = defineProps<{
    item: Record<string, any> | null;
    suggestedCode?: string;
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

const statuses = [
    'planning',
    'under_construction',
    'ready_for_sale',
    'sold',
    'completed',
    'on_hold',
] as const;

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

    <V2FormPage
        :title="isEdit ? t('Edit project') : t('Create project')"
        :eyebrow="t('Projects')"
        :back-href="isEdit ? '/projects' : undefined"
    >
        <Form
            :action="isEdit ? `/projects/${item!.id}` : '/projects'"
            :method="isEdit ? 'put' : 'post'"
            class="mis-form-card v2-project-form"
            v-slot="{ errors, processing }"
        >
            <div class="v2-form-split">
                <div class="v2-form-split-main">
                    <div class="v2-form-group">
                        <div class="mis-form-grid">
                            <div class="v2-field mis-form-span">
                                <Label for="name">{{ t('Name') }}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    autocomplete="off"
                                    :placeholder="t('Project name')"
                                    :default-value="item?.name ?? ''"
                                />
                                <InputError :message="errors.name" />
                            </div>
                            <div class="v2-field">
                                <Label for="code">{{ t('Code') }}</Label>
                                <Input
                                    id="code"
                                    name="code"
                                    type="text"
                                    required
                                    autocomplete="off"
                                    :placeholder="t('Project code')"
                                    :default-value="item?.code ?? suggestedCode ?? ''"
                                />
                                <InputError :message="errors.code" />
                            </div>
                            <div class="v2-field">
                                <Label for="type">{{ t('Type') }}</Label>
                                <Input
                                    id="type"
                                    name="type"
                                    type="text"
                                    autocomplete="off"
                                    :placeholder="t('Optional')"
                                    :default-value="item?.type ?? ''"
                                />
                                <InputError :message="errors.type" />
                            </div>
                            <div class="v2-field">
                                <Label for="location">{{ t('Location') }}</Label>
                                <Input
                                    id="location"
                                    name="location"
                                    type="text"
                                    autocomplete="off"
                                    :placeholder="t('Optional')"
                                    :default-value="item?.location ?? ''"
                                />
                                <InputError :message="errors.location" />
                            </div>
                            <div class="v2-field">
                                <Label for="start_date">{{ t('Start Date') }}</Label>
                                <Input
                                    id="start_date"
                                    name="start_date"
                                    type="date"
                                    :default-value="item?.start_date ?? ''"
                                />
                                <InputError :message="errors.start_date" />
                            </div>
                            <div class="v2-field">
                                <Label for="expected_completion_date">
                                    {{ t('Expected Completion Date') }}
                                </Label>
                                <Input
                                    id="expected_completion_date"
                                    name="expected_completion_date"
                                    type="date"
                                    :default-value="item?.expected_completion_date ?? ''"
                                />
                                <InputError :message="errors.expected_completion_date" />
                            </div>
                        </div>
                    </div>

                    <div class="v2-form-group">
                        <h2 class="v2-form-group-title">{{ t('Description') }}</h2>
                        <div class="v2-field">
                            <Label for="description" class="sr-only">
                                {{ t('Description') }}
                            </Label>
                            <textarea
                                id="description"
                                name="description"
                                rows="7"
                                class="mis-form-textarea"
                                :placeholder="t('Optional')"
                                :value="item?.description ?? ''"
                            />
                            <InputError :message="errors.description" />
                        </div>
                    </div>
                </div>

                <aside class="v2-form-split-aside">
                    <div class="v2-form-split-panel">
                        <div class="v2-form-split-block">
                            <h2 class="v2-form-split-block-title">{{ t('Status') }}</h2>
                            <div class="v2-field">
                                <Label for="status">{{ t('Project status') }}</Label>
                                <select
                                    id="status"
                                    name="status"
                                    class="mis-form-select"
                                    required
                                    :value="item?.status ?? 'planning'"
                                >
                                    <option
                                        v-for="status in statuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{ translateStatus(status, t) }}
                                    </option>
                                </select>
                                <InputError :message="errors.status" />
                            </div>
                        </div>

                        <div class="v2-form-split-block">
                            <h2 class="v2-form-split-block-title">{{ t('Budget') }}</h2>
                            <div class="mis-form-grid">
                                <div class="v2-field">
                                    <Label for="estimated_budget">
                                        {{ t('Estimated Budget') }}
                                    </Label>
                                    <Input
                                        id="estimated_budget"
                                        name="estimated_budget"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        inputmode="decimal"
                                        :placeholder="'0.00'"
                                        :default-value="item?.estimated_budget ?? ''"
                                    />
                                    <InputError :message="errors.estimated_budget" />
                                </div>
                                <div class="v2-field">
                                    <Label for="estimated_revenue">
                                        {{ t('Estimated Revenue') }}
                                    </Label>
                                    <Input
                                        id="estimated_revenue"
                                        name="estimated_revenue"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        inputmode="decimal"
                                        :placeholder="'0.00'"
                                        :default-value="item?.estimated_revenue ?? ''"
                                    />
                                    <InputError :message="errors.estimated_revenue" />
                                </div>
                                <div class="v2-field">
                                    <Label for="progress_percent">
                                        {{ t('Progress') }}
                                    </Label>
                                    <Input
                                        id="progress_percent"
                                        name="progress_percent"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        inputmode="decimal"
                                        :placeholder="'0'"
                                        :default-value="item?.progress_percent ?? ''"
                                    />
                                    <InputError :message="errors.progress_percent" />
                                </div>
                            </div>
                        </div>

                        <div class="v2-form-split-block">
                            <h2 class="v2-form-split-block-title">{{ t('Attachments') }}</h2>
                            <RecordAttachments
                                attachable="projects"
                                :attachable-id="isEdit ? item!.id : null"
                                :attachments="item?.attachments"
                                :error="errors.documents"
                            />
                        </div>
                    </div>
                </aside>
            </div>

            <div class="mis-form-actions">
                <Button type="submit" :disabled="processing">
                    {{ processing ? t('Saving…') : t('Save') }}
                </Button>
                <Button
                    v-if="!isEdit"
                    type="button"
                    variant="secondary"
                    @click="historyBack"
                >
                    {{ t('Cancel') }}
                </Button>
                <Button v-else as-child variant="secondary">
                    <Link href="/projects">{{ t('Cancel') }}</Link>
                </Button>
                <Form
                    v-if="isEdit"
                    :action="`/projects/${item!.id}`"
                    method="delete"
                    class="ms-auto"
                    v-slot="{ processing: deleting }"
                >
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="deleting"
                    >
                        <Trash2 class="size-4" />
                        {{ deleting ? t('Deleting…') : t('Delete') }}
                    </Button>
                </Form>
            </div>
        </Form>
    </V2FormPage>
</template>
