<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    /** Owner sets a dentist's commission rate (basis points, 10000 = 100%). */
    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->isOwner(), 403);
        abort_unless($user->role === User::ROLE_DENTIST, 422);

        $data = $request->validate([
            'commission_bp' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $user->update(['commission_bp' => $data['commission_bp']]);

        return back();
    }
}
