<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $books = Book::all();

        if ($users->isEmpty() || $books->isEmpty()) {
            return;
        }

        // 主要な動作確認用ユーザー
        $mainUser = User::where('email', 'yamada@example.com')->first()
            ?? $users->first();

        // 別ユーザー
        $otherUser = $users->where('id', '!=', $mainUser->id)->first();

        $today = Carbon::today();

        $mainBooks = $books->take(5)->values();

        // ① 3日後：3日前リマインダー発火対象
        if (isset($mainBooks[0])) {
            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $mainUser->id,
                    'book_id' => $mainBooks[0]->id,
                ],
                [
                    'due_date' => $today->copy()->addDays(3),
                    'status' => ReadingPlanStatus::InProgress,
                    'completed_at' => null,
                ]
            );
        }

        // ② 今日：当日リマインダー発火対象
        if (isset($mainBooks[1])) {
            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $mainUser->id,
                    'book_id' => $mainBooks[1]->id,
                ],
                [
                    'due_date' => $today->copy(),
                    'status' => ReadingPlanStatus::InProgress,
                    'completed_at' => null,
                ]
            );
        }

        // ③ 3日前：期限超過3日後リマインダー発火対象
        if (isset($mainBooks[2])) {
            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $mainUser->id,
                    'book_id' => $mainBooks[2]->id,
                ],
                [
                    'due_date' => $today->copy()->subDays(3),
                    'status' => ReadingPlanStatus::Expired,
                    'completed_at' => null,
                ]
            );
        }

        // ④ 7日後：リマインダー非発火パターン
        if (isset($mainBooks[3])) {
            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $mainUser->id,
                    'book_id' => $mainBooks[3]->id,
                ],
                [
                    'due_date' => $today->copy()->addDays(7),
                    'status' => ReadingPlanStatus::InProgress,
                    'completed_at' => null,
                ]
            );
        }

        // ⑤ 完了済み：リマインダー非発火パターン
        if (isset($mainBooks[4])) {
            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $mainUser->id,
                    'book_id' => $mainBooks[4]->id,
                ],
                [
                    'due_date' => $today->copy()->addDays(3),
                    'status' => ReadingPlanStatus::Completed,
                    'completed_at' => $today->copy()->subDay(),
                ]
            );
        }

        // ⑥ 別ユーザー用データ
        if ($otherUser && isset($books[5])) {
            ReadingPlan::updateOrCreate(
                [
                    'user_id' => $otherUser->id,
                    'book_id' => $books[5]->id,
                ],
                [
                    'due_date' => $today->copy()->addDays(5),
                    'status' => ReadingPlanStatus::InProgress,
                    'completed_at' => null,
                ]
            );
        }
    }
}