<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function createProduct(array $data, User $user)
    {
        $adminId = $user->isAdmin() ? $user->id : $user->admin_id;

        return Product::create([
            'admin_id' => $adminId,
            'name' => $data['name'],
            'price' => $data['price'],
            'stock' => $data['stock'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateProduct(Product $product, array $data)
    {
        $product->update([
            'name' => $data['name'] ?? $product->name,
            'price' => $data['price'] ?? $product->price,
            'stock' => $data['stock'] ?? $product->stock,
            'is_active' => $data['is_active'] ?? $product->is_active,
        ]);

        return $product;
    }

    public function getProductsForUser(User $user)
    {
        $adminId = $user->isAdmin() ? $user->id : $user->admin_id;
        
        return Product::where('admin_id', $adminId)
            ->orderBy('name')
            ->get();
    }

    public function getActiveProductsForUser(User $user)
    {
        $adminId = $user->isAdmin() ? $user->id : $user->admin_id;
        
        return Product::where('admin_id', $adminId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function checkLowStockProducts(User $admin, $threshold = 5)
    {
        return Product::where('admin_id', $admin->id)
            ->where('is_active', true)
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->get();
    }

    public function getOutOfStockProducts(User $admin)
    {
        return Product::where('admin_id', $admin->id)
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->orderBy('name')
            ->get();
    }

    public function getOrdersWithOutOfStockProducts(User $admin)
    {
        return DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.admin_id', $admin->id)
            ->whereIn('orders.status', ['standard', 'dated'])
            ->where('products.stock', '<=', 0)
            ->where('products.is_active', true)
            ->select('orders.*')
            ->distinct()
            ->get();
    }
}