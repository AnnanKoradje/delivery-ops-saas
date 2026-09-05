<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingRequest;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CompanySettingController extends Controller
{
    public function show(int $companySetting): View
    {
        // The tenant global scope resolves guessed foreign identifiers as not found.
        $setting = CompanySetting::query()->findOrFail($companySetting);
        Gate::authorize('view', $setting);

        return view('tenant.settings.show', compact('setting'));
    }

    public function update(UpdateCompanySettingRequest $request, int $companySetting): RedirectResponse
    {
        $setting = CompanySetting::query()->findOrFail($companySetting);
        Gate::authorize('update', $setting);
        $setting->update([...$request->validated(), 'tracking_enabled' => $request->boolean('tracking_enabled')]);

        return back()->with('status', 'Terminology and workspace settings updated.');
    }
}
