<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\Tenancy\CompanyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company_context', function (Builder $builder): void {
            if (! app()->bound(CompanyContext::class)) {
                return;
            }

            $context = app(CompanyContext::class);

            if ($context->hasCompany()) {
                $builder->where($builder->qualifyColumn('company_id'), $context->id());
            }
        });

        static::creating(function (self $model): void {
            if (! app()->bound(CompanyContext::class)) {
                return;
            }

            $context = app(CompanyContext::class);

            if (! $context->hasCompany()) {
                return;
            }

            $companyId = $context->id();

            if ($model->company_id !== null && (int) $model->company_id !== $companyId) {
                throw new LogicException('Tenant-owned records cannot be created for another company.');
            }

            $model->company_id = $companyId;
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany(Builder $query, Company|int $company): Builder
    {
        $companyId = $company instanceof Company ? $company->getKey() : $company;

        return $query
            ->withoutGlobalScope('company_context')
            ->where($query->qualifyColumn('company_id'), $companyId);
    }
}
