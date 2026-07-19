<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreObservationRequest;
use App\Http\Requests\UpdateObservationRequest;
use App\Models\Observation;
use App\Services\FavoriteService;
use App\Services\ObservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ObservationController extends Controller
{
    public function __construct(
        private readonly ObservationService $observationService,
        private readonly FavoriteService $favoriteService,
    ) {}

    /**
     * Display a paginated feed of published observations.
     */
    public function index(): View
    {
        $observations = $this->observationService->getPublishedFeed();

        $favoritedIds = [];
        if (Auth::check()) {
            $favoritedIds = Auth::user()->favorites()->pluck('observation_id')->all();
        }

        return view('observations.index', compact('observations', 'favoritedIds'));
    }

    /**
     * Display the authenticated user's observations.
     */
    public function myObservations(Request $request): View
    {
        $observations = $this->observationService->getUserObservations($request->user()->id);

        return view('observations.my', compact('observations'));
    }

    /**
     * Show the form for creating a new observation.
     */
    public function create(): View
    {
        return view('observations.create');
    }

    /**
     * Store a newly created observation.
     */
    public function store(StoreObservationRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $observation = $this->observationService->publishObservation(
            userId: $request->user()->id,
            validatedData: $validated,
            photos: $validated['photos'],
            videos: $validated['videos'] ?? [],
        );

        return redirect()
            ->route('observations.show', $observation)
            ->with('success', 'Observation published successfully.');
    }

    /**
     * Display the specified published observation.
     */
    public function show(Observation $observation): View
    {
        abort_unless($observation->published_at !== null, 404);

        $observation->load(['user', 'resources']);
        $observation->loadCount('favoritedBy');

        $isFavorited = Auth::check()
            && $this->favoriteService->isFavorited(Auth::id(), $observation->id);

        return view('observations.show', compact('observation', 'isFavorited'));
    }

    /**
     * Show the form for editing the specified observation.
     */
    public function edit(Observation $observation): View
    {
        $this->authorize('update', $observation);

        $observation->load('resources');

        return view('observations.edit', compact('observation'));
    }

    /**
     * Update the specified observation in storage.
     */
    public function update(UpdateObservationRequest $request, Observation $observation): RedirectResponse
    {
        $this->authorize('update', $observation);

        $validated = $request->validated();

        $this->observationService->updateObservation(
            observation: $observation,
            validatedData: $validated,
            newPhotos: $validated['photos'] ?? [],
            newVideos: $validated['videos'] ?? [],
            removeResourceIds: array_map('intval', $validated['remove_resources'] ?? []),
        );

        return redirect()
            ->route('observations.show', $observation)
            ->with('success', 'Observation updated successfully.');
    }

    /**
     * Remove the specified observation from storage.
     */
    public function destroy(Observation $observation): RedirectResponse
    {
        $this->authorize('delete', $observation);

        $this->observationService->deleteObservation($observation);

        return redirect()
            ->route('home')
            ->with('success', 'Observation deleted successfully.');
    }
}
