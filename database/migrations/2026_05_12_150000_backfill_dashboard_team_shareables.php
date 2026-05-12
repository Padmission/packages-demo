<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill missing Team shareables and prune orphan dashboards.
     *
     * For every dashboard that has only User shareables, mirror those into
     * Team shareables -- but only for teams that still exist. Dashboards
     * whose every User owner has been deleted (or whose teams have all been
     * deleted) are pruned, since they're invisible to every live user under
     * the new tenant-aware scope.
     */
    public function up(): void
    {
        $teamMorph = (new Team)->getMorphClass();
        $userMorph = (new User)->getMorphClass();

        $dashboards = DB::table('filament_cd_dashboards')->get();

        foreach ($dashboards as $dashboard) {
            $teamShareableExists = DB::table('filament_cd_dashboard_shareables')
                ->where('dashboard_id', $dashboard->id)
                ->where('shareable_type', $teamMorph)
                ->exists();

            if ($teamShareableExists) {
                continue;
            }

            $liveOwnerUserIds = DB::table('filament_cd_dashboard_shareables as s')
                ->join('users as u', 'u.id', '=', 's.shareable_id')
                ->where('s.dashboard_id', $dashboard->id)
                ->where('s.shareable_type', $userMorph)
                ->pluck('u.id');

            $liveTeamIds = DB::table('team_user as tu')
                ->join('teams as t', 't.id', '=', 'tu.team_id')
                ->whereIn('tu.user_id', $liveOwnerUserIds)
                ->pluck('t.id')
                ->unique();

            if ($liveTeamIds->isEmpty()) {
                DB::table('filament_cd_dashboard_shareables')
                    ->where('dashboard_id', $dashboard->id)
                    ->delete();
                DB::table('filament_cd_dashboards')
                    ->where('id', $dashboard->id)
                    ->delete();

                continue;
            }

            foreach ($liveTeamIds as $teamId) {
                DB::table('filament_cd_dashboard_shareables')->insert([
                    'dashboard_id' => $dashboard->id,
                    'shareable_type' => $teamMorph,
                    'shareable_id' => $teamId,
                    'role' => 'owner',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
