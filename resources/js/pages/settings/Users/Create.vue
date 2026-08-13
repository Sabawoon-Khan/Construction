<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

defineProps<{
    roles: Array<{ id: number; name: string }>;
}>();

const { t } = useTranslations();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

function submit(): void {
    form.post('/settings/users');
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Users', href: '/settings/users' },
            { title: 'Create', href: '/settings/users/create' },
        ],
    },
});
</script>

<template>
    <Head :title="t('Create user')" />

    <div class="space-y-6">
        <Heading variant="small" :title="t('Create user')" />

        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <Label for="name">{{ t('Name') }}</Label>
                <Input id="name" v-model="form.name" required />
                <InputError :message="form.errors.name" />
            </div>

            <div class="space-y-2">
                <Label for="email">{{ t('Email') }}</Label>
                <Input id="email" v-model="form.email" type="email" required />
                <InputError :message="form.errors.email" />
            </div>

            <div class="space-y-2">
                <Label for="password">{{ t('Password') }}</Label>
                <Input id="password" v-model="form.password" type="password" required />
                <InputError :message="form.errors.password" />
            </div>

            <div class="space-y-2">
                <Label for="password_confirmation">{{ t('Password') }}</Label>
                <Input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                />
            </div>

            <div class="space-y-2">
                <Label>{{ t('Assign roles') }}</Label>
                <select
                    v-model="form.roles"
                    multiple
                    class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                >
                    <option v-for="role in roles" :key="role.id" :value="role.name">
                        {{ role.name }}
                    </option>
                </select>
            </div>

            <Button type="submit" :disabled="form.processing">{{ t('Save') }}</Button>
        </form>
    </div>
</template>
