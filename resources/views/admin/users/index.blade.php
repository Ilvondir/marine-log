@extends('layouts.marine')

@section('title', 'Admin — Users')

@section('content')
    @include('admin.partials.navigation')

    <p class="marine-kicker">Accounts</p>
    <h1 style="font-size: clamp(1.8rem, 4vw, 2.5rem); margin: 0.5rem 0 2rem;">All Users</h1>

    @if (session('success'))
        <div class="marine-auth__note" style="margin-bottom: 1.5rem;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="marine-error" style="margin-bottom: 1.5rem;">
            {{ session('error') }}
        </div>
    @endif

    @if ($users->isEmpty())
        <div class="marine-empty">
            <div class="marine-empty__icon">👥</div>
            <p class="marine-empty__text">No users found.</p>
        </div>
    @else
        <div class="marine-panel" style="overflow-x: auto;">
            <table class="marine-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td style="font-weight: 600;">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span style="padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;
                                    {{ $user->isAdmin() ? 'background: rgba(77,196,255,0.15); color: #7dd3fc;' : 'background: rgba(246,251,252,0.06); color: rgba(246,251,252,0.7);' }}">
                                    {{ $user->role->name ?? '—' }}
                                </span>
                            </td>
                            <td>
                                @if ($user->isBlocked())
                                    <span style="color: #fca5a5; font-weight: 600;">Blocked</span>
                                @else
                                    <span style="color: #86efac;">Active</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('Y-m-d') }}</td>
                            <td>
                                @if ($user->id !== auth()->id() && !$user->isAdmin())
                                    @if ($user->isBlocked())
                                        <form action="{{ route('admin.users.unblock', $user) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="marine-table-action">Unblock</button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.users.block', $user) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="marine-table-action marine-table-action--danger">Block</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="marine-pagination">
            {{ $users->links() }}
        </div>
    @endif
@endsection
