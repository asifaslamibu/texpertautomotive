<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageStore;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private array $keys = [
        'site_name', 'header_logo', 'footer_logo', 'favicon', 'email_logo', 'email_footer_note', 'site_description',
        'email_confirmation_subject', 'email_confirmation_body', 'email_signoff',
        'color_primary', 'color_secondary',
        'announcement_enabled', 'announcement', 'announcement_code', 'free_shipping_note', 'promo_banner_enabled',
        'default_product_tag',
        'hero_badge', 'hero_title', 'hero_subtitle', 'hero_overlay', 'hero_slider_duration',
        // home "why shop with us" band
        'why_bg', 'why_icon_1', 'why_icon_2', 'why_icon_3', 'why_icon_4', 'why_icon_5',
        'why_img_1', 'why_img_2', 'why_img_3', 'why_img_4', 'why_img_5',
        'contact_phone', 'toll_free', 'contact_email', 'contact_address', 'newsletter_code',
        'po_distributor_name', 'po_distributor_email',
        'recaptcha_enabled', 'recaptcha_site_key', 'recaptcha_secret_key',
        'chatbot_enabled', 'chatbot_api_key', 'chatbot_model', 'chatbot_greeting',
        'social_facebook', 'social_instagram', 'social_linkedin', 'whatsapp',
        // marketing & tracking (ads)
        'ga4_id', 'gtm_id', 'google_ads_id', 'google_ads_purchase_label',
        'meta_pixel_id', 'tiktok_pixel_id', 'clarity_id', 'head_code', 'body_code', 'og_image',
        // payments (Stripe)
        'stripe_enabled', 'stripe_mode', 'stripe_currency', 'stripe_checkout_type',
        'stripe_test_key', 'stripe_test_secret', 'stripe_test_webhook_secret',
        'stripe_live_key', 'stripe_live_secret', 'stripe_live_webhook_secret',
        // shipping
        'shipping_enabled',
        // shipping (UPS)
        'ups_enabled', 'ups_env', 'ups_client_id', 'ups_client_secret', 'ups_account',
        'ups_ship_from_name', 'ups_ship_from_address', 'ups_ship_from_city',
        'ups_ship_from_state', 'ups_ship_from_zip', 'ups_ship_from_country',
        // shipping (Shippo)
        'shippo_enabled', 'shippo_mode', 'shippo_test_key', 'shippo_live_key',
    ];

    public function edit()
    {
        $settings = collect($this->keys)->mapWithKeys(fn ($k) => [$k => Setting::get($k)]);

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_description' => ['nullable', 'string', 'max:600'],
            'color_primary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_secondary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'header_logo' => ['nullable', 'string', 'max:300'],
            'footer_logo' => ['nullable', 'string', 'max:300'],
            'header_logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:2048'],
            'footer_logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:2048'],
            'favicon' => ['nullable', 'string', 'max:300'],
            'favicon_file' => ['nullable', 'file', 'mimes:png,ico,svg,jpg,jpeg,webp,gif', 'max:1024'],
            'email_logo' => ['nullable', 'string', 'max:300'],
            'email_logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'email_footer_note' => ['nullable', 'string', 'max:300'],
            'email_confirmation_subject' => ['nullable', 'string', 'max:200'],
            'email_confirmation_body' => ['nullable', 'string', 'max:2000'],
            'email_signoff' => ['nullable', 'string', 'max:200'],
            'announcement_enabled' => ['nullable', 'in:0,1'],
            'promo_banner_enabled' => ['nullable', 'in:0,1'],
            'announcement' => ['nullable', 'string', 'max:300'],
            'announcement_code' => ['nullable', 'string', 'max:40'],
            'free_shipping_note' => ['nullable', 'string', 'max:160'],
            'default_product_tag' => ['nullable', 'string', 'max:60'],
            'hero_badge' => ['nullable', 'string', 'max:120'],
            'hero_title' => ['required', 'string', 'max:200'],
            'hero_subtitle' => ['nullable', 'string', 'max:400'],
            'hero_overlay' => ['nullable', 'integer', 'min:0', 'max:90'],
            'hero_slider_duration' => ['nullable', 'integer', 'min:2', 'max:30'],
            'contact_phone' => ['nullable', 'string', 'max:60'],
            'toll_free' => ['nullable', 'string', 'max:60'],
            'newsletter_code' => ['nullable', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'po_distributor_name' => ['nullable', 'string', 'max:160'],
            'po_distributor_email' => ['nullable', 'email', 'max:160'],
            'recaptcha_enabled' => ['nullable', 'in:0,1'],
            'recaptcha_site_key' => ['nullable', 'string', 'max:120'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:120'],
            'chatbot_enabled' => ['nullable', 'in:0,1'],
            'chatbot_api_key' => ['nullable', 'string', 'max:200'],
            'chatbot_model' => ['nullable', 'string', 'max:60'],
            'chatbot_greeting' => ['nullable', 'string', 'max:300'],
            'contact_address' => ['nullable', 'string', 'max:200'],
            'social_facebook' => ['nullable', 'url', 'max:300'],
            'social_instagram' => ['nullable', 'url', 'max:300'],
            'social_linkedin' => ['nullable', 'url', 'max:300'],
            'whatsapp' => ['nullable', 'string', 'max:60'],
            'ga4_id' => ['nullable', 'string', 'max:40'],
            'gtm_id' => ['nullable', 'string', 'max:40'],
            'google_ads_id' => ['nullable', 'string', 'max:40'],
            'google_ads_purchase_label' => ['nullable', 'string', 'max:80'],
            'meta_pixel_id' => ['nullable', 'string', 'max:40'],
            'tiktok_pixel_id' => ['nullable', 'string', 'max:40'],
            'clarity_id' => ['nullable', 'string', 'max:40'],
            'head_code' => ['nullable', 'string', 'max:8000'],
            'body_code' => ['nullable', 'string', 'max:8000'],
            'og_image' => ['nullable', 'string', 'max:300'],
            'og_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // the band's own fields: the text inputs need rules too, or validate() drops them
            'why_bg' => ['nullable', 'string', 'max:300'],
            'why_bg_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'why_icon_1' => ['nullable', 'string', 'max:40'],
            'why_icon_2' => ['nullable', 'string', 'max:40'],
            'why_icon_3' => ['nullable', 'string', 'max:40'],
            'why_icon_4' => ['nullable', 'string', 'max:40'],
            'why_icon_5' => ['nullable', 'string', 'max:40'],
            'why_img_1' => ['nullable', 'string', 'max:300'],
            'why_img_2' => ['nullable', 'string', 'max:300'],
            'why_img_3' => ['nullable', 'string', 'max:300'],
            'why_img_4' => ['nullable', 'string', 'max:300'],
            'why_img_5' => ['nullable', 'string', 'max:300'],
            'why_img_1_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024'],
            'why_img_2_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024'],
            'why_img_3_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024'],
            'why_img_4_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024'],
            'why_img_5_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:1024'],
            'stripe_enabled' => ['nullable', 'in:0,1'],
            'stripe_mode' => ['nullable', 'in:test,live'],
            'stripe_checkout_type' => ['nullable', 'in:embedded,redirect'],
            'stripe_currency' => ['nullable', 'string', 'max:10'],
            'stripe_test_key' => ['nullable', 'string', 'max:255'],
            'stripe_test_secret' => ['nullable', 'string', 'max:255'],
            'stripe_test_webhook_secret' => ['nullable', 'string', 'max:255'],
            'stripe_live_key' => ['nullable', 'string', 'max:255'],
            'stripe_live_secret' => ['nullable', 'string', 'max:255'],
            'stripe_live_webhook_secret' => ['nullable', 'string', 'max:255'],
            'shipping_enabled' => ['nullable', 'in:0,1'],
            'ups_enabled' => ['nullable', 'in:0,1'],
            'ups_env' => ['nullable', 'in:sandbox,production'],
            'ups_client_id' => ['nullable', 'string', 'max:255'],
            'ups_client_secret' => ['nullable', 'string', 'max:255'],
            'ups_account' => ['nullable', 'string', 'max:20'],
            'ups_ship_from_name' => ['nullable', 'string', 'max:120'],
            'ups_ship_from_address' => ['nullable', 'string', 'max:200'],
            'ups_ship_from_city' => ['nullable', 'string', 'max:80'],
            'ups_ship_from_state' => ['nullable', 'string', 'max:20'],
            'ups_ship_from_zip' => ['nullable', 'string', 'max:20'],
            'ups_ship_from_country' => ['nullable', 'string', 'max:2'],
            'shippo_enabled' => ['nullable', 'in:0,1'],
            'shippo_mode' => ['nullable', 'in:test,live'],
            'shippo_test_key' => ['nullable', 'string', 'max:255'],
            'shippo_live_key' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('header_logo_file')) {
            $data['header_logo'] = ImageStore::store($request->file('header_logo_file'), 'logos');
        }
        if ($request->hasFile('footer_logo_file')) {
            $data['footer_logo'] = ImageStore::store($request->file('footer_logo_file'), 'logos');
        }
        if ($request->hasFile('favicon_file')) {
            $data['favicon'] = ImageStore::store($request->file('favicon_file'), 'logos');
        }
        if ($request->hasFile('why_bg_file')) {
            $data['why_bg'] = ImageStore::store($request->file('why_bg_file'), 'banners');
        }
        foreach (range(1, 5) as $n) {
            if ($request->hasFile("why_img_{$n}_file")) {
                $data["why_img_{$n}"] = ImageStore::store($request->file("why_img_{$n}_file"), 'icons');
            }
        }
        if ($request->hasFile('og_image_file')) {
            $data['og_image'] = ImageStore::store($request->file('og_image_file'), 'logos');
        }
        if ($request->hasFile('email_logo_file')) {
            $data['email_logo'] = ImageStore::store($request->file('email_logo_file'), 'logos');
        }

        unset($data['header_logo_file'], $data['footer_logo_file'], $data['favicon_file'], $data['og_image_file'], $data['email_logo_file'], $data['why_bg_file']);
        foreach (range(1, 5) as $n) {
            unset($data["why_img_{$n}_file"]);
        }

        // Stripe/payment settings are master-admin only — drop them for anyone else.
        if (! $request->user()?->isMaster()) {
            $data = collect($data)->reject(fn ($v, $k) => str_starts_with($k, 'stripe_'))->all();
        }

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        return back()->with('status', 'Settings saved. Storefront updated.');
    }
}
