<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\ExplorerController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Auth ────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Authenticated ────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::get('/', fn() => redirect()->route('connections.index'));

    // ── Connections (CRUD) ───────────────────────────────────────────────────
    Route::resource('connections', ConnectionController::class);

    Route::post('connections/test-live', [ConnectionController::class, 'testLive'])
        ->name('connections.test.live');

    Route::post('connections/{connection}/test', [ConnectionController::class, 'test'])
        ->name('connections.test');

    Route::post('connections/{connection}/duplicate', [ConnectionController::class, 'duplicate'])
        ->name('connections.duplicate');

    // ── Explorer (File Manager IDE) ──────────────────────────────────────────
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
        Route::post('/fs/rename',    [ExplorerController::class, 'rename'])        ->name('fs.rename');
        Route::delete('/fs/delete',  [ExplorerController::class, 'delete'])        ->name('fs.delete');
        Route::post('/fs/duplicate', [ExplorerController::class, 'duplicateFile'])->name('fs.duplicate');
        Route::post('/terminal',     [ExplorerController::class, 'terminal'])     ->name('terminal');

        Route::post('/shortcuts',              [ExplorerController::class, 'addShortcut'])    ->name('shortcuts.add');
        Route::delete('/shortcuts/{shortcut}', [ExplorerController::class, 'removeShortcut'])->name('shortcuts.remove');
        Route::patch('/shortcuts/{shortcut}',  [ExplorerController::class, 'updateShortcut'])->name('shortcuts.update');
    });

    // ── Docs generator ──────────────────────────────────────────────────────
    Route::get('/docs', fn() => view('docs.index'))->name('docs.index');

    // ── HTTP Client ──────────────────────────────────────────────────────────
    Route::get('/httpclient', fn() => view('httpclient.index'))->name('httpclient.index');
    Route::post('/httpclient/proxy', [\App\Http\Controllers\HttpClientController::class, 'proxy'])->name('httpclient.proxy');

    // ── JSON Formatter ───────────────────────────────────────────────────────
    Route::get('/jsonformatter', fn() => view('jsonformatter.index'))->name('jsonformatter.index');

    // ── XML / HTML Formatter ─────────────────────────────────────────────────
    Route::get('/xmlformatter', fn() => view('xmlformatter.index'))->name('xmlformatter.index');

    // ── Users (admin only) ───────────────────────────────────────────────────
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });
});
