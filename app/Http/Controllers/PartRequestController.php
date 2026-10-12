<?php

namespace App\Http\Controllers;

use App\Mail\PartRequestMail;
use App\Mail\RequestConfirmationMail;
use App\Models\Enquiry;
use App\Support\Notify;
use Illuminate\Http\Request;

class PartRequestController extends Controller
{
    public function index(Request $request)
    {
        return view('pages.part-request', [
            'prefillProduct' => (string) $request->query('product', ''),
            'prefillSku' => (string) $request->query('sku', ''),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:160'],
            'phone'     => ['nullable', 'string', 'max:40'],
            'part_name' => ['required', 'string', 'max:200'],
            'part_sku'  => ['nullable', 'string', 'max:120'],
            'brand'     => ['nullable', 'string', 'max:120'],
            'quantity'  => ['nullable', 'integer', 'min:1', 'max:100000'],
            'message'   => ['nullable', 'string', 'max:4000'],
        ]);

        Enquiry::create([
            'type' => 'part_request',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => 'Part: '.$data['part_name'],
            'message' => $data['message'] ?? null,
            'meta' => [
                'part_name' => $data['part_name'],
                'part_sku' => $data['part_sku'] ?? null,
                'brand' => $data['brand'] ?? null,
                'quantity' => $data['quantity'] ?? null,
            ],
        ]);

        Notify::send(
            adminMailable: new PartRequestMail($data),
            customerEmail: $data['email'],
            confirmation: new RequestConfirmationMail($data['name'], 'part request', [
                ['Part name', $data['part_name']],
                ['Part # / SKU', $data['part_sku'] ?? ''],
                ['Brand', $data['brand'] ?? ''],
                ['Quantity', $data['quantity'] ?? ''],
            ], 'part_request'),
            logLabel: 'Part request submission',
            logData: $data,
        );

        return redirect()->route('part-request')
            ->with('status', 'Thanks '.$data['name'].'! Your part request has been received — our team will get back to you shortly.');
    }
}
