<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

interface UserRepositoryInterface
{
    /**
     * Paginate all users, newest first.
     *
     * @return LengthAwarePaginator<User>
     */
    public function paginateAll(int $perPage = 20): LengthAwarePaginator;

    /**
     * Find a user by their ID.
     *
     * @throws ModelNotFoundException
     */
    public function findById(int $id): User;

    /**
     * Update a user record.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): User;
}
