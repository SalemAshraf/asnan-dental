<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isOwner(), 403);

        return Inertia::render('Audit/Index', [
            'logs' => AuditLog::where('clinic_id', $request->user()->clinic_id)
                ->orderByDesc('id')->paginate(30),
        ]);
    }
}
