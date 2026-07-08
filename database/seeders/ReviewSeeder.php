<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        $comments = [
            'とても読みやすく、内容も分かりやすかったです。',
            '学びが多く、もう一度読み返したい一冊です。',
            '初心者にも理解しやすい内容でした。',
            '実生活にも活かせる考え方が多かったです。',
            '構成が分かりやすく、最後まで楽しく読めました。',
            '印象に残る内容が多く、読後感も良かったです。',
            'テーマが興味深く、深く考えさせられました。',
            '具体例が多く、内容をイメージしやすかったです。',
        ];

        $count = 0;

        foreach ($books as $bookIndex => $book) {
            foreach ($users as $userIndex => $user) {
                if ($count >= 32) {
                    break 2;
                }

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => rand(3, 5),
                    'comment' => $comments[$count % count($comments)],
                ]);

                $count++;
            }
        }
    }
}