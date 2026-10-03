<?php

namespace App\Infrastructure\Provider;

use App\Application\Cashier\Query\ProductsForCashier;
use App\Application\Category\Query\CategoryList;
use App\Application\Inventory\Query\StockInHistory;
use App\Application\Product\Query\ProductList;
use App\Application\Report\Query\DailyReportQuery;
use App\Application\Shared\Clock;
use App\Application\Shared\TransactionManager;
use App\Application\Subscription\Port\PaymentGateway;
use App\Application\Subscription\Query\PaymentHistory;
use App\Application\Subscription\Query\StoreDirectory;
use App\Domain\Category\Repository\CategoryRepository;
use App\Domain\Inventory\Repository\StockInRepository;
use App\Domain\Product\Repository\ProductRepository;
use App\Domain\Sale\Repository\SaleRepository;
use App\Domain\Subscription\Repository\PaymentRepository;
use App\Domain\Subscription\Repository\SubscriptionRepository;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Payment\FakePaymentGateway;
use App\Infrastructure\Payment\XenditPaymentGateway;
use App\Infrastructure\Persistence\Eloquent\EloquentTransactionManager;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentCategoryList;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentDailyReportQuery;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentPaymentHistory;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentStoreDirectory;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentProductList;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentProductsForCashier;
use App\Infrastructure\Persistence\Eloquent\Query\EloquentStockInHistory;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentPaymentRepository;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentProductRepository;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentSaleRepository;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentStockInRepository;
use App\Infrastructure\Persistence\Eloquent\Repository\EloquentSubscriptionRepository;
use App\Infrastructure\Tenancy\CurrentStore;
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
        CategoryRepository::class => EloquentCategoryRepository::class,
        SaleRepository::class => EloquentSaleRepository::class,
        StockInRepository::class => EloquentStockInRepository::class,
        SubscriptionRepository::class => EloquentSubscriptionRepository::class,
        PaymentRepository::class => EloquentPaymentRepository::class,

        // Read models (read side)
        ProductsForCashier::class => EloquentProductsForCashier::class,
        ProductList::class => EloquentProductList::class,
        CategoryList::class => EloquentCategoryList::class,
        StockInHistory::class => EloquentStockInHistory::class,
        DailyReportQuery::class => EloquentDailyReportQuery::class,
        PaymentHistory::class => EloquentPaymentHistory::class,
        StoreDirectory::class => EloquentStoreDirectory::class,

        // Technical services
        TransactionManager::class => EloquentTransactionManager::class,
        Clock::class => SystemClock::class,
    ];

    public function register(): void
    {
        // One per request (or per job), so a store chosen once applies to every query.
        $this->app->scoped(CurrentStore::class);

        $this->app->bind(PaymentGateway::class, fn ($app) => match (config('warung.payment.driver')) {
            'xendit' => $app->make(XenditPaymentGateway::class),
            default => $app->make(FakePaymentGateway::class),
        });
    }
}
