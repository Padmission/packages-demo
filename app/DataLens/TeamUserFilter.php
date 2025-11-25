<?php

declare(strict_types=1);

namespace App\DataLens;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Padmission\DataLens\Contracts\UserFilterContract;
use Padmission\DataLens\Models\CustomReport;

class TeamUserFilter implements UserFilterContract
{
    public function __invoke(Builder $query, ?CustomReport $report): Builder
    {
        $tenantId = Filament::getTenant()?->id;

        if ($tenantId) {
            $query->whereHas('teams', fn ($q) => $q->where('teams.id', $tenantId));
        }

        return $query;
    }
}
