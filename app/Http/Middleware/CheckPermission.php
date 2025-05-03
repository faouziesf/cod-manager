<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        switch ($permission) {
            case 'manage-users':
                if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager()) {
                    abort(403);
                }
                break;
            
            case 'manage-orders':
                if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager()) {
                    abort(403);
                }
                break;
            
            case 'manage-settings':
                if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                    abort(403);
                }
                break;
            
            default:
                abort(403);
                break;
        }

        return $next($request);
    }
}