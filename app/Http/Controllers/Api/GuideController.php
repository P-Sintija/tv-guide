<?php

namespace App\Http\Controllers\Api;

use App\Enums\Channel;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetGuideRequest;
use App\Http\Requests\StoreGuideRequest;
use App\Http\Resources\GuideResource;
use App\Services\GuideService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GuideController extends Controller
{
    public function __construct(private GuideService $guideService) 
    {

    }

    public function guide(GetGuideRequest $request, Channel $channel_nr): AnonymousResourceCollection
    {
        $date = Carbon::parse($request->validated()['date']);
        $guides = $this->guideService->getChannelScheduleForDate($channel_nr, $date);

        return GuideResource::collection($guides);
    }

    public function onAir(Channel $channel_nr): GuideResource|Response
    {
        $guide = $this->guideService->getOnAirForChannel($channel_nr);

        return !$guide ? response()->noContent() : new GuideResource($guide);
    }

    public function upcoming(Channel $channel_nr): AnonymousResourceCollection
    {
        $guides = $this->guideService->getUpcomingForChannel($channel_nr);

        return GuideResource::collection($guides);
    }

    public function store(StoreGuideRequest $request): GuideResource
    {
        $guide = $this->guideService->create($request->validated());

        return new GuideResource($guide);
    }
}
