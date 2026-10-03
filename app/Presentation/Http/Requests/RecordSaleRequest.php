<?php

namespace App\Presentation\Http\Requests;

use App\Application\Cashier\DTO\Cart;
use Illuminate\Foundation\Http\FormRequest;

class RecordSaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            // Pieces, or grams/ml for weighed goods (1,5 kg = 1500).
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return ['items.required' => 'Keranjang masih kosong.'];
    }

    public function cart(): Cart
    {
        return Cart::fromArray($this->validated()['items']);
    }
}
