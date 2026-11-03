<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Console\Command;

class SyncLeaveStatus extends Command
{
    protected $signature = 'hr:sync-leave-status';

    protected $description = 'Mark employees On Leave while approved leave covers today, and return them to Active afterwards';

    public function handle(): int
    {
        $today = now()->toDateString();

        $covered = LeaveRequest::where('status', 'Approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('employee_id')
            ->unique();

        $changed = 0;

        Employee::whereIn('id', $covered)->where('status', 'Active')->get()
            ->each(function (Employee $employee) use (&$changed) {
                $employee->update(['status' => 'On Leave']);
                AuditLog::record(null, null, 'Leave status updated', $employee->id, $employee->full_name.' set to On Leave for approved leave covering today');
                $changed++;
            });

        Employee::where('status', 'On Leave')->whereNotIn('id', $covered)->get()
            ->each(function (Employee $employee) use (&$changed) {
                $employee->update(['status' => 'Active']);
                AuditLog::record(null, null, 'Leave status updated', $employee->id, $employee->full_name.' returned to Active after leave ended');
                $changed++;
            });

        $this->info($changed.' employee status'.($changed === 1 ? '' : 'es').' updated.');

        return self::SUCCESS;
    }
}
