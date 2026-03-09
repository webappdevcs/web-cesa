<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create pivot table for user <-> form_transfer access
        Schema::create('form_transfer_user_accesses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('form_transfer_id')->constrained('form_transfers')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'form_transfer_id'], 'form_transfer_user_access_unique');
        });

        // Add has_all_form_transfer_access column to users table
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('has_all_form_transfer_access')->default(false)->after('resource_permission');
        });

        // Bootstrap existing super_admin users with all-access
        DB::table('users')
            ->whereIn('id', function ($query): void {
                $query->select('model_id')
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('roles.name', 'super_admin')
                    ->where('model_has_roles.model_type', 'Webkul\\Security\\Models\\User');
            })
            ->update(['has_all_form_transfer_access' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_transfer_user_accesses');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('has_all_form_transfer_access');
        });
    }
};
