<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HrSeeder extends Seeder
{
    private array $d;

    private array $branchIds = [];

    private array $deptIds = [];

    public function run(): void
    {
        $this->d = json_decode(file_get_contents(database_path('data/seed.json')), true);

        Schema::disableForeignKeyConstraints();
        foreach ([
            'library_acks', 'library_documents', 'exit_clearance', 'exits', 'case_people', 'cases',
            'vacancies', 'training_attendance', 'trainings', 'appraisals', 'notifications', 'audit_log',
            'settings', 'payroll_lines', 'payroll_inputs', 'payroll_periods', 'request_actions', 'requests',
            'leave_requests', 'attendance', 'employee_documents', 'users', 'employees', 'holidays',
            'leave_types', 'grades', 'departments', 'branches', 'workflows', 'integrations',
        ] as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        $this->reference();
        $this->employees();
        $this->permissions();
        $this->users();
        $this->attendance();
        $this->leave();
        $this->requests();
        $this->talent();
        $this->relations();
        $this->library();
        $this->payroll();
        $this->admin();

        $this->command?->info('Seeded '.count($this->d['EMP']).' employees and the full HR dataset.');
    }

    private function reference(): void
    {
        foreach ($this->d['BRANCHES'] as $name) {
            $this->branchIds[$name] = DB::table('branches')->insertGetId([
                'name' => $name, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ($this->d['DEPTS'] as $name) {
            $this->deptIds[$name] = DB::table('departments')->insertGetId([
                'name' => $name, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ($this->d['GRADES'] as $code => $g) {
            DB::table('grades')->insert([
                'code' => $code, 'name' => $g['name'], 'transport' => $g['tr'],
                'housing' => $g['hs'], 'airtime' => $g['at'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ($this->d['LEAVE_TYPES'] as $code => $t) {
            DB::table('leave_types')->insert([
                'code' => $code, 'name' => $code, 'days' => $t['days'], 'note' => $t['note'],
                'gender' => $t['g'] ?? null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ($this->d['HOLIDAYS'] as $day) {
            DB::table('holidays')->insert(['day' => $day]);
        }
    }

    private function employees(): void
    {
        $standardDocs = ['CV', 'Academic documents', 'Appointment letter', 'Contract', 'NDA',
            'Job description', 'National ID copy', 'Appraisals', 'Disciplinary letters', 'Exit documents'];

        foreach ($this->d['EMP'] as $e) {
            DB::table('employees')->insert([
                'id' => $e['id'],
                'first_name' => $e['fn'],
                'last_name' => $e['ln'],
                'gender' => $e['g'],
                'dob' => $e['dob'],
                'title' => $e['title'],
                'dept_id' => $this->deptIds[$e['dept']],
                'branch_id' => $this->branchIds[$e['branch']],
                'supervisor_id' => null,
                'employment_type' => $e['type'],
                'joined' => $e['joined'],
                'grade_code' => $e['grade'],
                'basic' => $e['basic'],
                'status' => $e['status'],
                'nin' => $e['nin'],
                'phone' => $e['phone'],
                'email' => $e['email'],
                'address' => $e['addr'],
                'nok_name' => $e['nok']['n'],
                'nok_relation' => $e['nok']['rel'],
                'nok_phone' => $e['nok']['p'],
                'contract_end' => $e['cEnd'],
                'probation_start' => $e['pStart'],
                'probation_end' => $e['pEnd'],
                'appraisal_due' => $e['appr'],
                'annual_balance' => $e['bal']['Annual'],
                'sick_balance' => $e['bal']['Sick'],
                'other_balance' => $e['bal']['Other'],
                'loan_balance' => $e['loan'],
                'savings' => $e['sav'],
                'photo' => $e['photo'],
                'bank_account' => $e['acct'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($standardDocs as $doc) {
                DB::table('employee_documents')->insert([
                    'employee_id' => $e['id'],
                    'doc_type' => $doc,
                    'present' => ! empty($e['docs'][$doc]),
                ]);
            }
        }

        foreach ($this->d['EMP'] as $e) {
            if ($e['sup']) {
                DB::table('employees')->where('id', $e['id'])->update(['supervisor_id' => $e['sup']]);
            }
        }
    }

    private function users(): void
    {
        $accounts = [
            ['username' => 'brenda', 'name' => 'Brenda Nakato', 'employee' => 'PSF-0142', 'role' => 'staff'],
            ['username' => 'ruth', 'name' => 'Ruth Achieng', 'employee' => 'PSF-0007', 'role' => 'hr'],
            ['username' => 'daniel', 'name' => 'Daniel Mugisha', 'employee' => 'PSF-0001', 'role' => 'md'],
            ['username' => 'ivan', 'name' => 'Ivan Ssekandi', 'employee' => 'PSF-0034', 'role' => 'admin'],
        ];

        foreach ($accounts as $a) {
            $user = User::create([
                'name' => $a['name'],
                'username' => $a['username'],
                'email' => strtolower(str_replace(' ', '.', $a['name'])).'@plussavefinancialsolutions.com',
                'employee_id' => $a['employee'],
                'role' => $a['role'],
                'password' => Hash::make('PlusSave#2026'),
                'active' => true,
                'must_change_password' => false,
            ]);
            $user->assignRole($a['role']);
        }
    }

    private function permissions(): void
    {
        PermissionCatalog::seedDefaults();

        Permission::whereNotIn('name', PermissionCatalog::names())->delete();
        Role::whereNotIn('name', array_keys(PermissionCatalog::ROLES))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function attendance(): void
    {
        $today = now()->toDateString();
        foreach ($this->d['ATT'] as $emp => $row) {
            DB::table('attendance')->insert([
                'employee_id' => $emp,
                'work_date' => $today,
                'clock_in' => $row[0],
                'clock_out' => $row[1],
                'source' => $row[2],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function leave(): void
    {
        foreach ($this->d['LEAVES'] as $l) {
            DB::table('leave_requests')->insert([
                'ref' => $l['id'],
                'employee_id' => $l['emp'],
                'type_code' => $l['type'],
                'start_date' => $l['from'],
                'end_date' => $l['to'],
                'days' => $l['days'],
                'status' => $l['status'],
                'decided_at' => now()->subDays(5),
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(5),
            ]);
        }
    }

    private function requests(): void
    {
        $rows = [
            [
                'ref' => 'R-1031', 'kind' => 'Leave application', 'subject' => 'Annual leave, 3 to 7 Nov (5 days)',
                'target' => 'PSF-0056', 'maker' => 'PSF-0056', 'maker_role' => 'staff', 'chain' => ['hr', 'md'],
                'stage' => 0, 'status' => 'pending', 'at' => '2026-10-28 06:14:00',
                'fields' => [['Leave type', 'Annual'], ['Dates', '03 Nov 2026 to 07 Nov 2026'], ['Working days', '5'],
                    ['Balance before', '15 days'], ['Reliever', 'Patience Akello'], ['Attachment', 'None needed']],
                'payload' => ['emp' => 'PSF-0056', 'type' => 'Annual', 'from' => '2026-11-03', 'to' => '2026-11-07', 'days' => 5],
                'actions' => [['PSF-0056', 'staff', 'Submitted', 'Family function in Lira.', '2026-10-28 06:14:00']],
            ],
            [
                'ref' => 'R-1032', 'kind' => 'Personal details change', 'subject' => 'New phone number',
                'target' => 'PSF-0117', 'maker' => 'PSF-0117', 'maker_role' => 'staff', 'chain' => ['hr', 'md'],
                'stage' => 0, 'status' => 'pending', 'at' => '2026-10-27 11:40:00',
                'fields' => [['Field', 'Phone'], ['Current value', '0772 137 274'], ['New value', '0759 214 880'],
                    ['Supporting document', 'Telecom SIM replacement form.pdf']],
                'payload' => ['emp' => 'PSF-0117', 'field' => 'phone', 'value' => '0759 214 880'],
                'actions' => [['PSF-0117', 'staff', 'Submitted', 'Lost my old line.', '2026-10-27 11:40:00']],
            ],
            [
                'ref' => 'R-1033', 'kind' => 'Leave application', 'subject' => 'Compassionate leave, 30 to 31 Oct (2 days)',
                'target' => 'PSF-0142', 'maker' => 'PSF-0142', 'maker_role' => 'staff', 'chain' => ['hr', 'md'],
                'stage' => 0, 'status' => 'returned', 'at' => '2026-10-26 07:05:00',
                'fields' => [['Leave type', 'Other (compassionate)'], ['Dates', '30 Oct 2026 to 31 Oct 2026'],
                    ['Working days', '2'], ['Reliever', 'Juliet Nakimuli'], ['Attachment', 'None']],
                'payload' => ['emp' => 'PSF-0142', 'type' => 'Other', 'from' => '2026-10-30', 'to' => '2026-10-31', 'days' => 2],
                'actions' => [
                    ['PSF-0142', 'staff', 'Submitted', 'Burial of my uncle in Masaka.', '2026-10-26 07:05:00'],
                    ['PSF-0007', 'hr', 'Returned to maker by HR', 'Please attach the burial notice or a letter from the LC1 chairperson, then resubmit.', '2026-10-26 09:30:00'],
                ],
            ],
            [
                'ref' => 'R-1034', 'kind' => 'Transfer, promotion or salary change',
                'subject' => 'Brian Tumusiime: Teller to Senior Teller, Old Taxi Park',
                'target' => 'PSF-0102', 'maker' => 'PSF-0007', 'maker_role' => 'hr', 'chain' => ['md'],
                'stage' => 0, 'status' => 'pending', 'at' => '2026-10-27 08:00:00',
                'fields' => [['Change type', 'Promotion and transfer'], ['Previous position', 'Teller, Kikuubo, Grade G5'],
                    ['New position', 'Senior Teller, Old Taxi Park, Grade G4'], ['Previous salary', 'UGX 1,100,000'],
                    ['New salary', 'UGX 1,450,000'], ['Effective date', '01 Nov 2026'], ['Reason', 'Replacement for resigned teller']],
                'payload' => ['emp' => 'PSF-0102', 'kind' => 'Promotion and transfer', 'title' => 'Senior Teller',
                    'branch' => 'Old Taxi Park', 'dept' => 'Operations', 'salary' => 1450000, 'grade' => 'G4',
                    'eff' => '2026-11-01', 'reason' => 'Replacement for resigned teller; strong H1 targets'],
                'actions' => [['PSF-0007', 'hr', 'Submitted', 'Fills the Senior Teller gap at Old Taxi Park.', '2026-10-27 08:00:00']],
            ],
            [
                'ref' => 'R-1035', 'kind' => 'Contract renewal', 'subject' => 'Ivan Ssekandi: renew 2 years',
                'target' => 'PSF-0034', 'maker' => 'PSF-0007', 'maker_role' => 'hr', 'chain' => ['md'],
                'stage' => 0, 'status' => 'pending', 'at' => '2026-10-28 13:20:00',
                'fields' => [['Employee', 'Ivan Ssekandi, ICT Officer'], ['Current contract ends', '15 Dec 2026'],
                    ['Proposed new end date', '15 Dec 2028'], ['Salary', 'No change'], ['Last appraisal', '4.1 / 5']],
                'payload' => ['emp' => 'PSF-0034', 'oldEnd' => '2026-12-15', 'newEnd' => '2028-12-15',
                    'reason' => 'Renewed for core banking integration'],
                'actions' => [['PSF-0007', 'hr', 'Submitted', 'Core banking integration work continues into 2027.', '2026-10-28 13:20:00']],
            ],
            [
                'ref' => 'R-1036', 'kind' => 'Leave application', 'subject' => 'Annual leave, 23 to 27 Nov (5 days)',
                'target' => 'PSF-0007', 'maker' => 'PSF-0007', 'maker_role' => 'hr', 'chain' => ['md'],
                'stage' => 0, 'status' => 'pending', 'at' => '2026-10-28 15:02:00',
                'fields' => [['Leave type', 'Annual'], ['Dates', '23 Nov 2026 to 27 Nov 2026'], ['Working days', '5'],
                    ['Routing', 'HR is the maker, so it goes straight to the MD']],
                'payload' => ['emp' => 'PSF-0007', 'type' => 'Annual', 'from' => '2026-11-23', 'to' => '2026-11-27', 'days' => 5],
                'actions' => [['PSF-0007', 'hr', 'Submitted', 'Requests will be routed to the MD while I am away.', '2026-10-28 15:02:00']],
            ],
            [
                'ref' => 'R-1037', 'kind' => 'Leave application', 'subject' => 'Sick leave, 12 to 13 Oct (2 days)',
                'target' => 'PSF-0048', 'maker' => 'PSF-0048', 'maker_role' => 'staff', 'chain' => ['hr', 'md'],
                'stage' => 1, 'status' => 'approved', 'at' => '2026-10-12 05:50:00',
                'fields' => [['Leave type', 'Sick'], ['Dates', '12 Oct 2026 to 13 Oct 2026'], ['Working days', '2'],
                    ['Attachment', 'Medical form.pdf']],
                'payload' => ['emp' => 'PSF-0048', 'type' => 'Sick', 'from' => '2026-10-12', 'to' => '2026-10-13', 'days' => 2],
                'actions' => [
                    ['PSF-0048', 'staff', 'Submitted', 'Malaria. Medical form attached.', '2026-10-12 05:50:00'],
                    ['PSF-0007', 'hr', 'Verified by HR, forwarded to MD', 'Form checked.', '2026-10-12 07:10:00'],
                    ['PSF-0001', 'md', 'Approved by MD', 'Get well soon.', '2026-10-12 09:45:00'],
                ],
            ],
            [
                'ref' => 'R-1038', 'kind' => 'Training request', 'subject' => 'Defensive driving course',
                'target' => 'PSF-0153', 'maker' => 'PSF-0153', 'maker_role' => 'staff', 'chain' => ['hr', 'md'],
                'stage' => 1, 'status' => 'rejected', 'at' => '2026-10-05 08:00:00',
                'fields' => [['Course', 'Defensive driving'], ['Provider', 'External driving school'], ['Cost', 'UGX 650,000']],
                'payload' => ['emp' => 'PSF-0153', 'title' => 'Defensive driving', 'provider' => 'Driving school', 'date' => '2026-11-15'],
                'actions' => [
                    ['PSF-0153', 'staff', 'Submitted', 'To support deliveries to branches.', '2026-10-05 08:00:00'],
                    ['PSF-0007', 'hr', 'Verified by HR, forwarded to MD', 'Useful, but not budgeted.', '2026-10-06 07:00:00'],
                    ['PSF-0001', 'md', 'Rejected by MD', 'Not in the 2026 budget. Re-apply in Q1 2027.', '2026-10-07 12:00:00'],
                ],
            ],
        ];

        foreach ($rows as $r) {
            $id = DB::table('requests')->insertGetId([
                'ref' => $r['ref'],
                'kind' => $r['kind'],
                'subject' => $r['subject'],
                'target_employee' => $r['target'],
                'maker' => $r['maker'],
                'maker_role' => $r['maker_role'],
                'fields' => json_encode($r['fields']),
                'payload' => json_encode($r['payload']),
                'chain' => json_encode($r['chain']),
                'stage' => $r['stage'],
                'status' => $r['status'],
                'created_at' => $r['at'],
                'updated_at' => $r['at'],
            ]);

            foreach ($r['actions'] as $a) {
                DB::table('request_actions')->insert([
                    'request_id' => $id, 'actor' => $a[0], 'actor_role' => $a[1],
                    'action' => $a[2], 'comment' => $a[3], 'acted_at' => $a[4],
                ]);
            }
        }

        DB::table('notifications')->insert([
            ['employee_id' => 'PSF-0007', 'text' => 'R-1031 Leave application: Annual leave, 3 to 7 Nov is waiting for your review', 'is_read' => 0, 'created_at' => '2026-10-28 06:14:00'],
            ['employee_id' => 'PSF-0007', 'text' => 'R-1032 Personal details change: New phone number is waiting for your review', 'is_read' => 0, 'created_at' => '2026-10-27 11:40:00'],
            ['employee_id' => 'PSF-0001', 'text' => 'R-1034 Transfer, promotion or salary change is waiting for your approval', 'is_read' => 0, 'created_at' => '2026-10-27 08:00:00'],
            ['employee_id' => 'PSF-0142', 'text' => 'Your October 2026 payslip is ready.', 'is_read' => 0, 'created_at' => '2026-10-28 09:00:00'],
        ]);

        DB::table('audit_log')->insert([
            ['actor' => 'PSF-0007', 'actor_role' => 'hr', 'action' => 'Submitted', 'ref' => 'R-1036', 'detail' => 'Leave application: own annual leave', 'created_at' => '2026-10-28 15:02:00'],
            ['actor' => 'PSF-0007', 'actor_role' => 'hr', 'action' => 'Submitted', 'ref' => 'R-1035', 'detail' => 'Contract renewal: Ivan Ssekandi', 'created_at' => '2026-10-28 13:20:00'],
            ['actor' => 'PSF-0056', 'actor_role' => 'staff', 'action' => 'Submitted', 'ref' => 'R-1031', 'detail' => 'Leave application: annual leave', 'created_at' => '2026-10-28 06:14:00'],
            ['actor' => 'PSF-0001', 'actor_role' => 'md', 'action' => 'Rejected', 'ref' => 'R-1038', 'detail' => 'Training request: Defensive driving course', 'created_at' => '2026-10-07 12:00:00'],
        ]);
    }

    private function talent(): void
    {
        foreach ($this->d['APPRAISALS'] as $a) {
            DB::table('appraisals')->insert([
                'employee_id' => $a['emp'], 'cycle' => $a['cycle'], 'due_date' => $a['due'],
                'status' => $a['status'], 'score' => $a['score'], 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        foreach ($this->d['TRAININGS'] as $t) {
            DB::table('trainings')->insert([
                'id' => $t['id'], 'title' => $t['title'], 'provider' => $t['provider'],
                'training_date' => $t['date'], 'mandatory' => $t['mandatory'], 'status' => $t['status'],
                'audience' => $t['audience'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($t['done'] as $emp) {
                DB::table('training_attendance')->insert([
                    'training_id' => $t['id'], 'employee_id' => $emp, 'attended' => 1,
                ]);
            }
        }

        foreach ($this->d['VACANCIES'] as $v) {
            DB::table('vacancies')->insert([
                'id' => $v['id'], 'title' => $v['title'], 'dept_id' => $this->deptIds[$v['dept']],
                'branch_id' => $this->branchIds[$v['branch']], 'opened' => $v['opened'],
                'closes' => $v['closes'], 'applicants' => $v['applicants'], 'stage' => $v['stage'],
                'reason' => $v['reason'], 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function relations(): void
    {
        foreach ($this->d['CASES'] as $c) {
            DB::table('cases')->insert([
                'id' => $c['id'], 'kind' => $c['kind'], 'employee_id' => $c['emp'],
                'category' => $c['category'], 'incident_date' => $c['incident'],
                'reported_by' => $c['reportedBy'], 'description' => $c['desc'], 'stage' => $c['stage'],
                'hearing_date' => $c['hearing']['date'] ?? null,
                'panel' => json_encode($c['hearing']['panel'] ?? []),
                'minutes' => $c['hearing']['minutes'] ?? null,
                'decision' => $c['decision'], 'due_date' => $c['due'],
                'confidential' => $c['confidential'] ?? false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($c['involved'] as $emp) {
                DB::table('case_people')->insert(['case_id' => $c['id'], 'employee_id' => $emp, 'role' => 'involved']);
            }
            foreach ($c['witnesses'] as $emp) {
                DB::table('case_people')->insert(['case_id' => $c['id'], 'employee_id' => $emp, 'role' => 'witness']);
            }
        }

        foreach ($this->d['EXITS'] as $x) {
            DB::table('exits')->insert([
                'id' => $x['id'], 'employee_id' => $x['emp'], 'type' => $x['type'],
                'notice_date' => $x['notice'], 'last_working_day' => $x['lwd'], 'reason' => $x['reason'],
                'interview_notes' => $x['interview'], 'status' => $x['status'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($x['clear'] as $item => $done) {
                DB::table('exit_clearance')->insert([
                    'exit_id' => $x['id'], 'item' => $item, 'done' => $done,
                ]);
            }
        }
    }

    private function library(): void
    {
        foreach ($this->d['LIBRARY'] as $doc) {
            DB::table('library_documents')->insert([
                'id' => $doc['id'], 'title' => $doc['title'], 'version' => $doc['ver'],
                'category' => $doc['cat'], 'owner' => $doc['owner'], 'approved_date' => $doc['approved'],
                'acknowledgements' => $doc['ack'] ?? 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function payroll(): void
    {
        foreach ($this->d['PAYROLL_HIST'] as $p) {
            DB::table('payroll_periods')->insert([
                'period' => $p['period'], 'status' => $p['status'], 'gross' => $p['gross'], 'net' => $p['net'],
                'posted_at' => $p['period'].'-28 17:00:00', 'created_at' => $p['period'].'-01 09:00:00',
                'updated_at' => $p['period'].'-28 17:00:00',
            ]);
        }

        $periodId = DB::table('payroll_periods')->insertGetId([
            'period' => $this->d['PAY']['period'], 'status' => $this->d['PAY']['status'],
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($this->d['PAY']['inputs'] as $i) {
            DB::table('payroll_inputs')->insert([
                'period_id' => $periodId, 'employee_id' => $i['emp'], 'kind' => $i['kind'],
                'amount' => $i['amt'], 'note' => $i['note'], 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        DB::table('settings')->insert([
            'k' => 'payroll',
            'v' => json_encode($this->d['CONFIG']),
        ]);
    }

    private function admin(): void
    {
        foreach ($this->d['WORKFLOWS'] as $w) {
            DB::table('workflows')->insert([
                'title' => $w[0], 'maker_role' => $w[1], 'verify_step' => $w[2], 'approve_role' => $w[3],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        foreach ($this->d['SYSTEMS'] as $s) {
            DB::table('integrations')->insert([
                'name' => $s['name'], 'state' => $s['state'], 'flows' => json_encode($s['flows']),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
