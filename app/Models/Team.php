<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    /** @use HasFactory<\Database\Factories\TeamFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'max_members',
        'tasks_generated_until',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'max_members' => 'integer',
            'tasks_generated_until' => 'date',
            'settings' => 'array',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function recurringTasks(): HasMany
    {
        return $this->hasMany(RecurringTask::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function workSessions(): HasMany
    {
        return $this->hasMany(WorkSession::class);
    }
}
