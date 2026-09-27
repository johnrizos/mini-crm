<?php

namespace App\Http\Requests\Crm;

use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;

class CompanyRequest extends CrmRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'name' => [
                'required', 'string', 'max:255',
                $this->uniqueForOwner('companies', 'name', $company instanceof Company ? $company->id : null),
            ],
            'domain' => ['nullable', 'string', 'max:255', 'regex:/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/i'],
            'industry' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'You already have a company with this name.',
            'domain.regex' => 'Enter a domain like example.com, without https://.',
        ];
    }
}
