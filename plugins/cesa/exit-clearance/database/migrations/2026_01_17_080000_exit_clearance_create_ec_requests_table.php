<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exit_clearance_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('exit_clearance_departments')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->string('placement')->nullable();
            $table->date('join_date')->nullable();
            $table->date('request_date');
            $table->date('departure_date')->nullable();
            $table->text('reason')->nullable();
            $table->text('workload_feedback')->nullable();
            $table->text('career_growth_feedback')->nullable();
            $table->text('facility_welfare_feedback')->nullable();
            $table->text('work_relationship_feedback')->nullable();
            $table->text('compensation_feedback')->nullable();
            $table->text('division_feedback')->nullable();
            $table->text('company_feedback')->nullable();
            $table->text('clearance_kartu_halo')->nullable();
            $table->text('clearance_employee_debt')->nullable();
            $table->text('clearance_uniform_return')->nullable();
            $table->text('clearance_vehicle_return')->nullable();
            $table->text('clearance_inventory_return')->nullable();
            $table->text('clearance_account_deactivation')->nullable();
            $table->text('clearance_receivable_data')->nullable();
            $table->text('clearance_promotor_internal')->nullable();
            $table->text('clearance_nota_pending')->nullable();
            $table->text('clearance_stock_opname')->nullable();
            $table->string('resignation_letter_url')->nullable();
            $table->string('form_uid')->nullable();
            $table->string('form_status')->nullable();
            $table->string('form_response_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('department_id');
            $table->index('request_date');
            $table->index('email');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exit_clearance_requests');
    }
};
