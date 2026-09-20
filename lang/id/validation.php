<?php

// Hanya aturan yang dipakai aplikasi ini yang diterjemahkan.
return [
    'array' => ':attribute harus berupa daftar.',
    'distinct' => ':attribute terisi dua kali.',
    'email' => ':attribute harus berupa alamat email yang benar.',
    'exists' => ':attribute yang dipilih tidak ditemukan.',
    'integer' => ':attribute harus berupa angka.',
    'max' => [
        'array' => ':attribute maksimal :max item.',
        'numeric' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'min' => [
        'array' => ':attribute minimal :min item.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah dipakai.',

    // Kunci = nama kolom (Inggris), nilai = label yang dilihat pemilik warung.
    'attributes' => [
        'cost_price' => 'Harga beli',
        'email' => 'Email',
        'items' => 'Keranjang',
        'items.*.qty' => 'Jumlah',
        'name' => 'Nama produk',
        'password' => 'Password',
        'product_id' => 'Produk',
        'qty' => 'Jumlah',
        'sell_price' => 'Harga jual',
        'stock' => 'Stok',
    ],
];
