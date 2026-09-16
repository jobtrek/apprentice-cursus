<?php

namespace App\Enums;

enum UserRole: string
{
    case Apprentice = 'apprentice';
    case Coach = 'coach';
    case Trainer = 'trainer';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';
}
