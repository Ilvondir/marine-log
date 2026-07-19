@php
    $isFavorited = $isFavorited ?? false;
    $favoritesCount = $favoritesCount ?? 0;
@endphp

@auth
    <button
        type="button"
        class="favorite-btn{{ $isFavorited ? ' favorite-btn--active' : '' }}"
        data-observation-id="{{ $observation->id }}"
        data-url="{{ route('observations.favorite.toggle', $observation) }}"
        aria-label="{{ $isFavorited ? 'Remove from favorites' : 'Add to favorites' }}"
        aria-pressed="{{ $isFavorited ? 'true' : 'false' }}"
    >
        <span class="favorite-btn__star">{{ $isFavorited ? '★' : '☆' }}</span>
        @if ($favoritesCount > 0)
            <span class="favorite-btn__count">({{ $favoritesCount }})</span>
        @endif
    </button>
@else
    <span
        class="favorite-btn favorite-btn--guest"
        title="Log in to favorite"
        aria-label="Log in to favorite this observation"
    >
        <span class="favorite-btn__star">☆</span>
        @if ($favoritesCount > 0)
            <span class="favorite-btn__count">({{ $favoritesCount }})</span>
        @endif
    </span>
@endauth

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.favorite-btn[data-url]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const url = btn.dataset.url;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!csrf) return;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
            })
            .then(function (response) {
                if (response.status === 401) {
                    window.location.href = '/login';
                    return null;
                }
                return response.json();
            })
            .then(function (data) {
                if (!data) return;

                const star = btn.querySelector('.favorite-btn__star');
                let count = btn.querySelector('.favorite-btn__count');

                star.textContent = data.favorited ? '★' : '☆';
                btn.classList.toggle('favorite-btn--active', data.favorited);
                btn.setAttribute('aria-pressed', data.favorited ? 'true' : 'false');
                btn.setAttribute('aria-label', data.favorited ? 'Remove from favorites' : 'Add to favorites');

                if (data.count > 0) {
                    if (!count) {
                        count = document.createElement('span');
                        count.className = 'favorite-btn__count';
                        btn.appendChild(count);
                    }
                    count.textContent = '(' + data.count + ')';
                } else if (count) {
                    count.remove();
                }
            });
        });
    });
});
</script>
@endpush
@endonce
