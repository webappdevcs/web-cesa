<?php

namespace Cesa\Helpdesk\Tests\Feature;

use App\Models\User;
use Cesa\Helpdesk\Filament\Resources\TicketResource;
use Cesa\Helpdesk\Filament\Resources\TicketResource\Pages\ListTickets;
use Cesa\Helpdesk\Models\Comment;
use Cesa\Helpdesk\Models\Priority;
use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Cesa\Helpdesk\Models\Unit;
use Cesa\Helpdesk\Policies\ProblemCategoryPolicy;
use Cesa\Helpdesk\Policies\TicketPolicy;
use Cesa\Helpdesk\Policies\TicketStatusPolicy;
use Cesa\Helpdesk\Services\TicketCommentService;
use Cesa\Helpdesk\Services\TicketWorkflowService;
use Cesa\Helpdesk\Tests\HelpdeskTestCase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Webkul\Security\Models\User as SecurityUser;

class HelpdeskTicketLifecycleTest extends HelpdeskTestCase
{
    public function test_default_seeders_insert_priority_and_status_master_data(): void
    {
        $this->assertDatabaseHas('helpdesk_priorities', [
            'id'   => Priority::CRITICAL,
            'name' => 'Critical/Urgent',
        ]);

        $this->assertDatabaseHas('helpdesk_ticket_statuses', [
            'id'   => TicketStatus::OPEN,
            'name' => 'Open',
        ]);
    }

    public function test_ticket_creation_handler_response_and_closing_write_history_records(): void
    {
        $owner = User::factory()->create();
        $responsible = User::factory()->create();

        $unit = Unit::factory()->create();
        $unit->users()->attach($responsible->id);

        $category = ProblemCategory::factory()->create([
            'unit_id'                => $unit->id,
            'default_responsible_id' => $responsible->id,
        ]);

        $ticket = Ticket::query()->create([
            'priority_id'         => Priority::CRITICAL,
            'unit_id'             => $unit->id,
            'owner_id'            => $owner->id,
            'problem_category_id' => $category->id,
            'title'               => 'Printer error',
            'description'         => '<p>Cannot print payroll report.</p>',
            'ticket_status_id'    => TicketStatus::OPEN,
        ]);

        $this->assertSame($responsible->id, $ticket->responsible_id);
        $this->assertDatabaseCount('helpdesk_ticket_histories', 1);
        $this->assertNull($ticket->fresh()->approved_at);
        $this->assertNull($ticket->fresh()->solved_at);

        app(TicketCommentService::class)->create(
            $this->fakeHelpdeskUser($responsible->id, ['update_helpdesk_ticket']),
            $ticket->fresh(),
            ['comment' => 'Saya tindak lanjuti tiket ini.'],
        );

        $ticket = $ticket->fresh();

        $this->assertSame(TicketStatus::IN_PROGRESS, $ticket->ticket_status_id);
        $this->assertDatabaseCount('helpdesk_ticket_histories', 2);
        $this->assertNotNull($ticket->approved_at);
        $this->assertNull($ticket->solved_at);

        app(TicketWorkflowService::class)->transition(
            $this->fakeHelpdeskUser($responsible->id, ['update_helpdesk_ticket']),
            $ticket->fresh(),
            TicketStatus::CLOSED,
            closeReason: 'Sudah selesai ditangani.',
        );

        $this->assertDatabaseCount('helpdesk_ticket_histories', 3);
        $this->assertNotNull($ticket->fresh()->approved_at);
        $this->assertNotNull($ticket->fresh()->solved_at);
        $this->assertSame('Sudah selesai ditangani.', $ticket->fresh()->close_reason);
    }

    public function test_owner_follow_up_comment_keeps_ticket_open_until_handler_responds(): void
    {
        $owner = User::factory()->create();
        $responsible = User::factory()->create();

        $unit = Unit::factory()->create();
        $unit->users()->attach($responsible->id);

        $category = ProblemCategory::factory()->create([
            'unit_id'                => $unit->id,
            'default_responsible_id' => $responsible->id,
        ]);

        $ticket = Ticket::query()->create([
            'priority_id'         => Priority::MEDIUM,
            'unit_id'             => $unit->id,
            'owner_id'            => $owner->id,
            'problem_category_id' => $category->id,
            'title'               => 'Need access card reset',
            'description'         => '<p>Kartu akses tidak terbaca.</p>',
            'ticket_status_id'    => TicketStatus::OPEN,
        ]);

        app(TicketCommentService::class)->create(
            $this->fakeHelpdeskUser($owner->id, ['create_helpdesk_ticket']),
            $ticket->fresh(),
            ['comment' => 'Tambah informasi dari pelapor.'],
        );

        $ticket = $ticket->fresh();

        $this->assertSame(TicketStatus::OPEN, $ticket->ticket_status_id);
        $this->assertNull($ticket->approved_at);
        $this->assertNull($ticket->solved_at);
        $this->assertDatabaseCount('helpdesk_ticket_histories', 1);
    }

    public function test_internal_note_does_not_move_ticket_to_in_progress(): void
    {
        $owner = User::factory()->create();
        $responsible = User::factory()->create();

        $unit = Unit::factory()->create();
        $unit->users()->attach([$responsible->id]);

        $category = ProblemCategory::factory()->create([
            'unit_id'                => $unit->id,
            'default_responsible_id' => $responsible->id,
        ]);

        $ticket = Ticket::query()->create([
            'priority_id'         => Priority::MEDIUM,
            'unit_id'             => $unit->id,
            'owner_id'            => $owner->id,
            'problem_category_id' => $category->id,
            'title'               => 'Internet unstable',
            'description'         => '<p>Koneksi sering putus.</p>',
            'ticket_status_id'    => TicketStatus::OPEN,
        ]);

        app(TicketCommentService::class)->create(
            $this->fakeHelpdeskUser($responsible->id, ['update_helpdesk_ticket']),
            $ticket->fresh(),
            [
                'comment'    => 'Catatan internal untuk tim.',
                'visibility' => Comment::VISIBILITY_INTERNAL,
            ],
        );

        $ticket = $ticket->fresh();

        $this->assertSame(TicketStatus::OPEN, $ticket->ticket_status_id);
        $this->assertNull($ticket->approved_at);
        $this->assertDatabaseCount('helpdesk_ticket_histories', 1);
    }

    public function test_company_options_are_limited_to_default_and_allowed_companies(): void
    {
        DB::table('partners_partners')->insert([
            [
                'id'         => 200,
                'name'       => 'Allowed Partner',
                'sub_type'   => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => 201,
                'name'       => 'Default Partner',
                'sub_type'   => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => 202,
                'name'       => 'Hidden Partner',
                'sub_type'   => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('companies')->insert([
            [
                'id'         => 100,
                'name'       => 'Allowed Company',
                'company_id' => 'ALLOWED',
                'partner_id' => 200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => 101,
                'name'       => 'Default Company',
                'company_id' => 'DEFAULT',
                'partner_id' => 201,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id'         => 102,
                'name'       => 'Hidden Company',
                'company_id' => 'HIDDEN',
                'partner_id' => 202,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $baseUser = User::factory()->create();

        DB::table('users')
            ->where('id', $baseUser->id)
            ->update([
                'default_company_id' => 101,
            ]);

        DB::table('user_allowed_companies')->insert([
            'user_id'    => $baseUser->id,
            'company_id' => 100,
        ]);

        $this->actingAs(SecurityUser::query()->findOrFail($baseUser->id));

        $options = $this->invokeProtectedStaticMethod(TicketResource::class, 'companyOptions');

        $this->assertSame([
            100 => 'Allowed Company',
            101 => 'Default Company',
        ], $options);
    }

    public function test_responsible_options_are_scoped_to_selected_unit_only(): void
    {
        $assignedUser = User::factory()->create(['name' => 'Assigned User']);
        $otherUser = User::factory()->create(['name' => 'Other User']);

        $unit = Unit::factory()->create();
        $unit->users()->attach($assignedUser->id);

        $this->assertSame([], $this->invokeProtectedStaticMethod(TicketResource::class, 'unitUserOptions', [null]));
        $this->assertSame([
            $assignedUser->id => 'Assigned User',
        ], $this->invokeProtectedStaticMethod(TicketResource::class, 'unitUserOptions', [$unit->id]));
        $this->assertArrayNotHasKey($otherUser->id, $this->invokeProtectedStaticMethod(TicketResource::class, 'unitUserOptions', [$unit->id]));
    }

    public function test_visible_to_scope_keeps_all_records_for_user_with_global_ticket_access(): void
    {
        $query = Ticket::query();
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $user = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'view_any_helpdesk_ticket';
            }
        };

        $scopedQuery = Ticket::query()->visibleTo($user);

        $this->assertSame($sql, $scopedQuery->toSql());
        $this->assertSame($bindings, $scopedQuery->getBindings());
    }

    public function test_outgoing_scope_only_returns_tickets_created_by_current_user(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $unit = Unit::factory()->create();
        $category = ProblemCategory::factory()->create([
            'unit_id' => $unit->id,
        ]);

        $outgoingTicket = Ticket::factory()->create([
            'owner_id'            => $currentUser->id,
            'unit_id'             => $unit->id,
            'problem_category_id' => $category->id,
        ]);

        Ticket::factory()->create([
            'owner_id'            => $otherUser->id,
            'responsible_id'      => $currentUser->id,
            'unit_id'             => $unit->id,
            'problem_category_id' => $category->id,
        ]);

        $scopedUser = SecurityUser::query()->findOrFail($currentUser->id);

        $ticketIds = Ticket::query()->outgoingFor($scopedUser)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$outgoingTicket->id], $ticketIds);
    }

    public function test_incoming_scope_returns_tickets_received_by_user_or_their_unit(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $assignedUnit = Unit::factory()->create();
        $assignedUnit->users()->attach($currentUser->id);

        $otherUnit = Unit::factory()->create();

        $assignedCategory = ProblemCategory::factory()->create([
            'unit_id' => $assignedUnit->id,
        ]);

        $otherCategory = ProblemCategory::factory()->create([
            'unit_id' => $otherUnit->id,
        ]);

        $incomingByUnit = Ticket::factory()->create([
            'owner_id'            => $otherUser->id,
            'unit_id'             => $assignedUnit->id,
            'problem_category_id' => $assignedCategory->id,
        ]);

        $incomingByResponsible = Ticket::factory()->create([
            'owner_id'            => $otherUser->id,
            'unit_id'             => $otherUnit->id,
            'problem_category_id' => $otherCategory->id,
            'responsible_id'      => $currentUser->id,
        ]);

        Ticket::factory()->create([
            'owner_id'            => $currentUser->id,
            'unit_id'             => $otherUnit->id,
            'problem_category_id' => $otherCategory->id,
        ]);

        Ticket::factory()->create([
            'owner_id'            => $otherUser->id,
            'unit_id'             => $otherUnit->id,
            'problem_category_id' => $otherCategory->id,
            'responsible_id'      => $otherUser->id,
        ]);

        $scopedUser = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'update_helpdesk_ticket';
            }
        };
        $scopedUser->id = $currentUser->id;

        $ticketIds = Ticket::query()->incomingFor($scopedUser)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([
            $incomingByUnit->id,
            $incomingByResponsible->id,
        ], $ticketIds);
    }

    public function test_incoming_scope_does_not_include_unit_tickets_for_user_without_inbox_permission(): void
    {
        $currentUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $assignedUnit = Unit::factory()->create();
        $assignedUnit->users()->attach($currentUser->id);

        $assignedCategory = ProblemCategory::factory()->create([
            'unit_id' => $assignedUnit->id,
        ]);

        $incomingByUnit = Ticket::factory()->create([
            'owner_id'            => $otherUser->id,
            'unit_id'             => $assignedUnit->id,
            'problem_category_id' => $assignedCategory->id,
        ]);

        $scopedUser = SecurityUser::query()->findOrFail($currentUser->id);

        $ticketIds = Ticket::query()->incomingFor($scopedUser)->pluck('id')->all();

        $this->assertNotContains($incomingByUnit->id, $ticketIds);
    }

    public function test_list_ticket_page_only_shows_all_tab_for_user_with_global_ticket_access(): void
    {
        $globalUser = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'view_any_helpdesk_ticket';
            }
        };

        $regularUser = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return false;
            }
        };

        $this->assertTrue(ListTickets::shouldShowAllTab($globalUser));
        $this->assertFalse(ListTickets::shouldShowAllTab($regularUser));
    }

    public function test_list_ticket_page_defaults_to_tab_that_matches_user_access_level(): void
    {
        $incomingUser = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'update_helpdesk_ticket';
            }
        };

        $reporterUser = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'create_helpdesk_ticket';
            }
        };

        $this->assertSame('incoming', ListTickets::defaultActiveTabForUser($incomingUser));
        $this->assertSame('outgoing', ListTickets::defaultActiveTabForUser($reporterUser));
    }

    public function test_ticket_policy_allows_global_view_permission_to_open_any_ticket_detail(): void
    {
        $unit = Unit::factory()->create();
        $category = ProblemCategory::factory()->create([
            'unit_id' => $unit->id,
        ]);

        $ticket = Ticket::factory()->create([
            'unit_id'             => $unit->id,
            'problem_category_id' => $category->id,
        ]);

        $user = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'view_any_helpdesk_ticket';
            }
        };

        $this->assertTrue((new TicketPolicy)->view($user, $ticket));
    }

    public function test_ticket_policy_allows_unit_handler_with_update_permission_to_open_ticket_detail(): void
    {
        $currentUser = User::factory()->create();

        $unit = Unit::factory()->create();
        $unit->users()->attach($currentUser->id);

        $category = ProblemCategory::factory()->create([
            'unit_id' => $unit->id,
        ]);

        $ticket = Ticket::factory()->create([
            'unit_id'             => $unit->id,
            'problem_category_id' => $category->id,
        ]);

        $user = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'update_helpdesk_ticket';
            }
        };
        $user->id = $currentUser->id;

        $this->assertTrue((new TicketPolicy)->view($user, $ticket));
    }

    public function test_problem_category_policy_uses_resource_style_permission_key(): void
    {
        $user = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'view_any_helpdesk_problem::category';
            }
        };

        $this->assertTrue((new ProblemCategoryPolicy)->viewAny($user));
    }

    public function test_ticket_status_policy_uses_resource_style_permission_key(): void
    {
        $user = new class extends SecurityUser
        {
            public function can($abilities, $arguments = []): bool
            {
                return $abilities === 'view_any_helpdesk_ticket::status';
            }
        };

        $this->assertTrue((new TicketStatusPolicy)->viewAny($user));
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    protected function invokeProtectedStaticMethod(string $className, string $method, array $arguments = []): mixed
    {
        $reflection = new ReflectionMethod($className, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(null, $arguments);
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function fakeHelpdeskUser(int $id, array $abilities): SecurityUser
    {
        $user = new class extends SecurityUser
        {
            /** @var array<int, string> */
            public array $grantedAbilities = [];

            public function can($ability, $arguments = []): bool
            {
                return in_array($ability, $this->grantedAbilities, true);
            }
        };

        $user->id = $id;
        $user->grantedAbilities = $abilities;

        return $user;
    }
}
