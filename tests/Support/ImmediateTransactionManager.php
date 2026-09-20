<?php

namespace Tests\Support;

use App\Application\Shared\TransactionManager;
use Closure;

/** No database, so there is nothing to commit. */
final class ImmediateTransactionManager implements TransactionManager
{
    public function run(Closure $action): mixed
    {
        return $action();
    }
}
