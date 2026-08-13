<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Pencil, Shield, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import Can from '@/components/Can.vue';
import {
    MisEmptyState,
    MisListPage,
    MisListRow,
} from '@/components/mis';
import RowActionsMenu from '@/components/RowActionsMenu.vue';
import SettingsAddButton from '@/components/SettingsAddButton.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import type { RowActionItem } from '@/lib/row-actions';

type Permission = { id: number; name: string };

type RoleRecord = {
    id: number;
    name: string;
    users_count?: number;
    permissions?: Permission[];
};

const props = defineProps<{
    roles: RoleRecord[];
    permissions: Permission[];
}>();

const { t } = useTranslations();
const { can } = usePermissions();
const protectedRoles = ['Owner'];
const editingRole = ref<RoleRecord | null>(null);
const editName = ref('');
const editPermissions = ref<string[]>([]);

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

function openEdit(role: RoleRecord): void {
    editingRole.value = role;
    editName.value = role.name;
    editPermissions.value = role.permissions?.map((p) => p.name) ?? [];
}

function togglePermission(name: string, checked: boolean | 'indeterminate'): void {
    if (checked === true) {
        if (!editPermissions.value.includes(name)) {
            editPermissions.value = [...editPermissions.value, name];
        }
    } else {
        editPermissions.value = editPermissions.value.filter((p) => p !== name);
    }
}

function saveRole(): void {
    if (!editingRole.value) {
        return;
    }

    router.patch(`/settings/roles/${editingRole.value.id}`, {
        name: editName.value,
        permissions: editPermissions.value,
    }, {
        onSuccess: () => {
            editingRole.value = null;
        },
    });
}

function roleActions(role: RoleRecord): RowActionItem[] {
    return [
        {
            label: t('Edit'),
            icon: Pencil,
            onClick: () => openEdit(role),
            hidden: !can('roles.edit'),
        },
        {
            label: t('Move to trash'),
            icon: Trash2,
            variant: 'destructive',
            separator: true,
            href: `/settings/roles/${role.id}`,
            method: 'delete',
            hidden: protectedRoles.includes(role.name) || !can('roles.delete'),
            confirm: {
                title: t('Move to trash'),
                description: `${t('Move to trash')}: ${role.name}?`,
                confirmLabel: t('Move to trash'),
            },
        },
    ];
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Settings', href: '/settings/roles' },
            { title: 'Roles', href: '/settings/roles' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Role Management')" />

    <MisListPage
        :title="t('Role Management')"
        :description="t('Define roles and permissions')"
        :icon="Shield"
    >
        <template #actions>
            <Can permission="roles.create">
                <SettingsAddButton href="/settings/roles/create">
                    {{ t('Create role') }}
                </SettingsAddButton>
            </Can>
        </template>

        <MisEmptyState v-if="roles.length === 0">
            {{ t('No roles found') }}
        </MisEmptyState>

        <template v-else>
            <MisListRow
                v-for="role in roles"
                :key="role.id"
                class="flex flex-wrap items-center justify-between gap-3"
            >
                <div>
                    <p class="font-medium">{{ role.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ role.permissions?.length ?? 0 }}
                        {{ t('Permissions') }} ·
                        {{ role.users_count ?? 0 }} {{ t('Users') }}
                    </p>
                </div>
                <Can permission="roles.edit">
                    <RowActionsMenu :actions="roleActions(role)" />
                </Can>
            </MisListRow>
        </template>

        <template #below>
            <Dialog
                :open="!!editingRole"
                @update:open="(v) => !v && (editingRole = null)"
            >
                <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>
                            {{ t('Edit') }} {{ editingRole?.name }}
                        </DialogTitle>
                    </DialogHeader>

                    <div class="space-y-4">
                        <div class="space-y-2">
                            <Label for="role-name">{{ t('Name') }}</Label>
                            <Input
                                id="role-name"
                                v-model="editName"
                                :disabled="
                                    protectedRoles.includes(
                                        editingRole?.name ?? '',
                                    )
                                "
                            />
                        </div>

                        <div class="space-y-4">
                            <p class="text-sm font-medium">
                                {{ t('Permissions') }}
                            </p>
                            <div
                                v-for="[module, modulePermissions] in permissionGroups"
                                :key="module"
                                class="space-y-2"
                            >
                                <p
                                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    {{ module }}
                                </p>
                                <label
                                    v-for="permission in modulePermissions"
                                    :key="permission.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <Checkbox
                                        :checked="
                                            editPermissions.includes(
                                                permission.name,
                                            )
                                        "
                                        @update:checked="
                                            (v) =>
                                                togglePermission(
                                                    permission.name,
                                                    v,
                                                )
                                        "
                                    />
                                    {{ permission.name }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="editingRole = null"
                        >
                            {{ t('Cancel') }}
                        </Button>
                        <Button @click="saveRole">{{ t('Save') }}</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </template>
    </MisListPage>
</template>
