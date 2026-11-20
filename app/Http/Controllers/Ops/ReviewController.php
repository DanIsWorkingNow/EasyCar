<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

/** Review moderation: hide/publish and reply publicly. Staff see their branch's cars. */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->input('filter', 'all');

        $base = Review::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('car', fn ($c) => $c->where('branch_id', $user->branch_id)));

        $reviews = (clone $base)->with(['car', 'user', 'booking'])
            ->when($filter === 'hidden', fn ($q) => $q->where('is_published', false))
            ->when($filter === 'unanswered', fn ($q) => $q->whereNull('reply')->where('is_published', true))
            ->when($filter === 'low', fn ($q) => $q->where('rating', '<=', 2))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('ops.reviews.index', [
            'reviews' => $reviews,
            'filter' => $filter,
            'average' => round((float) (clone $base)->where('is_published', true)->avg('rating'), 2),
            'total' => (clone $base)->count(),
            'unanswered' => (clone $base)->whereNull('reply')->where('is_published', true)->count(),
        ]);
    }

    public function update(Request $request, Review $review)
    {
        $this->authorizeBranch($request, $review);

        $data = $request->validate([
            'is_published' => 'sometimes|boolean',
            'reply' => 'sometimes|nullable|string|max:2000',
        ]);

        if (array_key_exists('reply', $data)) {
            $review->fill([
                'reply' => $data['reply'] ?: null,
                'replied_by' => $data['reply'] ? $request->user()->id : null,
                'replied_at' => $data['reply'] ? now() : null,
            ]);
        }
        if (array_key_exists('is_published', $data)) {
            $review->is_published = (bool) $data['is_published'];
        }
        $review->save();

        return back()->with('success', match (true) {
            array_key_exists('is_published', $data) => $review->is_published ? 'Review published.' : 'Review hidden from the storefront.',
            default => $review->reply ? 'Reply posted.' : 'Reply removed.',
        });
    }

    private function authorizeBranch(Request $request, Review $review): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $review->car?->branch_id === $user->branch_id, 403);
    }
}
