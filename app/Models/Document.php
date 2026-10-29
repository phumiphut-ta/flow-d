<?php

namespace App\Models;

use App\Support\ThaiDate;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'doc_number',
        'doc_date',
        'current_status', // 'in_progress', 'completed'
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'doc_date' => 'date',
        ];
    }

    protected function docDateThai(): Attribute
    {
        return Attribute::make(
            get: fn () => ThaiDate::format($this->doc_date)
        );
    }

    protected function docDateReadable(): Attribute
    {
        return Attribute::make(
            get: fn () => ThaiDate::formatThaiReadable($this->doc_date)
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(DocumentMovement::class)->orderBy('round_number', 'asc');
    }

    public function latestMovement(): HasOne
    {
        return $this->hasOne(DocumentMovement::class)->latestOfMany('round_number');
    }

    public function activeMovement(): HasOne
    {
        return $this->hasOne(DocumentMovement::class)
            ->where('is_returned', false)
            ->latestOfMany('round_number');
    }
}
