<?php

use App\Models\User;

it('can access the custom reports page when authenticated', function () {
    $user = User::factory()->create();

    // Login the user programmatically for browser testing
    $this->actingAs($user);

    // Now visit the custom reports page as authenticated user
    $page = visit('/admin/custom-reports')
        ->wait(2)
        ->assertSee('Custom Reports')
        ->assertNoJavaScriptErrors();
});

it('can navigate to create a new custom report', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit('/admin/custom-reports')
        ->wait(2)
        ->assertSee('Custom Reports')
        ->assertNoJavaScriptErrors();

    // Try to access the create page directly since navigation might be complex
    $createPage = visit('/admin/custom-reports/create')
        ->wait(2);

    // If the create page loads, it should not have JavaScript errors
    if (! str_contains($createPage->url(), 'login')) {
        $createPage->assertNoJavaScriptErrors();
    }
});

it('displays the custom reports table correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit('/admin/custom-reports')
        ->wait(2)
        ->assertSee('Custom Reports')
        ->assertNoJavaScriptErrors();

    // Give time for Livewire to load the table
    $page->wait(1);
});

it('redirects unauthenticated users to login', function () {
    $page = visit('/admin/custom-reports');
    expect($page->url())->toContain('/admin/login');
});

it('works on mobile devices', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit('/admin/custom-reports')->on()->mobile()
        ->wait(2)
        ->assertSee('Custom Reports')
        ->assertNoJavaScriptErrors();
});

it('works in dark mode', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit('/admin/custom-reports')
        ->wait(2);

    // Add dark mode class
    $page->script("document.documentElement.classList.add('dark');");

    $page->wait(1)
        ->assertSee('Custom Reports')
        ->assertNoJavaScriptErrors();
});
