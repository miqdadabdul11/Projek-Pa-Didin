<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'client', 'operator', 'maintenance', 'viewer'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@bems.id'],
            [
                'name' => 'Kita Ni Admin',
                'password' => Hash::make('Ddw9889##'),
                'is_approved' => true,
            ]
        );

        $admin->update(['is_approved' => true]);
        $admin->assignRole('admin');
    }
}
