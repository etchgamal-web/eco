<?php
namespace App\Modules\Customer\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
class CustomerAddress extends Model { protected $fillable=['user_id','label','recipient_name','phone','address_line1','address_line2','city','state','postal_code','country','is_default']; protected function casts(): array { return ['is_default'=>'boolean']; } }
