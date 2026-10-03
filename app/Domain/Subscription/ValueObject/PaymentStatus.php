<?php

namespace App\Domain\Subscription\ValueObject;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';

    /** The invoice was not paid in time; a new checkout makes a new one. */
    case Expired = 'expired';
}
