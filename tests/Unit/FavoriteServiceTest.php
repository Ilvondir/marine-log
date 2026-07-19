<?php

namespace Tests\Unit;

use App\Contracts\Repositories\FavoriteRepositoryInterface;
use App\Contracts\Repositories\ObservationRepositoryInterface;
use App\Models\Observation;
use App\Services\FavoriteService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class FavoriteServiceTest extends TestCase
{
    private MockInterface $favoriteRepository;

    private MockInterface $observationRepository;

    private FavoriteService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->favoriteRepository = Mockery::mock(FavoriteRepositoryInterface::class);
        $this->observationRepository = Mockery::mock(ObservationRepositoryInterface::class);

        $this->service = new FavoriteService(
            $this->favoriteRepository,
            $this->observationRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_toggle_throws_for_unpublished_observation(): void
    {
        $observation = new Observation;
        $observation->id = 1;
        $observation->published_at = null;

        $this->observationRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($observation);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot favorite an unpublished observation.');

        $this->service->toggleFavorite(userId: 10, observationId: 1);
    }

    public function test_toggle_returns_favorited_true_when_adding(): void
    {
        $observation = Mockery::mock(Observation::class)->makePartial();
        $observation->id = 5;
        $observation->shouldReceive('getAttribute')->with('published_at')->andReturn('2026-07-19 00:00:00');
        $observation->shouldReceive('getAttribute')->with('id')->andReturn(5);

        $this->observationRepository
            ->shouldReceive('findById')
            ->once()
            ->with(5)
            ->andReturn($observation);

        $this->favoriteRepository
            ->shouldReceive('toggle')
            ->once()
            ->with(10, 5)
            ->andReturn(true);

        $this->favoriteRepository
            ->shouldReceive('countForObservation')
            ->once()
            ->with(5)
            ->andReturn(1);

        $result = $this->service->toggleFavorite(userId: 10, observationId: 5);

        $this->assertTrue($result['favorited']);
        $this->assertEquals(1, $result['count']);
    }

    public function test_toggle_returns_favorited_false_when_removing(): void
    {
        $observation = Mockery::mock(Observation::class)->makePartial();
        $observation->id = 5;
        $observation->shouldReceive('getAttribute')->with('published_at')->andReturn('2026-07-19 00:00:00');
        $observation->shouldReceive('getAttribute')->with('id')->andReturn(5);

        $this->observationRepository
            ->shouldReceive('findById')
            ->once()
            ->with(5)
            ->andReturn($observation);

        $this->favoriteRepository
            ->shouldReceive('toggle')
            ->once()
            ->with(10, 5)
            ->andReturn(false);

        $this->favoriteRepository
            ->shouldReceive('countForObservation')
            ->once()
            ->with(5)
            ->andReturn(0);

        $result = $this->service->toggleFavorite(userId: 10, observationId: 5);

        $this->assertFalse($result['favorited']);
        $this->assertEquals(0, $result['count']);
    }
}
