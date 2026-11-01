<?php

return [
    // Map assets are local. A licensed tile package can be enabled explicitly by environment.
    'tile_url' => env('MAP_TILE_URL', '/maps/offline-grid.svg'),
    'tiles_enabled' => env('MAP_TILES_ENABLED', false),
    // Kept for view/config compatibility; remote tile fetching is disabled in code.
    'online_tiles_enabled' => false,
    'online_timeout_minutes' => env('TRACKING_ONLINE_TIMEOUT_MINUTES', 15),
    'recent_days' => env('TRACKING_RECENT_DAYS', 7),
];
