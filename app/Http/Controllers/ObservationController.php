<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreObservationRequest;
use App\Http\Requests\UpdateObservationRequest;
use App\Models\Observation;
use App\Services\ObservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ObservationController extends Controller
{
    public function __construct(
        private readonly ObservationService $observationService,
    ) {}

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
     * Display the specified observation.
     */
    public function show(Observation $observation): View
    {
        $observation->load(['user', 'resources']);

        return view('observations.show', compact('observation'));
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
