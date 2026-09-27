<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Enums\DealStage;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\DealResource;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = $this->user();
        $open = DealStage::open();

        $byStage = $user->deals()
            ->toBase()
            ->selectRaw('stage, count(*) as deals, coalesce(sum(value_cents), 0) as value_cents')
            ->whereIn('stage', array_map(fn ($s) => $s->value, $open))
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $pipeline = array_map(fn (DealStage $stage) => [
            'stage' => $stage,
            'deals' => (int) ($byStage[$stage->value]->deals ?? 0),
            'value_cents' => (int) ($byStage[$stage->value]->value_cents ?? 0),
        ], $open);

        $statusCounts = $user->contacts()->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Win rate over deals closed in the last 90 days.
        $closed = $user->deals()->toBase()
            ->where('closed_at', '>=', now()->subDays(90))
            ->selectRaw('stage, count(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');
        $won = (int) ($closed[DealStage::Won->value] ?? 0);
        $lost = (int) ($closed[DealStage::Lost->value] ?? 0);

        return Inertia::render('Dashboard', [
            'stats' => [
                'contacts' => (int) $statusCounts->sum(),
                'companies' => $user->companies()->count(),
                'open_deals' => array_sum(array_column($pipeline, 'deals')),
                'pipeline_cents' => array_sum(array_column($pipeline, 'value_cents')),
                'won_this_month_cents' => (int) $user->deals()
                    ->where('stage', DealStage::Won)
                    ->where('closed_at', '>=', now()->startOfMonth())
                    ->sum('value_cents'),
                'win_rate' => $won + $lost > 0 ? round($won / ($won + $lost) * 100) : null,
            ],
            'pipeline' => $pipeline,
            'contactsByStatus' => array_map(fn (ContactStatus $status) => [
                'status' => $status,
                'total' => (int) ($statusCounts[$status->value] ?? 0),
            ], ContactStatus::cases()),
            'closingSoon' => DealResource::collection(
                $user->deals()
                    ->with(['contact', 'company'])
                    ->whereIn('stage', $open)
                    ->whereBetween('expected_close_date', [now()->startOfDay(), now()->addDays(14)->endOfDay()])
                    ->orderBy('expected_close_date')
                    ->limit(5)
                    ->get(),
            ),
            'recentActivities' => ActivityResource::collection(
                $user->activities()->with(['contact', 'deal'])->latest('happened_at')->limit(6)->get(),
            ),
        ]);
    }
}
