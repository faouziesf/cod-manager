@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('header', 'Tableau de bord')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Statistiques générales -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">
                Total commandes
            </dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900">
                {{ $statistics['total'] ?? 0 }}
            </dd>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">
                Commandes confirmées
            </dt>
            <dd class="mt-1 text-3xl font-semibold text-green-600">
                {{ $statistics['confirmed'] ?? 0 }}
            </dd>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">
                Commandes annulées
            </dt>
            <dd class="mt-1 text-3xl font-semibold text-red-600">
                {{ $statistics['canceled'] ?? 0 }}
            </dd>
        </div>
    </div>
    
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">
                Taux de confirmation
            </dt>
            <dd class="mt-1 text-3xl font-semibold text-indigo-600">
                {{ $statistics['confirmation_rate'] ?? 0 }}%
            </dd>
        </div>
    </div>
</div>

<!-- Commandes à traiter -->
<div class="mt-8">
    <h2 class="text-lg font-medium text-gray-900">Commandes à traiter</h2>
    <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 flex justify-between items-center">
                <div>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        Commandes standard
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">
                        {{ $availableOrders['standard'] ?? 0 }}
                    </dd>
                </div>
                <a href="{{ route('orders.process', ['type' => 'standard']) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Traiter
                </a>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 flex justify-between items-center">
                <div>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        Commandes datées
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">
                        {{ $availableOrders['dated'] ?? 0 }}
                    </dd>
                </div>
                <a href="{{ route('orders.process', ['type' => 'dated']) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Traiter
                </a>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6 flex justify-between items-center">
                <div>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        Commandes anciennes
                    </dt>
                    <dd class="mt-1 text-3xl font-semibold text-gray-900">
                        {{ $availableOrders['old'] ?? 0 }}
                    </dd>
                </div>
                <a href="{{ route('orders.process', ['type' => 'old']) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Traiter
                </a>
            </div>
        </div>
    </div>
</div>

@if(Auth::user()->isAdmin())
<!-- Infos compte admin -->
@if(isset($trialDaysLeft))
<div class="mt-8 bg-yellow-50 border-l-4 border-yellow-400 p-4">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="ml-3">
            <p class="text-sm text-yellow-700">
                Votre période d'essai se termine dans {{ $trialDaysLeft }} jours.
            </p>
        </div>
    </div>
</div>
@endif
@endif

<!-- Utilisateurs en ligne -->
@if(Auth::user()->isAdmin() || Auth::user()->isManager())
<div class="mt-8">
    <h2 class="text-lg font-medium text-gray-900">Utilisateurs en ligne</h2>
    <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
        <ul class="divide-y divide-gray-200">
            @forelse($onlineUsers ?? [] as $user)
            <li>
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-indigo-600 truncate">
                            {{ $user->name }}
                        </p>
                        <div class="ml-2 flex-shrink-0 flex">
                            <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                En ligne
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                            <p class="flex items-center text-sm text-gray-500">
                                {{ $user->email }}
                            </p>
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                            <p>
                                {{ $user->role == 'admin' ? 'Administrateur' : ($user->role == 'manager' ? 'Manager' : 'Employé') }}
                            </p>
                        </div>
                    </div>
                </div>
            </li>
            @empty
            <li class="px-4 py-4 sm:px-6 text-center text-gray-500">
                Aucun utilisateur en ligne pour le moment.
            </li>
            @endforelse
        </ul>
    </div>
</div>
@endif

@endsection