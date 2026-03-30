<?php

namespace App\Http\Middleware;

use App\Models\Property;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        // Don't redirect if already on onboarding page
        if ($request->is('*/onboarding') || $request->is('locador/onboarding')) {
            return $next($request);
        }

        // Also skip for logout, livewire, and auth routes
        if ($request->is('*/logout') || $request->is('livewire/*')) {
            return $next($request);
        }

        // Skip if user chose to skip onboarding
        if (session()->has('onboarding_skipped')) {
            return $next($request);
        }

        // Redirect if user has no properties
        if (! Property::where('user_id', Auth::id())->exists()) {
            return redirect()->to('/locador/onboarding');
        }

        return $next($request);
    }
}

