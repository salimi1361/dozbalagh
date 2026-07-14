Local offline map tiles belong in this directory using the standard structure:

    {z}/{x}/{y}.png

The application never requests a third-party tile service. Until a licensed Iran tile package is copied here, tracking points and paths render over `/maps/offline-grid.svg`.

After copying a local tile package, set this server environment value and clear the config cache:

    MAP_TILE_URL=/maps/tiles/{z}/{x}/{y}.png
