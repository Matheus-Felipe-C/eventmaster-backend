<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Seed one default "user" account only when no users exist for this role.
     */
    public function run(): void
    {
        $role = Role::query()->where('name', 'user')->first();

        if (! $role) {
            return;
        }

        if (User::query()->where('id_role', $role->id)->exists()) {
            return;
        }

        User::query()->create([
            'id_role' => $role->id,
            'name' => env('DEFAULT_USER_NAME', 'Default User'),
            'cpf' => env('DEFAULT_USER_CPF', '111.111.111-11'),
            'email' => env('DEFAULT_USER_EMAIL', 'user@example.com'),
            'password' => Hash::make(env('DEFAULT_USER_PASSWORD', 'password')),
        ]);
    }
}
