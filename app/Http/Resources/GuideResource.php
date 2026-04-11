<?php

namespace App\Http\Resources;

use App\Services\GuideService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'channel_nr' => $this->channel_nr->value,
            'starts_at' => $this->starts_at->format(GuideService::DATE_FORMAT),
            'ends_at' => $this->ends_at->format(GuideService::DATE_FORMAT),
            'adjusted_ends_at' => $this->adjusted_ends_at->format(GuideService::DATE_FORMAT),
        ];
    }
}
