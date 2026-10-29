<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_user_username_and_name(): void
    {
        $admin = User::create([
            'username' => 'admin',
            'name' => 'Admin User',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = User::create([
            'username' => 'staff1',
            'name' => 'Original Staff Name',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $staff), [
            'username' => 'staff1_renamed',
            'name' => 'Updated Staff Name',
            'role' => 'staff',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $staff->refresh();
        $this->assertEquals('staff1_renamed', $staff->username);
        $this->assertEquals('Updated Staff Name', $staff->name);
    }

    public function test_username_must_be_unique_when_updating_other_user(): void
    {
        $admin = User::create([
            'username' => 'admin',
            'name' => 'Admin User',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff1 = User::create([
            'username' => 'staff1',
            'name' => 'Staff 1',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $staff2 = User::create([
            'username' => 'staff2',
            'name' => 'Staff 2',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $staff2), [
            'username' => 'staff1', // already taken
            'name' => 'Staff 2 Updated',
            'role' => 'staff',
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors(['username']);
    }
}
