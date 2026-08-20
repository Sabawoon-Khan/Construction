<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ImagePlus, Star, Trash2, Video } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { MisFormPage } from '@/components/mis';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import { historyBack } from '@/lib/historyBack';

type MediaItem = {
    id: number;
    type: 'image' | 'video';
    url: string;
    is_cover?: boolean;
    original_filename?: string;
};

const props = defineProps<{
    item: Record<string, any> | null;
}>();

const isEdit = computed(() => !!props.item);
const { t } = useTranslations();

const form = useForm({
    title: props.item?.title ?? '',
    slug: props.item?.slug ?? '',
    summary: props.item?.summary ?? '',
    description: props.item?.description ?? '',
    location: props.item?.location ?? '',
    category: props.item?.category ?? '',
    client_name: props.item?.client_name ?? '',
    scope: props.item?.scope ?? '',
    area_size: props.item?.area_size ?? '',
    duration: props.item?.duration ?? '',
    total_apartments: props.item?.total_apartments ?? '',
    floors: props.item?.floors ?? '',
    plot_size: props.item?.plot_size ?? '',
    parking_spaces: props.item?.parking_spaces ?? '',
    project_status: props.item?.project_status ?? '',
    year: props.item?.year ?? '',
    completed_on: (props.item?.completed_on ?? '').toString().slice(0, 10),
    is_published: props.item?.is_published ?? false,
    sort_order: props.item?.sort_order ?? 0,
    cover: null as File | null,
    media: [] as File[],
    delete_media_ids: [] as number[],
    cover_media_id: null as number | null,
});

const existingMedia = ref<MediaItem[]>(
    (props.item?.media ?? []).map((m: MediaItem) => ({ ...m })),
);

const coverPreview = ref<string | null>(null);
const galleryPreviews = ref<{ file: File; url: string }[]>([]);

const accept = 'image/jpeg,image/png,image/webp,video/mp4,video/webm';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Portfolio', href: '/portfolio' },
            { title: 'Form', href: '/portfolio/create' },
        ],
    },
});

function onCoverChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    form.cover = file;
    if (coverPreview.value) {
        URL.revokeObjectURL(coverPreview.value);
    }
    coverPreview.value = file ? URL.createObjectURL(file) : null;
}

function onGalleryChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    form.media = [...form.media, ...files];
    for (const file of files) {
        galleryPreviews.value.push({ file, url: URL.createObjectURL(file) });
    }
    input.value = '';
}

function removePendingGallery(index: number) {
    const [removed] = galleryPreviews.value.splice(index, 1);
    if (removed) {
        URL.revokeObjectURL(removed.url);
    }
    form.media = galleryPreviews.value.map((entry) => entry.file);
}

function markExistingRemoved(id: number) {
    if (!form.delete_media_ids.includes(id)) {
        form.delete_media_ids.push(id);
    }
    existingMedia.value = existingMedia.value.filter((m) => m.id !== id);
    if (form.cover_media_id === id) {
        form.cover_media_id = null;
    }
}

function setCover(id: number) {
    form.cover_media_id = id;
    existingMedia.value = existingMedia.value.map((m) => ({
        ...m,
        is_cover: m.id === id,
    }));
}

function submit() {
    const options = { forceFormData: true as const };
    if (isEdit.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/portfolio/${props.item!.id}`, options);
    } else {
        form.post('/portfolio', options);
    }
}
</script>

<template>
    <Head :title="isEdit ? t('Edit portfolio project') : t('Create portfolio project')" />

    <MisFormPage
        :title="isEdit ? t('Edit portfolio project') : t('Create portfolio project')" :back-href="isEdit ? '/portfolio' : undefined"
    >
        <form class="mis-form-card" @submit.prevent="submit">
            <div class="mis-form-grid">
                <div class="grid gap-2">
                    <Label for="title">{{ t('Title') }}</Label>
                    <Input id="title" v-model="form.title" type="text" required />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="grid gap-2">
                    <Label for="slug">{{ t('Slug') }}</Label>
                    <Input id="slug" v-model="form.slug" type="text" :placeholder="t('Slug')" />
                    <InputError :message="form.errors.slug" />
                </div>
                <div class="grid gap-2 mis-form-span">
                    <Label for="summary">{{ t('Summary') }}</Label>
                    <textarea
                        id="summary"
                        v-model="form.summary"
                        rows="3"
                        class="mis-form-select min-h-[5.5rem] resize-y"
                    />
                    <InputError :message="form.errors.summary" />
                </div>
                <div class="grid gap-2 mis-form-span">
                    <Label for="description">{{ t('Description') }}</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="8"
                        class="mis-form-select min-h-[10rem] resize-y"
                    />
                    <InputError :message="form.errors.description" />
                </div>
                <div class="grid gap-2">
                    <Label for="category">{{ t('Category') }}</Label>
                    <Input id="category" v-model="form.category" type="text" />
                    <InputError :message="form.errors.category" />
                </div>
                <div class="grid gap-2">
                    <Label for="location">{{ t('Location') }}</Label>
                    <Input id="location" v-model="form.location" type="text" />
                    <InputError :message="form.errors.location" />
                </div>
                <div class="grid gap-2">
                    <Label for="client_name">{{ t('Client') }}</Label>
                    <Input id="client_name" v-model="form.client_name" type="text" />
                    <InputError :message="form.errors.client_name" />
                </div>
                <div class="grid gap-2">
                    <Label for="scope">{{ t('Scope of work') }}</Label>
                    <Input id="scope" v-model="form.scope" type="text" />
                    <InputError :message="form.errors.scope" />
                </div>
                <div class="grid gap-2">
                    <Label for="area_size">{{ t('Built-up area') }}</Label>
                    <Input id="area_size" v-model="form.area_size" type="text" :placeholder="t('e.g. 12,500 m²')" />
                    <InputError :message="form.errors.area_size" />
                </div>
                <div class="grid gap-2">
                    <Label for="plot_size">{{ t('Plot size') }}</Label>
                    <Input id="plot_size" v-model="form.plot_size" type="text" :placeholder="t('e.g. 2,000 m²')" />
                    <InputError :message="form.errors.plot_size" />
                </div>
                <div class="grid gap-2">
                    <Label for="total_apartments">{{ t('Total apartments') }}</Label>
                    <Input id="total_apartments" v-model="form.total_apartments" type="number" min="0" />
                    <InputError :message="form.errors.total_apartments" />
                </div>
                <div class="grid gap-2">
                    <Label for="floors">{{ t('Floors') }}</Label>
                    <Input id="floors" v-model="form.floors" type="number" min="0" />
                    <InputError :message="form.errors.floors" />
                </div>
                <div class="grid gap-2">
                    <Label for="parking_spaces">{{ t('Parking spaces') }}</Label>
                    <Input id="parking_spaces" v-model="form.parking_spaces" type="number" min="0" />
                    <InputError :message="form.errors.parking_spaces" />
                </div>
                <div class="grid gap-2">
                    <Label for="duration">{{ t('Duration') }}</Label>
                    <Input id="duration" v-model="form.duration" type="text" :placeholder="t('e.g. 24 months')" />
                    <InputError :message="form.errors.duration" />
                </div>
                <div class="grid gap-2">
                    <Label for="project_status">{{ t('Project status') }}</Label>
                    <select
                        id="project_status"
                        v-model="form.project_status"
                        class="mis-form-select"
                    >
                        <option value="">{{ t('Select status') }}</option>
                        <option value="completed">{{ t('Completed') }}</option>
                        <option value="ongoing">{{ t('Ongoing') }}</option>
                        <option value="planned">{{ t('Planned') }}</option>
                    </select>
                    <InputError :message="form.errors.project_status" />
                </div>
                <div class="grid gap-2">
                    <Label for="year">{{ t('Year') }}</Label>
                    <Input id="year" v-model="form.year" type="number" min="1950" max="2100" />
                    <InputError :message="form.errors.year" />
                </div>
                <div class="grid gap-2">
                    <Label for="completed_on">{{ t('Completed on') }}</Label>
                    <Input id="completed_on" v-model="form.completed_on" type="date" />
                    <InputError :message="form.errors.completed_on" />
                </div>
                <div class="grid gap-2">
                    <Label for="sort_order">{{ t('Sort order') }}</Label>
                    <Input id="sort_order" v-model.number="form.sort_order" type="number" min="0" />
                    <InputError :message="form.errors.sort_order" />
                </div>
                <div class="flex items-center gap-2 mis-form-span">
                    <input
                        id="is_published"
                        v-model="form.is_published"
                        type="checkbox"
                        class="size-4"
                    />
                    <Label for="is_published">{{ t('Published') }}</Label>
                </div>
            </div>

            <div class="mt-6 grid gap-4">
                <div class="grid gap-2">
                    <Label for="cover">{{ t('Cover image or video') }}</Label>
                    <Input
                        id="cover"
                        type="file"
                        :accept="accept"
                        @change="onCoverChange"
                    />
                    <InputError :message="form.errors.cover" />
                    <div
                        v-if="coverPreview"
                        class="mt-2 overflow-hidden rounded-xl border border-border/80 bg-muted/30"
                    >
                        <video
                            v-if="form.cover?.type.startsWith('video/')"
                            :src="coverPreview"
                            class="max-h-56 w-full object-cover"
                            controls
                            muted
                        />
                        <img
                            v-else
                            :src="coverPreview"
                            alt=""
                            class="max-h-56 w-full object-cover"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="media">{{ t('Gallery media') }}</Label>
                    <Input
                        id="media"
                        type="file"
                        multiple
                        :accept="accept"
                        @change="onGalleryChange"
                    />
                    <InputError :message="form.errors.media" />
                    <InputError :message="(form.errors as any)['media.0']" />

                    <div
                        v-if="existingMedia.length || galleryPreviews.length"
                        class="mt-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="media in existingMedia"
                            :key="media.id"
                            class="relative overflow-hidden rounded-xl border border-border/80 bg-muted/20"
                        >
                            <video
                                v-if="media.type === 'video'"
                                :src="media.url"
                                class="aspect-video w-full object-cover"
                                muted
                                playsinline
                                controls
                            />
                            <img
                                v-else
                                :src="media.url"
                                :alt="media.original_filename || ''"
                                class="aspect-video w-full object-cover"
                            />
                            <div class="flex items-center justify-between gap-2 p-2">
                                <span class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                    <Video v-if="media.type === 'video'" class="size-3.5" />
                                    <ImagePlus v-else class="size-3.5" />
                                    {{ media.is_cover ? t('Cover image or video') : t('Media') }}
                                </span>
                                <div class="flex gap-1">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="secondary"
                                        :disabled="media.is_cover"
                                        @click="setCover(media.id)"
                                    >
                                        <Star class="size-3.5" />
                                        {{ t('Set as cover') }}
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        @click="markExistingRemoved(media.id)"
                                    >
                                        <Trash2 class="size-3.5" />
                                        {{ t('Remove media') }}
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-for="(preview, index) in galleryPreviews"
                            :key="preview.url"
                            class="relative overflow-hidden rounded-xl border border-dashed border-border bg-muted/10"
                        >
                            <video
                                v-if="preview.file.type.startsWith('video/')"
                                :src="preview.url"
                                class="aspect-video w-full object-cover"
                                muted
                                playsinline
                                controls
                            />
                            <img
                                v-else
                                :src="preview.url"
                                alt=""
                                class="aspect-video w-full object-cover"
                            />
                            <div class="flex justify-end p-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="destructive"
                                    @click="removePendingGallery(index)"
                                >
                                    <Trash2 class="size-3.5" />
                                    {{ t('Remove media') }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mis-form-actions">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? t('Saving…') : t('Save') }}
                </Button>
                <Button v-if="!isEdit" type="button" variant="secondary" @click="historyBack">{{ t('Cancel') }}</Button>
                <Button v-else as-child variant="secondary">
                    <Link href="/portfolio">{{ t('Cancel') }}</Link>
                </Button>
                <Link
                    v-if="isEdit"
                    :href="`/portfolio/${item!.id}`"
                    method="delete"
                    as="button"
                    class="ml-auto inline-flex h-9 items-center justify-center gap-2 rounded-md bg-destructive px-4 text-sm font-medium text-white hover:bg-destructive/90"
                >
                    <Trash2 class="size-4" />
                    {{ t('Delete') }}
                </Link>
            </div>
        </form>
    </MisFormPage>
</template>
