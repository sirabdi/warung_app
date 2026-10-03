<?php

namespace App\Infrastructure\Persistence\Eloquent\Query;

use App\Application\Shared\Query\Page;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns an Eloquent query into an application-level Page: one COUNT, then only
 * the rows of the requested page. A page past the end falls back to the last one.
 */
trait PaginatesQueries
{
    /** @param list<string> $columns */
    private function page(Builder $query, int $page, int $perPage, array $columns, Closure $map): Page
    {
        $total = (clone $query)->toBase()->getCountForPagination();
        $page = min(max(1, $page), max(1, (int) ceil($total / $perPage)));

        $items = $query->forPage($page, $perPage)->get($columns)->map($map)->values()->all();

        return new Page($items, $total, $page, $perPage);
    }

    // "50%" must match the text 50%, not "50 and anything". '!' is the escape
    // character because MySQL and SQLite disagree about the backslash.
    private function whereContains(Builder $query, string $column, string $value): Builder
    {
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value).'%';

        return $query->whereRaw("{$column} like ? escape '!'", [$pattern]);
    }
}
