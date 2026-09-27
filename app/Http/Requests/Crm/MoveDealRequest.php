<?php

namespace App\Http\Requests\Crm;

use App\Enums\DealStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;

class MoveDealRequest extends CrmRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'stage' => ['required', new Enum(DealStage::class)],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }
}
