<?php

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'industry' => $this->industry,
            'contacts_count' => $this->whenCounted('contacts'),
            'open_deals_value_cents' => $this->whenHas('open_deals_value_cents', fn () => (int) $this->getAttribute('open_deals_value_cents')),
        ];
    }
}
