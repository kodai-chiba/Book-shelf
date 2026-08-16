<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Book;
use App\Http\Requests\BookRequest;
use Illuminate\Support\Facades\Http;

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
            'reviews.user',
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

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
        $this->authorize('delete', $book);
        
        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }

    public function searchByIsbn(string $isbn)
    {
        if (!preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の数字で入力してください。',
            ], 422);
        }

        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q' => 'isbn:' . $isbn,
            'key' => config('services.google_books.key'),
        ]);

        if ($response->failed()) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 500);
        }

        $data = $response->json();

        if (empty($data['items'])) {
            return response()->json([
                'error' => '該当する書籍が見つかりませんでした。',
            ], 404);
        }

        $volumeInfo = $data['items'][0]['volumeInfo'];

        $publishedDate = $volumeInfo['publishedDate'] ?? null;

        if ($publishedDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $publishedDate)) {
            $publishedDate = null;
        }

        return response()->json([
            'title' => $volumeInfo['title'] ?? null,
            'author' => $volumeInfo['authors'][0] ?? null,
            'published_date' => $publishedDate,
            'description' => $volumeInfo['description'] ?? null,
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
            'isbn' => $isbn,
        ]);
    }
}
