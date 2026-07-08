<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Review;
use App\Models\Like;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        foreach ($reviews as $review) {
            $likeUsers = $users
                ->where('id', '!=', $review->user_id)
                ->values()
                ->take(2);

            foreach ($likeUsers as $user) {
                Like::firstOrCreate([
                    'user_id' => $user->id,
                    'review_id' => $review->id,
                ]);
            }
        }
    }
}