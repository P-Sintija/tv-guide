<?php

namespace Tests\Helpers;

use App\Enums\Channel;
use Carbon\Carbon;
use Illuminate\Testing\TestResponse;

trait WithGuideApiRoutes
{
    public function getGuide(Channel $channel, Carbon $date): TestResponse
    {
        return $this->getJson(route('guide', [
            'channel_nr' => $channel->value,
            'date' => $date->toDateString(),
        ]));
    }

    public function getOnAir(Channel $channel): TestResponse
    {
        return $this->getJson(route('on-air', [
            'channel_nr' => $channel->value
        ]));
    }

    public function getUpcoming(Channel $channel): TestResponse
    {
        return $this->getJson(route('upcoming', [
            'channel_nr' => $channel->value
        ]));
    }

    public function storeGuide(array $data, bool $withAuthHeaders = true): TestResponse
    {
        if ($withAuthHeaders) {
            $encodedCredentials = base64_encode(
                config('auth.basic_auth.username') . ':' . config('auth.basic_auth.password')
            );

            return $this->withHeaders([
                'Authorization' => 'Basic ' . $encodedCredentials,
            ])->postJson(route('store', $data));
        }

        return $this->postJson(route('store', $data));
    }
}
