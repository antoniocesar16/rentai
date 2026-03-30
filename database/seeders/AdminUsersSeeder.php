<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Features;
use Spatie\Permission\Models\Role;

class AdminUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::findOrCreate('admin', 'web');

        $admins = [
            [
                'name' => 'Admin Principal',
                'email' => 'admin1@example.com',
                'password' => 'password',
            ],
            [
                'name' => 'Admin Secundario',
                'email' => 'admin2@example.com',
                'password' => 'password',
            ],
        ];

        foreach ($admins as $admin) {
            $user = User::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make($admin['password']),
                    'email_verified_at' => now(),
                ]
            );

            if ($user->name !== $admin['name']) {
                $user->forceFill(['name' => $admin['name']])->save();
            }

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            if (! $user->hasRole('admin')) {
                $user->assignRole('admin');
            }

            if (Features::hasTeamFeatures() && ! $user->ownedTeams()->where('personal_team', true)->exists()) {
                $team = Team::forceCreate([
                    'user_id' => $user->id,
                    'name' => $user->name."'s Team",
                    'personal_team' => true,
                ]);

                $user->forceFill(['current_team_id' => $team->id])->save();
            }
        }
    }
}

