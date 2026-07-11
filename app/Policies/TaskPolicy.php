<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Member;
use App\Models\Task;

class TaskPolicy
{
    public function complete(Member $member, Task $task): bool
    {
        return $task->completed_at === null
            && ($task->assigned_member_id === null || $task->assigned_member_id === $member->id);
    }
}
