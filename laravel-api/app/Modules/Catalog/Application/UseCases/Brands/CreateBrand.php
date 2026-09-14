<?php
namespace App\Modules\Catalog\Application\UseCases\Brands;
use App\Modules\Catalog\Domain\ValueObjects\BrandData;use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\DuplicateSlugException;use Illuminate\Support\Str;
final class CreateBrand {public function __construct(private readonly BrandRepositoryInterface $brands){} public function execute(BrandData $d): object{$s=Str::slug($d->slug?:$d->name);if($this->brands->slugExists($s))throw new DuplicateSlugException($s);return $this->brands->create($d,$s);}}
