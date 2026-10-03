<?php

namespace App\Presentation\Http\Requests;

use App\Application\Cashier\DTO\Cart;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class RecordSaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', $this->ownProduct()],
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

    /** Only products of the user's own store. */
    private function ownProduct(): Exists
    {
        return Rule::exists('products', 'id')->where('store_id', $this->user()->store_id);
    }
}
