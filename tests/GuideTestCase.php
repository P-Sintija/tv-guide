<?php

namespace Tests;

use App\Services\GuideService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\WithAssertHelper;
use Tests\Helpers\WithGuideApiRoutes;

class GuideTestCase extends TestCase
{
    use RefreshDatabase;
    use WithAssertHelper;
    use WithGuideApiRoutes;

    public Carbon $dayStart;

    public Carbon $dayEnd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dayStart =  now()->setTime(GuideService::GUIDE_START_HOUR, 0);
        $this->dayEnd = $this->dayStart->copy()->addDay();
    }
}
