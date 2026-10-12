<?php

namespace App\Http\Controllers;

use App\Mail\RequestConfirmationMail;
use App\Mail\ReturnRequestMail;
use App\Models\Enquiry;
use App\Support\ImageStore;
use App\Support\Notify;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    /** Reasons offered in the form; also the whitelist the request is validated against. */
    public const REASONS = [
        'Incorrect item was received',
        'Item arrived damaged',
        'Item is defective',
        'Ordered by mistake',
        'No longer needed',
        'Item does not fit / wrong size',
        'Other (explain in comments)',
    ];

    public const CARRIERS = ['UPS', 'FedEx', 'USPS', 'DHL', 'Freight / LTL', 'Other'];

    public function index(Request $request)
    {
        return view('pages.return-request', [
            'prefillOrder' => (string) $request->query('order', ''),
            'prefillSku' => (string) $request->query('sku', ''),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:60'],
            'name'         => ['required', 'string', 'max:120'],
            'email'        => ['required', 'email', 'max:160'],
            'phone'        => ['required', 'string', 'max:40'],
            'product_sku'  => ['required', 'string', 'max:120'],
            'quantity'     => ['required', 'integer', 'min:1', 'max:10000'],
            'reason'       => ['required', 'string', 'in:'.implode(',', self::REASONS)],
            'carrier'      => ['required', 'string', 'in:'.implode(',', self::CARRIERS)],
            'images'       => ['nullable', 'array', 'max:5'],
            'images.*'     => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'message'      => ['nullable', 'string', 'max:4000'],
            'certified'    => ['accepted'],
        ], [
            'certified.accepted' => 'Please confirm the product is new, unused and has never been installed.',
        ]);

        // Photographs are often what decides a damage/defect claim, so keep them
        // with the enquiry rather than asking the customer to email them later.
        $images = [];
        foreach ((array) $request->file('images', []) as $file) {
            $images[] = url(ImageStore::store($file, 'returns'));
        }
        $data['images'] = $images;
        $data['certified'] = true;

        Enquiry::create([
            'type' => 'return_request',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'subject' => 'Return request — order '.$data['order_number'],
            'message' => $data['message'] ?? null,
            'meta' => [
                'order_number' => $data['order_number'],
                'product_sku' => $data['product_sku'],
                'quantity' => $data['quantity'],
                'reason' => $data['reason'],
                'carrier' => $data['carrier'],
                'images' => $images,
                'certified' => true,
            ],
        ]);

        Notify::send(
            adminMailable: new ReturnRequestMail($data),
            customerEmail: $data['email'],
            confirmation: new RequestConfirmationMail($data['name'], 'return request', [
                ['Order number', $data['order_number']],
                ['Product SKU', $data['product_sku']],
                ['Quantity', $data['quantity']],
                ['Reason', $data['reason']],
            ], 'return_request'),
            logLabel: 'Return request submission',
            logData: $data,
        );

        return redirect()->route('return-request')
            ->with('status', 'Thanks '.$data['name'].'! Your return request has been received — our team will review it and email your return authorization.');
    }
}
