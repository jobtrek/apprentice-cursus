<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Apprenticeship;
use App\Models\ApprenticeshipContext;
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

    public static function email(int|string $id): string
    {
        return "demo-apprentice-{$id}@example.com";
    }

    public function run(): void
    {
        /** @var list<array{id: string, name: string, track: string}> $demo */
        $demo = json_decode((string) file_get_contents(resource_path('js/data/apprentices.json')), true);

        $contexts = [
            'IT' => $this->contextId(ApprenticeshipSeeder::IT),
            'EC' => $this->contextId(ApprenticeshipSeeder::EC),
        ];

        foreach ($demo as $row) {
            // Never overwrite an existing account (name, password, role, active state).
            if (User::query()->whereKey((int) $row['id'])->exists()) {
                continue;
            }

            $user = User::query()->forceCreate([
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'email' => self::email($row['id']),
                // Random, unknown password: demo accounts have no usable local credentials.
                'password' => Str::password(32),
                'is_active' => true,
                'apprenticeship_context_id' => $contexts[$row['track']] ?? null,
            ]);

            $user->assignRole(UserRole::Apprentice->value);
        }

        DB::statement("SELECT setval(pg_get_serial_sequence('users', 'id'), (SELECT MAX(id) FROM users))");
    }

    private function contextId(string $apprenticeshipName): ?int
    {
        $apprenticeshipId = Apprenticeship::where('name', $apprenticeshipName)->value('id');

        return $apprenticeshipId === null
            ? null
            : ApprenticeshipContext::where('apprenticeship_id', $apprenticeshipId)->value('id');
    }
}
