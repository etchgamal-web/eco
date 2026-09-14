<?php
namespace App\Modules\Catalog\Application\UseCases\Brands;
use App\Modules\Catalog\Domain\ValueObjects\BrandData;use App\Modules\Catalog\Domain\Contracts\BrandRepositoryInterface;use App\Modules\Catalog\Domain\Exceptions\DuplicateSlugException;use Illuminate\Support\Str;
final class UpdateBrand {public function __construct(private readonly BrandRepositoryInterface $brands){} public function execute(int $id,BrandData $d):object{$this->brands->findOrFail($id);$s=Str::slug($d->slug?:$d->name);if($this->brands->slugExists($s,$id))throw new DuplicateSlugException($s);return $this->brands->update($id,$d,$s);}}
