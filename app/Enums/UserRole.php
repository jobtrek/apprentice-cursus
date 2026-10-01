<?php

namespace App\Enums;

enum UserRole: string
{
    case Apprentice = 'apprentice';
    case Coach = 'coach';
    case Trainer = 'trainer';
    /** Local-only dev role: grants nothing outside the local environment (see User::isLocalAdmin()). */
    case Admin = 'admin';
}
