<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordStockInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            // Pieces, or grams/ml for weighed goods (a 25 kg sack = 25000).
            'qty' => ['required', 'integer', 'min:1', 'max:100000000'],
        ];
    }

    public function messages(): array
    {
        return ['product_id.required' => 'Pilih produk dulu.'];
    }
}
