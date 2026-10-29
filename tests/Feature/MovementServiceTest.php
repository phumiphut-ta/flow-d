<?php

namespace Tests\Feature;

use App\Models\DocumentMovement;
use App\Models\Route;
use App\Models\User;
use App\Services\MovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MovementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Route $pipelineRoute;

    protected Route $p2pRoute;

    protected MovementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'username' => 'staff1',
            'name' => 'เจ้าหน้าที่ทดสอบ',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->pipelineRoute = Route::create([
            'route_name' => 'ลงเลขรับ-หอบ. ลงนาม-สารบรรณ-เสร็จสิ้น',
            'steps_json' => ['ลงเลขรับ', 'หอบ. ลงนาม', 'สารบรรณ', 'เสร็จสิ้น'],
            'is_active' => true,
        ]);

        $this->p2pRoute = Route::create([
            'route_name' => 'ผบป.อบ.-หอบ.',
            'steps_json' => ['หอบ.'],
            'is_active' => true,
        ]);

        $this->service = app(MovementService::class);
    }

    public function test_intake_creates_document_and_first_movement(): void
    {
        $result = $this->service->intake(
            '101/69',
            '2026-10-29',
            $this->pipelineRoute->id,
            $this->user,
            null,
            '127.0.0.1'
        );

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('documents', [
            'doc_number' => '101/69',
            'current_status' => 'in_progress',
        ]);

        $movement = DocumentMovement::first();
        $this->assertNotNull($movement);
        $this->assertEquals(1, $movement->round_number);
        $this->assertEquals(0, $movement->current_step_index);
        $this->assertEquals('บันทึกรับหนังสือเข้า', $movement->current_step_name);
        $this->assertFalse($movement->is_returned);

        // Step log checkpoint
        $this->assertDatabaseHas('movement_step_logs', [
            'movement_id' => $movement->id,
            'step_index' => 0,
            'step_name' => 'บันทึกรับหนังสือเข้า',
            'status' => 'current',
        ]);
    }

    public function test_reentry_rule_rejects_when_previous_round_is_not_returned(): void
    {
        // First intake
        $this->service->intake(
            '102/69',
            '2026-10-29',
            $this->pipelineRoute->id,
            $this->user,
            null,
            '127.0.0.1'
        );

        // Attempt second intake before checkout
        $reentry = $this->service->intake(
            '102/69',
            '2026-10-29',
            $this->p2pRoute->id,
            $this->user,
            null,
            '127.0.0.1'
        );

        $this->assertFalse($reentry['success']);
        $this->assertStringContainsString('รอบก่อนหน้า (รอบที่ 1) ยังไม่ได้ส่งออกหนังสือ', $reentry['error']);
    }

    public function test_reentry_rule_allows_new_round_after_checkout(): void
    {
        // First intake
        $intake1 = $this->service->intake(
            '103/69',
            '2026-10-29',
            $this->p2pRoute->id,
            $this->user,
            null,
            '127.0.0.1'
        );

        // Checkout round 1
        $movement1 = $intake1['movement'];
        $checkout = $this->service->checkout($movement1->id, $this->user, null, '127.0.0.1');
        $this->assertTrue($checkout['success']);
        $this->assertTrue($movement1->fresh()->is_returned);

        // Second intake (Round 2)
        $intake2 = $this->service->intake(
            '103/69',
            '2026-10-29',
            $this->pipelineRoute->id,
            $this->user,
            null,
            '127.0.0.1'
        );

        $this->assertTrue($intake2['success']);
        $movement2 = $intake2['movement'];
        $this->assertEquals(2, $movement2->round_number);
        $this->assertEquals($this->pipelineRoute->id, $movement2->route_id);
    }

    public function test_anti_double_click_checkout_is_idempotent(): void
    {
        $intake = $this->service->intake(
            '104/69',
            '2026-10-29',
            $this->p2pRoute->id,
            $this->user,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];

        // First click
        $res1 = $this->service->checkout($movement->id, $this->user, null, '127.0.0.1');
        $this->assertTrue($res1['success']);
        $this->assertFalse(isset($res1['idempotent']));

        // Second duplicate click from network lag
        $res2 = $this->service->checkout($movement->id, $this->user, null, '127.0.0.1');
        $this->assertTrue($res2['success']);
        $this->assertTrue($res2['idempotent']);
    }

    public function test_pipeline_advances_step_by_step_to_completion(): void
    {
        $intake = $this->service->intake(
            '105/69',
            '2026-10-29',
            $this->pipelineRoute->id,
            $this->user,
            null,
            '127.0.0.1'
        );

        $movement = $intake['movement'];
        $this->assertEquals(0, $movement->current_step_index);

        // Advance 1: to step index 1 ('หอบ. ลงนาม')
        $adv1 = $this->service->advanceStep($movement->id, $this->user, null, '127.0.0.1');
        $this->assertTrue($adv1['success']);
        $mFresh = $movement->fresh();
        $this->assertEquals(1, $mFresh->current_step_index);
        $this->assertEquals('หอบ. ลงนาม', $mFresh->current_step_name);
        $this->assertFalse($mFresh->is_returned);

        // Advance 2: to step index 2 ('สารบรรณ')
        $adv2 = $this->service->advanceStep($movement->id, $this->user, null, '127.0.0.1');
        $this->assertTrue($adv2['success']);
        $mFresh = $movement->fresh();
        $this->assertEquals(2, $mFresh->current_step_index);
        $this->assertEquals('สารบรรณ', $mFresh->current_step_name);
        $this->assertFalse($mFresh->is_returned);

        // Advance 3: to final step index 3 ('เสร็จสิ้น') -> marks returned
        $adv3 = $this->service->advanceStep($movement->id, $this->user, null, '127.0.0.1');
        $this->assertTrue($adv3['success']);
        $mFresh = $movement->fresh();
        $this->assertEquals(3, $mFresh->current_step_index);
        $this->assertEquals('เสร็จสิ้น', $mFresh->current_step_name);
        $this->assertTrue($mFresh->is_returned);
        $this->assertNotNull($mFresh->out_datetime);
        $this->assertEquals('completed', $mFresh->document->fresh()->current_status);
    }
}
