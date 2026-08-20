<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CornerDownLeft, LoaderCircle, Search, X } from '@lucide/vue';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/composables/useTranslations';

type SearchResult = {
    id: number | string;
    resource: string;
    label: string;
    title: string;
    subtitle: string;
    url: string;
    action: 'edit' | 'view';
    score?: number;
    matched?: string | null;
};

type SearchGroup = {
    key: string;
    label: string;
    results: SearchResult[];
};

const { t } = useTranslations();

const open = ref(false);
const query = ref('');
const loading = ref(false);
const groups = ref<SearchGroup[]>([]);
const error = ref<string | null>(null);
const activeIndex = ref(0);
const activeFilter = ref<string | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let abortController: AbortController | null = null;

const filteredGroups = computed(() => {
    if (!activeFilter.value) {
        return groups.value;
    }

    return groups.value.filter((group) => group.key === activeFilter.value);
});

const flatResults = computed(() =>
    filteredGroups.value.flatMap((group) =>
        group.results.map((result) => ({
            ...result,
            groupLabel: group.label,
        })),
    ),
);

const resultCount = computed(() =>
    groups.value.reduce((total, group) => total + group.results.length, 0),
);

const shortcutLabel = computed(() =>
    typeof navigator !== 'undefined' && /Mac|iPhone|iPad/i.test(navigator.platform)
        ? '⌘/'
        : 'Ctrl /',
);

function openSearch(): void {
    open.value = true;
}

function closeSearch(): void {
    open.value = false;
}

function resetState(): void {
    query.value = '';
    groups.value = [];
    error.value = null;
    loading.value = false;
    activeIndex.value = 0;
    activeFilter.value = null;
    abortController?.abort();
    abortController = null;
}

function escapeHtml(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function highlight(text: string): string {
    const raw = text.trim();
    if (!raw || query.value.trim().length < 1) {
        return escapeHtml(text);
    }

    const tokens = query.value
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map((token) => token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));

    if (tokens.length === 0) {
        return escapeHtml(text);
    }

    const pattern = new RegExp(`(${tokens.join('|')})`, 'ig');
    return escapeHtml(text).replace(pattern, '<mark class="rounded bg-primary/15 px-0.5 text-foreground">$1</mark>');
}

function setFilter(key: string | null): void {
    activeFilter.value = key;
    activeIndex.value = 0;
}

watch(open, async (isOpen) => {
    if (isOpen) {
        await nextTick();
        inputRef.value?.focus();
        return;
    }

    resetState();
});

watch(query, (value) => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    const trimmed = value.trim();
    activeFilter.value = null;

    if (trimmed.length < 2 && !/^\d+$/.test(trimmed)) {
        groups.value = [];
        error.value = null;
        loading.value = false;
        abortController?.abort();
        return;
    }

    if (trimmed.length === 0) {
        groups.value = [];
        return;
    }

    debounceTimer = setTimeout(() => {
        void runSearch(trimmed);
    }, 180);
});

async function runSearch(term: string): Promise<void> {
    abortController?.abort();
    abortController = new AbortController();
    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(`/search?q=${encodeURIComponent(term)}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: abortController.signal,
        });

        if (!response.ok) {
            throw new Error(t('Search failed.'));
        }

        const data = (await response.json()) as { groups?: SearchGroup[] };
        groups.value = data.groups ?? [];
        activeIndex.value = 0;
    } catch (err) {
        if (err instanceof DOMException && err.name === 'AbortError') {
            return;
        }

        error.value = err instanceof Error ? err.message : t('Search failed.');
        groups.value = [];
    } finally {
        loading.value = false;
    }
}

function goTo(result: SearchResult): void {
    closeSearch();
    router.visit(result.url);
}

function moveActive(delta: number): void {
    const total = flatResults.value.length;

    if (total === 0) {
        return;
    }

    activeIndex.value = (activeIndex.value + delta + total) % total;
}

function onKeyDown(event: KeyboardEvent): void {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        moveActive(1);
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        moveActive(-1);
        return;
    }

    if (event.key === 'Enter') {
        const result = flatResults.value[activeIndex.value];
        if (result) {
            event.preventDefault();
            goTo(result);
        }
    }
}

function onGlobalKeydown(event: KeyboardEvent): void {
    if ((event.metaKey || event.ctrlKey) && event.key === '/') {
        event.preventDefault();
        open.value = !open.value;
    }
}

onMounted(() => {
    window.addEventListener('keydown', onGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onGlobalKeydown);
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
    abortController?.abort();
});
</script>

<template>
    <Button
        type="button"
        variant="ghost"
        class="hidden h-9 gap-2 rounded-full border border-border/70 bg-muted/30 px-3 text-muted-foreground hover:bg-muted/50 hover:text-foreground sm:inline-flex"
        :aria-label="t('Search')"
        @click="openSearch"
    >
        <Search class="size-4 shrink-0" />
        <span class="text-sm">{{ t('Search') }}</span>
        <kbd
            class="ms-1 hidden rounded border border-border/80 bg-background px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground lg:inline"
        >
            {{ shortcutLabel }}
        </kbd>
    </Button>

    <Button
        type="button"
        variant="ghost"
        size="icon"
        class="size-8 sm:hidden"
        :aria-label="t('Search')"
        @click="openSearch"
    >
        <Search class="size-4" />
    </Button>

    <Dialog v-model:open="open">
        <DialogContent
            class="gap-0 overflow-hidden p-0 sm:max-w-xl"
            :show-close-button="false"
        >
            <DialogHeader class="sr-only">
                <DialogTitle>{{ t('Search') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Search by name, code, phone, or related records.') }}
                </DialogDescription>
            </DialogHeader>

            <div class="flex items-center gap-3 border-b border-border/70 px-4">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <input
                    ref="inputRef"
                    v-model="query"
                    type="search"
                    class="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    :placeholder="t('Name, code, phone, sale no…')"
                    autocomplete="off"
                    @keydown="onKeyDown"
                />
                <button
                    v-if="query"
                    type="button"
                    class="rounded-md p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                    @click="query = ''"
                >
                    <X class="size-3.5" />
                </button>
                <LoaderCircle
                    v-if="loading"
                    class="size-4 shrink-0 animate-spin text-muted-foreground"
                />
            </div>

            <div
                v-if="groups.length > 1"
                class="flex flex-wrap gap-1.5 border-b border-border/60 px-3 py-2"
            >
                <button
                    type="button"
                    class="rounded-full px-2.5 py-1 text-xs font-medium transition"
                    :class="
                        !activeFilter
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted/60 text-muted-foreground hover:bg-muted'
                    "
                    @click="setFilter(null)"
                >
                    {{ t('All') }} · {{ resultCount }}
                </button>
                <button
                    v-for="group in groups"
                    :key="group.key"
                    type="button"
                    class="rounded-full px-2.5 py-1 text-xs font-medium transition"
                    :class="
                        activeFilter === group.key
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted/60 text-muted-foreground hover:bg-muted'
                    "
                    @click="setFilter(group.key)"
                >
                    {{ t(group.label) }} · {{ group.results.length }}
                </button>
            </div>

            <div class="max-h-[min(26rem,65vh)] overflow-y-auto p-2">
                <p
                    v-if="error"
                    class="px-3 py-6 text-center text-sm text-destructive"
                >
                    {{ error }}
                </p>

                <p
                    v-else-if="query.trim().length > 0 && query.trim().length < 2 && !/^\d+$/.test(query.trim())"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('Type at least 2 characters.') }}
                </p>

                <p
                    v-else-if="!loading && query.trim().length >= 1 && flatResults.length === 0 && (query.trim().length >= 2 || /^\d+$/.test(query.trim()))"
                    class="px-3 py-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('No results found.') }}
                </p>

                <div
                    v-else-if="query.trim().length === 0"
                    class="space-y-3 px-3 py-5 text-sm text-muted-foreground"
                >
                    <p>{{ t('Search by name, code, phone, or related records.') }}</p>
                    <ul class="space-y-1.5 text-xs">
                        <li>• {{ t('Customer name or phone') }}</li>
                        <li>• {{ t('Project / sale / purchase number') }}</li>
                        <li>• {{ t('Supplier linked to a purchase') }}</li>
                    </ul>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="group in filteredGroups"
                        :key="group.key"
                        class="space-y-1"
                    >
                        <p class="px-2 pt-1 text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ t(group.label) }}
                        </p>
                        <button
                            v-for="result in group.results"
                            :key="`${result.resource}-${result.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-start transition"
                            :class="
                                flatResults[activeIndex]?.id === result.id &&
                                flatResults[activeIndex]?.resource === result.resource
                                    ? 'bg-primary/10 text-foreground'
                                    : 'hover:bg-muted/60'
                            "
                            @mouseenter="
                                activeIndex = flatResults.findIndex(
                                    (item) =>
                                        item.id === result.id &&
                                        item.resource === result.resource,
                                )
                            "
                            @click="goTo(result)"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-medium [&_mark]:rounded [&_mark]:bg-primary/15 [&_mark]:px-0.5"
                                    v-html="highlight(result.title)"
                                />
                                <p
                                    v-if="result.subtitle"
                                    class="truncate text-xs text-muted-foreground [&_mark]:rounded [&_mark]:bg-primary/10 [&_mark]:px-0.5"
                                    v-html="highlight(result.subtitle)"
                                />
                            </div>
                            <span
                                class="shrink-0 rounded-md border border-border/70 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-muted-foreground"
                            >
                                {{ result.action === 'edit' ? t('Edit') : t('View') }}
                            </span>
                            <CornerDownLeft class="size-3.5 shrink-0 text-muted-foreground/70" />
                        </button>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
