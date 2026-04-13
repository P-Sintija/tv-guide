<?php

namespace Tests\Helpers;

use App\Enums\Channel;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;

trait WithGuideApiRoutes
{
    private function getGuide(Channel $channel, Carbon $date): TestResponse
    {
        return $this->getJson(route('guide', [
            'channel_nr' => $channel->value,
            'date' => $date->toDateString(),
        ]));
    }

    private function getOnAir(Channel $channel): TestResponse
    {
        return $this->getJson(route('on-air', [
            'channel_nr' => $channel->value
        ]));
    }

    private function getUpcoming(Channel $channel): TestResponse
    {
        return $this->getJson(route('upcoming', [
            'channel_nr' => $channel->value
        ]));
    }
}
