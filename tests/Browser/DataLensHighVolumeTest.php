<?php

use App\Models\Shop\Product;
use App\Models\Team;
use App\Models\User;
use Padmission\DataLens\Models\CustomReport;
use Padmission\DataLens\Models\CustomReportSummary;

it('tests with very high number of summaries to force pagination', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'High Volume Pagination Test',
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

    // Create 100 summaries to definitely test pagination limits
    for ($i = 1; $i <= 100; $i++) {
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => "HIGH_VOLUME_SUMMARY_" . str_pad($i, 3, '0', STR_PAD_LEFT),
            'configuration' => ['type' => 'count', 'field' => 'id'],
            'processing_strategy' => 'sql',
            'cache_enabled' => true,
        ]);
    }

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(5) // Extra time for 100 summaries
        ->assertSee('High Volume Pagination Test')
        ->assertNoJavaScriptErrors();

    // Count how many summaries are actually visible
    $content = $page->content();
    $visibleSummaries = 0;
    for ($i = 1; $i <= 100; $i++) {
        $summaryName = "HIGH_VOLUME_SUMMARY_" . str_pad($i, 3, '0', STR_PAD_LEFT);
        if (str_contains($content, $summaryName)) {
            $visibleSummaries++;
        }
    }

    dump("Total summaries created: 100");
    dump("Summaries visible on page: $visibleSummaries");

    // Check for pagination controls
    $hasPaginationControls = str_contains($content, 'Next') || 
                            str_contains($content, 'Previous') ||
                            str_contains($content, '2') ||
                            str_contains($content, 'pagination');

    dump("Pagination controls detected: " . ($hasPaginationControls ? 'Yes' : 'No'));

    if ($visibleSummaries === 100) {
        dump("⚠️  ALL 100 summaries are showing on one page - pagination may not be working");
    } elseif ($visibleSummaries < 100 && $hasPaginationControls) {
        dump("✅ Pagination appears to be working - showing $visibleSummaries out of 100");
    } else {
        dump("🤔 Unclear pagination state");
    }

    // Basic assertions
    expect($visibleSummaries)->toBeGreaterThan(0, 'Should show at least some summaries');
    expect($visibleSummaries)->toBeLessThanOrEqual(100, 'Should not show more than 100 summaries total');

    $page->wait(2);
});

it('tests the actual clicking behavior with detailed logging', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $this->actingAs($user);

    $report = CustomReport::create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'name' => 'Click Behavior Test',
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

    // Create 50 summaries with very unique names
    for ($i = 1; $i <= 50; $i++) {
        CustomReportSummary::create([
            'custom_report_id' => $report->id,
            'name' => "CLICK_TEST_ITEM_NUMBER_" . str_pad($i, 2, '0', STR_PAD_LEFT),
            'configuration' => ['type' => 'count', 'field' => 'id'],
            'processing_strategy' => 'sql',
            'cache_enabled' => true,
        ]);
    }

    $page = visit("/app/{$team->id}/data-lens/{$report->id}?relation=1")
        ->wait(4)
        ->assertSee('Click Behavior Test')
        ->assertNoJavaScriptErrors();

    $initialContent = $page->content();
    $initialUrl = $page->url();

    // Count initial summaries
    $initialVisible = 0;
    for ($i = 1; $i <= 50; $i++) {
        if (str_contains($initialContent, "CLICK_TEST_ITEM_NUMBER_" . str_pad($i, 2, '0', STR_PAD_LEFT))) {
            $initialVisible++;
        }
    }

    dump("Initial summaries visible: $initialVisible");
    dump("Initial URL: $initialUrl");

    // Look for specific pagination elements
    $hasNext = str_contains($initialContent, 'Next');
    $hasPage2 = str_contains($initialContent, '>2<') || str_contains($initialContent, 'page=2');
    $hasPageNumbers = preg_match('/>\s*2\s*</', $initialContent);

    dump("Has 'Next' button: " . ($hasNext ? 'Yes' : 'No'));
    dump("Has page '2' link: " . ($hasPage2 ? 'Yes' : 'No'));
    dump("Has page numbers: " . ($hasPageNumbers ? 'Yes' : 'No'));

    // Try multiple approaches to click pagination
    $navigationAttempted = false;
    $navigationSuccessful = false;

    if ($hasNext) {
        try {
            dump("Attempting to click 'Next' button...");
            $page->click('Next')
                ->wait(3);
            $navigationAttempted = true;
            $navigationSuccessful = true;
            dump("✅ Successfully clicked 'Next'");
        } catch (Exception $e) {
            dump("❌ Failed to click 'Next': " . $e->getMessage());
        }
    }

    if (!$navigationSuccessful && ($hasPage2 || $hasPageNumbers)) {
        try {
            dump("Attempting to click page '2'...");
            $page->click('2')
                ->wait(3);
            $navigationAttempted = true;
            $navigationSuccessful = true;
            dump("✅ Successfully clicked page '2'");
        } catch (Exception $e) {
            dump("❌ Failed to click page '2': " . $e->getMessage());
        }
    }

    if ($navigationAttempted && $navigationSuccessful) {
        $newContent = $page->content();
        $newUrl = $page->url();
        
        // Count summaries after navigation
        $newVisible = 0;
        for ($i = 1; $i <= 50; $i++) {
            if (str_contains($newContent, "CLICK_TEST_ITEM_NUMBER_" . str_pad($i, 2, '0', STR_PAD_LEFT))) {
                $newVisible++;
            }
        }

        dump("After navigation - summaries visible: $newVisible");
        dump("After navigation - URL: $newUrl");

        $contentChanged = $initialContent !== $newContent;
        $urlChanged = $initialUrl !== $newUrl;
        
        dump("Content changed: " . ($contentChanged ? 'Yes' : 'No'));
        dump("URL changed: " . ($urlChanged ? 'Yes' : 'No'));

        if (!$contentChanged && !$urlChanged) {
            dump("⚠️  PAGINATION CLICK HAD NO EFFECT - possible bug!");
        } else {
            dump("✅ Pagination appears to be working");
        }
    } else {
        dump("⚠️  No successful navigation attempted");
    }

    $page->wait(2);
});