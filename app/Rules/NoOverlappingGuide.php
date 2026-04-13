<?php

namespace App\Rules;

use App\Models\Guide;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoOverlappingGuide implements ValidationRule
{
    public function __construct(
        private ?int $channel,
        private ?string $start,
        private ?string $end,
    ) {
        
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Guide::query()
            ->where('channel_nr', $this->channel)
            ->where('starts_at', '<', $this->end)
            ->where('ends_at', '>', $this->start)
            ->exists();

        if($exists) {
             $fail('The given time range overlaps with an existing guide for this channel.');
        }    
    }
}
