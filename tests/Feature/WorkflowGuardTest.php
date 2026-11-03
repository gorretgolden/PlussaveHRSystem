<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\HrNotification;
use App\Models\HrRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\HrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkflowGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HrSeeder::class);
    }

    private function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    private function submitLeave(): HrRequest
    {
        $this->actingAs($this->user('brenda'))->post('/leave', [
            'type' => 'Annual',
            'from' => '2027-05-03',
            'to' => '2027-05-07',
            'reason' => 'Family event',
        ])->assertRedirect('/leave');

        return HrRequest::where('maker', 'PSF-0142')->latest('id')->firstOrFail();
    }

    public function test_hr_cannot_approve_at_the_verification_stage(): void
    {
        $request = $this->submitLeave();
        $before = (float) Employee::find('PSF-0142')->annual_balance;

        $this->actingAs($this->user('ruth'))
            ->from("/approvals/{$request->id}")
            ->post("/approvals/{$request->id}/act", ['action' => 'approve'])
            ->assertRedirect("/approvals/{$request->id}")
            ->assertSessionHasErrors('action');

        $request->refresh();
        $this->assertSame('pending', $request->status);
        $this->assertSame(0, $request->stage);
        $this->assertSame($before, (float) Employee::find('PSF-0142')->annual_balance);
        $this->assertDatabaseMissing('leave_requests', ['request_id' => $request->id]);
    }

    public function test_a_final_stage_request_cannot_be_forwarded_past_the_chain(): void
    {
        $request = $this->submitLeave();

        $this->actingAs($this->user('ruth'))
            ->post("/approvals/{$request->id}/act", ['action' => 'forward'])
            ->assertRedirect('/approvals');

        $this->actingAs($this->user('daniel'))
            ->from("/approvals/{$request->id}")
            ->post("/approvals/{$request->id}/act", ['action' => 'forward'])
            ->assertRedirect("/approvals/{$request->id}")
            ->assertSessionHasErrors('action');

        $request->refresh();
        $this->assertSame('pending', $request->status);
        $this->assertSame(1, $request->stage);
        $this->assertSame('md', $request->chain[$request->stage]);
    }

    public function test_a_role_must_hold_the_privilege_for_its_stage(): void
    {
        $request = $this->submitLeave();

        $permissions = Role::findByName('hr', 'web')->permissions->pluck('name')->all();
        PermissionCatalog::syncRole('hr', $permissions, ['Initiate']);

        $this->actingAs($this->user('ruth'))
            ->from("/approvals/{$request->id}")
            ->post("/approvals/{$request->id}/act", ['action' => 'forward'])
            ->assertRedirect("/approvals/{$request->id}")
            ->assertSessionHasErrors('action');

        $this->assertSame(0, $request->fresh()->stage);
        $this->assertStringContainsString('Verify', session('errors')->first('action'));
    }

    public function test_md_cannot_change_role_permissions_without_the_approval_step(): void
    {
        $before = $this->user('brenda')->getAllPermissions()->pluck('name')->sort()->values()->all();

        $this->actingAs($this->user('daniel'))
            ->put('/admin/roles/staff', [
                'permissions' => ['add-employee'],
                'privileges' => ['Initiate'],
            ])
            ->assertForbidden();

        $this->assertSame($before, $this->user('brenda')->fresh()->getAllPermissions()->pluck('name')->sort()->values()->all());
        $this->assertSame(0, HrRequest::where('kind', 'Role permission change')->count());
    }

    public function test_md_return_sends_the_request_back_to_hr_and_tells_the_maker(): void
    {
        $request = $this->submitLeave();

        $this->actingAs($this->user('ruth'))
            ->post("/approvals/{$request->id}/act", ['action' => 'forward']);

        $this->actingAs($this->user('daniel'))
            ->post("/approvals/{$request->id}/act", [
                'action' => 'return',
                'comment' => 'Check the balance again.',
            ])
            ->assertRedirect('/approvals')
            ->assertSessionHas('status');

        $request->refresh();
        $this->assertSame('pending', $request->status);
        $this->assertSame(0, $request->stage);
        $this->assertTrue(HrNotification::where('employee_id', 'PSF-0142')
            ->where('text', 'like', '%sent back to HR%')->exists());
        $this->assertTrue(HrNotification::where('employee_id', 'PSF-0007')
            ->where('text', 'like', '%returned to you by MD%')->exists());
    }

    public function test_a_leave_request_cannot_span_more_than_a_year(): void
    {
        Employee::whereKey('PSF-0142')->update(['annual_balance' => 3000]);
        $before = HrRequest::where('maker', 'PSF-0142')->count();

        $this->actingAs($this->user('brenda'))
            ->from('/leave/apply')
            ->post('/leave', [
                'type' => 'Annual',
                'from' => '2027-01-04',
                'to' => '2030-01-04',
            ])
            ->assertRedirect('/leave/apply')
            ->assertSessionHasErrors('to');

        $this->assertSame($before, HrRequest::where('maker', 'PSF-0142')->count());
    }

    public function test_malformed_filter_queries_are_ignored(): void
    {
        $this->actingAs($this->user('ivan'))->get('/admin/audit?role[]=hr')->assertOk();
        $this->actingAs($this->user('ivan'))->get('/admin/roles?role[]=hr')->assertOk();
        $this->actingAs($this->user('ruth'))->get('/employees?q[]=x&department[]=1&branch[]=2&type[]=x&status[]=y')->assertOk();
        $this->actingAs($this->user('ruth'))->get('/approvals?tab[]=x')->assertOk();
    }

    public function test_staff_request_page_does_not_link_to_a_forbidden_employee_file(): void
    {
        $request = $this->submitLeave();

        $this->actingAs($this->user('brenda'))
            ->get("/approvals/{$request->id}")
            ->assertOk()
            ->assertDontSee('href="'.route('employees.show', 'PSF-0142').'"', false);

        $this->actingAs($this->user('ruth'))
            ->get("/approvals/{$request->id}")
            ->assertOk()
            ->assertSee('href="'.route('employees.show', 'PSF-0142').'"', false);
    }

    public function test_on_leave_status_follows_the_approved_leave_calendar(): void
    {
        $leave = LeaveRequest::create([
            'ref' => 'LV-900',
            'employee_id' => 'PSF-0142',
            'type_code' => 'Annual',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'days' => 1,
            'status' => 'Approved',
        ]);

        $this->artisan('hr:sync-leave-status')->assertSuccessful();
        $this->assertSame('On Leave', Employee::find('PSF-0142')->status);

        $leave->update(['start_date' => now()->subDays(3)->toDateString(), 'end_date' => now()->subDay()->toDateString()]);
        $this->artisan('hr:sync-leave-status')->assertSuccessful();
        $this->assertSame('Active', Employee::find('PSF-0142')->status);
    }

    public function test_auditor_can_read_a_file_but_not_open_requests_it_cannot_see(): void
    {
        $auditor = User::create([
            'name' => 'Allan Auditor',
            'username' => 'allan',
            'email' => 'allan@example.test',
            'role' => 'auditor',
            'password' => Hash::make('PlusSave#2026'),
            'active' => true,
            'must_change_password' => false,
        ]);
        $auditor->assignRole('auditor');

        $request = HrRequest::where('target_employee', 'PSF-0142')->latest('created_at')->firstOrFail();
        $link = 'href="'.route('approvals.show', $request).'"';

        $this->actingAs($auditor)->get("/approvals/{$request->id}")->assertForbidden();
        $this->actingAs($auditor)->get('/employees/PSF-0142')->assertOk()->assertDontSee($link, false);
        $this->actingAs($this->user('daniel'))->get('/employees/PSF-0142')->assertOk()->assertSee($link, false);
    }

    public function test_approval_trail_keeps_its_order_when_actions_share_a_second(): void
    {
        $request = $this->submitLeave();

        $this->actingAs($this->user('ruth'))->post("/approvals/{$request->id}/act", ['action' => 'forward']);

        $actions = $request->fresh()->actions;
        $this->assertSame('Submitted', $actions->first()->action);
        $this->assertStringContainsString('forwarded', strtolower($actions->last()->action));
    }

    public function test_a_reference_already_taken_by_another_maker_is_not_reused(): void
    {
        $attrs = [
            'kind' => 'Leave application',
            'subject' => 'Annual leave, 03 May to 07 May (5 days)',
            'maker' => 'PSF-0142',
            'maker_role' => 'staff',
        ];

        $stolen = HrRequest::nextRef();
        $injected = false;

        // Another maker commits this exact reference between the read and the insert.
        HrRequest::creating(function () use (&$injected, $attrs, $stolen) {
            if ($injected) {
                return;
            }
            $injected = true;

            HrRequest::query()->insert($attrs + [
                'ref' => $stolen,
                'chain' => json_encode(['hr', 'md']),
                'status' => 'pending',
            ]);
        });

        $request = HrRequest::createWithRef($attrs + ['chain' => ['hr', 'md']]);

        $this->assertTrue($injected);
        $this->assertNotSame($stolen, $request->ref);
        $this->assertSame(
            [$stolen, $request->ref],
            HrRequest::orderBy('id')->pluck('ref')->slice(-2)->values()->all(),
            'The retry should have taken the next free reference.'
        );
    }
}
