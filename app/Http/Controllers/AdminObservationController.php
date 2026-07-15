<?php

namespace App\Http\Controllers;

use App\Models\Observation;
use App\Services\AdminService;
use App\Services\ObservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminObservationController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
        private readonly ObservationService $observationService,
    ) {}

    /**
     * Display a paginated list of all observations for admin moderation.
     */
    public function index(): View
    {
        $observations = $this->observationService->getAllObservations();

        return view('admin.observations.index', compact('observations'));
    }

    /**
     * Delete an observation (full cleanup including media).
     */
    public function destroy(Observation $observation): RedirectResponse
    {
        $this->observationService->deleteObservation($observation);

        return redirect()
            ->route('admin.observations.index')
            ->with('success', 'Observation deleted successfully.');
    }

    /**
     * Unpublish an observation (remove from public feed).
     */
    public function unpublish(Observation $observation): RedirectResponse
    {
        $this->adminService->unpublishObservation($observation->id);

        return redirect()
            ->route('admin.observations.index')
            ->with('success', 'Observation unpublished successfully.');
    }
}
