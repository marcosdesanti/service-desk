<?php

declare(strict_types=1);

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            
            if (Auth::check() && empty($model->tenant_id)) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (Auth::check()) {
                $user = Auth::user();
                // If user is not from the Master Tenant (NetLogin Brasil), scope by tenant_id
                if (!$user->is_master_admin) {
                    $builder->where('tenant_id', $user->tenant_id);
                }
            }
        });
    }

    /**
     * Get the tenant that owns the model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}