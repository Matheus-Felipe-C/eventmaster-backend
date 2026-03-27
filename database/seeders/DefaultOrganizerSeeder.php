<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultOrganizerSeeder extends Seeder
{
    /**
     * Seed one default "organizer" account only when no organizers exist.
     */
    public function run(): void
    {
        $role = Role::query()->where('name', 'organizer')->first();

        if (! $role) {
            return;
        }

        if (User::query()->where('id_role', $role->id)->exists()) {
            return;
        }

        User::query()->create([
            'id_role' => $role->id,
            'name' => env('DEFAULT_ORGANIZER_NAME', 'Default Organizer'),
            'cpf' => env('DEFAULT_ORGANIZER_CPF', '222.222.222-22'),
            'email' => env('DEFAULT_ORGANIZER_EMAIL', 'organizer@example.com'),
            'password' => Hash::make(env('DEFAULT_ORGANIZER_PASSWORD', 'password')),
        ]);
    }
}
