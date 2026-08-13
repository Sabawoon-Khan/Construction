<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{ item: Record<string, any> | null }>();
const isEdit = computed(() => !!props.item);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Material Units', href: '/material-units' },
            { title: 'Form', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="isEdit ? 'Edit Unit' : 'Create Unit'" />
    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading :title="isEdit ? 'Edit Unit' : 'Create Unit'" description="Examples: bag, kg, ton, m3." />
        <Form
            :action="isEdit ? `/material-units/${item!.id}` : '/material-units'"
            :method="isEdit ? 'put' : 'post'"
            class="max-w-xl space-y-6 rounded-xl border bg-card p-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="unit_name">Unit name</Label>
                <Input id="unit_name" name="unit_name" required :default-value="item?.unit_name ?? ''" placeholder="e.g. bag" />
                <InputError :message="errors.unit_name" />
            </div>
            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">{{ processing ? 'Saving...' : 'Save' }}</Button>
                <Button as-child variant="secondary"><Link href="/material-units">Cancel</Link></Button>
            </div>
        </Form>
    </div>
</template>
