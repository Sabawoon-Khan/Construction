export function translateStatus(
    status: string | null | undefined,
    t: (key: string) => string,
): string {
    const raw = String(status || '').trim();

    if (!raw || raw === '—') {
        return '—';
    }

    const spaced = raw.replaceAll('_', ' ');
    const lower = spaced.toLowerCase();
    const sentence = lower.charAt(0).toUpperCase() + lower.slice(1);
    const titled = lower.replace(/\b\w/g, (char) => char.toUpperCase());

    for (const key of [lower, sentence, titled, raw]) {
        const translated = t(key);
        if (translated !== key) {
            return translated;
        }
    }

    return sentence;
}
