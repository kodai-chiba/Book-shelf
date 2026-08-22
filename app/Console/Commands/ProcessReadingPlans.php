<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReadingPlan;
use App\Enums\ReadingPlanStatus;
use App\Notifications\ReadingPlanReminder;

class ProcessReadingPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plans:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期限切れ処理とリマインダー通知を実行する';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 期日を過ぎた進行中の読書計画を期限切れにする
        $expiredCount = ReadingPlan::where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('due_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Expired->value,
            ]);

        $this->info("{$expiredCount}件の読書計画を期限切れに更新しました。");

        // 期日の3日前
        ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('due_date', today()->addDays(3))
            ->get()
            ->each(function ($plan) {
                $plan->user->notify(
                    new ReadingPlanReminder($plan, 'three_days_before')
                );
            });

        // 期日当日
        ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress->value)
            ->whereDate('due_date', today())
            ->get()
            ->each(function ($plan) {
                $plan->user->notify(
                    new ReadingPlanReminder($plan, 'on_due_date')
                );
            });

        // 期日の3日後
        ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Expired->value)
            ->whereDate('due_date', today()->subDays(3))
            ->get()
            ->each(function ($plan) {
                $plan->user->notify(
                    new ReadingPlanReminder($plan, 'three_days_after')
                );
            });

        $this->info('リマインダー通知処理が完了しました。');

        return Command::SUCCESS;
    }
}
