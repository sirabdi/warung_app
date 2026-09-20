<?php

namespace App\Presentation\Http\Requests;

use App\Application\Product\DTO\ProductData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Only checks the shape of the input. The "name must be unique" rule lives in
 * the use case instead, so it applies no matter where it is called from.
 */
class SaveProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'sell_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'cost_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function productData(): ProductData
    {
        return ProductData::fromArray($this->validated());
    }
}
