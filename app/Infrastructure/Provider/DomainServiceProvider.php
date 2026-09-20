<?php

namespace App\Infrastructure\Provider;

use App\Application\Cashier\Query\ProductsForCashier;
use App\Application\Inventory\Query\StockInHistory;
use App\Application\Product\Query\ProductList;
use App\Application\Report\Query\DailyReportQuery;
use App\Application\Shared\Clock;
use App\Application\Shared\TransactionManager;
use App\Domain\Inventory\Repository\StockInRepository;
use App\Domain\Product\Repository\ProductRepository;
use App\Domain\Sale\Repository\SaleRepository;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Persistence\Eloquent\EloquentTransactionManager;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentDailyReportQuery;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentProductList;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentProductsForCashier;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentStockInHistory;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentProductRepository;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentSaleRepository;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentStockInRepository;
use Illuminate\Support\ServiceProvider;

/**
 * The only place where domain and application are wired to infrastructure.
 * Swapping Eloquent for something else means changing lines here and nowhere else.
 *
 * @var array<class-string, class-string>
 */
class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        // Repositories (write side)
        ProductRepository::class => EloquentProductRepository::class,
        SaleRepository::class => EloquentSaleRepository::class,
        StockInRepository::class => EloquentStockInRepository::class,

        // Read models (read side)
        ProductsForCashier::class => EloquentProductsForCashier::class,
        ProductList::class => EloquentProductList::class,
        StockInHistory::class => EloquentStockInHistory::class,
        DailyReportQuery::class => EloquentDailyReportQuery::class,

        // Technical services
        TransactionManager::class => EloquentTransactionManager::class,
        Clock::class => SystemClock::class,
    ];
}
