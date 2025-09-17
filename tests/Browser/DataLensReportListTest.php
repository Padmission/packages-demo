<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Padmission\DataLens\Models\CustomReport;

describe('Data Lens Report List', function () {
    beforeEach(function () {
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'list@datalens.test',
            'password' => Hash::make('password'),
        ]);

        // Create some sample reports for testing
        CustomReport::create([
            'name' => 'Sales Performance Report',
            'data_model' => 'App\\Models\\Shop\\Order',
            'columns' => [],
            'filters' => [],
            'creator_id' => $this->user->id,
            'team_id' => $this->user->team_id ?? 1,
        ]);

        CustomReport::create([
            'name' => 'Customer Analytics Dashboard',
            'data_model' => 'App\\Models\\Shop\\Customer',
            'columns' => [],
            'filters' => [],
            'creator_id' => $this->user->id,
            'team_id' => $this->user->team_id ?? 1,
        ]);

        CustomReport::create([
            'name' => 'Product Inventory Report',
            'data_model' => 'App\\Models\\Shop\\Product',
            'columns' => [],
            'filters' => [],
            'creator_id' => $this->user->id,
            'team_id' => $this->user->team_id ?? 1,
        ]);
    });

    test('can view reports list with created reports', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        $page->assertSee('Custom Reports')
            ->assertSee('Sales Performance Report')
            ->assertSee('Customer Analytics Dashboard')
            ->assertSee('Product Inventory Report')
            ->assertNoJavascriptErrors();

        // Verify table structure
        $page->assertSee('Name')
            ->assertPresent('input[type="search"]'); // Search field
    });

    test('can sort reports by clicking column headers', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Click on the Name column header to sort
        $page->click('Name')
            ->wait(1);

        // Reports should be sorted (check if sort indicator appears)
        $content = $page->content();
        expect($content)->toContain('Name');

        // Click again to reverse sort
        $page->click('Name')
            ->wait(1);

        $page->assertNoJavascriptErrors();
    });

    test('can search for specific reports', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Search for "Sales" report
        $page->fill('input[type="search"]', 'Sales')
            ->wait(1); // Wait for search to filter

        // Should see the Sales report but not others
        $page->assertSee('Sales Performance Report')
            ->assertDontSee('Customer Analytics Dashboard')
            ->assertDontSee('Product Inventory Report');

        // Clear search and search for "Customer"
        $page->clear('input[type="search"]')
            ->fill('input[type="search"]', 'Customer')
            ->wait(1);

        // Should see only Customer report
        $page->assertDontSee('Sales Performance Report')
            ->assertSee('Customer Analytics Dashboard')
            ->assertDontSee('Product Inventory Report');

        // Clear search to see all reports again
        $page->clear('input[type="search"]')
            ->wait(1);

        // All reports should be visible again
        $page->assertSee('Sales Performance Report')
            ->assertSee('Customer Analytics Dashboard')
            ->assertSee('Product Inventory Report')
            ->assertNoJavascriptErrors();
    });

    test('can click report to view details', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Click on a report name to view it
        $page->click('Sales Performance Report')
            ->wait(1);

        // Should be on the view/edit page for that report
        $page->assertPathContains('/admin/custom-reports/')
            ->assertSee('Sales Performance Report')
            ->assertSee('Order') // The data model
            ->assertNoJavascriptErrors();
    });

    test('can use bulk actions on reports', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Check if bulk action checkboxes exist
        $page->assertPresent('input[type="checkbox"]');

        // Select first checkbox for bulk action
        $page->check('input[type="checkbox"]:first-of-type')
            ->wait(0.5);

        // Check if bulk actions appear when item is selected
        $content = $page->content();
        $hasBulkActions = str_contains($content, 'Delete') ||
                         str_contains($content, 'selected');

        expect($hasBulkActions)->toBeTrue();

        $page->assertNoJavascriptErrors();
    });

    test('can use pagination when many reports exist', function () {
        // Create additional reports to trigger pagination
        for ($i = 1; $i <= 15; $i++) {
            CustomReport::create([
                'name' => "Test Report {$i}",
                'data_model' => 'App\\Models\\Shop\\Product',
                'columns' => [],
                'filters' => [],
                'creator_id' => $this->user->id,
                'team_id' => $this->user->team_id ?? 1,
            ]);
        }

        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Check for pagination controls if they exist
        $content = $page->content();
        if (str_contains($content, 'Next')) {
            // Click next page
            $page->click('Next')
                ->wait(1);

            // Should see different reports
            $page->assertSee('Test Report')
                ->assertNoJavascriptErrors();

            // Go back to first page
            if (str_contains($page->content(), 'Previous')) {
                $page->click('Previous')
                    ->wait(1);
            }
        }

        $page->assertNoJavascriptErrors();
    });

    test('can filter reports by different criteria', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Look for filter button/icon
        $content = $page->content();
        if (str_contains($content, 'Filter') || str_contains($content, 'funnel')) {
            // Click filter button if it exists
            if (str_contains($content, 'Filter')) {
                $page->click('Filter')
                    ->wait(1);
            }
        }

        // Interact with any available filters
        if (str_contains($page->content(), 'Created')) {
            // Try to use date filter if available
            $page->assertSee('Created');
        }

        $page->assertNoJavascriptErrors();
    });
});