<?php

namespace App\Libs\Facades;

use App\Libs\Amap\Data\GeocodeResult;
use App\Libs\Amap\Data\RegeocodeResult;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<int, GeocodeResult> geocode(string $address, ?string $city = null)
 * @method static GeocodeResult|null geocodeFirst(string $address, ?string $city = null)
 * @method static RegeocodeResult|null regeocode(float|string $longitude, float|string $latitude, array $options = [])
 *
 * @see \App\Libs\Amap\Amap
 */
class Amap extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Libs\Amap\Amap::class;
    }
}
