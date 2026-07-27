<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HttpClientController extends Controller
{
    public function proxy(Request $request)
    {
        $validated = $request->validate([
            'url' => ['required', 'url'],
            'method' => ['required', 'string'],
            'headers' => ['nullable', 'array'],
            'body' => ['nullable'],
        ]);

        $method = strtoupper($validated['method']);
        $headers = $validated['headers'] ?? [];
        $body = $validated['body'] ?? null;

        $options = ['timeout' => 30, 'headers' => $headers];

        if ($body !== null && !in_array($method, ['GET', 'HEAD'], true)) {
            $options['body'] = $body;
        }

        $response = Http::send($method, $validated['url'], $options);

        $headers = [];
        foreach ($response->headers() as $name => $value) {
            $headers[$name] = is_array($value) ? implode(', ', $value) : $value;
        }

        $rawBody = $response->body();

        // Binary bodies (PDF, images, zip, etc.) aren't valid UTF-8, so json_encode
        // would silently corrupt them — send those as base64 and flag it for the frontend.
        $isBinary = ! mb_check_encoding($rawBody, 'UTF-8');

        return response()->json([
            'status' => $response->status(),
            'statusText' => $response->reason(),
            'headers' => $headers,
            'body' => $isBinary ? base64_encode($rawBody) : $rawBody,
            'bodyEncoding' => $isBinary ? 'base64' : 'text',
            'bodySize' => strlen($rawBody),
        ]);
    }
}
