<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageMail;
use App\Mail\RequestConfirmationMail;
use App\Models\Enquiry;
use App\Support\Notify;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return view('pages.contact');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:160'],
            'phone'   => ['nullable', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        Enquiry::create([
            'type' => 'contact',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
        ]);

        Notify::send(
            adminMailable: new ContactMessageMail($data),
            customerEmail: $data['email'],
            confirmation: new RequestConfirmationMail($data['name'], 'message', [
                ['Subject', $data['subject'] ?? ''],
                ['Message', $data['message']],
            ], 'contact'),
            logLabel: 'Contact form submission',
            logData: $data,
        );

        return redirect()->route('contact.index')
            ->with('status', 'Thanks '.$data['name'].'! Your message has been sent — our team will get back to you shortly.');
    }
}
