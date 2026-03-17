<?php

namespace Cesa\Helpdesk\Http\Resources;

use Cesa\Helpdesk\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Webkul\Security\Models\User;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'description'         => $this->description,
            'priority_id'         => $this->priority_id,
            'unit_id'             => $this->unit_id,
            'owner_id'            => $this->owner_id,
            'problem_category_id' => $this->problem_category_id,
            'company_id'          => $this->company_id,
            'ticket_status_id'    => $this->ticket_status_id,
            'responsible_id'      => $this->responsible_id,
            'approved_at'         => $this->approved_at?->toIso8601String(),
            'solved_at'           => $this->solved_at?->toIso8601String(),
            'close_reason'        => $this->close_reason,
            'cancel_reason'       => $this->cancel_reason,
            'reopen_reason'       => $this->reopen_reason,
            'attachments'         => $this->mapAttachments($this->supporting_attachments ?? []),
            'priority'            => $this->whenLoaded('priority', fn (): array => [
                'id'   => $this->priority?->id,
                'name' => $this->priority?->name,
            ]),
            'unit'                => $this->whenLoaded('unit', fn (): array => [
                'id'   => $this->unit?->id,
                'name' => $this->unit?->name,
            ]),
            'problem_category'    => $this->whenLoaded('problemCategory', fn (): array => [
                'id'   => $this->problemCategory?->id,
                'name' => $this->problemCategory?->name,
            ]),
            'company'             => $this->whenLoaded('company', fn (): array => [
                'id'   => $this->company?->id,
                'name' => $this->company?->name,
            ]),
            'ticket_status'       => $this->whenLoaded('ticketStatus', fn (): array => [
                'id'   => $this->ticketStatus?->id,
                'name' => $this->ticketStatus?->name,
            ]),
            'owner'               => $this->whenLoaded('owner', fn (): array => [
                'id'         => $this->owner?->id,
                'name'       => $this->owner?->name,
                'email'      => $this->owner?->email,
                'avatar_url' => $this->owner?->avatar_url,
            ]),
            'responsible'         => $this->whenLoaded('responsible', fn (): ?array => $this->responsible ? [
                'id'         => $this->responsible->id,
                'name'       => $this->responsible->name,
                'email'      => $this->responsible->email,
                'avatar_url' => $this->responsible->avatar_url,
            ] : null),
            'comments'            => TicketCommentResource::collection($this->visibleComments($user)),
            'histories'           => TicketHistoryResource::collection($this->whenLoaded('histories')),
            'abilities'           => $user ? [
                'view'               => Gate::forUser($user)->allows('view', $this->resource),
                'update'             => Gate::forUser($user)->allows('update', $this->resource),
                'delete'             => Gate::forUser($user)->allows('delete', $this->resource),
                'comment'            => Gate::forUser($user)->allows('comment', $this->resource),
                'change_status'      => Gate::forUser($user)->allows('update', $this->resource)
                    || Gate::forUser($user)->allows('cancel', $this->resource)
                    || Gate::forUser($user)->allows('close', $this->resource)
                    || Gate::forUser($user)->allows('reopen', $this->resource),
                'assign_responsible' => $user->can('update_helpdesk_ticket') && Gate::forUser($user)->allows('update', $this->resource),
                'cancel'             => Gate::forUser($user)->allows('cancel', $this->resource),
                'close'              => Gate::forUser($user)->allows('close', $this->resource),
                'reopen'             => Gate::forUser($user)->allows('reopen', $this->resource),
                'add_internal_note'  => Gate::forUser($user)->allows('addInternalNote', $this->resource),
            ] : null,
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }

    protected function visibleComments(?User $user): Collection
    {
        $comments = $this->whenLoaded('comments', fn (): Collection => $this->comments);

        if (! $comments instanceof Collection) {
            return collect();
        }

        if ($user && Gate::forUser($user)->allows('viewInternalNotes', $this->resource)) {
            return $comments;
        }

        return $comments
            ->where('visibility', Comment::VISIBILITY_PUBLIC)
            ->values();
    }

    /**
     * @param  array<int, string>  $attachments
     * @return array<int, array<string, string|null>>
     */
    protected function mapAttachments(array $attachments): array
    {
        $disk = config('helpdesk.attachments.ticket.disk');
        $visibility = config('helpdesk.attachments.ticket.visibility');

        return array_values(array_map(function (string $path) use ($disk, $visibility): array {
            return [
                'name' => basename($path),
                'path' => $path,
                'url'  => $visibility === 'public' ? Storage::disk($disk)->url($path) : null,
            ];
        }, $attachments));
    }
}
