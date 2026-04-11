<?php

namespace App\Http\Controllers\Api;

use App\Enums\Channel;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetGuideRequest;
use App\Http\Resources\GuideResource;
use App\Services\GuideService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GuideController extends Controller
{
    public function __construct(private GuideService $guideService) 
    {

    }

    public function guide(GetGuideRequest $request): AnonymousResourceCollection
    {
        $channel = Channel::from($request->validated()['channel_nr']);
        $date = Carbon::parse($request->validated()['date']);
        $guides = $this->guideService->getChannelScheduleForDate($channel, $date);

        return GuideResource::collection($guides);
    }
}
