<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    // No updated_at — audit logs are immutable, never edited
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'entity_name',
        'previous_value',
        'new_value',
    ];

    protected $casts = [
        'previous_value' => 'array',
        'new_value'      => 'array',
    ];

    /* ── Action constants — one place, no magic strings ── */

    // Item actions
    const ITEM_CREATED          = 'ITEM_CREATED';
    const ITEM_UPDATED          = 'ITEM_UPDATED';
    const ITEM_DELETED          = 'ITEM_DELETED';
    const ITEM_QUANTITY_CHANGED = 'ITEM_QUANTITY_CHANGED';
    const ITEM_STATUS_CHANGED   = 'ITEM_STATUS_CHANGED';
    const ITEM_BORROWED         = 'ITEM_BORROWED';
    const ITEM_RETURNED         = 'ITEM_RETURNED';

    // User actions
    const USER_CREATED          = 'USER_CREATED';
    const USER_UPDATED          = 'USER_UPDATED';
    const USER_DELETED          = 'USER_DELETED';
    const USER_ROLE_CHANGED     = 'USER_ROLE_CHANGED';
    const USER_ACTIVATED        = 'USER_ACTIVATED';
    const USER_DEACTIVATED      = 'USER_DEACTIVATED';

    // Storage actions
    const CUPBOARD_CREATED      = 'CUPBOARD_CREATED';
    const CUPBOARD_UPDATED      = 'CUPBOARD_UPDATED';
    const CUPBOARD_DELETED      = 'CUPBOARD_DELETED';
    const PLACE_CREATED         = 'PLACE_CREATED';
    const PLACE_UPDATED         = 'PLACE_UPDATED';
    const PLACE_DELETED         = 'PLACE_DELETED';

    /* ── Relationships ── */

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'System',
        ]);
    }

    /* ── Static factory method — use this everywhere to create logs ──
     *
     * Usage:
     *   AuditLog::record(
     *       action:       AuditLog::ITEM_BORROWED,
     *       entityType:   'Item',
     *       entityId:     $item->id,
     *       entityName:   $item->name,
     *       previousValue: ['quantity' => 5, 'status' => 'instore'],
     *       newValue:      ['quantity' => 4, 'status' => 'borrowed'],
     *   );
     */
    public static function record(
        string $action,
        string $entityType,
        int    $entityId,
        string $entityName,
        ?array $previousValue = null,
        ?array $newValue      = null,
        ?int   $userId        = null,
    ): self {
        return self::create([
            'user_id'        => $userId ?? auth()->id(),
            'action'         => $action,
            'entity_type'    => $entityType,
            'entity_id'      => $entityId,
            'entity_name'    => $entityName,
            'previous_value' => $previousValue,
            'new_value'      => $newValue,
        ]);
    }
}
