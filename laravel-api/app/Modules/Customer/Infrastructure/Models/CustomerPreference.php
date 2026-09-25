<?php
namespace App\Modules\Customer\Infrastructure\Models;
use App\Modules\Auth\Infrastructure\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CustomerPreference extends Model { protected $fillable=['user_id','data']; protected function casts(): array{return ['data'=>'array'];} public function user(): BelongsTo{return $this->belongsTo(User::class);} }
