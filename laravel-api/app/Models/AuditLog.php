<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class AuditLog extends Model{protected $fillable=['actor_id','action','target_type','target_id','metadata'];protected function casts():array{return ['metadata'=>'array'];}public function actor():BelongsTo{return $this->belongsTo(User::class,'actor_id');}}
