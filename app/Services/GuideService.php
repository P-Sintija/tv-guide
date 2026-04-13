<?php

namespace App\Services;

use App\Enums\Channel;
use App\Models\Guide;
use App\Repositories\GuideRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class GuideService
{
    public const GUIDE_START_HOUR = 6;

    public const DATE_FORMAT = 'Y-m-d H:i:s';

    public const UPCOMING_COUNT = 10;

    public function __construct(private GuideRepository $guideRepository) 
    {
        
    }

    public function getChannelScheduleForDate(Channel $channel, string $date): Collection
    {
        [$start, $end] = $this->getDayRange($date);
        $guidesInRange = $this->guideRepository->getGuidesForChannelInRange($channel, $start);
        $guidesWithAdjustedEndTimes = $this->adjustEndTimes($guidesInRange);

        return $guidesWithAdjustedEndTimes->filter(function (Guide $guide) use ($start, $end) {
            return $guide->starts_at >= $start && $guide->starts_at < $end;
        });
    }

    public function getOnAirForChannel(Channel $channel): ?Guide
    {
        $time = now();
        $onAirGuide = $this->guideRepository->getGuideOnAirForChannel($channel, $time);

        if (!$onAirGuide) {
            return null;
        }

        $upcomingGuides = $this->getUpcoming($channel, $onAirGuide, $time);

        return !$upcomingGuides
            ? null
            : $this->adjustEndTimes($upcomingGuides)->first();
    }

    public function getUpcomingForChannel(Channel $channel): SupportCollection
    {
        $time = now();
        $onAirGuide = $this->guideRepository->getGuideOnAirForChannel($channel, $time);
        $count = $onAirGuide ? self::UPCOMING_COUNT : self::UPCOMING_COUNT + 1;
        $upcomingGuides = $this->getUpcoming($channel, $onAirGuide, $time, $count, true);

        return $this->adjustEndTimes($upcomingGuides)
            ->take(self::UPCOMING_COUNT);
    }

    public function create(array $attributes): Guide
    {
        $guide = Guide::create($attributes);
        $upcomingGuides = $this->guideRepository->getUpcomingGuidesForChannel($guide->channel_nr, $guide->ends_at);

        if ($upcomingGuides->isEmpty()) {
            return $guide;
        }

        $upcomingGuides->prepend($guide);

        return $this->adjustEndTimes($upcomingGuides)->first();
    }

    private function getDayRange(string $date): array
    {
        $start = Carbon::parse($date)->setTime(self::GUIDE_START_HOUR, 0);
        $end = $start->copy()->addDay();

        return [$start, $end];
    }

    private function adjustEndTimes(SupportCollection $guides): SupportCollection
    {
        $guides = $guides->values();

        return $guides->map(function (Guide $item, int $index) use ($guides) {
            $next = $guides[$index + 1] ?? null;
            $item->adjusted_ends_at = $next ? $next->starts_at : $item->ends_at;

            return $item;
        });
    }

    private function getUpcoming(
        Channel $channel,
        ?Guide $onAirGuide,
        Carbon $time,
        int $count = 1,
        bool $asCollection = false
    ): ?SupportCollection {
        $startsAt = $onAirGuide ? $onAirGuide->ends_at : $time;
        $upcomingGuides = $this->guideRepository->getUpcomingGuidesForChannel($channel, $startsAt, $count);

        if ($upcomingGuides->isEmpty() && $onAirGuide?->ends_at < $time) {
            return $asCollection ? collect() : null;
        }

        if ($onAirGuide) {
            $upcomingGuides->prepend($onAirGuide);
        }

        return $upcomingGuides;
    }
}
