<nav class="mt-5 px-2 space-y-1">
    <!-- Dashboard -->
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-base font-medium rounded-md">
        <svg class="mr-3 h-6 w-6 {{ request()->routeIs('dashboard') ? 'text-gray-300' : 'text-gray-400 group-hover:text-gray-300' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Tableau de bord
    </a>

    <!-- Commandes -->
    <div x-data="{ open: {{ request()->routeIs('orders.*') ? 'true' : 'false' }} }">
        <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group flex items-center px-2 py-2 text-base font-medium rounded-md w-full">
            <svg class="mr-3 h-6 w-6 text-gray-400 group-hover:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
            </svg>
            <span class="flex-1">Commandes</span>
            <svg :class="{'transform rotate-180': open}" class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div x-show="open" class="space-y-1 pl-5" style="display: none;">
            <a href="{{ route('orders.process', ['type' => 'standard']) }}" class="{{ request()->routeIs('orders.process') && request()->type == 'standard' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Traitement standard
            </a>
            <a href="{{ route('orders.process', ['type' => 'dated']) }}" class="{{ request()->routeIs('orders.process') && request()->type == 'dated' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Traitement daté
            </a>
            <a href="{{ route('orders.process', ['type' => 'old']) }}" class="{{ request()->routeIs('orders.process') && request()->type == 'old' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Traitement ancien
            </a>
            <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Liste des commandes
            </a>
            <a href="{{ route('orders.create') }}" class="{{ request()->routeIs('orders.create') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Nouvelle commande
            </a>
            <a href="{{ route('orders.search') }}" class="{{ request()->routeIs('orders.search') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Recherche avancée
            </a>
            @if(Auth::user()->isAdmin() || Auth::user()->isManager())
            <a href="{{ route('orders.import.form') }}" class="{{ request()->routeIs('orders.import.form') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Importer des commandes
            </a>
            @endif
        </div>
    </div>

    <!-- Produits -->
    <div x-data="{ open: {{ request()->routeIs('products.*') ? 'true' : 'false' }} }">
        <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group flex items-center px-2 py-2 text-base font-medium rounded-md w-full">
            <svg class="mr-3 h-6 w-6 text-gray-400 group-hover:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span class="flex-1">Produits</span>
            <svg :class="{'transform rotate-180': open}" class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div x-show="open" class="space-y-1 pl-5" style="display: none;">
            <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Liste des produits
            </a>
            <a href="{{ route('products.create') }}" class="{{ request()->routeIs('products.create') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Nouveau produit
            </a>
            @if(Auth::user()->isAdmin() || Auth::user()->isManager())
            <a href="{{ route('products.outOfStock') }}" class="{{ request()->routeIs('products.outOfStock') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Produits en rupture
            </a>
            @endif
        </div>
    </div>

    @if(Auth::user()->isSuperAdmin() || Auth::user()->isAdmin() || Auth::user()->isManager())
    <!-- Utilisateurs -->
    <div x-data="{ open: {{ request()->routeIs('users.*') ? 'true' : 'false' }} }">
        <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white group flex items-center px-2 py-2 text-base font-medium rounded-md w-full">
            <svg class="mr-3 h-6 w-6 text-gray-400 group-hover:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <span class="flex-1">Utilisateurs</span>
            <svg :class="{'transform rotate-180': open}" class="w-4 h-4 ml-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div x-show="open" class="space-y-1 pl-5" style="display: none;">
            <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Liste des utilisateurs
            </a>
            <a href="{{ route('users.create') }}" class="{{ request()->routeIs('users.create') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Nouveau utilisateur
            </a>
            <a href="{{ route('users.online') }}" class="{{ request()->routeIs('users.online') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                Utilisateurs en ligne
            </a>
        </div>
    </div>
    @endif

    @if(Auth::user()->isSuperAdmin() || Auth::user()->isAdmin())
    <!-- Paramètres -->
    <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.index') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-base font-medium rounded-md">
        <svg class="mr-3 h-6 w-6 {{ request()->routeIs('settings.index') ? 'text-gray-300' : 'text-gray-400 group-hover:text-gray-300' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Paramètres
    </a>
    @endif

    <!-- Profil -->
    <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }} group flex items-center px-2 py-2 text-base font-medium rounded-md">
        <svg class="mr-3 h-6 w-6 {{ request()->routeIs('profile') ? 'text-gray-300' : 'text-gray-400 group-hover:text-gray-300' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        Mon profil
    </a>

    <!-- Déconnexion -->
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="text-gray-300 hover:bg-gray-700 hover:text-white group flex items-center px-2 py-2 text-base font-medium rounded-md w-full">
            <svg class="mr-3 h-6 w-6 text-gray-400 group-hover:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0