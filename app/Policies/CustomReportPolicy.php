<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Padmission\DataLens\Models\CustomReport;

class CustomReportPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CustomReport $customReport): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CustomReport $customReport): bool
    {
        return $user->id === $customReport->getAttribute('creator_id');
    }

    public function delete(User $user, CustomReport $customReport): bool
    {
        return $user->id === $customReport->getAttribute('creator_id');
    }

    public function manageApi(User $user, CustomReport $customReport): bool
    {
        return $this->isTeamOwner($user);
    }

    public function manageSchedules(User $user, CustomReport $customReport): bool
    {
        return $this->isTeamOwner($user);
    }

    public function export(User $user, CustomReport $customReport): bool
    {
        return true;
    }

    public function share(User $user, CustomReport $customReport): bool
    {
        return $this->isTeamOwner($user);
    }

    public function useAggregation(User $user, CustomReport $customReport): bool
    {
        return true;
    }

    public function createSummary(User $user, CustomReport $customReport): bool
    {
        return true;
    }

    private function isTeamOwner(User $user): bool
    {
        return $user->teams()
            ->wherePivot('role', 'owner')
            ->exists();
    }
}
