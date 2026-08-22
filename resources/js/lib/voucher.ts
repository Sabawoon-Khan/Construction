export type VoucherKind = 'receipt' | 'payment' | 'statement' | 'salary';

export type VoucherField = {
    label: string;
    value?: string | number | null;
};

export type VoucherLine = {
    description: string;
    amount?: number | null;
    meta?: string | null;
};

const ones = [
    '',
    'One',
    'Two',
    'Three',
    'Four',
    'Five',
    'Six',
    'Seven',
    'Eight',
    'Nine',
    'Ten',
    'Eleven',
    'Twelve',
    'Thirteen',
    'Fourteen',
    'Fifteen',
    'Sixteen',
    'Seventeen',
    'Eighteen',
    'Nineteen',
];
const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

function underThousand(n: number): string {
    if (n < 20) return ones[n];
    if (n < 100) {
        const unit = n % 10;
        return `${tens[Math.floor(n / 10)]}${unit ? `-${ones[unit]}` : ''}`;
    }
    const rest = n % 100;
    return `${ones[Math.floor(n / 100)]} Hundred${rest ? ` ${underThousand(rest)}` : ''}`;
}

/** Simple English amount-in-words for voucher footers. */
export function amountInWords(value: unknown, currency = 'USD'): string {
    const amount = Math.abs(Number(value ?? 0));
    if (!Number.isFinite(amount)) return '';

    const whole = Math.floor(amount);
    const cents = Math.round((amount - whole) * 100);

    if (whole === 0 && cents === 0) {
        return `Zero ${currency} Only`;
    }

    const chunks: string[] = [];
    let n = whole;
    const scales = [
        { v: 1_000_000_000, label: 'Billion' },
        { v: 1_000_000, label: 'Million' },
        { v: 1_000, label: 'Thousand' },
    ];

    for (const scale of scales) {
        if (n >= scale.v) {
            const part = Math.floor(n / scale.v);
            chunks.push(`${underThousand(part)} ${scale.label}`);
            n %= scale.v;
        }
    }
    if (n > 0) chunks.push(underThousand(n));

    let text = chunks.join(' ') || 'Zero';
    text += ` ${currency}`;
    if (cents > 0) {
        text += ` and ${underThousand(cents)} Cents`;
    }
    return `${text} Only`;
}

export function formatVoucherDate(value?: string | null): string {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return String(value);
    return d.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}

export function printPage() {
    window.print();
}
