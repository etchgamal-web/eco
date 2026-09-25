<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/** @deprecated Use the Catalog module infrastructure model. */
class Product extends \App\Modules\Catalog\Infrastructure\Models\Product
{
    /** Preserve the legacy relation result while callers migrate to the module model. */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
