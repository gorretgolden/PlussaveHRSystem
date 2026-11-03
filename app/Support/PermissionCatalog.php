<?php

namespace App\Support;

use App\Models\Setting;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * The prototype's roles and permissions matrix. Labels and grouping are presentation;
 * the slug is the ability the application checks.
 */
final class PermissionCatalog
{
    public const MODULES = [
        'Employee records' => [
            'add-employee' => 'ADD EMPLOYEE',
            'edit-employee-file' => 'EDIT EMPLOYEE FILE',
            'view-employee-file' => 'VIEW EMPLOYEE FILE',
            'employee-positions' => 'EMPLOYEE POSITIONS',
            'upload-employee-documents' => 'UPLOAD EMPLOYEE DOCUMENTS',
            'deactivate-employee' => 'DEACTIVATE EMPLOYEE',
        ],
        'Payroll' => [
            'basic-salary-setup' => 'BASIC SALARY SETUP',
            'employee-grade-setup' => 'EMPLOYEE GRADE SETUP',
            'employee-grade-allowances' => 'EMPLOYEE GRADE ALLOWANCES',
            'employee-grade-deductions' => 'EMPLOYEE GRADE DEDUCTIONS',
            'deduction-setup' => 'DEDUCTION SETUP',
            'employee-salary' => 'EMPLOYEE SALARY',
            'adjust-salary-item' => 'ADJUST SALARY ITEM',
            'payroll-input' => 'PAYROLL INPUT',
            'run-payroll' => 'RUN PAYROLL',
            'post-payroll-to-core-banking' => 'POST PAYROLL TO CORE BANKING',
            'payslips' => 'PAYSLIPS',
        ],
        'Time and leave' => [
            'attendance' => 'ATTENDANCE',
            'attendance-correction' => 'ATTENDANCE CORRECTION',
            'leave-setup' => 'LEAVE SETUP',
            'apply-leave' => 'APPLY LEAVE',
            'leave-approval' => 'LEAVE APPROVAL',
        ],
        'Talent' => [
            'performance-appraisal' => 'PERFORMANCE APPRAISAL',
            'probation-and-contracts' => 'PROBATION AND CONTRACTS',
            'training-and-development' => 'TRAINING AND DEVELOPMENT',
            'recruitment' => 'RECRUITMENT',
            'transfers-and-promotions' => 'TRANSFERS AND PROMOTIONS',
        ],
        'Relations and exits' => [
            'disciplinary-cases' => 'DISCIPLINARY CASES',
            'grievances' => 'GRIEVANCES',
            'exit-management' => 'EXIT MANAGEMENT',
            'gratuity-calculation' => 'GRATUITY CALCULATION',
        ],
        'Library and reports' => [
            'hr-library-upload' => 'HR LIBRARY UPLOAD',
            'hr-library-view' => 'HR LIBRARY VIEW',
            'hr-reports' => 'HR REPORTS',
            'hr-dashboard' => 'HR DASHBOARD',
            'audit-trail' => 'AUDIT TRAIL',
        ],
        'Administration' => [
            'user-roles-and-permissions' => 'USER ROLES AND PERMISSIONS',
            'workflow-setup' => 'WORKFLOW SETUP',
            'integration-settings' => 'INTEGRATION SETTINGS',
        ],
    ];

    public const PRIVILEGES = [
        'Initiate', 'Verify', 'Approve', 'View only', 'Export reports', 'Delegate approvals',
    ];

    public const ROLES = [
        'staff' => 'Staff (self-service)',
        'hr' => 'HR Manager',
        'md' => 'Managing Director',
        'finance' => 'Finance (payroll posting)',
        'auditor' => 'Internal Auditor',
        'admin' => 'System Admin (IT)',
    ];

    private const DEFAULTS = [
        'staff' => [
            'apply-leave', 'hr-library-view', 'payslips', 'attendance-correction', 'grievances',
        ],
        'hr' => [
            'add-employee', 'edit-employee-file', 'view-employee-file', 'employee-positions',
            'upload-employee-documents', 'basic-salary-setup', 'employee-grade-setup',
            'employee-grade-allowances', 'employee-grade-deductions', 'deduction-setup',
            'employee-salary', 'adjust-salary-item', 'payroll-input', 'run-payroll', 'payslips',
            'attendance', 'attendance-correction', 'leave-setup', 'leave-approval',
            'performance-appraisal', 'probation-and-contracts', 'training-and-development',
            'recruitment', 'transfers-and-promotions', 'disciplinary-cases', 'grievances',
            'exit-management', 'gratuity-calculation', 'hr-library-upload', 'hr-library-view',
            'hr-reports', 'hr-dashboard',
        ],
        'md' => [
            'view-employee-file', 'leave-approval', 'run-payroll', 'post-payroll-to-core-banking',
            'transfers-and-promotions', 'disciplinary-cases', 'grievances', 'exit-management',
            'gratuity-calculation', 'hr-library-view', 'hr-reports', 'hr-dashboard', 'audit-trail',
        ],
        'finance' => [
            'payslips', 'hr-reports', 'post-payroll-to-core-banking',
        ],
        'auditor' => [
            'view-employee-file', 'hr-reports', 'audit-trail', 'hr-library-view',
        ],
        'admin' => [
            'user-roles-and-permissions', 'workflow-setup', 'integration-settings', 'audit-trail',
        ],
    ];

    private const DEFAULT_PRIVILEGES = [
        'staff' => ['Initiate'],
        'hr' => ['Initiate', 'Verify', 'Export reports'],
        'md' => ['Approve', 'Export reports', 'Delegate approvals'],
        'finance' => ['View only', 'Export reports'],
        'auditor' => ['View only', 'Export reports'],
        'admin' => ['Initiate'],
    ];

    public static function names(): array
    {
        return array_merge(...array_values(array_map('array_keys', self::MODULES)));
    }

    public static function module(string $name): string
    {
        foreach (self::MODULES as $module => $abilities) {
            if (array_key_exists($name, $abilities)) {
                return $module;
            }
        }

        return 'Other';
    }

    public static function label(string $name): string
    {
        foreach (self::MODULES as $abilities) {
            if (isset($abilities[$name])) {
                return $abilities[$name];
            }
        }

        return ucfirst(str_replace('-', ' ', $name));
    }

    public static function roleLabel(string $code): string
    {
        return self::ROLES[$code] ?? ucfirst(str_replace('-', ' ', $code));
    }

    public static function defaults(): array
    {
        return self::DEFAULTS;
    }

    public static function defaultPrivileges(): array
    {
        return self::DEFAULT_PRIVILEGES;
    }

    /** Current privileges per role, as saved by the roles screen. */
    public static function privileges(?string $role = null): array
    {
        $stored = Setting::get('role_privileges', []);

        if ($role !== null) {
            return $stored[$role] ?? self::DEFAULT_PRIVILEGES[$role] ?? [];
        }

        return $stored ?: self::DEFAULT_PRIVILEGES;
    }

    public static function syncRole(string $role, array $permissions, array $privileges): void
    {
        $model = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $model->syncPermissions(array_values(array_intersect($permissions, self::names())));

        $all = self::privileges();
        $all[$role] = array_values(array_intersect($privileges, self::PRIVILEGES));
        Setting::updateOrCreate(['k' => 'role_privileges'], ['v' => $all]);
    }

    public static function seedDefaults(): void
    {
        foreach (self::names() as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $code => $label) {
            $role = Role::firstOrCreate(['name' => $code, 'guard_name' => 'web']);
            $role->syncPermissions(self::DEFAULTS[$code] ?? []);
        }

        Setting::updateOrCreate(['k' => 'role_privileges'], ['v' => self::DEFAULT_PRIVILEGES]);
    }
}
