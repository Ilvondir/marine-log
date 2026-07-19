<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Observation;
use App\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function __construct(
        private readonly FavoriteService $favoriteService,
    ) {}

    /**
     * Toggle favorite state for the authenticated user.
     */
    public function toggle(Request $request, Observation $observation): JsonResponse
    {
        try {
            $result = $this->favoriteService->toggleFavorite(
                userId: $request->user()->id,
                observationId: $observation->id,
            );

            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Display the authenticated user's favorited observations.
     */
    public function index(Request $request): View
    {
        $observations = $this->favoriteService->getUserFavorites($request->user()->id);

        return view('observations.favorites', compact('observations'));
    }
}
