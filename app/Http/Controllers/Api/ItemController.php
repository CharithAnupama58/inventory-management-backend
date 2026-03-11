<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\AuditLog;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    /**
     * GET /api/v1/items
     * Supports: search, status, cupboard_id, place_id filters + pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Item::with(['place.cupboard']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('code', 'ilike', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('place_id')) {
            $query->where('place_id', $request->place_id);
        }

        if ($request->filled('cupboard_id')) {
            $query->whereHas('place', fn($q) =>
                $q->where('cupboard_id', $request->cupboard_id)
            );
        }

        $items = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => ItemResource::collection($items->items()),
            'meta'    => [
                'total'        => $items->total(),
                'per_page'     => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'counts'       => [
                    'instore'  => Item::where('status', 'instore')->count(),
                    'borrowed' => Item::where('status', 'borrowed')->count(),
                    'damaged'  => Item::where('status', 'damaged')->count(),
                    'missing'  => Item::where('status', 'missing')->count(),
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/items
     * Admin creates a new inventory item. Handles image upload.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('items', 'public');
        }

        $item = Item::create($data);
        $item->load('place.cupboard');

        AuditLog::record(
            action:     AuditLog::ITEM_CREATED,
            entityType: 'Item',
            entityId:   $item->id,
            entityName: $item->name,
            newValue:   [
                'code'     => $item->code,
                'quantity' => $item->quantity,
                'status'   => $item->status,
                'place'    => $item->place->name,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Item created successfully.',
            'data'    => new ItemResource($item),
        ], 201);
    }

    /**
     * GET /api/v1/items/{item}
     */
    public function show(Item $item): JsonResponse
    {
        $item->load(['place.cupboard', 'borrows' => fn($q) => $q->latest()->limit(5)]);

        return response()->json([
            'success' => true,
            'data'    => new ItemResource($item),
        ]);
    }

    /**
     * PUT /api/v1/items/{item}
     * Admin updates item details (not quantity/status — those have dedicated endpoints).
     */
    public function update(UpdateItemRequest $request, Item $item): JsonResponse
    {
        $before = $item->only(['name', 'code', 'description', 'place_id', 'serial_number']);

        $data = $request->validated();

        // Handle image replacement
        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $data['image_path'] = $request->file('image')->store('items', 'public');
        }

        $item->update($data);
        $item->load('place.cupboard');

        AuditLog::record(
            action:        AuditLog::ITEM_UPDATED,
            entityType:    'Item',
            entityId:      $item->id,
            entityName:    $item->name,
            previousValue: $before,
            newValue:      $item->only(['name', 'code', 'description', 'place_id', 'serial_number']),
        );

        return response()->json([
            'success' => true,
            'message' => 'Item updated successfully.',
            'data'    => new ItemResource($item),
        ]);
    }

    /**
     * DELETE /api/v1/items/{item}
     * Cannot delete items that are currently borrowed.
     */
    public function destroy(Item $item): JsonResponse
    {
        if ($item->status === Item::STATUS_BORROWED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete an item that is currently borrowed.',
            ], 422);
        }

        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }

        AuditLog::record(
            action:        AuditLog::ITEM_DELETED,
            entityType:    'Item',
            entityId:      $item->id,
            entityName:    $item->name,
            previousValue: ['code' => $item->code, 'quantity' => $item->quantity, 'status' => $item->status],
        );

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item deleted successfully.',
        ]);
    }

    /**
     * PATCH /api/v1/items/{item}/status
     * Admin manually updates item status (e.g. mark as damaged/missing).
     * Valid transitions are enforced here.
     */
    public function updateStatus(Request $request, Item $item): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:instore,borrowed,damaged,missing',
        ]);

        // Business rule: cannot manually set to 'borrowed' — must go through borrow flow
        if ($request->status === Item::STATUS_BORROWED) {
            return response()->json([
                'success' => false,
                'message' => 'Use the borrow endpoint to mark an item as borrowed.',
            ], 422);
        }

        $before = $item->status;
        $item->update(['status' => $request->status]);

        AuditLog::record(
            action:        AuditLog::ITEM_STATUS_CHANGED,
            entityType:    'Item',
            entityId:      $item->id,
            entityName:    $item->name,
            previousValue: ['status' => $before],
            newValue:      ['status' => $item->status],
        );

        return response()->json([
            'success' => true,
            'message' => 'Item status updated.',
            'data'    => new ItemResource($item),
        ]);
    }

    /**
     * PATCH /api/v1/items/{item}/quantity
     * Admin adjusts quantity (restock, correction, etc.)
     * Uses DB transaction to prevent race conditions.
     */
    public function adjustQuantity(Request $request, Item $item): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:increment,decrement,set',
            'amount' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request, $item) {
            // Lock the row for update to prevent concurrent modification
            $item = Item::lockForUpdate()->findOrFail($item->id);
            $before = $item->quantity;

            $newQty = match ($request->action) {
                'increment' => $item->quantity + $request->amount,
                'decrement' => $item->quantity - $request->amount,
                'set'       => $request->amount,
            };

            if ($newQty < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantity cannot go below zero.',
                ], 422);
            }

            $item->update(['quantity' => $newQty]);

            AuditLog::record(
                action:        AuditLog::ITEM_QUANTITY_CHANGED,
                entityType:    'Item',
                entityId:      $item->id,
                entityName:    $item->name,
                previousValue: ['quantity' => $before],
                newValue:      ['quantity' => $newQty, 'action' => $request->action, 'amount' => $request->amount],
            );

            return response()->json([
                'success' => true,
                'message' => 'Quantity updated.',
                'data'    => new ItemResource($item->fresh()),
            ]);
        });
    }
}
