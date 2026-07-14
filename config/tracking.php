<?php

return [
    // Must remain a same-origin, self-hosted URL. A standard {z}/{x}/{y} template is supported.
    'tile_url' => env('MAP_TILE_URL', '/maps/offline-grid.svg'),
    'tiles_enabled' => env('MAP_TILES_ENABLED', false),
];
