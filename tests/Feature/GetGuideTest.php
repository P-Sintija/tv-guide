<?php

namespace Tests\Feature;

use App\Enums\Channel;
use App\Models\Guide;
use Illuminate\Http\Response;
use Tests\GuideTestCase;

class GetGuideTest extends GuideTestCase
{
    public function test_guide_request_with_non_existing_channel(): void
    {
        $this->getJson(route('guide', [
            'channel_nr' => 6,
            'date' => now()->toDateString(),
        ]))->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_guide_request_validation(): void
    {
        $this->getJson(route('guide', [
            'channel_nr' => Channel::ONE,
            'date' => 'date',
        ]))->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'date',
            ], 'errors');
    }

    public function test_returns_guides_with_adjusted_end_times_for_a_specific_day(): void
    {
        $previousGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy()->subMinutes(2),
            'ends_at' => $this->dayStart->copy()->addMinutes(5),
        ]);

        $todaysFirstGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy(),
            'ends_at' => $this->dayStart->copy()->addHour(),
        ]);

        $todaysSecondGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $todaysFirstGuide->ends_at->copy(),
            'ends_at' => $todaysFirstGuide->ends_at->copy()->addHour(),
        ]);

        $tomorrowGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayEnd->copy()->addHour(),
            'ends_at' => $this->dayEnd->copy()->addHours(4),
        ]);

        $responseData = $this->getGuide(Channel::TWO, now())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['id' => $previousGuide->id])
            ->assertJsonMissing(['id' => $tomorrowGuide->id]);

        [$firstGuide, $lastGuide] = $responseData->json('data');
        $this->assertGuide($todaysFirstGuide, $firstGuide, $todaysFirstGuide->ends_at);
        $this->assertGuide($todaysSecondGuide, $lastGuide, $tomorrowGuide->starts_at);
    }

    public function test_guide_for_channel_with_no_guides(): void
    {
        $this->getGuide(Channel::ONE, now())
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_guide_for_channel_with_ended_guides(): void
    {
        Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $this->dayStart->copy()->subMinutes(2),
            'ends_at' => $this->dayStart->copy()->addMinutes(5),
        ]);

        $this->getGuide(Channel::TWO, now())
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
