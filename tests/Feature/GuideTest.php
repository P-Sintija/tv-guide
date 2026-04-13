<?php

namespace Tests\Feature;

use App\Enums\Channel;
use App\Models\Guide;
use App\Services\GuideService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\Helpers\WithAssertHelper;
use Tests\Helpers\WithGuideApiRoutes;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;
    use WithAssertHelper;
    use WithGuideApiRoutes;

    private Carbon $dayStart;

    private Carbon $dayEnd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dayStart =  now()->setTime(GuideService::GUIDE_START_HOUR, 0);
        $this->dayEnd = $this->dayStart->copy()->addDay();
    }

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
