<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Padmission\DataLens\Models\CustomReport;
use Padmission\DataLens\Support\Utils;

describe('CustomReportPolicy', function () {
    beforeEach(function () {
        $this->team = Team::factory()->create();

        $this->owner = User::factory()->create();
        $this->owner->teams()->attach($this->team, ['role' => 'owner']);

        $this->member = User::factory()->create();
        $this->member->teams()->attach($this->team, ['role' => 'member']);

        $this->report = CustomReport::create([
            'name' => 'Test Report',
            'data_model' => 'App\\Models\\User',
            'columns' => [],
            'filters' => [],
            'creator_id' => $this->owner->id,
            'team_id' => $this->team->id,
        ]);
    });

    test('any team member can view reports', function () {
        expect($this->member->can('view', $this->report))->toBeTrue();
        expect($this->owner->can('view', $this->report))->toBeTrue();
    });

    test('any team member can create reports', function () {
        expect($this->member->can('create', CustomReport::class))->toBeTrue();
    });

    test('only report creator can update', function () {
        expect($this->owner->can('update', $this->report))->toBeTrue();
        expect($this->member->can('update', $this->report))->toBeFalse();
    });

    test('only report creator can delete', function () {
        expect($this->owner->can('delete', $this->report))->toBeTrue();
        expect($this->member->can('delete', $this->report))->toBeFalse();
    });

    test('only team owner can manage API', function () {
        expect($this->owner->can('manageApi', $this->report))->toBeTrue();
        expect($this->member->can('manageApi', $this->report))->toBeFalse();
    });

    test('only team owner can manage schedules', function () {
        expect($this->owner->can('manageSchedules', $this->report))->toBeTrue();
        expect($this->member->can('manageSchedules', $this->report))->toBeFalse();
    });

    test('any team member can export', function () {
        expect($this->owner->can('export', $this->report))->toBeTrue();
        expect($this->member->can('export', $this->report))->toBeTrue();
    });

    test('only team owner can share', function () {
        expect($this->owner->can('share', $this->report))->toBeTrue();
        expect($this->member->can('share', $this->report))->toBeFalse();
    });

    test('any team member can use aggregation', function () {
        expect($this->owner->can('useAggregation', $this->report))->toBeTrue();
        expect($this->member->can('useAggregation', $this->report))->toBeTrue();
    });

    test('any team member can create summary', function () {
        expect($this->owner->can('createSummary', $this->report))->toBeTrue();
        expect($this->member->can('createSummary', $this->report))->toBeTrue();
    });
});

describe('Utils::checkPolicyOrAllow', function () {
    beforeEach(function () {
        $this->team = Team::factory()->create();

        $this->user = User::factory()->create();
        $this->user->teams()->attach($this->team, ['role' => 'member']);

        $this->report = CustomReport::create([
            'name' => 'Test Report',
            'data_model' => 'App\\Models\\User',
            'columns' => [],
            'filters' => [],
            'creator_id' => $this->user->id,
            'team_id' => $this->team->id,
        ]);
    });

    test('returns true when model is null', function () {
        $this->actingAs($this->user);

        expect(Utils::checkPolicyOrAllow('manageApi', null))->toBeTrue();
    });

    test('uses policy when available', function () {
        $this->actingAs($this->user);

        // member cannot manage API (policy returns false for non-owners)
        expect(Utils::checkPolicyOrAllow('manageApi', $this->report))->toBeFalse();
    });

    test('falls back to default allow when no policy or closure exists', function () {
        $this->actingAs($this->user);

        // nonExistentAbility has no policy method and no closure
        expect(Utils::checkPolicyOrAllow('nonExistentAbility', $this->report))->toBeTrue();
    });
});

describe('Utils convenience methods', function () {
    beforeEach(function () {
        $this->team = Team::factory()->create();

        $this->owner = User::factory()->create();
        $this->owner->teams()->attach($this->team, ['role' => 'owner']);

        $this->member = User::factory()->create();
        $this->member->teams()->attach($this->team, ['role' => 'member']);

        $this->report = CustomReport::create([
            'name' => 'Test Report',
            'data_model' => 'App\\Models\\User',
            'columns' => [],
            'filters' => [],
            'creator_id' => $this->owner->id,
            'team_id' => $this->team->id,
        ]);
    });

    test('canExport returns false when exports are globally disabled', function () {
        $this->actingAs($this->owner);
        config(['data-lens.exports.enabled' => false]);

        expect(Utils::canExport($this->report))->toBeFalse();
    });

    test('canExport returns true when exports are enabled and policy allows', function () {
        $this->actingAs($this->member);
        config(['data-lens.exports.enabled' => true]);

        expect(Utils::canExport($this->report))->toBeTrue();
    });

    test('canManageApi returns false when API is globally disabled', function () {
        $this->actingAs($this->owner);
        config(['data-lens.api.enabled' => false]);

        expect(Utils::canManageApi($this->report))->toBeFalse();
    });

    test('canManageSchedules returns false when scheduling is globally disabled', function () {
        $this->actingAs($this->owner);
        config(['data-lens.scheduling.enabled' => false]);

        expect(Utils::canManageSchedules($this->report))->toBeFalse();
    });

    test('canManageSchedules returns true for owner when scheduling is enabled', function () {
        $this->actingAs($this->owner);
        config(['data-lens.scheduling.enabled' => true]);

        expect(Utils::canManageSchedules($this->report))->toBeTrue();
    });

    test('canManageSchedules returns false for member even when scheduling is enabled', function () {
        $this->actingAs($this->member);
        config(['data-lens.scheduling.enabled' => true]);

        expect(Utils::canManageSchedules($this->report))->toBeFalse();
    });

    test('canShare returns false when sharing is globally disabled', function () {
        $this->actingAs($this->owner);
        config(['data-lens.features.sharing.user_sharing_enabled' => false]);

        expect(Utils::canShare($this->report))->toBeFalse();
    });

    test('canShare returns true for owner when sharing is enabled', function () {
        $this->actingAs($this->owner);
        config(['data-lens.features.sharing.user_sharing_enabled' => true]);

        expect(Utils::canShare($this->report))->toBeTrue();
    });

    test('canCreateSummary delegates to policy', function () {
        $this->actingAs($this->member);

        expect(Utils::canCreateSummary($this->report))->toBeTrue();
    });
});
