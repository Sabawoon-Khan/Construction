<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

defineProps<{
    open: boolean;
    projectId: number;
    availableShareholders: { id: number; name: string; phone?: string }[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t } = useTranslations();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('Add shareholder') }}</DialogTitle>
            </DialogHeader>

            <Form
                :action="`/projects/${projectId}/shareholders`"
                method="post"
                class="grid gap-4"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="add_partner_id">{{ t('Shareholder') }}</Label>
                        <RelatedCreateLink
                            href="/partners/create"
                            :label="t('New shareholder')"
                        />
                    </div>
                    <select
                        id="add_partner_id"
                        name="partner_id"
                        required
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                    >
                        <option value="" disabled selected>
                            {{ t('Select from list') }}
                        </option>
                        <option
                            v-for="s in availableShareholders"
                            :key="s.id"
                            :value="s.id"
                        >
                            {{ s.name }}{{ s.phone ? ` · ${s.phone}` : '' }}
                        </option>
                    </select>
                    <InputError :message="errors.partner_id" />
                    <p
                        v-if="!availableShareholders.length"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('No shareholders available') }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="add_ownership_percentage">{{
                        t('Ownership %')
                    }}</Label>
                    <Input
                        id="add_ownership_percentage"
                        name="ownership_percentage"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        required
                        placeholder="0"
                    />
                    <InputError :message="errors.ownership_percentage" />
                </div>

                <DialogFooter class="gap-2 sm:justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                    >
                        {{ t('Cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        :disabled="processing || !availableShareholders.length"
                    >
                        {{ processing ? t('Saving…') : t('Add to project') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
