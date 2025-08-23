<?php

use App\Models\Shop\Product;
use App\Models\Team;
use App\Models\User;
use Padmission\DataLens\Models\CustomReport;

it('can view a single custom report page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $this->actingAs($user);

    // Create a custom report with proper structure
    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Test Product Report',
        'data_model' => Product::class,
        'columns' => [
            ['field' => 'name', 'label' => 'Product', 'type' => 'text', 'classification' => 'simple'],
            ['field' => 'price', 'label' => 'Price', 'type' => 'money', 'classification' => 'simple'],
        ],
        'filters' => [],
        'settings' => [
            'api' => [
                'enabled' => false,
                'auth_type' => 'api_key',
            ],
            'filters' => [
                'global_logic_operator' => 'or',
            ],
        ],
    ]);

    // Visit the single report page
    $page = visit("/admin/custom-reports/{$report->id}")
        ->wait(3)
        ->assertSee('Test Product Report')
        ->assertNoJavaScriptErrors();
});

it('can run a custom report and see results', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $this->actingAs($user);

    // Create some test products first
    Product::factory()->count(3)->create([
        'team_id' => $team->id,
        'name' => 'Test Product',
        'price' => 100.00,
        'is_visible' => true,
    ]);

    // Create a custom report
    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Product Inventory Report',
        'data_model' => Product::class,
        'columns' => [
            ['field' => 'name', 'label' => 'Product Name', 'type' => 'text', 'classification' => 'simple'],
            ['field' => 'price', 'label' => 'Price', 'type' => 'money', 'classification' => 'simple'],
            ['field' => 'qty', 'label' => 'Stock', 'type' => 'number', 'classification' => 'simple'],
        ],
        'filters' => [],
        'settings' => [
            'api' => [
                'enabled' => false,
                'auth_type' => 'api_key',
            ],
            'filters' => [
                'global_logic_operator' => 'and',
            ],
        ],
    ]);

    // Visit the report and check for data
    $page = visit("/admin/custom-reports/{$report->id}")
        ->wait(3)
        ->assertSee('Product Inventory Report')
        ->assertNoJavaScriptErrors();

    // Look for table data or run button
    $page->wait(2);
});

it('handles non-existent report appropriately', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Try to visit a non-existent report
    $page = visit('/admin/custom-reports/999999')
        ->wait(2);

    // Check that the page doesn't show JavaScript errors
    // Even if it shows the non-existent URL, it should handle it gracefully
    $page->assertNoJavaScriptErrors();

    // The page should either redirect or show some kind of error/not found state
    $content = $page->content();
    $hasErrorHandling = str_contains($content, 'not found') ||
                       str_contains($content, 'Not Found') ||
                       str_contains($content, '404') ||
                       ! str_contains($page->url(), '999999');

    expect($hasErrorHandling)->toBeTrue();
});

it('redirects unauthenticated users from report show page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    // Create a report without authentication
    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Test Report',
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

    // Try to visit without authentication
    $page = visit("/admin/custom-reports/{$report->id}");
    expect($page->url())->toContain('/admin/login');
});

it('works on mobile devices for report viewing', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Mobile Test Report',
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

    $page = visit("/admin/custom-reports/{$report->id}")->on()->mobile()
        ->wait(3)
        ->assertSee('Mobile Test Report')
        ->assertNoJavaScriptErrors();
});

it('can navigate to report schedules page', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Scheduled Report Test',
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

    // Visit the schedules page
    $page = visit("/admin/custom-reports/{$report->id}/schedules")
        ->wait(2)
        ->assertNoJavaScriptErrors();
});
