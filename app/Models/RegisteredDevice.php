<?php

namespace App\Models;

use App\Support\ThaiDate;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegisteredDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_token',
        'device_name',
        'last_ip_address',
        'last_user_agent',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    protected function lastSeenAtThai(): Attribute
    {
        return Attribute::make(
            get: fn () => ThaiDate::formatDateTime($this->last_seen_at)
        );
    }

    public function inMovements(): HasMany
    {
        return $this->hasMany(DocumentMovement::class, 'in_device_id');
    }

    public function outMovements(): HasMany
    {
        return $this->hasMany(DocumentMovement::class, 'out_device_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'device_id');
    }
}
