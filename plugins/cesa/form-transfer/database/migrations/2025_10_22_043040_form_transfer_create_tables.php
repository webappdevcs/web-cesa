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
        if (! Schema::hasTable('form_transfers')) {
            Schema::create('form_transfers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name', 191);
                $table->string('code', 50)->nullable();
                $table->string('uid_prefix', 20);
                $table->unsignedTinyInteger('uid_padding')->default(5);
                $table->unsignedBigInteger('uid_sequence')->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('approver_mail_subject')->nullable();
                $table->string('approver_mail_greeting')->nullable();
                $table->string('approver_mail_action_text')->nullable();
                $table->longText('approver_mail_template')->nullable();
                $table->string('requester_mail_subject')->nullable();
                $table->string('requester_mail_greeting')->nullable();
                $table->string('requester_mail_action_text')->nullable();
                $table->longText('requester_mail_template')->nullable();
                $table->longText('approver_whatsapp_template')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['company_id', 'code']);
                $table->unique(['company_id', 'uid_prefix']);
            });
        }

        if (! Schema::hasTable('form_transfer_banks')) {
            Schema::create('form_transfer_banks', function (Blueprint $table): void {
                $table->id();
                $table->string('code')->unique()->comment('Bank code: BCA, BRI, MANDIRI, etc');
                $table->string('name')->comment('Full bank name');
                $table->string('short_name')->nullable()->comment('Display name');
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('form_transfer_divisions')) {
            Schema::create('form_transfer_divisions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('form_transfer_id')->constrained('form_transfers')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['form_transfer_id', 'code']);
                $table->index(['form_transfer_id', 'is_active'], 'form_transfer_divisions_active_lookup_index');
            });
        }

        if (! Schema::hasTable('form_transfer_reference_notes')) {
            Schema::create('form_transfer_reference_notes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('form_transfer_id')->constrained('form_transfers')->cascadeOnDelete();
                $table->string('label');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['form_transfer_id', 'is_active'], 'form_transfer_ref_notes_active_lookup_index');
            });
        }

        if (! Schema::hasTable('form_transfer_approval_workflows')) {
            Schema::create('form_transfer_approval_workflows', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('form_transfer_id')->constrained('form_transfers')->cascadeOnDelete();
                $table->foreignId('division_id')->nullable()->constrained('form_transfer_divisions')->nullOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->json('steps')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['form_transfer_id', 'code']);
                $table->index(
                    ['form_transfer_id', 'division_id', 'is_active'],
                    'form_transfer_workflows_lookup_index'
                );
            });
        }

        if (! Schema::hasTable('form_transfer_requests')) {
            Schema::create('form_transfer_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('uid', 50)->unique();
                $table->string('submission_status', 20)->default('baru')->index();
                $table->string('approval_status', 20)->default('pending')->index();
                $table->string('realization_status', 20)->default('pending')->index();
                $table->string('status_response_id')->nullable()->unique();
                $table->foreignId('form_transfer_id')->nullable()->constrained('form_transfers')->nullOnDelete();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('requester_name');
                $table->string('division_name')->nullable();
                $table->foreignId('division_id')->nullable()->constrained('form_transfer_divisions')->nullOnDelete();
                $table->string('email')->nullable();
                $table->string('account_number');
                $table->string('account_name');
                $table->foreignId('bank_id')->nullable()->constrained('form_transfer_banks')->nullOnDelete();
                $table->decimal('transfer_amount', 18, 2);
                $table->text('purpose')->nullable();
                $table->text('reference_note')->nullable();
                $table->text('invoice_path')->nullable();
                $table->text('account_attachment_path')->nullable()->comment('Lampiran nomor rekening');
                $table->date('realized_at')->nullable();
                $table->string('realization_proof_path')->nullable();
                $table->text('realization_notes')->nullable();
                $table->foreignId('approval_workflow_id')->nullable()->constrained('form_transfer_approval_workflows')->nullOnDelete();
                $table->json('approvals')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(
                    ['form_transfer_id', 'approval_status', 'realization_status'],
                    'form_transfer_requests_filtering_index'
                );
                $table->index(['company_id', 'created_at'], 'form_transfer_requests_scoped_listing_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_transfer_requests');
        Schema::dropIfExists('form_transfer_approval_workflows');
        Schema::dropIfExists('form_transfer_reference_notes');
        Schema::dropIfExists('form_transfer_divisions');
        Schema::dropIfExists('form_transfer_banks');
        Schema::dropIfExists('form_transfers');
    }
};
