<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class TranslationFileService
{
    public function __construct(
        private LocaleRegistry $locales,
        private ?string $langPath = null,
    ) {}

    /** @return list<string> */
    public function sourceKeys(): array
    {
        return array_keys($this->load('en'));
    }

    public function exists(string $locale): bool
    {
        return is_file($this->path($locale));
    }

    /** @return array<string, string> */
    public function load(string $locale): array
    {
        $path = $this->path($locale);

        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * Duplicate an existing locale file and register metadata so the language
     * appears in switchers, settings tabs, and RTL handling.
     *
     * @param  array{name: string, native: string, dir: string}  $meta
     */
    public function createLocale(string $code, array $meta, string $from = 'en'): void
    {
        $code = strtolower(trim($code));
        $from = strtolower(trim($from));

        if ($this->locales->exists($code) || $this->exists($code)) {
            throw new InvalidArgumentException("Locale already exists: {$code}");
        }

        if (! $this->locales->exists($from)) {
            throw new InvalidArgumentException("Source locale does not exist: {$from}");
        }

        $english = $this->load('en');

        if ($english === []) {
            throw new InvalidArgumentException('Source translation file is empty or missing.');
        }

        // Start from English keys, then overlay the chosen source locale values.
        $source = $from === 'en'
            ? $english
            : array_merge($english, $this->load($from));

        $this->locales->register($code, $meta);
        $this->save($code, $source);
    }

    /**
     * @param  array<string, string>  $updates
     */
    public function updateValues(string $locale, array $updates): void
    {
        $sourceKeys = $this->sourceKeys();
        $current = $this->load($locale);

        foreach (array_keys($updates) as $key) {
            if (! in_array($key, $sourceKeys, true) && ! array_key_exists($key, $current)) {
                throw new InvalidArgumentException("Unknown translation key: {$key}");
            }
        }

        foreach ($updates as $key => $value) {
            $current[$key] = (string) $value;
        }

        $this->save($locale, $current);
    }

    /**
     * @return LengthAwarePaginator<int, array{key: string, value: string, missing: bool}>
     */
    public function paginate(string $locale, ?string $search, int $page, int $perPage): LengthAwarePaginator
    {
        $source = $this->load('en');
        $translations = $this->load($locale);
        $allKeys = array_unique(array_merge(array_keys($source), array_keys($translations)));

        $entries = collect($allKeys)
            ->map(function (string $key) use ($locale, $source, $translations): array {
                $englishValue = $source[$key] ?? $key;
                $value = $translations[$key] ?? $englishValue;
                $missing = $locale !== 'en' && (! array_key_exists($key, $translations) || $value === $key || $value === $englishValue);

                return [
                    'key' => $key,
                    'value' => $value,
                    'missing' => $missing,
                ];
            })
            ->values();

        if ($search) {
            $needle = mb_strtolower($search);

            $entries = $entries->filter(function (array $entry) use ($needle): bool {
                return str_contains(mb_strtolower($entry['key']), $needle)
                    || str_contains(mb_strtolower($entry['value']), $needle);
            })->values();
        }

        $total = $entries->count();
        $items = $entries
            ->slice(($page - 1) * $perPage, $perPage)
            ->values()
            ->all();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /** @param  array<string, string>  $translations */
    private function save(string $locale, array $translations): void
    {
        $ordered = [];
        $sourceKeys = $this->sourceKeys();

        foreach ($sourceKeys as $key) {
            if (array_key_exists($key, $translations)) {
                $ordered[$key] = $translations[$key];
            }
        }

        // Preserve locale-only keys that do not exist in the English source.
        foreach ($translations as $key => $value) {
            if (! in_array($key, $sourceKeys, true)) {
                $ordered[$key] = $value;
            }
        }

        $json = json_encode($ordered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $json .= "\n";

        file_put_contents($this->path($locale), $json);
    }

    private function path(string $locale): string
    {
        return ($this->langPath ?? lang_path())."/{$locale}.json";
    }
}
