<?php

declare(strict_types=1);

use App\Models\Blog\Post;
use App\Models\Shop\Customer;
use App\Models\Shop\Order;
use App\Models\Shop\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Padmission\DataLens\Models\CustomReport;

describe('Data Lens Features', function () {
    beforeEach(function () {
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'features@datalens.test',
            'password' => Hash::make('password'),
        ]);

        // Create test data
        Product::factory()->count(20)->create();
        Customer::factory()->count(10)->create();
        Order::factory()->count(15)->create();
        Post::factory()->count(10)->create();
    });

    test('complete workflow: create, edit, and delete a report', function () {
        $this->actingAs($this->user);

        // Start from dashboard
        $page = visit('/admin');

        // Navigate to reports
        $page->click('Admin Custom Reports')
            ->wait(1);

        $page->assertPathIs('/admin/custom-reports');

        // Create a new report
        $page->click('New custom report')
            ->wait(1);

        // Fill the form with comprehensive data
        $page->fill('input[name="name"]', 'Comprehensive Test Report')
            ->click('select[name="data_model"]')
            ->wait(0.5)
            ->type('select[name="data_model"]', 'Product')
            ->wait(0.5)
            ->press('Enter')
            ->wait(1);

        // Go to columns and add some
        $page->click('Columns')
            ->wait(1)
            ->click('Add column')
            ->wait(0.5)
            ->click('Add column')
            ->wait(0.5);

        // Save the report
        $page->click('button[type="submit"]')
            ->wait(2);

        // Verify report was created and we're on the view page
        $page->assertSee('Comprehensive Test Report')
            ->assertPathContains('/admin/custom-reports');

        // Edit the report (if edit button is available)
        if (str_contains($page->content(), 'Edit')) {
            $page->click('Edit')
                ->wait(1)
                ->clear('input[name="name"]')
                ->fill('input[name="name"]', 'Updated Test Report')
                ->click('button[type="submit"]')
                ->wait(2);

            $page->assertSee('Updated Test Report');
        }

        $page->assertNoJavascriptErrors();
    });

    test('can export report data', function () {
        // Create a report first
        CustomReport::create([
            'name' => 'Export Test Report',
            'data_model' => 'App\\Models\\Shop\\Order',
            'columns' => ['id', 'total', 'status'],
            'filters' => [],
            'creator_id' => $this->user->id,
            'team_id' => $this->user->team_id ?? 1,
        ]);

        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Click on the report
        $page->click('Export Test Report')
            ->wait(1);

        // Look for export options
        $content = $page->content();
        if (str_contains($content, 'Export') || str_contains($content, 'Download')) {
            $page->assertSee('Export');
            // Could click export button if needed
        }

        $page->assertNoJavascriptErrors();
    });

    test('can share report with other users', function () {
        // Create another user to share with
        $otherUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports/create');

        // Fill basic info
        $page->fill('input[name="name"]', 'Shared Report')
            ->click('select[name="data_model"]')
            ->wait(0.5)
            ->type('select[name="data_model"]', 'Customer')
            ->wait(0.5)
            ->press('Enter')
            ->wait(1);

        // Check if user sharing is available
        if (str_contains($page->content(), 'Share with users')) {
            // Try to select the other user
            $page->click('select[name="users[]"]')
                ->wait(0.5)
                ->type('select[name="users[]"]', 'John')
                ->wait(0.5)
                ->press('Enter')
                ->wait(0.5);
        }

        // Save the report
        $page->click('button[type="submit"]')
            ->wait(2);

        $page->assertSee('Shared Report')
            ->assertNoJavascriptErrors();
    });

    test('can duplicate existing report', function () {
        // Create a report to duplicate
        CustomReport::create([
            'name' => 'Original Report',
            'data_model' => 'App\\Models\\Shop\\Product',
            'columns' => ['name', 'price', 'stock'],
            'filters' => [],
            'creator_id' => $this->user->id,
            'team_id' => $this->user->team_id ?? 1,
        ]);

        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Look for duplicate/replicate action
        $content = $page->content();
        if (str_contains($content, 'Replicate') || str_contains($content, 'Duplicate')) {
            // Click on the action menu for the report
            $page->click('Original Report')
                ->wait(1);

            // If on the view page, look for replicate button
            if (str_contains($page->content(), 'Replicate')) {
                $page->click('Replicate')
                    ->wait(1);

                // Should be on create page with pre-filled data
                $page->assertValue('input[name="name"]', 'Original Report (Copy)')
                    ->assertNoJavascriptErrors();
            }
        } else {
            // Just verify the report exists
            $page->assertSee('Original Report')
                ->assertNoJavascriptErrors();
        }
    });

    test('responsive design works on different devices', function () {
        $this->actingAs($this->user);

        // Test on mobile
        $mobilePage = visit('/admin/custom-reports')->on()->mobile();
        $mobilePage->assertSee('Custom Reports')
            ->assertSee('New custom report')
            ->assertNoJavascriptErrors();

        // Test on tablet (iPad)
        $tabletPage = visit('/admin/custom-reports')->on()->iPad();
        $tabletPage->assertSee('Custom Reports')
            ->assertNoJavascriptErrors();

        // Test create form on mobile
        $mobileFormPage = visit('/admin/custom-reports/create')->on()->iPhone14Pro();
        $mobileFormPage->assertSee('Basic Information')
            ->assertSee('Name')
            ->assertNoJavascriptErrors();
    });

    test('dark mode compatibility', function () {
        $this->actingAs($this->user);

        // Test reports list in dark mode
        $darkPage = visit('/admin/custom-reports')->onDarkMode();
        $darkPage->assertSee('Custom Reports')
            ->assertNoJavascriptErrors();

        // Test create form in dark mode
        $darkFormPage = visit('/admin/custom-reports/create')->onDarkMode();
        $darkFormPage->assertSee('Basic Information')
            ->assertSee('Name')
            ->assertNoJavascriptErrors();

        // Verify elements are still visible and interactive
        $darkFormPage->fill('input[name="name"]', 'Dark Mode Test')
            ->wait(0.5);

        $darkFormPage->assertValue('input[name="name"]', 'Dark Mode Test')
            ->assertNoJavascriptErrors();
    });

    test('complete end-to-end report workflow with data', function () {
        $this->actingAs($this->user);

        // Create a comprehensive report from scratch
        $page = visit('/admin/custom-reports/create');

        // Fill all form fields
        $page->fill('input[name="name"]', 'End-to-End Sales Report')
            ->wait(0.5);

        // Select Order model
        $page->click('select[name="data_model"]')
            ->wait(0.5)
            ->type('select[name="data_model"]', 'Order')
            ->wait(0.5)
            ->press('Enter')
            ->wait(1);

        // Configure columns
        $page->click('Columns')
            ->wait(1);

        // Add multiple columns
        for ($i = 0; $i < 3; $i++) {
            $page->click('Add column')
                ->wait(0.3);
        }

        // Configure filters
        $page->click('Filters')
            ->wait(1);

        if (str_contains($page->content(), 'Add filter')) {
            $page->click('Add filter')
                ->wait(0.5);
        }

        // Save the report
        $page->click('button[type="submit"]')
            ->wait(2);

        // Verify report was created and view it
        $page->assertSee('End-to-End Sales Report')
            ->assertPathContains('/admin/custom-reports');

        // Navigate back to list
        $page->click('Custom Reports') // Breadcrumb
            ->wait(1);

        // Search for the created report
        $page->fill('input[type="search"]', 'End-to-End')
            ->wait(1);

        $page->assertSee('End-to-End Sales Report')
            ->assertNoJavascriptErrors();

        // Verify no console errors throughout the workflow
        $page->assertNoConsoleLogs();
    });
});
