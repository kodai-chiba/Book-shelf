<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    public function __construct(
        private ReadingPlan $readingPlan,
        private string $timing
    ) {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $title = '読書計画のお知らせ';

        $body = match ($this->timing) {
            'three_days_before' => "「{$this->readingPlan->book->title}」の読書期限まであと3日です。",
            'on_due_date' => "「{$this->readingPlan->book->title}」の読書期限は今日です。",
            'three_days_after' => "「{$this->readingPlan->book->title}」の読書期限を3日過ぎています。",
            default => "「{$this->readingPlan->book->title}」の読書計画を確認してください。",
        };

        return [
            'title' => $title,
            'body' => $body,
            'timing' => $this->timing,
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
        ];
    }
}