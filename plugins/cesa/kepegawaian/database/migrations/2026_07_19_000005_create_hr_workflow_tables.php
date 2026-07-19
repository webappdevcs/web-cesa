<?php

use Cesa\Kepegawaian\Enums\HrWorkflowRunStatus;
use Cesa\Kepegawaian\Enums\HrWorkflowTaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees_hr_workflow_templates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('employees_hr_workflow_template_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')
                ->constrained('employees_hr_workflow_templates')
                ->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(10);
            $table->string('name');
            $table->string('department')->nullable();
            $table->unsignedInteger('due_days')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('requires_evidence')->default(false);
            $table->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['template_id', 'sort_order']);
            $table->unique(['template_id', 'department', 'name'], 'employees_hr_workflow_step_unique');
        });

        Schema::create('employees_hr_workflow_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('template_id')->nullable()->constrained('employees_hr_workflow_templates')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('employees_employees')->cascadeOnDelete();
            $table->string('template_code');
            $table->string('name');
            $table->string('type')->index();
            $table->string('status')->default(HrWorkflowRunStatus::InProgress->value)->index();
            $table->string('active_key', 64)->nullable()->unique();
            $table->json('context')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['template_id', 'status']);
        });

        Schema::create('employees_hr_workflow_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('employees_hr_workflow_runs')->cascadeOnDelete();
            $table->foreignId('template_step_id')->nullable()->constrained('employees_hr_workflow_template_steps')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(10);
            $table->string('name');
            $table->string('department')->nullable();
            $table->string('status')->default(HrWorkflowTaskStatus::Pending->value)->index();
            $table->boolean('is_required')->default(true);
            $table->boolean('requires_evidence')->default(false);
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('completion_note')->nullable();
            $table->string('evidence_path')->nullable();
            $table->timestamps();

            $table->index(['run_id', 'sort_order']);
            $table->index(['assigned_to_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees_hr_workflow_tasks');
        Schema::dropIfExists('employees_hr_workflow_runs');
        Schema::dropIfExists('employees_hr_workflow_template_steps');
        Schema::dropIfExists('employees_hr_workflow_templates');
    }
};
