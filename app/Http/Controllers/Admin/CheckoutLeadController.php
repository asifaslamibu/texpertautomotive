<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckoutLead;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Checkouts people started — including the ones they never finished. */
class CheckoutLeadController extends Controller
{
    public function index(Request $request)
    {
        $query = CheckoutLead::with('order')->latest('updated_at');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('company', 'like', "%{$q}%"));
        }

        match ($request->get('status')) {
            'ordered' => $query->whereNotNull('order_id'),
            default => $query->whereNull('order_id'),   // abandoned is the useful default
        };

        return view('admin.checkout-leads.index', [
            'leads' => $query->paginate(30)->withQueryString(),
            'abandonedCount' => CheckoutLead::abandoned()->count(),
            'orderedCount' => CheckoutLead::whereNotNull('order_id')->count(),
        ]);
    }

    public function destroy(CheckoutLead $checkoutLead)
    {
        $checkoutLead->delete();

        return back()->with('status', 'Checkout removed.');
    }

    public function export(Request $request): StreamedResponse
    {
        $ordered = $request->get('status') === 'ordered';
        $filename = ($ordered ? 'checkouts-completed-' : 'checkouts-abandoned-').now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($ordered) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Name', 'Email', 'Phone', 'Company', 'Address', 'City', 'State', 'ZIP', 'Cart total', 'Items', 'Order', 'Last activity']);

            CheckoutLead::when($ordered, fn ($q) => $q->whereNotNull('order_id'), fn ($q) => $q->whereNull('order_id'))
                ->with('order')->orderByDesc('updated_at')->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $l) {
                        fputcsv($out, [
                            $l->name, $l->email, $l->phone, $l->company,
                            $l->address, $l->city, $l->state, $l->zip,
                            number_format((float) $l->cart_total, 2),
                            collect($l->items ?? [])->map(fn ($i) => ($i['qty'] ?? 1).'x '.($i['sku'] ?: $i['name'] ?? ''))->implode('; '),
                            $l->order?->order_number,
                            $l->updated_at?->format('Y-m-d H:i'),
                        ]);
                    }
                });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
