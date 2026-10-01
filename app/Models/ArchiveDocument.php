<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use App\Enums\ArchiveDocumentCategory;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveDocument extends Model
{
    use HasAttachments;
    use HasCreator;
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'document_date',
        'notes',
    ];

    protected $appends = [
        'category_label',
    ];

    protected function casts(): array
    {
        return [
            'category' => ArchiveDocumentCategory::class,
            'document_date' => 'date',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return $this->category?->label() ?? '';
    }
}
