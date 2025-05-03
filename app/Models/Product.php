<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'admin_id',
        'name',
        'price',
        'stock',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    // Relations
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Helpers
    public function isOutOfStock()
    {
        return $this->stock <= 0;
    }

    public function decrementStock($quantity = 1)
    {
        $this->decrement('stock', $quantity);
    }

    public function incrementStock($quantity = 1)
    {
        $this->increment('stock', $quantity);
    }
}