<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpClientProxyTest extends TestCase
{
    public function test_http_client_proxy_forwards_request_and_returns_response(): void
    {
        Http::fake([
            'https://example.com/test' => Http::response('{"ok":true}', 200, [
                'Content-Type' => 'application/json',
                'X-Test' => '1',
            ]),
        ]);

        $this->withoutMiddleware();

        $response = $this->postJson('/httpclient/proxy', [
            'url' => 'https://example.com/test',
            'method' => 'POST',
            'headers' => ['X-Test' => '1'],
            'body' => '{"hello":"world"}',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 200)
            ->assertJsonPath('body', '{"ok":true}');
    }
}
