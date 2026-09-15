<?php

namespace App\Enums;

enum AttendanceNotificationPhase: string
{
    case CheckIn = 'check-in';
    case CheckOut = 'check-out';
}
