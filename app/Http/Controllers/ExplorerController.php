<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\ConnectionShortcut;
use App\Services\Explorer\FavoriteService;
use App\Services\Explorer\FileExplorerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ExplorerController extends Controller
{
    public function __construct(
        private readonly FileExplorerService $explorer,
        private readonly FavoriteService     $favorites,
    ) {}

    public function show(Connection $connection): View
    {
        $favorites  = $this->favorites->getForConnection($connection);
        $shortcuts  = $connection->shortcuts;

        return view('explorer.show', compact('connection', 'favorites', 'shortcuts'));
    }

    public function connect(Connection $connection): JsonResponse
    {
        try {
            $result = $this->explorer->connect($connection);

            return response()->json($result);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao conectar: ' . $e->getMessage(),
            ]);
        }
    }

    public function directory(Connection $connection, Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        try {
            $path    = $request->input('path');
            $entries = $this->explorer->listDirectory($connection, $path);

            return response()->json([
                'success' => true,
                'path'    => $path,
                'entries' => $entries,
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function readFile(Connection $connection, Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        try {
            $path = $request->input('path');
            $file = $this->explorer->readFile($connection, $path);

            return response()->json([
                'success'  => true,
                'path'     => $path,
                'name'     => basename($path),
                'content'  => $file['content'],
                'language' => $this->detectLanguage($path),
                'size'     => $file['size'],
                'modified' => $file['modified'],
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function saveFile(Connection $connection, Request $request): JsonResponse
    {
        $request->validate([
            'path'    => ['required', 'string'],
            'content' => ['present', 'string'],
        ]);

        try {
            $this->explorer->saveFile(
                $connection,
                $request->input('path'),
                $request->input('content'),
            );

            return response()->json(['success' => true, 'message' => 'Arquivo salvo com sucesso.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function search(Connection $connection, Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);

        try {
            $results = $this->explorer->searchFiles($connection, $request->input('q'));

            return response()->json(['success' => true, 'results' => $results]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addFavorite(Connection $connection, Request $request): JsonResponse
    {
        $request->validate([
            'path'  => ['required', 'string'],
            'label' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $fav = $this->favorites->add(
                $connection,
                $request->input('path'),
                $request->input('label'),
            );

            return response()->json([
                'success'  => true,
                'favorite' => ['id' => $fav->id, 'path' => $fav->path, 'label' => $fav->display_label],
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function removeFavorite(Connection $connection, Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        try {
            $this->favorites->remove($connection, $request->input('path'));

            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function createFile(Connection $connection, Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);
        try {
            $this->explorer->createFile($connection, $request->input('path'));
            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function createDir(Connection $connection, Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);
        try {
            $this->explorer->createDirectory($connection, $request->input('path'));
            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function rename(Connection $connection, Request $request): JsonResponse
    {
        $request->validate(['from' => ['required', 'string'], 'to' => ['required', 'string']]);
        try {
            $this->explorer->rename($connection, $request->input('from'), $request->input('to'));
            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delete(Connection $connection, Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);
        try {
            $this->explorer->delete($connection, $request->input('path'));
            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function duplicateFile(Connection $connection, Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'string'],
            'to'   => ['required', 'string'],
        ]);
        try {
            $this->explorer->copyFile($connection, $request->input('from'), $request->input('to'));
            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addShortcut(Connection $connection, Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'path' => ['required', 'string'],
            'icon' => ['nullable', 'string', 'max:60'],
        ]);

        try {
            $maxOrder = $connection->shortcuts()->max('sort_order') ?? -1;

            $shortcut = $connection->shortcuts()->create([
                'name'       => $request->input('name'),
                'path'       => $request->input('path'),
                'icon'       => $request->input('icon', 'bi-folder-symlink-fill'),
                'sort_order' => $maxOrder + 1,
            ]);

            return response()->json([
                'success'  => true,
                'shortcut' => $this->shortcutData($shortcut),
            ]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function removeShortcut(Connection $connection, ConnectionShortcut $shortcut): JsonResponse
    {
        try {
            abort_if($shortcut->connection_id !== $connection->id, 403);
            $shortcut->delete();
            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateShortcut(Connection $connection, ConnectionShortcut $shortcut, Request $request): JsonResponse
    {
        $request->validate([
            'name'       => ['sometimes', 'string', 'max:60'],
            'path'       => ['sometimes', 'string'],
            'icon'       => ['sometimes', 'string', 'max:60'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);
        try {
            abort_if($shortcut->connection_id !== $connection->id, 403);
            $shortcut->update($request->only(['name', 'path', 'icon', 'sort_order']));
            return response()->json(['success' => true, 'shortcut' => $this->shortcutData($shortcut->fresh())]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function shortcutData(ConnectionShortcut $s): array
    {
        return ['id' => $s->id, 'name' => $s->name, 'path' => $s->path, 'icon' => $s->icon, 'sort_order' => $s->sort_order];
    }

    private function detectLanguage(string $path): string
    {
        $name = strtolower(basename($path));
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Special filenames
        return match(true) {
            in_array($name, ['.env', '.env.example', '.env.local', '.env.production', '.env.testing']) => 'ini',
            str_ends_with($name, '.blade.php') => 'html',
            in_array($name, ['dockerfile', 'dockerfile.dev', 'dockerfile.prod']) => 'dockerfile',
            $name === 'makefile' => 'makefile',
            default => match($ext) {
                'php'            => 'php',
                'js', 'mjs'      => 'javascript',
                'ts'             => 'typescript',
                'tsx', 'jsx'     => 'javascript',
                'json'           => 'json',
                'html', 'htm'    => 'html',
                'css'            => 'css',
                'scss', 'sass'   => 'scss',
                'xml'            => 'xml',
                'sql'            => 'sql',
                'yml', 'yaml'    => 'yaml',
                'md', 'markdown' => 'markdown',
                'sh', 'bash'     => 'shell',
                'py'             => 'python',
                'go'             => 'go',
                'rs'             => 'rust',
                'java'           => 'java',
                'rb'             => 'ruby',
                'ini', 'conf'    => 'ini',
                'log'            => 'plaintext',
                'txt'            => 'plaintext',
                default          => 'plaintext',
            },
        };
    }
}
