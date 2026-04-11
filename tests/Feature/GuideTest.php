<?php

namespace Tests\Feature;

use App\Enums\Channel;
use App\Models\Guide;
use App\Services\GuideService;
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

    private GuideService $guideService;

    private Guide $channelOneGuide;

    private Guide $previousGuide;

    private Guide $todayFirstGuide;

    private Guide $todaySecondGuide;

    private Guide $todayLastGuide;

    private Guide $tomorrowGuide;

    private Guide $dayAfterTomorrowGuide;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guideService = $this->app->make(GuideService::class);
        [$start, $end] = $this->guideService->getDayRange(now());

        $this->channelOneGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::ONE->value,
        ]);
        $this->previousGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $start->copy()->subMinutes(2),
            'ends_at' => $start->copy(),
        ]);
        $this->todayFirstGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $start->copy(),
            'ends_at' => $start->copy()->addHour(),
        ]);
        $this->todaySecondGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $start->copy()->addHours(5)->addMinutes(37),
            'ends_at' => $start->copy()->addHours(7),
        ]);
        $this->todayLastGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $end->copy()->subSecond(),
            'ends_at' => $end->copy()->addSecond(),
        ]);
        $this->tomorrowGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $end->copy()->addMinute(),
            'ends_at' => $end->copy()->addDay(),
        ]);
        $this->dayAfterTomorrowGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO->value,
            'starts_at' => $end->copy()->addDay()->addMinute(),
            'ends_at' => $end->copy()->addDay()->addHour(),
        ]);
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

    public function test_can_get_guide_for_today(): void
    {
        $this->getGuide(Channel::ONE, now())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->channelOneGuide->id]);

        $this->getGuide(Channel::TWO, now())
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['id' => $this->todayFirstGuide->id])
            ->assertJsonFragment(['id' => $this->todaySecondGuide->id])
            ->assertJsonFragment(['id' => $this->todayLastGuide->id]);
    }

    public function test_can_get_next_day_guide(): void
    {
        $this->getGuide(Channel::TWO, now()->addDay())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->tomorrowGuide->id]);
    }

    public function test_excludes_out_of_range_guides(): void
    {
        $this->getGuide(Channel::ONE, now()->addDay())
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getGuide(Channel::TWO, now())
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing(['id' => $this->previousGuide->id])
            ->assertJsonMissing(['id' => $this->tomorrowGuide->id])
            ->assertJsonMissing(['id' => $this->dayAfterTomorrowGuide->id]);
    }

    public function test_adjusts_end_times_correctly(): void
    {
        $data = $this->getGuide(Channel::ONE, now())->json();
        $this->assertGuide($this->channelOneGuide, $data['data'][0], $this->channelOneGuide->ends_at);

        $todayData = $this->getGuide(Channel::TWO, now())->json();
        [$firstGuide, $secondGuide, $lastGuide] = $todayData['data'];
        $this->assertGuide($this->todayFirstGuide, $firstGuide, $this->todaySecondGuide->starts_at);
        $this->assertGuide($this->todaySecondGuide, $secondGuide, $this->todayLastGuide->starts_at);
        $this->assertGuide($this->todayLastGuide, $lastGuide, $this->tomorrowGuide->starts_at);

        $tomorrowData = $this->getGuide(Channel::TWO, now()->addDay())->json();
        $this->assertGuide($this->tomorrowGuide, $tomorrowData['data'][0], $this->dayAfterTomorrowGuide->starts_at);

        $dayAfterTomorrowData = $this->getGuide(Channel::TWO, now()->addDays(2))->json();
        $this->assertGuide($this->dayAfterTomorrowGuide, $dayAfterTomorrowData['data'][0], $this->dayAfterTomorrowGuide->ends_at);
    }
}
