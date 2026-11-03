<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\HrRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\HrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HrSeeder::class);
    }

    public function test_seeded_users_can_sign_in_and_wrong_password_is_rejected(): void
    {
        $this->post('/login', ['username' => 'ruth', 'password' => 'PlusSave#2026'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::where('username', 'ruth')->first());

        auth()->logout();

        $this->post('/login', ['username' => 'ruth', 'password' => 'wrong'])
            ->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_staff_cannot_open_the_employee_register(): void
    {
        $this->actingAs(User::where('username', 'brenda')->first())
            ->get('/employees')
            ->assertForbidden();
    }

    public function test_staff_cannot_open_another_persons_request(): void
    {
        $this->actingAs(User::where('username', 'brenda')->first())
            ->get('/approvals/1')
            ->assertForbidden();
    }

    public function test_leave_moves_from_staff_to_hr_to_md_and_reduces_the_balance_once(): void
    {
        $brenda = User::where('username', 'brenda')->first();
        $before = Employee::find('PSF-0142')->annual_balance;

        $this->actingAs($brenda)
            ->post('/leave', [
                'type' => 'Annual',
                'from' => '2027-04-05',
                'to' => '2027-04-09',
                'reason' => 'Family event',
            ])
            ->assertRedirect('/leave');

        $request = HrRequest::where('maker', 'PSF-0142')->latest('id')->first();
        $this->assertSame('Leave application', $request->kind);
        $this->assertSame('pending', $request->status);
        $this->assertSame(['hr', 'md'], $request->chain);
        $this->assertSame(5.0, (float) $request->payload['days']);
        $this->assertSame($before, Employee::find('PSF-0142')->annual_balance, 'Balance must not move before approval');

        $this->actingAs(User::where('username', 'ruth')->first())
            ->post("/approvals/{$request->id}/act", ['action' => 'forward'])
            ->assertRedirect('/approvals');
        $this->assertSame(1, $request->fresh()->stage);

        $this->actingAs(User::where('username', 'daniel')->first())
            ->post("/approvals/{$request->id}/act", ['action' => 'approve'])
            ->assertRedirect('/approvals');

        $request->refresh();
        $this->assertSame('approved', $request->status);
        $this->assertEquals($before - 5, Employee::find('PSF-0142')->annual_balance);
        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => 'PSF-0142',
            'status' => 'Approved',
            'request_id' => $request->id,
        ]);
    }

    public function test_a_leave_request_above_the_remaining_balance_is_refused(): void
    {
        $this->actingAs(User::where('username', 'brenda')->first())
            ->from('/leave/apply')
            ->post('/leave', [
                'type' => 'Annual',
                'from' => '2027-01-04',
                'to' => '2027-01-29',
            ])
            ->assertRedirect('/leave/apply')
            ->assertSessionHasErrors('to');

        $this->assertSame(0, HrRequest::where('maker', 'PSF-0142')->where('status', 'pending')->count());
    }

    public function test_a_leave_request_cannot_start_in_the_past(): void
    {
        $this->actingAs(User::where('username', 'brenda')->first())
            ->from('/leave/apply')
            ->post('/leave', [
                'type' => 'Annual',
                'from' => '2026-10-01',
                'to' => '2026-10-02',
            ])
            ->assertRedirect('/leave/apply')
            ->assertSessionHasErrors(['from' => 'The first day cannot be in the past.']);
    }

    public function test_a_leave_request_cannot_overlap_leave_already_approved(): void
    {
        LeaveRequest::create([
            'ref' => 'LV-399',
            'employee_id' => 'PSF-0142',
            'type_code' => 'Annual',
            'start_date' => '2027-04-05',
            'end_date' => '2027-04-09',
            'days' => 5,
            'status' => 'Approved',
        ]);

        $before = HrRequest::where('maker', 'PSF-0142')->count();

        $this->actingAs(User::where('username', 'brenda')->first())
            ->from('/leave/apply')
            ->post('/leave', [
                'type' => 'Annual',
                'from' => '2027-04-07',
                'to' => '2027-04-08',
            ])
            ->assertRedirect('/leave/apply')
            ->assertSessionHasErrors('from');

        $this->assertSame($before, HrRequest::where('maker', 'PSF-0142')->count());
    }

    public function test_approval_stops_when_the_balance_no_longer_covers_the_request(): void
    {
        $brenda = User::where('username', 'brenda')->first();

        $this->actingAs($brenda)->post('/leave', [
            'type' => 'Annual',
            'from' => '2027-04-05',
            'to' => '2027-04-09',
        ]);

        $request = HrRequest::where('maker', 'PSF-0142')->latest('id')->firstOrFail();

        $this->actingAs(User::where('username', 'ruth')->first())
            ->post("/approvals/{$request->id}/act", ['action' => 'forward']);

        Employee::whereKey('PSF-0142')->update(['annual_balance' => 2]);

        $this->actingAs(User::where('username', 'daniel')->first())
            ->from("/approvals/{$request->id}")
            ->post("/approvals/{$request->id}/act", ['action' => 'approve'])
            ->assertRedirect("/approvals/{$request->id}")
            ->assertSessionHasErrors('action');

        $this->assertSame('pending', $request->fresh()->status);
        $this->assertSame(2.0, (float) Employee::find('PSF-0142')->annual_balance);
        $this->assertDatabaseMissing('leave_requests', ['request_id' => $request->id]);
    }

    public function test_a_request_can_only_be_acted_on_by_the_role_at_the_current_stage(): void
    {
        $this->actingAs(User::where('username', 'daniel')->first())
            ->post('/approvals/1/act', ['action' => 'approve'])
            ->assertSessionHasErrors('action');

        $this->assertSame('pending', HrRequest::find(1)->status);
    }
}
