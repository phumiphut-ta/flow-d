<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\User;
use App\Services\MovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_liff_tracking_is_accessible_without_authentication(): void
    {
        $response = $this->get('/liff/tracking');
        $response->assertOk();
        $response->assertSee('Flow-D Tracking');
    }

    public function test_liff_tracking_searches_and_displays_document_timeline(): void
    {
        $user = User::create([
            'username' => 'staff1',
            'name' => 'นายสมชาย (สารบรรณ)',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $route = Route::create([
            'route_name' => 'ลงเลขรับ-หอบ. ลงนาม-สารบรรณ-เสร็จสิ้น',
            'steps_json' => ['ลงเลขรับ', 'หอบ. ลงนาม', 'สารบรรณ', 'เสร็จสิ้น'],
            'is_active' => true,
        ]);

        $service = app(MovementService::class);
        $intake = $service->intake(
            '999/69',
            '2026-10-29',
            $route->id,
            $user,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        $service->advanceStep($movement->id, $user, null, '127.0.0.1');

        $response = $this->get('/liff/tracking?doc_number=999/69');
        $response->assertOk();
        $response->assertSee('999/69');
        $response->assertSee('บันทึกรับหนังสือเข้า');
        $response->assertSee('หอบ. ลงนาม');
        $response->assertSee('ผู้รับหนังสือเข้า');
        $response->assertSee('นายสมชาย (สารบรรณ)');
    }

    public function test_liff_tracking_shows_intake_user_even_after_advancing_steps(): void
    {
        $intakeUser = User::create([
            'username' => 'intake_staff',
            'name' => 'เจ้าหน้าที่รับเข้า กุลนันทน์',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $advanceUser = User::create([
            'username' => 'advance_staff',
            'name' => 'เจ้าหน้าที่ส่งต่อ ประภาส',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        // Route created without explicitly typing "บันทึกรับหนังสือเข้า"
        $route = Route::create([
            'route_name' => 'เส้นทางทดสอบอัตโนมัติ',
            'steps_json' => ['รอลงนาม', 'นำจ่าย'],
            'is_active' => true,
        ]);

        // Step 1 should automatically be "บันทึกรับหนังสือเข้า"
        $this->assertEquals('บันทึกรับหนังสือเข้า', $route->steps_json[0]);

        $service = app(MovementService::class);
        $intake = $service->intake(
            '777/69',
            '2026-10-29',
            $route->id,
            $intakeUser,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        // Advance step by another user
        $service->advanceStep($movement->id, $advanceUser, null, '127.0.0.1');

        $response = $this->get('/liff/tracking?doc_number=777/69');
        $response->assertOk();
        // Check that intake user is preserved on step 1 and round summary
        $response->assertSee('บันทึกรับหนังสือเข้า');
        $response->assertSee('ผู้รับหนังสือเข้า');
        $response->assertSee('เจ้าหน้าที่รับเข้า กุลนันทน์');
    }

    public function test_liff_tracking_returns_json_when_requested(): void
    {
        $user = User::create([
            'username' => 'staff1',
            'name' => 'นายสมชาย',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $route = Route::create([
            'route_name' => 'ผบป.อบ.-หอบ.',
            'steps_json' => ['หอบ.'],
            'is_active' => true,
        ]);

        $service = app(MovementService::class);
        $service->intake('888/69', '2026-10-29', $route->id, $user, null, '127.0.0.1');

        $response = $this->getJson('/liff/tracking?doc_number=888/69');
        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'document' => [
                'doc_number' => '888/69',
            ],
        ]);
    }
}
