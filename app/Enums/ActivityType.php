<?php

namespace App\Enums;

enum ActivityType: string
{
    case Note = 'note';
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
