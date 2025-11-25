<?php

declare(strict_types=1);

namespace App\DataLens;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Padmission\DataLens\Contracts\UserDisplayFormatterContract;

class TeamUserDisplayFormatter implements UserDisplayFormatterContract
{
    public function __invoke(Model $user): string
    {
        $tenantId = Filament::getTenant()?->id;

        $role = $user->teams()
            ->where('teams.id', $tenantId)
            ->first()
            ?->pivot
            ?->role ?? 'member';

        return sprintf('%s (%s) - %s', $user->name, ucfirst($role), $user->email);
    }
}
