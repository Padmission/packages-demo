<?php

// ABOUTME: Trait for multi-tenancy that adds team relationship and automatic scoping
// ABOUTME: Applied to all models that should be isolated by team in the demo system

namespace App\Models\Concerns;

use App\Models\Scopes\TeamScope;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTeam
{
    protected static function bootBelongsToTeam(): void
    {
        static::addGlobalScope(new TeamScope);

        static::creating(function ($model) {
            if (! $model->team_id && auth()->check()) {
                $tenant = filament()->getTenant();
                if ($tenant instanceof Team) {
                    $model->team_id = $tenant->id;
                }
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
