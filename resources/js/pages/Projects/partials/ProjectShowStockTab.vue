<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import Can from '@/components/Can.vue';
import InputError from '@/components/InputError.vue';
import RelatedCreateLink from '@/components/RelatedCreateLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCalendar } from '@/composables/useCalendar';
import { useTranslations } from '@/composables/useTranslations';

defineProps<{
    item: Record<string, any>;
    stock?: {
        material_id: number;
        purchased: number;
        issued: number;
        on_hand: number;
        last_issue_at?: string | null;
    }[];
    stock_materials?: {
        id: number;
        name: string;
        code: string;
        unit?: { unit_name: string } | null;
    }[];
    stock_issues?: {
        id: number;
        material_id: number;
        quantity: number;
        transaction_date?: string | null;
        material?: { id: number; name: string } | null;
    }[];
}>();

const { t } = useTranslations();
const calendar = useCalendar();
const formatDate = (value: string | null | undefined) =>
    calendar.format(value) || '—';
</script>

<template>
    <div class="space-y-4">
        <Form
            action="/inventory"
            method="post"
            class="grid gap-4 rounded-xl border bg-card p-5 sm:grid-cols-4"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="project_id" :value="item.id" />
            <input
                type="hidden"
                name="redirect_to"
                :value="`/projects/${item.id}`"
            />
            <div class="grid gap-2 sm:col-span-2">
                <div class="flex items-center justify-between gap-2">
                    <Label>{{ t('Material') }}</Label>
                    <RelatedCreateLink href="/materials/create" :label="t('Add material')" />
                </div>
                <select
                    name="material_id"
                    required
                    class="h-9 rounded-md border px-3 text-sm"
                >
                    <option value="">{{ t('Select material') }}</option>
                    <option
                        v-for="row in (stock || []).filter(
                            (s) => s.on_hand > 0,
                        )"
                        :key="row.material_id"
                        :value="row.material_id"
                    >
                        {{
                            stock_materials?.find(
                                (m) => m.id === row.material_id,
                            )?.name || row.material_id
                        }}
                        ({{ t('on hand') }} {{ row.on_hand }})
                    </option>
                </select>
                <InputError :message="errors.material_id" />
            </div>
            <div class="grid gap-2">
                <Label>{{ t('Qty to issue') }}</Label>
                <Input
                    name="quantity"
                    type="number"
                    step="0.001"
                    min="0.001"
                    required
                />
                <InputError :message="errors.quantity" />
            </div>
            <div class="grid gap-2">
                <Label>{{ t('Date') }}</Label>
                <Input
                    name="transaction_date"
                    type="date"
                    :default-value="new Date().toISOString().slice(0, 10)"
                />
            </div>
            <div class="sm:col-span-4">
                <Can permission="inventory.create">
                    <Button type="submit" :disabled="processing">{{
                        processing ? t('Saving…') : t('Issue from stock')
                    }}</Button>
                </Can>
            </div>
        </Form>

        <div class="grid gap-4 lg:grid-cols-2">
            <div
                class="overflow-hidden rounded-xl border bg-card shadow-sm"
            >
                <div class="border-b px-5 py-4">
                    <h3 class="text-sm font-semibold">
                        {{ t('Stock on hand') }}
                    </h3>
                </div>
                <table class="min-w-full text-sm">
                    <thead
                        class="bg-muted/40 text-start text-muted-foreground"
                    >
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Material') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Unit') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('On hand') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in stock || []"
                            :key="`onhand-${row.material_id}`"
                            class="border-t"
                        >
                            <td class="px-4 py-3">
                                {{
                                    stock_materials?.find(
                                        (m) => m.id === row.material_id,
                                    )?.name || '—'
                                }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    stock_materials?.find(
                                        (m) => m.id === row.material_id,
                                    )?.unit?.unit_name || '—'
                                }}
                            </td>
                            <td class="px-4 py-3 font-medium tabular-nums">
                                {{ row.on_hand }}
                            </td>
                        </tr>
                        <tr v-if="!(stock || []).length">
                            <td
                                colspan="3"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                {{ t('No stock yet') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="overflow-hidden rounded-xl border bg-card shadow-sm"
            >
                <div class="border-b px-5 py-4">
                    <h3 class="text-sm font-semibold">
                        {{ t('Stock issue dates') }}
                    </h3>
                </div>
                <table class="min-w-full text-sm">
                    <thead
                        class="bg-muted/40 text-start text-muted-foreground"
                    >
                        <tr>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Material') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Issued qty') }}
                            </th>
                            <th class="px-4 py-3 font-medium">
                                {{ t('Issue date') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="issue in stock_issues || []"
                            :key="`issued-${issue.id}`"
                            class="border-t"
                        >
                            <td class="px-4 py-3">
                                {{
                                    issue.material?.name ||
                                    stock_materials?.find(
                                        (m) => m.id === issue.material_id,
                                    )?.name ||
                                    '—'
                                }}
                            </td>
                            <td class="px-4 py-3 tabular-nums">
                                {{ issue.quantity }}
                            </td>
                            <td class="px-4 py-3">
                                {{
                                    issue.transaction_date
                                        ? formatDate(issue.transaction_date)
                                        : '—'
                                }}
                            </td>
                        </tr>
                        <tr v-if="!(stock_issues || []).length">
                            <td
                                colspan="3"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                {{ t('No issue dates yet') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
