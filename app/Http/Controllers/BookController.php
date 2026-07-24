<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Book;
use App\Http\Requests\BookRequest;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $books = Book::with('genres')->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookRequest $request)
    { 
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $validated['created_by'] = auth()->id();

        $book = Book::create($validated);

        $book->genres()->attach($genres);

        return redirect()->route('books.index')
            ->with('success', '書籍を登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load([
            'genres',
            'creator',
            'reviews',
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        $genres = Genre::all();

        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookRequest $request, Book $book)
    {
        $validated = $request->validated();

        $genres = $validated['genres'];
        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genres);

        return redirect()
            ->route('books.index')
            ->with('success', '書籍情報を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
