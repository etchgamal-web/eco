<?php
namespace App\Modules\Customer\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
class CustomerPreference extends Model { protected $fillable=['user_id','data']; protected function casts(): array{return ['data'=>'array'];} }
