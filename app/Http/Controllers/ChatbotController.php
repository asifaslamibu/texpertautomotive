<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Support\ChatbotAssistant;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        if (! ChatbotAssistant::enabled()) {
            return response()->json([
                'reply' => 'Our chat assistant is currently unavailable. Please email us at '
                    .Setting::get('contact_email', 'sales@texpertdeals.us').' and we\'ll be glad to help.',
            ]);
        }

        $data = $request->validate([
            'messages' => ['required', 'array', 'min:1', 'max:30'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string', 'max:2000'],
        ]);

        $messages = collect($data['messages'])
            ->map(fn ($m) => ['role' => $m['role'], 'content' => $m['content']])
            ->values()
            ->all();

        // must end on a user turn for the API
        if (end($messages)['role'] !== 'user') {
            return response()->json(['reply' => 'Please type a message.']);
        }

        $reply = (new ChatbotAssistant)->reply($messages);

        return response()->json(['reply' => $reply]);
    }
}
