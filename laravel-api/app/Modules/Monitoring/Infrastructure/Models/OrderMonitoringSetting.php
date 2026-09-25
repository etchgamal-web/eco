<?php
namespace App\Modules\Monitoring\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
class OrderMonitoringSetting extends Model { protected $fillable=['rule_type','days','is_enabled']; protected function casts():array{return ['days'=>'integer','is_enabled'=>'boolean'];} }
