<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OrganizerController extends Controller
{
    /**
     * List all organizer users.
     */
    public function index(Request $request): JsonResponse
    {
        $organizerRole = Role::where('name', 'organizer')->first();

        if (! $organizerRole) {
            return response()->json([
                'message' => __('Organizer role not configured.'),
            ], 500);
        }

        $organizers = User::where('id_role', $organizerRole->id)
            ->with('role')
            ->paginate($request->integer('per_page', 15));

        return response()->json($organizers);
    }

    /**
     * Create a new organizer user.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'max:14', 'unique:users,cpf'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:32'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::where('name', 'organizer')->first();

        if (! $role) {
            return response()->json([
                'message' => __('Role not found.'),
            ], 500);
        }

        $user = User::create([
            'id_role' => $role->id,
            'name' => $request->name,
            'cpf' => $request->cpf,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'reason' => $request->reason,
            'password' => Hash::make($request->password),
        ]);

        $user->load('role');

        return response()->json([
            'message' => __('Organizer created successfully.'),
            'user' => [
                'id' => $user->id,
                'id_role' => $user->id_role,
                'role' => $user->role->name,
                'name' => $user->name,
                'cpf' => $user->cpf,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'reason' => $user->reason,
            ],
        ], 201);
    }

    /**
     * Promote an organizer to admin.
     */
    public function promote(User $user): JsonResponse
    {
        $organizerRole = Role::where('name', 'organizer')->first();
        $adminRole = Role::where('name', 'admin')->first();

        if (! $organizerRole || ! $adminRole) {
            return response()->json([
                'message' => __('Role not found.'),
            ], 500);
        }

        if ((int) $user->id_role !== (int) $organizerRole->id) {
            return response()->json([
                'message' => __('User is not an organizer.'),
            ], 422);
        }

        $user->id_role = $adminRole->id;
        $user->save();
        $user->load('role');

        return response()->json([
            'message' => __('Organizer promoted to admin successfully.'),
            'user' => [
                'id' => $user->id,
                'id_role' => $user->id_role,
                'role' => $user->role->name,
                'name' => $user->name,
                'cpf' => $user->cpf,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'reason' => $user->reason,
            ],
        ]);
    }

    /**
     * Demote an organizer to normal user.
     */
    public function demote(User $user): JsonResponse
    {
        $organizerRole = Role::where('name', 'organizer')->first();
        $userRole = Role::where('name', 'user')->first();

        if (! $organizerRole || ! $userRole) {
            return response()->json([
                'message' => __('Role not found.'),
            ], 500);
        }

        if ((int) $user->id_role !== (int) $organizerRole->id) {
            return response()->json([
                'message' => __('User is not an organizer.'),
            ], 422);
        }

        $user->id_role = $userRole->id;
        $user->save();
        $user->load('role');

        return response()->json([
            'message' => __('Organizer demoted to user successfully.'),
            'user' => [
                'id' => $user->id,
                'id_role' => $user->id_role,
                'role' => $user->role->name,
                'name' => $user->name,
                'cpf' => $user->cpf,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'reason' => $user->reason,
            ],
        ]);
    }
}
