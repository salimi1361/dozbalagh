<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class MapTileController extends Controller
{
    public function show(int $z, int $x, int $y): Response
    {
        abort_unless((bool) config('tracking.online_tiles_enabled'), 404);
        abort_unless($z >= 0 && $z <= 19, 404);

        $maximum = (2 ** $z) - 1;
        abort_unless($x >= 0 && $x <= $maximum && $y >= 0 && $y <= $maximum, 404);

        $path = storage_path("app/map-tiles/{$z}/{$x}/{$y}.png");
        $freshUntil = now()->subDays(7)->timestamp;

        if (! File::exists($path) || File::lastModified($path) < $freshUntil) {
            try {
                $upstream = Http::withHeaders([
                    'User-Agent' => 'DozolehFleetMap/1.0 (+'.config('app.url').')',
                    'Accept' => 'image/png,image/*;q=0.8',
                ])->connectTimeout(5)->timeout(12)->get("https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png");

                if ($upstream->successful() && str_starts_with((string) $upstream->header('Content-Type'), 'image/')) {
                    File::ensureDirectoryExists(dirname($path));
                    File::put($path, $upstream->body(), true);
                }
            } catch (ConnectionException) {
                // A previously cached tile remains usable if the upstream is temporarily unreachable.
            }
        }

        abort_unless(File::exists($path), 503, 'Online map tile is temporarily unavailable.');

        return response(File::get($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800, stale-if-error=2592000',
        ]);
    }
}
