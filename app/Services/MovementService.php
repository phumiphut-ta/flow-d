<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentMovement;
use App\Models\MovementStepLog;
use App\Models\RegisteredDevice;
use App\Models\Route;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MovementService
{
    /**
     * Intake document (Dual-pane quick intake form).
     *
     * @return array{success: bool, message?: string, error?: string, movement?: DocumentMovement}
     */
    public function intake(
        string $docNumber,
        string $docDate,
        int $routeId,
        User $user,
        ?RegisteredDevice $device,
        string $ip
    ): array {
        $cleanDocNumber = trim($docNumber);

        if ($cleanDocNumber === '') {
            return ['success' => false, 'error' => 'กรุณากรอกเลขรับหนังสือ'];
        }

        $route = Route::find($routeId);
        if (! $route || ! $route->is_active) {
            return ['success' => false, 'error' => 'เส้นทางที่เลือกไม่ถูกต้องหรือถูกปิดใช้งาน'];
        }

        return DB::transaction(function () use ($cleanDocNumber, $docDate, $route, $user, $device, $ip) {
            $document = Document::where('doc_number', $cleanDocNumber)->first();
            $roundNumber = 1;

            if ($document) {
                // Re-entry Rule: Check previous movement
                $latestMovement = DocumentMovement::where('document_id', $document->id)
                    ->orderBy('round_number', 'desc')
                    ->lockForUpdate()
                    ->first();

                if ($latestMovement && ! $latestMovement->is_returned) {
                    return [
                        'success' => false,
                        'error' => "รอบก่อนหน้า (รอบที่ {$latestMovement->round_number}) ยังไม่ได้ส่งออกหนังสือ ไม่สามารถเปิดรอบใหม่ได้",
                    ];
                }

                $roundNumber = $latestMovement ? ($latestMovement->round_number + 1) : 1;
                $document->update([
                    'current_status' => 'in_progress',
                    'doc_date' => $docDate,
                ]);
            } else {
                $document = Document::create([
                    'doc_number' => $cleanDocNumber,
                    'doc_date' => $docDate,
                    'current_status' => 'in_progress',
                    'created_by' => $user->id,
                ]);
            }

            // Determine initial step
            $steps = $route->steps_json ?? [];
            $initialStepName = ! empty($steps) ? $steps[0] : $route->route_name;

            $movement = DocumentMovement::create([
                'document_id' => $document->id,
                'route_id' => $route->id,
                'round_number' => $roundNumber,
                'current_step_index' => 0,
                'current_step_name' => $initialStepName,
                'in_datetime' => now(),
                'in_by_user_id' => $user->id,
                'in_device_id' => $device?->id,
                'in_ip_address' => $ip,
                'is_returned' => false,
            ]);

            // Create step log
            MovementStepLog::create([
                'movement_id' => $movement->id,
                'step_index' => 0,
                'step_name' => $initialStepName,
                'status' => 'current',
                'action_by_user_id' => $user->id,
                'action_datetime' => now(),
                'ip_address' => $ip,
            ]);

            return [
                'success' => true,
                'message' => "รับหนังสือเลขรับ {$cleanDocNumber} (รอบที่ {$roundNumber}) เข้าระบบเรียบร้อยแล้ว",
                'movement' => $movement,
            ];
        });
    }

    /**
     * Point-to-Point 1-click checkout with Pessimistic Lock & Idempotency.
     *
     * @return array{success: bool, idempotent?: bool, message?: string, error?: string}
     */
    public function checkout(
        int $movementId,
        User $user,
        ?RegisteredDevice $device,
        string $ip
    ): array {
        return DB::transaction(function () use ($movementId, $user, $device, $ip) {
            $movement = DocumentMovement::with(['document', 'route'])
                ->where('id', $movementId)
                ->lockForUpdate()
                ->first();

            if (! $movement) {
                return ['success' => false, 'error' => 'ไม่พบข้อมูลการเดินเรื่อง'];
            }

            // Idempotent Response: If already processed, return original success
            if ($movement->is_returned) {
                return [
                    'success' => true,
                    'idempotent' => true,
                    'message' => 'รายการนี้ได้ส่งออกหนังสือเรียบร้อยแล้ว (ลงระบบเสร็จสิ้น)',
                ];
            }

            $steps = $movement->route->steps_json ?? [];
            $totalSteps = count($steps);
            $lastIndex = max(0, $totalSteps - 1);
            $finalStepName = 'ลงระบบเสร็จสิ้น';

            // Mark current step log as completed
            MovementStepLog::where('movement_id', $movement->id)
                ->where('step_index', $movement->current_step_index)
                ->where('status', 'current')
                ->update([
                    'status' => 'completed',
                    'action_by_user_id' => $user->id,
                    'action_datetime' => now(),
                    'ip_address' => $ip,
                ]);

            // For any remaining intermediate or final pipeline steps not logged yet, record them as completed
            for ($i = $movement->current_step_index + 1; $i <= $lastIndex; $i++) {
                $stepName = $steps[$i] ?? $finalStepName;
                MovementStepLog::updateOrCreate(
                    [
                        'movement_id' => $movement->id,
                        'step_index' => $i,
                    ],
                    [
                        'step_name' => $stepName,
                        'status' => 'completed',
                        'action_by_user_id' => $user->id,
                        'action_datetime' => now(),
                        'ip_address' => $ip,
                    ]
                );
            }

            $movement->update([
                'current_step_index' => $lastIndex,
                'current_step_name' => $finalStepName,
                'out_datetime' => now(),
                'out_by_user_id' => $user->id,
                'out_device_id' => $device?->id,
                'out_ip_address' => $ip,
                'is_returned' => true,
            ]);

            // Update parent document status
            $movement->document->update(['current_status' => 'completed']);

            return [
                'success' => true,
                'message' => "ส่งออกหนังสือเลขรับ {$movement->document->doc_number} เรียบร้อยแล้ว (ลงระบบเสร็จสิ้น)",
            ];
        });
    }

    /**
     * Advance pipeline route step by step with checkpoint logging.
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function advanceStep(
        int $movementId,
        User $user,
        ?RegisteredDevice $device,
        string $ip
    ): array {
        return DB::transaction(function () use ($movementId, $user, $device, $ip) {
            $movement = DocumentMovement::with(['route', 'document'])
                ->where('id', $movementId)
                ->lockForUpdate()
                ->first();

            if (! $movement) {
                return ['success' => false, 'error' => 'ไม่พบข้อมูลการเดินเรื่อง'];
            }

            if ($movement->is_returned) {
                return ['success' => false, 'error' => 'หนังสือรายการนี้จบรอบไปแล้ว ไม่สามารถส่งต่อขั้นตอนได้'];
            }

            $steps = $movement->route->steps_json ?? [];
            $totalSteps = count($steps);

            if ($totalSteps <= 1) {
                // If it's a point-to-point route, advance means checkout
                return $this->checkout($movementId, $user, $device, $ip);
            }

            $currentIndex = $movement->current_step_index;
            $nextIndex = $currentIndex + 1;

            if ($nextIndex >= $totalSteps) {
                // Already at final step, trigger final checkout
                return $this->checkout($movementId, $user, $device, $ip);
            }

            // Mark current step log as completed
            $currentLog = MovementStepLog::where('movement_id', $movement->id)
                ->where('step_index', $currentIndex)
                ->where('status', 'current')
                ->first();

            if ($currentLog) {
                if ($currentIndex === 0) {
                    $currentLog->update([
                        'status' => 'completed',
                        'action_by_user_id' => $movement->in_by_user_id ?? $user->id,
                        'action_datetime' => $movement->in_datetime ?? now(),
                    ]);
                } else {
                    $currentLog->update([
                        'status' => 'completed',
                        'action_by_user_id' => $user->id,
                        'action_datetime' => now(),
                        'ip_address' => $ip,
                    ]);
                }
            }

            $nextStepName = $steps[$nextIndex];
            $isFinalStep = ($nextIndex === $totalSteps - 1);

            $movement->update([
                'current_step_index' => $nextIndex,
                'current_step_name' => $nextStepName,
                'out_datetime' => $isFinalStep ? now() : null,
                'out_by_user_id' => $isFinalStep ? $user->id : null,
                'out_device_id' => $isFinalStep ? $device?->id : null,
                'out_ip_address' => $isFinalStep ? $ip : null,
                'is_returned' => $isFinalStep,
            ]);

            // Create checkpoint for next step
            MovementStepLog::create([
                'movement_id' => $movement->id,
                'step_index' => $nextIndex,
                'step_name' => $nextStepName,
                'status' => $isFinalStep ? 'completed' : 'current',
                'action_by_user_id' => $user->id,
                'action_datetime' => now(),
                'ip_address' => $ip,
            ]);

            if ($isFinalStep) {
                $movement->document->update(['current_status' => 'completed']);

                return [
                    'success' => true,
                    'message' => "ส่งต่อไปยังขั้นตอนสุดท้าย ({$nextStepName}) และสิ้นสุดกระบวนการเรียบร้อยแล้ว",
                ];
            }

            return [
                'success' => true,
                'message' => "ส่งต่อไปยังขั้นตอน: {$nextStepName} เรียบร้อยแล้ว",
            ];
        });
    }

    /**
     * Batch check-out multiple movements.
     *
     * @param  list<int>  $movementIds
     * @return array{success: bool, count: int, message: string}
     */
    public function batchCheckout(
        array $movementIds,
        User $user,
        ?RegisteredDevice $device,
        string $ip
    ): array {
        $count = 0;

        DB::transaction(function () use ($movementIds, $user, $device, $ip, &$count) {
            foreach ($movementIds as $id) {
                $res = $this->checkout((int) $id, $user, $device, $ip);
                if ($res['success']) {
                    $count++;
                }
            }

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'batch_checkout',
                'description' => "ผู้ใช้ {$user->name} ทำการส่งออกหนังสือแบบชุด จำนวน {$count} รายการ",
                'ip_address' => $ip,
                'device_id' => $device?->id,
            ]);
        });

        return [
            'success' => true,
            'count' => $count,
            'message' => "ส่งออกหนังสือทั้งชุดสำเร็จจำนวน {$count} รายการ (ลงระบบเสร็จสิ้น)",
        ];
    }

    /**
     * Batch advance step for multiple movements.
     *
     * @param  list<int>  $movementIds
     * @return array{success: bool, count: int, message: string}
     */
    public function batchAdvance(
        array $movementIds,
        User $user,
        ?RegisteredDevice $device,
        string $ip
    ): array {
        $count = 0;

        DB::transaction(function () use ($movementIds, $user, $device, $ip, &$count) {
            foreach ($movementIds as $id) {
                $res = $this->advanceStep((int) $id, $user, $device, $ip);
                if ($res['success']) {
                    $count++;
                }
            }

            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'batch_advance',
                'description' => "ผู้ใช้ {$user->name} ทำการส่งต่อขั้นตอนแบบชุด จำนวน {$count} รายการ",
                'ip_address' => $ip,
                'device_id' => $device?->id,
            ]);
        });

        return [
            'success' => true,
            'count' => $count,
            'message' => "ส่งต่อขั้นตอนทั้งชุดสำเร็จจำนวน {$count} รายการ",
        ];
    }

    /**
     * Correct a mistyped document number (Staff within 30m, Admin same-day).
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function editDocNumber(
        int $movementId,
        string $newDocNumber,
        string $reason,
        User $user,
        ?RegisteredDevice $device,
        string $ip
    ): array {
        $cleanNumber = trim($newDocNumber);
        $cleanReason = trim($reason);

        if ($cleanNumber === '') {
            return ['success' => false, 'error' => 'กรุณากรอกเลขรับหนังสือใหม่'];
        }

        if ($cleanReason === '') {
            return ['success' => false, 'error' => 'กรุณาระบุเหตุผลในการแก้ไขเลขรับ'];
        }

        return DB::transaction(function () use ($movementId, $cleanNumber, $cleanReason, $user, $device, $ip) {
            $movement = DocumentMovement::with(['document', 'route'])
                ->where('id', $movementId)
                ->lockForUpdate()
                ->first();

            if (! $movement) {
                return ['success' => false, 'error' => 'ไม่พบข้อมูลการเดินเรื่อง'];
            }

            if (! $movement->canCorrectIntake($user)) {
                return [
                    'success' => false,
                    'error' => 'ไม่มีสิทธิ์แก้ไขเลขรับ หรือหมดระยะเวลาที่อนุญาตให้แก้ไขแล้ว (ผู้รับเข้าแก้ไขได้ภายใน 30 นาที, ผู้ดูแลระบบแก้ไขได้ภายในวันเดียวกัน)',
                ];
            }

            $document = $movement->document;
            $oldNumber = $document->doc_number;

            if ($oldNumber === $cleanNumber) {
                return ['success' => true, 'message' => 'เลขรับไม่มีการเปลี่ยนแปลง'];
            }

            // Conflict Check (Option 3-A): Block if exists
            $existing = Document::where('doc_number', $cleanNumber)
                ->where('id', '!=', $document->id)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'error' => "เลขรับ '{$cleanNumber}' มีอยู่ในระบบแล้ว ไม่สามารถแก้ไขเป็นเลขนี้ได้ เพื่อป้องกันความสับสนของสมุดคุม",
                ];
            }

            $document->update(['doc_number' => $cleanNumber]);

            // Mandatory Audit Log (Option 1-C)
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'doc_number_edit',
                'description' => "แก้ไขเลขรับจาก '{$oldNumber}' เป็น '{$cleanNumber}' (รอบที่ {$movement->round_number}) | เหตุผล: {$cleanReason}",
                'ip_address' => $ip,
                'device_id' => $device?->id,
            ]);

            return [
                'success' => true,
                'message' => "แก้ไขเลขรับจาก '{$oldNumber}' เป็น '{$cleanNumber}' เรียบร้อยแล้ว",
            ];
        });
    }

    /**
     * Void/Delete an erroneous intake (Staff within 30m, Admin same-day, step 0 only).
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function voidIntake(
        int $movementId,
        string $reason,
        User $user,
        ?RegisteredDevice $device,
        string $ip
    ): array {
        $cleanReason = trim($reason);

        if ($cleanReason === '') {
            return ['success' => false, 'error' => 'กรุณาระบุเหตุผลในการยกเลิกรายการรับเข้า'];
        }

        return DB::transaction(function () use ($movementId, $cleanReason, $user, $device, $ip) {
            $movement = DocumentMovement::with(['document'])
                ->where('id', $movementId)
                ->lockForUpdate()
                ->first();

            if (! $movement) {
                return ['success' => false, 'error' => 'ไม่พบข้อมูลการเดินเรื่อง'];
            }

            if (! $movement->canVoidIntake($user)) {
                return [
                    'success' => false,
                    'error' => 'ไม่สามารถยกเลิกรายการนี้ได้ (ต้องอยู่ที่ขั้นตอนแรกและยังไม่มีการส่งต่อ และอยู่ในระยะเวลาที่อนุญาต: ผู้รับเข้าภายใน 30 นาที หรือผู้ดูแลระบบภายในวันเดียวกัน)',
                ];
            }

            $document = $movement->document;
            $docNumber = $document->doc_number;
            $roundNumber = $movement->round_number;

            // Delete step logs
            $movement->stepLogs()->delete();

            // Delete movement
            $movement->delete();

            // If document has no other movements, delete document as well
            if ($document->movements()->count() === 0) {
                $document->delete();
            } else {
                $document->update(['current_status' => 'completed']);
            }

            // Mandatory Audit Log
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'intake_void',
                'description' => "ยกเลิก/ลบรายการรับเข้าเลขรับ '{$docNumber}' (รอบที่ {$roundNumber}) | เหตุผล: {$cleanReason}",
                'ip_address' => $ip,
                'device_id' => $device?->id,
            ]);

            return [
                'success' => true,
                'message' => "ยกเลิกรายการรับเข้าเลขรับ '{$docNumber}' เรียบร้อยแล้ว",
            ];
        });
    }
}
