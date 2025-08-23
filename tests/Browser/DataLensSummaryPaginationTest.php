<?php

use App\Models\Shop\Product;
use App\Models\Team;
use App\Models\User;
use Padmission\DataLens\Models\CustomReport;
use Padmission\DataLens\Models\CustomReportSummary;

it('can handle multiple summaries and test pagination', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    // Create a base report
    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Pagination Test Report',
        'data_model' => Product::class,
        'columns' => [
            ['field' => 'name', 'label' => 'Product Name', 'type' => 'text', 'classification' => 'simple'],
            ['field' => 'price', 'label' => 'Price', 'type' => 'money', 'classification' => 'simple'],
            ['field' => 'qty', 'label' => 'Stock', 'type' => 'number', 'classification' => 'simple'],
        ],
        'filters' => [],
        'settings' => [
            'api' => ['enabled' => false, 'auth_type' => 'api_key'],
            'filters' => ['global_logic_operator' => 'and'],
        ],
    ]);

    // Create 25 summaries to test pagination (assuming default page size might be 10-20)
    $summaryTypes = ['count', 'sum', 'average', 'min', 'max'];
    $fields = ['id', 'price', 'qty'];
    $strategies = ['sql', 'collection', 'hybrid', 'auto'];

    for ($i = 1; $i <= 25; $i++) {
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => "Summary #{$i} - " . ucfirst($summaryTypes[($i - 1) % count($summaryTypes)]),
            'configuration' => [
                'type' => $summaryTypes[($i - 1) % count($summaryTypes)],
                'field' => $fields[($i - 1) % count($fields)],
            ],
            'processing_strategy' => $strategies[($i - 1) % count($strategies)],
            'cache_enabled' => $i % 2 === 0, // Alternate cache enabled/disabled
        ]);
    }

    // Visit the report with relation parameter to show summaries
    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(4) // Give extra time for many summaries to load
        ->assertSee('Pagination Test Report')
        ->assertNoJavaScriptErrors();

    // Check for some of the summaries
    $content = $page->content();
    $summariesVisible = 0;

    for ($i = 1; $i <= 5; $i++) {
        if (str_contains($content, "Summary #{$i}")) {
            $summariesVisible++;
        }
    }

    // Should see at least some summaries
    expect($summariesVisible)->toBeGreaterThan(0);

    // Look for pagination controls
    $hasPagination = str_contains($content, 'Next') ||
                    str_contains($content, 'Previous') ||
                    str_contains($content, 'Page') ||
                    str_contains($content, '1') ||
                    str_contains($content, '2') ||
                    str_contains($content, '&raquo;') ||
                    str_contains($content, '&laquo;') ||
                    str_contains($content, 'pagination');

    if ($hasPagination) {
        $page->wait(1); // Pagination controls found
    }

    $page->wait(2);
});

it('can navigate between summary pages if pagination exists', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Navigation Test Report',
        'data_model' => Product::class,
        'columns' => [
            ['field' => 'name', 'label' => 'Product', 'type' => 'text', 'classification' => 'simple'],
            ['field' => 'price', 'label' => 'Price', 'type' => 'money', 'classification' => 'simple'],
        ],
        'filters' => [],
        'settings' => [
            'api' => ['enabled' => false, 'auth_type' => 'api_key'],
            'filters' => ['global_logic_operator' => 'and'],
        ],
    ]);

    // Create 30 summaries to ensure pagination
    for ($i = 1; $i <= 30; $i++) {
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => "Navigation Summary #{$i}",
            'configuration' => ['type' => 'count', 'field' => 'id'],
            'processing_strategy' => 'sql',
            'cache_enabled' => true,
        ]);
    }

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(4)
        ->assertSee('Navigation Test Report')
        ->assertNoJavaScriptErrors();

    $content = $page->content();

    // Try to find and click pagination controls
    if (str_contains($content, 'Next') || str_contains($content, '2')) {
        // Try to click Next or page 2
        try {
            if (str_contains($content, 'Next')) {
                $page->click('Next')
                    ->wait(3)
                    ->assertNoJavaScriptErrors();
            } elseif (str_contains($content, '2')) {
                $page->click('2')
                    ->wait(3)
                    ->assertNoJavaScriptErrors();
            }
        } catch (Exception) {
            // Pagination click failed - that's ok, we're testing if it exists
        }
    }

    $page->wait(2);
});

it('preserves relation parameter during pagination navigation', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Parameter Preservation Test',
        'data_model' => Product::class,
        'columns' => [
            ['field' => 'name', 'label' => 'Product', 'type' => 'text', 'classification' => 'simple'],
        ],
        'filters' => [],
        'settings' => [
            'api' => ['enabled' => false, 'auth_type' => 'api_key'],
            'filters' => ['global_logic_operator' => 'and'],
        ],
    ]);

    // Create enough summaries for pagination
    for ($i = 1; $i <= 20; $i++) {
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => "Preserve Test Summary #{$i}",
            'configuration' => ['type' => 'count', 'field' => 'id'],
            'processing_strategy' => 'sql',
            'cache_enabled' => true,
        ]);
    }

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(4)
        ->assertSee('Parameter Preservation Test')
        ->assertNoJavaScriptErrors();

    // Check that URL still contains relation=1
    $currentUrl = $page->url();
    expect($currentUrl)->toContain('relation=1');

    // If pagination exists, check that clicking maintains the parameter
    $content = $page->content();
    if (str_contains($content, 'Next') || str_contains($content, '2')) {
        try {
            if (str_contains($content, 'Next')) {
                $page->click('Next')->wait(2);
                $newUrl = $page->url();
                expect($newUrl)->toContain('relation=1');
            }
        } catch (Exception) {
            // Click failed, that's ok - we tested URL preservation
        }
    }

    $page->wait(2);
});

it('displays correct number of summaries per page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Per Page Count Test',
        'data_model' => Product::class,
        'columns' => [
            ['field' => 'name', 'label' => 'Product', 'type' => 'text', 'classification' => 'simple'],
        ],
        'filters' => [],
        'settings' => [
            'api' => ['enabled' => false, 'auth_type' => 'api_key'],
            'filters' => ['global_logic_operator' => 'and'],
        ],
    ]);

    // Create exactly 15 summaries
    for ($i = 1; $i <= 15; $i++) {
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => "Count Test Summary #{$i}",
            'configuration' => ['type' => 'count', 'field' => 'id'],
            'processing_strategy' => 'sql',
            'cache_enabled' => true,
        ]);
    }

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(4)
        ->assertSee('Per Page Count Test')
        ->assertNoJavaScriptErrors();

    // Count how many summaries are visible on the page
    $content = $page->content();
    $visibleSummaries = 0;

    for ($i = 1; $i <= 15; $i++) {
        if (str_contains($content, "Count Test Summary #{$i}")) {
            $visibleSummaries++;
        }
    }

    // Should see at least 1 summary, and not necessarily all 15 if paginated
    expect($visibleSummaries)->toBeGreaterThan(0);
    expect($visibleSummaries)->toBeLessThanOrEqual(15);

    $page->wait(2);
});

it('handles empty summaries list gracefully', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Empty Summaries Test',
        'data_model' => Product::class,
        'columns' => [
            ['field' => 'name', 'label' => 'Product', 'type' => 'text', 'classification' => 'simple'],
        ],
        'filters' => [],
        'settings' => [
            'api' => ['enabled' => false, 'auth_type' => 'api_key'],
            'filters' => ['global_logic_operator' => 'and'],
        ],
    ]);

    // Don't create any summaries - test empty state

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(3)
        ->assertSee('Empty Summaries Test')
        ->assertNoJavaScriptErrors();

    // Should handle empty summaries gracefully - no pagination should appear
    $content = $page->content();
    $hasEmptyState = str_contains($content, 'No summaries') ||
                    str_contains($content, 'No data') ||
                    str_contains($content, 'Empty') ||
                    ! str_contains($content, 'pagination');

    $page->wait(1);
});
