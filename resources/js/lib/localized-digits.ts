const FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
const AR_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

export function localizeDigits(value: string | number, locale: string): string {
    const str = String(value);

    if (locale === 'fa' || locale === 'ps' || locale === 'ur') {
        return str.replace(/\d/g, (d) => FA_DIGITS[Number(d)] ?? d);
    }

    if (locale === 'ar') {
        return str.replace(/\d/g, (d) => AR_DIGITS[Number(d)] ?? d);
    }

    return str;
}

export function normalizeDigits(value: string | number): string {
    return String(value)
        .replace(/[۰-۹]/g, (d) => String(FA_DIGITS.indexOf(d)))
        .replace(/[٠-٩]/g, (d) => String(AR_DIGITS.indexOf(d)));
}
