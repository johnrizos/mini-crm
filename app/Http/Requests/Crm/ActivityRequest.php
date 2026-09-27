<?php

namespace App\Http\Requests\Crm;

use App\Enums\ActivityType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;

class ActivityRequest extends CrmRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(ActivityType::class)],
            'body' => ['required', 'string', 'max:5000'],
            'happened_at' => ['nullable', 'date', 'before_or_equal:now'],
            'deal_id' => ['nullable', 'integer', $this->owned('deals')],
        ];
    }
}
