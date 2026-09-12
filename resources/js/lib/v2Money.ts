export function compactMoney(value: unknown, currency?: string | null): string {
    const amount = Number(value ?? 0);
    const code = currency || 'USD';
    const formatted = new Intl.NumberFormat('en-US', {
        notation: 'compact',
        compactDisplay: 'short',
        maximumFractionDigits: Math.abs(amount) >= 1000 ? 1 : 0,
    }).format(amount);

    return `${code} ${formatted}`;
}

export function compactMoneyByCurrency(
    amounts?: Record<string, number> | null,
    fallbackValue?: unknown,
): string {
    const merged: Record<string, number> = { AFN: 0, USD: 0 };
    for (const [code, value] of Object.entries(amounts ?? {})) {
        const resolved =
            code.toUpperCase() === 'AFG' ? 'AFN' : code.toUpperCase();
        const amount = Number(value);
        if (!Number.isFinite(amount)) continue;
        if (resolved === 'AFN' || resolved === 'USD') {
            merged[resolved] = amount;
        }
    }

    if (
        amounts == null &&
        Number(fallbackValue ?? 0) !== 0 &&
        merged.AFN === 0 &&
        merged.USD === 0
    ) {
        return compactMoney(fallbackValue ?? 0);
    }

    return (['AFN', 'USD'] as const)
        .map((code) => compactMoney(merged[code], code))
        .join(' · ');
}

export type MoneyChartRow = {
    key: string;
    label: string;
    by_currency: { AFN: number; USD: number };
};

export function buildMoneyBars(rows?: MoneyChartRow[] | null) {
    const source =
        rows && rows.length > 0
            ? rows
            : Array.from({ length: 6 }, (_, index) => ({
                  key: `m-${index}`,
                  label: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'][index],
                  by_currency: { AFN: 0, USD: 0 },
              }));

    const maxAfn = Math.max(
        ...source.map((row) => Number(row.by_currency?.AFN) || 0),
        1,
    );
    const maxUsd = Math.max(
        ...source.map((row) => Number(row.by_currency?.USD) || 0),
        1,
    );

    return source.map((row) => {
        const afn = Number(row.by_currency?.AFN) || 0;
        const usd = Number(row.by_currency?.USD) || 0;
        return {
            key: row.key,
            label: row.label,
            by_currency: { AFN: afn, USD: usd },
            afnHeight: Math.max(afn > 0 ? 6 : 3, Math.round((afn / maxAfn) * 44)),
            usdHeight: Math.max(usd > 0 ? 6 : 3, Math.round((usd / maxUsd) * 44)),
            peak: (afn === maxAfn && afn > 0) || (usd === maxUsd && usd > 0),
        };
    });
}

export function buildShareSegments(
    parts: Array<{ key: string; label: string; value: number; color: string }>,
) {
    const total = Math.max(
        parts.reduce((sum, part) => sum + Number(part.value || 0), 0),
        0,
    );

    return {
        total,
        segments: parts.map((part) => {
            const value = Number(part.value || 0);
            return {
                ...part,
                value,
                width:
                    total > 0
                        ? Math.max(value > 0 ? 6 : 0, (value / total) * 100)
                        : 0,
            };
        }),
    };
}
