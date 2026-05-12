<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use Filament\CustomDashboardsPlugin\UserDashboardAccess;
use Filament\Facades\Filament;

/**
 * Replaces the plugin's default UserDashboardAccess so that a multi-team user
 * only sees dashboards shared with the team they are currently impersonating
 * (Filament::getTenant()) rather than every team they belong to.
 *
 * The plugin resolves dashboard visibility through this class. By scoping the
 * Team morph_type ids down to just the active tenant key we close the cross
 * tenant leak without forking the plugin or fighting global scopes.
 */
class TenantAwareUserDashboardAccess extends UserDashboardAccess
{
    /**
     * @return array<int, array{morph_type: string, ids: array<int|string>}>
     */
    protected function resolveConstraints(): array
    {
        $constraints = parent::resolveConstraints();
        $tenant = Filament::getTenant();
        $teamMorph = (new Team)->getMorphClass();

        return array_map(function (array $constraint) use ($tenant, $teamMorph): array {
            if ($constraint['morph_type'] !== $teamMorph) {
                return $constraint;
            }

            $constraint['ids'] = $tenant instanceof Team
                ? [$tenant->getKey()]
                : [];

            return $constraint;
        }, $constraints);
    }
}
