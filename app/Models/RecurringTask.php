<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\BelongsToTeamScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([BelongsToTeamScope::class])]
class RecurringTask extends Model
{
    /** @use HasFactory<\Database\Factories\RecurringTaskFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'assigned_member_id',
        'requires_photo',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'requires_photo' => 'boolean',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assigned_member_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
