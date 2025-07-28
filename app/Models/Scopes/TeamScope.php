<?php

// ABOUTME: Global scope that automatically filters queries by current team
// ABOUTME: Applied via BelongsToTeam trait to enforce data isolation in multi-tenant demo

namespace App\Models\Scopes;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TeamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $tenant = filament()->getTenant();

        if ($tenant instanceof Team) {
            $builder->where($model->getTable() . '.team_id', $tenant->id);
        }
    }
}
