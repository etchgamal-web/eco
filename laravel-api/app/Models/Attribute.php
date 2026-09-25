<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/** @deprecated Use the Catalog module infrastructure model. */
class Attribute extends \App\Modules\Catalog\Infrastructure\Models\Attribute
{
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }
}
