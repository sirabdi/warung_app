<?php

namespace App\Application\Shared;

use Closure;

/**
 * Unit of work: one use case = one database transaction.
 * Use cases do not need to know whether that is MySQL, sqlite or anything else.
 */
interface TransactionManager
{
    /**
     * @template T
     *
     * @param  Closure(): T  $action
     * @return T
     */
    public function run(Closure $action): mixed;
}
