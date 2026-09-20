<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordStockInRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return ['product_id.required' => 'Pilih produk dulu.'];
    }
}
