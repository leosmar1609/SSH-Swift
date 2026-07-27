<?php

return [

    // Host the browser should open the WebSocket connection to. Left null by
    // default so it falls back to whatever host the browser is actually using
    // (request()->getHost()) — APP_URL is unreliable here since it often
    // points at the production domain even in local dev.
    'ws_host' => env('TERMINAL_WS_HOST'),

    // Port the `terminal:serve` Workerman server listens on.
    'ws_port' => (int) env('TERMINAL_WS_PORT', 6001),

    // How long a one-time auth token (issued when opening a terminal tab) stays valid, in seconds.
    'token_ttl' => 30,

];
