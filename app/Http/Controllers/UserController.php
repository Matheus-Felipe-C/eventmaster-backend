<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            return [
                'id' => $user->id,
                'id_role' => $user->id_role,
                'role' => $user->role->name,
                'name' => $user->name,
                'cpf' => $user->cpf,
                'email' => $user->email,
            ];
        });

        return response()->json($users);
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

        return response()->json([
            'message' => __('User role updated successfully.'),
            'user' => [
                'id' => $user->id,
                'id_role' => $user->id_role,
                'role' => $user->role->name,
                'name' => $user->name,
                'cpf' => $user->cpf,
                'email' => $user->email,
            ],
        ]);
    }
}
