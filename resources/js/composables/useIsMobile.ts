import { useMediaQuery } from '@vueuse/core';

/** Matches Tailwind `md` (min-width: 768px) — true below that. */
export function useIsMobile() {
    return useMediaQuery('(max-width: 767px)');
}
