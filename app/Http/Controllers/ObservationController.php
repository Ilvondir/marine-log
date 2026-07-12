<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreObservationRequest;
use App\Services\ObservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ObservationController extends Controller
{
    public function __construct(
        private readonly ObservationService $observationService,
    ) {}

    /**
     * Display a paginated feed of published observations.
     */
    public function index(): View
    {
        $observations = $this->observationService->getPublishedFeed();

        return view('observations.index', compact('observations'));
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
    public function show(int $observation): View
    {
        $observation = $this->observationService->findPublishedById($observation);

        return view('observations.show', compact('observation'));
    }
}
