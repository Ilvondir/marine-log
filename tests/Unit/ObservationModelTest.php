<?php

namespace Tests\Unit;

use App\Models\Observation;
use PHPUnit\Framework\TestCase;

class ObservationModelTest extends TestCase
{
    public function test_observation_table_name_defaults_to_convention(): void
    {
        $observation = new Observation;

        $this->assertEquals('observations', $observation->getTable());
    }

    public function test_observation_casts_contain_expected_datetime_fields(): void
    {
        $observation = new Observation;
        $casts = $observation->getCasts();

        $this->assertArrayHasKey('observed_at', $casts);
        $this->assertArrayHasKey('published_at', $casts);
        $this->assertEquals('datetime', $casts['observed_at']);
        $this->assertEquals('datetime', $casts['published_at']);
    }

    public function test_observation_casts_contain_decimal_fields(): void
    {
        $observation = new Observation;
        $casts = $observation->getCasts();

        $this->assertArrayHasKey('latitude', $casts);
        $this->assertArrayHasKey('longitude', $casts);
        $this->assertArrayHasKey('water_temperature', $casts);
        $this->assertArrayHasKey('depth_meters', $casts);
        $this->assertEquals('decimal:7', $casts['latitude']);
        $this->assertEquals('decimal:7', $casts['longitude']);
        $this->assertEquals('decimal:1', $casts['water_temperature']);
        $this->assertEquals('decimal:1', $casts['depth_meters']);
    }

    public function test_observation_fillable_includes_required_fields(): void
    {
        $observation = new Observation;
        $fillable = $observation->getFillable();

        $this->assertContains('species', $fillable);
        $this->assertContains('observed_at', $fillable);
        $this->assertContains('latitude', $fillable);
        $this->assertContains('longitude', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('published_at', $fillable);
    }
}
