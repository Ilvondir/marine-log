<nav style="display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 2rem;">
    <a href="{{ route('admin.dashboard') }}"
       class="marine-navlink {{ request()->routeIs('admin.dashboard') ? '' : 'marine-button--ghost' }}"
       style="{{ request()->routeIs('admin.dashboard') ? 'background: linear-gradient(135deg, rgba(17,122,139,0.95), rgba(13,59,102,0.95)); font-weight: 700;' : '' }}">
        Dashboard
    </a>
    <a href="{{ route('admin.observations.index') }}"
       class="marine-navlink {{ request()->routeIs('admin.observations.*') ? '' : 'marine-button--ghost' }}"
       style="{{ request()->routeIs('admin.observations.*') ? 'background: linear-gradient(135deg, rgba(17,122,139,0.95), rgba(13,59,102,0.95)); font-weight: 700;' : '' }}">
        Observations
    </a>
    <a href="{{ route('admin.users.index') }}"
       class="marine-navlink {{ request()->routeIs('admin.users.*') ? '' : 'marine-button--ghost' }}"
       style="{{ request()->routeIs('admin.users.*') ? 'background: linear-gradient(135deg, rgba(17,122,139,0.95), rgba(13,59,102,0.95)); font-weight: 700;' : '' }}">
        Users
    </a>
</nav>
