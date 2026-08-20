/**
 * Landing page copy — English source of truth (no geo-specific framing).
 */
export const featureGroups: Array<{
    id: string;
    titleKey: string;
    title: string;
    leadKey: string;
    lead: string;
    items: Array<{
        titleKey: string;
        title: string;
        bodyKey: string;
        body: string;
    }>;
}> = [
    {
        id: 'access',
        titleKey: 'landing.group.access.title',
        title: 'Security & access',
        leadKey: 'landing.group.access.lead',
        lead: 'Production auth and RBAC — ready on day one.',
        items: [
            {
                titleKey: 'landing.feat.auth.title',
                title: 'Auth (Fortify)',
                bodyKey: 'landing.feat.auth.body',
                body: 'Login, register, password reset, 2FA, and passkeys. Optional forced admin 2FA.',
            },
            {
                titleKey: 'landing.feat.rbac.title',
                title: 'Roles & permissions',
                bodyKey: 'landing.feat.rbac.body',
                body: 'Spatie Permission + config-driven matrix. Owner / Admin / User. Sync with one command.',
            },
            {
                titleKey: 'landing.feat.users.title',
                title: 'Users & roles CRUD',
                bodyKey: 'landing.feat.users.body',
                body: 'Settings UI with row actions, enable/disable users, and CSV export.',
            },
            {
                titleKey: 'landing.feat.sessions.title',
                title: 'Session revoke',
                bodyKey: 'landing.feat.sessions.body',
                body: 'Sign out other sessions instantly via session version bump.',
            },
        ],
    },
    {
        id: 'i18n',
        titleKey: 'landing.group.i18n.title',
        title: 'Languages & locales',
        leadKey: 'landing.group.i18n.lead',
        lead: 'True multilingual admin — including first-class RTL.',
        items: [
            {
                titleKey: 'landing.feat.i18n.title',
                title: 'en / fa',
                bodyKey: 'landing.feat.i18n.body',
                body: 'Cookie locale switcher, full RTL where needed, and readable Arabic-script typography.',
            },
            {
                titleKey: 'landing.feat.translations.title',
                title: 'Translations editor',
                bodyKey: 'landing.feat.translations.body',
                body: 'Edit JSON catalogs in Settings without redeploying by hand.',
            },
            {
                titleKey: 'landing.feat.jalali.title',
                title: 'Jalali & localized digits',
                bodyKey: 'landing.feat.jalali.body',
                body: 'Solar Hijri helpers, configurable week start, and localized digits.',
            },
            {
                titleKey: 'landing.feat.lookups.title',
                title: 'Lookups',
                bodyKey: 'landing.feat.lookups.body',
                body: 'Reference lists managed under Settings via a config whitelist.',
            },
        ],
    },
    {
        id: 'shell',
        titleKey: 'landing.group.shell.title',
        title: 'Admin shell & theming',
        leadKey: 'landing.group.shell.lead',
        lead: 'A production MIS chrome operators learn once.',
        items: [
            {
                titleKey: 'landing.feat.shell.title',
                title: 'Floating admin shell',
                bodyKey: 'landing.feat.shell.body',
                body: 'Dark sidebar, glass header, MisPage chrome, permission-aware navigation.',
            },
            {
                titleKey: 'landing.feat.theme.title',
                title: 'Theme colors & shell modes',
                bodyKey: 'landing.feat.theme.body',
                body: 'Org-persisted presets and floating / inset / flush / header layouts.',
            },
            {
                titleKey: 'landing.feat.tours.title',
                title: 'Product tours',
                bodyKey: 'landing.feat.tours.body',
                body: 'driver.js shell tour so new staff ramp faster.',
            },
            {
                titleKey: 'landing.feat.gallery.title',
                title: 'Component gallery',
                bodyKey: 'landing.feat.gallery.body',
                body: 'Owner-only /dev/gallery for MIS UI primitives.',
            },
        ],
    },
    {
        id: 'ops',
        titleKey: 'landing.group.ops.title',
        title: 'Ops, backups & audit',
        leadKey: 'landing.group.ops.lead',
        lead: 'Run it like production from the first deploy.',
        items: [
            {
                titleKey: 'landing.feat.backups.title',
                title: 'Backups & restore',
                bodyKey: 'landing.feat.backups.body',
                body: 'DB / files / full backups, retention tiers, mirror disk, safe restore.',
            },
            {
                titleKey: 'landing.feat.audit.title',
                title: 'Audit trail',
                bodyKey: 'landing.feat.audit.body',
                body: 'Auth logs, system logs, activitylog, and status-change history.',
            },
            {
                titleKey: 'landing.feat.notify.title',
                title: 'Notifications',
                bodyKey: 'landing.feat.notify.body',
                body: 'In-app bell plus critical mail alerts for ops failures.',
            },
            {
                titleKey: 'landing.feat.attachments.title',
                title: 'Attachments',
                bodyKey: 'landing.feat.attachments.body',
                body: 'Polymorphic attachments with secure download routes.',
            },
            {
                titleKey: 'landing.feat.seo.title',
                title: 'SEO',
                bodyKey: 'landing.feat.seo.body',
                body: 'Seo helper, SeoHead, robots.txt, and sitemap.xml.',
            },
            {
                titleKey: 'landing.feat.email.title',
                title: 'Email settings',
                bodyKey: 'landing.feat.email.body',
                body: 'Org mail configuration and transactional template foundations.',
            },
        ],
    },
    {
        id: 'build',
        titleKey: 'landing.group.build.title',
        title: 'Build faster',
        leadKey: 'landing.group.build.lead',
        lead: 'Scaffold modules. Keep domain out of the kit core.',
        items: [
            {
                titleKey: 'landing.feat.scaffold.title',
                title: 'kit:scaffold',
                bodyKey: 'landing.feat.scaffold.body',
                body: 'SoftDeletes CRUD stubs, permissions, optional offline, AI catalog entry.',
            },
            {
                titleKey: 'landing.feat.install.title',
                title: 'kit:install',
                bodyKey: 'landing.feat.install.body',
                body: 'Interactive setup wizard for a fresh clone.',
            },
            {
                titleKey: 'landing.feat.field.title',
                title: 'Field reports demo',
                bodyKey: 'landing.feat.field.body',
                body: 'Demo module for forms, offline sync, and AI catalog wiring.',
            },
            {
                titleKey: 'landing.feat.exports.title',
                title: 'CSV / print stubs',
                bodyKey: 'landing.feat.exports.body',
                body: 'ExportsCsv trait and print layout stubs for list pages.',
            },
        ],
    },
    {
        id: 'optional',
        titleKey: 'landing.group.optional.title',
        title: 'Opt-in modules',
        leadKey: 'landing.group.optional.lead',
        lead: 'Start lean. Flip flags when you need more.',
        items: [
            {
                titleKey: 'landing.feat.ai.title',
                title: 'AI (KIT_AI)',
                bodyKey: 'landing.feat.ai.body',
                body: 'Ctrl+K assist, Fill form, General Chat, Analytics, failover, usage logs.',
            },
            {
                titleKey: 'landing.feat.offline.title',
                title: 'Offline & PWA (KIT_OFFLINE)',
                bodyKey: 'landing.feat.offline.body',
                body: 'Dexie outbox, sync API, conflict UI, warm cache.',
            },
            {
                titleKey: 'landing.feat.api.title',
                title: 'API (KIT_API)',
                bodyKey: 'landing.feat.api.body',
                body: 'Sanctum token auth for login, me, logout, and users.',
            },
            {
                titleKey: 'landing.feat.sms.title',
                title: 'SMS foundation (KIT_SMS)',
                bodyKey: 'landing.feat.sms.body',
                body: 'Gateway abstraction + fake driver — wire carriers in your clone.',
            },
            {
                titleKey: 'landing.feat.tenancy.title',
                title: 'Tenancy foundation (KIT_TENANCY)',
                bodyKey: 'landing.feat.tenancy.body',
                body: 'BelongsToOrg + CurrentOrg scaffolding to extend in your product.',
            },
            {
                titleKey: 'landing.feat.flags.title',
                title: 'Feature flags',
                bodyKey: 'landing.feat.flags.body',
                body: 'seo, audit, attachments, translations, exports, tours, mail alerts — via config.',
            },
        ],
    },
];

export const highlights: Array<{
    titleKey: string;
    title: string;
    bodyKey: string;
    body: string;
    tag: string;
}> = [
    {
        tag: '01',
        titleKey: 'landing.hi.ship.title',
        title: 'Ship in days, not months',
        bodyKey: 'landing.hi.ship.body',
        body: 'Auth, roles, shell, backups, and audit already wired — scaffold your domain on top.',
    },
    {
        tag: '02',
        titleKey: 'landing.hi.ai.title',
        title: 'AI inside the admin',
        bodyKey: 'landing.hi.ai.body',
        body: 'Ctrl+K writing assist, form fill, catalog chat, and analytics with approve-gated deletes.',
    },
    {
        tag: '03',
        titleKey: 'landing.hi.field.title',
        title: 'Works in the field',
        bodyKey: 'landing.hi.field.body',
        body: 'Optional PWA + offline outbox for intermittent connectivity and sync conflicts.',
    },
    {
        tag: '04',
        titleKey: 'landing.hi.i18n.title',
        title: 'Multilingual by default',
        bodyKey: 'landing.hi.i18n.body',
        body: 'English plus RTL locales, translations editor, Jalali helpers, and rebrandable themes.',
    },
    {
        tag: '05',
        titleKey: 'landing.hi.ops.title',
        title: 'Ops-ready from day one',
        bodyKey: 'landing.hi.ops.body',
        body: 'Backup tiers, restore safety copies, auth logs, session revoke, critical mail alerts.',
    },
    {
        tag: '06',
        titleKey: 'landing.hi.clone.title',
        title: 'Clone, don’t fork forever',
        bodyKey: 'landing.hi.clone.body',
        body: 'Infrastructure stays upgradeable. Domain modules live in your product repo.',
    },
];

export const aiCapabilities: Array<{
    titleKey: string;
    title: string;
    bodyKey: string;
    body: string;
}> = [
    {
        titleKey: 'landing.ai.cap.inline.title',
        title: 'Ctrl+K writing assist',
        bodyKey: 'landing.ai.cap.inline.body',
        body: 'Improve, shorten, expand, grammar, auto, or custom prompts on any text field.',
    },
    {
        titleKey: 'landing.ai.cap.fill.title',
        title: 'Fill form',
        bodyKey: 'landing.ai.cap.fill.body',
        body: 'Describe a record in plain language. Preview, then accept — labels only for privacy.',
    },
    {
        titleKey: 'landing.ai.cap.chat.title',
        title: 'General Chat agent',
        bodyKey: 'landing.ai.cap.chat.body',
        body: 'Catalog-driven CRUD across registered resources. Deletes pause for approval.',
    },
    {
        titleKey: 'landing.ai.cap.analytics.title',
        title: 'AI Analytics',
        bodyKey: 'landing.ai.cap.analytics.body',
        body: 'Natural language to guarded read-only SQL. KPIs, charts, tables, insights.',
    },
    {
        titleKey: 'landing.ai.cap.files.title',
        title: 'File attachments',
        bodyKey: 'landing.ai.cap.files.body',
        body: 'PDF, images, TXT, CSV — summarize, extract, or create records from documents.',
    },
    {
        titleKey: 'landing.ai.cap.providers.title',
        title: 'Bring your own models',
        bodyKey: 'landing.ai.cap.providers.body',
        body: 'OpenAI, Anthropic, Gemini, Ollama, and more — with provider failover.',
    },
];

export const audiences: Array<{
    titleKey: string;
    title: string;
    bodyKey: string;
    body: string;
}> = [
    {
        titleKey: 'landing.aud.product.title',
        title: 'Product & engineering',
        bodyKey: 'landing.aud.product.body',
        body: 'Stop rebuilding auth and admin chrome. Ship private MIS products faster.',
    },
    {
        titleKey: 'landing.aud.agency.title',
        title: 'Agencies & consultancies',
        bodyKey: 'landing.aud.agency.body',
        body: 'Rebrand once, clone per client, keep domain work in the client repo.',
    },
    {
        titleKey: 'landing.aud.ngo.title',
        title: 'NGOs & operations teams',
        bodyKey: 'landing.aud.ngo.body',
        body: 'Audit trails, multilingual UI, and offline-ready workflows for real-world ops.',
    },
    {
        titleKey: 'landing.aud.rtl.title',
        title: 'Multilingual & RTL products',
        bodyKey: 'landing.aud.rtl.body',
        body: 'Ship English-first or RTL-first admins without bolting on i18n later.',
    },
];

export const commands: Array<{ cmd: string; bodyKey: string; body: string }> = [
    {
        cmd: 'php artisan kit:install',
        bodyKey: 'landing.cmd.install',
        body: 'Interactive setup for a fresh clone.',
    },
    {
        cmd: 'php artisan mis:sync-permissions',
        bodyKey: 'landing.cmd.perms',
        body: 'Sync permissions after you add modules.',
    },
    {
        cmd: 'php artisan kit:scaffold {Name}',
        bodyKey: 'landing.cmd.scaffold',
        body: 'Generate SoftDeletes CRUD + AI catalog entry.',
    },
    {
        cmd: 'php artisan kit:scaffold {Name} --offline',
        bodyKey: 'landing.cmd.offline',
        body: 'Scaffold with offline/outbox wiring.',
    },
    {
        cmd: 'php artisan backup:run',
        bodyKey: 'landing.cmd.backup',
        body: 'Run a backup with retention tiers.',
    },
];

export const stackItems: string[] = [
    'Laravel 13 + Fortify + Sanctum',
    'Inertia 3 · Vue 3 · TypeScript · Vite 8',
    'Tailwind 4 · shadcn-vue / Reka UI',
    'Spatie Permission + Activitylog',
    'Locales: en, fa',
    'PWA / Workbox · Dexie (offline)',
    'Laravel AI SDK · multi-provider',
    'Plus Jakarta Sans · Noto Sans Arabic',
];

export const notIncluded: string[] = [
    'Full multi-tenant SaaS tenancy',
    'Real SMS carrier drivers',
    'Filament admin',
    'Maatwebsite Excel',
    'CRDT offline sync',
    'Domain modules (finance, HR, schools…)',
];

export const faqs: Array<{
    qKey: string;
    q: string;
    aKey: string;
    a: string;
}> = [
    {
        qKey: 'landing.faq.1.q',
        q: 'Is Kit a finished ERP?',
        aKey: 'landing.faq.1.a',
        a: 'No. It is clone-ready infrastructure — auth, RBAC, i18n, shell, backups, audit, offline, AI. Your domain modules live in your clone.',
    },
    {
        qKey: 'landing.faq.2.q',
        q: 'Who is it for?',
        aKey: 'landing.faq.2.a',
        a: 'Product teams, agencies, NGOs, and builders who need a production MIS/ERP spine with multilingual UI, ops tools, and optional AI/offline.',
    },
    {
        qKey: 'landing.faq.3.q',
        q: 'Can I rebrand it?',
        aKey: 'landing.faq.3.a',
        a: 'Yes. App name, theme colors, shell layout, and locales are org settings — change the brand without forking CSS forever.',
    },
    {
        qKey: 'landing.faq.4.q',
        q: 'How do AI and offline work?',
        aKey: 'landing.faq.4.a',
        a: 'Both are opt-in via KIT_AI and KIT_OFFLINE. AI needs a model + API key. Offline uses PWA + Dexie outbox with a sync API.',
    },
    {
        qKey: 'landing.faq.5.q',
        q: 'What’s the difference between plans?',
        aKey: 'landing.faq.5.a',
        a: 'Open is the self-hosted core. Builder unlocks AI, offline, API/SMS foundations, and upgrades. Organization adds training, multi-clone rights, and SLA.',
    },
    {
        qKey: 'landing.faq.6.q',
        q: 'How do I get started?',
        aKey: 'landing.faq.6.a',
        a: 'Clone, set .env, composer install, kit:install or migrate --seed, npm install && npm run build, php artisan serve. Demo: admin@example.com / password.',
    },
];
