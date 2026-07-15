<?php

namespace Tests\Unit;

use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Observation;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class AdminServiceTest extends TestCase
{
    private MockInterface $userRepository;

    private MockInterface $observationRepository;

    private AdminService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->observationRepository = Mockery::mock(ObservationRepositoryInterface::class);

        $this->service = new AdminService(
            $this->userRepository,
            $this->observationRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_block_user_throws_when_target_is_admin(): void
    {
        $role = new Role;
        $role->name = Role::ADMIN;

        $admin = new User;
        $admin->id = 1;
        $admin->setRelation('role', $role);

        $target = new User;
        $target->id = 2;
        $target->setRelation('role', $role);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot block an admin account.');

        $this->service->blockUser($admin, $target);
    }

    public function test_block_user_throws_when_target_is_self(): void
    {
        $role = new Role;
        $role->name = Role::ADMIN;

        $admin = new User;
        $admin->id = 1;
        $admin->setRelation('role', $role);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot block your own account.');

        $this->service->blockUser($admin, $admin);
    }

    public function test_unpublish_observation_calls_repository_with_null_published_at(): void
    {
        $observation = new Observation;
        $observation->published_at = null;

        $this->observationRepository
            ->shouldReceive('update')
            ->once()
            ->with(5, ['published_at' => null])
            ->andReturn($observation);

        $result = $this->service->unpublishObservation(5);

        $this->assertNull($result->published_at);
    }
}
