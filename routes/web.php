<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ReviewController;
use App\Models\Book;

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

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
        ->name('reviews.edit');

    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->name('reviews.update');

    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->name('reviews.destroy');
    
    //お気に入り機能実装前の仮ルート
    Route::post('/books/{book}/favorite', function (Book $book) {
        return redirect()->route('books.show', $book);
    })->name('favorites.toggle');

    //いいね機能実装前の仮ルート
    Route::post('/reviews/{review}/like', function ($review) {
    return back();
    })->name('reviews.like');

    Route::get('/ranking', fn () => 'ランキング準備中')->name('ranking.index');
    Route::get('/favorites', fn () => 'お気に入り準備中')->name('favorites.index');
});
