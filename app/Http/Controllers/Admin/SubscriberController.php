<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriberController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscriber::query();
        if ($request->filled('q')) {
            $query->where('email', 'like', "%{$request->q}%");
        }

        $subscribers = $query->latest()->paginate(30)->withQueryString();

        return view('admin.subscribers.index', compact('subscribers'));
    }

    public function destroy(Subscriber $subscriber)
    {
        $subscriber->delete();

        return back()->with('status', 'Subscriber removed.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Source', 'Subscribed']);
            Subscriber::orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $s) {
                    fputcsv($out, [$s->email, $s->source, $s->created_at?->format('Y-m-d H:i')]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
