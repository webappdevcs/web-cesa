<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->integer('total_attendance_days')->default(0);
            $table->decimal('total_overtime_hours', 8, 2)->default(0);
            $table->integer('total_late_minutes')->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('total_penalties', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->json('details')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
