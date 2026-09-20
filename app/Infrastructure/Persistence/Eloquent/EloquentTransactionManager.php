<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Shared\TransactionManager;
use Closure;
use Illuminate\Support\Facades\DB;

final class EloquentTransactionManager implements TransactionManager
{
    public function run(Closure $action): mixed
    {
        return DB::transaction($action);
    }
}
