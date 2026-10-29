<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentMovement;
use App\Models\Route;
use App\Models\User;
use App\Services\MovementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TypoCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected User $otherStaff;

    protected Route $route;

    protected MovementService $movementService;

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
            'username' => 'staff1',
            'name' => 'เจ้าหน้าที่ 1',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->otherStaff = User::create([
            'username' => 'staff2',
            'name' => 'เจ้าหน้าที่ 2',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->route = Route::create([
            'route_name' => 'เส้นทางทดสอบ',
            'steps_json' => ['ลงเลขรับ', 'หอบ. ลงนาม', 'ส่งออก'],
            'is_active' => true,
        ]);

        $this->movementService = app(MovementService::class);
    }

    private function createIntake(string $docNumber, User $user): DocumentMovement
    {
        $result = $this->movementService->intake(
            $docNumber,
            Carbon::now()->toDateString(),
            $this->route->id,
            $user,
            null,
            '127.0.0.1'
        );

        return $result['movement'];
    }

    public function test_staff_within_30_minutes_can_correct_and_void(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00'));

        $movement = $this->createIntake('DOC-101', $this->staff);

        // At 10:15 (15 mins later)
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:15:00'));

        $this->assertTrue($movement->canCorrectIntake($this->staff));
        $this->assertTrue($movement->canVoidIntake($this->staff));

        // Different staff cannot correct or void
        $this->assertFalse($movement->canCorrectIntake($this->otherStaff));
        $this->assertFalse($movement->canVoidIntake($this->otherStaff));
    }

    public function test_staff_after_30_minutes_cannot_correct_or_void(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00'));

        $movement = $this->createIntake('DOC-102', $this->staff);

        // At 10:31 (31 mins later)
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:31:00'));

        $this->assertFalse($movement->canCorrectIntake($this->staff));
        $this->assertFalse($movement->canVoidIntake($this->staff));
    }

    public function test_admin_can_correct_and_void_same_day_even_after_30_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 09:00:00'));

        $movement = $this->createIntake('DOC-103', $this->staff);

        // At 16:00 (7 hours later on same day)
        Carbon::setTestNow(Carbon::parse('2026-10-29 16:00:00'));

        $this->assertTrue($movement->canCorrectIntake($this->admin));
        $this->assertTrue($movement->canVoidIntake($this->admin));

        // Next day at 09:00 - admin cannot correct or void
        Carbon::setTestNow(Carbon::parse('2026-10-30 09:00:00'));

        $this->assertFalse($movement->canCorrectIntake($this->admin));
        $this->assertFalse($movement->canVoidIntake($this->admin));
    }

    public function test_cannot_void_if_movement_has_advanced_past_step_zero(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00'));

        $movement = $this->createIntake('DOC-104', $this->staff);

        // Advance to step 1
        $this->movementService->advanceStep($movement->id, $this->staff, null, '127.0.0.1');
        $movement->refresh();

        $this->assertEquals(1, $movement->current_step_index);
        $this->assertFalse($movement->canVoidIntake($this->staff));
        $this->assertFalse($movement->canVoidIntake($this->admin));
    }

    public function test_edit_doc_number_successfully_updates_and_creates_log(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00'));

        $movement = $this->createIntake('DOC-MISTYPED', $this->staff);

        $response = $this->actingAs($this->staff)->post(route('dashboard.movements.edit_doc_number', $movement), [
            'new_doc_number' => 'DOC-CORRECTED',
            'reason' => 'พิมพ์เลขผิดตอนรับเข้า',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'id' => $movement->document_id,
            'doc_number' => 'DOC-CORRECTED',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'doc_number_edit',
            'user_id' => $this->staff->id,
        ]);

        $log = ActivityLog::where('action', 'doc_number_edit')->first();
        $this->assertStringContainsString('DOC-MISTYPED', $log->description);
        $this->assertStringContainsString('DOC-CORRECTED', $log->description);
        $this->assertStringContainsString('พิมพ์เลขผิดตอนรับเข้า', $log->description);
    }

    public function test_edit_doc_number_blocks_duplicate_number(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00'));

        // Existing document
        $this->createIntake('EXISTING-001', $this->admin);

        // Mistyped document
        $movement = $this->createIntake('TYPO-002', $this->staff);

        // Try to rename TYPO-002 to EXISTING-001
        $response = $this->actingAs($this->staff)->post(route('dashboard.movements.edit_doc_number', $movement), [
            'new_doc_number' => 'EXISTING-001',
            'reason' => 'ต้องการเปลี่ยนเป็นเลขที่มีอยู่แล้ว',
        ]);

        $response->assertSessionHasErrors(['new_doc_number']);

        // Database value should remain unchanged
        $this->assertDatabaseHas('documents', [
            'id' => $movement->document_id,
            'doc_number' => 'TYPO-002',
        ]);
    }

    public function test_void_intake_deletes_movement_and_round_1_document_with_audit_log(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00'));

        $movement = $this->createIntake('VOID-TARGET', $this->staff);

        $docId = $movement->document_id;
        $movementId = $movement->id;

        $response = $this->actingAs($this->staff)->post(route('dashboard.movements.void', $movement), [
            'reason' => 'เจ้าหน้าที่นำหนังสือมาผิดหน่วยงาน ขอยกเลิกรายการรับ',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Movement and Document should be deleted
        $this->assertDatabaseMissing('document_movements', ['id' => $movementId]);
        $this->assertDatabaseMissing('documents', ['id' => $docId]);

        // Audit log created
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'intake_void',
            'user_id' => $this->staff->id,
        ]);

        $log = ActivityLog::where('action', 'intake_void')->first();
        $this->assertStringContainsString('VOID-TARGET', $log->description);
        $this->assertStringContainsString('ขอยกเลิกรายการรับ', $log->description);
    }

    public function test_unauthorized_user_cannot_edit_or_void(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00'));

        $movement = $this->createIntake('AUTH-CHECK', $this->staff);

        // Other staff attempts edit
        $responseEdit = $this->actingAs($this->otherStaff)->post(route('dashboard.movements.edit_doc_number', $movement), [
            'new_doc_number' => 'AUTH-EDITED',
            'reason' => 'แอบแก้ไข',
        ]);
        $responseEdit->assertForbidden();

        // Other staff attempts void
        $responseVoid = $this->actingAs($this->otherStaff)->post(route('dashboard.movements.void', $movement), [
            'reason' => 'แอบยกเลิก',
        ]);
        $responseVoid->assertForbidden();
    }
}
