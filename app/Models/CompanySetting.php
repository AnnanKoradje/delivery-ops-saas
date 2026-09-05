<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CompanySettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'company_id', 'personnel_label_singular', 'personnel_label_plural',
    'operation_label_singular', 'operation_label_plural', 'time_zone', 'tracking_enabled',
])]
class CompanySetting extends Model
{
    /** @use HasFactory<CompanySettingFactory> */
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return ['tracking_enabled' => 'boolean'];
    }
}
