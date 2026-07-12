@extends('layouts.marine')

@section('content')
    <section>
        <p class="marine-kicker">Your observations</p>
        <h1 style="font-size: clamp(1.5rem, 4vw, 2.5rem); margin: 0.25rem 0 0; line-height: 1.1;">My Observations</h1>
        <p style="color: rgba(246,251,252,0.7); margin-top: 0.5rem;">Manage all your published observations from one place.</p>

        @if (session('success'))
            <div style="border-radius: 1rem; border: 1px solid rgba(74, 222, 128, 0.28); background: rgba(74, 222, 128, 0.08); padding: 0.9rem 1rem; color: #bbf7d0; margin: 1rem 0;" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if ($observations->count())
            <div style="overflow-x: auto; margin-top: 1.5rem;">
                <table class="marine-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Species</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($observations as $observation)
                            <tr>
                                <td>
                                    @if ($observation->photos->first())
                                        <img
                                            src="{{ asset('storage/' . $observation->photos->first()->path) }}"
                                            alt="{{ $observation->species }}"
                                            style="width: 4rem; height: 3rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid var(--marine-border);"
                                            loading="lazy"
                                        >
                                    @else
                                        <span style="color: rgba(246,251,252,0.3);">—</span>
                                    @endif
                                </td>
                                <td style="font-weight: 600;">{{ $observation->species }}</td>
                                <td>{{ $observation->location_name }}</td>
                                <td>{{ $observation->observed_at->format('M j, Y') }}</td>
                                <td>
                                    @if ($observation->published_at)
                                        <span style="color: #86efac;">Published</span>
                                    @else
                                        <span style="color: #fcd34d;">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <a href="{{ route('observations.show', $observation) }}" class="marine-table-action">View</a>
                                        <a href="{{ route('observations.edit', $observation) }}" class="marine-table-action">Edit</a>
                                        <form method="post" action="{{ route('observations.destroy', $observation) }}" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="marine-table-action marine-table-action--danger"
                                                onclick="return confirm('Delete this observation?')"
                                            >Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($observations->hasPages())
                <nav class="marine-pagination" aria-label="Pagination" style="margin-top: 1.5rem;">
                    @if ($observations->onFirstPage())
                        <span class="disabled"><span>&laquo;</span></span>
                    @else
                        <a href="{{ $observations->previousPageUrl() }}">&laquo;</a>
                    @endif

                    @foreach ($observations->getUrlRange(1, $observations->lastPage()) as $page => $url)
                        @if ($page == $observations->currentPage())
                            <span class="active"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if ($observations->hasMorePages())
                        <a href="{{ $observations->nextPageUrl() }}">&raquo;</a>
                    @else
                        <span class="disabled"><span>&raquo;</span></span>
                    @endif
                </nav>
            @endif
        @else
            <div class="marine-empty" style="margin-top: 2rem;">
                <div class="marine-empty__icon">📋</div>
                <p class="marine-empty__text">You haven't published any observations yet.</p>
                <a href="{{ route('observations.create') }}" class="marine-button" style="margin-top: 1.25rem;">Publish your first observation</a>
            </div>
        @endif
    </section>
@endsection
