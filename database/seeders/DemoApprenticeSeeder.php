<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds a fixed set of demo apprentices (ids 1..8) for local
 * development.
 */
class DemoApprenticeSeeder extends Seeder
{
    public const COUNT = 8;

    /** @var list<array{id: int, name: string, apprenticeship: 'IT'|'EC'}> */
    private const DEMO = [
        ['id' => 1, 'name' => 'Léa Dubois', 'apprenticeship' => 'IT'],
        ['id' => 2, 'name' => 'Nathan Girard', 'apprenticeship' => 'IT'],
        ['id' => 3, 'name' => 'Camille Petit', 'apprenticeship' => 'EC'],
        ['id' => 4, 'name' => 'Hugo Moreau', 'apprenticeship' => 'EC'],
        ['id' => 5, 'name' => 'Manon Rousseau', 'apprenticeship' => 'IT'],
        ['id' => 6, 'name' => 'Théo Simon', 'apprenticeship' => 'IT'],
        ['id' => 7, 'name' => 'Chloé Laurent', 'apprenticeship' => 'EC'],
        ['id' => 8, 'name' => 'Lucas Michel', 'apprenticeship' => 'EC'],
    ];

    public static function email(int|string $id): string
    {
        return "demo-apprentice-{$id}@example.com";
    }

    public function run(): void
    {
        $apprenticeships = [
            'IT' => Apprenticeship::where('name', ApprenticeshipSeeder::IT)->value('id'),
            'EC' => Apprenticeship::where('name', ApprenticeshipSeeder::EC)->value('id'),
        ];

        foreach (self::DEMO as $row) {
            // Never overwrite an existing account (name, password, role, active state).
            if (User::query()->whereKey($row['id'])->exists()) {
                continue;
            }

            $user = User::query()->forceCreate([
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => self::email($row['id']),
                // Random, unknown password: demo accounts have no usable local credentials.
                'password' => Str::password(32),
                'is_active' => true,
                'apprenticeship_id' => $apprenticeships[$row['apprenticeship']] ?? null,
            ]);

            $user->assignRole(UserRole::Apprentice->value);
        }

        DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users))");
    }
}
