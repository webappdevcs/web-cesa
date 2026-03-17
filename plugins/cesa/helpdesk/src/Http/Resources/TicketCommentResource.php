<?php

namespace Cesa\Helpdesk\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TicketCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'ticket_id'   => $this->ticket_id,
            'comment'     => $this->comment,
            'visibility'  => $this->visibility,
            'attachments' => $this->mapAttachments($this->attachments ?? []),
            'user'        => $this->whenLoaded('user', fn (): array => [
                'id'         => $this->user?->id,
                'name'       => $this->user?->name,
                'email'      => $this->user?->email,
                'avatar_url' => $this->user?->avatar_url,
            ]),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, string>  $attachments
     * @return array<int, array<string, string|null>>
     */
    protected function mapAttachments(array $attachments): array
    {
        $disk = config('helpdesk.attachments.comment.disk');
        $visibility = config('helpdesk.attachments.comment.visibility');

        return array_values(array_map(function (string $path) use ($disk, $visibility): array {
            return [
                'name' => basename($path),
                'path' => $path,
                'url'  => $visibility === 'public' ? Storage::disk($disk)->url($path) : null,
            ];
        }, $attachments));
    }
}
