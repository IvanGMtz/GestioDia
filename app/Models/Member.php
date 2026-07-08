<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MemberRole;
use App\Models\Scopes\BelongsToTeamScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([BelongsToTeamScope::class])]
class Member extends Model
{
    /** @use HasFactory<\Database\Factories\MemberFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => MemberRole::class,
            'active' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(MemberDevice::class);
    }

    public function assignedRecurringTasks(): HasMany
    {
        return $this->hasMany(RecurringTask::class, 'assigned_member_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_member_id');
    }

    public function completedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'completed_by_member_id');
    }

    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }

    public function magicLinks(): HasMany
    {
        return $this->hasMany(MagicLink::class);
    }
}
