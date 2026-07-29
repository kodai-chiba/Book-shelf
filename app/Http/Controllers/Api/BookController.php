<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Requests\BookRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');
        
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%")
                    ->orWhere('isbn', 'like', "%{$keyword}%");
            });
        }

        switch ($request->input('sort')) {
            case 'oldest':
                $query->orderBy('created_at');
                break;

            case 'rating_desc':
                $query->orderByDesc('reviews_avg_rating');
                break;

            case 'rating_asc':
                $query->orderBy('reviews_avg_rating');
                break;

            default:
                $query->orderByDesc('created_at');
                break;
        }

        $books = $query->paginate(10);

        return BookResource::collection($books);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookRequest $request)
    { 
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $validated['created_by'] = 1;

        $book = Book::create($validated);

        $book->genres()->attach($genres);

        $book->load('genres');

        return new BookResource($book)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): BookResource
    {
        $book->load(['genres', 'reviews.user']);

        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookRequest $request, Book $book): BookResource
    {
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genres);

        $book->load('genres');

        return new BookResource($book);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $book->delete();

        return response()->json([
            'message' => '書籍を削除しました。'
        ]);
    }
}
