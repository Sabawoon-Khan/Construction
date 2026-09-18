<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, Mail, MapPin, Phone } from '@lucide/vue';
import { usePublicCompany } from '@/composables/usePublicCompany';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
const {
    brandPrimary,
    brandSecondary,
    contactEmail,
    contactPhone,
    contactAddress,
} = usePublicCompany();
const year = new Date().getFullYear();

function lt(key: string, fallback: string): string {
    const value = t(key);
    return value === key ? fallback : value;
}
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
                <p v-if="contactAddress" class="construct-footer-line">
                    <MapPin :size="14" />
                    <span>{{ contactAddress }}</span>
                </p>
                <a
                    v-if="contactEmail"
                    class="construct-footer-line"
                    :href="`mailto:${contactEmail}`"
                >
                    <Mail :size="14" />
                    <span>{{ contactEmail }}</span>
                </a>
                <a
                    v-if="contactPhone"
                    class="construct-footer-line"
                    :href="`tel:${contactPhone.replace(/\s+/g, '')}`"
                >
                    <Phone :size="14" />
                    <span>{{ contactPhone }}</span>
                </a>
            </div>
        </div>
        <div class="construct-container construct-footer-copy">
            <span>© {{ year }} {{ brandPrimary }}</span>
            <span>{{ lt('home.footer.rights', 'All rights reserved.') }}</span>
        </div>
    </footer>
</template>
