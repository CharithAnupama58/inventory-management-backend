<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'name',
        'code',
        'quantity',
        'serial_number',
        'image_path',
        'description',
        'status',
    ];

    const STATUS_INSTORE  = 'instore';
    const STATUS_BORROWED = 'borrowed';
    const STATUS_DAMAGED  = 'damaged';
    const STATUS_MISSING  = 'missing';

    /* ── Relationships ── */

    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    // Convenience: get the cupboard through place
    public function cupboard()
    {
        return $this->hasOneThrough(
            Cupboard::class,
            Place::class,
            'id',         // places.id
            'id',         // cupboards.id
            'place_id',   // items.place_id
            'cupboard_id' // places.cupboard_id
        );
    }

    public function borrows()
    {
        return $this->hasMany(Borrow::class);
    }

    public function activeBorrow()
    {
        return $this->hasOne(Borrow::class)->where('status', 'borrowed');
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }

    /* ── Helpers ── */

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_INSTORE && $this->quantity > 0;
    }

    public function hasEnoughStock(int $qty): bool
    {
        return $this->quantity >= $qty;
    }
}
