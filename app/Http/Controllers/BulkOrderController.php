<?php

namespace App\Http\Controllers;

use App\Mail\RequestConfirmationMail;
use App\Mail\WholesaleRequestMail;
use App\Models\Enquiry;
use App\Support\Notify;
use Illuminate\Http\Request;

class BulkOrderController extends Controller
{
    public function index()
    {
        return view('pages.bulk-order');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'company'  => ['nullable', 'string', 'max:160'],
            'email'    => ['required', 'email', 'max:160'],
            'phone'    => ['nullable', 'string', 'max:40'],
            'products' => ['nullable', 'string', 'max:1000'],  // products / categories of interest
            'volume'   => ['nullable', 'string', 'max:120'],   // estimated order size / monthly volume
            'net30'    => ['nullable', 'boolean'],
            'message'  => ['nullable', 'string', 'max:4000'],
        ]);
        $data['net30'] = $request->boolean('net30');

        Enquiry::create([
            'type' => 'wholesale',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => !empty($data['company']) ? 'Company: '.$data['company'] : 'Wholesale request',
            'message' => $data['message'] ?? null,
            'meta' => [
                'company' => $data['company'] ?? null,
                'products' => $data['products'] ?? null,
                'volume' => $data['volume'] ?? null,
                'net30' => $data['net30'],
            ],
        ]);

        Notify::send(
            adminMailable: new WholesaleRequestMail($data),
            customerEmail: $data['email'],
            confirmation: new RequestConfirmationMail($data['name'], 'wholesale request', [
                ['Company', $data['company'] ?? ''],
                ['Products / categories', $data['products'] ?? ''],
                ['Estimated volume', $data['volume'] ?? ''],
                ['Wants Net-30', $data['net30'] ? 'Yes' : 'No'],
            ], 'wholesale'),
            logLabel: 'Bulk order / wholesale request',
            logData: $data,
        );

        return redirect()->route('bulk-order')
            ->with('status', 'Thanks '.$data['name'].'! Your wholesale request has been received — our team will send you a custom quote shortly.');
    }
}
