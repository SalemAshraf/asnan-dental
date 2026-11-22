<?php

namespace App\Http\Controllers;

use App\Models\ReminderMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReminderMessageController extends Controller
{
    public function update(Request $request, ReminderMessage $reminder)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['sent', 'skipped'])],
        ]);

        $reminder->update([
            'status' => $data['status'],
            'sent_at' => $data['status'] === 'sent' ? now() : null,
            'sent_by' => $data['status'] === 'sent' ? $request->user()->id : null,
        ]);

        return back();
    }
}
