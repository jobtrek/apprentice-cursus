<?php

namespace App\Exceptions;

use RuntimeException;

class ApprenticeshipNotSeededException extends RuntimeException
{
    public function __construct(public readonly string $apprenticeship)
    {
        parent::__construct("Apprenticeship [{$apprenticeship}] is not seeded.");
    }
}
