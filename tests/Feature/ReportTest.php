<?php

namespace Tests\Feature;

use App\Models\Route;
use App\Models\User;
use App\Services\MovementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected User $admin;

    protected Route $route1;

    protected Route $route2;

    protected MovementService $movementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::create([
            'username' => 'staff1',
            'name' => 'เจ้าหน้าที่ รายงาน',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'username' => 'admin',
            'name' => 'ผู้ดูแลระบบ รายงาน',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->route1 = Route::create([
            'route_name' => 'เส้นทาง ก',
            'steps_json' => ['บันทึกรับหนังสือเข้า', 'รอลงนาม', 'เสร็จสิ้น'],
            'is_active' => true,
        ]);

        $this->route2 = Route::create([
            'route_name' => 'เส้นทาง ข (จุดเดียว)',
            'steps_json' => ['บันทึกรับหนังสือเข้า'],
            'is_active' => true,
        ]);

        $this->movementService = app(MovementService::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('reports.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_staff_and_admin_can_access_reports(): void
    {
        $responseStaff = $this->actingAs($this->staff)->get(route('reports.index'));
        $responseStaff->assertOk();
        $responseStaff->assertSee('รายงานสรุปการรับ-ส่งหนังสือ');
        $responseStaff->assertSee('พิมพ์รายงาน / บันทึก PDF (A4)');

        $responseAdmin = $this->actingAs($this->admin)->get(route('reports.index'));
        $responseAdmin->assertOk();
    }

    public function test_reports_calculates_accurate_periodic_counts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-29 10:00:00'));

        // 1. Intake on route 1 (remains pending)
        $m1 = $this->movementService->intake(
            'REP-001',
            '2026-10-29',
            $this->route1->id,
            $this->staff,
            null,
            '127.0.0.1'
        )['movement'];

        // 2. Intake and checkout on route 1 (completed)
        $m2 = $this->movementService->intake(
            'REP-002',
            '2026-10-29',
            $this->route1->id,
            $this->staff,
            null,
            '127.0.0.1'
        )['movement'];
        $this->movementService->checkout($m2->id, $this->staff, null, '127.0.0.1');

        // 3. Intake on route 2 (remains pending)
        $m3 = $this->movementService->intake(
            'REP-003',
            '2026-10-29',
            $this->route2->id,
            $this->staff,
            null,
            '127.0.0.1'
        )['movement'];

        $response = $this->actingAs($this->staff)->get(route('reports.index', ['period' => 'today']));
        $response->assertOk();

        // Total Intake: 3
        $response->assertViewHas('totalIntake', 3);
        // Total Completed: 1
        $response->assertViewHas('totalCompleted', 1);
        // Total Pending: 2
        $response->assertViewHas('totalPending', 2);
        // Completion Rate: 33%
        $response->assertViewHas('completionRate', 33.0);

        // Filter specifically for route 1
        $responseRoute1 = $this->actingAs($this->staff)->get(route('reports.index', [
            'period' => 'today',
            'route_id' => $this->route1->id,
        ]));
        $responseRoute1->assertOk();
        $responseRoute1->assertViewHas('totalIntake', 2);
        $responseRoute1->assertViewHas('totalCompleted', 1);
    }
}

