<?php

namespace Database\Seeders;

use App\Models\Apprenticeship;
use Illuminate\Database\Seeder;

/**
 * Seeds the two apprenticeships the app supports (IT and EC).
 */
class ApprenticeshipSeeder extends Seeder
{
    public const IT = 'Informaticien·ne CFC';

    public const EC = 'Employé·e de commerce CFC';

    public function run(): void
    {
        foreach ([self::IT, self::EC] as $name) {
            Apprenticeship::query()->firstOrCreate(['name' => $name]);
        }
    }
}
