<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        Setting::create([
            'admin_id' => 1, // Super Admin
            'public_registration' => true,
            'trial_days' => 15,
            'max_managers' => 5,
            'max_employees' => 20,
        ]);
    }
}