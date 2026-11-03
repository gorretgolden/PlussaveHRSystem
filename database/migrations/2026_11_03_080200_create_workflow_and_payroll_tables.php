<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 12);
            $table->date('work_date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->string('source', 40)->default('Biometric');
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 14)->unique();
            $table->string('employee_id', 12);
            $table->string('type_code', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 5, 1);
            $table->string('reason', 400)->default('');
            $table->string('status', 20)->default('Pending');
            $table->unsignedBigInteger('request_id')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('type_code')->references('code')->on('leave_types');
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 14)->unique();
            $table->string('kind', 60);
            $table->string('subject', 200);
            $table->string('target_employee', 12)->nullable();
            $table->string('maker', 12);
            $table->string('maker_role', 20);
            $table->json('fields')->nullable();
            $table->json('payload')->nullable();
            $table->json('chain');
            $table->unsignedTinyInteger('stage')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->foreign('target_employee')->references('id')->on('employees')->nullOnDelete();
            $table->index('status');
            $table->index('maker');
        });

        Schema::create('request_actions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->string('actor', 12);
            $table->string('actor_role', 20);
            $table->string('action', 80);
            $table->string('comment', 600)->default('');
            $table->timestamp('acted_at')->useCurrent();

            $table->foreign('request_id')->references('id')->on('requests')->cascadeOnDelete();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->char('period', 7)->unique();
            $table->string('status', 20)->default('Draft');
            $table->decimal('gross', 16, 2)->nullable();
            $table->decimal('net', 16, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_inputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('period_id');
            $table->string('employee_id', 12);
            $table->string('kind', 20);
            $table->decimal('amount', 14, 2);
            $table->string('note', 200)->default('');
            $table->timestamps();

            $table->foreign('period_id')->references('id')->on('payroll_periods')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees');
        });

        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('period_id');
            $table->string('employee_id', 12);
            $table->decimal('basic', 14, 2)->default(0);
            $table->decimal('allowances', 14, 2)->default(0);
            $table->decimal('gross', 14, 2)->default(0);
            $table->decimal('paye', 14, 2)->default(0);
            $table->decimal('nssf_employee', 14, 2)->default(0);
            $table->decimal('nssf_employer', 14, 2)->default(0);
            $table->decimal('lst', 14, 2)->default(0);
            $table->decimal('loan_deduction', 14, 2)->default(0);
            $table->decimal('savings', 14, 2)->default(0);
            $table->decimal('other_deductions', 14, 2)->default(0);
            $table->decimal('net', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['period_id', 'employee_id']);
            $table->foreign('period_id')->references('id')->on('payroll_periods')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees');
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('k', 40)->primary();
            $table->json('v');
        });

        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('actor', 12)->nullable();
            $table->string('actor_role', 20)->nullable();
            $table->string('action', 60);
            $table->string('ref', 20)->nullable();
            $table->string('detail', 600)->default('');
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 12);
            $table->string('text', 300);
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index(['employee_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_log');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('payroll_lines');
        Schema::dropIfExists('payroll_inputs');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('request_actions');
        Schema::dropIfExists('requests');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance');
    }
};
