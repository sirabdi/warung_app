<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class RecordStockInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', $this->ownProduct()],
            // Pieces, or grams/ml for weighed goods (a 25 kg sack = 25000).
            'qty' => ['required', 'integer', 'min:1', 'max:100000000'],
        ];
    }

    public function messages(): array
    {
        return ['product_id.required' => 'Pilih produk dulu.'];
    }

    /** Only products of the user's own store. */
    private function ownProduct(): Exists
    {
        return Rule::exists('products', 'id')->where('store_id', $this->user()->store_id);
    }
}
