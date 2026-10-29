<?php

namespace App\Http\Controllers;

use App\Models\DocumentMovement;
use App\Models\Route;
use App\Services\MovementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected MovementService $movementService
    ) {}

    /**
     * Display Dual-Pane Desktop interface (Left Intake, Right Worklist).
     */
    public function index(Request $request): View
    {
        $activeRoutes = Route::where('is_active', true)->orderBy('route_name')->get();
        $tab = $request->query('tab', 'active') === 'completed' ? 'completed' : 'active';

        $query = DocumentMovement::with(['document', 'route', 'inUser', 'outUser']);

        if ($tab === 'completed') {
            $query->where('is_returned', true)
                ->whereDate('out_datetime', Carbon::today())
                ->orderBy('out_datetime', 'desc');
        } else {
            $query->where('is_returned', false)
                ->orderBy('in_datetime', 'asc');
        }

        $routeId = $request->integer('route_id');
        if ($routeId > 0) {
            $query->where('route_id', $routeId);
        }

        if ($request->filled('search')) {
            $searchTerm = trim($request->string('search'));
            if ($searchTerm !== '') {
                $query->whereHas('document', function ($q) use ($searchTerm) {
                    $q->where('doc_number', 'like', "%{$searchTerm}%");
                });
            }
        }

        $movements = $query->paginate(50)->withQueryString();

        // Count totals for stats header
        $totalActive = DocumentMovement::where('is_returned', false)->count();
        $todayIntake = DocumentMovement::whereDate('in_datetime', Carbon::today())->count();
        $todayCompleted = DocumentMovement::where('is_returned', true)
            ->whereDate('out_datetime', Carbon::today())
            ->count();

        return view('dashboard.index', [
            'activeRoutes' => $activeRoutes,
            'movements' => $movements,
            'totalActive' => $totalActive,
            'todayIntake' => $todayIntake,
            'todayCompleted' => $todayCompleted,
            'currentRouteFilter' => $routeId > 0 ? $routeId : null,
            'currentSearch' => $request->filled('search') ? trim($request->string('search')) : '',
            'activeTab' => $tab,
        ]);
    }

    /**
     * Process quick document intake.
     */
    public function intake(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'doc_number' => ['required', 'string', 'max:100'],
            'doc_date' => ['required', 'date'],
            'route_id' => ['required', 'integer', 'exists:routes,id'],
        ], [
            'doc_number.required' => 'กรุณากรอกเลขรับหนังสือ',
            'doc_date.required' => 'กรุณาเลือกวันที่หนังสือ',
            'route_id.required' => 'กรุณาเลือกเส้นทางเดินเอกสาร',
        ]);

        $device = $request->attributes->get('registered_device');
        $ip = (string) $request->ip();

        $result = $this->movementService->intake(
            $validated['doc_number'],
            $validated['doc_date'],
            (int) $validated['route_id'],
            $request->user(),
            $device,
            $ip
        );

        if (! $result['success']) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($result, 422);
            }

            return back()->withInput()->with('error', $result['error']);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('dashboard')->with('success', $result['message']);
    }

    /**
     * Point-to-point checkout.
     */
    public function checkout(Request $request, DocumentMovement $movement): JsonResponse|RedirectResponse
    {
        $device = $request->attributes->get('registered_device');
        $ip = (string) $request->ip();

        $result = $this->movementService->checkout(
            $movement->id,
            $request->user(),
            $device,
            $ip
        );

        if (! $result['success']) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($result, 422);
            }

            return back()->with('error', $result['error']);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Advance pipeline step.
     */
    public function advance(Request $request, DocumentMovement $movement): JsonResponse|RedirectResponse
    {
        $device = $request->attributes->get('registered_device');
        $ip = (string) $request->ip();

        $result = $this->movementService->advanceStep(
            $movement->id,
            $request->user(),
            $device,
            $ip
        );

        if (! $result['success']) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($result, 422);
            }

            return back()->with('error', $result['error']);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Batch check-out.
     */
    public function batchCheckout(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'movement_ids' => ['required', 'array'],
            'movement_ids.*' => ['integer', 'exists:document_movements,id'],
        ]);

        $device = $request->attributes->get('registered_device');
        $ip = (string) $request->ip();

        $result = $this->movementService->batchCheckout(
            $validated['movement_ids'],
            $request->user(),
            $device,
            $ip
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Batch advance.
     */
    public function batchAdvance(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'movement_ids' => ['required', 'array'],
            'movement_ids.*' => ['integer', 'exists:document_movements,id'],
        ]);

        $device = $request->attributes->get('registered_device');
        $ip = (string) $request->ip();

        $result = $this->movementService->batchAdvance(
            $validated['movement_ids'],
            $request->user(),
            $device,
            $ip
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Edit mistyped document number.
     */
    public function editDocNumber(Request $request, DocumentMovement $movement): JsonResponse|RedirectResponse
    {
        abort_unless(
            $movement->canCorrectIntake($request->user()),
            403,
            'ไม่มีสิทธิ์แก้ไขเลขรับรายการนี้ หรือเกินระยะเวลาที่กำหนด (ผู้รับเข้าภายใน 30 นาที หรือผู้ดูแลระบบภายในวันเดียวกัน)'
        );

        $validated = $request->validate([
            'new_doc_number' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'min:3'],
        ], [
            'new_doc_number.required' => 'กรุณากรอกเลขรับหนังสือใหม่',
            'reason.required' => 'กรุณาระบุเหตุผลในการแก้ไขเลขรับ',
            'reason.min' => 'เหตุผลต้องมีความยาวอย่างน้อย 3 ตัวอักษร',
        ]);

        $device = $request->attributes->get('registered_device');
        $ip = (string) $request->ip();

        $result = $this->movementService->editDocNumber(
            $movement->id,
            $validated['new_doc_number'],
            $validated['reason'],
            $request->user(),
            $device,
            $ip
        );

        if (! $result['success']) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($result, 422);
            }

            return back()->with('error', $result['error'])->withErrors(['new_doc_number' => $result['error']]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Void/Delete erroneous intake.
     */
    public function voidIntake(Request $request, DocumentMovement $movement): JsonResponse|RedirectResponse
    {
        abort_unless(
            $movement->canVoidIntake($request->user()),
            403,
            'ไม่มีสิทธิ์ยกเลิกรายการรับเข้า หรือเกินระยะเวลาที่กำหนด (ผู้รับเข้าภายใน 30 นาที หรือผู้ดูแลระบบภายในวันเดียวกัน และยังไม่มีการส่งต่อ)'
        );

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลในการยกเลิกรายการรับเข้า',
            'reason.min' => 'เหตุผลต้องมีความยาวอย่างน้อย 3 ตัวอักษร',
        ]);

        $device = $request->attributes->get('registered_device');
        $ip = (string) $request->ip();

        $result = $this->movementService->voidIntake(
            $movement->id,
            $validated['reason'],
            $request->user(),
            $device,
            $ip
        );

        if (! $result['success']) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($result, 422);
            }

            return back()->with('error', $result['error'])->withErrors(['reason' => $result['error']]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }
}
