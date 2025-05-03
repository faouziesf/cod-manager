<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(array $data, User $creator = null)
    {
        return DB::transaction(function () use ($data, $creator) {
            $role = $data['role'] ?? 'employee';
            $adminId = null;
            $managerId = null;

            if ($creator) {
                if ($creator->isSuperAdmin()) {
                    // Si le créateur est un super admin et crée un admin
                    if ($role === 'admin') {
                        $adminId = null;
                    }
                } elseif ($creator->isAdmin()) {
                    // Si le créateur est un admin
                    $adminId = $creator->id;
                    if ($role === 'manager') {
                        $managerId = null;
                    } elseif ($role === 'employee') {
                        $managerId = $data['manager_id'] ?? null;
                    }
                } elseif ($creator->isManager()) {
                    // Si le créateur est un manager
                    $adminId = $creator->admin_id;
                    $managerId = $creator->id;
                    $role = 'employee'; // Un manager ne peut créer que des employés
                }
            }

            $trialEndsAt = null;
            if ($role === 'admin' && isset($data['trial_days']) && $data['trial_days'] > 0) {
                $trialEndsAt = now()->addDays($data['trial_days']);
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $role,
                'admin_id' => $adminId,
                'manager_id' => $managerId,
                'is_active' => $data['is_active'] ?? true,
                'trial_ends_at' => $trialEndsAt,
                'ip_address' => $data['ip_address'] ?? null,
            ]);

            // Si c'est un admin, créer un setting par défaut
            if ($role === 'admin') {
                Setting::create([
                    'admin_id' => $user->id,
                ]);
            }

            return $user;
        });
    }

    public function updateUser(User $user, array $data)
    {
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'is_active' => $data['is_active'] ?? $user->is_active,
            'manager_id' => $data['manager_id'] ?? $user->manager_id,
        ]);

        if (isset($data['password']) && !empty($data['password'])) {
            $user->update([
                'password' => Hash::make($data['password']),
            ]);
        }

        return $user;
    }

    public function getUsersByRole(User $admin, $role)
    {
        if ($admin->isSuperAdmin()) {
            return User::where('role', $role)->get();
        } elseif ($admin->isAdmin()) {
            return User::where('admin_id', $admin->id)
                ->where('role', $role)
                ->get();
        } elseif ($admin->isManager()) {
            if ($role === 'employee') {
                return User::where('manager_id', $admin->id)
                    ->where('role', $role)
                    ->get();
            }
        }

        return collect();
    }

    public function canCreateMoreUsers(User $admin, $role)
    {
        $settings = $admin->settings;
        
        if (!$settings) {
            return false;
        }

        if ($role === 'manager') {
            $currentCount = User::where('admin_id', $admin->id)
                ->where('role', 'manager')
                ->count();
            return $currentCount < $settings->max_managers;
        } elseif ($role === 'employee') {
            $currentCount = User::where('admin_id', $admin->id)
                ->where('role', 'employee')
                ->count();
            return $currentCount < $settings->max_employees;
        }

        return false;
    }

    public function deactivateExpiredTrials()
    {
        $expiredAdmins = User::where('role', 'admin')
            ->where('is_active', true)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expiredAdmins as $admin) {
            DB::transaction(function () use ($admin) {
                // Désactiver l'admin
                $admin->update(['is_active' => false]);

                // Désactiver tous ses utilisateurs
                User::where('admin_id', $admin->id)
                    ->update(['is_active' => false]);
            });
        }

        return $expiredAdmins->count();
    }

    public function getOnlineUsers(User $user)
    {
        $query = User::where('last_activity_at', '>', now()->subMinutes(15));

        if ($user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('id', $user->id)
                  ->orWhere('admin_id', $user->id);
            });
        } elseif ($user->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('id', $user->id)
                  ->orWhere('manager_id', $user->id);
            });
        } else {
            $query->where('id', $user->id);
        }

        return $query->get();
    }

    public function updateLastActivity(User $user)
    {
        $user->update(['last_activity_at' => now()]);
        return $user;
    }
}