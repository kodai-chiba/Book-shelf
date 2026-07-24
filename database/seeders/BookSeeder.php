<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Book;
use App\Models\Genre;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        
        $books = [
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_date' => '1905-01-01',
                'description' => '夏目漱石の代表作。猫の視点から人間社会を風刺した名作。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
                'genres' => ['小説'],
            ],

            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_date' => '1905-01-07',
                'description' => '人間関係やコミュニケーションの原則を学べるビジネス書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
                'genres' => ['ビジネス', '自己啓発'],
            ],

            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '1985-01-01',
                'description' => '読みやすく保守しやすいコードを書くための考え方を解説した技術書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',
                'genres' => ['技術書'],
            ],

            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_date' => '1935-01-01',
                'description' => '主体的に生きるための習慣と考え方を体系的にまとめた自己啓発書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=4',
                'genres' => ['ビジネス', '自己啓発'],
            ],

            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_date' => '1909-01-01',
                'description' => '正義感の強い主人公が地方の学校で奮闘する痛快な小説。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=5',
                'genres' => ['小説'],
            ],

            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_date' => '1705-01-01',
                'description' => '人類の歴史を文明・社会・科学の視点から読み解く一冊。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=6',
                'genres' => ['歴史', '科学'],
            ],

            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_date' => '1965-01-01',
                'description' => '質の高いソフトウェアを作るためのコード設計と実践を学べる技術書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=7',
                'genres' => ['技術書'],
            ],

            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_date' => '1945-01-01',
                'description' => 'アドラー心理学をもとに、自分らしく生きるための考え方を示した本。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=8',
                'genres' => ['自己啓発'],
            ],

            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_date' => '2013-01-01',
                'description' => '芸人の世界を舞台に、才能や葛藤を描いた小説。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=9',
                'genres' => ['小説'],
            ],

            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_date' => '1999-01-01',
                'description' => 'データをもとに世界を正しく見るための考え方を学べる本。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=10',
                'genres' => ['ビジネス', '科学'],
            ],

            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_date' => '1805-01-01',
                'description' => 'コンテナが物流と世界経済に与えた影響を描いた歴史書。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=11',
                'genres' => ['ビジネス', '歴史'],
            ],
        ];

        foreach ($books as $bookData) {
            $genreNames = $bookData['genres'];
            unset($bookData['genres']);

            $book = Book::firstOrCreate(
                ['isbn' => $bookData['isbn']],
                [
                    ...$bookData,
                    'created_by' => $user->id,
                ]
            );

            $genreIds = Genre::whereIn('name', $genreNames)->pluck('id');

            $book->genres()->sync($genreIds);
        }
    }
}
