<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * Paginate all users, newest first.
     *
     * @return LengthAwarePaginator<User>
     */
    public function paginateAll(int $perPage = 20): LengthAwarePaginator
    {
        try {
            return User::query()
                ->with('role')
                ->latest('created_at')
                ->paginate($perPage);
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'paginateAll',
                'operation' => 'select',
                'entity_id' => null,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Find a user by their ID.
     *
     * @throws ModelNotFoundException
     */
    public function findById(int $id): User
    {
        try {
            /** @var User */
            return User::query()->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning('User not found.', [
                'repository' => self::class,
                'method' => 'findById',
                'operation' => 'select',
                'entity_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update a user record.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): User
    {
        try {
            $user = User::query()->findOrFail($id);
            $user->update($data);

            return $user->fresh();
        } catch (\Throwable $e) {
            Log::error('Repository operation failed.', [
                'repository' => self::class,
                'method' => 'update',
                'operation' => 'update',
                'entity_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
