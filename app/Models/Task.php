<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\BelongsToTeamScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[ScopedBy([BelongsToTeamScope::class])]
class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'recurring_task_id',
        'task_date',
        'title',
        'description',
        'assigned_member_id',
        'requires_photo',
        'completed_at',
        'completed_by_member_id',
        'completion_note',
        'photo_path',
        'photo_pruned_at',
    ];

    protected function casts(): array
    {
        return [
            'task_date' => 'date:Y-m-d',
            'assigned_member_id' => 'integer',
            'requires_photo' => 'boolean',
            'completed_at' => 'datetime',
            'completed_by_member_id' => 'integer',
            'photo_pruned_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function recurringTask(): BelongsTo
    {
        return $this->belongsTo(RecurringTask::class);
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assigned_member_id');
    }

    public function completedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'completed_by_member_id');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }
}
