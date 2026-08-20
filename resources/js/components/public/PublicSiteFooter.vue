<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, Mail, MapPin, Phone } from '@lucide/vue';
import { computed } from 'vue';
import { useTranslations } from '@/composables/useTranslations';

type PublicCompany = {
    name?: string | null;
    name_primary?: string | null;
    name_en?: string | null;
    phone?: string | null;
    email?: string | null;
    address?: string | null;
};

const page = usePage();
const { t } = useTranslations();
const publicCompany = computed(
    () => (page.props.publicCompany as PublicCompany | null) ?? null,
);
const locale = computed(() => (page.props.locale as string) || 'en');
const isEnglish = computed(() => locale.value === 'en');
const brandEn = computed(
    () =>
        publicCompany.value?.name_en?.trim() ||
        (isEnglish.value ? (page.props.name as string | undefined) : null) ||
        'Shahin Samim Barakzai',
);
const brandFa = computed(
    () =>
        publicCompany.value?.name_primary?.trim() ||
        (!isEnglish.value ? (page.props.name as string | undefined) : null) ||
        'شاهین صمیم بارکزی',
);
const brandPrimary = computed(() =>
    isEnglish.value ? brandEn.value : brandFa.value,
);
const brandSecondary = computed(() =>
    isEnglish.value ? null : brandEn.value,
);
const year = new Date().getFullYear();

function lt(key: string, fallback: string): string {
    const value = t(key);
    return value === key ? fallback : value;
}

const contactEmail = computed(
    () =>
        publicCompany.value?.email?.trim() ||
        lt('home.contact.emailPlaceholder', 'info@shahinsamimbarakzai.com'),
);
const contactPhone = computed(
    () =>
        publicCompany.value?.phone?.trim() ||
        lt('home.contact.phonePlaceholder', '+93 70 000 0000'),
);
const contactAddress = computed(
    () =>
        publicCompany.value?.address?.trim() ||
        lt('home.contact.addressPlaceholder', 'Kabul, Afghanistan'),
);
const hasRealEmail = computed(() => !!publicCompany.value?.email?.trim());
const hasRealPhone = computed(() => !!publicCompany.value?.phone?.trim());
</script>

<template>
    <footer class="construct-footer">
        <div class="construct-container construct-footer-grid">
            <div class="construct-footer-brand">
                <Link href="/" class="construct-brand">
                    <span><Building2 :size="15" /></span>
                    <span class="construct-brand-stack">
                        <strong>{{ brandPrimary }}</strong>
                        <em v-if="brandSecondary">{{ brandSecondary }}</em>
                    </span>
                </Link>
                <p>
                    {{
                        lt(
                            'home.footer.blurb',
                            'Luxury residential developments crafted with disciplined delivery, refined finishes, and lasting quality.',
                        )
                    }}
                </p>
            </div>

            <div>
                <h3>{{ lt('home.footer.explore', 'Explore') }}</h3>
                <a href="/#about">{{ lt('home.nav.about', 'About') }}</a>
                <a href="/#services">{{ lt('home.nav.services', 'Services') }}</a>
                <Link href="/work">{{ lt('home.nav.projects', 'Projects') }}</Link>
                <a href="/#contact">{{ lt('home.nav.contact', 'Contact') }}</a>
            </div>

            <div>
                <h3>{{ lt('home.footer.contact', 'Contact') }}</h3>
                <p class="construct-footer-line">
                    <MapPin :size="14" />
                    <span>{{ contactAddress }}</span>
                </p>
                <a
                    v-if="hasRealEmail"
                    class="construct-footer-line"
                    :href="`mailto:${contactEmail}`"
                >
                    <Mail :size="14" />
                    <span>{{ contactEmail }}</span>
                </a>
                <p v-else class="construct-footer-line">
                    <Mail :size="14" />
                    <span>{{ contactEmail }}</span>
                </p>
                <a
                    v-if="hasRealPhone"
                    class="construct-footer-line"
                    :href="`tel:${contactPhone.replace(/\s+/g, '')}`"
                >
                    <Phone :size="14" />
                    <span>{{ contactPhone }}</span>
                </a>
                <p v-else class="construct-footer-line">
                    <Phone :size="14" />
                    <span>{{ contactPhone }}</span>
                </p>
            </div>
        </div>
        <div class="construct-container construct-footer-copy">
            <span>© {{ year }} {{ brandPrimary }}</span>
            <span>{{ lt('home.footer.rights', 'All rights reserved.') }}</span>
        </div>
    </footer>
</template>
