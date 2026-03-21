<?php

namespace Cesa\Helpdesk\Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Cesa\Helpdesk\Models\Comment;
use Cesa\Helpdesk\Models\Priority;
use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Cesa\Helpdesk\Models\Unit;
use Cesa\Helpdesk\Tests\HelpdeskTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Webkul\Security\Models\User as SecurityUser;

class TicketControllerTest extends HelpdeskTestCase
{
    public function test_metadata_returns_ticket_form_options_for_mobile_client(): void
    {
        $baseUser = User::factory()->create();

        $unit = Unit::factory()->create(['name' => 'IT Support']);
        $responsible = User::factory()->create(['name' => 'Helpdesk Agent']);
        $unit->users()->attach($responsible->id);

        $category = ProblemCategory::factory()->create([
            'unit_id'                => $unit->id,
            'name'                   => 'Laptop',
            'default_responsible_id' => $responsible->id,
        ]);

        \Illuminate\Support\Facades\DB::table('partners_partners')->insert([
            [
                'id'         => 300,
                'name'       => 'Allowed Partner',
                'sub_type'   => 'company',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        \Illuminate\Support\Facades\DB::table('companies')->insert([
            [
                'id'         => 200,
                'name'       => 'Allowed Company',
                'company_id' => 'ALLOWED',
                'partner_id' => 300,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $baseUser->id)
            ->update([
                'default_company_id' => 200,
            ]);

        $apiUser = $this->fakeApiUser($baseUser->id, ['create_helpdesk_ticket'], defaultCompanyId: 200);

        Sanctum::actingAs($apiUser);

        $response = $this->getJson("/admin/api/v1/helpdesk/meta?unit_id={$unit->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.default_company_id', 200)
            ->assertJsonFragment([
                'id'   => $unit->id,
                'name' => 'IT Support',
            ])
            ->assertJsonFragment([
                'id'   => $category->id,
                'name' => 'Laptop',
            ])
            ->assertJsonFragment([
                'id'   => $responsible->id,
                'name' => 'Helpdesk Agent',
            ])
            ->assertJsonFragment([
                'id'   => 200,
                'name' => 'Allowed Company',
            ]);
    }

    public function test_store_ticket_endpoint_creates_ticket_for_reporter(): void
    {
        $baseUser = User::factory()->create();
        $responsible = User::factory()->create();

        $unit = Unit::factory()->create();
        $unit->users()->attach($responsible->id);

        $category = ProblemCategory::factory()->create([
            'unit_id'                => $unit->id,
            'default_responsible_id' => $responsible->id,
        ]);

        $apiUser = $this->fakeApiUser($baseUser->id, ['create_helpdesk_ticket']);

        Sanctum::actingAs($apiUser);

        $response = $this->postJson('/admin/api/v1/helpdesk/tickets', [
            'priority_id'         => Priority::HIGH,
            'unit_id'             => $unit->id,
            'problem_category_id' => $category->id,
            'title'               => 'Cannot access printer',
            'description'         => 'Printer in finance room is offline.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Cannot access printer')
            ->assertJsonPath('data.owner_id', $baseUser->id)
            ->assertJsonPath('data.responsible_id', $responsible->id)
            ->assertJsonPath('data.ticket_status_id', TicketStatus::OPEN);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'owner_id'            => $baseUser->id,
            'responsible_id'      => $responsible->id,
            'problem_category_id' => $category->id,
            'ticket_status_id'    => TicketStatus::OPEN,
        ]);
    }

    public function test_store_ticket_rejects_unsupported_attachment_types(): void
    {
        Storage::fake('public');

        $baseUser = User::factory()->create();
        $responsible = User::factory()->create();

        $unit = Unit::factory()->create();
        $unit->users()->attach($responsible->id);

        $category = ProblemCategory::factory()->create([
            'unit_id'                => $unit->id,
            'default_responsible_id' => $responsible->id,
        ]);

        Sanctum::actingAs($this->fakeApiUser($baseUser->id, ['create_helpdesk_ticket']));

        $this->postJson('/admin/api/v1/helpdesk/tickets', [
            'priority_id'             => Priority::HIGH,
            'unit_id'                 => $unit->id,
            'problem_category_id'     => $category->id,
            'title'                   => 'Need VPN access',
            'description'             => 'Please review the attached file.',
            'supporting_attachments'  => [
                UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream'),
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['supporting_attachments.0']);
    }

    public function test_index_endpoint_returns_incoming_and_outgoing_boxes(): void
    {
        $baseUser = User::factory()->create();
        $otherUser = User::factory()->create();

        $unit = Unit::factory()->create();
        $unit->users()->attach($baseUser->id);

        $category = ProblemCategory::factory()->create([
            'unit_id' => $unit->id,
        ]);

        $incomingTicket = Ticket::factory()->create([
            'owner_id'            => $otherUser->id,
            'unit_id'             => $unit->id,
            'problem_category_id' => $category->id,
        ]);

        $outgoingTicket = Ticket::factory()->create([
            'owner_id'            => $baseUser->id,
            'unit_id'             => $unit->id,
            'problem_category_id' => $category->id,
        ]);

        $apiUser = $this->fakeApiUser($baseUser->id, ['update_helpdesk_ticket']);

        Sanctum::actingAs($apiUser);

        $incomingResponse = $this->getJson('/admin/api/v1/helpdesk/tickets?box=incoming');
        $outgoingResponse = $this->getJson('/admin/api/v1/helpdesk/tickets?box=outgoing');

        $incomingResponse
            ->assertOk()
            ->assertJsonPath('meta.box', 'incoming')
            ->assertJsonPath('data.0.id', $incomingTicket->id);

        $outgoingResponse
            ->assertOk()
            ->assertJsonPath('meta.box', 'outgoing')
            ->assertJsonPath('data.0.id', $outgoingTicket->id);
    }

    public function test_store_comment_endpoint_moves_open_ticket_to_in_progress(): void
    {
        ['responsible' => $responsible, 'ticket' => $ticket] = $this->createTicketContext();

        $apiUser = $this->fakeApiUser($responsible->id, ['update_helpdesk_ticket']);

        Sanctum::actingAs($apiUser);

        $response = $this->postJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}/comments", [
            'comment' => 'Ticket is being handled by support.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.ticket_status_id', TicketStatus::IN_PROGRESS)
            ->assertJsonCount(1, 'data.comments');

        $this->assertDatabaseHas('helpdesk_comments', [
            'ticket_id'   => $ticket->id,
            'user_id'     => $responsible->id,
            'comment'     => 'Ticket is being handled by support.',
            'visibility'  => Comment::VISIBILITY_PUBLIC,
        ]);

        $this->assertSame(TicketStatus::IN_PROGRESS, Ticket::query()->findOrFail($ticket->id)->ticket_status_id);
        $this->assertDatabaseCount('helpdesk_ticket_histories', 2);
    }

    public function test_store_comment_endpoint_rejects_internal_note_from_owner(): void
    {
        ['owner' => $owner, 'ticket' => $ticket] = $this->createTicketContext();

        Sanctum::actingAs($this->fakeApiUser($owner->id, ['create_helpdesk_ticket']));

        $this->postJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}/comments", [
            'comment'    => 'Saya ingin ini jadi internal note.',
            'visibility' => Comment::VISIBILITY_INTERNAL,
        ])->assertForbidden();
    }

    public function test_store_comment_endpoint_rejects_unsupported_attachment_types(): void
    {
        Storage::fake('public');

        ['responsible' => $responsible, 'ticket' => $ticket] = $this->createTicketContext();

        Sanctum::actingAs($this->fakeApiUser($responsible->id, ['update_helpdesk_ticket']));

        $this->postJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}/comments", [
            'comment'     => 'Please see attached proof.',
            'attachments' => [
                UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream'),
            ],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attachments.0']);
    }

    public function test_show_endpoint_hides_internal_notes_from_owner_mobile_client(): void
    {
        ['owner' => $owner, 'responsible' => $responsible, 'ticket' => $ticket] = $this->createTicketContext();

        Sanctum::actingAs($this->fakeApiUser($responsible->id, ['update_helpdesk_ticket']));

        $this->postJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}/comments", [
            'comment' => 'Komentar publik untuk pelapor.',
        ])->assertOk();

        $this->postJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}/comments", [
            'comment'    => 'Catatan internal untuk tim.',
            'visibility' => Comment::VISIBILITY_INTERNAL,
        ])->assertOk();

        Sanctum::actingAs($this->fakeApiUser($owner->id, ['create_helpdesk_ticket']));

        $response = $this->getJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.comments')
            ->assertJsonPath('data.comments.0.comment', 'Komentar publik untuk pelapor.')
            ->assertJsonPath('data.comments.0.visibility', Comment::VISIBILITY_PUBLIC)
            ->assertJsonPath('data.abilities.add_internal_note', false);
    }

    public function test_store_comment_notifications_follow_visibility_rules(): void
    {
        $viewer = User::factory()->create();
        ['owner' => $owner, 'responsible' => $responsible, 'ticket' => $ticket, 'unit' => $unit] = $this->createTicketContext(
            additionalUnitUserIds: [$viewer->id],
        );
        $unit->refresh();

        Sanctum::actingAs($this->fakeApiUser($responsible->id, ['update_helpdesk_ticket']));

        $this->postJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}/comments", [
            'comment' => 'Komentar publik.',
        ])->assertOk();

        $this->assertSame(2, \Illuminate\Support\Facades\DB::table('notifications')->count());
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_id', $owner->id)->count());
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_id', $viewer->id)->count());
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_id', $responsible->id)->count());

        $this->postJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}/comments", [
            'comment'    => 'Catatan internal.',
            'visibility' => Comment::VISIBILITY_INTERNAL,
        ])->assertOk();

        $this->assertSame(3, \Illuminate\Support\Facades\DB::table('notifications')->count());
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_id', $owner->id)->count());
        $this->assertSame(2, \Illuminate\Support\Facades\DB::table('notifications')->where('notifiable_id', $viewer->id)->count());
    }

    public function test_update_endpoint_rejects_direct_open_to_closed_transition(): void
    {
        ['responsible' => $responsible, 'ticket' => $ticket] = $this->createTicketContext();

        Sanctum::actingAs($this->fakeApiUser($responsible->id, ['update_helpdesk_ticket']));

        $this->patchJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}", [
            'ticket_status_id' => TicketStatus::CLOSED,
            'close_reason'     => 'Solved immediately.',
        ])->assertForbidden();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id'               => $ticket->id,
            'ticket_status_id' => TicketStatus::OPEN,
        ]);
    }

    public function test_update_endpoint_requires_close_reason_when_closing_ticket(): void
    {
        ['responsible' => $responsible, 'ticket' => $ticket] = $this->createTicketContext(TicketStatus::IN_PROGRESS);

        Sanctum::actingAs($this->fakeApiUser($responsible->id, ['update_helpdesk_ticket']));

        $this->patchJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}", [
            'ticket_status_id' => TicketStatus::CLOSED,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['close_reason']);
    }

    public function test_update_endpoint_allows_handler_to_close_ticket(): void
    {
        ['responsible' => $responsible, 'ticket' => $ticket] = $this->createTicketContext(TicketStatus::IN_PROGRESS);

        Sanctum::actingAs($this->fakeApiUser($responsible->id, ['update_helpdesk_ticket']));

        $response = $this->patchJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}", [
            'ticket_status_id' => TicketStatus::CLOSED,
            'close_reason'     => 'Perbaikan sudah selesai.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.ticket_status_id', TicketStatus::CLOSED)
            ->assertJsonPath('data.close_reason', 'Perbaikan sudah selesai.')
            ->assertJsonPath('message', 'Ticket updated successfully.');

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id'               => $ticket->id,
            'ticket_status_id' => TicketStatus::CLOSED,
            'close_reason'     => 'Perbaikan sudah selesai.',
        ]);

        $this->assertNotNull(Ticket::query()->findOrFail($ticket->id)->solved_at);
    }

    public function test_update_endpoint_allows_owner_to_cancel_open_ticket(): void
    {
        ['owner' => $owner, 'ticket' => $ticket] = $this->createTicketContext();

        Sanctum::actingAs($this->fakeApiUser($owner->id, ['create_helpdesk_ticket']));

        $response = $this->patchJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}", [
            'ticket_status_id' => TicketStatus::CANCELLED,
            'cancel_reason'    => 'Masalah sudah tidak relevan.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.ticket_status_id', TicketStatus::CANCELLED)
            ->assertJsonPath('data.cancel_reason', 'Masalah sudah tidak relevan.');

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id'               => $ticket->id,
            'ticket_status_id' => TicketStatus::CANCELLED,
            'cancel_reason'    => 'Masalah sudah tidak relevan.',
        ]);
    }

    public function test_update_endpoint_rejects_owner_cancelling_in_progress_ticket(): void
    {
        ['owner' => $owner, 'ticket' => $ticket] = $this->createTicketContext(TicketStatus::IN_PROGRESS);

        Sanctum::actingAs($this->fakeApiUser($owner->id, ['create_helpdesk_ticket']));

        $this->patchJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}", [
            'ticket_status_id' => TicketStatus::CANCELLED,
            'cancel_reason'    => 'Tidak jadi dibutuhkan.',
        ])->assertForbidden();
    }

    public function test_update_endpoint_allows_handler_to_cancel_ticket_from_open_and_in_progress(): void
    {
        ['responsible' => $responsible, 'ticket' => $openTicket] = $this->createTicketContext();
        ['ticket'      => $inProgressTicket] = $this->createTicketContext(TicketStatus::IN_PROGRESS, responsible: $responsible);

        Sanctum::actingAs($this->fakeApiUser($responsible->id, ['update_helpdesk_ticket']));

        $this->patchJson("/admin/api/v1/helpdesk/tickets/{$openTicket->id}", [
            'ticket_status_id' => TicketStatus::CANCELLED,
            'cancel_reason'    => 'Ticket duplikat.',
        ])->assertOk();

        $this->patchJson("/admin/api/v1/helpdesk/tickets/{$inProgressTicket->id}", [
            'ticket_status_id' => TicketStatus::CANCELLED,
            'cancel_reason'    => 'Dibatalkan saat proses berjalan.',
        ])->assertOk();

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id'               => $openTicket->id,
            'ticket_status_id' => TicketStatus::CANCELLED,
            'cancel_reason'    => 'Ticket duplikat.',
        ]);

        $this->assertDatabaseHas('helpdesk_tickets', [
            'id'               => $inProgressTicket->id,
            'ticket_status_id' => TicketStatus::CANCELLED,
            'cancel_reason'    => 'Dibatalkan saat proses berjalan.',
        ]);
    }

    public function test_update_endpoint_allows_owner_to_reopen_closed_ticket(): void
    {
        ['owner' => $owner, 'ticket' => $ticket] = $this->createTicketContext(TicketStatus::CLOSED);

        Sanctum::actingAs($this->fakeApiUser($owner->id, ['create_helpdesk_ticket']));

        $response = $this->patchJson("/admin/api/v1/helpdesk/tickets/{$ticket->id}", [
            'ticket_status_id' => TicketStatus::OPEN,
            'reopen_reason'    => 'Masalah muncul lagi.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.ticket_status_id', TicketStatus::OPEN)
            ->assertJsonPath('data.reopen_reason', 'Masalah muncul lagi.');

        $reopenedTicket = Ticket::query()->findOrFail($ticket->id);

        $this->assertSame(TicketStatus::OPEN, $reopenedTicket->ticket_status_id);
        $this->assertNull($reopenedTicket->solved_at);
        $this->assertSame('Masalah muncul lagi.', $reopenedTicket->reopen_reason);
    }

    /**
     * @param  array<int, string>  $abilities
     */
    private function fakeApiUser(int $id, array $abilities, ?int $defaultCompanyId = null): SecurityUser
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
        $user->default_company_id = $defaultCompanyId;
        $user->grantedAbilities = $abilities;

        return $user;
    }

    /**
     * @param  array<int, int>  $additionalUnitUserIds
     * @return array{owner: User, responsible: User, unit: Unit, category: ProblemCategory, ticket: Ticket}
     */
    private function createTicketContext(
        int $status = TicketStatus::OPEN,
        array $additionalUnitUserIds = [],
        ?User $responsible = null,
    ): array {
        $owner = User::factory()->create();
        $responsible ??= User::factory()->create();

        $unit = Unit::factory()->create();
        $unit->users()->attach(array_merge([$responsible->id], $additionalUnitUserIds));

        $category = ProblemCategory::factory()->create([
            'unit_id'                => $unit->id,
            'default_responsible_id' => $responsible->id,
        ]);

        $ticket = Ticket::factory()->create([
            'owner_id'            => $owner->id,
            'unit_id'             => $unit->id,
            'problem_category_id' => $category->id,
            'ticket_status_id'    => $status,
            'responsible_id'      => $responsible->id,
            'approved_at'         => $status !== TicketStatus::OPEN ? now()->subHour() : null,
            'solved_at'           => $status === TicketStatus::CLOSED ? now()->subMinutes(10) : null,
            'close_reason'        => $status === TicketStatus::CLOSED ? 'Sudah ditutup sebelumnya.' : null,
            'cancel_reason'       => $status === TicketStatus::CANCELLED ? 'Sudah dibatalkan sebelumnya.' : null,
        ]);

        return compact('owner', 'responsible', 'unit', 'category', 'ticket');
    }
}
