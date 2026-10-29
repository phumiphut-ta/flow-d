<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_renders_successfully_for_authenticated_user(): void
    {
        $user = User::create([
            'username' => 'testadmin',
            'name' => 'ผู้ดูแลระบบ',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('งานระหว่างทาง');
        $response->assertSee('รับหนังสือเข้าด่วน (Intake)');
    }
}
