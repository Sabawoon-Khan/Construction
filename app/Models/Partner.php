<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\Trashable;
use App\Models\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasAttachments;
    use HasCreator;
    use SoftDeletes;
    use Trashable;

    protected string $creatorColumn = 'created_by';

    protected $fillable = [
        'created_by',
        'user_id',
        'partner_no',
        'name',
        'phone',
        'email',
        'national_id',
        'address',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_partner')
            ->withPivot('ownership_percentage')
            ->withTimestamps();
    }

    public function investments(): HasMany
    {
        return $this->hasMany(PartnerInvestment::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(PartnerDistribution::class);
    }

    public function ownershipOnProject(int $projectId): float
    {
        $project = $this->projects->firstWhere('id', $projectId);

        if ($project) {
            return (float) $project->pivot->ownership_percentage;
        }

        return (float) ($this->projects()->where('project_id', $projectId)->value('ownership_percentage') ?? 0);
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (self $partner): void {
            $partner->investments()->delete();
            $partner->distributions()->delete();
            $partner->projects()->detach();
        });
    }
}
