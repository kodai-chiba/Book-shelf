<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\BookController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('genres.index');
});

Route::middleware('auth')->group(function () {
    Route::resource('genres', GenreController::class);
    Route::resource('books', BookController::class);

    Route::get('/ranking', fn () => 'ランキング準備中')->name('ranking.index');
    Route::get('/favorites', fn () => 'お気に入り準備中')->name('favorites.index');
});
