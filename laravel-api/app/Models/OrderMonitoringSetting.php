<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class OrderMonitoringSetting extends Model { protected $fillable=['rule_type','days','is_enabled']; protected function casts():array{return ['days'=>'integer','is_enabled'=>'boolean'];} }
