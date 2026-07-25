<?php

namespace App\Http\Controllers;

use App\Models\Review;

class LikeController extends Controller
{
    public function toggle(Review $review)
    {
        auth()->user()
            ->likedReviews()
            ->toggle($review->id);

        return back();
    }
}
