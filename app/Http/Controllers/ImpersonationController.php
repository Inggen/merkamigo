<?php

namespace App\Http\Controllers;

use App\Domain\Platform\Actions\StopUserImpersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function stop(Request $request, StopUserImpersonation $stopUserImpersonation): RedirectResponse
    {
        $stopUserImpersonation->handle($request->user());

        return redirect()->route('filament.admin.resources.users.index');
    }
}
