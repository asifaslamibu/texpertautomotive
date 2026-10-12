<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PurchaseOrderMail;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PurchaseOrderController extends Controller
{
    use \App\Http\Controllers\Concerns\SortsRecords;

    public function index(Request $request)
    {
        $query = PurchaseOrder::with('order', 'distributor');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where(function ($w) use ($request) {
                $w->where('po_number', 'like', "%{$request->q}%")
                    ->orWhere('distributor_name', 'like', "%{$request->q}%")
                    ->orWhere('distributor_email', 'like', "%{$request->q}%");
            });
        }

        $this->applySort($query, $request, ['po_number', 'distributor_name', 'po_date', 'status'], 'id', 'desc');

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.purchase-orders.index', compact('orders'));
    }

    /** Delete several purchase orders at once. */
    public function bulkDestroy(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $n = PurchaseOrder::whereIn('id', $ids)->delete();

        return back()->with('status', $n.' purchase order(s) deleted.');
    }

    public function create()
    {
        $po = new PurchaseOrder([
            'po_number' => PurchaseOrder::nextNumber(),
            'po_date' => now(),
            'items' => [],
        ]);

        return view('admin.purchase-orders.form', ['po' => $po]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['po_number'] = PurchaseOrder::nextNumber();
        $data['status'] = 'draft';

        $po = PurchaseOrder::create($data);

        return redirect()
            ->route('admin.purchase-orders.show', $po)
            ->with('status', 'Purchase order '.$po->po_number.' created.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('order', 'distributor');
        $distributors = Distributor::where('active', true)->orderByDesc('is_default')->orderBy('name')->get();

        return view('admin.purchase-orders.show', ['po' => $purchaseOrder, 'distributors' => $distributors]);
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        return view('admin.purchase-orders.form', ['po' => $purchaseOrder]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->update($this->validated($request));

        return redirect()
            ->route('admin.purchase-orders.show', $purchaseOrder)
            ->with('status', 'Purchase order '.$purchaseOrder->po_number.' updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $number = $purchaseOrder->po_number;
        $purchaseOrder->delete();

        return redirect()
            ->route('admin.purchase-orders.index')
            ->with('status', "Purchase order {$number} deleted.");
    }

    /** Autocomplete source for the manual PO form — search products by name or SKU. */
    public function productSearch(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $products = \App\Models\Product::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['name', 'sku', 'price']);

        return response()->json(
            $products->map(fn ($p) => [
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) $p->price,
            ])
        );
    }

    /** Stream the PO as a PDF (inline preview or download). */
    public function pdf(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (! $this->pdfEngineReady()) {
            return redirect()
                ->route('admin.purchase-orders.show', $purchaseOrder)
                ->with('error', 'PDF engine is not installed on this server yet. Run "composer require barryvdh/laravel-dompdf" on the server.');
        }

        $pdf = $this->buildPdf($purchaseOrder);
        $file = $purchaseOrder->po_number.'.pdf';

        return $request->boolean('download')
            ? $pdf->download($file)
            : $pdf->stream($file);
    }

    /** Generate a draft purchase order from an existing customer order. */
    public function generateFromOrder(Order $order)
    {
        $po = $order->purchaseOrder ?: PurchaseOrder::fromOrder($order);

        return redirect()
            ->route('admin.purchase-orders.show', $po)
            ->with('status', 'Purchase order '.$po->po_number.' ready for '.$order->order_number.'.');
    }

    /** One-click: approve the PO and email its PDF to the chosen distributor. */
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Pick the recipient: the one chosen on the form, else the PO's assigned distributor,
        // else the default distributor, else the legacy Settings fallback.
        $distributor = $request->filled('distributor_id') ? Distributor::find($request->distributor_id) : null;
        $distributor = $distributor ?: $purchaseOrder->distributor ?: Distributor::default();

        $email = $distributor?->email ?: trim((string) Setting::get('po_distributor_email'));
        $name = $distributor?->name ?: (trim((string) Setting::get('po_distributor_name')) ?: 'Distributor');

        if ($email === '') {
            return back()->with('error', 'Add a distributor (with an email) first — Purchase Orders → Distributors.');
        }
        if (! $this->pdfEngineReady()) {
            return back()->with('error', 'PDF engine is not installed on this server yet. Run "composer require barryvdh/laravel-dompdf" on the server.');
        }

        try {
            $pdf = $this->buildPdf($purchaseOrder)->output();
            Mail::to($email)->send(new PurchaseOrderMail($purchaseOrder, $pdf, $name));

            $purchaseOrder->update([
                'distributor_id' => $distributor?->id ?? $purchaseOrder->distributor_id,
                'status' => 'sent',
                'sent_at' => now(),
                'approved_at' => now(),
            ]);

            return back()->with('status', 'Approved — purchase order emailed to '.$name.' ('.$email.').');
        } catch (\Throwable $e) {
            Log::warning('PO approve/email failed for '.$purchaseOrder->po_number.': '.$e->getMessage());

            return back()->with('error', 'Could not send the email: '.$e->getMessage());
        }
    }

    /** Export all purchase orders as a CSV (opens in Excel). */
    public function export(Request $request)
    {
        $filename = 'purchase-orders-'.now()->format('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'PO Number', 'From Order', 'Date', 'Customer', 'Customer Email',
                'Distributor', 'Items', 'Subtotal', 'Tax', 'Shipping', 'Total',
                'Status', 'Approved On',
            ]);

            PurchaseOrder::with('order', 'distributor')->latest('id')->chunk(500, function ($pos) use ($out) {
                foreach ($pos as $po) {
                    fputcsv($out, [
                        $po->po_number,
                        $po->order?->order_number ?? '',
                        optional($po->po_date)->format('Y-m-d'),
                        $po->distributor_name,
                        $po->distributor_email,
                        $po->distributor?->name ?? '',
                        count($po->lineItems()),
                        number_format($po->subtotal, 2, '.', ''),
                        number_format((float) $po->tax, 2, '.', ''),
                        number_format((float) $po->shipping, 2, '.', ''),
                        number_format($po->total, 2, '.', ''),
                        $po->approved_at ? 'Approved' : ucfirst($po->status),
                        optional($po->approved_at)->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, $headers);
    }

    /** Email the PO PDF to the distributor. */
    public function send(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        if (! $this->pdfEngineReady()) {
            return back()->with('error', 'PDF engine is not installed on this server yet. Run "composer require barryvdh/laravel-dompdf" on the server.');
        }

        try {
            $pdf = $this->buildPdf($purchaseOrder)->output();
            Mail::to($request->email)->send(new PurchaseOrderMail($purchaseOrder, $pdf));

            $purchaseOrder->update(['status' => 'sent', 'sent_at' => now()]);

            return back()->with('status', 'Purchase order emailed to '.$request->email.'.');
        } catch (\Throwable $e) {
            Log::warning('PO email failed for '.$purchaseOrder->po_number.': '.$e->getMessage());

            return back()->with('error', 'Could not send the email: '.$e->getMessage());
        }
    }

    private function buildPdf(PurchaseOrder $po): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('purchase-orders.pdf', ['po' => $po])
            ->setPaper('a4', 'portrait');
    }

    /** Is the dompdf package available on this server? */
    private function pdfEngineReady(): bool
    {
        return class_exists(\Barryvdh\DomPDF\Facade\Pdf::class);
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'po_date' => ['required', 'date'],
            'distributor_name' => ['required', 'string', 'max:255'],
            'distributor_phone' => ['nullable', 'string', 'max:60'],
            'distributor_email' => ['nullable', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'shipping_address' => ['nullable', 'string', 'max:1000'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Keep only rows that have any content.
        $validated['items'] = collect($request->input('items', []))
            ->map(fn ($r) => [
                'description' => trim((string) ($r['description'] ?? '')),
                'qty' => $r['qty'] === '' || $r['qty'] === null ? null : (float) $r['qty'],
                'price' => $r['price'] === '' || $r['price'] === null ? null : (float) $r['price'],
                'discount' => $r['discount'] === '' || $r['discount'] === null ? null : (float) $r['discount'],
            ])
            ->filter(fn ($r) => $r['description'] !== '' || $r['price'])   // qty alone (default 1) isn't a real row
            ->values()
            ->all();

        $validated['tax'] = $validated['tax'] ?? 0;
        $validated['shipping'] = $validated['shipping'] ?? 0;

        return $validated;
    }
}
