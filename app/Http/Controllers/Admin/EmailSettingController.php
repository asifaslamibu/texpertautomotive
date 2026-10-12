<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageStore;
use Illuminate\Http\Request;

class EmailSettingController extends Controller
{
    private array $keys = [
        'email_logo', 'email_footer_note', 'email_signoff',
        'email_contact_subject', 'email_contact_body',
        'email_part_subject', 'email_part_body',
        'email_wholesale_subject', 'email_wholesale_body',
    ];

    public function edit()
    {
        $settings = collect($this->keys)->mapWithKeys(fn ($k) => [$k => Setting::get($k)]);

        return view('admin.emails.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'email_logo' => ['nullable', 'string', 'max:300'],
            'email_logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'email_footer_note' => ['nullable', 'string', 'max:300'],
            'email_signoff' => ['nullable', 'string', 'max:200'],
            'email_contact_subject' => ['nullable', 'string', 'max:200'],
            'email_contact_body' => ['nullable', 'string', 'max:2000'],
            'email_part_subject' => ['nullable', 'string', 'max:200'],
            'email_part_body' => ['nullable', 'string', 'max:2000'],
            'email_wholesale_subject' => ['nullable', 'string', 'max:200'],
            'email_wholesale_body' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->hasFile('email_logo_file')) {
            $data['email_logo'] = ImageStore::store($request->file('email_logo_file'), 'logos');
        }
        unset($data['email_logo_file']);

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        return back()->with('status', 'Email settings saved.');
    }
}
