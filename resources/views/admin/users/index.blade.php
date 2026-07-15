@extends('layouts.marine')

@section('title', 'Admin — Users')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold mb-6">All Users</h1>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-3 bg-red-100 text-red-800 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b">
                    <th class="py-2 px-3">ID</th>
                    <th class="py-2 px-3">Name</th>
                    <th class="py-2 px-3">Email</th>
                    <th class="py-2 px-3">Role</th>
                    <th class="py-2 px-3">Status</th>
                    <th class="py-2 px-3">Registered</th>
                    <th class="py-2 px-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-3">{{ $user->id }}</td>
                        <td class="py-2 px-3">{{ $user->name }}</td>
                        <td class="py-2 px-3">{{ $user->email }}</td>
                        <td class="py-2 px-3">{{ $user->role->name ?? 'None' }}</td>
                        <td class="py-2 px-3">
                            @if ($user->isBlocked())
                                <span class="text-red-600 font-medium">Blocked</span>
                            @else
                                <span class="text-green-600 font-medium">Active</span>
                            @endif
                        </td>
                        <td class="py-2 px-3">{{ $user->created_at->format('Y-m-d') }}</td>
                        <td class="py-2 px-3">
                            @if ($user->id !== auth()->id() && !$user->isAdmin())
                                @if ($user->isBlocked())
                                    <form action="{{ route('admin.users.unblock', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-green-600 hover:underline">Unblock</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.users.block', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-red-600 hover:underline">Block</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-4 px-3 text-center text-gray-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
