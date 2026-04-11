<?php

namespace App\Services;

use App\Enums\Channel;
use App\Models\Guide;
use App\Repositories\GuideRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class GuideService
{
    public const GUIDE_START_HOUR = 6;

    public const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private GuideRepository $guideRepository) 
    {
        
    }

    public function getChannelScheduleForDate(Channel $channel, string $date): Collection
    {
        [$start, $end] = $this->getDayRange($date);
        $guidesInRange = $this->guideRepository->getGuidesForChannelInRange($channel, $start, $end);
        $guidesWithAdjustedEndTimes = $this->adjustEndTimes($guidesInRange);

        return $guidesWithAdjustedEndTimes->filter(function (Guide $guide) use ($start, $end) {
            return $guide->starts_at >= $start && $guide->starts_at < $end;
        });
    }

    public function getDayRange(string $date): array
    {
        $start = Carbon::parse($date)->setTime(self::GUIDE_START_HOUR, 0);
        $end = $start->copy()->addDay();

        return [$start, $end];
    }

    private function adjustEndTimes(Collection $guides): Collection
    {
        return $guides->values()
            ->map(function (Guide $item, int $index) use ($guides) {
                $next = $guides[$index + 1] ?? null;
                $item->adjusted_ends_at = $next ? $next->starts_at : $item->ends_at;

                return $item;
            });
    }
}
