<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query string of a paginated list: ?page=2&per_page=10&search=indo.
 * Bad values are clamped instead of rejected, so a hand-edited URL on an
 * Inertia page never ends in a redirect loop.
 */
class ListRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 10;

    public const MAX_PER_PAGE = 50;

    public function rules(): array
    {
        return [];
    }

    public function page(): int
    {
        return max(1, $this->integer('page', 1));
    }

    public function perPage(int $default = self::DEFAULT_PER_PAGE): int
    {
        return min(self::MAX_PER_PAGE, max(1, $this->integer('per_page', $default)));
    }

    /** Page number from another key, for screens with two lists (?history_page=2). */
    public function pageOf(string $key): int
    {
        return max(1, $this->integer($key, 1));
    }

    public function search(): string
    {
        return mb_substr(trim($this->string('search')->toString()), 0, 100);
    }
}
