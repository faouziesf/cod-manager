<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderNote;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(array $data, User $user)
    {
        return DB::transaction(function () use ($data, $user) {
            $order = Order::create([
                'admin_id' => $user->isAdmin() ? $user->id : $user->admin_id,
                'created_by' => $user->id,
                'assigned_to' => $data['assigned_to'] ?? null,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone1' => $data['phone1'],
                'phone2' => $data['phone2'] ?? null,
                'country' => $data['country'] ?? 'Tunisie',
                'region' => $data['region'],
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'standard',
                'total_price' => $data['total_price'],
                'scheduled_date' => $data['scheduled_date'] ?? null,
            ]);

            // Create order items
            foreach ($data['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // Add note
            if (isset($data['note']) && !empty($data['note'])) {
                $order->addNote($user->id, $data['note'], 'create');
            }

            return $order;
        });
    }

    public function updateOrder(Order $order, array $data, User $user)
    {
        return DB::transaction(function () use ($order, $data, $user) {
            $order->update([
                'first_name' => $data['first_name'] ?? $order->first_name,
                'last_name' => $data['last_name'] ?? $order->last_name,
                'phone1' => $data['phone1'] ?? $order->phone1,
                'phone2' => $data['phone2'] ?? $order->phone2,
                'country' => $data['country'] ?? $order->country,
                'region' => $data['region'] ?? $order->region,
                'city' => $data['city'] ?? $order->city,
                'address' => $data['address'] ?? $order->address,
            ]);

            // Update items if provided
            if (isset($data['items'])) {
                // Remove existing items
                $order->items()->delete();

                // Add new items
                foreach ($data['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }

                // Update total price
                $order->update([
                    'total_price' => $data['total_price'],
                ]);
            }

            // Add note
            if (isset($data['note']) && !empty($data['note'])) {
                $order->addNote($user->id, $data['note'], 'update');
            }

            return $order;
        });
    }

    public function assignOrder(Order $order, User $employee, User $assigner)
    {
        return DB::transaction(function () use ($order, $employee, $assigner) {
            $order->update([
                'assigned_to' => $employee->id,
            ]);

            $order->addNote(
                $assigner->id,
                "Commande assignée à {$employee->name}",
                'assign'
            );

            return $order;
        });
    }

    public function confirmOrder(Order $order, array $data, User $user)
    {
        return DB::transaction(function () use ($order, $data, $user) {
            // Vérifier tous les champs obligatoires
            if (empty($order->first_name) || empty($order->last_name) || empty($order->address)) {
                throw new \Exception('Les champs nom, prénom et adresse sont obligatoires pour confirmer une commande.');
            }

            $order->update([
                'status' => 'confirmed',
                'confirmed_price' => $data['confirmed_price'] ?? $order->total_price,
            ]);

            // Décrémenter le stock pour chaque produit
            foreach ($order->items as $item) {
                $item->product->decrementStock($item->quantity);
            }

            $note = $data['note'] ?? '';
            $order->addNote(
                $user->id,
                "{$user->name} a confirmé la commande" . ($note ? " avec la note: {$note}" : ""),
                'confirm'
            );

            return $order;
        });
    }

    public function cancelOrder(Order $order, array $data, User $user)
    {
        return DB::transaction(function () use ($order, $data, $user) {
            if (empty($data['note'])) {
                throw new \Exception('Une note est obligatoire pour annuler une commande.');
            }

            $order->update([
                'status' => 'canceled',
            ]);

            $order->addNote(
                $user->id,
                "Le client a annulé la commande. {$user->name} a laissé la note: {$data['note']}",
                'cancel'
            );

            return $order;
        });
    }

    public function scheduleOrder(Order $order, array $data, User $user)
    {
        return DB::transaction(function () use ($order, $data, $user) {
            if (empty($data['scheduled_date']) || empty($data['note'])) {
                throw new \Exception('La date et la note sont obligatoires pour programmer une commande.');
            }

            $order->update([
                'status' => 'dated',
                'scheduled_date' => $data['scheduled_date'],
            ]);

            $order->addNote(
                $user->id,
                "{$user->name} a programmé un appel pour le {$data['scheduled_date']} et a laissé une note: {$data['note']}",
                'schedule'
            );

            return $order;
        });
    }

    public function noAnswerOrder(Order $order, array $data, User $user, Setting $settings)
    {
        return DB::transaction(function () use ($order, $data, $user, $settings) {
            if (empty($data['note'])) {
                throw new \Exception('Une note est obligatoire pour cette action.');
            }

            $order->incrementAttempt();

            $order->addNote(
                $user->id,
                "{$user->name} a tenté de joindre le client sans succès et a laissé une note: {$data['note']}",
                'no_answer'
            );

            // Vérifier si la commande doit passer en 'old'
            if ($order->shouldBecomeOld($settings)) {
                $order->update(['status' => 'old']);
            }

            return $order;
        });
    }

    public function getOrdersForProcessing(User $user, Setting $settings, $type = 'standard')
    {
        $query = Order::query();

        // Filtrer par admin_id
        if ($user->isAdmin()) {
            $query->where('admin_id', $user->id);
        } elseif ($user->isManager() || $user->isEmployee()) {
            $query->where('admin_id', $user->admin_id);
        }

        // Filtrer par assigned_to pour les employés
        if ($user->isEmployee()) {
            $query->where('assigned_to', $user->id);
        }

        // Filtrer par type
        if ($type === 'standard') {
            $query->where('status', 'standard');
        } elseif ($type === 'dated') {
            $query->where('status', 'dated')
                ->whereDate('scheduled_date', '<=', now()->toDateString());
        } elseif ($type === 'old') {
            $query->where('status', 'old');
        }

        // Filtrer les commandes qui peuvent être traitées aujourd'hui
        $query->whereHas('admin', function ($q) {
            $q->where('is_active', true);
        });

        $orders = $query->get();

        // Filtrer les commandes qui peuvent être traitées maintenant
        return $orders->filter(function ($order) use ($settings) {
            return $order->canAttemptToday($settings) && $order->canAttemptNow($settings);
        })->sortBy([
            ['daily_attempts', 'asc'],
            ['attempts', 'asc'],
            ['created_at', 'asc'],
        ])->values();
    }

    public function getAvailableOrderCount(User $user, Setting $settings)
    {
        $standardCount = $this->getOrdersForProcessing($user, $settings, 'standard')->count();
        $datedCount = $this->getOrdersForProcessing($user, $settings, 'dated')->count();
        $oldCount = $this->getOrdersForProcessing($user, $settings, 'old')->count();

        return [
            'standard' => $standardCount,
            'dated' => $datedCount,
            'old' => $oldCount,
            'total' => $standardCount + $datedCount + $oldCount,
        ];
    }

    public function resetDailyAttempts()
    {
        Order::where('daily_attempts', '>', 0)->update(['daily_attempts' => 0]);
    }

    public function getOrderStatistics(User $user, $period = 'today')
    {
        $query = Order::query();

        // Filtrer par admin_id
        if ($user->isAdmin()) {
            $query->where('admin_id', $user->id);
        } elseif ($user->isManager() || $user->isEmployee()) {
            $query->where('admin_id', $user->admin_id);
        }

        // Filtrer par assigned_to pour les employés
        if ($user->isEmployee()) {
            $query->where('assigned_to', $user->id);
        }

        // Filtrer par période
        if ($period === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($period === 'yesterday') {
            $query->whereDate('created_at', today()->subDay());
        } elseif ($period === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'this_month') {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        } elseif ($period === 'last_month') {
            $lastMonth = now()->subMonth();
            $query->whereMonth('created_at', $lastMonth->month)
                  ->whereYear('created_at', $lastMonth->year);
        }

        $total = $query->count();
        $confirmed = $query->where('status', 'confirmed')->count();
        $canceled = $query->where('status', 'canceled')->count();
        $standard = $query->where('status', 'standard')->count();
        $dated = $query->where('status', 'dated')->count();
        $old = $query->where('status', 'old')->count();

        // Top produits
        $topProducts = OrderItem::whereHas('order', function($q) use ($query) {
            $q->where($query->getQuery()->wheres);
        })
        ->selectRaw('product_id, sum(quantity) as total_quantity')
        ->groupBy('product_id')
        ->orderByDesc('total_quantity')
        ->limit(5)
        ->with('product')
        ->get();

        // Top régions
        $topRegions = Order::where($query->getQuery()->wheres)
            ->selectRaw('region, count(*) as total')
            ->groupBy('region')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'canceled' => $canceled,
            'standard' => $standard,
            'dated' => $dated,
            'old' => $old,
            'confirmation_rate' => $total > 0 ? round(($confirmed / $total) * 100, 2) : 0,
            'top_products' => $topProducts,
            'top_regions' => $topRegions,
        ];
    }
}