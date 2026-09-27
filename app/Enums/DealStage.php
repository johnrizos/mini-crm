<?php

namespace App\Enums;

enum DealStage: string
{
    case New = 'new';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isClosed(): bool
    {
        return $this === self::Won || $this === self::Lost;
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $stage) => ! $stage->isClosed()));
    }
}
