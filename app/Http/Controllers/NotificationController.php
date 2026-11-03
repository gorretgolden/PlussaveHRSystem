<?php

namespace App\Http\Controllers;

use App\Models\HrNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function markRead(Request $request): RedirectResponse
    {
        $employeeId = $request->user()->employee_id;

        abort_unless($employeeId, 403, 'Your account is not linked to an employee record.');

        $count = HrNotification::where('employee_id', $employeeId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('status', $count
            ? $count.' '.Str::plural('notification', $count).' marked as read.'
            : 'You have no unread notifications.');
    }
}
