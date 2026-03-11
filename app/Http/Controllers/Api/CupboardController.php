<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCupboardRequest;
use App\Http\Resources\CupboardResource;
use App\Models\AuditLog;
use App\Models\Cupboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CupboardController extends Controller
{
    public function index(): JsonResponse
    {
        $cupboards = Cupboard::with(['places.items'])->get();

        return response()->json([
            'success' => true,
            'data'    => CupboardResource::collection($cupboards),
        ]);
    }

    public function store(StoreCupboardRequest $request): JsonResponse
    {
        $cupboard = Cupboard::create($request->validated());

        AuditLog::record(
            action:     AuditLog::CUPBOARD_CREATED,
            entityType: 'Cupboard',
            entityId:   $cupboard->id,
            entityName: $cupboard->name,
            newValue:   ['name' => $cupboard->name, 'code' => $cupboard->code, 'location' => $cupboard->location],
        );

        return response()->json([
            'success' => true,
            'message' => 'Cupboard created.',
            'data'    => new CupboardResource($cupboard),
        ], 201);
    }

    public function show(Cupboard $cupboard): JsonResponse
    {
        $cupboard->load(['places.items']);

        return response()->json([
            'success' => true,
            'data'    => new CupboardResource($cupboard),
        ]);
    }

    public function update(StoreCupboardRequest $request, Cupboard $cupboard): JsonResponse
    {
        $before = $cupboard->only(['name', 'code', 'description', 'location']);
        $cupboard->update($request->validated());

        AuditLog::record(
            action:        AuditLog::CUPBOARD_UPDATED,
            entityType:    'Cupboard',
            entityId:      $cupboard->id,
            entityName:    $cupboard->name,
            previousValue: $before,
            newValue:      $cupboard->only(['name', 'code', 'description', 'location']),
        );

        return response()->json([
            'success' => true,
            'message' => 'Cupboard updated.',
            'data'    => new CupboardResource($cupboard),
        ]);
    }

    public function destroy(Cupboard $cupboard): JsonResponse
    {
        // Prevent deletion if cupboard has places with items
        $hasItems = $cupboard->items()->exists();

        if ($hasItems) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete cupboard with items stored inside. Relocate items first.',
            ], 422);
        }

        AuditLog::record(
            action:        AuditLog::CUPBOARD_DELETED,
            entityType:    'Cupboard',
            entityId:      $cupboard->id,
            entityName:    $cupboard->name,
            previousValue: ['name' => $cupboard->name, 'code' => $cupboard->code],
        );

        $cupboard->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cupboard deleted.',
        ]);
    }
}
