<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            $this->userService->updateLastActivity($user);

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Ce compte a été désactivé.',
                ]);
            }

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.',
        ]);
    }

    public function showRegistrationForm()
    {
        // Vérifier si l'inscription publique est activée
        $setting = Setting::where('admin_id', 1)->first();
        if (!$setting || !$setting->public_registration) {
            return redirect()->route('login')
                ->withErrors(['message' => 'L\'inscription n\'est pas activée.']);
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Vérifier si l'inscription publique est activée
        $setting = Setting::where('admin_id', 1)->first();
        if (!$setting || !$setting->public_registration) {
            return redirect()->route('login')
                ->withErrors(['message' => 'L\'inscription n\'est pas activée.']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Vérifier si l'IP existe déjà pour un admin
        $ipAddress = $request->ip();
        $existingAdmin = User::where('role', 'admin')
            ->where('ip_address', $ipAddress)
            ->exists();

        if ($existingAdmin) {
            return back()->withErrors([
                'message' => 'Un compte admin existe déjà avec cette adresse IP.',
            ])->withInput();
        }

        $user = $this->userService->createUser([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'admin',
            'is_active' => true,
            'trial_days' => $setting->trial_days,
            'ip_address' => $ipAddress,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Compte créé avec succès. Votre période d\'essai de ' . $setting->trial_days . ' jours a commencé.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}