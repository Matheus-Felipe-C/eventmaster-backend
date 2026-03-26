<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * List all users.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::with('role')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        $users->getCollection()->transform(function (User $user) {
            return $user->makeHidden(['password', 'remember_token']);
        });

        return response()->json($users);
    }

    /**
     * Create a new user with a given role (e.g. admin, organizer).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'max:14', 'unique:users,cpf'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:user,admin,organizer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::where('name', $request->input('role'))->first();

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
            'password' => Hash::make($request->password),
        ]);

        $user->load('role');
        $user->makeHidden(['password', 'remember_token']);

        return response()->json([
            'message' => __('User created successfully.'),
            'user' => $user,
        ], 201);
    }   

    /**
     * Update a user's role.
     */
    public function updateRole(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => ['required', 'string', 'in:user,admin,organizer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('The given data was invalid.'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = Role::where('name', $request->input('role'))->first();

        if (! $role) {
            return response()->json([
                'message' => __('Role not found.'),
            ], 500);
        }

        $user->id_role = $role->id;
        $user->save();
        $user->load('role');
        $user->makeHidden(['password', 'remember_token']);

        return response()->json([
            'message' => __('User role updated successfully.'),
            'user' => $user,
        ]);
    }

    /**
     * Delete a user (admin only). Policy enforces: no root, no self, keep last admin.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $user->deleteAccount();

        return response()->json(['message' => __('User deleted successfully.')]);
    }
}
