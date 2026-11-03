<?php

namespace App\Http\Controllers;

use App\Models\HrRequest;
use App\Services\ApprovalService;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request, ApprovalService $approvals): View
    {
        $user = $request->user();
        $isReviewer = in_array($user->role, ['hr', 'md'], true);
        $tab = $request->query('tab');
        $tab = is_string($tab) ? $tab : '';

        if (! $isReviewer) {
            $tab = 'mine';
        } elseif (! in_array($tab, ['awaiting', 'mine', 'all'], true)) {
            $tab = 'awaiting';
        }

        $base = HrRequest::with('actions')->orderByDesc('created_at');
        $mine = (clone $base)->where('maker', $user->employee_id);

        $awaiting = $isReviewer
            ? (clone $base)->where('status', 'pending')->get()->filter(fn (HrRequest $r) => $approvals->canAct($r, $user))
            : collect();

        $items = match ($tab) {
            'mine' => $mine->get(),
            'all' => $isReviewer ? (clone $base)->get() : $mine->get(),
            default => $awaiting,
        };

        return view('approvals.index', [
            'tab' => $tab,
            'isReviewer' => $isReviewer,
            'items' => $items,
            'awaitingCount' => $awaiting->count(),
            'mineCount' => HrRequest::where('maker', $user->employee_id)->count(),
        ]);
    }

    public function show(Request $request, HrRequest $hrRequest, ApprovalService $approvals): View
    {
        abort_unless(
            in_array($request->user()->role, ['hr', 'md'], true) || $hrRequest->maker === $request->user()->employee_id,
            403,
            'You can only open your own requests.',
        );

        return view('approvals.show', [
            'hr' => $hrRequest->load('actions'),
            'canAct' => $approvals->canAct($hrRequest, $request->user()),
            'stageRole' => $approvals->stageRole($hrRequest),
        ]);
    }

    public function act(Request $request, HrRequest $hrRequest, ApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:forward,approve,return,reject'],
            'comment' => ['nullable', 'string', 'max:600'],
        ]);

        if (! $approvals->canAct($hrRequest, $request->user())) {
            $message = match (true) {
                $hrRequest->maker === $request->user()->employee_id => 'You cannot act on your own request.',
                $approvals->stageRole($hrRequest) === $request->user()->role => 'Your role does not hold the '.$approvals->requiredPrivilege($hrRequest).' privilege for this stage.',
                default => 'You cannot act on this request at its current stage.',
            };

            return back()->withErrors(['action' => $message]);
        }

        if (! in_array($data['action'], $approvals->allowedActions($hrRequest), true)) {
            $message = match ($data['action']) {
                'approve' => 'This request still needs verification. Verify and forward it to the MD first.',
                'forward' => 'This request is already at the final stage. Approve, return or reject it.',
                default => 'That action is not available at this stage.',
            };

            return back()->withErrors(['action' => $message])->withInput();
        }

        if (in_array($data['action'], ['return', 'reject'], true) && trim((string) ($data['comment'] ?? '')) === '') {
            return back()->withErrors(['comment' => 'Add a comment so the maker knows what to fix.'])->withInput();
        }

        if ($data['action'] === 'approve' && $hrRequest->kind === 'Leave application'
            && ($problem = LeaveService::approvalProblem($hrRequest)) !== null) {
            return back()->withErrors(['action' => 'This leave cannot be approved. '.$problem]);
        }

        $sentBackToHr = $data['action'] === 'return'
            && $request->user()->role === 'md'
            && ($hrRequest->chain[0] ?? null) === 'hr'
            && $hrRequest->stage > 0;

        $approvals->act($hrRequest, $request->user(), $data['action'], (string) ($data['comment'] ?? ''));

        $message = match ($data['action']) {
            'forward' => 'Verified and forwarded to the MD.',
            'approve' => 'Approved. The change has been applied.',
            'return' => $sentBackToHr ? 'Sent back to HR for another check.' : 'Returned to the maker for correction.',
            'reject' => 'Rejected. The maker has been notified.',
        };

        return redirect()->route('approvals.index')->with('status', $hrRequest->ref.' '.$message);
    }

    public function resubmit(Request $request, HrRequest $hrRequest, ApprovalService $approvals): RedirectResponse
    {
        $user = $request->user();

        abort_unless($hrRequest->maker === $user->employee_id && $hrRequest->status === 'returned', 403);

        $data = $request->validate(['comment' => ['required', 'string', 'max:600']]);
        $approvals->resubmit($hrRequest, $user, $data['comment']);

        return redirect()->route('approvals.show', $hrRequest)->with('status', $hrRequest->ref.' was resubmitted.');
    }
}
