<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\View\View;

class PlatformDashboardController extends Controller
{
    public function __invoke(): View
    {
        $summary = [
            'total' => Company::query()->count(),
            'pending' => Company::query()->where('status', Company::STATUS_PENDING)->count(),
            'active' => Company::query()->where('status', Company::STATUS_ACTIVE)->count(),
            'suspended' => Company::query()->where('status', Company::STATUS_SUSPENDED)->count(),
        ];

        return view('platform.dashboard', [
            'summary' => $summary,
            'recentCompanies' => Company::query()->latest()->limit(5)->get(),
        ]);
    }
}
