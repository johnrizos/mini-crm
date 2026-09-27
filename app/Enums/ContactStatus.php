<?php

namespace App\Enums;

enum ContactStatus: string
{
    case Lead = 'lead';
    case Prospect = 'prospect';
    case Customer = 'customer';
    case Churned = 'churned';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
