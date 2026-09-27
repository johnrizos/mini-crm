<?php

namespace App\Http\Controllers;

use App\Actions\Deals\DealBoard;
use App\Enums\DealStage;
use App\Http\Requests\Crm\DealRequest;
use App\Http\Requests\Crm\MoveDealRequest;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DealController extends Controller
{
    /** Won and lost columns only show recent history. */
    private const CLOSED_WINDOW_DAYS = 90;

    public function __construct(private readonly DealBoard $board) {}

    public function index(): Response
    {
        $user = $this->user();

        $deals = $user->deals()
            ->with(['contact', 'company'])
            ->where(fn (Builder $q) => $q
                ->whereIn('stage', DealStage::open())
                ->orWhere('closed_at', '>=', now()->subDays(self::CLOSED_WINDOW_DAYS)))
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return Inertia::render('deals/Index', [
            'deals' => DealResource::collection($deals),
            'contacts' => $user->contacts()->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'company_id'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_name, 'company_id' => $c->company_id]),
            'companies' => $user->companies()->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'closedWindowDays' => self::CLOSED_WINDOW_DAYS,
        ]);
    }

    public function store(DealRequest $request): RedirectResponse
    {
        $attributes = $request->dealAttributes();

        $deal = $this->user()->deals()->make($attributes);
        $this->board->appendTo($deal, $attributes['stage']);
        $deal->save();

        $this->toast("Deal \"{$deal->title}\" added.");

        return back();
    }

    public function update(DealRequest $request, Deal $deal): RedirectResponse
    {
        Gate::authorize('update', $deal);

        $attributes = $request->dealAttributes();
        $from = $deal->stage;

        DB::transaction(function () use ($deal, $attributes, $from) {
            $deal->fill($attributes);
            if ($from !== $attributes['stage']) {
                $this->board->appendTo($deal, $attributes['stage']);
            }
            $deal->save();

            if ($from !== $attributes['stage']) {
                $this->board->closeGap($deal->user_id, $from);
            }
        });

        $this->toast('Deal updated.');

        return back();
    }

    public function move(MoveDealRequest $request, Deal $deal): RedirectResponse
    {
        Gate::authorize('update', $deal);

        $stage = $request->enum('stage', DealStage::class) ?? $deal->stage;
        $this->board->move($deal, $stage, $request->integer('position'));

        return back();
    }

    public function destroy(Deal $deal): RedirectResponse
    {
        Gate::authorize('delete', $deal);

        DB::transaction(function () use ($deal) {
            $deal->delete();
            $this->board->closeGap($deal->user_id, $deal->stage);
        });

        $this->toast("Deal \"{$deal->title}\" deleted.");

        return back();
    }
}
