<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Data Lens Dashboard', function () {
    beforeEach(function () {
        // Create a test user with known credentials
        $this->user = User::factory()->create([
            'email' => 'admin@datalens.test',
            'password' => Hash::make('password'),
        ]);
    });

    test('can access admin login page', function () {
        $page = visit('/admin/login');

        $page->assertSee('Sign in')
            ->assertSee('Email')
            ->assertSee('Password')
            ->assertSee('Remember me')
            ->assertPresent('input[type="email"]')
            ->assertPresent('input[type="password"]')
            ->assertPresent('button[type="submit"]')
            ->assertNoJavascriptErrors();
    });

    test('can login and access admin dashboard with form interaction', function () {
        $page = visit('/admin/login');

        // Fill the login form with real interactions
        $page->fill('input[type="email"]', 'admin@datalens.test')
            ->fill('input[type="password"]', 'password')
            ->check('input[type="checkbox"]') // Remember me
            ->click('button[type="submit"]')
            ->wait(2); // Wait for redirect

        // Verify we're now on the dashboard
        $page->assertPathBeginsWith('/admin')
            ->assertSee('Dashboard')
            ->assertDontSee('Sign in')
            ->assertNoJavascriptErrors();

        // Verify user is authenticated
        $this->assertAuthenticated();
    });

    test('can see Data Lens navigation menu when authenticated', function () {
        $this->actingAs($this->user);

        $page = visit('/admin');

        $page->assertSee('Admin Custom Reports')
            ->assertNoJavascriptErrors();

        // Check for Analytics navigation group
        expect($page->content())
            ->toContain('Analytics')
            ->toContain('Admin Custom Reports');
    });

    test('can navigate to custom reports list', function () {
        $this->actingAs($this->user);

        $page = visit('/admin');

        // Click on the Admin Custom Reports navigation item
        $page->click('Admin Custom Reports')
            ->wait(1); // Wait for navigation

        $page->assertPathIs('/admin/custom-reports')
            ->assertSee('Custom Reports')
            ->assertNoJavascriptErrors();

        // Check for table components and action button
        $page->assertSee('New custom report')
            ->assertPresent('input[type="search"]'); // Search field

        // Verify table structure or empty state
        $content = $page->content();
        $hasExpectedContent = str_contains($content, 'New custom report') ||
                             str_contains($content, 'No custom reports') ||
                             str_contains($content, 'Name');
        expect($hasExpectedContent)->toBeTrue();
    });

    test('can access create report page', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Click on the create button
        $page->click('New custom report')
            ->wait(1); // Wait for form to load

        $page->assertPathIs('/admin/custom-reports/create')
            ->assertSee('Create')
            ->assertSee('Name')
            ->assertSee('Data model')
            ->assertPresent('input[name="name"]')
            ->assertPresent('select[name="data_model"]')
            ->assertNoJavascriptErrors();

        // Verify tabs are present
        $page->assertSee('Basic Information')
            ->assertSee('Columns')
            ->assertSee('Filters');
    });

    test('displays proper breadcrumbs navigation', function () {
        $this->actingAs($this->user);

        $page = visit('/admin/custom-reports');

        // Check for breadcrumb navigation
        expect($page->content())
            ->toContain('Custom Reports');

        // Navigate to create page and check breadcrumbs update
        $page->click('New custom report');
        $page->wait(1);

        expect($page->content())
            ->toContain('Custom Reports')
            ->toContain('Create');
    });
});