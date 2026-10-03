<?php

namespace App\Domain\Subscription\ValueObject;

enum SubscriptionStatus: string
{
    /** Registered, never paid: may only choose a plan and pay. */
    case Pending = 'pending';

    case Active = 'active';

    /** Paid before, period over: may log in, sees "Langganan Habis". */
    case Expired = 'expired';
}
