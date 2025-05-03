<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            abort(403);
        }

        $setting = $user->isSuperAdmin() 
            ? Setting::where('admin_id', 1)->first() 
            : $user->settings;

        if (!$setting) {
            $setting = Setting::create([
                'admin_id' => $user->id,
            ]);
        }

        return view('settings.index', [
            'setting' => $setting,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin() && !$user->isAdmin()) {
            abort(403);
        }

        $setting = $user->isSuperAdmin() 
            ? Setting::where('admin_id', 1)->first() 
            : $user->settings;

        if (!$setting) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'standard_max_daily_attempts' => 'required|integer|min:1',
            'standard_max_total_attempts' => 'required|integer|min:1',
            'standard_attempts_delay' => 'required|numeric|min:0.1',
            'dated_max_daily_attempts' => 'required|integer|min:1',
            'dated_max_total_attempts' => 'required|integer|min:1',
            'dated_attempts_delay' => 'required|numeric|min:0.1',
            'old_attempts_delay' => 'required|numeric|min:0.1',
        ]);

        if ($user->isSuperAdmin()) {
            $validator->sometimes('public_registration', 'boolean', function ($input) {
                return true;
            });
            
            $validator->sometimes('trial_days', 'required|integer|min:1', function ($input) {
                return true;
            });
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        
        // Mise à jour des paramètres pour le super admin
        if ($user->isSuperAdmin()) {
            $setting->update([
                'public_registration' => $request->has('public_registration'),
                'trial_days' => $data['trial_days'],
                'max_managers' => $data['max_managers'],
                'max_employees' => $data['max_employees'],
            ]);
        }

        // Mise à jour des paramètres communs
        $setting->update([
            'standard_max_daily_attempts' => $data['standard_max_daily_attempts'],
            'standard_max_total_attempts' => $data['standard_max_total_attempts'],
            'standard_attempts_delay' => $data['standard_attempts_delay'],
            'dated_max_daily_attempts' => $data['dated_max_daily_attempts'],
            'dated_max_total_attempts' => $data['dated_max_total_attempts'],
            'dated_attempts_delay' => $data['dated_attempts_delay'],
            'old_attempts_delay' => $data['old_attempts_delay'],
        ]);

        return back()->with('success', 'Paramètres mis à jour avec succès.');
    }
}