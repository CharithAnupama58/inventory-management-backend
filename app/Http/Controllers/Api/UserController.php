<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/v1/users
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name',  'ilike', "%{$request->search}%")
                  ->orWhere('email', 'ilike', "%{$request->search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($users->items()),
            'meta'    => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
            'status'   => 'active',
        ]);

        AuditLog::record(
            action:     AuditLog::USER_CREATED,
            entityType: 'User',
            entityId:   $user->id,
            entityName: $user->name,
            newValue:   ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        );

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data'    => new UserResource($user),
        ], 201);
    }

    /**
     * GET /api/v1/users/{user}
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * PUT /api/v1/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $before = ['name' => $user->name, 'email' => $user->email, 'role' => $user->role];

        $data = $request->only(['name', 'email', 'role']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        $after = ['name' => $user->name, 'email' => $user->email, 'role' => $user->role];

        if ($before['role'] !== $after['role']) {
            AuditLog::record(
                action:        AuditLog::USER_ROLE_CHANGED,
                entityType:    'User',
                entityId:      $user->id,
                entityName:    $user->name,
                previousValue: ['role' => $before['role']],
                newValue:      ['role' => $after['role']],
            );
        } else {
            AuditLog::record(
                action:        AuditLog::USER_UPDATED,
                entityType:    'User',
                entityId:      $user->id,
                entityName:    $user->name,
                previousValue: $before,
                newValue:      $after,
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * DELETE /api/v1/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        AuditLog::record(
            action:        AuditLog::USER_DELETED,
            entityType:    'User',
            entityId:      $user->id,
            entityName:    $user->name,
            previousValue: ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        );

        // Null out borrow records that reference this user as processor
        // before deleting to avoid foreign key violation
        DB::table('borrows')
            ->where('processed_by', $user->id)
            ->update(['processed_by' => null]);

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * PATCH /api/v1/users/{user}/toggle-status
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 422);
        }

        $before = $user->status;
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        if ($user->status === 'inactive') {
            $user->tokens()->delete();
        }

        AuditLog::record(
            action:        $user->status === 'active' ? AuditLog::USER_ACTIVATED : AuditLog::USER_DEACTIVATED,
            entityType:    'User',
            entityId:      $user->id,
            entityName:    $user->name,
            previousValue: ['status' => $before],
            newValue:      ['status' => $user->status],
        );

        return response()->json([
            'success' => true,
            'message' => "User {$user->status} successfully.",
            'data'    => new UserResource($user),
        ]);
    }
}
