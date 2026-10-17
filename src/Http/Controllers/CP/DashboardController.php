<?php

namespace Arronrees\ContentGovernance\Http\Controllers\CP;

use Arronrees\ContentGovernance\Auth\Abilities;
use Arronrees\ContentGovernance\Dashboard\Dashboard;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Statamic;

class DashboardController extends CpController
{
    public function __invoke(Request $request, Dashboard $dashboard, Abilities $abilities)
    {
        abort_unless($abilities->view(User::current()), 403);

        $collections = $abilities->viewableCollections(User::current());
        $site = Site::multiEnabled() && Site::get($request->input('site') ?? '') ? $request->input('site') : null;

        return Inertia::render('content-governance::Dashboard', [
            'site' => $site,
            'sites' => Site::multiEnabled()
                ? Site::all()->map(fn ($site) => ['handle' => $site->handle(), 'name' => $site->name()])->values()->all()
                : [],
            'summary' => $dashboard->summary($site, $collections),
            'expiring' => $dashboard->expiringSoon($site, $collections),
            'activity' => $dashboard->recentActivity($site, $collections),
            'scheduler' => $dashboard->scheduler(),
            'reviewUrl' => cp_route('governance.records.index'),
            'dashboardUrl' => cp_route('governance.dashboard'),
            'proRequired' => ! Statamic::pro(),
        ]);
    }
}
