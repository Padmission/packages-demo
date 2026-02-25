<?php

namespace App\Models;

use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model implements HasCurrentTenantLabel
{
    use HasFactory;

    /** @return BelongsToMany<User,$this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot('role');
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Current team';
    }
}
