<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class RequestConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /** setting-prefix per form type */
    private const PREFIX = [
        'contact' => 'contact',
        'part_request' => 'part',
        'wholesale' => 'wholesale',
    ];

    /**
     * @param  string  $name         Recipient name
     * @param  string  $requestType  Human label, e.g. "message", "part request"
     * @param  array   $summary      [[label, value], ...]
     * @param  string  $typeKey      contact | part_request | wholesale
     */
    public function __construct(
        public string $name,
        public string $requestType,
        public array $summary = [],
        public string $typeKey = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->resolve('subject', 'We received your {type} — {site}'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.confirmation', with: [
            'name' => $this->name,
            'bodyText' => $this->resolve('body', "We’ve received your {type} and our team is on it. You’ll hear back from us shortly — usually within 1 business day."),
            'signoff' => $this->resolve('signoff', '— The {site} Team', shared: true),
            'summary' => $this->summary,
        ]);
    }

    /** Per-form setting → generic fallback → default, with placeholders replaced. */
    private function resolve(string $field, string $default, bool $shared = false): string
    {
        $value = null;
        if (! $shared && ($prefix = self::PREFIX[$this->typeKey] ?? null)) {
            $value = Setting::get("email_{$prefix}_{$field}");
        }
        $value = $value ?: Setting::get($shared ? "email_{$field}" : "email_confirmation_{$field}");
        $value = $value ?: $default;

        $site = Setting::get('site_name', 'Texpert Deals');

        return strtr($value, [
            '{name}' => (string) (Str::of($this->name)->before(' ') ?: $this->name),
            '{type}' => $this->requestType,
            '{site}' => $site,
        ]);
    }
}
