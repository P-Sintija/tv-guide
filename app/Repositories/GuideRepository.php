<?php

namespace App\Repositories;

use App\Enums\Channel;
use App\Models\Guide;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class GuideRepository
{
    public function getGuidesForChannelInRange(Channel $channel, Carbon $start): Collection
    {
        return Guide::where('channel_nr', $channel->value)
            ->where('starts_at', '>=', $start)
            ->orderBy('starts_at')
            ->get();
    }

    public function getGuideOnAirForChannel(Channel $channel, Carbon $time): ?Guide
    {
        return Guide::where('channel_nr', $channel->value)
            ->where('starts_at', '<=', $time)
            ->orderByDesc('starts_at')
            ->first();
    }

    public function getUpcomingGuidesForChannel(Channel $channel, Carbon $time, int $count): Collection
    {
        return Guide::where('channel_nr', $channel->value)
            ->where('starts_at', '>=', $time)
            ->orderBy('starts_at')
            ->take($count)
            ->get();
    }
}
