<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'standard_max_daily_attempts',
        'standard_max_total_attempts',
        'standard_attempts_delay',
        'dated_max_daily_attempts',
        'dated_max_total_attempts',
        'dated_attempts_delay',
        'old_attempts_delay',
        'public_registration',
        'trial_days',
        'max_managers',
        'max_employees',
    ];

    protected $casts = [
        'standard_attempts_delay' => 'decimal:2',
        'dated_attempts_delay' => 'decimal:2',
        'old_attempts_delay' => 'decimal:2',
        'public_registration' => 'boolean',
    ];

    // Relations
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}