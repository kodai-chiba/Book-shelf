<?php

namespace Database\Seeders;

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
            1 => [
                '自分には合わず、最後まで読むのが大変でした。',
                '内容が少し分かりにくく、期待とは違いました。',
                'もう少し詳しい説明が欲しいと感じました。',
            ],
            2 => [
                '参考になる部分もありましたが、少し物足りなかったです。',
                '興味深い内容でしたが、やや読みづらく感じました。',
                '期待していた内容とは少し違いました。',
            ],
            3 => [
                '全体的に読みやすく、参考になりました。',
                '興味深い内容で、楽しく読むことができました。',
                '良い部分も多く、勉強になりました。',
            ],
            4 => [
                'とても読みやすく、内容も分かりやすかったです。',
                '学びが多く、もう一度読み返したい一冊です。',
                '実生活にも活かせる考え方が多かったです。',
            ],
            5 => [
                '非常に素晴らしい内容で、多くの学びがありました。',
                '何度でも読み返したいと思える一冊です。',
                'とても印象に残り、人にもおすすめしたい本です。',
            ],
        ];

        foreach (range(1, 5) as $rating) {
            $reviewCount = rand(2, 4);

            for ($i = 0; $i < $reviewCount; $i++) {
                $book = $books->random();

                $availableUsers = $users->filter(function ($user) use ($book) {
                    return !Review::where('user_id', $user->id)
                        ->where('book_id', $book->id)
                        ->exists();
                });

                if ($availableUsers->isEmpty()) {
                    continue;
                }

                $user = $availableUsers->random();

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => collect($comments[$rating])->random(),
                ]);
            }
        }
    }
}