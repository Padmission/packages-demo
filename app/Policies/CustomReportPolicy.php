<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Padmission\DataLens\Models\CustomReport;

class CustomReportPolicy
{
    use HandlesAuthorization;

    // TODO: Implement more granular permissions based on your application needs

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
        return true;
    }

    public function delete(User $user, CustomReport $customReport): bool
    {
        return true;
    }

    public function manageApi(User $user, CustomReport $customReport): bool
    {
        return true;
    }

    public function manageSchedules(User $user, CustomReport $customReport): bool
    {
        return true;
    }

    public function export(User $user, CustomReport $customReport): bool
    {
        return true;
    }
}
