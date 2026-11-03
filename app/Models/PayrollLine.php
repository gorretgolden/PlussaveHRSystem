<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollLine extends Model
{
    protected $guarded = [];

    protected $casts = [
        'basic' => 'decimal:2',
        'allowances' => 'decimal:2',
        'gross' => 'decimal:2',
        'paye' => 'decimal:2',
        'nssf_employee' => 'decimal:2',
        'nssf_employer' => 'decimal:2',
        'lst' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'savings' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'net' => 'decimal:2',
    ];
}
