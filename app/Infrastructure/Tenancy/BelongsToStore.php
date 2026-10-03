<?php

namespace App\Infrastructure\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Keeps every store inside its own data.
 *
 * Every query on the model is limited to the current store, and new rows get
 * its id. Because it sits on the model, repositories, read models and
 * relations (withCount, withSum, …) are covered without knowing about it.
 *
 * A web request without a store sees nothing at all. Only the console (seeders,
 * migrations, scheduled commands) may query across stores.
 */
trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope('store', function (Builder $query) {
            $storeId = app(CurrentStore::class)->id();

            if ($storeId !== null) {
                $query->where($query->qualifyColumn('store_id'), $storeId);
            } elseif (! app()->runningInConsole()) {
                $query->whereRaw('1 = 0');
            }
        });

        static::creating(function (Model $model) {
            $model->store_id ??= app(CurrentStore::class)->id()
                ?? throw new LogicException('Belum ada toko aktif untuk menyimpan '.class_basename($model).'.');
        });
    }
}
