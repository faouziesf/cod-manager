<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Region;
use App\Models\Setting;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $orderService;
    protected $productService;
    protected $notificationService;

    public function __construct(
        OrderService $orderService,
        ProductService $productService,
        NotificationService $notificationService
    ) {
        $this->orderService = $orderService;
        $this->productService = $productService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type', 'standard');

        $query = Order::query();

        if ($user->isAdmin()) {
            $query->where('admin_id', $user->id);
        } elseif ($user->isManager()) {
            $query->where('admin_id', $user->admin_id);
        } elseif ($user->isEmployee()) {
            $query->where('admin_id', $user->admin_id)
                ->where('assigned_to', $user->id);
        }

        if ($type === 'standard') {
            $query->where('status', 'standard');
        } elseif ($type === 'dated') {
            $query->where('status', 'dated');
        } elseif ($type === 'old') {
            $query->where('status', 'old');
        } elseif ($type === 'confirmed') {
            $query->where('status', 'confirmed');
        } elseif ($type === 'canceled') {
            $query->where('status', 'canceled');
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone1', 'like', "%{$search}%")
                    ->orWhere('phone2', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->all());

        return view('orders.index', [
            'orders' => $orders,
            'type' => $type,
            'search' => $search,
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $products = $this->productService->getActiveProductsForUser($user);
        $regions = Region::where('country', 'Tunisie')->get();

        $employees = collect();
        if ($user->isAdmin() || $user->isManager()) {
            $employees = User::where('admin_id', $user->isAdmin() ? $user->id : $user->admin_id)
                ->where('role', 'employee')
                ->where('is_active', true)
                ->get();
        }

        return view('orders.create', [
            'products' => $products,
            'regions' => $regions,
            'employees' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'phone1' => 'required|string',
            'region' => 'required|string',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|in:standard,confirmed,canceled,dated',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $items = [];
        foreach ($request->products as $product) {
            $dbProduct = Product::find($product['id']);
            $items[] = [
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'price' => $dbProduct->price,
            ];
        }

        $data = $request->all();
        $data['items'] = $items;
        $data['note'] = $request->note;

        if ($request->status === 'dated' && empty($request->scheduled_date)) {
            return back()->withErrors(['scheduled_date' => 'La date est obligatoire pour les commandes datées.'])->withInput();
        }

        if ($request->status === 'confirmed') {
            if (empty($request->first_name) || empty($request->last_name) || empty($request->address)) {
                return back()->withErrors(['message' => 'Nom, prénom et adresse sont obligatoires pour les commandes confirmées.'])->withInput();
            }
        }

        $order = $this->orderService->createOrder($data, $user);
        $this->notificationService->notifyOrderCreated($order);

        return redirect()->route('orders.index')
        ->with('success', 'Commande créée avec succès.');
    }

    public function show(Order $order)
    {
        $user = Auth::user();
        
        if ($user->isEmployee() && $order->assigned_to !== $user->id) {
            abort(403);
        }

        $order->load(['items.product', 'notes.user', 'assignedTo', 'creator']);

        return view('orders.show', [
            'order' => $order,
        ]);
    }

    public function edit(Order $order)
    {
        $user = Auth::user();
        
        if ($user->isEmployee() && $order->assigned_to !== $user->id) {
            abort(403);
        }

        $products = $this->productService->getActiveProductsForUser($user);
        $regions = Region::where('country', 'Tunisie')->get();

        $employees = collect();
        if ($user->isAdmin() || $user->isManager()) {
            $employees = User::where('admin_id', $user->isAdmin() ? $user->id : $user->admin_id)
                ->where('role', 'employee')
                ->where('is_active', true)
                ->get();
        }

        return view('orders.edit', [
            'order' => $order,
            'products' => $products,
            'regions' => $regions,
            'employees' => $employees,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $user = Auth::user();
        
        if ($user->isEmployee() && $order->assigned_to !== $user->id) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'phone1' => 'required|string',
            'region' => 'required|string',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $items = [];
        foreach ($request->products as $product) {
            $dbProduct = Product::find($product['id']);
            $items[] = [
                'product_id' => $product['id'],
                'quantity' => $product['quantity'],
                'price' => $dbProduct->price,
            ];
        }

        $data = $request->all();
        $data['items'] = $items;
        $data['note'] = $request->note;

        $order = $this->orderService->updateOrder($order, $data, $user);

        return redirect()->route('orders.show', $order)
            ->with('success', 'Commande mise à jour avec succès.');
    }

    public function destroy(Order $order)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403);
        }

        $order->delete();

        return redirect()->route('orders.index')
            ->with('success', 'Commande supprimée avec succès.');
    }

    public function process($type = 'standard')
    {
        $user = Auth::user();
        $settings = $user->isAdmin() ? $user->settings : $user->admin->settings;

        $orders = $this->orderService->getOrdersForProcessing($user, $settings, $type);

        if ($orders->isEmpty()) {
            if ($type === 'standard') {
                $nextType = 'dated';
            } elseif ($type === 'dated') {
                $nextType = 'old';
            } else {
                return view('orders.process_empty', [
                    'message' => 'Pause café ! Aucune commande disponible à traiter pour le moment.',
                    'type' => $type,
                ]);
            }

            $nextOrders = $this->orderService->getOrdersForProcessing($user, $settings, $nextType);
            if ($nextOrders->isNotEmpty()) {
                return redirect()->route('orders.process', ['type' => $nextType]);
            }

            return view('orders.process_empty', [
                'message' => 'Pause café ! Aucune commande disponible à traiter pour le moment.',
                'type' => $type,
            ]);
        }

        $order = $orders->first();
        $products = $this->productService->getActiveProductsForUser($user);
        $regions = Region::where('country', 'Tunisie')->get();

        return view('orders.process', [
            'order' => $order,
            'products' => $products,
            'regions' => $regions,
            'type' => $type,
        ]);
    }

    public function processAction(Request $request, Order $order)
    {
        $user = Auth::user();
        $settings = $user->isAdmin() ? $user->settings : $user->admin->settings;
        
        if ($user->isEmployee() && $order->assigned_to !== $user->id) {
            abort(403);
        }

        $action = $request->action;
        $note = $request->note;
        $type = $request->type ?? 'standard';

        try {
            if ($action === 'confirm') {
                if (empty($order->first_name) || empty($order->last_name) || empty($order->address)) {
                    return back()->withErrors(['message' => 'Nom, prénom et adresse sont obligatoires pour confirmer une commande.'])
                        ->withInput();
                }

                $this->orderService->confirmOrder($order, [
                    'confirmed_price' => $request->confirmed_price,
                    'note' => $note,
                ], $user);

                $this->notificationService->notifyOrderConfirmed($order);
            } elseif ($action === 'cancel') {
                if (empty($note)) {
                    return back()->withErrors(['note' => 'Une note est obligatoire pour annuler une commande.'])
                        ->withInput();
                }

                $this->orderService->cancelOrder($order, [
                    'note' => $note,
                ], $user);
            } elseif ($action === 'date') {
                if (empty($note) || empty($request->scheduled_date)) {
                    return back()->withErrors(['message' => 'La date et la note sont obligatoires pour programmer une commande.'])
                        ->withInput();
                }

                $this->orderService->scheduleOrder($order, [
                    'scheduled_date' => $request->scheduled_date,
                    'note' => $note,
                ], $user);

                $this->notificationService->notifyOrderDated($order);
            } elseif ($action === 'no_answer') {
                if (empty($note)) {
                    return back()->withErrors(['note' => 'Une note est obligatoire pour cette action.'])
                        ->withInput();
                }

                $this->orderService->noAnswerOrder($order, [
                    'note' => $note,
                ], $user, $settings);
            }

            return redirect()->route('orders.process', ['type' => $type])
                ->with('success', 'Action effectuée avec succès.');
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()])->withInput();
        }
    }

    public function assign(Request $request, Order $order)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $employee = User::findOrFail($request->employee_id);
        
        // Vérifier que l'employé appartient bien à cet admin
        if ($user->isAdmin() && $employee->admin_id !== $user->id) {
            abort(403);
        }

        // Vérifier que l'employé est sous ce manager
        if ($user->isManager() && $employee->manager_id !== $user->id) {
            abort(403);
        }

        // Garder l'ancien assigné pour la notification
        $previousAssignee = $order->assignedTo;

        $this->orderService->assignOrder($order, $employee, $user);
        $this->notificationService->notifyOrderAssigned($order, $previousAssignee);

        return back()->with('success', 'Commande assignée avec succès.');
    }

    public function search(Request $request)
    {
        $user = Auth::user();
        $search = $request->get('search', '');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $region = $request->get('region');

        $query = Order::query();

        // Filtrer par admin
        if ($user->isAdmin()) {
            $query->where('admin_id', $user->id);
        } elseif ($user->isManager() || $user->isEmployee()) {
            $query->where('admin_id', $user->admin_id);
        }

        // Filtrer par employé
        if ($user->isEmployee()) {
            $query->where('assigned_to', $user->id);
        }

        // Filtrer par recherche
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone1', 'like', "%{$search}%")
                  ->orWhere('phone2', 'like', "%{$search}%");
            });
        }

        // Filtrer par statut
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Filtrer par date
        if (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Filtrer par région
        if (!empty($region)) {
            $query->where('region', $region);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->all());

        $regions = Region::where('country', 'Tunisie')->get();

        return view('orders.search', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'region' => $region,
            'regions' => $regions,
        ]);
    }

    public function downloadCsv(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403);
        }

        $query = Order::query();

        // Filtrer par admin
        if ($user->isAdmin()) {
            $query->where('admin_id', $user->id);
        } elseif ($user->isManager()) {
            $query->where('admin_id', $user->admin_id);
        }

        // Appliquer les filtres
        $search = $request->get('search', '');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $region = $request->get('region');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone1', 'like', "%{$search}%")
                  ->orWhere('phone2', 'like', "%{$search}%");
            });
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if (!empty($region)) {
            $query->where('region', $region);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        $filename = 'commandes_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID',
                'Nom',
                'Prénom',
                'Téléphone 1',
                'Téléphone 2',
                'Région',
                'Ville',
                'Adresse',
                'Statut',
                'Prix Total',
                'Prix Confirmé',
                'Date Créée',
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->first_name,
                    $order->last_name,
                    $order->phone1,
                    $order->phone2,
                    $order->region,
                    $order->city,
                    $order->address,
                    $order->status,
                    $order->total_price,
                    $order->confirmed_price,
                    $order->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importForm()
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403);
        }

        return view('orders.import');
    }

    public function import(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403);
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, 1000, ',');
        $required_columns = ['phone1', 'region', 'total_price'];
        
        $mapping = [];
        foreach ($header as $index => $column) {
            $column = strtolower(trim($column));
            if (in_array($column, $required_columns)) {
                $mapping[$column] = $index;
            } else {
                $mapping[$column] = $index;
            }
        }

        // Vérifier que tous les champs requis sont présents
        $missing = array_diff($required_columns, array_keys($mapping));
        if (!empty($missing)) {
            return back()->withErrors(['message' => 'Colonnes manquantes: ' . implode(', ', $missing)]);
        }

        $imported = 0;
        $errors = [];

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            try {
                $orderData = [
                    'first_name' => isset($mapping['first_name']) ? $data[$mapping['first_name']] : null,
                    'last_name' => isset($mapping['last_name']) ? $data[$mapping['last_name']] : null,
                    'phone1' => $data[$mapping['phone1']],
                    'phone2' => isset($mapping['phone2']) ? $data[$mapping['phone2']] : null,
                    'region' => $data[$mapping['region']],
                    'city' => isset($mapping['city']) ? $data[$mapping['city']] : null,
                    'address' => isset($mapping['address']) ? $data[$mapping['address']] : null,
                    'total_price' => $data[$mapping['total_price']],
                    'status' => isset($mapping['status']) ? $data[$mapping['status']] : 'standard',
                    'scheduled_date' => isset($mapping['scheduled_date']) ? $data[$mapping['scheduled_date']] : null,
                ];

                // Créer des produits par défaut si nécessaire
                $orderData['items'] = [];
                if (isset($mapping['product_name']) && !empty($data[$mapping['product_name']])) {
                    $productName = $data[$mapping['product_name']];
                    $quantity = isset($mapping['quantity']) ? $data[$mapping['quantity']] : 1;
                    
                    $product = Product::where('name', $productName)
                        ->where('admin_id', $user->isAdmin() ? $user->id : $user->admin_id)
                        ->first();
                    
                    if (!$product) {
                        $product = $this->productService->createProduct([
                            'name' => $productName,
                            'price' => $orderData['total_price'],
                            'stock' => 1000000, // Stock par défaut
                            'is_active' => true,
                        ], $user);
                    }
                    
                    $orderData['items'][] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $product->price,
                    ];
                } else {
                    // Créer un produit par défaut si aucun n'est spécifié
                    $product = Product::where('name', 'Produit par défaut')
                        ->where('admin_id', $user->isAdmin() ? $user->id : $user->admin_id)
                        ->first();
                    
                    if (!$product) {
                        $product = $this->productService->createProduct([
                            'name' => 'Produit par défaut',
                            'price' => $orderData['total_price'],
                            'stock' => 1000000,
                            'is_active' => true,
                        ], $user);
                    }
                    
                    $orderData['items'][] = [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'price' => $product->price,
                    ];
                }

                $order = $this->orderService->createOrder($orderData, $user);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = 'Ligne ' . ($imported + 2) . ': ' . $e->getMessage();
            }
        }

        fclose($handle);

        if (!empty($errors)) {
            return back()->with('warning', 'Importation terminée avec des erreurs. ' . $imported . ' commandes importées.')
                ->with('errors', $errors);
        }

        return back()->with('success', $imported . ' commandes importées avec succès.');
    }
}