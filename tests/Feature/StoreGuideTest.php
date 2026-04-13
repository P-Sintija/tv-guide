<?php

namespace Tests\Feature;

use App\Enums\Channel;
use App\Models\Guide;
use Illuminate\Http\Response;
use Tests\GuideTestCase;

class StoreGuideTest extends GuideTestCase
{
    public Guide $existingGuide;

    protected function setUp(): void
    {
        parent::setUp();

        $this->existingGuide = Guide::factory()->createOne([
            'channel_nr' => Channel::TWO,
            'starts_at' => '2026-01-01 10:00:00',
            'ends_at' => '2026-01-01 11:00:00',
        ]);
    }

    public function test_returns_unauthorized_with_no_valid_credentials(): void
    {
        $guide = Guide::factory()->makeOne();

        $this->storeGuide($guide->getAttributes(), false)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_guide_title_required_validation(): void
    {
        $guide = Guide::factory()->makeOne();
        $data = $guide->getAttributes();
        unset($data['title']);

        $this->storeGuide($data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('title', 'errors');
    }

    public function test_guide_title_length_validation(): void
    {
        $guide = Guide::factory()->makeOne();
        $data = $guide->getAttributes();
        $data['title'] = str_repeat('t', Guide::MAX_TITLE_LENGTH + 1);

        $this->storeGuide($data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('title', 'errors');
    }

    public function test_guide_channel_number_required_validation(): void
    {
        $guide = Guide::factory()->makeOne();
        $data = $guide->getAttributes();
        unset($data['channel_nr']);

        $this->storeGuide($data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('channel_nr', 'errors');
    }

    public function test_guide_channel_number_enum_validation(): void
    {
        $guide = Guide::factory()->makeOne();
        $data = $guide->getAttributes();
        $data['channel_nr'] = 6;

        $this->storeGuide($data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('channel_nr', 'errors');
    }

    public function test_dates_required_validation(): void
    {
        $guide = Guide::factory()->makeOne();
        $data = $guide->getAttributes();
        $data['starts_at'] = '';
        unset($data['ends_at']);

        $this->storeGuide($data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'starts_at',
                'ends_at',
            ], 'errors');
    }

    public function test_date_format_validation(): void
    {
        $guide = Guide::factory()->makeOne();
        $data = $guide->getAttributes();
        $data['starts_at'] = 'test';
        $data['ends_at'] = '2023-01-01';

        $this->storeGuide($data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'starts_at',
                'ends_at',
            ], 'errors');
    }

    public function test_date_comparison_validation(): void
    {
        $guide = Guide::factory()->makeOne();
        $data = $guide->getAttributes();
        $data['starts_at'] = '2026-01-01 12:30:00';
        $data['ends_at'] = '2026-01-01 07:30:00';

        $this->storeGuide($data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'ends_at',
            ], 'errors');
    }

    public function test_starts_inside_existing_guide_validation(): void
    {
        $guide = Guide::factory()->makeOne([
            'channel_nr' => $this->existingGuide->channel_nr,
            'starts_at' => $this->existingGuide->starts_at->copy()->addMinute(),
            'ends_at' => $this->existingGuide->ends_at->copy()->addMinutes(30),
        ]);

        $this->storeGuide($guide->getAttributes())
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('ends_at', 'errors');
    }

    public function test_ends_inside_existing_guide_validation(): void
    {
        $guide = Guide::factory()->makeOne([
            'channel_nr' => $this->existingGuide->channel_nr,
            'starts_at' => $this->existingGuide->starts_at->copy()->subMinutes(30),
            'ends_at' => $this->existingGuide->ends_at->copy()->subMinute(),
        ]);

        $this->storeGuide($guide->getAttributes())
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('ends_at', 'errors');
    }

    public function test_starts_and_ends_inside_existing_guide_validation(): void
    {
        $guide = Guide::factory()->makeOne([
            'channel_nr' => $this->existingGuide->channel_nr,
            'starts_at' => $this->existingGuide->starts_at->copy()->addMinute(),
            'ends_at' => $this->existingGuide->ends_at->copy()->subMinute(),
        ]);

        $this->storeGuide($guide->getAttributes())
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('ends_at', 'errors');
    }

    public function test_covers_an_existing_guide_validation(): void
    {
        $guide = Guide::factory()->makeOne([
            'channel_nr' => $this->existingGuide->channel_nr,
            'starts_at' => $this->existingGuide->starts_at->copy()->subMinutes(30),
            'ends_at' => $this->existingGuide->ends_at->copy()->addMinutes(30),
        ]);

        $this->storeGuide($guide->getAttributes())
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('ends_at', 'errors');
    }

    public function test_stores_new_guide_before_an_existing_guide(): void
    {
        $guide = Guide::factory()->makeOne([
            'channel_nr' => $this->existingGuide->channel_nr,
            'starts_at' => $this->existingGuide->starts_at->copy()->subHours(2),
            'ends_at' => $this->existingGuide->starts_at->copy()->subMinutes(30),
        ]);

        $this->assertCount(1, Guide::all());

        $responseData = $this->storeGuide($guide->getAttributes())
            ->assertStatus(Response::HTTP_CREATED);

        $createdGuide = Guide::orderByDesc('id')->first();
        $responseGuideData = $responseData->json('data');

        $this->assertCount(2, Guide::all());
        $this->assertGuide($createdGuide, $responseGuideData, $this->existingGuide->starts_at);
    }

    public function test_stores_new_guide_after_an_existing_guide(): void
    {
        $guide = Guide::factory()->makeOne([
            'channel_nr' => $this->existingGuide->channel_nr,
            'starts_at' => $this->existingGuide->ends_at->copy()->addMinutes(30),
            'ends_at' => $this->existingGuide->ends_at->copy()->addMinutes(55),
        ]);

        $this->assertCount(1, Guide::all());

        $responseData = $this->storeGuide($guide->getAttributes())
            ->assertStatus(Response::HTTP_CREATED);

        $createdGuide = Guide::orderByDesc('id')->first();
        $responseGuideData = $responseData->json('data');

        $this->assertCount(2, Guide::all());
        $this->assertGuide($createdGuide, $responseGuideData, $createdGuide->ends_at);
    }
}
