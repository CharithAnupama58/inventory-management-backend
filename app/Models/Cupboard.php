<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cupboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'location',
        'color',
        'bg_color',
    ];

    /* ── Relationships ── */

    public function places()
    {
        return $this->hasMany(Place::class);
    }

    // All items inside this cupboard (through places)
    public function items()
    {
        return $this->hasManyThrough(Item::class, Place::class);
    }

    /* ── Helpers ── */

    // Total number of items stored across all places
    public function getTotalItemsAttribute(): int
    {
        return $this->items()->count();
    }

    // Number of items with status "instore"
    public function getAvailableItemsAttribute(): int
    {
        return $this->items()->where('status', 'instore')->count();
    }
}
