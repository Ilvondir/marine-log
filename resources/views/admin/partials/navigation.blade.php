<nav class="bg-gray-800 text-white px-4 py-3 mb-6">
    <div class="max-w-6xl mx-auto flex items-center space-x-6">
        <span class="font-bold text-lg">Admin Panel</span>
        <a href="{{ route('admin.dashboard') }}"
           class="hover:text-blue-300 {{ request()->routeIs('admin.dashboard') ? 'text-blue-300 underline' : '' }}">
            Dashboard
        </a>
        <a href="{{ route('admin.observations.index') }}"
           class="hover:text-blue-300 {{ request()->routeIs('admin.observations.*') ? 'text-blue-300 underline' : '' }}">
            Observations
        </a>
        <a href="{{ route('admin.users.index') }}"
           class="hover:text-blue-300 {{ request()->routeIs('admin.users.*') ? 'text-blue-300 underline' : '' }}">
            Users
        </a>
    </div>
</nav>
