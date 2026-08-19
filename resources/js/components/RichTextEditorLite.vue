<script setup lang="ts">
import { Link2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';

const props = withDefaults(
    defineProps<{
        modelValue: string;
        placeholder?: string;
    }>(),
    {
        placeholder: 'Write here...',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();
const { t } = useTranslations();

const editorRef = ref<HTMLDivElement | null>(null);
const isFocused = ref(false);
const isEmpty = computed(() => !props.modelValue || props.modelValue === '<br>');

watch(
    () => props.modelValue,
    (value) => {
        const el = editorRef.value;
        if (!el) {
            return;
        }

        if (el.innerHTML !== value) {
            el.innerHTML = value || '';
        }
    },
    { immediate: true },
);

function onInput(): void {
    emit('update:modelValue', editorRef.value?.innerHTML || '');
}

function applyFormat(command: string): void {
    editorRef.value?.focus();
    document.execCommand(command, false);
    onInput();
}

function insertLink(): void {
    const url = window.prompt(t('Enter URL'));
    if (!url) {
        return;
    }

    editorRef.value?.focus();
    document.execCommand('createLink', false, url);
    onInput();
}
</script>

<template>
    <div class="rounded-xl border border-border/80 bg-background">
        <div class="flex flex-wrap gap-1 border-b border-border/70 p-2">
            <Button type="button" size="sm" variant="outline" @click="applyFormat('bold')">
                B
            </Button>
            <Button type="button" size="sm" variant="outline" @click="applyFormat('italic')">
                I
            </Button>
            <Button type="button" size="sm" variant="outline" @click="applyFormat('underline')">
                U
            </Button>
            <Button type="button" size="sm" variant="outline" @click="applyFormat('insertUnorderedList')">
                {{ t('List') }}
            </Button>
            <Button type="button" size="sm" variant="outline" @click="applyFormat('insertOrderedList')">
                {{ t('Numbered') }}
            </Button>
            <Button type="button" size="sm" variant="outline" @click="insertLink">
                <Link2 class="size-3.5" />
            </Button>
            <Button type="button" size="sm" variant="ghost" @click="applyFormat('removeFormat')">
                {{ t('Clear') }}
            </Button>
        </div>
        <div class="relative">
            <div
                ref="editorRef"
                contenteditable="true"
                class="prose prose-sm dark:prose-invert min-h-36 w-full max-w-none px-3 py-2 text-sm outline-none"
                @focus="isFocused = true"
                @blur="isFocused = false"
                @input="onInput"
            />
            <p
                v-if="!isFocused && isEmpty"
                class="pointer-events-none absolute top-2 left-3 text-sm text-muted-foreground"
            >
                {{ placeholder }}
            </p>
        </div>
    </div>
</template>
