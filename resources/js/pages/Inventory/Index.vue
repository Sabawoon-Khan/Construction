<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import { MisFilterBar, MisSearchInput, MisStatCard, MisStatGrid } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

type StockRow = {
    project_id: number;
    project_name?: string;
    material_id: number;
    material_name?: string;
    material_code?: string;
    unit_name?: string;
    purchased: number;
    issued: number;
    on_hand: number;
    minimum_stock: number;
};

const props = defineProps<{
    items: StockRow[];
    projects: { id: number; name: string }[];
    materials: { id: number; name: string; code: string; unit?: { unit_name: string } | null }[];
    filters: { project_id?: number | null; search?: string };
    totals: { rows: number; on_hand_rows: number };
}>();

const { t } = useTranslations();
const projectId = ref(props.filters.project_id ? String(props.filters.project_id) : '');
const search = ref(props.filters.search ?? '');

const issueMaterials = computed(() => {
    if (!projectId.value) {
        return props.materials;
    }

    const ids = new Set(
        props.items
            .filter((row) => String(row.project_id) === projectId.value && row.on_hand > 0)
            .map((row) => row.material_id),
    );

    return props.materials.filter((material) => ids.has(material.id));
});

function applyFilter() {
    router.get('/inventory', {
        project_id: projectId.value || undefined,
        search: search.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    projectId.value = '';
    applyFilter();
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Stock', href: '/inventory' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Stock')" />
    <div class="flex h-full flex-1 flex-col gap-4">
        <MisFilterBar :apply-label="t('Apply')" :clear-label="t('All')" @apply="applyFilter" @clear="clearFilters">
            <MisSearchInput v-model="search" class="min-w-[200px] flex-1" :placeholder="t('Search...')" />
            <select v-model="projectId" class="h-9 min-w-[160px] rounded-md border border-input bg-background px-3 text-sm">
                <option value="">{{ t('All projects') }}</option>
                <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
            </select>
        </MisFilterBar>

        <MisStatGrid :columns="2">
            <MisStatCard :label="t('Stock rows')" :value="totals.rows" />
            <MisStatCard :label="t('With quantity on hand')" :value="totals.on_hand_rows" />
        </MisStatGrid>

        <Can permission="inventory.create">
        <Form
            action="/inventory"
            method="post"
            class="grid gap-4 rounded-xl border bg-card p-5 sm:grid-cols-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label>{{ t('Project') }}</Label>
                <select name="project_id" required class="h-9 rounded-md border px-3 text-sm" :value="projectId">
                    <option value="" disabled>{{ t('Select project') }}</option>
                    <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                </select>
                <InputError :message="errors.project_id" />
            </div>
            <div class="grid gap-2">
                <Label>{{ t('Material') }}</Label>
                <select name="material_id" required class="h-9 rounded-md border px-3 text-sm">
                    <option value="">{{ t('Select material') }}</option>
                    <option v-for="material in issueMaterials" :key="material.id" :value="material.id">
                        {{ material.name }}{{ material.unit?.unit_name ? ` (${material.unit.unit_name})` : '' }}
                    </option>
                </select>
                <InputError :message="errors.material_id" />
            </div>
            <div class="grid gap-2">
                <Label>{{ t('Qty to issue') }}</Label>
                <Input name="quantity" type="number" step="0.001" min="0.001" required />
                <InputError :message="errors.quantity" />
            </div>
            <div class="grid gap-2">
                <Label>{{ t('Date') }}</Label>
                <Input name="transaction_date" type="date" :default-value="new Date().toISOString().slice(0, 10)" />
            </div>
            <div class="sm:col-span-4">
                <Button type="submit" :disabled="processing">{{ processing ? t('Saving…') : t('Issue from stock') }}</Button>
            </div>
        </Form>
        </Can>

        <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-soft">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-muted/30 text-start text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ t('Project') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Material') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Unit') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Purchased') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('Issued') }}</th>
                            <th class="px-4 py-3 font-medium">{{ t('On hand') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in items" :key="`${row.project_id}-${row.material_id}-${index}`" class="border-b last:border-0">
                            <td class="px-4 py-3">
                                <Link :href="`/projects/${row.project_id}`" class="hover:underline">{{ row.project_name }}</Link>
                            </td>
                            <td class="px-4 py-3">{{ row.material_name }} <span class="text-muted-foreground">{{ row.material_code }}</span></td>
                            <td class="px-4 py-3">{{ row.unit_name || '—' }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ row.purchased }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ row.issued }}</td>
                            <td class="px-4 py-3 tabular-nums font-medium" :class="row.on_hand <= 0 ? 'text-muted-foreground' : ''">{{ row.on_hand }}</td>
                        </tr>
                        <EmptyState v-if="!items.length" :colspan="6" :title="t('No stock yet')" />
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
