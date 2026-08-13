<script setup lang="ts">
defineProps<{
    title?: string;
    description?: string;
    colspan?: number;
}>();
</script>

<template>
    <tr>
        <td :colspan="colspan ?? 6" class="px-4 py-14 text-center">
            <div class="mx-auto max-w-sm space-y-1">
                <p class="text-sm font-medium">{{ title || 'No records yet' }}</p>
                <p class="text-sm text-muted-foreground">
                    {{ description || 'Try adjusting filters or add a new record.' }}
                </p>
            </div>
        </td>
    </tr>
</template>
