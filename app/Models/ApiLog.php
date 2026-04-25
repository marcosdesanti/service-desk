<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
   protected $fillable = [
    'tenant_id',
    'endpoint',
    'method',
    'payload_received',
    'status_code',
    'authorized',
    'response_time_ms',
    'ip_address',
];

public function tenant()
{
    return $this->belongsTo(Tenant::class);
}
}
