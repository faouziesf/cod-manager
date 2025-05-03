<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $request->get('role', 'all');

        $query = User::query();

        if ($user->isSuperAdmin()) {
            if ($role !== 'all') {
                $query->where('role', $role);
            }
        } elseif ($user->isAdmin()) {
            $query->where('admin_id', $user->id);
            if ($role !== 'all') {
                $query->where('role', $role);
            }
        } elseif ($user->isManager()) {
            $query->where('manager_id', $user->id)
                ->where('role', 'employee');
        } else {
            abort(403);
        }

        $users = $query->orderBy('name')->paginate(15);

        return view('users.index', [
            'users' => $users,
            'role' => $role,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager()) {
            abort(403);
        }

        // Vérifier si l'admin peut créer d'autres utilisateurs
        if ($user->isAdmin()) {
            $canCreateManager = $this->userService->canCreateMoreUsers($user, 'manager');
            $canCreateEmployee = $this->userService->canCreateMoreUsers($user, 'employee');
            
            if (!$canCreateManager && !$canCreateEmployee) {
                return back()->withErrors(['message' => 'Vous avez atteint le nombre maximal d\'utilisateurs autorisés.']);
            }
        }

        $managers = collect();
        if ($user->isAdmin()) {
            $managers = User::where('admin_id', $user->id)
                ->where('role', 'manager')
                ->where('is_active', true)
                ->get();
        }

        return view('users.create', [
            'managers' => $managers,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin() && !$user->isAdmin() && !$user->isManager()) {
            abort(403);
        }

        $role = $request->role;
        
        // Vérifier les permissions
        if ($user->isManager() && $role !== 'employee') {
            return back()->withErrors(['message' => 'Vous ne pouvez créer que des employés.']);
        }

        // Vérifier si l'admin peut créer d'autres utilisateurs
        if ($user->isAdmin()) {
            $canCreate = $this->userService->canCreateMoreUsers($user, $role);
            if (!$canCreate) {
                return back()->withErrors(['message' => 'Vous avez atteint le nombre maximal d\'utilisateurs autorisés.']);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,manager,employee',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->userService->createUser($request->all(), $user);

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        $currentUser = Auth::user();
        
        // Vérifier les permissions
        if ($currentUser->isSuperAdmin()) {
            // Le super admin peut éditer tout le monde
        } elseif ($currentUser->isAdmin()) {
            if ($user->admin_id !== $currentUser->id) {
                abort(403);
            }
        } elseif ($currentUser->isManager()) {
            if ($user->manager_id !== $currentUser->id) {
                abort(403);
            }
        } else {
            abort(403);
        }

        $managers = collect();
        if ($currentUser->isAdmin() && $user->role === 'employee') {
            $managers = User::where('admin_id', $currentUser->id)
                ->where('role', 'manager')
                ->where('is_active', true)
                ->get();
        }

        return view('users.edit', [
            'user' => $user,
            'managers' => $managers,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();
        
        // Vérifier les permissions
        if ($currentUser->isSuperAdmin()) {
            // Le super admin peut éditer tout le monde
        } elseif ($currentUser->isAdmin()) {
            if ($user->admin_id !== $currentUser->id) {
                abort(403);
            }
        } elseif ($currentUser->isManager()) {
            if ($user->manager_id !== $currentUser->id) {
                abort(403);
            }
        } else {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'boolean',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->userService->updateUser($user, $request->all());

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur mis à jour avec succès.');
    }

    public function destroy(User $user)
    {
        $currentUser = Auth::user();
        
        if ($user->id === $currentUser->id) {
            return back()->withErrors(['message' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }
        
        // Vérifier les permissions
        if ($currentUser->isSuperAdmin()) {
            // Le super admin peut supprimer tout le monde
        } elseif ($currentUser->isAdmin()) {
            if ($user->admin_id !== $currentUser->id) {
                abort(403);
            }
        } elseif ($currentUser->isManager()) {
            if ($user->manager_id !== $currentUser->id) {
                abort(403);
            }
        } else {
            abort(403);
        }

        // Supprimer l'utilisateur
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Utilisateur supprimé avec succès.');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('users.profile', [
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->userService->updateUser($user, $request->all());

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    public function online()
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403);
        }

        $onlineUsers = $this->userService->getOnlineUsers($user);

        return view('users.online', [
            'users' => $onlineUsers,
        ]);
    }
}