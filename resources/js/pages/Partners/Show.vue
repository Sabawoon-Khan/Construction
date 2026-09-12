<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FolderKanban, Pencil, Wallet } from '@lucide/vue';
import Can from '@/components/Can.vue';
import EmptyState from '@/components/EmptyState.vue';
import RecordAttachments from '@/components/RecordAttachments.vue';
import { computed } from 'vue';
import SortableTh from '@/components/SortableTh.vue';
import TableFrame from '@/components/TableFrame.vue';
import TableIndexTd from '@/components/TableIndexTd.vue';
import TableIndexTh from '@/components/TableIndexTh.vue';
import { indexTableColumn } from '@/composables/useTableColumns';
import { MisDetailGrid, MisMoneyStack } from '@/components/mis';
import { Button } from '@/components/ui/button';
import {
    V2DetailHero,
    V2ListPage,
    V2Panel,
    V2StatCard,
    V2StatGrid,
} from '@/components/v2';
import { useMisPage } from '@/composables/useMisPage';
import { provideTableSort } from '@/composables/useTableSort';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber, money } from '@/lib/utils';

const props = defineProps<{
    item: Record<string, any>;
    projects: {
        id: number;
        name: string;
        ownership_percentage: number;
        entitled_share: number;
        paid_total: number;
        owed_total?: number;
        balance_owed: number;
        created_at?: string;
    }[];
    stats: {
        projects_count: number;
        contributions_total: number;
        payouts_total: number;
        contributions_by_currency?: Record<string, number>;
        payouts_by_currency?: Record<string, number>;
    };
}>();
const { t } = useTranslations();
const tableColumns = computed(() => [
    indexTableColumn(),
    { key: 'name', label: t('Project') },
    { key: 'ownership_percentage', label: t('Share') },
    { key: 'entitled_share', label: t('Profit share') },
    { key: 'paid_total', label: t('Paid') },
    { key: 'balance_owed', label: t('Owed') },
    { key: 'created_at', label: t('Created at') },
]);
const { formatDate } = useMisPage();
const { sortedRows } = provideTableSort(() => props.projects);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/appearance' },
            { title: 'Shareholders', href: '/partners' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="item.name" />
    <V2ListPage>
        <V2DetailHero image="/images/dashboard-v2-building.png">
            <template #eyebrow>{{ t('Shareholders') }}</template>
            <template #title>{{ item.name }}</template>
            <template #description>
                {{ t('Shareholder') }} {{ item.partner_no || '' }}
            </template>
            <template #actions>
                <Button as-child variant="outline">
                    <a href="/reports/print" target="_blank">{{ t('Print distribution') }}</a>
                </Button>
                <Can permission="partners.edit">
                    <Button as-child>
                        <Link :href="`/partners/${item.id}/edit`">
                            <Pencil class="size-4" /> {{ t('Edit') }}
                        </Link>
                    </Button>
                </Can>
            </template>
            <template #stats>
                <V2StatGrid class="detail-stat-grid">
                    <V2StatCard
                        :delay="0"
                        :title="t('Projects')"
                        :value="stats.projects_count"
                    >
                        <template #icon><FolderKanban /></template>
                    </V2StatCard>
                    <V2StatCard :delay="1" icon-tone="warm" :title="t('Total contributed')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Total contributed') }}</span>
                        <MisMoneyStack
                            :amounts="stats.contributions_by_currency"
                            :fallback="stats.contributions_total"
                        />
                    </V2StatCard>
                    <V2StatCard :delay="2" icon-tone="teal" :title="t('Total paid out')">
                        <template #icon><Wallet /></template>
                        <span>{{ t('Total paid out') }}</span>
                        <MisMoneyStack
                            :amounts="stats.payouts_by_currency"
                            :fallback="stats.payouts_total"
                        />
                    </V2StatCard>
                </V2StatGrid>
            </template>
        </V2DetailHero>

        <RecordAttachments
            attachable="partners"
            :attachable-id="item.id"
            :attachments="item.attachments"
            boxed
        />

        <V2Panel :padded="false" :title="t('Project shares')">
            <TableFrame table-id="partner-shares" :columns="tableColumns">
                <table class="min-w-full text-sm">
                    <thead class="table-chrome-head text-start text-muted-foreground">
                        <tr>
                            <TableIndexTh />
                            <SortableTh column="name">{{ t('Project') }}</SortableTh>
                            <SortableTh column="ownership_percentage">{{ t('Share') }}</SortableTh>
                            <SortableTh column="entitled_share">{{ t('Profit share') }}</SortableTh>
                            <SortableTh column="paid_total">{{ t('Paid') }}</SortableTh>
                            <SortableTh column="balance_owed">{{ t('Owed') }}</SortableTh>
                            <SortableTh column="created_at">{{ t('Created at') }}</SortableTh>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, index) in sortedRows" :key="p.id" class="border-t hover:bg-muted/30">
                            <TableIndexTd :index="index" />
                            <td class="px-4 py-3">
                                <Link :href="`/projects/${p.id}`" class="font-medium text-primary hover:underline">{{ p.name }}</Link>
                            </td>
                            <td class="px-4 py-3 tabular-nums">{{ formatNumber(p.ownership_percentage) }}%</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(p.entitled_share) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(p.paid_total) }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ money(p.balance_owed) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">{{ formatDate(p.created_at) }}</td>
                        </tr>
                        <EmptyState
                            v-if="!projects.length"
                            :colspan="7"
                            :title="t('Not assigned to any project yet. Add from a project page.')"
                        />
                    </tbody>
                </table>
            </TableFrame>
        </V2Panel>

        <V2Panel :title="t('Contact details')">
            <MisDetailGrid
                :items="[
                    { label: t('Partner No'), value: item.partner_no },
                    { label: t('Phone'), value: item.phone },
                    { label: t('Email'), value: item.email },
                    { label: t('National ID'), value: item.national_id },
                    { label: t('Address'), value: item.address, span: 2 },
                    { label: t('Notes'), value: item.notes, span: 3 },
                ]"
            />
        </V2Panel>
    </V2ListPage>
</template>
