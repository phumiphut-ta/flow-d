<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_username_and_password_without_email(): void
    {
        $user = User::create([
            'username' => 'teststaff',
            'name' => 'เจ้าหน้าที่ทดสอบ',
            'password' => Hash::make('password123'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'teststaff',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'username' => 'disableduser',
            'name' => 'ผู้ใช้ถูกปิด',
            'password' => Hash::make('password123'),
            'role' => 'staff',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'disableduser',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::create([
            'username' => 'staff01',
            'name' => 'นายสมคิด',
            'password' => Hash::make('oldpassword'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'oldpassword',
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newsecret123', $user->fresh()->password));
    }

    public function test_admin_can_reset_password_for_other_users(): void
    {
        $admin = User::create([
            'username' => 'admin',
            'name' => 'ผู้ดูแลระบบ',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = User::create([
            'username' => 'stafftarget',
            'name' => 'เป้าหมายรีเซ็ต',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post("/admin/users/{$staff->id}/reset-password", [
            'new_password' => 'resetNew999',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('resetNew999', $staff->fresh()->password));
    }
}
