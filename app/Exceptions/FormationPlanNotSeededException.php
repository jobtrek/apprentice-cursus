<?php

namespace App\Exceptions;

use RuntimeException;

class FormationPlanNotSeededException extends RuntimeException
{
    public function __construct(public readonly string $formationPlan)
    {
        parent::__construct("Formation plan [{$formationPlan}] is not seeded.");
    }
}
