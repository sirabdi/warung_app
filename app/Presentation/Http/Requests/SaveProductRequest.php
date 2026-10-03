<?php

namespace App\Presentation\Http\Requests;

use App\Application\Product\DTO\ProductData;
use App\Domain\Shared\ValueObject\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'category_id' => ['required', 'integer', 'min:1'],
            'sell_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'cost_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'unit' => ['nullable', Rule::enum(Unit::class)],
            // In steps of the unit: pieces, or grams/ml for kg/liter.
            'stock' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    public function messages(): array
    {
        return ['category_id.required' => 'Kategori wajib dipilih.'];
    }

    public function productData(): ProductData
    {
        return ProductData::fromArray($this->validated());
    }
}
