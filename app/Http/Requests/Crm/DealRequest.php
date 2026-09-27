<?php

namespace App\Http\Requests\Crm;

use App\Enums\DealStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Enum;

class DealRequest extends CrmRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // Entered in euros; stored in cents.
            'value' => ['required', 'numeric', 'min:0', 'max:100000000', 'decimal:0,2'],
            'stage' => ['required', new Enum(DealStage::class)],
            'contact_id' => ['nullable', 'integer', $this->owned('contacts')],
            'company_id' => ['nullable', 'integer', $this->owned('companies')],
            'expected_close_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array{title: string, value_cents: int, stage: DealStage, contact_id: int|null, company_id: int|null, expected_close_date: string|null}
     */
    public function dealAttributes(): array
    {
        return [
            'title' => $this->string('title')->toString(),
            'value_cents' => (int) round($this->float('value') * 100),
            'stage' => $this->enum('stage', DealStage::class) ?? DealStage::New,
            'contact_id' => $this->filled('contact_id') ? $this->integer('contact_id') : null,
            'company_id' => $this->filled('company_id') ? $this->integer('company_id') : null,
            'expected_close_date' => $this->filled('expected_close_date') ? $this->string('expected_close_date')->toString() : null,
        ];
    }
}
