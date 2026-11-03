<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->timestamps();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->string('code', 4)->primary();
            $table->string('name', 60);
            $table->decimal('transport', 14, 2)->default(0);
            $table->decimal('housing', 14, 2)->default(0);
            $table->decimal('airtime', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->string('code', 20)->primary();
            $table->string('name', 40);
            $table->decimal('days', 5, 1);
            $table->string('note', 200)->default('');
            $table->char('gender', 1)->nullable();
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->date('day')->primary();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->string('id', 12)->primary();
            $table->string('first_name', 60);
            $table->string('last_name', 60);
            $table->char('gender', 1);
            $table->date('dob');
            $table->string('title', 80);
            $table->foreignId('dept_id')->constrained('departments');
            $table->foreignId('branch_id')->constrained('branches');
            $table->string('supervisor_id', 12)->nullable();
            $table->string('employment_type', 20);
            $table->date('joined');
            $table->string('grade_code', 4);
            $table->decimal('basic', 14, 2);
            $table->string('status', 20)->default('Active');
            $table->string('nin', 24)->default('');
            $table->string('phone', 30)->default('');
            $table->string('email', 120)->unique();
            $table->string('address', 120)->default('');
            $table->string('nok_name', 80)->default('');
            $table->string('nok_relation', 30)->default('');
            $table->string('nok_phone', 30)->default('');
            $table->date('contract_end')->nullable();
            $table->date('probation_start')->nullable();
            $table->date('probation_end')->nullable();
            $table->date('appraisal_due')->nullable();
            $table->decimal('annual_balance', 5, 1)->default(0);
            $table->decimal('sick_balance', 5, 1)->default(0);
            $table->decimal('other_balance', 5, 1)->default(0);
            $table->decimal('loan_balance', 14, 2)->default(0);
            $table->decimal('savings', 14, 2)->default(0);
            $table->mediumText('photo')->nullable();
            $table->string('bank_account', 40)->default('');
            $table->timestamps();

            $table->foreign('supervisor_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('grade_code')->references('code')->on('grades');
            $table->index('status');
            $table->index('branch_id');
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->string('employee_id', 12);
            $table->string('doc_type', 60);
            $table->boolean('present')->default(false);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->primary(['employee_id', 'doc_type']);
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('branches');
    }
};
