<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index()
    {
        $user = Auth::user();
        $products = $this->productService->getProductsForUser($user);

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $product = $this->productService->createProduct($request->all(), $user);

        return redirect()->route('products.index')
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product)
    {
        $user = Auth::user();
        
        if ($user->isAdmin() && $product->admin_id !== $user->id) {
            abort(403);
        } elseif (($user->isManager() || $user->isEmployee()) && $product->admin_id !== $user->admin_id) {
            abort(403);
        }

        return view('products.show', [
            'product' => $product,
        ]);
    }

    public function edit(Product $product)
    {
        $user = Auth::user();
        
        if ($user->isAdmin() && $product->admin_id !== $user->id) {
            abort(403);
        } elseif (($user->isManager() || $user->isEmployee()) && $product->admin_id !== $user->admin_id) {
            abort(403);
        }

        return view('products.edit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $user = Auth::user();
        
        if ($user->isAdmin() && $product->admin_id !== $user->id) {
            abort(403);
        } elseif (($user->isManager() || $user->isEmployee()) && $product->admin_id !== $user->admin_id) {
            abort(403);
        }

        if ($user->isEmployee()) {
            return back()->withErrors(['message' => 'Vous n\'avez pas les permissions pour modifier un produit.']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->productService->updateProduct($product, $request->all());

        return redirect()->route('products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    public function destroy(Product $product)
    {
        $user = Auth::user();
        
        if ($user->isAdmin() && $product->admin_id !== $user->id) {
            abort(403);
        } elseif ($user->isManager() && $product->admin_id !== $user->admin_id) {
            abort(403);
        }

        if ($user->isEmployee()) {
            return back()->withErrors(['message' => 'Vous n\'avez pas les permissions pour supprimer un produit.']);
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }

    public function outOfStock()
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403);
        }

        $outOfStockProducts = $this->productService->getOutOfStockProducts($user->isAdmin() ? $user : $user->admin);
        $ordersWithOutOfStock = $this->productService->getOrdersWithOutOfStockProducts($user->isAdmin() ? $user : $user->admin);

        return view('products.out_of_stock', [
            'products' => $outOfStockProducts,
            'orders' => $ordersWithOutOfStock,
        ]);
    }
}