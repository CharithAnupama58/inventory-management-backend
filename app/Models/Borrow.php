<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrow extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'borrower_name',
        'contact',
        'quantity',
        'borrow_date',
        'expected_return_date',
        'actual_return_date',
        'return_condition',
        'notes',
        'status',
        'processed_by',
    ];

    protected $casts = [
        'borrow_date'          => 'date',
        'expected_return_date' => 'date',
        'actual_return_date'   => 'date',
    ];

    const STATUS_BORROWED = 'borrowed';
    const STATUS_RETURNED = 'returned';

    /* ── Relationships ── */

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /* ── Helpers ── */

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_BORROWED
            && $this->expected_return_date->isPast();
    }

    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) return 0;
        return $this->expected_return_date->diffInDays(now());
    }

    public function daysUntilDue(): int
    {
        if ($this->status !== self::STATUS_BORROWED) return 0;
        return (int) now()->diffInDays($this->expected_return_date, false);
    }
}
