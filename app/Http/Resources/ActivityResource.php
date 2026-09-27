<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'body' => $this->body,
            'happened_at' => $this->happened_at->toIso8601String(),
            'contact' => $this->whenLoaded('contact', fn () => [
                'id' => $this->contact->id,
                'full_name' => $this->contact->full_name,
            ]),
            'deal' => $this->whenLoaded('deal', fn () => $this->deal ? [
                'id' => $this->deal->id,
                'title' => $this->deal->title,
            ] : null),
        ];
    }
}
