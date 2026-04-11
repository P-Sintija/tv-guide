<?php

namespace App\Repositories;

use App\Enums\Channel;
use App\Models\Guide;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class GuideRepository
{
    public function getGuidesForChannelInRange(Channel $channel, Carbon $start, Carbon $end): Collection
    {
        return Guide::where('channel_nr', $channel->value)
            ->where('starts_at', '<', $end->copy()->addDay())
            ->where('ends_at', '>', $start)
            ->orderBy('starts_at')
            ->get();
    }
}
