<?php
namespace App\Modules\Catalog;
use App\Modules\Catalog\Domain\Contracts\AttributeRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\AttributeValueRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\ProductMediaRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\ProductReviewRepositoryInterface;
use App\Modules\Catalog\Domain\Contracts\ProductRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Persistence\EloquentAttributeRepository;
use App\Modules\Catalog\Infrastructure\Persistence\EloquentAttributeValueRepository;
use App\Modules\Catalog\Infrastructure\Persistence\EloquentBrandRepository;
use App\Modules\Catalog\Infrastructure\Persistence\EloquentCategoryRepository;
use App\Modules\Catalog\Infrastructure\Persistence\EloquentProductMediaRepository;
use App\Modules\Catalog\Infrastructure\Persistence\EloquentProductReviewRepository;
use App\Modules\Catalog\Infrastructure\Persistence\EloquentProductRepository;
use Illuminate\Support\ServiceProvider;
class CatalogServiceProvider extends ServiceProvider
{
    public array $bindings = [ProductRepositoryInterface::class => EloquentProductRepository::class, AttributeRepositoryInterface::class => EloquentAttributeRepository::class, AttributeValueRepositoryInterface::class => EloquentAttributeValueRepository::class, BrandRepositoryInterface::class => EloquentBrandRepository::class, CategoryRepositoryInterface::class => EloquentCategoryRepository::class, ProductMediaRepositoryInterface::class => EloquentProductMediaRepository::class, ProductReviewRepositoryInterface::class => EloquentProductReviewRepository::class];
}
