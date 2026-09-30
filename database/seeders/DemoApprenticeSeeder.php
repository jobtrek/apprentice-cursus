<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            User::query()->firstOrNew(['id' => (int) $row['id']])->forceFill([
                'name' => $row['name'],
                'email' => "demo-apprentice-{$row['id']}@example.com",
                'password' => 'password',
                'role' => UserRole::Apprentice,
                'is_active' => true,
                'apprenticeship_id' => $apprenticeships[$row['track']] ?? null,
            ])->save();
        }

        DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users))");
    }
}
