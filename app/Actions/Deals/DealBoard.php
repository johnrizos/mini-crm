<?php

namespace App\Actions\Deals;

use App\Enums\DealStage;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the kanban order consistent: positions within a stage are always
 * 0..n-1, whatever order moves and deletes arrive in.
 */
class DealBoard
{
    /**
     * Move a deal to a stage (possibly the one it is in) at a zero-based position.
     */
    public function move(Deal $deal, DealStage $stage, int $position): void
    {
        DB::transaction(function () use ($deal, $stage, $position) {
            $from = $deal->stage;

            $ids = $this->idsIn($deal->user_id, $stage, except: $deal->id);
            array_splice($ids, min(max($position, 0), count($ids)), 0, [$deal->id]);

            if ($from !== $stage) {
                $deal->stage = $stage;
                $deal->closed_at = $stage->isClosed() ? now() : null;
                $deal->save();
            }

            $this->renumber($ids);

            if ($from !== $stage) {
                $this->renumber($this->idsIn($deal->user_id, $from));
            }
        });

        $deal->refresh();
    }

    /**
     * Put a new or re-staged deal at the bottom of its column.
     */
    public function appendTo(Deal $deal, DealStage $stage): void
    {
        $max = Deal::query()
            ->where('user_id', $deal->user_id)
            ->where('stage', $stage)
            ->when($deal->exists, fn ($q) => $q->whereKeyNot($deal->getKey()))
            ->max('position');

        $deal->stage = $stage;
        $deal->position = $max === null ? 0 : (int) $max + 1;
        $deal->closed_at = $stage->isClosed() ? ($deal->closed_at ?? now()) : null;
    }

    /**
     * Close the gap a deleted deal leaves behind.
     */
    public function closeGap(int $userId, DealStage $stage): void
    {
        $this->renumber($this->idsIn($userId, $stage));
    }

    /**
     * @return list<int>
     */
    private function idsIn(int $userId, DealStage $stage, ?int $except = null): array
    {
        $ids = Deal::query()
            ->where('user_id', $userId)
            ->where('stage', $stage)
            ->when($except, fn ($q) => $q->whereKeyNot($except))
            ->orderBy('position')
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values($ids);
    }

    /**
     * @param  list<int>  $ids
     */
    private function renumber(array $ids): void
    {
        foreach ($ids as $position => $id) {
            Deal::query()
                ->whereKey($id)
                ->where('position', '!=', $position)
                ->update(['position' => $position]);
        }
    }
}
