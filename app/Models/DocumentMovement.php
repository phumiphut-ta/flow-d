<?php

namespace App\Models;

use App\Support\ThaiDate;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'route_id',
        'round_number',
        'current_step_index',
        'current_step_name',
        'in_datetime',
        'in_by_user_id',
        'in_device_id',
        'in_ip_address',
        'out_datetime',
        'out_by_user_id',
        'out_device_id',
        'out_ip_address',
        'is_returned',
    ];

    protected function casts(): array
    {
        return [
            'in_datetime' => 'datetime',
            'out_datetime' => 'datetime',
            'is_returned' => 'boolean',
            'round_number' => 'integer',
            'current_step_index' => 'integer',
        ];
    }

    protected function inDatetimeThai(): Attribute
    {
        return Attribute::make(
            get: fn () => ThaiDate::formatDateTime($this->in_datetime)
        );
    }

    protected function outDatetimeThai(): Attribute
    {
        return Attribute::make(
            get: fn () => ThaiDate::formatDateTime($this->out_datetime)
        );
    }

    /**
     * Compute duration in pending status (เดือน วัน ชั่วโมง นาที ไม่แสดงวินาทีและไม่มีทศนิยม).
     */
    protected function waitingDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $start = $this->in_datetime;
                $end = $this->out_datetime ?? now();
                if (! $start) {
                    return '-';
                }

                $interval = $start->diff($end);
                $months = ($interval->y * 12) + $interval->m;
                $days = $interval->d;
                $hours = $interval->h;
                $minutes = $interval->i;

                $parts = [];
                if ($months > 0) {
                    $parts[] = "{$months} เดือน";
                }
                if ($days > 0) {
                    $parts[] = "{$days} วัน";
                }
                if ($hours > 0) {
                    $parts[] = "{$hours} ชั่วโมง";
                }
                if ($minutes > 0 || empty($parts)) {
                    $parts[] = "{$minutes} นาที";
                }

                return implode(' ', $parts);
            }
        );
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function inUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'in_by_user_id');
    }

    public function outUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'out_by_user_id');
    }

    public function inDevice(): BelongsTo
    {
        return $this->belongsTo(RegisteredDevice::class, 'in_device_id');
    }

    public function outDevice(): BelongsTo
    {
        return $this->belongsTo(RegisteredDevice::class, 'out_device_id');
    }

    public function stepLogs(): HasMany
    {
        return $this->hasMany(MovementStepLog::class, 'movement_id')->orderBy('step_index', 'asc');
    }

    /**
     * Determine whether admin can undo/reopen this movement.
     * Constraint 2: Must be within SAME calendar day (action date == today).
     */
    public function canAdminUndo(): bool
    {
        if ($this->is_returned) {
            return $this->out_datetime && $this->out_datetime->isToday();
        }

        // If in-progress, check if latest step log was updated today
        $latestLog = $this->stepLogs()->latest('id')->first();
        if ($latestLog && $latestLog->action_datetime) {
            return $latestLog->action_datetime->isToday();
        }

        return $this->in_datetime && $this->in_datetime->isToday();
    }

    /**
     * Determine if a user can edit this movement's document number.
     * Rule: Staff intake creator within 30 mins, or Admin same-day.
     */
    public function canCorrectIntake(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (! $user || $this->is_returned) {
            return false;
        }

        if ($user->isAdmin()) {
            return $this->in_datetime && $this->in_datetime->isToday();
        }

        if ($this->in_by_user_id === $user->id) {
            return $this->in_datetime && $this->in_datetime->diffInMinutes(now()) <= 30;
        }

        return false;
    }

    /**
     * Determine if a user can void/delete this intake.
     * Rule: Can correct intake AND movement is still at initial step (step 0).
     */
    public function canVoidIntake(?User $user = null): bool
    {
        return $this->canCorrectIntake($user) && $this->current_step_index === 0;
    }
}
