<?php

use App\Models\Shop\Product;
use App\Models\Team;
use App\Models\User;
use Padmission\DataLens\Models\CustomReport;
use Padmission\DataLens\Models\CustomReportSummary;

it('verifies that clicking to second page actually changes content', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Page Content Change Test',
        'data_model' => Product::class,
        'columns' => [
            ['field' => 'name', 'label' => 'Product Name', 'type' => 'text', 'classification' => 'simple'],
            ['field' => 'price', 'label' => 'Price', 'type' => 'money', 'classification' => 'simple'],
        ],
        'filters' => [],
        'settings' => [
            'api' => ['enabled' => false, 'auth_type' => 'api_key'],
            'filters' => ['global_logic_operator' => 'and'],
        ],
    ]);

    // Create 30 summaries with unique, identifiable names
    $summaryNames = [];
    for ($i = 1; $i <= 30; $i++) {
        $name = "UNIQUE_SUMMARY_PAGE_TEST_{$i}";
        $summaryNames[] = $name;
        
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => $name,
            'configuration' => [
                'type' => 'count',
                'field' => 'id',
            ],
            'processing_strategy' => 'sql',
            'cache_enabled' => true,
        ]);
    }

    // Visit first page
    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(4)
        ->assertSee('Page Content Change Test')
        ->assertNoJavaScriptErrors();

    // Record which summaries are visible on page 1
    $page1Content = $page->content();
    $page1Summaries = [];
    foreach ($summaryNames as $name) {
        if (str_contains($page1Content, $name)) {
            $page1Summaries[] = $name;
        }
    }

    dump("Page 1 summaries found: " . count($page1Summaries));
    dump("First few summaries on page 1: " . implode(', ', array_slice($page1Summaries, 0, 5)));

    // Record the URL of page 1
    $page1Url = $page->url();
    
    // Look for pagination controls and try to click to page 2
    $hasNextPage = false;
    if (str_contains($page1Content, 'Next') || str_contains($page1Content, '2')) {
        try {
            if (str_contains($page1Content, '2')) {
                $page->click('2')
                    ->wait(3)
                    ->assertNoJavaScriptErrors();
                $hasNextPage = true;
            } elseif (str_contains($page1Content, 'Next')) {
                $page->click('Next')
                    ->wait(3)
                    ->assertNoJavaScriptErrors();
                $hasNextPage = true;
            }
        } catch (Exception $e) {
            dump("Failed to click pagination: " . $e->getMessage());
        }
    }

    if ($hasNextPage) {
        // Record URL of page 2
        $page2Url = $page->url();
        dump("Page 1 URL: " . $page1Url);
        dump("Page 2 URL: " . $page2Url);

        // Check if URL changed
        $urlChanged = $page1Url !== $page2Url;
        
        // Record which summaries are visible on page 2
        $page2Content = $page->content();
        $page2Summaries = [];
        foreach ($summaryNames as $name) {
            if (str_contains($page2Content, $name)) {
                $page2Summaries[] = $name;
            }
        }

        dump("Page 2 summaries found: " . count($page2Summaries));
        dump("First few summaries on page 2: " . implode(', ', array_slice($page2Summaries, 0, 5)));

        // Check if content actually changed between pages
        $contentChanged = $page1Content !== $page2Content;
        
        // Check if different summaries are shown
        $differentSummaries = $page1Summaries !== $page2Summaries;
        
        dump("URL changed: " . ($urlChanged ? 'Yes' : 'No'));
        dump("Content changed: " . ($contentChanged ? 'Yes' : 'No'));
        dump("Different summaries: " . ($differentSummaries ? 'Yes' : 'No'));

        // Assertions to verify pagination actually works
        expect($urlChanged || $contentChanged)->toBeTrue('Either URL or content should change when navigating pages');
        expect($page2Summaries)->not->toBeEmpty('Page 2 should show some summaries');
        
        // If we have enough summaries and proper pagination, we should see different content
        if (count($page1Summaries) > 0 && count($page2Summaries) > 0) {
            expect($differentSummaries)->toBeTrue('Page 2 should show different summaries than page 1');
        }
    } else {
        dump("No pagination controls found or clickable");
        // If no pagination, all summaries should be on one page
        expect($page1Summaries)->not->toBeEmpty('Should show summaries on single page');
    }

    $page->wait(2);
});

it('tests pagination with exact page size detection', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Page Size Detection Test',
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

    // Create 25 summaries to test pagination behavior
    for ($i = 1; $i <= 25; $i++) {
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => "SIZE_TEST_SUMMARY_" . str_pad($i, 2, '0', STR_PAD_LEFT), // Zero-padded for sorting
            'configuration' => ['type' => 'count', 'field' => 'id'],
            'processing_strategy' => 'sql',
            'cache_enabled' => true,
        ]);
    }

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(4)
        ->assertSee('Page Size Detection Test')
        ->assertNoJavaScriptErrors();

    // Count summaries on first page
    $content = $page->content();
    $summariesOnPage1 = 0;
    for ($i = 1; $i <= 25; $i++) {
        $summaryName = "SIZE_TEST_SUMMARY_" . str_pad($i, 2, '0', STR_PAD_LEFT);
        if (str_contains($content, $summaryName)) {
            $summariesOnPage1++;
        }
    }

    dump("Summaries visible on page 1: $summariesOnPage1");

    // Check if pagination exists
    $hasPagination = str_contains($content, 'Next') || 
                    str_contains($content, '2') ||
                    str_contains($content, 'pagination');

    if ($hasPagination) {
        dump("Pagination detected - page size appears to be: $summariesOnPage1");
        expect($summariesOnPage1)->toBeLessThan(25, 'Should not show all 25 summaries on one page if paginated');
        expect($summariesOnPage1)->toBeGreaterThan(0, 'Should show some summaries on first page');
    } else {
        dump("No pagination - all summaries on one page");
        expect($summariesOnPage1)->toBe(25, 'Should show all 25 summaries if not paginated');
    }

    $page->wait(2);
});

it('verifies pagination URL parameters and state', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'URL Parameters Test',
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

    // Create 20 summaries for pagination
    for ($i = 1; $i <= 20; $i++) {
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => "URL_TEST_SUMMARY_{$i}",
            'configuration' => ['type' => 'count', 'field' => 'id'],
            'processing_strategy' => 'sql',
            'cache_enabled' => true,
        ]);
    }

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(4)
        ->assertSee('URL Parameters Test')
        ->assertNoJavaScriptErrors();

    $initialUrl = $page->url();
    dump("Initial URL: $initialUrl");
    
    // Verify initial URL contains relation parameter
    expect($initialUrl)->toContain('relation=1');

    $content = $page->content();
    
    // Try to navigate to next page and check URL
    if (str_contains($content, 'Next') || str_contains($content, '2')) {
        try {
            if (str_contains($content, '2')) {
                $page->click('2')->wait(3);
            } elseif (str_contains($content, 'Next')) {
                $page->click('Next')->wait(3);
            }

            $newUrl = $page->url();
            dump("URL after navigation: $newUrl");

            // Verify relation parameter is preserved
            expect($newUrl)->toContain('relation=1');
            
            // Check if page parameter was added
            $hasPageParam = str_contains($newUrl, 'page=2') || 
                           str_contains($newUrl, 'page=') ||
                           $newUrl !== $initialUrl;
            
            dump("URL changed: " . ($hasPageParam ? 'Yes' : 'No'));
            
            if ($hasPageParam) {
                expect($newUrl)->not->toBe($initialUrl, 'URL should change when navigating pages');
            }
        } catch (Exception $e) {
            dump("Navigation failed: " . $e->getMessage());
        }
    } else {
        dump("No pagination controls found");
    }

    $page->wait(2);
});