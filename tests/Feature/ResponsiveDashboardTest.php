<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_dashboard_contains_an_accessible_mobile_navigation_drawer(): void
    {
        $permission = Permission::factory()->create(['slug' => 'platform.dashboard.view']);
        $role = Role::factory()->create([
            'slug' => 'super-admin',
            'scope' => Role::PLATFORM_SCOPE,
        ]);
        $role->permissions()->attach($permission);

        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/platform');

        $response->assertOk()
            ->assertSee('Open navigation menu', false)
            ->assertSee('Mobile navigation', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('lg:hidden', false)
            ->assertSee('min-h-11', false);
    }
}
