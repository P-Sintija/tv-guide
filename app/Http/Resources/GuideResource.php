<?php

namespace App\Http\Resources;

use App\Services\GuideService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $endsAt = $this->ends_at->format(GuideService::DATE_FORMAT);
        $adjustedEndsAt = $this->adjusted_ends_at?->format(GuideService::DATE_FORMAT) ?? $endsAt;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'channel_nr' => (int)$this->channel_nr->value,
            'starts_at' => $this->starts_at->format(GuideService::DATE_FORMAT),
            'ends_at' => $endsAt,
            'adjusted_ends_at' => $adjustedEndsAt,
        ];
    }
}
