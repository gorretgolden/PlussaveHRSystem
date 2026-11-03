<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 12);
            $table->string('cycle', 40);
            $table->date('due_date')->nullable();
            $table->string('status', 60);
            $table->decimal('score', 3, 1)->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('title', 160);
            $table->string('provider', 120)->default('');
            $table->date('training_date')->nullable();
            $table->boolean('mandatory')->default(false);
            $table->string('status', 30)->default('Scheduled');
            $table->string('audience', 120)->default('');
            $table->timestamps();
        });

        Schema::create('training_attendance', function (Blueprint $table) {
            $table->string('training_id', 10);
            $table->string('employee_id', 12);
            $table->boolean('attended')->default(true);

            $table->primary(['training_id', 'employee_id']);
            $table->foreign('training_id')->references('id')->on('trainings')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::create('vacancies', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('title', 120);
            $table->foreignId('dept_id')->constrained('departments');
            $table->foreignId('branch_id')->constrained('branches');
            $table->date('opened');
            $table->date('closes')->nullable();
            $table->unsignedInteger('applicants')->default(0);
            $table->string('stage', 40)->default('Advertised');
            $table->string('reason', 200)->default('');
            $table->timestamps();
        });

        Schema::create('cases', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('kind', 20);
            $table->string('employee_id', 12);
            $table->string('category', 140);
            $table->date('incident_date')->nullable();
            $table->string('reported_by', 12)->nullable();
            $table->text('description')->nullable();
            $table->string('stage', 30);
            $table->date('hearing_date')->nullable();
            $table->json('panel')->nullable();
            $table->text('minutes')->nullable();
            $table->string('decision', 200)->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('confidential')->default(false);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
        });

        Schema::create('case_people', function (Blueprint $table) {
            $table->string('case_id', 10);
            $table->string('employee_id', 12);
            $table->string('role', 20);

            $table->primary(['case_id', 'employee_id', 'role']);
            $table->foreign('case_id')->references('id')->on('cases')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::create('exits', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('employee_id', 12);
            $table->string('type', 20);
            $table->date('notice_date')->nullable();
            $table->date('last_working_day')->nullable();
            $table->string('reason', 200)->default('');
            $table->text('interview_notes')->nullable();
            $table->string('status', 40)->default('Clearance in progress');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
        });

        Schema::create('exit_clearance', function (Blueprint $table) {
            $table->string('exit_id', 10);
            $table->string('item', 90);
            $table->boolean('done')->default(false);

            $table->primary(['exit_id', 'item']);
            $table->foreign('exit_id')->references('id')->on('exits')->cascadeOnDelete();
        });

        Schema::create('library_documents', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('title', 160);
            $table->string('version', 10);
            $table->string('category', 30);
            $table->string('owner', 60);
            $table->date('approved_date')->nullable();
            $table->unsignedInteger('acknowledgements')->default(0);
            $table->timestamps();
        });

        Schema::create('library_acks', function (Blueprint $table) {
            $table->string('document_id', 10);
            $table->string('employee_id', 12);
            $table->timestamp('acked_at')->useCurrent();

            $table->primary(['document_id', 'employee_id']);
            $table->foreign('document_id')->references('id')->on('library_documents')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('maker_role', 30);
            $table->string('verify_step', 140)->default('');
            $table->string('approve_role', 140);
            $table->timestamps();
        });

        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('state', 10)->default('ok');
            $table->json('flows');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('library_acks');
        Schema::dropIfExists('library_documents');
        Schema::dropIfExists('exit_clearance');
        Schema::dropIfExists('exits');
        Schema::dropIfExists('case_people');
        Schema::dropIfExists('cases');
        Schema::dropIfExists('vacancies');
        Schema::dropIfExists('training_attendance');
        Schema::dropIfExists('trainings');
        Schema::dropIfExists('appraisals');
    }
};
