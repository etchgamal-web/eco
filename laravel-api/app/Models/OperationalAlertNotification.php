<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class OperationalAlertNotification extends Model { public $timestamps=false; protected $fillable=['operational_alert_id','user_id','sent_at']; protected function casts():array{return ['sent_at'=>'datetime'];} }
