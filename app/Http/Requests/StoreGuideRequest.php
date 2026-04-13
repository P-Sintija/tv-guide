<?php

namespace App\Http\Requests;

use App\Enums\Channel;
use App\Models\Guide;
use App\Rules\NoOverlappingGuide;
use App\Services\GuideService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|max:' . Guide::MAX_TITLE_LENGTH,
            'channel_nr' => [
                'required',
                'integer',
                new Enum(Channel::class),
            ],
            'starts_at' => [
                'required',
                Rule::date()->format(GuideService::DATE_FORMAT),
            ],
            'ends_at' => [
                'bail',
                'required',
                Rule::date()->format(GuideService::DATE_FORMAT),
                'after:starts_at',
                new NoOverlappingGuide($this->input('channel_nr'), $this->input('starts_at'), $this->input('ends_at')),
            ],
        ];
    }
}
