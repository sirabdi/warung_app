<?php

namespace App\Presentation\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->only('id', 'name'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'lowStockThreshold' => (int) config('warung.low_stock_threshold'),
        ];
    }
}
