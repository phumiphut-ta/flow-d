<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\DocumentMovement;
use App\Models\MovementStepLog;
use App\Models\RegisteredDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecoveryService
{
    /**
     * Re-open a checked-out document (Admin Recovery).
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function reopenCheckout(
        int $movementId,
        User $admin,
        string $reason,
        ?RegisteredDevice $device,
        string $ip
    ): array {
        if (! $admin->isAdmin()) {
            return ['success' => false, 'error' => 'ไม่มีสิทธิ์ดำเนินการ: สำหรับผู้ดูแลระบบเท่านั้น'];
        }

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            return ['success' => false, 'error' => 'กรุณาระบุเหตุผลในการกู้คืนสถานะ'];
        }

        return DB::transaction(function () use ($movementId, $admin, $cleanReason, $device, $ip) {
            $movement = DocumentMovement::with(['document', 'route'])
                ->where('id', $movementId)
                ->lockForUpdate()
                ->first();

            if (! $movement) {
                return ['success' => false, 'error' => 'ไม่พบข้อมูลการเดินเรื่อง'];
            }

            if (! $movement->is_returned) {
                return ['success' => false, 'error' => 'รายการนี้ยังไม่ได้ส่งออกหนังสือ ไม่จำเป็นต้องกู้คืน'];
            }

            // Constraint 2: Same-day restriction
            if (! $movement->out_datetime || ! $movement->out_datetime->isToday()) {
                return [
                    'success' => false,
                    'error' => 'ไม่สามารถกู้คืนได้เนื่องจากข้ามวันแล้ว (ระบบอนุญาตให้กู้คืนเฉพาะรายการภายในวันเดียวกันเท่านั้น เพื่อความถูกต้องของสมุดคุม)',
                ];
            }

            $steps = $movement->route->steps_json ?? [];
            $stepName = $steps[$movement->current_step_index] ?? $movement->route->route_name;

            $movement->update([
                'current_step_name' => $stepName,
                'is_returned' => false,
                'out_datetime' => null,
                'out_by_user_id' => null,
                'out_device_id' => null,
                'out_ip_address' => null,
            ]);

            // Reopen step log
            $latestStepLog = MovementStepLog::where('movement_id', $movement->id)
                ->where('step_index', $movement->current_step_index)
                ->orderBy('id', 'desc')
                ->first();

            if ($latestStepLog) {
                $latestStepLog->update([
                    'status' => 'current',
                    'action_by_user_id' => $admin->id,
                    'action_datetime' => now(),
                    'ip_address' => $ip,
                ]);
            }

            $movement->document->update(['current_status' => 'in_progress']);

            // Constraint 4: Mandatory Audit Logging
            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_reopen_checkout',
                'description' => "ยกเลิกการส่งออกหนังสือเลขรับ {$movement->document->doc_number} (รอบที่ {$movement->round_number}) | เหตุผล: {$cleanReason}",
                'ip_address' => $ip,
                'device_id' => $device?->id,
            ]);

            return [
                'success' => true,
                'message' => "กู้คืนสถานะหนังสือเลขรับ {$movement->document->doc_number} กลับเป็น 'อยู่ระหว่างทาง' เรียบร้อยแล้ว",
            ];
        });
    }

    /**
     * Single step rollback for pipeline routes (Admin Recovery).
     *
     * @return array{success: bool, message?: string, error?: string}
     */
    public function rollbackStep(
        int $movementId,
        User $admin,
        string $reason,
        ?RegisteredDevice $device,
        string $ip
    ): array {
        if (! $admin->isAdmin()) {
            return ['success' => false, 'error' => 'ไม่มีสิทธิ์ดำเนินการ: สำหรับผู้ดูแลระบบเท่านั้น'];
        }

        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            return ['success' => false, 'error' => 'กรุณาระบุเหตุผลในการกู้คืนสถานะ'];
        }

        return DB::transaction(function () use ($movementId, $admin, $cleanReason, $device, $ip) {
            $movement = DocumentMovement::with(['document', 'route'])
                ->where('id', $movementId)
                ->lockForUpdate()
                ->first();

            if (! $movement) {
                return ['success' => false, 'error' => 'ไม่พบข้อมูลการเดินเรื่อง'];
            }

            // Constraint 1: Single step undo only
            if ($movement->current_step_index <= 0) {
                return ['success' => false, 'error' => 'รายการนี้อยู่ที่ขั้นตอนแรกแล้ว ไม่สามารถย้อนขั้นตอนได้อีก'];
            }

            // Check latest log date for Constraint 2
            $latestStepLog = MovementStepLog::where('movement_id', $movement->id)
                ->where('step_index', $movement->current_step_index)
                ->orderBy('id', 'desc')
                ->first();

            if (! $latestStepLog || ! $latestStepLog->action_datetime || ! $latestStepLog->action_datetime->isToday()) {
                return [
                    'success' => false,
                    'error' => 'ไม่สามารถย้อนขั้นตอนได้เนื่องจากการดำเนินการเกิดขึ้นในวันก่อนหน้า (อนุญาตเฉพาะภายในวันเดียวกันเท่านั้น)',
                ];
            }

            $steps = $movement->route->steps_json ?? [];
            $targetIndex = $movement->current_step_index - 1;
            $targetStepName = $steps[$targetIndex] ?? 'ขั้นตอนก่อนหน้า';
            $currentStepName = $movement->current_step_name;

            // Mark current step log as rollbacked
            $latestStepLog->update([
                'status' => 'rollbacked',
                'action_by_user_id' => $admin->id,
                'action_datetime' => now(),
                'ip_address' => $ip,
            ]);

            // Re-activate previous step log
            MovementStepLog::where('movement_id', $movement->id)
                ->where('step_index', $targetIndex)
                ->update([
                    'status' => 'current',
                    'action_by_user_id' => $admin->id,
                    'action_datetime' => now(),
                    'ip_address' => $ip,
                ]);

            // If movement was completed, revert to in_progress
            $movement->update([
                'current_step_index' => $targetIndex,
                'current_step_name' => $targetStepName,
                'is_returned' => false,
                'out_datetime' => null,
                'out_by_user_id' => null,
                'out_device_id' => null,
                'out_ip_address' => null,
            ]);

            $movement->document->update(['current_status' => 'in_progress']);

            // Constraint 4: Mandatory Audit Logging
            ActivityLog::create([
                'user_id' => $admin->id,
                'action' => 'admin_step_rollback',
                'description' => "ย้อนขั้นตอนเลขรับ {$movement->document->doc_number} จาก '{$currentStepName}' กลับไป '{$targetStepName}' | เหตุผล: {$cleanReason}",
                'ip_address' => $ip,
                'device_id' => $device?->id,
            ]);

            return [
                'success' => true,
                'message' => "ย้อนขั้นตอนของเลขรับ {$movement->document->doc_number} กลับไปยัง '{$targetStepName}' เรียบร้อยแล้ว",
            ];
        });
    }
}
