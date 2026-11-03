<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\HrRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    public function workingDays(Carbon $from, Carbon $to): float
    {
        $holidays = Holiday::pluck('day')->map(fn ($d) => Carbon::parse($d)->toDateString())->all();
        $days = 0;
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            if ($d->isWeekend() || in_array($d->toDateString(), $holidays, true)) {
                continue;
            }
            $days++;
        }

        return (float) $days;
    }

    public function overlap(Employee $employee, Carbon $from, Carbon $to): ?string
    {
        $clash = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->orderBy('start_date')
            ->first();

        if ($clash) {
            return 'These dates overlap leave already approved from '
                .Carbon::parse($clash->start_date)->format('j M Y').' to '.Carbon::parse($clash->end_date)->format('j M Y').'.';
        }

        $pending = HrRequest::where('kind', 'Leave application')
            ->where('status', 'pending')
            ->where('maker', $employee->id)
            ->get()
            ->first(fn (HrRequest $r) => ($r->payload['from'] ?? '') <= $to->toDateString()
                && ($r->payload['to'] ?? '') >= $from->toDateString());

        if ($pending) {
            return 'These dates overlap '.$pending->ref.', which is still waiting for approval.';
        }

        return null;
    }

    public function apply(Employee $employee, User $user, array $data): HrRequest
    {
        $from = Carbon::parse($data['from']);
        $to = Carbon::parse($data['to']);
        $days = $this->workingDays($from, $to);
        $type = LeaveType::findOrFail($data['type']);

        $chain = $user->role === 'staff' ? ['hr', 'md'] : ['md'];

        return DB::transaction(function () use ($employee, $user, $data, $from, $to, $days, $type, $chain) {
            $request = HrRequest::createWithRef([
                'kind' => 'Leave application',
                'subject' => sprintf('%s leave, %s to %s (%s days)', $type->name, $from->format('d M'), $to->format('d M'), $days),
                'target_employee' => $employee->id,
                'maker' => $employee->id,
                'maker_role' => $user->role,
                'fields' => [
                    ['Leave type', $type->name],
                    ['Dates', $from->format('d M Y').' to '.$to->format('d M Y')],
                    ['Working days', (string) $days],
                    ['Reason', ($data['reason'] ?? '') ?: '-'],
                ],
                'payload' => [
                    'emp' => $employee->id,
                    'type' => $type->code,
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'days' => $days,
                ],
                'chain' => $chain,
                'stage' => 0,
                'status' => 'pending',
            ]);

            $request->actions()->create([
                'actor' => $employee->id,
                'actor_role' => $user->role,
                'action' => 'Submitted',
                'comment' => ($data['reason'] ?? '') ?: '',
            ]);

            NotificationService::role($chain[0], $request->ref.' '.$request->kind.': '.$request->subject.' is waiting for your review');

            AuditLog::record($employee->id, $user->role, 'Submitted', $request->ref, $request->kind.': '.$request->subject);

            return $request;
        });
    }

    public static function balanceColumn(string $type): ?string
    {
        return ['Annual' => 'annual_balance', 'Sick' => 'sick_balance', 'Other' => 'other_balance'][$type] ?? null;
    }

    public static function approvalProblem(HrRequest $request): ?string
    {
        $p = $request->payload ?? [];
        $employee = Employee::find($p['emp'] ?? null);

        if (! $employee) {
            return 'The employee record for this request no longer exists.';
        }

        $days = (float) ($p['days'] ?? 0);
        $column = self::balanceColumn((string) ($p['type'] ?? ''));

        if ($column && $days > (float) $employee->{$column}) {
            return 'This request needs '.rtrim(rtrim(number_format($days, 2), '0'), '.')
                .' working '.str('day')->plural($days).' but '.$employee->first_name
                .' has only '.(float) $employee->{$column}.' left in that balance.';
        }

        $clash = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->whereDate('start_date', '<=', $p['to'])
            ->whereDate('end_date', '>=', $p['from'])
            ->orderBy('start_date')
            ->first();

        if ($clash) {
            return 'These dates now overlap leave approved from '
                .Carbon::parse($clash->start_date)->format('j M Y').' to '.Carbon::parse($clash->end_date)->format('j M Y').'.';
        }

        return null;
    }

    public static function postApproval(HrRequest $request): void
    {
        $p = $request->payload;
        $employee = Employee::find($p['emp']);

        if (! $employee) {
            return;
        }

        if (in_array($p['type'], ['Annual', 'Sick', 'Other'], true)) {
            $column = ['Annual' => 'annual_balance', 'Sick' => 'sick_balance', 'Other' => 'other_balance'][$p['type']];
            $employee->decrement($column, $p['days']);
        }

        LeaveRequest::createWithRef([
            'employee_id' => $employee->id,
            'type_code' => $p['type'],
            'start_date' => $p['from'],
            'end_date' => $p['to'],
            'days' => $p['days'],
            'reason' => $request->subject,
            'status' => 'Approved',
            'request_id' => $request->id,
            'decided_at' => now(),
        ]);

        $today = now()->toDateString();
        if ($p['from'] <= $today && $p['to'] >= $today) {
            $employee->update(['status' => 'On Leave']);
        }
    }
}
