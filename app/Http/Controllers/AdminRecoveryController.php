<?php

namespace App\Http\Controllers;

use App\Models\DocumentMovement;
use App\Services\RecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminRecoveryController extends Controller
{
    public function __construct(
        protected RecoveryService $recoveryService
    ) {}

    /**
     * Reopen a checked-out document (Same-day single undo).
     */
    public function reopen(Request $request, DocumentMovement $movement): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลในการกู้คืนสถานะ',
            'reason.min' => 'เหตุผลต้องมีความยาวอย่างน้อย 3 ตัวอักษร',
        ]);

        $device = $request->attributes->get('registered_device');
        $ip = (string) $request->ip();

        $result = $this->recoveryService->reopenCheckout(
            $movement->id,
            $request->user(),
            $validated['reason'],
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
     * Single step rollback for a pipeline movement (Same-day single undo).
     */
    public function rollback(Request $request, DocumentMovement $movement): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3'],
        ], [
            'reason.required' => 'กรุณาระบุเหตุผลในการย้อนขั้นตอน',
            'reason.min' => 'เหตุผลต้องมีความยาวอย่างน้อย 3 ตัวอักษร',
        ]);

        $device = $request->attributes->get('registered_device');
        $ip = (string) $request->ip();

        $result = $this->recoveryService->rollbackStep(
            $movement->id,
            $request->user(),
            $validated['reason'],
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
}
