<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
}
