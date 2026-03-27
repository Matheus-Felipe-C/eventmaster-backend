<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function index()
    {
        // Retorna todos os usuários onde o nome da role não seja o de usuário comum
        $staffs = User::whereHas('role', function ($query) {
            $query->where('name', '!=', 'user');
        })->with('role')->get();

        return response()->json($staffs, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'cpf' => 'required|string|unique:users,cpf',
            'password' => 'required|string|min:6',
        ]);

        $staffRole = Role::where('name', 'staff')->first();

        if (! $staffRole) {
            return response()->json(['message' => __('Staff role not configured.')], 500);
        }

        $staff = User::create([
            ...$validated,
            'id_role' => $staffRole->id,
            'password' => Hash::make($validated['password']),
        ]);

        $staff->load('role');

        return response()->json($staff, 201);
    }

    public function show(User $staff)
    {
        return response()->json($staff->load('role'), 200);
    }

    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $staff->id,
            'cpf' => 'sometimes|required|string|unique:users,cpf,' . $staff->id,
        ]);

        if ($request->has('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $staff->update($validated);
        $staff->load('role');

        return response()->json($staff, 200);
    }

    public function destroy(User $staff)
    {
        $userRole = Role::where('name', 'user')->first();

        if (! $userRole) {
            return response()->json(['message' => __('User role not configured.')], 500);
        }

        $staff->id_role = $userRole->id;
        $staff->save();
        $staff->load('role');

        return response()->json([
            'message' => __('Staff role removed successfully.'),
            'user' => $staff,
        ], 200);
    }
}