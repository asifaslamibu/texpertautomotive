<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    use \App\Http\Controllers\Concerns\SortsRecords;

    public function index(Request $request)
    {
        $type = $request->input('type');

        $query = Enquiry::query()
            ->when(in_array($type, array_keys(Enquiry::TYPES), true), fn ($q) => $q->where('type', $type));

        $this->applySort($query, $request, ['name', 'email', 'type', 'is_read', 'created_at'], 'created_at', 'desc');

        $enquiries = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => Enquiry::count(),
            'unread' => Enquiry::where('is_read', false)->count(),
        ];
        foreach (array_keys(Enquiry::TYPES) as $t) {
            $counts[$t] = Enquiry::where('type', $t)->count();
        }

        return view('admin.enquiries.index', compact('enquiries', 'counts', 'type'));
    }

    public function show(Enquiry $enquiry)
    {
        if (! $enquiry->is_read) {
            $enquiry->update(['is_read' => true]);
        }

        return view('admin.enquiries.show', compact('enquiry'));
    }

    public function toggleRead(Enquiry $enquiry)
    {
        $enquiry->update(['is_read' => ! $enquiry->is_read]);

        return back()->with('status', 'Enquiry marked as '.($enquiry->is_read ? 'read' : 'unread').'.');
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();

        return redirect()->route('admin.enquiries.index')->with('status', 'Enquiry deleted.');
    }

    public function bulkDestroy(\Illuminate\Http\Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $n = \App\Models\Enquiry::whereIn('id', $ids)->delete();

        return back()->with('status', $n.' enquiry(ies) deleted.');
    }
}
