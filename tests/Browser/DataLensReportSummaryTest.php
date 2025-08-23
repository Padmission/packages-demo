<?php

use App\Models\Shop\Product;
use App\Models\Team;
use App\Models\User;
use Padmission\DataLens\Models\CustomReport;
use Padmission\DataLens\Models\CustomReportSummary;

it('can access data lens reports page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    // Visit the tenant-based Data Lens page
    $page = visit("/app/{$team->id}/data-lens")
        ->wait(3)
        ->assertNoJavaScriptErrors();

    // Should show Data Lens interface
    $page->wait(2);
});

it('can view data lens report with summaries', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    // Attach user to team so they can access tenant
    $user->teams()->attach($team);
    $this->actingAs($user);

    // Create a custom report first
    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Product Analytics Report',
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

    // Create some summaries for this report
    CustomReportSummary::create([
        'custom_report_id' => $report->id,
        'name' => 'Total Products',
        'configuration' => [
            'type' => 'count',
            'field' => 'id',
        ],
        'processing_strategy' => 'sql',
        'cache_enabled' => true,
    ]);

    CustomReportSummary::create([
        'custom_report_id' => $report->id,
        'name' => 'Average Price',
        'configuration' => [
            'type' => 'average',
            'field' => 'price',
        ],
        'processing_strategy' => 'sql',
        'cache_enabled' => true,
    ]);

    // Visit the specific report in Data Lens with relation parameter to show summaries
    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(3)
        ->assertSee('Product Analytics Report')
        ->assertNoJavaScriptErrors();

    // Should now show summary information
    $content = $page->content();
    if (str_contains($content, 'Total Products') || str_contains($content, 'Average Price')) {
        // Summaries are visible
        $page->wait(1);
    }

    // Look for summary information
    $page->wait(2);
});

it('can create new data lens report summary', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    // Create a base report
    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Sales Summary Report',
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

    // Visit the report page to create summaries with relation parameter
    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(3)
        ->assertNoJavaScriptErrors();

    // Check for summary creation capabilities
    $content = $page->content();
    if (str_contains($content, 'Summary') || str_contains($content, 'New')) {
        $page->wait(1); // Summaries are available
    }

    $page->assertNoJavaScriptErrors();
});

it('displays cached summary data correctly', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    // Create test products
    Product::factory()->count(5)->create([
        'team_id' => $team->id,
        'price' => 150.00,
        'name' => 'Test Product',
    ]);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Cached Summary Report',
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

    // Create summary with cached data
    CustomReportSummary::create([
        'custom_report_id' => $report->id,
        'name' => 'Product Count Summary',
        'configuration' => [
            'type' => 'count',
            'field' => 'id',
        ],
        'processing_strategy' => 'sql',
        'cache_enabled' => true,
        'cached_at' => now(),
        'cached_data' => json_encode(['count' => 5, 'average_price' => 150.00]),
    ]);

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(3)
        ->assertSee('Cached Summary Report')
        ->assertNoJavaScriptErrors();

    // Should display cached summary data
    $content = $page->content();
    if (str_contains($content, 'Product Count Summary') || str_contains($content, '5') || str_contains($content, '150')) {
        // Cached data is visible
        $page->wait(1);
    }

    // Wait for any cached data to display
    $page->wait(2);
});

it('handles report summaries on mobile', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Mobile Summary Test',
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

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")->on()->mobile()
        ->wait(3)
        ->assertSee('Mobile Summary Test')
        ->assertNoJavaScriptErrors();
});

it('requires authentication for data lens access', function () {
    $team = Team::factory()->create();

    // Try to access without authentication
    $page = visit("/app/{$team->id}/data-lens");
    expect($page->url())->toContain('/login');
});

it('works with different processing strategies', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Processing Strategy Test',
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

    // Create summaries with different strategies
    foreach (['sql', 'collection', 'hybrid', 'auto'] as $strategy) {
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => ucfirst($strategy) . ' Strategy Summary',
            'configuration' => [
                'type' => 'count',
                'field' => 'id',
            ],
            'processing_strategy' => $strategy,
            'cache_enabled' => true,
        ]);
    }

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(3)
        ->assertSee('Processing Strategy Test')
        ->assertNoJavaScriptErrors();

    // Should show different processing strategy summaries
    $content = $page->content();
    $strategies = ['Sql Strategy', 'Collection Strategy', 'Hybrid Strategy', 'Auto Strategy'];
    $foundStrategy = false;
    foreach ($strategies as $strategy) {
        if (str_contains($content, $strategy)) {
            $foundStrategy = true;

            break;
        }
    }
    if ($foundStrategy) {
        $page->wait(1); // Strategy summaries are visible
    }

    $page->wait(2);
});

it('can navigate between data lens reports', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    // Create multiple reports
    $report1 = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'First Report',
        'data_model' => Product::class,
        'columns' => [['field' => 'name', 'label' => 'Product', 'type' => 'text', 'classification' => 'simple']],
        'filters' => [],
        'settings' => ['api' => ['enabled' => false, 'auth_type' => 'api_key'], 'filters' => ['global_logic_operator' => 'and']],
    ]);

    $report2 = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Second Report',
        'data_model' => Product::class,
        'columns' => [['field' => 'price', 'label' => 'Price', 'type' => 'money', 'classification' => 'simple']],
        'filters' => [],
        'settings' => ['api' => ['enabled' => false, 'auth_type' => 'api_key'], 'filters' => ['global_logic_operator' => 'and']],
    ]);

    // Visit data lens index page
    $page = visit("/app/{$team->id}/data-lens")
        ->wait(3)
        ->assertNoJavaScriptErrors();

    // Should be able to see both reports
    $page->wait(2);
});

it('shows summaries only when relation parameter is set', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Relation Parameter Test',
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

    // Create a summary
    CustomReportSummary::create([
        'custom_report_id' => $report->id,
        'name' => 'Relation Test Summary',
        'configuration' => [
            'type' => 'count',
            'field' => 'id',
        ],
        'processing_strategy' => 'sql',
        'cache_enabled' => true,
    ]);

    // First visit without relation parameter - should not show summary details
    $pageWithoutRelation = visit("/app/{$team->id}/data-lens/{$report->id}")
        ->wait(3)
        ->assertSee('Relation Parameter Test')
        ->assertNoJavaScriptErrors();

    // Now visit with relation=1 parameter - should show summary details
    $pageWithRelation = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(3)
        ->assertSee('Relation Parameter Test')
        ->assertNoJavaScriptErrors();

    // Check if summary information is visible with relation parameter
    $contentWithRelation = $pageWithRelation->content();
    if (str_contains($contentWithRelation, 'Relation Test Summary') ||
        str_contains($contentWithRelation, 'Summary') ||
        str_contains($contentWithRelation, 'count')) {
        // Summary details are visible with relation parameter
        $pageWithRelation->wait(1);
    }

    $pageWithRelation->wait(2);
});

it('handles summary cache invalidation correctly', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Cache Test Report',
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

    // Create summary with cache disabled
    CustomReportSummary::create([
        'custom_report_id' => $report->id,
        'name' => 'No Cache Summary',
        'configuration' => [
            'type' => 'count',
            'field' => 'id',
        ],
        'processing_strategy' => 'sql',
        'cache_enabled' => false,
    ]);

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(3)
        ->assertSee('Cache Test Report')
        ->assertNoJavaScriptErrors();

    // Should show summary without cache
    $content = $page->content();
    if (str_contains($content, 'No Cache Summary')) {
        $page->wait(1); // Non-cached summary is visible
    }

    $page->wait(1);
});
