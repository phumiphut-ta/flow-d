<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentMovement;
use App\Models\Route;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected Route $pipelineRoute;

    protected Route $pointRoute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::create([
            'username' => 'staff_user',
            'name' => 'เจ้าหน้าที่ทดสอบ',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->pipelineRoute = Route::create([
            'route_name' => 'เส้นทาง 3 ขั้น',
            'steps_json' => ['1. บันทึกรับหนังสือเข้า', 'รอลงนาม', 'ส่งออกหนังสือ'],
            'is_active' => true,
        ]);

        $this->pointRoute = Route::create([
            'route_name' => 'จุดเดียวจบรอบ',
            'steps_json' => ['1. บันทึกรับหนังสือเข้า'],
            'is_active' => true,
        ]);
    }

    public function test_intake_without_explicit_doc_date_defaults_to_today(): void
    {
        $response = $this->actingAs($this->staff)->post('/dashboard/intake', [
            'doc_number' => 'DOC-AUTO-DATE-001',
            'route_id' => $this->pipelineRoute->id,
            // doc_date omitted
        ]);

        $response->assertRedirect('/dashboard');
        $doc = Document::where('doc_number', 'DOC-AUTO-DATE-001')->first();
        $this->assertNotNull($doc);
        $this->assertEquals(now()->toDateString(), $doc->doc_date->format('Y-m-d'));
    }

    public function test_dashboard_orders_movements_newest_first(): void
    {
        // Document 1 created earlier
        $doc1 = Document::create([
            'doc_number' => 'DOC-OLD-001',
            'doc_date' => now()->toDateString(),
            'current_status' => 'in_progress',
            'created_by' => $this->staff->id,
        ]);
        $mov1 = DocumentMovement::create([
            'document_id' => $doc1->id,
            'route_id' => $this->pipelineRoute->id,
            'round_number' => 1,
            'current_step_index' => 0,
            'current_step_name' => '1. บันทึกรับหนังสือเข้า',
            'in_datetime' => Carbon::now()->subHours(2),
            'in_by_user_id' => $this->staff->id,
            'is_returned' => false,
        ]);

        // Document 2 created later
        $doc2 = Document::create([
            'doc_number' => 'DOC-NEW-002',
            'doc_date' => now()->toDateString(),
            'current_status' => 'in_progress',
            'created_by' => $this->staff->id,
        ]);
        $mov2 = DocumentMovement::create([
            'document_id' => $doc2->id,
            'route_id' => $this->pipelineRoute->id,
            'round_number' => 1,
            'current_step_index' => 0,
            'current_step_name' => '1. บันทึกรับหนังสือเข้า',
            'in_datetime' => Carbon::now(),
            'in_by_user_id' => $this->staff->id,
            'is_returned' => false,
        ]);

        $response = $this->actingAs($this->staff)->get('/dashboard');

        $response->assertOk();
        // DOC-NEW-002 should appear before DOC-OLD-001 in response body
        $response->assertSeeInOrder(['DOC-NEW-002', 'DOC-OLD-001']);
    }

    public function test_dashboard_filters_by_current_step(): void
    {
        // Doc in Step 1
        $doc1 = Document::create([
            'doc_number' => 'DOC-STEP-1',
            'doc_date' => now()->toDateString(),
            'current_status' => 'in_progress',
            'created_by' => $this->staff->id,
        ]);
        DocumentMovement::create([
            'document_id' => $doc1->id,
            'route_id' => $this->pipelineRoute->id,
            'round_number' => 1,
            'current_step_index' => 0,
            'current_step_name' => '1. บันทึกรับหนังสือเข้า',
            'in_datetime' => now(),
            'in_by_user_id' => $this->staff->id,
            'is_returned' => false,
        ]);

        // Doc in Step 2
        $doc2 = Document::create([
            'doc_number' => 'DOC-STEP-2',
            'doc_date' => now()->toDateString(),
            'current_status' => 'in_progress',
            'created_by' => $this->staff->id,
        ]);
        DocumentMovement::create([
            'document_id' => $doc2->id,
            'route_id' => $this->pipelineRoute->id,
            'round_number' => 1,
            'current_step_index' => 1,
            'current_step_name' => 'รอลงนาม',
            'in_datetime' => now(),
            'in_by_user_id' => $this->staff->id,
            'is_returned' => false,
        ]);

        // Filter by 'รอลงนาม'
        $response = $this->actingAs($this->staff)->get('/dashboard?step='.urlencode('รอลงนาม'));

        $response->assertOk();
        $response->assertSee('DOC-STEP-2');
        $response->assertDontSee('DOC-STEP-1');
    }
}
