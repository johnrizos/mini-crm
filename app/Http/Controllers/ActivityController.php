<?php

namespace App\Http\Controllers;

use App\Http\Requests\Crm\ActivityRequest;
use App\Models\Activity;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ActivityController extends Controller
{
    public function store(ActivityRequest $request, Contact $contact): RedirectResponse
    {
        Gate::authorize('update', $contact);

        $activity = new Activity([
            ...$request->validated(),
            'happened_at' => $request->date('happened_at') ?? now(),
        ]);
        $activity->user()->associate($this->user());
        $activity->contact()->associate($contact);
        $activity->save();

        $this->toast("{$activity->type->label()} logged.");

        return back();
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        Gate::authorize('delete', $activity);

        $activity->delete();

        return back();
    }
}
