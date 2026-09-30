<?php

namespace App\Enums;

enum UserRole: string
{
    case Apprentice = 'apprentice';
    case Coach = 'coach';
    case Trainer = 'trainer';
}
