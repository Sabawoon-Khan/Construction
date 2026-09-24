export type PrintRecordOption = {
    key: string;
    title: string;
    subtitle?: string;
    amount?: string;
};

export type CollectedPrintRow = PrintRecordOption & {
    element: HTMLTableRowElement;
};

function cellText(td: Element): string {
    return (td.textContent || '').replace(/\s+/g, ' ').trim();
}

export function findPrintHost(from: HTMLElement | null): HTMLElement | null {
    if (!from) {
        return document.querySelector<HTMLElement>(
            '.print-target, .print-document, .voucher-sheet, .a4-print-sheet',
        );
    }

    return (
        from.closest<HTMLElement>(
            '.print-target, .print-sheet, .print-document, .voucher-sheet, .print-table-only',
        ) ||
        document.querySelector<HTMLElement>(
            '.print-target, .print-document, .voucher-sheet, .a4-print-sheet',
        )
    );
}

export function collectPrintableRows(host: HTMLElement): CollectedPrintRow[] {
    const rows = [...host.querySelectorAll('table tbody tr')].filter((tr) => {
        if (tr.querySelector('[colspan]')) {
            return false;
        }

        return tr.querySelectorAll('td').length > 0;
    }) as HTMLTableRowElement[];

    return rows.map((tr, index) => {
        const cells = [...tr.querySelectorAll('td')];
        const last = cells.at(-1);

        if (
            last?.querySelector(
                'button, [data-slot="dropdown-menu-trigger"], [data-slot="dropdown-menu"]',
            )
        ) {
            cells.pop();
        }

        const texts = cells.map(cellText).filter(Boolean);
        const rest =
            texts[0] && /^\d+$/.test(texts[0]) ? texts.slice(1) : texts;

        return {
            key: tr.getAttribute('data-print-key') || `row-${index}`,
            title: rest[0] || texts[0] || `#${index + 1}`,
            subtitle: rest.slice(1, 3).join(' · ') || undefined,
            element: tr,
        };
    });
}

export function applyPrintRowSelection(
    rows: CollectedPrintRow[],
    selectedKeys: string[],
): () => void {
    const selected = new Set(selectedKeys);

    for (const row of rows) {
        row.element.classList.toggle(
            'print-row-skip',
            !selected.has(row.key),
        );
    }

    return () => {
        for (const row of rows) {
            row.element.classList.remove('print-row-skip');
        }
    };
}
