<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $table = 'employees';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'dob' => 'date',
        'joined' => 'date',
        'contract_end' => 'date',
        'probation_start' => 'date',
        'probation_end' => 'date',
        'appraisal_due' => 'date',
        'basic' => 'decimal:2',
        'loan_balance' => 'decimal:2',
        'savings' => 'decimal:2',
        'annual_balance' => 'float',
        'sick_balance' => 'float',
        'other_balance' => 'float',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_code', 'code');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class, 'employee_id');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'employee_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }
}
