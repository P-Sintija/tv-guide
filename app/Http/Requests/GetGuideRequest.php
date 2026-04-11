<?php

namespace App\Http\Requests;

use App\Enums\Channel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class GetGuideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel_nr' => ['required', new Enum(Channel::class)],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel_nr' => $this->route('channel_nr'),
            'date' => $this->route('date'),
        ]);
    }
}
