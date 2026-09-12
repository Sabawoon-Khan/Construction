import { driver, type DriveStep } from 'driver.js';
import 'driver.js/dist/driver.css';
import { useTranslations } from '@/composables/useTranslations';

const TOUR_STORAGE_KEY = 'kit.tour.admin-shell.dismissed';

export function useProductTour() {
    const { t } = useTranslations();

    function hasDismissed(): boolean {
        try {
            return localStorage.getItem(TOUR_STORAGE_KEY) === '1';
        } catch {
            return true;
        }
    }

    function dismiss(): void {
        try {
            localStorage.setItem(TOUR_STORAGE_KEY, '1');
        } catch {
            // ignore
        }
    }

    function startAdminShellTour(force = false): void {
        if (!force && hasDismissed()) {
            return;
        }

        const candidates: DriveStep[] = [
            {
                element: '[data-tour="app-sidebar"]',
                popover: {
                    title: t('Navigation'),
                    description: t(
                        'Use the sidebar to move between dashboard, modules, and administration.',
                    ),
                    side: 'right',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="global-search"]',
                popover: {
                    title: t('Search'),
                    description: t(
                        'Find projects, people, expenses, and more — or press Ctrl+K / ⌘K.',
                    ),
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                element: '[data-tour="create-primary"]',
                popover: {
                    title: t('Create'),
                    description: t(
                        'Use Create on list pages to add a new record. Field reports also work offline.',
                    ),
                    side: 'bottom',
                    align: 'end',
                },
            },
        ];

        const steps = candidates.filter((step) => {
            if (typeof step.element !== 'string') {
                return true;
            }

            return Boolean(document.querySelector(step.element));
        });

        if (steps.length === 0) {
            return;
        }

        const prefersReducedMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        const tour = driver({
            showProgress: true,
            animate: !prefersReducedMotion,
            allowClose: true,
            nextBtnText: t('Next'),
            prevBtnText: t('Back'),
            doneBtnText: t('Done'),
            onDestroyed: () => dismiss(),
            steps,
        });

        tour.drive();
    }

    return {
        startAdminShellTour,
        hasDismissed,
        dismiss,
    };
}
