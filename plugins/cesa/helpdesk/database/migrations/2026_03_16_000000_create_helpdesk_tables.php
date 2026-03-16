<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_priorities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('helpdesk_ticket_statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('helpdesk_units', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('helpdesk_unit_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('unit_id')->constrained('helpdesk_units')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['unit_id', 'user_id'], 'helpdesk_unit_user_unique');
        });

        Schema::create('helpdesk_problem_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('unit_id')->constrained('helpdesk_units')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('default_responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['unit_id', 'name'], 'helpdesk_problem_categories_unit_name_unique');
        });

        Schema::create('helpdesk_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('priority_id')->constrained('helpdesk_priorities')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('helpdesk_units')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('problem_category_id')->constrained('helpdesk_problem_categories')->restrictOnDelete();
            $table->foreignId('ticket_status_id')->constrained('helpdesk_ticket_statuses')->restrictOnDelete();
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('title');
            $table->longText('description');
            $table->json('supporting_attachments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('solved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_id', 'ticket_status_id'], 'helpdesk_tickets_unit_status_index');
            $table->index(['owner_id', 'responsible_id'], 'helpdesk_tickets_owner_responsible_index');
        });

        Schema::create('helpdesk_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('comment');
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('helpdesk_ticket_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('helpdesk_tickets')->cascadeOnDelete();
            $table->foreignId('ticket_status_id')->constrained('helpdesk_ticket_statuses')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ticket_id', 'created_at'], 'helpdesk_ticket_histories_ticket_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_ticket_histories');
        Schema::dropIfExists('helpdesk_comments');
        Schema::dropIfExists('helpdesk_tickets');
        Schema::dropIfExists('helpdesk_problem_categories');
        Schema::dropIfExists('helpdesk_unit_user');
        Schema::dropIfExists('helpdesk_units');
        Schema::dropIfExists('helpdesk_ticket_statuses');
        Schema::dropIfExists('helpdesk_priorities');
    }
};
