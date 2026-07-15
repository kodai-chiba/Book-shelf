<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GenreController;

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
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::resource('genres', GenreController::class);

    Route::get('/books', fn() => '書籍準備中')->name('books.index');
    Route::get('/books/create', fn () => '書籍登録準備中')->name('books.create');
    Route::get('/ranking', fn () => 'ランキング準備中')->name('ranking.index');
    Route::get('/favorites', fn () => 'お気に入り準備中')->name('favorites.index');
});
