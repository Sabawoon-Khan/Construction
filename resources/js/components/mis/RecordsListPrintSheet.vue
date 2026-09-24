<script setup lang="ts">
import MisPrintFooter from '@/components/mis/MisPrintFooter.vue';
import MisPrintLetterhead from '@/components/mis/MisPrintLetterhead.vue';
import { useTranslations } from '@/composables/useTranslations';
import { formatNumber } from '@/lib/utils';

defineProps<{
    title: string;
    subtitle?: string | null;
    columns: string[];
    rows: string[][];
}>();

const { t } = useTranslations();
</script>

<template>
    <article class="expense-a4-sheet a4-print-sheet print-target">
        <MisPrintLetterhead :title="title" :subtitle="subtitle || undefined" />

        <div class="a4-summary">
            <div>
                <span>{{ t('Records') }}</span>
                <strong>{{ formatNumber(rows.length, 0) }}</strong>
            </div>
        </div>

        <table class="a4-table">
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th v-for="column in columns" :key="column">{{ column }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in rows" :key="index">
                    <td class="col-num">{{ index + 1 }}</td>
                    <td v-for="(cell, cellIndex) in row" :key="cellIndex">
                        {{ cell }}
                    </td>
                </tr>
                <tr v-if="!rows.length">
                    <td :colspan="columns.length + 1" class="empty">
                        {{ t('No records') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <MisPrintFooter />
    </article>
</template>
