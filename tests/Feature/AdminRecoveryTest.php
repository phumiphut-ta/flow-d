<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\User;
use App\Services\MovementService;
use App\Services\RecoveryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected Route $route;

    protected MovementService $movementService;

    protected RecoveryService $recoveryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin',
            'name' => 'ผู้ดูแลระบบ',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->staff = User::create([
            'username' => 'staff',
            'name' => 'เจ้าหน้าที่',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->route = Route::create([
            'route_name' => 'ลงเลขรับ-หอบ. ลงนาม-สารบรรณ-เสร็จสิ้น',
            'steps_json' => ['ลงเลขรับ', 'หอบ. ลงนาม', 'สารบรรณ', 'เสร็จสิ้น'],
            'is_active' => true,
        ]);

        $this->movementService = app(MovementService::class);
        $this->recoveryService = app(RecoveryService::class);
    }

    public function test_staff_cannot_perform_recovery(): void
    {
        $intake = $this->movementService->intake(
            '201/69',
            '2026-10-29',
            $this->route->id,
            $this->staff,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        $this->movementService->checkout($movement->id, $this->staff, null, '127.0.0.1');

        $result = $this->recoveryService->reopenCheckout(
            $movement->id,
            $this->staff,
            'เผลอกดผิด',
            null,
            '127.0.0.1'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('สำหรับผู้ดูแลระบบเท่านั้น', $result['error']);
    }

    public function test_admin_can_reopen_checkout_same_day_with_reason(): void
    {
        $intake = $this->movementService->intake(
            '202/69',
            '2026-10-29',
            $this->route->id,
            $this->staff,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        $this->movementService->checkout($movement->id, $this->staff, null, '127.0.0.1');
        $this->assertTrue($movement->fresh()->is_returned);

        // Admin reopens
        $result = $this->recoveryService->reopenCheckout(
            $movement->id,
            $this->admin,
            'หยิบแฟ้มผิดเล่มมากดติ๊กออก',
            null,
            '127.0.0.1'
        );

        $this->assertTrue($result['success']);
        $mFresh = $movement->fresh();
        $this->assertFalse($mFresh->is_returned);
        $this->assertNull($mFresh->out_datetime);
        $this->assertEquals('in_progress', $mFresh->document->current_status);

        // Check Activity Log
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'admin_reopen_checkout',
        ]);
    }

    public function test_admin_cannot_reopen_if_checkout_was_on_previous_day(): void
    {
        $intake = $this->movementService->intake(
            '203/69',
            '2026-10-28',
            $this->route->id,
            $this->staff,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        // Simulate checkout yesterday
        $movement->update([
            'is_returned' => true,
            'out_datetime' => Carbon::yesterday(),
            'out_by_user_id' => $this->staff->id,
        ]);

        $result = $this->recoveryService->reopenCheckout(
            $movement->id,
            $this->admin,
            'ต้องการกู้คืนย้อนหลัง',
            null,
            '127.0.0.1'
        );

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ข้ามวันแล้ว', $result['error']);
    }

    public function test_admin_can_rollback_single_step_same_day(): void
    {
        $intake = $this->movementService->intake(
            '204/69',
            '2026-10-29',
            $this->route->id,
            $this->staff,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        // Advance to step 1 ('หอบ. ลงนาม')
        $this->movementService->advanceStep($movement->id, $this->staff, null, '127.0.0.1');
        $this->assertEquals(1, $movement->fresh()->current_step_index);

        // Admin rollbacks
        $result = $this->recoveryService->rollbackStep(
            $movement->id,
            $this->admin,
            'ส่งผิดแผนก ต้องการดึงกลับขั้นตอนลงเลขรับ',
            null,
            '127.0.0.1'
        );

        $this->assertTrue($result['success']);
        $mFresh = $movement->fresh();
        $this->assertEquals(0, $mFresh->current_step_index);
        $this->assertEquals('บันทึกรับหนังสือเข้า', $mFresh->current_step_name);

        // Check latest step log is marked 'rollbacked'
        $this->assertDatabaseHas('movement_step_logs', [
            'movement_id' => $movement->id,
            'step_index' => 1,
            'status' => 'rollbacked',
        ]);

        // Check activity log
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'admin_step_rollback',
        ]);
    }

    public function test_admin_recovery_http_reopen_endpoint(): void
    {
        $intake = $this->movementService->intake(
            '205/69',
            '2026-10-29',
            $this->route->id,
            $this->staff,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        $this->movementService->checkout($movement->id, $this->staff, null, '127.0.0.1');

        $response = $this->actingAs($this->admin)->post("/admin/recovery/movements/{$movement->id}/reopen", [
            'reason' => 'ส่งออกผิดเล่ม ต้องการนำกลับมาแก้ไข',
        ]);

        $response->assertSessionHas('success');
        $this->assertFalse($movement->fresh()->is_returned);
    }

    public function test_admin_recovery_http_rollback_endpoint(): void
    {
        $intake = $this->movementService->intake(
            '206/69',
            '2026-10-29',
            $this->route->id,
            $this->staff,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        $this->movementService->advanceStep($movement->id, $this->staff, null, '127.0.0.1');

        $response = $this->actingAs($this->admin)->post("/admin/recovery/movements/{$movement->id}/rollback", [
            'reason' => 'ส่งข้ามขั้นตอน ต้องการย้อนกลับ',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(0, $movement->fresh()->current_step_index);
    }

    public function test_recovery_requires_mandatory_reason_at_least_3_characters(): void
    {
        $intake = $this->movementService->intake(
            '207/69',
            '2026-10-29',
            $this->route->id,
            $this->staff,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        $this->movementService->checkout($movement->id, $this->staff, null, '127.0.0.1');

        // Reason too short
        $response = $this->actingAs($this->admin)->post("/admin/recovery/movements/{$movement->id}/reopen", [
            'reason' => 'ab',
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertTrue($movement->fresh()->is_returned);
    }

    public function test_staff_cannot_access_recovery_http_routes(): void
    {
        $intake = $this->movementService->intake(
            '208/69',
            '2026-10-29',
            $this->route->id,
            $this->staff,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        $this->movementService->checkout($movement->id, $this->staff, null, '127.0.0.1');

        $response = $this->actingAs($this->staff)->post("/admin/recovery/movements/{$movement->id}/reopen", [
            'reason' => 'พยายามกู้คืน',
        ]);

        $response->assertStatus(403);
    }
}
