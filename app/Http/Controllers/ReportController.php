<?php

namespace App\Http\Controllers;

use App\Models\ReadingPlan;
use App\Models\Review;

class ReportController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $totalReviews = Review::where('user_id', $userId)->count();

        $booksRead = ReadingPlan::where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        $averageRating = Review::where('user_id', $userId)
            ->avg('rating') ?? 0;

        $ratingDistribution = collect(range(1, 5))->map(function ($rating) use ($userId) {
            return Review::where('user_id', $userId)
                ->where('rating', $rating)
                ->count();
        });

        $topRatedBooks = Review::with('book')
            ->where('user_id', $userId)
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->limit(5)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,
                ];
            });

        $genreRatings = Review::with('book.genres')
            ->where('user_id', $userId)
            ->get()
            ->flatMap(function ($review) {
                return $review->book->genres->map(function ($genre) use ($review) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                    ];
                });
            })
            ->groupBy('id')
            ->map(function ($reviews) {
                return [
                    'id' => $reviews->first()['id'],
                    'name' => $reviews->first()['name'],
                    'count' => $reviews->count(),
                    'average_rating' => $reviews->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $booksRead,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}