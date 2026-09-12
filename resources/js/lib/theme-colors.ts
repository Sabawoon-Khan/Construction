export type ThemeRadius = 'soft' | 'medium' | 'sharp';
export type ThemeDensity = 'comfortable' | 'compact';
export type ThemeSidebarActive = 'pill' | 'accent' | 'soft';
export type ThemeShell =
    | 'sidebar-floating'
    | 'sidebar-inset'
    | 'sidebar-flush'
    | 'header';

export type ThemeColorSettings = {
    brand: string;
    secondary: string;
    accent: string;
    backgroundLight: string;
    backgroundDark: string;
    accentDark: string;
    radius: ThemeRadius;
    density: ThemeDensity;
    sidebarActive: ThemeSidebarActive;
    shell: ThemeShell;
};

export type ThemePreset = {
    id: string;
    labelKey: string;
    descriptionKey: string;
    settings: ThemeColorSettings;
};

export const THEME_COLORS_STORAGE_KEY = 'theme-colors';

/** Najm-Almadris–aligned defaults (navy sidebar, gold secondary, teal accent). */
export const DEFAULT_THEME_COLORS: ThemeColorSettings = {
    brand: '#162238',
    secondary: '#d4a853',
    accent: '#0d9488',
    backgroundLight: '#f1f4f9',
    backgroundDark: '#0b1220',
    accentDark: '#2dd4bf',
    radius: 'medium',
    density: 'comfortable',
    sidebarActive: 'pill',
    shell: 'sidebar-floating',
};

export const THEME_PRESETS: ThemePreset[] = [
    {
        id: 'structo',
        labelKey: 'Structo glass',
        descriptionKey: 'Soft frosted panels, orange accents, airy workspace',
        settings: {
            brand: '#111111',
            secondary: '#f3c14e',
            accent: '#ff8a3a',
            accentDark: '#fb923c',
            backgroundLight: '#eef0ee',
            backgroundDark: '#0c0c0c',
            radius: 'soft',
            density: 'comfortable',
            sidebarActive: 'pill',
            shell: 'sidebar-floating',
        },
    },
    {
        id: 'najm',
        labelKey: 'Najm classic',
        descriptionKey: 'Navy sidebar with teal and gold',
        settings: { ...DEFAULT_THEME_COLORS },
    },
    {
        id: 'forest',
        labelKey: 'Forest green',
        descriptionKey: 'Deep green brand with warm gold',
        settings: {
            brand: '#0f5132',
            secondary: '#c8a44d',
            accent: '#0d9488',
            accentDark: '#2dd4bf',
            backgroundLight: '#fbf8ef',
            backgroundDark: '#0b1220',
            radius: 'soft',
            density: 'comfortable',
            sidebarActive: 'pill',
            shell: 'sidebar-floating',
        },
    },
    {
        id: 'midnight',
        labelKey: 'Midnight',
        descriptionKey: 'Cool indigo shell with bright cyan',
        settings: {
            brand: '#1e1b4b',
            secondary: '#a78bfa',
            accent: '#06b6d4',
            accentDark: '#22d3ee',
            backgroundLight: '#f1f5f9',
            backgroundDark: '#0b1020',
            radius: 'medium',
            density: 'comfortable',
            sidebarActive: 'accent',
        shell: 'sidebar-floating',
        },
    },
    {
        id: 'ocean',
        labelKey: 'Ocean',
        descriptionKey: 'Coastal blue with soft aqua accents',
        settings: {
            brand: '#0c4a6e',
            secondary: '#38bdf8',
            accent: '#0ea5e9',
            accentDark: '#7dd3fc',
            backgroundLight: '#f0f9ff',
            backgroundDark: '#0b1520',
            radius: 'soft',
            density: 'comfortable',
            sidebarActive: 'soft',
        shell: 'sidebar-floating',
        },
    },
    {
        id: 'slate',
        labelKey: 'Slate',
        descriptionKey: 'Neutral charcoal with teal highlights',
        settings: {
            brand: '#111827',
            secondary: '#94a3b8',
            accent: '#14b8a6',
            accentDark: '#2dd4bf',
            backgroundLight: '#f8fafc',
            backgroundDark: '#0a0a0a',
            radius: 'sharp',
            density: 'compact',
            sidebarActive: 'accent',
        shell: 'sidebar-floating',
        },
    },
    {
        id: 'ember',
        labelKey: 'Ember',
        descriptionKey: 'Warm dusk tones with amber accents',
        settings: {
            brand: '#1c1917',
            secondary: '#f59e0b',
            accent: '#ea580c',
            accentDark: '#fb923c',
            backgroundLight: '#fafaf9',
            backgroundDark: '#0c0a09',
            radius: 'medium',
            density: 'comfortable',
            sidebarActive: 'accent',
        shell: 'sidebar-floating',
        },
    },
];

export const THEME_COLOR_FIELDS: Array<{
    key: keyof Pick<
        ThemeColorSettings,
        | 'brand'
        | 'secondary'
        | 'accent'
        | 'accentDark'
        | 'backgroundLight'
        | 'backgroundDark'
    >;
    labelKey: string;
    descriptionKey: string;
}> = [
    {
        key: 'brand',
        labelKey: 'Sidebar / brand color',
        descriptionKey: 'Active nav, brand mark accents, and structural chrome',
    },
    {
        key: 'secondary',
        labelKey: 'Secondary / gold',
        descriptionKey: 'Highlights, badges, and secondary accents',
    },
    {
        key: 'accent',
        labelKey: 'Accent color',
        descriptionKey: 'Buttons, links, and active highlights (light mode)',
    },
    {
        key: 'accentDark',
        labelKey: 'Accent color (dark mode)',
        descriptionKey: 'Buttons and highlights in dark mode',
    },
    {
        key: 'backgroundLight',
        labelKey: 'Background (light mode)',
        descriptionKey: 'Main page background in light mode',
    },
    {
        key: 'backgroundDark',
        labelKey: 'Background (dark mode)',
        descriptionKey: 'Main page background in dark mode',
    },
];

export const THEME_RADIUS_OPTIONS: Array<{
    value: ThemeRadius;
    labelKey: string;
    descriptionKey: string;
}> = [
    {
        value: 'soft',
        labelKey: 'Soft',
        descriptionKey: 'Rounded cards and controls',
    },
    {
        value: 'medium',
        labelKey: 'Medium',
        descriptionKey: 'Balanced default radius',
    },
    {
        value: 'sharp',
        labelKey: 'Sharp',
        descriptionKey: 'Tighter, more formal edges',
    },
];

export const THEME_DENSITY_OPTIONS: Array<{
    value: ThemeDensity;
    labelKey: string;
    descriptionKey: string;
}> = [
    {
        value: 'comfortable',
        labelKey: 'Comfortable',
        descriptionKey: 'Spacious navigation and content',
    },
    {
        value: 'compact',
        labelKey: 'Compact',
        descriptionKey: 'Denser sidebar and lists',
    },
];

export const THEME_SIDEBAR_ACTIVE_OPTIONS: Array<{
    value: ThemeSidebarActive;
    labelKey: string;
    descriptionKey: string;
}> = [
    {
        value: 'pill',
        labelKey: 'White pill',
        descriptionKey: 'Active item filled with the brand color',
    },
    {
        value: 'accent',
        labelKey: 'Accent fill',
        descriptionKey: 'Active item uses the accent color',
    },
    {
        value: 'soft',
        labelKey: 'Soft glow',
        descriptionKey: 'Subtle translucent active state',
    },
];

export const THEME_SHELL_OPTIONS: Array<{
    value: ThemeShell;
    labelKey: string;
    descriptionKey: string;
}> = [
    {
        value: 'sidebar-floating',
        labelKey: 'Floating sidebar',
        descriptionKey: 'Rounded island sidebar over the page (default)',
    },
    {
        value: 'sidebar-inset',
        labelKey: 'Workspace canvas',
        descriptionKey: 'Solid side rail with a soft rounded content card',
    },
    {
        value: 'sidebar-flush',
        labelKey: 'Edge rail',
        descriptionKey: 'Full-height sidebar flush to the screen edge',
    },
    {
        value: 'header',
        labelKey: 'Header only',
        descriptionKey: 'No sidebar — top navigation across the ERP',
    },
];

export function shellToSidebarVariant(
    shell: ThemeShell,
): 'floating' | 'inset' | 'sidebar' {
    if (shell === 'sidebar-inset') {
        return 'inset';
    }

    if (shell === 'sidebar-flush') {
        return 'sidebar';
    }

    return 'floating';
}

const RADIUS_VALUES: Record<ThemeRadius, string> = {
    soft: '1.125rem',
    medium: '0.875rem',
    sharp: '0.375rem',
};

function hexToRgb(hex: string): { r: number; g: number; b: number } | null {
    const normalized = hex.replace('#', '');

    if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
        return null;
    }

    return {
        r: parseInt(normalized.slice(0, 2), 16),
        g: parseInt(normalized.slice(2, 4), 16),
        b: parseInt(normalized.slice(4, 6), 16),
    };
}

export function withAlpha(hex: string, alpha: number): string {
    const rgb = hexToRgb(hex);

    if (!rgb) {
        return hex;
    }

    return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`;
}

function isDarkMode(): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    return document.documentElement.classList.contains('dark');
}

function isGlassMode(): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    return document.documentElement.classList.contains('glass');
}

function fromServerShape(
    input: Partial<ThemeColorSettings> & {
        sidebar_active?: ThemeSidebarActive;
    },
): Partial<ThemeColorSettings> {
    const { sidebar_active, ...rest } = input;

    return {
        ...rest,
        ...(sidebar_active ? { sidebarActive: sidebar_active } : {}),
    };
}

export function normalizeThemeColors(
    input:
        | (Partial<ThemeColorSettings> & {
              sidebar_active?: ThemeSidebarActive;
          })
        | null
        | undefined,
): ThemeColorSettings {
    const mapped = fromServerShape(input ?? {});

    return {
        ...DEFAULT_THEME_COLORS,
        ...mapped,
        radius: (['soft', 'medium', 'sharp'] as const).includes(
            mapped.radius as ThemeRadius,
        )
            ? (mapped.radius as ThemeRadius)
            : DEFAULT_THEME_COLORS.radius,
        density: (['comfortable', 'compact'] as const).includes(
            mapped.density as ThemeDensity,
        )
            ? (mapped.density as ThemeDensity)
            : DEFAULT_THEME_COLORS.density,
        sidebarActive: (['pill', 'accent', 'soft'] as const).includes(
            mapped.sidebarActive as ThemeSidebarActive,
        )
            ? (mapped.sidebarActive as ThemeSidebarActive)
            : DEFAULT_THEME_COLORS.sidebarActive,
        shell: (
            [
                'sidebar-floating',
                'sidebar-inset',
                'sidebar-flush',
                'header',
            ] as const
        ).includes(mapped.shell as ThemeShell)
            ? (mapped.shell as ThemeShell)
            : DEFAULT_THEME_COLORS.shell,
    };
}

/** Payload shape expected by the Laravel API (snake_case for sidebar_active). */
export function toServerThemePayload(settings: ThemeColorSettings): Record<
    string,
    string
> {
    return {
        brand: settings.brand,
        secondary: settings.secondary,
        accent: settings.accent,
        accentDark: settings.accentDark,
        backgroundLight: settings.backgroundLight,
        backgroundDark: settings.backgroundDark,
        radius: settings.radius,
        density: settings.density,
        sidebar_active: settings.sidebarActive,
        shell: settings.shell,
    };
}

export function applyThemeColors(settings: ThemeColorSettings | null): void {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;
    const cssVars = [
        '--school-navy',
        '--school-gold',
        '--school-accent',
        '--school-panel',
        '--kit-brand',
        '--kit-accent',
        '--kit-panel',
        '--primary',
        '--primary-foreground',
        '--accent-foreground',
        '--sidebar-background',
        '--sidebar',
        '--sidebar-primary',
        '--sidebar-ring',
        '--ring',
        '--accent',
        '--chart-1',
        '--chart-2',
        '--chart-3',
        '--background',
        '--brand-accent',
        '--app-shell',
        '--radius',
        '--app-v2-brand',
        '--app-v2-secondary',
        '--app-v2-accent',
        '--app-v2-background',
        '--card',
        '--foreground',
        '--card-foreground',
        '--muted-foreground',
        '--border',
    ];

    if (!settings) {
        cssVars.forEach((name) => root.style.removeProperty(name));
        root.removeAttribute('data-theme-radius');
        root.removeAttribute('data-theme-density');
        root.removeAttribute('data-theme-sidebar-active');
        root.removeAttribute('data-theme-shell');

        return;
    }

    const dark = isDarkMode();
    const glass = isGlassMode();
    const accent = dark && !glass ? settings.accentDark : settings.accent;
    const background =
        dark && !glass ? settings.backgroundDark : settings.backgroundLight;

    root.style.setProperty('--school-navy', settings.brand);
    root.style.setProperty('--school-gold', settings.secondary);
    root.style.setProperty('--school-accent', accent);
    root.style.setProperty('--school-panel', background);
    root.style.setProperty('--kit-brand', settings.brand);
    root.style.setProperty('--kit-accent', accent);
    root.style.setProperty('--kit-panel', background);
    root.style.setProperty('--primary', accent);
    root.style.setProperty(
        '--primary-foreground',
        dark ? '#042f2e' : '#ffffff',
    );
    root.style.setProperty('--brand-accent', accent);
    root.style.setProperty('--accent-foreground', accent);
    root.style.setProperty(
        '--sidebar-background',
        glass
            ? dark
                ? withAlpha(settings.brand, 0.92)
                : withAlpha('#ffffff', 0.52)
            : settings.brand,
    );
    root.style.setProperty(
        '--sidebar',
        glass
            ? dark
                ? withAlpha(settings.brand, 0.92)
                : withAlpha('#ffffff', 0.52)
            : settings.brand,
    );
    root.style.setProperty(
        '--sidebar-primary',
        glass && !dark ? settings.brand : accent,
    );
    root.style.setProperty('--sidebar-ring', withAlpha(accent, 0.4));
    root.style.setProperty('--ring', withAlpha(accent, dark ? 0.38 : 0.3));
    root.style.setProperty(
        '--accent',
        withAlpha(accent, dark ? 0.16 : 0.1),
    );
    root.style.setProperty('--chart-1', glass && !dark ? accent : settings.brand);
    root.style.setProperty('--chart-2', settings.secondary);
    root.style.setProperty('--chart-3', accent);
    root.style.setProperty('--background', background);
    root.style.setProperty('--app-shell', background);
    root.style.setProperty('--radius', RADIUS_VALUES[settings.radius]);
    /* v2 shell helpers */
    root.style.setProperty('--app-v2-brand', settings.brand);
    root.style.setProperty('--app-v2-secondary', settings.secondary);
    root.style.setProperty('--app-v2-accent', accent);
    root.style.setProperty('--app-v2-background', background);

    if (glass && !dark) {
        root.style.setProperty('--card', withAlpha('#ffffff', 0.46));
        root.style.setProperty('--foreground', '#14181a');
        root.style.setProperty('--card-foreground', '#14181a');
        root.style.setProperty('--muted-foreground', '#6b7378');
        root.style.setProperty('--border', withAlpha('#14181a', 0.08));
    } else if (glass && dark) {
        root.style.setProperty('--card', withAlpha('#121a27', 0.86));
        root.style.setProperty('--foreground', '#e8eef7');
        root.style.setProperty('--card-foreground', '#e8eef7');
        root.style.setProperty('--muted-foreground', '#9aa8bc');
        root.style.setProperty('--border', withAlpha('#94a3b8', 0.16));
        root.style.setProperty('--sidebar-foreground', withAlpha('#e8eef7', 0.88));
    } else {
        root.style.removeProperty('--card');
        root.style.removeProperty('--foreground');
        root.style.removeProperty('--card-foreground');
        root.style.removeProperty('--muted-foreground');
        root.style.removeProperty('--border');
        root.style.removeProperty('--sidebar-foreground');
    }

    root.dataset.themeRadius = settings.radius;
    root.dataset.themeDensity = settings.density;
    root.dataset.themeSidebarActive = settings.sidebarActive;
    root.dataset.themeShell = settings.shell;

    let meta = document.querySelector<HTMLMetaElement>(
        'meta[name="theme-color"]',
    );
    if (!meta) {
        meta = document.createElement('meta');
        meta.name = 'theme-color';
        document.head.appendChild(meta);
    }
    meta.content = background;
}

export function loadStoredThemeColors(): ThemeColorSettings | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = localStorage.getItem(THEME_COLORS_STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as Partial<ThemeColorSettings> & {
            sidebar_active?: ThemeSidebarActive;
        };

        return normalizeThemeColors(parsed);
    } catch {
        return null;
    }
}

export function cacheThemeColors(settings: ThemeColorSettings): void {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem(THEME_COLORS_STORAGE_KEY, JSON.stringify(settings));
}

export function saveThemeColors(settings: ThemeColorSettings): void {
    cacheThemeColors(settings);
    applyThemeColors(settings);
}

export function resetThemeColors(): void {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.removeItem(THEME_COLORS_STORAGE_KEY);
    applyThemeColors(null);
}

export function initializeThemeColors(
    serverColors?: ThemeColorSettings | null,
): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (serverColors) {
        const normalized = normalizeThemeColors(serverColors);
        cacheThemeColors(normalized);
        applyThemeColors(normalized);
    }

    const observer = new MutationObserver(() => {
        const current = loadStoredThemeColors();

        if (current) {
            applyThemeColors(current);
        }
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}

export function settingsMatchPreset(
    settings: ThemeColorSettings,
    preset: ThemePreset,
): boolean {
    return (
        settings.brand === preset.settings.brand &&
        settings.secondary === preset.settings.secondary &&
        settings.accent === preset.settings.accent &&
        settings.accentDark === preset.settings.accentDark &&
        settings.backgroundLight === preset.settings.backgroundLight &&
        settings.backgroundDark === preset.settings.backgroundDark &&
        settings.radius === preset.settings.radius &&
        settings.density === preset.settings.density &&
        settings.sidebarActive === preset.settings.sidebarActive &&
        settings.shell === preset.settings.shell
    );
}
