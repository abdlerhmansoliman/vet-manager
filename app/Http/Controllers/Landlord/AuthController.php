<?php

declare(strict_types=1);

namespace App\Http\Controllers\Landlord;

use App\Actions\Landlord\LandlordLoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\LandlordLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the landlord superadmin login form.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('landlord')->check()) {
            return redirect()->route('landlord.dashboard');
        }

        return view('landlord.auth.login');
    }

    /**
     * Handle incoming authentication request.
     */
    public function store(LandlordLoginRequest $request, LandlordLoginAction $action): RedirectResponse
    {
        $action->execute($request->toDTO());

        $request->session()->regenerate();

        return redirect()->intended(route('landlord.dashboard'))->with(
            'success',
            'Welcome back to the Central Platform Console.'
        );
    }

    /**
     * Destroy the authenticated landlord session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('landlord')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landlord.login')->with(
            'success',
            'You have been logged out of the Operations Console.'
        );
    }
}
