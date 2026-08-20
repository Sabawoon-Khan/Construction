<?php

namespace App\Models;

use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ExpenseCategory extends Model
{
    use HasCreator;

    protected $fillable = [
        'user_id',
        'name',
        'code',
        'description',
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

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return list<array{code: string, name: string, description: string}>
     */
    public static function defaultCatalog(): array
    {
        return [
            ['code' => 'materials', 'name' => 'Materials', 'description' => 'Building materials'],
            ['code' => 'labor', 'name' => 'Labor', 'description' => 'Site labor and wages'],
            ['code' => 'machinery', 'name' => 'Machinery rental', 'description' => 'Equipment hire'],
            ['code' => 'transport', 'name' => 'Transport', 'description' => 'Haulage and fuel'],
            ['code' => 'permits', 'name' => 'Permits', 'description' => 'Licenses and permits'],
            ['code' => 'admin', 'name' => 'Administration', 'description' => 'Office and admin'],
            ['code' => 'salaries', 'name' => 'Salaries', 'description' => 'Staff salaries'],
            ['code' => 'other', 'name' => 'Other', 'description' => 'Uncategorized spend'],
            ['code' => 'general', 'name' => 'General', 'description' => 'Default category'],
        ];
    }

    public static function ensureDefaults(): void
    {
        foreach (self::defaultCatalog() as $row) {
            self::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'is_active' => true,
                ],
            );
        }
    }

    /** @return list<int> */
    public static function salaryIds(): array
    {
        self::ensureDefaults();

        return self::query()
            ->whereIn('code', ['salaries', 'salary', 'payroll'])
            ->pluck('id')
            ->all();
    }

    /** @return list<int> */
    public static function materialIds(): array
    {
        self::ensureDefaults();

        return self::query()
            ->whereIn('code', ['materials', 'material'])
            ->pluck('id')
            ->all();
    }

    /** @return Collection<int, ExpenseCategory> */
    public static function nonMaterialOptions()
    {
        self::ensureDefaults();

        $ids = self::materialIds();

        return self::query()
            ->where('is_active', true)
            ->when($ids !== [], fn ($q) => $q->whereNotIn('id', $ids))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }
}
