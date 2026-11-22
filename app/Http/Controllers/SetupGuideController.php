<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SetupGuideController extends Controller
{
    public function dismiss(Request $request)
    {
        $request->user()->clinic->forceFill(['setup_dismissed_at' => now()])->save();

        return back();
    }

    public function show(Request $request)
    {
        $request->user()->clinic->forceFill(['setup_dismissed_at' => null])->save();

        return back();
    }
}
