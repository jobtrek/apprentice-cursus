<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ApprenticeshipContext;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds local demo apprentices, with the ids the demo averages in
 * resources/js/data/dashboard.ts are keyed on. Each one is put in the standard
 * (non-MP) context of its section (created by EvaluationTreeSeeder), or seeded
 * with no context when the section has none yet.
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
        $demo = json_decode((string) file_get_contents(database_path('seeders/data/demo_apprentices.json')), true);

        // Standard (non-MP) context of each section, keyed by its short name ("IT", "EC").
        $contexts = ApprenticeshipContext::query()
            ->where('is_mp', false)
            ->with('apprenticeship')
            ->get()
            ->mapWithKeys(fn (ApprenticeshipContext $context): array => [
                $context->apprenticeship->shortName() => $context->id,
            ]);

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
}
