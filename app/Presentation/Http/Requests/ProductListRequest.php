<?php

namespace App\Presentation\Http\Requests;

/** ListRequest plus ?category=3 to show one category only. */
class ProductListRequest extends ListRequest
{
    public function categoryId(): ?int
    {
        $id = $this->integer('category');

        return $id > 0 ? $id : null;
    }
}
