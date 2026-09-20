<?php

namespace App\Application\Cashier\UseCase;

use App\Application\Cashier\DTO\Cart;
use App\Application\Cashier\DTO\SaleResult;
use App\Application\Shared\Clock;
use App\Application\Shared\TransactionManager;
use App\Domain\Product\Exception\ProductNotFound;
use App\Domain\Product\Repository\ProductRepository;
use App\Domain\Sale\Entity\Sale;
use App\Domain\Sale\Repository\SaleRepository;
use App\Domain\Sale\ValueObject\SaleCode;

/**
 * Core feature #3: record a sale, stock goes down automatically.
 *
 * All products are locked first, then sold one by one. If a single one runs
 * short the whole sale is rolled back — there are no half-finished sales.
 */
final readonly class RecordSale
{
    public function __construct(
        private ProductRepository $products,
        private SaleRepository $sales,
        private TransactionManager $transaction,
        private Clock $clock,
    ) {}

    public function execute(Cart $cart, ?int $cashierId = null): SaleResult
    {
        return $this->transaction->run(function () use ($cart, $cashierId): SaleResult {
            $locked = $this->products->lockMany($cart->productIds());

            $soldAt = $this->clock->now();
            $sale = Sale::start(SaleCode::for($soldAt), $soldAt, $cashierId);

            foreach ($cart->items as $item) {
                $product = $locked[$item->productId] ?? throw ProductNotFound::withId($item->productId);

                $sale->sell($product, $item->qty);
                $this->products->save($product);
            }

            $sale->complete();
            $this->sales->save($sale);

            return new SaleResult((string) $sale->code(), $sale->total(), $sale->itemCount());
        });
    }
}
