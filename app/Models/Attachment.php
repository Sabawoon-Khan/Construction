<?php

namespace App\Models;

use App\Concerns\Trashable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use SoftDeletes;
    use Trashable;

    protected $fillable = [
        'client_uuid',
        'attachable_type',
        'attachable_id',
        'title',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected $appends = ['download_url'];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('attachments.download', $this);
    }

    public function ownedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $this->uploaded_by === (int) $user->id) {
            return true;
        }

        $attachable = $this->attachable;

        if ($attachable && isset($attachable->user_id)) {
            return (int) $attachable->user_id === (int) $user->id;
        }

        return false;
    }

    public function canDownload(?User $user): bool
    {
        if ($this->ownedBy($user)) {
            return true;
        }

        $module = $this->attachablePermissionModule();

        return $module !== null && (bool) $user?->can($module.'.view');
    }

    public function canDelete(?User $user): bool
    {
        if ($this->ownedBy($user)) {
            return true;
        }

        $module = $this->attachablePermissionModule();

        return $module !== null && (bool) $user?->can($module.'.edit');
    }

    protected function attachablePermissionModule(): ?string
    {
        $type = $this->attachable_type;
        $parent = $this->attachable;

        foreach (config('uploads.attachables', []) as $config) {
            if (! is_array($config)) {
                continue;
            }

            $model = $config['model'] ?? null;

            if ($model !== $type && ! ($parent && is_string($model) && $parent instanceof $model)) {
                continue;
            }

            $permissions = $config['permissions'] ?? [];
            $first = is_array($permissions) && $permissions !== [] ? (string) $permissions[0] : '';

            if ($first === '' || ! str_contains($first, '.')) {
                return null;
            }

            return explode('.', $first, 2)[0];
        }

        return null;
    }

    public function deleteFile(): void
    {
        if ($this->file_path && Storage::disk('local')->exists($this->file_path)) {
            Storage::disk('local')->delete($this->file_path);
        }
    }

    public function purgeTrashAssets(): void
    {
        $this->deleteFile();
    }
}
