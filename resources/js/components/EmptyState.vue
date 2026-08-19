<script setup lang="ts">
defineProps<{
    title?: string;
    colspan?: number;
}>();
</script>

<template>
    <tr>
        <td :colspan="colspan ?? 6" class="px-4 py-14 text-center">
            <div class="mx-auto max-w-sm space-y-1">
                <p class="text-sm font-medium">{{ title || 'No records yet' }}</p>
            </div>
        </td>
    </tr>
</template>
