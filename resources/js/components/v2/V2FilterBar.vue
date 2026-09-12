<template>
    <div class="filter-panel">
        <div class="toolbar">
            <slot />
            <div v-if="$slots.columns" class="toolbar-cols">
                <slot name="columns" />
            </div>
        </div>
    </div>
</template>
