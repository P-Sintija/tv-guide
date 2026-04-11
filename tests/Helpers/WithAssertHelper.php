<?php

namespace Tests\Helpers;

use App\Models\Guide;
use Carbon\Carbon;

trait WithAssertHelper
{
    private function assertGuide(Guide $expected, array $actual, Carbon $adjustedEndsAt): void
    {
        $this->assertEquals($expected->id, $actual['id']);
        $this->assertEquals($expected->title, $actual['title']);
        $this->assertEquals($expected->channel_nr->value, $actual['channel_nr']);
        $this->assertEquals($expected->starts_at, $actual['starts_at']);
        $this->assertEquals($expected->ends_at, $actual['ends_at']);
        $this->assertEquals($adjustedEndsAt, $actual['adjusted_ends_at']);
    }
}
