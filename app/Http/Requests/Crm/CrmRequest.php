<?php

namespace App\Http\Requests\Crm;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

/**
 * Base for CRM form requests. Authorization happens in the controllers through
 * policies; this adds rules that keep ids and unique checks inside the
 * current user's own records.
 */
abstract class CrmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function owner(): User
    {
        /** @var User */
        return $this->user();
    }

    /**
     * The id must exist AND belong to the current user. A plain `exists` rule
     * would let anyone attach another account's company by guessing its id.
     */
    protected function owned(string $table): Exists
    {
        return (new Exists($table, 'id'))->where('user_id', $this->owner()->id);
    }

    protected function uniqueForOwner(string $table, string $column, ?int $ignoreId = null): Unique
    {
        return (new Unique($table, $column))
            ->where('user_id', $this->owner()->id)
            ->ignore($ignoreId);
    }
}
