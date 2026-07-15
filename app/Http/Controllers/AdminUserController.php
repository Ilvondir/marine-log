<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Display a paginated list of all users for admin management.
     */
    public function index(): View
    {
        $users = $this->userRepository->paginateAll();

        return view('admin.users.index', compact('users'));
    }

    /**
     * Block a user account.
     */
    public function block(Request $request, User $user): RedirectResponse
    {
        try {
            $this->adminService->blockUser($request->user(), $user);

            return redirect()
                ->route('admin.users.index')
                ->with('success', "User {$user->name} has been blocked.");
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Unblock a user account.
     */
    public function unblock(User $user): RedirectResponse
    {
        $this->adminService->unblockUser($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} has been unblocked.");
    }
}
