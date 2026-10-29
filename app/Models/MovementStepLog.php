<?php

namespace App\Models;

use App\Support\ThaiDate;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementStepLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'movement_id',
        'step_index',
        'step_name',
        'status', // 'completed', 'current', 'pending', 'rollbacked'
        'action_by_user_id',
        'action_datetime',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'step_index' => 'integer',
            'action_datetime' => 'datetime',
        ];
    }

    protected function actionDatetimeThai(): Attribute
    {
        return Attribute::make(
            get: fn () => ThaiDate::formatDateTime($this->action_datetime)
        );
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(DocumentMovement::class);
    }

    public function actionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_by_user_id');
    }
}
