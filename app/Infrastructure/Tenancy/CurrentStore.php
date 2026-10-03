<?php

namespace App\Infrastructure\Tenancy;

use Illuminate\Contracts\Auth\Factory as Auth;

/**
 * The store whose data the app is working on right now.
 *
 * In a request that is the store of the logged-in user. Seeders, commands and
 * tests can pick one explicitly with set().
 */
final class CurrentStore
{
    private ?int $id = null;

    public function __construct(private readonly Auth $auth) {}

    public function set(?int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id ?? $this->auth->guard()->user()?->store_id;
    }

    /**
     * Runs $action as that store, then switches back.
     *
     * @template T
     *
     * @param  callable(): T  $action
     * @return T
     */
    public function as(int $id, callable $action): mixed
    {
        $previous = $this->id;
        $this->id = $id;

        try {
            return $action();
        } finally {
            $this->id = $previous;
        }
    }
}
