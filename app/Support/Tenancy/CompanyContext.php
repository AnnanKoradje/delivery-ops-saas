<?php

namespace App\Support\Tenancy;

use App\Models\Company;
use LogicException;

class CompanyContext
{
    private ?Company $company = null;

    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }

    public function clear(): void
    {
        $this->company = null;
    }

    public function company(): ?Company
    {
        return $this->company;
    }

    public function hasCompany(): bool
    {
        return $this->company !== null;
    }

    public function id(): int
    {
        if ($this->company === null) {
            throw new LogicException('A tenant company context is required for this operation.');
        }

        return $this->company->getKey();
    }
}
