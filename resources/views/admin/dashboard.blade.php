@extends('layouts.marine')

@section('title', 'Admin — Dashboard')

@section('content')
    @include('admin.partials.navigation')

    <div class="max-w-6xl mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold mb-6">Operations Harbor</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white border rounded-lg p-4 shadow-sm">
                <p class="text-gray-500 text-sm">Total Observations</p>
                <p class="text-3xl font-bold">{{ \App\Models\Observation::count() }}</p>
            </div>
            <div class="bg-white border rounded-lg p-4 shadow-sm">
                <p class="text-gray-500 text-sm">Total Users</p>
                <p class="text-3xl font-bold">{{ \App\Models\User::count() }}</p>
            </div>
            <div class="bg-white border rounded-lg p-4 shadow-sm">
                <p class="text-gray-500 text-sm">Blocked Users</p>
                <p class="text-3xl font-bold text-red-600">{{ \App\Models\User::whereNotNull('blocked_at')->count() }}</p>
            </div>
        </div>

        <div class="bg-white border rounded-lg p-4 shadow-sm">
            <p class="text-gray-500 text-sm mb-2">Quick Links</p>
            <div class="space-x-4">
                <a href="{{ route('admin.observations.index') }}" class="text-blue-600 hover:underline">Manage Observations →</a>
                <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:underline">Manage Users →</a>
            </div>
        </div>
    </div>
@endsection
