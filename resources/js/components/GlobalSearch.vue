<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CornerDownLeft, LoaderCircle, Search, X } from '@lucide/vue';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useAppNavigation } from '@/composables/useAppNavigation';
import { usePermissions } from '@/composables/usePermissions';
import { useTranslations } from '@/composables/useTranslations';
import { toUrl } from '@/lib/utils';

type ResultAction = 'edit' | 'view' | 'go' | 'create' | 'recent';

type SearchResult = {
    id: number | string;
    resource: string;
    label: string;
    title: string;
    subtitle: string;
    url: string;
    action: ResultAction;
    score?: number;
    matched?: string | null;
};

type SearchGroup = {
    key: string;
    label: string;
    results: SearchResult[];
};

type RecentItem = {
    title: string;
    subtitle?: string;
    url: string;
    resource: string;
};

const RECENT_KEY = 'global-search-recent';
const RECENT_MAX = 8;

const { t } = useTranslations();
const { allNavItems } = useAppNavigation();
const { can } = usePermissions();

const open = ref(false);
const query = ref('');
const loading = ref(false);
const groups = ref<SearchGroup[]>([]);
const error = ref<string | null>(null);
const activeIndex = ref(0);
const activeFilter = ref<string | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);
const recentItems = ref<RecentItem[]>([]);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let abortController: AbortController | null = null;

const isEmptyQuery = computed(() => query.value.trim().length === 0);

const filteredGroups = computed(() => {
    if (!activeFilter.value) {
        return groups.value;
    }

    return groups.value.filter((group) => group.key === activeFilter.value);
});

const recentResults = computed((): SearchResult[] =>
    recentItems.value.map((item, index) => ({
        id: `recent-${item.url}-${index}`,
        resource: item.resource || 'recent',
        label: 'Recent',
        title: item.title,
        subtitle: item.subtitle ?? '',
        url: item.url,
        action: 'recent',
    })),
);

const commandResults = computed((): SearchResult[] => {
    const items: SearchResult[] = [];

    for (const nav of allNavItems.value) {
        const href = toUrl(nav.href);

        if (!href) {
            continue;
        }

        items.push({
            id: `go-${href}`,
            resource: 'command',
            label: 'Commands',
            title: t('Go to :title', { title: nav.title }),
            subtitle: '',
            url: href,
            action: 'go',
        });

        if (
            nav.createHref &&
            (!nav.createPermission || can(nav.createPermission))
        ) {
            items.push({
                id: `create-${nav.createHref}`,
                resource: 'command',
                label: 'Commands',
                title: t('Create :title', { title: nav.title }),
                subtitle: '',
                url: nav.createHref,
                action: 'create',
            });
        }
    }

    return items;
});

const emptyGroups = computed((): SearchGroup[] => {
    const next: SearchGroup[] = [];

    if (recentResults.value.length > 0) {
        next.push({
            key: 'recent',
            label: 'Recent',
            results: recentResults.value,
        });
    }

    if (commandResults.value.length > 0) {
        next.push({
            key: 'commands',
            label: 'Commands',
            results: commandResults.value,
        });
    }

    return next;
});

const displayGroups = computed(() =>
    isEmptyQuery.value ? emptyGroups.value : filteredGroups.value,
);

const flatResults = computed(() =>
    displayGroups.value.flatMap((group) =>
        group.results.map((result) => ({
            ...result,
            groupLabel: group.label,
        })),
    ),
);

const resultCount = computed(() =>
    groups.value.reduce((total, group) => total + group.results.length, 0),
);

const shortcutLabel = ref('Ctrl+K');

function loadRecent(): void {
    try {
        const raw = localStorage.getItem(RECENT_KEY);
        if (!raw) {
            recentItems.value = [];
            return;
        }

        const parsed = JSON.parse(raw) as unknown;
        if (!Array.isArray(parsed)) {
            recentItems.value = [];
            return;
        }

        recentItems.value = parsed
            .filter(
                (item): item is RecentItem =>
                    !!item &&
                    typeof item === 'object' &&
                    typeof (item as RecentItem).title === 'string' &&
                    typeof (item as RecentItem).url === 'string' &&
                    typeof (item as RecentItem).resource === 'string',
            )
            .slice(0, RECENT_MAX);
    } catch {
        recentItems.value = [];
    }
}

function pushRecent(item: RecentItem): void {
    const next = [
        item,
        ...recentItems.value.filter((existing) => existing.url !== item.url),
    ].slice(0, RECENT_MAX);

    recentItems.value = next;

    try {
        localStorage.setItem(RECENT_KEY, JSON.stringify(next));
    } catch {
        // ignore quota / private mode
    }
}

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
    return escapeHtml(text).replace(
        pattern,
        '<mark class="gs-mark">$1</mark>',
    );
}

function setFilter(key: string | null): void {
    activeFilter.value = key;
    activeIndex.value = 0;
}

function actionLabel(action: ResultAction): string {
    if (action === 'edit') {
        return t('Edit');
    }
    if (action === 'create') {
        return t('Create');
    }
    if (action === 'go') {
        return t('Go');
    }
    if (action === 'recent') {
        return t('Recent');
    }

    return t('View');
}

function isActiveResult(result: SearchResult): boolean {
    const active = flatResults.value[activeIndex.value];

    return active?.id === result.id && active?.resource === result.resource;
}

watch(open, async (isOpen) => {
    if (isOpen) {
        loadRecent();
        activeIndex.value = 0;
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
    activeIndex.value = 0;

    if (trimmed.length === 0) {
        groups.value = [];
        error.value = null;
        loading.value = false;
        abortController?.abort();
        return;
    }

    if (trimmed.length < 2 && !/^\d+$/.test(trimmed)) {
        groups.value = [];
        error.value = null;
        loading.value = false;
        abortController?.abort();
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
    pushRecent({
        title: result.title,
        subtitle: result.subtitle || undefined,
        url: result.url,
        resource: result.resource,
    });
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
    if (!(event.metaKey || event.ctrlKey)) {
        return;
    }

    if (event.key === '/' || event.key === 'k' || event.key === 'K') {
        event.preventDefault();
        open.value = !open.value;
    }
}

onMounted(() => {
    if (/Mac|iPhone|iPad/i.test(navigator.platform)) {
        shortcutLabel.value = '⌘K';
    }
    loadRecent();
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
    <button
        type="button"
        class="gs-trigger gs-trigger-pill"
        data-tour="global-search"
        data-search-variant="pill"
        :aria-label="t('Search')"
        @click="openSearch"
    >
        <Search class="gs-trigger-icon" :stroke-width="1.75" />
        <span class="gs-trigger-label">{{ t('Search') }}</span>
        <kbd class="gs-trigger-kbd">{{ shortcutLabel }}</kbd>
    </button>

    <button
        type="button"
        class="gs-trigger gs-trigger-icon-only"
        data-tour="global-search"
        data-search-variant="icon"
        :aria-label="t('Search')"
        @click="openSearch"
    >
        <Search class="gs-trigger-icon" :stroke-width="1.75" />
    </button>

    <Dialog v-model:open="open">
        <DialogContent
            data-global-search
            class="gs-dialog gap-0 overflow-hidden border-0 bg-transparent p-0 shadow-none sm:max-w-[34rem] sm:rounded-[28px] sm:p-0"
            :show-close-button="false"
        >
            <DialogHeader class="sr-only">
                <DialogTitle>{{ t('Search') }}</DialogTitle>
                <DialogDescription>
                    {{ t('Search by name, code, phone, or related records.') }}
                </DialogDescription>
            </DialogHeader>

            <div class="gs-panel">
                <div class="gs-glow" aria-hidden="true" />
                <div class="gs-input-row">
                    <div class="gs-input-shell">
                        <Search class="gs-input-icon" :stroke-width="1.75" />
                        <input
                            ref="inputRef"
                            v-model="query"
                            type="search"
                            class="gs-input"
                            :placeholder="t('Name, code, phone, sale no…')"
                            autocomplete="off"
                            @keydown="onKeyDown"
                        />
                        <button
                            v-if="query"
                            type="button"
                            class="gs-clear"
                            :aria-label="t('Clear')"
                            @click="query = ''"
                        >
                            <X class="size-3.5" />
                        </button>
                        <LoaderCircle
                            v-if="loading"
                            class="gs-spinner size-4 shrink-0 animate-spin"
                        />
                        <kbd class="gs-esc">esc</kbd>
                    </div>
                </div>

                <div
                    v-if="!isEmptyQuery && groups.length > 1"
                    class="gs-filters"
                >
                    <button
                        type="button"
                        class="gs-chip"
                        :class="{ active: !activeFilter }"
                        @click="setFilter(null)"
                    >
                        {{ t('All') }} · {{ resultCount }}
                    </button>
                    <button
                        v-for="group in groups"
                        :key="group.key"
                        type="button"
                        class="gs-chip"
                        :class="{ active: activeFilter === group.key }"
                        @click="setFilter(group.key)"
                    >
                        {{ t(group.label) }} · {{ group.results.length }}
                    </button>
                </div>

                <div class="gs-results">
                    <p v-if="error" class="gs-empty gs-empty-error">
                        {{ error }}
                    </p>

                    <p
                        v-else-if="
                            query.trim().length > 0 &&
                            query.trim().length < 2 &&
                            !/^\d+$/.test(query.trim())
                        "
                        class="gs-empty"
                    >
                        {{ t('Type at least 2 characters.') }}
                    </p>

                    <p
                        v-else-if="
                            !loading &&
                            !isEmptyQuery &&
                            flatResults.length === 0 &&
                            (query.trim().length >= 2 ||
                                /^\d+$/.test(query.trim()))
                        "
                        class="gs-empty"
                    >
                        {{ t('No results found.') }}
                    </p>

                    <div
                        v-else-if="isEmptyQuery && displayGroups.length === 0"
                        class="gs-empty"
                    >
                        <p>
                            {{
                                t(
                                    'Search by name, code, phone, or related records.',
                                )
                            }}
                        </p>
                    </div>

                    <div v-else class="gs-groups">
                        <div
                            v-for="group in displayGroups"
                            :key="group.key"
                            class="gs-group"
                        >
                            <p class="gs-group-label">
                                {{ t(group.label) }}
                            </p>
                            <button
                                v-for="result in group.results"
                                :key="`${result.resource}-${result.id}`"
                                type="button"
                                class="gs-result"
                                :class="{
                                    active: isActiveResult(result),
                                }"
                                @mouseenter="
                                    activeIndex = flatResults.findIndex(
                                        (item) =>
                                            item.id === result.id &&
                                            item.resource === result.resource,
                                    )
                                "
                                @click="goTo(result)"
                            >
                                <div class="gs-result-text">
                                    <p
                                        class="gs-result-title"
                                        v-html="highlight(result.title)"
                                    />
                                    <p
                                        v-if="result.subtitle"
                                        class="gs-result-subtitle"
                                        v-html="highlight(result.subtitle)"
                                    />
                                </div>
                                <span class="gs-result-action">
                                    {{ actionLabel(result.action) }}
                                </span>
                                <CornerDownLeft
                                    class="gs-result-enter"
                                    :stroke-width="1.75"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="gs-footer">
                    <span>
                        <kbd>↑</kbd><kbd>↓</kbd>
                        {{ t('Navigate') }}
                    </span>
                    <span>
                        <kbd>↵</kbd>
                        {{ t('Open') }}
                    </span>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.gs-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgb(255 255 255 / 55%);
    background: rgb(255 255 255 / 55%);
    color: #3e454b;
    cursor: pointer;
    box-shadow: 0 10px 28px rgb(40 45 50 / 4%);
    backdrop-filter: blur(18px);
    transition:
        background 0.15s ease,
        transform 0.15s ease,
        color 0.15s ease;
}

.gs-trigger:hover {
    background: white;
    transform: translateY(-1px);
}

.gs-trigger:focus-visible {
    outline: 2px solid color-mix(in srgb, var(--brand-accent, var(--primary)) 45%, transparent);
    outline-offset: 2px;
}

.gs-trigger-pill {
    display: none;
    gap: 8px;
    height: 39px;
    padding: 0 14px 0 12px;
    border-radius: 999px;
}

.gs-trigger-icon-only {
    width: 39px;
    height: 39px;
    border-radius: 50%;
}

.gs-trigger-icon {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
    opacity: 0.85;
}

.gs-trigger-label {
    font-size: 12px;
    font-weight: 550;
    letter-spacing: -0.1px;
    color: #2e3031;
}

.gs-trigger-kbd {
    display: none;
    margin-inline-start: 2px;
    padding: 2px 7px;
    border-radius: 999px;
    border: 1px solid rgb(0 0 0 / 6%);
    background: rgb(255 255 255 / 72%);
    color: #6a737a;
    font-family: inherit;
    font-size: 10px;
    font-weight: 600;
    line-height: 1.2;
}

@media (min-width: 1024px) {
    .gs-trigger-kbd {
        display: inline-flex;
    }
}

@media (min-width: 640px) {
    .gs-trigger-pill {
        display: inline-flex;
    }

    .gs-trigger-icon-only {
        display: none;
    }
}

@media (max-width: 639px) {
    .gs-trigger-pill {
        display: none;
    }
}
</style>

<style>
/* Portal styles — dialog renders outside the component tree */
[data-slot='dialog-overlay']:has(+ [data-global-search]) {
    background:
        radial-gradient(
            42rem 28rem at 50% 42%,
            rgba(255, 138, 58, 0.12),
            transparent 70%
        ),
        rgb(20 24 26 / 32%) !important;
    backdrop-filter: blur(10px) saturate(120%);
    -webkit-backdrop-filter: blur(10px) saturate(120%);
}

[data-global-search][data-slot='dialog-content'] {
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    padding: 0 !important;
    gap: 0 !important;
    overflow: visible !important;
}

[data-global-search][data-slot='dialog-content'] > div.mx-auto.mb-1 {
    display: none;
}

[data-global-search] .gs-panel {
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    isolation: isolate;
    border-radius: 22px 22px 0 0;
    border: 1px solid rgba(255, 255, 255, 0.72);
    background:
        linear-gradient(
            165deg,
            rgba(255, 255, 255, 0.78) 0%,
            rgba(255, 255, 255, 0.48) 52%,
            rgba(255, 250, 245, 0.42) 100%
        );
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.95) inset,
        0 28px 80px -20px rgba(26, 26, 26, 0.28),
        0 12px 32px rgba(26, 26, 26, 0.08);
    backdrop-filter: blur(28px) saturate(150%);
    -webkit-backdrop-filter: blur(28px) saturate(150%);
    font-family:
        var(--font-sans), 'Plus Jakarta Sans', 'Helvetica Neue', sans-serif;
    color: #1a1a1a;
}

[data-global-search] .gs-glow {
    pointer-events: none;
    position: absolute;
    z-index: 0;
    left: 50%;
    bottom: -18%;
    width: 70%;
    height: 48%;
    transform: translateX(-50%);
    border-radius: 50%;
    background: radial-gradient(
        closest-side,
        rgba(255, 138, 58, 0.28),
        rgba(255, 180, 100, 0.08) 55%,
        transparent 75%
    );
    filter: blur(8px);
}

[data-global-search] .gs-panel > *:not(.gs-glow) {
    position: relative;
    z-index: 1;
}

@media (min-width: 640px) {
    [data-global-search] .gs-panel {
        border-radius: 26px;
    }
}

[data-global-search] .gs-input-row {
    padding: 14px 14px 12px;
    border-bottom: 1px solid rgba(26, 26, 26, 0.06);
}

[data-global-search] .gs-input-shell {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px 10px 4px 12px;
    border-radius: 16px;
    border: 1px solid rgba(255, 170, 140, 0.45);
    background: rgba(255, 255, 255, 0.72);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.95) inset,
        0 8px 20px rgba(26, 26, 26, 0.04);
}

[data-global-search] .gs-input-icon {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
    color: #98a2b3;
}

[data-global-search] .gs-input {
    width: 100%;
    height: 38px;
    border: 0;
    background: transparent;
    color: #1a1a1a;
    font-size: 14.5px;
    font-weight: 500;
    letter-spacing: -0.2px;
    outline: none;
}

[data-global-search] .gs-input::placeholder {
    color: #98a2b3;
    font-weight: 450;
}

[data-global-search] .gs-input::-webkit-search-cancel-button {
    display: none;
}

[data-global-search] .gs-clear {
    display: grid;
    place-items: center;
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    border: 0;
    border-radius: 50%;
    background: rgba(26, 26, 26, 0.05);
    color: #667085;
    cursor: pointer;
    transition:
        background 0.15s ease,
        color 0.15s ease;
}

[data-global-search] .gs-clear:hover {
    background: rgba(26, 26, 26, 0.1);
    color: #1a1a1a;
}

[data-global-search] .gs-spinner {
    color: #98a2b3;
}

[data-global-search] .gs-esc {
    display: none;
    padding: 4px 8px;
    border-radius: 8px;
    border: 1px solid rgba(26, 26, 26, 0.08);
    background: rgba(255, 255, 255, 0.85);
    color: #667085;
    font-family: inherit;
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.02em;
    text-transform: lowercase;
}

@media (min-width: 640px) {
    [data-global-search] .gs-esc {
        display: inline-flex;
    }
}

[data-global-search] .gs-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 10px 14px;
    border-bottom: 1px solid rgba(26, 26, 26, 0.05);
}

[data-global-search] .gs-chip {
    height: 28px;
    padding: 0 12px;
    border: 1px solid transparent;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.55);
    color: #667085;
    font-size: 11px;
    font-weight: 550;
    cursor: pointer;
    transition:
        background 0.15s ease,
        color 0.15s ease,
        box-shadow 0.15s ease;
}

[data-global-search] .gs-chip:hover:not(.active) {
    background: rgba(255, 255, 255, 0.88);
}

[data-global-search] .gs-chip.active {
    background: #1a1a1a;
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(26, 26, 26, 0.2);
}

[data-global-search] .gs-results {
    max-height: min(26rem, 58vh);
    overflow-y: auto;
    overscroll-behavior: contain;
    padding: 8px 10px 10px;
    scrollbar-width: thin;
}

[data-global-search] .gs-empty {
    padding: 2.5rem 1rem;
    text-align: center;
    color: #667085;
    font-size: 13px;
    font-weight: 450;
    line-height: 1.45;
}

[data-global-search] .gs-empty-error {
    color: #e51f56;
}

[data-global-search] .gs-groups {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

[data-global-search] .gs-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

[data-global-search] .gs-group-label {
    padding: 6px 12px 4px;
    color: #98a2b3;
    font-size: 10px;
    font-weight: 650;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

[data-global-search] .gs-result {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    min-height: 42px;
    padding: 9px 12px;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #1a1a1a;
    text-align: start;
    cursor: pointer;
    transition:
        background 0.12s ease,
        color 0.12s ease,
        box-shadow 0.12s ease,
        transform 0.12s ease;
}

[data-global-search] .gs-result:hover {
    background: rgba(255, 255, 255, 0.62);
}

[data-global-search] .gs-result.active {
    background: #1a1a1a;
    color: #ffffff;
    box-shadow: 0 12px 28px rgba(26, 26, 26, 0.22);
}

[data-global-search] .gs-result-text {
    min-width: 0;
    flex: 1;
}

[data-global-search] .gs-result-title {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 13px;
    font-weight: 550;
    letter-spacing: -0.15px;
}

[data-global-search] .gs-result-subtitle {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 2px;
    color: #667085;
    font-size: 11px;
    font-weight: 450;
}

[data-global-search] .gs-result.active .gs-result-subtitle {
    color: rgba(255, 255, 255, 0.62);
}

[data-global-search] .gs-mark {
    border-radius: 4px;
    background: rgba(255, 138, 58, 0.22);
    padding: 0 2px;
    color: inherit;
}

[data-global-search] .gs-result.active .gs-mark {
    background: rgba(255, 138, 58, 0.4);
}

[data-global-search] .gs-result-action {
    flex-shrink: 0;
    padding: 3px 8px;
    border-radius: 999px;
    border: 1px solid rgba(26, 26, 26, 0.06);
    background: rgba(255, 255, 255, 0.7);
    color: #667085;
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

[data-global-search] .gs-result.active .gs-result-action {
    border-color: rgba(255, 255, 255, 0.14);
    background: rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.78);
}

[data-global-search] .gs-result-enter {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    color: #98a2b3;
    opacity: 0;
    transition: opacity 0.12s ease;
}

[data-global-search] .gs-result.active .gs-result-enter {
    color: rgba(255, 255, 255, 0.55);
    opacity: 1;
}

[data-global-search] .gs-footer {
    display: none;
    align-items: center;
    gap: 18px;
    padding: 11px 16px 13px;
    border-top: 1px solid rgba(26, 26, 26, 0.06);
    background: rgba(255, 255, 255, 0.28);
    color: #98a2b3;
    font-size: 11px;
    font-weight: 500;
}

[data-global-search] .gs-footer span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

[data-global-search] .gs-footer kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 8px;
    border: 1px solid rgba(26, 26, 26, 0.08);
    background: rgba(255, 255, 255, 0.82);
    color: #667085;
    font-family: inherit;
    font-size: 10px;
    font-weight: 650;
    box-shadow: 0 1px 0 rgba(255, 255, 255, 0.9) inset;
}

@media (min-width: 640px) {
    [data-global-search] .gs-footer {
        display: flex;
    }
}
</style>
