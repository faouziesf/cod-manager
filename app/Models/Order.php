<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admin_id',
        'created_by',
        'assigned_to',
        'first_name',
        'last_name',
        'phone1',
        'phone2',
        'country',
        'region',
        'city',
        'address',
        'status',
        'total_price',
        'confirmed_price',
        'attempts',
        'daily_attempts',
        'scheduled_date',
        'last_attempt_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:3',
        'confirmed_price' => 'decimal:3',
        'scheduled_date' => 'date',
        'last_attempt_at' => 'datetime',
    ];

    // Relations
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class)->orderBy('created_at', 'desc');
    }

    // Helpers
    public function isStandard()
    {
        return $this->status === 'standard';
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function isCanceled()
    {
        return $this->status === 'canceled';
    }

    public function isDated()
    {
        return $this->status === 'dated';
    }

    public function isOld()
    {
        return $this->status === 'old';
    }

    public function addNote($userId, $note, $actionType)
    {
        return $this->notes()->create([
            'user_id' => $userId,
            'note' => $note,
            'action_type' => $actionType,
        ]);
    }

    public function incrementAttempt()
    {
        $this->increment('attempts');
        $this->increment('daily_attempts');
        $this->last_attempt_at = now();
        $this->save();
    }

    public function resetDailyAttempts()
    {
        $this->daily_attempts = 0;
        $this->save();
    }

    public function canAttemptToday($settings)
    {
        if ($this->isStandard()) {
            return $this->daily_attempts < $settings->standard_max_daily_attempts;
        } elseif ($this->isDated()) {
            return $this->daily_attempts < $settings->dated_max_daily_attempts;
        }
        
        return true; // Pour les commandes anciennes, pas de limite journalière
    }

    public function canAttemptNow($settings)
    {
        if (!$this->last_attempt_at) {
            return true;
        }

        $delay = 0;
        if ($this->isStandard()) {
            $delay = $settings->standard_attempts_delay;
        } elseif ($this->isDated()) {
            $delay = $settings->dated_attempts_delay;
        } else {
            $delay = $settings->old_attempts_delay;
        }

        $nextPossibleAttempt = $this->last_attempt_at->addHours($delay);
        return now()->greaterThanOrEqualTo($nextPossibleAttempt);
    }

    public function shouldBecomeDated()
    {
        return $this->isStandard() && $this->scheduled_date && $this->scheduled_date->isToday();
    }

    public function shouldBecomeOld($settings)
    {
        if ($this->isStandard()) {
            return $this->attempts >= $settings->standard_max_total_attempts;
        } elseif ($this->isDated()) {
            return $this->attempts >= $settings->dated_max_total_attempts;
        }
        
        return false;
    }
}