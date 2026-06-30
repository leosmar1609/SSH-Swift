<?php

use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\ExplorerController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('connections.index'));

// ── Connections (CRUD) ──────────────────────────────────────────────────────
Route::resource('connections', ConnectionController::class);

Route::post('connections/test-live', [ConnectionController::class, 'testLive'])
    ->name('connections.test.live');

Route::post('connections/{connection}/test', [ConnectionController::class, 'test'])
    ->name('connections.test');

// ── Explorer (File Manager IDE) ─────────────────────────────────────────────
Route::prefix('explorer/{connection}')->name('explorer.')->group(function () {

    Route::get('/',              [ExplorerController::class, 'show'])          ->name('show');
    Route::post('/connect',      [ExplorerController::class, 'connect'])       ->name('connect');
    Route::get('/directory',     [ExplorerController::class, 'directory'])     ->name('directory');
    Route::get('/file',          [ExplorerController::class, 'readFile'])      ->name('file.read');
    Route::get('/log/tail',      [ExplorerController::class, 'tailLog'])       ->name('log.tail');
    Route::post('/file/save',    [ExplorerController::class, 'saveFile'])      ->name('file.save');
    Route::get('/search',        [ExplorerController::class, 'search'])        ->name('search');
    Route::post('/favorites',    [ExplorerController::class, 'addFavorite'])   ->name('favorites.add');
    Route::delete('/favorites',  [ExplorerController::class, 'removeFavorite'])->name('favorites.remove');

    Route::post('/fs/file',      [ExplorerController::class, 'createFile'])   ->name('fs.file');
    Route::post('/fs/dir',       [ExplorerController::class, 'createDir'])    ->name('fs.dir');
    Route::post('/fs/rename',    [ExplorerController::class, 'rename'])       ->name('fs.rename');
    Route::delete('/fs/delete',  [ExplorerController::class, 'delete'])       ->name('fs.delete');
    Route::post('/terminal',     [ExplorerController::class, 'terminal'])     ->name('terminal');

    Route::post('/shortcuts',              [ExplorerController::class, 'addShortcut'])    ->name('shortcuts.add');
    Route::delete('/shortcuts/{shortcut}', [ExplorerController::class, 'removeShortcut'])->name('shortcuts.remove');
    Route::patch('/shortcuts/{shortcut}',  [ExplorerController::class, 'updateShortcut'])->name('shortcuts.update');
});
