<?php

namespace App\Application\Inventory\UseCase;

use App\Application\Inventory\DTO\StockInResult;
use App\Application\Shared\Clock;
use App\Application\Shared\TransactionManager;
use App\Domain\Inventory\Entity\StockIn;
use App\Domain\Inventory\Repository\StockInRepository;
use App\Domain\Product\Repository\ProductRepository;

/** Core feature #2: record goods received. */
final readonly class RecordStockIn
{
    public function __construct(
        private ProductRepository $products,
        private StockInRepository $stockIns,
        private TransactionManager $transaction,
        private Clock $clock,
    ) {}

    public function execute(int $productId, int $qty, ?int $recordedBy = null): StockInResult
    {
        return $this->transaction->run(function () use ($productId, $qty, $recordedBy): StockInResult {
            $product = $this->products->lock($productId);

            $this->stockIns->save(StockIn::record($product, $qty, $this->clock->today(), $recordedBy));
            $this->products->save($product);

            return new StockInResult($product->name(), $qty, $product->stock());
        });
    }
}
