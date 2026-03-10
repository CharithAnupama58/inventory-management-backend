<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'cupboard_id',
        'name',
        'capacity',
    ];

    /* ── Relationships ── */

    public function cupboard()
    {
        return $this->belongsTo(Cupboard::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /* ── Helpers ── */

    public function getItemCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getIsFullAttribute(): bool
    {
        return $this->item_count >= $this->capacity;
    }

    public function getUsagePercentAttribute(): int
    {
        if ($this->capacity === 0) return 0;
        return (int) round(($this->item_count / $this->capacity) * 100);
    }
}
