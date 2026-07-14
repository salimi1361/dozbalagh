<?php

return [
    // The browser only requests same-origin URLs. Online tiles are proxied and cached by this app.
    'tile_url' => env('MAP_TILE_URL', '/maps/online/{z}/{x}/{y}.png'),
    'tiles_enabled' => env('MAP_TILES_ENABLED', true),
    'online_tiles_enabled' => env('MAP_ONLINE_TILES_ENABLED', true),
    'online_timeout_minutes' => env('TRACKING_ONLINE_TIMEOUT_MINUTES', 15),
];
