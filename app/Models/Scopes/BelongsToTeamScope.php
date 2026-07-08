<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BelongsToTeamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound(Team::class)) {
            $builder->where($model->getTable().'.team_id', app(Team::class)->id);
        }
    }
}
