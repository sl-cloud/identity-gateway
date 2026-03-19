<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function __invoke(Request $request)
    {
        $user = Auth::user();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Log the logout
        if ($user) {
            $this->auditService->logUserLogout($user, $request);
        }

        return redirect('/auth/login');
    }
}
