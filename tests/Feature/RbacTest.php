<?php

namespace Tests\Feature;

use App\Models\HrNotification;
use App\Models\HrRequest;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\HrSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacTest extends TestCase
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

    public function test_seeded_users_hold_their_spatie_role(): void
    {
        $this->assertTrue($this->user('brenda')->hasRole('staff'));
        $this->assertTrue($this->user('ruth')->hasRole('hr'));
        $this->assertTrue($this->user('daniel')->hasRole('md'));
        $this->assertTrue($this->user('ivan')->hasRole('admin'));
        $this->assertTrue($this->user('ruth')->can('leave-approval'));
        $this->assertFalse($this->user('brenda')->can('leave-approval'));
    }

    public function test_only_hr_md_and_admin_reach_the_roles_screen(): void
    {
        $this->actingAs($this->user('ruth'))->get('/admin/roles')->assertOk()->assertSee('Roles and permissions');
        $this->actingAs($this->user('daniel'))->get('/admin/roles')->assertOk();
        $this->actingAs($this->user('ivan'))->get('/admin/roles')->assertOk();
        $this->actingAs($this->user('brenda'))->get('/admin/roles')->assertForbidden();
    }

    public function test_employee_register_is_gated_by_permission_not_job_title(): void
    {
        $finance = User::create([
            'name' => 'Fiona Finance',
            'username' => 'fiona',
            'email' => 'fiona@example.test',
            'role' => 'finance',
            'password' => Hash::make('PlusSave#2026'),
            'active' => true,
            'must_change_password' => false,
        ]);
        $finance->assignRole('finance');

        $this->actingAs($finance)->get('/employees')->assertForbidden();

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

        $this->actingAs($auditor)->get('/employees')->assertOk();
    }

    public function test_role_change_waits_for_md_approval_and_then_applies(): void
    {
        $ruth = $this->user('ruth');
        $permissions = ['apply-leave', 'attendance-correction', 'hr-library-view'];
        $privileges = ['Initiate'];

        $this->actingAs($ruth)
            ->put('/admin/roles/staff', ['permissions' => $permissions, 'privileges' => $privileges])
            ->assertRedirect('/admin/roles?role=staff');

        $request = HrRequest::where('kind', 'Role permission change')->latest('id')->firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame(['md'], $request->chain);
        $this->assertSame('staff', $request->payload['role']);
        $this->assertContains('payslips', $this->user('brenda')->fresh()->getAllPermissions()->pluck('name')->all());

        $this->actingAs($this->user('daniel'))
            ->post('/approvals/'.$request->id.'/act', ['action' => 'approve', 'comment' => 'Approved for the new intake.'])
            ->assertRedirect('/approvals');

        $this->assertSame($permissions, $this->user('brenda')->fresh()->getAllPermissions()->pluck('name')->sort()->values()->all());
        $this->assertSame($privileges, PermissionCatalog::privileges('staff'));
        $this->assertSame('approved', $request->fresh()->status);
    }

    public function test_a_role_cannot_initiate_and_approve_the_same_item(): void
    {
        $this->actingAs($this->user('ruth'))
            ->put('/admin/roles/hr', [
                'permissions' => ['add-employee', 'leave-approval'],
                'privileges' => ['Initiate', 'Approve'],
            ])
            ->assertSessionHasErrors('privileges');
    }

    public function test_hr_can_send_the_same_role_only_one_pending_change(): void
    {
        $ruth = $this->user('ruth');

        $payload = ['permissions' => ['add-employee', 'leave-approval'], 'privileges' => ['Verify']];
        $this->actingAs($ruth)->put('/admin/roles/hr', $payload)->assertSessionHasNoErrors();
        $this->actingAs($ruth)->put('/admin/roles/hr', [
            'permissions' => ['add-employee'], 'privileges' => ['Verify'],
        ])->assertSessionHasErrors('permissions');

        $this->assertSame(1, HrRequest::where('kind', 'Role permission change')->where('status', 'pending')->count());
    }

    public function test_account_locks_after_five_wrong_passwords(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'brenda', 'password' => 'wrong']);
        }

        $brenda = $this->user('brenda')->fresh();
        $this->assertTrue($brenda->isLocked());
        $this->assertNotNull($brenda->locked_until);

        $this->post('/login', ['username' => 'brenda', 'password' => 'PlusSave#2026'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_idle_session_is_signed_out_and_logged(): void
    {
        $brenda = $this->user('brenda');
        $brenda->forceFill(['last_activity_at' => now()->subMinutes(31)])->save();

        $this->actingAs($brenda)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_log', [
            'actor' => 'PSF-0142',
            'action' => 'Signed out',
            'detail' => 'Brenda Nakato signed out after 30 minutes idle',
        ]);
    }

    public function test_temporary_password_must_be_changed_before_using_the_system(): void
    {
        $brenda = $this->user('brenda');
        $brenda->forceFill(['must_change_password' => true])->save();

        $this->actingAs($brenda)->get('/dashboard')->assertRedirect('/password/change');
        $this->actingAs($brenda)->get('/password/change')->assertOk()->assertSee('Choose a new password before continuing.');
    }

    public function test_admin_can_read_the_audit_trail_but_staff_cannot(): void
    {
        $this->actingAs($this->user('ivan'))->get('/admin/audit')->assertOk()->assertSee('Audit trail');
        $this->actingAs($this->user('brenda'))->get('/admin/audit')->assertForbidden();
    }

    private function roleUser(string $username, string $role): User
    {
        $user = User::create([
            'name' => ucfirst($username).' '.ucfirst($role),
            'username' => $username,
            'email' => $username.'@example.test',
            'role' => $role,
            'password' => Hash::make('PlusSave#2026'),
            'active' => true,
            'must_change_password' => false,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_non_reviewers_only_see_the_requests_they_raised(): void
    {
        $finance = $this->roleUser('fiona', 'finance');
        $other = HrRequest::firstOrFail();

        $this->actingAs($finance)
            ->get('/approvals?tab=all')
            ->assertOk()
            ->assertSee('My requests')
            ->assertDontSee($other->ref)
            ->assertDontSee('All requests');
    }

    public function test_finance_lands_on_a_scoped_workspace_not_the_hr_dashboard(): void
    {
        $finance = $this->roleUser('fiona2', 'finance');

        $this->actingAs($finance)->get('/dashboard')
            ->assertOk()
            ->assertSee('Finance workspace')
            ->assertSee('Payroll periods')
            ->assertDontSee('Payroll and headcount summary')
            ->assertDontSee('Employee count')
            ->assertDontSee('Staff absent today');
    }

    public function test_auditor_lands_on_a_read_only_workspace_with_the_audit_trail(): void
    {
        $auditor = $this->roleUser('allan', 'auditor');

        $this->actingAs($auditor)->get('/dashboard')
            ->assertOk()
            ->assertSee('Auditor workspace')
            ->assertSee('Recent system activity')
            ->assertDontSee('Payroll and headcount summary');
    }

    public function test_mark_all_notifications_as_read_clears_the_unread_count(): void
    {
        $brenda = $this->user('brenda');
        $this->assertGreaterThan(0, HrNotification::where('employee_id', 'PSF-0142')->where('is_read', false)->count());

        $this->actingAs($brenda)
            ->post('/notifications/read')
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(0, HrNotification::where('employee_id', 'PSF-0142')->where('is_read', false)->count());
    }
}
