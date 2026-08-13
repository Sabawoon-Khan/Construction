<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

type Permission = { id: number; name: string };

const props = defineProps<{
    permissions: Permission[];
}>();

const { t } = useTranslations();

const form = useForm({
    name: '',
    permissions: [] as string[],
});

const permissionGroups = computed(() => {
    const groups: Record<string, Permission[]> = {};

    for (const permission of props.permissions) {
        const [module] = permission.name.split('.');
        const key = module ?? permission.name;

        if (!groups[key]) {
            groups[key] = [];
        }

        groups[key].push(permission);
    }

    return Object.entries(groups).sort(([a], [b]) => a.localeCompare(b));
});

function togglePermission(name: string, checked: boolean | 'indeterminate'): void {
    if (checked === true) {
        if (!form.permissions.includes(name)) {
            form.permissions = [...form.permissions, name];
        }
    } else {
        form.permissions = form.permissions.filter((p) => p !== name);
    }
}

function submit(): void {
    form.post('/settings/roles');
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Roles', href: '/settings/roles' },
            { title: 'Create', href: '/settings/roles/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Create role')" />

    <div class="space-y-6">
        <Heading variant="small" :title="t('Create role')" />

        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="name">{{ t('Name') }}</Label>
                <Input id="name" v-model="form.name" required />
                <InputError :message="form.errors.name" />
            </div>

            <div class="space-y-4">
                <p class="text-sm font-medium">{{ t('Permissions') }}</p>
                <div
                    v-for="[module, modulePermissions] in permissionGroups"
                    :key="module"
                    class="space-y-2"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ module }}
                    </p>
                    <label
                        v-for="permission in modulePermissions"
                        :key="permission.id"
                        class="flex items-center gap-2 text-sm"
                    >
                        <Checkbox
                            :checked="form.permissions.includes(permission.name)"
                            @update:checked="(v) => togglePermission(permission.name, v)"
                        />
                        {{ permission.name }}
                    </label>
                </div>
            </div>

            <Button type="submit" :disabled="form.processing">{{ t('Save') }}</Button>
        </form>
    </div>
</template>
