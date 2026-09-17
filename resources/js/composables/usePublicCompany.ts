import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type PublicCompany = {
    name?: string | null;
    name_primary?: string | null;
    name_en?: string | null;
    phone?: string | null;
    email?: string | null;
    address?: string | null;
};

export function usePublicCompany() {
    const page = usePage();
    const publicCompany = computed(
        () => (page.props.publicCompany as PublicCompany | null) ?? null,
    );
    const locale = computed(() => (page.props.locale as string) || 'en');
    const isEnglish = computed(() => locale.value === 'en');
    const sharedName = computed(
        () => (page.props.name as string | undefined)?.trim() || '',
    );
    const brandEn = computed(
        () => publicCompany.value?.name_en?.trim() || sharedName.value,
    );
    const brandFa = computed(
        () => publicCompany.value?.name_primary?.trim() || sharedName.value,
    );
    const brandPrimary = computed(() =>
        isEnglish.value ? brandEn.value : brandFa.value,
    );
    const brandSecondary = computed(() =>
        isEnglish.value ? null : brandEn.value || null,
    );
    const contactEmail = computed(
        () => publicCompany.value?.email?.trim() || '',
    );
    const contactPhone = computed(
        () => publicCompany.value?.phone?.trim() || '',
    );
    const contactAddress = computed(
        () => publicCompany.value?.address?.trim() || '',
    );

    return {
        publicCompany,
        locale,
        isEnglish,
        brandEn,
        brandFa,
        brandPrimary,
        brandSecondary,
        contactEmail,
        contactPhone,
        contactAddress,
    };
}
