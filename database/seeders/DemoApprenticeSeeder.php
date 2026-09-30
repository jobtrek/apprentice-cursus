<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the apprentices shown on the (still static) apprentices dashboard,
 * using the same ids so its links resolve to real users.
 */
class DemoApprenticeSeeder extends Seeder
{
    public const COUNT = 8;

    public function run(): void
    {
        /** @var list<array{id: string, name: string, track: string}> $demo */
        $demo = json_decode((string) file_get_contents(resource_path('js/data/apprentices.json')), true);

        $apprenticeships = [
            'IT' => Apprenticeship::where('name', ApprenticeshipSeeder::IT)->value('id'),
            'EC' => Apprenticeship::where('name', ApprenticeshipSeeder::EC)->value('id'),
        ];

        foreach ($demo as $row) {
            // Never overwrite an existing account (name, password, role, active state).
            if (User::query()->whereKey((int) $row['id'])->exists()) {
                continue;
            }

            $user = User::query()->forceCreate([
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'email' => "demo-apprentice-{$row['id']}@example.com",
                // Random, unknown password: demo accounts have no usable local credentials.
                'password' => Str::password(32),
                'is_active' => true,
                'apprenticeship_id' => $apprenticeships[$row['track']] ?? null,
            ]);

            $user->assignRole(UserRole::Apprentice->value);
        }

        DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users))");
    }
}
