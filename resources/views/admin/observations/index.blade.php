@extends('layouts.marine')

@section('title', 'Admin — Observations')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold mb-6">All Observations</h1>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b">
                    <th class="py-2 px-3">ID</th>
                    <th class="py-2 px-3">Species</th>
                    <th class="py-2 px-3">Author</th>
                    <th class="py-2 px-3">Status</th>
                    <th class="py-2 px-3">Created</th>
                    <th class="py-2 px-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($observations as $observation)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2 px-3">{{ $observation->id }}</td>
                        <td class="py-2 px-3">{{ $observation->species }}</td>
                        <td class="py-2 px-3">{{ $observation->user->name ?? 'Unknown' }}</td>
                        <td class="py-2 px-3">
                            @if ($observation->published_at)
                                <span class="text-green-600 font-medium">Published</span>
                            @else
                                <span class="text-gray-500">Draft</span>
                            @endif
                        </td>
                        <td class="py-2 px-3">{{ $observation->created_at->format('Y-m-d') }}</td>
                        <td class="py-2 px-3 space-x-2">
                            @if ($observation->published_at)
                                <form action="{{ route('admin.observations.unpublish', $observation) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-yellow-600 hover:underline">Unpublish</button>
                                </form>
                            @endif
                            <form action="{{ route('admin.observations.destroy', $observation) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this observation? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 px-3 text-center text-gray-500">No observations found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $observations->links() }}
    </div>
</div>
@endsection
