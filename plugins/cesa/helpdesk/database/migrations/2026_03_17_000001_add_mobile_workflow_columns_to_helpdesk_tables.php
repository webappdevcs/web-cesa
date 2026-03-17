<?php

use Cesa\Helpdesk\Models\Comment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_tickets', function (Blueprint $table): void {
            $table->text('close_reason')->nullable()->after('solved_at');
            $table->text('cancel_reason')->nullable()->after('close_reason');
            $table->text('reopen_reason')->nullable()->after('cancel_reason');
        });

        Schema::table('helpdesk_comments', function (Blueprint $table): void {
            $table->string('visibility')
                ->default(Comment::VISIBILITY_PUBLIC)
                ->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_comments', function (Blueprint $table): void {
            $table->dropColumn('visibility');
        });

        Schema::table('helpdesk_tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'close_reason',
                'cancel_reason',
                'reopen_reason',
            ]);
        });
    }
};
