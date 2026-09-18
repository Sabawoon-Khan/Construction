<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Singleton business profile for this MIS.
 * Operational data (projects, sales, expenses, etc.) does not store company_id —
 * everything in the database belongs to this one company.
 */
class Company extends Model
{
    use HasCreator;

    protected $fillable = [
        'user_id',
        'name',
        'name_en',
        'legal_name',
        'registration_no',
        'phone',
        'email',
        'address',
        'address_en',
        'base_currency',
        'logo_path',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Locale-aware display name: English when locale is `en`, otherwise primary (Dari/Persian) name.
     */
    public function displayName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'en' && filled($this->name_en)) {
            return (string) $this->name_en;
        }

        return (string) $this->name;
    }

    /**
     * Public-site identity: names and contact from this company row.
     *
     * @return array{name: string, name_primary: string, name_en: string, phone: string|null, email: string|null, address: string|null}
     */
    public function toPublicArray(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $namePrimary = filled($this->name)
            ? (string) $this->name
            : (string) OrgSetting::getValue('app_name', config('app.name_fa'));
        $nameEn = filled($this->name_en)
            ? (string) $this->name_en
            : (string) OrgSetting::getValue('app_name_en', config('app.name'));

        $addressPrimary = filled($this->address) ? (string) $this->address : null;
        $addressEn = filled($this->address_en) ? (string) $this->address_en : null;
        $address = $locale === 'en'
            ? ($addressEn ?: $addressPrimary)
            : ($addressPrimary ?: $addressEn);
        $displayName = $this->displayName($locale);

        return [
            'name' => $displayName !== '' ? $displayName : OrgSetting::appName($locale),
            'name_primary' => $namePrimary,
            'name_en' => $nameEn,
            'phone' => filled($this->phone) ? (string) $this->phone : null,
            'email' => filled($this->email) ? (string) $this->email : null,
            'address' => $address,
        ];
    }

    public function syncBrandSettings(): void
    {
        OrgSetting::setValue('app_name', $this->name);
        OrgSetting::setValue('app_name_en', (string) ($this->name_en ?? ''));
        OrgSetting::applyBrandToConfig();
    }
}
