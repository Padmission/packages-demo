<?php

namespace App\Models;

use Filament\CustomDashboardsPlugin\Contracts\CanReceiveSharedDashboards;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Team extends Model implements CanReceiveSharedDashboards, HasCurrentTenantLabel
{
    use HasFactory;

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot('role');
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Current team';
    }

    public static function getDashboardShareableLabel(): string
    {
        return 'Team';
    }

    public static function getDashboardShareableTitleAttribute(): string
    {
        return 'name';
    }

    public static function resolveDashboardShareablesForUser(Authenticatable $user): ?Relation
    {
        return $user->teams();
    }

    public static function getDashboardShareableOptionsQuery(Authenticatable $user): Builder
    {
        return static::query()->whereRelation('users', 'users.id', $user->getKey());
    }
}
