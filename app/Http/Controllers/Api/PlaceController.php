<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceResource;
use App\Models\AuditLog;
use App\Models\Cupboard;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaceController extends Controller
{
    public function store(Request $request, Cupboard $cupboard): JsonResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:100',
                           // Unique within the same cupboard
                           \Illuminate\Validation\Rule::unique('places')->where('cupboard_id', $cupboard->id)],
            'capacity' => 'required|integer|min:1|max:999',
        ]);

        $place = $cupboard->places()->create($request->only('name', 'capacity'));

        AuditLog::record(
            action:     AuditLog::PLACE_CREATED,
            entityType: 'Place',
            entityId:   $place->id,
            entityName: "{$place->name} → {$cupboard->name}",
            newValue:   ['name' => $place->name, 'cupboard' => $cupboard->name, 'capacity' => $place->capacity],
        );

        return response()->json([
            'success' => true,
            'message' => 'Place created.',
            'data'    => new PlaceResource($place->load('cupboard')),
        ], 201);
    }

    public function show(Place $place): JsonResponse
    {
        $place->load(['cupboard', 'items']);

        return response()->json([
            'success' => true,
            'data'    => new PlaceResource($place),
        ]);
    }

    public function update(Request $request, Place $place): JsonResponse
    {
        $request->validate([
            'name'     => ['sometimes', 'string', 'max:100',
                           \Illuminate\Validation\Rule::unique('places')
                               ->where('cupboard_id', $place->cupboard_id)
                               ->ignore($place->id)],
            'capacity' => 'sometimes|integer|min:1|max:999',
        ]);

        $before = $place->only(['name', 'capacity']);
        $place->update($request->only('name', 'capacity'));

        AuditLog::record(
            action:        AuditLog::PLACE_UPDATED,
            entityType:    'Place',
            entityId:      $place->id,
            entityName:    $place->name,
            previousValue: $before,
            newValue:      $place->only(['name', 'capacity']),
        );

        return response()->json([
            'success' => true,
            'message' => 'Place updated.',
            'data'    => new PlaceResource($place->load('cupboard')),
        ]);
    }

    public function destroy(Place $place): JsonResponse
    {
        if ($place->items()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a place that has items assigned to it. Relocate items first.',
            ], 422);
        }

        AuditLog::record(
            action:        AuditLog::PLACE_DELETED,
            entityType:    'Place',
            entityId:      $place->id,
            entityName:    $place->name,
            previousValue: ['name' => $place->name, 'capacity' => $place->capacity],
        );

        $place->delete();

        return response()->json([
            'success' => true,
            'message' => 'Place deleted.',
        ]);
    }
}
