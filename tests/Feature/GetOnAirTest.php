<?php

namespace Tests\Feature;

use App\Enums\Channel;
use App\Models\Guide;
use Illuminate\Http\Response;
use Tests\GuideTestCase;

class GetOnAirTest extends GuideTestCase
{
    public function test_on_air_request_with_non_existing_channel(): void
    {
        $this->getJson(route('on-air', [
            'channel_nr' => 6
        ]))->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_on_air_for_channel_with_no_guides(): void
    {
        $this->getOnAir(Channel::ONE)
            ->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function test_returns_no_content_when_nothing_is_on_air(): void
    {
        $guide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy()->subMinutes(2),
            'ends_at' => $this->dayStart->copy()->addMinutes(5),
        ]);

        $this->travelTo($guide->ends_at->copy()->addSecond());

        $this->getOnAir(Channel::TWO)
            ->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function test_returns_guide_when_it_is_currently_on_air(): void
    {
        $guide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy(),
            'ends_at' => $this->dayStart->copy()->addHour(),
        ]);

        $nextGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $guide->ends_at->copy()->addHour(),
            'ends_at' => $guide->ends_at->copy()->addHours(3),
        ]);

        $this->travelTo($guide->starts_at->copy()->addMinutes(37));

        $responseData = $this->getOnAir(Channel::TWO)
            ->assertOk();

        $guideData = $responseData->json('data');
        $this->assertGuide($guide, $guideData, $nextGuide->starts_at);
    }

    public function test_returns_current_guide_when_next_guide_has_not_started_yet(): void
    {
        $guide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy(),
            'ends_at' => $this->dayStart->copy()->addHour(),
        ]);

        $nextGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $guide->ends_at->copy()->addHour(),
            'ends_at' => $guide->ends_at->copy()->addHours(3),
        ]);

        $this->travelTo($guide->ends_at->copy()->addMinutes(5));

        $responseData = $this->getOnAir(Channel::TWO)
            ->assertOk();

        $guideData = $responseData->json('data');
        $this->assertGuide($guide, $guideData, $nextGuide->starts_at);
    }
}
