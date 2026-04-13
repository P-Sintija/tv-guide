<?php

namespace Tests\Feature;

use App\Enums\Channel;
use App\Models\Guide;
use Illuminate\Http\Response;
use Tests\GuideTestCase;

class GetUpcomingTest extends GuideTestCase
{
    public function test_upcoming_request_with_non_existing_channel(): void
    {
        $this->getJson(route('upcoming', [
            'channel_nr' => 6
        ]))->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_upcoming_for_channel_with_no_guides(): void
    {
        $this->getUpcoming(Channel::ONE)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_returns_upcoming_guides_when_nothing_is_on_air(): void
    {
        $todaysFirstGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy(),
            'ends_at' => $this->dayStart->copy()->addHour(),
        ]);

        $todaysSecondGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy()->addHour(),
            'ends_at' => $this->dayStart->copy()->addHours(2),
        ]);

        $this->travelTo($this->dayStart->copy()->subSecond());

        $responseData = $this->getUpcoming(Channel::TWO)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        [$firstGuide, $lastGuide] = $responseData->json('data');
        $this->assertGuide($todaysFirstGuide, $firstGuide, $todaysFirstGuide->ends_at);
        $this->assertGuide($todaysSecondGuide, $lastGuide, $todaysSecondGuide->ends_at);
    }

    public function test_returns_upcoming_guides_including_current_when_on_air(): void
    {
        $todaysFirstGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy(),
            'ends_at' => $this->dayStart->copy()->addHour(),
        ]);

        $todaysSecondGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $todaysFirstGuide->ends_at->copy()->addMinute(),
            'ends_at' => $todaysFirstGuide->ends_at->copy()->addHour(),
        ]);

        $tomorrowGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayEnd->copy()->addHour(),
            'ends_at' => $this->dayEnd->copy()->addHours(4),
        ]);

        $this->travelTo($todaysSecondGuide->starts_at->copy()->subSecond());

        $responseData = $this->getUpcoming(Channel::TWO)
            ->assertOk()
            ->assertJsonCount(3, 'data');

        [$firstGuide, $secondGuide, $lastGuide] = $responseData->json('data');
        $this->assertGuide($todaysFirstGuide, $firstGuide, $todaysSecondGuide->starts_at);
        $this->assertGuide($todaysSecondGuide, $secondGuide, $tomorrowGuide->starts_at);
        $this->assertGuide($tomorrowGuide, $lastGuide, $tomorrowGuide->ends_at);
    }

    public function test_returns_empty_when_there_are_no_upcoming_guides(): void
    {
        $guide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy(),
            'ends_at' => $this->dayStart->copy()->addHour(),
        ]);

        $this->travelTo($guide->ends_at->copy()->addSecond());

        $this->getUpcoming(Channel::TWO)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
