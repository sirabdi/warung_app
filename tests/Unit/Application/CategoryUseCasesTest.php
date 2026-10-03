<?php

namespace Tests\Unit\Application;

use App\Application\Category\UseCase\AddCategory;
use App\Application\Category\UseCase\DeleteCategory;
use App\Application\Category\UseCase\RenameCategory;
use App\Application\Product\DTO\ProductData;
use App\Application\Product\UseCase\AddProduct;
use App\Domain\Category\Exception\CategoryInUse;
use App\Domain\Category\Exception\CategoryNotFound;
use App\Domain\Category\Exception\DuplicateCategoryName;
use App\Domain\Product\Entity\Product;
use App\Domain\Shared\ValueObject\Money;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryCategoryRepository;
use Tests\Support\InMemoryProductRepository;

/** Use cases with in-memory repositories: no Laravel, no database. */
class CategoryUseCasesTest extends TestCase
{
    private InMemoryCategoryRepository $categories;

    private InMemoryProductRepository $products;

    protected function setUp(): void
    {
        $this->categories = new InMemoryCategoryRepository;
        $this->products = new InMemoryProductRepository;
    }

    public function test_category_names_are_unique(): void
    {
        $add = new AddCategory($this->categories);
        $add->execute('Minuman');

        $this->expectException(DuplicateCategoryName::class);
        $add->execute(' Minuman ');
    }

    public function test_renaming_to_its_own_name_is_allowed(): void
    {
        $category = (new AddCategory($this->categories))->execute('Rokok');

        $renamed = (new RenameCategory($this->categories))->execute($category->id(), 'Rokok');

        $this->assertSame('Rokok', $renamed->name());
    }

    public function test_a_category_in_use_cannot_be_deleted(): void
    {
        $category = (new AddCategory($this->categories))->execute('Rokok');
        $this->products->add(Product::register('Sampoerna', $category->id(), Money::of(31000)));

        $this->expectException(CategoryInUse::class);
        $this->expectExceptionMessage('Kategori Rokok masih dipakai 1 produk.');

        (new DeleteCategory($this->categories, $this->products))->execute($category->id());
    }

    public function test_an_empty_category_can_be_deleted(): void
    {
        $category = (new AddCategory($this->categories))->execute('Gas');

        (new DeleteCategory($this->categories, $this->products))->execute($category->id());

        $this->assertFalse($this->categories->exists($category->id()));
    }

    public function test_a_product_needs_an_existing_category(): void
    {
        $this->expectException(CategoryNotFound::class);

        (new AddProduct($this->products, $this->categories))->execute(
            new ProductData('Aqua', 99, Money::of(4000), Money::zero()),
        );
    }
}
