<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'COD Manager') }} - @yield('title', 'Gestion des commandes')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        @auth
        <!-- Barre latérale mobile -->
        <div x-show="sidebarOpen" class="fixed inset-0 z-40 flex md:hidden">
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0">
                <div class="absolute inset-0 bg-gray-600 opacity-75"></div>
            </div>
            
            <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full pt-5 pb-4 bg-gray-800">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Fermer la barre latérale</span>
                        <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="flex-shrink-0 flex items-center px-4">
                    <span class="text-white text-lg font-bold">COD Manager</span>
                </div>
                
                <div class="mt-5 flex-1 h-0 overflow-y-auto">
                    @include('layouts.navigation')
                </div>
            </div>
            
            <div class="flex-shrink-0 w-14" aria-hidden="true">
                <!-- Force sidebar to shrink to fit close icon -->
            </div>
        </div>

        <!-- Barre latérale fixe pour les écrans de bureau -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64">
                <div class="flex flex-col h-0 flex-1">
                    <div class="flex items-center h-16 flex-shrink-0 px-4 bg-gray-800">
                        <span class="text-white text-lg font-bold">COD Manager</span>
                    </div>
                    
                    <div class="flex-1 flex flex-col overflow-y-auto bg-gray-800">
                        @include('layouts.navigation')
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex flex-col w-0 flex-1 md:ml-64">
            <div class="md:hidden pl-1 pt-1 sm:pl-3 sm:pt-3">
                <button @click="sidebarOpen = true" class="-ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
                    <span class="sr-only">Ouvrir la barre latérale</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
            
            <main class="flex-1 relative overflow-y-auto focus:outline-none">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <h1 class="text-2xl font-semibold text-gray-900">@yield('header', 'Dashboard')</h1>
                    </div>
                    
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <!-- Alerte de succès -->
                        @if (session('success'))
                            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative my-4" role="alert">
                                <strong class="font-bold">Succès!</strong>
                                <span class="block sm:inline">{{ session('success') }}</span>
                            </div>
                        @endif

                        <!-- Alerte d'erreur -->
                        @if (session('error'))
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative my-4" role="alert">
                                <strong class="font-bold">Erreur!</strong>
                                <span class="block sm:inline">{{ session('error') }}</span>
                            </div>
                        @endif

                        <!-- Alertes de validation -->
                        @if ($errors->any())
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative my-4" role="alert">
                                <strong class="font-bold">Erreurs de validation:</strong>
                                <ul class="mt-2 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="py-4">
                            @yield('content')
                        </div>
                    </div>
                </div>
            </main>
        </div>
        @else
        <main>
            @yield('content')
        </main>
        @endauth
    </div>
    
    @auth
    <!-- Notifications -->
    <div x-data="{ showNotifications: false }" class="fixed bottom-4 right-4">
        <button 
            @click="showNotifications = !showNotifications" 
            class="bg-indigo-600 p-2 rounded-full text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span id="notification-count" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full"></span>
        </button>
        
        <div x-show="showNotifications" class="absolute bottom-12 right-0 w-80 mt-2 bg-white rounded-md shadow-lg overflow-hidden z-50" style="display: none;">
            <div class="py-2">
                <div class="flex justify-between px-4 py-2 bg-gray-100">
                    <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                    <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Voir toutes</a>
                </div>
                <div id="notifications-list" class="max-h-60 overflow-y-auto divide-y divide-gray-100">
                    <div class="px-4 py-2 text-center text-sm text-gray-500">
                        Chargement des notifications...
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endauth

    <script>
        document.addEventListener('alpine:init', () => {
            // Alpine.js data stores or custom code here
        });
    </script>
    
    @yield('scripts')
</body>
</html>