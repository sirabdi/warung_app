<?php

return [
    // Products with stock <= this number show up under "Stok Menipis" in the report.
    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 5),
];
