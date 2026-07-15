@extends('layouts.marine')

@section('title', 'Admin — Observations')

@section('content')
    @include('admin.partials.navigation')

    <p class="marine-kicker">Moderation</p>
    <h1 style="font-size: clamp(1.8rem, 4vw, 2.5rem); margin: 0.5rem 0 2rem;">All Observations</h1>

    @if (session('success'))
        <div class="marine-auth__note" style="margin-bottom: 1.5rem;">
            {{ session('success') }}
        </div>
    @endif

    @if ($observations->isEmpty())
        <div class="marine-empty">
            <div class="marine-empty__icon">🐠</div>
            <p class="marine-empty__text">No observations found.</p>
        </div>
    @else
        <div class="marine-panel" style="overflow-x: auto;">
            <table class="marine-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Species</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($observations as $observation)
                        <tr>
                            <td>{{ $observation->id }}</td>
                            <td style="font-weight: 600;">{{ $observation->species }}</td>
                            <td>{{ $observation->user->name ?? '—' }}</td>
                            <td>
                                @if ($observation->published_at)
                                    <span style="color: #86efac;">Published</span>
                                @else
                                    <span style="color: rgba(246,251,252,0.5);">Draft</span>
                                @endif
                            </td>
                            <td>{{ $observation->created_at->format('Y-m-d') }}</td>
                            <td style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                @if ($observation->published_at)
                                    <form action="{{ route('admin.observations.unpublish', $observation) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="marine-table-action">Unpublish</button>
                                    </form>
                                @else
                                    <form action="{{ route('admin.observations.republish', $observation) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="marine-table-action">Publish</button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.observations.destroy', $observation) }}" method="POST"
                                      onsubmit="return confirm('Delete this observation permanently? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="marine-table-action marine-table-action--danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="marine-pagination">
            {{ $observations->links() }}
        </div>
    @endif
@endsection
