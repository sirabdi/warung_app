<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Tenancy\BelongsToStore;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persistence model, not an aggregate. Category rules live in
 * App\Domain\Category\Entity\Category.
 */
class Category extends Model
{
    use BelongsToStore, HasFactory;

    protected $fillable = ['name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
