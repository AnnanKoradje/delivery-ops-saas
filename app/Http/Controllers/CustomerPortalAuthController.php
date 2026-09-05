<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPortalLoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CustomerPortalAuthController extends Controller
{
    public function create(): View
    {
        return view('portal.auth.login');
    }

    public function store(CustomerPortalLoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The supplied credentials are not valid.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('portal.deliveries.index'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('customer')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
