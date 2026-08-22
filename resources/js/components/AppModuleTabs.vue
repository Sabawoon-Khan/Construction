<script setup lang="ts">
import { computed } from 'vue';
import { MisCreateButton, MisTabs } from '@/components/mis';
import { navItemPath, useModuleTabs } from '@/composables/useModuleTabs';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
const { tabItems, activeId, activeItem, isSettingsArea } = useModuleTabs();

const tabs = computed(() =>
    tabItems.value.map((item) => ({
        id: navItemPath(item.href),
        label: item.title,
        href: navItemPath(item.href),
        icon: item.icon,
    })),
);

const model = computed({
    get: () => activeId.value,
    set() {},
});

const showCreate = computed(
    () => Boolean(activeItem.value?.createHref && activeItem.value?.createPermission),
);

const visible = computed(
    () => !isSettingsArea.value && (tabs.value.length > 1 || showCreate.value),
);
</script>

<template>
    <div v-if="visible" class="print:hidden">
        <MisTabs
            v-model="model"
            nowrap
            :tabs="tabs"
            class="min-w-0"
        >
            <template
                v-if="showCreate"
                #actions
            >
                <MisCreateButton
                    :href="activeItem!.createHref!"
                    :permission="activeItem!.createPermission!"
                    variant="ghost"
                    size="sm"
                    class="text-primary"
                >
                    {{ t('Create') }}
                </MisCreateButton>
            </template>
        </MisTabs>
    </div>
</template>
