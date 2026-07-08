<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\BelongsToTeamScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ScopedBy([BelongsToTeamScope::class])]
class WorkSession extends Model
{
    /** @use HasFactory<\Database\Factories\WorkSessionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clocked_in_at' => 'datetime',
            'clocked_out_at' => 'datetime',
            'auto_closed' => 'boolean',
            'original_values' => 'array',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function editedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'edited_by_member_id');
    }
}
