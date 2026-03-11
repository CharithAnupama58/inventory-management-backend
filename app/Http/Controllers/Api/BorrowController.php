<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBorrowRequest;
use App\Http\Requests\ProcessReturnRequest;
use App\Http\Resources\BorrowResource;
use App\Models\AuditLog;
use App\Models\Borrow;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowController extends Controller
{
    /**
     * GET /api/v1/borrows
     * Admin only — all borrows with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Borrow::with(['item.place.cupboard', 'processedBy']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('borrower_name', 'ilike', "%{$request->search}%")
                  ->orWhere('contact',      'ilike', "%{$request->search}%")
                  ->orWhereHas('item', fn($q2) =>
                      $q2->where('name', 'ilike', "%{$request->search}%")
                         ->orWhere('code', 'ilike', "%{$request->search}%")
                  );
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('overdue')) {
            $query->where('status', 'borrowed')
                  ->where('expected_return_date', '<', now()->toDateString());
        }

        $borrows = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => BorrowResource::collection($borrows->items()),
            'meta'    => [
                'total'        => $borrows->total(),
                'per_page'     => $borrows->perPage(),
                'current_page' => $borrows->currentPage(),
                'last_page'    => $borrows->lastPage(),
                'counts'       => [
                    'total'    => Borrow::count(),
                    'active'   => Borrow::where('status', 'borrowed')->count(),
                    'overdue'  => Borrow::where('status', 'borrowed')
                                        ->where('expected_return_date', '<', now()->toDateString())
                                        ->count(),
                    'returned' => Borrow::where('status', 'returned')->count(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/my-borrows
     * Staff sees borrows they processed.
     */
    public function myBorrows(Request $request): JsonResponse
    {
        $query = Borrow::with(['item.place.cupboard'])
                       ->where('processed_by', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $borrows = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => BorrowResource::collection($borrows->items()),
            'meta'    => [
                'total'        => $borrows->total(),
                'current_page' => $borrows->currentPage(),
                'last_page'    => $borrows->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/borrows/{borrow}
     */
    public function show(Borrow $borrow): JsonResponse
    {
        $borrow->load(['item.place.cupboard', 'processedBy']);

        return response()->json([
            'success' => true,
            'data'    => new BorrowResource($borrow),
        ]);
    }

    /**
     * POST /api/v1/borrows
     *
     * Core business logic — this is the borrow flow:
     * 1. Validate item is available and has enough stock
     * 2. Lock the item row (prevent race condition)
     * 3. Deduct quantity
     * 4. Update item status to 'borrowed' if all stock is out
     * 5. Create borrow record
     * 6. Write audit log
     * All inside a DB transaction — either everything succeeds or nothing does.
     */
    public function store(StoreBorrowRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {

            // Lock the item row to prevent two simultaneous borrows
            $item = Item::lockForUpdate()->findOrFail($request->item_id);

            // Business rule checks
            if ($item->status === Item::STATUS_DAMAGED) {
                return response()->json([
                    'success' => false,
                    'message' => 'This item is marked as damaged and cannot be borrowed.',
                ], 422);
            }

            if ($item->status === Item::STATUS_MISSING) {
                return response()->json([
                    'success' => false,
                    'message' => 'This item is marked as missing.',
                ], 422);
            }

            if (!$item->hasEnoughStock($request->quantity)) {
                return response()->json([
                    'success' => false,
                    'message' => "Only {$item->quantity} unit(s) available. Requested: {$request->quantity}.",
                ], 422);
            }

            $beforeQty    = $item->quantity;
            $beforeStatus = $item->status;

            // Deduct quantity
            $newQty = $item->quantity - $request->quantity;

            // Status rule: if quantity hits 0, mark as fully borrowed
            $newStatus = $newQty === 0 ? Item::STATUS_BORROWED : Item::STATUS_INSTORE;

            $item->update([
                'quantity' => $newQty,
                'status'   => $newStatus,
            ]);

            // Create the borrow record
            $borrow = Borrow::create([
                'item_id'              => $item->id,
                'borrower_name'        => $request->borrower_name,
                'contact'              => $request->contact,
                'quantity'             => $request->quantity,
                'borrow_date'          => $request->borrow_date,
                'expected_return_date' => $request->expected_return_date,
                'notes'                => $request->notes,
                'status'               => Borrow::STATUS_BORROWED,
                'processed_by'         => $request->user()->id,
            ]);

            $borrow->load(['item.place.cupboard', 'processedBy']);

            // Audit log — records both the quantity change and the borrow event
            AuditLog::record(
                action:        AuditLog::ITEM_BORROWED,
                entityType:    'Item',
                entityId:      $item->id,
                entityName:    $item->name,
                previousValue: ['quantity' => $beforeQty, 'status' => $beforeStatus],
                newValue:      [
                    'quantity'      => $newQty,
                    'status'        => $newStatus,
                    'borrower'      => $request->borrower_name,
                    'qty_borrowed'  => $request->quantity,
                    'due_date'      => $request->expected_return_date,
                ],
            );

            return response()->json([
                'success' => true,
                'message' => 'Item borrowed successfully.',
                'data'    => new BorrowResource($borrow),
            ], 201);
        });
    }

    /**
     * PATCH /api/v1/borrows/{borrow}/return
     *
     * Return flow:
     * 1. Validate borrow is still active
     * 2. Lock item row
     * 3. Add quantity back
     * 4. Update item status based on return condition
     * 5. Close the borrow record
     * 6. Write audit log
     * All inside a DB transaction.
     */
    public function processReturn(ProcessReturnRequest $request, Borrow $borrow): JsonResponse
    {
        if ($borrow->status === Borrow::STATUS_RETURNED) {
            return response()->json([
                'success' => false,
                'message' => 'This borrow has already been returned.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $borrow) {

            $item = Item::lockForUpdate()->findOrFail($borrow->item_id);

            $beforeQty    = $item->quantity;
            $beforeStatus = $item->status;

            // Add quantity back
            $newQty = $item->quantity + $borrow->quantity;

            // Status rule based on return condition:
            // - 'damaged' condition → item stays damaged, needs admin review
            // - 'good' or 'fair' → restore to instore
            $newStatus = $request->return_condition === 'damaged'
                ? Item::STATUS_DAMAGED
                : Item::STATUS_INSTORE;

            $item->update([
                'quantity' => $newQty,
                'status'   => $newStatus,
            ]);

            // Close the borrow
            $borrow->update([
                'status'           => Borrow::STATUS_RETURNED,
                'actual_return_date' => now()->toDateString(),
                'return_condition' => $request->return_condition,
                'notes'            => $request->notes ?? $borrow->notes,
            ]);

            $borrow->load(['item.place.cupboard', 'processedBy']);

            // Audit log
            AuditLog::record(
                action:        AuditLog::ITEM_RETURNED,
                entityType:    'Item',
                entityId:      $item->id,
                entityName:    $item->name,
                previousValue: ['quantity' => $beforeQty, 'status' => $beforeStatus],
                newValue:      [
                    'quantity'         => $newQty,
                    'status'           => $newStatus,
                    'returned_by'      => $borrow->borrower_name,
                    'qty_returned'     => $borrow->quantity,
                    'return_condition' => $request->return_condition,
                ],
            );

            return response()->json([
                'success' => true,
                'message' => 'Return processed successfully.',
                'data'    => new BorrowResource($borrow),
            ]);
        });
    }
}
