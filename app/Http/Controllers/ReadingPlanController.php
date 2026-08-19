<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Enums\ReadingPlanStatus;
use App\Http\Requests\ReadingPlanRequest;

class ReadingPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentStatus = $request->input('status');

        $query = ReadingPlan::with('book')
            ->where('user_id', auth()->id());

        if ($currentStatus) {
            $query->where('status', $currentStatus);
        }

        $readingPlans = $query
            ->orderBy('due_date')
            ->paginate(10)
            ->withQueryString();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReadingPlanRequest $request)
    {
        $validated = $request->validated();

        ReadingPlan::create([
            'user_id' => auth()->id(),
            'book_id' => $validated['book_id'],
            'due_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を登録しました。');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReadingPlan $readingPlan)
    {
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403);
        }

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ReadingPlanRequest $request, ReadingPlan $readingPlan)
    {
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403);
        } 

        $validated = $request->validated();

        $readingPlan->update([
            'due_date' => $validated['target_date'],
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReadingPlan $readingPlan)
    {
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403);
        }

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    public function complete(ReadingPlan $readingPlan)
    {
        if ($readingPlan->user_id !== auth()->id()) {
            abort(403);
        }

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を完了しました。');
    } 
}
