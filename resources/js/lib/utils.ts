import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

const CURRENCY_ALIASES: Record<string, string> = { AFG: 'AFN' };

export function resolveCurrency(code?: string | null, fallback?: string | null): string {
    const raw = String(code || fallback || pageBaseCurrency())
        .trim()
        .toUpperCase();
    const mapped = CURRENCY_ALIASES[raw] ?? raw;

    return /^[A-Z]{3}$/.test(mapped) ? mapped : pageBaseCurrency();
}

function pageBaseCurrency(): string {
    try {
        const base = (usePage().props.company as { base_currency?: string } | undefined)?.base_currency;
        const raw = String(base || 'USD').trim().toUpperCase();
        const mapped = CURRENCY_ALIASES[raw] ?? raw;

        return /^[A-Z]{3}$/.test(mapped) ? mapped : 'USD';
    } catch {
        return 'USD';
    }
}

export function money(value: unknown, currency?: string | null) {
    const code = resolveCurrency(currency);

    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: code,
            maximumFractionDigits: 2,
        }).format(Number(value ?? 0));
    } catch {
        return `${code} ${Number(value ?? 0).toFixed(2)}`;
    }
}

export function moneyByCurrency(
    amounts?: Record<string, number> | null,
    fallbackValue?: unknown,
): string {
    const parts = Object.entries(amounts ?? {})
        .map(([code, amount]) => [resolveCurrency(code), Number(amount ?? 0)] as const)
        .filter(([, amount]) => Number.isFinite(amount) && amount !== 0)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([code, amount]) => money(amount, code));

    if (parts.length) {
        return parts.join(' · ');
    }

    return money(fallbackValue ?? 0);
}

export function formatNumber(value: unknown, digits = 2) {
    return new Intl.NumberFormat('en-US', {
        maximumFractionDigits: digits,
    }).format(Number(value ?? 0));
}

export function moneyBase(value: unknown, baseCurrency?: string | null) {
    return money(value, baseCurrency);
}

export function formatDual(
    original: unknown,
    base: unknown,
    originalCurrency: string,
    baseCurrency: string,
): string {
    return `${money(original, originalCurrency)} (${moneyBase(base, baseCurrency)})`;
}
