<?php

namespace App\Services\Explorer;

use App\Models\Connection;
use App\Services\SSH\SFTPService;
use App\Services\SSH\SSHService;
use Illuminate\Support\Facades\Session;
use RuntimeException;

class FileExplorerService
{
    public function __construct(
        private readonly SFTPService $sftp,
        private readonly SSHService  $ssh,
    ) {}

    public function connect(Connection $connection): array
    {
        $result = $this->ssh->testConnection($connection);

        if (! $result['success']) {
            return $result;
        }

        $root = $result['directory'] ?? '/';

        Session::put("explorer.{$connection->id}.root", $root);
        Session::put("explorer.{$connection->id}.connected", true);

        return [
            'success' => true,
            'root'    => $root,
            'message' => $result['message'],
        ];
    }

    public function listDirectory(Connection $connection, string $path): array
    {
        $this->guardPath($connection, $path);

        return $this->sftp->listDirectory($connection, $path);
    }

    public function readFile(Connection $connection, string $path): array
    {
        $this->guardPath($connection, $path);

        return $this->sftp->readFile($connection, $path);
    }

    public function saveFile(Connection $connection, string $path, string $content): void
    {
        $this->guardPath($connection, $path);

        $this->sftp->writeFile($connection, $path, $content);
    }

    public function searchFiles(Connection $connection, string $query): array
    {
        $root = $this->getRoot($connection);

        return $this->sftp->searchFiles($connection, $root, $query);
    }

    public function createFile(Connection $connection, string $path): void
    {
        $this->guardPath($connection, $path);
        $this->sftp->createFile($connection, $path);
    }

    public function createDirectory(Connection $connection, string $path): void
    {
        $this->guardPath($connection, $path);
        $this->sftp->createDirectory($connection, $path);
    }

    public function rename(Connection $connection, string $from, string $to): void
    {
        $this->guardPath($connection, $from);
        $this->guardPath($connection, $to);
        $this->sftp->rename($connection, $from, $to);
    }

    public function delete(Connection $connection, string $path): void
    {
        $this->guardPath($connection, $path);
        $this->sftp->delete($connection, $path);
    }

    public function copyFile(Connection $connection, string $from, string $to): void
    {
        $this->guardPath($connection, $from);
        $this->guardPath($connection, $to);
        $this->sftp->copyFile($connection, $from, $to);
    }

    public function tailFile(Connection $connection, string $path, int $offset): array
    {
        $this->guardPath($connection, $path);
        return $this->sftp->tailFile($connection, $path, $offset);
    }

    public function getRoot(Connection $connection): string
    {
        return Session::get("explorer.{$connection->id}.root", '/');
    }

    public function isConnected(Connection $connection): bool
    {
        return (bool) Session::get("explorer.{$connection->id}.connected", false);
    }

    public function disconnect(Connection $connection): void
    {
        Session::forget("explorer.{$connection->id}");
    }

    private function guardPath(Connection $connection, string $path): void
    {
        $normalized = $this->normalizePath($path);
        $root       = $this->normalizePath($this->getRoot($connection));

        // Root '/' means no session whitelist is set — allow everything.
        if ($root === '/') {
            return;
        }

        if (! str_starts_with($normalized . '/', $root . '/') && $normalized !== $root) {
            throw new RuntimeException("Acesso negado: '{$path}' está fora do diretório permitido.");
        }
    }

    private function normalizePath(string $path): string
    {
        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }

        return '/' . implode('/', $parts);
    }
}
