type FilterValue = string | number | boolean | null | undefined;
type FilterRecord = Record<string, FilterValue>;
export type ExportScope = 'all' | 'filtered';
export type ExportCell = string | number | boolean | null | undefined;

export type BuildExportUrlOptions = {
    url: string;
    filters?: FilterRecord;
    scope: ExportScope;
    format?: string;
};

function omitEmpty(filters: FilterRecord): Record<string, string> {
    const query: Record<string, string> = {};

    for (const [key, value] of Object.entries(filters)) {
        if (value === null || value === undefined || value === '') {
            continue;
        }

        query[key] = String(value);
    }

    return query;
}

export function buildExportUrl(options: BuildExportUrlOptions): string {
    const params = new URLSearchParams();
    params.set('format', options.format ?? 'xlsx');
    params.set('scope', options.scope);

    if (options.scope === 'filtered' && options.filters) {
        for (const [key, value] of Object.entries(omitEmpty(options.filters))) {
            params.set(key, value);
        }
    }

    const qs = params.toString();
    return qs ? `${options.url}?${qs}` : options.url;
}

export function downloadExport(url: string): void {
    window.location.assign(url);
}

function excelXmlEscape(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function excelXmlCell(value: ExportCell): { type: 'Number' | 'String'; text: string } {
    if (typeof value === 'number' && Number.isFinite(value)) {
        return { type: 'Number', text: String(value) };
    }

    if (typeof value === 'boolean') {
        return { type: 'String', text: value ? '1' : '0' };
    }

    if (value === null || value === undefined) {
        return { type: 'String', text: '' };
    }

    return { type: 'String', text: String(value) };
}

/**
 * Build SpreadsheetML and trigger a browser download (no server round-trip).
 */
export function downloadClientSpreadsheet(
    filename: string,
    headers: string[],
    rows: ExportCell[][],
): void {
    const safeName = filename.replace(/[^\w.\-]+/g, '_').replace(/\.(csv|xls|xlsx)$/i, '') || 'export';
    let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
    xml += '<?mso-application progid="Excel.Sheet"?>\n';
    xml += '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
    xml += ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
    xml += '<Worksheet ss:Name="Export"><Table>';

    xml += '<Row>';
    for (const header of headers) {
        xml += `<Cell><Data ss:Type="String">${excelXmlEscape(header)}</Data></Cell>`;
    }
    xml += '</Row>';

    for (const row of rows) {
        xml += '<Row>';
        for (const value of row) {
            const cell = excelXmlCell(value);
            xml += `<Cell><Data ss:Type="${cell.type}">${excelXmlEscape(cell.text)}</Data></Cell>`;
        }
        xml += '</Row>';
    }

    xml += '</Table></Worksheet></Workbook>';

    const blob = new Blob([xml], {
        type: 'application/vnd.ms-excel;charset=utf-8',
    });
    const href = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = href;
    link.download = `${safeName}.xls`;
    link.click();
    URL.revokeObjectURL(href);
}

export function useTableExport() {
    return {
        buildExportUrl,
        downloadExport,
        downloadClientSpreadsheet,
    };
}
