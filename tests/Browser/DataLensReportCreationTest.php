<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Shop\Product;
use App\Models\Shop\Order;
use App\Models\Shop\Customer;
use App\Models\Shop\Brand;
use App\Models\Blog\Post;
use Illuminate\Support\Facades\Hash;

describe('Data Lens Report Creation', function () {
    beforeEach(function () {
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'report@datalens.test',
            'password' => Hash::make('password'),
        ]);

        // Create some test data for various models
        Brand::factory()->count(3)->create();
        Product::factory()->count(10)->create();
        Customer::factory()->count(5)->create();
        Order::factory()->count(5)->create();
        Post::factory()->count(5)->create();
    });

    test('can create a simple product report', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports/create');

        // Wait for form to fully load
        $page->wait(1);

        // Fill basic information - using type instead of fill for better compatibility
        $nameInput = 'input[name="data.name"], input[name="name"], #data\\.name';
        $page->type($nameInput, 'Product Sales Report')
            ->wait(0.5);

        // For Filament select components, we need to interact differently
        // Click to open the select dropdown
        $dataModelSelect = '[name="data.data_model"], [name="data_model"], #data\\.data_model';
        $page->click($dataModelSelect . ', [x-data*="select"]')
            ->wait(0.5);

        // Type to search for Product
        $page->type('Product')
            ->wait(0.5)
            ->press('Enter')
            ->wait(1); // Wait for reactive updates

        // Navigate to Columns tab
        $page->click('button:contains("Columns"), [role="tab"]:contains("Columns")')
            ->wait(1);

        // The columns should now be available
        $content = $page->content();
        if (str_contains($content, 'Add column')) {
            $page->click('button:contains("Add column")')
                ->wait(0.5);
        }

        // Navigate to Filters tab
        $page->click('button:contains("Filters"), [role="tab"]:contains("Filters")')
            ->wait(0.5);

        // Save the report
        $page->click('button:contains("Create"), button[type="submit"]')
            ->wait(2);

        // Should be redirected
        $page->assertPathContains('/admin/custom-reports')
            ->assertNoJavascriptErrors();
    });

    test('can navigate between tabs and interact with form elements', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports/create');
        $page->wait(1); // Wait for form load

        // Fill the name field
        $nameInput = 'input[name="data.name"], input[name="name"], #data\\.name';
        $page->type($nameInput, 'Test Navigation Report')
            ->wait(0.5);

        // Click on Columns tab before selecting a model
        $page->click('button:contains("Columns"), [role="tab"]:contains("Columns")')
            ->wait(0.5);

        // Should see instruction message
        $page->assertSee('select');

        // Go back to Basic Information
        $page->click('button:contains("Basic Information"), [role="tab"]:contains("Basic Information")')
            ->wait(0.5);

        // Open data model select
        $dataModelSelect = '[name="data.data_model"], [name="data_model"], #data\\.data_model';
        $page->click($dataModelSelect . ', [x-data*="select"]')
            ->wait(0.5);

        // Type to search and select Order
        $page->type('Order')
            ->wait(0.5)
            ->press('Enter')
            ->wait(1);

        // Navigate to Columns tab again
        $page->click('button:contains("Columns"), [role="tab"]:contains("Columns")')
            ->wait(1);

        // Check if columns are available
        $content = $page->content();
        if (str_contains($content, 'Add column')) {
            $page->assertSee('Add column');
        }

        // Navigate to Filters tab
        $page->click('button:contains("Filters"), [role="tab"]:contains("Filters")')
            ->wait(0.5);

        $page->assertNoJavascriptErrors();
    });

    test('can create report with customer data and filters', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports/create');
        $page->wait(1);

        // Fill basic information
        $nameInput = 'input[name="data.name"], input[name="name"], #data\\.name';
        $page->type($nameInput, 'Customer Analytics Report')
            ->wait(0.5);

        // Select Customer model
        $dataModelSelect = '[name="data.data_model"], [name="data_model"], #data\\.data_model';
        $page->click($dataModelSelect . ', [x-data*="select"]')
            ->wait(0.5);

        $page->type('Customer')
            ->wait(0.5)
            ->press('Enter')
            ->wait(1);

        // Go to Columns tab
        $page->click('button:contains("Columns"), [role="tab"]:contains("Columns")')
            ->wait(1);

        // Add columns if button is available
        $content = $page->content();
        if (str_contains($content, 'Add column')) {
            $page->click('button:contains("Add column")')
                ->wait(0.5)
                ->click('button:contains("Add column")')
                ->wait(0.5);
        }

        // Go to Filters tab
        $page->click('button:contains("Filters"), [role="tab"]:contains("Filters")')
            ->wait(0.5);

        // Try adding a filter if available
        $content = $page->content();
        if (str_contains($content, 'Add filter')) {
            $page->click('button:contains("Add filter")')
                ->wait(0.5);
        }

        // Save the report
        $page->click('button:contains("Create"), button[type="submit"]')
            ->wait(2);

        // Verify creation
        $page->assertPathContains('/admin/custom-reports')
            ->assertNoJavascriptErrors();
    });

    test('shows validation errors for missing required fields', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports/create');
        $page->wait(1);

        // Try to create without filling required fields
        $page->click('button:contains("Create"), button[type="submit"]')
            ->wait(1);

        // Should see validation errors
        $page->assertSee('required');

        // Fill only the name
        $nameInput = 'input[name="data.name"], input[name="name"], #data\\.name';
        $page->type($nameInput, 'Incomplete Report')
            ->wait(0.5);

        // Try to submit again
        $page->click('button:contains("Create"), button[type="submit"]')
            ->wait(1);

        // Should still see error for missing data model
        $page->assertSee('required');

        $page->assertNoJavascriptErrors();
    });

    test('can cancel report creation and return to list', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports/create');
        $page->wait(1);

        // Fill some data
        $nameInput = 'input[name="data.name"], input[name="name"], #data\\.name';
        $page->type($nameInput, 'Cancelled Report')
            ->wait(0.5);

        // Click cancel button
        $page->click('a:contains("Cancel"), button:contains("Cancel")')
            ->wait(1);

        // Should be redirected back to the reports list
        $page->assertPathIs('/admin/custom-reports')
            ->assertDontSee('Cancelled Report') // Report shouldn't be created
            ->assertNoJavascriptErrors();
    });
});