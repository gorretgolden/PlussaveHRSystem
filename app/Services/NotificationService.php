<?php

namespace App\Services;

use App\Models\HrNotification;
use App\Models\User;

class NotificationService
{
    public static function to(string $employeeId, string $text): void
    {
        HrNotification::create([
            'employee_id' => $employeeId,
            'text' => $text,
            'is_read' => false,
            'created_at' => now(),
        ]);
    }

    public static function role(string $role, string $text): void
    {
        User::query()
            ->where('role', $role)
            ->where('active', true)
            ->whereNotNull('employee_id')
            ->pluck('employee_id')
            ->each(fn ($id) => static::to($id, $text));
    }
}
