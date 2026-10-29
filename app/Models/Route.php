<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_name',
        'steps_json',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Normalize steps array so that "บันทึกรับหนังสือเข้า" is ALWAYS Step 1.
     */
    public static function normalizeSteps(array|string|null $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : preg_split('/[\r\n,]+/', $raw);
        }

        $steps = is_array($raw) ? array_values(array_filter(array_map('trim', $raw))) : [];

        if (empty($steps)) {
            return ['บันทึกรับหนังสือเข้า'];
        }

        $intakeKeywords = ['บันทึกรับหนังสือเข้า', 'รับหนังสือเข้า', 'ลงเลขรับ', 'รับเข้า'];
        if (in_array(mb_strtolower($steps[0]), $intakeKeywords)) {
            $steps[0] = 'บันทึกรับหนังสือเข้า';
        } else {
            array_unshift($steps, 'บันทึกรับหนังสือเข้า');
        }

        return $steps;
    }

    public function getStepsJsonAttribute($value): array
    {
        $raw = json_decode($value ?? '[]', true) ?: [];

        return self::normalizeSteps($raw);
    }

    public function setStepsJsonAttribute($value): void
    {
        $steps = self::normalizeSteps($value);
        $this->attributes['steps_json'] = json_encode($steps, JSON_UNESCAPED_UNICODE);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(DocumentMovement::class);
    }

    /**
     * Check if route is a multi-step pipeline or point-to-point.
     */
    public function isPipeline(): bool
    {
        return count($this->steps_json) > 1;
    }
}
