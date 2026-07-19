<?php

namespace App\Providers;

use App\Contracts\Repositories\FavoriteRepositoryInterface;
use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Observation;
use App\Policies\ObservationPolicy;
use App\Repositories\EloquentFavoriteRepository;
use App\Repositories\EloquentObservationRepository;
use App\Repositories\EloquentResourceRepository;
use App\Repositories\EloquentUserRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ObservationRepositoryInterface::class, EloquentObservationRepository::class);
        $this->app->bind(ResourceRepositoryInterface::class, EloquentResourceRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(FavoriteRepositoryInterface::class, EloquentFavoriteRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Observation::class, ObservationPolicy::class);

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
