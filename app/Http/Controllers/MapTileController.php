<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class MapTileController extends Controller
{
    public function show(int $z, int $x, int $y): Response
    {
        abort_unless((bool) config('tracking.tiles_enabled'), 404);
        abort_unless($z >= 0 && $z <= 19, 404);

        $maximum = (2 ** $z) - 1;
        abort_unless($x >= 0 && $x <= $maximum && $y >= 0 && $y <= $maximum, 404);

        $path = storage_path("app/map-tiles/{$z}/{$x}/{$y}.png");

        // Only serve tiles already present on the application server. Never fetch a
        // missing tile from a third-party service during a user request.
        abort_unless(File::exists($path), 404, 'Offline map tile is unavailable.');

        return response(File::get($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800, stale-if-error=2592000',
        ]);
    }
}
