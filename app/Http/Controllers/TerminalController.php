<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TerminalController extends Controller
{
    /**
     * Renders the standalone terminal tab for a connection. Issues a fresh
     * one-time token the page's WebSocket client uses to authenticate with
     * the `terminal:serve` gateway (which has no access to the web session).
     */
    public function openTab(Connection $connection): View
    {
        $token = Str::random(40);

        Cache::put("terminal_token:{$token}", [
            'user_id' => auth()->id(),
            'connection_id' => $connection->id,
            'cwd' => request()->query('path'),
        ], now()->addSeconds(config('terminal.token_ttl')));

        $wsHost = config('terminal.ws_host') ?: request()->getHost();

        // "localhost" often resolves to the IPv6 loopback (::1) first, but
        // Workerman only binds IPv4 (0.0.0.0) — the WS connection then hangs
        // instead of failing fast. Force the unambiguous IPv4 loopback.
        if ($wsHost === 'localhost') {
            $wsHost = '127.0.0.1';
        }

        return view('terminal.session', [
            'connection' => $connection,
            'token' => $token,
            'wsHost' => $wsHost,
            'wsPort' => config('terminal.ws_port'),
        ]);
    }
}
