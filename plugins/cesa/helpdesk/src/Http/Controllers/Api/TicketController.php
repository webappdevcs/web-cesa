<?php

namespace Cesa\Helpdesk\Http\Controllers\Api;

use Cesa\Helpdesk\Http\Requests\StoreTicketCommentRequest;
use Cesa\Helpdesk\Http\Requests\StoreTicketRequest;
use Cesa\Helpdesk\Http\Requests\TicketIndexRequest;
use Cesa\Helpdesk\Http\Requests\TicketMetadataRequest;
use Cesa\Helpdesk\Http\Requests\UpdateTicketRequest;
use Cesa\Helpdesk\Http\Resources\TicketResource;
use Cesa\Helpdesk\Models\Priority;
use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Ticket;
use Cesa\Helpdesk\Models\TicketStatus;
use Cesa\Helpdesk\Models\Unit;
use Cesa\Helpdesk\Services\TicketCommentService;
use Cesa\Helpdesk\Services\TicketWorkflowService;
use Cesa\Helpdesk\Support\TicketOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Webkul\Security\Models\User;

class TicketController extends Controller
{
    public function __construct(
        protected TicketWorkflowService $ticketWorkflowService,
        protected TicketCommentService $ticketCommentService,
    ) {}

    public function metadata(TicketMetadataRequest $request): JsonResponse
    {
        $user = $this->resolveUser($request->user());

        Gate::forUser($user)->authorize('viewAny', Ticket::class);

        $unitId = $request->integer('unit_id');

        $problemCategories = ProblemCategory::query()
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->orderBy('name')
            ->get(['id', 'unit_id', 'name', 'default_responsible_id'])
            ->map(fn (ProblemCategory $category): array => [
                'id'                     => $category->id,
                'unit_id'                => $category->unit_id,
                'name'                   => $category->name,
                'default_responsible_id' => $category->default_responsible_id,
            ])
            ->values()
            ->all();

        return response()->json([
            'message' => 'Ticket metadata retrieved successfully.',
            'data'    => [
                'boxes'      => $this->availableBoxes($user),
                'priorities' => Priority::query()
                    ->orderBy('id')
                    ->get(['id', 'name'])
                    ->map(fn (Priority $priority): array => [
                        'id'   => $priority->id,
                        'name' => $priority->name,
                    ])
                    ->values()
                    ->all(),
                'statuses' => TicketStatus::query()
                    ->orderBy('id')
                    ->get(['id', 'name'])
                    ->map(fn (TicketStatus $status): array => [
                        'id'   => $status->id,
                        'name' => $status->name,
                    ])
                    ->values()
                    ->all(),
                'units' => Unit::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Unit $unit): array => [
                        'id'   => $unit->id,
                        'name' => $unit->name,
                    ])
                    ->values()
                    ->all(),
                'problem_categories' => $problemCategories,
                'responsible_users'  => collect(TicketOptions::unitUserOptions($unitId))
                    ->map(fn (string $name, int|string $id): array => [
                        'id'   => (int) $id,
                        'name' => $name,
                    ])
                    ->values()
                    ->all(),
                'companies' => collect(TicketOptions::companyOptionsForUser($user))
                    ->map(fn (string $name, int|string $id): array => [
                        'id'   => (int) $id,
                        'name' => $name,
                    ])
                    ->values()
                    ->all(),
                'default_company_id' => TicketOptions::defaultCompanyIdForUser($user),
            ],
        ]);
    }

    public function index(TicketIndexRequest $request)
    {
        $user = $this->resolveUser($request->user());

        Gate::forUser($user)->authorize('viewAny', Ticket::class);

        $box = $this->resolveBox($request, $user);

        $query = Ticket::query()
            ->with([
                'priority',
                'unit',
                'problemCategory',
                'ticketStatus',
                'owner',
                'responsible',
                'company',
            ]);

        $this->applyBoxScope($query, $box, $user);

        if ($search = trim((string) $request->string('search'))) {
            $query->where(function ($builder) use ($search): void {
                if (is_numeric($search)) {
                    $builder->whereKey((int) $search)
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");

                    return;
                }

                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query
            ->when($request->filled('priority_id'), fn ($builder) => $builder->where('priority_id', $request->integer('priority_id')))
            ->when($request->filled('ticket_status_id'), fn ($builder) => $builder->where('ticket_status_id', $request->integer('ticket_status_id')))
            ->when($request->filled('unit_id'), fn ($builder) => $builder->where('unit_id', $request->integer('unit_id')))
            ->when($request->filled('problem_category_id'), fn ($builder) => $builder->where('problem_category_id', $request->integer('problem_category_id')))
            ->when($request->filled('responsible_id'), fn ($builder) => $builder->where('responsible_id', $request->integer('responsible_id')))
            ->latest('created_at');

        $tickets = $query
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return TicketResource::collection($tickets)->additional([
            'meta' => [
                'box'    => $box,
                'counts' => $this->ticketCounts($user),
            ],
        ]);
    }

    public function store(StoreTicketRequest $request)
    {
        $user = $this->resolveUser($request->user());

        Gate::forUser($user)->authorize('create', Ticket::class);

        $data = $request->validated();
        $data['owner_id'] = $user->id;
        $data['company_id'] = $data['company_id'] ?? TicketOptions::defaultCompanyIdForUser($user);
        $data['supporting_attachments'] = $this->storeUploadedFiles(
            $request->file('supporting_attachments', []),
            config('helpdesk.attachments.ticket.disk'),
            config('helpdesk.attachments.ticket.directory'),
        );

        $ticket = DB::transaction(function () use ($data): Ticket {
            return Ticket::query()->create($data);
        });

        $ticket->load($this->ticketDetailRelations());

        return (new TicketResource($ticket))
            ->additional([
                'message' => 'Ticket created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Ticket $ticket, TicketMetadataRequest $request): TicketResource
    {
        $user = $this->resolveUser($request->user());

        Gate::forUser($user)->authorize('view', $ticket);

        $ticket->load($this->ticketDetailRelations());

        return new TicketResource($ticket);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $user = $this->resolveUser($request->user());

        if ($request->exists('responsible_id') && ! $user->can('update_helpdesk_ticket')) {
            abort(403, 'You are not allowed to change ticket assignment.');
        }

        $data = $request->validated();

        if ($request->has('supporting_attachments') || $request->exists('existing_supporting_attachments')) {
            $data['supporting_attachments'] = array_values(array_merge(
                $data['existing_supporting_attachments'] ?? [],
                $this->storeUploadedFiles(
                    $request->file('supporting_attachments', []),
                    config('helpdesk.attachments.ticket.disk'),
                    config('helpdesk.attachments.ticket.directory'),
                ),
            ));
        }

        unset($data['existing_supporting_attachments']);

        if (($request->filled('problem_category_id') || $request->filled('unit_id')) && ! array_key_exists('responsible_id', $data)) {
            $data['responsible_id'] = null;
        }

        $this->authorizeRequestedTicketMutation($user, $ticket, $data);

        $ticket = $this->ticketWorkflowService->updateTicket($user, $ticket, $data);

        $ticket->load($this->ticketDetailRelations());

        return (new TicketResource($ticket))
            ->additional([
                'message' => 'Ticket updated successfully.',
            ]);
    }

    public function destroy(Ticket $ticket, TicketMetadataRequest $request): JsonResponse
    {
        $user = $this->resolveUser($request->user());

        Gate::forUser($user)->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json([
            'message' => 'Ticket deleted successfully.',
        ]);
    }

    public function storeComment(StoreTicketCommentRequest $request, Ticket $ticket): TicketResource
    {
        $user = $this->resolveUser($request->user());

        Gate::forUser($user)->authorize('comment', $ticket);

        $data = $request->validated();
        $data['attachments'] = $this->storeUploadedFiles(
            $request->file('attachments', []),
            config('helpdesk.attachments.comment.disk'),
            config('helpdesk.attachments.comment.directory'),
        );

        $this->ticketCommentService->create($user, $ticket, $data);

        $ticket = $ticket->fresh();
        $ticket->load($this->ticketDetailRelations());

        return (new TicketResource($ticket))
            ->additional([
                'message' => 'Ticket comment created successfully.',
            ]);
    }

    protected function resolveUser(mixed $user): User
    {
        abort_unless($user instanceof User, 403, 'Authenticated user is invalid.');

        return $user;
    }

    protected function resolveBox(TicketIndexRequest $request, User $user): string
    {
        if ($request->filled('box')) {
            return (string) $request->string('box');
        }

        if ($user->can('view_any_helpdesk_ticket') || $user->can('view_helpdesk_ticket') || $user->can('update_helpdesk_ticket')) {
            return 'incoming';
        }

        return 'outgoing';
    }

    protected function applyBoxScope($query, string $box, User $user): void
    {
        if ($box === 'all') {
            abort_unless($user->can('view_any_helpdesk_ticket'), 403, 'You are not allowed to access all tickets.');

            return;
        }

        if ($box === 'incoming') {
            $query->incomingFor($user);

            return;
        }

        $query->outgoingFor($user);
    }

    protected function ticketCounts(User $user): array
    {
        $counts = [
            'incoming' => Ticket::query()->incomingFor($user)->count(),
            'outgoing' => Ticket::query()->outgoingFor($user)->count(),
        ];

        if ($user->can('view_any_helpdesk_ticket')) {
            $counts['all'] = Ticket::query()->count();
        }

        return $counts;
    }

    protected function availableBoxes(User $user): array
    {
        $boxes = [
            ['key' => 'incoming', 'label' => 'Incoming'],
            ['key' => 'outgoing', 'label' => 'Outgoing'],
        ];

        if ($user->can('view_any_helpdesk_ticket')) {
            $boxes[] = ['key' => 'all', 'label' => 'All'];
        }

        return $boxes;
    }

    protected function authorizeRequestedTicketMutation(User $user, Ticket $ticket, array $data): void
    {
        $statusId = $data['ticket_status_id'] ?? null;
        $attributePayload = Arr::except($data, [
            'ticket_status_id',
            'close_reason',
            'cancel_reason',
            'reopen_reason',
        ]);

        if ($attributePayload !== []) {
            Gate::forUser($user)->authorize('update', $ticket);
        }

        if ($statusId === null) {
            if ($attributePayload === []) {
                Gate::forUser($user)->authorize('update', $ticket);
            }

            return;
        }

        match ((int) $statusId) {
            TicketStatus::CANCELLED => Gate::forUser($user)->authorize('cancel', $ticket),
            TicketStatus::CLOSED    => Gate::forUser($user)->authorize('close', $ticket),
            TicketStatus::OPEN      => $ticket->isStatus(TicketStatus::CLOSED)
                ? Gate::forUser($user)->authorize('reopen', $ticket)
                : Gate::forUser($user)->authorize('update', $ticket),
            default => Gate::forUser($user)->authorize('update', $ticket),
        };
    }

    protected function ticketDetailRelations(): array
    {
        return [
            'priority',
            'unit',
            'problemCategory',
            'ticketStatus',
            'owner',
            'responsible',
            'company',
            'comments'  => fn ($query) => $query->with('user')->orderBy('created_at'),
            'histories' => fn ($query) => $query->with(['user', 'ticketStatus'])->latest('created_at'),
        ];
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     * @return array<int, string>
     */
    protected function storeUploadedFiles(array|UploadedFile|null $files, string $disk, string $directory): array
    {
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_map(
            fn (UploadedFile $file): string => $file->store($directory, $disk),
            $files,
        ));
    }
}
