<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEvent extends Model
{
    protected $fillable = ['shipment_id', 'from_status', 'to_status', 'actor_id', 'note'];

    public function shipment(): BelongsTo { return $this->belongsTo(Shipment::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
